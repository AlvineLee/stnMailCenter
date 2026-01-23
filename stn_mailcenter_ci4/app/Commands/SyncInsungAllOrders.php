<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\InsungOrderService;

/**
 * 인성 전체 콜센터 주문 동기화 CLI 명령어
 * 거래처 코드 2338395 전용
 * tbl_cc_list 기준 전체 콜센터의 주문을 조회하여 Redis에 캐싱
 */
class SyncInsungAllOrders extends BaseCommand
{
    protected $group       = 'insung';
    protected $name        = 'insung:sync-all-orders';
    protected $description = '전체 콜센터 주문을 조회하여 Redis에 캐싱합니다. (인성주문 전용)';
    protected $usage       = 'insung:sync-all-orders [from_date] [to_date]';
    protected $arguments   = [
        'from_date' => '시작일자 (YYYYMMDD, 기본값: 오늘)',
        'to_date'   => '종료일자 (YYYYMMDD, 기본값: 오늘)'
    ];

    public function run(array $params)
    {
        $fromDate = $params[0] ?? date('Ymd');
        $toDate = $params[1] ?? date('Ymd');

        // 날짜 형식 정규화 (YYYY-MM-DD -> YYYYMMDD)
        $fromDate = str_replace('-', '', $fromDate);
        $toDate = str_replace('-', '', $toDate);

        CLI::write("===========================================", 'yellow');
        CLI::write("인성 전체 콜센터 주문 동기화 시작", 'yellow');
        CLI::write("===========================================", 'yellow');
        CLI::write("기간: {$fromDate} ~ {$toDate}", 'green');
        CLI::write("", 'white');

        try {
            $insungOrderService = new InsungOrderService();

            // Redis 연결 확인
            if (!$insungOrderService->isRedisAvailable()) {
                CLI::error("Redis 연결 실패. Redis 서버를 확인해주세요.");
                return;
            }

            CLI::write("Redis 연결 확인: OK", 'green');
            CLI::write("전체 콜센터 주문 조회 중...", 'yellow');

            // 전체 콜센터 주문 조회 및 Redis 저장
            $result = $insungOrderService->fetchAllCallCenterOrders($fromDate, $toDate);

            $summary = $result['summary'] ?? [];
            $orders = $result['orders'] ?? [];

            CLI::write("", 'white');
            CLI::write("===========================================", 'green');
            CLI::write("동기화 완료!", 'green');
            CLI::write("===========================================", 'green');
            CLI::write("", 'white');

            // 요약 정보 출력
            CLI::write("총 콜센터: " . ($summary['total_call_centers'] ?? 0), 'white');
            CLI::write("성공: " . ($summary['success_count'] ?? 0), 'green');
            CLI::write("실패: " . ($summary['error_count'] ?? 0), 'red');
            CLI::write("", 'white');
            CLI::write("총 주문: " . ($summary['total_orders'] ?? 0) . "건", 'white');
            CLI::write("  - 진행중: " . ($summary['progress_orders'] ?? 0) . "건", 'yellow');
            CLI::write("  - 완료: " . ($summary['completed_orders'] ?? 0) . "건", 'cyan');
            CLI::write("  - 취소: " . ($summary['cancelled_orders'] ?? 0) . "건", 'red');
            CLI::write("  - 중복제거: " . ($summary['duplicate_orders'] ?? 0) . "건", 'light_gray');

            // Redis 통계
            $redisStats = $result['redis_stats'] ?? [];
            if (!empty($redisStats)) {
                CLI::write("", 'white');
                CLI::write("Redis 상태:", 'white');
                CLI::write("  - 캐시된 주문: " . ($redisStats['progress_order_count'] ?? 0) . "건", 'green');
                CLI::write("  - 메모리: " . ($redisStats['used_memory_human'] ?? 'N/A'), 'white');
            }

            // 오류 목록 출력
            if (!empty($summary['errors'])) {
                CLI::write("", 'white');
                CLI::write("오류 목록:", 'red');
                foreach ($summary['errors'] as $error) {
                    CLI::write("  - {$error}", 'red');
                }
            }

            // 콜센터별 주문 수 (상위 10개)
            if (!empty($summary['by_call_center'])) {
                CLI::write("", 'white');
                CLI::write("콜센터별 주문 (상위 10개):", 'white');
                $count = 0;
                foreach ($summary['by_call_center'] as $name => $orderCount) {
                    CLI::write("  - {$name}: {$orderCount}건", 'white');
                    $count++;
                    if ($count >= 10) break;
                }
            }

            CLI::write("", 'white');
            CLI::write($result['message'] ?? '동기화 완료', 'green');

        } catch (\Exception $e) {
            CLI::error("동기화 중 오류 발생: " . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
        }
    }
}
