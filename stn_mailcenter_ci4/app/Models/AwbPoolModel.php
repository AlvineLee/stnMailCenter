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
            log_message('error', "AwbPoolModel::markAsUsed - AWB record not found: awb_no={$awbNo}, order_no={$orderNumber}");
            return false;
        }
        
        $pkColumn = isset($awbRecord['id']) ? 'id' : 'awb_no';
        $pkValue = $awbRecord[$pkColumn];
        
        $updateResult = $this->update($pkValue, [
            'order_no' => $orderNumber,
            'used_at' => date('Y-m-d H:i:s')
        ]);
        
        if (!$updateResult) {
            log_message('error', "AwbPoolModel::markAsUsed - Update failed: awb_no={$awbNo}, order_no={$orderNumber}, pkValue={$pkValue}, pkColumn={$pkColumn}");
            // update() 실패 시 markAsUsedByAwbNo로 재시도
            return $this->markAsUsedByAwbNo($awbNo, $orderNumber);
        }
        
        log_message('debug', "AwbPoolModel::markAsUsed - Success: awb_no={$awbNo}, order_no={$orderNumber}, pkValue={$pkValue}");
        return true;
    }

    /**
     * awb_no로 직접 업데이트 (PK가 awb_no인 경우)
     */
    public function markAsUsedByAwbNo($awbNo, $orderNumber)
    {
        // 먼저 레코드 존재 확인
        $awbRecord = $this->where('awb_no', $awbNo)->first();
        
        if (!$awbRecord) {
            log_message('error', "AwbPoolModel::markAsUsedByAwbNo - AWB record not found: awb_no={$awbNo}, order_no={$orderNumber}");
            return false;
        }
        
        // 업데이트 실행
        $updateResult = $this->where('awb_no', $awbNo)
             ->set([
                 'order_no' => $orderNumber,
                 'used_at' => date('Y-m-d H:i:s')
             ])
             ->update();
        
        $affectedRows = $this->db->affectedRows();
        
        if ($affectedRows > 0) {
            log_message('debug', "AwbPoolModel::markAsUsedByAwbNo - Success: awb_no={$awbNo}, order_no={$orderNumber}, affectedRows={$affectedRows}");
            return true;
        } else {
            // 업데이트 결과가 false이거나 영향받은 행이 0인 경우
            $dbError = $this->db->error();
            log_message('error', "AwbPoolModel::markAsUsedByAwbNo - Failed: awb_no={$awbNo}, order_no={$orderNumber}, affectedRows={$affectedRows}, updateResult=" . ($updateResult ? 'true' : 'false') . ", DB error: " . ($dbError['message'] ?? 'none'));
            return false;
        }
    }
}

