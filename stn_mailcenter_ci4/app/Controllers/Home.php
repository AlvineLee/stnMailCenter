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
        
        // 로그인된 경우 Dashboard로 리다이렉트
        return redirect()->to('/dashboard');
    }
}
