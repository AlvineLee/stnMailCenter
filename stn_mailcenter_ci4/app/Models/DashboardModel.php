<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
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
     * 고객사 목록 조회 (슈퍼관리자용)
     */
    public function getActiveCustomers()
    {
        return $this->db->table('tbl_customer_hierarchy')
                       ->where('is_active', TRUE)
                       ->orderBy('hierarchy_level', 'ASC')
                       ->orderBy('customer_name', 'ASC')
                       ->get()
                       ->getResultArray();
    }

    /**
     * 고객사 정보 조회
     */
    public function getCustomerById($customerId)
    {
        return $this->db->table('tbl_customer_hierarchy')
                       ->where('id', $customerId)
                       ->get()
                       ->getRowArray();
    }

    /**
     * 주문 통계 조회
     */
    public function getOrderStats($customerId, $userRole, $loginType = 'stn', $userType = null, $compCode = null, $ccCode = null, $compCodeForEnv = null, $loginUserId = null)
    {
        $today = date('Y-m-d');
        $builder = $this->db->table('tbl_orders o');
        
        // 인성 API를 통해 전달받은 주문은 updated_at이 현재 시간으로 업데이트되므로
        // updated_at >= 오늘날짜 조건으로 통일
        $builder->where('DATE(o.updated_at) >=', $today);
        
        if ($loginType === 'daumdata') {
            // daumdata 로그인: 인성 시스템 주문 통계
            // 배송조회와 동일한 JOIN 구조 사용
            $builder->join('tbl_users_list u_list', 'o.user_id = u_list.idx', 'left');
            $builder->join('tbl_company_list cl_ch', 'u_list.user_company = cl_ch.comp_code', 'left');
        }
        
        // 서브도메인 필터링 (최우선, 배송조회와 동일)
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $hasSubdomainFilter = false;
        
        if ($currentSubdomain && $currentSubdomain !== 'default') {
            $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
            if ($subdomainCompCode) {
                $hasSubdomainFilter = true;
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
                
                // 서브도메인 필터 적용 (인성 API 주문만)
                $builder->where('o.order_system', 'insung');
                $builder->where('u_list.user_company', $subdomainCompCode);
            }
        }
        
        // 서브도메인 필터가 없을 때만 일반 필터 적용 (배송조회와 동일)
        if (!$hasSubdomainFilter) {
            // 권한에 따른 필터링
            if ($loginType === 'daumdata') {
                if ($userType == '1') {
                    // 메인 사이트 관리자: 전체 주문
                    // 필터 없음
                } elseif ($userType == '3') {
                    // 콜센터 관리자: 소속 콜센터의 고객사 주문만
                    if ($ccCode) {
                        $builder->join('tbl_cc_list cc', 'cl_ch.cc_idx = cc.idx', 'left');
                        $builder->where('cc.cc_code', $ccCode);
                    }
                } elseif ($userType == '5') {
                    // 일반 고객: 본인 고객사의 주문만
                    if ($compCode) {
                        $builder->where('u_list.user_company', $compCode);
                    }
                }
            } else {
                // STN 로그인: 기존 로직
                // 권한에 따른 필터링
                if ($userRole !== 'super_admin') {
                    $builder->where('o.customer_id', $customerId);
                } elseif ($customerId) {
                    $builder->where('o.customer_id', $customerId);
                }
            }
        }
        
        // 본인주문조회 필터 (env1=3): insung_user_id로 필터링
        if ($compCodeForEnv && $loginUserId) {
            $envBuilder = $this->db->table('tbl_company_env');
            $envBuilder->select('env1');
            $envBuilder->where('comp_code', $compCodeForEnv);
            $envQuery = $envBuilder->get();
            if ($envQuery !== false) {
                $envResult = $envQuery->getRowArray();
                if ($envResult && isset($envResult['env1']) && $envResult['env1'] == '3') {
                    $builder->where('o.insung_user_id', $loginUserId);
                }
            }
        }
        
        // 전체 주문 수 (오늘 날짜 기준)
        $totalOrders = $builder->countAllResults(false);
        
        // 상태별 주문 수를 한 번의 쿼리로 처리 (GROUP BY 사용)
        // 인성 API 주문: state 컬럼 (한글 상태값: '예약', '접수', '배차', '운행', '완료' 또는 숫자 코드: '90', '10', '11', '12', '30')
        // 일반 주문: status 컬럼을 state로 매핑
        $statusBuilder = $this->db->table('tbl_orders o');
        // 인성 API를 통해 전달받은 주문은 updated_at이 현재 시간으로 업데이트되므로
        // updated_at >= 오늘날짜 조건으로 통일
        $statusBuilder->where('DATE(o.updated_at) >=', $today);
        
        // daumdata 로그인일 때 필요한 JOIN 추가 (배송조회와 동일한 구조)
        if ($loginType === 'daumdata') {
            $statusBuilder->join('tbl_users_list u_list', 'o.user_id = u_list.idx', 'left');
            $statusBuilder->join('tbl_company_list cl_ch', 'u_list.user_company = cl_ch.comp_code', 'left');
        }
        
        // 서브도메인 필터링 (최우선, 배송조회와 동일)
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $hasSubdomainFilter = false;
        
        if ($currentSubdomain && $currentSubdomain !== 'default') {
            $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
            if ($subdomainCompCode) {
                $hasSubdomainFilter = true;
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
                
                // 서브도메인 필터 적용 (인성 API 주문만)
                $statusBuilder->where('o.order_system', 'insung');
                $statusBuilder->where('u_list.user_company', $subdomainCompCode);
            }
        }
        
        // 서브도메인 필터가 없을 때만 일반 필터 적용 (배송조회와 동일)
        if (!$hasSubdomainFilter) {
            // 필터 적용 (applyFilters는 JOIN을 추가하지 않음, 이미 위에서 추가됨)
            $this->applyFilters($statusBuilder, $loginType, $userRole, $userType, $customerId, $compCode, $ccCode, $compCodeForEnv, $loginUserId);
        }
        
        // 본인주문조회 필터 (env1=3): insung_user_id로 필터링
        if ($compCodeForEnv && $loginUserId) {
            $envBuilder = $this->db->table('tbl_company_env');
            $envBuilder->select('env1');
            $envBuilder->where('comp_code', $compCodeForEnv);
            $envQuery = $envBuilder->get();
            if ($envQuery !== false) {
                $envResult = $envQuery->getRowArray();
                if ($envResult && isset($envResult['env1']) && $envResult['env1'] == '3') {
                    $statusBuilder->where('o.insung_user_id', $loginUserId);
                }
            }
        }
        
        // CASE WHEN으로 상태를 정규화하여 GROUP BY
        // 인성 API 주문의 state와 일반 주문의 status를 통합
        $statusBuilder->select("
            CASE 
                WHEN o.order_system = 'insung' AND (o.state = '예약' OR o.state = '90' OR o.state = 'A예약') THEN '예약'
                WHEN o.order_system = 'insung' AND (o.state = '접수' OR o.state = '10' OR o.state = 'A접수') THEN '접수'
                WHEN o.order_system = 'insung' AND (o.state = '배차' OR o.state = '11') THEN '배차'
                WHEN o.order_system = 'insung' AND (o.state = '운행' OR o.state = '12') THEN '운행'
                WHEN o.order_system = 'insung' AND (o.state = '완료' OR o.state = '30') THEN '완료'
                WHEN o.order_system = 'insung' AND (o.state = '대기' OR o.state = '20' OR o.state = 'A대기') THEN '대기'
                WHEN o.order_system = 'insung' AND (o.state = '취소' OR o.state = '40') THEN '취소'
                WHEN o.order_system != 'insung' AND o.status = 'pending' THEN '예약'
                WHEN o.order_system != 'insung' AND o.status = 'processing' THEN '접수'
                WHEN o.order_system != 'insung' AND o.status = 'completed' THEN '운행'
                WHEN o.order_system != 'insung' AND o.status = 'delivered' THEN '완료'
                WHEN o.order_system != 'insung' AND o.status = 'cancelled' THEN '취소'
                ELSE '기타'
            END as status_category,
            COUNT(*) as count
        ");
        $statusBuilder->groupBy('status_category');
        
        $statusResults = $statusBuilder->get()->getResultArray();
        
        // 결과를 key-value 형태로 정리
        $statusCounts = [
            '예약' => 0,
            '접수' => 0,
            '배차' => 0,
            '운행' => 0,
            '완료' => 0,
            '대기' => 0,
            '취소' => 0
        ];
        
        foreach ($statusResults as $result) {
            $category = $result['status_category'] ?? '';
            $count = (int)($result['count'] ?? 0);
            if (isset($statusCounts[$category])) {
                $statusCounts[$category] = $count;
            }
        }
        
        $stats = [
            'total_orders' => $totalOrders,
            'reservation_orders' => $statusCounts['예약'],
            'reception_orders' => $statusCounts['접수'],
            'dispatch_orders' => $statusCounts['배차'],
            'driving_orders' => $statusCounts['운행'],
            'completed_orders' => $statusCounts['완료'],
            'waiting_orders' => $statusCounts['대기'],
            'cancelled_orders' => $statusCounts['취소'],
            'today_orders' => 0
        ];
        
        // 오늘 주문 수 (updated_at 기준, 배송조회와 동일하게 >= 조건 사용)
        // 인성 API를 통해 전달받은 주문은 updated_at이 현재 시간으로 업데이트되므로
        // updated_at >= 오늘날짜 조건으로 통일
        $todayBuilder = $this->db->table('tbl_orders o')
                              ->where('DATE(o.updated_at) >=', $today);
        
        if ($loginType === 'daumdata') {
            // 배송조회와 동일한 JOIN 구조 사용
            $todayBuilder->join('tbl_users_list u_list', 'o.user_id = u_list.idx', 'left');
            $todayBuilder->join('tbl_company_list cl_ch', 'u_list.user_company = cl_ch.comp_code', 'left');
        }
        
        // 서브도메인 필터링 (최우선, 배송조회와 동일)
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $hasSubdomainFilter = false;
        
        if ($currentSubdomain && $currentSubdomain !== 'default') {
            $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
            if ($subdomainCompCode) {
                $hasSubdomainFilter = true;
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
                
                // 서브도메인 필터 적용 (인성 API 주문만)
                $todayBuilder->where('o.order_system', 'insung');
                $todayBuilder->where('u_list.user_company', $subdomainCompCode);
            }
        }
        
        // 서브도메인 필터가 없을 때만 일반 필터 적용 (배송조회와 동일)
        if (!$hasSubdomainFilter) {
            // 권한에 따른 필터링
            if ($loginType === 'daumdata') {
                if ($userType == '1') {
                    // 필터 없음
                } elseif ($userType == '3') {
                    // 콜센터 관리자: 소속 콜센터의 고객사 주문만
                    if ($ccCode) {
                        $todayBuilder->join('tbl_cc_list cc', 'cl_ch.cc_idx = cc.idx', 'left');
                        $todayBuilder->where('cc.cc_code', $ccCode);
                    }
                } elseif ($userType == '5') {
                    // 일반 고객: 본인 고객사의 주문만
                    if ($compCode) {
                        $todayBuilder->where('u_list.user_company', $compCode);
                    }
                }
            } else {
                if ($userRole !== 'super_admin') {
                    $todayBuilder->where('o.customer_id', $customerId);
                } elseif ($customerId) {
                    $todayBuilder->where('o.customer_id', $customerId);
                }
            }
        }
        
        $stats['today_orders'] = $todayBuilder->countAllResults();
        
        return $stats;
    }

    /**
     * 최근 주문 조회
     */
    public function getRecentOrders($customerId, $userRole, $limit = 10, $loginType = 'stn', $userType = null, $compCode = null, $ccCode = null, $compCodeForEnv = null, $loginUserId = null)
    {
        $builder = $this->db->table('tbl_orders o');
        
        if ($loginType === 'daumdata') {
            // daumdata 로그인: 인성 시스템 주문 조회 (배송조회와 동일한 JOIN 구조)
            $builder->select('
                o.id,
                o.order_number,
                o.insung_order_number,
                o.state,
                o.status,
                o.order_system,
                o.order_date,
                o.order_time,
                o.reserve_date,
                o.created_at,
                o.total_amount,
                o.departure_company_name,
                o.destination_company_name,
                st.service_name as service,
                COALESCE(ch.customer_name, cl_ch.comp_name) as customer,
                COALESCE(u.real_name, u_list.user_name) as user_name,
                oq.delivery_method,
                o.car_type,
                DATE_FORMAT(o.created_at, "%Y-%m-%d %H:%i") as date
            ');
            
            $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
            // 배송조회와 동일한 JOIN 구조
            $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
            $builder->join('tbl_users_list u_list', 'o.user_id = u_list.idx', 'left');
            $builder->join('tbl_company_list cl_ch', 'u_list.user_company = cl_ch.comp_code', 'left');
            $builder->join('tbl_users u', 'o.user_id = u.id', 'left');
            $builder->join('tbl_orders_quick oq', 'o.id = oq.order_id', 'left');
        } else {
            // STN 로그인: 기존 로직
            $builder->select('
                o.id,
                o.order_number,
                o.status,
                o.order_date,
                o.order_time,
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
        }
        
        // 인성 API를 통해 전달받은 주문은 updated_at이 현재 시간으로 업데이트되므로
        // updated_at >= 오늘날짜 조건으로 통일
        $today = date('Y-m-d');
        $builder->where('DATE(o.updated_at) >=', $today);
        
        // 서브도메인 필터링 (최우선, 배송조회와 동일)
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $hasSubdomainFilter = false;
        
        if ($currentSubdomain && $currentSubdomain !== 'default') {
            $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
            if ($subdomainCompCode) {
                $hasSubdomainFilter = true;
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
                
                // 서브도메인 필터 적용 (인성 API 주문만)
                $builder->where('o.order_system', 'insung');
                $builder->where('u_list.user_company', $subdomainCompCode);
            }
        }
        
        // 서브도메인 필터가 없을 때만 일반 필터 적용 (배송조회와 동일)
        if (!$hasSubdomainFilter && $loginType === 'daumdata') {
            // 권한에 따른 필터링 (배송조회와 동일한 로직)
            if ($userType == '1') {
                // 메인 사이트 관리자: 전체 주문
                // 필터 없음
            } elseif ($userType == '3') {
                // 콜센터 관리자: 소속 콜센터의 고객사 주문만
                if ($compCode) {
                    // comp_code로 comp_name 조회
                    $compNameBuilder = $this->db->table('tbl_company_list');
                    $compNameBuilder->select('comp_name');
                    $compNameBuilder->where('comp_code', $compCode);
                    $compNameQuery = $compNameBuilder->get();
                    $userCompName = null;
                    if ($compNameQuery !== false) {
                        $compNameResult = $compNameQuery->getRowArray();
                        if ($compNameResult && !empty($compNameResult['comp_name'])) {
                            $userCompName = $compNameResult['comp_name'];
                        }
                    }
                    
                    // 인성 API 주문만 필터링
                    $builder->where('o.order_system', 'insung');
                    $builder->where('u_list.user_company', $compCode);
                }
            } elseif ($userType == '5') {
                // 일반 고객: 본인 고객사의 주문만 (인성 API 주문만)
                if ($compCode) {
                    $builder->where('o.order_system', 'insung');
                    $builder->where('u_list.user_company', $compCode);
                }
            }
        } elseif (!$hasSubdomainFilter && $loginType !== 'daumdata') {
            // STN 로그인: 권한에 따른 필터링
            if ($userRole !== 'super_admin') {
                $builder->where('o.customer_id', $customerId);
            } elseif ($customerId) {
                $builder->where('o.customer_id', $customerId);
            }
        }
        
        // 본인주문조회 필터 (env1=3): insung_user_id로 필터링
        if ($compCodeForEnv && $loginUserId) {
            $envBuilder = $this->db->table('tbl_company_env');
            $envBuilder->select('env1');
            $envBuilder->where('comp_code', $compCodeForEnv);
            $envQuery = $envBuilder->get();
            if ($envQuery !== false) {
                $envResult = $envQuery->getRowArray();
                if ($envResult && isset($envResult['env1']) && $envResult['env1'] == '3') {
                    $builder->where('o.insung_user_id', $loginUserId);
                }
            }
        }
        
        // 배송조회와 동일한 정렬 조건
        $builder->orderBy('o.order_date', 'DESC');
        $builder->orderBy('o.order_time', 'DESC');
        
        $builder->limit($limit);
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'Dashboard recent orders query failed: ' . $this->db->getLastQuery());
            return [];
        }
        
        $orders = $query->getResultArray();
        
        // user_name 복호화 처리 (tbl_users_list.user_name이 암호화되어 있을 수 있음)
        if ($loginType === 'daumdata' && !empty($orders)) {
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            foreach ($orders as &$order) {
                // user_name이 u_list.user_name에서 온 경우 복호화 필요
                // COALESCE(u.real_name, u_list.user_name)이므로 u_list.user_name이 사용된 경우만 복호화
                // 하지만 실제로는 u_list.user_name이 암호화되어 있으므로 복호화 시도
                if (isset($order['user_name']) && !empty($order['user_name'])) {
                    // 복호화 시도 (실패하면 원본 반환)
                    $decrypted = $encryptionHelper->decrypt($order['user_name']);
                    // 복호화 결과가 원본과 다르면 복호화 성공
                    if ($decrypted !== $order['user_name']) {
                        $order['user_name'] = $decrypted;
                    }
                }
            }
            unset($order);
        }
        
        return $orders;
    }

    /**
     * 주문 목록 조회 (페이징 없음)
     */
    public function getAllOrders($customerId, $userRole, $selectedCustomerId = null)
    {
        $builder = $this->db->table('tbl_orders o');
        
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
        
        return $builder->get()->getResultArray();
    }
    
    /**
     * 통계 조회 시 필터링 적용 (공통 메서드)
     */
    private function applyFilters($builder, $loginType, $userRole, $userType, $customerId, $compCode, $ccCode, $compCodeForEnv = null, $loginUserId = null)
    {
        if ($loginType === 'daumdata') {
            // daumdata 로그인: 인성 API 주문은 u_list.user_company로 필터링
            // JOIN은 이미 호출 전에 추가되어 있음 (u_list, cl_ch)
            
            // 권한에 따른 필터링
            if ($userType == '1') {
                // 필터 없음
            } elseif ($userType == '3') {
                // 콜센터 관리자: 소속 콜센터의 고객사 주문만
                if ($ccCode) {
                    $builder->join('tbl_cc_list cc', 'cl_ch.cc_idx = cc.idx', 'left');
                    $builder->where('cc.cc_code', $ccCode);
                }
            } elseif ($userType == '5') {
                // 일반 고객: 본인 고객사의 주문만
                if ($compCode) {
                    $builder->where('u_list.user_company', $compCode);
                }
            }
        } else {
            if ($userRole !== 'super_admin') {
                $builder->where('o.customer_id', $customerId);
            } elseif ($customerId) {
                $builder->where('o.customer_id', $customerId);
            }
        }
        
        // 본인주문조회 필터 (env1=3): insung_user_id로 필터링
        if ($compCodeForEnv && $loginUserId) {
            $envBuilder = $this->db->table('tbl_company_env');
            $envBuilder->select('env1');
            $envBuilder->where('comp_code', $compCodeForEnv);
            $envQuery = $envBuilder->get();
            if ($envQuery !== false) {
                $envResult = $envQuery->getRowArray();
                if ($envResult && isset($envResult['env1']) && $envResult['env1'] == '3') {
                    $builder->where('o.insung_user_id', $loginUserId);
                }
            }
        }
    }
}


