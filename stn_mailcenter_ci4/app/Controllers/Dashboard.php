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
        $db = \Config\Database::connect();
        
        // 고객사 선택 (슈퍼관리자용)
        $selectedCustomerId = $this->request->getGet('customer_id') ?: $customerId;
        
        // 고객사 목록 조회 (슈퍼관리자용)
        $customers = [];
        if ($userRole === 'super_admin') {
            $customers = $db->table('tbl_customer_hierarchy')
                          ->where('is_active', TRUE)
                          ->orderBy('hierarchy_level', 'ASC')
                          ->orderBy('customer_name', 'ASC')
                          ->get()
                          ->getResultArray();
        }
        
        // 통계 데이터 조회
        $stats = $this->getOrderStats($selectedCustomerId, $userRole);
        
        // 최근 주문 조회
        $recent_orders = $this->getRecentOrders($selectedCustomerId, $userRole);
        
        // 선택된 고객사 정보
        $selectedCustomer = null;
        if ($selectedCustomerId) {
            $selectedCustomer = $db->table('tbl_customer_hierarchy')
                                 ->where('id', $selectedCustomerId)
                                 ->get()
                                 ->getRowArray();
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
     * 주문 통계 조회
     */
    private function getOrderStats($customerId, $userRole)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_orders o');
        
        // 권한에 따른 필터링
        if ($userRole !== 'super_admin') {
            $builder->where('o.customer_id', $customerId);
        } elseif ($customerId) {
            $builder->where('o.customer_id', $customerId);
        }
        
        // 전체 주문 수
        $totalOrders = $builder->countAllResults(false);
        
        // 상태별 주문 수
        $statusStats = $db->table('tbl_orders o')
                         ->select('status, COUNT(*) as count')
                         ->groupBy('status');
        
        if ($userRole !== 'super_admin') {
            $statusStats->where('o.customer_id', $customerId);
        } elseif ($customerId) {
            $statusStats->where('o.customer_id', $customerId);
        }
        
        $statusResults = $statusStats->get()->getResultArray();
        
        $stats = [
            'total_orders' => $totalOrders,
            'pending_orders' => 0,
            'processing_orders' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'today_orders' => 0
        ];
        
        foreach ($statusResults as $status) {
            switch ($status['status']) {
                case 'pending':
                    $stats['pending_orders'] = $status['count'];
                    break;
                case 'processing':
                    $stats['processing_orders'] = $status['count'];
                    break;
                case 'completed':
                    $stats['completed_orders'] = $status['count'];
                    break;
                case 'cancelled':
                    $stats['cancelled_orders'] = $status['count'];
                    break;
            }
        }
        
        // 오늘 주문 수
        $todayBuilder = $db->table('tbl_orders o')
                          ->where('DATE(o.created_at)', date('Y-m-d'));
        
        if ($userRole !== 'super_admin') {
            $todayBuilder->where('o.customer_id', $customerId);
        } elseif ($customerId) {
            $todayBuilder->where('o.customer_id', $customerId);
        }
        
        $stats['today_orders'] = $todayBuilder->countAllResults();
        
        return $stats;
    }
    
    /**
     * 최근 주문 조회
     */
    private function getRecentOrders($customerId, $userRole)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_orders o');
        
        $builder->select('
            o.id,
            o.order_number,
            o.status,
            o.created_at,
            o.total_amount,
            st.service_name as service,
            ch.customer_name as customer,
            u.real_name as user_name,
            DATE_FORMAT(o.created_at, "%Y-%m-%d %H:%i") as date
        ');
        
        $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
        $builder->join('tbl_users u', 'o.user_id = u.id', 'left');
        
        // 권한에 따른 필터링
        if ($userRole !== 'super_admin') {
            $builder->where('o.customer_id', $customerId);
        } elseif ($customerId) {
            $builder->where('o.customer_id', $customerId);
        }
        
        $builder->orderBy('o.created_at', 'DESC');
        $builder->limit(10);
        
        return $builder->get()->getResultArray();
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
        $db = \Config\Database::connect();
        
        // 고객사 선택 (슈퍼관리자용)
        $selectedCustomerId = $this->request->getGet('customer_id') ?: $customerId;
        
        // 고객사 목록 조회 (슈퍼관리자용)
        $customers = [];
        if ($userRole === 'super_admin') {
            $customers = $db->table('tbl_customer_hierarchy')
                          ->where('is_active', TRUE)
                          ->orderBy('hierarchy_level', 'ASC')
                          ->orderBy('customer_name', 'ASC')
                          ->get()
                          ->getResultArray();
        }
        
        // 주문 목록 조회
        $builder = $db->table('tbl_orders o');
        
        $builder->select('
            o.id,
            o.order_number,
            o.status,
            o.created_at,
            o.total_amount,
            o.payment_type,
            st.service_name,
            st.service_category,
            ch.customer_name,
            u.real_name as user_name
        ');
        
        $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
        $builder->join('tbl_users u', 'o.user_id = u.id', 'left');
        
        // 권한에 따른 필터링
        if ($userRole !== 'super_admin') {
            $builder->where('o.customer_id', $customerId);
        } elseif ($selectedCustomerId) {
            $builder->where('o.customer_id', $selectedCustomerId);
        }
        
        $builder->orderBy('o.created_at', 'DESC');
        
        $orders = $builder->get()->getResultArray();
        
        // 선택된 고객사 정보
        $selectedCustomer = null;
        if ($selectedCustomerId) {
            $selectedCustomer = $db->table('tbl_customer_hierarchy')
                                 ->where('id', $selectedCustomerId)
                                 ->get()
                                 ->getRowArray();
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
