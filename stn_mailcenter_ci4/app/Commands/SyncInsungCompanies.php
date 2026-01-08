<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\InsungApiService;
use App\Models\InsungApiListModel;
use App\Models\InsungCompanyListModel;

class SyncInsungCompanies extends BaseCommand
{
    protected $group       = 'insung';
    protected $name        = 'insung:sync-companies';
    protected $description = '인성 API 거래처 목록을 동기화합니다.';
    protected $usage       = 'insung:sync-companies [api_idx] [cc_code]';
    protected $arguments   = [
        'api_idx' => 'API 인덱스 (tbl_api_list.idx)',
        'cc_code' => '콜센터 코드 (선택사항)'
    ];

    public function run(array $params)
    {
        // 필수 파라미터 확인
        $apiIdx = $params[0] ?? null;
        $ccCode = $params[1] ?? null;

        if (empty($apiIdx)) {
            CLI::error('필수 파라미터가 누락되었습니다.');
            CLI::write('사용법: ' . $this->usage, 'yellow');
            return;
        }

        CLI::write("인성 API 거래처 목록 동기화를 시작합니다...", 'yellow');
        CLI::write("API 인덱스: {$apiIdx}", 'green');
        if ($ccCode) {
            CLI::write("콜센터 코드: {$ccCode}", 'green');
        }

        try {
            // API 정보 조회
            $apiListModel = new InsungApiListModel();
            $apiInfo = $apiListModel->find($apiIdx);

            if (!$apiInfo || empty($apiInfo['token'])) {
                CLI::error("API 정보를 찾을 수 없거나 토큰이 없습니다.");
                return;
            }

            $mCode = $apiInfo['mcode'] ?? '';
            $ccCodeFromApi = $apiInfo['cccode'] ?? '';
            $token = $apiInfo['token'] ?? '';
            
            // cc_code 파라미터가 있으면 우선 사용
            $targetCcCode = $ccCode ?? $ccCodeFromApi;

            if (empty($mCode) || empty($targetCcCode) || empty($token)) {
                CLI::error("API 정보가 불완전합니다. m_code, cc_code, token이 필요합니다.");
                return;
            }

            // 인성 API 서비스 초기화
            $insungApiService = new InsungApiService();
            $db = \Config\Database::connect();

            CLI::write("거래처 목록 조회 중...", 'yellow');

            // 첫 번째 페이지 호출하여 total_page 확인 (limit=100으로 설정)
            $firstPageResult = $insungApiService->getCompanyList($mCode, $targetCcCode, $token, '', '', 1, 100, $apiIdx);

            if (!$firstPageResult) {
                CLI::error("거래처 목록 조회 실패");
                return;
            }

            $companyData = $firstPageResult;
            $code = '';
            $totalPage = 1;
            $totalRecord = 0;

            // 응답 구조 파싱
            if (is_object($companyData) && isset($companyData->Result)) {
                if (isset($companyData->Result[0]->result_info[0]->code)) {
                    $code = $companyData->Result[0]->result_info[0]->code;
                } elseif (isset($companyData->Result[0]->code)) {
                    $code = $companyData->Result[0]->code;
                }
                
                if (isset($companyData->Result[1])) {
                    $pageInfoData = $companyData->Result[1];
                    if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                        $pageInfo = $pageInfoData->page_info[0];
                        $totalPage = (int)($pageInfo->total_page ?? 1);
                        $totalRecord = (int)($pageInfo->total_record ?? 0);
                    } elseif (isset($pageInfoData->total_page)) {
                        $totalPage = (int)$pageInfoData->total_page;
                        $totalRecord = (int)($pageInfoData->total_record ?? 0);
                    }
                }
            } elseif (is_array($companyData)) {
                if (isset($companyData[0])) {
                    if (is_object($companyData[0])) {
                        if (isset($companyData[0]->result_info[0]->code)) {
                            $code = $companyData[0]->result_info[0]->code;
                        } elseif (isset($companyData[0]->code)) {
                            $code = $companyData[0]->code;
                        }
                    } elseif (is_array($companyData[0])) {
                        $code = $companyData[0]['code'] ?? '';
                    }
                }
                
                if (isset($companyData[1])) {
                    $pageInfoData = is_object($companyData[1]) ? $companyData[1] : (object)$companyData[1];
                    if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                        $pageInfo = $pageInfoData->page_info[0];
                        $totalPage = (int)($pageInfo->total_page ?? 1);
                        $totalRecord = (int)($pageInfo->total_record ?? 0);
                    } elseif (isset($pageInfoData->total_page)) {
                        $totalPage = (int)$pageInfoData->total_page;
                        $totalRecord = (int)($pageInfoData->total_record ?? 0);
                    }
                }
            }

            if ($code !== '1000') {
                CLI::error("API 응답 오류: code={$code}");
                return;
            }

            CLI::write("총 페이지: {$totalPage}, 총 레코드: {$totalRecord}", 'green');

            // cc_idx 조회 (cc_code로)
            $ccBuilder = $db->table('tbl_cc_list');
            $ccBuilder->select('idx');
            $ccBuilder->where('cc_code', $targetCcCode);
            $ccBuilder->where('cc_apicode', $apiIdx);
            $ccQuery = $ccBuilder->get();
            $ccResult = $ccQuery ? $ccQuery->getRowArray() : null;
            $ccIdx = $ccResult['idx'] ?? null;

            if (empty($ccIdx)) {
                CLI::error("콜센터 정보를 찾을 수 없습니다: cc_code={$targetCcCode}, api_idx={$apiIdx}");
                return;
            }

            $syncedCount = 0;
            $updatedCount = 0;
            $insertedCount = 0;

            // 모든 페이지 순회
            for ($page = 1; $page <= $totalPage; $page++) {
                CLI::write("페이지 {$page}/{$totalPage} 처리 중...", 'yellow');
                
                // 첫 번째 페이지는 이미 호출했으므로 재사용, 나머지는 새로 호출
                if ($page === 1) {
                    $pageResult = $firstPageResult;
                } else {
                    $pageResult = $insungApiService->getCompanyList($mCode, $targetCcCode, $token, '', '', $page, 100, $apiIdx);
                }

                if (!$pageResult) {
                    CLI::write("페이지 {$page} 조회 실패", 'red');
                    continue;
                }

                $pageData = $pageResult;
                $items = [];

                // 응답 구조 파싱
                if (is_object($pageData) && isset($pageData->Result)) {
                    $resultArray = is_array($pageData->Result) ? $pageData->Result : [$pageData->Result];
                    if (isset($resultArray[2]->items[0]->item)) {
                        $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
                    }
                } elseif (is_array($pageData) && isset($pageData[2])) {
                    if (isset($pageData[2]->items[0]->item)) {
                        $items = is_array($pageData[2]->items[0]->item) ? $pageData[2]->items[0]->item : [$pageData[2]->items[0]->item];
                    }
                }

                foreach ($items as $item) {
                    $compNo = $item->comp_no ?? '';
                    $corpName = $item->corp_name ?? '';
                    $owner = $item->owner ?? '';
                    $telNo = $item->tel_no ?? '';
                    $address = $item->adddress ?? '';
                    $staffName = $item->staff_name ?? '';
                    $credit = $item->credit ?? '3';

                    if (empty($compNo)) {
                        continue;
                    }

                    // 기존 거래처 확인
                    $compBuilder = $db->table('tbl_company_list');
                    $compBuilder->where('comp_code', $compNo);
                    $compQuery = $compBuilder->get();
                    $existingCompany = $compQuery ? $compQuery->getRowArray() : null;

                    $companyData = [
                        'comp_code' => $compNo,
                        'comp_name' => $corpName,
                        'cc_idx' => $ccIdx,
                        'comp_owner' => $owner,
                        'comp_tel' => $telNo,
                        'comp_addr' => $address,
                        'comp_type' => $credit,
                        'representative_name' => $owner,
                        'business_number' => $item->business_number ?? ''
                    ];

                    if ($existingCompany) {
                        // 업데이트
                        $compBuilder = $db->table('tbl_company_list');
                        $compBuilder->where('comp_code', $compNo);
                        $compBuilder->update($companyData);
                        $updatedCount++;
                    } else {
                        // 삽입
                        $db->table('tbl_company_list')->insert($companyData);
                        $insertedCount++;
                    }
                    $syncedCount++;
                }
            }

            CLI::write("동기화 완료!", 'green');
            CLI::write("총 처리: {$syncedCount}건 (신규: {$insertedCount}건, 업데이트: {$updatedCount}건)", 'green');

        } catch (\Exception $e) {
            CLI::error("오류 발생: " . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
        }
    }
}

