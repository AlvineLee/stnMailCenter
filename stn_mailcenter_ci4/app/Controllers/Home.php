<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return view('welcome_message');
        }
        
        // 서브도메인 접근 권한 체크 (리다이렉트 루프 방지를 위해 체크만 수행)
        $subdomainCheck = $this->checkSubdomainAccess();
        if ($subdomainCheck !== true) {
            return $subdomainCheck;
        }
        
        // 로그인된 경우 Dashboard로 리다이렉트
        return redirect()->to('/dashboard');
    }
}
