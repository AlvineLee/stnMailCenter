<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\InsungApiService;
use App\Models\OrderModel;
use App\Models\DeliveryModel;
use App\Models\InsungApiListModel;

class SyncInsungOrderDetails extends BaseCommand
{
    protected $group       = 'insung';
    protected $name        = 'insung:sync-order-details';
    protected $description = '인성 API 주문 상세 정보를 동기화합니다.';
    protected $usage       = 'insung:sync-order-details [user_type] [cc_code] [comp_name]';
    protected $arguments   = [
        'user_type' => '사용자 타입 (1: 전체, 3: 콜센터, 5: 고객사)',
        'cc_code'   => '콜센터 코드 (user_type=3일 때 필수)',
        'comp_name' => '고객사명 (user_type=5일 때 필수)'
    ];

    public function run(array $params)
    {
        $userType = $params[0] ?? null;
        $ccCode = $params[1] ?? null;
        $compName = $params[2] ?? null;

        CLI::write("인성 API 주문 상세 정보 동기화를 시작합니다...", 'yellow');

        try {
            // 필터 조건 구성
            $filters = [];
            if ($userType == '3' && !empty($ccCode)) {
                $filters['cc_code'] = $ccCode;
                CLI::write("콜센터 코드: {$ccCode}", 'green');
            } elseif ($userType == '5' && !empty($compName)) {
                $filters['comp_name'] = $compName;
                CLI::write("고객사명: {$compName}", 'green');
            } elseif ($userType == '1') {
                CLI::write("전체 조회 모드", 'green');
            } else {
                CLI::error('필수 파라미터가 누락되었습니다.');
                CLI::write('사용법: ' . $this->usage, 'yellow');
                return;
            }

            // 인성 주문번호가 있는 주문들 조회 (API 정보 포함)
            $deliveryModel = new DeliveryModel();
            $orders = $deliveryModel->getInsungOrdersForSync($filters);

            if (empty($orders)) {
                CLI::write("동기화할 주문이 없습니다.", 'yellow');
                return;
            }

            CLI::write("동기화 대상 주문 수: " . count($orders), 'green');

            // 인성 API 서비스 초기화
            $insungApiService = new InsungApiService();
            $orderModel = new OrderModel();

            $syncedCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $errors = [];

            // 각 주문에 대해 인성 API로 상세 조회 및 업데이트
            foreach ($orders as $order) {
                try {
                    // 주문별 API 정보 확인 (DB에서 조회된 정보 사용)
                    $mCode = $order['m_code'] ?? null;
                    $ccCodeOrder = $order['cc_code'] ?? null;
                    $token = $order['token'] ?? null;
                    $userId = $order['insung_user_id'] ?? null;
                    $apiIdx = $order['api_idx'] ?? null;
                    $serialNumber = $order['insung_order_number'] ?? null;

                    // STN 로그인 주문의 경우 기본 API 정보 사용
                    if (empty($mCode) || empty($ccCodeOrder)) {
                        $mCode = '4540';
                        $ccCodeOrder = '7829';
                        $userId = '에스티엔온라인접수';

                        // 기본 API 정보로 api_idx 조회
                        $insungApiListModel = new InsungApiListModel();
                        $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode($mCode, $ccCodeOrder);
                        if ($apiInfo) {
                            $apiIdx = $apiInfo['idx'];
                            $token = $apiInfo['token'] ?? '';
                        }

                        CLI::write("STN 로그인 주문 - 기본 API 정보 사용: mcode={$mCode}, cccode={$ccCodeOrder}", 'yellow');
                    }

                    // 필수 API 정보가 없으면 스킵
                    if (!$mCode || !$ccCodeOrder || !$token || !$userId || !$apiIdx || !$serialNumber) {
                        $skippedCount++;
                        $errors[] = "주문번호 {$order['order_number']}: API 정보가 불완전합니다.";
                        CLI::write("  스킵: 주문번호 {$order['order_number']} - API 정보 불완전", 'yellow');
                        continue;
                    }

                    // 인성 API로 주문 상세 조회 (리스트 형태로 처리하므로 주석처리)
                    /*
                    CLI::write("  처리 중: 주문번호 {$order['order_number']} (Serial: {$serialNumber})", 'cyan');
                    $apiResult = $insungApiService->getOrderDetail($mCode, $ccCodeOrder, $token, $userId, $serialNumber, $apiIdx);

                    if (!$apiResult['success'] || !isset($apiResult['data'])) {
                        $errorCount++;
                        $errors[] = "주문번호 {$order['order_number']}: {$apiResult['message']}";
                        CLI::write("    실패: {$apiResult['message']}", 'red');
                        continue;
                    }

                    $apiData = $apiResult['data'];

                    // API 응답 파싱 및 업데이트 데이터 구성
                    $updateData = [];
                    $getValue = function($data, $key, $default = null) {
                        if (is_object($data)) {
                            return $data->$key ?? $default;
                        } elseif (is_array($data)) {
                            return $data[$key] ?? $default;
                        }
                        return $default;
                    };

                    $parseAmount = function($value) {
                        if (empty($value)) return null;
                        $cleaned = str_replace(['원', ',', ' '], '', $value);
                        return is_numeric($cleaned) ? (float)$cleaned : null;
                    };

                    $parseDateTime = function($value) {
                        if (empty($value)) return null;
                        $timestamp = strtotime($value);
                        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                    };

                    $mapInsungStatusToLocal = function($saveState) {
                        $statusMap = [
                            '10' => 'pending',
                            '11' => 'processing',
                            '12' => 'processing',
                            '20' => 'pending',
                            '30' => 'completed',
                            '40' => 'cancelled',
                            '50' => 'pending',
                            '90' => 'pending'
                        ];
                        return $statusMap[$saveState] ?? 'pending';
                    };

                    // 1. 접수자 정보 ($apiData[1])
                    if (isset($apiData[1])) {
                        $customerInfo = $apiData[1];
                        $customerName = $getValue($customerInfo, 'customer_name');
                        if ($customerName && $customerName !== ($order['customer_name'] ?? '')) {
                            $updateData['customer_name'] = $customerName;
                        }
                        $customerTel = $getValue($customerInfo, 'customer_tel_number');
                        if ($customerTel && $customerTel !== ($order['customer_tel_number'] ?? '')) {
                            $updateData['customer_tel_number'] = $customerTel;
                        }
                        $customerDept = $getValue($customerInfo, 'customer_department');
                        if ($customerDept && $customerDept !== ($order['customer_department'] ?? '')) {
                            $updateData['customer_department'] = $customerDept;
                        }
                        $customerDuty = $getValue($customerInfo, 'customer_duty');
                        if ($customerDuty && $customerDuty !== ($order['customer_duty'] ?? '')) {
                            $updateData['customer_duty'] = $customerDuty;
                        }
                    }

                    // 2. 기사 정보 ($apiData[2])
                    if (isset($apiData[2])) {
                        $riderInfo = $apiData[2];
                        $riderCodeNo = $getValue($riderInfo, 'rider_code_no');
                        if ($riderCodeNo && $riderCodeNo !== ($order['rider_code_no'] ?? '')) {
                            $updateData['rider_code_no'] = $riderCodeNo;
                        }
                        $riderName = $getValue($riderInfo, 'rider_name');
                        if ($riderName && $riderName !== ($order['rider_name'] ?? '')) {
                            $updateData['rider_name'] = $riderName;
                        }
                        $riderTel = $getValue($riderInfo, 'rider_tel_number');
                        if ($riderTel && $riderTel !== ($order['rider_tel_number'] ?? '')) {
                            $updateData['rider_tel_number'] = $riderTel;
                        }
                        $riderLon = $getValue($riderInfo, 'rider_lon');
                        if ($riderLon && $riderLon !== ($order['rider_lon'] ?? '')) {
                            $updateData['rider_lon'] = $riderLon;
                        }
                        $riderLat = $getValue($riderInfo, 'rider_lat');
                        if ($riderLat && $riderLat !== ($order['rider_lat'] ?? '')) {
                            $updateData['rider_lat'] = $riderLat;
                        }
                    }

                    // 3. 주문 시간 정보 ($apiData[3])
                    if (isset($apiData[3])) {
                        $timeInfo = $apiData[3];
                        $orderTime = $getValue($timeInfo, 'order_time');
                        if ($orderTime) {
                            $parsedTime = $parseDateTime($orderTime);
                            if ($parsedTime) {
                                $updateData['order_time'] = date('H:i:s', strtotime($parsedTime));
                                $updateData['order_date'] = date('Y-m-d', strtotime($parsedTime));
                            }
                        }
                        $allocationTime = $getValue($timeInfo, 'allocation_time');
                        if ($allocationTime) {
                            $parsedAllocation = $parseDateTime($allocationTime);
                            if ($parsedAllocation && $parsedAllocation !== ($order['allocation_time'] ?? null)) {
                                $updateData['allocation_time'] = $parsedAllocation;
                            }
                        }
                        $pickupTime = $getValue($timeInfo, 'pickup_time');
                        if ($pickupTime) {
                            $parsedPickup = $parseDateTime($pickupTime);
                            if ($parsedPickup && $parsedPickup !== ($order['pickup_time'] ?? null)) {
                                $updateData['pickup_time'] = $parsedPickup;
                            }
                        }
                        $resolveTime = $getValue($timeInfo, 'resolve_time');
                        if ($resolveTime) {
                            $parsedResolve = $parseDateTime($resolveTime);
                            if ($parsedResolve && $parsedResolve !== ($order['resolve_time'] ?? null)) {
                                $updateData['resolve_time'] = $parsedResolve;
                            }
                        }
                        $completeTime = $getValue($timeInfo, 'complete_time');
                        if ($completeTime) {
                            $parsedComplete = $parseDateTime($completeTime);
                            if ($parsedComplete && $parsedComplete !== ($order['complete_time'] ?? null)) {
                                $updateData['complete_time'] = $parsedComplete;
                            }
                        }
                    }

                    // 4. 주소 정보 ($apiData[4])
                    if (isset($apiData[4])) {
                        $addressInfo = $apiData[4];
                        $departureDong = $getValue($addressInfo, 'departure_dong_name');
                        if ($departureDong && $departureDong !== ($order['departure_dong'] ?? '')) {
                            $updateData['departure_dong'] = $departureDong;
                        }
                        $departureAddress = $getValue($addressInfo, 'departure_address');
                        if ($departureAddress && $departureAddress !== ($order['departure_address'] ?? '')) {
                            $updateData['departure_address'] = $departureAddress;
                        }
                        $departureCompany = $getValue($addressInfo, 'departure_company_name');
                        if ($departureCompany && $departureCompany !== ($order['departure_company_name'] ?? '')) {
                            $updateData['departure_company_name'] = $departureCompany;
                        }
                        $startLon = $getValue($addressInfo, 'start_lon');
                        if ($startLon && $startLon !== ($order['departure_lon'] ?? '')) {
                            $updateData['departure_lon'] = $startLon;
                        }
                        $startLat = $getValue($addressInfo, 'start_lat');
                        if ($startLat && $startLat !== ($order['departure_lat'] ?? '')) {
                            $updateData['departure_lat'] = $startLat;
                        }
                        $startCCode = $getValue($addressInfo, 'start_c_code');
                        if ($startCCode && $startCCode !== ($order['s_c_code'] ?? '')) {
                            $updateData['s_c_code'] = $startCCode;
                        }
                        $startDept = $getValue($addressInfo, 'start_department');
                        if ($startDept && $startDept !== ($order['departure_department'] ?? '')) {
                            $updateData['departure_department'] = $startDept;
                        }
                        $startDuty = $getValue($addressInfo, 'start_duty');
                        if ($startDuty && $startDuty !== ($order['departure_manager'] ?? '')) {
                            $updateData['departure_manager'] = $startDuty;
                        }
                        $destDong = $getValue($addressInfo, 'destination_dong_name');
                        if ($destDong && $destDong !== ($order['destination_dong'] ?? '')) {
                            $updateData['destination_dong'] = $destDong;
                        }
                        $destAddress = $getValue($addressInfo, 'destination_address');
                        if ($destAddress && $destAddress !== ($order['destination_address'] ?? '')) {
                            $updateData['destination_address'] = $destAddress;
                        }
                        $destCompany = $getValue($addressInfo, 'destination_company_name');
                        if ($destCompany && $destCompany !== ($order['destination_company_name'] ?? '')) {
                            $updateData['destination_company_name'] = $destCompany;
                        }
                        $destTel = $getValue($addressInfo, 'destination_tel_number');
                        if ($destTel && $destTel !== ($order['destination_contact'] ?? '')) {
                            $updateData['destination_contact'] = $destTel;
                        }
                        $destLon = $getValue($addressInfo, 'dest_lon');
                        if ($destLon && $destLon !== ($order['destination_lon'] ?? '')) {
                            $updateData['destination_lon'] = $destLon;
                        }
                        $destLat = $getValue($addressInfo, 'dest_lat');
                        if ($destLat && $destLat !== ($order['destination_lat'] ?? '')) {
                            $updateData['destination_lat'] = $destLat;
                        }
                        $destCCode = $getValue($addressInfo, 'dest_c_code');
                        if ($destCCode && $destCCode !== ($order['d_c_code'] ?? '')) {
                            $updateData['d_c_code'] = $destCCode;
                        }
                        $destDept = $getValue($addressInfo, 'dest_department');
                        if ($destDept && $destDept !== ($order['destination_department'] ?? '')) {
                            $updateData['destination_department'] = $destDept;
                        }
                        $destDuty = $getValue($addressInfo, 'dest_duty');
                        if ($destDuty && $destDuty !== ($order['destination_manager'] ?? '')) {
                            $updateData['destination_manager'] = $destDuty;
                        }
                        $distance = $getValue($addressInfo, 'distince');
                        if ($distance) {
                            $parsedDistance = $parseAmount($distance);
                            if ($parsedDistance !== null && $parsedDistance != ($order['distance'] ?? 0)) {
                                $updateData['distance'] = $parsedDistance;
                            }
                        }
                        $happyCall = $getValue($addressInfo, 'happy_call');
                        if ($happyCall && $happyCall !== ($order['happy_call'] ?? '')) {
                            $updateData['happy_call'] = $happyCall;
                        }
                    }

                    // 5. 금액 정보 ($apiData[5])
                    if (isset($apiData[5])) {
                        $costInfo = $apiData[5];
                        $saveState = $getValue($costInfo, 'save_state');
                        if (!empty($saveState)) {
                            $status = $mapInsungStatusToLocal($saveState);
                            if ($status && $status !== ($order['status'] ?? '')) {
                                $updateData['status'] = $status;
                            }
                        }
                        $state = $getValue($costInfo, 'state');
                        if ($state && $state !== ($order['state'] ?? '')) {
                            $updateData['state'] = $state;
                        }
                        $totalCost = $getValue($costInfo, 'total_cost');
                        if ($totalCost) {
                            $parsedAmount = $parseAmount($totalCost);
                            if ($parsedAmount !== null && $parsedAmount != ($order['total_amount'] ?? 0)) {
                                $updateData['total_amount'] = $parsedAmount;
                            }
                        }
                        $basicCost = $getValue($costInfo, 'basic_cost');
                        if ($basicCost) {
                            $parsedBasic = $parseAmount($basicCost);
                            if ($parsedBasic !== null && $parsedBasic != ($order['total_fare'] ?? 0)) {
                                $updateData['total_fare'] = $parsedBasic;
                            }
                        }
                        $additionCost = $getValue($costInfo, 'addition_cost');
                        if ($additionCost) {
                            $parsedAddition = $parseAmount($additionCost);
                            if ($parsedAddition !== null && $parsedAddition != ($order['add_cost'] ?? 0)) {
                                $updateData['add_cost'] = $parsedAddition;
                            }
                        }
                        $discountCost = $getValue($costInfo, 'discount_cost');
                        if ($discountCost) {
                            $parsedDiscount = $parseAmount($discountCost);
                            if ($parsedDiscount !== null && $parsedDiscount != ($order['discount_cost'] ?? 0)) {
                                $updateData['discount_cost'] = $parsedDiscount;
                            }
                        }
                        $deliveryCost = $getValue($costInfo, 'delivery_cost');
                        if ($deliveryCost) {
                            $parsedDelivery = $parseAmount($deliveryCost);
                            if ($parsedDelivery !== null && $parsedDelivery != ($order['delivery_cost'] ?? 0)) {
                                $updateData['delivery_cost'] = $parsedDelivery;
                            }
                        }
                        $carType = $getValue($costInfo, 'car_type');
                        $cargoType = $getValue($costInfo, 'cargo_type');
                        $cargoName = $getValue($costInfo, 'cargo_name');
                        if ($cargoType && $cargoType !== ($order['car_kind'] ?? '')) {
                            $updateData['car_kind'] = $cargoType;
                        }
                        if ($carType && $carType !== ($order['car_type'] ?? '')) {
                            $updateData['car_type'] = $carType;
                        }
                        if ($cargoName && $cargoName !== ($order['cargo_name'] ?? '')) {
                            $updateData['cargo_name'] = $cargoName;
                        }
                        $payment = $getValue($costInfo, 'payment');
                        if ($payment) {
                            $paymentTypeMap = [
                                '착불' => 'cash_on_delivery',
                                '선불' => 'cash_in_advance',
                                '계좌이체' => 'bank_transfer',
                                '신용거래' => 'credit_transaction'
                            ];
                            $mappedPaymentType = $paymentTypeMap[$payment] ?? null;
                            if ($mappedPaymentType && $mappedPaymentType !== ($order['payment_type'] ?? '')) {
                                $updateData['payment_type'] = $mappedPaymentType;
                            }
                        }
                    }

                    // 9. 기타 정보 ($apiData[9])
                    if (isset($apiData[9])) {
                        $extraInfo = $apiData[9];
                        if (empty($updateData['status'])) {
                            $saveState = $getValue($extraInfo, 'save_state');
                            if (!empty($saveState)) {
                                $status = $mapInsungStatusToLocal($saveState);
                                if ($status && $status !== ($order['status'] ?? '')) {
                                    $updateData['status'] = $status;
                                }
                            }
                        }
                        $itemType = $getValue($extraInfo, 'item_type');
                        if ($itemType && $itemType !== ($order['item_type'] ?? '')) {
                            $updateData['item_type'] = $itemType;
                        }
                        $summary = $getValue($extraInfo, 'summary');
                        if ($summary && $summary !== ($order['delivery_content'] ?? '')) {
                            $updateData['delivery_content'] = $summary;
                        }
                        $reason = $getValue($extraInfo, 'reason');
                        if ($reason && $reason !== ($order['reason'] ?? '')) {
                            $updateData['reason'] = $reason;
                        }
                        $orderRegistType = $getValue($extraInfo, 'order_regist_type');
                        if ($orderRegistType && $orderRegistType !== ($order['order_regist_type'] ?? '')) {
                            $updateData['order_regist_type'] = $orderRegistType;
                        }
                        $doc = $getValue($extraInfo, 'doc');
                        if ($doc && $doc !== ($order['doc'] ?? '')) {
                            $updateData['doc'] = $doc;
                        }
                        $sfast = $getValue($extraInfo, 'sfast');
                        if ($sfast && $sfast !== ($order['sfast'] ?? '')) {
                            $updateData['sfast'] = $sfast;
                        }
                    }

                    // 업데이트할 데이터가 있으면 DB 업데이트
                    if (!empty($updateData)) {
                        $updateData['updated_at'] = date('Y-m-d H:i:s');
                        $orderModel->update($order['id'], $updateData);
                        $syncedCount++;
                        CLI::write("    성공: " . count($updateData) . "개 필드 업데이트", 'green');
                    } else {
                        CLI::write("    변경사항 없음", 'yellow');
                    }
                    */
                    
                    // 리스트 형태로 처리하므로 개별 주문상세 API 호출은 스킵
                    $skippedCount++;
                    CLI::write("  스킵: 주문번호 {$order['order_number']} (리스트 형태로 처리됨)", 'yellow');

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "주문번호 {$order['order_number']}: " . $e->getMessage();
                    CLI::write("    오류: " . $e->getMessage(), 'red');
                }
            }

            CLI::write("", '');
            CLI::write("동기화 완료!", 'green');
            CLI::write("동기화 성공: {$syncedCount}건", 'green');
            CLI::write("스킵: {$skippedCount}건", 'yellow');
            CLI::write("실패: {$errorCount}건", 'red');

            if (!empty($errors)) {
                CLI::write("", '');
                CLI::write("오류 상세:", 'yellow');
                foreach ($errors as $error) {
                    CLI::write("  - {$error}", 'red');
                }
            }

        } catch (\Exception $e) {
            CLI::error("동기화 중 오류 발생: " . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');
        }
    }
}

