<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingCompanyModel extends Model
{
    protected $table = 'tbl_shipping_companies';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'company_code',
        'company_name',
        'platform_code',
        'api_config',
        'is_active',
        'contract_start_date',
        'contract_end_date'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'company_code' => 'required|max_length[50]|is_unique[tbl_shipping_companies.company_code,id,{id}]',
        'company_name' => 'required|max_length[100]',
        'platform_code' => 'required|max_length[50]|is_unique[tbl_shipping_companies.platform_code,id,{id}]',
        'is_active' => 'permit_empty|in_list[0,1]',
        'contract_start_date' => 'permit_empty|valid_date',
        'contract_end_date' => 'permit_empty|valid_date'
    ];

    protected $validationMessages = [
        'company_code' => [
            'required' => '운송사 코드는 필수입니다.',
            'is_unique' => '이미 사용 중인 운송사 코드입니다.'
        ],
        'company_name' => [
            'required' => '운송사명은 필수입니다.'
        ]
    ];

    /**
     * 활성화된 운송사 목록 조회
     */
    public function getActiveCompanies()
    {
        return $this->where('is_active', 1)
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }

    /**
     * 모든 운송사 목록 조회 (관리자용)
     */
    public function getAllCompanies()
    {
        return $this->orderBy('company_name', 'ASC')
                   ->findAll();
    }
}

