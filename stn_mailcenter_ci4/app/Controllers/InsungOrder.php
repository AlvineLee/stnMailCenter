<?php

namespace App\Controllers;

use App\Libraries\InsungOrderService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 인성주문 컨트롤러
 * 거래처 코드 2338395 전용
 * tbl_cc_list 기준 전체 콜센터(38개)의 주문을 조회하여 Redis에 캐싱
 */
class InsungOrder extends BaseController
{
    protected $insungOrderService;

    public function __construct()
    {
        $this->insungOrderService = new InsungOrderService();
    }

    /**
     * 인성주문 목록 페이지
     */
    public function list()
    {
        // 거래처 코드 확인 (2338395만 접근 가능)
        $userCompCode = session()->get('user_company');
        if ($userCompCode != '2338395') {
            return redirect()->to('/dashboard')->with('error', '접근 권한이 없습니다.');
        }

        // 콜센터 목록 조회 (중복 제거)
        $ccModel = new \App\Models\InsungCcListModel();
        $allCallCenters = $ccModel->getCcListWithApiInfo([], 1, 100)['cc_list'] ?? [];

        // api_name 기준으로 중복 제거
        $callCenters = [];
        $seenNames = [];
        foreach ($allCallCenters as $cc) {
            $name = $cc['api_name'] ?? '';
            if ($name && !in_array($name, $seenNames)) {
                $callCenters[] = $cc;
                $seenNames[] = $name;
            }
        }

        $data = [
            'title' => '인성주문 - 전체 콜센터 주문 현황',
            'content_header' => [
                'title' => '인성주문',
                'description' => '콜센터 전체 주문 현황 (실시간)'
            ],
            'call_centers' => $callCenters
        ];

        return view('insung_order/list', $data);
    }

    /**
     * 전체 콜센터 주문 조회 (AJAX)
     */
    public function fetchOrders(): ResponseInterface
    {
        // 거래처 코드 확인
        $userCompCode = session()->get('user_company');
        if ($userCompCode != '2338395') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ]);
        }

        $fromDate = $this->request->getPost('from_date') ?? date('Y-m-d');
        $toDate = $this->request->getPost('to_date') ?? date('Y-m-d');

        // 날짜 형식 변환 (YYYY-MM-DD -> YYYYMMDD)
        $fromDateFormatted = str_replace('-', '', $fromDate);
        $toDateFormatted = str_replace('-', '', $toDate);

        try {
            $result = $this->insungOrderService->fetchAllCallCenterOrders($fromDateFormatted, $toDateFormatted);

            return $this->response->setJSON([
                'success' => true,
                'data' => $result['orders'] ?? [],
                'summary' => $result['summary'] ?? [],
                'redis_stats' => $result['redis_stats'] ?? [],
                'message' => $result['message'] ?? '주문 조회 완료'
            ]);

        } catch (\Exception $e) {
            // log_message('error', 'InsungOrder::fetchOrders Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 조회 중 오류 발생: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Redis 통계 조회 (AJAX)
     */
    public function getRedisStats(): ResponseInterface
    {
        // 거래처 코드 확인
        $userCompCode = session()->get('user_company');
        if ($userCompCode != '2338395') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ]);
        }

        try {
            $stats = $this->insungOrderService->getRedisStats();

            return $this->response->setJSON([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Redis 통계 조회 오류: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Redis + DB 통합 주문 조회 (AJAX)
     * 페이지 로드 시 자동 호출
     * Redis(진행중) + DB(완료/취소) 모두 반환
     */
    public function getCachedOrders(): ResponseInterface
    {
        // 거래처 코드 확인
        $userCompCode = session()->get('user_company');
        if ($userCompCode != '2338395') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ]);
        }

        try {
            $startTime = microtime(true);
            $today = date('Y-m-d');

            // Redis(진행중) + DB(완료/취소) 통합 조회
            $result = $this->insungOrderService->getAllOrdersCombined($today);
            $ordersTime = microtime(true);

            $stats = $this->insungOrderService->getRedisStats();
            $statsTime = microtime(true);

            $orders = $result['orders'] ?? [];
            $summary = $result['summary'] ?? [];

            $totalTime = microtime(true) - $startTime;
            $ordersElapsed = round(($ordersTime - $startTime) * 1000, 2);
            $statsElapsed = round(($statsTime - $ordersTime) * 1000, 2);
            $totalElapsed = round($totalTime * 1000, 2);

            log_message('info', "InsungOrder::getCachedOrders - 조회 시간: 주문조회 {$ordersElapsed}ms, 통계조회 {$statsElapsed}ms, 총 {$totalElapsed}ms, 주문수 " . count($orders) . "건");

            return $this->response->setJSON([
                'success' => true,
                'data' => $orders,
                'summary' => $summary,
                'redis_stats' => $stats,
                'count' => count($orders),
                'elapsed_ms' => $totalElapsed,
                'message' => "오늘 주문: 진행중 {$summary['progress_orders']}건, 완료 {$summary['completed_orders']}건, 취소 {$summary['cancelled_orders']}건 ({$totalElapsed}ms)"
            ]);

        } catch (\Exception $e) {
            // log_message('error', 'InsungOrder::getCachedOrders Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 조회 오류: ' . $e->getMessage()
            ]);
        }
    }
}