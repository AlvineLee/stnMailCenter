<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\HistoryModel;
use App\Models\UserPreferencesModel;

class History extends BaseController
{
    protected $historyModel;

    public function __construct()
    {
        $this->historyModel = new HistoryModel();
    }
    
    public function list()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 서브도메인 접근 권한 체크
        $subdomainCheck = $this->checkSubdomainAccess();
        if ($subdomainCheck !== true) {
            return $subdomainCheck;
        }
        
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        $userType = session()->get('user_type');
        $ccCode = session()->get('cc_code');
        $compName = session()->get('comp_name');
        $userCompany = session()->get('user_company'); // tbl_users_list.user_company
        $userIdx = session()->get('user_idx'); // tbl_users_list.idx (인성 API 주문용)
        $userId = session()->get('user_id'); // 일반 주문용 user_id
        
        // 검색 조건 처리
        $searchType = $this->request->getGet('search_type') ?? 'all';
        $searchKeyword = $this->request->getGet('search_keyword') ?? '';
        $statusFilter = $this->request->getGet('status') ?? 'all';
        $serviceFilter = $this->request->getGet('service') ?? 'all';
        // 날짜 필터 (기본값: 오늘 날짜)
        $today = date('Y-m-d');
        $startDate = $this->request->getGet('start_date') ?? $today;
        $endDate = $this->request->getGet('end_date') ?? $today;
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = 20;
        
        // 정렬 파라미터
        $orderBy = $this->request->getGet('order_by') ?? null;
        $orderDir = $this->request->getGet('order_dir') ?? 'desc';
        
        // 필터 조건 구성
        $filters = [
            'search_type' => $searchType,
            'search_keyword' => $searchKeyword,
            'status' => $statusFilter,
            'service' => $serviceFilter,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_by' => $orderBy,
            'order_dir' => $orderDir
        ];
        
        // 서브도메인 기반 필터링 (우선순위 1)
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $subdomainCompCode = null;
        
        if ($currentSubdomain !== 'default') {
            // 서브도메인으로 접근한 경우 해당 고객사만 조회
            $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
            if ($subdomainCompCode) {
                // 서브도메인 기반 필터링 (m_code, cc_code로 필터링)
                $apiCodes = $subdomainConfig->getCurrentApiCodes();
                if ($apiCodes && !empty($apiCodes['m_code']) && !empty($apiCodes['cc_code'])) {
                    $filters['subdomain_m_code'] = $apiCodes['m_code'];
                    $filters['subdomain_cc_code'] = $apiCodes['cc_code'];
                    $filters['subdomain_comp_code'] = $subdomainCompCode;
                    // log_message('info', "History list - Subdomain filter applied: {$currentSubdomain}, comp_code={$subdomainCompCode}, m_code={$apiCodes['m_code']}, cc_code={$apiCodes['cc_code']}");
                }
            }
        }
        
        // user_type별 필터링 (서브도메인 필터와 함께 적용)
        if ($loginType === 'daumdata') {
            if ($userType == '1') {
                // user_type = 1: 전체 주문 리스트
                // 서브도메인이 있으면 서브도메인 내 전체, 없으면 전체
                $filters['user_type'] = '1';
                $filters['customer_id'] = null;
            } elseif ($userType == '3') {
                // user_type = 3: user_company가 본인의 user_company와 같은 데이터들
                $filters['user_type'] = '3';
                $filters['user_company'] = $userCompany; // 본인의 user_company
            } elseif ($userType == '5') {
                // user_type = 5: 같은 회사(comp_code)의 모든 주문 조회
                $filters['user_type'] = '5';
                $filters['user_company'] = $userCompany; // 같은 회사의 모든 주문 조회
            }
        } else {
            // STN 로그인인 경우 기존 로직
            $filters['customer_id'] = $userRole !== 'super_admin' ? $customerId : null;
        }
        
        // Model을 통한 데이터 조회
        $result = $this->historyModel->getHistoryList($filters, $page, $perPage);
        $orders = $result['orders'];
        $totalCount = $result['total_count'];
        
        // 주문 데이터 포맷팅 (뷰 로직을 컨트롤러로 이동)
        $formattedOrders = [];
        foreach ($orders as $order) {
            $formattedOrder = $order;
            
            // 날짜/시간 포맷팅
            $orderDate = $order['order_date'] ?? '';
            $orderTime = $order['order_time'] ?? '';
            if ($orderDate && $orderTime) {
                $formattedOrder['formatted_order_datetime'] = $orderDate . ' ' . $orderTime;
            } elseif ($orderDate) {
                $formattedOrder['formatted_order_datetime'] = $orderDate;
            } else {
                $formattedOrder['formatted_order_datetime'] = '-';
            }
            
            // 상태 라벨 및 클래스 처리
            $statusLabel = '';
            $statusClass = '';
            $showMapOnClick = false;
            $isRiding = false;
            $insungOrderNumberForMap = '';
            
            if (($order['order_system'] ?? '') === 'insung') {
                // 인성 API 주문: state 값 처리
                $stateValue = $order['state'] ?? '';
                $stateLabels = [
                    '10' => '접수',
                    '11' => '배차',
                    '12' => '운행',
                    '20' => '대기',
                    '30' => '완료',
                    '40' => '취소',
                    '50' => '문의',
                    '90' => '예약'
                ];
                
                if (isset($stateLabels[$stateValue])) {
                    $statusLabel = $stateLabels[$stateValue];
                    $statusClass = 'state-' . $stateValue;
                    $stateCode = $stateValue;
                } else {
                    $statusLabel = $stateValue ?: '-';
                    $statusClass = 'state-' . preg_replace('/\s+/', '', $statusLabel);
                    $labelToCode = array_flip($stateLabels);
                    $stateCode = $labelToCode[$stateValue] ?? '';
                }
                
                // 운행 상태 확인 (기사 위치 표시용)
                if ($stateCode === '12' || $statusLabel === '운행') {
                    $isRiding = true;
                }
                
                // 인성 주문번호 확인
                if (!empty($order['insung_order_number'])) {
                    $insungOrderNumberForMap = $order['insung_order_number'];
                } elseif (!empty($order['order_number'])) {
                    $insungOrderNumberForMap = $order['order_number'];
                }
                
                // 주문번호가 있으면 맵 표시
                if (!empty($insungOrderNumberForMap)) {
                    $showMapOnClick = true;
                }
            } else {
                // 일반 주문: status 값 처리
                $statusLabels = [
                    'pending' => '대기중',
                    'processing' => '접수완료',
                    'completed' => '배송중',
                    'delivered' => '배송완료',
                    'cancelled' => '취소',
                    'api_failed' => 'API실패'
                ];
                $statusValue = $order['status'] ?? '';
                $statusLabel = $statusLabels[$statusValue] ?? ($statusValue ?: '-');
                $statusClass = 'status-' . $statusValue;
                
                // 배송중 상태 확인 (기사 위치 표시용)
                if ($statusValue === 'completed') {
                    $isRiding = true;
                }
                
                // 주문번호 확인
                if (!empty($order['order_number'])) {
                    $insungOrderNumberForMap = $order['order_number'];
                }
                
                // 주문번호가 있으면 맵 표시
                if (!empty($insungOrderNumberForMap)) {
                    $showMapOnClick = true;
                }
            }
            
            $formattedOrder['status_label'] = $statusLabel;
            $formattedOrder['status_class'] = $statusClass;
            $formattedOrder['show_map_on_click'] = $showMapOnClick && !empty($insungOrderNumberForMap);
            $formattedOrder['is_riding'] = $isRiding;
            $formattedOrder['insung_order_number_for_map'] = $insungOrderNumberForMap;
            
            // 주문번호 표시용 (인성 API 주문의 경우 insung_order_number 우선)
            if (($order['order_system'] ?? '') === 'insung' && !empty($order['insung_order_number'])) {
                $formattedOrder['display_order_number'] = $order['insung_order_number'];
            } else {
                $formattedOrder['display_order_number'] = $order['order_number'] ?? '-';
            }
            
            // 왕복/편도 변환
            $deliveryRoute = $order['quick_delivery_route'] ?? '';
            $formattedOrder['delivery_route_label'] = ($deliveryRoute === 'round_trip' ? '왕복' : ($deliveryRoute === 'one_way' ? '편도' : '-'));
            
            // 금액 포맷팅
            $formattedOrder['total_fare_formatted'] = $order['total_fare'] ? number_format($order['total_fare']) . '원' : '-';
            $formattedOrder['add_cost_formatted'] = $order['add_cost'] ? number_format($order['add_cost']) . '원' : '-';
            $formattedOrder['delivery_cost_formatted'] = $order['delivery_cost'] ? number_format($order['delivery_cost']) . '원' : '-';
            $formattedOrder['total_amount_formatted'] = $order['total_amount'] ? number_format($order['total_amount']) . '원' : '-';
            
            // 채널 라벨 변환
            $channelLabels = [
                'A' => 'API접수',
                'I' => '인터넷접수',
                'T' => '전화접수'
            ];
            $channel = $order['order_regist_type'] ?? '';
            $formattedOrder['channel_label'] = $channelLabels[$channel] ?? ($channel ?: '-');
            
            $formattedOrders[] = $formattedOrder;
        }
        
        // 페이징 정보 계산 (PaginationHelper 사용)
        $queryParams = [
            'search_type' => $searchType,
            'search_keyword' => $searchKeyword,
            'status' => $statusFilter,
            'service' => $serviceFilter,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_by' => $orderBy,
            'order_dir' => $orderDir
        ];
        
        $paginationHelper = new \App\Libraries\PaginationHelper(
            $totalCount,
            $perPage,
            $page,
            base_url('history/list'),
            $queryParams
        );
        
        // 리스트 페이지 진입 시 인성 API 주문 목록 및 상세 동기화 (1페이지에서만 실행)
        // 종료기간이 오늘보다 크거나 같을 때 API 연동 실행
        $today = date('Y-m-d');
        $shouldSyncAPI = ($page == 1 && in_array($loginType, ['daumdata', 'stn']) && strtotime($endDate) >= strtotime($today));
        
        if ($shouldSyncAPI) {
            try {
                // 인성 API 주문 목록 동기화
                $mCode = session()->get('m_code');
                $ccCode = session()->get('cc_code');
                $userId = session()->get('user_id');
                
                // m_code, cc_code가 없는 경우 서브도메인 기반으로 조회
                if (empty($mCode) || empty($ccCode)) {
                    $subdomainConfig = config('Subdomain');
                    $apiCodes = $subdomainConfig->getCurrentApiCodes();
                    
                    if ($apiCodes && !empty($apiCodes['m_code']) && !empty($apiCodes['cc_code'])) {
                        $mCode = $apiCodes['m_code'];
                        $ccCode = $apiCodes['cc_code'];
                        $subdomain = $subdomainConfig->getCurrentSubdomain();
                        log_message('info', "History list - Subdomain access ({$subdomain}) - Using mcode={$mCode}, cccode={$ccCode}");
                    }
                }
                
                if (!empty($mCode) && !empty($ccCode) && !empty($userId)) {
                    $this->syncInsungOrdersViaCLI($mCode, $ccCode, $userId, $startDate, $endDate);
                }
                
                // 인성 API 주문 상세 동기화
                $this->syncInsungOrderDetailsViaCLI();
            } catch (\Exception $e) {
                // 동기화 실패해도 리스트는 표시
                log_message('error', "Failed to trigger Insung sync on list page: " . $e->getMessage());
            }
        }
        
        // 사용자별 컬럼 순서 조회
        $userPreferencesModel = new UserPreferencesModel();
        $userId = session()->get('user_idx') ?? session()->get('user_id');
        $columnOrder = null;
        if ($userId && $loginType) {
            $columnOrder = $userPreferencesModel->getColumnOrder($loginType, (string)$userId, 'history');
        }
        
        $data = [
            'title' => '이용내역상세조회',
            'content_header' => [
                'title' => '이용내역상세조회',
                'description' => '주문 이용 내역을 상세하게 조회할 수 있습니다.'
            ],
            'orders' => $formattedOrders,
            'pagination' => $paginationHelper,
            'search_type' => $searchType,
            'search_keyword' => $searchKeyword,
            'status_filter' => $statusFilter,
            'service_filter' => $serviceFilter,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_by' => $orderBy,
            'order_dir' => $orderDir,
            'status_options' => [
                'all' => '전체',
                'pending' => '대기중',
                'processing' => '처리중',
                'completed' => '완료',
                'cancelled' => '취소',
                'api_failed' => 'API실패'
            ],
            'search_type_options' => [
                'all' => '전체',
                'order_number' => '주문번호',
                'service_name' => '서비스명',
                'customer_name' => '고객사명',
                'departure_address' => '출발지',
                'destination_address' => '도착지'
            ],
            'user_role' => $userRole,
            'customer_id' => $customerId,
            'column_order' => $columnOrder
        ];
        
        return view('history/list', $data);
    }
    
    /**
     * 리스트 페이지 진입 시 인성 API 주문 목록 동기화 (CLI 명령어로 백그라운드 실행)
     * 
     * @param string $mCode 마스터 코드
     * @param string $ccCode 콜센터 코드
     * @param string $userId 사용자 ID
     * @param string $startDate 시작 날짜
     * @param string $endDate 종료 날짜
     */
    private function syncInsungOrdersViaCLI($mCode, $ccCode, $userId, $startDate = null, $endDate = null)
    {
        try {
            // 프로젝트 루트 경로 찾기
            $projectRoot = ROOTPATH;
            $sparkPath = $projectRoot . 'spark';
            
            // spark 파일이 존재하는지 확인
            if (!file_exists($sparkPath)) {
                log_message('warning', "Spark file not found at: {$sparkPath}");
                return;
            }
            
            // 날짜 설정 (기본값: 오늘 날짜)
            $today = date('Y-m-d');
            $syncStartDate = $startDate ?? $today;
            $syncEndDate = $endDate ?? $today;
            
            // CLI 명령어 구성
            $command = sprintf(
                'php %s insung:sync-orders %s %s %s %s %s > /dev/null 2>&1 &',
                escapeshellarg($sparkPath),
                escapeshellarg($mCode),
                escapeshellarg($ccCode),
                escapeshellarg($userId),
                escapeshellarg($syncStartDate),
                escapeshellarg($syncEndDate)
            );
            
            // 명령어 실행
            exec($command);
            
            // log_message('info', "Insung orders sync CLI command triggered on list page: {$command}");
            
        } catch (\Exception $e) {
            log_message('error', "Exception in syncInsungOrdersViaCLI: " . $e->getMessage());
        }
    }
    
    /**
     * 리스트 페이지 진입 시 인성 API 주문 상세 동기화 (CLI 명령어로 백그라운드 실행)
     */
    private function syncInsungOrderDetailsViaCLI()
    {
        try {
            // 사용자 권한에 따른 필터 조건 구성
            $userType = session()->get('user_type');
            $ccCode = session()->get('cc_code');
            $compName = session()->get('comp_name');
            
            // CLI 명령어로 백그라운드 실행
            $projectRoot = ROOTPATH;
            $sparkPath = $projectRoot . 'spark';
            if (!file_exists($sparkPath)) {
                log_message('warning', "Spark file not found at: {$sparkPath}");
                return;
            }
            
            // 파라미터 구성
            $params = [
                escapeshellarg($userType ?? '1')
            ];
            
            if ($userType == '3' && !empty($ccCode)) {
                $params[] = escapeshellarg($ccCode);
                $params[] = escapeshellarg('');
            } elseif ($userType == '5' && !empty($compName)) {
                $params[] = escapeshellarg('');
                $params[] = escapeshellarg($compName);
            } else {
                $params[] = escapeshellarg('');
                $params[] = escapeshellarg('');
            }
            
            $command = sprintf(
                'php %s insung:sync-order-details %s > /dev/null 2>&1 &',
                escapeshellarg($sparkPath),
                implode(' ', $params)
            );
            
            exec($command);
            // log_message('info', "Insung order details sync CLI command triggered on list page: {$command}");
            
        } catch (\Exception $e) {
            log_message('error', "Exception in syncInsungOrderDetailsViaCLI: " . $e->getMessage());
        }
    }
    
    /**
     * 컬럼 순서 저장 API
     */
    public function saveColumnOrder()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $loginType = session()->get('login_type');
        $userId = session()->get('user_idx') ?? session()->get('user_id');
        
        if (!$userId || !$loginType) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '사용자 정보를 찾을 수 없습니다.'
            ])->setStatusCode(400);
        }
        
        $columnOrder = $this->request->getJSON(true)['column_order'] ?? null;
        
        if (!is_array($columnOrder) || empty($columnOrder)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '컬럼 순서가 올바르지 않습니다.'
            ])->setStatusCode(400);
        }
        
        $userPreferencesModel = new UserPreferencesModel();
        $result = $userPreferencesModel->saveColumnOrder($loginType, (string)$userId, 'history', $columnOrder);
        
        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => '컬럼 순서가 저장되었습니다.'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => '컬럼 순서 저장에 실패했습니다.'
            ])->setStatusCode(500);
        }
    }
}

