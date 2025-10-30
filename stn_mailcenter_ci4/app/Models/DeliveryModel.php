<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryModel extends Model
{
    protected $table = 'tbl_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 배송 목록 조회 (페이징 포함)
     */
    public function getDeliveryList($filters = [], $page = 1, $perPage = 20)
    {
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
        if (isset($filters['customer_id']) && $filters['customer_id']) {
            $builder->where('o.customer_id', $filters['customer_id']);
        }
        
        // 검색 조건 적용
        if (!empty($filters['search_keyword'])) {
            switch ($filters['search_type']) {
                case 'order_number':
                    $builder->like('o.order_number', $filters['search_keyword']);
                    break;
                case 'service_name':
                    $builder->like('st.service_name', $filters['search_keyword']);
                    break;
                case 'customer_name':
                    $builder->like('ch.customer_name', $filters['search_keyword']);
                    break;
                case 'departure_address':
                    $builder->groupStart()
                           ->like('oq.departure_address', $filters['search_keyword'])
                           ->orLike('o.departure_address', $filters['search_keyword'])
                           ->groupEnd();
                    break;
                case 'destination_address':
                    $builder->groupStart()
                           ->like('oq.destination_address', $filters['search_keyword'])
                           ->orLike('o.destination_address', $filters['search_keyword'])
                           ->groupEnd();
                    break;
                case 'all':
                default:
                    $builder->groupStart()
                           ->like('o.order_number', $filters['search_keyword'])
                           ->orLike('st.service_name', $filters['search_keyword'])
                           ->orLike('ch.customer_name', $filters['search_keyword'])
                           ->orLike('oq.departure_address', $filters['search_keyword'])
                           ->orLike('o.departure_address', $filters['search_keyword'])
                           ->orLike('oq.destination_address', $filters['search_keyword'])
                           ->orLike('o.destination_address', $filters['search_keyword'])
                           ->groupEnd();
                    break;
            }
        }
        
        // 상태 필터
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $builder->where('o.status', $filters['status']);
        }
        
        // 서비스 필터
        if (isset($filters['service']) && $filters['service'] !== 'all') {
            $builder->where('st.service_category', $filters['service']);
        }
        
        // 총 개수 조회 (페이징용)
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults();
        
        // 정렬 및 페이징
        $builder->orderBy('o.created_at', 'DESC');
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'Delivery list query failed: ' . $this->db->getLastQuery());
            return [
                'orders' => [],
                'total_count' => 0
            ];
        }
        
        $orders = $query->getResultArray();
        
        // 주문 목록에 인코딩된 주문번호 추가
        foreach ($orders as &$order) {
            try {
                $encodedOrderNumber = base64_encode($order['order_number']);
                $order['encrypted_order_number'] = $encodedOrderNumber;
            } catch (\Exception $e) {
                log_message('error', 'Order number encoding failed: ' . $e->getMessage());
                $order['encrypted_order_number'] = '';
            }
        }
        
        return [
            'orders' => $orders,
            'total_count' => $totalCount
        ];
    }

    /**
     * 서비스 타입별 통계 조회
     */
    public function getServiceStats($customerId = null)
    {
        $builder = $this->db->table('tbl_orders o');
        
        $builder->select('st.service_category, COUNT(*) as count');
        $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        
        if ($customerId) {
            $builder->where('o.customer_id', $customerId);
        }
        
        $builder->groupBy('st.service_category')
                ->orderBy('count', 'DESC');
        
        $query = $builder->get();
        return $query->getResultArray();
    }

    /**
     * 주문 상세 정보 조회
     */
    public function getOrderDetail($orderNumber, $customerId = null)
    {
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
        
        if ($customerId) {
            $builder->where('o.customer_id', $customerId);
        }
        
        $query = $builder->get();
        return $query->getRowArray();
    }

    /**
     * 페이징 정보 계산
     */
    public function calculatePagination($totalCount, $page, $perPage)
    {
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ];
    }
}
