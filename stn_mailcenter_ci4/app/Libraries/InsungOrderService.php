<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use Config\Database;

/**
 * 인성 주문 서비스
 * tbl_cc_list 기준 전체 콜센터(38개)의 주문을 조회하여 Redis에 캐싱
 *
 * - 진행중 주문: Redis에 캐싱 (실시간 조회용)
 * - 완료/취소 주문: DB에 저장 (통계용)
 */
class InsungOrderService
{
    protected $db;
    protected $cache;
    protected $redis;
    protected $useRedis = false;
    protected $insungApiService;

    // Redis 키 프리픽스
    // 키 형식: insung:order:{serial_number} - 인성접수번호가 키
    const REDIS_ORDER_PREFIX = 'insung:order:';  // 진행중 주문 (인성접수번호가 키)
    const REDIS_SUMMARY_KEY = 'insung:summary:';    // 요약 통계
    const REDIS_SUMMARY_TTL = 300;  // 요약만 5분 캐시

    // 완료/취소 상태 코드 (이 상태들은 DB에 저장)
    // API 응답: 접수, 배차, 픽업, 배송, 완료, 취소 등 한글 문자열
    const COMPLETED_STATES = ['완료', '배송완료', '픽업완료', '인수완료'];  // 완료
    const CANCELLED_STATES = ['취소', '주문취소', '배송취소'];  // 취소

    public function __construct()
    {
        $this->db = Database::connect();
        $this->cache = \Config\Services::cache();
        $this->insungApiService = new InsungApiService();

        // Redis 연결 시도
        $this->initRedis();
    }

    /**
     * Redis 초기화
     */
    protected function initRedis()
    {
        try {
            // CI4 Cache 설정에서 Redis 정보 가져오기
            $cacheConfig = config('Cache');

            $this->redis = new \Redis();
            $connected = $this->redis->connect(
                $cacheConfig->redis['host'] ?? '127.0.0.1',
                $cacheConfig->redis['port'] ?? 6379,
                $cacheConfig->redis['timeout'] ?? 0
            );

            if ($connected) {
                // 비밀번호가 설정된 경우 인증 (connect 직후 바로 인증)
                $password = $cacheConfig->redis['password'] ?? null;

                if (!empty($password)) {
                    // 비밀번호만 전달 (Redis 기본 인증)
                    $authResult = $this->redis->auth($password);
                    if (!$authResult) {
                        throw new \Exception('Redis 인증 실패');
                    }
                }

                $this->redis->select($cacheConfig->redis['database'] ?? 0);
                $this->useRedis = true;
                // log_message('info', 'InsungOrderService: Redis 연결 성공');
            }
        } catch (\RedisException $e) {
            // log_message('warning', 'InsungOrderService: Redis 연결 실패 - ' . $e->getMessage());
            $this->useRedis = false;
        } catch (\Exception $e) {
            // log_message('warning', 'InsungOrderService: Redis 연결 실패 - ' . $e->getMessage());
            $this->useRedis = false;
        }
    }

    /**
     * 전체 콜센터 주문 조회
     *
     * @param string $fromDate 시작일 (YYYYMMDD)
     * @param string $toDate 종료일 (YYYYMMDD)
     * @return array
     */
    public function fetchAllCallCenterOrders(string $fromDate, string $toDate): array
    {
        $allOrders = [];
        $progressOrders = [];   // Redis에 저장할 진행중 주문 모음
        $completedOrders = [];  // DB에 저장할 완료 주문 모음
        $cancelledOrders = [];  // DB에 저장할 취소 주문 모음
        $seenSerialNumbers = []; // 중복 체크용 (serial_number 기준)
        $duplicateCount = 0;     // 중복 건수
        $ordersByCallCenter = []; // 콜센터별 주문 건수
        $summary = [
            'total_call_centers' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'total_orders' => 0,
            'duplicate_orders' => 0, // 중복 제거된 건수
            'progress_orders' => 0,  // 진행중
            'completed_orders' => 0,  // 완료
            'cancelled_orders' => 0,  // 취소
            'errors' => []
        ];

        // 1. tbl_cc_list 기준으로 모든 콜센터 API 정보 조회
        $apiList = $this->getApiListForAllCallCenters();
        $summary['total_call_centers'] = count($apiList);

        // 2. cccode 기준으로 그룹화 (같은 cccode는 한 번만 API 호출)
        // View에서 user_id가 이미 포함되어 있으므로 user_id가 있는 첫 번째 것을 사용
        $groupedByApiCcCode = [];
        foreach ($apiList as $apiInfo) {
            $apiCcCode = $apiInfo['cccode'];
            if (!isset($groupedByApiCcCode[$apiCcCode])) {
                $groupedByApiCcCode[$apiCcCode] = $apiInfo;  // 첫 번째 것을 기본 정보로 저장
                $groupedByApiCcCode[$apiCcCode]['_child_count'] = 1;
            } else {
                $groupedByApiCcCode[$apiCcCode]['_child_count']++;
                // user_id가 없으면 다른 콜센터의 user_id로 대체
                if (empty($groupedByApiCcCode[$apiCcCode]['user_id']) && !empty($apiInfo['user_id'])) {
                    $groupedByApiCcCode[$apiCcCode]['user_id'] = $apiInfo['user_id'];
                }
            }
        }

        // 3. 순차 API 호출 (안정성 우선)
        $startTime = microtime(true);
        $apiResults = $this->callOrderListApiSequential($groupedByApiCcCode, $fromDate, $toDate);
        $apiCallTime = round((microtime(true) - $startTime) * 1000, 2);

        // 디버깅: API 호출 결과 분석
        $successCount = 0;
        $errorCount = 0;
        $totalOrders = 0;
        $errorMessages = [];
        foreach ($apiResults as $ccCode => $res) {
            if (isset($res['error'])) {
                $errorCount++;
                if (count($errorMessages) < 5) {
                    $errorMessages[] = "{$ccCode}: {$res['error']}";
                }
            } else {
                $successCount++;
                $totalOrders += count($res['orders'] ?? []);
            }
        }
        log_message('info', "InsungOrderService: 순차 API 호출 완료 - {$apiCallTime}ms, 성공:{$successCount}, 에러:{$errorCount}, 총주문:{$totalOrders}건");
        if (!empty($errorMessages)) {
            log_message('warning', "InsungOrderService: 에러 샘플 - " . implode(' | ', $errorMessages));
        }

        // 4. 결과 처리
        foreach ($apiResults as $ccCode => $result) {
            $apiInfo = $groupedByApiCcCode[$ccCode] ?? null;
            if (!$apiInfo) continue;

            $apiName = $apiInfo['api_name'] ?? $apiInfo['cc_name'] ?? $ccCode;
            $ccName = $apiInfo['cc_name'] ?? $apiName;
            $ccIdxForError = $apiInfo['cc_idx'] ?? 'N/A';

            // 에러 처리
            if (isset($result['error'])) {
                $errorMsg = "[{$ccName}] (cc_idx: {$ccIdxForError}) " . $result['error'];
                $summary['errors'][] = $errorMsg;
                $summary['error_count']++;
                continue;
            }

            $orders = $result['orders'] ?? [];

            if (!empty($orders)) {
                // 주문 분류 (serial_number 기준 중복 제거)
                foreach ($orders as $order) {
                    $serialNumber = $order['serial_number'] ?? null;

                    // serial_number 없거나 이미 처리된 주문은 스킵
                    if (!$serialNumber) {
                        continue;
                    }
                    if (isset($seenSerialNumbers[$serialNumber])) {
                        $duplicateCount++;
                        continue;
                    }
                    $seenSerialNumbers[$serialNumber] = true;

                    $order['cc_code'] = $ccCode;  // tbl_api_list.cccode 저장 (API 인증 코드)
                    $order['api_name'] = $apiName;
                    $now = date('Y-m-d H:i:s');

                    // URL-encoded 필드 디코딩 (API에서 URL-encoded로 반환되는 경우)
                    if (isset($order['order_state'])) {
                        $order['order_state'] = urldecode($order['order_state']);
                    }
                    if (isset($order['state'])) {
                        $order['state'] = urldecode($order['state']);
                    }

                    // API 응답의 order_state 값 확인 (문자열로 변환)
                    $orderState = (string)($order['order_state'] ?? $order['state'] ?? '');

                    if (in_array($orderState, self::COMPLETED_STATES, true)) {
                        // 완료 주문 -> 배열에 추가
                        $completedOrders[] = $order;
                        $summary['completed_orders']++;
                    } elseif (in_array($orderState, self::CANCELLED_STATES, true)) {
                        // 취소 주문 -> 배열에 추가
                        $cancelledOrders[] = $order;
                        $summary['cancelled_orders']++;
                    } else {
                        // 진행중 주문 -> 배열에 추가
                        $order['created_at'] = $now;
                        $order['updated_at'] = $now;
                        $progressOrders[] = $order;
                        $summary['progress_orders']++;
                    }

                    $allOrders[] = $order;
                    $summary['total_orders']++;

                    // 콜센터별 카운트
                    if (!isset($ordersByCallCenter[$apiName])) {
                        $ordersByCallCenter[$apiName] = 0;
                    }
                    $ordersByCallCenter[$apiName]++;
                }
            }

            $summary['success_count']++;
        }

        // 3. 일괄 저장 처리
        // Redis: 전체 주문(진행중+완료+취소) 삭제 후 일괄 저장 (Pipeline)
        if (!empty($allOrders)) {
            $this->clearAndBulkSaveAllOrders($allOrders);
        }

        // DB: 완료/취소 주문 일괄 저장 (Batch Insert) - 기록 보관용
        if (!empty($completedOrders)) {
            $this->bulkSaveOrdersToDb($completedOrders, 'completed');
        }
        if (!empty($cancelledOrders)) {
            $this->bulkSaveOrdersToDb($cancelledOrders, 'cancelled');
        }

        // Redis에 요약 정보 저장
        $this->cacheSummaryToRedis($summary, $fromDate);

        $summary['duplicate_orders'] = $duplicateCount;

        // 콜센터별 건수 내림차순 정렬
        arsort($ordersByCallCenter);
        $summary['by_call_center'] = $ordersByCallCenter;

        // log_message('info', "InsungOrderService: 조회 완료 - 진행중:{$summary['progress_orders']}, 완료:{$summary['completed_orders']}, 취소:{$summary['cancelled_orders']}, 중복제거:{$duplicateCount}");

        return [
            'orders' => $allOrders,
            'summary' => $summary,
            'redis_stats' => $this->getRedisStats(),
            'message' => "총 {$summary['total_call_centers']}개 콜센터 중 {$summary['success_count']}개 성공, 주문 {$summary['total_orders']}건 조회"
        ];
    }

    /**
     * tbl_cc_list 기준으로 모든 콜센터 API 정보 조회
     * v_cc_api_user_simple View 사용 (user_id 포함)
     * user_id가 있는 행만 조회 (없으면 API 호출 불가)
     */
    protected function getApiListForAllCallCenters(): array
    {
        // View 사용: user_id가 있는 행만 조회
        $query = $this->db->query('SELECT * FROM v_cc_api_user_simple WHERE user_id IS NOT NULL');
        return $query ? $query->getResultArray() : [];
    }

    /**
     * 콜센터 idx(cc_idx)로 해당 거래처에 속한 첫 번째 사용자 ID 조회
     *
     * 테이블 관계:
     * tbl_cc_list.idx = tbl_company_list.cc_idx
     * tbl_company_list.comp_code = tbl_users_list.user_company
     *
     * user_ccode가 있는 인성회원만 조회 (슈퍼유저 제외)
     *
     * @param int|string|null $ccIdx tbl_cc_list.idx
     * @return string|null 사용자 ID
     */
    protected function getFirstUserIdByCcIdx($ccIdx): ?string
    {
        if (empty($ccIdx)) {
            // log_message('warning', "InsungOrderService: cc_idx가 비어있음");
            return null;
        }

        // 1. tbl_company_list에서 comp_code 조회 (cc_idx) - 첫번째 거래처만
        $compBuilder = $this->db->table('tbl_company_list');
        $compBuilder->select('comp_code');
        $compBuilder->where('cc_idx', $ccIdx);
        $compBuilder->where('LENGTH(comp_name) >', 3);  // 테스트/더미 거래처 제외
        $compBuilder->limit(1);
        $compQuery = $compBuilder->get();
        $compResult = $compQuery ? $compQuery->getRowArray() : null;

        if (!$compResult || empty($compResult['comp_code'])) {
            // log_message('warning', "InsungOrderService: comp_code 없음 - ccIdx={$ccIdx}");
            return null;
        }

        $compCode = $compResult['comp_code'];

        // 2. tbl_users_list에서 user_id 조회 (user_company = comp_code, user_ccode 있는 인성회원)
        $userBuilder = $this->db->table('tbl_users_list');
        $userBuilder->select('user_id');
        $userBuilder->where('user_company', $compCode);
        $userBuilder->where('user_ccode IS NOT NULL');
        $userBuilder->where("user_ccode != ''");
        $userBuilder->limit(1);

        $userQuery = $userBuilder->get();
        $userResult = $userQuery ? $userQuery->getRowArray() : null;

        // if ($userResult) {
        //     log_message('debug', "InsungOrderService: 사용자 찾음 - ccIdx={$ccIdx}, compCode={$compCode}, userId={$userResult['user_id']}");
        // } else {
        //     log_message('warning', "InsungOrderService: 사용자 없음 - ccIdx={$ccIdx}, compCode={$compCode}");
        // }

        return $userResult['user_id'] ?? null;
    }

    /**
     * cc_idx로 사용자를 찾지 못한 이유 진단
     */
    protected function getDiagnosticReasonForCcIdx($ccIdx): string
    {
        // 1. tbl_company_list에서 거래처 조회
        $compBuilder = $this->db->table('tbl_company_list');
        $compBuilder->select('comp_code, comp_name');
        $compBuilder->where('cc_idx', $ccIdx);
        $compBuilder->where('LENGTH(comp_name) >', 3);  // 테스트/더미 거래처 제외
        $compQuery = $compBuilder->get();
        $compResult = $compQuery ? $compQuery->getRowArray() : null;

        if (!$compResult) {
            return "거래처 없음 (tbl_company_list.cc_idx={$ccIdx} 없음)";
        }

        $compCode = $compResult['comp_code'];
        $compName = $compResult['comp_name'] ?? '';

        // 2. 해당 거래처의 전체 사용자 수 조회
        $allUsersCount = $this->db->table('tbl_users_list')
            ->where('user_company', $compCode)
            ->countAllResults();

        // 3. user_ccode가 있는 인성회원 수 조회
        $insungUsersCount = $this->db->table('tbl_users_list')
            ->where('user_company', $compCode)
            ->where('user_ccode IS NOT NULL')
            ->where("user_ccode != ''")
            ->countAllResults();

        if ($allUsersCount == 0) {
            return "사용자 없음 (거래처:{$compName}/{$compCode}, 등록된 사용자 0명)";
        }

        if ($insungUsersCount == 0) {
            return "인성회원 없음 (거래처:{$compName}/{$compCode}, 사용자:{$allUsersCount}명, user_ccode 있는 사용자 0명)";
        }

        return "사용자 없음 (거래처:{$compName}/{$compCode})";
    }

    /**
     * 인성 API 주문 목록 호출
     * /api/order_list/ 엔드포인트 직접 호출 (cc_order=F 파라미터 추가)
     * OAUTH-FAILED 응답 시 토큰 갱신 후 재시도
     */
    protected function callOrderListApi(string $mCode, string $ccCode, string $userId, string $token, string $fromDate, string $toDate, ?int $apiIdx = null, bool $isRetry = false): array
    {
        // InsungApiService의 baseUrl 사용
        $baseUrl = $this->insungApiService->getBaseUrl();
        $url = $baseUrl . '/api/order_list/';

        // 날짜 형식 변환 (YYYY-MM-DD -> YYYYMMDD)
        $fromDateFormatted = str_replace('-', '', $fromDate);
        $toDateFormatted = str_replace('-', '', $toDate);

        $params = [
            'type' => 'json',
            'm_code' => $mCode,
            'cc_code' => $ccCode,
            'user_id' => $userId,
            'token' => $token,
            'from_date' => $fromDateFormatted,
            'to_date' => $toDateFormatted,
            'limit' => 1000,
            'page' => 1,
            'cc_order' => 'F'  // 콜센터 전체 오더 조회
        ];

        // log_message('debug', "InsungOrderService: API 호출 - {$url}, ccCode={$ccCode}" . ($isRetry ? ' (재시도)' : ''));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL Error: {$error}");
        }

        curl_close($ch);

        // OAUTH-FAILED 먼저 체크 (JSON 파싱 전에 문자열로 체크)
        if (strpos($response, 'OAUTH-FAILED') !== false && !$isRetry && $apiIdx) {
            // log_message('info', "InsungOrderService: OAUTH-FAILED 감지 - 토큰 갱신 시도 (apiIdx={$apiIdx}, ccCode={$ccCode})");

            // 토큰 갱신
            $newToken = $this->insungApiService->updateTokenKey($apiIdx);

            if ($newToken) {
                // log_message('info', "InsungOrderService: 토큰 갱신 성공 - apiIdx={$apiIdx}");
                // 새 토큰으로 재시도
                return $this->callOrderListApi($mCode, $ccCode, $userId, $newToken, $fromDate, $toDate, $apiIdx, true);
            } else {
                // log_message('error', "InsungOrderService: 토큰 갱신 실패 - apiIdx={$apiIdx}");
                return [];
            }
        }

        // JSON 파싱 전처리 - 문자열 값 내부의 줄바꿈/제어문자만 제거 (JSON 구조는 유지)
        // API 응답에서 "customer_name" : "린컴\n주식회사" 같이 이스케이프 안된 줄바꿈이 있음
        // sanitizeJsonResponse 메서드 사용: 문자열 내부만 정리하여 JSON 구조 보존
        $cleanedResponse = $this->sanitizeJsonResponse($response);

        // JSON 디코딩 (UTF-8 정리 후)
        $cleanedResponse = mb_convert_encoding($cleanedResponse, 'UTF-8', 'UTF-8');
        $data = json_decode($cleanedResponse, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // 에러 위치 찾기: 이진 탐색으로 JSON이 깨지는 지점 찾기
            // $errorPos = $this->findJsonErrorPosition($cleanedResponse);
            // if ($errorPos > 0) {
            //     $errorContext = substr($cleanedResponse, max(0, $errorPos - 50), 100);
            //     log_message('error', "InsungOrderService: JSON 에러 위치 ccCode={$ccCode} pos={$errorPos}: " . addcslashes($errorContext, "\r\n\t\""));

            //     // 해당 위치의 hex dump
            //     $hexContext = substr($cleanedResponse, max(0, $errorPos - 10), 30);
            //     $hex = '';
            //     for ($i = 0; $i < strlen($hexContext); $i++) {
            //         $hex .= sprintf("%02X ", ord($hexContext[$i]));
            //     }
            //     log_message('debug', "InsungOrderService: HEX at error pos: " . $hex);
            // }
            // $jsonError = json_last_error_msg();
            // $responseLen = strlen($response);
            // $cleanedLen = strlen($cleanedResponse);
            // log_message('warning', "InsungOrderService: JSON Parse Error [{$jsonError}] - ccCode={$ccCode}, originalLen={$responseLen}, cleanedLen={$cleanedLen}");

            // // 정리된 JSON 시작/끝 부분 출력
            // $firstPart = substr($cleanedResponse, 0, 300);
            // $lastPart = substr($cleanedResponse, -300);
            // log_message('debug', "InsungOrderService: 정리된 JSON 시작: " . addcslashes($firstPart, "\r\n\t"));
            // log_message('debug', "InsungOrderService: 정리된 JSON 끝: " . addcslashes($lastPart, "\r\n\t"));

            // // 정리 후에도 제어문자가 남아있는지 확인 (전체 검사)
            // if (preg_match_all('/[\x00-\x1F\x7F]/', $cleanedResponse, $remaining, PREG_OFFSET_CAPTURE)) {
            //     $chars = [];
            //     foreach (array_slice($remaining[0], 0, 10) as $m) {
            //         $pos = $m[1];
            //         $context = substr($cleanedResponse, max(0, $pos - 20), 50);
            //         $chars[] = sprintf("0x%02X@%d near '%s'", ord($m[0]), $pos, addcslashes($context, "\r\n\t"));
            //     }
            //     log_message('error', "InsungOrderService: 정리 후에도 제어문자 존재! ccCode={$ccCode}: " . implode(' | ', $chars));
            // } else {
            //     log_message('debug', "InsungOrderService: 제어문자 모두 제거됨 (ccCode={$ccCode})");
            // }

            // // JSON 구조 검증 - 대괄호/중괄호 균형 확인
            // $openBrackets = substr_count($cleanedResponse, '[');
            // $closeBrackets = substr_count($cleanedResponse, ']');
            // $openBraces = substr_count($cleanedResponse, '{');
            // $closeBraces = substr_count($cleanedResponse, '}');
            // log_message('debug', "InsungOrderService: 괄호 균형 ccCode={$ccCode}: [ {$openBrackets}/{$closeBrackets} ] { {$openBraces}/{$closeBraces} }");

            // // 따옴표 개수 확인 (홀수면 문제)
            // $quotes = substr_count($cleanedResponse, '"');
            // $isOdd = ($quotes % 2 === 1) ? '홀수(문제!)' : '짝수(정상)';
            // log_message('debug', "InsungOrderService: 따옴표 개수 ccCode={$ccCode}: {$quotes}개 ({$isOdd})");

            return [];
        }

        // API 응답 구조: [0 => {code, msg}, 1 => {메타정보}, 2~ => 주문데이터]
        // 또는: [0 => {code, msg}] (데이터 없음)
        if (!is_array($data) || empty($data)) {
            return [];
        }

        // 응답 코드 확인
        $code = null;
        $msg = null;
        if (isset($data[0])) {
            $code = is_object($data[0]) ? ($data[0]->code ?? null) : ($data[0]['code'] ?? null);
            $msg = is_object($data[0]) ? ($data[0]->msg ?? null) : ($data[0]['msg'] ?? null);
        }

        // OAUTH-FAILED 추가 체크 (JSON 파싱 성공한 경우)
        if ($msg && strpos($msg, 'OAUTH-FAILED') !== false && !$isRetry && $apiIdx) {
            // log_message('info', "InsungOrderService: OAUTH-FAILED - 토큰 갱신 시도 (apiIdx={$apiIdx}, ccCode={$ccCode})");

            // 토큰 갱신
            $newToken = $this->insungApiService->updateTokenKey($apiIdx);

            if ($newToken) {
                // log_message('info', "InsungOrderService: 토큰 갱신 성공 - apiIdx={$apiIdx}");
                // 새 토큰으로 재시도
                return $this->callOrderListApi($mCode, $ccCode, $userId, $newToken, $fromDate, $toDate, $apiIdx, true);
            } else {
                // log_message('error', "InsungOrderService: 토큰 갱신 실패 - apiIdx={$apiIdx}");
                return [];
            }
        }

        if ($code !== '1000') {
            // log_message('warning', "InsungOrderService: API 오류 응답 - code={$code}, msg={$msg}, ccCode={$ccCode}");
            return [];
        }

        // 주문 데이터 추출 (인덱스 2부터)
        $orders = [];
        for ($i = 2; $i < count($data); $i++) {
            $item = $data[$i];
            if (is_object($item)) {
                $item = (array)$item;
            }
            // serial_number가 있으면 주문 데이터
            if (isset($item['serial_number'])) {
                $orders[] = $item;
            }
        }

        // log_message('info', "InsungOrderService: ccCode={$ccCode} 주문 " . count($orders) . "건 조회됨");

        return $orders;
    }

    /**
     * 순차 API 호출 (안정성 우선)
     * 각 콜센터에 대해 하나씩 API 호출
     *
     * @param array $groupedByApiCcCode ccCode별로 그룹화된 API 정보
     * @param string $fromDate 시작일 (YYYYMMDD)
     * @param string $toDate 종료일 (YYYYMMDD)
     * @return array ccCode를 키로 하는 결과 배열 ['orders' => [...]] 또는 ['error' => '...']
     */
    protected function callOrderListApiSequential(array $groupedByApiCcCode, string $fromDate, string $toDate): array
    {
        $results = [];
        $totalApis = count($groupedByApiCcCode);
        $currentApi = 0;

        log_message('info', "InsungOrderService: 순차 API 호출 시작 - 총 {$totalApis}개 API");

        foreach ($groupedByApiCcCode as $ccCode => $apiInfo) {
            $currentApi++;

            // View 컬럼명: mcode, token
            $mCode = $apiInfo['mcode'] ?? $apiInfo['m_code'] ?? '';
            $token = $apiInfo['token'] ?? $apiInfo['token_key'] ?? '';
            $userId = $apiInfo['user_id'] ?? '';
            $apiIdx = $apiInfo['api_idx'] ?? null;

            // 필수 정보 체크
            if (empty($mCode) || empty($token) || empty($userId)) {
                $results[$ccCode] = ['error' => 'API 정보 불완전'];
                continue;
            }

            try {
                $orders = $this->callOrderListApi(
                    $mCode,
                    $ccCode,
                    $userId,
                    $token,
                    $fromDate,
                    $toDate,
                    $apiIdx
                );
                $results[$ccCode] = ['orders' => $orders];
            } catch (\Exception $e) {
                $results[$ccCode] = ['error' => $e->getMessage()];
            }
        }

        log_message('info', "InsungOrderService: 순차 API 호출 완료 - {$totalApis}개 API");

        return $results;
    }

    /**
     * curl_multi를 사용한 병렬 API 호출 (현재 미사용)
     * 서버 부하를 고려하여 배치 단위(BATCH_SIZE)로 나누어 실행
     *
     * @param array $groupedByApiCcCode ccCode별로 그룹화된 API 정보
     * @param string $fromDate 시작일 (YYYYMMDD)
     * @param string $toDate 종료일 (YYYYMMDD)
     * @return array ccCode를 키로 하는 결과 배열 ['orders' => [...]] 또는 ['error' => '...']
     */
    protected function callOrderListApiParallel(array $groupedByApiCcCode, string $fromDate, string $toDate): array
    {
        $results = [];
        $retryList = []; // OAUTH-FAILED로 재시도가 필요한 ccCode 목록

        // 배치 크기 설정 (동시 연결 수 제한) - API 서버 부하 고려
        $batchSize = 3;

        // InsungApiService의 baseUrl 사용
        $baseUrl = $this->insungApiService->getBaseUrl();
        $url = $baseUrl . '/api/order_list/';

        // 날짜 형식 변환 (YYYY-MM-DD -> YYYYMMDD)
        $fromDateFormatted = str_replace('-', '', $fromDate);
        $toDateFormatted = str_replace('-', '', $toDate);

        // API 정보를 배치로 분할
        $batches = array_chunk($groupedByApiCcCode, $batchSize, true);
        $totalBatches = count($batches);
        $batchNum = 0;

        log_message('info', "InsungOrderService: 병렬 API 호출 시작 - 총 " . count($groupedByApiCcCode) . "개 API, {$totalBatches}개 배치 (배치당 {$batchSize}개)");

        foreach ($batches as $batch) {
            $batchNum++;
            $multiHandle = curl_multi_init();
            $curlHandles = [];

            // 1. 배치 내 API 요청을 curl_multi에 추가
            foreach ($batch as $ccCode => $apiInfo) {
                // View 컬럼명: mcode, token (m_code, token_key 아님)
                $mCode = $apiInfo['mcode'] ?? $apiInfo['m_code'] ?? '';
                $token = $apiInfo['token'] ?? $apiInfo['token_key'] ?? '';
                $userId = $apiInfo['user_id'] ?? '';

                // 필수 정보 체크
                if (empty($mCode) || empty($token) || empty($userId)) {
                    $results[$ccCode] = ['error' => 'API 정보 불완전 (mCode/token/userId 누락)'];
                    continue;
                }

                $params = [
                    'type' => 'json',
                    'm_code' => $mCode,
                    'cc_code' => $ccCode,
                    'user_id' => $userId,
                    'token' => $token,
                    'from_date' => $fromDateFormatted,
                    'to_date' => $toDateFormatted,
                    'limit' => 1000,
                    'page' => 1,
                    'cc_order' => 'F'  // 콜센터 전체 오더 조회
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[$ccCode] = [
                    'handle' => $ch,
                    'apiInfo' => $apiInfo
                ];
            }

            // 2. 배치 병렬 실행
            $running = null;
            do {
                $status = curl_multi_exec($multiHandle, $running);
                if ($running) {
                    // 활성 연결이 있으면 대기 (CPU 사용률 최적화)
                    curl_multi_select($multiHandle);
                }
            } while ($running && $status == CURLM_OK);

            // 3. 배치 결과 수집
            foreach ($curlHandles as $ccCode => $handleInfo) {
                $ch = $handleInfo['handle'];
                $apiInfo = $handleInfo['apiInfo'];

                // cURL 에러 체크
                if (curl_errno($ch)) {
                    $results[$ccCode] = ['error' => 'cURL Error: ' . curl_error($ch)];
                    curl_multi_remove_handle($multiHandle, $ch);
                    curl_close($ch);
                    continue;
                }

                // HTTP 상태 코드 확인
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode !== 200) {
                    $results[$ccCode] = ['error' => "HTTP {$httpCode}"];
                    curl_multi_remove_handle($multiHandle, $ch);
                    curl_close($ch);
                    continue;
                }

                $response = curl_multi_getcontent($ch);
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);

                // 빈 응답 체크
                if (empty($response)) {
                    $results[$ccCode] = ['error' => '빈 응답'];
                    continue;
                }

                // OAUTH-FAILED 체크 (재시도 필요)
                if (strpos($response, 'OAUTH-FAILED') !== false) {
                    $retryList[$ccCode] = $apiInfo;
                    continue;
                }

                // HTML 에러 페이지 체크
                if (strpos($response, '<html') !== false || strpos($response, '<!DOCTYPE') !== false) {
                    $results[$ccCode] = ['error' => 'HTML 응답 (서버 에러)'];
                    continue;
                }

                // JSON 파싱
                $orders = $this->parseApiResponse($response, $ccCode);
                if ($orders === null) {
                    $responseLen = strlen($response);
                    $results[$ccCode] = ['error' => "JSON 파싱 실패 (len={$responseLen})"];
                } else {
                    $results[$ccCode] = ['orders' => $orders];
                }
            }

            curl_multi_close($multiHandle);

            // 배치 간 딜레이 (API 서버 부하 분산) - 마지막 배치는 제외
            if ($batchNum < $totalBatches) {
                usleep(100000); // 100ms
            }
        }

        // 4. OAUTH-FAILED 건 개별 재시도 (토큰 갱신 후)
        if (!empty($retryList)) {
            log_message('info', "InsungOrderService: OAUTH-FAILED " . count($retryList) . "건 토큰 갱신 후 재시도");

            foreach ($retryList as $ccCode => $apiInfo) {
                $apiIdx = $apiInfo['api_idx'] ?? null;
                if (!$apiIdx) {
                    $results[$ccCode] = ['error' => 'OAUTH-FAILED (api_idx 없음)'];
                    continue;
                }

                // 토큰 갱신
                $newToken = $this->insungApiService->updateTokenKey($apiIdx);
                if (!$newToken) {
                    $results[$ccCode] = ['error' => 'OAUTH-FAILED (토큰 갱신 실패)'];
                    continue;
                }

                // 새 토큰으로 개별 재시도
                try {
                    $orders = $this->callOrderListApi(
                        $apiInfo['mcode'] ?? $apiInfo['m_code'] ?? '',
                        $ccCode,
                        $apiInfo['user_id'] ?? '',
                        $newToken,
                        $fromDate,
                        $toDate,
                        $apiIdx,
                        true  // isRetry = true
                    );
                    $results[$ccCode] = ['orders' => $orders];
                } catch (\Exception $e) {
                    $results[$ccCode] = ['error' => '재시도 실패: ' . $e->getMessage()];
                }
            }
        }

        return $results;
    }

    /**
     * API 응답 파싱 (병렬 처리용)
     *
     * @param string $response API 응답 문자열
     * @param string $ccCode 콜센터 코드 (로깅용)
     * @return array|null 주문 배열 또는 파싱 실패 시 null, 특수 에러 시 ['error' => '...']
     */
    protected function parseApiResponse(string $response, string $ccCode): ?array
    {
        // 빈 응답 체크
        if (empty($response)) {
            log_message('warning', "parseApiResponse: 빈 응답 - ccCode={$ccCode}");
            return null;
        }

        // 응답 길이 체크 (너무 짧으면 에러 응답일 가능성)
        $responseLen = strlen($response);
        if ($responseLen < 50) {
            log_message('warning', "parseApiResponse: 짧은 응답 - ccCode={$ccCode}, len={$responseLen}, content=" . addcslashes($response, "\r\n\t"));
            return null;
        }

        // JSON 배열 구조 기본 체크 (시작/끝 문자)
        $trimmed = trim($response);
        if ($trimmed[0] !== '[' || $trimmed[strlen($trimmed) - 1] !== ']') {
            log_message('warning', "parseApiResponse: JSON 배열 아님 - ccCode={$ccCode}, start=" . ord($trimmed[0]) . ", end=" . ord($trimmed[strlen($trimmed) - 1]));
            $firstPart = substr($response, 0, 200);
            log_message('warning', "parseApiResponse: 응답 시작 - " . addcslashes($firstPart, "\r\n\t"));
            return null;
        }

        // JSON 파싱 전처리
        $cleanedResponse = $this->sanitizeJsonResponse($response);
        $cleanedResponse = mb_convert_encoding($cleanedResponse, 'UTF-8', 'UTF-8');
        $data = json_decode($cleanedResponse, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonError = json_last_error_msg();
            $firstPart = substr($response, 0, 200);
            log_message('warning', "parseApiResponse: JSON 에러 - ccCode={$ccCode}, error={$jsonError}, len={$responseLen}, start=" . addcslashes($firstPart, "\r\n\t"));
            return null;
        }

        // API 응답 구조 체크
        if (!is_array($data) || empty($data)) {
            return [];
        }

        // 응답 코드 확인
        $code = null;
        $msg = null;
        if (isset($data[0])) {
            $code = is_object($data[0]) ? ($data[0]->code ?? null) : ($data[0]['code'] ?? null);
            $msg = is_object($data[0]) ? ($data[0]->msg ?? null) : ($data[0]['msg'] ?? null);
        }

        if ($code !== '1000') {
            log_message('info', "parseApiResponse: API 오류 응답 - ccCode={$ccCode}, code={$code}, msg={$msg}");
            return [];
        }

        // 주문 데이터 추출 (인덱스 2부터)
        $orders = [];
        for ($i = 2; $i < count($data); $i++) {
            $item = $data[$i];
            if (is_object($item)) {
                $item = (array)$item;
            }
            if (isset($item['serial_number'])) {
                $orders[] = $item;
            }
        }

        return $orders;
    }

    /**
     * JSON 응답 문자열을 정리하여 파싱 가능하게 만듦
     * API 응답에 문자열 값 안에 줄바꿈, 탭 등 제어문자가 이스케이프 없이 들어있는 경우 처리
     *
     * @param string $response 원본 JSON 응답
     * @return string 정리된 JSON 문자열
     */
    protected function sanitizeJsonResponse(string $response): string
    {
        // JSON 문자열 내부의 제어문자를 처리
        // 문자 단위로 처리하여 문자열 값 내부의 제어문자만 공백으로 치환
        // 문자열 외부의 제어문자는 삭제 (JSON 포맷팅용 \r\n 등)
        $result = '';
        $len = strlen($response);
        $inString = false;
        $escape = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $response[$i];
            $ord = ord($char);

            // 제어문자 확인 (0x00-0x1F, 0x7F)
            $isControl = ($ord < 32 || $ord === 127);

            if ($escape) {
                // 이전 문자가 백슬래시인 경우
                // 유효한 JSON 이스케이프: ", \, /, b, f, n, r, t, u (+ 4 hex digits)
                // 그 외는 잘못된 이스케이프 → 백슬래시 제거하고 문자만 유지
                static $validEscapes = ['"' => 1, '\\' => 1, '/' => 1, 'b' => 1, 'f' => 1, 'n' => 1, 'r' => 1, 't' => 1, 'u' => 1];
                if ($isControl) {
                    // 백슬래시 + 제어문자 → 공백으로 치환 (마지막에 추가된 \ 제거)
                    $result = substr($result, 0, -1) . ' ';
                } elseif (!isset($validEscapes[$char])) {
                    // 잘못된 이스케이프 (예: \a, \v, \x, \0, \' 등) → 백슬래시 제거
                    // 첫 10개만 로깅
                    // static $invalidEscapeCount = 0;
                    // if ($invalidEscapeCount < 10) {
                    //     $context = substr($response, max(0, $i - 20), 50);
                    //     log_message('debug', "sanitizeJson: 잘못된 이스케이프 \\{$char} at pos {$i}: " . addcslashes($context, "\r\n\t"));
                    //     $invalidEscapeCount++;
                    // }
                    $result = substr($result, 0, -1) . $char;
                } else {
                    $result .= $char;
                }
                $escape = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                // 백슬래시 - 다음 문자가 이스케이프됨
                $result .= $char;
                $escape = true;
                continue;
            }

            if ($char === '"') {
                // 따옴표 - 문자열 시작/끝
                $inString = !$inString;
                $result .= $char;
                continue;
            }

            if ($isControl) {
                if ($inString) {
                    // 문자열 내부의 제어문자 → 공백으로 치환
                    $result .= ' ';
                }
                // 문자열 외부의 제어문자 → 삭제 (JSON 포맷팅용)
                continue;
            }

            $result .= $char;
        }

        return $result;
    }

    /**
     * JSON 파싱 에러가 발생하는 위치를 이진 탐색으로 찾기
     * 유효한 JSON 배열의 마지막 위치를 찾아서 그 다음 위치를 반환
     */
    protected function findJsonErrorPosition(string $json): int
    {
        $len = strlen($json);
        if ($len < 10) {
            return 0;
        }

        // 배열/객체 구조를 따라가며 에러 위치 찾기
        // 각 최상위 요소를 하나씩 파싱해봄
        $pos = 0;

        // 첫 번째 [ 찾기
        while ($pos < $len && $json[$pos] !== '[') {
            $pos++;
        }
        if ($pos >= $len) {
            return 0;
        }

        $pos++; // [ 다음으로 이동
        $elementCount = 0;
        $lastGoodPos = $pos;

        while ($pos < $len) {
            // 공백 건너뛰기
            while ($pos < $len && ctype_space($json[$pos])) {
                $pos++;
            }
            if ($pos >= $len) break;

            // ] 이면 배열 끝
            if ($json[$pos] === ']') {
                return 0; // 정상 종료
            }

            // , 이면 다음 요소
            if ($json[$pos] === ',') {
                $pos++;
                continue;
            }

            // { 이면 객체 시작 - 매칭되는 } 찾기
            if ($json[$pos] === '{') {
                $braceCount = 1;
                $inStr = false;
                $esc = false;
                $objStart = $pos;
                $pos++;

                while ($pos < $len && $braceCount > 0) {
                    $c = $json[$pos];
                    if ($esc) {
                        $esc = false;
                    } elseif ($c === '\\' && $inStr) {
                        $esc = true;
                    } elseif ($c === '"') {
                        $inStr = !$inStr;
                    } elseif (!$inStr) {
                        if ($c === '{') $braceCount++;
                        elseif ($c === '}') $braceCount--;
                    }
                    $pos++;
                }

                // 이 객체 하나를 파싱해봄
                $objJson = '[' . substr($json, $objStart, $pos - $objStart) . ']';
                $test = json_decode($objJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // 이 객체에서 에러 발생 - 더 세밀하게 찾기
                    return $objStart + $this->findErrorInObject(substr($json, $objStart, $pos - $objStart));
                }

                $lastGoodPos = $pos;
                $elementCount++;
            } else {
                // 예상치 못한 문자
                return $pos;
            }
        }

        return $lastGoodPos;
    }

    /**
     * 단일 JSON 객체 내에서 에러 위치 찾기
     */
    protected function findErrorInObject(string $objJson): int
    {
        // 키-값 쌍을 하나씩 파싱
        $len = strlen($objJson);
        $pos = 1; // { 다음

        while ($pos < $len) {
            // 공백 건너뛰기
            while ($pos < $len && ctype_space($objJson[$pos])) $pos++;
            if ($pos >= $len || $objJson[$pos] === '}') return 0;

            // 키 시작 (")
            if ($objJson[$pos] !== '"') {
                return $pos;
            }

            // 키 문자열 끝 찾기
            $pos++;
            while ($pos < $len && !($objJson[$pos] === '"' && $objJson[$pos-1] !== '\\')) {
                $pos++;
            }
            $pos++; // " 다음

            // : 찾기
            while ($pos < $len && $objJson[$pos] !== ':') $pos++;
            $pos++; // : 다음

            // 공백 건너뛰기
            while ($pos < $len && ctype_space($objJson[$pos])) $pos++;

            // 값 파싱
            if ($objJson[$pos] === '"') {
                // 문자열 값 - 끝 따옴표 찾기
                $valueStart = $pos;
                $pos++;
                $esc = false;
                while ($pos < $len) {
                    if ($esc) {
                        $esc = false;
                    } elseif ($objJson[$pos] === '\\') {
                        $esc = true;
                    } elseif ($objJson[$pos] === '"') {
                        break;
                    }
                    $pos++;
                }
                if ($pos >= $len) {
                    return $valueStart; // 닫히지 않은 문자열
                }
                $pos++; // " 다음
            } elseif ($objJson[$pos] === '{' || $objJson[$pos] === '[') {
                // 중첩 객체/배열 - 매칭 찾기
                $bracket = $objJson[$pos];
                $closeBracket = ($bracket === '{') ? '}' : ']';
                $count = 1;
                $pos++;
                while ($pos < $len && $count > 0) {
                    if ($objJson[$pos] === $bracket) $count++;
                    elseif ($objJson[$pos] === $closeBracket) $count--;
                    $pos++;
                }
            } else {
                // 숫자, bool, null
                while ($pos < $len && !in_array($objJson[$pos], [',', '}', ' ', "\t", "\r", "\n"])) {
                    $pos++;
                }
            }

            // , 또는 } 찾기
            while ($pos < $len && ctype_space($objJson[$pos])) $pos++;
            if ($pos < $len && $objJson[$pos] === ',') $pos++;
        }

        return 0;
    }

    /**
     * 진행중 주문 Redis에 Insert/Update
     * 키: insung:order:{serial_number} (인성접수번호)
     * TTL 없음 (영구 저장, 완료/취소 시 삭제)
     */
    protected function saveProgressOrderToRedis(array $order): void
    {
        if (!$this->useRedis) {
            return;
        }

        try {
            $serialNumber = $order['serial_number'] ?? $order['order_no'] ?? null;
            if (!$serialNumber) {
                // log_message('warning', 'Redis 저장 실패: serial_number 없음');
                return;
            }

            // 키: insung:order:{인성접수번호}
            $key = self::REDIS_ORDER_PREFIX . $serialNumber;

            // 기존 데이터 확인 (Update 시 기존 정보 유지)
            $existingData = $this->redis->get($key);
            if ($existingData) {
                $existing = json_decode($existingData, true);
                // 기존 데이터와 병합 (새 데이터 우선)
                $order = array_merge($existing, $order);
                $order['updated_at'] = date('Y-m-d H:i:s');
            } else {
                $order['created_at'] = date('Y-m-d H:i:s');
                $order['updated_at'] = date('Y-m-d H:i:s');
            }

            // TTL 없이 영구 저장 (SET)
            $this->redis->set($key, json_encode($order));

            // log_message('debug', "Redis 저장: {$key}");
        } catch (\Exception $e) {
            // log_message('error', 'Redis 저장 오류: ' . $e->getMessage());
        }
    }

    /**
     * Redis 전체 삭제 후 전체 주문 일괄 저장 (Pipeline 사용)
     * 진행중 + 완료 + 취소 모든 상태의 주문 저장
     *
     * @param array $orders 전체 주문 배열
     */
    protected function clearAndBulkSaveAllOrders(array $orders): void
    {
        if (!$this->useRedis || empty($orders)) {
            return;
        }

        try {
            // 1. 기존 진행중 주문 전체 삭제
            $existingKeys = $this->redis->keys(self::REDIS_ORDER_PREFIX . '*');
            if (!empty($existingKeys)) {
                $this->redis->del($existingKeys);
                // log_message('info', "Redis: 기존 주문 " . count($existingKeys) . "건 삭제");
            }

            // 2. Pipeline으로 일괄 저장
            $pipe = $this->redis->multi(\Redis::PIPELINE);

            foreach ($orders as $order) {
                $serialNumber = $order['serial_number'] ?? $order['order_no'] ?? null;
                if (!$serialNumber) {
                    continue;
                }

                $key = self::REDIS_ORDER_PREFIX . $serialNumber;
                $pipe->set($key, json_encode($order, JSON_UNESCAPED_UNICODE));
            }

            $pipe->exec();

            // log_message('info', "Redis: 전체 주문 " . count($orders) . "건 일괄 저장 완료");

        } catch (\Exception $e) {
            // log_message('error', 'Redis 일괄 저장 오류: ' . $e->getMessage());
        }
    }

    /**
     * Redis에서 진행중 주문 삭제 (완료/취소 시)
     */
    protected function removeOrderFromRedis(string $serialNumber): void
    {
        if (!$this->useRedis || empty($serialNumber)) {
            return;
        }

        try {
            $key = self::REDIS_ORDER_PREFIX . $serialNumber;
            $this->redis->del($key);
            // log_message('debug', "Redis 삭제: {$key}");
        } catch (\Exception $e) {
            // log_message('error', 'Redis 삭제 오류: ' . $e->getMessage());
        }
    }

    /**
     * 완료 주문 DB 저장 (+ Redis에서 삭제)
     */
    protected function saveCompletedOrderToDb(array $order): void
    {
        try {
            $this->saveOrderToDb($order, 'completed');

            // Redis에서 삭제 (진행중 -> 완료)
            $serialNumber = $order['serial_number'] ?? $order['order_no'] ?? null;
            if ($serialNumber) {
                $this->removeOrderFromRedis($serialNumber);
            }
        } catch (\Exception $e) {
            // log_message('error', '완료 주문 DB 저장 오류: ' . $e->getMessage());
        }
    }

    /**
     * 취소 주문 DB 저장 (+ Redis에서 삭제)
     */
    protected function saveCancelledOrderToDb(array $order): void
    {
        try {
            $this->saveOrderToDb($order, 'cancelled');

            // Redis에서 삭제 (진행중 -> 취소)
            $serialNumber = $order['serial_number'] ?? $order['order_no'] ?? null;
            if ($serialNumber) {
                $this->removeOrderFromRedis($serialNumber);
            }
        } catch (\Exception $e) {
            // log_message('error', '취소 주문 DB 저장 오류: ' . $e->getMessage());
        }
    }

    /**
     * 주문 DB 저장 (공통)
     * API 응답 필드: serial_number, order_state, order_date 등
     */
    protected function saveOrderToDb(array $order, string $status): void
    {
        $serialNumber = $order['serial_number'] ?? null;
        if (!$serialNumber) {
            // log_message('warning', "InsungOrderService::saveOrderToDb - serial_number 없음, 저장 스킵");
            return;
        }

        try {
            // tbl_insung_order_history 테이블에 저장
            $builder = $this->db->table('tbl_insung_order_history');

            // 중복 체크 (새 builder 인스턴스 사용)
            $checkBuilder = $this->db->table('tbl_insung_order_history');
            $exists = $checkBuilder->where('serial_number', $serialNumber)->countAllResults();

            // order_date 형식 변환 (YYYYMMDD -> YYYY-MM-DD)
            $orderDate = $order['order_date'] ?? $order['pickup_date'] ?? date('Ymd');
            if (strlen($orderDate) === 8 && is_numeric($orderDate)) {
                $orderDate = substr($orderDate, 0, 4) . '-' . substr($orderDate, 4, 2) . '-' . substr($orderDate, 6, 2);
            }

            $data = [
                'serial_number' => $serialNumber,
                'cc_code' => $order['cc_code'] ?? null,
                'api_name' => $order['api_name'] ?? null,
                'order_state' => $order['order_state'] ?? null,
                'state' => $order['state'] ?? null,
                'status_type' => $status,
                'order_data' => json_encode($order, JSON_UNESCAPED_UNICODE),
                'order_date' => $orderDate,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($exists > 0) {
                // 업데이트
                $builder->where('serial_number', $serialNumber)->update($data);
                // log_message('debug', "InsungOrderService::saveOrderToDb - 업데이트: {$serialNumber}, status={$status}");
            } else {
                // 신규 저장
                $data['created_at'] = date('Y-m-d H:i:s');
                $builder->insert($data);
                // log_message('info', "InsungOrderService::saveOrderToDb - 신규저장: {$serialNumber}, status={$status}, order_state={$data['order_state']}");
            }
        } catch (\Exception $e) {
            // log_message('error', "InsungOrderService::saveOrderToDb 오류: {$serialNumber}, " . $e->getMessage());
        }
    }

    /**
     * 완료/취소 주문 DB 일괄 저장 (신규만 INSERT)
     * 이미 DB에 있는 serial_number는 스킵
     *
     * @param array $orders 주문 배열
     * @param string $status 상태 (completed|cancelled)
     */
    protected function bulkSaveOrdersToDb(array $orders, string $status): void
    {
        if (empty($orders)) {
            return;
        }

        try {
            $now = date('Y-m-d H:i:s');

            // 1. 모든 serial_number 수집
            $allSerialNumbers = [];
            foreach ($orders as $order) {
                $serialNumber = $order['serial_number'] ?? null;
                if ($serialNumber) {
                    $allSerialNumbers[] = $serialNumber;
                }
            }

            if (empty($allSerialNumbers)) {
                return;
            }

            // 2. DB에 이미 존재하는 serial_number 조회
            $existingSerials = [];
            $existingRecords = $this->db->table('tbl_insung_order_history')
                ->select('serial_number')
                ->whereIn('serial_number', $allSerialNumbers)
                ->get()
                ->getResultArray();

            foreach ($existingRecords as $record) {
                $existingSerials[$record['serial_number']] = true;
            }

            $skippedCount = count($existingSerials);

            // 3. 신규 주문만 batchData에 추가
            $batchData = [];
            foreach ($orders as $order) {
                $serialNumber = $order['serial_number'] ?? null;
                if (!$serialNumber) {
                    continue;
                }

                // 이미 DB에 있으면 스킵
                if (isset($existingSerials[$serialNumber])) {
                    continue;
                }

                // order_date 형식 변환 (YYYYMMDD -> YYYY-MM-DD)
                $orderDate = $order['order_date'] ?? $order['pickup_date'] ?? date('Ymd');
                if (strlen($orderDate) === 8 && is_numeric($orderDate)) {
                    $orderDate = substr($orderDate, 0, 4) . '-' . substr($orderDate, 4, 2) . '-' . substr($orderDate, 6, 2);
                }

                $batchData[] = [
                    'serial_number' => $serialNumber,
                    'cc_code' => $order['cc_code'] ?? null,
                    'api_name' => $order['api_name'] ?? null,
                    'order_state' => $order['order_state'] ?? null,
                    'state' => $order['state'] ?? null,
                    'status_type' => $status,
                    'order_data' => json_encode($order, JSON_UNESCAPED_UNICODE),
                    'order_date' => $orderDate,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            if (empty($batchData)) {
                // if ($skippedCount > 0) {
                //     log_message('debug', "InsungOrderService: {$status} 주문 {$skippedCount}건 스킵 (이미 DB에 존재)");
                // }
                return;
            }

            // 4. 신규 주문만 INSERT (insertBatch 사용)
            $this->db->table('tbl_insung_order_history')->insertBatch($batchData);

            // log_message('info', "InsungOrderService: {$status} 주문 " . count($batchData) . "건 신규 저장, {$skippedCount}건 스킵");

        } catch (\Exception $e) {
            // log_message('error', "InsungOrderService::bulkSaveOrdersToDb 오류: " . $e->getMessage());

            // Fallback: 개별 저장 (INSERT IGNORE 방식)
            // log_message('info', "InsungOrderService: Fallback - 개별 저장 시도");
            foreach ($orders as $order) {
                $this->saveOrderToDbIfNotExists($order, $status);
            }
        }
    }

    /**
     * 개별 주문 DB 저장 (존재하지 않는 경우에만)
     */
    protected function saveOrderToDbIfNotExists(array $order, string $status): void
    {
        $serialNumber = $order['serial_number'] ?? null;
        if (!$serialNumber) {
            return;
        }

        try {
            // 이미 존재하는지 확인
            $exists = $this->db->table('tbl_insung_order_history')
                ->where('serial_number', $serialNumber)
                ->countAllResults() > 0;

            if ($exists) {
                return; // 스킵
            }

            // 신규 저장
            $this->saveOrderToDb($order, $status);

        } catch (\Exception $e) {
            // log_message('error', "InsungOrderService::saveOrderToDbIfNotExists 오류: " . $e->getMessage());
        }
    }

    /**
     * 요약 정보 Redis 캐싱
     */
    protected function cacheSummaryToRedis(array $summary, string $date): void
    {
        if (!$this->useRedis) {
            return;
        }

        try {
            $key = self::REDIS_SUMMARY_KEY . $date;
            $this->redis->setex($key, self::REDIS_SUMMARY_TTL, json_encode($summary));
        } catch (\Exception $e) {
            // log_message('error', 'Redis 요약 캐싱 오류: ' . $e->getMessage());
        }
    }

    /**
     * Redis 통계 조회
     */
    public function getRedisStats(): array
    {
        if (!$this->useRedis) {
            return ['redis_available' => false];
        }

        try {
            $info = $this->redis->info();
            // 진행중 주문 키 패턴: insung:order:*
            $progressKeys = $this->redis->keys(self::REDIS_ORDER_PREFIX . '*');

            return [
                'redis_available' => true,
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? 'N/A',
                'progress_order_count' => count($progressKeys),
                'uptime_in_days' => $info['uptime_in_days'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'redis_available' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Redis에서 전체 진행중 주문 조회
     */
    public function getAllProgressOrdersFromRedis(): array
    {
        if (!$this->useRedis) {
            return [];
        }

        try {
            // 패턴: insung:order:*
            $pattern = self::REDIS_ORDER_PREFIX . '*';
            $keys = $this->redis->keys($pattern);

            $orders = [];
            foreach ($keys as $key) {
                $data = $this->redis->get($key);
                if ($data) {
                    $orders[] = json_decode($data, true);
                }
            }

            return $orders;
        } catch (\Exception $e) {
            // log_message('error', 'Redis 조회 오류: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Redis에서 특정 주문 조회
     */
    public function getOrderFromRedis(string $serialNumber): ?array
    {
        if (!$this->useRedis || empty($serialNumber)) {
            return null;
        }

        try {
            $key = self::REDIS_ORDER_PREFIX . $serialNumber;
            $data = $this->redis->get($key);

            if ($data) {
                return json_decode($data, true);
            }

            return null;
        } catch (\Exception $e) {
            // log_message('error', 'Redis 조회 오류: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Redis 연결 상태 확인
     */
    public function isRedisAvailable(): bool
    {
        return $this->useRedis;
    }

    /**
     * DB에서 완료/취소 주문 조회
     *
     * @param string $date 조회 날짜 (YYYY-MM-DD)
     * @param string|null $statusType 상태 (completed|cancelled|null=전체)
     * @return array
     */
    public function getOrdersFromDb(string $date, ?string $statusType = null): array
    {
        try {
            $builder = $this->db->table('tbl_insung_order_history');
            $builder->where('order_date', $date);

            if ($statusType) {
                $builder->where('status_type', $statusType);
            }

            $query = $builder->get();
            $results = $query ? $query->getResultArray() : [];

            // order_data JSON을 파싱하여 반환
            $orders = [];
            foreach ($results as $row) {
                $orderData = json_decode($row['order_data'] ?? '{}', true);
                if ($orderData) {
                    // DB 메타 정보 추가
                    $orderData['_db_status'] = $row['status_type'];
                    $orderData['_db_updated_at'] = $row['updated_at'];
                    $orders[] = $orderData;
                }
            }

            return $orders;

        } catch (\Exception $e) {
            // log_message('error', 'InsungOrderService::getOrdersFromDb 오류: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Redis에서 전체 주문 조회 (진행중 + 완료 + 취소)
     * order_state 기준으로 분류
     *
     * 참고: Redis에는 fetchAllCallCenterOrders() 호출 시 기존 데이터를 전부 삭제 후
     * 오늘 날짜 주문만 새로 저장되므로 별도 날짜 필터링 불필요
     *
     * @param string $date 조회 날짜 (YYYY-MM-DD) - 현재 미사용 (향후 확장용)
     * @return array
     */
    public function getAllOrdersCombined(string $date): array
    {
        $startTime = microtime(true);

        $allOrders = [];
        $summary = [
            'progress_orders' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'total_orders' => 0
        ];

        // Redis에서 전체 주문 조회 (날짜 필터링 없이 전체 반환)
        $redisOrders = $this->getAllOrdersFromRedis();
        $redisTime = microtime(true);

        foreach ($redisOrders as $order) {
            // order_state 기준으로 분류
            $orderState = (string)($order['order_state'] ?? $order['state'] ?? '');

            if (in_array($orderState, self::COMPLETED_STATES, true)) {
                $order['_status_type'] = 'completed';
                $summary['completed_orders']++;
            } elseif (in_array($orderState, self::CANCELLED_STATES, true)) {
                $order['_status_type'] = 'cancelled';
                $summary['cancelled_orders']++;
            } else {
                $order['_status_type'] = 'progress';
                $summary['progress_orders']++;
            }

            $allOrders[] = $order;
        }

        $summary['total_orders'] = count($allOrders);

        $endTime = microtime(true);
        $redisElapsed = round(($redisTime - $startTime) * 1000, 2);
        $classifyElapsed = round(($endTime - $redisTime) * 1000, 2);
        $totalElapsed = round(($endTime - $startTime) * 1000, 2);

        log_message('info', "InsungOrderService::getAllOrdersCombined - Redis조회 {$redisElapsed}ms, 분류처리 {$classifyElapsed}ms, 총 {$totalElapsed}ms, 주문수 " . count($allOrders) . "건");

        return [
            'orders' => $allOrders,
            'summary' => $summary
        ];
    }

    /**
     * Redis에서 전체 주문 조회 (모든 상태)
     */
    protected function getAllOrdersFromRedis(): array
    {
        if (!$this->useRedis) {
            return [];
        }

        try {
            $startTime = microtime(true);

            $keys = $this->redis->keys(self::REDIS_ORDER_PREFIX . '*');
            $keysTime = microtime(true);

            if (empty($keys)) {
                log_message('info', "InsungOrderService::getAllOrdersFromRedis - keys조회 " . round(($keysTime - $startTime) * 1000, 2) . "ms, 키 0개");
                return [];
            }

            $orders = [];
            $values = $this->redis->mGet($keys);
            $mgetTime = microtime(true);

            foreach ($values as $value) {
                if ($value) {
                    $order = json_decode($value, true);
                    if ($order) {
                        $orders[] = $order;
                    }
                }
            }
            $parseTime = microtime(true);

            $keysElapsed = round(($keysTime - $startTime) * 1000, 2);
            $mgetElapsed = round(($mgetTime - $keysTime) * 1000, 2);
            $parseElapsed = round(($parseTime - $mgetTime) * 1000, 2);
            $totalElapsed = round(($parseTime - $startTime) * 1000, 2);

            log_message('info', "InsungOrderService::getAllOrdersFromRedis - keys조회 {$keysElapsed}ms, mGet {$mgetElapsed}ms, JSON파싱 {$parseElapsed}ms, 총 {$totalElapsed}ms, 키 " . count($keys) . "개, 주문 " . count($orders) . "건");

            return $orders;
        } catch (\Exception $e) {
            log_message('error', 'Redis 전체 조회 오류: ' . $e->getMessage());
            return [];
        }
    }
}