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
        'cc_apicode',      // tbl_api_list.idx 연결
        'cc_quickid',      // 퀵 아이디
        'cc_telno',        // 연락처
        'cc_memo',         // 메모
        'cc_dongname',     // 기준동명
        'cc_addr',         // 주소
        'cc_addr_detail',  // 상세주소
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
     * 모든 콜센터 목록 조회 (api_gbn='M'인 API와 연결된 콜센터만)
     */
    public function getAllCcList()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_cc_list c');
        $builder->select('c.*');
        $builder->join('tbl_api_list a', 'c.cc_apicode = a.idx', 'inner');
        $builder->where("TRIM(a.api_gbn) = 'M'");
        $builder->orderBy('c.cc_code', 'ASC');

        $query = $builder->get();
        return $query !== false ? $query->getResultArray() : [];
    }

    /**
     * 특정 콜센터 정보 조회
     */
    public function getCcByCode($ccCode)
    {
        return $this->where('cc_code', $ccCode)->first();
    }

    /**
     * 활성화된 콜센터 목록 조회 (api_gbn='M'인 API와 연결된 콜센터만)
     */
    public function getActiveCcList()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_cc_list c');
        $builder->select('c.*');
        $builder->join('tbl_api_list a', 'c.cc_apicode = a.idx', 'inner');
        $builder->where("TRIM(a.api_gbn) = 'M'");
        $builder->where('c.is_active', 1);
        $builder->orderBy('c.cc_code', 'ASC');

        $query = $builder->get();
        return $query !== false ? $query->getResultArray() : [];
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

    /**
     * 콜센터 목록 조회 (API 정보 조인, 검색 필터, 페이징)
     */
    public function getCcListWithApiInfo($filters = [], $page = 1, $perPage = 20)
    {
        $db = \Config\Database::connect();

        $builder = $db->table('tbl_cc_list c');
        $builder->select('
            c.*,
            a.idx as api_idx,
            a.cccode as api_cccode,
            a.api_name,
            a.api_code,
            a.mcode
        ');
        $builder->join('tbl_api_list a', 'c.cc_apicode = a.idx', 'inner');
        $builder->where("TRIM(a.api_gbn) = 'M'");

        // 검색 필터 적용
        if (!empty($filters['cc_code'])) {
            $builder->like('c.cc_code', $filters['cc_code']);
        }
        if (!empty($filters['cc_name'])) {
            $builder->like('c.cc_name', $filters['cc_name']);
        }
        if (!empty($filters['api_cccode'])) {
            $builder->where('a.cccode', $filters['api_cccode']);
        }

        // 전체 건수 조회
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults(false);

        // 디버그: 실행된 쿼리 로그
        log_message('debug', 'getCcListWithApiInfo - totalCount: ' . $totalCount);
        log_message('debug', 'getCcListWithApiInfo - Last Query: ' . $db->getLastQuery());

        // 페이징 적용
        $offset = ($page - 1) * $perPage;
        $builder->orderBy('c.idx', 'ASC');
        $builder->limit($perPage, $offset);

        $query = $builder->get();
        $ccList = $query !== false ? $query->getResultArray() : [];

        // 각 콜센터별 소속 고객사 개수 조회
        foreach ($ccList as &$cc) {
            $companyCount = $db->table('tbl_company_list')
                              ->where('cc_idx', $cc['idx'])
                              ->countAllResults();
            $cc['company_count'] = $companyCount;
        }

        return [
            'cc_list' => $ccList,
            'total_count' => $totalCount,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalCount / $perPage),
                'total_count' => $totalCount
            ]
        ];
    }

    /**
     * 콜센터 코드 중복 체크
     */
    public function isCodeExists($ccCode, $excludeIdx = null)
    {
        $builder = $this->where('cc_code', $ccCode);
        if ($excludeIdx) {
            $builder->where('idx !=', $excludeIdx);
        }
        return $builder->countAllResults() > 0;
    }

    /**
     * 퀵아이디 중복 체크
     */
    public function isQuickIdExists($quickId, $excludeIdx = null)
    {
        $builder = $this->where('cc_quickid', $quickId);
        if ($excludeIdx) {
            $builder->where('idx !=', $excludeIdx);
        }
        return $builder->countAllResults() > 0;
    }
}

