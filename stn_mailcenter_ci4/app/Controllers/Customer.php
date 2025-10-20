<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Customer extends BaseController
{
    public function head()
    {
        $data = [
            'title' => '본점관리',
            'content_header' => [
                'title' => '본점관리',
                'description' => '본점 정보를 관리할 수 있습니다.'
            ]
        ];

        return view('customer/head', $data);
    }

    public function branch()
    {
        $data = [
            'title' => '지사관리',
            'content_header' => [
                'title' => '지사관리',
                'description' => '지사 정보를 관리할 수 있습니다.'
            ]
        ];

        return view('customer/branch', $data);
    }

    public function agency()
    {
        $data = [
            'title' => '대리점관리',
            'content_header' => [
                'title' => '대리점관리',
                'description' => '대리점 정보를 관리할 수 있습니다.'
            ]
        ];

        return view('customer/agency', $data);
    }

    public function budget()
    {
        $data = [
            'title' => '예산관리',
            'content_header' => [
                'title' => '예산관리',
                'description' => '예산 정보를 관리할 수 있습니다.'
            ]
        ];

        return view('customer/budget', $data);
    }

    public function items()
    {
        $data = [
            'title' => '항목관리',
            'content_header' => [
                'title' => '항목관리',
                'description' => '요금 및 청구 항목을 관리할 수 있습니다.'
            ],
            'fee_items' => [
                '악천후', '심야', '주말', '명절', '고객할증', '경유', '상하차',
                '야간', '조조', '동승', '초급송', '과적', '엘리베이터없음'
            ],
            'billing_items' => [
                'No', '일자', '회사명', '부서명', '사용자', '연락처',
                '출발회사명', '출발동', '도착회사명', '도착동', '행정지역',
                '오더방법', '기본요금', '추가요금', '대납', '수화물대',
                '요금합계', '할인요금', '정산금액', '도착연락처',
                '도착지상세주소', '배차시간', '픽업시간', '완료시간',
                '배송사유', '적요', '거리(Km)', '출발지상세', '운송수단',
                '적용구간', '배송기사', '사번', '부서코드', 'ID'
            ]
        ];

        return view('customer/items', $data);
    }
}
