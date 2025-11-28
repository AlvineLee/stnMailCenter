<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="items-management-container">
    <div class="items-sections-grid">
        <!-- 청구관리 섹션 (왼쪽) -->
        <div class="items-section">
            <div class="section-header">
                <h3 class="section-title">청구관리</h3>
                <div class="select-all-controls">
                    <button type="button" class="select-all-btn" onclick="selectAllBillingItems()">전체선택</button>
                    <button type="button" class="deselect-all-btn" onclick="deselectAllBillingItems()">전체해제</button>
                </div>
            </div>
            <div class="billing-items-list">
                <?php 
                $billingItems = ['No', '일자', '회사명', '부서명', '사용자', '연락처', '출발회사명', '출발동', '도착회사명', '도착동', '행정지역', '오더방법', '기본요금', '추가요금', '대납', '수화물대', '요금합계', '할인요금', '정산금액', '도착연락처', '도착지상세주소', '배차시간', '픽업시간', '완료시간', '배송사유', '적요', '거리(Km)', '출발지상세', '운송수단', '적용구간', '배송기사', '사번', '부서코드', 'ID'];
                foreach ($billingItems as $item): 
                ?>
                <label class="billing-checkbox">
                    <input type="checkbox" name="billing_<?= strtolower(str_replace(['(', ')', ' ', '/'], ['', '', '_', '_'], $item)) ?>" value="1">
                    <span class="item-label"><?= $item ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 요금관리 섹션 (오른쪽) -->
        <div class="items-section">
            <div class="section-header">
                <h3 class="section-title">요금관리</h3>
                <div class="select-all-controls">
                    <button type="button" class="select-all-btn" onclick="selectAllFeeItems()">전체선택</button>
                    <button type="button" class="deselect-all-btn" onclick="deselectAllFeeItems()">전체해제</button>
                </div>
            </div>
            <div class="fee-items-list">
                <?php 
                $feeItems = ['악천후', '심야', '주말', '명절', '고객할증', '경유', '상하차', '야간', '조조', '동승', '초급송', '과적', '엘리베이터없음'];
                foreach ($feeItems as $item): 
                ?>
                <div class="fee-item-row">
                    <label class="fee-checkbox">
                        <input type="checkbox" name="fee_<?= strtolower(str_replace(['(', ')', ' '], ['', '', '_'], $item)) ?>" value="1">
                        <span class="item-label"><?= $item ?></span>
                    </label>
                    <?php if (!in_array($item, ['상하차', '동승', '엘리베이터없음'])): ?>
                    <div class="fee-input-group">
                        <input type="number" name="fee_<?= strtolower(str_replace(['(', ')', ' '], ['', '', '_'], $item)) ?>_amount" placeholder="0" class="fee-input">
                        <span class="currency-suffix">원</span>
                        <select class="fee-dropdown">
                            <option value="">선택</option>
                            <option value="won">원</option>
                            <option value="percent">%</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 저장 버튼 -->
    <div class="items-actions">
        <button type="button" class="save-button" onclick="saveItems()">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            설정 저장
        </button>
    </div>
</div>

<script>
// 요금관리 전체선택
function selectAllFeeItems() {
    document.querySelectorAll('.fee-items-list input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

// 요금관리 전체해제
function deselectAllFeeItems() {
    document.querySelectorAll('.fee-items-list input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// 청구관리 전체선택
function selectAllBillingItems() {
    document.querySelectorAll('.billing-items-list input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

// 청구관리 전체해제
function deselectAllBillingItems() {
    document.querySelectorAll('.billing-items-list input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function saveItems() {
    // 요금관리 항목 수집
    const feeItems = {};
    document.querySelectorAll('.fee-items-list input[type="checkbox"]').forEach(checkbox => {
        if (checkbox.checked) {
            const name = checkbox.name;
            const amountInput = document.querySelector(`input[name="${name}_amount"]`);
            const dropdown = document.querySelector(`select[name="${name}_type"]`);
            
            feeItems[name] = {
                enabled: true,
                amount: amountInput ? amountInput.value : 0,
                type: dropdown ? dropdown.value : 'fixed'
            };
        }
    });

    // 청구관리 항목 수집
    const billingItems = {};
    document.querySelectorAll('.billing-items-list input[type="checkbox"]').forEach(checkbox => {
        if (checkbox.checked) {
            billingItems[checkbox.name] = true;
        }
    });

    // 서버로 전송
    const data = {
        fee_items: feeItems,
        billing_items: billingItems
    };

    // console.log('저장할 데이터:', data);
    
    // 실제 구현에서는 AJAX로 서버에 전송
    alert('설정이 저장되었습니다.');
}
</script>
<?= $this->endSection() ?>
