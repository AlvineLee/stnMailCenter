<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\InsungStatsService;

/**
 * 기존 주문 데이터의 state 값을 텍스트에서 숫자 코드로 변환
 * 및 통계 테이블 중복 데이터 정리
 */
class FixStateData extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'fix:state-data';
    protected $description = 'Fix state data, clean duplicates, and re-aggregate stats';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        $stateTextToCode = [
            '접수' => '10', '배차' => '11', '운행' => '12', '대기' => '20',
            '완료' => '30', '취소' => '40', '문의' => '50', '예약' => '90',
        ];

        CLI::write("=== Fixing state data in tbl_insung_daily_orders ===", 'yellow');

        // 현재 상태 확인
        $result = $db->query("SELECT state, COUNT(*) as cnt FROM tbl_insung_daily_orders GROUP BY state");
        CLI::write("Current state distribution:", 'cyan');
        foreach ($result->getResultArray() as $row) {
            CLI::write("  state: '{$row['state']}' = {$row['cnt']}건", 'white');
        }

        // 텍스트 → 숫자코드 변환
        $updated = 0;
        foreach ($stateTextToCode as $text => $code) {
            $affectedRows = $db->query("UPDATE tbl_insung_daily_orders SET state = ? WHERE state = ?", [$code, $text]);
            $count = $db->affectedRows();
            if ($count > 0) {
                CLI::write("  '{$text}' -> '{$code}': {$count}건 업데이트", 'green');
                $updated += $count;
            }
        }

        CLI::write("\n총 {$updated}건 업데이트 완료", 'green');

        // 변환 후 상태 확인
        CLI::write("\nUpdated state distribution:", 'cyan');
        $result2 = $db->query("SELECT state, state_text, COUNT(*) as cnt FROM tbl_insung_daily_orders GROUP BY state, state_text");
        foreach ($result2->getResultArray() as $row) {
            CLI::write("  state: '{$row['state']}' ({$row['state_text']}) = {$row['cnt']}건", 'white');
        }

        // 중복 통계 데이터 정리
        CLI::write("\n=== Cleaning duplicate stats data ===", 'yellow');

        // 현재 통계 테이블 상태 확인
        $dupCheck = $db->query("
            SELECT period_type, period_start, cc_code, COUNT(*) as cnt
            FROM tbl_insung_stats
            GROUP BY period_type, period_start, cc_code
            HAVING COUNT(*) > 1
        ")->getResultArray();

        if (!empty($dupCheck)) {
            CLI::write("  중복 데이터 발견: " . count($dupCheck) . "건", 'red');

            // 통계 테이블 전체 삭제 후 재생성
            CLI::write("  통계 테이블 초기화...", 'yellow');
            $db->query("TRUNCATE TABLE tbl_insung_stats");
            CLI::write("  통계 테이블 초기화 완료", 'green');
        } else {
            CLI::write("  중복 데이터 없음", 'green');
        }

        // 통계 재집계
        CLI::write("\n=== Re-aggregating statistics ===", 'yellow');

        // 데이터가 있는 날짜 목록 조회
        $dates = $db->query("SELECT DISTINCT order_date FROM tbl_insung_daily_orders ORDER BY order_date")->getResultArray();
        $statsService = new InsungStatsService();

        foreach ($dates as $dateRow) {
            $date = $dateRow['order_date'];
            CLI::write("  Aggregating {$date}...", 'white');
            $statsService->aggregateDaily($date);
        }

        CLI::write("\n통계 재집계 완료!", 'green');

        // 최종 확인
        CLI::write("\n=== Verification ===", 'yellow');
        $result3 = $db->query("SELECT period_label, total_orders, state_30_count, state_40_count FROM tbl_insung_stats WHERE cc_code IS NULL AND period_type = 'daily' ORDER BY period_start DESC LIMIT 5");
        foreach ($result3->getResultArray() as $row) {
            CLI::write("  {$row['period_label']}: total={$row['total_orders']}, 완료={$row['state_30_count']}, 취소={$row['state_40_count']}", 'white');
        }
    }
}