<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ApiServiceFactory;
use App\Helpers\IpHelper;

class TestIlyangApi extends BaseCommand
{
    protected $group       = 'api';
    protected $name        = 'api:test-ilyang';
    protected $description = '일양 API 테스트를 실행합니다.';

    public function run(array $params)
    {
        CLI::write('일양 API 테스트를 시작합니다...', 'yellow');
        
        try {
            // IP 정보 출력
            CLI::write('현재 IP: ' . IpHelper::getRealIpAddress(), 'green');
            CLI::write('WhiteList IP: 45.120.69.179', 'green');
            
            // API 서비스 생성
            $apiService = ApiServiceFactory::create('ilyang', true);
            
            // 테스트 데이터
            $testData = [
                'order_number' => 'CLI-TEST-' . date('YmdHis'),
                'departure_company_name' => 'CLI테스트발송회사',
                'departure_contact' => '010-1234-5678',
                'departure_address' => '서울시 강남구 테헤란로 123',
                'departure_manager' => '홍길동',
                'destination_company_name' => 'CLI테스트수취회사',
                'destination_contact' => '010-9876-5432',
                'destination_address' => '서울시 서초구 서초대로 456',
                'destination_manager' => '김철수',
                'item_type' => 'CLI테스트상품',
                'weight' => '1',
                'quantity' => '1',
                'payment_type' => 'prepaid',
                'delivery_instructions' => 'CLI 테스트용 주문',
                'delivery_notes' => 'CLI테스트'
            ];
            
            CLI::write('테스트 데이터 전송 중...', 'yellow');
            
            $result = $apiService->createDelivery($testData);
            
            if ($result['success']) {
                CLI::write('✅ 일양 API 테스트 성공!', 'green');
                CLI::write('응답 데이터:', 'blue');
                CLI::write(json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                CLI::write('❌ 일양 API 테스트 실패!', 'red');
                CLI::write('에러: ' . json_encode($result), 'red');
            }
            
        } catch (\Exception $e) {
            CLI::write('❌ 테스트 실행 중 오류 발생: ' . $e->getMessage(), 'red');
        }
    }
}
