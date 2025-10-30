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
    public function getOrderStats($customerId, $userRole)
    {
        $builder = $this->db->table('tbl_orders o');
        
        // 권한에 따른 필터링
        if ($userRole !== 'super_admin') {
            $builder->where('o.customer_id', $customerId);
        } elseif ($customerId) {
            $builder->where('o.customer_id', $customerId);
        }
        
        // 전체 주문 수
        $totalOrders = $builder->countAllResults(false);
        
        // 상태별 주문 수
        $statusStats = $this->db->table('tbl_orders o')
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
        $todayBuilder = $this->db->table('tbl_orders o')
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
    public function getRecentOrders($customerId, $userRole, $limit = 10)
    {
        $builder = $this->db->table('tbl_orders o');
        
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
        $builder->limit($limit);
        
        return $builder->get()->getResultArray();
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
}
