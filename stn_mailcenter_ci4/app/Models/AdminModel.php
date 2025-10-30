<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table = 'tbl_customer_service_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'customer_id',
        'service_type_id', 
        'is_enabled',
        'max_daily_orders',
        'max_monthly_orders',
        'special_instructions',
        'created_at',
        'updated_at'
    ];

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
     * 서비스 타입 목록 조회
     */
    public function getServiceTypes()
    {
        return $this->db->table('tbl_service_types')
                       ->where('is_active', TRUE)
                       ->orderBy('service_category', 'ASC')
                       ->orderBy('sort_order', 'ASC')
                       ->get()
                       ->getResultArray();
    }

    /**
     * 고객사별 서비스 권한 조회 (슈퍼관리자용)
     */
    public function getAllServicePermissions()
    {
        return $this->db->table('tbl_customer_service_permissions csp')
                       ->select('csp.*, ch.customer_name, st.service_name, st.service_category')
                       ->join('tbl_customer_hierarchy ch', 'csp.customer_id = ch.id')
                       ->join('tbl_service_types st', 'csp.service_type_id = st.id')
                       ->where('ch.is_active', TRUE)
                       ->orderBy('ch.customer_name', 'ASC')
                       ->orderBy('st.service_category', 'ASC')
                       ->get()
                       ->getResultArray();
    }

    /**
     * 특정 고객사의 서비스 권한 조회
     */
    public function getServicePermissionsByCustomer($customerId)
    {
        return $this->db->table('tbl_customer_service_permissions csp')
                       ->select('csp.*, ch.customer_name, st.service_name, st.service_category')
                       ->join('tbl_customer_hierarchy ch', 'csp.customer_id = ch.id')
                       ->join('tbl_service_types st', 'csp.service_type_id = st.id')
                       ->where('csp.customer_id', $customerId)
                       ->where('ch.is_active', TRUE)
                       ->orderBy('st.service_category', 'ASC')
                       ->get()
                       ->getResultArray();
    }

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
     * 서비스 권한 조회 (권한 체크용)
     */
    public function getServicePermissionById($permissionId, $customerId = null)
    {
        $builder = $this->db->table('tbl_customer_service_permissions')
                           ->where('id', $permissionId);
        
        if ($customerId) {
            $builder->where('customer_id', $customerId);
        }
        
        return $builder->get()->getRowArray();
    }

    /**
     * 서비스 권한 업데이트
     */
    public function updateServicePermission($permissionId, $updateData)
    {
        return $this->db->table('tbl_customer_service_permissions')
                       ->where('id', $permissionId)
                       ->update($updateData);
    }

    /**
     * 중복 서비스 권한 체크
     */
    public function checkDuplicatePermission($customerId, $serviceTypeId)
    {
        return $this->db->table('tbl_customer_service_permissions')
                       ->where('customer_id', $customerId)
                       ->where('service_type_id', $serviceTypeId)
                       ->get()
                       ->getRowArray();
    }

    /**
     * 새로운 서비스 권한 생성
     */
    public function createServicePermission($insertData)
    {
        return $this->db->table('tbl_customer_service_permissions')
                       ->insert($insertData);
    }
}
