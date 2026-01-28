<?php

namespace App\Models;

use CodeIgniter\Model;

class MailroomDriverModel extends Model
{
    protected $table = 'tbl_mailroom_drivers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'driver_code',
        'driver_name',
        'phone',
        'email',
        'fcm_token',
        'building_id',
        'status'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 활성 기사 목록
     */
    public function getActiveDrivers()
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists($this->table)) {
                return [];
            }

            $fields = $db->getFieldNames($this->table);
            if (in_array('status', $fields)) {
                return $this->where('status', 'active')
                    ->orderBy('driver_name', 'ASC')
                    ->findAll();
            } else {
                return $this->orderBy('driver_name', 'ASC')->findAll();
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 기사 코드로 조회
     */
    public function getByCode($driverCode)
    {
        return $this->where('driver_code', $driverCode)->first();
    }

    /**
     * 기사별 담당 건물 목록
     */
    public function getDriverBuildings($driverId)
    {
        $db = \Config\Database::connect();

        // 테이블 존재 여부 확인
        if (!$db->tableExists('tbl_mailroom_driver_buildings')) {
            return [];
        }

        $result = $db->table('tbl_mailroom_driver_buildings db')
            ->select('db.building_id as id, b.building_code, b.building_name')
            ->join('tbl_mailroom_buildings b', 'b.id = db.building_id')
            ->where('db.driver_id', $driverId)
            ->get();

        if ($result === false) {
            return [];
        }

        return $result->getResultArray();
    }

    /**
     * FCM 토큰 업데이트
     */
    public function updateFcmToken($driverId, $token)
    {
        return $this->update($driverId, ['fcm_token' => $token]);
    }
}