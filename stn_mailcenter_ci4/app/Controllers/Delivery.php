<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Delivery extends BaseController
{
    public function __construct()
    {
        $this->db = \Config\Database::connect();
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
        $offset = ($page - 1) * $perPage;
        
        // 주문 데이터 조회
        $builder = $this->db->table('tbl_orders o');
        
        $builder->select('
            o.id,
            o.order_number,
            o.status,
            o.created_at,
            o.updated_at,
            o.total_amount,
            st.service_name,
            st.service_category,
            ch.customer_name,
            u.real_name as user_name,
            oq.delivery_method,
            oq.urgency_level,
            COALESCE(oq.departure_address, o.departure_address) as departure_address,
            COALESCE(oq.destination_address, o.destination_address) as destination_address,
            oq.delivery_instructions,
            oq.delivery_route,
            oq.box_selection,
            oq.box_quantity,
            oq.pouch_selection,
            oq.pouch_quantity,
            oq.shopping_bag_selection
        ');
        
        $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
        $builder->join('tbl_users u', 'o.user_id = u.id', 'left');
        $builder->join('tbl_orders_quick oq', 'o.id = oq.order_id', 'left');
        
        // 권한에 따른 필터링
        if ($userRole !== 'super_admin') {
            $builder->where('o.customer_id', $customerId);
        }
        
        // 검색 조건 적용
        if (!empty($searchKeyword)) {
            switch ($searchType) {
                case 'order_number':
                    $builder->like('o.order_number', $searchKeyword);
                    break;
                case 'service_name':
                    $builder->like('st.service_name', $searchKeyword);
                    break;
                case 'customer_name':
                    $builder->like('ch.customer_name', $searchKeyword);
                    break;
                case 'departure_address':
                    $builder->groupStart()
                           ->like('oq.departure_address', $searchKeyword)
                           ->orLike('o.departure_address', $searchKeyword)
                           ->groupEnd();
                    break;
                case 'destination_address':
                    $builder->groupStart()
                           ->like('oq.destination_address', $searchKeyword)
                           ->orLike('o.destination_address', $searchKeyword)
                           ->groupEnd();
                    break;
                case 'all':
                default:
                    $builder->groupStart()
                           ->like('o.order_number', $searchKeyword)
                           ->orLike('st.service_name', $searchKeyword)
                           ->orLike('ch.customer_name', $searchKeyword)
                           ->orLike('oq.departure_address', $searchKeyword)
                           ->orLike('o.departure_address', $searchKeyword)
                           ->orLike('oq.destination_address', $searchKeyword)
                           ->orLike('o.destination_address', $searchKeyword)
                           ->groupEnd();
                    break;
            }
        }
        
        // 상태 필터
        if ($statusFilter !== 'all') {
            $builder->where('o.status', $statusFilter);
        }
        
        // 서비스 필터
        if ($serviceFilter !== 'all') {
            $builder->where('st.service_category', $serviceFilter);
        }
        
        // 총 개수 조회 (페이징용) - 별도 쿼리로 실행
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults();
        
        // 정렬 및 페이징
        $builder->orderBy('o.created_at', 'DESC');
        $builder->limit($perPage, $offset);
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'Delivery list query failed: ' . $this->db->getLastQuery());
            $orders = [];
        } else {
            $orders = $query->getResultArray();
            log_message('debug', 'Delivery list query success. Found ' . count($orders) . ' orders');
            if (!empty($orders)) {
                log_message('debug', 'First order: ' . json_encode($orders[0]));
            }
        }
        
        // 서비스 타입별 통계
        $serviceStats = $this->db->table('tbl_orders o')
                               ->select('st.service_category, COUNT(*) as count')
                               ->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        
        if ($userRole !== 'super_admin') {
            $serviceStats->where('o.customer_id', $customerId);
        }
        
        $serviceStats->groupBy('st.service_category')
                    ->orderBy('count', 'DESC');
        
        $serviceTypes = $serviceStats->get()->getResultArray();
        
        // 페이징 정보 계산
        $totalPages = ceil($totalCount / $perPage);
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ];
        
        // 주문 목록에 인코딩된 주문번호 추가 (간단한 Base64 인코딩)
        foreach ($orders as &$order) {
            try {
                // 간단한 Base64 인코딩 (실제 운영환경에서는 더 강력한 암호화 필요)
                $encodedOrderNumber = base64_encode($order['order_number']);
                $order['encrypted_order_number'] = $encodedOrderNumber;
            } catch (\Exception $e) {
                log_message('error', 'Order number encoding failed: ' . $e->getMessage());
                $order['encrypted_order_number'] = '';
            }
        }
        
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
            // 주문 상세 정보 조회
            $builder = $this->db->table('tbl_orders o');
            
            $builder->select('
                o.id,
                o.order_number,
                o.status,
                o.created_at,
                o.updated_at,
                o.total_amount,
                o.payment_type,
                o.notes,
                o.company_name,
                o.contact,
                o.address,
                o.departure_address,
                o.departure_detail,
                o.departure_contact,
                o.waypoint_address,
                o.waypoint_detail,
                o.waypoint_contact,
                o.waypoint_notes,
                o.destination_type,
                o.mailroom,
                o.destination_address,
                o.detail_address,
                o.destination_contact,
                o.item_type,
                o.quantity,
                o.unit,
                o.delivery_content,
                st.service_name,
                st.service_category,
                ch.customer_name,
                u.real_name as user_name,
                oq.delivery_method,
                oq.urgency_level,
                oq.estimated_time,
                oq.pickup_time,
                oq.delivery_time,
                oq.driver_contact,
                oq.vehicle_info,
                oq.delivery_instructions,
                oq.delivery_route,
                oq.box_selection,
                oq.box_quantity,
                oq.pouch_selection,
                oq.pouch_quantity,
                oq.shopping_bag_selection,
                oq.additional_fee
            ');
            
            $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
            $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
            $builder->join('tbl_users u', 'o.user_id = u.id', 'left');
            $builder->join('tbl_orders_quick oq', 'o.id = oq.order_id', 'left');
            
            $builder->where('o.order_number', $orderNumber);
            
            // 권한에 따른 필터링
            if ($userRole !== 'super_admin') {
                $builder->where('o.customer_id', $customerId);
            }
            
            $query = $builder->get();
            $order = $query->getRowArray();
            
            if (!$order) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문 정보를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }
            
            // 상태 라벨 매핑
            $statusLabels = [
                'pending' => '대기중',
                'processing' => '처리중',
                'completed' => '완료',
                'cancelled' => '취소'
            ];
            
            // 결제 방식 라벨 매핑
            $paymentLabels = [
                'cash_on_delivery' => '착불',
                'cash_in_advance' => '선불',
                'bank_transfer' => '계좌이체',
                'credit_transaction' => '신용거래'
            ];
            
            // 긴급도 라벨 매핑
            $urgencyLabels = [
                'normal' => '일반',
                'urgent' => '긴급',
                'super_urgent' => '초긴급'
            ];
            
            // 배송 방법 라벨 매핑
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
