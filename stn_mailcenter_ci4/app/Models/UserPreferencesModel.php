<?php

namespace App\Models;

use CodeIgniter\Model;

class UserPreferencesModel extends Model
{
    protected $table = 'tbl_user_preferences';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_type',
        'user_id',
        'list_type',
        'column_order',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 사용자별 리스트 컬럼 순서 조회
     * 
     * @param string $userType 사용자 타입 (daumdata, stn)
     * @param string $userId 사용자 ID
     * @param string $listType 리스트 타입 (delivery, history)
     * @return array|null 컬럼 순서 배열 또는 null
     */
    public function getColumnOrder($userType, $userId, $listType)
    {
        $preference = $this->where('user_type', $userType)
                          ->where('user_id', $userId)
                          ->where('list_type', $listType)
                          ->first();
        
        if (!$preference || empty($preference['column_order'])) {
            return null;
        }
        
        // JSON 문자열을 배열로 변환
        $columnOrder = is_string($preference['column_order']) 
            ? json_decode($preference['column_order'], true) 
            : $preference['column_order'];
        
        return is_array($columnOrder) ? $columnOrder : null;
    }

    /**
     * 사용자별 리스트 컬럼 순서 저장
     * 
     * @param string $userType 사용자 타입 (daumdata, stn)
     * @param string $userId 사용자 ID
     * @param string $listType 리스트 타입 (delivery, history)
     * @param array $columnOrder 컬럼 순서 배열
     * @return bool 저장 성공 여부
     */
    public function saveColumnOrder($userType, $userId, $listType, $columnOrder)
    {
        if (!is_array($columnOrder)) {
            return false;
        }
        
        // 기존 설정 확인
        $existing = $this->where('user_type', $userType)
                         ->where('user_id', $userId)
                         ->where('list_type', $listType)
                         ->first();
        
        $data = [
            'user_type' => $userType,
            'user_id' => $userId,
            'list_type' => $listType,
            'column_order' => json_encode($columnOrder)
        ];
        
        if ($existing) {
            // 업데이트
            return $this->update($existing['id'], $data) !== false;
        } else {
            // 신규 생성
            return $this->insert($data) !== false;
        }
    }
}


