<?php

namespace App\Models;

use CodeIgniter\Model;

class InsungApiListModel extends Model
{
    protected $table = 'tbl_api_list';
    protected $primaryKey = 'idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'mcode',
        'cccode',
        'ckey',
        'token',
        'api_code',
        'api_gbn',
        'api_name'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * API 정보 조회 (idx로)
     */
    public function getApiInfoByIdx($idx)
    {
        return $this->where('idx', $idx)->first();
    }

    /**
     * API 정보 조회 (api_code로)
     */
    public function getApiInfoByCode($apiCode)
    {
        return $this->where('api_code', $apiCode)->first();
    }

    /**
     * 토큰 업데이트
     */
    public function updateToken($idx, $token)
    {
        return $this->update($idx, ['token' => $token]);
    }

    /**
     * mcode와 cccode로 API 정보 조회
     */
    public function getApiInfoByMcodeCccode($mcode, $cccode)
    {
        return $this->where('mcode', $mcode)
                    ->where('cccode', $cccode)
                    ->first();
    }

    /**
     * cc_code로 API 정보 조회 (tbl_cc_list.cc_code와 tbl_api_list.api_code 매칭)
     * 
     * @param string $ccCode tbl_cc_list의 cc_code 값 (tbl_api_list.api_code와 매칭)
     * @return array|null
     */
    public function getApiInfoByCcCode($ccCode)
    {
        if (empty($ccCode)) {
            return null;
        }
        
        // tbl_api_list.api_code와 tbl_cc_list.cc_code를 매칭
        // 다른 코드에서 사용하는 방식과 동일하게 getApiInfoByCode 사용
        return $this->getApiInfoByCode($ccCode);
    }

    /**
     * mcode로 API 목록 조회
     *
     * @param string $mcode 마스터 코드
     * @return array
     */
    public function getApiListByMcode($mcode)
    {
        return $this->where('mcode', $mcode)
                    ->orderBy('idx', 'ASC')
                    ->findAll();
    }

    /**
     * 메인 API 목록 조회 (api_gbn='M')
     *
     * @return array
     */
    public function getMainApiList()
    {
        return $this->where("TRIM(api_gbn) = 'M'")
                    ->orderBy('idx', 'ASC')
                    ->findAll();
    }

    /**
     * API 목록 조회 (검색 필터, 페이징)
     *
     * @param array $filters 검색 필터
     * @param int $page 현재 페이지
     * @param int $perPage 페이지당 건수
     * @return array
     */
    public function getApiListWithPagination($filters = [], $page = 1, $perPage = 20)
    {
        $builder = $this->builder();
        $builder->select('*');

        // 검색 필터 적용
        if (!empty($filters['api_gbn'])) {
            $builder->where('TRIM(api_gbn)', trim($filters['api_gbn']));
        }
        if (!empty($filters['cccode'])) {
            $builder->like('cccode', $filters['cccode']);
        }
        if (!empty($filters['api_name'])) {
            $builder->like('api_name', $filters['api_name']);
        }

        // 전체 건수 조회
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults(false);

        // 페이징 적용
        $offset = ($page - 1) * $perPage;
        $builder->orderBy('idx', 'ASC');
        $builder->limit($perPage, $offset);

        $query = $builder->get();
        $apiList = $query !== false ? $query->getResultArray() : [];

        return [
            'api_list' => $apiList,
            'total_count' => $totalCount,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalCount / $perPage),
                'total_count' => $totalCount
            ]
        ];
    }
}


