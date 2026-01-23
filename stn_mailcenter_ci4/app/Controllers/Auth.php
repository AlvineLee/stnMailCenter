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
        
        $apiCode = null; // api_code 추가
        
        if ($isSubdomainAccess) {
            // 서브도메인 접근 시: 해당 서브도메인의 API만 조회
            $apiCodes = $subdomainConfig->getCurrentApiCodes();
            if ($apiCodes) {
                $insungApiListModel = new \App\Models\InsungApiListModel();
                $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($apiCodes['m_code'], $apiCodes['cc_code']);
                if ($apiInfo) {
                    $apiIdx = $apiInfo['idx'] ?? null;
                    $apiCode = $apiInfo['api_code'] ?? null; // api_code 가져오기
                    // 서브도메인 API를 리스트에 추가 (드롭다운 표시용)
                    $apiList = [$apiInfo];
                }
            }
        } else {
            // 메인도메인일 때 mcode=4540인 API 목록 조회
            $insungApiListModel = new \App\Models\InsungApiListModel();
            $apiList = $insungApiListModel->getApiListByMcode('4540');
        }
        
        // QR코드 생성 (PHP 라이브러리 사용)
        $qrCodeBase64 = null;
        try {
            // 로고 이미지 경로 결정
            $logoPath = null;
            if (!empty($subdomainInfo['logo_path'])) {
                $logoPath = ROOTPATH . 'public/' . $subdomainInfo['logo_path'];
                if (!file_exists($logoPath)) {
                    $logoPath = null;
                }
            }
            // 로고가 없으면 기본 DaumData 로고 사용
            if (!$logoPath) {
                $defaultLogoPath = ROOTPATH . 'public/assets/images/logo/daumdata_logo_2.png';
                if (file_exists($defaultLogoPath)) {
                    $logoPath = $defaultLogoPath;
                }
            }
            
            // QR코드 크기 (더 크게 생성하여 로고가 인식 범위 안쪽에 들어가도록)
            $qrSize = 200;
            // 로고 크기: QR코드 크기의 약 15% (인식 범위 안쪽에 안전하게 배치)
            $logoSize = 30;
            
            // endroid/qr-code v4.x 사용
            if (class_exists('\Endroid\QrCode\Builder\Builder')) {
                $builder = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data(current_url())
                    ->size($qrSize)
                    ->margin(2);
                
                // 오류 정정 레벨 설정 (H: High - 최대 30% 데이터 손실 허용)
                if (class_exists('\Endroid\QrCode\ErrorCorrectionLevel')) {
                    $builder->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High);
                }
                
                // 로고가 있으면 중앙에 추가 (인식 범위 안쪽에 배치)
                if ($logoPath) {
                    $builder->logoPath($logoPath)
                        ->logoResizeToWidth($logoSize)
                        ->logoResizeToHeight($logoSize);
                }
                
                $result = $builder->build();
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($result->getString());
            }
            // endroid/qr-code v3.x 사용 (하위 호환)
            elseif (class_exists('\Endroid\QrCode\QrCode')) {
                $qrCode = new \Endroid\QrCode\QrCode(current_url());
                $qrCode->setSize($qrSize);
                $qrCode->setMargin(2);
                
                // 오류 정정 레벨 설정 (H: High)
                if (method_exists($qrCode, 'setErrorCorrectionLevel')) {
                    $qrCode->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High);
                }
                
                // 로고가 있으면 중앙에 추가 (인식 범위 안쪽에 배치)
                if ($logoPath) {
                    $qrCode->setLogoPath($logoPath);
                    $qrCode->setLogoWidth($logoSize);
                    $qrCode->setLogoHeight($logoSize);
                }
                
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($result->getString());
            }
        } catch (\Exception $e) {
            log_message('error', 'QR코드 생성 실패: ' . $e->getMessage());
        }
        
        $data = [
            'title' => $subdomainInfo['name'] . ' - 로그인',
            'error' => session()->getFlashdata('error'),
            'subdomain' => $subdomainInfo,
            'is_subdomain' => $isSubdomainAccess,
            'api_idx' => $apiIdx,
            'api_code' => $apiCode, // api_code 추가
            'api_list' => $apiList,
            'qr_code' => $qrCodeBase64
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
        $ipAddress = $this->request->getIPAddress(); // 내부망 IP (REMOTE_ADDR)
        $forwardedIp = $this->getForwardedIp(); // 외부 IP (X-Forwarded-For)

        // reCAPTCHA v3 검증
        $recaptchaResult = $this->verifyRecaptcha();
        if ($recaptchaResult !== true) {
            // AJAX 요청인 경우 JSON 응답 반환
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => '보안 검증 실패',
                    'error_detail' => $recaptchaResult,
                    'error_type' => 'recaptcha_failed'
                ]);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', '보안 검증 실패')
                ->with('error_detail', $recaptchaResult);
        }

        // 로그인 잠금 체크
        $loginAttemptModel = new \App\Models\LoginAttemptModel();
        $systemSettingModel = new \App\Models\SystemSettingModel();

        $loginSettings = $systemSettingModel->getLoginSettings();
        $maxAttempts = (int)($loginSettings['login_max_attempts'] ?? 5);
        $lockoutMinutes = (int)($loginSettings['login_lockout_minutes'] ?? 5);

        $lockStatus = $loginAttemptModel->isLocked($username, $ipAddress, $maxAttempts, $lockoutMinutes);

        if ($lockStatus['locked']) {
            $remainingMinutes = ceil($lockStatus['remaining_seconds'] / 60);
            $errorMessage = "로그인 시도 횟수({$maxAttempts}회)를 초과하여 {$remainingMinutes}분 동안 로그인이 제한됩니다.";

            // AJAX 요청인 경우 JSON 응답 반환
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => '로그인이 일시적으로 제한되었습니다.',
                    'error_detail' => $errorMessage,
                    'error_type' => 'login_locked',
                    'remaining_seconds' => $lockStatus['remaining_seconds']
                ]);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', '로그인이 일시적으로 제한되었습니다.')
                ->with('error_detail', $errorMessage);
        }

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
            
            // 인성 API로 회원정보 조회 (신규 회원 추가 또는 기존 회원 정보 업데이트)
            // stnlogis와 동일하게 로그인할 때 무조건 인성 회원 조회 API를 호출하여 주소 필드 업데이트
            // DB에 사용자가 없거나 있거나 상관없이 API 호출
            // 먼저 user_id만으로 DB 조회 (비밀번호는 나중에 확인)
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->where('user_id', $username);
            $userQuery = $userBuilder->get();
            $existingUser = $userQuery ? $userQuery->getRowArray() : null;
            
            // 인성 API로 회원정보 조회 (로그인 성공 여부와 관계없이 항상 호출)
            if (true) {
                
                // 인성 API로 회원정보 조회 (신규 회원 추가 또는 기존 회원 정보 업데이트)
                // 메인도메인에서 선택한 API 정보 또는 서브도메인 기본 API 정보 가져오기
                $selectedApiIdx = $this->request->getPost('selected_api_idx');
                $mCode = null;
                $ccCode = null;
                $token = null;
                $apiIdx = null;
                
                $apiName = null;  // 콜센터명 (로고 옆 표시용)
                $ccCompName = null;  // 거래처명 (로고 옆 표시용)

                if ($selectedApiIdx) {
                    // 메인도메인: 선택한 API 정보 사용
                    // selected_api_idx는 tbl_api_list.cccode 값이므로 cccode로 조회
                    $apiListModel = new \App\Models\InsungApiListModel();
                    $apiInfo = $apiListModel->where('cccode', $selectedApiIdx)->first();
                    log_message('info', "Auth::processLogin - apiInfo for cccode={$selectedApiIdx}: " . json_encode($apiInfo, JSON_UNESCAPED_UNICODE));
                    if ($apiInfo) {
                        $mCode = $apiInfo['mcode'];
                        $ccCode = $apiInfo['cccode'];
                        $token = $apiInfo['token'];
                        $apiIdx = $apiInfo['idx'];  // 실제 tbl_api_list.idx 저장
                        $apiName = $apiInfo['api_name'] ?? null;  // 콜센터명
                    }
                } else {
                    // 서브도메인: 서브도메인의 comp_code로 API 정보 조회
                    $subdomainConfig = config('Subdomain');
                    $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
                    
                    if ($currentSubdomain && $currentSubdomain !== 'default') {
                        $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
                        
                        if ($subdomainCompCode) {
                            // comp_code로 API 정보 조회 (tbl_company_list -> tbl_cc_list -> tbl_api_list)
                            $compBuilder = $db->table('tbl_company_list c');
                            $compBuilder->select('
                                d.mcode as m_code,
                                d.cccode as cc_code,
                                d.token,
                                d.idx as api_idx
                            ');
                            $compBuilder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
                            $compBuilder->join('tbl_api_list d', 'cc.cc_apicode = d.idx', 'inner');
                            $compBuilder->where('c.comp_code', $subdomainCompCode);
                            $compQuery = $compBuilder->get();
                            
                            if ($compQuery !== false) {
                                $compResult = $compQuery->getRowArray();
                                if ($compResult) {
                                    $mCode = $compResult['m_code'];
                                    $ccCode = $compResult['cc_code'];
                                    $token = $compResult['token'];
                                    $apiIdx = $compResult['api_idx'];
                                }
                            }
                        }
                    }
                }
                
                // API 정보가 있으면 회원정보 조회
                if ($mCode && $ccCode && $token) {
                    try {
                        $insungApiService = new \App\Libraries\InsungApiService();
                        $memberResult = $insungApiService->getMemberDetail($mCode, $ccCode, $token, $username, $apiIdx);
                        
                        if ($memberResult && (is_array($memberResult) || is_object($memberResult))) {
                            // API 응답 파싱
                            $code = '';
                            $memberDetail = null;
                            
                            if (is_array($memberResult) && isset($memberResult[0])) {
                                $code = $memberResult[0]->code ?? $memberResult[0]['code'] ?? '';
                                if ($code === '1000' && isset($memberResult[1])) {
                                    $memberDetail = is_object($memberResult[1]) ? (array)$memberResult[1] : $memberResult[1];
                                }
                            } elseif (is_object($memberResult) && isset($memberResult->Result)) {
                                $code = $memberResult->Result[0]->result_info[0]->code ?? '';
                                if ($code === '1000' && isset($memberResult->Result[1]->item[0])) {
                                    $memberDetail = (array)$memberResult->Result[1]->item[0];
                                }
                            }
                            
                            // 회원정보가 조회되면 DB에 추가 또는 업데이트
                            if ($code === '1000' && $memberDetail) {
                                $userCcode = $memberDetail['c_code'] ?? $memberDetail['user_code'] ?? '';
                                $userName = $memberDetail['name'] ?? $memberDetail['cust_name'] ?? '';
                                $userDept = $memberDetail['dept_name'] ?? '';
                                $userTel1 = $memberDetail['tel_no1'] ?? $memberDetail['tel_number'] ?? '';
                                $userTel2 = $memberDetail['tel_no2'] ?? '';
                                $compNo = $memberDetail['comp_no'] ?? '';
                                $userCompany = $compNo; // comp_no를 user_company로 사용
                                
                                // user_company로 comp_code 조회 (tbl_company_list)
                                $compCode = null;
                                if ($userCompany) {
                                    $compBuilder = $db->table('tbl_company_list');
                                    $compBuilder->select('comp_code');
                                    $compBuilder->where('comp_code', $userCompany);
                                    $compQuery = $compBuilder->get();
                                    if ($compQuery !== false) {
                                        $compResult = $compQuery->getRowArray();
                                        if ($compResult) {
                                            $compCode = $compResult['comp_code'];
                                        }
                                    }
                                }
                                
                                // 주소 정보
                                $sido = $memberDetail['sido'] ?? '';
                                $gugun = $memberDetail['gugun'] ?? '';
                                $dongName = $memberDetail['dong_name'] ?? $memberDetail['basic_dong'] ?? '';
                                $ri = $memberDetail['ri'] ?? '';
                                $userAddr = trim(implode(' ', array_filter([$sido, $gugun, $dongName, $ri])));
                                $lon = $memberDetail['lon'] ?? '';
                                $lat = $memberDetail['lat'] ?? '';
                                
                                // 암호화 헬퍼 인스턴스 생성
                                $encryptionHelper = new \App\Libraries\EncryptionHelper();
                                $encryptedFields = ['user_pass', 'user_name', 'user_tel1', 'user_tel2'];
                                
                                if (!$existingUser) {
                                    // 신규 회원: 인성 API login으로 비밀번호 검증 후 DB에 추가
                                    // 비밀번호 검증 없이 DB에 저장하면 보안 취약점이 됨
                                    $loginResult = $insungApiService->login($mCode, $ccCode, $token, $username, $password, $apiIdx);

                                    // 인성 API 로그인 결과 확인
                                    $loginSuccess = false;
                                    if ($loginResult && (is_array($loginResult) || is_object($loginResult))) {
                                        $loginCode = '';
                                        if (is_array($loginResult) && isset($loginResult[0])) {
                                            $loginCode = $loginResult[0]->code ?? $loginResult[0]['code'] ?? '';
                                        } elseif (is_object($loginResult) && isset($loginResult->Result)) {
                                            $loginCode = $loginResult->Result[0]->result_info[0]->code ?? '';
                                        }
                                        $loginSuccess = ($loginCode === '1000');
                                        log_message('info', "Auth::processLogin - Insung API login result: code={$loginCode}, success=" . ($loginSuccess ? 'yes' : 'no'));
                                    }

                                    if ($loginSuccess) {
                                        // 인성 API 로그인 성공: DB에 신규 회원 등록
                                        $newUserData = [
                                            'user_id' => $username,
                                            'user_pass' => $password, // 인성 API에서 검증된 비밀번호 저장
                                            'user_name' => $userName,
                                            'user_dept' => $userDept,
                                            'user_tel1' => $userTel1,
                                            'user_company' => $compCode ?? $userCompany,
                                            'user_ccode' => $userCcode,
                                            'user_type' => '5', // 기본값: 일반 고객
                                            'user_class' => '5', // 기본값: 일반
                                            'user_addr' => $userAddr,
                                            'user_addr_detail' => $ri,
                                            'user_sido' => $sido,
                                            'user_gungu' => $gugun,
                                            'user_dong' => $dongName,
                                            'user_lon' => $lon ?: null,
                                            'user_lat' => $lat ?: null
                                        ];

                                        if (!empty($userTel2)) {
                                            $newUserData['user_tel2'] = $userTel2;
                                        }

                                        // 회원정보 변경과 동일하게 모델의 insert() 메서드 사용 (beforeInsert 콜백으로 자동 암호화)
                                        $insertResult = $this->insungUsersListModel->insert($newUserData);

                                        if ($insertResult) {
                                            log_message('info', "Auth::processLogin - Auto-registered user from API: user_id={$username}");
                                            // 다시 인증 시도
                                            $user = $this->insungUsersListModel->authenticate($username, $password);
                                        }
                                    } else {
                                        log_message('warning', "Auth::processLogin - Insung API login failed for new user: user_id={$username}");
                                        // 인성 API 로그인 실패: $user는 false 유지 (로그인 실패)
                                    }
                                } else {
                                    // 기존 회원: 주소 정보 업데이트
                                    $updateData = [];
                                    $needsUpdate = false;

                                    // 로컬 DB 인증 실패 시 인성 API login으로 비밀번호 검증
                                    if (!$user) {
                                        log_message('info', "Auth::processLogin - Local auth failed for existing user, trying Insung API login: user_id={$username}");
                                        $loginResult = $insungApiService->login($mCode, $ccCode, $token, $username, $password, $apiIdx);

                                        // 인성 API 로그인 결과 확인
                                        $loginSuccess = false;
                                        if ($loginResult && (is_array($loginResult) || is_object($loginResult))) {
                                            $loginCode = '';
                                            if (is_array($loginResult) && isset($loginResult[0])) {
                                                $loginCode = $loginResult[0]->code ?? $loginResult[0]['code'] ?? '';
                                            } elseif (is_object($loginResult) && isset($loginResult->Result)) {
                                                $loginCode = $loginResult->Result[0]->result_info[0]->code ?? '';
                                            }
                                            $loginSuccess = ($loginCode === '1000');
                                            log_message('info', "Auth::processLogin - Insung API login result for existing user: code={$loginCode}, success=" . ($loginSuccess ? 'yes' : 'no'));
                                        }

                                        if ($loginSuccess) {
                                            // 인성 API 로그인 성공: 로컬 DB 비밀번호 업데이트 필요
                                            $updateData['user_pass'] = $password;
                                            $needsUpdate = true;
                                            log_message('info', "Auth::processLogin - Insung API login success, will update local password: user_id={$username}");
                                        } else {
                                            // 인성 API 로그인도 실패: 로그인 실패 유지
                                            log_message('warning', "Auth::processLogin - Both local and Insung API login failed: user_id={$username}");
                                        }
                                    }

                                    // 기존 데이터 복호화 (비교용)
                                    $decryptedExisting = $encryptionHelper->decryptFields($existingUser, $encryptedFields);

                                    // 이름 변경 확인
                                    if (!empty($userName) && $decryptedExisting['user_name'] !== $userName) {
                                        $updateData['user_name'] = $userName;
                                        $needsUpdate = true;
                                    }
                                    
                                    // 부서 변경 확인
                                    if ($decryptedExisting['user_dept'] !== $userDept) {
                                        $updateData['user_dept'] = $userDept;
                                        $needsUpdate = true;
                                    }
                                    
                                    // 전화번호1 변경 확인
                                    if (!empty($userTel1) && $decryptedExisting['user_tel1'] !== $userTel1) {
                                        $updateData['user_tel1'] = $userTel1;
                                        $needsUpdate = true;
                                    }
                                    
                                    // 전화번호2 변경 확인
                                    if ($decryptedExisting['user_tel2'] !== ($userTel2 ?? '')) {
                                        if (!empty($userTel2)) {
                                            $updateData['user_tel2'] = $userTel2;
                                        } else {
                                            $updateData['user_tel2'] = null;
                                        }
                                        $needsUpdate = true;
                                    }
                                    
                                    // 회사 코드 변경 확인
                                    $newCompCode = $compCode ?? $userCompany;
                                    if (!empty($newCompCode) && $decryptedExisting['user_company'] !== $newCompCode) {
                                        $updateData['user_company'] = $newCompCode;
                                        $needsUpdate = true;
                                    }
                                    
                                    // c_code 변경 확인
                                    if (!empty($userCcode) && $decryptedExisting['user_ccode'] !== $userCcode) {
                                        $updateData['user_ccode'] = $userCcode;
                                        $needsUpdate = true;
                                    }
                                    
                                    // 주소 정보는 stnlogis와 동일하게 무조건 업데이트 (변경 확인 없이)
                                    $updateData['user_addr'] = $userAddr;
                                    $updateData['user_addr_detail'] = $ri;
                                    $updateData['user_sido'] = $sido;
                                    $updateData['user_gungu'] = $gugun;
                                    $updateData['user_dong'] = $dongName;
                                    $updateData['user_lon'] = !empty($lon) ? $lon : null;
                                    $updateData['user_lat'] = !empty($lat) ? $lat : null;
                                    $needsUpdate = true; // 주소 필드는 무조건 업데이트

                                    // 주의: 비밀번호는 업데이트하지 않음!
                                    // 기존 회원의 비밀번호를 입력된 값으로 변경하면 보안 취약점이 됨
                                    // (잘못된 비밀번호로도 로그인 가능해짐)
                                    // 비밀번호 변경은 회원정보 수정 페이지에서만 가능하도록 함

                                    // 변경사항이 있으면 업데이트
                                    if ($needsUpdate) {
                                        // 회원정보 변경 페이지와 동일하게 null 값 제거
                                        $updateData = array_filter($updateData, function($value) {
                                            return $value !== null;
                                        });
                                        
                                        // log_message('debug', "Auth::processLogin - Updating existing user: user_id={$username}, updateData keys: " . json_encode(array_keys($updateData), JSON_UNESCAPED_UNICODE));
                                        // log_message('debug', "Auth::processLogin - updateData contains user_pass: " . (isset($updateData['user_pass']) ? 'yes, value=' . substr($updateData['user_pass'], 0, 5) . '...' : 'no'));
                                        // log_message('debug', "Auth::processLogin - Password before update: " . ($password ?? 'empty'));
                                        
                                        // 회원정보 변경과 동일하게 모델의 update() 메서드 사용 (beforeUpdate 콜백으로 자동 암호화)
                                        // InsungUsersListModel의 update() 메서드는 beforeUpdate 콜백을 통해 자동으로 암호화 처리
                                        $userIdx = $existingUser['idx'] ?? null;
                                        if ($userIdx) {
                                            // log_message('debug', "Auth::processLogin - Calling InsungUsersListModel->update() with user_idx={$userIdx}, updateData keys: " . json_encode(array_keys($updateData), JSON_UNESCAPED_UNICODE));
                                            $updateResult = $this->insungUsersListModel->update($userIdx, $updateData);
                                            // log_message('debug', "Auth::processLogin - Update result: " . ($updateResult ? 'success' : 'failed'));
                                        } else {
                                            log_message('error', "Auth::processLogin - user idx not found for user_id={$username}");
                                            $updateResult = false;
                                        }
                                        
                                        if ($updateResult) {
                                            log_message('info', "Auth::processLogin - Updated user info from API: user_id={$username}, updated_rows={$updateResult}");
                                            // 비밀번호가 업데이트된 경우 (인성 API 로그인 성공) 다시 인증 시도
                                            if (isset($updateData['user_pass'])) {
                                                $user = $this->insungUsersListModel->authenticate($username, $password);
                                                log_message('info', "Auth::processLogin - Re-authenticated after password update: user_id={$username}, success=" . ($user ? 'yes' : 'no'));
                                            }
                                            // 비밀번호 업데이트 없이 주소만 업데이트한 경우 $user는 최초 authenticate() 결과 유지
                                        } else {
                                            log_message('warning', "Auth::processLogin - Update failed: user_id={$username}, updateResult={$updateResult}, SQL error: " . ($db->error()['message'] ?? 'none'));
                                        }
                                    } else {
                                        // log_message('debug', "Auth::processLogin - No update needed for user_id={$username}");
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        log_message('error', "Auth::processLogin - Failed to fetch member from API: " . $e->getMessage());
                    }
                }
            }
            
            // API 호출 후에도 인증 실패한 경우
            if (!$user) {
                // 로그인 실패 기록
                $loginAttemptModel->recordAttempt($username, $ipAddress, $forwardedIp, false, '아이디/비밀번호 불일치', 'daumdata');

                // 현재 실패 횟수 확인
                $currentLockStatus = $loginAttemptModel->isLocked($username, $ipAddress, $maxAttempts, $lockoutMinutes);
                $remainingAttempts = $maxAttempts - $currentLockStatus['failure_count'];
                $failCount = $currentLockStatus['failure_count'] ?? 0;

                // 5회 이상 실패 시 v2 챌린지 필요
                $needsV2Challenge = ($failCount >= 5);

                // 로그인 실패: 아이디/비밀번호 불일치
                $errorMessage = '입력하신 아이디와 비밀번호를 확인해주세요. 대소문자와 특수문자를 정확히 입력했는지 확인하시기 바랍니다.';
                if ($remainingAttempts > 0) {
                    $errorMessage .= " (남은 시도 횟수: {$remainingAttempts}회)";
                }

                // AJAX 요청인 경우 JSON 응답 반환
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'success' => false,
                        'error' => '아이디 또는 비밀번호가 올바르지 않습니다.',
                        'error_detail' => $errorMessage,
                        'error_type' => 'invalid_credentials',
                        'remaining_attempts' => $remainingAttempts,
                        'needs_v2_challenge' => $needsV2Challenge
                    ]);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.')
                    ->with('error_detail', $errorMessage);
            }
            
            // $user가 있으면 로그인 성공 처리
            // 초기 API 정보 설정 (로그인 성공 후 sync_user_data에서 사용하기 위해 먼저 설정)
            $ckey = $user['ckey'] ?? '';
            $mCode = $user['m_code'] ?? '';
            $ccCode = $user['cc_code'] ?? '';
            $apiIdx = null;
            
            // 메인도메인에서 선택한 API 정보 처리
            $subdomainConfig = config('Subdomain');
            $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
            $selectedApiIdx = $this->request->getPost('selected_api_idx');
            
            // 메인도메인이고 API가 선택된 경우, 선택한 API 정보로 업데이트
            $apiIdx = null;
            if ($currentSubdomain === 'default' && !empty($selectedApiIdx)) {
                try {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    $selectedApiInfo = $insungApiListModel->getApiInfoByIdx($selectedApiIdx);
                    
                    if ($selectedApiInfo) {
                        // 선택한 API 정보로 업데이트
                        $mCode = $selectedApiInfo['mcode'] ?? $mCode;
                        $ccCode = $selectedApiInfo['cccode'] ?? $ccCode;
                        $ckey = $selectedApiInfo['ckey'] ?? $ckey;
                        $apiIdx = $selectedApiIdx; // 선택한 API idx 저장
                    }
                } catch (\Exception $e) {
                    log_message('error', "Error retrieving selected API info: " . $e->getMessage());
                }
            }
            
            // api_idx가 없으면 mCode, ccCode로 조회
            if (!$apiIdx && !empty($mCode) && !empty($ccCode)) {
                try {
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                    if ($apiInfo && isset($apiInfo['idx'])) {
                        $apiIdx = $apiInfo['idx'];
                    }
                } catch (\Exception $e) {
                    log_message('error', "Error retrieving API idx: " . $e->getMessage());
                }
            }
            
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
            
            // stnlogis와 동일하게 로그인 성공 후에도 주소 필드 업데이트 (sync_user_data와 동일한 동작)
            // user_type이 3 또는 5일 때 주소 정보 동기화
            $userType = $user['user_type'] ?? '5';
            $memberCreditValue = null; // getMemberDetail에서 가져온 credit 값 저장용
            if (($userType == '3' || $userType == '5') && !empty($mCode) && !empty($ccCode) && !empty($token)) {
                try {
                    // $db 변수 정의 (sync_user_data에서 사용)
                    if (!isset($db)) {
                        $db = \Config\Database::connect();
                    }
                    
                    // log_message('debug', "Auth::processLogin - Starting sync_user_data: user_id={$username}, user_type={$userType}, mCode={$mCode}, ccCode={$ccCode}");
                    
                    // 로그인 성공 후 주소 정보 동기화 (stnlogis의 sync_user_data와 동일)
                    $insungApiService = new \App\Libraries\InsungApiService();
                    $memberResult = $insungApiService->getMemberDetail($mCode, $ccCode, $token, $username, $apiIdx);
                    
                    if ($memberResult && (is_array($memberResult) || is_object($memberResult))) {
                        $code = '';
                        $memberDetail = null;
                        
                        if (is_array($memberResult) && isset($memberResult[0])) {
                            $code = $memberResult[0]->code ?? $memberResult[0]['code'] ?? '';
                            if ($code === '1000' && isset($memberResult[1])) {
                                $memberDetail = is_object($memberResult[1]) ? (array)$memberResult[1] : $memberResult[1];
                            }
                        } elseif (is_object($memberResult) && isset($memberResult->Result)) {
                            $code = $memberResult->Result[0]->result_info[0]->code ?? '';
                            if ($code === '1000' && isset($memberResult->Result[1]->item[0])) {
                                $memberDetail = (array)$memberResult->Result[1]->item[0];
                            }
                        }
                        
                        // log_message('debug', "Auth::processLogin - sync_user_data API response: code={$code}, memberDetail=" . ($memberDetail ? 'exists' : 'null'));
                        
                        // 주소 정보 업데이트 (stnlogis의 sync_user_data와 동일)
                        if ($code === '1000' && $memberDetail) {
                            // credit 값 추출 (회원 상세 정보에서 가져옴)
                            // 우선순위: credit_customer_code (회원별 지급구분) > credit (거래처 기본 지급구분)
                            if (isset($memberDetail['credit_customer_code']) && $memberDetail['credit_customer_code'] !== '' && $memberDetail['credit_customer_code'] !== null) {
                                $memberCreditValue = $memberDetail['credit_customer_code'];
                                log_message('info', "Auth::processLogin - Credit from credit_customer_code: {$memberCreditValue}");
                            } elseif (isset($memberDetail['credit']) && !empty($memberDetail['credit'])) {
                                $memberCreditValue = $memberDetail['credit'];
                                log_message('info', "Auth::processLogin - Credit from member_detail (default): {$memberCreditValue}");
                            } else {
                                log_message('info', "Auth::processLogin - Credit not in member_detail, keys: " . json_encode(array_keys($memberDetail)));
                            }

                            $sido = $memberDetail['sido'] ?? '';
                            $gugun = $memberDetail['gugun'] ?? '';
                            $dongName = $memberDetail['dong_name'] ?? $memberDetail['basic_dong'] ?? '';
                            $ri = $memberDetail['ri'] ?? '';
                            $userAddr = '';
                            if ($sido) {
                                $userAddr = trim($sido . ' ' . $gugun . ' ' . $dongName . ' ' . $ri);
                            }
                            $lon = $memberDetail['lon'] ?? '';
                            $lat = $memberDetail['lat'] ?? '';
                            $compNo = $memberDetail['comp_no'] ?? '';
                            if ($compNo == '0') {
                                $compNo = '';
                            }
                            
                            // 암호화 헬퍼 인스턴스 생성
                            $encryptionHelper = new \App\Libraries\EncryptionHelper();
                            $encryptedFields = ['user_pass', 'user_name', 'user_tel1', 'user_tel2'];
                            
                            // 기존 사용자 정보 조회 (비밀번호 평문 여부 확인용)
                            $existingUserForSync = $this->insungUsersListModel->where('user_id', $username)->first();
                            $isPlainTextPassword = false;
                            if ($existingUserForSync && isset($existingUserForSync['user_pass'])) {
                                $existingPassword = $existingUserForSync['user_pass'];
                                // 암호화된 데이터는 최소 30자 이상이어야 함
                                if (strlen($existingPassword) < 30) {
                                    $isPlainTextPassword = true;
                                } else {
                                    // base64 디코딩 시도
                                    $decoded = base64_decode($existingPassword, true);
                                    if ($decoded === false) {
                                        $isPlainTextPassword = true;
                                    } else {
                                        // 복호화 시도
                                        $testDecrypted = $encryptionHelper->decrypt($existingPassword);
                                        // 복호화 결과가 원본과 같으면 평문
                                        if ($testDecrypted === $existingPassword) {
                                            $isPlainTextPassword = true;
                                        }
                                    }
                                }
                            }
                            
                            // 주소 필드 무조건 업데이트 (stnlogis와 동일)
                            $updateData = [
                                'user_company' => $compNo,
                                'user_dept' => $memberDetail['dept_name'] ?? '',
                                'user_name' => $memberDetail['charge_name'] ?? $memberDetail['cust_name'] ?? '',
                                'user_tel1' => $memberDetail['tel_number'] ?? $memberDetail['tel_no1'] ?? '',
                                'user_addr' => $userAddr,
                                'user_sido' => $sido,
                                'user_gungu' => $gugun,
                                'user_dong' => $dongName,
                                'user_addr_detail' => $ri,
                                'user_lon' => !empty($lon) ? $lon : null,
                                'user_lat' => !empty($lat) ? $lat : null
                            ];
                            
                            // 비밀번호가 평문이면 암호화 처리 (회원정보 변경 페이지와 동일하게)
                            if (!empty($password) && $isPlainTextPassword) {
                                // log_message('info', "Auth::processLogin - sync_user_data: Encrypting plaintext password for user_id={$username}");
                                $updateData['user_pass'] = $password;
                            }
                            
                            // log_message('debug', "Auth::processLogin - sync_user_data updateData before encryption: " . json_encode($updateData, JSON_UNESCAPED_UNICODE));
                            
                            // 암호화 처리 (수동으로 처리 - $db->table()->update()는 모델 콜백을 실행하지 않음)
                            $updateData = $encryptionHelper->encryptFields($updateData, $encryptedFields);
                            
                            // 암호화 결과 확인
                            $encryptedCheck = [];
                            foreach ($encryptedFields as $field) {
                                if (isset($updateData[$field])) {
                                    $encryptedCheck[$field] = [
                                        'original_length' => strlen($updateData[$field] ?? ''),
                                        'is_base64' => base64_decode($updateData[$field] ?? '', true) !== false,
                                        'preview' => substr($updateData[$field] ?? '', 0, 30)
                                    ];
                                }
                            }
                            // log_message('debug', "Auth::processLogin - sync_user_data updateData after encryption check: " . json_encode($encryptedCheck, JSON_UNESCAPED_UNICODE));
                            
                            // user_ccode로 업데이트 (stnlogis와 동일)
                            $userCcode = $memberDetail['c_code'] ?? $memberDetail['user_code'] ?? '';
                            if ($userCcode) {
                                $updateBuilder = $db->table('tbl_users_list');
                                $updateBuilder->where('user_ccode', $userCcode);
                                $updateResult = $updateBuilder->update($updateData);
                                
                                if ($updateResult) {
                                    log_message('info', "Auth::processLogin - Synced user address data (sync_user_data): user_id={$username}, user_ccode={$userCcode}, updated_rows={$updateResult}");
                                } else {
                                    $dbError = $db->error();
                                    log_message('warning', "Auth::processLogin - sync_user_data update failed: user_id={$username}, user_ccode={$userCcode}, updateResult={$updateResult}, SQL error: " . ($dbError['message'] ?? 'none'));
                                }
                            } else {
                                log_message('warning', "Auth::processLogin - sync_user_data: user_ccode not found in API response for user_id={$username}");
                            }
                        } else {
                            log_message('warning', "Auth::processLogin - sync_user_data: API code not 1000 or memberDetail empty. code={$code}");
                        }
                    } else {
                        log_message('warning', "Auth::processLogin - sync_user_data: memberResult is false or invalid for user_id={$username}");
                    }
                } catch (\Exception $e) {
                    log_message('error', "Auth::processLogin - Failed to sync user address data: " . $e->getMessage() . ", trace: " . $e->getTraceAsString());
                }
            } else {
                log_message('debug', "Auth::processLogin - sync_user_data skipped: user_type={$userType}, mCode=" . ($mCode ?? 'empty') . ", ccCode=" . ($ccCode ?? 'empty') . ", token=" . ($token ? 'exists' : 'empty'));
            }
            
            // ukey, akey 생성 (세션 저장용)
            $randomPrefix = bin2hex(random_bytes(4)); // 임의의 8글자 생성 (16진수)
            $ukey = $randomPrefix . $ckey; // 8글자 + ckey
            $akey = md5($ukey); // ukey를 MD5로 변환
            
            // 거래처 credit 값 조회 - getMemberDetailByCode API 사용 (/api/member_detail/find/)
            // 회원별 지급구분(credit_customer_code) 우선, 없으면 거래처 기본 지급구분(credit) 사용
            $credit = null;
            $userType = $user['user_type'] ?? '5';
            $userCcode = $user['user_ccode'] ?? null;

            if (!empty($mCode) && !empty($ccCode) && !empty($token) && !empty($userCcode)) {
                try {
                    $insungApiService = new \App\Libraries\InsungApiService();
                    // 회원 상세 조회 (c_code로 조회) - /api/member_detail/find/
                    $memberDetailResult = $insungApiService->getMemberDetailByCode($mCode, $ccCode, $token, $userCcode, $apiIdx);

                    // API 응답 구조 파싱
                    $memberInfo = null;
                    $code = '';
                    if ($memberDetailResult && (is_array($memberDetailResult) || is_object($memberDetailResult))) {
                        if (is_array($memberDetailResult) && isset($memberDetailResult[0])) {
                            $code = $memberDetailResult[0]->code ?? $memberDetailResult[0]['code'] ?? '';
                            if ($code === '1000' && isset($memberDetailResult[1])) {
                                $memberInfo = is_object($memberDetailResult[1]) ? (array)$memberDetailResult[1] : $memberDetailResult[1];
                            }
                        } elseif (is_object($memberDetailResult) && isset($memberDetailResult->Result)) {
                            $code = $memberDetailResult->Result[0]->result_info[0]->code ?? '';
                            if ($code === '1000' && isset($memberDetailResult->Result[1]->item[0])) {
                                $memberInfo = (array)$memberDetailResult->Result[1]->item[0];
                            }
                        }
                    }

                    if ($memberInfo) {
                        // credit 값 추출 - credit_customer_code 우선 사용 (회원별 지급구분)
                        if (isset($memberInfo['credit_customer_code']) && $memberInfo['credit_customer_code'] !== '') {
                            $credit = $memberInfo['credit_customer_code'];
                            // log_message('info', "Auth::processLogin - Credit from 'credit_customer_code': {$credit}");
                        } elseif (isset($memberInfo['credit']) && $memberInfo['credit'] !== '') {
                            $credit = $memberInfo['credit'];
                            // log_message('info', "Auth::processLogin - Credit from 'credit': {$credit}");
                        } else {
                            $credit = '3'; // 기본값
                        }

                        // 거래처명 가져오기 (로고 옆 표시용)
                        if (isset($memberInfo['cust_name']) && !empty($memberInfo['cust_name'])) {
                            $ccCompName = $memberInfo['cust_name'];
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', "Auth::processLogin - Failed to fetch member credit: " . $e->getMessage());
                }
            }

            // log_message('info', "Auth::processLogin - Final credit value: " . ($credit ?? 'NULL'));
            
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
                'user_ccode' => $user['user_ccode'] ?? null, // 인성 시스템 회원 코드 (API 조회용)
                'user_cc_idx' => $user['cc_idx'] ?? null, // tbl_company_list.cc_idx (콜센터 관리자용)
                'cc_code' => $ccCode, // 선택한 API의 cc_code 또는 기존 값
                'm_code' => $mCode, // 선택한 API의 m_code 또는 기존 값
                'token' => $token, // 선택한 API의 token 또는 기존 값
                'ckey' => $ckey, // 선택한 API의 ckey 또는 기존 값
                'api_idx' => $apiIdx, // 선택한 API의 idx (주문접수, 직원검색 등에서 사용)
                'ukey' => $ukey, // ukey
                'akey' => $akey, // akey
                'user_type' => $userType, // 메뉴 접근 권한용
                'user_class' => $user['user_class'] ?? '5', // 주문조회 권한용
                'login_type' => 'daumdata',
                'is_logged_in' => true,
                'company_logo_path' => !empty($user['logo_path']) ? $user['logo_path'] : null, // 고객사 로고 경로
                'credit' => $memberCreditValue ?? $credit, // 거래구분 값 (회원 상세에서 우선, 없으면 거래처에서)
                'api_name' => $apiName, // 콜센터명 (로고 옆 표시용)
                'cc_comp_name' => $ccCompName // 거래처명 (로고 옆 표시용)
            ];

            log_message('info', "Auth::processLogin - Session data: api_name=" . ($apiName ?? 'NULL') . ", cc_comp_name=" . ($ccCompName ?? 'NULL') . ", selected_api_idx=" . ($selectedApiIdx ?? 'NULL'));

            // 로그인 성공은 기록하지 않음 (실패만 기록)

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
                        // log_message('info', "Login denied: user_company({$userCompanyTrimmed}) != subdomain_comp_code({$subdomainCompCodeTrimmed}) for user: {$username}");
                        
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

            // 거래처 2338395인 경우: 전체 콜센터 주문 동기화 (인성주문 페이지용)
            try {
                $userCompanyCode = $user['user_company'] ?? null;
                if ($userCompanyCode == '2338395') {
                    $this->syncInsungAllOrdersViaCLI();
                    log_message('info', "Auth::processLogin - Triggered insung:sync-all-orders for company 2338395");
                }
            } catch (\Exception $e) {
                log_message('error', "Failed to trigger Insung all orders sync on login: " . $e->getMessage());
            }
            
            // 로그인 시 직원 검색 큐 등록 (CLI 명령어로 백그라운드 실행)
            try {
                if (!empty($apiIdx) && !empty($user['comp_code'])) {
                    // 직원 검색 큐 등록 및 CLI 명령어를 백그라운드로 실행
                    $this->syncInsungEmployeesViaCLI($apiIdx, $user['comp_code'], $user['user_id']);
                }
            } catch (\Exception $e) {
                // 직원 검색 큐 등록 실패해도 로그인은 진행
                log_message('error', "Failed to trigger Insung employees search queue on login: " . $e->getMessage());
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

                // 로그인 성공은 기록하지 않음 (실패만 기록)

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
                // STN 로그인 실패 기록
                $loginAttemptModel->recordAttempt($username, $ipAddress, $forwardedIp, false, '아이디/비밀번호 불일치', 'stn');

                // 현재 실패 횟수 확인
                $currentLockStatus = $loginAttemptModel->isLocked($username, $ipAddress, $maxAttempts, $lockoutMinutes);
                $remainingAttempts = $maxAttempts - $currentLockStatus['failure_count'];

                $errorDetail = '입력하신 아이디와 비밀번호를 확인해주세요. 대소문자와 특수문자를 정확히 입력했는지 확인하시기 바랍니다. 비밀번호를 잊으셨다면 시스템 관리자에게 문의하세요.';
                if ($remainingAttempts > 0) {
                    $errorDetail .= " (남은 시도 횟수: {$remainingAttempts}회)";
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.')
                    ->with('error_detail', $errorDetail);
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
    
    /**
     * X-Forwarded-For 헤더에서 외부 IP 추출
     *
     * @return string|null 외부 IP 또는 null
     */
    private function getForwardedIp()
    {
        // X-Forwarded-For 헤더 확인 (프록시를 통해 전달된 원본 IP)
        $forwardedFor = $this->request->getServer('HTTP_X_FORWARDED_FOR');

        if (!empty($forwardedFor)) {
            // 여러 개의 IP가 쉼표로 구분되어 있을 수 있음 (첫 번째가 원본 클라이언트 IP)
            $ips = array_map('trim', explode(',', $forwardedFor));
            $forwardedIp = $ips[0] ?? null;

            // IP 유효성 검사 (IPv4 또는 IPv6)
            if ($forwardedIp && filter_var($forwardedIp, FILTER_VALIDATE_IP)) {
                return $forwardedIp;
            }
        }

        // X-Real-IP 헤더 확인 (일부 프록시에서 사용)
        $realIp = $this->request->getServer('HTTP_X_REAL_IP');
        if (!empty($realIp) && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        return null;
    }

    /**
     * 직원 검색 큐 등록 및 CLI 명령어 실행
     */
    private function syncInsungEmployeesViaCLI($apiIdx, $compCode, $userId)
    {
        try {
            $db = \Config\Database::connect();
            
            // 테이블 존재 여부 확인
            if (!$db->tableExists('tbl_employee_search_queue')) {
                // 테이블이 없으면 조용히 스킵
                return;
            }
            
            // 큐에 등록
            $queueData = [
                'api_idx' => $apiIdx,
                'comp_code' => $compCode,
                'user_id' => $userId,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('tbl_employee_search_queue')->insert($queueData);
            $queueIdx = $db->insertID();
            
            // 프로젝트 루트 경로 찾기
            $projectRoot = ROOTPATH;
            $sparkPath = $projectRoot . 'spark';
            
            // spark 파일이 존재하는지 확인
            if (!file_exists($sparkPath)) {
                log_message('warning', "Spark file not found at: {$sparkPath}");
                // 큐 상태를 failed로 변경
                $db->table('tbl_employee_search_queue')
                    ->where('idx', $queueIdx)
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Spark 파일을 찾을 수 없습니다.',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                return;
            }
            
            // CLI 명령어 구성 (백그라운드 실행)
            $command = sprintf(
                'php %s insung:sync-employees %s > /dev/null 2>&1 &',
                escapeshellarg($sparkPath),
                escapeshellarg($queueIdx)
            );
            
            // 명령어 실행
            exec($command);
            
            // log_message('info', "Insung employees search queue created on login: queue_idx={$queueIdx}, comp_code={$compCode}");

        } catch (\Exception $e) {
            log_message('error', "Exception in syncInsungEmployeesViaCLI: " . $e->getMessage());
        }
    }

    /**
     * 전체 콜센터 주문 동기화 CLI 실행 (인성주문 페이지용)
     * 거래처 코드 2338395 전용
     */
    private function syncInsungAllOrdersViaCLI()
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

            // 오늘 날짜 (YYYYMMDD 형식)
            $today = date('Ymd');

            // CLI 명령어 구성 (백그라운드 실행)
            $command = sprintf(
                'php %s insung:sync-all-orders %s %s > /dev/null 2>&1 &',
                escapeshellarg($sparkPath),
                escapeshellarg($today),
                escapeshellarg($today)
            );

            // 명령어 실행
            exec($command);

            log_message('info', "Insung all orders sync CLI command triggered: {$command}");

        } catch (\Exception $e) {
            log_message('error', "Exception in syncInsungAllOrdersViaCLI: " . $e->getMessage());
        }
    }

    /**
     * reCAPTCHA v2 챌린지 필요 여부 체크 (AJAX 엔드포인트)
     * 5회 이상 로그인 실패 시 v2 챌린지 필요
     */
    public function checkRecaptcha()
    {
        // JSON 요청 파싱
        $json = $this->request->getJSON(true);
        $username = $json['username'] ?? '';

        if (empty($username)) {
            return $this->response->setJSON([
                'needs_v2' => false,
                'fail_count' => 0
            ]);
        }

        $ipAddress = $this->request->getIPAddress();
        $loginAttemptModel = new \App\Models\LoginAttemptModel();
        $systemSettingModel = new \App\Models\SystemSettingModel();

        $loginSettings = $systemSettingModel->getLoginSettings();
        $maxAttempts = (int)($loginSettings['login_max_attempts'] ?? 5);
        $lockoutMinutes = (int)($loginSettings['login_lockout_minutes'] ?? 5);

        // 현재 실패 횟수 조회
        $lockStatus = $loginAttemptModel->isLocked($username, $ipAddress, $maxAttempts, $lockoutMinutes);
        $failCount = $lockStatus['failure_count'] ?? 0;

        // 5회 이상 실패 시 v2 챌린지 필요
        $needsV2 = ($failCount >= 5);

        return $this->response->setJSON([
            'needs_v2' => $needsV2,
            'fail_count' => $failCount
        ]);
    }

    /**
     * reCAPTCHA v3 + v2 하이브리드 검증
     *
     * @return true|string 성공 시 true, 실패 시 에러 메시지
     */
    private function verifyRecaptcha()
    {
        $v3SecretKey = getenv('RECAPTCHA_V3_SECRET_KEY');
        $v2SecretKey = getenv('RECAPTCHA_V2_SECRET_KEY');
        $threshold = (float)(getenv('RECAPTCHA_V3_THRESHOLD') ?: 0.5);

        // reCAPTCHA 설정이 없으면 검증 건너뛰기
        if (empty($v3SecretKey) && empty($v2SecretKey)) {
            log_message('debug', 'Auth::verifyRecaptcha - reCAPTCHA not configured, skipping verification');
            return true;
        }

        $recaptchaV3Token = $this->request->getPost('recaptcha_token');
        $recaptchaV2Token = $this->request->getPost('recaptcha_v2_token');

        // v2 토큰이 있으면 v2 검증 (5회 이상 실패 시)
        if (!empty($recaptchaV2Token) && !empty($v2SecretKey)) {
            return $this->verifyRecaptchaV2($recaptchaV2Token, $v2SecretKey);
        }

        // v3 토큰이 없으면 검증 건너뛰기 (클라이언트 측 reCAPTCHA 오류 시 graceful degradation)
        if (empty($recaptchaV3Token)) {
            log_message('warning', 'Auth::verifyRecaptcha - reCAPTCHA token is empty, skipping verification');
            return true;
        }

        // v3 Secret Key가 없으면 검증 건너뛰기
        if (empty($v3SecretKey)) {
            log_message('debug', 'Auth::verifyRecaptcha - v3 secret key not configured, skipping verification');
            return true;
        }

        try {
            // Google reCAPTCHA API 호출
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $postData = [
                'secret' => $v3SecretKey,
                'response' => $recaptchaV3Token,
                'remoteip' => $this->request->getIPAddress()
            ];

            // cURL 요청
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $verifyUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                log_message('error', "Auth::verifyRecaptcha - cURL error: {$curlError}");
                // cURL 오류 시 로그인 허용 (reCAPTCHA 서버 문제일 수 있음)
                return true;
            }

            if ($httpCode !== 200) {
                log_message('error', "Auth::verifyRecaptcha - HTTP error: {$httpCode}");
                // HTTP 오류 시 로그인 허용
                return true;
            }

            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', "Auth::verifyRecaptcha - JSON decode error: " . json_last_error_msg());
                // JSON 파싱 오류 시 로그인 허용
                return true;
            }

            // 검증 결과 확인
            if (!isset($result['success']) || $result['success'] !== true) {
                $errorCodes = $result['error-codes'] ?? [];
                log_message('warning', "Auth::verifyRecaptcha - Verification failed: " . json_encode($errorCodes));
                return '보안 검증에 실패했습니다. 다시 시도해주세요.';
            }

            // 액션 확인 (login 액션인지)
            if (isset($result['action']) && $result['action'] !== 'login') {
                log_message('warning', "Auth::verifyRecaptcha - Invalid action: {$result['action']}");
                return '잘못된 보안 검증 요청입니다.';
            }

            // 점수 확인 (봇 여부 판단)
            $score = $result['score'] ?? 0;
            if ($score < $threshold) {
                log_message('warning', "Auth::verifyRecaptcha - Low score: {$score} (threshold: {$threshold})");
                return '보안 검증 점수가 낮습니다. 잠시 후 다시 시도해주세요.';
            }

            log_message('info', "Auth::verifyRecaptcha - v3 verification successful: score={$score}");
            return true;

        } catch (\Exception $e) {
            log_message('error', "Auth::verifyRecaptcha - Exception: " . $e->getMessage());
            // 예외 발생 시 로그인 허용 (서비스 장애 방지)
            return true;
        }
    }

    /**
     * reCAPTCHA v2 검증
     *
     * @param string $token v2 토큰
     * @param string $secretKey v2 시크릿 키
     * @return true|string 성공 시 true, 실패 시 에러 메시지
     */
    private function verifyRecaptchaV2($token, $secretKey)
    {
        try {
            // Google reCAPTCHA API 호출
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $postData = [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $this->request->getIPAddress()
            ];

            // cURL 요청
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $verifyUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                log_message('error', "Auth::verifyRecaptchaV2 - cURL error: {$curlError}");
                return true; // graceful degradation
            }

            if ($httpCode !== 200) {
                log_message('error', "Auth::verifyRecaptchaV2 - HTTP error: {$httpCode}");
                return true;
            }

            $result = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', "Auth::verifyRecaptchaV2 - JSON decode error: " . json_last_error_msg());
                return true;
            }

            // 검증 결과 확인
            if (!isset($result['success']) || $result['success'] !== true) {
                $errorCodes = $result['error-codes'] ?? [];
                log_message('warning', "Auth::verifyRecaptchaV2 - Verification failed: " . json_encode($errorCodes));
                return '체크박스 인증에 실패했습니다. 다시 시도해주세요.';
            }

            log_message('info', "Auth::verifyRecaptchaV2 - Verification successful");
            return true;

        } catch (\Exception $e) {
            log_message('error', "Auth::verifyRecaptchaV2 - Exception: " . $e->getMessage());
            return true;
        }
    }

}
