<?php

namespace App\Models;

use CodeIgniter\Model;

class MailroomOrderModel extends Model
{
    protected $table = 'tbl_mailroom_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_no',
        'from_building_id', 'from_floor_id', 'from_tenant_id',
        'from_company', 'from_contact_name', 'from_contact_phone',
        'to_building_id', 'to_floor_id', 'to_tenant_id',
        'to_company', 'to_contact_name', 'to_contact_phone',
        'item_description', 'item_count', 'priority', 'memo',
        // 배송 유형 및 처리자 정보
        'delivery_type',      // internal=내부(직접처리), external=외부(기사투입)
        'handler_type',       // mailroom_staff, internal_driver, external_driver
        'handler_user_id',    // 처리자 사용자ID (mailroom_staff인 경우)
        'handler_memo',       // 처리자 메모
        'status', 'assigned_driver_id',
        'confirmed_at', 'picked_at', 'delivered_at', 'cancelled_at',
        'barcode',
        'insung_order_no', 'insung_synced_at', 'insung_sync_status', 'insung_sync_message',
        'created_by'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 주문번호 생성 (MR + 날짜 + 일련번호)
     */
    public function generateOrderNo(): string
    {
        $today = date('Ymd');
        $prefix = 'MR' . $today;

        $lastOrder = $this->like('order_no', $prefix, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastOrder) {
            $lastNum = (int)substr($lastOrder['order_no'], -3);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $prefix . str_pad($newNum, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 바코드 생성
     */
    public function generateBarcode($orderId): string
    {
        return 'MR' . str_pad($orderId, 8, '0', STR_PAD_LEFT);
    }

    /**
     * 주문 목록 조회 (건물/기사용)
     */
    public function getOrdersByBuilding($buildingId, $status = null, $date = null)
    {
        // 테이블 존재 여부 확인
        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            return [];
        }

        $builder = $this->select('tbl_mailroom_orders.*,
            fb.building_name as from_building_name, ff.floor_name as from_floor_name,
            tb.building_name as to_building_name, tf.floor_name as to_floor_name,
            d.driver_name')
            ->join('tbl_mailroom_buildings fb', 'fb.id = tbl_mailroom_orders.from_building_id', 'left')
            ->join('tbl_mailroom_floors ff', 'ff.id = tbl_mailroom_orders.from_floor_id', 'left')
            ->join('tbl_mailroom_buildings tb', 'tb.id = tbl_mailroom_orders.to_building_id', 'left')
            ->join('tbl_mailroom_floors tf', 'tf.id = tbl_mailroom_orders.to_floor_id', 'left')
            ->join('tbl_mailroom_drivers d', 'd.id = tbl_mailroom_orders.assigned_driver_id', 'left')
            ->groupStart()
                ->where('tbl_mailroom_orders.from_building_id', $buildingId)
                ->orWhere('tbl_mailroom_orders.to_building_id', $buildingId)
            ->groupEnd();

        if ($status) {
            $builder->where('tbl_mailroom_orders.status', $status);
        }

        if ($date) {
            $builder->where('DATE(tbl_mailroom_orders.created_at)', $date);
        }

        return $builder->orderBy('tbl_mailroom_orders.priority', 'DESC')
            ->orderBy('tbl_mailroom_orders.created_at', 'DESC')
            ->findAll();
    }

    /**
     * 기사별 주문 목록
     */
    public function getOrdersByDriver($driverId, $status = null)
    {
        $builder = $this->select('tbl_mailroom_orders.*,
            fb.building_name as from_building_name, ff.floor_name as from_floor_name,
            tb.building_name as to_building_name, tf.floor_name as to_floor_name')
            ->join('tbl_mailroom_buildings fb', 'fb.id = tbl_mailroom_orders.from_building_id', 'left')
            ->join('tbl_mailroom_floors ff', 'ff.id = tbl_mailroom_orders.from_floor_id', 'left')
            ->join('tbl_mailroom_buildings tb', 'tb.id = tbl_mailroom_orders.to_building_id', 'left')
            ->join('tbl_mailroom_floors tf', 'tf.id = tbl_mailroom_orders.to_floor_id', 'left')
            ->where('tbl_mailroom_orders.assigned_driver_id', $driverId);

        if ($status) {
            if (is_array($status)) {
                $builder->whereIn('tbl_mailroom_orders.status', $status);
            } else {
                $builder->where('tbl_mailroom_orders.status', $status);
            }
        }

        return $builder->orderBy('tbl_mailroom_orders.priority', 'DESC')
            ->orderBy('tbl_mailroom_orders.created_at', 'DESC')
            ->findAll();
    }

    /**
     * 기사 담당 건물 기준 주문 목록
     */
    public function getOrdersForDriver($driverId)
    {
        // 기사 담당 건물 조회
        $db = \Config\Database::connect();

        // 테이블 존재 여부 확인
        if (!$db->tableExists('tbl_mailroom_driver_buildings')) {
            return [];
        }

        $result = $db->table('tbl_mailroom_driver_buildings')
            ->where('driver_id', $driverId)
            ->get();

        if ($result === false) {
            return [];
        }

        $buildings = $result->getResultArray();

        $buildingIds = array_column($buildings, 'building_id');
        if (empty($buildingIds)) {
            return [];
        }

        return $this->select('tbl_mailroom_orders.*,
            fb.building_name as from_building_name, ff.floor_name as from_floor_name,
            tb.building_name as to_building_name, tf.floor_name as to_floor_name')
            ->join('tbl_mailroom_buildings fb', 'fb.id = tbl_mailroom_orders.from_building_id', 'left')
            ->join('tbl_mailroom_floors ff', 'ff.id = tbl_mailroom_orders.from_floor_id', 'left')
            ->join('tbl_mailroom_buildings tb', 'tb.id = tbl_mailroom_orders.to_building_id', 'left')
            ->join('tbl_mailroom_floors tf', 'tf.id = tbl_mailroom_orders.to_floor_id', 'left')
            ->groupStart()
                ->whereIn('tbl_mailroom_orders.from_building_id', $buildingIds)
                ->orWhereIn('tbl_mailroom_orders.to_building_id', $buildingIds)
            ->groupEnd()
            ->whereIn('tbl_mailroom_orders.status', ['pending', 'confirmed', 'picked'])
            ->orderBy('tbl_mailroom_orders.priority', 'DESC')
            ->orderBy('tbl_mailroom_orders.created_at', 'ASC')
            ->findAll();
    }

    /**
     * 주문 상세 (기사용 - 연락처 숨김)
     */
    public function getOrderDetailForDriver($orderId)
    {
        $order = $this->select('tbl_mailroom_orders.id, tbl_mailroom_orders.order_no,
            tbl_mailroom_orders.from_building_id, tbl_mailroom_orders.from_floor_id,
            tbl_mailroom_orders.from_company, tbl_mailroom_orders.from_contact_name,
            tbl_mailroom_orders.to_building_id, tbl_mailroom_orders.to_floor_id,
            tbl_mailroom_orders.to_company, tbl_mailroom_orders.to_contact_name,
            tbl_mailroom_orders.item_description, tbl_mailroom_orders.item_count,
            tbl_mailroom_orders.priority, tbl_mailroom_orders.memo, tbl_mailroom_orders.status,
            tbl_mailroom_orders.barcode, tbl_mailroom_orders.created_at,
            tbl_mailroom_orders.confirmed_at, tbl_mailroom_orders.picked_at, tbl_mailroom_orders.delivered_at,
            fb.building_name as from_building_name, ff.floor_name as from_floor_name,
            tb.building_name as to_building_name, tf.floor_name as to_floor_name')
            ->join('tbl_mailroom_buildings fb', 'fb.id = tbl_mailroom_orders.from_building_id', 'left')
            ->join('tbl_mailroom_floors ff', 'ff.id = tbl_mailroom_orders.from_floor_id', 'left')
            ->join('tbl_mailroom_buildings tb', 'tb.id = tbl_mailroom_orders.to_building_id', 'left')
            ->join('tbl_mailroom_floors tf', 'tf.id = tbl_mailroom_orders.to_floor_id', 'left')
            ->find($orderId);

        // 연락처 제외됨 (to_contact_phone, from_contact_phone 없음)
        return $order;
    }

    /**
     * 상태 변경
     */
    public function updateStatus($orderId, $status, $userId = null)
    {
        $data = ['status' => $status];

        switch ($status) {
            case 'confirmed':
                $data['confirmed_at'] = date('Y-m-d H:i:s');
                break;
            case 'picked':
                $data['picked_at'] = date('Y-m-d H:i:s');
                break;
            case 'delivered':
                $data['delivered_at'] = date('Y-m-d H:i:s');
                break;
            case 'cancelled':
                $data['cancelled_at'] = date('Y-m-d H:i:s');
                break;
        }

        $result = $this->update($orderId, $data);

        // 이력 저장
        if ($result) {
            $logModel = new MailroomOrderLogModel();
            $logModel->insert([
                'order_id' => $orderId,
                'status' => $status,
                'created_by' => $userId
            ]);
        }

        return $result;
    }

    /**
     * 통계
     */
    public function getStats($buildingId = null, $date = null)
    {
        // 테이블 존재 여부 확인
        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            return [
                'total' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'picked' => 0,
                'delivered' => 0,
                'cancelled' => 0,
                'urgent' => 0
            ];
        }

        $builder = $this->builder();

        if ($buildingId) {
            $builder->groupStart()
                ->where('from_building_id', $buildingId)
                ->orWhere('to_building_id', $buildingId)
            ->groupEnd();
        }

        if ($date) {
            $builder->where('DATE(tbl_mailroom_orders.created_at)', $date);
        } else {
            $builder->where('DATE(tbl_mailroom_orders.created_at)', date('Y-m-d'));
        }

        $total = $builder->countAllResults(false);

        $stats = [
            'total' => $total,
            'pending' => $this->where('tbl_mailroom_orders.status', 'pending')->countAllResults(false),
            'confirmed' => $this->where('tbl_mailroom_orders.status', 'confirmed')->countAllResults(false),
            'picked' => $this->where('tbl_mailroom_orders.status', 'picked')->countAllResults(false),
            'delivered' => $this->where('tbl_mailroom_orders.status', 'delivered')->countAllResults(false),
            'cancelled' => $this->where('tbl_mailroom_orders.status', 'cancelled')->countAllResults(false),
            'urgent' => $this->where('tbl_mailroom_orders.priority', 'urgent')
                ->whereIn('tbl_mailroom_orders.status', ['pending', 'confirmed', 'picked'])
                ->countAllResults(false)
        ];

        return $stats;
    }

    /**
     * 배송 유형별 통계
     */
    public function getStatsByDeliveryType($buildingId = null, $date = null)
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($this->table)) {
            return [
                'internal' => 0,
                'external' => 0,
                'internal_completed' => 0,
                'external_completed' => 0
            ];
        }

        $builder = $this->builder();
        if ($buildingId) {
            $builder->groupStart()
                ->where('from_building_id', $buildingId)
                ->orWhere('to_building_id', $buildingId)
            ->groupEnd();
        }
        if ($date) {
            $builder->where('DATE(tbl_mailroom_orders.created_at)', $date);
        } else {
            $builder->where('DATE(tbl_mailroom_orders.created_at)', date('Y-m-d'));
        }

        return [
            'internal' => (clone $builder)->where('delivery_type', 'internal')->countAllResults(false),
            'external' => (clone $builder)->where('delivery_type', 'external')->countAllResults(false),
            'internal_completed' => (clone $builder)->where('delivery_type', 'internal')->where('status', 'delivered')->countAllResults(false),
            'external_completed' => (clone $builder)->where('delivery_type', 'external')->where('status', 'delivered')->countAllResults(false),
        ];
    }

    /**
     * 인성 API 연동이 필요한 주문인지 확인
     * 외부 배송(external)이고 외부 기사(external_driver)인 경우에만 연동
     */
    public function needsInsungSync($orderId): bool
    {
        $order = $this->find($orderId);
        if (!$order) {
            return false;
        }

        return $order['delivery_type'] === 'external'
            && $order['handler_type'] === 'external_driver';
    }

    /**
     * 배송 유형 결정 (출발지/도착지 건물 기준)
     * - 같은 건물 내 배송: internal
     * - 다른 건물 간 배송: 판단 필요 (인접 여부에 따라)
     */
    public function determineDeliveryType(int $fromBuildingId, int $toBuildingId): string
    {
        // 같은 건물이면 무조건 내부 배송
        if ($fromBuildingId === $toBuildingId) {
            return 'internal';
        }

        // TODO: 인접 건물 여부 체크 로직 추가 가능
        // 현재는 다른 건물이면 외부 배송으로 판단
        return 'external';
    }

    /**
     * 외부 배송 목록 (인성 API 미연동 건)
     */
    public function getPendingExternalOrders($date = null)
    {
        $builder = $this->where('delivery_type', 'external')
            ->where('handler_type', 'external_driver')
            ->where('insung_sync_status', 'none')
            ->whereIn('status', ['pending', 'confirmed']);

        if ($date) {
            $builder->where('DATE(created_at)', $date);
        }

        return $builder->orderBy('created_at', 'ASC')->findAll();
    }
}