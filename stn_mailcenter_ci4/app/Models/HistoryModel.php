<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoryModel extends Model
{
    protected $table = 'tbl_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    /**
     * 이용내역상세조회 리스트 조회
     * 
     * @param array $filters 필터 조건
     * @param int $page 페이지 번호
     * @param int $perPage 페이지당 항목 수
     * @return array
     */
    public function getHistoryList($filters = [], $page = 1, $perPage = 20)
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
            
            // user_dept 필터링 (user_class 3 이상일 때)
            if (isset($filters['user_dept']) && !empty($filters['user_dept'])) {
                $builder->where('u_list.user_dept', $filters['user_dept']);
            }
            
            // 정산관리부서 필터링 (user_class 4일 때)
            if (isset($filters['settlement_depts']) && is_array($filters['settlement_depts']) && !empty($filters['settlement_depts'])) {
                $builder->whereIn('u_list.user_dept', $filters['settlement_depts']);
            } elseif (isset($filters['settlement_depts']) && is_array($filters['settlement_depts']) && empty($filters['settlement_depts'])) {
                // 정산관리부서가 설정되지 않았으면 빈 결과
                $builder->where('1', '0'); // 항상 false 조건
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
                    // CLI로 저장된 데이터는 comp_no로 필터링
                    // 서브도메인 필터가 있을 때는 이미 comp_no로 필터링됨
                    // 추가 필터링 불필요
                }
                // user_type = 1: 서브도메인 필터만 적용 (추가 필터 없음)
            }
        }
        // user_dept 필터링 (서브도메인 필터가 없을 때, user_class 3 이상일 때)
        if (isset($filters['user_dept']) && !empty($filters['user_dept'])) {
            $builder->where('u_list.user_dept', $filters['user_dept']);
        }
        
        // 정산관리부서 필터링 (서브도메인 필터가 없을 때, user_class 4일 때)
        if (isset($filters['settlement_depts']) && is_array($filters['settlement_depts']) && !empty($filters['settlement_depts'])) {
            $builder->whereIn('u_list.user_dept', $filters['settlement_depts']);
        } elseif (isset($filters['settlement_depts']) && is_array($filters['settlement_depts']) && empty($filters['settlement_depts'])) {
            // 정산관리부서가 설정되지 않았으면 빈 결과
            $builder->where('1', '0'); // 항상 false 조건
        }
        // 서브도메인 필터가 없을 때 daumdata 로그인 필터링
        // m_code, cc_code가 세션에 있으면 customer_id(거래처번호)로 직접 필터링
        // user_type=5일 때도 env1=1이면 comp_code 필터가 필요함
        if (!$hasSubdomainFilter) {
            $loginType = $filters['login_type'] ?? null;
            if ($loginType === 'daumdata') {
                // user_type=5이고 skip_user_company_filter가 있으면 (env1=1) comp_code 필터 추가 필요
                // user_type=5이고 skip_user_company_filter가 없으면 user_company 필터가 아래에서 추가되므로 여기서는 추가하지 않음
                // user_type=1일 때는 comp_code 필터는 유지
                $shouldApplyCompCodeFilter = false;
                if (!isset($filters['user_type']) || $filters['user_type'] != '5') {
                    $shouldApplyCompCodeFilter = true;
                } elseif (isset($filters['user_type']) && $filters['user_type'] == '5' && isset($filters['skip_user_company_filter']) && $filters['skip_user_company_filter']) {
                    // user_type=5이고 env1=1일 때는 comp_code 필터 필요
                    $shouldApplyCompCodeFilter = true;
                }
                
                if ($shouldApplyCompCodeFilter) {
                    $compCode = $filters['comp_code'] ?? null;
                    if (!$compCode) {
                        // 세션에서 comp_code 가져오기
                        $session = \Config\Services::session();
                        $compCode = $session->get('comp_code');
                    }
                    
                    if ($compCode) {
                        // customer_id 필드에 거래처번호가 저장되어 있으므로 직접 비교
                        $builder->where('o.customer_id', $compCode);
                    }
                }
            }
        }
        
        if (isset($filters['user_type']) && $filters['user_type'] == '3' && isset($filters['user_company']) && $filters['user_company']) {
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
            // customer_id 필드에 거래처번호가 저장되어 있으므로 직접 비교
            if (isset($filters['user_company']) && $filters['user_company']) {
                $builder->where('o.customer_id', $filters['user_company']);
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
        
        // 날짜 필터
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            // start_date와 end_date가 있으면 해당 기간으로 필터링
            $builder->where('DATE(o.updated_at) >=', $filters['start_date']);
            $builder->where('DATE(o.updated_at) <=', $filters['end_date']);
        } else {
            // 날짜 필터가 없으면 오늘 날짜 이후만 조회
            // 인성 API를 통해 전달받은 주문은 updated_at이 현재 시간으로 업데이트되므로
            // updated_at >= 오늘날짜 조건으로 통일
            $today = date('Y-m-d');
            $builder->where('DATE(o.updated_at) >=', $today);
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
        
        // 본인주문조회 필터 (env1=3): insung_user_id로 필터링
        if (isset($filters['insung_user_id']) && $filters['insung_user_id']) {
            $builder->where('o.insung_user_id', $filters['insung_user_id']);
        }
        
        // 총 개수 조회 (페이징용)
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults();
        
        // 정렬 및 페이징
        // 컬럼 인덱스와 DB 필드 매핑
        $columnFieldMap = [
            1 => ['field' => 'o.order_date', 'secondary' => 'o.order_time'], // 접수일자
            2 => null, // 전표 (정렬 불가)
            3 => 'o.state', // 상태
            4 => 'o.order_number', // 주문번호
            5 => 'o.company_name', // 의뢰자
            6 => 'o.customer_duty', // 의뢰담당
            7 => 'o.departure_address', // 출발지
            8 => 'o.departure_dong', // 출발동
            9 => 'o.departure_manager', // 출발담당
            10 => 'o.departure_department', // 출발부서
            11 => 'o.departure_contact', // 출발전화번호
            12 => 'o.departure_detail', // 출발상세
            13 => 'o.destination_address', // 도착지
            14 => 'o.destination_dong', // 도착동
            15 => 'o.destination_manager', // 도착담당
            16 => 'o.destination_contact', // 도착전화번호
            17 => 'o.detail_address', // 도착상세
            18 => 'oq.delivery_route', // 왕복
            19 => 'st.service_category', // 형태
            20 => 'o.car_type', // 차종
            21 => 'o.total_fare', // 기본요금
            22 => 'o.add_cost', // 추가
            23 => 'o.delivery_cost', // 탁송료
            24 => 'o.total_amount', // 정산금액
            25 => 'o.item_type', // 상품
            26 => 'o.delivery_content', // 적요
            27 => 'o.order_regist_type' // 채널
        ];
        
        $orderBy = $filters['order_by'] ?? null;
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        
        if ($orderBy && isset($columnFieldMap[$orderBy]) && $columnFieldMap[$orderBy] !== null) {
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
            // 기본 정렬: 접수일자 역순 (모든 주문접수 리스트 통일)
            $builder->orderBy('o.order_date', 'DESC');
            $builder->orderBy('o.order_time', 'DESC');
        }
        
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'History list query failed: ' . $this->db->getLastQuery());
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
        
        return [
            'orders' => $orders,
            'total_count' => $totalCount
        ];
    }
    
    /**
     * 페이징 정보 계산
     * 
     * @param int $totalCount 전체 개수
     * @param int $page 현재 페이지
     * @param int $perPage 페이지당 항목 수
     * @return array
     */
    public function calculatePagination($totalCount, $page, $perPage)
    {
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'total_count' => $totalCount,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ];
    }
}

