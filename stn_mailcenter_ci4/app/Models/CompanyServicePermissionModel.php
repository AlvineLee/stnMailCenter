<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyServicePermissionModel extends Model
{
    protected $table = 'tbl_company_service_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'comp_code',
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
     * 거래처별 서비스 권한 조회
     */
    public function getCompanyServicePermissions($compCode)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_company_service_permissions csp');
        
        $builder->select('
            csp.*,
            st.service_code,
            st.service_name,
            st.service_category,
            st.is_active as service_is_active
        ');
        
        $builder->join('tbl_service_types st', 'csp.service_type_id = st.id', 'left');
        $builder->where('csp.comp_code', $compCode);
        $builder->orderBy('st.service_category', 'ASC');
        $builder->orderBy('st.sort_order', 'ASC');
        $builder->orderBy('st.service_name', 'ASC');
        
        $query = $builder->get();
        
        if ($query === false) {
            log_message('error', 'CompanyServicePermissionModel: Failed to get company service permissions');
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 거래처별 서비스 권한 일괄 저장/업데이트
     */
    public function batchUpdateCompanyServicePermissions($compCode, $permissions)
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
                $existing = $this->where('comp_code', $compCode)
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
                        'comp_code' => $compCode,
                        'service_type_id' => $serviceTypeId,
                        'is_enabled' => $isEnabled ? 1 : 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                log_message('error', 'CompanyServicePermissionModel: Transaction failed');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'CompanyServicePermissionModel: ' . $e->getMessage());
            $db->transRollback();
            return false;
        }
    }

    /**
     * 거래처별 서비스 권한 삭제
     */
    public function deleteCompanyServicePermissions($compCode)
    {
        return $this->where('comp_code', $compCode)->delete();
    }
}

