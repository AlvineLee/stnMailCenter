<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="print-container" style="max-width: 1000px; margin: 0 auto; padding: 20px; background: white;">
    <div style="text-align: center; margin-bottom: 20px;">
        <button onclick="event.stopPropagation(); window.print(); return false;" class="form-button form-button-primary" style="margin-bottom: 10px;">인쇄</button>
        <button onclick="event.stopPropagation(); window.close(); return false;" class="form-button form-button-secondary">닫기</button>
    </div>
    
    <?php 
    $waybillData = $waybill_data['body']['trace'] ?? [];
    if (empty($waybillData)) {
        $waybillData = isset($waybill_data['body']['trace']) && is_array($waybill_data['body']['trace']) ? $waybill_data['body']['trace'] : [];
    }
    
    // waybillData가 비어있으면 주문 데이터로부터 송장 생성
    if (empty($waybillData) && !empty($order)) {
        $waybillData = [[
            'hawb_no' => $order['shipping_tracking_number'] ?? '',
            'order_no' => $order['order_number'] ?? '',
            'sendnm' => $order['departure_company_name'] ?? '',
            'recevnm' => $order['destination_company_name'] ?? '',
            'itemlist' => []
        ]];
    }
    
    foreach ($waybillData as $index => $trace):
        $hawbNo = $trace['hawb_no'] ?? $order['shipping_tracking_number'] ?? '';
        $sendnm = $trace['sendnm'] ?? $order['departure_company_name'] ?? '';
        $recevnm = $trace['recevnm'] ?? $order['destination_company_name'] ?? '';
        $orderNo = $trace['order_no'] ?? $order['order_number'] ?? '';
        $itemlist = $trace['itemlist'] ?? [];
        $eventymd = $trace['eventymd'] ?? '';
        $eventnm = $trace['eventnm'] ?? '';
        $signernm = $trace['signernm'] ?? '';
        
        // 주문 데이터에서 상세 정보 추출
        $departureCompany = $order['departure_company_name'] ?? '';
        $departureManager = $order['departure_manager'] ?? '';
        $departureContact = $order['departure_contact'] ?? '';
        $departurePhone2 = $order['departure_phone2'] ?? '';
        $departureAddress = $order['departure_address'] ?? '';
        $departureDetail = $order['departure_detail'] ?? '';
        $departureZip = preg_match('/(\d{5,6})/', $departureAddress, $matches) ? $matches[1] : '';
        
        $destinationCompany = $order['destination_company_name'] ?? '';
        $destinationManager = $order['destination_manager'] ?? '';
        $destinationContact = $order['destination_contact'] ?? '';
        $destinationPhone2 = $order['destination_phone2'] ?? '';
        $destinationAddress = $order['destination_address'] ?? '';
        $destinationDetail = $order['detail_address'] ?? '';
        $destinationZip = preg_match('/(\d{5,6})/', $destinationAddress, $matches) ? $matches[1] : '';
        
        $itemType = $order['item_type'] ?? '일반상품';
        $itemPrice = $order['item_price'] ?? $order['total_amount'] ?? '0';
        $quantity = $order['quantity'] ?? '1';
        $weight = $order['weight'] ?? '1';
        $paymentType = $order['payment_type'] ?? '신용';
        $deliveryFee = $order['delivery_cost'] ?? $order['cash_fare'] ?? '0';
        $deliveryNotes = $order['delivery_content'] ?? $order['notes'] ?? '';
        $orderDate = $order['order_date'] ?? date('Y-m-d');
        $shippingDate = date('Ymd', strtotime($orderDate));
    ?>
    <!-- 운송장 출력용지 (가로형: 왼쪽 락부, 오른쪽 본부) -->
    <div class="waybill-sheet" style="border: 2px solid #000; margin-bottom: 20px; page-break-after: always; width: 100%;">
        <div style="display: flex; min-height: 400px;">
            <!-- 왼쪽: 락부 (작은 부분) -->
            <div style="width: 180px; padding: 15px; border-right: 2px dashed #000; background: #fffbf0; display: flex; flex-direction: column;">
                <!-- 일양로지스 로고/센터명 -->
                <div style="text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 15px; border-bottom: 1px solid #000; padding-bottom: 5px;">
                    일양로지스
                </div>
                
                <!-- 바코드 영역 -->
                <div style="text-align: center; margin-bottom: 15px;">
                    <div style="font-size: 9px; margin-bottom: 3px; font-weight: bold;">운송장번호</div>
                    <div style="font-size: 16px; font-weight: bold; letter-spacing: 1px; margin-bottom: 5px;"><?= esc($hawbNo) ?></div>
                    <!-- 바코드 이미지 영역 -->
                    <div style="margin-top: 5px; height: 45px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; padding: 3px;">
                        <svg id="barcode-left-<?= $index ?>" style="height: 40px; width: 100%;"></svg>
                    </div>
                </div>
                
                <!-- 상품정보 -->
                <div style="margin-top: 10px; border: 1px solid #000; min-height: 120px; padding: 8px; flex: 1;">
                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-size: 11px; font-weight: bold; margin: 5px 0; text-align: center;">상품정보</div>
                    <div style="font-size: 9px; margin-top: 10px; line-height: 1.4;">
                        <div><strong>상품명:</strong> <?= esc($itemType) ?></div>
                        <div><strong>수량:</strong> <?= esc($quantity) ?>개</div>
                        <div><strong>중량:</strong> <?= esc($weight) ?>kg</div>
                        <?php if (!empty($itemPrice) && $itemPrice != '0'): ?>
                        <div><strong>가격:</strong> <?= number_format((int)$itemPrice) ?>원</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 수취주소 -->
                <div style="margin-top: 10px; border: 1px solid #000; min-height: 80px; padding: 8px; background: #fffbf0;">
                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-size: 10px; font-weight: bold; margin: 5px 0; float: left; margin-right: 8px;">수취주소</div>
                    <div style="font-size: 9px; line-height: 1.3; margin-top: 5px;">
                        <?php if (!empty($destinationZip)): ?>
                        <div>[<?= esc($destinationZip) ?>]</div>
                        <?php endif; ?>
                        <div><?= esc($destinationAddress) ?></div>
                        <?php if (!empty($destinationDetail)): ?>
                        <div><?= esc($destinationDetail) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 하단 운송장번호 반복 -->
                <div style="font-size: 8px; margin-top: 8px; text-align: center; border-top: 1px solid #ccc; padding-top: 5px;">
                    <?= esc($hawbNo) ?>
                </div>
            </div>
            
            <!-- 오른쪽: 본부 (큰 부분) -->
            <div style="flex: 1; padding: 20px; display: flex; flex-direction: column;">
                <!-- 헤더 -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px;">
                    <div>
                        <div style="font-size: 20px; font-weight: bold; margin-bottom: 3px;">운송장번호: <?= esc($hawbNo) ?></div>
                        <?php if (!empty($orderNo)): ?>
                        <div style="font-size: 12px; color: #666;">주문번호: <?= esc($orderNo) ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; font-weight: bold;">고객상담</div>
                        <div style="font-size: 14px; font-weight: bold; color: #4a90e2;">1588-0002</div>
                    </div>
                </div>
                
                <!-- 받는 분 / 보낸 분 (상세 정보) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <!-- 받는 분 -->
                    <div style="border: 2px solid #4a90e2; padding: 12px; background: #e8f4f8;">
                        <div style="font-size: 13px; font-weight: bold; color: #4a90e2; margin-bottom: 8px; border-bottom: 1px solid #4a90e2; padding-bottom: 3px;">받는 분</div>
                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;"><?= esc($recevnm ?: $destinationCompany) ?></div>
                        <?php if (!empty($destinationManager)): ?>
                        <div style="font-size: 11px; margin-bottom: 3px;">담당자: <?= esc($destinationManager) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($destinationContact)): ?>
                        <div style="font-size: 11px; margin-bottom: 3px;">전화: <?= esc($destinationContact) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($destinationPhone2)): ?>
                        <div style="font-size: 11px; margin-bottom: 3px;">휴대폰: <?= esc($destinationPhone2) ?></div>
                        <?php endif; ?>
                        <div style="font-size: 10px; margin-top: 5px; line-height: 1.4;">
                            <?php if (!empty($destinationZip)): ?>
                            <div>[<?= esc($destinationZip) ?>]</div>
                            <?php endif; ?>
                            <div><?= esc($destinationAddress) ?></div>
                            <?php if (!empty($destinationDetail)): ?>
                            <div><?= esc($destinationDetail) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 보낸 분 -->
                    <div style="border: 2px solid #4a90e2; padding: 12px; background: #e8f4f8;">
                        <div style="font-size: 13px; font-weight: bold; color: #4a90e2; margin-bottom: 8px; border-bottom: 1px solid #4a90e2; padding-bottom: 3px;">보낸 분</div>
                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;"><?= esc($sendnm ?: $departureCompany) ?></div>
                        <?php if (!empty($departureManager)): ?>
                        <div style="font-size: 11px; margin-bottom: 3px;">담당자: <?= esc($departureManager) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($departureContact)): ?>
                        <div style="font-size: 11px; margin-bottom: 3px;">전화: <?= esc($departureContact) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($departurePhone2)): ?>
                        <div style="font-size: 11px; margin-bottom: 3px;">휴대폰: <?= esc($departurePhone2) ?></div>
                        <?php endif; ?>
                        <div style="font-size: 10px; margin-top: 5px; line-height: 1.4;">
                            <?php if (!empty($departureZip)): ?>
                            <div>[<?= esc($departureZip) ?>]</div>
                            <?php endif; ?>
                            <div><?= esc($departureAddress) ?></div>
                            <?php if (!empty($departureDetail)): ?>
                            <div><?= esc($departureDetail) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- 상품/운임 정보 테이블 -->
                <div style="border: 1px solid #000; margin-bottom: 15px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <thead>
                            <tr style="background: #f0f0f0;">
                                <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">발송일자</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 20%;">상품명</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 10%;">수량</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 10%;">중량(kg)</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">운임구분</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">운임금액</th>
                                <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">비고</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?= esc(date('Y-m-d', strtotime($orderDate))) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?= esc($itemType) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?= esc($quantity) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?= esc($weight) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;">
                                    <?php
                                    $paymentTypeMap = [
                                        '11' => '신용',
                                        '21' => '선불',
                                        '22' => '착불',
                                        '신용' => '신용',
                                        '선불' => '선불',
                                        '착불' => '착불'
                                    ];
                                    echo esc($paymentTypeMap[$paymentType] ?? $paymentType);
                                    ?>
                                </td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: right;">
                                    <?php if (!empty($deliveryFee) && $deliveryFee != '0'): ?>
                                        <?= number_format((int)$deliveryFee) ?>원
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 10px;"><?= esc($deliveryNotes) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- 배송 추적 정보 (itemlist) -->
                <?php if (!empty($itemlist)): ?>
                <div style="border: 1px solid #000; margin-bottom: 15px; padding: 10px;">
                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 3px;">배송 추적 정보</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                        <thead>
                            <tr style="background: #f0f0f0;">
                                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 15%;">날짜</th>
                                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">시간</th>
                                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 25%;">배송지점</th>
                                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 15%;">상태</th>
                                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 20%;">상태설명</th>
                                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 15%;">비고</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $traceItems = [];
                            foreach ($itemlist as $itemGroup) {
                                $items = $itemGroup['item'] ?? [];
                                if (!is_array($items)) {
                                    $items = [$items];
                                }
                                foreach ($items as $item) {
                                    $traceItems[] = $item;
                                }
                            }
                            // 날짜+시간 기준으로 정렬 (최신순)
                            usort($traceItems, function($a, $b) {
                                $dateA = ($a['status_date'] ?? '') . ' ' . ($a['status_time'] ?? '');
                                $dateB = ($b['status_date'] ?? '') . ' ' . ($b['status_time'] ?? '');
                                return strcmp($dateB, $dateA);
                            });
                            foreach ($traceItems as $item): 
                                $statusDate = $item['status_date'] ?? '';
                                $statusTime = $item['status_time'] ?? '';
                                $station = $item['station'] ?? '';
                                $traceCode = $item['tracecode'] ?? '';
                                $traceStatus = $item['tracestatus'] ?? '';
                                $nondlcode = $item['nondlcode'] ?? '';
                                $nondelivreasnnm = $item['nondelivreasnnm'] ?? '';
                            ?>
                            <tr>
                                <td style="border: 1px solid #000; padding: 5px; text-align: center;"><?= esc($statusDate) ?></td>
                                <td style="border: 1px solid #000; padding: 5px; text-align: center;"><?= esc($statusTime) ?></td>
                                <td style="border: 1px solid #000; padding: 5px; text-align: center;"><?= esc($station) ?></td>
                                <td style="border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold;"><?= esc($traceCode) ?></td>
                                <td style="border: 1px solid #000; padding: 5px; text-align: center;"><?= esc($traceStatus) ?></td>
                                <td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 9px;">
                                    <?php if (!empty($nondelivreasnnm)): ?>
                                        <?= esc($nondelivreasnnm) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- 개인정보 보호 안내 -->
                <div style="font-size: 10px; color: #666; margin-bottom: 15px; text-align: center; padding: 8px; background: #f9f9f9; border: 1px dashed #ccc;">
                    ※개인 정보 보호를 위해 운송장은 폐기 바랍니다.
                </div>
                
                <!-- 하단: 일양로지스 및 바코드 -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 2px solid #000; padding-top: 15px;">
                    <div style="font-size: 18px; font-weight: bold;">일양로지스</div>
                    <div style="text-align: center;">
                        <div style="font-size: 10px; margin-bottom: 5px; font-weight: bold;">운송장번호</div>
                        <div style="font-size: 16px; font-weight: bold; letter-spacing: 1px; margin-bottom: 5px;"><?= esc($hawbNo) ?></div>
                        <!-- 바코드 이미지 영역 -->
                        <div style="margin-top: 5px; height: 45px; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; padding: 3px;">
                            <svg id="barcode-right-<?= $index ?>" style="height: 40px; width: 250px;"></svg>
                        </div>
                    </div>
                </div>
                
                <!-- 하단: 받는 분 / 보낸 분 반복 (간략) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; border-top: 1px solid #ccc; padding-top: 10px;">
                    <div>
                        <div style="font-size: 10px; font-weight: bold; margin-bottom: 3px; color: #4a90e2;">받는 분</div>
                        <div style="font-size: 11px;"><?= esc($recevnm ?: $destinationCompany) ?></div>
                        <div style="font-size: 10px;"><?= esc($destinationContact) ?></div>
                        <div style="font-size: 9px;"><?= esc($destinationAddress) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 10px; font-weight: bold; margin-bottom: 3px; color: #4a90e2;">보낸 분</div>
                        <div style="font-size: 11px;"><?= esc($sendnm ?: $departureCompany) ?></div>
                        <div style="font-size: 10px;"><?= esc($departureContact) ?></div>
                        <div style="font-size: 9px;"><?= esc($departureAddress) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- JsBarcode 라이브러리 -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
// 송장출력 페이지에서는 사이드바 관련 이벤트 비활성화
document.addEventListener('DOMContentLoaded', function() {
    // 사이드바 관련 클릭 이벤트 핸들러 비활성화
    if (typeof $ !== 'undefined') {
        // jQuery가 로드된 경우 사이드바 외부 클릭 이벤트 핸들러 제거
        // header.php에서 등록된 이벤트 핸들러를 제거
        $(document).off('click');
        
        // 사이드바가 있다면 숨기기
        $('.sidebar').removeClass('open');
        $('#mobileMenuToggle').removeClass('active');
    }
    
    // 모든 클릭 이벤트에서 이벤트 전파 방지 (버튼 클릭 시)
    document.addEventListener('click', function(e) {
        // 버튼 클릭 시에만 이벤트 전파 방지
        if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
            e.stopPropagation();
            e.stopImmediatePropagation();
        }
    }, true); // 캡처 단계에서 처리
    
    // 바코드 생성
    <?php foreach ($waybillData as $index => $trace): ?>
        <?php 
        $hawbNo = $trace['hawb_no'] ?? $order['shipping_tracking_number'] ?? '';
        if (!empty($hawbNo)): 
        ?>
            // 왼쪽 바코드 생성
            JsBarcode("#barcode-left-<?= $index ?>", "<?= esc($hawbNo, 'js') ?>", {
                format: "CODE128",
                width: 1.5,
                height: 40,
                displayValue: false,
                margin: 0
            });
            
            // 오른쪽 바코드 생성
            JsBarcode("#barcode-right-<?= $index ?>", "<?= esc($hawbNo, 'js') ?>", {
                format: "CODE128",
                width: 1.5,
                height: 40,
                displayValue: false,
                margin: 0
            });
        <?php endif; ?>
    <?php endforeach; ?>
    
    // 팝업 창인 경우 컨텐츠 크기에 맞게 창 크기 조정
    if (window.opener) {
        // 모든 이미지와 바코드가 로드된 후 크기 조정
        window.addEventListener('load', function() {
            setTimeout(function() {
                try {
                    const contentHeight = document.body.scrollHeight;
                    const contentWidth = document.body.scrollWidth;
                    // 컨텐츠 크기 + 여유공간(60px)으로 조정, 화면 크기 제한
                    const adjustedHeight = Math.min(contentHeight + 60, screen.height - 100);
                    const adjustedWidth = Math.min(Math.max(contentWidth + 60, 1000), screen.width - 100);
                    window.resizeTo(adjustedWidth, adjustedHeight);
                } catch (e) {
                    // 크기 조정 실패 시 무시
                }
            }, 100); // 바코드 렌더링 대기
        });
    }
});
</script>

<style>
/* 송장출력 페이지에서 사이드바 완전히 숨기기 */
.sidebar, #mobileMenuToggle {
    display: none !important;
}

@media print {
    .print-container button {
        display: none;
    }
    .waybill-sheet {
        page-break-after: always;
        margin: 0;
        border: 2px solid #000;
    }
    .waybill-sheet:last-child {
        page-break-after: auto;
    }
    /* 인쇄 시 바코드가 제대로 보이도록 */
    svg {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    /* 인쇄 시 배경색 유지 */
    .waybill-sheet > div > div:first-child {
        background: #fffbf0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<?= $this->endSection() ?>

<!-- 송장출력 페이지는 footer 스크립트 제외 -->
</body>
</html>
