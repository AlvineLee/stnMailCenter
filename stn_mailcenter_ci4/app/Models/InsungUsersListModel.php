<?php

namespace App\Models;

use CodeIgniter\Model;

class InsungUsersListModel extends Model
{
    protected $table = 'tbl_users_list';
    protected $primaryKey = 'idx'; // 테이블의 기본키가 idx인 것으로 가정
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'user_pass',
        'user_name',
        'user_dept',
        'user_tel1',
        'user_tel2',
        'user_addr',
        'user_addr_detail',
        'user_company',
        'user_type',
        'user_ccode',
        'user_sido',
        'user_gungu',
        'user_dong',
        'user_lon',
        'user_lat'
    ];

    protected $useTimestamps = false; // tbl_users_list에 created_at, updated_at 컬럼이 없음
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * daumdata 로그인 인증
     * user_id와 user_pass로 인증하고, company_list와 cc_list 정보를 함께 조회
     */
    public function authenticate($userId, $password)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_users_list a');
        
        $builder->select('
            a.*,
            b.comp_code,
            b.comp_name,
            b.comp_owner,
            b.cc_idx,
            b.logo_path,
            d.mcode as m_code,
            d.cccode as cc_code,
            d.token,
            d.ckey
        ');
        
        $builder->join('tbl_company_list b', 'a.user_company = b.comp_code', 'left');
        $builder->join('tbl_cc_list c', 'b.cc_idx = c.idx', 'left');
        // collation 충돌 해결: CONVERT를 사용하여 collation 통일
        $builder->join('tbl_api_list d', 'CONVERT(c.cc_code USING utf8mb4) COLLATE utf8mb4_general_ci = CONVERT(d.api_code USING utf8mb4) COLLATE utf8mb4_general_ci', 'left', false);
        $builder->where('a.user_id', $userId);
        $builder->where('a.user_pass', $password); // 비밀번호는 평문으로 저장되어 있다고 가정
        
        $query = $builder->get();
        
        if ($query === false) {
            log_message('error', 'InsungUsersListModel: Failed to authenticate user');
            return false;
        }
        
        $user = $query->getRowArray();
        
        if ($user) {
            return $user;
        }
        
        return false;
    }

    /**
     * 사용자 ID로 사용자 정보 조회 (company, cc 정보 포함)
     */
    public function getUserWithCompanyInfo($userId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_users_list a');
        
        $builder->select('
            a.*,
            b.comp_code,
            b.comp_name,
            b.comp_owner,
            b.cc_idx,
            b.logo_path,
            d.mcode as m_code,
            d.cccode as cc_code,
            d.token,
            d.ckey
        ');
        
        $builder->join('tbl_company_list b', 'a.user_company = b.comp_code', 'left');
        $builder->join('tbl_cc_list c', 'b.cc_idx = c.idx', 'left');
        // collation 충돌 해결: CONVERT를 사용하여 collation 통일
        $builder->join('tbl_api_list d', 'CONVERT(c.cc_code USING utf8mb4) COLLATE utf8mb4_general_ci = CONVERT(d.api_code USING utf8mb4) COLLATE utf8mb4_general_ci', 'left', false);
        $builder->where('a.user_id', $userId);
        
        $query = $builder->get();
        
        if ($query === false) {
            log_message('error', 'InsungUsersListModel: Failed to get user with company info');
            return null;
        }
        
        return $query->getRowArray();
    }

    /**
     * 콜센터와 고객사와 조인하여 모든 회원 목록 조회 (페이징 및 필터링 지원)
     * 성능 최적화: WHERE 조건을 먼저 적용하고 JOIN
     */
    public function getAllUserListWithFilters($ccCode = null, $compCode = null, $searchName = null, $searchId = null, $page = 1, $perPage = 20)
    {
        $db = \Config\Database::connect();
        
        // 성능 최적화: COUNT 쿼리 최적화
        $countBuilder = $db->table('tbl_users_list u');
        
        // WHERE 조건 적용
        if ($compCode) {
            $countBuilder->where('u.user_company', $compCode);
        }
        
        if ($searchName && trim($searchName) !== '') {
            $countBuilder->like('u.user_name', trim($searchName));
        }
        
        if ($searchId && trim($searchId) !== '') {
            $countBuilder->like('u.user_id', trim($searchId));
        }
        
        // ccCode 필터링을 위해 JOIN 필요
        if ($ccCode) {
            $countBuilder->join('tbl_company_list c_count', 'u.user_company = c_count.comp_code', 'inner');
            $countBuilder->join('tbl_cc_list cc_count', 'c_count.cc_idx = cc_count.idx', 'inner');
            $countBuilder->where('cc_count.cc_code', $ccCode);
            // 중복 제거를 위해 DISTINCT COUNT 사용
            $countBuilder->distinct();
            $countBuilder->select('u.idx');
            $totalCount = $countBuilder->countAllResults(false);
        } else {
            // JOIN 없이 COUNT (더 빠름)
            $totalCount = $countBuilder->countAllResults();
        }
        
        // 실제 데이터 조회 쿼리
        $builder = $db->table('tbl_users_list u');
        
        // WHERE 조건 적용
        if ($compCode) {
            $builder->where('u.user_company', $compCode);
        }
        
        if ($searchName && trim($searchName) !== '') {
            $builder->like('u.user_name', trim($searchName));
        }
        
        if ($searchId && trim($searchId) !== '') {
            $builder->like('u.user_id', trim($searchId));
        }
        
        // 필요한 컬럼만 SELECT (성능 최적화)
        // 중복 제거를 위해 DISTINCT 사용
        $builder->select('
            u.idx,
            u.user_id,
            u.user_name,
            u.user_dept,
            u.user_tel1,
            u.user_tel2,
            u.user_addr,
            u.user_addr_detail,
            u.user_company,
            u.user_type,
            u.user_ccode,
            u.user_sido,
            u.user_gungu,
            u.user_dong,
            u.user_lon,
            u.user_lat,
            c.comp_code,
            c.comp_name,
            c.comp_owner,
            cc.cc_code,
            cc.cc_name
        ');
        
        // DISTINCT 추가 (중복 제거)
        $builder->distinct();
        
        // INNER JOIN 사용
        $builder->join('tbl_company_list c', 'u.user_company = c.comp_code', 'inner');
        $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
        
        // ccCode 필터
        if ($ccCode) {
            $builder->where('cc.cc_code', $ccCode);
        }
        
        // 정렬 및 페이징
        $builder->orderBy('cc.cc_code', 'ASC');
        $builder->orderBy('c.comp_code', 'ASC');
        $builder->orderBy('u.user_id', 'ASC');
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);
        
        $query = $builder->get();
        
        if ($query === false) {
            log_message('error', 'InsungUsersListModel: Failed to get user list with filters');
            return [
                'users' => [],
                'total_count' => 0,
                'pagination' => []
            ];
        }
        
        // 페이징 정보 계산 (공통 헬퍼 함수 사용)
        helper('pagination');
        $pagination = calculatePagination($totalCount, $page, $perPage);
        
        return [
            'users' => $query->getResultArray(),
            'total_count' => $totalCount,
            'pagination' => $pagination
        ];
    }
}

