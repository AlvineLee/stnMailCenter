<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="print-container" style="max-width: 800px; margin: 0 auto; padding: 20px; background: white;">
    <div style="text-align: center; margin-bottom: 20px;">
        <button onclick="event.stopPropagation(); window.print(); return false;" class="form-button form-button-primary" style="margin-bottom: 10px;">인쇄</button>
        <button onclick="event.stopPropagation(); window.close(); return false;" class="form-button form-button-secondary">닫기</button>
    </div>
    
    <?php 
    $waybillData = $waybill_data['body']['trace'] ?? [];
    if (empty($waybillData)) {
        $waybillData = isset($waybill_data['body']['trace']) && is_array($waybill_data['body']['trace']) ? $waybill_data['body']['trace'] : [];
    }
    
    foreach ($waybillData as $index => $trace):
        $hawbNo = $trace['hawb_no'] ?? '';
        $sendnm = $trace['sendnm'] ?? $order['departure_company_name'] ?? '';
        $recevnm = $trace['recevnm'] ?? $order['destination_company_name'] ?? '';
        $orderNo = $trace['order_no'] ?? $order['order_number'] ?? '';
        $itemlist = $trace['itemlist'] ?? [];
    ?>
    <!-- 운송장 출력용지 (왼쪽: 락부, 오른쪽: 본부) -->
    <div class="waybill-sheet" style="border: 1px solid #000; margin-bottom: 20px; page-break-after: always;">
        <div style="display: flex; border-bottom: 1px dashed #000;">
            <!-- 왼쪽: 락부 (작은 부분) -->
            <div style="width: 150px; padding: 10px; border-right: 1px dashed #000; background: #fffbf0;">
                <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">SELSC</div>
                
                <!-- 바코드 영역 -->
                <div style="text-align: center; margin-bottom: 10px;">
                    <div style="font-size: 10px; margin-bottom: 5px;">운송장번호</div>
                    <div style="font-size: 14px; font-weight: bold;"><?= esc($hawbNo) ?></div>
                    <!-- 바코드 이미지 영역 -->
                    <div style="margin-top: 5px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <svg id="barcode-left-<?= $index ?>" style="height: 35px; width: 100%;"></svg>
                    </div>
                </div>
                
                <!-- 상품정보 -->
                <div style="margin-top: 10px; border: 1px solid #ccc; min-height: 100px; padding: 5px;">
                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-size: 10px; margin: 5px 0;">상품정보</div>
                </div>
                
                <!-- 수취주소 -->
                <div style="margin-top: 10px; border: 1px solid #ccc; min-height: 60px; padding: 5px; background: #fffbf0;">
                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-size: 10px; margin: 5px 0;">수취주소</div>
                    <div style="font-size: 9px; margin-top: 5px;"><?= esc($order['destination_address'] ?? '') ?></div>
                </div>
                
                <div style="font-size: 8px; margin-top: 5px; text-align: center;"><?= esc($hawbNo) ?></div>
            </div>
            
            <!-- 오른쪽: 본부 (큰 부분) -->
            <div style="flex: 1; padding: 15px;">
                <!-- 헤더 -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: bold;">운송장: <?= esc($hawbNo) ?></div>
                    </div>
                    <div style="font-size: 12px;">고객상담 1588-0002</div>
                </div>
                
                <!-- 받는 분 / 보낸 분 -->
                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <!-- 받는 분 -->
                    <div style="flex: 1; border: 2px solid #4a90e2; padding: 10px; background: #e8f4f8;">
                        <div style="writing-mode: vertical-rl; text-orientation: upright; font-size: 12px; font-weight: bold; float: left; margin-right: 10px; color: #4a90e2;">받는 분</div>
                        <div>
                            <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;"><?= esc($recevnm) ?></div>
                            <div style="font-size: 12px; margin-bottom: 3px;"><?= esc($order['destination_contact'] ?? '') ?></div>
                            <div style="font-size: 11px;"><?= esc($order['destination_address'] ?? '') ?></div>
                        </div>
                    </div>
                    
                    <!-- 보낸 분 -->
                    <div style="flex: 1; border: 2px solid #4a90e2; padding: 10px; background: #e8f4f8;">
                        <div style="writing-mode: vertical-rl; text-orientation: upright; font-size: 12px; font-weight: bold; float: left; margin-right: 10px; color: #4a90e2;">보낸 분</div>
                        <div>
                            <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;"><?= esc($sendnm) ?></div>
                            <div style="font-size: 12px; margin-bottom: 3px;"><?= esc($order['departure_contact'] ?? '') ?></div>
                            <div style="font-size: 11px;"><?= esc($order['departure_address'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- 개인정보 보호 안내 -->
                <div style="font-size: 10px; color: #666; margin-bottom: 15px; text-align: center;">
                    ※개인 정보 보호를 위해 운송장은 폐기 바랍니다.
                </div>
                
                <!-- 운임/수량/중량/비고 -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;">
                    <div>
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">운임</div>
                        <div style="font-size: 12px;"><?= esc($order['payment_type'] ?? '신용') ?></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">수량</div>
                        <div style="font-size: 12px;"><?= esc($order['quantity'] ?? '1') ?></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">중량</div>
                        <div style="font-size: 12px;"><?= esc($order['item_type'] ?? '1') ?></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">비고</div>
                        <div style="font-size: 12px;"><?= esc($order['notes'] ?? '테스트용') ?></div>
                    </div>
                </div>
                
                <!-- SELSC 및 바코드 -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div style="font-size: 16px; font-weight: bold;">SELSC</div>
                    <div style="text-align: center;">
                        <div style="font-size: 10px; margin-bottom: 5px;">운송장번호</div>
                        <div style="font-size: 14px; font-weight: bold;"><?= esc($hawbNo) ?></div>
                        <!-- 바코드 이미지 영역 -->
                        <div style="margin-top: 5px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <svg id="barcode-right-<?= $index ?>" style="height: 35px; width: 200px;"></svg>
                        </div>
                    </div>
                </div>
                
                <!-- 하단: 받는 분 / 보낸 분 반복 -->
                <div style="display: flex; gap: 20px; border-top: 1px solid #ccc; padding-top: 15px;">
                    <div style="flex: 1;">
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px; color: #4a90e2;">받는 분</div>
                        <div style="font-size: 12px;"><?= esc($recevnm) ?></div>
                        <div style="font-size: 11px;"><?= esc($order['destination_contact'] ?? '') ?></div>
                        <div style="font-size: 10px;"><?= esc($order['destination_address'] ?? '') ?></div>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px; color: #4a90e2;">보낸 분</div>
                        <div style="font-size: 12px;"><?= esc($sendnm) ?></div>
                        <div style="font-size: 11px;"><?= esc($order['departure_contact'] ?? '') ?></div>
                        <div style="font-size: 10px;"><?= esc($order['departure_address'] ?? '') ?></div>
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
        $hawbNo = $trace['hawb_no'] ?? '';
        if (!empty($hawbNo)): 
        ?>
            // 왼쪽 바코드 생성
            JsBarcode("#barcode-left-<?= $index ?>", "<?= esc($hawbNo, 'js') ?>", {
                format: "CODE128",
                width: 1.5,
                height: 35,
                displayValue: false,
                margin: 0
            });
            
            // 오른쪽 바코드 생성
            JsBarcode("#barcode-right-<?= $index ?>", "<?= esc($hawbNo, 'js') ?>", {
                format: "CODE128",
                width: 1.5,
                height: 35,
                displayValue: false,
                margin: 0
            });
        <?php endif; ?>
    <?php endforeach; ?>
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
    }
    .waybill-sheet:last-child {
        page-break-after: auto;
    }
    /* 인쇄 시 바코드가 제대로 보이도록 */
    svg {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<?= $this->endSection() ?>

<!-- 송장출력 페이지는 footer 스크립트 제외 -->
</body>
</html>
