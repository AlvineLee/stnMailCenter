<?php

namespace App\Models;

use CodeIgniter\Model;

class MailroomOrderLogModel extends Model
{
    protected $table = 'tbl_mailroom_order_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_id',
        'status',
        'message',
        'created_by'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    /**
     * 주문별 이력 조회
     */
    public function getLogsByOrder($orderId)
    {
        return $this->where('order_id', $orderId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}