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
        
        // tbl_orders의 모든 필드 선택
        $builder->select('
            o.*,
            st.service_name,
            st.service_code,
            st.service_category,
            ch.customer_name,
            u.real_name as user_name,
            oq.delivery_method,
            oq.urgency_level,
            oq.delivery_instructions,
            oq.delivery_route,
            oq.box_selection,
            oq.box_quantity,
            oq.pouch_selection,
            oq.pouch_quantity,
            oq.shopping_bag_selection,
            COALESCE(oq.departure_address, o.departure_address) as departure_address,
            COALESCE(oq.destination_address, o.destination_address) as destination_address
        ');
        
        $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
        $builder->join('tbl_users u', 'o.user_id = u.id', 'left');
        $builder->join('tbl_orders_quick oq', 'o.id = oq.order_id', 'left');
        
        // daumdata 로그인 필터링 (cc_code 또는 comp_name)
        if (isset($filters['cc_code']) && $filters['cc_code']) {
            // user_type = 3: 소속 콜센터의 고객사 주문접수 리스트만
            // tbl_orders의 company_name과 tbl_company_list의 comp_name을 매칭
            // tbl_company_list를 통해 cc_code로 필터링
            $builder->join('tbl_company_list cl', 'o.company_name = cl.comp_name', 'inner');
            $builder->join('tbl_cc_list ccl', 'cl.cc_idx = ccl.idx', 'inner');
            $builder->where('ccl.cc_code', $filters['cc_code']);
        } elseif (isset($filters['comp_name']) && $filters['comp_name']) {
            // user_type = 5: 본인 고객사의 주문접수 리스트만
            $builder->where('o.company_name', $filters['comp_name']);
        } elseif (isset($filters['customer_id']) && $filters['customer_id']) {
            // STN 로그인: 기존 로직
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
            o.*,
            st.service_name,
            st.service_code,
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

    /**
     * 인성 주문번호가 있는 주문들 조회 (동기화용)
     * 취소/완료된 주문은 제외
     * 주문별 API 정보를 JOIN하여 함께 조회 (세션 의존 제거)
     * 
     * @param array $filters 필터 조건 (daumdata 로그인 필터링 포함)
     * @return array 주문 목록 (API 정보 포함)
     */
    public function getInsungOrdersForSync($filters = [])
    {
        $builder = $this->db->table('tbl_orders o');
        
        // 주문 정보와 API 정보를 함께 조회
        $builder->select('
            o.id,
            o.order_number,
            o.insung_order_number,
            o.status,
            o.total_amount,
            o.created_at,
            o.updated_at,
            api.mcode as m_code,
            api.cccode as cc_code,
            api.token,
            api.idx as api_idx,
            u.user_id as insung_user_id
        ');
        
        // 인성 주문번호가 있는 주문만 조회 (order_system과 무관하게)
        // STN 로그인 주문도 인성 API 연동 시 insung_order_number가 있으므로 포함
        $builder->where('o.insung_order_number IS NOT NULL');
        $builder->where('o.insung_order_number !=', '');
        
        // 완료된 주문만 제외 (취소된 주문은 포함하여 인성 CS에서 취소 처리된 경우 상태 업데이트)
        // 취소 상태는 인성 API에서 확인하여 업데이트해야 하므로 동기화 대상에 포함
        $builder->where('o.status !=', 'delivered');
        
        // API 정보를 위한 JOIN (LEFT JOIN으로 변경하여 STN 로그인 주문도 포함)
        // tbl_orders.user_id -> tbl_users_list.idx
        $builder->join('tbl_users_list u', 'o.user_id = u.idx', 'left');
        
        // tbl_users_list.user_company -> tbl_company_list.comp_code
        $builder->join('tbl_company_list c', 'u.user_company = c.comp_code', 'left');
        
        // tbl_company_list.cc_idx -> tbl_cc_list.idx
        $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'left');
        
        // tbl_cc_list.cc_code -> tbl_api_list.api_code (collation 충돌 해결)
        $builder->join('tbl_api_list api', 'CONVERT(cc.cc_code USING utf8mb4) COLLATE utf8mb4_general_ci = CONVERT(api.api_code USING utf8mb4) COLLATE utf8mb4_general_ci', 'left', false);
        
        // API 정보가 있는 주문만 조회 (NULL 체크는 제거하여 STN 로그인 주문도 포함)
        // STN 로그인 주문의 경우 API 정보가 NULL이어도 동기화 가능 (기본값 사용)
        
        // daumdata 로그인 필터링 (선택적)
        if (isset($filters['cc_code']) && $filters['cc_code']) {
            $builder->where('cc.cc_code', $filters['cc_code']);
        } elseif (isset($filters['comp_name']) && $filters['comp_name']) {
            $builder->where('c.comp_name', $filters['comp_name']);
        } elseif (isset($filters['customer_id']) && $filters['customer_id']) {
            $builder->where('o.customer_id', $filters['customer_id']);
        }
        
        // 최근 업데이트된 주문부터 (최대 50개)
        $builder->orderBy('o.updated_at', 'DESC');
        $builder->limit(50);
        
        $query = $builder->get();
        return $query->getResultArray();
    }
}
