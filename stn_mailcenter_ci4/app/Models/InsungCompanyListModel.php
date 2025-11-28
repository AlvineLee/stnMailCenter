<?php

namespace App\Models;

use CodeIgniter\Model;

class InsungCompanyListModel extends Model
{
    protected $table = 'tbl_company_list';
    protected $primaryKey = 'comp_idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'comp_code',
        'comp_name',
        'cc_idx',
        'business_number',
        'comp_owner',
        'contact_phone',
        'contact_email',
        'address',
        'logo_path',
        'description',
        'is_active',
        'comp_tel',
        'comp_memo',
        'comp_dong',
        'comp_addr',
        'comp_addr_detail'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * 콜센터와 조인하여 모든 고객사 목록 조회 (페이징 지원, 검색 지원)
     */
    public function getAllCompanyListWithCc($ccCode = null, $searchName = null, $page = 1, $perPage = 20)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_company_list c');
        
        $builder->select('
            c.*,
            cc.cc_code,
            cc.cc_name
        ');
        
        $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'left');
        
        if ($ccCode && $ccCode !== 'all') {
            $builder->where('cc.cc_code', $ccCode);
        }
        
        // 고객사명 검색
        if ($searchName && trim($searchName) !== '') {
            $builder->like('c.comp_name', trim($searchName));
        }
        
        // 총 개수 조회 (페이징용)
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults();
        
        // 정렬 및 페이징 (고객사명 오름차순)
        $builder->orderBy('c.comp_name', 'ASC');
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);
        
        $query = $builder->get();
        
        // 디버깅: 실제 실행된 쿼리 로그
        log_message('debug', 'InsungCompanyListModel::getAllCompanyListWithCc - SQL: ' . $builder->getCompiledSelect(false));
        
        if ($query === false) {
            log_message('error', 'InsungCompanyListModel: Failed to get company list with cc');
            return [
                'companies' => [],
                'total_count' => 0
            ];
        }
        
        $result = $query->getResultArray();
        
        // 디버깅: 결과 개수 로그
        log_message('debug', 'InsungCompanyListModel::getAllCompanyListWithCc - Result count: ' . count($result));
        
        return [
            'companies' => $result,
            'total_count' => $totalCount
        ];
    }

    /**
     * 특정 고객사 정보 조회
     */
    public function getCompanyByCode($compCode)
    {
        // comp_code는 문자열일 수 있으므로 타입 변환
        $compCode = (string)$compCode;
        
        // idx를 명시적으로 선택하여 조회
        $result = $this->select('*')->where('comp_code', $compCode)->first();
        
        // 디버깅: 결과 확인
        if ($result) {
            log_message('debug', 'InsungCompanyListModel::getCompanyByCode - Found company for comp_code: ' . $compCode . ', keys: ' . json_encode(array_keys($result)));
        } else {
            log_message('debug', 'InsungCompanyListModel::getCompanyByCode - Company not found for comp_code: ' . $compCode);
        }
        
        return $result;
    }
}

