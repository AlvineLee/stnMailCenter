<?php

namespace App\Controllers;

/**
 * 메일룸 기사 앱 컨트롤러 (PWA 데모)
 */
class MailroomDriver extends BaseController
{
    /**
     * 기사 앱 메인 (접수 내역)
     */
    public function index()
    {
        // 샘플 데이터
        $orders = $this->getSampleOrders();

        return view('mailroom_driver/index', [
            'orders' => $orders,
            'driver_name' => '김기사'
        ]);
    }

    /**
     * 주문 상세
     */
    public function detail($orderId)
    {
        $orders = $this->getSampleOrders();
        $order = null;

        foreach ($orders as $o) {
            if ($o['id'] == $orderId) {
                $order = $o;
                break;
            }
        }

        if (!$order) {
            return redirect()->to('/mailroom-driver');
        }

        return view('mailroom_driver/detail', [
            'order' => $order
        ]);
    }

    /**
     * 샘플 주문 데이터
     */
    private function getSampleOrders(): array
    {
        return [
            [
                'id' => 1,
                'status' => 'pending',
                'status_text' => '접수',
                'from_building' => '그랑서울타워',
                'from_floor' => '15층',
                'from_company' => '(주)테크솔루션',
                'to_building' => '그랑서울타워',
                'to_floor' => '3층',
                'to_company' => '편의점 CU',
                'item' => '서류봉투 1개',
                'memo' => '긴급 계약서',
                'created_at' => '10:30',
                'priority' => 'urgent'
            ],
            [
                'id' => 2,
                'status' => 'pending',
                'status_text' => '접수',
                'from_building' => '그랑서울타워',
                'from_floor' => '22층',
                'from_company' => '법무법인 정의',
                'to_building' => '센터원빌딩',
                'to_floor' => '8층',
                'to_company' => '회계법인 신뢰',
                'item' => '문서 파일 2개',
                'memo' => '',
                'created_at' => '10:45',
                'priority' => 'normal'
            ],
            [
                'id' => 3,
                'status' => 'picked',
                'status_text' => '픽업완료',
                'from_building' => '센터원빌딩',
                'from_floor' => '12층',
                'from_company' => '디자인스튜디오',
                'to_building' => '그랑서울타워',
                'to_floor' => '18층',
                'to_company' => '마케팅에이전시',
                'item' => '샘플 박스 1개',
                'memo' => '깨지기 쉬움 주의',
                'created_at' => '09:15',
                'priority' => 'normal'
            ],
            [
                'id' => 4,
                'status' => 'completed',
                'status_text' => '배송완료',
                'from_building' => '그랑서울타워',
                'from_floor' => '5층',
                'from_company' => '인사팀',
                'to_building' => '그랑서울타워',
                'to_floor' => '20층',
                'to_company' => '대표이사실',
                'item' => '서류봉투 1개',
                'memo' => '',
                'created_at' => '08:30',
                'priority' => 'normal'
            ],
        ];
    }
}