<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table = 'tbl_departments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'customer_id',
        'department_code',
        'department_name',
        'parent_department_id',
        'department_level',
        'manager_name',
        'manager_contact',
        'manager_email',
        'cost_center',
        'budget_limit',
        'is_active',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'customer_id' => 'required|integer',
        'department_code' => 'required|max_length[20]',
        'department_name' => 'required|max_length[100]',
        'parent_department_id' => 'permit_empty|integer',
        'department_level' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[3]',
        'manager_name' => 'permit_empty|max_length[50]',
        'manager_contact' => 'permit_empty|max_length[20]',
        'manager_email' => 'permit_empty|valid_email|max_length[100]',
        'cost_center' => 'permit_empty|max_length[20]',
        'budget_limit' => 'permit_empty|decimal',
        'is_active' => 'permit_empty|in_list[0,1]',
        'notes' => 'permit_empty'
    ];

    protected $validationMessages = [
        'customer_id' => [
            'required' => '고객사 ID는 필수입니다.',
            'integer' => '고객사 ID는 정수여야 합니다.'
        ],
        'department_code' => [
            'required' => '부서 코드는 필수입니다.',
            'max_length' => '부서 코드는 20자를 초과할 수 없습니다.'
        ],
        'department_name' => [
            'required' => '부서명은 필수입니다.',
            'max_length' => '부서명은 100자를 초과할 수 없습니다.'
        ],
        'manager_email' => [
            'valid_email' => '유효한 이메일 주소를 입력해주세요.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 고객사별 부서 목록 조회
     */
    public function getDepartmentsByCustomer($customerId, $activeOnly = true)
    {
        $builder = $this->builder();
        $builder->where('customer_id', $customerId);
        
        if ($activeOnly) {
            $builder->where('is_active', 1);
        }
        
        $builder->orderBy('department_level', 'ASC');
        $builder->orderBy('department_name', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 부서 계층 구조 조회
     */
    public function getDepartmentHierarchy($customerId, $parentId = null)
    {
        $builder = $this->builder();
        $builder->where('customer_id', $customerId);
        $builder->where('is_active', 1);
        
        if ($parentId === null) {
            $builder->where('parent_department_id IS NULL');
        } else {
            $builder->where('parent_department_id', $parentId);
        }
        
        $builder->orderBy('department_level', 'ASC');
        $builder->orderBy('department_name', 'ASC');
        
        $departments = $builder->get()->getResultArray();
        
        // 하위 부서 재귀 조회
        foreach ($departments as &$department) {
            $department['children'] = $this->getDepartmentHierarchy($customerId, $department['id']);
        }
        
        return $departments;
    }

    /**
     * 부서 코드 중복 확인
     */
    public function isDepartmentCodeUnique($customerId, $departmentCode, $excludeId = null)
    {
        $builder = $this->builder();
        $builder->where('customer_id', $customerId);
        $builder->where('department_code', $departmentCode);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() === 0;
    }

    /**
     * 부서별 사용자 수 조회
     */
    public function getDepartmentUserCount($departmentId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_user_departments');
        $builder->where('department_id', $departmentId);
        $builder->where('is_primary', 1);
        
        return $builder->countAllResults();
    }

    /**
     * 부서별 예산 사용 현황 조회
     */
    public function getDepartmentBudgetUsage($departmentId, $startDate = null, $endDate = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_orders o');
        $builder->select('SUM(o.total_amount) as total_usage');
        $builder->where('o.department_id', $departmentId);
        $builder->where('o.status', 'completed');
        
        if ($startDate) {
            $builder->where('o.created_at >=', $startDate);
        }
        if ($endDate) {
            $builder->where('o.created_at <=', $endDate);
        }
        
        $result = $builder->get()->getRow();
        return $result ? (float)$result->total_usage : 0;
    }

    /**
     * 부서 삭제 가능 여부 확인 (사용자나 주문이 있는지 확인)
     */
    public function canDeleteDepartment($departmentId)
    {
        $db = \Config\Database::connect();
        
        // 사용자 확인
        $userBuilder = $db->table('tbl_user_departments');
        $userBuilder->where('department_id', $departmentId);
        $userCount = $userBuilder->countAllResults();
        
        // 주문 확인
        $orderBuilder = $db->table('tbl_orders');
        $orderBuilder->where('department_id', $departmentId);
        $orderCount = $orderBuilder->countAllResults();
        
        // 하위 부서 확인
        $childBuilder = $this->builder();
        $childBuilder->where('parent_department_id', $departmentId);
        $childCount = $childBuilder->countAllResults();
        
        return $userCount === 0 && $orderCount === 0 && $childCount === 0;
    }

    /**
     * 부서 활성화/비활성화
     */
    public function toggleDepartmentStatus($departmentId, $status)
    {
        return $this->update($departmentId, ['is_active' => $status ? 1 : 0]);
    }

    /**
     * 부서 검색
     */
    public function searchDepartments($customerId, $searchTerm, $activeOnly = true)
    {
        $builder = $this->builder();
        $builder->where('customer_id', $customerId);
        
        if ($activeOnly) {
            $builder->where('is_active', 1);
        }
        
        $builder->groupStart();
        $builder->like('department_code', $searchTerm);
        $builder->orLike('department_name', $searchTerm);
        $builder->orLike('manager_name', $searchTerm);
        $builder->groupEnd();
        
        $builder->orderBy('department_name', 'ASC');
        
        return $builder->get()->getResultArray();
    }
}
