<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InternationalProductDetailModel;

class Service extends BaseController
{
    public function __construct()
    {
        helper('form');
    }
    /**
     * 메일룸 서비스
     */
    public function mailroom()
    {
        return $this->showServicePage('mailroom', '메일룸서비스');
    }
    
    /**
     * 퀵서비스 - 오토바이
     */
    public function quickMotorcycle()
    {
        return $this->showServicePage('quick-motorcycle', '오토바이(소화물)');
    }
    
    /**
     * 퀵서비스 - 차량
     */
    public function quickVehicle()
    {
        return $this->showServicePage('quick-vehicle', '차량(화물)');
    }
    
    /**
     * 퀵서비스 - 플렉스
     */
    public function quickFlex()
    {
        return $this->showServicePage('quick-flex', '플렉스(소화물)');
    }
    
    /**
     * 퀵서비스 - 이사짐
     */
    public function quickMoving()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $data = [
            'title' => "STN Network - 이사짐화물(소형)",
            'content_header' => [
                'title' => '이사짐화물(소형)',
                'description' => "주문을 접수해주세요"
            ],
            'service_type' => 'quick-moving',
            'service_name' => '이사짐화물(소형)',
            'user' => [
                'username' => session()->get('username'),
                'company_name' => session()->get('company_name')
            ],
            'truck_capacities' => \App\Config\TruckOptions::getCapacities(),
            'truck_body_types' => \App\Config\TruckOptions::getBodyTypes()
        ];
        
        return view("service/quick-moving", $data);
    }
    
    /**
     * 해외특송서비스
     */
    public function international()
    {
        return $this->showServicePage('international', '해외특송서비스');
    }
    
    /**
     * 연계배송서비스 - 고속버스
     */
    public function linkedBus()
    {
        return $this->showServicePage('linked-bus', '고속버스(제로데이)');
    }
    
    /**
     * 연계배송서비스 - KTX
     */
    public function linkedKtx()
    {
        return $this->showServicePage('linked-ktx', 'KTX');
    }
    
    /**
     * 연계배송서비스 - 공항
     */
    public function linkedAirport()
    {
        return $this->showServicePage('linked-airport', '공항');
    }
    
    /**
     * 연계배송서비스 - 해운
     */
    public function linkedShipping()
    {
        return $this->showServicePage('linked-shipping', '해운');
    }
    
    /**
     * 택배서비스 - 방문택배
     */
    public function parcelVisit()
    {
        return $this->showServicePage('parcel-visit', '방문택배');
    }
    
    /**
     * 택배서비스 - 당일택배
     */
    public function parcelSameDay()
    {
        return $this->showServicePage('parcel-same-day', '당일택배');
    }
    
    /**
     * 택배서비스 - 편의점택배
     */
    public function parcelConvenience()
    {
        return $this->showServicePage('parcel-convenience', '편의점택배');
    }
    
    /**
     * 택배서비스 - 행낭
     */
    public function parcelBag()
    {
        return $this->showServicePage('parcel-bag', '행낭');
    }
    
    /**
     * 우편서비스
     */
    public function postal()
    {
        return $this->showServicePage('postal', '우편서비스');
    }
    
    /**
     * 일반서비스 - 사내문서
     */
    public function generalDocument()
    {
        return $this->showServicePage('general-document', '사내문서');
    }
    
    /**
     * 일반서비스 - 개인심부름
     */
    public function generalErrand()
    {
        return $this->showServicePage('general-errand', '개인심부름');
    }
    
    /**
     * 일반서비스 - 세무컨설팅
     */
    public function generalTax()
    {
        return $this->showServicePage('general-tax', '세무컨설팅');
    }
    
    /**
     * 생활서비스 - 사다주기
     */
    public function lifeBuy()
    {
        return $this->showServicePage('life-buy', '사다주기');
    }
    
    /**
     * 생활서비스 - 택시
     */
    public function lifeTaxi()
    {
        return $this->showServicePage('life-taxi', '택시');
    }
    
    /**
     * 생활서비스 - 대리운전
     */
    public function lifeDriver()
    {
        return $this->showServicePage('life-driver', '대리운전');
    }
    
    /**
     * 생활서비스 - 화환
     */
    public function lifeWreath()
    {
        return $this->showServicePage('life-wreath', '화환');
    }
    
    /**
     * 생활서비스 - 숙박
     */
    public function lifeAccommodation()
    {
        return $this->showServicePage('life-accommodation', '숙박');
    }
    
    /**
     * 생활서비스 - 문구
     */
    public function lifeStationery()
    {
        return $this->showServicePage('life-stationery', '문구');
    }
    
    /**
     * 공통 서비스 페이지 표시
     */
    private function showServicePage($serviceType, $serviceName)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $data = [
            'title' => "STN Network - {$serviceName}",
            'content_header' => [
                'title' => $serviceName,
                'description' => "주문을 접수해주세요"
            ],
            'service_type' => $serviceType,
            'service_name' => $serviceName,
            'user' => [
                'username' => session()->get('username'),
                'company_name' => session()->get('company_name')
            ]
        ];
        
        return view("service/{$serviceType}", $data);
    }
    
    /**
     * 서비스별 주문 접수 처리
     */
    public function submitServiceOrder()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $serviceType = $this->request->getPost('service_type');
        $serviceName = $this->request->getPost('service_name');
        
        // 디버깅 로그 추가
        log_message('debug', 'Service submitServiceOrder: service_type=' . $serviceType . ', service_name=' . $serviceName);
        
        // 폼 데이터 검증 (서비스 타입에 따라 다르게)
        $validation = \Config\Services::validation();
        
        // 기본 검증 규칙
        $validationRules = [
            'company_name' => 'required',
            'contact' => 'required',
            'departure_company_name' => 'required',
            'departure_contact' => 'required',
            'departure_address' => 'required',
            'destination_company_name' => 'required',
            'destination_contact' => 'required',
            'destination_address' => 'required',
            'payment_type' => 'required'
        ];
        
        // 퀵 서비스들만 item_type 필수
        if (in_array($serviceType, ['quick-motorcycle', 'quick-vehicle', 'quick-flex', 'quick-moving'])) {
            $validationRules['item_type'] = 'required';
        }
        
        $validation->setRules($validationRules);
        
        if (!$validation->withRequest($this->request)->run()) {
            log_message('error', 'Service validation failed: ' . json_encode($validation->getErrors()));
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        try {
            // DB 연결 재시도 로직 (매크로와의 경합 고려)
            $maxRetries = 5;
            $db = null;
            
            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    $db = \Config\Database::connect();
                    
                    // 연결 테스트 (매크로 실행 중인지 확인)
                    $db->query('SELECT 1');
                    break; // 연결 성공시 루프 종료
                } catch (\Exception $e) {
                    if ($i === $maxRetries - 1) {
                        throw $e; // 마지막 시도에서도 실패시 예외 발생
                    }
                    
                    // 매크로 실행 간격(10초)을 고려한 대기
                    $waitTime = ($i + 1) * 2; // 2초, 4초, 6초, 8초 대기
                    sleep($waitTime);
                }
            }
            
            // 1. 서비스 타입 ID 조회
            $serviceTypeId = $this->getServiceTypeId($serviceType);
            log_message('debug', 'Service type ID: ' . $serviceTypeId . ' for service: ' . $serviceType);
            
            // 2. 주문번호 생성 (간단한 방식)
            $today = date('Ymd');
            $timestamp = time(); // 타임스탬프 사용으로 중복 방지
            $orderNumber = sprintf('ORD-%s-%s', $today, substr($timestamp, -4));
            
            // 3. 주문 기본 정보 저장 (tbl_orders)
            $orderData = [
                'user_id' => session()->get('user_id') ?? 1, // 임시로 1 사용
                'customer_id' => session()->get('customer_id') ?? 1, // 임시로 1 사용
                'service_type_id' => $serviceTypeId,
                'order_number' => $orderNumber,
                'company_name' => $this->request->getPost('company_name'),
                'contact' => $this->request->getPost('contact'),
                'address' => $this->request->getPost('address'),
                'departure_company_name' => $this->request->getPost('departure_company_name'),
                'departure_contact' => $this->request->getPost('departure_contact'),
                'departure_department' => $this->request->getPost('departure_department'),
                'departure_manager' => $this->request->getPost('departure_manager'),
                'departure_dong' => $this->request->getPost('departure_dong'),
                'departure_address' => $this->request->getPost('departure_address'),
                'departure_detail' => $this->request->getPost('departure_detail'),
                'waypoint_address' => $this->request->getPost('waypoint_address'),
                'waypoint_detail' => $this->request->getPost('waypoint_detail'),
                'waypoint_contact' => $this->request->getPost('waypoint_contact'),
                'waypoint_notes' => $this->request->getPost('waypoint_notes'),
                'destination_type' => $this->request->getPost('destination_type'),
                'mailroom' => $this->request->getPost('mailroom'),
                'destination_company_name' => $this->request->getPost('destination_company_name'),
                'destination_contact' => $this->request->getPost('destination_contact'),
                'destination_department' => $this->request->getPost('destination_department'),
                'destination_manager' => $this->request->getPost('destination_manager'),
                'destination_dong' => $this->request->getPost('destination_dong'),
                'destination_address' => $this->request->getPost('destination_address'),
                'detail_address' => $this->request->getPost('detail_address'),
                'item_type' => $this->request->getPost('item_type') ?? $this->getDefaultItemType($serviceType),
                'quantity' => $this->request->getPost('quantity') ?? 1,
                'unit' => $this->request->getPost('unit') ?? '개',
                'delivery_content' => $this->request->getPost('delivery_content'),
                'status' => 'pending',
                'total_amount' => 0, // 기본값
                'payment_type' => $this->request->getPost('payment_type'),
                'notes' => $this->request->getPost('notes'),
                'order_date' => $this->request->getPost('order_date') ?: date('Y-m-d'),
                'order_time' => $this->request->getPost('order_time') ?: date('H:i:s'),
                'notification_service' => $this->request->getPost('notification_service') ? 1 : 0
            ];
            
            $db->table('tbl_orders')->insert($orderData);
            $orderId = $db->insertID();
            log_message('debug', 'Order inserted with ID: ' . $orderId);
            
            // 4. 퀵 서비스 전용 데이터 저장 (tbl_orders_quick)
            if (in_array($serviceType, ['quick-motorcycle', 'quick-vehicle', 'quick-flex', 'quick-moving'])) {
                // 박스/행낭/쇼핑백 정보를 JSON으로 저장
                $packageInfo = [
                    'box_selection' => $this->request->getPost('box_selection'),
                    'box_quantity' => $this->request->getPost('box_quantity'),
                    'pouch_selection' => $this->request->getPost('pouch_selection'),
                    'pouch_quantity' => $this->request->getPost('pouch_quantity'),
                    'shopping_bag_selection' => $this->request->getPost('shopping_bag_selection')
                ];
                
                // delivery_method 값을 DB ENUM에 맞게 변환
                $deliveryMethod = $this->request->getPost('delivery_method') ?? 'motorcycle';
                if (in_array($deliveryMethod, ['truck', 'van', 'cargo'])) {
                    $deliveryMethod = 'vehicle'; // 차량 관련 옵션들을 모두 'vehicle'로 변환
                }
                
                $quickData = [
                    'order_id' => $orderId,
                    'delivery_method' => $deliveryMethod,
                    'urgency_level' => $this->request->getPost('urgency_level') ?? 'normal',
                    'estimated_time' => null, // 추후 계산 로직 추가
                    'pickup_time' => null, // 추후 설정
                    'delivery_time' => null, // 추후 계산
                    'driver_contact' => null, // 추후 배정
                    'vehicle_info' => null, // 추후 배정
                    'departure_address' => $this->request->getPost('departure_address') ?? '',
                    'destination_address' => $this->request->getPost('destination_address') ?? '',
                    'delivery_instructions' => $this->request->getPost('special_instructions') ?? '',
                    'delivery_route' => $this->request->getPost('delivery_route') ?? '',
                    'box_selection' => $this->request->getPost('box_selection') ?? '',
                    'box_quantity' => (int)($this->request->getPost('box_quantity') ?? 0),
                    'pouch_selection' => $this->request->getPost('pouch_selection') ?? '',
                    'pouch_quantity' => (int)($this->request->getPost('pouch_quantity') ?? 0),
                    'shopping_bag_selection' => $this->request->getPost('shopping_bag_selection') ?? '',
                    'special_instructions' => json_encode([
                        'delivery_instructions' => $this->request->getPost('special_instructions'),
                        'package_info' => $packageInfo,
                        'delivery_route' => $this->request->getPost('delivery_route')
                    ]), // 기존 호환성을 위해 유지
                    'additional_fee' => 0
                ];
                
                $db->table('tbl_orders_quick')->insert($quickData);
                log_message('debug', 'Quick service data inserted for order ID: ' . $orderId);
            }
            
            // 5. 일반 서비스 전용 데이터 저장 (tbl_orders_general)
            if (in_array($serviceType, ['general-document', 'general-errand', 'general-tax'])) {
                $serviceSubtype = '';
                $documentType = null;
                $errandType = null;
                $taxCategory = null;
                
                switch ($serviceType) {
                    case 'general-document':
                        $serviceSubtype = 'document';
                        $documentType = $this->request->getPost('document_type') ?? '일반문서';
                        break;
                    case 'general-errand':
                        $serviceSubtype = 'errand';
                        $errandType = $this->request->getPost('errand_type') ?? '일반심부름';
                        break;
                    case 'general-tax':
                        $serviceSubtype = 'tax';
                        $taxCategory = $this->request->getPost('tax_category') ?? '일반세무';
                        break;
                }
                
                $generalData = [
                    'order_id' => $orderId,
                    'service_subtype' => $serviceSubtype,
                    'document_type' => $documentType,
                    'errand_type' => $errandType,
                    'tax_category' => $taxCategory,
                    'deadline' => $this->request->getPost('deadline') ?? null,
                    'priority' => $this->request->getPost('priority') ?? 'normal',
                    'assigned_to' => null, // 추후 배정
                    'completion_notes' => null,
                    'attachments' => null
                ];
                
                $db->table('tbl_orders_general')->insert($generalData);
                log_message('debug', 'General service data inserted for order ID: ' . $orderId);
            }
            
            // 6. 택배 서비스 전용 데이터 저장 (tbl_orders_parcel)
            if (in_array($serviceType, ['parcel-visit', 'parcel-same-day', 'parcel-convenience', 'parcel-bag'])) {
                $parcelType = '';
                switch ($serviceType) {
                    case 'parcel-visit':
                        $parcelType = 'visit';
                        break;
                    case 'parcel-same-day':
                        $parcelType = 'same_day';
                        break;
                    case 'parcel-convenience':
                        $parcelType = 'convenience';
                        break;
                    case 'parcel-bag':
                        $parcelType = 'bag';
                        break;
                }
                
                $parcelData = [
                    'order_id' => $orderId,
                    'parcel_type' => $parcelType,
                    'weight' => $this->request->getPost('weight') ?? null,
                    'dimensions' => $this->request->getPost('dimensions') ?? null,
                    'insurance_amount' => $this->request->getPost('insurance_amount') ?? 0,
                    'delivery_option' => $this->request->getPost('delivery_option') ?? 'standard',
                    'pickup_time' => $this->request->getPost('pickup_time') ?? null,
                    'tracking_number' => null, // 추후 생성
                    'carrier' => null // 추후 배정
                ];
                
                $db->table('tbl_orders_parcel')->insert($parcelData);
                log_message('debug', 'Parcel service data inserted for order ID: ' . $orderId);
            }
            
            // 7. 생활 서비스 전용 데이터 저장 (tbl_orders_life)
            if (in_array($serviceType, ['life-buy', 'life-taxi', 'life-driver', 'life-wreath', 'life-accommodation', 'life-stationery'])) {
                $lifeType = '';
                switch ($serviceType) {
                    case 'life-buy':
                        $lifeType = 'buy';
                        break;
                    case 'life-taxi':
                        $lifeType = 'taxi';
                        break;
                    case 'life-driver':
                        $lifeType = 'driver';
                        break;
                    case 'life-wreath':
                        $lifeType = 'wreath';
                        break;
                    case 'life-accommodation':
                        $lifeType = 'accommodation';
                        break;
                    case 'life-stationery':
                        $lifeType = 'stationery';
                        break;
                }
                
                $lifeData = [
                    'order_id' => $orderId,
                    'life_type' => $lifeType,
                    'service_details' => $this->request->getPost('service_details') ?? null,
                    'preferred_time' => $this->request->getPost('preferred_time') ?? null,
                    'budget' => $this->request->getPost('budget') ?? null,
                    'special_requirements' => $this->request->getPost('special_requirements') ?? null,
                    'contact_person' => $this->request->getPost('contact_person') ?? null,
                    'status' => 'pending'
                ];
                
                $db->table('tbl_orders_life')->insert($lifeData);
                log_message('debug', 'Life service data inserted for order ID: ' . $orderId);
            }
            
            // 8. 특수 서비스 전용 데이터 저장 (tbl_orders_special)
            if (in_array($serviceType, ['international', 'linked-bus', 'linked-ktx', 'linked-airport', 'linked-shipping', 'postal', 'mailroom'])) {
                $specialType = '';
                switch ($serviceType) {
                    case 'international':
                        $specialType = 'international';
                        break;
                    case 'linked-bus':
                        $specialType = 'linked_bus';
                        break;
                    case 'linked-ktx':
                        $specialType = 'linked_ktx';
                        break;
                    case 'linked-airport':
                        $specialType = 'linked_airport';
                        break;
                    case 'linked-shipping':
                        $specialType = 'linked_shipping';
                        break;
                    case 'postal':
                        $specialType = 'postal';
                        break;
                    case 'mailroom':
                        $specialType = 'mailroom';
                        break;
                }
                
                $specialData = [
                    'order_id' => $orderId,
                    'special_type' => $specialType,
                    'service_details' => $this->request->getPost('service_details') ?? null,
                    'delivery_method' => $this->request->getPost('delivery_method') ?? null,
                    'estimated_delivery' => $this->request->getPost('estimated_delivery') ?? null,
                    'tracking_info' => null, // 추후 생성
                    'special_instructions' => $this->request->getPost('special_instructions') ?? null
                ];
                
                $db->table('tbl_orders_special')->insert($specialData);
                log_message('debug', 'Special service data inserted for order ID: ' . $orderId);
            }
            
            // 9. 해외특송 물품 상세 정보 저장 (tbl_international_product_details)
            if ($serviceType === 'international') {
                $productDetails = [];
                $productNames = $this->request->getPost('product_name') ?? [];
                $productQuantities = $this->request->getPost('product_quantity') ?? [];
                $productWeights = $this->request->getPost('product_weight') ?? [];
                $productWidths = $this->request->getPost('product_width') ?? [];
                $productLengths = $this->request->getPost('product_length') ?? [];
                $productHeights = $this->request->getPost('product_height') ?? [];
                $productHsCodes = $this->request->getPost('product_hs_code') ?? [];
                
                // 배열 데이터를 처리하여 물품 상세 정보 구성
                for ($i = 0; $i < count($productNames); $i++) {
                    if (!empty($productNames[$i])) { // 빈 값이 아닌 경우만 저장
                        $productDetails[] = [
                            'product_name' => $productNames[$i],
                            'product_quantity' => (int)($productQuantities[$i] ?? 1),
                            'product_weight' => !empty($productWeights[$i]) ? (float)$productWeights[$i] : null,
                            'product_width' => !empty($productWidths[$i]) ? (float)$productWidths[$i] : null,
                            'product_length' => !empty($productLengths[$i]) ? (float)$productLengths[$i] : null,
                            'product_height' => !empty($productHeights[$i]) ? (float)$productHeights[$i] : null,
                            'product_hs_code' => !empty($productHsCodes[$i]) ? $productHsCodes[$i] : null
                        ];
                    }
                }
                
                // 물품 상세 정보가 있는 경우에만 저장
                if (!empty($productDetails)) {
                    $productDetailModel = new InternationalProductDetailModel();
                    $productDetailModel->saveProductDetails($orderId, $productDetails);
                    log_message('debug', 'International product details saved for order ID: ' . $orderId . ', count: ' . count($productDetails));
                }
            }
            
            // 연결 정리 (매크로와의 경합 방지)
            $db->close();
            
            // 트랜잭션 제거 (성능 향상)
            // $db->transComplete();
            // if ($db->transStatus() === false) {
            //     throw new \Exception('주문 저장 중 오류가 발생했습니다.');
            // }
            
            return redirect()->to('/delivery/list')
                ->with('success', "{$serviceName} 주문이 성공적으로 접수되었습니다. 주문번호: {$orderNumber}");
            
        } catch (\Exception $e) {
            log_message('error', 'Service order submission failed: ' . $e->getMessage());
            log_message('error', 'Exception trace: ' . $e->getTraceAsString());
            
            // DB 연결 실패 시 임시로 세션에 저장
            $tempOrderData = [
                'id' => time(), // 임시 ID
                'order_number' => 'TEMP-' . date('YmdHis'),
                'service_type' => $serviceType,
                'service_name' => $serviceName,
                'company_name' => $this->request->getPost('company_name'),
                'contact' => $this->request->getPost('contact'),
                'departure_address' => $this->request->getPost('departure_address'),
                'destination_address' => $this->request->getPost('destination_address'),
                'item_type' => $this->request->getPost('item_type'),
                'payment_type' => $this->request->getPost('payment_type'),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // 세션에 임시 저장
            $tempOrders = session()->get('temp_orders') ?? [];
            $tempOrders[] = $tempOrderData;
            session()->set('temp_orders', $tempOrders);
            
            return redirect()->to('/delivery/list')
                ->with('success', "{$serviceName} 주문이 임시로 저장되었습니다. (DB 연결 실패)")
                ->with('warning', '데이터베이스 연결에 실패했습니다. 임시 저장된 데이터입니다.');
        }
    }
    
    /**
     * 서비스 타입 코드로 서비스 타입 ID 조회
     */
    private function getServiceTypeId($serviceType)
    {
        $db = \Config\Database::connect();
        
        $serviceTypeMap = [
            'quick-motorcycle' => 1,
            'quick-vehicle' => 2,
            'quick-flex' => 3,
            'quick-moving' => 4,
            'parcel-visit' => 5,
            'parcel-same-day' => 6,
            'parcel-convenience' => 7,
            'parcel-bag' => 8,
            'life-buy' => 9,
            'life-taxi' => 10,
            'life-driver' => 11,
            'life-wreath' => 12,
            'life-accommodation' => 13,
            'life-stationery' => 14,
            'general-document' => 15,
            'general-errand' => 16,
            'general-tax' => 17,
            'international' => 18,
            'linked-bus' => 19,
            'linked-ktx' => 20,
            'linked-airport' => 21,
            'linked-shipping' => 22,
            'postal' => 23,
            'mailroom' => 24
        ];
        
        // 매핑에서 찾기
        if (isset($serviceTypeMap[$serviceType])) {
            return $serviceTypeMap[$serviceType];
        }
        
        // DB에서 조회 (매핑에 없는 경우)
        $result = $db->table('tbl_service_types')
                    ->where('service_code', $serviceType)
                    ->get()
                    ->getRowArray();
        
        if ($result) {
            return $result['id'];
        }
        
        // 기본값 (quick-motorcycle)
        return 1;
    }
    
    /**
     * 서비스 타입에 따른 기본 item_type 반환
     */
    private function getDefaultItemType($serviceType)
    {
        $defaultItemTypes = [
            'mailroom' => '우편물',
            'international' => '국제특송',
            'parcel-visit' => '택배',
            'parcel-same-day' => '당일택배',
            'parcel-convenience' => '편의점택배',
            'parcel-bag' => '행낭',
            'linked-bus' => '고속버스',
            'linked-ktx' => 'KTX',
            'linked-airport' => '공항',
            'linked-shipping' => '해운',
            'life-buy' => '구매대행',
            'life-taxi' => '택시',
            'life-driver' => '대리운전',
            'life-wreath' => '화환',
            'life-accommodation' => '숙박',
            'life-stationery' => '문구',
            'general-document' => '사내문서',
            'general-errand' => '개인심부름',
            'general-tax' => '세무컨설팅',
            'postal' => '우편서비스'
        ];
        
        return $defaultItemTypes[$serviceType] ?? '일반';
    }
}
