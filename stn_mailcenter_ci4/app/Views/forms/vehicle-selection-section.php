<?php
/**
 * 배송수단 선택 섹션 공통 컴포넌트
 * 다마스, 라보, 트럭 선택 및 트럭 상세 옵션 포함
 */

$defaultVehicle = $defaultVehicle ?? 'damas';
$truckCapacityStyle = $truckCapacityStyle ?? 'select'; // 'select' or 'radio'
?>

<!-- 배송수단 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">배송수단</h2>
        <div class="space-y-3">
            <!-- 배송수단 이미지 선택 -->
            <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-600">배송수단 선택 *</label>
                <div class="flex gap-2">
                    <label class="vehicle-option cursor-pointer">
                        <input type="radio" name="delivery_method" value="damas" <?= old('delivery_method', $defaultVehicle) === 'damas' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                        <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                            <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                <img src="<?= base_url('assets/icons/201.png') ?>" alt="다마스" class="w-12 h-12 object-contain">
                            </div>
                            <span class="text-xs font-medium text-gray-700">다마스</span>
                        </div>
                    </label>
                    <label class="vehicle-option cursor-pointer">
                        <input type="radio" name="delivery_method" value="labo" <?= old('delivery_method') === 'labo' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                        <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                            <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                <img src="<?= base_url('assets/icons/202.png') ?>" alt="라보" class="w-12 h-12 object-contain">
                            </div>
                            <span class="text-xs font-medium text-gray-700">라보</span>
                        </div>
                    </label>
                    <label class="vehicle-option cursor-pointer">
                        <input type="radio" name="delivery_method" value="truck" <?= old('delivery_method') === 'truck' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                        <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                            <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                <img src="<?= base_url('assets/icons/25.png') ?>" alt="트럭" class="w-12 h-12 object-contain">
                            </div>
                            <span class="text-xs font-medium text-gray-700">트럭</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- 트럭 선택 시 상세 항목 -->
            <div id="truckDetails" class="space-y-3" style="display: none;">
                <?php if ($truckCapacityStyle === 'select'): ?>
                <!-- Select 박스 방식 (quick-vehicle 스타일) -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- 차량무게 -->
                    <div class="space-y-1">
                        <label for="truck_capacity" class="block text-xs font-medium text-gray-600">차량무게 *</label>
                        <select id="truck_capacity" name="truck_capacity" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            <option value="">선택</option>
                            <?php 
                            $truck_capacities = \App\Config\TruckOptions::getCapacities();
                            foreach ($truck_capacities as $key => $value): 
                            ?>
                            <option value="<?= $key ?>" <?= old('truck_capacity') === $key ? 'selected' : '' ?>><?= $value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- 차량종류 -->
                    <div class="space-y-1">
                        <label for="truck_body_type" class="block text-xs font-medium text-gray-600">차량종류 *</label>
                        <select id="truck_body_type" name="truck_body_type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            <option value="">선택</option>
                            <?php 
                            $truck_body_types = \App\Config\TruckOptions::getBodyTypes();
                            foreach ($truck_body_types as $key => $value): 
                            ?>
                            <option value="<?= $key ?>" <?= old('truck_body_type') === $key ? 'selected' : '' ?>><?= $value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php else: ?>
                <!-- 라디오 버튼 방식 (quick-moving 스타일) -->
                <div class="bg-white border border-gray-200 rounded-lg p-3">
                    <label class="block text-xs font-medium text-gray-600 mb-2">차량무게</label>
                    <div class="grid grid-cols-4 gap-1">
                        <?php 
                        $truck_capacities = \App\Config\TruckOptions::getCapacities();
                        foreach ($truck_capacities as $key => $value): 
                        ?>
                        <label class="flex items-center space-x-1 cursor-pointer p-1 hover:bg-gray-50 rounded">
                            <input type="radio" name="truck_capacity" value="<?= $key ?>" <?= old('truck_capacity') === $key ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                            <span class="text-xs text-gray-700"><?= $value ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="bg-white border border-gray-200 rounded-lg p-3">
                    <label class="block text-xs font-medium text-gray-600 mb-2">차량종류</label>
                    <div class="grid grid-cols-4 gap-1">
                        <?php 
                        $truck_body_types = \App\Config\TruckOptions::getBodyTypes();
                        foreach ($truck_body_types as $key => $value): 
                        ?>
                        <label class="flex items-center space-x-1 cursor-pointer p-1 hover:bg-gray-50 rounded">
                            <input type="radio" name="truck_body_type" value="<?= $key ?>" <?= old('truck_body_type') === $key ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                            <span class="text-xs text-gray-700"><?= $value ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?= $this->include('forms/quick-delivery-options-section') ?>
        </div>
    </section>
</div>

<style>
/* 배송수단 선택 시 포커싱 스타일 */
.vehicle-option input[type="radio"]:checked + .vehicle-card {
    border-color: #3b82f6;
    background-color: #eff6ff;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.vehicle-option input[type="radio"]:checked + .vehicle-card img {
    transform: scale(1.1);
}

.vehicle-card {
    transition: all 0.2s ease-in-out;
}

.vehicle-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>

<script>
// 배송수단 변경 시 트럭 상세 항목 및 상하차방법 버튼 표시/숨김
window.toggleVehicleDetails = function() {
    const truckDetails = document.getElementById('truckDetails');
    const loadingUnloadingBtn = document.getElementById('loadingUnloadingBtn');
    const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
    
    // 전달사항 필드 찾기 및 초기화
    const deliveryContentField = document.getElementById('special_instructions') || 
                                 document.getElementById('delivery_content') || 
                                 document.getElementById('deliveryInstructions') ||
                                 document.querySelector('textarea[name="special_instructions"]') ||
                                 document.querySelector('textarea[name="delivery_content"]') ||
                                 document.querySelector('textarea[name="deliveryInstructions"]');
    
    // 배송수단 변경 시 전달사항 초기화 (상하차방법 관련 텍스트 제거)
    if (deliveryContentField && deliveryContentField.value) {
        let currentValue = deliveryContentField.value;
        // "상차: ... / 하차: ..." 패턴 제거
        currentValue = currentValue.replace(/상차:\s*[^/]*(\s*\/\s*하차:\s*[^/]*)?/g, '').trim();
        // "하차: ..." 패턴 제거 (상차 없이 하차만 있는 경우)
        currentValue = currentValue.replace(/하차:\s*[^/]*/g, '').trim();
        // 앞뒤 공백 정리
        deliveryContentField.value = currentValue;
    }
    
    if (selectedVehicle && selectedVehicle.value === 'truck') {
        if (truckDetails) truckDetails.style.display = 'block';
        if (loadingUnloadingBtn) {
            loadingUnloadingBtn.classList.add('show');
            loadingUnloadingBtn.style.setProperty('display', 'block', 'important');
        }
    } else if (selectedVehicle && (selectedVehicle.value === 'damas' || selectedVehicle.value === 'labo')) {
        if (truckDetails) truckDetails.style.display = 'none';
        if (loadingUnloadingBtn) {
            loadingUnloadingBtn.classList.add('show');
            loadingUnloadingBtn.style.setProperty('display', 'block', 'important');
        }
        // 트럭이 아닌 경우 차량무게, 차량종류 값 초기화
        document.querySelectorAll('input[name="truck_capacity"]').forEach(radio => radio.checked = false);
        document.querySelectorAll('input[name="truck_body_type"]').forEach(radio => radio.checked = false);
        document.querySelectorAll('select[name="truck_capacity"]').forEach(select => select.value = '');
        document.querySelectorAll('select[name="truck_body_type"]').forEach(select => select.value = '');
    } else {
        // 오토바이, 자전거, 스쿠터 등 다른 배송수단일 때는 버튼 숨김
        if (truckDetails) truckDetails.style.display = 'none';
        if (loadingUnloadingBtn) {
            loadingUnloadingBtn.classList.remove('show');
            loadingUnloadingBtn.style.setProperty('display', 'none', 'important');
        }
    }
    
    // 전역 함수가 있으면 호출 (delivery-instructions-section.php의 함수)
    if (typeof toggleLoadingUnloadingButton === 'function') {
        toggleLoadingUnloadingButton();
    }
    
    // 상차방법/하차방법 섹션도 업데이트
    if (typeof toggleLoadingUnloadingMethod === 'function') {
        toggleLoadingUnloadingMethod();
    }
    
    // 물품종류 선택 옵션도 업데이트
    if (typeof toggleItemTypeSelection === 'function') {
        toggleItemTypeSelection();
    }
};

// 페이지 로드 시 초기 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    toggleVehicleDetails();
    
    // 배송수단 변경 시 이벤트 리스너
    document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleVehicleDetails();
        });
    });
    
    // 상하차방법 선택 버튼 클릭 이벤트
    const loadingUnloadingBtn = document.getElementById('loadingUnloadingBtn');
    if (loadingUnloadingBtn) {
        loadingUnloadingBtn.addEventListener('click', function() {
            if (typeof openLoadingUnloadingModal === 'function') {
                openLoadingUnloadingModal();
            }
        });
    }
});
</script>

