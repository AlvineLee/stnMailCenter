<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DeliveryModel;
use App\Models\UserPreferencesModel;

class Delivery extends BaseController
{
    protected $deliveryModel;

    public function __construct()
    {
        $this->deliveryModel = new DeliveryModel();
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
                    // log_message('info', "Delivery list - Subdomain filter applied: {$currentSubdomain}, comp_code={$subdomainCompCode}, m_code={$apiCodes['m_code']}, cc_code={$apiCodes['cc_code']}");
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
        $result = $this->deliveryModel->getDeliveryList($filters, $page, $perPage);
        $orders = $result['orders'];
        $totalCount = $result['total_count'];
        
        // 서비스 통계 조회
        $serviceTypes = $this->deliveryModel->getServiceStats(
            $userRole !== 'super_admin' ? $customerId : null
        );
        
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
            base_url('delivery/list'),
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
                        log_message('info', "Delivery list - Subdomain access ({$subdomain}) - Using mcode={$mCode}, cccode={$ccCode}");
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
            $columnOrder = $userPreferencesModel->getColumnOrder($loginType, (string)$userId, 'delivery');
        }
        
        // 주문 데이터 포맷팅 (뷰 로직을 컨트롤러로 이동)
        // 전화번호 필드 복호화 처리
        $encryptionHelper = new \App\Libraries\EncryptionHelper();
        $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno'];
        
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
        
        $formattedOrders = [];
        foreach ($orders as $order) {
            // 전화번호 필드 복호화
            $order = $encryptionHelper->decryptFields($order, $phoneFields);
            $formattedOrder = $order;
            
            // 리스트에서 항상 마스킹 처리할 필드 (권한 상관없이)
            // 배송조회 리스트: 라이더연락처 항상 마스킹
            if (isset($formattedOrder['rider_tel_number']) && !empty($formattedOrder['rider_tel_number']) && $formattedOrder['rider_tel_number'] !== '-') {
                $formattedOrder['rider_tel_number'] = $encryptionHelper->maskPhone($formattedOrder['rider_tel_number']);
            }
            
            // 상태 라벨 및 맵 표시 조건 처리
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
            
            // 일양 주문인 경우 배송정보 상세 조회 가능 플래그
            $formattedOrder['show_ilyang_detail'] = (
                ($order['order_system'] ?? '') === 'ilyang' &&
                !empty($order['shipping_tracking_number'])
            );
            $formattedOrder['ilyang_tracking_number'] = $order['shipping_tracking_number'] ?? '';
            
            // 일양 주문인 경우 맵 표시 비활성화 (일양은 상세 조회만 가능)
            $isIlyangOrder = ($order['order_system'] ?? '') === 'ilyang';
            $formattedOrder['show_map_on_click'] = !$isIlyangOrder && $showMapOnClick && !empty($insungOrderNumberForMap);
            $formattedOrder['is_riding'] = $isRiding;
            $formattedOrder['insung_order_number_for_map'] = $insungOrderNumberForMap;
            
            // 주문번호 표시용 (인성 API 주문의 경우 insung_order_number 우선)
            if (($order['order_system'] ?? '') === 'insung' && !empty($order['insung_order_number'])) {
                $formattedOrder['display_order_number'] = $order['insung_order_number'];
            } else {
                $formattedOrder['display_order_number'] = $order['order_number'] ?? '-';
            }
            
            // 송장출력 버튼 표시 조건
            $serviceName = $order['service_name'] ?? '';
            $serviceCategory = $order['service_category'] ?? '';
            $serviceCode = $order['service_code'] ?? '';
            $trackingNumber = $order['shipping_tracking_number'] ?? '';
            
            $isShippingService = (
                $serviceCategory === 'international' || 
                $serviceCategory === 'parcel' ||
                $serviceCategory === 'special' ||
                $serviceCode === 'international' ||
                $serviceCode === 'parcel-visit' ||
                $serviceCode === 'parcel-same-day' ||
                $serviceCode === 'parcel-convenience' ||
                $serviceCode === 'parcel-night' ||
                $serviceCode === 'parcel-bag' ||
                strpos($serviceName, '해외특송') !== false ||
                strpos($serviceName, '택배') !== false
            );
            
            $formattedOrder['show_waybill_button'] = (
                ($order['status'] ?? '') === 'processing' &&
                !empty($trackingNumber) &&
                $isShippingService
            );
            
            // 결제 타입 라벨
            $paymentLabels = [
                'cash_on_delivery' => '착불',
                'cash_in_advance' => '선불',
                'bank_transfer' => '계좌이체',
                'credit_transaction' => '신용거래'
            ];
            $formattedOrder['payment_type_label'] = $paymentLabels[$order['payment_type'] ?? ''] ?? ($order['payment_type'] ?? '-');
            
            // 상태 라벨 (일반 주문용 - data-column-index="19"용)
            $generalStatusLabels = [
                'pending' => '대기중',
                'processing' => '접수완료',
                'completed' => '배송중',
                'delivered' => '배송완료',
                'cancelled' => '취소',
                'api_failed' => 'API실패'
            ];
            $formattedOrder['general_status_label'] = $generalStatusLabels[$order['status'] ?? ''] ?? ($order['status'] ?? '-');
            
            $formattedOrders[] = $formattedOrder;
        }
        
        $data = [
            'title' => '배송조회(리스트)',
            'content_header' => [
                'title' => '배송조회(리스트)',
                'description' => '전체 배송 현황을 조회할 수 있습니다.'
            ],
            'orders' => $formattedOrders,
            'pagination' => $paginationHelper,
            'service_types' => $serviceTypes,
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
        
        return view('delivery/list', $data);
    }
    
    /**
     * 주문 상세 정보 조회 (AJAX) - 암호화된 주문번호 사용
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
        
        $encryptedOrderNumber = $this->request->getGet('order_number');
        if (!$encryptedOrderNumber) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호가 필요합니다.'
            ])->setStatusCode(400);
        }
        
        try {
            // 인코딩된 주문번호 디코딩 (간단한 Base64 디코딩)
            $orderNumber = base64_decode($encryptedOrderNumber);
            if (!$orderNumber) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '유효하지 않은 주문번호입니다.'
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Order number decoding failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호 디코딩에 실패했습니다.'
            ])->setStatusCode(400);
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        try {
            // Model을 통한 주문 상세 정보 조회
            $order = $this->deliveryModel->getOrderDetail(
                $orderNumber, 
                $userRole !== 'super_admin' ? $customerId : null
            );
            
            if (!$order) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문 정보를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }
            
            // 전화번호 필드 복호화 처리
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno', 'waypoint_contact', 'driver_contact'];
            $order = $encryptionHelper->decryptFields($order, $phoneFields);
            
            // 라벨 매핑
            $statusLabels = [
                'pending' => '대기중',
                'processing' => '접수완료',
                'completed' => '배송중',
                'delivered' => '배송완료',
                'cancelled' => '취소',
                'api_failed' => 'API실패'
            ];
            
            $paymentLabels = [
                'cash_on_delivery' => '착불',
                'cash_in_advance' => '선불',
                'bank_transfer' => '계좌이체',
                'credit_transaction' => '신용거래'
            ];
            
            $urgencyLabels = [
                'normal' => '일반',
                'urgent' => '긴급',
                'super_urgent' => '초긴급'
            ];
            
            $deliveryMethodLabels = [
                'motorcycle' => '퀵오토바이',
                'vehicle' => '퀵차량',
                'flex' => '퀵플렉스',
                'moving' => '퀵이사'
            ];
            
            // 사용자 권한 정보 (마스킹 처리용)
            $loginType = session()->get('login_type');
            $userClass = session()->get('user_class');
            $userType = session()->get('user_type');
            $loginUserId = session()->get('user_id'); // 로그인한 user_id
            $orderInsungUserId = $order['insung_user_id'] ?? ''; // 주문의 insung_user_id
            
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
            
            // 팝업에서 마스킹 처리 (user_type=5이고 본인이 접수한 것이 아닌 경우)
            if ($finalUserType === '5' && $loginUserId && $orderInsungUserId && $loginUserId !== $orderInsungUserId) {
                foreach ($phoneFields as $field) {
                    if (isset($order[$field]) && !empty($order[$field]) && $order[$field] !== '-') {
                        $order[$field] = $encryptionHelper->maskPhone($order[$field]);
                    }
                }
            }
            
            // 응답 데이터 구성
            $responseData = [
                'success' => true,
                'data' => [
                    'order_number' => $order['order_number'],
                    'service_name' => $order['service_name'],
                    'service_code' => $order['service_code'] ?? '',
                    'service_category' => $order['service_category'],
                    'customer_name' => $order['customer_name'],
                    'shipping_tracking_number' => $order['shipping_tracking_number'] ?? '',
                    'shipping_platform_code' => $order['shipping_platform_code'] ?? '',
                    'user_name' => $order['user_name'],
                    'status' => $order['status'],
                    // 마스킹 처리용 정보
                    'user_type' => $finalUserType,
                    'login_user_id' => $loginUserId,
                    'order_insung_user_id' => $orderInsungUserId,
                    'status_label' => $statusLabels[$order['status']] ?? $order['status'],
                    'created_at' => $order['created_at'],
                    'updated_at' => $order['updated_at'],
                    'total_amount' => number_format($order['total_amount']) . '원',
                    'payment_type' => $paymentLabels[$order['payment_type']] ?? $order['payment_type'],
                    'notes' => $order['notes'],
                    
                    // 주문자 정보
                    'company_name' => $order['company_name'],
                    'contact' => $order['contact'],
                    'address' => $order['address'],
                    
                    // 출발지 정보
                    'departure_address' => $order['departure_address'],
                    'departure_detail' => $order['departure_detail'],
                    'departure_contact' => $order['departure_contact'],
                    
                    // 경유지 정보
                    'waypoint_address' => $order['waypoint_address'],
                    'waypoint_detail' => $order['waypoint_detail'],
                    'waypoint_contact' => $order['waypoint_contact'],
                    'waypoint_notes' => $order['waypoint_notes'],
                    
                    // 도착지 정보
                    'destination_type' => $order['destination_type'],
                    'mailroom' => $order['mailroom'],
                    'destination_address' => $order['destination_address'],
                    'detail_address' => $order['detail_address'],
                    'destination_contact' => $order['destination_contact'],
                    
                    // 물품 정보
                    'item_type' => $order['item_type'],
                    'quantity' => $order['quantity'],
                    'unit' => $order['unit'],
                    'delivery_content' => $order['delivery_content'],
                    
                    // 퀵 서비스 정보
                    'delivery_method' => $order['delivery_method'],
                    'delivery_method_label' => $deliveryMethodLabels[$order['delivery_method']] ?? $order['delivery_method'],
                    'urgency_level' => $order['urgency_level'],
                    'urgency_label' => $urgencyLabels[$order['urgency_level']] ?? $order['urgency_level'],
                    'estimated_time' => $order['estimated_time'],
                    'pickup_time' => $order['pickup_time'],
                    'delivery_time' => $order['delivery_time'],
                    'driver_contact' => $order['driver_contact'],
                    'vehicle_info' => $order['vehicle_info'],
                    'delivery_instructions' => $order['delivery_instructions'],
                    'delivery_route' => $order['delivery_route'],
                    'box_selection' => $order['box_selection'],
                    'box_quantity' => $order['box_quantity'],
                    'pouch_selection' => $order['pouch_selection'],
                    'pouch_quantity' => $order['pouch_quantity'],
                    'shopping_bag_selection' => $order['shopping_bag_selection'],
                    'additional_fee' => $order['additional_fee'] ? number_format($order['additional_fee']) . '원' : '0원'
                ]
            ];
            
            return $this->response->setJSON($responseData);
            
        } catch (\Exception $e) {
            log_message('error', 'Order detail query failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 정보 조회 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 일양 배송정보 상세 조회
     */
    public function getIlyangDeliveryDetail()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $trackingNumber = $this->request->getGet('tracking_number');
        if (!$trackingNumber) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '운송장번호가 필요합니다.'
            ])->setStatusCode(400);
        }
        
        try {
            // 일양 API 서비스 초기화
            $apiService = \App\Libraries\ApiServiceFactory::create('ilyang', true); // 테스트 모드
            
            if (!$apiService) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '일양 API 서비스를 초기화할 수 없습니다.'
                ])->setStatusCode(500);
            }
            
            // 일양 API로 배송정보 조회
            $result = $apiService->getDeliveryStatus([$trackingNumber]);
            
            if (!$result || !$result['success']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $result['error'] ?? '배송정보를 조회할 수 없습니다.',
                    'return_code' => $result['return_code'] ?? '',
                    'return_desc' => $result['return_desc'] ?? ''
                ])->setStatusCode(500);
            }
            
            // API 응답 데이터 반환
            return $this->response->setJSON([
                'success' => true,
                'data' => $result['data'] ?? [],
                'return_code' => $result['return_code'] ?? '',
                'return_desc' => $result['return_desc'] ?? '',
                'success_count' => $result['success_count'] ?? 0,
                'total_count' => $result['total_count'] ?? 0
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::getIlyangDeliveryDetail - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '배송정보 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 주문 상태 변경
     */
    public function updateStatus()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }
        
        $orderNumber = $inputData['order_number'] ?? null;
        $newStatus = $inputData['status'] ?? null;
        
        if (!$orderNumber || !$newStatus) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호와 상태가 필요합니다.'
            ])->setStatusCode(400);
        }
        
        // 유효한 상태인지 확인
        $validStatuses = ['pending', 'processing', 'completed', 'delivered'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '유효하지 않은 상태입니다.'
            ])->setStatusCode(400);
        }
        
        try {
            // 주문 조회
            $order = $this->deliveryModel->getOrderDetail($orderNumber);
            
            if (!$order) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문을 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }
            
            // 상태 업데이트 (Model 사용) - 모든 서비스에 대해 상태 변경 허용
            $orderModel = new \App\Models\OrderModel();
            $result = $orderModel->updateOrderStatus($order['id'], $newStatus);
            
            // 상태가 '접수완료(processing)'로 변경되고, 송장번호가 없고, 택배/해외특송 서비스인 경우 송장번호 할당
            if ($result && $newStatus === 'processing' && empty($order['shipping_tracking_number'])) {
                $serviceCode = $order['service_code'] ?? '';
                $serviceCategory = $order['service_category'] ?? '';
                
                // 택배 서비스 또는 해외특송 서비스인지 확인
                $isShippingService = (
                    in_array($serviceCode, ['international', 'parcel-visit', 'parcel-same-day', 'parcel-convenience', 'parcel-night', 'parcel-bag']) ||
                    $serviceCategory === 'parcel' ||
                    $serviceCategory === 'special'
                );
                
                if ($isShippingService) {
                    try {
                        // 송장번호 할당 로직 (Service::create와 동일)
                        $apiServiceFactory = \App\Libraries\ApiServiceFactory::class;
                        $activeShippingCompany = \App\Libraries\ApiServiceFactory::getActiveShippingCompany($serviceCode);
                        
                        if ($activeShippingCompany) {
                            $platformCode = $activeShippingCompany['platform_code'];
                            $awbPoolModel = new \App\Models\AwbPoolModel();
                            $awbPool = $awbPoolModel->getAvailableAwbNo();
                            
                            if ($awbPool && isset($awbPool['awb_no'])) {
                                $awbNo = $awbPool['awb_no'];
                                
                                // 송장번호 업데이트
                                $orderModel->updateShippingInfo($order['id'], $platformCode, $awbNo);
                                
                                // 송장번호 풀에서 사용 처리 (awb_no로 직접 업데이트)
                                $markResult = $awbPoolModel->markAsUsedByAwbNo($awbNo, $orderNumber);
                                
                                if (!$markResult) {
                                    log_message('error', "AWB No assignment failed on status change: {$awbNo} to order: {$orderNumber}");
                                }
                                
                                log_message('info', "AWB No assigned on status change: {$awbNo} (Platform: {$platformCode}) to order: {$orderNumber}");
                            } else {
                                log_message('warning', "No available AWB number in pool for order: {$orderNumber}");
                                // 송장번호가 없어도 플랫폼코드는 저장
                                $orderModel->updateShippingInfo($order['id'], $platformCode);
                            }
                        }
                    } catch (\Exception $e) {
                        log_message('error', 'AWB assignment on status change failed: ' . $e->getMessage());
                        // 송장번호 할당 실패해도 상태 변경은 성공으로 처리
                    }
                }
            }
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => '주문 상태가 변경되었습니다.'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '상태 변경에 실패했습니다.'
                ])->setStatusCode(500);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::updateStatus - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '상태 변경 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 송장출력 페이지
     */
    public function printWaybill()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $orderNumber = $this->request->getGet('order_number');
        $trackingNumber = $this->request->getGet('tracking_number');
        
        if (!$orderNumber || !$trackingNumber) {
            return redirect()->to('/delivery/list')->with('error', '주문번호와 송장번호가 필요합니다.');
        }
        
        try {
            // 주문 정보 조회
            $order = $this->deliveryModel->getOrderDetail($orderNumber);
            
            if (!$order) {
                return redirect()->to('/delivery/list')->with('error', '주문을 찾을 수 없습니다.');
            }
            
            // 송장번호 조회 API 호출
            $apiService = \App\Libraries\ApiServiceFactory::createForService(
                $order['service_code'] ?? 'international', 
                true // 테스트 모드
            );
            
            if (!$apiService) {
                return redirect()->to('/delivery/list')->with('error', 'API 서비스를 초기화할 수 없습니다.');
            }
            
            // 송장번호 조회 (주문 데이터 전달)
            $waybillData = $apiService->getWaybillData($trackingNumber, $order);
            
            if (!$waybillData || !$waybillData['success']) {
                return redirect()->to('/delivery/list')->with('error', '송장 정보를 조회할 수 없습니다.');
            }
            
            // 일양 주문인지 확인 (order_system 또는 order_number로 판단)
            $isIlyangOrder = false;
            if (($order['order_system'] ?? '') === 'ilyang') {
                $isIlyangOrder = true;
            } elseif (!empty($order['order_number']) && strpos($order['order_number'], 'ILYANG-') === 0) {
                // 주문번호가 ILYANG-으로 시작하는 경우도 일양 주문으로 인식
                $isIlyangOrder = true;
            }
            
            // 일양 주문인 경우 별도 테이블에서 접수 데이터 조회
            $ilyangOrderData = null;
            if ($isIlyangOrder) {
                $ilyangOrderModel = new \App\Models\IlyangOrderModel();
                $ilyangOrderData = $ilyangOrderModel->getByOrderId($order['id']);
            }
            
            $data = [
                'title' => '송장출력',
                'order' => $order,
                'waybill_data' => $waybillData['data'] ?? [],
                'ilyang_order_data' => $ilyangOrderData  // 일양 접수 데이터
            ];
            
            // 일양 주문인 경우 새로운 일양 전용 운송장 양식 사용
            if ($isIlyangOrder) {
                return view('delivery/print_waybill_ilyang', $data);
            } else {
                return view('delivery/print_waybill', $data);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::printWaybill - ' . $e->getMessage());
            return redirect()->to('/delivery/list')->with('error', '송장출력 중 오류가 발생했습니다.');
        }
    }

    /**
     * 인성 API 주문 동기화 (AJAX)
     * 배송조회 리스트 진입 시 인성 주문번호가 있는 주문들의 최신 상태를 동기화
     * CLI 명령어로 백그라운드 실행
     */
    public function syncInsungOrders()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $loginType = session()->get('login_type');
        
        // daumdata 또는 STN 로그인만 동기화 가능
        if (!in_array($loginType, ['daumdata', 'stn'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '인성 API 동기화는 daumdata 또는 STN 로그인에서만 가능합니다.'
            ])->setStatusCode(403);
        }
        
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
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'CLI 명령어를 실행할 수 없습니다.'
                ])->setStatusCode(500);
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
            // log_message('info', "Insung order details sync CLI command triggered: {$command}");
            
            return $this->response->setJSON([
                'success' => true,
                'message' => '동기화가 백그라운드에서 실행 중입니다.'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::syncInsungOrders - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '동기화 시작 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 인성 API 주문 동기화 (기존 로직 - 참고용, 사용 안 함)
     * CLI로 변경됨
     */
    private function syncInsungOrdersOld()
    {
        // 이 메서드는 더 이상 사용하지 않음 (CLI로 변경됨)
        // 참고용으로만 남겨둠
        try {
            // 사용자 권한에 따른 필터 조건 구성 (선택적)
            $userType = session()->get('user_type');
            $ccCode = session()->get('cc_code');
            $compName = session()->get('comp_name');
            
            $filters = [];
            if ($userType == '3') {
                // user_type = 3: 소속 콜센터의 고객사 주문만
                $filters['cc_code'] = $ccCode;
            } elseif ($userType == '5') {
                // user_type = 5: 본인 고객사의 주문만
                $filters['comp_name'] = $compName;
            }
            // user_type = 1: 필터 없음 (전체 조회)
            
            // 인성 주문번호가 있는 주문들 조회 (API 정보 포함)
            $orders = $this->deliveryModel->getInsungOrdersForSync($filters);
            
            if (empty($orders)) {
                return $this->response->setJSON([
                    'success' => true,
                    'synced_count' => 0,
                    'message' => '동기화할 주문이 없습니다.'
                ]);
            }
            
            // 인성 API 서비스 초기화
            $insungApiService = new \App\Libraries\InsungApiService();
            $orderModel = new \App\Models\OrderModel();
            
            $syncedCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $errors = [];
            
            // 각 주문에 대해 인성 API로 상세 조회 및 업데이트
            foreach ($orders as $order) {
                try {
                    // 주문별 API 정보 확인 (DB에서 조회된 정보 사용)
                    $mCode = $order['m_code'] ?? null;
                    $ccCode = $order['cc_code'] ?? null;
                    $token = $order['token'] ?? null;
                    $userId = $order['insung_user_id'] ?? null;
                    $apiIdx = $order['api_idx'] ?? null;
                    $serialNumber = $order['insung_order_number'] ?? null;
                    
                    // STN 로그인 주문의 경우 기본 API 정보 사용
                    if (empty($mCode) || empty($ccCode)) {
                        $mCode = '4540';
                        $ccCode = '7829';
                        $userId = '에스티엔온라인접수';
                        
                        // 기본 API 정보로 api_idx 조회
                        $insungApiListModel = new \App\Models\InsungApiListModel();
                        $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                        if ($apiInfo) {
                            $apiIdx = $apiInfo['idx'];
                            $token = $apiInfo['token'] ?? '';
                        }
                        
                        log_message('info', "STN login order sync - Using default API info: mcode={$mCode}, cccode={$ccCode}, user_id={$userId}");
                    }
                    
                    // 필수 API 정보가 없으면 스킵
                    if (!$mCode || !$ccCode || !$token || !$userId || !$apiIdx || !$serialNumber) {
                        $skippedCount++;
                        $errors[] = "주문번호 {$order['order_number']}: API 정보가 불완전합니다.";
                        log_message('warning', "Insung order sync skipped: Order ID {$order['id']}, Missing API info");
                        continue;
                    }
                    
                    // 인성 API로 주문 상세 조회 (CLI에서 리스트 형태로 처리하므로 주석처리)
                    /*
                    $apiResult = $insungApiService->getOrderDetail($mCode, $ccCode, $token, $userId, $serialNumber, $apiIdx);
                    
                    if (!$apiResult['success'] || !isset($apiResult['data'])) {
                        $errorCount++;
                        $errors[] = "주문번호 {$order['order_number']}: {$apiResult['message']}";
                        log_message('warning', "Insung order sync failed: Order ID {$order['id']}, Message: {$apiResult['message']}");
                        continue;
                    }
                    
                    $apiData = $apiResult['data'];
                    
                    // API 응답 파싱 (인성 API Response 구조)
                    // $apiData[0]: 응답 코드
                    // $apiData[1]: 고객 정보 (customer_name, customer_tel_number, customer_department, customer_duty)
                    // $apiData[2]: 기사 정보 (rider_code_no, rider_name, rider_tel_number, rider_lon, rider_lat)
                    // $apiData[3]: 주문 시간 정보 (order_time, allocation_time, pickup_time, resolve_time, complete_time)
                    // $apiData[4]: 주소 정보 (departure_dong_name, departure_address, departure_company_name, destination_dong_name, destination_address, destination_tel_number, destination_company_name, start_lon, start_lat, dest_lon, dest_lat, start_c_code, dest_c_code, start_department, start_duty, dest_department, dest_duty, happy_call, distince)
                    // $apiData[5]: 금액 정보 (car_type, cargo_type, cargo_name, payment, state, save_state, total_cost, basic_cost, addition_cost, discount_cost, delivery_cost)
                    // $apiData[7]: 완료 시간
                    // $apiData[9]: 기타 정보 (reason, order_regist_type, doc, item_type, sfast, summary)
                    
                    $updateData = [];
                    */
                    
                    // CLI에서 리스트 형태로 처리하므로 개별 주문상세 API 호출 및 처리 로직은 주석처리됨
                    /*
                    
                    // 헬퍼 함수: 객체/배열에서 값 추출
                    $getValue = function($data, $key, $default = null) {
                        if (is_object($data)) {
                            return $data->$key ?? $default;
                        } elseif (is_array($data)) {
                            return $data[$key] ?? $default;
                        }
                        return $default;
                    };
                    
                    // 헬퍼 함수: 숫자 변환 (원, 쉼표 제거)
                    $parseAmount = function($value) {
                        if (empty($value)) return null;
                        $cleaned = str_replace(['원', ',', ' '], '', $value);
                        return is_numeric($cleaned) ? (float)$cleaned : null;
                    };
                    
                    // 헬퍼 함수: 날짜/시간 파싱
                    $parseDateTime = function($value) {
                        if (empty($value)) return null;
                        // YYYY-MM-DD HH:MM:SS 형식 또는 다른 형식 처리
                        $timestamp = strtotime($value);
                        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                    };
                    
                    // ============================================
                    // 1. 접수자 정보 ($apiData[1])
                    // ============================================
                    if (isset($apiData[1])) {
                        $customerInfo = $apiData[1];
                        
                        $customerName = $getValue($customerInfo, 'customer_name');
                        if ($customerName && $customerName !== ($order['customer_name'] ?? '')) {
                            $updateData['customer_name'] = $customerName;
                        }
                        
                        $customerTel = $getValue($customerInfo, 'customer_tel_number');
                        if ($customerTel && $customerTel !== ($order['customer_tel_number'] ?? '')) {
                            $updateData['customer_tel_number'] = $customerTel;
                        }
                        
                        $customerDept = $getValue($customerInfo, 'customer_department');
                        if ($customerDept && $customerDept !== ($order['customer_department'] ?? '')) {
                            $updateData['customer_department'] = $customerDept;
                        }
                        
                        $customerDuty = $getValue($customerInfo, 'customer_duty');
                        if ($customerDuty && $customerDuty !== ($order['customer_duty'] ?? '')) {
                            $updateData['customer_duty'] = $customerDuty;
                        }
                    }
                    
                    // ============================================
                    // 2. 기사 정보 ($apiData[2]) - 중요: 기사 이름 및 연락처
                    // ============================================
                    if (isset($apiData[2])) {
                        $riderInfo = $apiData[2];
                        
                        $riderCodeNo = $getValue($riderInfo, 'rider_code_no');
                        if ($riderCodeNo && $riderCodeNo !== ($order['rider_code_no'] ?? '')) {
                            $updateData['rider_code_no'] = $riderCodeNo;
                        }
                        
                        $riderName = $getValue($riderInfo, 'rider_name');
                        if ($riderName && $riderName !== ($order['rider_name'] ?? '')) {
                            $updateData['rider_name'] = $riderName;
                        }
                        
                        $riderTel = $getValue($riderInfo, 'rider_tel_number');
                        if ($riderTel && $riderTel !== ($order['rider_tel_number'] ?? '')) {
                            $updateData['rider_tel_number'] = $riderTel;
                        }
                        
                        $riderLon = $getValue($riderInfo, 'rider_lon');
                        if ($riderLon && $riderLon !== ($order['rider_lon'] ?? '')) {
                            $updateData['rider_lon'] = $riderLon;
                        }
                        
                        $riderLat = $getValue($riderInfo, 'rider_lat');
                        if ($riderLat && $riderLat !== ($order['rider_lat'] ?? '')) {
                            $updateData['rider_lat'] = $riderLat;
                        }
                    }
                    
                    // ============================================
                    // 3. 주문 시간 정보 ($apiData[3])
                    // ============================================
                    if (isset($apiData[3])) {
                        $timeInfo = $apiData[3];
                        
                        // order_time (접수시간)
                        $orderTime = $getValue($timeInfo, 'order_time');
                        if ($orderTime) {
                            $parsedTime = $parseDateTime($orderTime);
                            if ($parsedTime) {
                                $updateData['order_time'] = date('H:i:s', strtotime($parsedTime));
                                $updateData['order_date'] = date('Y-m-d', strtotime($parsedTime));
                            }
                        }
                        
                        // allocation_time (배차시간)
                        $allocationTime = $getValue($timeInfo, 'allocation_time');
                        if ($allocationTime) {
                            $parsedAllocation = $parseDateTime($allocationTime);
                            if ($parsedAllocation && $parsedAllocation !== ($order['allocation_time'] ?? null)) {
                                $updateData['allocation_time'] = $parsedAllocation;
                            }
                        }
                        
                        // pickup_time (픽업시간)
                        $pickupTime = $getValue($timeInfo, 'pickup_time');
                        if ($pickupTime) {
                            $parsedPickup = $parseDateTime($pickupTime);
                            if ($parsedPickup && $parsedPickup !== ($order['pickup_time'] ?? null)) {
                                $updateData['pickup_time'] = $parsedPickup;
                            }
                        }
                        
                        // resolve_time (예약시간)
                        $resolveTime = $getValue($timeInfo, 'resolve_time');
                        if ($resolveTime) {
                            $parsedResolve = $parseDateTime($resolveTime);
                            if ($parsedResolve && $parsedResolve !== ($order['resolve_time'] ?? null)) {
                                $updateData['resolve_time'] = $parsedResolve;
                            }
                        }
                        
                        // complete_time (완료시간)
                        $completeTime = $getValue($timeInfo, 'complete_time');
                        if ($completeTime) {
                            $parsedComplete = $parseDateTime($completeTime);
                            if ($parsedComplete && $parsedComplete !== ($order['complete_time'] ?? null)) {
                                $updateData['complete_time'] = $parsedComplete;
                            }
                        }
                    }
                    
                    // ============================================
                    // 4. 주소 정보 ($apiData[4])
                    // ============================================
                    if (isset($apiData[4])) {
                        $addressInfo = $apiData[4];
                        
                        // 출발지 정보
                        $departureDong = $getValue($addressInfo, 'departure_dong_name');
                        if ($departureDong && $departureDong !== ($order['departure_dong'] ?? '')) {
                            $updateData['departure_dong'] = $departureDong;
                        }
                        
                        $departureAddress = $getValue($addressInfo, 'departure_address');
                        if ($departureAddress && $departureAddress !== ($order['departure_address'] ?? '')) {
                            $updateData['departure_address'] = $departureAddress;
                        }
                        
                        $departureCompany = $getValue($addressInfo, 'departure_company_name');
                        if ($departureCompany && $departureCompany !== ($order['departure_company_name'] ?? '')) {
                            $updateData['departure_company_name'] = $departureCompany;
                        }
                        
                        // 출발지 좌표
                        $startLon = $getValue($addressInfo, 'start_lon');
                        if ($startLon && $startLon !== ($order['departure_lon'] ?? '')) {
                            $updateData['departure_lon'] = $startLon;
                        }
                        
                        $startLat = $getValue($addressInfo, 'start_lat');
                        if ($startLat && $startLat !== ($order['departure_lat'] ?? '')) {
                            $updateData['departure_lat'] = $startLat;
                        }
                        
                        // 출발지 고객코드
                        $startCCode = $getValue($addressInfo, 'start_c_code');
                        if ($startCCode && $startCCode !== ($order['s_c_code'] ?? '')) {
                            $updateData['s_c_code'] = $startCCode;
                        }
                        
                        // 출발지 부서/담당
                        $startDept = $getValue($addressInfo, 'start_department');
                        if ($startDept && $startDept !== ($order['departure_department'] ?? '')) {
                            $updateData['departure_department'] = $startDept;
                        }
                        
                        $startDuty = $getValue($addressInfo, 'start_duty');
                        if ($startDuty && $startDuty !== ($order['departure_manager'] ?? '')) {
                            $updateData['departure_manager'] = $startDuty;
                        }
                        
                        // 출발지 연락처
                        $startTel = $getValue($addressInfo, 'start_tel_number');
                        if ($startTel && $startTel !== ($order['departure_contact'] ?? '')) {
                            $updateData['departure_contact'] = $startTel;
                        }
                        
                        // departure_detail: dong 값이 있으면 dong 값을 사용
                        if ($departureDong && $departureDong !== ($order['departure_detail'] ?? '')) {
                            $updateData['departure_detail'] = $departureDong;
                        }
                        
                        // 도착지 정보
                        $destDong = $getValue($addressInfo, 'destination_dong_name');
                        if ($destDong && $destDong !== ($order['destination_dong'] ?? '')) {
                            $updateData['destination_dong'] = $destDong;
                        }
                        
                        $destAddress = $getValue($addressInfo, 'destination_address');
                        if ($destAddress && $destAddress !== ($order['destination_address'] ?? '')) {
                            $updateData['destination_address'] = $destAddress;
                        }
                        
                        $destCompany = $getValue($addressInfo, 'destination_company_name');
                        if ($destCompany && $destCompany !== ($order['destination_company_name'] ?? '')) {
                            $updateData['destination_company_name'] = $destCompany;
                        }
                        
                        $destTel = $getValue($addressInfo, 'destination_tel_number');
                        if ($destTel && $destTel !== ($order['destination_contact'] ?? '')) {
                            $updateData['destination_contact'] = $destTel;
                        }
                        
                        // 도착지 좌표
                        $destLon = $getValue($addressInfo, 'dest_lon');
                        if ($destLon && $destLon !== ($order['destination_lon'] ?? '')) {
                            $updateData['destination_lon'] = $destLon;
                        }
                        
                        $destLat = $getValue($addressInfo, 'dest_lat');
                        if ($destLat && $destLat !== ($order['destination_lat'] ?? '')) {
                            $updateData['destination_lat'] = $destLat;
                        }
                        
                        // 도착지 고객코드
                        $destCCode = $getValue($addressInfo, 'dest_c_code');
                        if ($destCCode && $destCCode !== ($order['d_c_code'] ?? '')) {
                            $updateData['d_c_code'] = $destCCode;
                        }
                        
                        // 도착지 부서/담당
                        $destDept = $getValue($addressInfo, 'dest_department');
                        if ($destDept && $destDept !== ($order['destination_department'] ?? '')) {
                            $updateData['destination_department'] = $destDept;
                        }
                        
                        $destDuty = $getValue($addressInfo, 'dest_duty');
                        if ($destDuty && $destDuty !== ($order['destination_manager'] ?? '')) {
                            $updateData['destination_manager'] = $destDuty;
                        }
                        
                        // detail_address: dong 값이 있으면 dong 값을 사용
                        if ($destDong && $destDong !== ($order['detail_address'] ?? '')) {
                            $updateData['detail_address'] = $destDong;
                        }
                        
                        // 거리 정보
                        $distance = $getValue($addressInfo, 'distince');
                        if ($distance) {
                            $parsedDistance = $parseAmount($distance);
                            if ($parsedDistance !== null && $parsedDistance != ($order['distance'] ?? 0)) {
                                $updateData['distance'] = $parsedDistance;
                            }
                        }
                        
                        // 해피콜 회신번호
                        $happyCall = $getValue($addressInfo, 'happy_call');
                        if ($happyCall && $happyCall !== ($order['happy_call'] ?? '')) {
                            $updateData['happy_call'] = $happyCall;
                        }
                    }
                    
                    // ============================================
                    // 5. 금액 정보 ($apiData[5])
                    // ============================================
                    if (isset($apiData[5])) {
                        $costInfo = $apiData[5];
                        
                        // 상태 매핑 (save_state -> status)
                        $saveState = $getValue($costInfo, 'save_state');
                        if (!empty($saveState)) {
                            $status = $this->mapInsungStatusToLocal($saveState);
                            if ($status && $status !== ($order['status'] ?? '')) {
                                $updateData['status'] = $status;
                                log_message('info', "Insung order status change detected: Order ID {$order['id']}, Serial {$serialNumber}, Old: " . ($order['status'] ?? 'N/A') . ", New: {$status}, Insung State: {$saveState}");
                            }
                        }
                        
                        // state (인성 API 처리상태)
                        $state = $getValue($costInfo, 'state');
                        if ($state && $state !== ($order['state'] ?? '')) {
                            $updateData['state'] = $state;
                        }
                        
                        // 총 금액 (total_cost)
                        $totalCost = $getValue($costInfo, 'total_cost');
                        if ($totalCost) {
                            $parsedAmount = $parseAmount($totalCost);
                            if ($parsedAmount !== null && $parsedAmount != ($order['total_amount'] ?? 0)) {
                                $updateData['total_amount'] = $parsedAmount;
                            }
                        }
                        
                        // 기본요금 (basic_cost)
                        $basicCost = $getValue($costInfo, 'basic_cost');
                        if ($basicCost) {
                            $parsedBasic = $parseAmount($basicCost);
                            if ($parsedBasic !== null && $parsedBasic != ($order['total_fare'] ?? 0)) {
                                $updateData['total_fare'] = $parsedBasic;
                            }
                        }
                        
                        // 추가요금 (addition_cost)
                        $additionCost = $getValue($costInfo, 'addition_cost');
                        if ($additionCost) {
                            $parsedAddition = $parseAmount($additionCost);
                            if ($parsedAddition !== null && $parsedAddition != ($order['add_cost'] ?? 0)) {
                                $updateData['add_cost'] = $parsedAddition;
                            }
                        }
                        
                        // 할인요금 (discount_cost)
                        $discountCost = $getValue($costInfo, 'discount_cost');
                        if ($discountCost) {
                            $parsedDiscount = $parseAmount($discountCost);
                            if ($parsedDiscount !== null && $parsedDiscount != ($order['discount_cost'] ?? 0)) {
                                $updateData['discount_cost'] = $parsedDiscount;
                            }
                        }
                        
                        // 탁송요금 (delivery_cost)
                        $deliveryCost = $getValue($costInfo, 'delivery_cost');
                        if ($deliveryCost) {
                            $parsedDelivery = $parseAmount($deliveryCost);
                            if ($parsedDelivery !== null && $parsedDelivery != ($order['delivery_cost'] ?? 0)) {
                                $updateData['delivery_cost'] = $parsedDelivery;
                            }
                        }
                        
                        // 차종 정보 (car_type, cargo_type, cargo_name)
                        $carType = $getValue($costInfo, 'car_type');
                        $cargoType = $getValue($costInfo, 'cargo_type');
                        $cargoName = $getValue($costInfo, 'cargo_name');
                        
                        if ($cargoType && $cargoType !== ($order['car_kind'] ?? '')) {
                            $updateData['car_kind'] = $cargoType;
                        }
                        
                        if ($carType && $carType !== ($order['car_type'] ?? '')) {
                            $updateData['car_type'] = $carType;
                        }
                        
                        if ($cargoName && $cargoName !== ($order['cargo_name'] ?? '')) {
                            $updateData['cargo_name'] = $cargoName;
                        }
                        
                        // 결제 수단 (payment)
                        $payment = $getValue($costInfo, 'payment');
                        if ($payment) {
                            // payment 값을 payment_type ENUM으로 매핑
                            $paymentTypeMap = [
                                '착불' => 'cash_on_delivery',
                                '선불' => 'cash_in_advance',
                                '계좌이체' => 'bank_transfer',
                                '신용거래' => 'credit_transaction'
                            ];
                            $mappedPaymentType = $paymentTypeMap[$payment] ?? null;
                            if ($mappedPaymentType && $mappedPaymentType !== ($order['payment_type'] ?? '')) {
                                $updateData['payment_type'] = $mappedPaymentType;
                            }
                        }
                    }
                    
                    // ============================================
                    // 6. 기타 정보 ($apiData[9])
                    // ============================================
                    if (isset($apiData[9])) {
                        $extraInfo = $apiData[9];
                        
                        // save_state가 $apiData[9]에 있을 수도 있음
                        if (empty($updateData['status'])) {
                            $saveState = $getValue($extraInfo, 'save_state');
                            if (!empty($saveState)) {
                                $status = $this->mapInsungStatusToLocal($saveState);
                                if ($status && $status !== ($order['status'] ?? '')) {
                                    $updateData['status'] = $status;
                                }
                            }
                        }
                        
                        // 물품 종류 (item_type)
                        $itemType = $getValue($extraInfo, 'item_type');
                        if ($itemType && $itemType !== ($order['item_type'] ?? '')) {
                            $updateData['item_type'] = $itemType;
                        }
                        
                        // 전달내용 (summary)
                        $summary = $getValue($extraInfo, 'summary');
                        if ($summary && $summary !== ($order['delivery_content'] ?? '')) {
                            $updateData['delivery_content'] = $summary;
                        }
                        
                        // 배송사유 (reason)
                        $reason = $getValue($extraInfo, 'reason');
                        if ($reason && $reason !== ($order['reason'] ?? '')) {
                            $updateData['reason'] = $reason;
                        }
                        
                        // 접수타입 (order_regist_type) - A:API접수, I:인터넷접수, T:전화접수
                        $orderRegistType = $getValue($extraInfo, 'order_regist_type');
                        if ($orderRegistType && $orderRegistType !== ($order['order_regist_type'] ?? '')) {
                            $updateData['order_regist_type'] = $orderRegistType;
                        }
                        
                        // 배송방법 (doc)
                        $doc = $getValue($extraInfo, 'doc');
                        if ($doc && $doc !== ($order['doc'] ?? '')) {
                            $updateData['doc'] = $doc;
                        }
                        
                        // 배송선택 (sfast) - 1:일반, 3:급송, 5:조조, 7:야간, 8:할증, 9:과적, 0:택배, A:심야, B:휴일, C:납품, D:대기, F:눈비, 4:독차, 6:혼적, G:할인, M:마일, H:우편, I:행랑, J:해외, K:신문, Q:퀵, N:보관, O:혹한, P:상하차, R:명절
                        $sfast = $getValue($extraInfo, 'sfast');
                        if ($sfast && $sfast !== ($order['sfast'] ?? '')) {
                            $updateData['sfast'] = $sfast;
                        }
                    }
                    
                    // 업데이트할 데이터가 있으면 DB 업데이트
                    if (!empty($updateData)) {
                        // 전화번호 필드 암호화 처리
                        $encryptionHelper = new \App\Libraries\EncryptionHelper();
                        $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno'];
                        $updateData = $encryptionHelper->encryptFields($updateData, $phoneFields);
                        
                        $orderModel->update($order['id'], $updateData);
                        $syncedCount++;
                        
                        log_message('info', "Insung order synced: Order ID {$order['id']}, Serial {$serialNumber}, Updated: " . json_encode($updateData));
                    } else {
                        // 상태가 변경되지 않았어도 동기화 시도는 기록
                        log_message('debug', "Insung order sync checked: Order ID {$order['id']}, Serial {$serialNumber}, No changes detected. Current status: " . ($order['status'] ?? 'N/A') . ", Insung state: " . ($saveState ?? 'N/A'));
                    }
                    */
                    
                    // CLI에서 리스트 형태로 처리하므로 개별 주문상세 API 호출은 스킵
                    $skippedCount++;
                    log_message('info', "Insung order sync skipped (handled by CLI): Order ID {$order['id']}, Serial {$serialNumber}");
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "주문번호 {$order['order_number']}: " . $e->getMessage();
                    log_message('error', "Insung order sync error for order {$order['order_number']}: " . $e->getMessage());
                }
            }
            
            $message = "{$syncedCount}개 주문이 동기화되었습니다.";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount}개 스킵)";
            }
            if ($errorCount > 0) {
                $message .= " ({$errorCount}개 실패)";
            }
            
            return $this->response->setJSON([
                'success' => true,
                'synced_count' => $syncedCount,
                'error_count' => $errorCount,
                'skipped_count' => $skippedCount,
                'total_count' => count($orders),
                'message' => $message,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::syncInsungOrders - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '동기화 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 인성 API 상태를 로컬 DB 상태로 매핑
     * 
     * @param string $insungStatus 인성 API의 save_state 값
     * @return string|null 로컬 DB의 status 값
     */
    private function mapInsungStatusToLocal($insungStatus)
    {
        // 인성 API 상태값을 로컬 DB 상태로 매핑
        // 취소 상태도 정확히 매핑되도록 개선
        $statusMap = [
            '접수' => 'processing',
            '배차' => 'processing',
            '운행' => 'completed',
            '완료' => 'delivered',
            '예약' => 'pending',
            '취소' => 'cancelled',
            '취소됨' => 'cancelled',
            '취소처리' => 'cancelled',
            '취소완료' => 'cancelled',
            'A취소' => 'cancelled',  // A 접두사가 붙은 취소 상태
            'A예약' => 'pending',    // A 접두사가 붙은 예약 상태
            'A접수' => 'processing', // A 접두사가 붙은 접수 상태
            // 숫자 코드도 처리 (인성 API에서 숫자로 반환할 수도 있음)
            '10' => 'processing',  // 접수
            '20' => 'processing',  // 배차
            '30' => 'completed',   // 운행
            '40' => 'delivered',   // 완료
            '50' => 'cancelled',   // 취소
        ];
        
        // "A" 접두사가 붙은 상태값 처리 (예: "A취소", "A예약", "A접수")
        if (preg_match('/^A(.+)$/u', $insungStatus, $matches)) {
            $baseStatus = trim($matches[1]);
            if (isset($statusMap[$baseStatus])) {
                return $statusMap[$baseStatus];
            }
        }
        
        // 대소문자 구분 없이 매핑
        $insungStatus = trim($insungStatus);
        $mappedStatus = $statusMap[$insungStatus] ?? null;
        
        // 매핑되지 않은 경우 로그 기록
        if (!$mappedStatus && !empty($insungStatus)) {
            log_message('warning', "Unmapped Insung status: '{$insungStatus}'");
        }
        
        return $mappedStatus;
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
        $result = $userPreferencesModel->saveColumnOrder($loginType, (string)$userId, 'delivery', $columnOrder);
        
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
     * 맵 뷰 페이지 (인성 주문번호로 주문 상세 조회 후 맵 표시)
     */
    public function mapView()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $serialNumber = $this->request->getGet('idx'); // 인성 주문번호
        if (!$serialNumber) {
            return redirect()->to('/delivery/list')->with('error', '주문번호가 필요합니다.');
        }
        
        // API 정보 조회
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        $userId = session()->get('user_id');
        
        // 세션에 없으면 DB에서 조회
        if (empty($mCode) || empty($ccCode) || empty($token)) {
            $insungApiListModel = new \App\Models\InsungApiListModel();
            $loginType = session()->get('login_type');
            $userType = session()->get('user_type');
            
            if ($loginType === 'daumdata' && $userType == '1') {
                $ccCodeFromSession = session()->get('cc_code');
                if ($ccCodeFromSession) {
                    $apiInfo = $insungApiListModel->getApiInfoByCode($ccCodeFromSession);
                    if ($apiInfo) {
                        $mCode = $apiInfo['mcode'] ?? '';
                        $ccCode = $apiInfo['cccode'] ?? '';
                        $token = $apiInfo['token'] ?? '';
                        $apiIdx = $apiInfo['idx'] ?? null;
                    }
                }
            } else {
                $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode('4540', '7829');
                if ($apiInfo) {
                    $mCode = $apiInfo['mcode'] ?? '4540';
                    $ccCode = $apiInfo['cccode'] ?? '7829';
                    $token = $apiInfo['token'] ?? '';
                    $apiIdx = $apiInfo['idx'] ?? null;
                }
            }
        }
        
        if (!$mCode || !$ccCode || !$token) {
            return redirect()->to('/delivery/list')->with('error', 'API 정보가 설정되지 않았습니다.');
        }
        
        try {
            $insungApiService = new \App\Libraries\InsungApiService();
            $orderDetailResult = $insungApiService->getOrderDetail($mCode, $ccCode, $token, $userId ?? '', $serialNumber, $apiIdx);
            
            if (!$orderDetailResult || !isset($orderDetailResult['success']) || !$orderDetailResult['success']) {
                return redirect()->to('/delivery/list')->with('error', $orderDetailResult['message'] ?? '주문 상세 정보를 가져올 수 없습니다.');
            }
            
            $orderData = $orderDetailResult['data'] ?? null;
            if (!$orderData || !is_array($orderData)) {
                log_message('error', 'Delivery::mapView - orderData is not array: ' . json_encode($orderData));
                return redirect()->to('/delivery/list')->with('error', '주문 상세 데이터가 없습니다.');
            }
            
            // 디버깅: API 응답 구조 로그
            log_message('debug', 'Delivery::mapView - orderData structure: ' . json_encode($orderData, JSON_UNESCAPED_UNICODE));
            
            // 인성 API 응답 구조 파싱 (실제 응답 구조에 맞춤)
            // $orderData[0]: 응답 코드
            // $orderData[1]: 고객 정보
            // $orderData[2]: 기사 정보 (rider_code_no, rider_name, rider_tel_number)
            // $orderData[3]: 주문 시간 정보
            // $orderData[4]: 주소 정보 (departure_address, destination_address 등)
            // $orderData[5]: 기타 정보 (car_type, state, save_state 등)
            // $orderData[6]: 기사 위치 정보 (rider_lon, rider_lat) - 실제 좌표는 여기!
            // $orderData[9]: 주소 좌표 정보 (start_lon, start_lat, dest_lon, dest_lat) - 실제 좌표는 여기!
            
            // getValue 헬퍼 함수 (Admin::getOrderDetail와 동일)
            $getValue = function($data, $key, $default = '') {
                if (is_object($data)) {
                    return $data->$key ?? $default;
                } elseif (is_array($data)) {
                    return $data[$key] ?? $default;
                }
                return $default;
            };
            
            // 좌표 변환 함수 (stnlogis 프로젝트의 LonLat 함수와 정확히 동일)
            // 인성 API 좌표를 실제 위도/경도로 변환
            $parseCoordinate = function($value) {
                if (empty($value) || $value === '0' || $value === '') {
                    return null;
                }
                // stnlogis의 LonLat 함수와 정확히 동일한 변환 로직
                $a = $value / 360000.0;  // 정수 나눗셈이 아닌 실수 나눗셈
                $b = (($value / 360000.0) - $a) / 10.0 * 6;
                $c = ($a + $b) * 100.0;
                
                $aa = $c / 100;  // stnlogis에서는 정수 변환이 없음
                $d = ($c - ($aa * 100)) / 60.0;
                $bb = $aa + $d;
                
                log_message('debug', "Delivery::mapView - 좌표 변환: {$value} -> {$bb}");
                return $bb;
            };
            
            $riderLon = null;
            $riderLat = null;
            $startLon = null;
            $startLat = null;
            $destLon = null;
            $destLat = null;
            $departureAddress = '';
            $destinationAddress = '';
            $departureCompanyName = '';
            $destinationCompanyName = '';
            $departureTel = '';
            $destinationTel = '';
            $riderName = '';
            $riderCode = '';
            $riderTel = '';
            $orderState = '';
            
            // 기사 정보 (인덱스 2)
            if (isset($orderData[2])) {
                $riderInfo = $orderData[2];
                $riderName = $getValue($riderInfo, 'rider_name');
                $riderCode = $getValue($riderInfo, 'rider_code_no');
                $riderTel = $getValue($riderInfo, 'rider_tel_number');
            }
            
            // 기사 위치 정보 (인덱스 6) - 실제 좌표는 여기에 있음!
            if (isset($orderData[6])) {
                $riderLocationInfo = $orderData[6];
                $riderLonRaw = $getValue($riderLocationInfo, 'rider_lon');
                $riderLatRaw = $getValue($riderLocationInfo, 'rider_lat');
                // stnlogis의 LonLat() 함수 사용
                $riderLon = $parseCoordinate($riderLonRaw);
                $riderLat = $parseCoordinate($riderLatRaw);
            }
            
            // 주소 정보 (인덱스 4)
            if (isset($orderData[4])) {
                $addressInfo = $orderData[4];
                $departureAddress = $getValue($addressInfo, 'departure_address');
                $departureCompanyName = $getValue($addressInfo, 'departure_company_name');
                $departureTel = $getValue($addressInfo, 'departure_tel_number');
                $destinationAddress = $getValue($addressInfo, 'destination_address');
                $destinationCompanyName = $getValue($addressInfo, 'destination_company_name');
                $destinationTel = $getValue($addressInfo, 'destination_tel_number');
            }
            
            // 주소 좌표 정보 (인덱스 9) - 실제 좌표는 여기에 있음!
            if (isset($orderData[9])) {
                $coordinateInfo = $orderData[9];
                $startLonRaw = $getValue($coordinateInfo, 'start_lon');
                $startLatRaw = $getValue($coordinateInfo, 'start_lat');
                $destLonRaw = $getValue($coordinateInfo, 'dest_lon');
                $destLatRaw = $getValue($coordinateInfo, 'dest_lat');
                
                log_message('debug', "Delivery::mapView - 원본 좌표 (raw): start_lon={$startLonRaw}, start_lat={$startLatRaw}, dest_lon={$destLonRaw}, dest_lat={$destLatRaw}");
                
                // stnlogis의 LonLat() 함수 사용 (확실히 변환을 하고 있음)
                $startLon = $parseCoordinate($startLonRaw);
                $startLat = $parseCoordinate($startLatRaw);
                $destLon = $parseCoordinate($destLonRaw);
                $destLat = $parseCoordinate($destLatRaw);
                
                log_message('debug', "Delivery::mapView - 변환 후 좌표: start_lat={$startLat}, start_lon={$startLon}, dest_lat={$destLat}, dest_lon={$destLon}");
            }
            
            // 주문 상태 확인 (인덱스 5)
            if (isset($orderData[5])) {
                $extraInfo = $orderData[5];
                $orderState = $getValue($extraInfo, 'save_state'); // save_state가 실제 상태
                if (empty($orderState)) {
                    $orderState = $getValue($extraInfo, 'state');
                }
            }
            
            // 운행 상태 확인 (기사 위치 표시 여부)
            $isRiding = ($orderState === '12' || $orderState === '운행');
            
            log_message('debug', "Delivery::mapView - Parsed coordinates: start_lon={$startLon}, start_lat={$startLat}, dest_lon={$destLon}, dest_lat={$destLat}, rider_lon={$riderLon}, rider_lat={$riderLat}, orderState={$orderState}");
            
            // 좌표가 없으면 에러 메시지 표시
            if (!$startLon || !$startLat || !$destLon || !$destLat) {
                log_message('warning', "Delivery::mapView - Missing coordinates: start_lon={$startLon}, start_lat={$startLat}, dest_lon={$destLon}, dest_lat={$destLat}");
            }
            
            $data = [
                'title' => '위치 조회',
                'serial_number' => $serialNumber,
                'start_lon' => $startLon,
                'start_lat' => $startLat,
                'dest_lon' => $destLon,
                'dest_lat' => $destLat,
                'rider_lon' => $riderLon,
                'rider_lat' => $riderLat,
                'departure_address' => trim($departureAddress),
                'departure_company_name' => trim($departureCompanyName),
                'departure_tel' => trim($departureTel),
                'destination_address' => trim($destinationAddress),
                'destination_company_name' => trim($destinationCompanyName),
                'destination_tel' => trim($destinationTel),
                'rider_name' => $riderName,
                'rider_code' => $riderCode,
                'rider_tel' => $riderTel,
                'is_riding' => $isRiding
            ];
            
            return view('delivery/map-view', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::mapView - Error: ' . $e->getMessage());
            return redirect()->to('/delivery/list')->with('error', '맵 조회 중 오류가 발생했습니다.');
        }
    }
}
