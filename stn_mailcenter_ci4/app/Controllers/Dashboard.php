<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class Dashboard extends BaseController
{
    protected $orderModel;
    
    public function __construct()
    {
        $this->orderModel = new OrderModel();
        helper('form');
    }
    
    /**
     * 메인 대시보드 (주문접수 페이지)
     */
    public function index()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $data = [
            'title' => 'STN Network - 주문접수',
            'content_header' => [
                'title' => '주문접수',
                'description' => '새로운 주문을 접수해주세요'
            ],
            'user' => [
                'username' => session()->get('username'),
                'company_name' => session()->get('company_name')
            ]
        ];
        
        return view('dashboard/index', $data);
    }
    
    /**
     * 주문 접수 처리
     */
    public function submitOrder()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 폼 데이터 검증
        $validation = \Config\Services::validation();
        $validation->setRules([
            'companyName' => 'required',
            'contact' => 'required',
            'departureAddress' => 'required',
            'destinationAddress' => 'required',
            'itemType' => 'required',
            'deliveryContent' => 'required'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        // 주문 데이터 준비
        $orderData = [
            'user_id' => session()->get('user_id'),
            'company_name' => $this->request->getPost('companyName'),
            'contact' => $this->request->getPost('contact'),
            'address' => $this->request->getPost('address'),
            'departure_address' => $this->request->getPost('departureAddress'),
            'departure_detail' => $this->request->getPost('departureDetail'),
            'departure_contact' => $this->request->getPost('departureContact'),
            'destination_type' => $this->request->getPost('destinationType'),
            'mailroom' => $this->request->getPost('mailroom'),
            'destination_address' => $this->request->getPost('destinationAddress'),
            'detail_address' => $this->request->getPost('detailAddress'),
            'destination_contact' => $this->request->getPost('destinationContact'),
            'item_type' => $this->request->getPost('itemType'),
            'quantity' => $this->request->getPost('quantity'),
            'unit' => $this->request->getPost('unit'),
            'delivery_content' => $this->request->getPost('deliveryContent'),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 주문 저장 (임시로 세션에 저장)
        $orders = session()->get('orders') ?? [];
        $orderData['id'] = count($orders) + 1;
        $orders[] = $orderData;
        session()->set('orders', $orders);
        
        return redirect()->to('/')->with('success', '주문이 접수되었습니다.');
    }
    
    /**
     * 주문 목록 조회
     */
    public function orders()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $orders = session()->get('orders') ?? [];
        
        $data = [
            'title' => 'STN Network - 주문조회',
            'content_header' => [
                'title' => '주문조회',
                'description' => '접수된 주문을 확인하세요'
            ],
            'orders' => $orders
        ];
        
        return view('dashboard/orders', $data);
    }
}
