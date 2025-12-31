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
        // 인성 API 주문의 경우 customer_name을 tbl_company_list에서 가져오기
        $builder->select('
            o.*,
            st.service_name,
            st.service_code,
            st.service_category,
            COALESCE(ch.customer_name, cl_ch.comp_name) as customer_name,
            COALESCE(u.real_name, u_list.user_name) as user_name,
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
        // customer_id JOIN: 인성 API 주문은 comp_code를 통해, 일반 주문은 customer_id를 통해
        $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
        // 인성 API 주문과 일반 주문 모두를 위한 JOIN (필터링에서도 사용)
        $builder->join('tbl_users_list u_list', 'o.user_id = u_list.idx', 'left');
        $builder->join('tbl_company_list cl_ch', 'u_list.user_company = cl_ch.comp_code', 'left');
        // 일반 주문의 경우 tbl_users를 통해 user_name 가져오기
        $builder->join('tbl_users u', 'o.user_id = u.id', 'left');
        $builder->join('tbl_orders_quick oq', 'o.id = oq.order_id', 'left');
        
        // 서브도메인 기반 필터링 (최우선)
        $hasSubdomainFilter = isset($filters['subdomain_comp_code']) && $filters['subdomain_comp_code'];
        
        if ($hasSubdomainFilter) {
            // 서브도메인으로 접근한 경우 해당 고객사만 조회
            $subdomainCompCode = $filters['subdomain_comp_code'];
            
            // comp_code로 comp_name 조회
            $compNameBuilder = $this->db->table('tbl_company_list');
            $compNameBuilder->select('comp_name');
            $compNameBuilder->where('comp_code', $subdomainCompCode);
            $compNameQuery = $compNameBuilder->get();
            $subdomainCompName = null;
            if ($compNameQuery !== false) {
                $compNameResult = $compNameQuery->getRowArray();
                if ($compNameResult && !empty($compNameResult['comp_name'])) {
                    $subdomainCompName = $compNameResult['comp_name'];
                }
            }
            
            // 서브도메인 필터 적용 (항상 적용)
            if ($subdomainCompName) {
                $builder->groupStart()
                    ->groupStart()
                        ->where('o.order_system', 'insung')
                        ->where('u_list.user_company', $subdomainCompCode)
                    ->groupEnd()
                    ->orGroupStart()
                        ->groupStart()
                            ->where('o.order_system IS NULL', null, true)
                            ->orWhere('o.order_system !=', 'insung')
                        ->groupEnd()
                        ->where('o.company_name', $subdomainCompName)
                    ->groupEnd()
                ->groupEnd();
            } else {
                // comp_name 조회 실패 시 comp_code로만 필터링
                $builder->groupStart()
                    ->groupStart()
                        ->where('o.order_system', 'insung')
                        ->where('u_list.user_company', $subdomainCompCode)
                    ->groupEnd()
                ->groupEnd();
            }
            
            // 서브도메인 필터가 있을 때 user_type별 추가 필터링
            if (isset($filters['user_type'])) {
                if ($filters['user_type'] == '3' && isset($filters['user_company']) && $filters['user_company']) {
                    // user_type = 3: user_company가 본인의 user_company와 같은 데이터들
                    // 인성 API 주문: u_list.user_company로 필터링
                    // 일반 주문: o.company_name으로 필터링 (comp_code로 comp_name 조회 필요)
                    $compNameBuilder = $this->db->table('tbl_company_list');
                    $compNameBuilder->select('comp_name');
                    $compNameBuilder->where('comp_code', $filters['user_company']);
                    $compNameQuery = $compNameBuilder->get();
                    $userCompName = null;
                    if ($compNameQuery !== false) {
                        $compNameResult = $compNameQuery->getRowArray();
                        if ($compNameResult && !empty($compNameResult['comp_name'])) {
                            $userCompName = $compNameResult['comp_name'];
                        }
                    }
                    
                    if ($userCompName) {
                        $builder->groupStart()
                            ->groupStart()
                                ->where('o.order_system', 'insung')
                                ->where('u_list.user_company', $filters['user_company'])
                            ->groupEnd()
                            ->orGroupStart()
                                ->groupStart()
                                    ->where('o.order_system IS NULL', null, true)
                                    ->orWhere('o.order_system !=', 'insung')
                                ->groupEnd()
                                ->where('o.company_name', $userCompName)
                            ->groupEnd()
                        ->groupEnd();
                    } else {
                        // comp_name 조회 실패 시 comp_code로만 필터링
                        $builder->where('u_list.user_company', $filters['user_company']);
                    }
                } elseif ($filters['user_type'] == '5') {
                    // user_type = 5: 같은 회사(comp_code)의 모든 주문 조회
                    // 서브도메인 필터가 이미 적용되어 있으므로 추가 필터링 불필요
                    // (서브도메인 필터가 comp_code로 필터링하므로)
                }
                // user_type = 1: 서브도메인 필터만 적용 (추가 필터 없음)
            }
        }
        // 서브도메인 필터가 없을 때 daumdata 로그인 필터링
        elseif (isset($filters['user_type']) && $filters['user_type'] == '3' && isset($filters['user_company']) && $filters['user_company']) {
            // user_type = 3: user_company가 본인의 user_company와 같은 데이터들
            $compNameBuilder = $this->db->table('tbl_company_list');
            $compNameBuilder->select('comp_name');
            $compNameBuilder->where('comp_code', $filters['user_company']);
            $compNameQuery = $compNameBuilder->get();
            $userCompName = null;
            if ($compNameQuery !== false) {
                $compNameResult = $compNameQuery->getRowArray();
                if ($compNameResult && !empty($compNameResult['comp_name'])) {
                    $userCompName = $compNameResult['comp_name'];
                }
            }
            
            if ($userCompName) {
                $builder->groupStart()
                    ->groupStart()
                        ->where('o.order_system', 'insung')
                        ->where('u_list.user_company', $filters['user_company'])
                    ->groupEnd()
                    ->orGroupStart()
                        ->groupStart()
                            ->where('o.order_system IS NULL', null, true)
                            ->orWhere('o.order_system !=', 'insung')
                        ->groupEnd()
                        ->where('o.company_name', $userCompName)
                    ->groupEnd()
                ->groupEnd();
            } else {
                // comp_name 조회 실패 시 comp_code로만 필터링
                $builder->where('u_list.user_company', $filters['user_company']);
            }
        } elseif (isset($filters['user_type']) && $filters['user_type'] == '5') {
            // user_type = 5: 같은 회사(comp_code)의 모든 주문 조회
            // user_company (comp_code)로 필터링
            if (isset($filters['user_company']) && $filters['user_company']) {
                // comp_code로 comp_name 조회
                $compNameBuilder = $this->db->table('tbl_company_list');
                $compNameBuilder->select('comp_name');
                $compNameBuilder->where('comp_code', $filters['user_company']);
                $compNameQuery = $compNameBuilder->get();
                $userCompName = null;
                if ($compNameQuery !== false) {
                    $compNameResult = $compNameQuery->getRowArray();
                    if ($compNameResult && !empty($compNameResult['comp_name'])) {
                        $userCompName = $compNameResult['comp_name'];
                    }
                }
                
                if ($userCompName) {
                    // 인성 API 주문과 일반 주문 모두 comp_code/comp_name으로 필터링
                    $builder->groupStart()
                        ->groupStart()
                            ->where('o.order_system', 'insung')
                            ->where('u_list.user_company', $filters['user_company'])
                        ->groupEnd()
                        ->orGroupStart()
                            ->groupStart()
                                ->where('o.order_system IS NULL', null, true)
                                ->orWhere('o.order_system !=', 'insung')
                            ->groupEnd()
                            ->where('o.company_name', $userCompName)
                        ->groupEnd()
                    ->groupEnd();
                } else {
                    // comp_name 조회 실패 시 comp_code로만 필터링
                    $builder->where('u_list.user_company', $filters['user_company']);
                }
            }
        } elseif (isset($filters['cc_code']) && $filters['cc_code']) {
            // user_type = 3: 소속 콜센터의 고객사 주문접수 리스트만
            // 인성 API 주문과 일반 주문 모두 포함
            // WHERE 절에서 조건 분리 (JOIN은 이미 위에서 했음)
            $builder->join('tbl_company_list cl_insung', 'u_list.user_company = cl_insung.comp_code', 'left');
            $builder->join('tbl_company_list cl_normal', 'o.company_name = cl_normal.comp_name', 'left');
            $builder->join('tbl_cc_list ccl_insung', 'cl_insung.cc_idx = ccl_insung.idx', 'left');
            $builder->join('tbl_cc_list ccl_normal', 'cl_normal.cc_idx = ccl_normal.idx', 'left');
            
            $builder->groupStart()
                ->groupStart()
                    ->where('o.order_system', 'insung')
                    ->where('ccl_insung.cc_code', $filters['cc_code'])
                ->groupEnd()
                ->orGroupStart()
                    ->groupStart()
                        ->where('o.order_system IS NULL', null, true)
                        ->orWhere('o.order_system !=', 'insung')
                    ->groupEnd()
                    ->where('ccl_normal.cc_code', $filters['cc_code'])
                ->groupEnd()
            ->groupEnd();
        } elseif (isset($filters['comp_name']) && $filters['comp_name']) {
            // user_type = 5: 본인 고객사의 주문접수 리스트만
            // 인성 API 주문과 일반 주문 모두 포함
            // JOIN은 이미 위에서 했음
            $builder->join('tbl_company_list cl_insung', 'u_list.user_company = cl_insung.comp_code', 'left');
            
            $builder->groupStart()
                ->groupStart()
                    ->where('o.order_system', 'insung')
                    ->where('cl_insung.comp_name', $filters['comp_name'])
                ->groupEnd()
                ->orGroupStart()
                    ->groupStart()
                        ->where('o.order_system IS NULL', null, true)
                        ->orWhere('o.order_system !=', 'insung')
                    ->groupEnd()
                    ->where('o.company_name', $filters['comp_name'])
                ->groupEnd()
            ->groupEnd();
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
        
        // 날짜 필터: order_date가 오늘 날짜보다 같거나 큰 경우만 조회
        $today = date('Y-m-d');
        $builder->where('DATE(o.order_date) >=', $today);
        
        // 총 개수 조회 (페이징용)
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults();
        
        // 정렬 및 페이징
        // 컬럼 인덱스와 DB 필드 매핑
        $columnFieldMap = [
            1 => ['field' => 'o.order_date', 'secondary' => 'o.order_time'], // 접수일자
            2 => 'o.reserve_date', // 예약일
            3 => 'o.state', // 상태
            4 => 'o.company_name', // 회사명
            5 => 'o.complete_time', // 완료시간
            6 => 'o.customer_department', // 접수부서
            7 => 'o.customer_duty', // 접수담당
            8 => 'o.destination_manager', // 도착지담당명
            9 => 'o.delivery_content', // 전달내용
            10 => 'o.item_type', // 상품
            11 => 'o.rider_tel_number', // 라이더연락처
            12 => 'o.order_number', // 주문번호
            13 => 'o.departure_company_name', // 출발지고객명
            14 => 'o.departure_manager', // 출발지담당명
            15 => 'o.departure_dong', // 출발지동
            16 => 'o.destination_company_name', // 도착지고객명
            17 => 'o.destination_dong', // 도착지동
            18 => 'o.payment_method', // 지불
            19 => 'oq.delivery_method', // 배송
            20 => 'o.delivery_vehicle', // 배송수단
            21 => 'o.rider_id', // 기사번호
            22 => 'o.rider_name' // 기사이름
        ];
        
        $orderBy = $filters['order_by'] ?? null;
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        
        if ($orderBy && isset($columnFieldMap[$orderBy])) {
            $sortField = $columnFieldMap[$orderBy];
            
            // 복합 정렬이 필요한 경우 (접수일자)
            if (is_array($sortField) && isset($sortField['field']) && isset($sortField['secondary'])) {
                $builder->orderBy($sortField['field'], $orderDir);
                $builder->orderBy($sortField['secondary'], $orderDir);
            } else {
                // 단일 필드 정렬
                $builder->orderBy($sortField, $orderDir);
            }
        } else {
            // 기본 정렬
            $builder->orderBy('o.order_date', 'DESC');
            $builder->orderBy('o.order_time', 'DESC');
        }
        
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
        
        // 순번 추가 (역순: 마지막 항목이 1번)
        $endNum = $totalCount - (($page - 1) * $perPage);
        foreach ($orders as $index => &$order) {
            $order['row_number'] = $endNum - $index;
        }
        
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
        
        // 오늘 날짜인 주문만 연동 처리
        $today = date('Y-m-d');
        $builder->where('DATE(o.order_date)', $today);
        
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
