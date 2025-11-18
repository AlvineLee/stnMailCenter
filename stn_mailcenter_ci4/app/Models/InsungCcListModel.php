<?php

namespace App\Models;

use CodeIgniter\Model;

class InsungCcListModel extends Model
{
    protected $table = 'tbl_cc_list';
    protected $primaryKey = 'idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'cc_code',
        'cc_name',
        'contact_phone',
        'contact_email',
        'address',
        'logo_path',
        'description',
        'is_active',
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
     * 모든 콜센터 목록 조회
     */
    public function getAllCcList()
    {
        return $this->orderBy('cc_code', 'ASC')->findAll();
    }

    /**
     * 특정 콜센터 정보 조회
     */
    public function getCcByCode($ccCode)
    {
        return $this->where('cc_code', $ccCode)->first();
    }

    /**
     * 활성화된 콜센터 목록 조회
     */
    public function getActiveCcList()
    {
        return $this->where('is_active', 1)
                   ->orderBy('cc_code', 'ASC')
                   ->findAll();
    }

    /**
     * 모든 콜센터 목록 조회 (소속 고객사 개수 포함)
     */
    public function getAllCcListWithCompanyCount()
    {
        $ccList = $this->getAllCcList();
        
        // 각 콜센터별 소속 고객사 개수 조회
        $db = \Config\Database::connect();
        foreach ($ccList as &$cc) {
            $companyCount = $db->table('tbl_company_list c')
                              ->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner')
                              ->where('cc.cc_code', $cc['cc_code'])
                              ->countAllResults();
            $cc['company_count'] = $companyCount;
        }
        
        return $ccList;
    }
}

