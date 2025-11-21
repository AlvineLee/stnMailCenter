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
        
        $data = [
            'title' => 'STN Network - 로그인',
            'error' => session()->getFlashdata('error')
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
            return redirect()->back()
                ->withInput()
                ->with('error', '아이디와 비밀번호를 입력해주세요.');
        }
        
        // 로그인 타입에 따라 분기 처리
        if ($loginType === 'daumdata') {
            // daumdata 로그인 처리
            $user = $this->insungUsersListModel->authenticate($username, $password);
            
            if ($user) {
                // ckey 기반 ukey, akey 생성
                $ckey = $user['ckey'] ?? '';
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
                    'cc_code' => $user['cc_code'] ?? '', // tbl_api_list의 cc_code 우선, 없으면 tbl_cc_list의 cc_code
                    'm_code' => $user['m_code'] ?? '', // 인성 API m_code (tbl_api_list에서)
                    'token' => $user['token'] ?? '', // 인성 API token (tbl_api_list에서)
                    'ckey' => $ckey, // 인성 API ckey (tbl_api_list에서)
                    'ukey' => $ukey, // 임의의 8글자 + ckey
                    'akey' => $akey, // ukey를 MD5로 변환한 값
                    'user_type' => $user['user_type'] ?? '5',
                    'login_type' => 'daumdata',
                    'is_logged_in' => true,
                    'company_logo_path' => !empty($user['logo_path']) ? $user['logo_path'] : null // 고객사 로고 경로
                ];
                
                session()->set($userData);
                
                return redirect()->to('/')->with('success', '로그인되었습니다.');
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.');
            }
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
                
                return redirect()->to('/')->with('success', '로그인되었습니다.');
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.');
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
}
