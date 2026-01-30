<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyMailroomPermissionModel extends Model
{
    protected $table = 'tbl_company_mailroom_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'comp_code',
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
     * 거래처의 메일룸 권한 확인
     * 레코드가 존재하면 권한 있음, 없으면 권한 없음
     */
    public function hasPermission($compCode)
    {
        $record = $this->where('comp_code', $compCode)->first();
        return !empty($record);
    }

    /**
     * 거래처에 메일룸 권한 부여
     */
    public function grantPermission($compCode)
    {
        // 이미 권한이 있는지 확인
        if ($this->hasPermission($compCode)) {
            return true;
        }

        // 새 권한 레코드 생성
        return $this->insert([
            'comp_code' => $compCode,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 거래처의 메일룸 권한 제거
     */
    public function revokePermission($compCode)
    {
        return $this->where('comp_code', $compCode)->delete();
    }

    /**
     * 거래처 메일룸 권한 설정 (true=권한부여, false=권한제거)
     */
    public function setPermission($compCode, $hasPermission)
    {
        if ($hasPermission) {
            return $this->grantPermission($compCode);
        } else {
            return $this->revokePermission($compCode);
        }
    }

    /**
     * 메일룸 권한이 있는 모든 거래처 목록 조회
     */
    public function getAllPermittedCompanies()
    {
        return $this->findAll();
    }

    /**
     * 특정 거래처 코드 목록에 대한 메일룸 권한 일괄 조회
     * 반환: ['comp_code' => true/false, ...]
     */
    public function getPermissionsForCompanies($compCodes)
    {
        if (empty($compCodes)) {
            return [];
        }

        $permitted = $this->whereIn('comp_code', $compCodes)->findAll();
        $permissionMap = [];

        // 모든 거래처를 false로 초기화
        foreach ($compCodes as $code) {
            $permissionMap[$code] = false;
        }

        // 권한이 있는 거래처를 true로 설정
        foreach ($permitted as $record) {
            $permissionMap[$record['comp_code']] = true;
        }

        return $permissionMap;
    }
}