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
        'api_gbn'
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
}


