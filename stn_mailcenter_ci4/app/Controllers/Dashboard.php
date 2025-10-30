<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\DashboardModel;

class Dashboard extends BaseController
{
    protected $orderModel;
    protected $dashboardModel;
    
    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->dashboardModel = new DashboardModel();
        helper('form');
    }
    
    /**
     * 메인 대시보드
     */
    public function index()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        // 고객사 선택 (슈퍼관리자용)
        $selectedCustomerId = $this->request->getGet('customer_id') ?: $customerId;
        
        // 고객사 목록 조회 (슈퍼관리자용)
        $customers = [];
        if ($userRole === 'super_admin') {
            $customers = $this->dashboardModel->getActiveCustomers();
        }
        
        // 통계 데이터 조회
        $stats = $this->dashboardModel->getOrderStats($selectedCustomerId, $userRole);
        
        // 최근 주문 조회
        $recent_orders = $this->dashboardModel->getRecentOrders($selectedCustomerId, $userRole);
        
        // 선택된 고객사 정보
        $selectedCustomer = null;
        if ($selectedCustomerId) {
            $selectedCustomer = $this->dashboardModel->getCustomerById($selectedCustomerId);
        }
        
        $data = [
            'title' => 'STN Network - 대시보드',
            'content_header' => [
                'title' => '대시보드',
                'description' => '전체 현황을 한눈에 확인하세요'
            ],
            'user' => [
                'username' => session()->get('username'),
                'real_name' => session()->get('real_name'),
                'customer_name' => session()->get('customer_name'),
                'user_role' => $userRole
            ],
            'stats' => $stats,
            'recent_orders' => $recent_orders,
            'customers' => $customers,
            'selected_customer_id' => $selectedCustomerId,
            'selected_customer' => $selectedCustomer
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
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        // 고객사 선택 (슈퍼관리자용)
        $selectedCustomerId = $this->request->getGet('customer_id') ?: $customerId;
        
        // 고객사 목록 조회 (슈퍼관리자용)
        $customers = [];
        if ($userRole === 'super_admin') {
            $customers = $this->dashboardModel->getActiveCustomers();
        }
        
        // 주문 목록 조회
        $orders = $this->dashboardModel->getAllOrders($customerId, $userRole, $selectedCustomerId);
        
        // 선택된 고객사 정보
        $selectedCustomer = null;
        if ($selectedCustomerId) {
            $selectedCustomer = $this->dashboardModel->getCustomerById($selectedCustomerId);
        }
        
        $data = [
            'title' => 'STN Network - 주문조회',
            'content_header' => [
                'title' => '주문조회',
                'description' => '접수된 주문을 확인하세요'
            ],
            'orders' => $orders,
            'customers' => $customers,
            'selected_customer_id' => $selectedCustomerId,
            'selected_customer' => $selectedCustomer,
            'user_role' => $userRole
        ];
        
        return view('dashboard/orders', $data);
    }
}
