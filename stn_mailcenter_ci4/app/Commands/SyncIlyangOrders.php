<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\IlyangApiService;
use App\Models\OrderModel;
use App\Libraries\ApiServiceFactory;

class SyncIlyangOrders extends BaseCommand
{
    protected $group       = 'ilyang';
    protected $name        = 'ilyang:sync-orders';
    protected $description = '일양 API 주문 상태를 동기화합니다.';
    protected $usage       = 'ilyang:sync-orders [start_date] [end_date]';
    protected $arguments   = [
        'start_date' => '시작일자 (YYYY-MM-DD, 기본값: 오늘)',
        'end_date'   => '종료일자 (YYYY-MM-DD, 기본값: 오늘)'
    ];

    public function run(array $params)
    {
        $startDate = $params[0] ?? date('Y-m-d');
        $endDate = $params[1] ?? date('Y-m-d');

        CLI::write("일양 API 주문 상태 동기화를 시작합니다...", 'yellow');
        CLI::write("기간: {$startDate} ~ {$endDate}", 'green');

        try {
            $orderModel = new OrderModel();
            $db = \Config\Database::connect();
            
            // order_system='ilyang'이고 shipping_tracking_number가 있는 주문 조회
            $builder = $db->table('tbl_orders o');
            $builder->select('o.id, o.order_number, o.shipping_tracking_number, o.status, o.order_date');
            $builder->where('o.order_system', 'ilyang');
            $builder->where('o.shipping_tracking_number IS NOT NULL', null, false);
            $builder->where('o.shipping_tracking_number !=', '');
            
            // 날짜 필터 (order_date 기준)
            if ($startDate) {
                $builder->where('DATE(o.order_date) >=', $startDate);
            }
            if ($endDate) {
                $builder->where('DATE(o.order_date) <=', $endDate);
            }
            
            $query = $builder->get();
            $orders = $query ? $query->getResultArray() : [];
            
            if (empty($orders)) {
                CLI::write("동기화할 일양 주문이 없습니다.", 'yellow');
                return;
            }
            
            CLI::write("동기화 대상 주문 수: " . count($orders), 'green');
            
            // 일양 API 서비스 초기화
            $apiService = ApiServiceFactory::create('ilyang', true); // 테스트 모드
            
            if (!$apiService) {
                CLI::error("일양 API 서비스를 초기화할 수 없습니다.");
                return;
            }
            
            $syncedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];
            
            // 운송장번호를 100건씩 묶어서 조회 (일양 API 제한)
            $trackingNumbers = array_column($orders, 'shipping_tracking_number');
            $chunks = array_chunk($trackingNumbers, 100);
            
            foreach ($chunks as $chunkIndex => $chunk) {
                CLI::write("배송정보 조회 중... (청크 " . ($chunkIndex + 1) . "/" . count($chunks) . ", " . count($chunk) . "건)", 'yellow');
                
                // 일양 API로 배송정보 조회
                $apiResult = $apiService->getDeliveryStatus($chunk);
                
                if (!$apiResult['success']) {
                    CLI::error("배송정보 조회 실패: " . ($apiResult['error'] ?? '알 수 없는 오류'));
                    $errorCount += count($chunk);
                    continue;
                }
                
                $returnCode = $apiResult['return_code'] ?? '';
                if ($returnCode !== 'R0') {
                    CLI::error("배송정보 조회 실패: returnCode={$returnCode}, returnDesc=" . ($apiResult['return_desc'] ?? ''));
                    $errorCount += count($chunk);
                    continue;
                }
                
                // 응답 데이터 파싱
                $responseData = $apiResult['data'] ?? [];
                $traces = $responseData['body']['trace'] ?? [];
                
                if (empty($traces)) {
                    CLI::write("조회된 배송정보가 없습니다.", 'yellow');
                    continue;
                }
                
                // 각 배송정보에 대해 주문 상태 업데이트
                foreach ($traces as $trace) {
                    $hawbNo = $trace['hawb_no'] ?? '';
                    $orderNo = $trace['order_no'] ?? '';
                    $eventymd = $trace['eventymd'] ?? '';
                    $eventnm = $trace['eventnm'] ?? '';
                    $signernm = $trace['signernm'] ?? '';
                    $itemlist = $trace['itemlist'] ?? [];
                    
                    if (empty($hawbNo)) {
                        continue;
                    }
                    
                    // 운송장번호로 주문 찾기
                    $order = null;
                    foreach ($orders as $o) {
                        if ($o['shipping_tracking_number'] === $hawbNo) {
                            $order = $o;
                            break;
                        }
                    }
                    
                    if (!$order) {
                        continue;
                    }
                    
                    // itemlist에서 최신 배송상태 추출
                    $latestTrace = null;
                    $latestDate = '';
                    $latestTime = '';
                    
                    foreach ($itemlist as $itemGroup) {
                        $items = $itemGroup['item'] ?? [];
                        if (!is_array($items)) {
                            $items = [$items];
                        }
                        
                        foreach ($items as $item) {
                            $statusDate = $item['status_date'] ?? '';
                            $statusTime = $item['status_time'] ?? '';
                            $traceCode = $item['tracecode'] ?? '';
                            
                            // 최신 상태 찾기 (날짜+시간 기준)
                            $datetime = $statusDate . ' ' . $statusTime;
                            if (empty($latestDate) || $datetime > ($latestDate . ' ' . $latestTime)) {
                                $latestDate = $statusDate;
                                $latestTime = $statusTime;
                                $latestTrace = $item;
                            }
                        }
                    }
                    
                    if ($latestTrace) {
                        $traceCode = $latestTrace['tracecode'] ?? '';
                        $nondlcode = $latestTrace['nondlcode'] ?? '';
                        $tracestatus = $latestTrace['tracestatus'] ?? '';
                        
                        // 일양 배송상태 코드를 로컬 DB 상태값으로 매핑
                        $newStatus = $apiService->mapTraceCodeToStatus($traceCode, $nondlcode);
                        
                        // 배송정보 데이터 구성
                        $deliveryData = [
                            'trace_code' => $traceCode,
                            'status' => $newStatus,
                            'eventymd' => $eventymd,
                            'signernm' => $signernm,
                            'itemlist' => $itemlist
                        ];
                        
                        // 일양 배송정보 업데이트 (상태 포함)
                        $updateResult = $orderModel->updateIlyangDeliveryInfo($order['id'], $deliveryData);
                        
                        if ($updateResult) {
                            $updatedCount++;
                            $statusChanged = ($order['status'] !== $newStatus) ? " ({$order['status']} -> {$newStatus})" : "";
                            CLI::write("주문 배송정보 업데이트: order_id={$order['id']}, hawb_no={$hawbNo}, trace_code={$traceCode}{$statusChanged} ({$tracestatus})", 'green');
                        } else {
                            $errorCount++;
                            $errors[] = "주문 배송정보 업데이트 실패: order_id={$order['id']}, hawb_no={$hawbNo}";
                        }
                    } else {
                        // itemlist가 없어도 기본 정보는 저장
                        $deliveryData = [
                            'eventymd' => $eventymd,
                            'signernm' => $signernm,
                            'itemlist' => $itemlist
                        ];
                        $updateResult = $orderModel->updateIlyangDeliveryInfo($order['id'], $deliveryData);
                        if ($updateResult) {
                            $syncedCount++;
                        }
                    }
                }
            }
            
            CLI::write("동기화 완료!", 'green');
            CLI::write("총 조회: " . count($orders) . "건", 'green');
            CLI::write("상태 변경: {$updatedCount}건", 'green');
            CLI::write("변경 없음: {$syncedCount}건", 'green');
            
            if ($errorCount > 0) {
                CLI::write("오류 발생: {$errorCount}건", 'yellow');
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
