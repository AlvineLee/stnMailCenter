<?php

namespace App\Models;

use CodeIgniter\Model;

class MailroomBuildingModel extends Model
{
    protected $table = 'tbl_mailroom_buildings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'comp_code',
        'building_code',
        'building_name',
        'address',
        'floor_count',
        'status'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 거래처코드로 활성 건물 목록
     */
    public function getActiveBuildingsByCompCode($compCode)
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists($this->table)) {
                return [];
            }

            $fields = $db->getFieldNames($this->table);
            $builder = $this->builder();

            // comp_code 필터
            if (in_array('comp_code', $fields)) {
                $builder->where('comp_code', $compCode);
            }

            // status 컬럼으로 활성 여부 필터
            if (in_array('status', $fields)) {
                $builder->where('status', 'active');
            } elseif (in_array('is_active', $fields)) {
                $builder->where('is_active', 1);
            }

            return $builder->orderBy('building_name', 'ASC')->get()->getResultArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 활성 건물 목록
     */
    public function getActiveBuildings()
    {
        try {
            // 테이블 존재 여부 확인
            $db = \Config\Database::connect();
            if (!$db->tableExists($this->table)) {
                return [];
            }

            // status 컬럼 존재 여부 확인
            $fields = $db->getFieldNames($this->table);
            if (in_array('status', $fields)) {
                return $this->where('status', 'active')
                    ->orderBy('building_name', 'ASC')
                    ->findAll();
            } elseif (in_array('is_active', $fields)) {
                // 이전 스키마 호환
                return $this->where('is_active', 1)
                    ->orderBy('building_name', 'ASC')
                    ->findAll();
            } else {
                return $this->orderBy('building_name', 'ASC')->findAll();
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 건물별 층 목록 포함
     */
    public function getBuildingWithFloors($buildingId)
    {
        $building = $this->find($buildingId);
        if ($building) {
            $floorModel = new MailroomFloorModel();
            $building['floors'] = $floorModel->getFloorsByBuilding($buildingId);
        }
        return $building;
    }
}