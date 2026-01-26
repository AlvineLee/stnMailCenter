<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\InsungOrderService;

class InsungOrderApi extends ResourceController
{
    protected $format = 'json';

    // API KEY 설정 (나중에 .env나 Config로 이동 가능)
    private const API_KEY = 'STN_MAILCENTER_API_2026';

    protected $insungOrderService;

    public function __construct()
    {
        $this->insungOrderService = new InsungOrderService();
    }

    /**
     * API KEY 검증
     */
    private function validateApiKey(): bool
    {
        $apiKey = $this->request->getHeaderLine('X-API-KEY');

        // 헤더에 없으면 쿼리스트링에서 확인
        if (empty($apiKey)) {
            $apiKey = $this->request->getGet('api_key');
        }

        return $apiKey === self::API_KEY;
    }

    /**
     * 인증 실패 응답
     */
    private function unauthorizedResponse()
    {
        return $this->respond([
            'success' => false,
            'message' => 'Invalid API Key',
            'data' => null
        ], 401);
    }

    /**
     * Redis 전체 주문 목록 조회
     *
     * GET /api/insung-order/list
     * Header: X-API-KEY 또는 ?api_key=xxx
     */
    public function list()
    {
        if (!$this->validateApiKey()) {
            return $this->unauthorizedResponse();
        }

        try {
            $orders = $this->insungOrderService->getAllProgressOrdersFromRedis();

            return $this->respond([
                'success' => true,
                'message' => 'OK',
                'count' => count($orders),
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Redis 특정 주문 조회
     *
     * GET /api/insung-order/detail/{serialNumber}
     */
    public function detail($serialNumber = null)
    {
        if (!$this->validateApiKey()) {
            return $this->unauthorizedResponse();
        }

        if (empty($serialNumber)) {
            return $this->respond([
                'success' => false,
                'message' => 'serial_number is required',
                'data' => null
            ], 400);
        }

        try {
            $order = $this->insungOrderService->getOrderFromRedis($serialNumber);

            if ($order) {
                return $this->respond([
                    'success' => true,
                    'message' => 'OK',
                    'data' => $order
                ]);
            } else {
                return $this->respond([
                    'success' => false,
                    'message' => 'Order not found',
                    'data' => null
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Redis 통계 조회
     *
     * GET /api/insung-order/stats
     */
    public function stats()
    {
        if (!$this->validateApiKey()) {
            return $this->unauthorizedResponse();
        }

        try {
            $stats = $this->insungOrderService->getRedisStats();

            return $this->respond([
                'success' => true,
                'message' => 'OK',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->respond([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
