<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use App\Libraries\IlyangApiSpec;
use App\Helpers\IpHelper;

class IlyangApiService
{
    protected $client;
    protected $baseUrl;
    protected $accessKey;
    protected $accountNo;
    protected $ediCode;
    protected $isTestMode;
    protected $apiConfig;

    public function __construct($isTestMode = true, $apiConfig = null)
    {
        $this->isTestMode = $isTestMode;
        $this->apiConfig = $apiConfig;
        
        $this->baseUrl = $isTestMode ? 
            IlyangApiSpec::ENDPOINTS['test'] : 
            IlyangApiSpec::ENDPOINTS['prod'];
        
        // api_config가 있으면 우선 사용, 없으면 기본값 사용
        if (!empty($apiConfig)) {
            $this->accessKey = $apiConfig['access_key'] ?? IlyangApiSpec::HEADERS['accessKey'];
            $this->accountNo = $apiConfig['account_no'] ?? IlyangApiSpec::HEADERS['accountNo'];
            $this->ediCode = $apiConfig['edi_code'] ?? IlyangApiSpec::HEADERS['ediCode'];
        } else {
            $this->accessKey = IlyangApiSpec::HEADERS['accessKey'];
            $this->accountNo = IlyangApiSpec::HEADERS['accountNo'];
            $this->ediCode = IlyangApiSpec::HEADERS['ediCode'];
        }

        $this->client = \Config\Services::curlrequest([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);
    }

    /**
     * 운송장정보 전송 API
     * 
     * @param array $waybillData 운송장 데이터
     * @return array API 응답 결과
     */
    public function sendWaybillData($waybillData)
    {
        $endpoint = 'logisticsData.json';
        
        // 클라이언트 실제 IP 주소 가져오기 (공인 IP 우선)
        $clientIp = IpHelper::getRealIpAddress();
        
        // IP 헤더 정보 로깅 (디버깅용)
        $ipHeaders = IpHelper::getAllIpHeaders();
        log_message('debug', "Ilyang API - IP Headers: " . json_encode($ipHeaders, JSON_UNESCAPED_UNICODE));
        log_message('debug', "Ilyang API - Selected Client IP: {$clientIp}, isPrivate: " . (IpHelper::isPrivateIp($clientIp) ? 'yes' : 'no'));
        
        // 요청 헤더 설정 (IlyangApiSpec 사용)
        $headers = [
            'accessKey' => $this->accessKey,
            'accountNo' => $this->accountNo,
            'ediCode' => $this->ediCode,
            'filename' => $this->ediCode . date('Ymd_Hi'), // ediCodeYYYYMMDD_hhmi 형식 (운송사별 설정 사용)
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Forwarded-For' => $clientIp
        ];

        // 요청 바디 구성
        $requestBody = [
            'dataList' => [$waybillData]
        ];

        try {
            // WhiteList IP 검증 로그
            $whitelistIps = is_array(IlyangApiSpec::WHITELIST_IP) ? IlyangApiSpec::WHITELIST_IP : [IlyangApiSpec::WHITELIST_IP];
            log_message('info', "Ilyang API Request - Base URL: {$this->baseUrl}, Endpoint: {$endpoint}");
            log_message('info', "Ilyang API Request - Client IP: {$clientIp}, WhiteList IP: " . implode(', ', $whitelistIps));
            log_message('info', "Ilyang API Headers: " . json_encode($headers, JSON_UNESCAPED_UNICODE));
            
            // JSON 데이터를 문자열로 변환
            $jsonData = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
            log_message('info', "Ilyang API Request Body: " . $jsonData);
            
            // 전체 URL 구성 (base_uri와 endpoint 합치기)
            $fullUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
            log_message('info', "Ilyang API Full URL: {$fullUrl}");
            
            // CURLRequest를 전체 URL로 직접 호출
            $response = $this->client->post($fullUrl, [
                'headers' => $headers,
                'body' => $jsonData,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();
            $responseData = json_decode($responseBody, true);

            log_message('info', "Ilyang API Response - Status: {$statusCode}, Body: " . $responseBody);

            // HTTP 상태 코드 확인
            $httpSuccess = $statusCode >= 200 && $statusCode < 300;
            
            // 비즈니스 로직 응답 코드 확인 (returnCode와 successCount)
            $businessSuccess = false;
            $returnCode = '';
            $returnDesc = '';
            $successCount = 0;
            $totalCount = 0;
            
            if ($httpSuccess && is_array($responseData) && isset($responseData['head'])) {
                $head = $responseData['head'];
                $returnCode = $head['returnCode'] ?? '';
                $returnDesc = $head['returnDesc'] ?? '';
                $successCount = (int)($head['successCount'] ?? 0);
                $totalCount = (int)($head['totalCount'] ?? 0);
                
                // returnCode가 'R0' (OK)이고 successCount가 0보다 크면 성공
                // 또는 returnCode가 없고 successCount가 0보다 크면 성공
                if ($returnCode === 'R0' || ($returnCode === '' && $successCount > 0)) {
                    $businessSuccess = true;
                } else {
                    // returnCode가 'R0'이 아니면 실패 (R2: 고객정보없음 등)
                    log_message('warning', "Ilyang API Business Error - returnCode: {$returnCode}, returnDesc: {$returnDesc}, successCount: {$successCount}, totalCount: {$totalCount}");
                }
            } else {
                // 응답 구조가 올바르지 않으면 HTTP 성공 여부만 사용
                $businessSuccess = $httpSuccess;
            }

            return [
                'success' => $httpSuccess && $businessSuccess,
                'status_code' => $statusCode,
                'data' => $responseData,
                'raw_response' => $responseBody,
                'return_code' => $returnCode,
                'return_desc' => $returnDesc,
                'success_count' => $successCount,
                'total_count' => $totalCount
            ];

        } catch (\Exception $e) {
            log_message('error', 'Ilyang API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 주문 데이터를 일양 API 형식으로 변환
     * 
     * @param array $orderData 주문 데이터
     * @return array 일양 API 형식 데이터
     */
    public function convertOrderToWaybillData($orderData)
    {
        // 현재 시간을 기준으로 시퀀스 번호 생성
        $sequenceNo = date('YmdHis') . rand(1000, 9999);
        
        // 발송일자 (오늘 날짜)
        $shippingDate = date('Ymd');
        
        // 운송장번호는 이미 할당된 값 사용 (tbl_orders.shipping_tracking_number)
        $waybillNo = $orderData['shipping_tracking_number'] ?? '';
        
        if (empty($waybillNo)) {
            throw new \Exception('운송장번호가 할당되지 않았습니다.');
        }
        
        $waybillData = [
            'ilySeqNo' => $sequenceNo,
            'ilyShpDate' => $shippingDate,
            'ilyRecType' => 'CC', // 고객출력자료 송신
            'ilyAwbNo' => $waybillNo,
            'ilyCusAcno' => $this->accountNo,
            'ilyCusOrdno' => $orderData['order_number'] ?? '',
            'ilyGodName' => $orderData['item_type'] ?? '일반상품',
            'ilyGodPrice' => $orderData['item_price'] ?? '0',
            'ilyDlvRmks' => $orderData['delivery_notes'] ?? '',
            'ilySndName' => $orderData['departure_company_name'] ?? '',
            'ilySndManName' => $this->sanitizeManagerName($orderData['departure_manager'] ?? ''),
            'ilySndTel1' => $orderData['departure_contact'] ?? '',
            'ilySndTel2' => $orderData['departure_phone2'] ?? '',
            'ilySndZip' => $this->extractZipCode($orderData['departure_address'] ?? ''),
            'ilySndAddr' => $orderData['departure_address'] ?? '',
            'ilySndCenter' => $this->getCenterCode($orderData['departure_address'] ?? ''),
            'ilyRcvName' => $orderData['destination_company_name'] ?? '',
            'ilyRcvManName' => $this->sanitizeManagerName($orderData['destination_manager'] ?? ''),
            'ilyRcvTel1' => $orderData['destination_contact'] ?? '',
            'ilyRcvTel2' => $orderData['destination_phone2'] ?? '',
            'ilyRcvZip' => $this->extractZipCode($orderData['destination_address'] ?? ''),
            'ilyRcvAddr' => $orderData['destination_address'] ?? '',
            'ilyRcvCenter' => $this->getCenterCode($orderData['destination_address'] ?? ''),
            'ilyDlvMesg' => $orderData['delivery_instructions'] ?? '',
            'ilyPayType' => $this->convertPaymentType($orderData['payment_type'] ?? ''),
            'ilyBoxQty' => $orderData['quantity'] ?? '1',
            'ilyBoxWgt' => $orderData['weight'] ?? '1',
            'ilyAmtCash' => $orderData['delivery_fee'] ?? '0',
            'ilyOrgAwbno' => '',
            'ilyCusApild' => $this->ediCode,
            'ilyCusApiId' => $this->ediCode  // 일양 API 응답에서 요구하는 필드 (ilyCusApild와 동일한 값)
        ];

        // 필수 필드 검증
        $validationErrors = $this->validateWaybillData($waybillData);
        if (!empty($validationErrors)) {
            log_message('error', 'Waybill data validation failed: ' . implode(', ', $validationErrors));
        }

        return $waybillData;
    }

    /**
     * 결제 타입을 일양 API 형식으로 변환
     */
    private function convertPaymentType($paymentType)
    {
        $mapping = [
            'credit' => '11',    // 신용
            'prepaid' => '21',   // 선불
            'cod' => '22',       // 착불
            'cash' => '22'       // 현금 -> 착불
        ];

        return $mapping[$paymentType] ?? '11';
    }

    /**
     * 데이터 유효성 검증
     */
    public function validateWaybillData($data)
    {
        $errors = IlyangApiSpec::validateRequiredFields($data);
        $formatErrors = IlyangApiSpec::validateDataFormat($data);
        
        return array_merge($errors, $formatErrors);
    }

    /**
     * 주소에서 우편번호 추출
     */
    private function extractZipCode($address)
    {
        // 우편번호 패턴 매칭 (5자리 또는 6자리)
        if (preg_match('/(\d{5,6})/', $address, $matches)) {
            return $matches[1];
        }
        return '00000';
    }

    /**
     * 주소 기반 센터 코드 반환
     */
    private function getCenterCode($address)
    {
        // 간단한 지역별 센터 코드 매핑
        if (strpos($address, '서울') !== false) {
            return 'SELNS';
        } elseif (strpos($address, '경기') !== false) {
            return 'GGGNS';
        } elseif (strpos($address, '인천') !== false) {
            return 'ICNNS';
        }
        
        return 'SELNS'; // 기본값
    }

    /**
     * 배송 요청 생성 (기존 호환성 유지)
     * 
     * @param array $deliveryData 주문 데이터
     * @return array API 응답 결과 (waybillData 포함)
     */
    public function createDelivery($deliveryData)
    {
        $waybillData = $this->convertOrderToWaybillData($deliveryData);
        $result = $this->sendWaybillData($waybillData);
        
        // 변환된 waybillData를 결과에 포함 (일양 접수 데이터 저장용)
        if ($result['success']) {
            $result['waybill_data'] = $waybillData;
        }
        
        return $result;
    }

    /**
     * 배송 상태 조회 (운송장번호로 조회)
     * 
     * @param string|array $trackingNumbers 운송장번호 (문자열 또는 배열, 최대 100건)
     * @return array API 응답 결과
     */
    public function getDeliveryStatus($trackingNumbers)
    {
        $endpoint = 'waybillData.json';
        
        // 배열이 아니면 배열로 변환
        if (!is_array($trackingNumbers)) {
            $trackingNumbers = [$trackingNumbers];
        }
        
        // 최대 100건까지 제한
        if (count($trackingNumbers) > 100) {
            return [
                'success' => false,
                'error' => '운송장번호는 최대 100건까지 조회 가능합니다.'
            ];
        }
        
        // 클라이언트 실제 IP 주소 가져오기
        $clientIp = IpHelper::getRealIpAddress();
        
        // 요청 헤더 설정
        $headers = [
            'accessKey' => $this->accessKey,
            'accountNo' => $this->accountNo,
            'ediCode' => $this->ediCode,
            'filename' => $this->ediCode . date('Ymd_Hi'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Forwarded-For' => $clientIp
        ];
        
        // 요청 바디 구성 (운송장번호를 쉼표로 구분)
        $requestBody = [
            'hawbNos' => implode(',', $trackingNumbers)
        ];
        
        try {
            $jsonData = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
            
            // 전체 URL 구성
            $fullUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
            
            // CURLRequest를 전체 URL로 직접 호출
            $response = $this->client->post($fullUrl, [
                'headers' => $headers,
                'body' => $jsonData,
                'http_errors' => false
            ]);
            
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            log_message('info', "Ilyang API getDeliveryStatus - Status: {$statusCode}, hawbNos: " . implode(',', $trackingNumbers));
            
            // HTTP 상태 코드 확인
            $httpSuccess = $statusCode >= 200 && $statusCode < 300;
            
            // 비즈니스 로직 응답 코드 확인
            $businessSuccess = false;
            $returnCode = '';
            $returnDesc = '';
            $successCount = 0;
            $totalCount = 0;
            
            if ($httpSuccess && is_array($responseData) && isset($responseData['head'])) {
                $head = $responseData['head'];
                $returnCode = $head['returnCode'] ?? '';
                $returnDesc = $head['returnDesc'] ?? '';
                $successCount = (int)($head['successCount'] ?? 0);
                $totalCount = (int)($head['totalCount'] ?? 0);
                
                // returnCode가 'R0'이면 성공
                if ($returnCode === 'R0') {
                    $businessSuccess = true;
                } else {
                    log_message('warning', "Ilyang API getDeliveryStatus - Business Error: returnCode={$returnCode}, returnDesc={$returnDesc}");
                }
            } else {
                $businessSuccess = $httpSuccess;
            }
            
            return [
                'success' => $httpSuccess && $businessSuccess,
                'status_code' => $statusCode,
                'data' => $responseData,
                'raw_response' => $responseBody,
                'return_code' => $returnCode,
                'return_desc' => $returnDesc,
                'success_count' => $successCount,
                'total_count' => $totalCount
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'Ilyang API getDeliveryStatus Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 주문번호로 배송 상태 조회
     * 
     * @param string|array $orderNumbers 주문번호 (문자열 또는 배열, 최대 50건)
     * @return array API 응답 결과
     */
    public function getDeliveryStatusByOrderNo($orderNumbers)
    {
        $endpoint = 'orderData.json';
        
        // 배열이 아니면 배열로 변환
        if (!is_array($orderNumbers)) {
            $orderNumbers = [$orderNumbers];
        }
        
        // 최대 50건까지 제한
        if (count($orderNumbers) > 50) {
            return [
                'success' => false,
                'error' => '주문번호는 최대 50건까지 조회 가능합니다.'
            ];
        }
        
        // 클라이언트 실제 IP 주소 가져오기
        $clientIp = IpHelper::getRealIpAddress();
        
        // 요청 헤더 설정
        $headers = [
            'accessKey' => $this->accessKey,
            'accountNo' => $this->accountNo,
            'ediCode' => $this->ediCode,
            'filename' => $this->ediCode . date('Ymd_Hi'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Forwarded-For' => $clientIp
        ];
        
        // 요청 바디 구성 (주문번호를 쉼표로 구분)
        $requestBody = [
            'orderNos' => implode(',', $orderNumbers)
        ];
        
        try {
            $jsonData = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
            
            // 전체 URL 구성
            $fullUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
            
            // CURLRequest를 전체 URL로 직접 호출
            $response = $this->client->post($fullUrl, [
                'headers' => $headers,
                'body' => $jsonData,
                'http_errors' => false
            ]);
            
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            log_message('info', "Ilyang API getDeliveryStatusByOrderNo - Status: {$statusCode}, orderNos: " . implode(',', $orderNumbers));
            
            // HTTP 상태 코드 확인
            $httpSuccess = $statusCode >= 200 && $statusCode < 300;
            
            // 비즈니스 로직 응답 코드 확인
            $businessSuccess = false;
            $returnCode = '';
            $returnDesc = '';
            $successCount = 0;
            $totalCount = 0;
            
            if ($httpSuccess && is_array($responseData) && isset($responseData['head'])) {
                $head = $responseData['head'];
                $returnCode = $head['returnCode'] ?? '';
                $returnDesc = $head['returnDesc'] ?? '';
                $successCount = (int)($head['successCount'] ?? 0);
                $totalCount = (int)($head['totalCount'] ?? 0);
                
                // returnCode가 'R0'이면 성공
                if ($returnCode === 'R0') {
                    $businessSuccess = true;
                } else {
                    log_message('warning', "Ilyang API getDeliveryStatusByOrderNo - Business Error: returnCode={$returnCode}, returnDesc={$returnDesc}");
                }
            } else {
                $businessSuccess = $httpSuccess;
            }
            
            return [
                'success' => $httpSuccess && $businessSuccess,
                'status_code' => $statusCode,
                'data' => $responseData,
                'raw_response' => $responseBody,
                'return_code' => $returnCode,
                'return_desc' => $returnDesc,
                'success_count' => $successCount,
                'total_count' => $totalCount
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'Ilyang API getDeliveryStatusByOrderNo Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 일양 배송상태 코드를 로컬 DB 상태값으로 매핑
     * 
     * @param string $traceCode 일양 배송상태 코드 (PU, AR, BG, WC, DL, EX)
     * @param string $nondlcode 미배송사유 코드 (BA, CA, CM, NH, RD, ND)
     * @return string 로컬 DB 상태값 (pending, processing, completed, delivered, cancelled)
     */
    public function mapTraceCodeToStatus($traceCode, $nondlcode = '')
    {
        // 배송상태 코드 매핑
        $statusMap = [
            'PU' => 'processing',  // 발송사무소 인수 -> 접수완료
            'AR' => 'processing',  // 배송경유지 도착 -> 접수완료
            'BG' => 'completed',   // 배송경유지 출고 -> 배송중
            'WC' => 'completed',   // 직원 배송중 -> 배송중
            'DL' => 'delivered',   // 배달완료 -> 배송완료
            'EX' => 'processing'   // 미배달 -> 접수완료 (재배송 가능)
        ];
        
        // 기본 상태값
        $status = $statusMap[$traceCode] ?? 'processing';
        
        // 미배송사유가 있는 경우 (EX 상태일 때)
        if ($traceCode === 'EX' && !empty($nondlcode)) {
            // 수취거절(RD)인 경우 취소로 처리
            if ($nondlcode === 'RD') {
                $status = 'cancelled';
            }
            // 그 외 미배송은 접수완료 상태 유지 (재배송 가능)
        }
        
        return $status;
    }

    /**
     * 배송 취소 (향후 구현)
     */
    public function cancelDelivery($trackingNumber, $reason = '')
    {
        // TODO: 일양 API 취소 구현
        return [
            'success' => false,
            'message' => '취소 기능은 아직 구현되지 않았습니다.'
        ];
    }

    /**
     * 송장 데이터 조회 (주문 데이터 기반)
     * 
     * @param string $trackingNumber 송장번호
     * @param array $orderData 주문 데이터 (선택적)
     * @return array 송장 데이터
     */
    public function getWaybillData($trackingNumber, $orderData = null)
    {
        // 주문 데이터가 제공되지 않은 경우, DB에서 조회
        if ($orderData === null) {
            $orderModel = new \App\Models\OrderModel();
            $order = $orderModel->where('shipping_tracking_number', $trackingNumber)->first();
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => '송장번호에 해당하는 주문을 찾을 수 없습니다.'
                ];
            }
            
            $orderData = $order;
        }

        // 송장 데이터 형식으로 변환 (뷰에서 기대하는 형식)
        $waybillData = [
            'success' => true,
            'data' => [
                'body' => [
                    'trace' => [
                        [
                            'hawb_no' => $trackingNumber,
                            'order_no' => $orderData['order_number'] ?? '',
                            'sendnm' => $orderData['departure_company_name'] ?? $orderData['departure_address'] ?? '',
                            'recevnm' => $orderData['destination_company_name'] ?? $orderData['destination_address'] ?? '',
                            'itemlist' => [
                                [
                                    'item_name' => $orderData['item_type'] ?? '일반상품',
                                    'quantity' => $orderData['quantity'] ?? '1',
                                    'weight' => $orderData['weight'] ?? '1'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $waybillData;
    }

    /**
     * 일양 API 담당자명 필드 정제
     * - 언더스코어(_) 등 특수문자를 공백으로 대체
     * - 최대 20바이트로 제한 (일양 API 스펙: String 20)
     * - 한글은 2바이트, 영문/숫자/공백은 1바이트로 계산
     *
     * @param string $name 담당자명
     * @return string 정제된 담당자명
     */
    protected function sanitizeManagerName($name)
    {
        if (empty($name)) {
            return '';
        }

        // 언더스코어를 공백으로 대체
        $name = str_replace('_', ' ', $name);

        // 기타 특수문자 제거 (한글, 영문, 숫자, 공백만 허용)
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);

        // 연속된 공백을 하나로
        $name = preg_replace('/\s+/', ' ', $name);

        // 앞뒤 공백 제거
        $name = trim($name);

        // 최대 20바이트로 제한 (한글 2바이트, 영문/숫자/공백 1바이트)
        $name = $this->truncateToBytes($name, 20);

        return $name;
    }

    /**
     * 문자열을 바이트 수 기준으로 자르기 (EUC-KR 기준: 한글 2바이트)
     *
     * @param string $str 원본 문자열 (UTF-8)
     * @param int $maxBytes 최대 바이트 수
     * @return string 잘린 문자열
     */
    protected function truncateToBytes($str, $maxBytes)
    {
        $result = '';
        $byteCount = 0;
        $len = mb_strlen($str, 'UTF-8');

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($str, $i, 1, 'UTF-8');
            // 한글 및 한자 등 동아시아 문자는 2바이트, 그 외 1바이트
            $charBytes = (preg_match('/[\x{AC00}-\x{D7AF}\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}]/u', $char)) ? 2 : 1;

            if ($byteCount + $charBytes > $maxBytes) {
                break;
            }

            $result .= $char;
            $byteCount += $charBytes;
        }

        return $result;
    }
}