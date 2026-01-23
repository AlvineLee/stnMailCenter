<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\InsungApiService;
use App\Libraries\InsungOrderService;
use App\Libraries\InsungStatsService;
use App\Models\InsungDailyOrderModel;
use App\Models\InsungApiListModel;

/**
 * 인성 일일 주문 수집 배치
 * 매일 새벽 1시 cron으로 실행
 *
 * 1. 전날 주문 목록 수집 (tbl_insung_order_history에서 또는 API 직접 호출)
 * 2. 각 주문의 상세 정보 수집 (/api/order_detail/)
 * 3. tbl_insung_daily_orders에 저장 (1000건 단위 배치)
 * 4. 통계 집계 (일별/주별/월별/분기별/반기별/연별)
 */
class SyncInsungDailyOrders extends BaseCommand
{
    protected $group       = 'insung';
    protected $name        = 'insung:sync-daily-orders';
    protected $description = '인성 전날 주문 상세를 수집하고 통계를 집계합니다. (새벽 1시 배치용)';
    protected $usage       = 'insung:sync-daily-orders [date]';
    protected $arguments   = [
        'date' => '수집 대상 날짜 (YYYY-MM-DD, 기본: 어제)'
    ];

    private $insungApiService;
    private $dailyOrderModel;
    private $apiListModel;

    private $batchSize = 1000;
    private $apiDelayMs = 100; // API 호출 간 딜레이 (ms)

    public function run(array $params)
    {
        $targetDate = $params[0] ?? date('Y-m-d', strtotime('-1 day'));

        CLI::write("========================================", 'cyan');
        CLI::write(" 인성 일일 주문 수집 배치", 'cyan');
        CLI::write("========================================", 'cyan');
        CLI::write("대상 날짜: {$targetDate}", 'yellow');
        CLI::write("시작 시간: " . date('Y-m-d H:i:s'), 'yellow');
        CLI::write("");

        try {
            $this->insungApiService = new InsungApiService();
            $this->dailyOrderModel = new InsungDailyOrderModel();
            $this->apiListModel = new InsungApiListModel();

            // Step 1: 전날 주문 목록 수집
            CLI::write("[1/3] 주문 목록 수집 중...", 'green');
            $orders = $this->fetchOrdersForDate($targetDate);

            if (empty($orders)) {
                CLI::write("수집할 주문이 없습니다.", 'yellow');
                return;
            }

            CLI::write("  - 수집된 주문 수: " . count($orders) . "건", 'white');

            // Step 2: 주문 상세 수집 및 저장
            CLI::write("");
            CLI::write("[2/3] 주문 상세 수집 및 저장 중...", 'green');
            $result = $this->fetchAndSaveOrderDetails($orders, $targetDate);

            CLI::write("  - 신규 저장: {$result['inserted']}건", 'white');
            CLI::write("  - 업데이트: {$result['updated']}건", 'white');
            if (!empty($result['errors'])) {
                CLI::write("  - 오류: " . count($result['errors']) . "건", 'red');
            }

            // Step 3: 통계 집계
            CLI::write("");
            CLI::write("[3/3] 통계 집계 중...", 'green');
            $this->aggregateStats($targetDate);

            CLI::write("");
            CLI::write("========================================", 'cyan');
            CLI::write(" 완료!", 'green');
            CLI::write("========================================", 'cyan');
            CLI::write("종료 시간: " . date('Y-m-d H:i:s'), 'yellow');

        } catch (\Exception $e) {
            CLI::error("배치 실행 중 오류 발생: " . $e->getMessage());
            log_message('error', "SyncInsungDailyOrders Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * 특정 날짜의 주문 목록 수집
     *
     * @param string $date
     * @return array
     */
    private function fetchOrdersForDate(string $date): array
    {
        $allOrders = [];

        // v_cc_api_user_simple View 사용 (user_id가 있는 콜센터만)
        $db = \Config\Database::connect();
        $query = $db->query('SELECT * FROM v_cc_api_user_simple WHERE user_id IS NOT NULL');

        if (!$query) {
            CLI::write("  - 콜센터 목록 조회 실패", 'red');
            return [];
        }

        $ccList = $query->getResultArray();

        // cccode 기준으로 그룹화 (같은 cccode는 한 번만 API 호출)
        // cc_order=F 옵션으로 콜센터 전체 오더를 가져오므로 중복 호출 방지
        $groupedByCcCode = [];
        foreach ($ccList as $apiInfo) {
            $apiCcCode = $apiInfo['cccode'] ?? '';
            if (empty($apiCcCode)) continue;

            if (!isset($groupedByCcCode[$apiCcCode])) {
                $groupedByCcCode[$apiCcCode] = $apiInfo;
            } else {
                // user_id가 없으면 다른 것으로 대체
                if (empty($groupedByCcCode[$apiCcCode]['user_id']) && !empty($apiInfo['user_id'])) {
                    $groupedByCcCode[$apiCcCode]['user_id'] = $apiInfo['user_id'];
                }
            }
        }

        CLI::write("  - 콜센터 수: " . count($groupedByCcCode) . "개 (중복 제거 전: " . count($ccList) . "개)", 'yellow');

        $dateFormatted = str_replace('-', '', $date); // YYYYMMDD 형식

        foreach ($groupedByCcCode as $ccCode => $cc) {
            try {
                // View 컬럼명: mcode, token (m_code, token_key 아님)
                $mCode = $cc['mcode'] ?? $cc['m_code'] ?? '';
                $token = $cc['token'] ?? $cc['token_key'] ?? '';
                $userId = $cc['user_id'] ?? '';
                $apiIdx = $cc['api_idx'] ?? null;
                $ccCode = $cc['cccode'] ?? '';
                $apiName = $cc['api_name'] ?? '';

                CLI::write("  - 콜센터 조회: {$apiName} ({$ccCode})", 'white');

                // 필수 정보 체크
                if (empty($mCode) || empty($token) || empty($userId)) {
                    CLI::write("    -> 스킵: API 정보 불완전", 'yellow');
                    continue;
                }

                // /api/order_list/ 직접 호출 (cc_order=F 사용)
                $orders = $this->callOrderListApiDirect($mCode, $ccCode, $token, $userId, $dateFormatted, $apiIdx);

                if (!empty($orders)) {
                    $validCount = 0;
                    foreach ($orders as $order) {
                        // 배열이 아니면 스킵
                        if (!is_array($order)) {
                            continue;
                        }

                        $order['cc_code'] = $ccCode;
                        $order['api_name'] = $apiName;
                        $order['m_code'] = $mCode;
                        $order['token'] = $token;
                        $order['user_id'] = $userId;
                        $order['api_idx'] = $apiIdx;
                        $allOrders[] = $order;
                        $validCount++;
                    }
                    if ($validCount > 0) {
                        CLI::write("    -> {$validCount}건 수집", 'green');
                    }
                }

            } catch (\Exception $e) {
                CLI::write("    -> 오류: " . $e->getMessage(), 'red');
                log_message('error', "SyncInsungDailyOrders - CC {$ccCode} error: " . $e->getMessage());
            }

            // API 과부하 방지
            usleep($this->apiDelayMs * 1000);
        }

        return $allOrders;
    }

    /**
     * 주문 상세 수집 및 저장
     *
     * @param array $orders
     * @param string $targetDate
     * @return array
     */
    private function fetchAndSaveOrderDetails(array $orders, string $targetDate): array
    {
        $result = [
            'inserted' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        $detailOrders = [];
        $processedCount = 0;
        $totalCount = count($orders);

        foreach ($orders as $order) {
            try {
                $serialNumber = $order['serial_number'] ?? $order['order_no'] ?? null;
                if (empty($serialNumber)) {
                    continue;
                }

                // 주문 상세 API 호출
                $detailResult = $this->insungApiService->getOrderDetail(
                    $order['m_code'],
                    $order['cc_code'],
                    $order['token'],
                    $order['user_id'],
                    $serialNumber,
                    $order['api_idx']
                );

                if ($detailResult['success'] && !empty($detailResult['data'])) {
                    $detailData = $this->parseOrderDetailResponse($detailResult['data']);
                    $detailData['serial_number'] = $serialNumber;
                    $detailData['cc_code'] = $order['cc_code'];
                    $detailData['api_name'] = $order['api_name'];
                    $detailData['order_date'] = $targetDate;
                    $detailData['collected_at'] = date('Y-m-d H:i:s');
                    $detailData['raw_data'] = json_encode($detailResult['data'], JSON_UNESCAPED_UNICODE);

                    $detailOrders[] = $detailData;
                }

                $processedCount++;

                // 진행 상황 표시 (100건마다)
                if ($processedCount % 100 === 0) {
                    CLI::write("  - 진행: {$processedCount}/{$totalCount} (" . round($processedCount / $totalCount * 100) . "%)", 'white');
                }

                // 배치 크기에 도달하면 저장
                if (count($detailOrders) >= $this->batchSize) {
                    $batchResult = $this->dailyOrderModel->batchUpsert($detailOrders);
                    $result['inserted'] += $batchResult['inserted'];
                    $result['updated'] += $batchResult['updated'];
                    $result['errors'] = array_merge($result['errors'], $batchResult['errors']);
                    $detailOrders = [];
                    CLI::write("    -> 배치 저장 완료 (inserted: {$batchResult['inserted']}, updated: {$batchResult['updated']})", 'cyan');
                }

                // API 과부하 방지
                usleep($this->apiDelayMs * 1000);

            } catch (\Exception $e) {
                $result['errors'][] = ($order['serial_number'] ?? 'unknown') . ": " . $e->getMessage();
            }
        }

        // 남은 데이터 저장
        if (!empty($detailOrders)) {
            $batchResult = $this->dailyOrderModel->batchUpsert($detailOrders);
            $result['inserted'] += $batchResult['inserted'];
            $result['updated'] += $batchResult['updated'];
            $result['errors'] = array_merge($result['errors'], $batchResult['errors']);
        }

        return $result;
    }

    /**
     * order_detail API 응답 파싱
     *
     * @param array $apiData
     * @return array
     */
    private function parseOrderDetailResponse(array $apiData): array
    {
        $getValue = function ($data, $key, $default = null) {
            if (is_object($data)) {
                return $data->$key ?? $default;
            } elseif (is_array($data)) {
                return $data[$key] ?? $default;
            }
            return $default;
        };

        $parseDateTime = function ($value) {
            if (empty($value)) return null;
            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
        };

        $stateLabels = [
            '10' => '접수', '11' => '배차', '12' => '운행', '20' => '대기',
            '30' => '완료', '40' => '취소', '50' => '문의', '90' => '예약'
        ];

        // 텍스트 → 숫자코드 변환 (API가 텍스트로 반환하는 경우)
        $stateTextToCode = [
            '접수' => '10', '배차' => '11', '운행' => '12', '대기' => '20',
            '완료' => '30', '취소' => '40', '문의' => '50', '예약' => '90',
            // 추가 가능한 텍스트 변형
            '완 료' => '30', '취 소' => '40', '배 차' => '11', '운 행' => '12',
        ];

        // state 변환 함수
        $normalizeState = function ($rawState) use ($stateLabels, $stateTextToCode) {
            if (empty($rawState)) return null;

            // 이미 숫자 코드인 경우
            if (isset($stateLabels[$rawState])) {
                return $rawState;
            }

            // 텍스트인 경우 숫자 코드로 변환
            $trimmed = trim($rawState);
            if (isset($stateTextToCode[$trimmed])) {
                return $stateTextToCode[$trimmed];
            }

            // 알 수 없는 state는 그대로 반환
            return $rawState;
        };

        $result = [];

        // 접수자 정보 (index 1)
        if (isset($apiData[1])) {
            $info = is_object($apiData[1]) ? (array)$apiData[1] : $apiData[1];
            $result['customer_name'] = $getValue($info, 'customer_name');
            $result['customer_tel_number'] = $getValue($info, 'customer_tel_number');
            $result['customer_department'] = $getValue($info, 'customer_department');
            $result['customer_duty'] = $getValue($info, 'customer_duty');
        }

        // 기사 정보 (index 2)
        if (isset($apiData[2])) {
            $info = is_object($apiData[2]) ? (array)$apiData[2] : $apiData[2];
            $result['rider_code_no'] = $getValue($info, 'rider_code_no');
            $result['rider_name'] = $getValue($info, 'rider_name');
            $result['rider_tel_number'] = $getValue($info, 'rider_tel_number');
        }

        // 시간 정보 (index 3)
        if (isset($apiData[3])) {
            $info = is_object($apiData[3]) ? (array)$apiData[3] : $apiData[3];
            $result['order_time'] = $parseDateTime($getValue($info, 'order_time'));
            $result['allocation_time'] = $parseDateTime($getValue($info, 'allocation_time'));
            $result['pickup_time'] = $parseDateTime($getValue($info, 'pickup_time'));
            $result['resolve_time'] = $parseDateTime($getValue($info, 'resolve_time'));
            $result['complete_time'] = $parseDateTime($getValue($info, 'complete_time'));
        }

        // 주소 정보 (index 4)
        if (isset($apiData[4])) {
            $info = is_object($apiData[4]) ? (array)$apiData[4] : $apiData[4];
            $result['departure_dong_name'] = $getValue($info, 'departure_dong_name');
            $result['departure_address'] = $getValue($info, 'departure_address');
            $result['departure_tel_number'] = $getValue($info, 'departure_tel_number');
            $result['departure_company_name'] = $getValue($info, 'departure_company_name');
            $result['destination_dong_name'] = $getValue($info, 'destination_dong_name');
            $result['destination_address'] = $getValue($info, 'destination_address');
            $result['destination_tel_number'] = $getValue($info, 'destination_tel_number');
            $result['destination_company_name'] = $getValue($info, 'destination_company_name');
            $result['start_c_code'] = $getValue($info, 'start_c_code');
            $result['dest_c_code'] = $getValue($info, 'dest_c_code');
            $result['start_department'] = $getValue($info, 'start_department');
            $result['start_duty'] = $getValue($info, 'start_duty');
            $result['dest_department'] = $getValue($info, 'dest_department');
            $result['dest_duty'] = $getValue($info, 'dest_duty');
            $result['happy_call'] = $getValue($info, 'happy_call');

            $distance = $getValue($info, 'distince');
            if ($distance) {
                $result['distince'] = (float)str_replace(['Km', 'km', ' '], '', $distance);
            }
        }

        // 배송 정보 (index 5)
        if (isset($apiData[5])) {
            $info = is_object($apiData[5]) ? (array)$apiData[5] : $apiData[5];
            $result['car_type'] = $getValue($info, 'car_type');
            $result['cargo_type'] = $getValue($info, 'cargo_type');
            $result['cargo_name'] = $getValue($info, 'cargo_name');
            $result['payment'] = $getValue($info, 'payment');

            $rawState = $getValue($info, 'state') ?? $getValue($info, 'save_state');
            $state = $normalizeState($rawState);
            $result['state'] = $state;
            $result['state_text'] = $stateLabels[$state] ?? $rawState;

            $totalCost = $getValue($info, 'total_cost');
            if ($totalCost) {
                $result['price'] = (int)str_replace(['원', ',', ' '], '', $totalCost);
            }
        }

        // 기타 정보 (index 9)
        if (isset($apiData[9])) {
            $info = is_object($apiData[9]) ? (array)$apiData[9] : $apiData[9];
            $result['doc'] = $getValue($info, 'doc');
            $result['item_type'] = $getValue($info, 'item_type');
            $result['sfast'] = $getValue($info, 'sfast');
            $result['summary'] = $getValue($info, 'summary');
            $result['reason'] = $getValue($info, 'reason');
            $result['order_regist_type'] = $getValue($info, 'order_regist_type');

            // state가 없으면 여기서 가져오기
            if (empty($result['state'])) {
                $rawState = $getValue($info, 'save_state');
                $state = $normalizeState($rawState);
                $result['state'] = $state;
                $result['state_text'] = $stateLabels[$state] ?? $rawState;
            }
        }

        return $result;
    }

    /**
     * 통계 집계
     *
     * @param string $date
     */
    private function aggregateStats(string $date): void
    {
        try {
            $statsService = new InsungStatsService();

            // 일별 통계
            CLI::write("  - 일별 통계 집계...", 'white');
            $statsService->aggregateDaily($date);

            // 주별 통계 (일요일이면 주간 통계도 집계)
            $dayOfWeek = date('w', strtotime($date));
            if ($dayOfWeek == 0) { // 일요일
                CLI::write("  - 주별 통계 집계...", 'white');
                $statsService->aggregateWeekly($date);
            }

            // 월별 통계 (월말이면)
            $isLastDayOfMonth = (date('d', strtotime($date)) == date('t', strtotime($date)));
            if ($isLastDayOfMonth) {
                CLI::write("  - 월별 통계 집계...", 'white');
                $statsService->aggregateMonthly($date);

                // 분기별/반기별/연별 통계 (해당 월말이면)
                $month = (int)date('m', strtotime($date));

                if (in_array($month, [3, 6, 9, 12])) {
                    CLI::write("  - 분기별 통계 집계...", 'white');
                    $statsService->aggregateQuarterly($date);
                }

                if (in_array($month, [6, 12])) {
                    CLI::write("  - 반기별 통계 집계...", 'white');
                    $statsService->aggregateSemiAnnual($date);
                }

                if ($month == 12) {
                    CLI::write("  - 연별 통계 집계...", 'white');
                    $statsService->aggregateYearly($date);
                }
            }

            CLI::write("  - 통계 집계 완료", 'green');

        } catch (\Exception $e) {
            CLI::write("  - 통계 집계 오류: " . $e->getMessage(), 'red');
            log_message('error', "SyncInsungDailyOrders - Stats aggregation error: " . $e->getMessage());
        }
    }

    /**
     * /api/order_list/ 직접 호출 (cc_order=F 사용)
     * 콜센터 전체 오더 조회
     *
     * @param string $mCode
     * @param string $ccCode
     * @param string $token
     * @param string $userId
     * @param string $date YYYYMMDD 형식
     * @param int|null $apiIdx
     * @return array
     */
    private function callOrderListApiDirect(string $mCode, string $ccCode, string $token, string $userId, string $date, ?int $apiIdx = null, bool $debug = false): array
    {
        $baseUrl = $this->insungApiService->getBaseUrl();
        $url = $baseUrl . '/api/order_list/';

        $allOrders = [];
        $page = 1;
        $limit = 1000;

        do {
            $params = [
                'type' => 'json',
                'm_code' => $mCode,
                'cc_code' => $ccCode,
                'user_id' => $userId,
                'token' => $token,
                'from_date' => $date,
                'to_date' => $date,
                'limit' => $limit,
                'page' => $page,
                'cc_order' => 'F'  // 콜센터 전체 오더 조회
            ];

            // DEBUG: 요청 파라미터 출력
            if ($debug) {
                CLI::write("      [DEBUG] URL: {$url}", 'cyan');
                CLI::write("      [DEBUG] Params:", 'cyan');
                CLI::write("        m_code: {$mCode}", 'cyan');
                CLI::write("        cc_code: {$ccCode}", 'cyan');
                CLI::write("        user_id: {$userId}", 'cyan');
                CLI::write("        token: " . substr($token, 0, 20) . "...", 'cyan');
                CLI::write("        from_date: {$date}", 'cyan');
                CLI::write("        to_date: {$date}", 'cyan');
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                if ($debug) {
                    CLI::write("      [DEBUG] cURL Error: " . curl_error($ch), 'red');
                }
                curl_close($ch);
                break;
            }

            curl_close($ch);

            if (empty($response)) {
                if ($debug) {
                    CLI::write("      [DEBUG] Empty response", 'red');
                }
                break;
            }

            // DEBUG: 응답 출력
            if ($debug) {
                CLI::write("      [DEBUG] Response (첫 500자):", 'cyan');
                CLI::write("      " . substr($response, 0, 500), 'white');
            }

            // JSON 파싱 전처리 (InsungOrderService와 동일)
            $cleanedResponse = $this->sanitizeJsonResponse($response);
            $cleanedResponse = mb_convert_encoding($cleanedResponse, 'UTF-8', 'UTF-8');
            $data = json_decode($cleanedResponse, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

            if (!$data || !is_array($data)) {
                if ($debug) {
                    $jsonError = json_last_error_msg();
                    CLI::write("      [DEBUG] JSON 파싱 실패: {$jsonError}", 'red');
                }
                break;
            }

            // 응답 코드 확인
            $code = $data[0]['code'] ?? '';
            $msg = $data[0]['msg'] ?? '';

            if ($debug) {
                CLI::write("      [DEBUG] API 응답: code={$code}, msg={$msg}", 'yellow');
            }

            if ($code !== '1000') {
                break;
            }

            // 주문 데이터 추출 (인덱스 2부터 - 0:응답코드, 1:메타데이터, 2~:주문)
            $orders = array_slice($data, 2);

            if ($debug) {
                $totalRecord = $data[1]['total_record'] ?? 'N/A';
                CLI::write("      [DEBUG] total_record: {$totalRecord}", 'cyan');
                CLI::write("      [DEBUG] 주문 건수: " . count($orders), 'cyan');
            }

            if (empty($orders)) {
                break;
            }

            $allOrders = array_merge($allOrders, $orders);

            // total_page 확인
            $totalPage = (int)($data[0]['total_page'] ?? 1);

            if ($debug) {
                CLI::write("      [DEBUG] total_page: {$totalPage}", 'cyan');
            }

            $page++;

            // 다음 페이지가 있으면 딜레이
            if ($page <= $totalPage) {
                usleep($this->apiDelayMs * 1000);
            }

        } while ($page <= $totalPage);

        return $allOrders;
    }

    /**
     * JSON 응답 정제 (InsungOrderService에서 복사)
     * 문자열 내부의 제어문자를 공백으로, 외부의 제어문자는 삭제
     */
    private function sanitizeJsonResponse(string $response): string
    {
        $result = '';
        $len = strlen($response);
        $inString = false;
        $escape = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $response[$i];
            $ord = ord($char);
            $isControl = ($ord < 32 || $ord === 127);

            if ($escape) {
                static $validEscapes = ['"' => 1, '\\' => 1, '/' => 1, 'b' => 1, 'f' => 1, 'n' => 1, 'r' => 1, 't' => 1, 'u' => 1];
                if ($isControl) {
                    $result = substr($result, 0, -1) . ' ';
                } elseif (!isset($validEscapes[$char])) {
                    $result = substr($result, 0, -1) . $char;
                } else {
                    $result .= $char;
                }
                $escape = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                $result .= $char;
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                $result .= $char;
                continue;
            }

            if ($isControl) {
                if ($inString) {
                    $result .= ' ';
                }
                continue;
            }

            $result .= $char;
        }

        return $result;
    }
}