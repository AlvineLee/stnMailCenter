<?php
// Validation 에러 메시지 레이어팝업 컴포넌트
// 사용법: $this->include('forms/validation-error-modal')

$validationErrors = session()->getFlashdata('errors') ?? [];
$generalError = session()->getFlashdata('error') ?? null;
?>

<!-- Validation 에러 레이어팝업 -->
<div id="validationErrorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
        <!-- 헤더 -->
        <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-red-50 rounded-t-lg">
            <div class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-red-800">입력 오류</h3>
            </div>
            <button type="button" id="closeValidationErrorModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- 본문 -->
        <div class="p-6">
            <?php if ($generalError): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-800"><?= esc($generalError) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($validationErrors)): ?>
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">다음 항목을 확인해주세요:</p>
                    <ul class="space-y-2">
                        <?php foreach ($validationErrors as $field => $error): ?>
                            <li class="flex items-start space-x-2">
                                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-gray-800">
                                        <?php
                                        // 필드명을 한글로 변환
                                        $fieldNames = [
                                            'company_name' => '회사명',
                                            'contact' => '연락처',
                                            'departure_contact' => '출발지 연락처',
                                            'departure_manager' => '출발지 담당',
                                            'departure_address' => '출발지 주소',
                                            'departure_detail' => '출발지 상세주소',
                                            'destination_contact' => '도착지 연락처',
                                            'destination_manager' => '도착지 담당',
                                            'destination_address' => '도착지 주소',
                                            'destination_detail' => '도착지 상세주소',
                                            'item_type' => '물품종류',
                                            'delivery_method' => '배송수단',
                                            'urgency_level' => '긴급도',
                                            'delivery_route' => '배송경로',
                                            'waypoint_address' => '경유지 주소',
                                            'payment_type' => '지급구분'
                                        ];
                                        echo $fieldNames[$field] ?? $field;
                                        ?>
                                    </span>
                                    <p class="text-sm text-red-600 mt-1"><?= esc($error) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (empty($validationErrors) && !$generalError): ?>
                <div class="text-center py-4">
                    <p class="text-sm text-gray-600">에러 메시지가 없습니다.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 푸터 -->
        <div class="flex justify-end p-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" id="confirmValidationError" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded text-sm font-medium transition-colors">
                확인
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('validationErrorModal');
    const closeBtn = document.getElementById('closeValidationErrorModal');
    const confirmBtn = document.getElementById('confirmValidationError');
    
    // 에러가 있으면 모달 표시
    <?php if (!empty($validationErrors) || $generalError): ?>
    if (modal) {
        // 사이드바 숨기기
        if (typeof window.hideSidebarForModal === 'function') {
            window.hideSidebarForModal();
        }
        
        // 사이드바 z-index 낮추기
        if (typeof window.lowerSidebarZIndex === 'function') {
            window.lowerSidebarZIndex();
        }
        
        // 모달 표시
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        
        // 에러가 있는 첫 번째 필드로 포커스 이동
        <?php if (!empty($validationErrors)): ?>
        const firstErrorField = Object.keys(<?= json_encode($validationErrors) ?>)[0];
        if (firstErrorField) {
            const fieldElement = document.getElementById(firstErrorField);
            if (fieldElement) {
                setTimeout(function() {
                    fieldElement.focus();
                    fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // 시각적 피드백 (빨간 테두리)
                    fieldElement.classList.add('border-red-500', 'ring-2', 'ring-red-300');
                    setTimeout(function() {
                        fieldElement.classList.remove('border-red-500', 'ring-2', 'ring-red-300');
                    }, 3000);
                }, 300);
            }
        }
        <?php endif; ?>
    }
    <?php endif; ?>
    
    // 모달 닫기 함수
    function closeModal() {
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            
            // 사이드바 z-index 복원
            if (typeof window.restoreSidebarZIndex === 'function') {
                window.restoreSidebarZIndex();
            }
        }
    }
    
    // 닫기 버튼 클릭
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // 확인 버튼 클릭
    if (confirmBtn) {
        confirmBtn.addEventListener('click', closeModal);
    }
    
    // 모달 배경 클릭 시 닫기
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // ESC 키로 닫기
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
</script>

