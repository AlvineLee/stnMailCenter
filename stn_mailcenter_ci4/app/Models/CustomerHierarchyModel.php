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
        // 실제 DB 필드명 사용 (customer_name)
        $builder = $this->builder();
        $builder->where('is_active', 1);
        $builder->orderBy('hierarchy_level', 'ASC');
        $builder->orderBy('customer_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get active customers');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 계층 레벨별 고객사 조회
     */
    public function getCustomersByLevel($level)
    {
        // 실제 DB 필드명 사용 (customer_name)
        $builder = $this->builder();
        $builder->where('hierarchy_level', $level);
        $builder->where('is_active', 1);
        $builder->orderBy('customer_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get customers by level');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 본점 목록 조회
     */
    public function getHeadOffices()
    {
        return $this->getCustomersByLevel('head_office');
    }

    /**
     * 지사 목록 조회 (상위 고객사 정보 포함)
     */
    public function getBranches()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_customer_hierarchy c');
        
        $builder->select('
            c.*,
            p.customer_name as parent_customer_name
        ');
        
        $builder->join('tbl_customer_hierarchy p', 'c.parent_id = p.id', 'left');
        $builder->where('c.hierarchy_level', 'branch');
        $builder->where('c.is_active', 1);
        $builder->orderBy('c.customer_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get branches');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 대리점 목록 조회 (상위 고객사 정보 포함)
     */
    public function getAgencies()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_customer_hierarchy c');
        
        $builder->select('
            c.*,
            p.customer_name as parent_customer_name
        ');
        
        $builder->join('tbl_customer_hierarchy p', 'c.parent_id = p.id', 'left');
        $builder->where('c.hierarchy_level', 'agency');
        $builder->where('c.is_active', 1);
        $builder->orderBy('c.customer_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get agencies');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 고객사 계층 구조 조회
     */
    public function getCustomerHierarchy($parentId = null)
    {
        $builder = $this->builder();
        $builder->where('is_active', 1);
        
        // 실제 DB 필드명 사용 (parent_id)
        if ($parentId === null) {
            $builder->where('parent_id IS NULL');
        } else {
            $builder->where('parent_id', $parentId);
        }
        
        $builder->orderBy('hierarchy_level', 'ASC');
        $builder->orderBy('customer_name', 'ASC'); // 실제 DB 필드명
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get customer hierarchy');
            return [];
        }
        
        $customers = $query->getResultArray();
        
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
        
        // 실제 DB 필드명 사용
        $builder->groupStart();
        $builder->like('customer_name', $searchTerm); // DB: customer_name
        $builder->orLike('business_number', $searchTerm);
        $builder->orLike('representative_name', $searchTerm); // DB: representative_name
        $builder->groupEnd();
        
        $builder->orderBy('customer_name', 'ASC'); // DB: customer_name
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to search customers');
            return [];
        }
        
        return $query->getResultArray();
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
        
        // 하위 고객사 확인 (실제 DB 필드명: parent_id)
        $childBuilder = $this->builder();
        $childBuilder->where('parent_id', $customerId);
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

    /**
     * 본점 생성 (실제 DB 필드명 사용)
     */
    public function createHeadOffice($data)
    {
        // 실제 DB 필드명 사용 (customer_name, customer_code, parent_id 등)
        $dbData = [
            'customer_code' => $data['customer_code'],
            'customer_name' => $data['customer_name'],
            'hierarchy_level' => 'head_office',
            'parent_id' => null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => 1
        ];

        // allowedFields에 실제 DB 필드명이 없으므로 직접 insert
        $this->db->table($this->table)->insert($dbData);
        return $this->db->insertID();
    }

    /**
     * 고객사 코드 중복 체크
     */
    public function checkCustomerCodeExists($code)
    {
        $builder = $this->builder();
        $builder->where('customer_code', $code);
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to check customer code');
            return false;
        }
        
        return $query->getNumRows() > 0;
    }

    /**
     * 고객사 코드 생성 (중복 체크 포함)
     */
    public function generateCustomerCode($companyName)
    {
        // 회사명 기반 코드 생성
        $baseCode = preg_replace('/[^a-zA-Z0-9가-힣]/u', '', $companyName);
        $code = $baseCode . '_' . date('Ymd');
        
        // 중복 체크
        if ($this->checkCustomerCodeExists($code)) {
            $code = $baseCode . '_' . time();
        }
        
        return $code;
    }

    /**
     * 지사 생성 (실제 DB 필드명 사용)
     */
    public function createBranch($data)
    {
        $dbData = [
            'customer_code' => $data['customer_code'],
            'customer_name' => $data['customer_name'],
            'hierarchy_level' => 'branch',
            'parent_id' => $data['parent_id'],
            'business_number' => $data['business_number'] ?? null,
            'representative_name' => $data['representative_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'address' => $data['address'] ?? null,
            'contract_start_date' => $data['contract_start_date'] ?? null,
            'contract_end_date' => $data['contract_end_date'] ?? null,
            'is_active' => 1
        ];

        $this->db->table($this->table)->insert($dbData);
        return $this->db->insertID();
    }

    /**
     * 대리점 생성 (실제 DB 필드명 사용)
     */
    public function createAgency($data)
    {
        $dbData = [
            'customer_code' => $data['customer_code'],
            'customer_name' => $data['customer_name'],
            'hierarchy_level' => 'agency',
            'parent_id' => $data['parent_id'],
            'business_number' => $data['business_number'] ?? null,
            'representative_name' => $data['representative_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'address' => $data['address'] ?? null,
            'contract_start_date' => $data['contract_start_date'] ?? null,
            'contract_end_date' => $data['contract_end_date'] ?? null,
            'is_active' => 1
        ];

        $this->db->table($this->table)->insert($dbData);
        return $this->db->insertID();
    }

    /**
     * 고객사 정보 수정 (실제 DB 필드명 사용)
     */
    public function updateCustomer($customerId, $data)
    {
        $dbData = [];
        
        if (isset($data['customer_name'])) {
            $dbData['customer_name'] = $data['customer_name'];
        }
        if (isset($data['business_number'])) {
            $dbData['business_number'] = $data['business_number'];
        }
        if (isset($data['representative_name'])) {
            $dbData['representative_name'] = $data['representative_name'];
        }
        if (isset($data['contact_phone'])) {
            $dbData['contact_phone'] = $data['contact_phone'];
        }
        if (isset($data['contact_email'])) {
            $dbData['contact_email'] = $data['contact_email'];
        }
        if (isset($data['address'])) {
            $dbData['address'] = $data['address'];
        }
        if (isset($data['contract_start_date'])) {
            $dbData['contract_start_date'] = $data['contract_start_date'];
        }
        if (isset($data['contract_end_date'])) {
            $dbData['contract_end_date'] = $data['contract_end_date'];
        }
        if (isset($data['is_active'])) {
            $dbData['is_active'] = $data['is_active'] ? 1 : 0;
        }
        if (isset($data['logo_path'])) {
            $dbData['logo_path'] = $data['logo_path'];
        }

        if (empty($dbData)) {
            return false;
        }

        $this->db->table($this->table)->where('id', $customerId)->update($dbData);
        return $this->db->affectedRows() > 0;
    }

    /**
     * 고객사 상세 정보 조회
     */
    public function getCustomerById($customerId)
    {
        $builder = $this->builder();
        $builder->where('id', $customerId);
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get customer by ID');
            return null;
        }
        
        return $query->getRowArray();
    }

    /**
     * 상위 고객사 목록 조회 (지사/대리점 등록 시 상위 선택용)
     */
    public function getParentCustomers($excludeId = null, $maxLevel = null)
    {
        $builder = $this->builder();
        $builder->where('is_active', 1);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        // 지사 등록 시: 본점만
        if ($maxLevel === 'branch') {
            $builder->where('hierarchy_level', 'head_office');
        }
        // 대리점 등록 시: 본점, 지사만
        elseif ($maxLevel === 'agency') {
            $builder->whereIn('hierarchy_level', ['head_office', 'branch']);
        }
        
        $builder->orderBy('hierarchy_level', 'ASC');
        $builder->orderBy('customer_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get parent customers');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 고객사가 속한 본점 ID 찾기 (재귀적으로 상위를 찾아 본점까지)
     */
    public function getHeadOfficeId($customerId)
    {
        $customer = $this->find($customerId);
        if (!$customer) {
            return null;
        }

        // 이미 본점이면 자기 자신 반환
        if ($customer['hierarchy_level'] === 'head_office') {
            return $customer['id'];
        }

        // 상위 고객사가 없으면 null
        if (empty($customer['parent_id'])) {
            return null;
        }

        // 재귀적으로 상위 고객사를 찾아 본점까지
        return $this->getHeadOfficeId($customer['parent_id']);
    }

    /**
     * 본점 하위의 모든 고객사 ID 목록 조회 (본점 포함)
     */
    public function getCustomerGroupIds($headOfficeId)
    {
        $customerIds = [$headOfficeId]; // 본점 자신 포함
        
        // 본점 하위의 모든 고객사 조회
        $builder = $this->builder();
        $builder->where('is_active', 1);
        
        // 재귀적으로 모든 하위 고객사 찾기
        $this->getAllChildrenIds($headOfficeId, $customerIds);
        
        return $customerIds;
    }

    /**
     * 재귀적으로 모든 하위 고객사 ID 수집
     */
    private function getAllChildrenIds($parentId, &$customerIds)
    {
        $builder = $this->builder();
        $builder->where('parent_id', $parentId);
        $builder->where('is_active', 1);
        
        $query = $builder->get();
        if ($query === false) {
            return;
        }
        
        $children = $query->getResultArray();
        foreach ($children as $child) {
            $customerIds[] = $child['id'];
            // 재귀적으로 하위 고객사 찾기
            $this->getAllChildrenIds($child['id'], $customerIds);
        }
    }

    /**
     * 로그인한 사용자가 속한 그룹의 고객사 목록 조회 (계층 레벨별)
     */
    public function getCustomersByUserGroup($userId, $hierarchyLevel)
    {
        // 사용자의 customer_id 조회
        $db = \Config\Database::connect();
        $userBuilder = $db->table('tbl_users');
        $userBuilder->select('customer_id');
        $userBuilder->where('id', $userId);
        $userQuery = $userBuilder->get();
        
        if ($userQuery === false || $userQuery->getNumRows() === 0) {
            return [];
        }
        
        $user = $userQuery->getRowArray();
        $userCustomerId = $user['customer_id'];
        
        // 사용자가 속한 본점 ID 찾기
        $headOfficeId = $this->getHeadOfficeId($userCustomerId);
        if (!$headOfficeId) {
            return [];
        }
        
        // 본점 하위의 모든 고객사 ID 목록
        $customerGroupIds = $this->getCustomerGroupIds($headOfficeId);
        
        // 해당 계층 레벨의 고객사만 필터링
        $builder = $this->builder();
        $builder->whereIn('id', $customerGroupIds);
        $builder->where('hierarchy_level', $hierarchyLevel);
        $builder->where('is_active', 1);
        $builder->orderBy('customer_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'CustomerHierarchyModel: Failed to get customers by user group');
            return [];
        }
        
        return $query->getResultArray();
    }
}
