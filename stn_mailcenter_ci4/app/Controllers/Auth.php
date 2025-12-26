<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AuthModel;
use App\Models\InsungUsersListModel;

class Auth extends BaseController
{
    protected $userModel;
    protected $authModel;
    protected $insungUsersListModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->authModel = new AuthModel();
        $this->insungUsersListModel = new InsungUsersListModel();
        helper('form');
    }
    
    /**
     * 로그인 페이지 표시
     */
    public function login()
    {
        // 이미 로그인된 경우 메인 페이지로 리다이렉트
        if (session()->get('user_id')) {
            return redirect()->to('/');
        }
        
        // 서브도메인 설정 가져오기
        $subdomainConfig = config('Subdomain');
        $subdomainInfo = $subdomainConfig->getCurrentConfig();
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        
        // 서브도메인으로 접근한 경우 (default가 아닌 경우)
        $isSubdomainAccess = ($currentSubdomain !== 'default');
        
        // API 정보 조회 (고객검색 팝업용)
        $apiIdx = null;
        $apiList = [];
        
        if ($isSubdomainAccess) {
            // 서브도메인 접근 시: 해당 서브도메인의 API만 조회
            $apiCodes = $subdomainConfig->getCurrentApiCodes();
            if ($apiCodes) {
                $insungApiListModel = new \App\Models\InsungApiListModel();
                $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($apiCodes['m_code'], $apiCodes['cc_code']);
                if ($apiInfo) {
                    $apiIdx = $apiInfo['idx'] ?? null;
                    // 서브도메인 API를 리스트에 추가 (드롭다운 표시용)
                    $apiList = [$apiInfo];
                }
            }
        } else {
            // 메인도메인일 때 mcode=4540인 API 목록 조회
            $insungApiListModel = new \App\Models\InsungApiListModel();
            $apiList = $insungApiListModel->getApiListByMcode('4540');
        }
        
        $data = [
            'title' => $subdomainInfo['name'] . ' - 로그인',
            'error' => session()->getFlashdata('error'),
            'subdomain' => $subdomainInfo,
            'is_subdomain' => $isSubdomainAccess,
            'api_idx' => $apiIdx,
            'api_list' => $apiList
        ];
        
        return view('auth/login', $data);
    }
    
    /**
     * 로그인 처리
     */
    public function processLogin()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $loginType = $this->request->getPost('login_type') ?? 'stn'; // 'stn' 또는 'daumdata'
        
        // 입력값 검증
        if (empty($username) || empty($password)) {
            // AJAX 요청인 경우 JSON 응답 반환
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => '아이디와 비밀번호를 입력해주세요.',
                    'error_detail' => '로그인을 위해 아이디와 비밀번호를 모두 입력해주세요.',
                    'error_type' => 'empty_fields'
                ]);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', '아이디와 비밀번호를 입력해주세요.');
        }
        
        // 로그인 타입에 따라 분기 처리
        if ($loginType === 'daumdata') {
            // daumdata 로그인 처리
            $user = $this->insungUsersListModel->authenticate($username, $password);
            
            if (!$user) {
                // 로그인 실패: 아이디/비밀번호 불일치
                $errorMessage = '입력하신 아이디와 비밀번호를 확인해주세요. 대소문자와 특수문자를 정확히 입력했는지 확인하시기 바랍니다.';
                
                // AJAX 요청인 경우 JSON 응답 반환
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => '아이디 또는 비밀번호가 올바르지 않습니다.',
                        'error_detail' => $errorMessage,
                        'error_type' => 'invalid_credentials'
                    ]);
                }
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.')
                    ->with('error_detail', $errorMessage);
            }
            
            // $user가 있으면 로그인 성공 처리 (위에서 !$user 체크로 이미 return됨)
            // 초기 API 정보 설정
            $ckey = $user['ckey'] ?? '';
            $mCode = $user['m_code'] ?? '';
            $ccCode = $user['cc_code'] ?? '';
            $apiIdx = null;
            
            // 메인도메인에서 선택한 API 정보 처리
            $subdomainConfig = config('Subdomain');
            $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
            $selectedApiIdx = $this->request->getPost('selected_api_idx');
            
            // 메인도메인이고 API가 선택된 경우, 선택한 API 정보로 업데이트
            if ($currentSubdomain === 'default' && !empty($selectedApiIdx)) {
                try {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    $selectedApiInfo = $insungApiListModel->getApiInfoByIdx($selectedApiIdx);
                    
                    if ($selectedApiInfo) {
                        // 선택한 API 정보로 업데이트
                        $mCode = $selectedApiInfo['mcode'] ?? $mCode;
                        $ccCode = $selectedApiInfo['cccode'] ?? $ccCode;
                        $ckey = $selectedApiInfo['ckey'] ?? $ckey;
                    }
                } catch (\Exception $e) {
                    log_message('error', "Error retrieving selected API info: " . $e->getMessage());
                }
            }
            
            // [주석 처리됨] API 정보가 있으면 토큰 갱신 시도
            // 다중 사용자 환경에서 토큰 헬 방지를 위해 로그인 시 자동 토큰 갱신 비활성화
            // 한 콜센터에 수백 개 거래처, 수천~수만 명의 사용자가 있을 때
            // 로그인할 때마다 자동으로 토큰을 갱신하면 토큰이 계속 변경되어
            // 다른 사용자들의 요청이 실패하는 토큰 헬이 발생함
            // 토큰 갱신은 관리자 화면에서 수동으로만 수행해야 함
            /*
            if (!empty($mCode) && !empty($ccCode) && !empty($ckey)) {
                try {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                    
                    if ($apiInfo && isset($apiInfo['idx'])) {
                        $apiIdx = $apiInfo['idx'];
                        
                        // OAuth 인증을 통해 새 토큰 발급
                        $insungApiService = new \App\Libraries\InsungApiService();
                        
                        // ukey, akey 생성 (로그인 시마다 새로 생성)
                        $keyStr = getenv('INSUNG_KEY_STR') ?: 'myapikey';
                        $ukey = $keyStr . trim($ckey);
                        $akey = md5($ukey);
                        
                        // 토큰 갱신 (OAuth 인증)
                        $newToken = $insungApiService->updateTokenKey($apiIdx);
                        
                        if ($newToken) {
                            log_message('info', "OAuth token refreshed on login for user: {$username}, api_idx: {$apiIdx}");
                            $user['token'] = $newToken; // 새 토큰으로 업데이트
                        } else {
                            log_message('warning', "OAuth token refresh failed on login for user: {$username}, using existing token");
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', "OAuth token refresh error on login: " . $e->getMessage());
                    // 토큰 갱신 실패해도 로그인은 진행 (기존 토큰 사용)
                }
            }
            */
            
            // 기존 토큰 사용 (DB에 저장된 토큰)
            $token = '';
            if (!empty($mCode) && !empty($ccCode)) {
                try {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                    
                    if ($apiInfo && isset($apiInfo['token']) && !empty($apiInfo['token'])) {
                        $token = $apiInfo['token'];
                        // log_message('info', "Using existing token from database for user: {$username}");
                    } else {
                        log_message('warning', "No token found in database for user: {$username}, token may need manual refresh");
                    }
                } catch (\Exception $e) {
                    log_message('error', "Error retrieving token from database: " . $e->getMessage());
                }
            }
            
            // ukey, akey 생성 (세션 저장용)
            $randomPrefix = bin2hex(random_bytes(4)); // 임의의 8글자 생성 (16진수)
            $ukey = $randomPrefix . $ckey; // 8글자 + ckey
            $akey = md5($ukey); // ukey를 MD5로 변환
            
            // 세션에 사용자 정보 저장 (daumdata)
            $userData = [
                'user_id' => $user['user_id'], // 문자열 user_id (로그인용)
                'user_idx' => $user['idx'] ?? null, // tbl_users_list의 idx (주문 저장용)
                'user_dept' => $user['user_dept'] ?? '',
                'user_name' => $user['user_name'] ?? '',
                'user_tel1' => $user['user_tel1'] ?? '',
                'user_tel2' => $user['user_tel2'] ?? '',
                'user_addr' => $user['user_addr'] ?? '',
                'user_addr_detail' => $user['user_addr_detail'] ?? '',
                'user_dong' => $user['user_dong'] ?? '',
                'user_sido' => $user['user_sido'] ?? '',
                'user_gungu' => $user['user_gungu'] ?? '',
                'comp_name' => $user['comp_name'] ?? '',
                'comp_owner' => $user['comp_owner'] ?? '',
                'comp_tel' => $user['comp_tel'] ?? '',
                'comp_code' => $user['comp_code'] ?? null, // customer_id로 사용
                'user_company' => $user['user_company'] ?? null, // tbl_users_list.user_company (고객사 코드)
                'cc_code' => $ccCode, // 선택한 API의 cc_code 또는 기존 값
                'm_code' => $mCode, // 선택한 API의 m_code 또는 기존 값
                'token' => $token, // 선택한 API의 token 또는 기존 값
                'ckey' => $ckey, // 선택한 API의 ckey 또는 기존 값
                'ukey' => $ukey, // ukey
                'akey' => $akey, // akey
                'user_type' => $user['user_type'] ?? '5',
                'login_type' => 'daumdata',
                'is_logged_in' => true,
                'company_logo_path' => !empty($user['logo_path']) ? $user['logo_path'] : null // 고객사 로고 경로
            ];
            
            session()->set($userData);
            
            // 서브도메인 접근 권한 체크 (메인도메인은 제한 해제)
            // user_company가 없는 계정은 진정한 슈퍼 관리자
            // 서브도메인: user_company가 있는 경우는 반드시 서브도메인의 comp_code와 일치해야 함
            $subdomainConfig = config('Subdomain');
            $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
            $userCompany = $user['user_company'] ?? null;
            
            // 디버깅 로그
            log_message('debug', "Login domain check: currentSubdomain={$currentSubdomain}, userCompany=" . ($userCompany ?? 'NULL') . ", username={$username}");
            
            // user_company가 없는 경우는 진정한 슈퍼 관리자로 간주하여 모든 도메인에서 통과
            if (empty($userCompany) || trim($userCompany) === '') {
                log_message('debug', "Login allowed: user_company is empty (true super admin), username={$username}");
                // 진정한 슈퍼 관리자는 통과
            } else {
                // user_company가 있는 경우
                $userCompanyTrimmed = trim((string)$userCompany);
                
                // 메인도메인인 경우: 서브도메인 체크 건너뛰기 (모든 계정 접근 허용)
                if ($currentSubdomain === 'default') {
                    log_message('debug', "Login allowed: main domain access (no subdomain check), username={$username}, userCompany={$userCompanyTrimmed}");
                    // 메인도메인에서는 통과
                } else {
                    // 서브도메인으로 접근한 경우: 서브도메인의 comp_code와 반드시 일치해야 함
                    $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
                    
                    log_message('debug', "Subdomain comp_code check: subdomainCompCode=" . ($subdomainCompCode ?? 'NULL') . ", userCompany={$userCompanyTrimmed}");
                    
                    // subdomainCompCode가 null이면 서브도메인 설정 오류이므로 거부
                    if (empty($subdomainCompCode)) {
                        session()->destroy();
                        log_message('error', "Login denied: subdomain_comp_code is NULL for subdomain={$currentSubdomain}, user: {$username}");
                        
                        $subdomainName = $subdomainConfig->getCurrentConfig()['name'] ?? '해당 서브도메인';
                        $errorMessage = "{$subdomainName}의 서브도메인 설정이 올바르지 않습니다. 시스템 관리자에게 문의해주세요.";
                        
                        // AJAX 요청인 경우 JSON 응답 반환
                        if ($this->request->isAJAX()) {
                            return $this->response->setJSON([
                                'success' => false,
                                'error' => '서브도메인 설정 오류',
                                'error_detail' => $errorMessage,
                                'error_type' => 'subdomain_config_error'
                            ]);
                        }
                        
                        return redirect()->back()
                            ->withInput()
                            ->with('error', '서브도메인 설정 오류')
                            ->with('error_detail', $errorMessage);
                    }
                    
                    // 문자열 비교 (타입 변환하여 정확한 비교)
                    $subdomainCompCodeTrimmed = trim((string)$subdomainCompCode);
                    
                    if ($userCompanyTrimmed !== $subdomainCompCodeTrimmed) {
                        session()->destroy();
                        log_message('info', "Login denied: user_company({$userCompanyTrimmed}) != subdomain_comp_code({$subdomainCompCodeTrimmed}) for user: {$username}");
                        
                        // 사용자의 고객사명 조회
                        $userCompanyName = '';
                        try {
                            $db = \Config\Database::connect();
                            $compBuilder = $db->table('tbl_company_list');
                            $compBuilder->select('comp_name');
                            $compBuilder->where('comp_code', $userCompanyTrimmed);
                            $compQuery = $compBuilder->get();
                            if ($compQuery !== false) {
                                $compResult = $compQuery->getRowArray();
                                if ($compResult && !empty($compResult['comp_name'])) {
                                    $compName = $compResult['comp_name'];
                                    // 첫 번째 언더바(_) 이후만 표시
                                    $underscorePos = strpos($compName, '_');
                                    if ($underscorePos !== false) {
                                        $userCompanyName = substr($compName, $underscorePos + 1);
                                    } else {
                                        $userCompanyName = $compName;
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            log_message('error', "Error retrieving company name: " . $e->getMessage());
                        }
                        
                        $subdomainName = $subdomainConfig->getCurrentConfig()['name'] ?? '해당 서브도메인';
                        $errorMessage = "로그인은 성공했지만, {$subdomainName}에 접근할 권한이 없습니다.";
                        if ($userCompanyName) {
                            $errorMessage .= " 현재 계정은 '{$userCompanyName}' 소속입니다.";
                        }
                        $errorMessage .= " 올바른 서브도메인으로 접속하시거나, 시스템 관리자에게 접근 권한을 요청해주세요.";
                        
                        // AJAX 요청인 경우 JSON 응답 반환
                        if ($this->request->isAJAX()) {
                            return $this->response->setJSON([
                                'success' => false,
                                'error' => '서브도메인 접근 권한이 없습니다.',
                                'error_detail' => $errorMessage,
                                'error_type' => 'subdomain_access_denied'
                            ]);
                        }
                        
                        return redirect()->back()
                            ->withInput()
                            ->with('error', '서브도메인 접근 권한이 없습니다.')
                            ->with('error_detail', $errorMessage);
                    } else {
                        log_message('debug', "Login allowed: user_company({$userCompanyTrimmed}) matches subdomain_comp_code({$subdomainCompCodeTrimmed}), username={$username}");
                    }
                }
            }
            
            // 로그인 시 인성 API 주문 목록 동기화 (CLI 명령어로 백그라운드 실행)
            try {
                if (!empty($mCode) && !empty($ccCode) && !empty($user['user_id'])) {
                    // CLI 명령어를 백그라운드로 실행
                    $this->syncInsungOrdersViaCLI($mCode, $ccCode, $user['user_id']);
                }
            } catch (\Exception $e) {
                // 주문 목록 동기화 실패해도 로그인은 진행
                log_message('error', "Failed to trigger Insung orders sync on login: " . $e->getMessage());
            }
            
            // AJAX 요청인 경우 JSON 응답 반환
            if ($this->request->isAJAX()) {
                // 현재 요청의 프로토콜을 유지하여 리다이렉트 URL 생성
                $currentProtocol = $this->request->getServer('HTTPS') && $this->request->getServer('HTTPS') !== 'off' ? 'https' : 'http';
                $currentHost = $this->request->getServer('HTTP_HOST') ?? '';
                // 개발/로컬 환경에서는 HTTP 강제
                if (ENVIRONMENT !== 'production') {
                    $currentProtocol = 'http';
                }
                $redirectUrl = $currentProtocol . '://' . $currentHost . '/';
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => '로그인되었습니다.',
                    'redirect' => $redirectUrl
                ]);
            }
            
            // 현재 요청의 프로토콜을 유지하여 리다이렉트
            $currentProtocol = $this->request->getServer('HTTPS') && $this->request->getServer('HTTPS') !== 'off' ? 'https' : 'http';
            $currentHost = $this->request->getServer('HTTP_HOST') ?? '';
            // 개발/로컬 환경에서는 HTTP 강제
            if (ENVIRONMENT !== 'production') {
                $currentProtocol = 'http';
            }
            $redirectUrl = $currentProtocol . '://' . $currentHost . '/';
            return redirect()->to($redirectUrl)->with('success', '로그인되었습니다.');
        } else {
            // 기존 STN 로그인 처리
            $user = $this->authModel->authenticate($username, $password);
            
            // 디버깅용 로그 (임시)
            log_message('debug', 'Login attempt: username=' . $username . ', user_found=' . ($user ? 'yes' : 'no'));
            
            if ($user) {
                // 고객사 정보 조회
                $customerInfo = $this->authModel->getCustomerInfo($user['customer_id']);
                
                // 세션에 사용자 정보 저장
                $userData = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'real_name' => $user['real_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'customer_id' => $user['customer_id'],
                    'customer_name' => $customerInfo['customer_name'] ?? '',
                    'customer_code' => $customerInfo['customer_code'] ?? '',
                    'hierarchy_level' => $customerInfo['hierarchy_level'] ?? '',
                    'user_role' => $user['user_role'],
                    'department_id' => $user['department_id'],
                    'login_type' => 'stn',
                    'is_logged_in' => true
                ];
                
                session()->set($userData);
                
                // 마지막 로그인 시간 업데이트
                $this->authModel->updateUserInfo($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
                
                // AJAX 요청인 경우 JSON 응답 반환
                if ($this->request->isAJAX()) {
                    // 현재 요청의 프로토콜을 유지하여 리다이렉트 URL 생성
                    $currentProtocol = $this->request->getServer('HTTPS') && $this->request->getServer('HTTPS') !== 'off' ? 'https' : 'http';
                    $currentHost = $this->request->getServer('HTTP_HOST') ?? '';
                    // 개발/로컬 환경에서는 HTTP 강제
                    if (ENVIRONMENT !== 'production') {
                        $currentProtocol = 'http';
                    }
                    $redirectUrl = $currentProtocol . '://' . $currentHost . '/';
                    
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => '로그인되었습니다.',
                        'redirect' => $redirectUrl
                    ]);
                }
                
                // 현재 요청의 프로토콜을 유지하여 리다이렉트
                $currentProtocol = $this->request->getServer('HTTPS') && $this->request->getServer('HTTPS') !== 'off' ? 'https' : 'http';
                $currentHost = $this->request->getServer('HTTP_HOST') ?? '';
                // 개발/로컬 환경에서는 HTTP 강제
                if (ENVIRONMENT !== 'production') {
                    $currentProtocol = 'http';
                }
                $redirectUrl = $currentProtocol . '://' . $currentHost . '/';
                return redirect()->to($redirectUrl)->with('success', '로그인되었습니다.');
            } else {
                // STN 로그인 실패: 아이디/비밀번호 불일치
                return redirect()->back()
                    ->withInput()
                    ->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.')
                    ->with('error_detail', '입력하신 아이디와 비밀번호를 확인해주세요. 대소문자와 특수문자를 정확히 입력했는지 확인하시기 바랍니다. 비밀번호를 잊으셨다면 시스템 관리자에게 문의하세요.');
            }
        }
    }
    
    /**
     * 로그아웃 처리
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login')->with('success', '로그아웃되었습니다.');
    }
    
    /**
     * 로그인 체크 미들웨어
     */
    public function checkLogin()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
    }
    
    /**
     * 로그인 시 인성 API 주문 목록 동기화 (CLI 명령어로 백그라운드 실행)
     * 
     * @param string $mCode 마스터 코드
     * @param string $ccCode 콜센터 코드
     * @param string $userId 사용자 ID
     */
    private function syncInsungOrdersViaCLI($mCode, $ccCode, $userId)
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
            
            // 오늘 날짜
            $today = date('Y-m-d');
            
            // CLI 명령어 구성
            // 백그라운드 실행을 위해 nohup 또는 & 사용
            // Windows 환경에서는 다르게 처리해야 할 수 있음
            $command = sprintf(
                'php %s insung:sync-orders %s %s %s %s %s > /dev/null 2>&1 &',
                escapeshellarg($sparkPath),
                escapeshellarg($mCode),
                escapeshellarg($ccCode),
                escapeshellarg($userId),
                escapeshellarg($today),
                escapeshellarg($today)
            );
            
            
            // 명령어 실행
            exec($command);
            
            // log_message('info', "Insung orders sync CLI command triggered: {$command}");
            
        } catch (\Exception $e) {
            log_message('error', "Exception in syncInsungOrdersViaCLI: " . $e->getMessage());
        }
    }
}
