<?php

namespace App\Models;

use CodeIgniter\Model;

class MailroomFloorModel extends Model
{
    protected $table = 'tbl_mailroom_floors';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'building_id',
        'floor_name',
        'floor_order',
        'description',
        'is_active'
    ];

    /**
     * 건물별 층 목록
     */
    public function getFloorsByBuilding($buildingId)
    {
        return $this->where('building_id', $buildingId)
            ->where('is_active', 1)
            ->orderBy('floor_order', 'ASC')
            ->findAll();
    }
}