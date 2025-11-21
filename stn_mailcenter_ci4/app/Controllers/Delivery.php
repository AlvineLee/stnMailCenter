<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DeliveryModel;

class Delivery extends BaseController
{
    protected $deliveryModel;

    public function __construct()
    {
        $this->deliveryModel = new DeliveryModel();
    }
    
    public function list()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        $userType = session()->get('user_type');
        $ccCode = session()->get('cc_code');
        $compName = session()->get('comp_name');
        
        // 검색 조건 처리
        $searchType = $this->request->getGet('search_type') ?? 'all';
        $searchKeyword = $this->request->getGet('search_keyword') ?? '';
        $statusFilter = $this->request->getGet('status') ?? 'all';
        $serviceFilter = $this->request->getGet('service') ?? 'all';
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = 20;
        
        // 필터 조건 구성
        $filters = [
            'search_type' => $searchType,
            'search_keyword' => $searchKeyword,
            'status' => $statusFilter,
            'service' => $serviceFilter
        ];
        
        // daumdata 로그인인 경우 user_type별 필터링
        if ($loginType === 'daumdata') {
            if ($userType == '1') {
                // user_type = 1: 전체 고객사의 주문접수 리스트
                $filters['customer_id'] = null; // 전체 조회
            } elseif ($userType == '3') {
                // user_type = 3: 소속 콜센터의 고객사 주문접수 리스트만
                $filters['cc_code'] = $ccCode;
            } elseif ($userType == '5') {
                // user_type = 5: 본인 고객사의 주문접수 리스트만
                $filters['comp_name'] = $compName;
            }
        } else {
            // STN 로그인인 경우 기존 로직
            $filters['customer_id'] = $userRole !== 'super_admin' ? $customerId : null;
        }
        
        // Model을 통한 데이터 조회
        $result = $this->deliveryModel->getDeliveryList($filters, $page, $perPage);
        $orders = $result['orders'];
        $totalCount = $result['total_count'];
        
        // 서비스 통계 조회
        $serviceTypes = $this->deliveryModel->getServiceStats(
            $userRole !== 'super_admin' ? $customerId : null
        );
        
        // 페이징 정보 계산
        $pagination = $this->deliveryModel->calculatePagination($totalCount, $page, $perPage);
        
        $data = [
            'title' => '배송조회(리스트)',
            'content_header' => [
                'title' => '배송조회(리스트)',
                'description' => '전체 배송 현황을 조회할 수 있습니다.'
            ],
            'orders' => $orders,
            'pagination' => $pagination,
            'service_types' => $serviceTypes,
            'search_type' => $searchType,
            'search_keyword' => $searchKeyword,
            'status_filter' => $statusFilter,
            'service_filter' => $serviceFilter,
            'status_options' => [
                'all' => '전체',
                'pending' => '대기중',
                'processing' => '처리중',
                'completed' => '완료',
                'cancelled' => '취소'
            ],
            'search_type_options' => [
                'all' => '전체',
                'order_number' => '주문번호',
                'service_name' => '서비스명',
                'customer_name' => '고객사명',
                'departure_address' => '출발지',
                'destination_address' => '도착지'
            ],
            'user_role' => $userRole,
            'customer_id' => $customerId
        ];
        
        return view('delivery/list', $data);
    }
    
    /**
     * 주문 상세 정보 조회 (AJAX) - 암호화된 주문번호 사용
     */
    public function getOrderDetail()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $encryptedOrderNumber = $this->request->getGet('order_number');
        if (!$encryptedOrderNumber) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호가 필요합니다.'
            ])->setStatusCode(400);
        }
        
        try {
            // 인코딩된 주문번호 디코딩 (간단한 Base64 디코딩)
            $orderNumber = base64_decode($encryptedOrderNumber);
            if (!$orderNumber) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '유효하지 않은 주문번호입니다.'
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Order number decoding failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호 디코딩에 실패했습니다.'
            ])->setStatusCode(400);
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        try {
            // Model을 통한 주문 상세 정보 조회
            $order = $this->deliveryModel->getOrderDetail(
                $orderNumber, 
                $userRole !== 'super_admin' ? $customerId : null
            );
            
            if (!$order) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문 정보를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }
            
            // 라벨 매핑
            $statusLabels = [
                'pending' => '대기중',
                'processing' => '접수완료',
                'completed' => '배송중',
                'delivered' => '배송완료',
                'cancelled' => '취소'
            ];
            
            $paymentLabels = [
                'cash_on_delivery' => '착불',
                'cash_in_advance' => '선불',
                'bank_transfer' => '계좌이체',
                'credit_transaction' => '신용거래'
            ];
            
            $urgencyLabels = [
                'normal' => '일반',
                'urgent' => '긴급',
                'super_urgent' => '초긴급'
            ];
            
            $deliveryMethodLabels = [
                'motorcycle' => '퀵오토바이',
                'vehicle' => '퀵차량',
                'flex' => '퀵플렉스',
                'moving' => '퀵이사'
            ];
            
            // 응답 데이터 구성
            $responseData = [
                'success' => true,
                'data' => [
                    'order_number' => $order['order_number'],
                    'service_name' => $order['service_name'],
                    'service_code' => $order['service_code'] ?? '',
                    'service_category' => $order['service_category'],
                    'customer_name' => $order['customer_name'],
                    'shipping_tracking_number' => $order['shipping_tracking_number'] ?? '',
                    'shipping_platform_code' => $order['shipping_platform_code'] ?? '',
                    'user_name' => $order['user_name'],
                    'status' => $order['status'],
                    'status_label' => $statusLabels[$order['status']] ?? $order['status'],
                    'created_at' => $order['created_at'],
                    'updated_at' => $order['updated_at'],
                    'total_amount' => number_format($order['total_amount']) . '원',
                    'payment_type' => $paymentLabels[$order['payment_type']] ?? $order['payment_type'],
                    'notes' => $order['notes'],
                    
                    // 주문자 정보
                    'company_name' => $order['company_name'],
                    'contact' => $order['contact'],
                    'address' => $order['address'],
                    
                    // 출발지 정보
                    'departure_address' => $order['departure_address'],
                    'departure_detail' => $order['departure_detail'],
                    'departure_contact' => $order['departure_contact'],
                    
                    // 경유지 정보
                    'waypoint_address' => $order['waypoint_address'],
                    'waypoint_detail' => $order['waypoint_detail'],
                    'waypoint_contact' => $order['waypoint_contact'],
                    'waypoint_notes' => $order['waypoint_notes'],
                    
                    // 도착지 정보
                    'destination_type' => $order['destination_type'],
                    'mailroom' => $order['mailroom'],
                    'destination_address' => $order['destination_address'],
                    'detail_address' => $order['detail_address'],
                    'destination_contact' => $order['destination_contact'],
                    
                    // 물품 정보
                    'item_type' => $order['item_type'],
                    'quantity' => $order['quantity'],
                    'unit' => $order['unit'],
                    'delivery_content' => $order['delivery_content'],
                    
                    // 퀵 서비스 정보
                    'delivery_method' => $order['delivery_method'],
                    'delivery_method_label' => $deliveryMethodLabels[$order['delivery_method']] ?? $order['delivery_method'],
                    'urgency_level' => $order['urgency_level'],
                    'urgency_label' => $urgencyLabels[$order['urgency_level']] ?? $order['urgency_level'],
                    'estimated_time' => $order['estimated_time'],
                    'pickup_time' => $order['pickup_time'],
                    'delivery_time' => $order['delivery_time'],
                    'driver_contact' => $order['driver_contact'],
                    'vehicle_info' => $order['vehicle_info'],
                    'delivery_instructions' => $order['delivery_instructions'],
                    'delivery_route' => $order['delivery_route'],
                    'box_selection' => $order['box_selection'],
                    'box_quantity' => $order['box_quantity'],
                    'pouch_selection' => $order['pouch_selection'],
                    'pouch_quantity' => $order['pouch_quantity'],
                    'shopping_bag_selection' => $order['shopping_bag_selection'],
                    'additional_fee' => $order['additional_fee'] ? number_format($order['additional_fee']) . '원' : '0원'
                ]
            ];
            
            return $this->response->setJSON($responseData);
            
        } catch (\Exception $e) {
            log_message('error', 'Order detail query failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 정보 조회 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 주문 상태 변경
     */
    public function updateStatus()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }
        
        $orderNumber = $inputData['order_number'] ?? null;
        $newStatus = $inputData['status'] ?? null;
        
        if (!$orderNumber || !$newStatus) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호와 상태가 필요합니다.'
            ])->setStatusCode(400);
        }
        
        // 유효한 상태인지 확인
        $validStatuses = ['pending', 'processing', 'completed', 'delivered'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '유효하지 않은 상태입니다.'
            ])->setStatusCode(400);
        }
        
        try {
            // 주문 조회
            $order = $this->deliveryModel->getOrderDetail($orderNumber);
            
            if (!$order) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문을 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }
            
            // 상태 업데이트 (Model 사용) - 모든 서비스에 대해 상태 변경 허용
            $orderModel = new \App\Models\OrderModel();
            $result = $orderModel->updateOrderStatus($order['id'], $newStatus);
            
            // 상태가 '접수완료(processing)'로 변경되고, 송장번호가 없고, 택배/해외특송 서비스인 경우 송장번호 할당
            if ($result && $newStatus === 'processing' && empty($order['shipping_tracking_number'])) {
                $serviceCode = $order['service_code'] ?? '';
                $serviceCategory = $order['service_category'] ?? '';
                
                // 택배 서비스 또는 해외특송 서비스인지 확인
                $isShippingService = (
                    in_array($serviceCode, ['international', 'parcel-visit', 'parcel-same-day', 'parcel-convenience', 'parcel-night', 'parcel-bag']) ||
                    $serviceCategory === 'parcel' ||
                    $serviceCategory === 'special'
                );
                
                if ($isShippingService) {
                    try {
                        // 송장번호 할당 로직 (Service::create와 동일)
                        $apiServiceFactory = \App\Libraries\ApiServiceFactory::class;
                        $activeShippingCompany = \App\Libraries\ApiServiceFactory::getActiveShippingCompany($serviceCode);
                        
                        if ($activeShippingCompany) {
                            $platformCode = $activeShippingCompany['platform_code'];
                            $awbPoolModel = new \App\Models\AwbPoolModel();
                            $awbPool = $awbPoolModel->getAvailableAwbNo();
                            
                            if ($awbPool && isset($awbPool['awb_no'])) {
                                $awbNo = $awbPool['awb_no'];
                                
                                // 송장번호 업데이트
                                $orderModel->updateShippingInfo($order['id'], $platformCode, $awbNo);
                                
                                // 송장번호 풀에서 사용 처리
                                $awbPoolModel->markAsUsed($awbNo, $orderNumber);
                                
                                log_message('info', "AWB No assigned on status change: {$awbNo} (Platform: {$platformCode}) to order: {$orderNumber}");
                            } else {
                                log_message('warning', "No available AWB number in pool for order: {$orderNumber}");
                                // 송장번호가 없어도 플랫폼코드는 저장
                                $orderModel->updateShippingInfo($order['id'], $platformCode);
                            }
                        }
                    } catch (\Exception $e) {
                        log_message('error', 'AWB assignment on status change failed: ' . $e->getMessage());
                        // 송장번호 할당 실패해도 상태 변경은 성공으로 처리
                    }
                }
            }
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => '주문 상태가 변경되었습니다.'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '상태 변경에 실패했습니다.'
                ])->setStatusCode(500);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::updateStatus - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '상태 변경 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 송장출력 페이지
     */
    public function printWaybill()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $orderNumber = $this->request->getGet('order_number');
        $trackingNumber = $this->request->getGet('tracking_number');
        
        if (!$orderNumber || !$trackingNumber) {
            return redirect()->to('/delivery/list')->with('error', '주문번호와 송장번호가 필요합니다.');
        }
        
        try {
            // 주문 정보 조회
            $order = $this->deliveryModel->getOrderDetail($orderNumber);
            
            if (!$order) {
                return redirect()->to('/delivery/list')->with('error', '주문을 찾을 수 없습니다.');
            }
            
            // 송장번호 조회 API 호출
            $apiService = \App\Libraries\ApiServiceFactory::createForService(
                $order['service_code'] ?? 'international', 
                true // 테스트 모드
            );
            
            if (!$apiService) {
                return redirect()->to('/delivery/list')->with('error', 'API 서비스를 초기화할 수 없습니다.');
            }
            
            // 송장번호 조회 (주문 데이터 전달)
            $waybillData = $apiService->getWaybillData($trackingNumber, $order);
            
            if (!$waybillData || !$waybillData['success']) {
                return redirect()->to('/delivery/list')->with('error', '송장 정보를 조회할 수 없습니다.');
            }
            
            $data = [
                'title' => '송장출력',
                'order' => $order,
                'waybill_data' => $waybillData['data'] ?? []
            ];
            
            return view('delivery/print_waybill', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::printWaybill - ' . $e->getMessage());
            return redirect()->to('/delivery/list')->with('error', '송장출력 중 오류가 발생했습니다.');
        }
    }

    /**
     * 인성 API 주문 동기화 (AJAX)
     * 배송조회 리스트 진입 시 인성 주문번호가 있는 주문들의 최신 상태를 동기화
     * 세션 의존 제거: 각 주문의 user_id를 통해 DB에서 API 정보를 조회
     */
    public function syncInsungOrders()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }
        
        $loginType = session()->get('login_type');
        
        // daumdata 로그인만 동기화 가능
        if ($loginType !== 'daumdata') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '인성 API 동기화는 daumdata 로그인에서만 가능합니다.'
            ])->setStatusCode(403);
        }
        
        try {
            // 사용자 권한에 따른 필터 조건 구성 (선택적)
            $userType = session()->get('user_type');
            $ccCode = session()->get('cc_code');
            $compName = session()->get('comp_name');
            
            $filters = [];
            if ($userType == '3') {
                // user_type = 3: 소속 콜센터의 고객사 주문만
                $filters['cc_code'] = $ccCode;
            } elseif ($userType == '5') {
                // user_type = 5: 본인 고객사의 주문만
                $filters['comp_name'] = $compName;
            }
            // user_type = 1: 필터 없음 (전체 조회)
            
            // 인성 주문번호가 있는 주문들 조회 (API 정보 포함)
            $orders = $this->deliveryModel->getInsungOrdersForSync($filters);
            
            if (empty($orders)) {
                return $this->response->setJSON([
                    'success' => true,
                    'synced_count' => 0,
                    'message' => '동기화할 주문이 없습니다.'
                ]);
            }
            
            // 인성 API 서비스 초기화
            $insungApiService = new \App\Libraries\InsungApiService();
            $orderModel = new \App\Models\OrderModel();
            
            $syncedCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $errors = [];
            
            // 각 주문에 대해 인성 API로 상세 조회 및 업데이트
            foreach ($orders as $order) {
                try {
                    // 주문별 API 정보 확인 (DB에서 조회된 정보 사용)
                    $mCode = $order['m_code'] ?? null;
                    $ccCode = $order['cc_code'] ?? null;
                    $token = $order['token'] ?? null;
                    $userId = $order['insung_user_id'] ?? null;
                    $apiIdx = $order['api_idx'] ?? null;
                    $serialNumber = $order['insung_order_number'] ?? null;
                    
                    // 필수 API 정보가 없으면 스킵
                    if (!$mCode || !$ccCode || !$token || !$userId || !$apiIdx || !$serialNumber) {
                        $skippedCount++;
                        $errors[] = "주문번호 {$order['order_number']}: API 정보가 불완전합니다.";
                        log_message('warning', "Insung order sync skipped: Order ID {$order['id']}, Missing API info");
                        continue;
                    }
                    
                    // 인성 API로 주문 상세 조회 (주문별 API 정보 사용)
                    $apiResult = $insungApiService->getOrderDetail($mCode, $ccCode, $token, $userId, $serialNumber, $apiIdx);
                    
                    if (!$apiResult['success'] || !isset($apiResult['data'])) {
                        $errorCount++;
                        $errors[] = "주문번호 {$order['order_number']}: {$apiResult['message']}";
                        log_message('warning', "Insung order sync failed: Order ID {$order['id']}, Message: {$apiResult['message']}");
                        continue;
                    }
                    
                    $apiData = $apiResult['data'];
                    
                    // API 응답 파싱 (레거시 코드 구조 참고)
                    // $apiData[0]: 응답 코드
                    // $apiData[1]: 고객 정보
                    // $apiData[2]: 기사 정보
                    // $apiData[3]: 주문 시간 정보
                    // $apiData[4]: 주소 정보
                    // $apiData[5]: 금액 정보
                    // $apiData[7]: 완료 시간
                    // $apiData[9]: 기타 정보
                    
                    $updateData = [];
                    
                    // 상태 매핑 (save_state -> status)
                    if (isset($apiData[5]) && isset($apiData[5]->save_state)) {
                        $saveState = $apiData[5]->save_state ?? '';
                        $status = $this->mapInsungStatusToLocal($saveState);
                        if ($status) {
                            $updateData['status'] = $status;
                        }
                    }
                    
                    // 금액 업데이트
                    if (isset($apiData[5]) && isset($apiData[5]->total_cost)) {
                        $totalCost = $apiData[5]->total_cost ?? '';
                        // "원" 제거 및 쉼표 제거 후 숫자로 변환
                        $totalCost = str_replace(['원', ','], '', $totalCost);
                        if (is_numeric($totalCost)) {
                            $updateData['total_amount'] = (float)$totalCost;
                        }
                    }
                    
                    // 업데이트할 데이터가 있으면 DB 업데이트
                    if (!empty($updateData)) {
                        $orderModel->update($order['id'], $updateData);
                        $syncedCount++;
                        
                        log_message('info', "Insung order synced: Order ID {$order['id']}, Serial {$serialNumber}, Updated: " . json_encode($updateData));
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "주문번호 {$order['order_number']}: " . $e->getMessage();
                    log_message('error', "Insung order sync error for order {$order['order_number']}: " . $e->getMessage());
                }
            }
            
            $message = "{$syncedCount}개 주문이 동기화되었습니다.";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount}개 스킵)";
            }
            if ($errorCount > 0) {
                $message .= " ({$errorCount}개 실패)";
            }
            
            return $this->response->setJSON([
                'success' => true,
                'synced_count' => $syncedCount,
                'error_count' => $errorCount,
                'skipped_count' => $skippedCount,
                'total_count' => count($orders),
                'message' => $message,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Delivery::syncInsungOrders - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '동기화 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    
    /**
     * 인성 API 상태를 로컬 DB 상태로 매핑
     * 
     * @param string $insungStatus 인성 API의 save_state 값
     * @return string|null 로컬 DB의 status 값
     */
    private function mapInsungStatusToLocal($insungStatus)
    {
        $statusMap = [
            '접수' => 'processing',
            '배차' => 'processing',
            '운행' => 'completed',
            '완료' => 'delivered',
            '예약' => 'pending',
            '취소' => 'cancelled'
        ];
        
        return $statusMap[$insungStatus] ?? null;
    }
}
