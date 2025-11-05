<?php

namespace App\Models;

use CodeIgniter\Model;

class AwbPoolModel extends Model
{
    protected $table = 'tbl_ily_awb_pool';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'awb_no',
        'order_no',
        'used_at'
    ];

    protected $useTimestamps = false;

    /**
     * 미사용 송장번호 조회 (가장 작은 값)
     */
    public function getAvailableAwbNo()
    {
        return $this->where('used_at IS NULL', null, false)
                   ->where('order_no IS NULL', null, false)
                   ->orderBy('awb_no', 'ASC')
                   ->limit(1)
                   ->first();
    }

    /**
     * 송장번호 사용 처리
     */
    public function markAsUsed($awbNo, $orderNumber)
    {
        // PK 컬럼 확인 (id 또는 awb_no)
        $awbRecord = $this->where('awb_no', $awbNo)->first();
        
        if (!$awbRecord) {
            return false;
        }
        
        $pkColumn = isset($awbRecord['id']) ? 'id' : 'awb_no';
        $pkValue = $awbRecord[$pkColumn];
        
        return $this->update($pkValue, [
            'order_no' => $orderNumber,
            'used_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * awb_no로 직접 업데이트 (PK가 awb_no인 경우)
     */
    public function markAsUsedByAwbNo($awbNo, $orderNumber)
    {
        $this->where('awb_no', $awbNo)
             ->set([
                 'order_no' => $orderNumber,
                 'used_at' => date('Y-m-d H:i:s')
             ])
             ->update();
        
        return $this->db->affectedRows() > 0;
    }
}

