<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\InsungApiService;
use App\Models\InsungApiListModel;

class SyncInsungEmployees extends BaseCommand
{
    protected $group       = 'insung';
    protected $name        = 'insung:sync-employees';
    protected $description = '인성 API 직원 검색을 백그라운드로 처리합니다.';
    protected $usage       = 'insung:sync-employees [queue_idx]';
    protected $arguments   = [
        'queue_idx' => '큐 인덱스 (tbl_employee_search_queue.idx)'
    ];

    public function run(array $params)
    {
        // 필수 파라미터 확인
        $queueIdx = $params[0] ?? null;

        if (empty($queueIdx)) {
            CLI::error('필수 파라미터가 누락되었습니다.');
            CLI::write('사용법: ' . $this->usage, 'yellow');
            return;
        }

        CLI::write("인성 API 직원 검색 처리를 시작합니다...", 'yellow');
        CLI::write("큐 인덱스: {$queueIdx}", 'green');

        try {
            $db = \Config\Database::connect();
            
            // 테이블 존재 여부 확인 및 자동 마이그레이션
            if (!$db->tableExists('tbl_employee_search_queue') || !$db->tableExists('tbl_employee_search_results')) {
                CLI::write('필요한 테이블이 없습니다. 마이그레이션을 실행합니다...', 'yellow');
                
                // 마이그레이션 자동 실행 (MigrationRunner 사용)
                try {
                    $migrate = \Config\Services::migrations();
                    $migrate->setNamespace(null);
                    
                    if ($migrate->latest()) {
                        CLI::write('마이그레이션이 성공적으로 완료되었습니다.', 'green');
                    } else {
                        CLI::error('마이그레이션 실행 중 오류가 발생했습니다.');
                        CLI::write('수동으로 실행해주세요: php spark migrate', 'yellow');
                        return;
                    }
                } catch (\Exception $e) {
                    CLI::error('마이그레이션 실행 실패: ' . $e->getMessage());
                    CLI::write('수동으로 실행해주세요: php spark migrate', 'yellow');
                    return;
                }
            }
            
            // 큐 정보 조회
            $queueBuilder = $db->table('tbl_employee_search_queue');
            $queueBuilder->where('idx', $queueIdx);
            $queueQuery = $queueBuilder->get();
            $queueInfo = $queueQuery ? $queueQuery->getRowArray() : null;

            if (!$queueInfo) {
                CLI::error("큐 정보를 찾을 수 없습니다: queue_idx={$queueIdx}");
                return;
            }

            // 이미 처리 중이거나 완료된 경우
            if ($queueInfo['status'] === 'completed') {
                CLI::write("이미 처리 완료된 큐입니다.", 'yellow');
                return;
            }

            if ($queueInfo['status'] === 'processing') {
                CLI::write("이미 처리 중인 큐입니다.", 'yellow');
                return;
            }

            // 상태를 processing으로 변경
            $queueBuilder = $db->table('tbl_employee_search_queue');
            $queueBuilder->where('idx', $queueIdx);
            $queueBuilder->update([
                'status' => 'processing',
                'started_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // API 정보 조회
            $apiIdx = $queueInfo['api_idx'];
            $apiListModel = new InsungApiListModel();
            $apiInfo = $apiListModel->find($apiIdx);

            if (!$apiInfo || empty($apiInfo['token'])) {
                // 에러 상태로 변경
                $queueBuilder = $db->table('tbl_employee_search_queue');
                $queueBuilder->where('idx', $queueIdx);
                $queueBuilder->update([
                    'status' => 'failed',
                    'error_message' => 'API 정보를 찾을 수 없거나 토큰이 없습니다.',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                CLI::error("API 정보를 찾을 수 없거나 토큰이 없습니다.");
                return;
            }

            $mCode = $apiInfo['mcode'] ?? '';
            $ccCode = $apiInfo['cccode'] ?? '';
            $token = $apiInfo['token'] ?? '';
            $compCode = $queueInfo['comp_code'];

            if (empty($mCode) || empty($ccCode) || empty($token)) {
                // 에러 상태로 변경
                $queueBuilder = $db->table('tbl_employee_search_queue');
                $queueBuilder->where('idx', $queueIdx);
                $queueBuilder->update([
                    'status' => 'failed',
                    'error_message' => 'API 정보가 불완전합니다.',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                CLI::error("API 정보가 불완전합니다.");
                return;
            }

            // 인성 API 서비스 초기화
            $insungApiService = new InsungApiService();

            CLI::write("직원 목록 조회 중...", 'yellow');

            // 직원 목록 조회 (첫 페이지)
            $page = 1;
            $limit = 100;
            $result = $insungApiService->getCustomerAttachedList(
                $mCode,
                $ccCode,
                $token,
                $compCode,  // comp_no
                '',         // comp_name
                '',         // user_id
                '',         // user_name
                '',         // tel_no
                '',         // cust_name
                '',         // dept_name
                '',         // staff_name
                $page,
                $limit,
                $apiIdx
            );

            // API 응답 구조 확인
            $code = null;
            $msg = null;
            $totalPage = 1;
            $totalRecord = 0;
            $currentDisplayArticle = 0;

            if (is_object($result) && isset($result->Result[0]->result_info[0]->code)) {
                $code = $result->Result[0]->result_info[0]->code;
                $msg = $result->Result[0]->result_info[0]->msg ?? '';
            } elseif (is_array($result) && isset($result[0]->code)) {
                $code = $result[0]->code;
                $msg = $result[0]->msg ?? '';
            }

            if (!$result || $code != '1000') {
                // 에러 상태로 변경
                $queueBuilder = $db->table('tbl_employee_search_queue');
                $queueBuilder->where('idx', $queueIdx);
                $queueBuilder->update([
                    'status' => 'failed',
                    'error_message' => $msg ?: '직원 목록 조회 실패',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                CLI::error("직원 목록 조회 실패: " . ($msg ?: 'Unknown error'));
                return;
            }

            // 페이지 정보 추출
            if (is_object($result)) {
                if (isset($result->Result[1])) {
                    $pageInfo = $result->Result[1];
                    $totalPage = isset($pageInfo->total_page) ? (int)$pageInfo->total_page : 1;
                    $totalRecord = isset($pageInfo->total_record) ? (int)$pageInfo->total_record : 0;
                    $currentDisplayArticle = isset($pageInfo->current_display_article) ? (int)$pageInfo->current_display_article : (isset($pageInfo->display_article) ? (int)$pageInfo->display_article : 0);
                }
            } elseif (is_array($result)) {
                if (isset($result[1])) {
                    $pageInfo = is_object($result[1]) ? $result[1] : (object)$result[1];
                    $totalPage = isset($pageInfo->total_page) ? (int)$pageInfo->total_page : 1;
                    $totalRecord = isset($pageInfo->total_record) ? (int)$pageInfo->total_record : 0;
                    $currentDisplayArticle = isset($pageInfo->current_display_article) ? (int)$pageInfo->current_display_article : (isset($pageInfo->display_article) ? (int)$pageInfo->display_article : 0);
                }
            }

            // 총 건수 업데이트
            $queueBuilder = $db->table('tbl_employee_search_queue');
            $queueBuilder->where('idx', $queueIdx);
            $queueBuilder->update([
                'total_count' => $totalRecord,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            CLI::write("총 페이지: {$totalPage}, 총 레코드: {$totalRecord}", 'green');

            $processedCount = 0;
            $insertedCount = 0;

            // 모든 페이지 순회
            for ($currentPage = 1; $currentPage <= $totalPage; $currentPage++) {
                CLI::write("페이지 {$currentPage}/{$totalPage} 처리 중...", 'yellow');

                // 첫 페이지는 이미 조회했으므로 재사용
                if ($currentPage === 1) {
                    $pageResult = $result;
                } else {
                    $pageResult = $insungApiService->getCustomerAttachedList(
                        $mCode,
                        $ccCode,
                        $token,
                        $compCode,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        $currentPage,
                        $limit,
                        $apiIdx
                    );

                    if (!$pageResult) {
                        CLI::write("페이지 {$currentPage} 조회 실패", 'red');
                        continue;
                    }
                }

                // 결과 파싱
                $loopNum = $currentDisplayArticle > 0 ? $currentDisplayArticle + 2 : 2;
                $items = [];

                if (is_object($pageResult)) {
                    for ($i = 2; $i < $loopNum; $i++) {
                        if (!isset($pageResult->Result[$i])) continue;
                        $items[] = $pageResult->Result[$i];
                    }
                } elseif (is_array($pageResult)) {
                    for ($i = 2; $i < $loopNum; $i++) {
                        if (!isset($pageResult[$i])) continue;
                        $items[] = $pageResult[$i];
                    }
                }

                // 각 직원의 상세 정보 조회 및 저장
                foreach ($items as $item) {
                    $cCode = is_object($item) ? ($item->c_code ?? '') : ($item['c_code'] ?? '');
                    
                    if (empty($cCode)) {
                        continue;
                    }

                    // 상세 정보 조회
                    $memberDetail = $insungApiService->getMemberDetailByCode($mCode, $ccCode, $token, $cCode, $apiIdx);

                    // 사용중지 고객 패스
                    if ($memberDetail && is_object($memberDetail) && isset($memberDetail->Result[1]->query_result[0]->code) && $memberDetail->Result[1]->query_result[0]->code == 40) {
                        continue;
                    }

                    $detailItem = null;
                    if ($memberDetail && is_object($memberDetail) && isset($memberDetail->Result[1]->item[0])) {
                        $detailItem = $memberDetail->Result[1]->item[0];
                    }

                    // 기존 결과 확인 (queue_idx와 c_code로 중복 체크)
                    $resultBuilder = $db->table('tbl_employee_search_results');
                    $resultBuilder->where('queue_idx', $queueIdx);
                    $resultBuilder->where('c_code', $cCode);
                    $existingResult = $resultBuilder->get()->getRowArray();

                    // 결과 데이터 준비
                    $resultData = [
                        'queue_idx' => $queueIdx,
                        'c_code' => $cCode,
                        'cust_name' => is_object($item) ? ($item->cust_name ?? '') : ($item['cust_name'] ?? ''),
                        'dept_name' => is_object($item) ? ($item->dept_name ?? '') : ($item['dept_name'] ?? ''),
                        'charge_name' => is_object($item) ? ($item->charge_name ?? '') : ($item['charge_name'] ?? ''),
                        'tel_no1' => is_object($item) ? ($item->tel_no1 ?? '') : ($item['tel_no1'] ?? ''),
                        'dept_name_detail' => $detailItem->dept_name ?? '',
                        'charge_name_detail' => $detailItem->charge_name ?? '',
                        'basic_dong' => $detailItem->basic_dong ?? '',
                        'sido' => $detailItem->sido ?? '',
                        'gugun' => $detailItem->gugun ?? '',
                        'ri' => $detailItem->ri ?? '',
                        'location' => $detailItem->location ?? '',
                        'lon' => !empty($detailItem->lon) ? $detailItem->lon : null,
                        'lat' => !empty($detailItem->lat) ? $detailItem->lat : null,
                        'full_address' => trim(implode(' ', array_filter([
                            $detailItem->sido ?? '',
                            $detailItem->gugun ?? '',
                            $detailItem->basic_dong ?? '',
                            $detailItem->ri ?? ''
                        ]))),
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    if ($existingResult) {
                        // 기존 데이터 업데이트
                        $resultBuilder = $db->table('tbl_employee_search_results');
                        $resultBuilder->where('queue_idx', $queueIdx);
                        $resultBuilder->where('c_code', $cCode);
                        $resultBuilder->update($resultData);
                    } else {
                        // 새 데이터 삽입
                        $db->table('tbl_employee_search_results')->insert($resultData);
                        $insertedCount++;
                    }
                    $processedCount++;

                    // 진행 상황 업데이트 (100건마다)
                    if ($processedCount % 100 == 0) {
                        $queueBuilder = $db->table('tbl_employee_search_queue');
                        $queueBuilder->where('idx', $queueIdx);
                        $queueBuilder->update([
                            'processed_count' => $processedCount,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        CLI::write("진행 상황: {$processedCount}/{$totalRecord} 처리 완료", 'green');
                    }
                }
            }

            // 완료 상태로 변경
            $queueBuilder = $db->table('tbl_employee_search_queue');
            $queueBuilder->where('idx', $queueIdx);
            $queueBuilder->update([
                'status' => 'completed',
                'processed_count' => $processedCount,
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            CLI::write("처리 완료!", 'green');
            CLI::write("총 처리: {$processedCount}건 (저장: {$insertedCount}건)", 'green');

        } catch (\Exception $e) {
            // 에러 상태로 변경
            $db = \Config\Database::connect();
            $queueBuilder = $db->table('tbl_employee_search_queue');
            $queueBuilder->where('idx', $queueIdx);
            $queueBuilder->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            CLI::error("오류 발생: " . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
        }
    }
}

