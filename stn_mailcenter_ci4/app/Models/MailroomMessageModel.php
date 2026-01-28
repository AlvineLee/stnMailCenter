<?php

namespace App\Models;

use CodeIgniter\Model;

class MailroomMessageModel extends Model
{
    protected $table = 'tbl_mailroom_messages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_id',
        'sender_type',
        'sender_id',
        'message_type',
        'message',
        'is_read'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    /**
     * 주문별 메시지 목록
     */
    public function getMessagesByOrder($orderId)
    {
        return $this->where('order_id', $orderId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * 읽지 않은 메시지 수
     */
    public function getUnreadCount($orderId, $forType)
    {
        // forType: driver면 recipient이 보낸 메시지, recipient면 driver가 보낸 메시지
        $senderType = ($forType === 'driver') ? 'recipient' : 'driver';

        return $this->where('order_id', $orderId)
            ->where('sender_type', $senderType)
            ->where('is_read', 0)
            ->countAllResults();
    }

    /**
     * 메시지 읽음 처리
     */
    public function markAsRead($orderId, $forType)
    {
        $senderType = ($forType === 'driver') ? 'recipient' : 'driver';

        return $this->where('order_id', $orderId)
            ->where('sender_type', $senderType)
            ->set(['is_read' => 1])
            ->update();
    }

    /**
     * 프리셋 메시지 목록
     */
    public function getPresetMessages($for)
    {
        $db = \Config\Database::connect();
        return $db->table('tbl_mailroom_preset_messages')
            ->where('message_for', $for)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();
    }
}