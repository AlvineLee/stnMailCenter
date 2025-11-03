<?php

namespace App\Models;

use CodeIgniter\Model;

class UserServicePermissionModel extends Model
{
    protected $table = 'tbl_user_service_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
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

    protected $validationRules = [
        'user_id' => 'required|integer',
        'service_type_id' => 'required|integer',
        'is_enabled' => 'permit_empty|in_list[0,1]',
        'max_daily_orders' => 'permit_empty|integer',
        'max_monthly_orders' => 'permit_empty|integer'
    ];

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
     * 특정 서비스 타입에 대한 모든 사용자 권한 비활성화
     */
    public function deactivatePermissionsByServiceType($serviceTypeId)
    {
        return $this->builder()
                   ->where('service_type_id', $serviceTypeId)
                   ->where('is_enabled', 1)
                   ->update(['is_enabled' => 0]);
    }

    /**
     * 사용자별 서비스 권한 조회 (카테고리별 그룹화)
     */
    public function getUserServicePermissionsGrouped($userId)
    {
        $builder = $this->builder();
        $builder->select('
            usp.*,
            st.service_code,
            st.service_name,
            st.service_category,
            st.sort_order,
            st.is_active as service_is_active
        ');
        $builder->from('tbl_user_service_permissions usp');
        $builder->join('tbl_service_types st', 'usp.service_type_id = st.id', 'left');
        $builder->where('usp.user_id', $userId);
        $builder->orderBy('st.service_category', 'ASC');
        $builder->orderBy('st.sort_order', 'ASC');
        $builder->orderBy('st.service_name', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'UserServicePermissionModel: Failed to get user service permissions grouped');
            return [];
        }
        
        $permissions = $query->getResultArray();
        
        // 카테고리별로 그룹화
        $grouped = [];
        foreach ($permissions as $permission) {
            $category = $permission['service_category'] ?? '기타';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }

    /**
     * 사용자별 서비스 권한 목록 조회
     */
    public function getUserServicePermissions($userId)
    {
        $builder = $this->builder();
        $builder->select('
            usp.*,
            st.service_code,
            st.service_name,
            st.service_category,
            st.is_active as service_is_active
        ');
        $builder->from('tbl_user_service_permissions usp');
        $builder->join('tbl_service_types st', 'usp.service_type_id = st.id', 'left');
        $builder->where('usp.user_id', $userId);
        $builder->orderBy('st.service_category', 'ASC');
        $builder->orderBy('st.sort_order', 'ASC');
        
        $query = $builder->get();
        if ($query === false) {
            log_message('error', 'UserServicePermissionModel: Failed to get user service permissions');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 특정 사용자-서비스 권한 조회
     */
    public function getUserServicePermission($userId, $serviceTypeId)
    {
        return $this->where('user_id', $userId)
                   ->where('service_type_id', $serviceTypeId)
                   ->first();
    }

    /**
     * 사용자별 서비스 권한 일괄 업데이트
     */
    public function batchUpdateUserPermissions($userId, $statusUpdates)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            foreach ($statusUpdates as $update) {
                $serviceTypeId = $update['service_type_id'] ?? null;
                $isEnabled = $update['is_enabled'] ?? 0;
                
                if ($serviceTypeId) {
                    // 기존 권한 확인
                    $existing = $this->getUserServicePermission($userId, $serviceTypeId);
                    
                    if ($existing) {
                        // 업데이트
                        $this->update($existing['id'], [
                            'is_enabled' => $isEnabled ? 1 : 0,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        // 새로 생성
                        $this->insert([
                            'user_id' => $userId,
                            'service_type_id' => $serviceTypeId,
                            'is_enabled' => $isEnabled ? 1 : 0,
                            'max_daily_orders' => 0,
                            'max_monthly_orders' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
            
            $db->transComplete();
            
            return $db->transStatus() !== false;
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'UserServicePermissionModel: Batch update failed - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 사용자의 모든 서비스 권한 활성화
     */
    public function activateAllUserServices($userId)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // 모든 활성 서비스 타입 조회
            $serviceTypeModel = new \App\Models\ServiceTypeModel();
            $allServiceTypes = $serviceTypeModel->where('is_active', 1)->findAll();
            
            if (empty($allServiceTypes)) {
                // 서비스 타입이 없으면 기존 권한만 활성화
                $result = $this->builder()
                              ->where('user_id', $userId)
                              ->update(['is_enabled' => 1]);
                
                $db->transComplete();
                return $result !== false;
            }
            
            // 각 서비스 타입에 대해 권한 확인 및 활성화
            foreach ($allServiceTypes as $serviceType) {
                $existing = $this->getUserServicePermission($userId, $serviceType['id']);
                
                if ($existing) {
                    // 기존 권한 활성화
                    $this->update($existing['id'], [
                        'is_enabled' => 1,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // 권한이 없으면 생성하고 활성화 상태로
                    $this->insert([
                        'user_id' => $userId,
                        'service_type_id' => $serviceType['id'],
                        'is_enabled' => 1,
                        'max_daily_orders' => 0,
                        'max_monthly_orders' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            $db->transComplete();
            
            return $db->transStatus() !== false;
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'UserServicePermissionModel: activateAllUserServices failed - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 사용자의 모든 서비스 권한 비활성화
     */
    public function deactivateAllUserServices($userId)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // 모든 활성 서비스 타입 조회
            $serviceTypeModel = new \App\Models\ServiceTypeModel();
            $allServiceTypes = $serviceTypeModel->where('is_active', 1)->findAll();
            
            if (empty($allServiceTypes)) {
                // 서비스 타입이 없으면 기존 권한만 비활성화
                $result = $this->builder()
                              ->where('user_id', $userId)
                              ->update(['is_enabled' => 0]);
                
                $db->transComplete();
                return $result !== false;
            }
            
            // 각 서비스 타입에 대해 권한 확인 및 비활성화
            foreach ($allServiceTypes as $serviceType) {
                $existing = $this->getUserServicePermission($userId, $serviceType['id']);
                
                if ($existing) {
                    // 기존 권한 비활성화
                    $this->update($existing['id'], [
                        'is_enabled' => 0,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // 권한이 없으면 생성하고 비활성화 상태로
                    $this->insert([
                        'user_id' => $userId,
                        'service_type_id' => $serviceType['id'],
                        'is_enabled' => 0,
                        'max_daily_orders' => 0,
                        'max_monthly_orders' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            $db->transComplete();
            
            return $db->transStatus() !== false;
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'UserServicePermissionModel: deactivateAllUserServices failed - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 사용자의 서비스 권한 삭제
     */
    public function deleteUserServicePermission($userId, $serviceTypeId)
    {
        return $this->where('user_id', $userId)
                   ->where('service_type_id', $serviceTypeId)
                   ->delete();
    }
}

