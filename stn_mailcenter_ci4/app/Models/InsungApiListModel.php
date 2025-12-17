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
}


