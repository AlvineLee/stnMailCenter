<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceTypeModel extends Model
{
    protected $table = 'tbl_service_types';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'service_code',
        'service_name',
        'service_category',
        'description',
        'is_active',
        'sort_order',
        'is_external_link',
        'external_url'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'service_code' => 'required|max_length[50]',
        'service_name' => 'required|max_length[100]',
        'service_category' => 'required|max_length[50]',
        'description' => 'permit_empty',
        'is_active' => 'permit_empty|in_list[0,1]',
        'sort_order' => 'permit_empty|integer',
        'is_external_link' => 'permit_empty|in_list[0,1]',
        'external_url' => 'permit_empty|max_length[500]|valid_url'
    ];

    protected $validationMessages = [
        'service_code' => [
            'required' => '서비스 코드는 필수입니다.',
            'max_length' => '서비스 코드는 50자를 초과할 수 없습니다.',
            'is_unique' => '이미 사용 중인 서비스 코드입니다.'
        ],
        'service_name' => [
            'required' => '서비스명은 필수입니다.',
            'max_length' => '서비스명은 100자를 초과할 수 없습니다.'
        ],
        'service_category' => [
            'required' => '서비스 카테고리는 필수입니다.',
            'max_length' => '서비스 카테고리는 50자를 초과할 수 없습니다.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 활성 서비스 타입 목록 조회
     */
    public function getActiveServiceTypes()
    {
        return $this->where('is_active', 1)
                   ->orderBy('sort_order', 'ASC')
                   ->orderBy('service_name', 'ASC')
                   ->findAll();
    }

    /**
     * 카테고리별 서비스 타입 조회
     */
    public function getServiceTypesByCategory($category)
    {
        return $this->where('service_category', $category)
                   ->where('is_active', 1)
                   ->orderBy('sort_order', 'ASC')
                   ->orderBy('service_name', 'ASC')
                   ->findAll();
    }

    /**
     * 서비스 코드로 서비스 타입 조회
     */
    public function getByServiceCode($serviceCode)
    {
        return $this->where('service_code', $serviceCode)
                   ->where('is_active', 1)
                   ->first();
    }

    /**
     * 서비스 타입 활성화/비활성화
     */
    public function toggleServiceStatus($serviceId, $status)
    {
        return $this->update($serviceId, ['is_active' => $status ? 1 : 0]);
    }

    /**
     * 서비스 타입 검색
     */
    public function searchServiceTypes($searchTerm, $category = null)
    {
        $builder = $this->builder();
        $builder->where('is_active', 1);
        
        if ($category) {
            $builder->where('service_category', $category);
        }
        
        $builder->groupStart();
        $builder->like('service_code', $searchTerm);
        $builder->orLike('service_name', $searchTerm);
        $builder->orLike('description', $searchTerm);
        $builder->groupEnd();
        
        $builder->orderBy('sort_order', 'ASC');
        $builder->orderBy('service_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'ServiceTypeModel: Failed to search service types');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 서비스 카테고리 목록 조회
     */
    public function getServiceCategories()
    {
        $builder = $this->builder();
        $builder->select('DISTINCT service_category');
        $builder->where('is_active', 1);
        $builder->orderBy('service_category', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'ServiceTypeModel: Failed to get service categories');
            return [];
        }
        
        $result = $query->getResultArray();
        
        return array_column($result, 'service_category');
    }

    /**
     * 서비스 타입 통계
     */
    public function getServiceTypeStats()
    {
        $builder = $this->builder();
        $builder->select('
            service_category,
            COUNT(*) as total_count,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_count
        ');
        
        $builder->groupBy('service_category');
        $builder->orderBy('service_category', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'ServiceTypeModel: Failed to get service type stats');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 카테고리별로 그룹화된 서비스 타입 조회 (관리 페이지용)
     */
    public function getServiceTypesGroupedByCategory()
    {
        $builder = $this->builder();
        $builder->orderBy('service_category', 'ASC');
        $builder->orderBy('sort_order', 'ASC');
        $builder->orderBy('service_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'ServiceTypeModel: Failed to get service types grouped by category');
            return [];
        }
        
        $allServices = $query->getResultArray();
        
        // 카테고리별로 그룹화
        $grouped = [];
        foreach ($allServices as $service) {
            $category = $service['service_category'] ?? '기타';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $service;
        }
        
        return $grouped;
    }

    /**
     * 모든 서비스 타입 조회 (관리 페이지용 - 활성/비활성 모두)
     */
    public function getAllServiceTypes()
    {
        return $this->orderBy('service_category', 'ASC')
                   ->orderBy('sort_order', 'ASC')
                   ->orderBy('service_name', 'ASC')
                   ->findAll();
    }

    /**
     * 전체 통계 조회
     */
    public function getOverallStats()
    {
        $total = $this->countAllResults(false);
        $active = $this->where('is_active', 1)->countAllResults(false);
        $inactive = $total - $active;
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive
        ];
    }

    /**
     * 일괄 상태 업데이트
     */
    public function batchUpdateStatus($serviceIds, $isActive)
    {
        if (empty($serviceIds)) {
            return false;
        }
        
        return $this->builder()
                   ->whereIn('id', $serviceIds)
                   ->update(['is_active' => $isActive ? 1 : 0]);
    }

    /**
     * 모든 서비스 비활성화
     */
    public function deactivateAll()
    {
        return $this->builder()->update(['is_active' => 0]);
    }

    /**
     * 일괄 순서 업데이트 (드래그 앤 드롭)
     */
    public function batchUpdateSortOrder($sortUpdates)
    {
        if (empty($sortUpdates) || !is_array($sortUpdates)) {
            return false;
        }
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            foreach ($sortUpdates as $update) {
                $serviceId = $update['service_id'] ?? null;
                $sortOrder = $update['sort_order'] ?? 0;
                
                if ($serviceId) {
                    $this->update($serviceId, ['sort_order' => (int)$sortOrder]);
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                log_message('error', 'ServiceTypeModel: Failed to update sort order');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'ServiceTypeModel: Exception in batchUpdateSortOrder - ' . $e->getMessage());
            $db->transRollback();
            return false;
        }
    }

    /**
     * 카테고리명 목록 조회 (중복 제거)
     */
    public function getDistinctCategories()
    {
        $builder = $this->builder();
        $builder->distinct();
        $builder->select('service_category');
        $builder->where('service_category IS NOT NULL');
        $builder->where('service_category !=', '');
        $builder->orderBy('service_category', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'ServiceTypeModel: Failed to get distinct categories');
            return [];
        }
        
        $result = $query->getResultArray();
        return array_column($result, 'service_category');
    }
}
