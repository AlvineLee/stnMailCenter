<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\DashboardModel;
use App\Libraries\InsungApiService;
use App\Models\InsungApiListModel;

class Dashboard extends BaseController
{
    protected $orderModel;
    protected $dashboardModel;
    
    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->dashboardModel = new DashboardModel();
        helper('form');
    }
    
    /**
     * 메인 대시보드
     */
    public function index()
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
        $userType = session()->get('user_type');
        $customerId = session()->get('customer_id');
        $compCode = session()->get('comp_code');
        $ccCode = session()->get('cc_code');
        
        // 고객사 선택 (슈퍼관리자용)
        $selectedCustomerId = $this->request->getGet('customer_id') ?: $customerId;
        
        // 고객사 목록 조회 (슈퍼관리자용)
        $customers = [];
        if ($userRole === 'super_admin') {
            $customers = $this->dashboardModel->getActiveCustomers();
        }
        
        // 통계 데이터 조회
        $stats = $this->dashboardModel->getOrderStats($selectedCustomerId, $userRole, $loginType, $userType, $compCode, $ccCode);
        
        // 최근 주문 조회
        $recent_orders = $this->dashboardModel->getRecentOrders($selectedCustomerId, $userRole, 10, $loginType, $userType, $compCode, $ccCode);
        
        // 선택된 고객사 정보
        $selectedCustomer = null;
        if ($selectedCustomerId) {
            $selectedCustomer = $this->dashboardModel->getCustomerById($selectedCustomerId);
        }
        
        // DB 연결 정보 가져오기
        $dbConfig = config('Database');
        
        // 환경 변수에서 직접 읽기 (디버깅용)
        $envHostname = getenv('DB_HOSTNAME');
        $envDatabase = getenv('DB_DATABASE');
        $envUsername = getenv('DB_USERNAME');
        
        $dbInfo = [
            'hostname' => $dbConfig->default['hostname'] ?? 'unknown',
            'database' => $dbConfig->default['database'] ?? 'unknown',
            'username' => $dbConfig->default['username'] ?? 'unknown',
            'port' => $dbConfig->default['port'] ?? 3306,
            'source' => [
                'env_hostname' => $envHostname ?: 'not set',
                'config_hostname' => $dbConfig->default['hostname'] ?? 'not set',
                'env_file_exists' => file_exists(ROOTPATH . 'env') ? 'yes (env)' : (file_exists(ROOTPATH . '.env') ? 'yes (.env)' : 'no')
            ]
        ];
        
        $data = [
            'title' => 'DaumData - 대시보드',
            'content_header' => [
                'title' => '대시보드',
                'description' => '전체 현황을 한눈에 확인하세요'
            ],
            'user' => [
                'username' => session()->get('username'),
                'real_name' => session()->get('real_name'),
                'customer_name' => session()->get('customer_name'),
                'user_role' => $userRole
            ],
            'stats' => $stats,
            'recent_orders' => $recent_orders,
            'customers' => $customers,
            'selected_customer_id' => $selectedCustomerId,
            'selected_customer' => $selectedCustomer,
            'db_info' => $dbInfo
        ];
        
        return view('dashboard/index', $data);
    }
    
    
    /**
     * 주문 접수 처리
     */
    public function submitOrder()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 폼 데이터 검증
        $validation = \Config\Services::validation();
        $validation->setRules([
            'companyName' => 'required',
            'contact' => 'required',
            'departureAddress' => 'required',
            'destinationAddress' => 'required',
            'itemType' => 'required',
            'deliveryContent' => 'required'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        // 주문 데이터 준비
        $orderData = [
            'user_id' => session()->get('user_id'),
            'company_name' => $this->request->getPost('companyName'),
            'contact' => $this->request->getPost('contact'),
            'address' => $this->request->getPost('address'),
            'departure_address' => $this->request->getPost('departureAddress'),
            'departure_detail' => $this->request->getPost('departureDetail'),
            'departure_contact' => $this->request->getPost('departureContact'),
            'destination_type' => $this->request->getPost('destinationType'),
            'mailroom' => $this->request->getPost('mailroom'),
            'destination_address' => $this->request->getPost('destinationAddress'),
            'detail_address' => $this->request->getPost('detailAddress'),
            'destination_contact' => $this->request->getPost('destinationContact'),
            'item_type' => $this->request->getPost('itemType'),
            'quantity' => $this->request->getPost('quantity'),
            'unit' => $this->request->getPost('unit'),
            'delivery_content' => $this->request->getPost('deliveryContent'),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 주문 저장 (임시로 세션에 저장)
        $orders = session()->get('orders') ?? [];
        $orderData['id'] = count($orders) + 1;
        $orders[] = $orderData;
        session()->set('orders', $orders);
        
        return redirect()->to('/')->with('success', '주문이 접수되었습니다.');
    }
    
    /**
     * 주문 목록 조회
     */
    public function orders()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        // 고객사 선택 (슈퍼관리자용)
        $selectedCustomerId = $this->request->getGet('customer_id') ?: $customerId;
        
        // 고객사 목록 조회 (슈퍼관리자용)
        $customers = [];
        if ($userRole === 'super_admin') {
            $customers = $this->dashboardModel->getActiveCustomers();
        }
        
        // 주문 목록 조회
        $orders = $this->dashboardModel->getAllOrders($customerId, $userRole, $selectedCustomerId);
        
        // 선택된 고객사 정보
        $selectedCustomer = null;
        if ($selectedCustomerId) {
            $selectedCustomer = $this->dashboardModel->getCustomerById($selectedCustomerId);
        }
        
        $data = [
            'title' => 'DaumData - 주문조회',
            'content_header' => [
                'title' => '주문조회',
                'description' => '접수된 주문을 확인하세요'
            ],
            'orders' => $orders,
            'customers' => $customers,
            'selected_customer_id' => $selectedCustomerId,
            'selected_customer' => $selectedCustomer,
            'user_role' => $userRole
        ];
        
        return view('dashboard/orders', $data);
    }
    
    /**
     * 인성 API 인증 테스트
     */
    public function testInsungApi()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ]);
        }
        
        $loginType = session()->get('login_type');
        if ($loginType !== 'daumdata') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'daumdata 로그인만 테스트 가능합니다.'
            ]);
        }
        
        try {
            $mCode = session()->get('m_code');
            $ccCode = session()->get('cc_code');
            $token = session()->get('token');
            $userId = session()->get('user_id');
            
            if (empty($mCode) || empty($ccCode) || empty($token)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '세션에 API 정보가 없습니다.',
                    'session_data' => [
                        'm_code' => $mCode,
                        'cc_code' => $ccCode,
                        'token' => $token ? '있음' : '없음'
                    ]
                ]);
            }
            
            // API 정보 조회 (api_idx 찾기)
            $apiModel = new InsungApiListModel();
            $apiInfo = $apiModel->getApiInfoByMcodeCccode($mCode, $ccCode);
            
            if (!$apiInfo) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 정보를 찾을 수 없습니다.',
                    'search_params' => [
                        'mcode' => $mCode,
                        'cccode' => $ccCode
                    ]
                ]);
            }
            
            $apiIdx = $apiInfo['idx'];
            
            // InsungApiService 인스턴스 생성
            $insungApi = new InsungApiService();
            
            // 회원 상세 조회 API 호출 (토큰 자동 갱신 포함)
            $result = $insungApi->getMemberDetail($mCode, $ccCode, $token, $userId, $apiIdx);
            
            if (!$result) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 호출 실패 (응답 없음)'
                ]);
            }
            
            $code = isset($result[0]->code) ? $result[0]->code : 'unknown';
            
            if ($code == "1000") {
                // 성공
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'API 인증 성공',
                    'code' => $code,
                    'data' => [
                        'user_id' => $userId,
                        'm_code' => $mCode,
                        'cc_code' => $ccCode,
                        'member_info' => isset($result[1]) ? $result[1] : null
                    ],
                    'raw_response' => $result
                ]);
            } else if ($code == "1001") {
                // 토큰 만료 (이미 자동 갱신 및 재시도됨)
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '토큰 만료 (자동 갱신 시도됨)',
                    'code' => $code,
                    'note' => '토큰이 갱신되었을 수 있습니다. 다시 시도해주세요.'
                ]);
            } else {
                // 기타 에러
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 호출 오류',
                    'code' => $code,
                    'raw_response' => $result
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Insung API Test Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '테스트 중 오류 발생: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * 토큰 갱신 테스트
     */
    public function testTokenRefresh()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ]);
        }
        
        $loginType = session()->get('login_type');
        if ($loginType !== 'daumdata') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'daumdata 로그인만 테스트 가능합니다.'
            ]);
        }
        
        try {
            $mCode = session()->get('m_code');
            $ccCode = session()->get('cc_code');
            
            if (empty($mCode) || empty($ccCode)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '세션에 API 정보가 없습니다.'
                ]);
            }
            
            // API 정보 조회
            $apiModel = new InsungApiListModel();
            $apiInfo = $apiModel->getApiInfoByMcodeCccode($mCode, $ccCode);
            
            if (!$apiInfo) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 정보를 찾을 수 없습니다.'
                ]);
            }
            
            $apiIdx = $apiInfo['idx'];
            $oldToken = $apiInfo['token'] ?? '';
            $mcode = $apiInfo['mcode'] ?? '';
            $cccode = $apiInfo['cccode'] ?? '';
            $ckey = $apiInfo['ckey'] ?? '';
            
            // 필수 필드 확인
            if (empty($mcode) || empty($cccode) || empty($ckey)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 정보에 필수 필드가 누락되었습니다.',
                    'api_idx' => $apiIdx,
                    'missing_fields' => [
                        'mcode' => empty($mcode),
                        'cccode' => empty($cccode),
                        'ckey' => empty($ckey)
                    ]
                ]);
            }
            
            // ckey 값 확인 (디버깅용 - 처음 10자만 표시)
            $ckeyPreview = strlen($ckey) > 10 ? substr($ckey, 0, 10) . '...' : $ckey;
            
            // InsungApiService 인스턴스 생성
            $insungApi = new InsungApiService();
            
            // 토큰 갱신
            $newToken = $insungApi->updateTokenKey($apiIdx);
            
            if ($newToken) {
                // 갱신된 토큰으로 세션 업데이트
                session()->set('token', $newToken);
                
                // DB에서 갱신된 토큰 확인
                $updatedApiInfo = $apiModel->getApiInfoByIdx($apiIdx);
                $dbToken = $updatedApiInfo['token'] ?? '';
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => '토큰 갱신 성공',
                    'data' => [
                        'api_idx' => $apiIdx,
                        'mcode' => $mcode,
                        'cccode' => $cccode,
                        'old_token' => $oldToken ? substr($oldToken, 0, 20) . '...' : '없음',
                        'new_token' => substr($newToken, 0, 20) . '...',
                        'token_length' => strlen($newToken),
                        'db_token_updated' => !empty($dbToken) && $dbToken === $newToken
                    ]
                ]);
            } else {
                // 로그 파일에서 최근 에러 확인을 위한 안내
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '토큰 갱신 실패',
                    'api_idx' => $apiIdx,
                    'mcode' => $mcode,
                    'cccode' => $cccode,
                    'ckey_exists' => !empty($ckey),
                    'ckey_length' => strlen($ckey),
                    'ckey_preview' => $ckeyPreview,
                    'note' => 'ckey 인증 실패. DB의 ckey 값이 인성 API 서버에 등록된 값과 일치하는지 확인하세요.'
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Token Refresh Test Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '테스트 중 오류 발생: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
