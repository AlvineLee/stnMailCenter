<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerHierarchyModel extends Model
{
    protected $table = 'tbl_customer_hierarchy';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'company_name',
        'business_number',
        'hierarchy_level',
        'parent_customer_id',
        'contact_person',
        'contact_phone',
        'contact_email',
        'address',
        'is_active',
        'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'company_name' => 'required|max_length[100]',
        'business_number' => 'required|max_length[20]',
        'hierarchy_level' => 'required|in_list[head_office,branch,agency]',
        'contact_person' => 'required|max_length[50]',
        'contact_phone' => 'required|max_length[20]',
        'contact_email' => 'required|valid_email|max_length[100]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];

    protected $validationMessages = [
        'company_name' => [
            'required' => '회사명은 필수입니다.',
            'max_length' => '회사명은 100자를 초과할 수 없습니다.'
        ],
        'business_number' => [
            'required' => '사업자번호는 필수입니다.',
            'max_length' => '사업자번호는 20자를 초과할 수 없습니다.'
        ],
        'hierarchy_level' => [
            'required' => '계층 레벨은 필수입니다.',
            'in_list' => '유효하지 않은 계층 레벨입니다.'
        ],
        'contact_email' => [
            'valid_email' => '유효한 이메일 주소를 입력해주세요.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 활성 고객사 목록 조회
     */
    public function getActiveCustomers()
    {
        return $this->where('is_active', 1)
                   ->orderBy('hierarchy_level', 'ASC')
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }

    /**
     * 계층 레벨별 고객사 조회
     */
    public function getCustomersByLevel($level)
    {
        return $this->where('hierarchy_level', $level)
                   ->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }

    /**
     * 본점 목록 조회
     */
    public function getHeadOffices()
    {
        return $this->getCustomersByLevel('head_office');
    }

    /**
     * 지사 목록 조회
     */
    public function getBranches()
    {
        return $this->getCustomersByLevel('branch');
    }

    /**
     * 대리점 목록 조회
     */
    public function getAgencies()
    {
        return $this->getCustomersByLevel('agency');
    }

    /**
     * 고객사 계층 구조 조회
     */
    public function getCustomerHierarchy($parentId = null)
    {
        $builder = $this->builder();
        $builder->where('is_active', 1);
        
        if ($parentId === null) {
            $builder->where('parent_customer_id IS NULL');
        } else {
            $builder->where('parent_customer_id', $parentId);
        }
        
        $builder->orderBy('hierarchy_level', 'ASC');
        $builder->orderBy('company_name', 'ASC');
        
        $customers = $builder->get()->getResultArray();
        
        // 하위 고객사 재귀 조회
        foreach ($customers as &$customer) {
            $customer['children'] = $this->getCustomerHierarchy($customer['id']);
        }
        
        return $customers;
    }

    /**
     * 고객사 검색
     */
    public function searchCustomers($searchTerm, $level = null)
    {
        $builder = $this->builder();
        $builder->where('is_active', 1);
        
        if ($level) {
            $builder->where('hierarchy_level', $level);
        }
        
        $builder->groupStart();
        $builder->like('company_name', $searchTerm);
        $builder->orLike('business_number', $searchTerm);
        $builder->orLike('contact_person', $searchTerm);
        $builder->groupEnd();
        
        $builder->orderBy('company_name', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 고객사 활성화/비활성화
     */
    public function toggleCustomerStatus($customerId, $status)
    {
        return $this->update($customerId, ['is_active' => $status ? 1 : 0]);
    }

    /**
     * 고객사 삭제 가능 여부 확인
     */
    public function canDeleteCustomer($customerId)
    {
        $db = \Config\Database::connect();
        
        // 하위 고객사 확인
        $childBuilder = $this->builder();
        $childBuilder->where('parent_customer_id', $customerId);
        $childCount = $childBuilder->countAllResults();
        
        // 사용자 확인
        $userBuilder = $db->table('tbl_users');
        $userBuilder->where('customer_id', $customerId);
        $userCount = $userBuilder->countAllResults();
        
        // 주문 확인
        $orderBuilder = $db->table('tbl_orders');
        $orderBuilder->where('customer_id', $customerId);
        $orderCount = $orderBuilder->countAllResults();
        
        return $childCount === 0 && $userCount === 0 && $orderCount === 0;
    }
}
