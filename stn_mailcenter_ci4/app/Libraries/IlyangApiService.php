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
        
        // 클라이언트 실제 IP 주소 가져오기
        $clientIp = IpHelper::getRealIpAddress();
        
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
            log_message('info', "Ilyang API Request - Base URL: {$this->baseUrl}, Endpoint: {$endpoint}");
            log_message('info', "Ilyang API Request - Client IP: {$clientIp}, WhiteList IP: " . IlyangApiSpec::WHITELIST_IP);
            log_message('info', "Ilyang API Headers: " . json_encode($headers, JSON_UNESCAPED_UNICODE));
            
            // JSON 데이터를 문자열로 변환
            $jsonData = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
            log_message('info', "Ilyang API Request Body: " . $jsonData);
            
            $response = $this->client->post($endpoint, [
                'headers' => $headers,
                'body' => $jsonData,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody();
            $responseData = json_decode($responseBody, true);

            log_message('info', "Ilyang API Response - Status: {$statusCode}, Body: " . $responseBody);

            return [
                'success' => $statusCode >= 200 && $statusCode < 300,
                'status_code' => $statusCode,
                'data' => $responseData,
                'raw_response' => $responseBody
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
            'ilySndManName' => $orderData['departure_manager'] ?? '',
            'ilySndTel1' => $orderData['departure_contact'] ?? '',
            'ilySndTel2' => $orderData['departure_phone2'] ?? '',
            'ilySndZip' => $this->extractZipCode($orderData['departure_address'] ?? ''),
            'ilySndAddr' => $orderData['departure_address'] ?? '',
            'ilySndCenter' => $this->getCenterCode($orderData['departure_address'] ?? ''),
            'ilyRcvName' => $orderData['destination_company_name'] ?? '',
            'ilyRcvManName' => $orderData['destination_manager'] ?? '',
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
            'ilyCusApild' => $this->ediCode
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
     */
    public function createDelivery($deliveryData)
    {
        $waybillData = $this->convertOrderToWaybillData($deliveryData);
        return $this->sendWaybillData($waybillData);
    }

    /**
     * 배송 상태 조회 (향후 구현)
     */
    public function getDeliveryStatus($trackingNumber)
    {
        // TODO: 일양 API 상태 조회 구현
        return [
            'success' => false,
            'message' => '상태 조회 기능은 아직 구현되지 않았습니다.'
        ];
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
}