<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Member extends BaseController
{
    public function list()
    {
        $data = [
            'title' => '회원정보(리스트)',
            'content_header' => [
                'title' => '회원정보(리스트)',
                'description' => '전체 회원 정보를 조회하고 관리할 수 있습니다.'
            ]
        ];

        return view('member/list', $data);
    }
}
