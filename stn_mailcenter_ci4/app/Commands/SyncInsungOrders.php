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
            $userBuilder->select('idx, user_id, user_company, user_type, user_class, user_dept, user_ccode');
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
            $userClass = $userInfo['user_class'] ?? '5'; // 주문조회 권한용
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
            
            // user_class별 파라미터 설정 (주문조회 권한)
            // user_type과 user_class는 별개로 판단
            // comp_no(거래처번호)는 항상 전달해야 함
            // user_class=1,2일 때는 dept_name 파라미터만 전달하지 않음 (전체 조회)
            // user_class=3 이상일 때는 dept_name 파라미터 전달 (부서별 조회)
            $apiCompCode = $compCode; // comp_no는 항상 전달
            if (!empty($compCode)) {
                CLI::write("거래처 코드: {$compCode} (user_class={$userClass})", 'green');
            }
            
            // 본인오더조회(env1=3) 확인 (staff_code 설정 전에 먼저 확인)
            $db = \Config\Database::connect();
            $envBuilder = $db->table('tbl_company_env');
            $envBuilder->select('env1');
            $envBuilder->where('comp_code', $compCode);
            $envQuery = $envBuilder->get();
            $isSelfOrderOnly = false;
            $env1 = null;
            if ($envQuery !== false) {
                $envInfo = $envQuery->getRowArray();
                if ($envInfo && isset($envInfo['env1'])) {
                    $env1 = $envInfo['env1'];
                    if ($env1 == '3') {
                        $isSelfOrderOnly = true;
                        CLI::write("본인오더조회 모드: 로그인한 사용자의 주문만 저장합니다.", 'yellow');
                    }
                }
            }
            
            // user_class별 파라미터 설정
            $staffCode = null;
            // CLI 연동 시에는 부서명 파라미터를 전달하지 않음 (DB 조회 시에만 user_dept 필터링 적용)
            $deptName = null;
            
            // user_type = 5일 때 staff_code 사용
            // 단, env1=1(전체 조회)이거나 user_class=1,2일 때는 제외
            if ($userType == '5' && !empty($userCode)) {
                // env1=1(전체 조회)이면 staff_code 제외
                if ($env1 == '1') {
                    CLI::write("사용자 코드 파라미터 제외 (user_type=5, env1=1: 전체 조회)", 'yellow');
                } elseif ($userClass == '1' || $userClass == '2') {
                    CLI::write("사용자 코드 파라미터 제외 (user_type=5, user_class={$userClass})", 'yellow');
                } else {
                    $staffCode = $userCode;
                    CLI::write("사용자 코드 파라미터 추가: staff_code={$userCode} (user_type=5, user_class={$userClass}, env1={$env1})", 'green');
                }
            }

            $orderModel = new OrderModel();
            $totalInserted = 0;
            $totalUpdated = 0;
            $totalDeleted = 0;
            $totalErrors = [];

            // 부서별 오더목록 API 호출 → 파싱 → DB 저장
            // (내부적으로 2번 호출: state 파라미터 없이 + state=40)
            // CLI 연동 시에는 부서명 파라미터를 전달하지 않음 (DB 조회 시에만 user_dept 필터링 적용)
            CLI::write("부서별 오더목록 조회 및 저장 중...", 'yellow');
            $orderListResultDept = $insungApiService->getOrderList($mCode, $ccCode, $token, $userId, $startDate, $endDate, null, $staffCode, null, $apiCompCode, 1000, 1, $apiIdx, true);

            if ($orderListResultDept['success'] && isset($orderListResultDept['data'])) {
                CLI::write("부서별 오더목록 저장 중...", 'yellow');
                // 삭제 로직용 endDate: getOrderList에서 +5일로 변경되므로 동일하게 적용
                $deleteEndDate = $endDate;
                $today = date('Y-m-d');
                if (empty($deleteEndDate) || $deleteEndDate === $today) {
                    $deleteEndDate = date('Y-m-d', strtotime('+5 days'));
                }
                $resultDept = $orderModel->insertOrUpdateInsungOrders($orderListResultDept['data'], $userIdx, $customerId, $isSelfOrderOnly, $userId, $mCode, $ccCode, $token, $apiIdx, $compCode, $startDate, $deleteEndDate);
                $totalInserted += $resultDept['inserted'];
                $totalUpdated += $resultDept['updated'];
                if (isset($resultDept['deleted'])) {
                    $totalDeleted += ($resultDept['deleted'] ?? 0);
                }
                if (!empty($resultDept['errors'])) {
                    $totalErrors = array_merge($totalErrors, $resultDept['errors']);
                }
                CLI::write("부서별 오더목록 저장 완료: 신규 {$resultDept['inserted']}건, 업데이트 {$resultDept['updated']}건", 'green');
                if (isset($resultDept['deleted']) && $resultDept['deleted'] > 0) {
                    CLI::write("삭제된 주문: {$resultDept['deleted']}건 (인성 API에서 사라진 주문)", 'yellow');
                }
            } else {
                CLI::write("부서별 오더목록 조회 실패: " . ($orderListResultDept['message'] ?? '알 수 없는 오류'), 'red');
            }

            CLI::write("동기화 완료!", 'green');
            CLI::write("총 신규 주문: {$totalInserted}건", 'green');
            CLI::write("총 업데이트: {$totalUpdated}건", 'green');
            if ($totalDeleted > 0) {
                CLI::write("총 삭제: {$totalDeleted}건 (인성 API에서 사라진 주문)", 'yellow');
            }

            if (!empty($totalErrors)) {
                CLI::write("오류 발생: " . count($totalErrors) . "건", 'yellow');
                foreach ($totalErrors as $error) {
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

