<?php

namespace App\Libraries;

use App\Models\InsungDailyOrderModel;
use App\Models\InsungStatsModel;

/**
 * 인성 통계 집계 서비스
 *
 * 일별/주별/월별/분기별/반기별/연별 통계 집계
 * 거래처별 + 전체 통계
 */
class InsungStatsService
{
    private $orderModel;
    private $statsModel;

    public function __construct()
    {
        $this->orderModel = new InsungDailyOrderModel();
        $this->statsModel = new InsungStatsModel();
    }

    /**
     * 일별 통계 집계
     *
     * @param string $date YYYY-MM-DD
     */
    public function aggregateDaily(string $date): void
    {
        $startDate = $date;
        $endDate = $date;
        $periodLabel = $date;

        $this->aggregateForPeriod('daily', $startDate, $endDate, $periodLabel);
    }

    /**
     * 주별 통계 집계
     *
     * @param string $date 기준 날짜 (해당 주의 마지막 날)
     */
    public function aggregateWeekly(string $date): void
    {
        $weekNumber = date('W', strtotime($date));
        $year = date('Y', strtotime($date));

        // 해당 주의 월요일 ~ 일요일
        $startDate = date('Y-m-d', strtotime("{$year}-W{$weekNumber}-1"));
        $endDate = date('Y-m-d', strtotime("{$year}-W{$weekNumber}-7"));
        $periodLabel = "{$year}-W{$weekNumber}";

        $this->aggregateForPeriod('weekly', $startDate, $endDate, $periodLabel);
    }

    /**
     * 월별 통계 집계
     *
     * @param string $date 기준 날짜 (해당 월의 마지막 날)
     */
    public function aggregateMonthly(string $date): void
    {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($date));
        $periodLabel = "{$year}-{$month}";

        $this->aggregateForPeriod('monthly', $startDate, $endDate, $periodLabel);
    }

    /**
     * 분기별 통계 집계
     *
     * @param string $date 기준 날짜 (해당 분기의 마지막 날)
     */
    public function aggregateQuarterly(string $date): void
    {
        $year = date('Y', strtotime($date));
        $month = (int)date('m', strtotime($date));
        $quarter = ceil($month / 3);

        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $startDate = "{$year}-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime("{$year}-{$endMonth}-01"));
        $periodLabel = "{$year}-Q{$quarter}";

        $this->aggregateForPeriod('quarterly', $startDate, $endDate, $periodLabel);
    }

    /**
     * 반기별 통계 집계
     *
     * @param string $date 기준 날짜 (해당 반기의 마지막 날)
     */
    public function aggregateSemiAnnual(string $date): void
    {
        $year = date('Y', strtotime($date));
        $month = (int)date('m', strtotime($date));
        $half = $month <= 6 ? 1 : 2;

        if ($half === 1) {
            $startDate = "{$year}-01-01";
            $endDate = "{$year}-06-30";
        } else {
            $startDate = "{$year}-07-01";
            $endDate = "{$year}-12-31";
        }
        $periodLabel = "{$year}-H{$half}";

        $this->aggregateForPeriod('semi_annual', $startDate, $endDate, $periodLabel);
    }

    /**
     * 연별 통계 집계
     *
     * @param string $date 기준 날짜 (해당 연도의 마지막 날)
     */
    public function aggregateYearly(string $date): void
    {
        $year = date('Y', strtotime($date));

        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        $periodLabel = $year;

        $this->aggregateForPeriod('yearly', $startDate, $endDate, $periodLabel);
    }

    /**
     * 특정 기간의 통계 집계
     *
     * @param string $periodType
     * @param string $startDate
     * @param string $endDate
     * @param string $periodLabel
     */
    private function aggregateForPeriod(string $periodType, string $startDate, string $endDate, string $periodLabel): void
    {
        // 1. 전체 통계 집계
        $totalStats = $this->calculateStats($startDate, $endDate, null);
        $totalStats['period_end'] = $endDate;
        $totalStats['period_label'] = $periodLabel;
        $this->statsModel->upsertStats($periodType, $startDate, null, $totalStats);

        // 2. 콜센터별 통계 집계
        $ccCodes = $this->getDistinctCallCenters($startDate, $endDate);
        foreach ($ccCodes as $cc) {
            $ccStats = $this->calculateStats($startDate, $endDate, $cc['cc_code']);
            $ccStats['period_end'] = $endDate;
            $ccStats['period_label'] = $periodLabel;
            $ccStats['api_name'] = $cc['api_name'];
            $this->statsModel->upsertStats($periodType, $startDate, $cc['cc_code'], $ccStats);
        }
    }

    /**
     * 기간별 콜센터 목록 조회
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getDistinctCallCenters(string $startDate, string $endDate): array
    {
        $db = \Config\Database::connect();
        // GROUP BY 시 api_name이 NULL이 아닌 값을 우선 선택
        return $db->table('tbl_insung_daily_orders')
            ->select('cc_code, MAX(api_name) as api_name')
            ->where('order_date >=', $startDate)
            ->where('order_date <=', $endDate)
            ->where('cc_code IS NOT NULL')
            ->groupBy('cc_code')
            ->get()
            ->getResultArray();
    }

    /**
     * 통계 계산
     *
     * @param string $startDate
     * @param string $endDate
     * @param string|null $ccCode
     * @return array
     */
    private function calculateStats(string $startDate, string $endDate, ?string $ccCode): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_insung_daily_orders')
            ->where('order_date >=', $startDate)
            ->where('order_date <=', $endDate);

        if ($ccCode !== null) {
            $builder->where('cc_code', $ccCode);
        }

        // 기본 통계
        $basic = $builder->select('
            COUNT(*) as total_orders,
            SUM(CASE WHEN state = "10" THEN 1 ELSE 0 END) as state_10_count,
            SUM(CASE WHEN state = "11" THEN 1 ELSE 0 END) as state_11_count,
            SUM(CASE WHEN state = "12" THEN 1 ELSE 0 END) as state_12_count,
            SUM(CASE WHEN state = "20" THEN 1 ELSE 0 END) as state_20_count,
            SUM(CASE WHEN state = "30" THEN 1 ELSE 0 END) as state_30_count,
            SUM(CASE WHEN state = "40" THEN 1 ELSE 0 END) as state_40_count,
            SUM(CASE WHEN state = "50" THEN 1 ELSE 0 END) as state_50_count,
            SUM(CASE WHEN state = "90" THEN 1 ELSE 0 END) as state_90_count,
            SUM(CASE WHEN order_regist_type = "A" THEN 1 ELSE 0 END) as regist_type_a_count,
            SUM(CASE WHEN order_regist_type = "I" THEN 1 ELSE 0 END) as regist_type_i_count,
            SUM(CASE WHEN order_regist_type = "T" THEN 1 ELSE 0 END) as regist_type_t_count,
            SUM(COALESCE(distince, 0)) as total_distance_km,
            AVG(COALESCE(distince, 0)) as avg_distance_km,
            SUM(COALESCE(price, 0)) as total_price,
            AVG(COALESCE(price, 0)) as avg_price,
            COUNT(DISTINCT rider_code_no) as unique_riders
        ')->get()->getRowArray();

        $totalOrders = (int)($basic['total_orders'] ?? 0);

        // 비율 계산
        $completionRate = $totalOrders > 0 ? round(((int)$basic['state_30_count'] / $totalOrders) * 100, 2) : 0;
        $cancellationRate = $totalOrders > 0 ? round(((int)$basic['state_40_count'] / $totalOrders) * 100, 2) : 0;
        $dispatchedCount = (int)$basic['state_11_count'] + (int)$basic['state_12_count'] + (int)$basic['state_30_count'];
        $dispatchRate = $totalOrders > 0 ? round(($dispatchedCount / $totalOrders) * 100, 2) : 0;
        $ordersPerRider = (int)$basic['unique_riders'] > 0 ? round($totalOrders / (int)$basic['unique_riders'], 2) : 0;

        // 시간 지표 (완료 건만)
        $timeBuilder = $db->table('tbl_insung_daily_orders')
            ->select('
                AVG(TIMESTAMPDIFF(MINUTE, order_time, allocation_time)) as avg_dispatch_time,
                AVG(TIMESTAMPDIFF(MINUTE, allocation_time, pickup_time)) as avg_pickup_time,
                AVG(TIMESTAMPDIFF(MINUTE, order_time, complete_time)) as avg_delivery_time
            ')
            ->where('order_date >=', $startDate)
            ->where('order_date <=', $endDate)
            ->where('state', '30');

        if ($ccCode !== null) {
            $timeBuilder->where('cc_code', $ccCode);
        }

        $timeStats = $timeBuilder->get()->getRowArray();

        // 시간대별 분포
        $hourlyDistribution = $this->orderModel->getHourlyDistribution($startDate, $endDate, $ccCode);

        // 차종별 분포
        $carTypeBuilder = $db->table('tbl_insung_daily_orders')
            ->select('car_type, COUNT(*) as count')
            ->where('order_date >=', $startDate)
            ->where('order_date <=', $endDate)
            ->where('car_type IS NOT NULL')
            ->where('car_type !=', '');

        if ($ccCode !== null) {
            $carTypeBuilder->where('cc_code', $ccCode);
        }

        $carTypeResults = $carTypeBuilder->groupBy('car_type')->get()->getResultArray();
        $carTypeDistribution = [];
        foreach ($carTypeResults as $row) {
            $carTypeDistribution[$row['car_type']] = (int)$row['count'];
        }

        return [
            'total_orders' => $totalOrders,
            'state_10_count' => (int)($basic['state_10_count'] ?? 0),
            'state_11_count' => (int)($basic['state_11_count'] ?? 0),
            'state_12_count' => (int)($basic['state_12_count'] ?? 0),
            'state_20_count' => (int)($basic['state_20_count'] ?? 0),
            'state_30_count' => (int)($basic['state_30_count'] ?? 0),
            'state_40_count' => (int)($basic['state_40_count'] ?? 0),
            'state_50_count' => (int)($basic['state_50_count'] ?? 0),
            'state_90_count' => (int)($basic['state_90_count'] ?? 0),
            'regist_type_a_count' => (int)($basic['regist_type_a_count'] ?? 0),
            'regist_type_i_count' => (int)($basic['regist_type_i_count'] ?? 0),
            'regist_type_t_count' => (int)($basic['regist_type_t_count'] ?? 0),
            'completion_rate' => $completionRate,
            'cancellation_rate' => $cancellationRate,
            'dispatch_rate' => $dispatchRate,
            'avg_dispatch_time_min' => $timeStats['avg_dispatch_time'] !== null ? (int)$timeStats['avg_dispatch_time'] : null,
            'avg_pickup_time_min' => $timeStats['avg_pickup_time'] !== null ? (int)$timeStats['avg_pickup_time'] : null,
            'avg_delivery_time_min' => $timeStats['avg_delivery_time'] !== null ? (int)$timeStats['avg_delivery_time'] : null,
            'total_distance_km' => round((float)($basic['total_distance_km'] ?? 0), 2),
            'avg_distance_km' => round((float)($basic['avg_distance_km'] ?? 0), 2),
            'total_price' => (int)($basic['total_price'] ?? 0),
            'avg_price' => (int)($basic['avg_price'] ?? 0),
            'unique_riders' => (int)($basic['unique_riders'] ?? 0),
            'orders_per_rider' => $ordersPerRider,
            'hourly_distribution' => json_encode($hourlyDistribution),
            'car_type_distribution' => json_encode($carTypeDistribution, JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * 특정 날짜 범위의 통계 재집계 (수동 실행용)
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function reAggregateRange(string $startDate, string $endDate): void
    {
        $current = strtotime($startDate);
        $end = strtotime($endDate);

        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $this->aggregateDaily($date);
            $current = strtotime('+1 day', $current);
        }

        log_message('info', "InsungStatsService::reAggregateRange - Completed for {$startDate} to {$endDate}");
    }
}