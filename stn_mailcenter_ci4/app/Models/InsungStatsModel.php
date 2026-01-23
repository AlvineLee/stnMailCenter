<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 인성 통계 모델
 * tbl_insung_stats 테이블 관리
 */
class InsungStatsModel extends Model
{
    protected $table = 'tbl_insung_stats';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'period_type', 'period_start', 'period_end', 'period_label',
        'cc_code', 'api_name',
        'total_orders',
        'state_10_count', 'state_11_count', 'state_12_count', 'state_20_count',
        'state_30_count', 'state_40_count', 'state_50_count', 'state_90_count',
        'regist_type_a_count', 'regist_type_i_count', 'regist_type_t_count',
        'completion_rate', 'cancellation_rate', 'dispatch_rate',
        'avg_dispatch_time_min', 'avg_pickup_time_min', 'avg_delivery_time_min',
        'total_distance_km', 'avg_distance_km', 'total_price', 'avg_price',
        'unique_riders', 'orders_per_rider',
        'hourly_distribution', 'car_type_distribution',
        'calculated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 통계 UPSERT
     *
     * @param string $periodType daily|weekly|monthly|quarterly|semi_annual|yearly
     * @param string $periodStart
     * @param string|null $ccCode
     * @param array $stats
     * @return bool
     */
    public function upsertStats(string $periodType, string $periodStart, ?string $ccCode, array $stats): bool
    {
        $builder = $this->where('period_type', $periodType)
                        ->where('period_start', $periodStart);

        // NULL 비교는 IS NULL 사용해야 함
        if ($ccCode === null) {
            $builder->where('cc_code IS NULL');
        } else {
            $builder->where('cc_code', $ccCode);
        }

        $existing = $builder->first();

        $stats['period_type'] = $periodType;
        $stats['period_start'] = $periodStart;
        $stats['cc_code'] = $ccCode;
        $stats['calculated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            return $this->update($existing['id'], $stats);
        } else {
            return $this->insert($stats) !== false;
        }
    }

    /**
     * 기간 유형별 통계 조회
     *
     * @param string $periodType
     * @param string|null $ccCode
     * @param int $limit
     * @return array
     */
    public function getStatsByPeriodType(string $periodType, ?string $ccCode = null, int $limit = 30): array
    {
        $builder = $this->where('period_type', $periodType);

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        } else {
            $builder->where('cc_code IS NULL');
        }

        return $builder->orderBy('period_start', 'DESC')
                       ->limit($limit)
                       ->findAll();
    }

    /**
     * 특정 기간의 콜센터별 통계 조회
     *
     * @param string $periodType
     * @param string $periodStart
     * @return array
     */
    public function getStatsByPeriodWithCallCenters(string $periodType, string $periodStart): array
    {
        return $this->where('period_type', $periodType)
                    ->where('period_start', $periodStart)
                    ->where('cc_code IS NOT NULL')
                    ->orderBy('total_orders', 'DESC')
                    ->findAll();
    }

    /**
     * 전체 통계 조회 (특정 기간)
     *
     * @param string $periodType
     * @param string $periodStart
     * @return array|null
     */
    public function getTotalStats(string $periodType, string $periodStart): ?array
    {
        return $this->where('period_type', $periodType)
                    ->where('period_start', $periodStart)
                    ->where('cc_code IS NULL')
                    ->first();
    }

    /**
     * 기간 비교 (현재 vs 이전)
     *
     * @param string $periodType
     * @param string $currentPeriodStart
     * @param string $previousPeriodStart
     * @param string|null $ccCode
     * @return array ['current' => array, 'previous' => array, 'changes' => array]
     */
    public function comparePeriods(string $periodType, string $currentPeriodStart, string $previousPeriodStart, ?string $ccCode = null): array
    {
        $current = $this->where('period_type', $periodType)
                        ->where('period_start', $currentPeriodStart)
                        ->where('cc_code', $ccCode)
                        ->first();

        $previous = $this->where('period_type', $periodType)
                         ->where('period_start', $previousPeriodStart)
                         ->where('cc_code', $ccCode)
                         ->first();

        $changes = [];
        if ($current && $previous) {
            $compareFields = ['total_orders', 'completion_rate', 'cancellation_rate', 'avg_delivery_time_min'];
            foreach ($compareFields as $field) {
                $currentVal = $current[$field] ?? 0;
                $previousVal = $previous[$field] ?? 0;

                if ($previousVal > 0) {
                    $changes[$field] = round((($currentVal - $previousVal) / $previousVal) * 100, 2);
                } else {
                    $changes[$field] = $currentVal > 0 ? 100 : 0;
                }
            }
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'changes' => $changes,
        ];
    }

    /**
     * 상위 N개 콜센터 조회
     *
     * @param string $periodType
     * @param string $periodStart
     * @param string $orderBy 정렬 기준 필드
     * @param int $limit
     * @return array
     */
    public function getTopCallCenters(string $periodType, string $periodStart, string $orderBy = 'total_orders', int $limit = 10): array
    {
        return $this->where('period_type', $periodType)
                    ->where('period_start', $periodStart)
                    ->where('cc_code IS NOT NULL')
                    ->orderBy($orderBy, 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}