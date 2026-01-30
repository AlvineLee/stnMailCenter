<?php
/**
 * 전달사항 섹션 공통 컴포넌트
 * 
 * @var string $fieldName 필드명 (기본값: 'special_instructions')
 * @var string $fieldId 필드 ID (기본값: 'special_instructions')
 * @var string $placeholder placeholder 텍스트 (기본값: '전달하실 내용을 입력하세요.')
 */

// 기본값 설정
$fieldName = $fieldName ?? 'special_instructions';
$fieldId = $fieldId ?? 'special_instructions';
$placeholder = $placeholder ?? '전달하실 내용을 입력하세요.';
?>

<!-- 전달사항 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <div class="flex items-center justify-between mb-2 pb-1 border-b border-gray-300">
            <h2 class="text-sm font-semibold text-gray-700">전달사항</h2>
            <button type="button" id="loadingUnloadingBtn" class="loading-unloading-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                상하차방법 선택
            </button>
        </div>
        <div class="space-y-1">
            <p class="text-xs text-gray-600 font-medium">전달사항을 입력해주세요</p>
            <textarea id="<?= esc($fieldId) ?>" name="<?= esc($fieldName) ?>" placeholder="<?= esc($placeholder) ?>" lang="ko"
                      class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent h-20 resize-none bg-white"><?= old($fieldName) ?></textarea>
        </div>
    </section>
</div>

<?php if (isset($useDeliveryReason) && $useDeliveryReason === 'Y'): ?>
<!-- 배송사유 (필수) -->
<div class="mb-2">
    <section class="bg-yellow-50 rounded-lg shadow-sm border border-yellow-300 p-3">
        <div class="flex items-center justify-between mb-2 pb-1 border-b border-yellow-400">
            <h2 class="text-sm font-semibold text-yellow-800">배송사유 <span class="text-red-500">*</span></h2>
        </div>
        <div class="space-y-2">
            <?php /*if (!empty($deliveryReasons)): ?>
            <div class="flex flex-wrap gap-1 mb-2">
                <?php foreach ($deliveryReasons as $reason): ?>
                <button type="button"
                        class="delivery-reason-btn bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-2 py-1 rounded text-xs font-medium transition-colors border border-yellow-300"
                        data-reason="<?= esc($reason['reason_name']) ?>">
                    <?= esc($reason['reason_name']) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; */?>
            <textarea id="delivery_reason" name="delivery_reason" placeholder="배송사유를 입력하세요. (필수)" lang="ko" required
                      class="w-full px-3 py-2 text-sm border border-yellow-400 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent h-16 resize-none bg-white"><?= old('delivery_reason') ?></textarea>
        </div>
    </section>
</div>
<?php endif; ?>

<style>
/* 상하차방법 버튼은 기본적으로 숨김 */
.loading-unloading-btn {
    display: none !important;
}
/* 다마스, 라보, 트럭일 때만 표시 */
.loading-unloading-btn.show {
    display: block !important;
}
</style>

<script>
// 전달사항 섹션에서 상하차방법 버튼 표시/숨김 제어
(function() {
    function toggleLoadingUnloadingButton() {
        const loadingUnloadingBtn = document.getElementById('loadingUnloadingBtn');
        if (!loadingUnloadingBtn) return;
        
        // 배송수단 선택 확인
        const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
        
        // 다마스, 라보, 트럭일 때만 버튼 표시
        if (selectedVehicle && (selectedVehicle.value === 'damas' || selectedVehicle.value === 'labo' || selectedVehicle.value === 'truck')) {
            loadingUnloadingBtn.classList.add('show');
            loadingUnloadingBtn.style.setProperty('display', 'block', 'important');
        } else {
            // 오토바이, 자전거, 스쿠터 등 다른 배송수단일 때는 숨김
            loadingUnloadingBtn.classList.remove('show');
            loadingUnloadingBtn.style.setProperty('display', 'none', 'important');
        }
    }
    
    // 함수 실행 (여러 시점에서 호출)
    function initButtonToggle() {
        toggleLoadingUnloadingButton();
        
        // 배송수단 변경 시 이벤트 리스너 추가
        document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
            if (!radio.hasAttribute('data-loading-unloading-listener')) {
                radio.addEventListener('change', toggleLoadingUnloadingButton);
                radio.setAttribute('data-loading-unloading-listener', 'true');
            }
        });
    }
    
    // 즉시 실행 (스크립트가 로드되는 시점)
    setTimeout(initButtonToggle, 0);
    
    // DOMContentLoaded 시 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initButtonToggle);
    } else {
        initButtonToggle();
    }
    
    // window.load 시에도 실행 (모든 리소스 로드 완료 후)
    window.addEventListener('load', initButtonToggle);
    
    // MutationObserver로 동적으로 추가되는 배송수단 라디오 버튼도 감지
    const observer = new MutationObserver(function(mutations) {
        initButtonToggle();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // 전역 함수로도 노출 (다른 스크립트에서 호출 가능)
    window.toggleLoadingUnloadingButton = toggleLoadingUnloadingButton;
})();

// 배송사유 버튼 클릭 이벤트
(function() {
    function initDeliveryReasonButtons() {
        const reasonBtns = document.querySelectorAll('.delivery-reason-btn');
        const reasonTextarea = document.getElementById('delivery_reason');

        if (!reasonTextarea || reasonBtns.length === 0) return;

        reasonBtns.forEach(function(btn) {
            if (!btn.hasAttribute('data-reason-listener')) {
                btn.addEventListener('click', function() {
                    const reason = this.getAttribute('data-reason');
                    if (reason) {
                        // 기존 텍스트가 있으면 줄바꿈 후 추가, 없으면 바로 삽입
                        if (reasonTextarea.value.trim()) {
                            reasonTextarea.value += '\n' + reason;
                        } else {
                            reasonTextarea.value = reason;
                        }
                        reasonTextarea.focus();
                    }
                });
                btn.setAttribute('data-reason-listener', 'true');
            }
        });
    }

    // DOMContentLoaded 시 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDeliveryReasonButtons);
    } else {
        initDeliveryReasonButtons();
    }

    // window.load 시에도 실행
    window.addEventListener('load', initDeliveryReasonButtons);
})();
</script>

