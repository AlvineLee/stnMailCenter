<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InternationalProductModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Libraries\ApiServiceFactory;

class Service extends BaseController
{
    public function __construct()
    {
        helper('form');
    }
    /**
     * 멀티오더 엑셀 파일 파싱
     */
    public function parseMultiOrderExcel()
    {
        $file = $this->request->getFile('excel_file');
        
        if (!$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '파일 업로드에 실패했습니다.'
            ]);
        }
        
        try {
            // 임시 파일로 저장
            $tempPath = $file->store('temp');
            $fullPath = WRITEPATH . 'uploads/' . $tempPath;
            
            // PhpSpreadsheet로 엑셀 파일 읽기
            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // 헤더 제거 (첫 번째 행)
            array_shift($rows);
            
            $parsedData = [];
            foreach ($rows as $index => $row) {
                // 빈 행 건너뛰기
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $parsedData[] = [
                    'id' => $index + 1,
                    'use' => true,
                    'departure' => [
                        'name' => $row[0] ?? '',
                        'phone' => $row[1] ?? '',
                        'address' => $row[2] ?? ''
                    ],
                    'destination' => [
                        'name' => $row[3] ?? '',
                        'phone' => $row[4] ?? '',
                        'address' => $row[5] ?? ''
                    ],
                    'departureCoords' => '-',
                    'destinationCoords' => '-'
                ];
            }
            
            // 임시 파일 삭제
            unlink($fullPath);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $parsedData
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Excel parsing failed: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => '엑셀 파일 파싱에 실패했습니다: ' . $e->getMessage()
            ]);
        }
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
        
        // 슈퍼 관리자는 모든 서비스 접근 가능
        // 일반 사용자는 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            helper('menu');
            if (!hasServicePermission('quick-moving')) {
                // 권한이 없으면 접근 거부
                return redirect()->to('/dashboard')->with('error', '해당 서비스에 대한 접근 권한이 없습니다.');
            }
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
    
    public function parcelNight()
    {
        return $this->showServicePage('parcel-night', '야간배송');
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
        
        // 슈퍼 관리자는 모든 서비스 접근 가능
        // 일반 사용자는 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            helper('menu');
            if (!hasServicePermission($serviceType)) {
                // 권한이 없으면 접근 거부
                return redirect()->to('/dashboard')->with('error', '해당 서비스에 대한 접근 권한이 없습니다.');
            }
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
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $serviceType = $this->request->getPost('service_type');
        $serviceName = $this->request->getPost('service_name');
        
        // 슈퍼 관리자는 모든 서비스 접근 가능
        // 일반 사용자는 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            helper('menu');
            if (!hasServicePermission($serviceType)) {
                // 권한이 없으면 접근 거부
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '해당 서비스에 대한 접근 권한이 없습니다.'
                ])->setStatusCode(403);
            }
        }
        
        // 디버깅 로그 추가
        log_message('debug', 'Service submitServiceOrder: service_type=' . $serviceType . ', service_name=' . $serviceName);
        
        // 폼 데이터 검증 (서비스 타입에 따라 다르게)
        $validation = \Config\Services::validation();
        
        // 기본 검증 규칙
        $validationRules = [
            'company_name' => 'required',
            'contact' => 'required',
            'departure_company_name' => 'permit_empty',
            'departure_contact' => 'required', // 출발지 연락처 필수
            'departure_manager' => 'required', // 출발지 담당 필수
            'departure_address' => 'required', // 출발지 주소 필수
            'departure_detail' => 'required', // 출발지 상세주소 필수
            'destination_company_name' => 'permit_empty',
            'destination_contact' => 'required', // 도착지 연락처 필수
            'destination_manager' => 'required', // 도착지 담당 필수
            'destination_address' => 'required', // 도착지 주소 필수
            'destination_detail' => 'required', // 도착지 상세주소 필수
            'payment_type' => 'permit_empty'
        ];
        
        // 퀵 서비스들만 item_type 필수
        if (in_array($serviceType, ['quick-motorcycle', 'quick-vehicle', 'quick-flex', 'quick-moving'])) {
            $validationRules['item_type'] = 'required';
        }
        
        $validation->setRules($validationRules);
        
        if (!$validation->withRequest($this->request)->run()) {
            log_message('error', 'Service validation failed: ' . json_encode($validation->getErrors()));
            
            // 서비스 타입에 따라 해당 서비스 페이지로 리다이렉트
            $redirectUrl = '/service/' . $serviceType;
            if (empty($serviceType)) {
                // service_type이 없으면 메인으로
                $redirectUrl = '/';
            }
            
            return redirect()->to($redirectUrl)
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
            
            // 3. 주문 기본 정보 저장 (tbl_orders) - Model 사용
            $orderModel = new \App\Models\OrderModel();
            
            // 로그인 타입에 따라 user_id와 customer_id 처리
            $loginType = session()->get('login_type');
            $userId = null;
            $customerId = null;
            
            if ($loginType === 'daumdata') {
                // daumdata 로그인: tbl_users_list의 idx를 user_id로 사용
                $userIdx = session()->get('user_idx');
                if ($userIdx) {
                    $userId = (int)$userIdx; // tbl_users_list의 idx를 사용
                }
                // daumdata 로그인 시 customer_id는 comp_code를 사용
                $compCode = session()->get('comp_code');
                if ($compCode) {
                    $customerId = (int)$compCode; // comp_code를 customer_id로 사용
                }
            } else {
                // STN 로그인
                $userId = (int)(session()->get('user_id') ?? 1);
                $customerId = (int)(session()->get('customer_id') ?? 1);
            }
            
            $orderData = [
                'user_id' => $userId ?? 1,
                'customer_id' => $customerId ?? 1,
                'department_id' => 1, // 기본값
                'service_type_id' => $serviceTypeId,
                'order_number' => $orderNumber,
                'order_system' => ($loginType === 'daumdata') ? 'insung' : 'stn',
                'company_name' => $this->request->getPost('company_name'),
                'contact' => $this->request->getPost('contact'),
                'address' => $this->request->getPost('address'),
                'departure_company_name' => $this->request->getPost('departure_company_name'),
                'departure_contact' => $this->request->getPost('departure_contact'),
                'departure_address' => $this->request->getPost('departure_address'),
                'departure_detail' => $this->request->getPost('departure_detail'),
                'departure_manager' => $this->request->getPost('departure_manager'),
                'departure_department' => $this->request->getPost('departure_department'),
                'departure_dong' => $this->request->getPost('departure_dong'),
                'waypoint_address' => $this->request->getPost('waypoint_address'),
                'waypoint_detail' => $this->request->getPost('waypoint_detail'),
                'waypoint_contact' => $this->request->getPost('waypoint_contact'),
                'waypoint_notes' => $this->request->getPost('waypoint_notes'),
                'destination_type' => $this->request->getPost('destination_type') ?? 'direct',
                'mailroom' => $this->request->getPost('mailroom'),
                'destination_company_name' => $this->request->getPost('destination_company_name'),
                'destination_contact' => $this->request->getPost('destination_contact'),
                'destination_address' => $this->request->getPost('destination_address'),
                'detail_address' => $this->request->getPost('detail_address'),
                'destination_manager' => $this->request->getPost('destination_manager'),
                'destination_department' => $this->request->getPost('destination_department'),
                'destination_dong' => $this->request->getPost('destination_dong'),
                'item_type' => $this->request->getPost('item_type') ?? $this->getDefaultItemType($serviceType),
                'quantity' => $this->request->getPost('quantity') ?? 1,
                'unit' => $this->request->getPost('unit') ?? '개',
                'delivery_content' => $this->request->getPost('delivery_content'),
                'box_medium_overload' => $this->request->getPost('box_medium_overload') ? 1 : 0,
                'pouch_medium_overload' => $this->request->getPost('pouch_medium_overload') ? 1 : 0,
                'bag_medium_overload' => ($serviceType === 'parcel-bag' && $this->request->getPost('bag_medium_overload')) ? 1 : 0,
                'status' => 'pending'
            ];
            
            // life-driver 서비스의 경우 추가 필드를 tbl_orders에도 저장
            if ($serviceType === 'life-driver') {
                $orderData['call_type'] = $this->request->getPost('callType') ?? null;
                $orderData['total_fare'] = $this->request->getPost('total_fare') ? (float)$this->request->getPost('total_fare') : 0.00;
                $orderData['postpaid_fare'] = $this->request->getPost('postpaid_fare') ? (float)$this->request->getPost('postpaid_fare') : 0.00;
                $orderData['distance'] = $this->request->getPost('distance') ? (float)$this->request->getPost('distance') : 0.00;
                $orderData['cash_fare'] = $this->request->getPost('cash_fare') ? (float)$this->request->getPost('cash_fare') : 0.00;
            }
            
            $orderId = $orderModel->createOrder($orderData);
            
            if (!$orderId) {
                log_message('error', 'Order insert failed');
                throw new \Exception('주문 저장에 실패했습니다.');
            }
            
            log_message('debug', 'Order inserted with ID: ' . $orderId);
            
            // 3-1. daumdata 로그인 + 퀵 서비스인 경우 인성 API 주문 접수
            if ($loginType === 'daumdata' && in_array($serviceType, ['quick-motorcycle', 'quick-vehicle', 'quick-flex', 'quick-moving'])) {
                try {
                    $insungApiService = new \App\Libraries\InsungApiService();
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    
                    // 세션에서 API 정보 가져오기
                    $mCode = session()->get('m_code');
                    $ccCode = session()->get('cc_code');
                    $token = session()->get('token');
                    $userId = session()->get('user_id'); // daumdata의 user_id
                    
                    // api_idx 조회 (cc_code로 조회)
                    $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                    $apiIdx = $apiInfo ? $apiInfo['idx'] : null;
                    
                    if ($mCode && $ccCode && $token && $userId && $apiIdx) {
                        // 인성 API에 전달할 주문 데이터 구성
                        $insungOrderData = [
                            'service_type' => $serviceType,
                            'delivery_method' => $this->request->getPost('delivery_method') ?? 'motorcycle',
                            'company_name' => $this->request->getPost('company_name'),
                            'contact' => $this->request->getPost('contact'),
                            'departure_company_name' => $this->request->getPost('departure_company_name'),
                            'departure_contact' => $this->request->getPost('departure_contact'),
                            'departure_address' => $this->request->getPost('departure_address'),
                            'departure_detail' => $this->request->getPost('departure_detail'),
                            'departure_manager' => $this->request->getPost('departure_manager'),
                            'departure_department' => $this->request->getPost('departure_department'),
                            'departure_dong' => $this->request->getPost('departure_dong'),
                            'departure_lon' => $this->request->getPost('departure_lon') ?? '',
                            'departure_lat' => $this->request->getPost('departure_lat') ?? '',
                            'destination_company_name' => $this->request->getPost('destination_company_name'),
                            'destination_contact' => $this->request->getPost('destination_contact'),
                            'destination_address' => $this->request->getPost('destination_address'),
                            'detail_address' => $this->request->getPost('detail_address'),
                            'destination_manager' => $this->request->getPost('destination_manager'),
                            'destination_department' => $this->request->getPost('destination_department'),
                            'destination_dong' => $this->request->getPost('destination_dong'),
                            'destination_lon' => $this->request->getPost('destination_lon') ?? '',
                            'destination_lat' => $this->request->getPost('destination_lat') ?? '',
                            'item_type' => $this->request->getPost('item_type') ?? $this->getDefaultItemType($serviceType),
                            'delivery_content' => $this->request->getPost('delivery_content'),
                            'notes' => $this->request->getPost('notes'),
                            'payment_type' => $this->request->getPost('payment_type'),
                            'total_amount' => $this->request->getPost('total_amount') ?? '0',
                            'distance' => $this->request->getPost('distance') ?? ''
                        ];
                        
                        $insungResult = $insungApiService->registerOrder($mCode, $ccCode, $token, $userId, $insungOrderData, $apiIdx);
                        
                        if ($insungResult['success'] && !empty($insungResult['serial_number'])) {
                            // 인성 주문번호 저장
                            $orderModel->update($orderId, [
                                'insung_order_number' => $insungResult['serial_number']
                            ]);
                            
                            log_message('info', "Insung API order registered successfully. Order ID: {$orderId}, Serial Number: {$insungResult['serial_number']}");
                        } else {
                            log_message('warning', "Insung API order registration failed. Order ID: {$orderId}, Message: {$insungResult['message']}");
                            // 인성 API 실패해도 주문은 정상 처리됨
                        }
                    } else {
                        log_message('warning', "Insung API order registration skipped. Missing required session data. Order ID: {$orderId}");
                    }
                } catch (\Exception $e) {
                    log_message('error', "Insung API order registration exception: " . $e->getMessage());
                    // 인성 API 실패해도 주문은 정상 처리됨
                }
            }
            
            // 4. 해외특송 또는 택배서비스인 경우 송장번호 할당 및 플랫폼코드 저장
            if (in_array($serviceType, ['international', 'parcel-visit', 'parcel-same-day', 'parcel-convenience', 'parcel-night', 'parcel-bag'])) {
                try {
                    // 4-1. 활성화된 운송사 조회 (Factory Pattern 사용 - 동적 조회)
                    $activeShippingCompany = ApiServiceFactory::getActiveShippingCompany($serviceType);
                    
                    if ($activeShippingCompany) {
                        $platformCode = $activeShippingCompany['platform_code'];
                        
                        // 4-2. 미사용 송장번호 조회 (Model 사용)
                        // TODO: 향후 운송사별 송장번호 풀 테이블 분리 시 company_code로 필터링 필요
                        $awbPoolModel = new \App\Models\AwbPoolModel();
                        $awbPool = $awbPoolModel->getAvailableAwbNo();
                        
                        if ($awbPool && isset($awbPool['awb_no'])) {
                            $awbNo = $awbPool['awb_no'];
                            
                            // 4-3. tbl_orders에 플랫폼코드와 송장번호 업데이트 (Model 사용)
                            $orderModel->updateShippingInfo($orderId, $platformCode, $awbNo);
                            
                            // 4-4. tbl_ily_awb_pool 업데이트 (Model 사용)
                            $awbPoolModel->markAsUsed($awbNo, $orderNumber);
                            
                            log_message('info', "AWB No assigned: {$awbNo} (Platform: {$platformCode}) to order: {$orderNumber}");
                        } else {
                            log_message('warning', "No available AWB number in pool for order: {$orderNumber}");
                            // 송장번호가 없어도 플랫폼코드는 저장
                            $orderModel->updateShippingInfo($orderId, $platformCode);
                        }
                    } else {
                        log_message('warning', "No active shipping company found for service: {$serviceType}, order: {$orderNumber}");
                    }
                } catch (\Exception $e) {
                    log_message('error', "AWB assignment exception: " . $e->getMessage());
                    // 송장번호 할당 실패해도 주문은 정상 처리됨
                }
            }
            
            // 5. API 연동 (서비스별 자동 매핑)
            if (ApiServiceFactory::needsApiIntegration($serviceType)) {
                try {
                    // 할당된 송장번호와 플랫폼코드 조회 (Model 사용)
                    $orderInfo = $orderModel->getShippingInfo($orderId);
                    
                    // Factory Pattern을 통해 활성화된 운송사 설정으로 API 서비스 생성
                    $apiService = ApiServiceFactory::createForService($serviceType, true); // 테스트 모드
                    
                    if (!$apiService) {
                        log_message('warning', "Failed to create API service for service: {$serviceType}");
                        // API 서비스 생성 실패해도 주문은 정상 처리됨
                    } else {
                        $apiType = ApiServiceFactory::getApiTypeByService($serviceType);
                        
                        $deliveryData = [
                            'order_number' => $orderNumber,
                            'shipping_tracking_number' => $orderInfo ? ($orderInfo['shipping_tracking_number'] ?? '') : '',
                            'shipping_platform_code' => $orderInfo ? ($orderInfo['shipping_platform_code'] ?? '') : '',
                            'departure_company_name' => $this->request->getPost('departure_company_name'),
                            'departure_contact' => $this->request->getPost('departure_contact'),
                            'departure_address' => $this->request->getPost('departure_address'),
                            'departure_manager' => $this->request->getPost('departure_manager'),
                            'destination_company_name' => $this->request->getPost('destination_company_name'),
                            'destination_contact' => $this->request->getPost('destination_contact'),
                            'destination_address' => $this->request->getPost('destination_address'),
                            'destination_manager' => $this->request->getPost('destination_manager'),
                            'item_type' => $this->request->getPost('item_type'),
                            'weight' => $this->request->getPost('weight') ?? '1',
                            'quantity' => $this->request->getPost('quantity') ?? '1',
                            'payment_type' => $this->request->getPost('payment_type'),
                            'delivery_instructions' => $this->request->getPost('delivery_instructions'),
                            'delivery_notes' => $this->request->getPost('notes')
                        ];
                        
                        $apiResult = $apiService->createDelivery($deliveryData);
                        
                        if ($apiResult['success']) {
                            log_message('info', "{$apiType} API success: " . json_encode($apiResult['data']));
                            
                            // API 응답 처리 (오류가 있는 경우 로그만 기록)
                            if (isset($apiResult['data']['body']['logisticsResultData'])) {
                                foreach ($apiResult['data']['body']['logisticsResultData'] as $result) {
                                    if (isset($result['ilyErrorType']) && $result['ilyErrorType'] !== 'E0') {
                                        // 오류가 있는 경우
                                        log_message('error', "{$apiType} API Error - Order: {$orderNumber}, Error: " . json_encode($result));
                                    }
                                }
                            }
                        } else {
                            log_message('error', "{$apiType} API failed: " . json_encode($apiResult));
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', "API exception ({$serviceType}): " . $e->getMessage());
                    // API 실패해도 주문은 정상 처리됨
                }
            }
            
            // 7. 퀵 서비스 전용 데이터 저장 (tbl_orders_quick)
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
            
            // 7. 일반 서비스 전용 데이터 저장 (tbl_orders_general)
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
            
            // 8. 택배 서비스 전용 데이터 저장 (tbl_orders_parcel)
            if (in_array($serviceType, ['parcel-visit', 'parcel-same-day', 'parcel-convenience', 'parcel-night', 'parcel-bag'])) {
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
                    case 'parcel-night':
                        $parcelType = 'night';
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
                
                // parcel-bag 서비스의 경우 bagType과 bagMaterial도 저장
                if ($serviceType === 'parcel-bag') {
                    $parcelData['bag_type'] = $this->request->getPost('bagType') ?? null;
                    $parcelData['bag_material'] = $this->request->getPost('bagMaterial') ?? null;
                }
                
                // parcel-visit 서비스의 경우 박스/행낭 정보 저장
                if ($serviceType === 'parcel-visit') {
                    $parcelData['box_selection'] = $this->request->getPost('box_selection') ?? null;
                    $parcelData['box_quantity'] = (int)($this->request->getPost('box_quantity') ?? 0);
                    $parcelData['pouch_selection'] = $this->request->getPost('pouch_selection') ?? null;
                    $parcelData['pouch_quantity'] = (int)($this->request->getPost('pouch_quantity') ?? 0);
                }
                
                $db->table('tbl_orders_parcel')->insert($parcelData);
                log_message('debug', 'Parcel service data inserted for order ID: ' . $orderId);
            }
            
            // 9. 생활 서비스 전용 데이터 저장 (tbl_orders_life)
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
                
                // life-driver 서비스의 경우 추가 필드 저장
                if ($serviceType === 'life-driver') {
                    $lifeData['call_type'] = $this->request->getPost('callType') ?? null;
                    $lifeData['total_fare'] = $this->request->getPost('total_fare') ? (float)$this->request->getPost('total_fare') : 0.00;
                    $lifeData['postpaid_fare'] = $this->request->getPost('postpaid_fare') ? (float)$this->request->getPost('postpaid_fare') : 0.00;
                    $lifeData['distance'] = $this->request->getPost('distance') ? (float)$this->request->getPost('distance') : 0.00;
                    $lifeData['cash_fare'] = $this->request->getPost('cash_fare') ? (float)$this->request->getPost('cash_fare') : 0.00;
                }
                
                $db->table('tbl_orders_life')->insert($lifeData);
                log_message('debug', 'Life service data inserted for order ID: ' . $orderId);
            }
            
            // 10. 특수 서비스 전용 데이터 저장 (tbl_orders_special)
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
                    'service_subtype' => $specialType,
                    'country_code' => $this->request->getPost('country_code') ?? null,
                    'customs_declaration' => $this->request->getPost('customs_declaration') ?? null,
                    'tracking_number' => null, // 추후 생성
                    'linked_service_info' => $this->request->getPost('linked_service_info') ?? null,
                    'departure_terminal' => $this->request->getPost('departure_terminal') ?? null,
                    'arrival_terminal' => $this->request->getPost('arrival_terminal') ?? null,
                    'departure_time' => $this->request->getPost('departure_time') ?? null,
                    'arrival_time' => $this->request->getPost('arrival_time') ?? null,
                    'seat_number' => $this->request->getPost('seat_number') ?? null,
                    'ticket_number' => $this->request->getPost('ticket_number') ?? null,
                    'special_handling' => $this->request->getPost('special_handling') ?? null
                ];
                
                $db->table('tbl_orders_special')->insert($specialData);
                log_message('debug', 'Special service data inserted for order ID: ' . $orderId);
            }
            
            // 11. 해외특송 물품 상세 정보 저장 (tbl_orders_international_products)
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
                    $productModel = new InternationalProductModel();
                    $productModel->saveProductDetails($orderId, $productDetails);
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
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
            
            log_message('error', 'Service order submission failed: ' . $errorMessage);
            log_message('error', 'Exception code: ' . $errorCode);
            log_message('error', 'Exception trace: ' . $e->getTraceAsString());
            
            // DB 연결 관련 에러인지 확인
            $isDbError = (
                strpos($errorMessage, 'Unable to connect') !== false ||
                strpos($errorMessage, 'Connection refused') !== false ||
                strpos($errorMessage, 'Access denied') !== false ||
                strpos($errorMessage, 'database') !== false ||
                strpos($errorMessage, 'SQLSTATE') !== false
            );
            
            // DB 연결 실패 시에만 임시로 세션에 저장
            if ($isDbError) {
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
            } else {
                // 다른 에러인 경우 - 서비스 타입에 따라 해당 서비스 페이지로 리다이렉트
                $redirectUrl = '/service/' . $serviceType;
                if (empty($serviceType)) {
                    // service_type이 없으면 메인으로
                    $redirectUrl = '/';
                }
                
                return redirect()->to($redirectUrl)
                    ->withInput()
                    ->with('error', "주문 접수 중 오류가 발생했습니다: " . $errorMessage)
                    ->with('warning', '잠시 후 다시 시도해주세요.');
            }
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
            'parcel-night' => '야간배송',
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
