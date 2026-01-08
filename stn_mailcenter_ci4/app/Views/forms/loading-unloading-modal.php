<?php
/**
 * 상차방법/하차방법 선택 레이어 팝업
 */
?>

<!-- 상차방법/하차방법 선택 모달 -->
<div id="loadingUnloadingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden m-4 flex flex-col">
        <!-- 모달 헤더 -->
        <div class="flex items-center justify-between p-3 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-700">상차 / 하차 방법 선택</h2>
            <button type="button" id="closeLoadingUnloadingModal" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        
        <!-- 모달 본문 -->
        <div class="p-3 overflow-y-auto flex-1">
            <!-- 다마스/라보: 드롭다운 방식 -->
            <div id="damasLaboMethod" class="space-y-3" style="display: none;">
                <div class="space-y-1">
                    <label for="modal_loading_method" class="block text-sm font-medium text-gray-700">상차방법</label>
                    <select id="modal_loading_method" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                        <option value="">선택하세요</option>
                        <option value="신청안함">신청안함</option>
                        <option value="기사님과 함께">기사님과 함께</option>
                        <option value="기사님 단독">기사님 단독</option>
                    </select>
                </div>
                
                <div class="space-y-1">
                    <label for="modal_unloading_method" class="block text-sm font-medium text-gray-700">하차방법</label>
                    <select id="modal_unloading_method" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                        <option value="">선택하세요</option>
                        <option value="신청안함">신청안함</option>
                        <option value="기사님과 함께">기사님과 함께</option>
                        <option value="기사님 단독">기사님 단독</option>
                    </select>
                </div>
            </div>
            
            <!-- 트럭: 라디오버튼 방식 -->
            <div id="truckMethod" class="space-y-3" style="display: none;">
                <!-- 상차방법 -->
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">상차방법</label>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-1 space-y-0 max-h-64 overflow-y-auto">
                        <label class="flex items-center space-x-2 cursor-pointer p-1 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_loading_method_truck" value="1. 기사 운전만" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">1. 기사 운전만</span>
                                <p class="text-xs text-gray-500 mt-0.5">물품상차는 고객님이 알아서 해주시고, 기사님은 옆에서 코치만 해드립니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_loading_method_truck" value="2. 차위 정리만" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">2. 차위 정리만</span>
                                <p class="text-xs text-gray-500 mt-0.5">고객님이 트럭 위로 물품을 올려주시면 기사님은 트럭 위에서 적재 정리만 합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_loading_method_truck" value="3. 지게차를 통한 상차" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">3. 지게차를 통한 상차</span>
                                <p class="text-xs text-gray-500 mt-0.5">고객님이 지게차로 상차합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_loading_method_truck" value="4. 호이스트이용" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">4. 호이스트이용</span>
                                <p class="text-xs text-gray-500 mt-0.5">고객님이 호이스트로 상차합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_loading_method_truck" value="5. 기사님혼자 1층" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">5. 기사님혼자 1층</span>
                                <p class="text-xs text-gray-500 mt-0.5">물품이 1층 차량 바로 옆에 있어야 하며, 기사님이 혼자 상차합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_loading_method_truck" value="6. 고객님과 기사님이 함께 수작업" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">6. 고객님과 기사님이 함께 수작업</span>
                                <p class="text-xs text-gray-500 mt-0.5">건물 내에서부터 차량까지 함께 상차합니다. 승강기 작업 기준, 계단 시 추가비용.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_loading_method_truck" value="7. 기사님 단독 수작업" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">7. 기사님 단독 수작업</span>
                                <p class="text-xs text-gray-500 mt-0.5">기사님 혼자 상차. 혼자 들 수 있는 물품만 가능. 승강기 기준, 계단 시 추가비용.</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- 하차방법 -->
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">하차방법</label>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-1 space-y-0 max-h-64 overflow-y-auto">
                        <label class="flex items-center space-x-2 cursor-pointer p-1 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_unloading_method_truck" value="1. 기사 운전만" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">1. 기사 운전만</span>
                                <p class="text-xs text-gray-500 mt-0.5">물품하차는 고객님이 알아서 해주시고, 기사님은 옆에서 코치만 해드립니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_unloading_method_truck" value="2. 차위 정리만" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">2. 차위 정리만</span>
                                <p class="text-xs text-gray-500 mt-0.5">고객님이 트럭 위로 물품을 올려주시면 기사님은 트럭 위에서 적재 정리만 합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_unloading_method_truck" value="3. 지게차를 통한 하차" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">3. 지게차를 통한 하차</span>
                                <p class="text-xs text-gray-500 mt-0.5">고객님이 지게차로 하차합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_unloading_method_truck" value="4. 호이스트이용" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">4. 호이스트이용</span>
                                <p class="text-xs text-gray-500 mt-0.5">고객님이 호이스트로 하차합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_unloading_method_truck" value="5. 기사님혼자 1층" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">5. 기사님혼자 1층</span>
                                <p class="text-xs text-gray-500 mt-0.5">물품을 차량 옆 1층에만 기사님 혼자 하차합니다.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_unloading_method_truck" value="6. 고객님과 기사님이 함께 수작업" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">6. 고객님과 기사님이 함께 수작업</span>
                                <p class="text-xs text-gray-500 mt-0.5">차량에서부터 건물까지 함께 하차. 승강기 작업 기준, 계단 시 추가비용.</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-white rounded-md border border-transparent hover:border-gray-300 transition-colors">
                            <input type="radio" name="modal_unloading_method_truck" value="7. 기사님 단독 수작업" class="w-5 h-5 text-gray-600 focus:ring-gray-500 flex-shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-700">7. 기사님 단독 수작업</span>
                                <p class="text-xs text-gray-500 mt-0.5">기사님 혼자 하차. 혼자 들 수 있는 물품만 가능. 승강기 기준, 계단 시 추가비용.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 모달 푸터 -->
        <div class="flex items-center justify-end space-x-2 p-3 border-t border-gray-200">
            <button type="button" id="cancelLoadingUnloadingModal" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md text-sm font-medium transition-colors">
                취소
            </button>
            <button type="button" id="confirmLoadingUnloadingModal" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors">
                확인
            </button>
        </div>
    </div>
</div>

<script>
// 모달 열기
window.openLoadingUnloadingModal = function() {
    const modal = document.getElementById('loadingUnloadingModal');
    const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
    const damasLaboMethod = document.getElementById('damasLaboMethod');
    const truckMethod = document.getElementById('truckMethod');
    
    if (!modal) return;
    
    // 배송수단에 따라 모달 내용 변경
    if (selectedVehicle && selectedVehicle.value === 'truck') {
        if (damasLaboMethod) damasLaboMethod.style.display = 'none';
        if (truckMethod) truckMethod.style.display = 'block';
        
        // 기존 선택값 복원 (트럭: 라디오버튼)
        const existingLoadingInput = document.querySelector('input[name="loading_method_truck"]');
        const existingUnloadingInput = document.querySelector('input[name="unloading_method_truck"]');
        
        // 모든 라디오버튼 해제
        document.querySelectorAll('input[name="modal_loading_method_truck"]').forEach(rb => rb.checked = false);
        document.querySelectorAll('input[name="modal_unloading_method_truck"]').forEach(rb => rb.checked = false);
        
        // 기존 값 복원
        if (existingLoadingInput) {
            const radio = document.querySelector(`input[name="modal_loading_method_truck"][value="${existingLoadingInput.value}"]`);
            if (radio) radio.checked = true;
        }
        
        if (existingUnloadingInput) {
            const radio = document.querySelector(`input[name="modal_unloading_method_truck"][value="${existingUnloadingInput.value}"]`);
            if (radio) radio.checked = true;
        }
    } else if (selectedVehicle && (selectedVehicle.value === 'damas' || selectedVehicle.value === 'labo')) {
        if (damasLaboMethod) damasLaboMethod.style.display = 'block';
        if (truckMethod) truckMethod.style.display = 'none';
        
        // 기존 선택값 복원
        const existingLoading = document.getElementById('loading_method')?.value || '';
        const existingUnloading = document.getElementById('unloading_method')?.value || '';
        const modalLoading = document.getElementById('modal_loading_method');
        const modalUnloading = document.getElementById('modal_unloading_method');
        if (modalLoading) modalLoading.value = existingLoading;
        if (modalUnloading) modalUnloading.value = existingUnloading;
    } else {
        return; // 배송수단이 선택되지 않았으면 모달 열지 않음
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

// 모달 닫기
function closeLoadingUnloadingModal() {
    const modal = document.getElementById('loadingUnloadingModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// 모달 확인 버튼 클릭
function confirmLoadingUnloadingModal() {
    const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
    
    // 전달사항 필드 찾기 (여러 가능한 필드명 시도)
    let deliveryContentField = document.getElementById('special_instructions') || 
                               document.getElementById('delivery_content') || 
                               document.getElementById('deliveryInstructions') ||
                               document.querySelector('textarea[name="special_instructions"]') ||
                               document.querySelector('textarea[name="delivery_content"]') ||
                               document.querySelector('textarea[name="deliveryInstructions"]');
    
    if (!deliveryContentField) {
        // 전달사항 필드를 찾지 못한 경우
        alert('전달사항 필드를 찾을 수 없습니다.');
        return;
    }
    
    let loadingText = '';
    let unloadingText = '';
    
    if (selectedVehicle && selectedVehicle.value === 'truck') {
        // 트럭: 라디오버튼 값 수집
        const loadingRadio = document.querySelector('input[name="modal_loading_method_truck"]:checked');
        const unloadingRadio = document.querySelector('input[name="modal_unloading_method_truck"]:checked');
        
        if (loadingRadio && loadingRadio.value) {
            // 번호 제거 (예: "1. 기사 운전만" -> "기사 운전만")
            loadingText = loadingRadio.value.replace(/^\d+\.\s*/, '');
        }
        if (unloadingRadio && unloadingRadio.value) {
            // 번호 제거 (예: "3. 지게차를 통한 하차" -> "지게차를 통한 하차")
            unloadingText = unloadingRadio.value.replace(/^\d+\.\s*/, '');
        }
    } else if (selectedVehicle && (selectedVehicle.value === 'damas' || selectedVehicle.value === 'labo')) {
        // 다마스/라보: 드롭다운 값 수집
        const modalLoading = document.getElementById('modal_loading_method');
        const modalUnloading = document.getElementById('modal_unloading_method');
        
        if (modalLoading && modalLoading.value) {
            loadingText = modalLoading.value;
        }
        if (modalUnloading && modalUnloading.value) {
            unloadingText = modalUnloading.value;
        }
    }
    
    // 전달사항 필드에 텍스트 추가
    const currentValue = deliveryContentField.value || '';
    const parts = [];
    
    if (loadingText && loadingText !== '선택하세요' && loadingText !== '신청안함') {
        parts.push('상차: ' + loadingText);
    }
    if (unloadingText && unloadingText !== '선택하세요' && unloadingText !== '신청안함') {
        parts.push('하차: ' + unloadingText);
    }
    
    if (parts.length > 0) {
        const newText = parts.join(' / ');
        
        // 기존 값이 있으면 공백 하나 추가 후 추가, 없으면 그냥 추가
        if (currentValue.trim()) {
            deliveryContentField.value = currentValue.trim() + ' ' + newText;
        } else {
            deliveryContentField.value = newText;
        }
    }
    
    closeLoadingUnloadingModal();
}

// 이벤트 리스너
document.addEventListener('DOMContentLoaded', function() {
    const closeBtn = document.getElementById('closeLoadingUnloadingModal');
    const cancelBtn = document.getElementById('cancelLoadingUnloadingModal');
    const confirmBtn = document.getElementById('confirmLoadingUnloadingModal');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeLoadingUnloadingModal);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeLoadingUnloadingModal);
    }
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmLoadingUnloadingModal);
    }
    
    // 모달 배경 클릭 시 닫기
    const modal = document.getElementById('loadingUnloadingModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeLoadingUnloadingModal();
            }
        });
    }
});
</script>

