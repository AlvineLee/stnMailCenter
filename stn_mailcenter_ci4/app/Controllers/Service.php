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
            'title' => "DaumData - 이사짐화물(소형)",
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

        // 인성 API로 지급구분(credit) 실시간 조회 후 세션에 저장
        // 세션에 저장하면 include 되는 하위 뷰(common-paytype 등)에서 별도 파라미터 전달 없이 접근 가능
        $credit = $this->fetchCreditFromApi();
        if ($credit !== null) {
            session()->set('credit', $credit);
        }

        // 배송사유 설정 조회 (거래처별)
        $useDeliveryReason = 'N';
        $deliveryReasons = [];
        $compCode = session()->get('comp_code');
        if (!empty($compCode)) {
            $db = \Config\Database::connect();
            $company = $db->table('tbl_company_list')
                ->where('comp_code', $compCode)
                ->get()
                ->getRowArray();
            if ($company && ($company['use_delivery_reason'] ?? 'N') === 'Y') {
                $useDeliveryReason = 'Y';
                // 배송사유 목록 조회
                $deliveryReasonModel = new \App\Models\DeliveryReasonModel();
                $deliveryReasons = $deliveryReasonModel->getActiveReasons();
            }
        }

        $data = [
            'title' => "DaumData - {$serviceName}",
            'content_header' => [
                'title' => $serviceName,
                'description' => "주문을 접수해주세요"
            ],
            'service_type' => $serviceType,
            'service_name' => $serviceName,
            'user' => [
                'username' => session()->get('username'),
                'company_name' => session()->get('company_name')
            ],
            // 배송사유 관련
            'useDeliveryReason' => $useDeliveryReason,
            'deliveryReasons' => $deliveryReasons
            // credit은 세션에서 조회하므로 별도 전달 불필요
        ];

        return view("service/{$serviceType}", $data);
    }

    /**
     * 인성 API로 지급구분(credit) 조회
     * 회원별 지급구분(credit_customer_code) 우선, 없으면 거래처 기본 지급구분(credit) 사용
     *
     * @return string|null credit 값 (1=선불, 2=착불, 3=신용, 4=송금, 5/6/7=카드)
     */
    private function fetchCreditFromApi()
    {
        $loginType = session()->get('login_type');

        // daumdata 로그인이 아니면 세션의 credit 값 사용 (기존 방식)
        if ($loginType !== 'daumdata') {
            return session()->get('credit');
        }

        // 세션에서 API 정보 가져오기
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        $userCcode = session()->get('user_ccode');

        // 필수 정보가 없으면 null 반환
        if (empty($mCode) || empty($ccCode) || empty($token) || empty($userCcode)) {
            // log_message('debug', "Service::fetchCreditFromApi - Missing required params: mCode={$mCode}, ccCode={$ccCode}, token=" . ($token ? 'exists' : 'empty') . ", userCcode={$userCcode}");
            return session()->get('credit'); // 폴백: 세션의 credit 사용
        }

        try {
            $insungApiService = new \App\Libraries\InsungApiService();
            // 회원 상세 조회 (c_code로 조회) - /api/member_detail/find/
            $memberDetailResult = $insungApiService->getMemberDetailByCode($mCode, $ccCode, $token, $userCcode, $apiIdx);

            // log_message('info', "Service::fetchCreditFromApi - API called with userCcode={$userCcode}");

            // API 응답 구조 파싱
            $memberInfo = null;
            $code = '';
            if ($memberDetailResult && (is_array($memberDetailResult) || is_object($memberDetailResult))) {
                if (is_array($memberDetailResult) && isset($memberDetailResult[0])) {
                    $code = $memberDetailResult[0]->code ?? $memberDetailResult[0]['code'] ?? '';
                    if ($code === '1000' && isset($memberDetailResult[1])) {
                        $memberInfo = is_object($memberDetailResult[1]) ? (array)$memberDetailResult[1] : $memberDetailResult[1];
                    }
                } elseif (is_object($memberDetailResult) && isset($memberDetailResult->Result)) {
                    $code = $memberDetailResult->Result[0]->result_info[0]->code ?? '';
                    if ($code === '1000' && isset($memberDetailResult->Result[1]->item[0])) {
                        $memberInfo = (array)$memberDetailResult->Result[1]->item[0];
                    }
                }
            }

            // log_message('info', "Service::fetchCreditFromApi - API response code={$code}, memberInfo=" . ($memberInfo ? 'exists' : 'null'));

            if ($memberInfo) {
                // credit 값 추출 - credit_customer_code 우선 사용 (회원별 지급구분)
                if (isset($memberInfo['credit_customer_code']) && $memberInfo['credit_customer_code'] !== '' && $memberInfo['credit_customer_code'] !== null) {
                    $credit = $memberInfo['credit_customer_code'];
                    // log_message('info', "Service::fetchCreditFromApi - Using credit_customer_code: {$credit}");
                    return $credit;
                } elseif (isset($memberInfo['credit']) && $memberInfo['credit'] !== '') {
                    $credit = $memberInfo['credit'];
                    // log_message('info', "Service::fetchCreditFromApi - Using credit (default): {$credit}");
                    return $credit;
                }
            }

            return session()->get('credit'); // 폴백: 세션의 credit 사용

        } catch (\Exception $e) {
            log_message('error', "Service::fetchCreditFromApi - API call failed: " . $e->getMessage());
            return session()->get('credit'); // 폴백: 세션의 credit 사용
        }
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

        log_message('debug', 'submitServiceOrder - service_type from POST: ' . var_export($serviceType, true));
        log_message('debug', 'submitServiceOrder - service_name from POST: ' . var_export($serviceName, true));

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
        
        // log_message('debug', 'Service submitServiceOrder: service_type=' . $serviceType . ', service_name=' . $serviceName);

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
            'destination_contact' => 'permit_empty', // 도착지 연락처 선택사항
            'destination_manager' => 'permit_empty', // 도착지 담당 선택사항
            'destination_address' => 'required', // 도착지 주소 필수
            'destination_detail' => 'required', // 도착지 상세주소 필수
            'payment_type' => 'permit_empty'
        ];

        // 배송사유 필수 검사 (거래처 설정에 따라)
        $compCode = session()->get('comp_code');
        if (!empty($compCode)) {
            $dbTemp = \Config\Database::connect();
            $company = $dbTemp->table('tbl_company_list')
                ->select('use_delivery_reason')
                ->where('comp_code', $compCode)
                ->get()
                ->getRowArray();
            if ($company && ($company['use_delivery_reason'] ?? 'N') === 'Y') {
                $validationRules['delivery_reason'] = 'required';
            }
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

            // 3. 주문 기본 정보 저장 (tbl_orders) - Model 사용
            $orderModel = new \App\Models\OrderModel();
            
            // 로그인 타입에 따라 user_id와 customer_id 처리
            $loginType = session()->get('login_type');
            $userId = null;
            $customerId = null;
            
            // 2. 주문번호 생성
            // daumdata 로그인: 인성 API 등록 후 받은 serial_number로 생성 (나중에 업데이트)
            // STN 로그인: ORD- 형식으로 생성
            $orderNumber = null;
            if ($loginType !== 'daumdata') {
                $today = date('Ymd');
                $timestamp = time(); // 타임스탬프 사용으로 중복 방지
                $orderNumber = sprintf('ORD-%s-%s', $today, substr($timestamp, -4));
            }
            // daumdata 로그인 시 orderNumber는 인성 API 등록 후 설정됨
            
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
            
            // insung_user_id 조회 (tbl_users_list에서 user_id 문자열 조회)
            $insungUserId = null;
            if ($userId) {
                $userListBuilder = $db->table('tbl_users_list');
                $userListBuilder->select('user_id');
                $userListBuilder->where('idx', $userId);
                $userListQuery = $userListBuilder->get();
                if ($userListQuery !== false) {
                    $userListResult = $userListQuery->getRowArray();
                    if ($userListResult && !empty($userListResult['user_id'])) {
                        $insungUserId = $userListResult['user_id'];
                    }
                }
            }
            
            // order_system 결정: 일양 택배 서비스인 경우 'ilyang', daumdata 로그인인 경우 'insung', 그 외 'stn'
            $apiType = \App\Libraries\ApiServiceFactory::getApiTypeByService($serviceType);
            $orderSystem = 'stn';
            if ($apiType === 'ilyang') {
                $orderSystem = 'ilyang';
            } elseif ($loginType === 'daumdata') {
                $orderSystem = 'insung';
            }
            
            $orderData = [
                'user_id' => $userId ?? 1,
                'insung_user_id' => $insungUserId,
                'customer_id' => $customerId ?? 1,
                'department_id' => 1, // 기본값
                'service_type_id' => $serviceTypeId,
                'order_number' => $orderNumber ?? 'TEMP-' . date('YmdHis') . '-' . substr(time(), -4), // daumdata는 임시 번호, 인성 API 등록 후 업데이트
                'order_system' => $orderSystem,
                'company_name' => $this->request->getPost('company_name'),
                'contact' => $this->request->getPost('contact'),
                'sms_telno' => $this->request->getPost('sms_telno') ?? $this->request->getPost('contact'),
                'o_c_code' => $this->request->getPost('o_c_code') ?? null,
                'address' => $this->request->getPost('address'),
                'departure_company_name' => $this->request->getPost('departure_company_name'),
                'departure_contact' => $this->request->getPost('departure_contact'),
                'departure_address' => $this->request->getPost('departure_address'),
                'departure_detail' => $this->request->getPost('departure_detail'),
                'departure_manager' => $this->request->getPost('departure_manager'),
                'departure_department' => $this->request->getPost('departure_department'),
                // 루비 버전 참조: departure_dong2가 있으면 우선 사용, 없으면 departure_dong 사용
                'departure_dong' => $this->request->getPost('departure_dong2') ?: $this->request->getPost('departure_dong'),
                'departure_lon' => $this->request->getPost('departure_lon') ?? null,
                'departure_lat' => $this->request->getPost('departure_lat') ?? null,
                's_c_code' => $this->request->getPost('s_c_code') ?? null,
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
                // 루비 버전 참조: destination_dong2가 있으면 우선 사용, 없으면 destination_dong 사용
                'destination_dong' => $this->request->getPost('destination_dong2') ?: $this->request->getPost('destination_dong'),
                'destination_lon' => $this->request->getPost('destination_lon') ?? null,
                'destination_lat' => $this->request->getPost('destination_lat') ?? null,
                'd_c_code' => $this->request->getPost('d_c_code') ?? null,
                'item_type' => $this->request->getPost('item_type') ?? $this->getDefaultItemType($serviceType),
                'quantity' => $this->request->getPost('quantity') ?? 1,
                'unit' => $this->request->getPost('unit') ?? '개',
                'delivery_content' => $this->buildDeliveryContent(),
                'box_medium_overload' => $this->request->getPost('box_medium_overload') ? 1 : 0,
                'pouch_medium_overload' => $this->request->getPost('pouch_medium_overload') ? 1 : 0,
                'bag_medium_overload' => ($serviceType === 'parcel-bag' && $this->request->getPost('bag_medium_overload')) ? 1 : 0,
                'status' => 'pending',
                'state' => '10', // 인성 API 처리상태: 10(접수)
                'total_amount' => $this->request->getPost('total_amount') ?? 0,
                'add_cost' => $this->request->getPost('add_cost') ?? 0,
                'discount_cost' => $this->request->getPost('discount_cost') ?? 0,
                'delivery_cost' => $this->request->getPost('delivery_cost') ?? 0,
                'car_kind' => $this->request->getPost('car_kind') ?? null,
                'payment_type' => $this->request->getPost('payment_type'),
                'notes' => $this->request->getPost('notes'),
                'reserve_check' => $this->request->getPost('reserve_check') ?? 0,
                'reserve_date' => $this->request->getPost('reserve_date') ?? null,
                'reserve_hour' => $this->request->getPost('reserve_hour') ?? null,
                'reserve_min' => $this->request->getPost('reserve_min') ?? null,
                'reserve_sec' => $this->request->getPost('reserve_sec') ?? 0,
                'notification_service' => $this->request->getPost('notification_service') ? 1 : 0
            ];
            
            // life-driver 서비스의 경우 추가 필드를 tbl_orders에도 저장
            if ($serviceType === 'life-driver') {
                $orderData['call_type'] = $this->request->getPost('callType') ?? null;
                $orderData['total_fare'] = $this->request->getPost('total_fare') ? (float)$this->request->getPost('total_fare') : 0.00;
                $orderData['postpaid_fare'] = $this->request->getPost('postpaid_fare') ? (float)$this->request->getPost('postpaid_fare') : 0.00;
                $orderData['distance'] = $this->request->getPost('distance') ? (float)$this->request->getPost('distance') : 0.00;
                $orderData['cash_fare'] = $this->request->getPost('cash_fare') ? (float)$this->request->getPost('cash_fare') : 0.00;
            }
            
            // 택배 서비스의 경우 추가 필드를 tbl_orders에 저장
            if (in_array($serviceType, ['parcel-visit', 'parcel-same-day', 'parcel-convenience', 'parcel-night', 'parcel-bag'])) {
                $orderData['weight'] = $this->request->getPost('weight') ?? null;
                $orderData['dimensions'] = $this->request->getPost('dimensions') ?? null;
                $orderData['insurance_amount'] = $this->request->getPost('insurance_amount') ?? 0;
                
                // parcel-bag 서비스의 경우 bagType과 bagMaterial도 저장
                if ($serviceType === 'parcel-bag') {
                    $orderData['bag_type'] = $this->request->getPost('bagType') ?? null;
                    $orderData['bag_material'] = $this->request->getPost('bagMaterial') ?? null;
                }
                
                // parcel-visit 서비스의 경우 박스/행낭 정보 저장
                if ($serviceType === 'parcel-visit') {
                    $orderData['box_selection'] = $this->request->getPost('box_selection') ?? null;
                    $orderData['box_quantity'] = (int)($this->request->getPost('box_quantity') ?? 0);
                    $orderData['pouch_selection'] = $this->request->getPost('pouch_selection') ?? null;
                    $orderData['pouch_quantity'] = (int)($this->request->getPost('pouch_quantity') ?? 0);
                }
            }
            
            log_message('debug', 'Before createOrder - service_type_id in orderData: ' . var_export($orderData['service_type_id'] ?? 'NOT SET', true));

            $orderId = $orderModel->createOrder($orderData);

            if (!$orderId) {
                log_message('error', 'Order insert failed');
                throw new \Exception('주문 저장에 실패했습니다.');
            }

            // log_message('debug', 'Order inserted with ID: ' . $orderId);

            // 3-1. 퀵 서비스인 경우 인성 API 주문 접수
            if (in_array($serviceType, ['quick-motorcycle', 'quick-vehicle', 'quick-flex', 'quick-moving'])) {
                try {
                    $insungApiService = new \App\Libraries\InsungApiService();
                    $insungApiListModel = new \App\Models\InsungApiListModel();
                    
                    // 로그인 타입 확인 (STN 로그인 여부)
                    $isStnLogin = ($loginType !== 'daumdata');
                    
                    // 세션에서 API 정보 가져오기
                    $mCode = session()->get('m_code');
                    $ccCode = session()->get('cc_code');
                    $token = session()->get('token');
                    $apiIdx = session()->get('api_idx');
                    // $userId는 이미 위에서 설정되었으므로 재사용 (tbl_users_list.idx)
                    // 인성 API에는 user_id 문자열을 전달해야 하므로 $insungApiUserId 사용
                    
                    // api_idx가 있으면 그것으로 API 정보 조회 (로그인 시 선택한 API)
                    if ($apiIdx) {
                        $apiInfo = $insungApiListModel->find($apiIdx);
                        if ($apiInfo) {
                            $mCode = $apiInfo['mcode'] ?? $mCode;
                            $ccCode = $apiInfo['cccode'] ?? $ccCode;
                            $token = $apiInfo['token'] ?? $token;
                        }
                    }
                    
                    // m_code, cc_code가 여전히 없는 경우 처리 (슈퍼유저 또는 서브도메인 접근)
                    if (empty($mCode) || empty($ccCode)) {
                        // 서브도메인 기반으로 조회 시도
                        $subdomainConfig = config('Subdomain');
                        $apiCodes = $subdomainConfig->getCurrentApiCodes();
                        
                        if ($apiCodes && !empty($apiCodes['m_code']) && !empty($apiCodes['cc_code'])) {
                            // 서브도메인에서 조회 성공
                            $mCode = $apiCodes['m_code'];
                            $ccCode = $apiCodes['cc_code'];
                            $subdomain = $subdomainConfig->getCurrentSubdomain();
                            log_message('info', "Subdomain access detected ({$subdomain}) - Using mcode={$mCode}, cccode={$ccCode}");
                        } else {
                            // 서브도메인 조회 실패 시 기본값 사용 (STN 로그인)
                            $mCode = '4540';
                            $ccCode = '7829';
                            $userId = '에스티엔온라인접수'; // STN 로그인 시 고정 user_id
                            log_message('info', "STN login detected - Using default mcode={$mCode}, cccode={$ccCode}, user_id={$userId}");
                        }
                    }
                    
                    // api_idx가 없으면 mcode, cccode로 조회
                    if (!$apiIdx) {
                        $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                        $apiIdx = $apiInfo ? $apiInfo['idx'] : null;
                    }
                    
                    // 토큰이 없으면 DB에서 가져오기
                    if (empty($token)) {
                        if ($apiIdx) {
                            $apiInfo = $insungApiListModel->find($apiIdx);
                            if ($apiInfo) {
                                $token = $apiInfo['token'] ?? '';
                            }
                        } else {
                            $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                            if ($apiInfo) {
                                $token = $apiInfo['token'] ?? '';
                            }
                        }
                    }
                    
                    // daumdata 로그인인 경우 인성 API에 전달할 user_id (문자열) 조회
                    // $userId는 tbl_users_list.idx (숫자)이므로, 인성 API에는 user_id 문자열을 전달해야 함
                    $insungApiUserId = null;
                    if (!$isStnLogin) {
                        // 이미 위에서 $insungUserId를 조회했으므로 재사용
                        if (!empty($insungUserId)) {
                            $insungApiUserId = $insungUserId;
                        } else {
                            // $insungUserId가 없으면 세션에서 user_id 문자열 가져오기
                            $insungApiUserId = session()->get('user_id');
                        }
                    } else {
                        // STN 로그인: 고정 user_id 사용
                        $insungApiUserId = '에스티엔온라인접수';
                    }
                    
                    if ($mCode && $ccCode && $token && $insungApiUserId && $apiIdx) {
                        // 과적 옵션 확인 (박스 또는 행낭 중형 선택 시)
                        $isOverload = false;
                        $boxOverload = $this->request->getPost('box_medium_overload_check_other') ?? $this->request->getPost('box_medium_overload_check') ?? '';
                        $pouchOverload = $this->request->getPost('pouch_medium_overload_check_other') ?? $this->request->getPost('pouch_medium_overload_check') ?? '';
                        if ($boxOverload === '1' || $pouchOverload === '1') {
                            $isOverload = true;
                        }
                        
                        // 인성 API에 전달할 주문 데이터 구성
                        $insungOrderData = [
                            'service_type' => $serviceType,
                            'delivery_method' => $this->request->getPost('delivery_method') ?? 'motorcycle',
                            'deliveryMethod' => $this->request->getPost('delivery_route') ?? $this->request->getPost('deliveryMethod') ?? $this->request->getPost('delivery_method') ?? $this->request->getPost('doc') ?? 'one_way', // 배송방법 (편도/왕복/경유)
                            'deliveryType' => $this->request->getPost('deliveryType') ?? $this->request->getPost('delivery_type') ?? $this->request->getPost('sfast') ?? 'normal', // 배송형태 (일반/급송)
                            'urgency_level' => $this->request->getPost('urgency_level') ?? 'normal', // 긴급도
                            'is_overload' => $isOverload, // 과적 옵션
                            'company_name' => $this->request->getPost('company_name'),
                            'contact' => $this->request->getPost('contact'),
                            'sms_telno' => $this->request->getPost('sms_telno') ?? $this->request->getPost('contact'),
                            'o_c_code' => $this->request->getPost('o_c_code') ?? '',
                            'departure_company_name' => $this->request->getPost('departure_company_name'),
                            'departure_contact' => $this->request->getPost('departure_contact'),
                            'departure_address' => $this->request->getPost('departure_address'),
                            'departure_detail' => $this->request->getPost('departure_detail'),
                            'departure_manager' => $this->request->getPost('departure_manager'),
                            'departure_department' => $this->request->getPost('departure_department'),
                            // 루비 버전 참조: departure_dong2가 있으면 우선 사용, 없으면 departure_dong 사용
                            'departure_dong' => $this->request->getPost('departure_dong2') ?: $this->request->getPost('departure_dong'),
                            'departure_lon' => $this->request->getPost('departure_lon') ?? '',
                            'departure_lat' => $this->request->getPost('departure_lat') ?? '',
                            // 루비 버전 참조: departure_fulladdr (지번 주소, 좌표 조회용)
                            'departure_fulladdr' => $this->request->getPost('departure_fulladdr') ?? '',
                            's_c_code' => $this->request->getPost('s_c_code') ?? '',
                            'destination_company_name' => $this->request->getPost('destination_company_name'),
                            'destination_contact' => $this->request->getPost('destination_contact'),
                            'destination_address' => $this->request->getPost('destination_address'),
                            'detail_address' => $this->request->getPost('detail_address'),
                            'destination_manager' => $this->request->getPost('destination_manager'),
                            'destination_department' => $this->request->getPost('destination_department'),
                            // 루비 버전 참조: destination_dong2가 있으면 우선 사용, 없으면 destination_dong 사용
                            'destination_dong' => $this->request->getPost('destination_dong2') ?: $this->request->getPost('destination_dong'),
                            'destination_lon' => $this->request->getPost('destination_lon') ?? '',
                            'destination_lat' => $this->request->getPost('destination_lat') ?? '',
                            // 루비 버전 참조: destination_fulladdr (지번 주소, 좌표 조회용)
                            'destination_fulladdr' => $this->request->getPost('destination_fulladdr') ?? '',
                            'd_c_code' => $this->request->getPost('d_c_code') ?? '',
                            'item_type' => $this->request->getPost('item_type') ?? $this->request->getPost('itemType') ?? $this->getDefaultItemType($serviceType),
                            'delivery_content' => $this->request->getPost('delivery_content') ?? $this->request->getPost('special_instructions') ?? $this->request->getPost('deliveryInstructions') ?? '',
                            'notes' => $this->request->getPost('notes') ?? $this->request->getPost('special_instructions') ?? $this->request->getPost('deliveryInstructions') ?? '',
                            'payment_type' => $this->request->getPost('payment_type'),
                            'total_amount' => $this->request->getPost('total_amount') ? (string)(float)$this->request->getPost('total_amount') : '0',
                            'add_cost' => $this->request->getPost('add_cost') ? (string)(float)$this->request->getPost('add_cost') : '0',
                            'discount_cost' => $this->request->getPost('discount_cost') ? (string)(float)$this->request->getPost('discount_cost') : '0',
                            'delivery_cost' => $this->request->getPost('delivery_cost') ? (string)(float)$this->request->getPost('delivery_cost') : '0',
                            // 차량무게와 차량종류를 조합하여 car_kind 생성
                            'car_kind' => $this->buildCarKind($this->request->getPost('truck_capacity'), $this->request->getPost('truck_body_type')),
                            'reserve_check' => $this->request->getPost('reserve_check') ?? '0',
                            'reserve_date' => $this->request->getPost('reserve_date') ?? '',
                            'reserve_hour' => $this->request->getPost('reserve_hour') ?? '',
                            'reserve_min' => $this->request->getPost('reserve_min') ?? '',
                            'reserve_sec' => $this->request->getPost('reserve_sec') ?? '0',
                            'distance' => $this->request->getPost('distance') ?? ''
                        ];
                        
                        $insungResult = $insungApiService->registerOrder($mCode, $ccCode, $token, $insungApiUserId, $insungOrderData, $apiIdx);
                        
                        if ($insungResult['success'] && !empty($insungResult['serial_number'])) {
                            // 인성 주문번호 저장 및 order_number, user_id 업데이트 (daumdata 로그인은 INSUNG- 형식 사용)
                            $updateData = [
                                'insung_order_number' => $insungResult['serial_number'],
                                'order_number' => 'INSUNG-' . $insungResult['serial_number']
                            ];
                            
                            // user_id도 함께 업데이트 (인터넷 접수 시 인성 API에 전달한 user_id)
                            // $userId는 tbl_users_list.idx (숫자)이므로 그대로 저장
                            if (!empty($userId)) {
                                $updateData['user_id'] = $userId;
                                
                                // insung_user_id도 함께 저장 (tbl_users_list에서 user_id 문자열 조회)
                                $userListBuilder = $db->table('tbl_users_list');
                                $userListBuilder->select('user_id');
                                $userListBuilder->where('idx', $userId);
                                $userListQuery = $userListBuilder->get();
                                if ($userListQuery !== false) {
                                    $userListResult = $userListQuery->getRowArray();
                                    if ($userListResult && !empty($userListResult['user_id'])) {
                                        $updateData['insung_user_id'] = $userListResult['user_id'];
                                    }
                                }
                            }
                            
                            $orderModel->update($orderId, $updateData);
                            
                            // 인성 접수 데이터를 별도 테이블에 저장
                            try {
                                $insungOrderModel = new \App\Models\InsungOrderModel();
                                
                                // 인성 API에 전송한 request body ($params)와 응답의 serial_number 저장
                                // registerOrder의 결과에 request_params가 포함되어 있음
                                $requestParams = $insungResult['request_params'] ?? null;
                                
                                if ($requestParams) {
                                    $saveResult = $insungOrderModel->saveInsungOrderData(
                                        $orderId, 
                                        $requestParams, 
                                        $insungResult['serial_number'] ?? null
                                    );
                                    
                                    if ($saveResult) {
                                        log_message('info', "Insung order data saved: order_id={$orderId}, serial_number={$insungResult['serial_number']}");
                                    } else {
                                        log_message('error', "Failed to save Insung order data: order_id={$orderId}");
                                    }
                                } else {
                                    log_message('warning', "Insung request_params not found in API result for order_id={$orderId}");
                                }
                            } catch (\Exception $e) {
                                log_message('error', "Exception saving Insung order data: " . $e->getMessage());
                                // 인성 접수 데이터 저장 실패해도 주문은 정상 처리됨
                            }
                            
                            log_message('info', "Insung API order registered successfully. Order ID: {$orderId}, Serial Number: {$insungResult['serial_number']}, User ID: {$userId}");
                        } else {
                            log_message('warning', "Insung API order registration failed. Order ID: {$orderId}, Message: " . ($insungResult['message'] ?? 'Unknown error'));
                            // 인성 API 실패 시 주문 상태를 'api_failed'로 변경
                            try {
                                $orderModel->update($orderId, [
                                    'status' => 'api_failed'
                                ]);
                                log_message('info', "Order status updated to 'api_failed' for Order ID: {$orderId}");
                            } catch (\Exception $e) {
                                log_message('error', "Failed to update order status to 'api_failed' for Order ID: {$orderId}. Error: " . $e->getMessage());
                            }
                        }
                    } else {
                        log_message('warning', "Insung API order registration skipped. Missing required session data. Order ID: {$orderId}");
                    }
                } catch (\Exception $e) {
                    log_message('error', "Insung API order registration exception: " . $e->getMessage());
                    // 인성 API 예외 발생 시 주문 상태를 'api_failed'로 변경
                    try {
                        $orderModel->update($orderId, [
                            'status' => 'api_failed'
                        ]);
                        log_message('info', "Order status updated to 'api_failed' due to exception for Order ID: {$orderId}");
                    } catch (\Exception $updateException) {
                        log_message('error', "Failed to update order status to 'api_failed' for Order ID: {$orderId}. Error: " . $updateException->getMessage());
                    }
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
                            // awb_no로 직접 업데이트하는 방식 사용 (더 안정적)
                            $markResult = $awbPoolModel->markAsUsedByAwbNo($awbNo, $orderNumber);
                            
                            if ($markResult) {
                                log_message('info', "AWB No assigned: {$awbNo} (Platform: {$platformCode}) to order: {$orderNumber}");
                            } else {
                                log_message('error', "AWB No assignment failed: {$awbNo} (Platform: {$platformCode}) to order: {$orderNumber}");
                            }
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
                        
                        // 일양 API 응답 확인 (returnCode와 successCount 확인)
                        if ($apiResult['success']) {
                            $returnCode = $apiResult['return_code'] ?? '';
                            $returnDesc = $apiResult['return_desc'] ?? '';
                            $successCount = $apiResult['success_count'] ?? 0;
                            
                            // returnCode가 'R0'이고 successCount가 0보다 크면 성공
                            if ($returnCode === 'R0' && $successCount > 0) {
                                log_message('info', "{$apiType} API success: returnCode={$returnCode}, successCount={$successCount}, " . json_encode($apiResult['data']));
                                
                                // 일양 API 연동 성공 시 order_system과 order_number 업데이트 및 일양 접수 데이터 저장
                                if ($apiType === 'ilyang') {
                                    // order_number 형식: ILYANG-{기존주문번호} 또는 ILYANG-{운송장번호}
                                    $ilyangOrderNumber = 'ILYANG-' . ($orderNumber ?: ($orderInfo['shipping_tracking_number'] ?? $orderId));
                                    
                                    $updateData = [
                                        'order_system' => 'ilyang',
                                        'order_number' => $ilyangOrderNumber,
                                        'status' => 'processing'  // API 연동 성공 시 자동으로 '접수완료' 상태로 변경
                                    ];
                                    
                                    $orderModel->update($orderId, $updateData);
                                    log_message('info', "Ilyang API order updated: order_id={$orderId}, order_system=ilyang, order_number={$ilyangOrderNumber}, status=processing");
                                    
                                    // 일양 접수 데이터를 별도 테���블에 저장
                                    try {
                                        $ilyangOrderModel = new \App\Models\IlyangOrderModel();
                                        
                                        // API 응답에서 변��된 waybillData 가져오기
                                        if (isset($apiResult['waybill_data']) && is_array($apiResult['waybill_data'])) {
                                            $waybillData = $apiResult['waybill_data'];
                                            $saveResult = $ilyangOrderModel->saveIlyangOrderData($orderId, $waybillData);
                                            
                                            if ($saveResult) {
                                                log_message('info', "Ilyang order data saved: order_id={$orderId}, ily_awb_no={$waybillData['ilyAwbNo']}");
                                            } else {
                                                log_message('error', "Failed to save Ilyang order data: order_id={$orderId}");
                                            }
                                        } else {
                                            // waybill_data가 없으면 deliveryData를 기반으로 다시 변환
                                            if (method_exists($apiService, 'convertOrderToWaybillData')) {
                                                $waybillData = $apiService->convertOrderToWaybillData($deliveryData);
                                                $saveResult = $ilyangOrderModel->saveIlyangOrderData($orderId, $waybillData);
                                                
                                                if ($saveResult) {
                                                    log_message('info', "Ilyang order data saved (converted): order_id={$orderId}, ily_awb_no={$waybillData['ilyAwbNo']}");
                                                } else {
                                                    log_message('error', "Failed to save Ilyang order data: order_id={$orderId}");
                                                }
                                            } else {
                                                log_message('warning', "Ilyang API service does not have convertOrderToWaybillData method");
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        log_message('error', "Exception saving Ilyang order data: " . $e->getMessage());
                                        // 일양 접수 데이터 저장 실패해도 주문은 정상 처리됨
                                    }
                                }
                            } else {
                                // 비즈니스 로직 실패 (returnCode가 'R0'이 아니거나 successCount가 0)
                                log_message('error', "{$apiType} API business error - Order: {$orderNumber}, returnCode: {$returnCode}, returnDesc: {$returnDesc}, successCount: {$successCount}, " . json_encode($apiResult['data']));
                            }
                            
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
                // log_message('debug', 'Quick service data inserted for order ID: ' . $orderId);
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
                // log_message('debug', 'General service data inserted for order ID: ' . $orderId);
            }

            // 8. 택배 서비스 전용 데이터 저장 (tbl_orders_parcel 사용 안 함, tbl_orders만 사용)
            // 택배 서비스의 경우 별도 테이블에 저장하지 않고 tbl_orders에만 저장
            if (in_array($serviceType, ['parcel-visit', 'parcel-same-day', 'parcel-convenience', 'parcel-night', 'parcel-bag'])) {
                // log_message('debug', 'Parcel service order created (tbl_orders only) for order ID: ' . $orderId);
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
                // log_message('debug', 'Life service data inserted for order ID: ' . $orderId);
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
            
            // 최종 주문번호 조회 (인성 API 등록 후 업데이트된 주문번호를 사용)
            $finalOrderNumber = $orderNumber;
            if ($orderId) {
                $finalDb = \Config\Database::connect();
                $orderBuilder = $finalDb->table('tbl_orders');
                $orderBuilder->select('order_number');
                $orderBuilder->where('id', $orderId);
                $orderQuery = $orderBuilder->get();
                if ($orderQuery !== false) {
                    $orderResult = $orderQuery->getRowArray();
                    if ($orderResult && !empty($orderResult['order_number'])) {
                        $finalOrderNumber = $orderResult['order_number'];
                    }
                }
                $finalDb->close();
            }
            
            return redirect()->to('/delivery/list')
                ->with('success', "{$serviceName} 주문이 성공적으로 접수되었습니다. 주문번호: {$finalOrderNumber}");
            
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
        log_message('debug', 'getServiceTypeId called with serviceType: ' . var_export($serviceType, true));

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
     * 차량무게와 차량종류를 조합하여 car_kind 생성
     * 루비 버전 참조: car_kind는 select 태그(<select name="car_kind" id="itemtype_kind">)에서 직접 선택된 값
     * 만약 select에서 선택된 값이 없으면, truck_capacity와 truck_body_type을 조합하여 생성
     * 
     * @param string|null $truckCapacity 차량무게 (예: '25t', '5t')
     * @param string|null $truckBodyType 차량종류 (예: 'extra_long_axle', 'cargo', 'plus_wing')
     * @return string car_kind 값 (빈 문자열 또는 조합된 값)
     */
    private function buildCarKind($truckCapacity = null, $truckBodyType = null)
    {
        // 루비 버전 참조: car_kind는 select 태그에서 직접 선택된 값이 우선
        $carKind = $this->request->getPost('car_kind');
        if (!empty($carKind)) {
            return $carKind;
        }
        
        // 트럭이 아닌 경우 (다마스, 라보 등) car_kind는 빈 값
        $deliveryMethod = $this->request->getPost('delivery_method');
        $serviceType = $this->request->getPost('service_type');
        
        // 트럭 서비스가 아닌 경우 빈 값 반환
        if ($deliveryMethod !== 'truck' && !in_array($serviceType, ['quick-vehicle', 'quick-moving'])) {
            return '';
        }
        
        // 인성 API 문서 참조: 차종구분 코드 매핑
        // API 문서: 01: 플축카고, 11: 리프트카고, 12: 플러스리, 42: 플축리, 02: 윙바디, 03: 플러스윙, 04: 축윙, 05: 플축윙, 14: 리프트윙, 16: 플러스윙리, 17: 플축윙리, 06: 탑, 08: 리프트탑, 07: 호루, 50: 리프트호루, 47: 자바라, 51: 리프트자바라, 18: 냉동탑, 19: 냉장탑, 21: 냉동윙, 22: 냉장윙, 23: 냉동탑리, 24: 냉장탑리, 25: 냉동플축윙, 26: 냉장플축윙, 27: 냉동플축리, 28: 냉장플축리, 29: 평카, 30: 로브이, 31: 츄레라, 34: 로베드, 32: 사다리, 33: 초장축
        $bodyTypeMap = [
            'plus_axle_cargo' => '01',          // 플축카고
            'lift_cargo' => '11',               // 리프트카고
            'plus_lift' => '12',                // 플러스리
            'plus_axle_lift' => '42',           // 플축리
            'wing_body' => '02',                // 윙바디
            'plus_wing' => '03',                // 플러스윙
            'axle_wing' => '04',                // 축윙
            'plus_axle_wing' => '05',           // 플축윙
            'lift_wing' => '14',                // 리프트윙
            'plus_wing_lift' => '16',           // 플러스윙리
            'plus_axle_wing_lift' => '17',      // 플축윙리
            'top' => '06',                      // 탑
            'lift_top' => '08',                 // 리프트탑
            'tarpaulin' => '07',                // 호루
            'lift_tarpaulin' => '50',           // 리프트호루
            'jabara' => '47',                   // 자바라
            'lift_jabara' => '51',              // 리프트자바라
            'refrigerated_top' => '18',         // 냉동탑
            'cold_storage_top' => '19',         // 냉장탑
            'refrigerated_wing' => '21',        // 냉동윙
            'cold_storage_wing' => '22',         // 냉장윙
            'refrigerated_top_lift' => '23',    // 냉동탑리
            'cold_storage_top_lift' => '24',    // 냉장탑리
            'refrigerated_plus_axle_wing' => '25', // 냉동플축윙
            'cold_storage_plus_axle_wing' => '26', // 냉장플축윙
            'refrigerated_plus_axle_lift' => '27', // 냉동플축리
            'cold_storage_plus_axle_lift' => '28', // 냉장플축리
            'flat_car' => '29',                 // 평카
            'lowboy' => '30',                   // 로브이
            'trailer' => '31',                  // 츄레라
            'lowbed' => '34',                   // 로베드
            'ladder' => '32',                   // 사다리
            'extra_long_axle' => '33',          // 초장축
        ];
        
        // truck_body_type이 있으면 숫자 코드로 변환
        if (!empty($truckBodyType) && isset($bodyTypeMap[$truckBodyType])) {
            return $bodyTypeMap[$truckBodyType];
        }
        
        // 매핑되지 않은 경우 빈 값 반환 (인성 API가 특정 형식만 받으므로)
        return '';
    }
    
    /**
     * 전달사항 내용 조합 (배송사유 포함)
     * 배송사유가 있으면 전달사항 앞에 추가
     */
    private function buildDeliveryContent()
    {
        $deliveryReason = trim($this->request->getPost('delivery_reason') ?? '');
        $specialInstructions = $this->request->getPost('delivery_content')
            ?? $this->request->getPost('special_instructions')
            ?? '';

        // 배송사유가 있으면 앞에 추가
        if (!empty($deliveryReason)) {
            if (!empty($specialInstructions)) {
                return "[배송사유] " . $deliveryReason . "\n" . $specialInstructions;
            }
            return "[배송사유] " . $deliveryReason;
        }

        return $specialInstructions;
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
    
    /**
     * 상차방법/하차방법을 delivery_content에 추가
     */
    private function buildDeliveryContentWithLoadingUnloading($deliveryContent, $deliveryMethod, $loadingMethod, $unloadingMethod, $loadingMethodTruck = null, $unloadingMethodTruck = null)
    {
        $content = $deliveryContent ?? '';
        $loadingUnloadingParts = [];
        
        // 배송수단이 다마스, 라보, 트럭일 때만 처리
        if (in_array($deliveryMethod, ['damas', 'labo', 'truck'])) {
            // 트럭인 경우 (라디오버튼 - 단일 값)
            if ($deliveryMethod === 'truck') {
                if (!empty($loadingMethodTruck)) {
                    // 배열이 아닌 단일 값으로 처리
                    $loadingValue = is_array($loadingMethodTruck) ? (isset($loadingMethodTruck[0]) ? $loadingMethodTruck[0] : '') : $loadingMethodTruck;
                    if (!empty($loadingValue)) {
                        $loadingUnloadingParts[] = '상차: ' . $loadingValue;
                    }
                }
                if (!empty($unloadingMethodTruck)) {
                    // 배열이 아닌 단일 값으로 처리
                    $unloadingValue = is_array($unloadingMethodTruck) ? (isset($unloadingMethodTruck[0]) ? $unloadingMethodTruck[0] : '') : $unloadingMethodTruck;
                    if (!empty($unloadingValue)) {
                        $loadingUnloadingParts[] = '하차: ' . $unloadingValue;
                    }
                }
            } else {
                // 다마스/라보인 경우
                if (!empty($loadingMethod)) {
                    $loadingUnloadingParts[] = '상차: ' . $loadingMethod;
                }
                if (!empty($unloadingMethod)) {
                    $loadingUnloadingParts[] = '하차: ' . $unloadingMethod;
                }
            }
        }
        
        // 상차/하차 내용이 있으면 기존 delivery_content 뒤에 추가
        if (!empty($loadingUnloadingParts)) {
            $loadingUnloadingText = implode(' ', $loadingUnloadingParts);
            if (!empty($content)) {
                $content = $content . ' ' . $loadingUnloadingText;
            } else {
                $content = $loadingUnloadingText;
            }
        }
        
        return $content;
    }
    
    /**
     * 최근 주문 조회 (출발지 또는 도착지 distinct 10개)
     * type: 'departure' 또는 'destination'
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getRecentOrdersForDeparture()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $type = $this->request->getGet('type') ?? 'departure'; // 'departure' 또는 'destination'
        
        $loginType = session()->get('login_type');
        
        // 로그인 타입에 따라 user_id 결정
        if ($loginType === 'daumdata') {
            $userId = session()->get('user_idx');
            $insungUserId = session()->get('user_id');
        } else {
            $userId = session()->get('user_id');
            $insungUserId = null;
        }
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '사용자 정보를 찾을 수 없습니다.'
            ])->setStatusCode(400);
        }
        
        // tbl_orders에서 본인이 등록한 주문만 조회
        $db = \Config\Database::connect();
        
        if ($type === 'departure') {
            // 출발지 distinct 조회
            $builder = $db->table('tbl_orders o');
            $builder->select('o.save_date, 
                o.departure_company_name, o.departure_contact, o.departure_department, o.departure_manager,
                o.departure_detail, o.departure_address, o.departure_dong,
                o.departure_lat, o.departure_lon');
            $builder->where('o.user_id', $userId);
            if ($loginType === 'daumdata' && $insungUserId) {
                $builder->where('o.insung_user_id', $insungUserId);
            }
            $oneMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
            $builder->where('o.save_date >=', $oneMonthAgo);
            $builder->where('o.departure_company_name IS NOT NULL');
            $builder->where('o.departure_company_name !=', '');
            $builder->groupBy('o.departure_company_name');
            $builder->orderBy('o.save_date', 'DESC');
            $builder->limit(10);
        } else {
            // 도착지 distinct 조회
            $builder = $db->table('tbl_orders o');
            $builder->select('o.save_date, 
                o.destination_company_name, o.destination_contact, o.destination_department, o.destination_manager,
                o.detail_address, o.destination_address, o.destination_dong,
                o.destination_lat, o.destination_lon');
            $builder->where('o.user_id', $userId);
            if ($loginType === 'daumdata' && $insungUserId) {
                $builder->where('o.insung_user_id', $insungUserId);
            }
            $oneMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
            $builder->where('o.save_date >=', $oneMonthAgo);
            $builder->where('o.destination_company_name IS NOT NULL');
            $builder->where('o.destination_company_name !=', '');
            $builder->groupBy('o.destination_company_name');
            $builder->orderBy('o.save_date', 'DESC');
            $builder->limit(10);
        }
        
        $query = $builder->get();
        
        if ($query === false) {
            log_message('error', 'Service::getRecentOrdersForDeparture - Query failed');
            return $this->response->setJSON([
                'success' => false,
                'message' => '최근 주문 조회에 실패했습니다.',
                'data' => []
            ]);
        }
        
        $orders = $query->getResultArray();
        
        // 연락처 필드 복호화 처리
        $encryptionHelper = new \App\Libraries\EncryptionHelper();
        $phoneField = $type === 'departure' ? 'departure_contact' : 'destination_contact';
        foreach ($orders as &$order) {
            // 전화번호 필드 복호화
            $order = $encryptionHelper->decryptFields($order, [$phoneField]);
            
            // 날짜 포맷팅 (Y-m-d H:i:s -> Y-m-d)
            if (isset($order['save_date'])) {
                $date = new \DateTime($order['save_date']);
                $order['save_date'] = $date->format('Y-m-d');
            }
        }
        unset($order);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $orders
        ]);
    }
}
