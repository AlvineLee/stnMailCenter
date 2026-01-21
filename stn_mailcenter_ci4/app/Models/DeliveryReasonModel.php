<?php

namespace App\Models;

use CodeIgniter\Model;

class DeliveryReasonModel extends Model
{
    protected $table = 'tbl_delivery_reasons';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'reason_code',
        'reason_name',
        'sort_order',
        'is_active',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = false;

    /**
     * 전체 배송사유 목록 조회 (정렬순서대로)
     */
    public function getAllReasons()
    {
        return $this->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * 활성화된 배송사유 목록 조회
     */
    public function getActiveReasons()
    {
        return $this->where('is_active', 'Y')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * 배송사유 추가
     */
    public function addReason($data)
    {
        // 중복 코드 체크
        $existing = $this->where('reason_code', $data['reason_code'])->first();
        if ($existing) {
            return ['success' => false, 'message' => '이미 존재하는 배송사유 코드입니다.'];
        }

        // 정렬순서가 없으면 마지막으로
        if (empty($data['sort_order'])) {
            $maxOrder = $this->selectMax('sort_order')->first();
            $data['sort_order'] = ($maxOrder['sort_order'] ?? 0) + 1;
        }

        $data['created_at'] = date('Y-m-d H:i:s');

        if ($this->insert($data)) {
            return ['success' => true, 'id' => $this->getInsertID()];
        }
        return ['success' => false, 'message' => '배송사유 추가에 실패했습니다.'];
    }

    /**
     * 배송사유 수정
     */
    public function updateReason($id, $data)
    {
        // 중복 코드 체크 (자신 제외)
        if (!empty($data['reason_code'])) {
            $existing = $this->where('reason_code', $data['reason_code'])
                            ->where('id !=', $id)
                            ->first();
            if ($existing) {
                return ['success' => false, 'message' => '이미 존재하는 배송사유 코드입니다.'];
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($this->update($id, $data)) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => '배송사유 수정에 실패했습니다.'];
    }

    /**
     * 배송사유 삭제
     */
    public function deleteReason($id)
    {
        if ($this->delete($id)) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => '배송사유 삭제에 실패했습니다.'];
    }

    /**
     * 정렬순서 일괄 업데이트
     */
    public function updateSortOrders($orders)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($orders as $id => $sortOrder) {
            $this->update($id, ['sort_order' => $sortOrder, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $db->transComplete();
        return $db->transStatus();
    }
}