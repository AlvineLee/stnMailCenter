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
        
        // 사용자 인증 (임시 - 실제로는 데이터베이스에서 확인)
        if ($username === 'admin' && $password === 'admin') {
            // 세션에 사용자 정보 저장
            $userData = [
                'user_id' => 1,
                'username' => '은하고객',
                'company_name' => '은하코퍼레이션',
                'is_logged_in' => true
            ];
            
            session()->set($userData);
            
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
