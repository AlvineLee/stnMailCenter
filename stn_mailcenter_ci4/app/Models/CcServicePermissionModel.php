<?php

namespace App\Models;

use CodeIgniter\Model;

class CcServicePermissionModel extends Model
{
    protected $table = 'tbl_cc_service_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'cc_code',
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

    /**
     * 콜센터별 서비스 권한 조회
     */
    public function getCcServicePermissions($ccCode)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_cc_service_permissions csp');
        
        $builder->select('
            csp.*,
            st.service_code,
            st.service_name,
            st.service_category,
            st.is_active as service_is_active
        ');
        
        $builder->join('tbl_service_types st', 'csp.service_type_id = st.id', 'left');
        $builder->where('csp.cc_code', $ccCode);
        $builder->orderBy('st.service_category', 'ASC');
        $builder->orderBy('st.sort_order', 'ASC');
        $builder->orderBy('st.service_name', 'ASC');
        
        $query = $builder->get();
        
        if ($query === false) {
            log_message('error', 'CcServicePermissionModel: Failed to get cc service permissions');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 콜센터별 서비스 권한 일괄 저장/업데이트
     */
    public function batchUpdateCcServicePermissions($ccCode, $permissions)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            foreach ($permissions as $permission) {
                $serviceTypeId = $permission['service_type_id'] ?? null;
                $isEnabled = isset($permission['is_enabled']) ? (bool)$permission['is_enabled'] : false;
                
                if (!$serviceTypeId) {
                    continue;
                }
                
                // 기존 권한 확인
                $existing = $this->where('cc_code', $ccCode)
                               ->where('service_type_id', $serviceTypeId)
                               ->first();
                
                if ($existing) {
                    // 업데이트
                    $this->update($existing['id'], [
                        'is_enabled' => $isEnabled ? 1 : 0,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // 생성
                    $this->insert([
                        'cc_code' => $ccCode,
                        'service_type_id' => $serviceTypeId,
                        'is_enabled' => $isEnabled ? 1 : 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                log_message('error', 'CcServicePermissionModel: Transaction failed');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'CcServicePermissionModel: ' . $e->getMessage());
            $db->transRollback();
            return false;
        }
    }

    /**
     * 콜센터별 서비스 권한 삭제
     */
    public function deleteCcServicePermissions($ccCode)
    {
        return $this->where('cc_code', $ccCode)->delete();
    }

    /**
     * 모든 콜센터에 서비스 권한 일괄 적용 (마스터 설정 반영)
     */
    public function batchUpdateAllCcServicePermissions($permissions)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // 모든 콜센터 목록 조회
            $ccList = $db->table('tbl_cc_list')
                        ->select('cc_code')
                        ->get()
                        ->getResultArray();
            
            if (empty($ccList)) {
                $db->transComplete();
                return true; // 콜센터가 없으면 성공으로 처리
            }
            
            // 각 콜센터에 대해 권한 적용
            foreach ($ccList as $cc) {
                $ccCode = $cc['cc_code'];
                
                foreach ($permissions as $permission) {
                    $serviceTypeId = $permission['service_type_id'] ?? null;
                    $isEnabled = isset($permission['is_enabled']) ? (bool)$permission['is_enabled'] : false;
                    
                    if (!$serviceTypeId) {
                        continue;
                    }
                    
                    // 기존 권한 확인
                    $existing = $this->where('cc_code', $ccCode)
                                   ->where('service_type_id', $serviceTypeId)
                                   ->first();
                    
                    if ($existing) {
                        // 업데이트
                        $this->update($existing['id'], [
                            'is_enabled' => $isEnabled ? 1 : 0,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        // 생성
                        $this->insert([
                            'cc_code' => $ccCode,
                            'service_type_id' => $serviceTypeId,
                            'is_enabled' => $isEnabled ? 1 : 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                log_message('error', 'CcServicePermissionModel: Transaction failed in batchUpdateAllCcServicePermissions');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'CcServicePermissionModel: ' . $e->getMessage());
            $db->transRollback();
            return false;
        }
    }
}

