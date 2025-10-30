<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DeliveryModel;

class Delivery extends BaseController
{
    protected $deliveryModel;

    public function __construct()
    {
        $this->deliveryModel = new DeliveryModel();
    }
    
    public function list()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        // 검색 조건 처리
        $searchType = $this->request->getGet('search_type') ?? 'all';
        $searchKeyword = $this->request->getGet('search_keyword') ?? '';
        $statusFilter = $this->request->getGet('status') ?? 'all';
        $serviceFilter = $this->request->getGet('service') ?? 'all';
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = 20;
        
        // 필터 조건 구성
        $filters = [
            'search_type' => $searchType,
            'search_keyword' => $searchKeyword,
            'status' => $statusFilter,
            'service' => $serviceFilter,
            'customer_id' => $userRole !== 'super_admin' ? $customerId : null
        ];
        
        // Model을 통한 데이터 조회
        $result = $this->deliveryModel->getDeliveryList($filters, $page, $perPage);
        $orders = $result['orders'];
        $totalCount = $result['total_count'];
        
        // 서비스 통계 조회
        $serviceTypes = $this->deliveryModel->getServiceStats(
            $userRole !== 'super_admin' ? $customerId : null
        );
        
        // 페이징 정보 계산
        $pagination = $this->deliveryModel->calculatePagination($totalCount, $page, $perPage);
        
        $data = [
            'title' => '배송조회(리스트)',
            'content_header' => [
                'title' => '배송조회(리스트)',
                'description' => '전체 배송 현황을 조회할 수 있습니다.'
            ],
            'orders' => $orders,
            'pagination' => $pagination,
            'service_types' => $serviceTypes,
            'search_type' => $searchType,
            'search_keyword' => $searchKeyword,
            'status_filter' => $statusFilter,
            'service_filter' => $serviceFilter,
            'status_options' => [
                'all' => '전체',
                'pending' => '대기중',
                'processing' => '처리중',
                'completed' => '완료',
                'cancelled' => '취소'
            ],
            'search_type_options' => [
                'all' => '전체',
                'order_number' => '주문번호',
                'service_name' => '서비스명',
                'customer_name' => '고객사명',
                'departure_address' => '출발지',
                'destination_address' => '도착지'
            ],
            'user_role' => $userRole,
            'customer_id' => $customerId
        ];
        
        return view('delivery/list', $data);
    }
    
    /**
     * 주문 상세 정보 조회 (AJAX) - 암호화된 주문번호 사용
     */
    public function getOrderDetail()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $encryptedOrderNumber = $this->request->getGet('order_number');
        if (!$encryptedOrderNumber) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호가 필요합니다.'
            ])->setStatusCode(400);
        }
        
        try {
            // 인코딩된 주문번호 디코딩 (간단한 Base64 디코딩)
            $orderNumber = base64_decode($encryptedOrderNumber);
            if (!$orderNumber) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '유효하지 않은 주문번호입니다.'
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Order number decoding failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호 디코딩에 실패했습니다.'
            ])->setStatusCode(400);
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        try {
            // Model을 통한 주문 상세 정보 조회
            $order = $this->deliveryModel->getOrderDetail(
                $orderNumber, 
                $userRole !== 'super_admin' ? $customerId : null
            );
            
            if (!$order) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문 정보를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }
            
            // 라벨 매핑
            $statusLabels = [
                'pending' => '대기중',
                'processing' => '처리중',
                'completed' => '완료',
                'cancelled' => '취소'
            ];
            
            $paymentLabels = [
                'cash_on_delivery' => '착불',
                'cash_in_advance' => '선불',
                'bank_transfer' => '계좌이체',
                'credit_transaction' => '신용거래'
            ];
            
            $urgencyLabels = [
                'normal' => '일반',
                'urgent' => '긴급',
                'super_urgent' => '초긴급'
            ];
            
            $deliveryMethodLabels = [
                'motorcycle' => '퀵오토바이',
                'vehicle' => '퀵차량',
                'flex' => '퀵플렉스',
                'moving' => '퀵이사'
            ];
            
            // 응답 데이터 구성
            $responseData = [
                'success' => true,
                'data' => [
                    'order_number' => $order['order_number'],
                    'service_name' => $order['service_name'],
                    'service_category' => $order['service_category'],
                    'customer_name' => $order['customer_name'],
                    'user_name' => $order['user_name'],
                    'status' => $order['status'],
                    'status_label' => $statusLabels[$order['status']] ?? $order['status'],
                    'created_at' => $order['created_at'],
                    'updated_at' => $order['updated_at'],
                    'total_amount' => number_format($order['total_amount']) . '원',
                    'payment_type' => $paymentLabels[$order['payment_type']] ?? $order['payment_type'],
                    'notes' => $order['notes'],
                    
                    // 주문자 정보
                    'company_name' => $order['company_name'],
                    'contact' => $order['contact'],
                    'address' => $order['address'],
                    
                    // 출발지 정보
                    'departure_address' => $order['departure_address'],
                    'departure_detail' => $order['departure_detail'],
                    'departure_contact' => $order['departure_contact'],
                    
                    // 경유지 정보
                    'waypoint_address' => $order['waypoint_address'],
                    'waypoint_detail' => $order['waypoint_detail'],
                    'waypoint_contact' => $order['waypoint_contact'],
                    'waypoint_notes' => $order['waypoint_notes'],
                    
                    // 도착지 정보
                    'destination_type' => $order['destination_type'],
                    'mailroom' => $order['mailroom'],
                    'destination_address' => $order['destination_address'],
                    'detail_address' => $order['detail_address'],
                    'destination_contact' => $order['destination_contact'],
                    
                    // 물품 정보
                    'item_type' => $order['item_type'],
                    'quantity' => $order['quantity'],
                    'unit' => $order['unit'],
                    'delivery_content' => $order['delivery_content'],
                    
                    // 퀵 서비스 정보
                    'delivery_method' => $order['delivery_method'],
                    'delivery_method_label' => $deliveryMethodLabels[$order['delivery_method']] ?? $order['delivery_method'],
                    'urgency_level' => $order['urgency_level'],
                    'urgency_label' => $urgencyLabels[$order['urgency_level']] ?? $order['urgency_level'],
                    'estimated_time' => $order['estimated_time'],
                    'pickup_time' => $order['pickup_time'],
                    'delivery_time' => $order['delivery_time'],
                    'driver_contact' => $order['driver_contact'],
                    'vehicle_info' => $order['vehicle_info'],
                    'delivery_instructions' => $order['delivery_instructions'],
                    'delivery_route' => $order['delivery_route'],
                    'box_selection' => $order['box_selection'],
                    'box_quantity' => $order['box_quantity'],
                    'pouch_selection' => $order['pouch_selection'],
                    'pouch_quantity' => $order['pouch_quantity'],
                    'shopping_bag_selection' => $order['shopping_bag_selection'],
                    'additional_fee' => $order['additional_fee'] ? number_format($order['additional_fee']) . '원' : '0원'
                ]
            ];
            
            return $this->response->setJSON($responseData);
            
        } catch (\Exception $e) {
            log_message('error', 'Order detail query failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 정보 조회 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }
}
