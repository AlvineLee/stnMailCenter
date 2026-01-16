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
        
        // 모바일 디바이스 감지 (User-Agent 확인)
        $userAgent = $this->request->getUserAgent();
        $isMobile = false;
        if ($userAgent) {
            $mobileKeywords = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'windows phone', 'opera mini', 'opera mobi'];
            $userAgentString = strtolower($userAgent->getAgentString());
            foreach ($mobileKeywords as $keyword) {
                if (strpos($userAgentString, $keyword) !== false) {
                    $isMobile = true;
                    break;
                }
            }
        }
        $perPage = $isMobile ? 15 : 20;
        
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
            'order_dir' => $orderDir,
            'login_type' => $loginType,
            'comp_code' => session()->get('comp_code')
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
        
        // user_class는 세션에서 가져오거나 DB에서 조회 (주문조회 권한용)
        $userClass = session()->get('user_class');
        $userDept = session()->get('user_dept');
        $userIdx = null; // user_class=4일 때 정산관리부서 조회용
        if (empty($userClass) && $loginType === 'daumdata') {
            $userId = session()->get('user_id');
            if ($userId) {
                $db = \Config\Database::connect();
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->select('idx, user_class, user_dept');
                $userBuilder->where('user_id', $userId);
                $userQuery = $userBuilder->get();
                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult) {
                        if (isset($userResult['idx'])) {
                            $userIdx = $userResult['idx'];
                        }
                        if (isset($userResult['user_class'])) {
                            $userClass = $userResult['user_class'];
                        }
                        if (isset($userResult['user_dept'])) {
                            $userDept = $userResult['user_dept'];
                        }
                    }
                }
            }
        } elseif ($loginType === 'daumdata' && $userClass == '4') {
            // user_class=4일 때 user_idx 조회
            $userId = session()->get('user_id');
            if ($userId) {
                $db = \Config\Database::connect();
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->select('idx');
                $userBuilder->where('user_id', $userId);
                $userQuery = $userBuilder->get();
                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult && isset($userResult['idx'])) {
                        $userIdx = $userResult['idx'];
                    }
                }
            }
        }
        
        // user_class별 필터링 (주문조회 권한) - 공통 메서드 사용
        $userClassFilters = $this->buildUserClassFilters(
            $loginType,
            $userClass,
            $userType,
            $userCompany,
            $userDept,
            $userIdx,
            $subdomainCompCode,
            $userRole,
            $customerId
        );
        $filters = array_merge($filters, $userClassFilters);
        
        // user_idx와 user_id를 filters에 추가 (HistoryModel에서 사용)
        if ($userIdx) {
            $filters['user_idx'] = $userIdx;
        }
        if ($userId) {
            $filters['user_id'] = $userId;
        }
        
        // 본인주문조회(env1=3) 필터링: 일반 등급(user_class=5)일 때만 insung_user_id로 필터링
        
        if ($userClass == '5') {
            $compCodeForEnv = $subdomainCompCode ?? $userCompany;
            if ($compCodeForEnv) {
                $db = \Config\Database::connect();
                $envBuilder = $db->table('tbl_company_env');
                $envBuilder->select('env1');
                $envBuilder->where('comp_code', $compCodeForEnv);
                $envQuery = $envBuilder->get();
                if ($envQuery !== false) {
                    $envResult = $envQuery->getRowArray();
                    if ($envResult && isset($envResult['env1']) && $envResult['env1'] == '3') {
                        // 본인주문조회: 로그인한 사용자의 insung_user_id로 필터링
                        $loginUserId = session()->get('user_id'); // tbl_users_list.user_id (문자열)
                        if ($loginUserId) {
                            $filters['insung_user_id'] = $loginUserId;
                        }
                    }
                }
            }
        }
        
        // Model을 통한 데이터 조회
        $result = $this->historyModel->getHistoryList($filters, $page, $perPage);
        $orders = $result['orders'];
        $totalCount = $result['total_count'];
        
        // 전화번호 필드 복호화 처리
        $encryptionHelper = new \App\Libraries\EncryptionHelper();
        $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno'];
        foreach ($orders as &$order) {
            $order = $encryptionHelper->decryptFields($order, $phoneFields);
        }
        unset($order); // 참조 해제
        
        // 사용자 권한 정보 (마스킹 처리용)
        $loginType = session()->get('login_type');
        $userClass = session()->get('user_class');
        $userType = session()->get('user_type');
        $loginUserId = session()->get('user_id'); // 로그인한 user_id
        
        // user_class가 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata' && $loginUserId) {
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_class');
            $userBuilder->where('user_id', $loginUserId);
            $userQuery = $userBuilder->get();
            if ($userQuery !== false) {
                $userResult = $userQuery->getRowArray();
                if ($userResult && isset($userResult['user_class'])) {
                    $userClass = $userResult['user_class'];
                }
            }
        }
        
        // user_type 결정 (user_class 우선, 없으면 user_type 사용)
        $finalUserType = '1';
        if ($loginType === 'daumdata') {
            if ($userClass == '1' || $userClass == '3') {
                $finalUserType = '1';
            } elseif ($userClass == '5') {
                $finalUserType = '5';
            } else {
                $finalUserType = $userType ?? '1';
            }
        }
        
        // 주문 데이터 포맷팅 (뷰 로직을 컨트롤러로 이동)
        $formattedOrders = [];
        $loginUserId = session()->get('user_id'); // 로그인한 user_id (본인 접수 여부 확인용)
        
        foreach ($orders as $order) {
            $formattedOrder = $order;
            
            // 본인 접수 여부 확인
            $isOwnOrder = false;
            if ($loginUserId && isset($order['insung_user_id']) && !empty($order['insung_user_id'])) {
                $isOwnOrder = ($loginUserId === (string)$order['insung_user_id']);
            }
            
            // 본인이 접수한 주문이 아닌 경우에만 마스킹 처리
            if (!$isOwnOrder) {
                // 이용내역 상세조회 리스트: 출발전화번호, 도착전화번호 마스킹
                if (isset($formattedOrder['departure_contact']) && !empty($formattedOrder['departure_contact']) && $formattedOrder['departure_contact'] !== '-') {
                    $formattedOrder['departure_contact'] = $encryptionHelper->maskPhone($formattedOrder['departure_contact']);
                }
                if (isset($formattedOrder['destination_contact']) && !empty($formattedOrder['destination_contact']) && $formattedOrder['destination_contact'] !== '-') {
                    $formattedOrder['destination_contact'] = $encryptionHelper->maskPhone($formattedOrder['destination_contact']);
                }
                
                // 이용내역 상세조회 리스트: 출발지 주소, 도착지 주소 마스킹
                if (isset($formattedOrder['departure_address']) && !empty($formattedOrder['departure_address']) && $formattedOrder['departure_address'] !== '-') {
                    $formattedOrder['departure_address'] = $encryptionHelper->maskAddress($formattedOrder['departure_address']);
                }
                if (isset($formattedOrder['destination_address']) && !empty($formattedOrder['destination_address']) && $formattedOrder['destination_address'] !== '-') {
                    $formattedOrder['destination_address'] = $encryptionHelper->maskAddress($formattedOrder['destination_address']);
                }
            }
            
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
            
            // 본인이 접수한 주문이 아닌 경우 금액 마스킹 처리 (포맷팅 이후)
            if (!$isOwnOrder) {
                // 이용내역 상세조회 리스트: 기본요금, 정산금액 마스킹
                if (isset($formattedOrder['total_fare_formatted']) && !empty($formattedOrder['total_fare_formatted']) && $formattedOrder['total_fare_formatted'] !== '-') {
                    $formattedOrder['total_fare_formatted'] = $encryptionHelper->maskAmount($formattedOrder['total_fare_formatted']);
                }
                if (isset($formattedOrder['total_amount_formatted']) && !empty($formattedOrder['total_amount_formatted']) && $formattedOrder['total_amount_formatted'] !== '-') {
                    $formattedOrder['total_amount_formatted'] = $encryptionHelper->maskAmount($formattedOrder['total_amount_formatted']);
                }
            }
            
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
                $apiIdx = session()->get('api_idx');
                
                // api_idx가 있으면 그것으로 API 정보 조회 (로그인 시 선택한 API)
                if ($apiIdx) {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    $apiInfo = $insungApiListModel->find($apiIdx);
                    if ($apiInfo) {
                        $mCode = $apiInfo['mcode'] ?? $mCode;
                        $ccCode = $apiInfo['cccode'] ?? $ccCode;
                    }
                }
                
                // m_code, cc_code가 없는 경우 서브도메인 기반으로 조회
                if (empty($mCode) || empty($ccCode)) {
                    $subdomainConfig = config('Subdomain');
                    $apiCodes = $subdomainConfig->getCurrentApiCodes();
                    
                    if ($apiCodes && !empty($apiCodes['m_code']) && !empty($apiCodes['cc_code'])) {
                        $mCode = $apiCodes['m_code'];
                        $ccCode = $apiCodes['cc_code'];
                        $subdomain = $subdomainConfig->getCurrentSubdomain();
                        // log_message('info', "History list - Subdomain access ({$subdomain}) - Using mcode={$mCode}, cccode={$ccCode}");
                    }
                }
                
                if (!empty($mCode) && !empty($ccCode) && !empty($userId)) {
                    $this->syncInsungOrdersViaCLI($mCode, $ccCode, $userId, $startDate, $endDate);
                }
                
                // 인성 API 주문 상세 동기화
                $this->syncInsungOrderDetailsViaCLI();
                
                // 일양 API 주문 상태 동기화 (order_system='ilyang'인 주문들)
                $this->syncIlyangOrdersViaCLI($startDate, $endDate);
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
     * 리스트 페이지 진입 시 일양 API 주문 상태 동기화 (CLI 명령어로 백그라운드 실행)
     * 
     * @param string $startDate 시작 날짜
     * @param string $endDate 종료 날짜
     */
    private function syncIlyangOrdersViaCLI($startDate = null, $endDate = null)
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
                'php %s ilyang:sync-orders %s %s > /dev/null 2>&1 &',
                escapeshellarg($sparkPath),
                escapeshellarg($syncStartDate),
                escapeshellarg($syncEndDate)
            );
            
            // 명령어 실행
            exec($command);
            
            // log_message('info', "Ilyang orders sync CLI command triggered on list page: {$command}");
            
        } catch (\Exception $e) {
            log_message('error', "Exception in syncIlyangOrdersViaCLI: " . $e->getMessage());
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
    
    /**
     * 인성 API 주문 상세 조회
     */
    public function getOrderDetail()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $serialNumber = $this->request->getGet('serial_number') ?? $this->request->getGet('order_number');
        
        if (empty($serialNumber)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호가 필요합니다.'
            ])->setStatusCode(400);
        }
        
        // DB에서 주문의 insung_user_id 조회 (본인 접수 여부 확인용)
        $orderInsungUserId = '';
        try {
            $db = \Config\Database::connect();
            $orderBuilder = $db->table('tbl_orders');
            $orderBuilder->select('insung_user_id');
            $orderBuilder->where('insung_order_number', $serialNumber);
            $orderQuery = $orderBuilder->get();
            if ($orderQuery !== false) {
                $orderResult = $orderQuery->getRowArray();
                if ($orderResult && !empty($orderResult['insung_user_id'])) {
                    $orderInsungUserId = (string)$orderResult['insung_user_id'];
                }
            }
        } catch (\Exception $e) {
            log_message('error', "History::getOrderDetail - Error getting insung_user_id from DB: " . $e->getMessage());
        }

        // API 정보 조회
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        $userId = session()->get('user_id') ?? '';
        
        // 세션에 없으면 서브도메인 또는 DB에서 조회
        if (!$mCode || !$ccCode || !$token || !$apiIdx) {
            $subdomainConfig = config('Subdomain');
            $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
            
            // 서브도메인이 있으면 서브도메인 기반으로 조회
            if ($currentSubdomain && $currentSubdomain !== 'default') {
                $apiInfo = $subdomainConfig->getApiInfoForSubdomain($currentSubdomain);
                
                if ($apiInfo) {
                    $mCode = $apiInfo['m_code'] ?? $mCode;
                    $ccCode = $apiInfo['cc_code'] ?? $ccCode;
                    $token = $apiInfo['token'] ?? $token;
                    $apiIdx = $apiInfo['api_idx'] ?? $apiIdx;
                    
                    // 세션에 저장
                    session()->set('m_code', $mCode);
                    session()->set('cc_code', $ccCode);
                    session()->set('token', $token);
                    session()->set('api_idx', $apiIdx);
                }
            } else {
                // 서브도메인이 없으면 세션의 user_company 또는 cc_code로 조회
                $userCompany = session()->get('user_company');
                $ccCodeFromSession = session()->get('cc_code');
                
                if ($userCompany || $ccCodeFromSession) {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    
                    if ($ccCodeFromSession) {
                        // cc_code로 조회
                        $apiInfo = $insungApiListModel->getApiInfoByCcCode($ccCodeFromSession);
                    } elseif ($userCompany) {
                        // user_company로 조회 (comp_code -> cc_code -> api_info)
                        try {
                            $db = \Config\Database::connect();
                            $compBuilder = $db->table('tbl_company_list c');
                            $compBuilder->select('
                                d.idx as api_idx,
                                d.mcode as m_code,
                                d.cccode as cc_code,
                                d.token
                            ');
                            $compBuilder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
                            $compBuilder->join('tbl_api_list d', 'cc.cc_apicode = d.idx', 'inner');
                            $compBuilder->where('c.comp_code', $userCompany);
                            $compQuery = $compBuilder->get();
                            
                            if ($compQuery !== false) {
                                $apiInfo = $compQuery->getRowArray();
                            }
                        } catch (\Exception $e) {
                            log_message('error', 'History::getOrderDetail - Error getting API info: ' . $e->getMessage());
                            $apiInfo = null;
                        }
                    }
                    
                    if ($apiInfo) {
                        $mCode = $apiInfo['m_code'] ?? $apiInfo['mcode'] ?? $mCode;
                        $ccCode = $apiInfo['cc_code'] ?? $apiInfo['cccode'] ?? $ccCode;
                        $token = $apiInfo['token'] ?? $token;
                        $apiIdx = $apiInfo['api_idx'] ?? $apiInfo['idx'] ?? $apiIdx;
                        
                        // 세션에 저장
                        session()->set('m_code', $mCode);
                        session()->set('cc_code', $ccCode);
                        session()->set('token', $token);
                        session()->set('api_idx', $apiIdx);
                    }
                }
            }
        }

        if (!$mCode || !$ccCode || !$token || !$apiIdx) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보가 설정되지 않았습니다.'
            ])->setStatusCode(500);
        }

        try {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // 인성 API 주문 상세 조회
            $orderDetailResult = $insungApiService->getOrderDetail($mCode, $ccCode, $token, $userId, $serialNumber, $apiIdx);
            
            // API 응답 로그 출력
            // log_message('info', "History::getOrderDetail - API Response: " . json_encode($orderDetailResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            if (!$orderDetailResult || !isset($orderDetailResult['success']) || !$orderDetailResult['success']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $orderDetailResult['message'] ?? '주문 상세 정보를 가져올 수 없습니다.'
                ])->setStatusCode(404);
            }

            $orderData = $orderDetailResult['data'] ?? null;
            
            // 원본 API 응답 데이터도 로그 출력
            if ($orderData) {
                // log_message('info', "History::getOrderDetail - API Raw Data: " . json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
            
            if (!$orderData || !is_array($orderData)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문 상세 데이터가 없습니다.'
                ])->setStatusCode(404);
            }
            
            // 예약일 필드 업데이트 (resolve_time 또는 pickup_time을 reserve_date에 저장)
            try {
                $db = \Config\Database::connect();
                $orderBuilder = $db->table('tbl_orders');
                $orderBuilder->where('insung_order_number', $serialNumber);
                $orderQuery = $orderBuilder->get();
                
                if ($orderQuery !== false) {
                    $order = $orderQuery->getRowArray();
                    
                    if ($order) {
                        // 헬퍼 함수: 객체/배열에서 값 추출
                        $getValue = function($data, $key, $default = null) {
                            if (is_object($data)) {
                                return $data->$key ?? $default;
                            } elseif (is_array($data)) {
                                return $data[$key] ?? $default;
                            }
                            return $default;
                        };
                        
                        // 헬퍼 함수: 날짜/시간 파싱
                        $parseDateTime = function($value) {
                            if (empty($value)) return null;
                            $timestamp = strtotime($value);
                            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                        };
                        
                        $updateData = [];
                        
                        // $apiData[3]: 주문 시간 정보 (order_time, allocation_time, pickup_time, resolve_time, complete_time)
                        if (isset($orderData[3])) {
                            $timeInfo = $orderData[3];
                            
                            // pickup_time (픽업시간)
                            $pickupTime = $getValue($timeInfo, 'pickup_time');
                            $parsedPickup = null;
                            if ($pickupTime) {
                                $parsedPickup = $parseDateTime($pickupTime);
                            }
                            
                            // resolve_time (예약시간)
                            $resolveTime = $getValue($timeInfo, 'resolve_time');
                            if ($resolveTime) {
                                $parsedResolve = $parseDateTime($resolveTime);
                                if ($parsedResolve) {
                                    $updateData['resolve_time'] = $parsedResolve;
                                    // resolve_time이 있으면 reserve_date에도 저장 (예약시간)
                                    $updateData['reserve_date'] = $parsedResolve;
                                }
                            } elseif ($parsedPickup && empty($order['reserve_date'] ?? null)) {
                                // resolve_time이 없고 pickup_time이 있으면 reserve_date에 저장 (예약일 때는 예약시간)
                                $updateData['reserve_date'] = $parsedPickup;
                            }
                            
                            // 업데이트 실행
                            if (!empty($updateData)) {
                                $updateData['updated_at'] = date('Y-m-d H:i:s');
                                $orderBuilder = $db->table('tbl_orders');
                                $orderBuilder->where('insung_order_number', $serialNumber);
                                $orderBuilder->update($updateData);
                                
                                log_message('info', "History::getOrderDetail - 예약일 필드 업데이트 완료: serial_number={$serialNumber}, reserve_date=" . ($updateData['reserve_date'] ?? 'N/A'));
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // 예약일 필드 업데이트 실패해도 상세 정보는 반환
                log_message('error', "History::getOrderDetail - 예약일 필드 업데이트 실패: " . $e->getMessage());
            }

            // 인성 API 응답 구조: 배열 형태로 여러 객체가 들어있음
            // 모든 필드를 평탄화하여 추출
            $flattenData = function($data, $prefix = '') use (&$flattenData) {
                $result = [];
                
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        $newKey = $prefix ? ($prefix . '_' . $key) : $key;
                        
                        if (is_array($value) || is_object($value)) {
                            // 중첩된 배열/객체는 재귀적으로 처리
                            $nested = $flattenData($value, $newKey);
                            $result = array_merge($result, $nested);
                        } else {
                            $result[$newKey] = $value;
                        }
                    }
                } elseif (is_object($data)) {
                    foreach ($data as $key => $value) {
                        $newKey = $prefix ? ($prefix . '_' . $key) : $key;
                        
                        if (is_array($value) || is_object($value)) {
                            // 중첩된 배열/객체는 재귀적으로 처리
                            $nested = $flattenData($value, $newKey);
                            $result = array_merge($result, $nested);
                        } else {
                            $result[$newKey] = $value;
                        }
                    }
                }
                
                return $result;
            };
            
            // 전체 응답 데이터를 평탄화
            // 배열의 각 객체를 하나의 객체로 병합 (접두사 없이)
            $allFields = [];
            
            // $orderData가 배열인지 확인
            if (is_array($orderData)) {
                // 배열의 각 인덱스별로 처리
                foreach ($orderData as $index => $item) {
                    if (is_array($item) || is_object($item)) {
                        // 각 객체의 키를 직접 병합 (접두사 없이)
                        foreach ($item as $key => $value) {
                            // code, msg 같은 메타 정보는 제외
                            if ($key === 'code' || $key === 'msg') {
                                continue;
                            }
                            // 이미 존재하는 키는 덮어쓰지 않음 (첫 번째 값 우선)
                            if (!isset($allFields[$key]) || empty($allFields[$key])) {
                                $allFields[$key] = $value;
                            }
                        }
                    } else {
                        $allFields["index_{$index}"] = $item;
                    }
                }
            } elseif (is_object($orderData)) {
                // 객체인 경우 직접 변환
                foreach ($orderData as $key => $value) {
                    if ($key !== 'code' && $key !== 'msg') {
                        $allFields[$key] = $value;
                    }
                }
            } else {
                // 배열도 객체도 아닌 경우
                log_message('error', "History::getOrderDetail - orderData is not array or object: " . gettype($orderData));
            }
            
            // serial_number 추가
            $allFields['serial_number'] = $serialNumber;
            $allFields['order_number'] = $serialNumber;
            
            // 사용자 권한 정보 (마스킹 처리용)
            $loginType = session()->get('login_type');
            $userClass = session()->get('user_class');
            $userType = session()->get('user_type');
            $loginUserId = session()->get('user_id'); // 로그인한 user_id
            
            // user_class가 없으면 DB에서 조회
            if (empty($userClass) && $loginType === 'daumdata' && $loginUserId) {
                $db = \Config\Database::connect();
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->select('user_class');
                $userBuilder->where('user_id', $loginUserId);
                $userQuery = $userBuilder->get();
                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult && isset($userResult['user_class'])) {
                        $userClass = $userResult['user_class'];
                    }
                }
            }
            
            // user_type 결정 (user_class 우선, 없으면 user_type 사용)
            $finalUserType = '1';
            if ($loginType === 'daumdata') {
                if ($userClass == '1' || $userClass == '3') {
                    $finalUserType = '1';
                } elseif ($userClass == '5') {
                    $finalUserType = '5';
                } else {
                    $finalUserType = $userType ?? '1';
                }
            }
            
            // 주문의 insung_user_id는 이미 DB에서 조회한 값 사용 ($orderInsungUserId)
            // 디버깅용 로그
            $isOwnOrder = ($loginUserId && $orderInsungUserId && $loginUserId === $orderInsungUserId);
            // log_message('info', "History::getOrderDetail - Masking check: finalUserType={$finalUserType}, loginUserId={$loginUserId}, orderInsungUserId={$orderInsungUserId}, isOwnOrder=" . ($isOwnOrder ? 'YES' : 'NO') . ", shouldMask=" . ($finalUserType === '5' && $loginUserId && !$isOwnOrder ? 'YES' : 'NO'));
            
            // 마스킹 처리용 정보 추가
            $allFields['user_type'] = $finalUserType;
            $allFields['login_user_id'] = $loginUserId;
            $allFields['order_insung_user_id'] = $orderInsungUserId;
            
            // 팝업에서 마스킹 처리 (user_type=5이고 본인이 접수한 것이 아닌 경우)
            // DB에서 조회한 insung_user_id와 로그인한 user_id를 비교
            $shouldMask = false;
            if ($finalUserType === '5' && $loginUserId) {
                if (empty($orderInsungUserId)) {
                    // insung_user_id가 없으면 무조건 마스킹 (안전을 위해)
                    $shouldMask = true;
                } elseif ($loginUserId !== $orderInsungUserId) {
                    // 본인이 접수한 것이 아니면 마스킹
                    $shouldMask = true;
                }
                // 본인이 접수한 경우 ($loginUserId === $orderInsungUserId)는 마스킹하지 않음
            }
            
            if ($shouldMask) {
                $encryptionHelper = new \App\Libraries\EncryptionHelper();
                
                // 전화번호 마스킹
                $phoneFields = ['customer_tel_number', 'rider_tel_number', 'departure_tel_number', 'destination_tel_number', 'happy_call'];
                foreach ($phoneFields as $field) {
                    if (isset($allFields[$field]) && !empty($allFields[$field]) && $allFields[$field] !== '-') {
                        $allFields[$field] = $encryptionHelper->maskPhone($allFields[$field]);
                    }
                }
                
                // 주소 마스킹
                $addressFields = ['departure_address', 'destination_address'];
                foreach ($addressFields as $field) {
                    if (isset($allFields[$field]) && !empty($allFields[$field]) && $allFields[$field] !== '-') {
                        $allFields[$field] = $encryptionHelper->maskAddress($allFields[$field]);
                    }
                }
                
                // log_message('info', "History::getOrderDetail - Masking applied to phone and address fields");
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $allFields,
                'raw_data' => $orderData // 원본 데이터도 함께 전달 (디버깅용)
            ]);
            
        } catch (\Exception $e) {
            log_message('error', "History::getOrderDetail - Error: " . $e->getMessage());
            log_message('error', "History::getOrderDetail - Stack trace: " . $e->getTraceAsString());
            log_message('error', "History::getOrderDetail - File: " . $e->getFile() . ", Line: " . $e->getLine());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 상세 정보 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 인성 API 주문 사인 조회
     */
    public function getOrderSign()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $serialNumber = $this->request->getGet('serial_number') ?? $this->request->getGet('order_number');
        
        if (empty($serialNumber)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호가 필요합니다.'
            ])->setStatusCode(400);
        }

        // API 정보 조회
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        $userId = session()->get('user_id') ?? '';
        
        // 세션에 없으면 서브도메인 또는 DB에서 조회
        if (!$mCode || !$ccCode || !$token || !$apiIdx) {
            $subdomainConfig = config('Subdomain');
            $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
            
            // 서브도메인이 있으면 서브도메인 기반으로 조회
            if ($currentSubdomain && $currentSubdomain !== 'default') {
                $apiInfo = $subdomainConfig->getApiInfoForSubdomain($currentSubdomain);
                
                if ($apiInfo) {
                    $mCode = $apiInfo['m_code'] ?? $mCode;
                    $ccCode = $apiInfo['cc_code'] ?? $ccCode;
                    $token = $apiInfo['token'] ?? $token;
                    $apiIdx = $apiInfo['api_idx'] ?? $apiIdx;
                    
                    // 세션에 저장
                    session()->set('m_code', $mCode);
                    session()->set('cc_code', $ccCode);
                    session()->set('token', $token);
                    session()->set('api_idx', $apiIdx);
                }
            } else {
                // 서브도메인이 없으면 세션의 user_company 또는 cc_code로 조회
                $userCompany = session()->get('user_company');
                $ccCodeFromSession = session()->get('cc_code');
                
                if ($userCompany || $ccCodeFromSession) {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    
                    if ($ccCodeFromSession) {
                        // cc_code로 조회
                        $apiInfo = $insungApiListModel->getApiInfoByCcCode($ccCodeFromSession);
                    } elseif ($userCompany) {
                        // user_company로 조회 (comp_code -> cc_code -> api_info)
                        try {
                            $db = \Config\Database::connect();
                            $compBuilder = $db->table('tbl_company_list c');
                            $compBuilder->select('
                                d.idx as api_idx,
                                d.mcode as m_code,
                                d.cccode as cc_code,
                                d.token
                            ');
                            $compBuilder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
                            $compBuilder->join('tbl_api_list d', 'cc.cc_apicode = d.idx', 'inner');
                            $compBuilder->where('c.comp_code', $userCompany);
                            $compQuery = $compBuilder->get();
                            
                            if ($compQuery !== false) {
                                $apiInfo = $compQuery->getRowArray();
                            }
                        } catch (\Exception $e) {
                            log_message('error', 'History::getOrderSign - Error getting API info: ' . $e->getMessage());
                            $apiInfo = null;
                        }
                    }
                    
                    if ($apiInfo) {
                        $mCode = $apiInfo['m_code'] ?? $apiInfo['mcode'] ?? $mCode;
                        $ccCode = $apiInfo['cc_code'] ?? $apiInfo['cccode'] ?? $ccCode;
                        $token = $apiInfo['token'] ?? $token;
                        $apiIdx = $apiInfo['api_idx'] ?? $apiInfo['idx'] ?? $apiIdx;
                        
                        // 세션에 저장
                        session()->set('m_code', $mCode);
                        session()->set('cc_code', $ccCode);
                        session()->set('token', $token);
                        session()->set('api_idx', $apiIdx);
                    }
                }
            }
        }

        if (!$mCode || !$ccCode || !$token || !$apiIdx) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보가 설정되지 않았습니다.'
            ])->setStatusCode(500);
        }

        try {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // 인성 API 주문 사인 조회
            $orderSignResult = $insungApiService->getOrderSign($mCode, $ccCode, $token, $userId, $serialNumber, $apiIdx);
            
            if (!$orderSignResult || !isset($orderSignResult['success']) || !$orderSignResult['success']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $orderSignResult['message'] ?? '주문 사인 정보를 가져올 수 없습니다.'
                ])->setStatusCode(404);
            }

            $signData = $orderSignResult['data'] ?? null;
            
            if (!$signData) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문 사인 데이터가 없습니다.'
                ])->setStatusCode(404);
            }

            // 응답 데이터 추출 (departure_sign, destination_sign, receipt_url)
            $result = [
                'departure_sign' => '',
                'destination_sign' => '',
                'receipt_url' => ''
            ];
            
            // JSON 응답 구조에 따라 데이터 추출
            // 배열의 첫 번째 요소에서 추출하거나, 직접 객체에서 추출
            $dataItem = null;
            
            // signData가 이미 객체로 전달되므로 직접 사용
            if (is_array($signData)) {
                // 배열인 경우 departure_sign, destination_sign, receipt_url 필드가 있는 요소 찾기
                $dataItem = null;
                foreach ($signData as $item) {
                    if (is_array($item)) {
                        if (isset($item['departure_sign']) || isset($item['destination_sign']) || isset($item['receipt_url'])) {
                            $dataItem = $item;
                            break;
                        }
                    } elseif (is_object($item)) {
                        if (isset($item->departure_sign) || isset($item->destination_sign) || isset($item->receipt_url)) {
                            $dataItem = $item;
                            break;
                        }
                    }
                }
                
                // 필드가 있는 요소를 찾지 못한 경우 첫 번째 요소 사용
                if (!$dataItem && isset($signData[0])) {
                    $dataItem = $signData[0];
                } elseif (!$dataItem) {
                    // 배열이지만 인덱스가 없는 경우 (연관 배열)
                    $dataItem = $signData;
                }
            } elseif (is_object($signData)) {
                $dataItem = $signData;
            }
            
            if ($dataItem) {
                // departure_sign 추출 (배열과 객체 모두 처리)
                if (is_array($dataItem)) {
                    $result['departure_sign'] = isset($dataItem['departure_sign']) ? (string)$dataItem['departure_sign'] : '';
                    $result['destination_sign'] = isset($dataItem['destination_sign']) ? (string)$dataItem['destination_sign'] : '';
                    $result['receipt_url'] = isset($dataItem['receipt_url']) ? (string)$dataItem['receipt_url'] : '';
                } elseif (is_object($dataItem)) {
                    $result['departure_sign'] = isset($dataItem->departure_sign) ? (string)$dataItem->departure_sign : '';
                    $result['destination_sign'] = isset($dataItem->destination_sign) ? (string)$dataItem->destination_sign : '';
                    $result['receipt_url'] = isset($dataItem->receipt_url) ? (string)$dataItem->receipt_url : '';
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            log_message('error', "History::getOrderSign - Error: " . $e->getMessage());
            log_message('error', "History::getOrderSign - Stack trace: " . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 사인 정보 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}

