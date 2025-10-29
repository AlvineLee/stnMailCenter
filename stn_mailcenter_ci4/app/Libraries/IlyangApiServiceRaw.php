<?php

namespace App\Libraries;

class IlyangApiServiceRaw
{
    protected $baseUrl;
    protected $apiKey;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = 'https://api.ilyang.co.kr';
        $this->apiKey = 'your_api_key_here';
        $this->secretKey = 'your_secret_key_here';
    }

    /**
     * curl을 사용한 API 요청
     */
    public function sendCurlRequest($endpoint, $method = 'POST', $data = [], $headers = [])
    {
        $url = $this->baseUrl . $endpoint;
        
        // 기본 헤더 설정
        $defaultHeaders = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'X-API-Key: ' . $this->apiKey,
            'X-Secret-Key: ' . $this->secretKey,
            'Accept: application/json'
        ];

        // 커스텀 헤더와 병합
        $allHeaders = array_merge($defaultHeaders, $headers);

        // curl 초기화
        $ch = curl_init();
        
        // curl 옵션 설정
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_SSL_VERIFYPEER => false, // 개발 환경에서만 사용
            CURLOPT_SSL_VERIFYHOST => false  // 개발 환경에서만 사용
        ]);

        // POST/PUT 요청인 경우 데이터 설정
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // 요청 실행
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            log_message('error', 'Ilyang API cURL Error: ' . $error);
            return [
                'success' => false,
                'error' => $error
            ];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status_code' => $httpCode,
            'data' => json_decode($response, true),
            'raw_response' => $response
        ];
    }

    /**
     * 배송 요청 예시
     */
    public function createDelivery($deliveryData)
    {
        $endpoint = '/api/delivery/create';
        
        $data = [
            'order_number' => $deliveryData['order_number'],
            'sender' => [
                'name' => $deliveryData['sender_name'],
                'phone' => $deliveryData['sender_phone'],
                'address' => $deliveryData['sender_address']
            ],
            'receiver' => [
                'name' => $deliveryData['receiver_name'],
                'phone' => $deliveryData['receiver_phone'],
                'address' => $deliveryData['receiver_address']
            ],
            'item_info' => [
                'type' => $deliveryData['item_type'],
                'weight' => $deliveryData['weight'],
                'quantity' => $deliveryData['quantity']
            ]
        ];

        return $this->sendCurlRequest($endpoint, 'POST', $data);
    }
}
