<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiServiceFactory;
use App\Libraries\IlyangApiSpec;
use App\Helpers\IpHelper;

class ApiTest extends BaseController
{
    /**
     * API 테스트 메인 페이지
     */
    public function index()
    {
        $data = [
            'title' => 'API 테스트',
            'current_ip' => IpHelper::getRealIpAddress(),
            'whitelist_ip' => IlyangApiSpec::WHITELIST_IP,
            'all_ip_headers' => IpHelper::getAllIpHeaders()
        ];

        return view('api_test/index', $data);
    }

    /**
     * 일양 API 테스트
     */
    public function testIlyangApi()
    {
        try {
            $apiService = ApiServiceFactory::create('ilyang', true); // 테스트 모드
            
            // 테스트 데이터 생성 (모든 필수 필드 포함)
            $testData = [
                'order_number' => 'TEST-' . date('YmdHis'),
                'departure_company_name' => '테스트발송회사',
                'departure_contact' => '010-1234-5678',
                'departure_address' => '서울시 강남구 테헤란로 123',
                'departure_manager' => '홍길동',
                'departure_phone2' => '02-1234-5678',
                'destination_company_name' => '테스트수취회사',
                'destination_contact' => '010-9876-5432',
                'destination_address' => '서울시 서초구 서초대로 456',
                'destination_manager' => '김철수',
                'destination_phone2' => '02-9876-5432',
                'item_type' => '테스트상품',
                'item_price' => '10000',
                'weight' => '1',
                'quantity' => '1',
                'payment_type' => 'prepaid',
                'delivery_instructions' => 'API 테스트용 주문',
                'delivery_notes' => '테스트',
                'delivery_fee' => '3000'
            ];

            $result = $apiService->createDelivery($testData);

            return $this->response->setJSON([
                'success' => true,
                'message' => '일양 API 테스트 완료',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '일양 API 테스트 실패: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * IP 정보 테스트
     */
    public function testIpInfo()
    {
        $data = [
            'current_ip' => IpHelper::getRealIpAddress(),
            'whitelist_ip' => IlyangApiSpec::WHITELIST_IP,
            'all_headers' => IpHelper::getAllIpHeaders(),
            'is_private_ip' => IpHelper::isPrivateIp(IpHelper::getRealIpAddress()),
            'public_ip' => IpHelper::getPublicIpFromService()
        ];

        return $this->response->setJSON([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * API 명세 정보 조회
     */
    public function getApiSpec()
    {
        $data = [
            'endpoints' => IlyangApiSpec::ENDPOINTS,
            'headers' => IlyangApiSpec::HEADERS,
            'whitelist_ip' => IlyangApiSpec::WHITELIST_IP,
            'required_fields' => IlyangApiSpec::REQUIRED_FIELDS,
            'optional_fields' => IlyangApiSpec::OPTIONAL_FIELDS,
            'validation_rules' => IlyangApiSpec::VALIDATION_RULES
        ];

        return $this->response->setJSON([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * 샘플 데이터 생성
     */
    public function generateSampleData()
    {
        $sampleData = IlyangApiSpec::getSampleRequestBody();
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $sampleData
        ]);
    }
}
