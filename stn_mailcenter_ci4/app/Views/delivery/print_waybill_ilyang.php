<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="print-container" style="margin: 0 auto; padding: 20px; background: white;">
    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;">
        <button id="print-btn" class="form-button form-button-primary">인쇄</button>
        <button id="close-btn" class="form-button form-button-secondary">닫기</button>
    </div>
    
    <?php 
    // 일양 접수 데이터가 있으면 우선 사용, 없으면 주문 데이터 사용
    $ilyangData = $ilyang_order_data ?? null;
    
    // 데이터 추출
    if ($ilyangData) {
        // 일양 접수 데이터 우선 사용
        $hawbNo = $ilyangData['ily_awb_no'] ?? $order['shipping_tracking_number'] ?? '';
        $orderNo = $ilyangData['ily_cus_ordno'] ?? $order['order_number'] ?? '';
        
        // 발송인 정보
        $sndName = $ilyangData['ily_snd_name'] ?? $order['departure_company_name'] ?? '';
        $sndManName = $ilyangData['ily_snd_man_name'] ?? $order['departure_manager'] ?? '';
        $sndTel1 = $ilyangData['ily_snd_tel1'] ?? $order['departure_contact'] ?? '';
        $sndTel2 = $ilyangData['ily_snd_tel2'] ?? '';
        $sndZip = $ilyangData['ily_snd_zip'] ?? '';
        $sndAddr = $ilyangData['ily_snd_addr'] ?? $order['departure_address'] ?? '';
        $sndCenter = $ilyangData['ily_snd_center'] ?? '';
        
        // 수취인 정보
        $rcvName = $ilyangData['ily_rcv_name'] ?? $order['destination_company_name'] ?? '';
        $rcvManName = $ilyangData['ily_rcv_man_name'] ?? $order['destination_manager'] ?? '';
        $rcvTel1 = $ilyangData['ily_rcv_tel1'] ?? $order['destination_contact'] ?? '';
        $rcvTel2 = $ilyangData['ily_rcv_tel2'] ?? '';
        $rcvZip = $ilyangData['ily_rcv_zip'] ?? '';
        $rcvAddr = $ilyangData['ily_rcv_addr'] ?? $order['destination_address'] ?? '';
        $rcvCenter = $ilyangData['ily_rcv_center'] ?? '';
        
        // 상품 정보
        $godName = $ilyangData['ily_god_name'] ?? $order['item_type'] ?? '일반상품';
        $godPrice = $ilyangData['ily_god_price'] ?? $order['item_price'] ?? $order['total_amount'] ?? '0';
        $boxQty = $ilyangData['ily_box_qty'] ?? $order['quantity'] ?? '1';
        $boxWgt = $ilyangData['ily_box_wgt'] ?? $order['weight'] ?? '1';
        $payType = $ilyangData['ily_pay_type'] ?? $order['payment_type'] ?? '11';
        $amtCash = $ilyangData['ily_amt_cash'] ?? $order['delivery_cost'] ?? $order['cash_fare'] ?? '0';
        $dlvRmks = $ilyangData['ily_dlv_rmks'] ?? $order['delivery_content'] ?? $order['notes'] ?? '';
        $dlvMesg = $ilyangData['ily_dlv_mesg'] ?? '';
        $shpDate = $ilyangData['ily_shp_date'] ?? $order['order_date'] ?? date('Y-m-d');
        $cusAcno = $ilyangData['ily_cus_acno'] ?? '';
        
        // 발송일자 포맷팅 (YYYY-MM-DD -> YYYY-MM-DD)
        if (strlen($shpDate) == 8) {
            $shpDate = substr($shpDate, 0, 4) . '-' . substr($shpDate, 4, 2) . '-' . substr($shpDate, 6, 2);
        }
    } else {
        // 일양 접수 데이터가 없으면 주문 데이터 사용
        $hawbNo = $order['shipping_tracking_number'] ?? '';
        $orderNo = $order['order_number'] ?? '';
        
        $sndName = $order['departure_company_name'] ?? '';
        $sndManName = $order['departure_manager'] ?? '';
        $sndTel1 = $order['departure_contact'] ?? '';
        $sndTel2 = '';
        $sndZip = preg_match('/(\d{5,6})/', $order['departure_address'] ?? '', $matches) ? $matches[1] : '';
        $sndAddr = $order['departure_address'] ?? '';
        $sndCenter = '';
        
        $rcvName = $order['destination_company_name'] ?? '';
        $rcvManName = $order['destination_manager'] ?? '';
        $rcvTel1 = $order['destination_contact'] ?? '';
        $rcvTel2 = '';
        $rcvZip = preg_match('/(\d{5,6})/', $order['destination_address'] ?? '', $matches) ? $matches[1] : '';
        $rcvAddr = $order['destination_address'] ?? '';
        $rcvCenter = '';
        
        $godName = $order['item_type'] ?? '일반상품';
        $godPrice = $order['item_price'] ?? $order['total_amount'] ?? '0';
        $boxQty = $order['quantity'] ?? '1';
        $boxWgt = $order['weight'] ?? '1';
        $payType = $order['payment_type'] ?? '11';
        $amtCash = $order['delivery_cost'] ?? $order['cash_fare'] ?? '0';
        $dlvRmks = $order['delivery_content'] ?? $order['notes'] ?? '';
        $dlvMesg = '';
        $shpDate = $order['order_date'] ?? date('Y-m-d');
        $cusAcno = '';
    }
    
    // 결제 타입 매핑
    $paymentTypeMap = [
        '11' => '신용',
        '21' => '선불',
        '22' => '착불',
        'credit_transaction' => '신용',
        'cash_in_advance' => '선불',
        'cash_on_delivery' => '착불',
        '신용' => '신용',
        '선불' => '선불',
        '착불' => '착불'
    ];
    $payTypeLabel = $paymentTypeMap[$payType] ?? '신용';
    
    // 주소 확대 (구+동명/도로명) - 수취인 주소에서 추출
    $rcvAddrExpanded = $rcvAddr;
    if (preg_match('/([가-힣]+구)\s*([가-힣]+동)/', $rcvAddr, $matches)) {
        $rcvAddrExpanded = $matches[1] . ' ' . $matches[2];
    }
    
    // 배달지점명 (수취인 센터코드에서 추출 또는 기본값)
    $deliveryBranchName = $rcvCenter ?: 'N-2-동부산A'; // 기본값
    
    // 동일배송지 BOX수 (기본값: 1/1)
    $sameDeliveryBoxCount = '1/1';
    
    // 배송직원코드 (기본값: 없음)
    $deliveryPersonCode = '';
    ?>
    
    <!-- 일양 운송장 출력용지 (3개 스티커 구조) -->
    <div class="ilyang-waybill-sheet" style="position: relative; width: 800px; margin: 0 auto 20px; background: white; border: 1px solid #ddd;">
        <div style="display: flex; width: 800px; height: 423px;">
            
            <!-- 왼쪽 스티커 (1개) - 빨간색 테두리 -->
            <div class="sticker-left" style="position: relative; width: 280px; height: 423px; border: 2px solid #e74c3c; padding: 8px; box-sizing: border-box; background: white;">
                <!-- ⑩ 배달지점명 (상단, 큰 폰트) -->
                <div style="position: absolute; top: 8px; left: 8px; width: 264px;">
                    <div style="font-family: '휴먼둥근해드라인', 'Human Round Head Line', sans-serif; font-size: 33px; font-weight: bold; color: #000; line-height: 1.2;">
                        <?= esc($deliveryBranchName) ?>
                    </div>
                </div>
                
                <!-- ① 바코드 + 운송장번호 (상단 중앙) -->
                <div style="position: absolute; top: 55px; left: 8px; width: 264px; text-align: center;">
                    <div style="height: 75px; display: flex; align-items: center; justify-content: center; margin-bottom: 0px;">
                        <svg id="barcode-left-1" style="height: 70px; width: 100%;"></svg>
                    </div>
                    <div style="font-family: 'Gulim', sans-serif; font-size: 11px; font-weight: bold; letter-spacing: 0.5px; color: #000; margin-top: -3px;">
                        <?= esc($hawbNo) ?>
                    </div>
                </div>
                
                <!-- ⑤ 상품정보 + ⑨ 수취주소 (라운딩 박스로 감싸기) -->
                <div style="position: absolute; top: 140px; left: 8px; width: 264px; border: 1px solid #3498db; border-radius: 8px; overflow: hidden; background: white;">
                    <!-- 상품정보 (상단, 흰색 배경) -->
                    <div style="display: flex; align-items: stretch; background: white; border-bottom: 1px solid #3498db;">
                        <div style="width: 28px; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; border-right: 1px solid #3498db; padding: 8px 8px 8px 0;">
                            <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 12px; color: #3498db; letter-spacing: 0.5px; font-weight: bold;">상품정보</div>
                        </div>
                        <div style="flex: 1; padding: 8px; font-family: 'Gulim', sans-serif; font-size: 11px; color: #000; line-height: 1.4;">
                            <?= esc($godName) ?>
                        </div>
                    </div>
                    
                    <!-- 수취주소 (하단, 노란색 배경) -->
                    <div style="display: flex; align-items: stretch; background: #fffacd;">
                        <div style="width: 28px; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; border-right: 1px solid #3498db; padding: 8px 8px 8px 0; background: #3498db;">
                            <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 12px; color: white; letter-spacing: 0.5px; font-weight: bold;">수취주소</div>
                        </div>
                        <div style="flex: 1; padding: 8px;">
                            <div style="font-family: '휴먼둥근해드라인', 'Human Round Head Line', sans-serif; font-size: 20px; font-weight: bold; color: #000; line-height: 1.3; margin-bottom: 4px;">
                                <?= esc($rcvAddrExpanded) ?>
                            </div>
                            <div style="font-family: 'Gulim', sans-serif; font-size: 12px; color: #333; line-height: 1.2; font-weight: bold;">
                                <?php if (!empty($rcvZip)): ?>
                                <div>[<?= esc($rcvZip) ?>]</div>
                                <?php endif; ?>
                                <div><?= esc($rcvAddr) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ⑧ 고객사 주문번호 (최하단) -->
                <div style="position: absolute; bottom: 8px; left: 8px; width: 264px;">
                    <div style="font-family: '휴먼둥근해드라인', 'Human Round Head Line', sans-serif; font-size: 12px; font-weight: bold; color: #000;">
                        <?= esc($orderNo) ?>
                    </div>
                </div>
            </div>
            
            <!-- 오른쪽 영역 (2개 스티커) -->
            <div style="flex: 1; display: flex; flex-direction: column; width: 520px;">
                
                <!-- 오른쪽 상단 스티커 (1개) - 보낸분/받는분 간단 정보 -->
                <div class="sticker-right-top" style="position: relative; width: 100%; height: 135px; border: 2px solid #3498db; border-left: none; border-top: 2px solid #e74c3c; padding: 4px; box-sizing: border-box; background: white;">
                    <!-- 헤더 -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; padding-bottom: 2px; border-bottom: 1px solid #3498db; height: 18px;">
                        <div style="display: flex; align-items: center;">
                            <img src="<?= base_url('assets/images/bill/ilyang_ci.png') ?>" alt="일양택배" style="height: 21px; max-width: 120px; object-fit: contain;">
                        </div>
                        <div style="font-family: 'Gulim', sans-serif; font-size: 14.4px; color: #333;">운송장: <span style="font-weight: bold; color: #000;"><?= esc($hawbNo) ?></span></div>
                        <div style="font-family: 'Gulim', sans-serif; font-size: 9px; color: #2980b9; font-weight: bold;">고객상담 1588-0002</div>
                    </div>
                    
                    <!-- 받는분/보낸분 라운딩 박스 -->
                    <div style="border: 1px solid #3498db; border-radius: 8px; overflow: hidden; background: white; margin-top: 4px;">
                        <!-- ② 받는분 (상단, 노란색 배경) -->
                        <div style="display: flex; align-items: stretch; background: #fffacd; border-bottom: 1px solid #3498db;">
                            <div style="width: 28px; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; border-right: 1px solid #3498db; padding: 6px 8px 6px 0; background: #3498db;">
                                <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 12px; color: white; letter-spacing: 0.5px; font-weight: bold;">받는분</div>
                            </div>
                            <div style="flex: 1; padding: 6px; overflow: hidden; display: flex; flex-direction: column; justify-content: center;">
                                <div style="display: flex; align-items: center; margin-bottom: 1px; flex-wrap: wrap; line-height: 1.0;">
                                    <div style="font-family: '휴먼둥근해드라인', 'Human Round Head Line', sans-serif; font-size: 16px; font-weight: bold; color: #000; margin-right: 5px;">
                                        <?= esc($rcvName) ?>
                                    </div>
                                    <?php if (!empty($rcvManName)): ?>
                                    <div style="font-family: 'Gulim', sans-serif; font-size: 14px; color: #333; margin-right: 5px; font-weight: bold;">
                                        <?= esc($rcvManName) ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($rcvTel1)): ?>
                                    <div style="font-family: 'Gulim', sans-serif; font-size: 14px; color: #333; font-weight: bold;">
                                        <?= esc($rcvTel1) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div style="font-family: 'Gulim', sans-serif; font-size: 14px; color: #333; line-height: 1.0; margin-top: 1px; font-weight: bold;">
                                    <?= esc($rcvAddr) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ③ 보낸분 (하단, 흰색 배경) -->
                        <div style="display: flex; align-items: stretch; background: white;">
                            <div style="width: 28px; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; border-right: 1px solid #3498db; padding: 6px 8px 6px 0;">
                                <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 12px; color: #3498db; letter-spacing: 0.5px; font-weight: bold;">보낸분</div>
                            </div>
                            <div style="flex: 1; padding: 6px; overflow: hidden; display: flex; flex-direction: column; justify-content: center;">
                                <div style="display: flex; align-items: center; margin-bottom: 1px; flex-wrap: wrap; line-height: 1.0;">
                                    <div style="font-family: '휴먼둥근해드라인', 'Human Round Head Line', sans-serif; font-size: 12px; font-weight: bold; color: #000; margin-right: 5px;">
                                        <?= esc($sndName) ?>
                                    </div>
                                    <?php if (!empty($sndTel1)): ?>
                                    <div style="font-family: 'Gulim', sans-serif; font-size: 10px; color: #333; font-weight: bold;">
                                        <?= esc($sndTel1) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <!-- 개인정보 보호 안내 -->
                                <div style="text-align: right; font-family: 'Gulim', sans-serif; font-size: 9px; color: #3498db; margin-top: 4px; padding: 2px 0;">
                                    ※개인 정보 보호를 위해 운송장은 폐기 바랍니다.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 오른쪽 하단 스티커 (1개) - 운임/수량/중량/비고 + 바코드 + 보낸분/받는분 상세 -->
                <div class="sticker-right-bottom" style="position: relative; width: 100%; border: 2px solid #3498db; border-left: none; border-top: 2px solid #e74c3c; padding: 8px; box-sizing: border-box; background: white; flex: 1; display: flex; flex-direction: column;">
                    <!-- 상단: 운임/수량/중량/비고 + 바코드 영역 -->
                    <div style="display: flex; gap: 8px;">
                        <!-- 왼쪽: 운임/수량/중량/비고 -->
                        <div style="flex: 0.5; padding-right: 8px;">
                            <!-- ⑫ 운임 -->
                            <div style="display: flex; margin-bottom: 3px; height: 35px; align-items: center;">
                                <div style="width: 24px; height: 35px; border: 1px solid #3498db; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; background: white;">
                                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 11px; color: #3498db; letter-spacing: 0.5px; font-weight: bold;">운임</div>
                                </div>
                                <div style="flex: 1; border: 1px solid #3498db; padding: 2px 4px; font-family: 'Gulim', sans-serif; font-size: 12px; color: #000; font-weight: bold; height: 35px; display: flex; align-items: center;">
                                    <?= esc($payTypeLabel) ?> <?= number_format((int)$amtCash) ?>원
                                </div>
                            </div>
                            
                            <!-- ⑫ 수량 -->
                            <div style="display: flex; margin-bottom: 3px; height: 35px; align-items: center;">
                                <div style="width: 24px; height: 35px; border: 1px solid #3498db; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; background: white;">
                                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 11px; color: #3498db; letter-spacing: 0.5px; font-weight: bold;">수량</div>
                                </div>
                                <div style="flex: 1;">
                                    <div style="border: 1px solid #3498db; padding: 2px 4px; font-family: 'Gulim', sans-serif; font-size: 12px; color: #000; text-align: center; font-weight: bold; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <?= esc($boxQty) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ⑫ 중량 -->
                            <div style="display: flex; margin-bottom: 3px; height: 35px; align-items: center;">
                                <div style="width: 24px; height: 35px; border: 1px solid #3498db; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; background: white;">
                                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 11px; color: #3498db; letter-spacing: 0.5px; font-weight: bold;">중량</div>
                                </div>
                                <div style="flex: 1;">
                                    <div style="border: 1px solid #3498db; padding: 2px 4px; font-family: 'Gulim', sans-serif; font-size: 12px; color: #000; text-align: center; font-weight: bold; height: 35px; display: flex; align-items: center; justify-content: center;">
                                        <?= esc($boxWgt) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ⑬ 비고 -->
                            <div style="display: flex; height: 35px; align-items: center;">
                                <div style="width: 24px; height: 35px; border: 1px solid #3498db; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; background: white;">
                                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 11px; color: #3498db; letter-spacing: 0.5px; font-weight: bold;">비고</div>
                                </div>
                                <div style="flex: 1; padding: 2px 4px; font-family: 'Gulim', sans-serif; font-size: 12px; color: #000; line-height: 1.2; font-weight: bold;">
                                    <?= esc($dlvRmks) ?>
                                    <?php if (!empty($dlvMesg)): ?>
                                        <?= esc($dlvMesg) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 오른쪽: 운임 데이터 + 바코드 -->
                        <div style="width: 280px; padding-left: 4px; display: flex; flex-direction: column;">
                            <!-- 바코드 영역 -->
                            <div style="display: flex; align-items: flex-start; gap: 4px;">
                                <!-- GGGNS (바코드 왼쪽) -->
                                <div style="font-family: 'Gulim', sans-serif; font-size: 11px; color: #333; font-weight: bold; padding-top: 2px; flex-shrink: 0;">
                                    <?= esc($sndCenter ?: '마포지점(NS00)') ?>
                                </div>
                                
                                <!-- 바코드 -->
                                <div style="flex: 1; text-align: center; min-width: 0;">
                                    <!-- 운임 데이터 (바코드 위, 한 줄) -->
                                    <div style="font-family: 'Gulim', sans-serif; font-size: 11px; color: #333; line-height: 1.3; margin-bottom: 6px; text-align: center; font-weight: bold; white-space: nowrap;">
                                        <?= esc($shpDate) ?>
                                        <?php if (!empty($cusAcno)): ?>
                                        <?= esc($cusAcno) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($deliveryPersonCode)): ?>
                                        <?= esc($deliveryPersonCode) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($sameDeliveryBoxCount)): ?>
                                        /<?= esc($sameDeliveryBoxCount) ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- ① 바코드 -->
                                    <div style="height: 75px; display: flex; align-items: center; justify-content: center; margin-bottom: 2px; overflow: visible;">
                                        <svg id="barcode-right-1" style="height: 70px; width: auto; min-width: 100%;"></svg>
                                    </div>
                                    <div style="font-family: 'Gulim', sans-serif; font-size: 13.2px; font-weight: bold; letter-spacing: 0.5px; color: #000; margin-top: 4px;">
                                        <?= esc($hawbNo) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 하단: 받는분/보낸분 (전체 너비) -->
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd;">
                        <!-- 받는분/보낸분 라운딩 박스 -->
                        <div style="border: 1px solid #3498db; border-radius: 8px; overflow: hidden; background: white;">
                            <!-- 받는분 (상단, 노란색 배경) -->
                            <div style="display: flex; align-items: stretch; background: #fffacd; border-bottom: 1px solid #3498db;">
                                <div style="width: 28px; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; border-right: 1px solid #3498db; padding: 6px 8px 6px 0; background: #3498db;">
                                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 12px; color: white; letter-spacing: 0.5px; font-weight: bold;">받는분</div>
                                </div>
                                <div style="flex: 1; padding: 6px; overflow: hidden; display: flex; flex-direction: column; justify-content: center;">
                                    <div style="display: flex; align-items: center; margin-bottom: 1px; flex-wrap: wrap; line-height: 1.0;">
                                        <div style="font-family: '휴먼둥근해드라인', 'Human Round Head Line', sans-serif; font-size: 16px; font-weight: bold; color: #000; margin-right: 5px;">
                                            <?= esc($rcvName) ?>
                                        </div>
                                        <?php if (!empty($rcvManName)): ?>
                                        <div style="font-family: 'Gulim', sans-serif; font-size: 14px; color: #333; margin-right: 5px; font-weight: bold;">
                                            <?= esc($rcvManName) ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($rcvTel1)): ?>
                                        <div style="font-family: 'Gulim', sans-serif; font-size: 14px; color: #333; font-weight: bold;">
                                            <?= esc($rcvTel1) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-family: 'Gulim', sans-serif; font-size: 14px; color: #333; line-height: 1.0; margin-top: 1px; font-weight: bold;">
                                        <?= esc($rcvAddr) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 보낸분 (하단, 흰색 배경) -->
                            <div style="display: flex; align-items: stretch; background: white;">
                                <div style="width: 28px; display: flex; align-items: center; justify-content: center; margin-right: 8px; flex-shrink: 0; border-right: 1px solid #3498db; padding: 6px 8px 6px 0;">
                                    <div style="writing-mode: vertical-rl; text-orientation: upright; font-family: 'Gulim', sans-serif; font-size: 12px; color: #3498db; letter-spacing: 0.5px; font-weight: bold;">보낸분</div>
                                </div>
                                <div style="flex: 1; padding: 6px; overflow: hidden; display: flex; flex-direction: column; justify-content: center;">
                                    <div style="display: flex; align-items: center; margin-bottom: 1px; flex-wrap: wrap; line-height: 1.0;">
                                        <div style="font-family: '휴먼둥근해드라인', 'Human Round Head Line', sans-serif; font-size: 12px; font-weight: bold; color: #000; margin-right: 5px;">
                                            <?= esc($sndName) ?>
                                        </div>
                                        <?php if (!empty($sndManName)): ?>
                                        <div style="font-family: 'Gulim', sans-serif; font-size: 10px; color: #333; margin-right: 5px; font-weight: bold;">
                                            <?= esc($sndManName) ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($sndTel1)): ?>
                                        <div style="font-family: 'Gulim', sans-serif; font-size: 10px; color: #333; font-weight: bold;">
                                            <?= esc($sndTel1) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-family: 'Gulim', sans-serif; font-size: 10px; color: #333; line-height: 1.0; margin-top: 1px; font-weight: bold;">
                                        <?= esc($sndAddr) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JsBarcode 라이브러리 -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
// 송장출력 페이지에서는 사이드바 관련 이벤트 비활성화
document.addEventListener('DOMContentLoaded', function() {
    // 사이드바 관련 클릭 이벤트 핸들러 비활성화
    if (typeof $ !== 'undefined') {
        $(document).off('click');
        $('.sidebar').removeClass('open');
        $('#mobileMenuToggle').removeClass('active');
    }
    
    // 인쇄 버튼 이벤트 (capture 단계에서 먼저 처리)
    const printBtn = document.getElementById('print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            window.print();
            return false;
        }, true); // capture 단계에서 처리
        
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.print();
            return false;
        }, false); // bubble 단계에서도 처리
    }
    
    // 닫기 버튼 이벤트 (capture 단계에서 먼저 처리)
    const closeBtn = document.getElementById('close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            if (window.opener) {
                window.close();
            } else {
                // 팝업이 아닌 경우 뒤로 가기 또는 메인 페이지로 이동
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = '/';
                }
            }
            return false;
        }, true); // capture 단계에서 처리
        
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (window.opener) {
                window.close();
            } else {
                // 팝업이 아닌 경우 뒤로 가기 또는 메인 페이지로 이동
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = '/';
                }
            }
            return false;
        }, false); // bubble 단계에서도 처리
    }
    
    // 모든 클릭 이벤트에서 이벤트 전파 방지 (버튼 클릭 시 제외)
    document.addEventListener('click', function(e) {
        const target = e.target;
        const isButton = target.tagName === 'BUTTON' || target.closest('button');
        const isPrintBtn = target.id === 'print-btn' || target.closest('#print-btn');
        const isCloseBtn = target.id === 'close-btn' || target.closest('#close-btn');
        
        // 인쇄/닫기 버튼은 제외
        if (isPrintBtn || isCloseBtn) {
            return;
        }
        
        if (isButton) {
            e.stopPropagation();
            e.stopImmediatePropagation();
        }
    }, true);
    
    // 바코드 생성 (CODE39 형식, 밀도 15 MIL 이상)
    <?php if (!empty($hawbNo)): ?>
        // 왼쪽 바코드
        JsBarcode("#barcode-left-1", "<?= esc($hawbNo, 'js') ?>", {
            format: "CODE39",
            width: 2,  // 밀도 15 MIL 이상을 위한 width 조정
            height: 70,
            displayValue: false,
            margin: 0
        });
        
        // 오른쪽 바코드
        JsBarcode("#barcode-right-1", "<?= esc($hawbNo, 'js') ?>", {
            format: "CODE39",
            width: 2,  // 왼쪽 바코드와 동일
            height: 70,  // 높이 70으로 복원
            displayValue: false,
            margin: 0
        });
    <?php endif; ?>
    
    // 팝업 창인 경우 크기를 800px로 고정
    if (window.opener) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                try {
                    // 송장 높이 423px + 버튼/여백을 고려하여 높이 설정
                    const adjustedHeight = Math.min(600, screen.height - 50);
                    window.resizeTo(880, adjustedHeight); // 너비 880px (800px + 여백 80px)
                } catch (e) {
                    // 크기 조정 실패 시 기본 크기로 설정
                    try {
                        window.resizeTo(880, 600);
                    } catch (e2) {
                        // 크기 조정 실패 시 무시
                    }
                }
            }, 200); // 바코드 렌더링 대기 시간 증가
        });
    }
});
</script>

<style>
/* 송장출력 페이지에서 사이드바 완전히 숨기기 */
.sidebar, #mobileMenuToggle {
    display: none !important;
}

/* 휴먼둥근해드라인 폰트 로드 (웹 폰트 또는 시스템 폰트) */
@font-face {
    font-family: '휴먼둥근해드라인';
    src: local('휴먼둥근해드라인'), local('Human Round Head Line');
    font-weight: normal;
    font-style: normal;
}

/* 일양 운송장 시트 스타일 */
.ilyang-waybill-sheet {
    background: white;
}

/* 스티커 구분선 (빨간색 점선) */
.sticker-left::after {
    content: '';
    position: absolute;
    right: -2px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: repeating-linear-gradient(
        to bottom,
        #e74c3c 0px,
        #e74c3c 5px,
        transparent 5px,
        transparent 10px
    );
}

.sticker-right-top::before {
    content: '';
    position: absolute;
    top: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: repeating-linear-gradient(
        to right,
        #e74c3c 0px,
        #e74c3c 5px,
        transparent 5px,
        transparent 10px
    );
}

.sticker-right-top::after {
    content: '';
    position: absolute;
    right: -2px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: repeating-linear-gradient(
        to bottom,
        #e74c3c 0px,
        #e74c3c 5px,
        transparent 5px,
        transparent 10px
    );
}

.sticker-right-bottom::before {
    content: '';
    position: absolute;
    top: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: repeating-linear-gradient(
        to right,
        #e74c3c 0px,
        #e74c3c 5px,
        transparent 5px,
        transparent 10px
    );
}

.sticker-right-bottom::after {
    content: '';
    position: absolute;
    right: -2px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: repeating-linear-gradient(
        to bottom,
        #e74c3c 0px,
        #e74c3c 5px,
        transparent 5px,
        transparent 10px
    );
}

@media print {
    @page {
        margin: 0 !important;
        size: A4 portrait;
    }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    /* 레이아웃 요소 숨기기 */
    .sidebar,
    #mobileMenuToggle,
    .main-content > div:first-child:not(.print-container),
    header,
    footer,
    nav {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .print-container {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        transform: scale(0.96);
        transform-origin: top center;
    }
    .print-container > div:first-child {
        display: none !important;
    }
    .print-container button {
        display: none !important;
    }
    body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .ilyang-waybill-sheet {
        page-break-after: auto !important;
        page-break-before: auto !important;
        margin: 0 auto !important;
        width: 800px !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .ilyang-waybill-sheet > div {
        width: 800px !important;
        max-width: 100% !important;
        height: 423px !important;
        box-sizing: border-box !important;
    }
    /* 인쇄 시 바코드가 제대로 보이도록 */
    svg {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    /* 인쇄 시 배경색 유지 */
    .sticker-left {
        border-color: #e74c3c !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .sticker-right-top, .sticker-right-bottom {
        border-color: #3498db !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<?= $this->endSection() ?>

<!-- 송장출력 페이지는 footer 스크립트 제외 -->
</body>
</html>
