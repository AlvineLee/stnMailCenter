<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Auth extends BaseController
{
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
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
        
        // 입력값 검증
        if (empty($username) || empty($password)) {
            return redirect()->back()
                ->withInput()
                ->with('error', '아이디와 비밀번호를 입력해주세요.');
        }
        
        // 디버깅: 사용자 조회 테스트
        $db = \Config\Database::connect();
        $userFromDb = $db->table('tbl_users')
                        ->where('username', $username)
                        ->where('status', 'active')
                        ->get()
                        ->getRowArray();
        
        log_message('debug', 'User lookup: username=' . $username . ', found=' . ($userFromDb ? 'yes' : 'no'));
        if ($userFromDb) {
            log_message('debug', 'User data: ' . json_encode($userFromDb));
            log_message('debug', 'Password verify: ' . (password_verify($password, $userFromDb['password']) ? 'success' : 'failed'));
        }
        
        // 데이터베이스에서 사용자 인증
        $user = $this->userModel->authenticate($username, $password);
        
        // 디버깅용 로그 (임시)
        log_message('debug', 'Login attempt: username=' . $username . ', user_found=' . ($user ? 'yes' : 'no'));
        
        if ($user) {
            // 고객사 정보 조회
            $db = \Config\Database::connect();
            $customerInfo = $db->table('tbl_customer_hierarchy')
                              ->where('id', $user['customer_id'])
                              ->get()
                              ->getRowArray();
            
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
                'is_logged_in' => true
            ];
            
            session()->set($userData);
            
            // 마지막 로그인 시간 업데이트
            $this->userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
            
            return redirect()->to('/')->with('success', '로그인되었습니다.');
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.');
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
