<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 인성 주문 상세 모델
 * tbl_insung_daily_orders 테이블 관리
 */
class InsungDailyOrderModel extends Model
{
    protected $table = 'tbl_insung_daily_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'serial_number', 'cc_code', 'api_name', 'order_date',
        'customer_name', 'customer_tel_number', 'customer_department', 'customer_duty',
        'rider_code_no', 'rider_name', 'rider_tel_number',
        'order_time', 'allocation_time', 'pickup_time', 'resolve_time', 'complete_time',
        'reason', 'order_regist_type',
        'departure_dong_name', 'departure_address', 'departure_tel_number', 'departure_company_name',
        'destination_dong_name', 'destination_address', 'destination_tel_number', 'destination_company_name',
        'summary',
        'car_type', 'cargo_type', 'cargo_name', 'payment', 'state', 'state_text',
        'doc', 'item_type', 'sfast', 'start_c_code', 'dest_c_code',
        'start_department', 'start_duty', 'dest_department', 'dest_duty',
        'happy_call', 'distince', 'price',
        'raw_data', 'collected_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 배치 Insert/Update (1000건 단위)
     * serial_number 기준 UPSERT
     *
     * @param array $orders 주문 데이터 배열
     * @param int $batchSize 배치 크기 (기본 1000)
     * @return array ['inserted' => int, 'updated' => int, 'errors' => array]
     */
    public function batchUpsert(array $orders, int $batchSize = 1000): array
    {
        $result = [
            'inserted' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        if (empty($orders)) {
            return $result;
        }

        $chunks = array_chunk($orders, $batchSize);
        $db = \Config\Database::connect();

        foreach ($chunks as $chunkIndex => $chunk) {
            try {
                $db->transBegin();

                foreach ($chunk as $order) {
                    $serialNumber = $order['serial_number'] ?? null;
                    if (empty($serialNumber)) {
                        continue;
                    }

                    // 기존 레코드 확인
                    $existing = $this->where('serial_number', $serialNumber)->first();

                    if ($existing) {
                        // UPDATE
                        $order['updated_at'] = date('Y-m-d H:i:s');
                        $this->update($existing['id'], $order);
                        $result['updated']++;
                    } else {
                        // INSERT
                        $order['created_at'] = date('Y-m-d H:i:s');
                        $order['updated_at'] = date('Y-m-d H:i:s');
                        $this->insert($order);
                        $result['inserted']++;
                    }
                }

                $db->transCommit();
            } catch (\Exception $e) {
                $db->transRollback();
                $result['errors'][] = "Chunk {$chunkIndex}: " . $e->getMessage();
                log_message('error', "InsungDailyOrderModel::batchUpsert - Chunk {$chunkIndex} error: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * 날짜 범위로 주문 조회
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $ccCode 콜센터 코드 (선택)
     * @return array
     */
    public function getOrdersByDateRange(string $startDate, string $endDate, ?string $ccCode = null): array
    {
        $builder = $this->where('order_date >=', $startDate)
                        ->where('order_date <=', $endDate);

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        }

        return $builder->orderBy('order_date', 'ASC')
                       ->orderBy('order_time', 'ASC')
                       ->findAll();
    }

    /**
     * 특정 날짜의 주문 건수 조회
     *
     * @param string $date
     * @param string|null $ccCode
     * @return int
     */
    public function getOrderCountByDate(string $date, ?string $ccCode = null): int
    {
        $builder = $this->where('order_date', $date);

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        }

        return $builder->countAllResults();
    }

    /**
     * 상태별 주문 건수 집계
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $ccCode
     * @return array ['state' => count, ...]
     */
    public function getStateCountsByDateRange(string $startDate, string $endDate, ?string $ccCode = null): array
    {
        $builder = $this->select('state, COUNT(*) as count')
                        ->where('order_date >=', $startDate)
                        ->where('order_date <=', $endDate);

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        }

        $results = $builder->groupBy('state')->findAll();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['state']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * 콜센터별 주문 건수 집계
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getOrderCountsByCallCenter(string $startDate, string $endDate): array
    {
        return $this->select('cc_code, api_name, COUNT(*) as total_orders')
                    ->where('order_date >=', $startDate)
                    ->where('order_date <=', $endDate)
                    ->groupBy('cc_code')
                    ->orderBy('total_orders', 'DESC')
                    ->findAll();
    }

    /**
     * 기사별 주문 건수 집계
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $ccCode
     * @return array
     */
    public function getOrderCountsByRider(string $startDate, string $endDate, ?string $ccCode = null): array
    {
        $builder = $this->select('rider_code_no, rider_name, cc_code, COUNT(*) as total_orders,
                                  SUM(CASE WHEN state = "30" THEN 1 ELSE 0 END) as completed_orders,
                                  SUM(CASE WHEN state = "40" THEN 1 ELSE 0 END) as cancelled_orders')
                        ->where('order_date >=', $startDate)
                        ->where('order_date <=', $endDate)
                        ->where('rider_code_no IS NOT NULL')
                        ->where('rider_code_no !=', '');

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        }

        return $builder->groupBy('rider_code_no')
                       ->orderBy('total_orders', 'DESC')
                       ->findAll();
    }

    /**
     * 시간대별 주문 분포 집계
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $ccCode
     * @return array [hour => count, ...]
     */
    public function getHourlyDistribution(string $startDate, string $endDate, ?string $ccCode = null): array
    {
        $builder = $this->select('HOUR(order_time) as hour, COUNT(*) as count')
                        ->where('order_date >=', $startDate)
                        ->where('order_date <=', $endDate)
                        ->where('order_time IS NOT NULL');

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        }

        $results = $builder->groupBy('HOUR(order_time)')
                           ->orderBy('hour', 'ASC')
                           ->findAll();

        // 0~23시 초기화
        $distribution = array_fill(0, 24, 0);
        foreach ($results as $row) {
            $distribution[(int)$row['hour']] = (int)$row['count'];
        }

        return $distribution;
    }

    /**
     * 평균 배송 시간 계산 (분)
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $ccCode
     * @return array ['avg_dispatch' => int, 'avg_pickup' => int, 'avg_delivery' => int]
     */
    public function getAverageDeliveryTimes(string $startDate, string $endDate, ?string $ccCode = null): array
    {
        $builder = $this->select('
            AVG(TIMESTAMPDIFF(MINUTE, order_time, allocation_time)) as avg_dispatch,
            AVG(TIMESTAMPDIFF(MINUTE, allocation_time, pickup_time)) as avg_pickup,
            AVG(TIMESTAMPDIFF(MINUTE, order_time, complete_time)) as avg_delivery
        ')
        ->where('order_date >=', $startDate)
        ->where('order_date <=', $endDate)
        ->where('state', '30'); // 완료된 주문만

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        }

        $result = $builder->first();

        return [
            'avg_dispatch' => $result['avg_dispatch'] !== null ? (int)$result['avg_dispatch'] : null,
            'avg_pickup' => $result['avg_pickup'] !== null ? (int)$result['avg_pickup'] : null,
            'avg_delivery' => $result['avg_delivery'] !== null ? (int)$result['avg_delivery'] : null,
        ];
    }
}