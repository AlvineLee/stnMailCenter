<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\InsungApiService;
use App\Models\OrderModel;
use App\Models\InsungApiListModel;
use App\Models\InsungUsersListModel;

class SyncInsungOrders extends BaseCommand
{
    protected $group       = 'insung';
    protected $name        = 'insung:sync-orders';
    protected $description = '인성 API 주문 목록을 동기화합니다.';
    protected $usage       = 'insung:sync-orders [m_code] [cc_code] [user_id] [start_date] [end_date]';
    protected $arguments   = [
        'm_code'     => '마스터 코드',
        'cc_code'    => '콜센터 코드',
        'user_id'    => '사용자 ID',
        'start_date' => '시작일자 (YYYY-MM-DD, 기본값: 오늘)',
        'end_date'   => '종료일자 (YYYY-MM-DD, 기본값: 오늘)'
    ];

    public function run(array $params)
    {
        // 필수 파라미터 확인
        $mCode = $params[0] ?? null;
        $ccCode = $params[1] ?? null;
        $userId = $params[2] ?? null;
        $startDate = $params[3] ?? date('Y-m-d');
        $endDate = $params[4] ?? date('Y-m-d');

        if (empty($mCode) || empty($ccCode) || empty($userId)) {
            CLI::error('필수 파라미터가 누락되었습니다.');
            CLI::write('사용법: ' . $this->usage, 'yellow');
            return;
        }

        CLI::write("인성 API 주문 목록 동기화를 시작합니다...", 'yellow');
        CLI::write("마스터 코드: {$mCode}", 'green');
        CLI::write("콜센터 코드: {$ccCode}", 'green');
        CLI::write("사용자 ID: {$userId}", 'green');
        CLI::write("기간: {$startDate} ~ {$endDate}", 'green');

        try {
            // API 정보 조회
            $apiListModel = new InsungApiListModel();
            $apiInfo = $apiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);

            if (!$apiInfo || empty($apiInfo['token'])) {
                CLI::error("API 정보를 찾을 수 없거나 토큰이 없습니다.");
                return;
            }

            $token = $apiInfo['token'];
            $apiIdx = $apiInfo['idx'] ?? null;

            // 사용자 정보 조회 (명시적으로 필요한 컬럼만 선택)
            $usersListModel = new InsungUsersListModel();
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('idx, user_id, user_company, user_type, user_dept, user_ccode');
            $userBuilder->where('user_id', $userId);
            $userQuery = $userBuilder->get();
            $userInfo = $userQuery ? $userQuery->getRowArray() : null;

            if (!$userInfo) {
                CLI::error("사용자 정보를 찾을 수 없습니다: {$userId}");
                return;
            }

            $userIdx = $userInfo['idx'] ?? null;
            $compCode = $userInfo['user_company'] ?? null;
            $userType = $userInfo['user_type'] ?? '5';
            $userDept = $userInfo['user_dept'] ?? null; // 부서명 (user_type = 3일 때 사용)
            
            // user_type = 5일 때 staff_code로 사용할 값 조회
            // user_ccode 필드를 사용
            $userCode = null;
            if ($userType == '5') {
                $userCode = $userInfo['user_ccode'] ?? null;
            }

            if (empty($userIdx)) {
                CLI::error("사용자 idx를 찾을 수 없습니다.");
                return;
            }

            // 고객사 ID 설정 (comp_code를 customer_id로 사용)
            $customerId = !empty($compCode) ? (int)$compCode : 1;

            // 인성 API 서비스 초기화
            $insungApiService = new InsungApiService();

            CLI::write("주문 목록 조회 중...", 'yellow');
            if (!empty($compCode)) {
                CLI::write("거래처 코드: {$compCode}", 'green');
            }
            
            // user_type별 파라미터 설정 - 주석처리 (API 데이터 못 가져오는 문제로 임시 비활성화)
            $staffCode = null;
            $deptName = null;
            
            // if ($userType == '5' && !empty($userCode)) {
            //     // user_type = 5: staff_code 사용
            //     $staffCode = $userCode;
            //     CLI::write("사용자 타입: 5, staff_code: {$userCode}", 'green');
            // } elseif ($userType == '3' && !empty($userDept)) {
            //     // user_type = 3: dept_name 사용
            //     $deptName = $userDept;
            //     CLI::write("사용자 타입: 3, dept_name: {$userDept}", 'green');
            // }

            // 주문 목록 조회
            // user_type = 5: staff_code 전달, dept_name = null
            // user_type = 3: dept_name 전달, staff_code = null
            // user_type = 1: 둘 다 null
            // staff_code, dept_name 파라미터 임시 비활성화
            $orderListResult = $insungApiService->getOrderList($mCode, $ccCode, $token, $userId, $startDate, $endDate, null, null, null, $compCode, 1000, 1, $apiIdx);

            if (!$orderListResult['success'] || !isset($orderListResult['data'])) {
                CLI::error("주문 목록 조회 실패: " . $orderListResult['message']);
                return;
            }

            // 응답 데이터 구조 확인 (디버깅용) - 주석처리
            // $responseData = $orderListResult['data'];
            
            // 전체 응답 로그 출력 (DB 저장 확인용) - 주석처리
            // CLI::write("=== 인성 API 주문 목록 응답 전체 ===", 'cyan');
            // CLI::write("응답 데이터 타입: " . gettype($responseData), 'yellow');
            // CLI::write("응답 전체 내용 (JSON):", 'yellow');
            // CLI::write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'white');
            // CLI::write("=== 응답 분석 ===", 'cyan');
            
            // if (is_array($responseData)) {
            //     CLI::write("응답 배열 개수: " . count($responseData), 'yellow');
            //     if (isset($responseData[0])) {
            //         CLI::write("응답 코드 확인 중...", 'yellow');
            //         $code = is_object($responseData[0]) ? ($responseData[0]->code ?? 'N/A') : (is_array($responseData[0]) ? ($responseData[0]['code'] ?? 'N/A') : 'N/A');
            //         $msg = is_object($responseData[0]) ? ($responseData[0]->msg ?? 'N/A') : (is_array($responseData[0]) ? ($responseData[0]['msg'] ?? 'N/A') : 'N/A');
            //         CLI::write("응답 코드: {$code}, 메시지: {$msg}", 'green');
            //         CLI::write("Response[0] 전체:", 'yellow');
            //         CLI::write(json_encode($responseData[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'white');
            //     }
            //     if (isset($responseData[1])) {
            //         CLI::write("\$responseData[1] 타입: " . gettype($responseData[1]), 'yellow');
            //         if (is_array($responseData[1])) {
            //             CLI::write("\$responseData[1] 배열 개수: " . count($responseData[1]), 'yellow');
            //             CLI::write("Response[1] 전체 (주문 목록):", 'yellow');
            //             CLI::write(json_encode($responseData[1], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'white');
            //             // 첫 번째 주문 항목 샘플 출력
            //             if (isset($responseData[1][0])) {
            //                 CLI::write("=== 첫 번째 주문 항목 샘플 ===", 'cyan');
            //                 CLI::write(json_encode($responseData[1][0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'white');
            //             }
            //         } elseif (is_object($responseData[1])) {
            //             CLI::write("Response[1] 객체 전체:", 'yellow');
            //             CLI::write(json_encode($responseData[1], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'white');
            //         }
            //     }
            // } elseif (is_object($responseData)) {
            //     CLI::write("Response 객체 전체:", 'yellow');
            //     CLI::write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'white');
            // }
            // CLI::write("=== 응답 분석 완료 ===", 'cyan');

            CLI::write("주문 목록 저장 중...", 'yellow');

            // 주문 목록 저장 (INSERT ON DUPLICATE KEY UPDATE)
            $orderModel = new OrderModel();
            $result = $orderModel->insertOrUpdateInsungOrders($orderListResult['data'], $userIdx, $customerId);

            CLI::write("동기화 완료!", 'green');
            CLI::write("신규 주문: {$result['inserted']}건", 'green');
            CLI::write("업데이트: {$result['updated']}건", 'green');

            if (!empty($result['errors'])) {
                CLI::write("오류 발생: " . count($result['errors']) . "건", 'yellow');
                foreach ($result['errors'] as $error) {
                    CLI::write("  - {$error}", 'red');
                }
            }
            
            // 예약 상태인 주문들은 주문목록 API의 pickup_time으로 reserve_date가 설정됨
            // 주문상세 API 호출 불필요 (pickup_time이 있으면 그것만 사용)

        } catch (\Exception $e) {
            CLI::error("동기화 중 오류 발생: " . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
        }
    }
}

