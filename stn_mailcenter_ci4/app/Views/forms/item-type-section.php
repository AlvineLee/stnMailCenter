<?php
/**
 * 물품종류 섹션 공통 컴포넌트
 * 
 * @var string $itemTypeName 필드명 (기본값: 'item_type')
 * @var string $itemTypeId 필드 ID (기본값: 'item_type')
 * @var string $defaultValue 기본값 (기본값: '서류')
 * @var bool $showBoxSelection 박스선택 표시 여부 (기본값: true)
 * @var bool $showPouchSelection 행낭선택 표시 여부 (기본값: true)
 * @var bool $showShoppingBagSelection 쇼핑백선택 표시 여부 (기본값: true)
 * @var bool $showOverloadCheckbox 과적 체크박스 표시 여부 (기본값: true)
 * @var string $boxGuideButtonText 박스 규격 안내 버튼 텍스트 (기본값: '박스 규격 안내')
 * @var string $pouchGuideButtonText 행낭 규격 안내 버튼 텍스트 (기본값: '행낭 규격 안내')
 */

// 기본값 설정
$itemTypeName = $itemTypeName ?? 'item_type';
$itemTypeId = $itemTypeId ?? 'item_type';
$defaultValue = $defaultValue ?? '서류';
$showBoxSelection = $showBoxSelection ?? true;
$showPouchSelection = $showPouchSelection ?? true;
$showShoppingBagSelection = $showShoppingBagSelection ?? true;
$showOverloadCheckbox = $showOverloadCheckbox ?? true;
$boxGuideButtonText = $boxGuideButtonText ?? '박스 규격 안내';
$pouchGuideButtonText = $pouchGuideButtonText ?? '행낭 규격 안내';
?>

<!-- 물품종류 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">물품종류</h2>
        <div class="space-y-3">
            <div class="space-y-1">
                <label for="<?= esc($itemTypeId) ?>" class="block text-xs font-medium text-gray-600">물품종류 *</label>
                <input type="text" id="<?= esc($itemTypeId) ?>" name="<?= esc($itemTypeName) ?>" value="<?= old($itemTypeName, $defaultValue) ?>" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white" lang="ko">
            </div>
            
            <!-- 규격 안내 버튼 (다마스/라보/트럭이 아닐 때만 표시) -->
            <?php if ($showBoxSelection || $showPouchSelection): ?>
            <div id="guideButtons" class="grid grid-cols-2 gap-2">
                <?php if ($showBoxSelection): ?>
                <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                    <?= esc($boxGuideButtonText) ?>
                </button>
                <?php endif; ?>
                <?php if ($showPouchSelection): ?>
                <button type="button" id="pouchGuideButton" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                    <?= esc($pouchGuideButtonText) ?>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 트럭일 때: 박스/팔레트 선택 -->
            <div id="truckItemSelection" class="space-y-3" style="display: none;">
                <div class="grid grid-cols-4 gap-2">
                    <div class="space-y-1">
                        <label for="truck_box_selection" class="block text-xs font-medium text-gray-600">박스선택</label>
                        <select id="truck_box_selection" name="truck_box_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            <option value="">선택</option>
                            <option value="small" <?= old('truck_box_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                            <option value="medium" <?= old('truck_box_selection') === 'medium' ? 'selected' : '' ?>>중형</option>
                            <option value="large" <?= old('truck_box_selection') === 'large' ? 'selected' : '' ?>>대형</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="truck_box_quantity" class="block text-xs font-medium text-gray-600">개수</label>
                        <input type="number" id="truck_box_quantity" name="truck_box_quantity" value="<?= old('truck_box_quantity', '0') ?>" min="0"
                               class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                    </div>
                    <div class="space-y-1">
                        <label for="pallet_selection" class="block text-xs font-medium text-gray-600">팔레트선택</label>
                        <select id="pallet_selection" name="pallet_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            <option value="">선택</option>
                            <option value="small" <?= old('pallet_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                            <option value="medium" <?= old('pallet_selection') === 'medium' ? 'selected' : '' ?>>중형</option>
                            <option value="large" <?= old('pallet_selection') === 'large' ? 'selected' : '' ?>>대형</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="pallet_quantity" class="block text-xs font-medium text-gray-600">개수</label>
                        <input type="number" id="pallet_quantity" name="pallet_quantity" value="<?= old('pallet_quantity', '0') ?>" min="0"
                               class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                    </div>
                </div>
            </div>
            
            <!-- 다마스/라보일 때: 박스선택, 개수, 팔레트선택, 개수 표시 -->
            <!-- 다른 배송수단일 때: 박스선택, 행낭선택, 쇼핑백선택 각각 개수 선택 -->
            <div id="normalItemSelection">
                <?php if ($showBoxSelection): ?>
                <!-- 다마스/라보일 때: 박스선택, 개수, 팔레트선택, 개수 -->
                <div id="damasLaboItemSelection" class="grid grid-cols-4 gap-2">
                    <div class="space-y-1">
                        <label for="box_selection" class="block text-xs font-medium text-gray-600">박스선택</label>
                        <select id="box_selection" name="box_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            <option value="">선택</option>
                            <option value="small" <?= old('box_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                            <option value="medium" <?= old('box_selection') === 'medium' ? 'selected' : '' ?>>중형</option>
                            <option value="large" <?= old('box_selection') === 'large' ? 'selected' : '' ?>>대형</option>
                        </select>
                        <?php if ($showOverloadCheckbox): ?>
                        <!-- 중형 선택 시 과적 체크박스 -->
                        <div id="box_medium_overload" style="display: none;" class="mt-1">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" id="box_medium_overload_check" name="box_medium_overload" value="1" <?= old('box_medium_overload') ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                <span class="text-xs font-medium text-gray-700">과적</span>
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-1">
                        <label for="box_quantity" class="block text-xs font-medium text-gray-600">개수</label>
                        <input type="number" id="box_quantity" name="box_quantity" value="<?= old('box_quantity', '0') ?>" min="0"
                               class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                    </div>
                    <div class="space-y-1">
                        <label for="pallet_selection" class="block text-xs font-medium text-gray-600">팔레트선택</label>
                        <select id="pallet_selection" name="pallet_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            <option value="">선택</option>
                            <option value="small" <?= old('pallet_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                            <option value="medium" <?= old('pallet_selection') === 'medium' ? 'selected' : '' ?>>중형</option>
                            <option value="large" <?= old('pallet_selection') === 'large' ? 'selected' : '' ?>>대형</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="pallet_quantity" class="block text-xs font-medium text-gray-600">개수</label>
                        <input type="number" id="pallet_quantity" name="pallet_quantity" value="<?= old('pallet_quantity', '0') ?>" min="0"
                               class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                    </div>
                </div>
                
                <!-- 다른 배송수단일 때: 박스선택, 행낭선택, 쇼핑백선택 각각 개수 선택 -->
                <div id="otherVehicleItemSelection" class="flex flex-wrap sm:flex-nowrap gap-1 sm:gap-2 items-end" style="display: none;">
                    <?php if ($showBoxSelection): ?>
                    <div class="flex-1 min-w-[70px] sm:min-w-0">
                        <label for="box_selection_other" class="block text-xs font-medium text-gray-600 mb-1">박스선택</label>
                        <div class="space-y-1">
                            <select id="box_selection_other" name="box_selection_other" class="w-full px-1 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                <option value="">선택</option>
                                <option value="small" <?= old('box_selection_other') === 'small' ? 'selected' : '' ?>>소박스</option>
                                <option value="medium" <?= old('box_selection_other') === 'medium' ? 'selected' : '' ?>>중박스</option>
                                <option value="large" <?= old('box_selection_other') === 'large' ? 'selected' : '' ?>>대박스</option>
                            </select>
                            <?php if ($showOverloadCheckbox): ?>
                            <!-- 중형 선택 시 과적 체크박스 -->
                            <div id="box_medium_overload_other" style="display: none;" class="flex items-center justify-center pt-0.5">
                                <label class="flex items-center space-x-1 cursor-pointer">
                                    <input type="checkbox" id="box_medium_overload_check_other" name="box_medium_overload_check_other" value="1" <?= old('box_medium_overload_check_other') ? 'checked' : '' ?> class="w-3 h-3 text-gray-600 focus:ring-gray-500 border-gray-300 rounded">
                                    <span class="text-xs text-gray-600">과적</span>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex-1 min-w-[50px] sm:min-w-[45px] sm:max-w-[60px]">
                        <label for="box_quantity_other" class="block text-xs font-medium text-gray-600 mb-1">개수</label>
                        <input type="number" id="box_quantity_other" name="box_quantity_other" value="<?= old('box_quantity_other', '0') ?>" min="0" disabled
                               class="w-full px-1 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-gray-100 cursor-not-allowed">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($showPouchSelection): ?>
                    <div id="pouchSelectionDivOther" class="flex-1 min-w-[70px] sm:min-w-0">
                        <label for="pouch_selection_other" class="block text-xs font-medium text-gray-600 mb-1">행낭선택</label>
                        <div class="space-y-1">
                            <select id="pouch_selection_other" name="pouch_selection_other" class="w-full px-1 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                <option value="">선택</option>
                                <option value="small" <?= old('pouch_selection_other') === 'small' ? 'selected' : '' ?>>소행낭</option>
                                <option value="medium" <?= old('pouch_selection_other') === 'medium' ? 'selected' : '' ?>>중행낭</option>
                                <option value="large" <?= old('pouch_selection_other') === 'large' ? 'selected' : '' ?>>대행낭</option>
                            </select>
                            <?php if ($showOverloadCheckbox): ?>
                            <!-- 중형 선택 시 과적 체크박스 -->
                            <div id="pouch_medium_overload_other" style="display: none;" class="flex items-center justify-center pt-0.5">
                                <label class="flex items-center space-x-1 cursor-pointer">
                                    <input type="checkbox" id="pouch_medium_overload_check_other" name="pouch_medium_overload_check_other" value="1" <?= old('pouch_medium_overload_check_other') ? 'checked' : '' ?> class="w-3 h-3 text-gray-600 focus:ring-gray-500 border-gray-300 rounded">
                                    <span class="text-xs text-gray-600">과적</span>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="pouchQuantityDivOther" class="flex-1 min-w-[50px] sm:min-w-[45px] sm:max-w-[60px]">
                        <label for="pouch_quantity_other" class="block text-xs font-medium text-gray-600 mb-1">개수</label>
                        <input type="number" id="pouch_quantity_other" name="pouch_quantity_other" value="<?= old('pouch_quantity_other', '0') ?>" min="0" disabled
                               class="w-full px-1 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-gray-100 cursor-not-allowed">
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($showShoppingBagSelection): ?>
                    <div id="shoppingBagSelectionDivOther" class="flex-1 min-w-[80px] sm:min-w-0">
                        <label for="shopping_bag_selection_other" class="block text-xs font-medium text-gray-600 mb-1">쇼핑백선택</label>
                        <select id="shopping_bag_selection_other" name="shopping_bag_selection_other" class="w-full px-1 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            <option value="">선택</option>
                            <option value="small" <?= old('shopping_bag_selection_other') === 'small' ? 'selected' : '' ?>>소쇼핑팩</option>
                            <option value="medium" <?= old('shopping_bag_selection_other') === 'medium' ? 'selected' : '' ?>>중쇼핑백</option>
                            <option value="large" <?= old('shopping_bag_selection_other') === 'large' ? 'selected' : '' ?>>대쇼핑백</option>
                        </select>
                    </div>
                    <div id="shoppingBagQuantityDivOther" class="flex-1 min-w-[50px] sm:min-w-[45px] sm:max-w-[60px]">
                        <label for="shopping_bag_quantity_other" class="block text-xs font-medium text-gray-600 mb-1">개수</label>
                        <input type="number" id="shopping_bag_quantity_other" name="shopping_bag_quantity_other" value="<?= old('shopping_bag_quantity_other', '0') ?>" min="0" disabled
                               class="w-full px-1 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-gray-100 cursor-not-allowed">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script>
// 배송수단에 따라 물품종류 선택 옵션 표시/숨김 (전역 함수)
window.toggleItemTypeSelection = function() {
    const truckItemSelection = document.getElementById('truckItemSelection');
    const normalItemSelection = document.getElementById('normalItemSelection');
    const damasLaboItemSelection = document.getElementById('damasLaboItemSelection');
    const otherVehicleItemSelection = document.getElementById('otherVehicleItemSelection');
    const guideButtons = document.getElementById('guideButtons');
    const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
    
    if (selectedVehicle && (selectedVehicle.value === 'damas' || selectedVehicle.value === 'labo' || selectedVehicle.value === 'truck')) {
        // 다마스/라보/트럭일 때: 규격 안내 버튼 숨김
        if (guideButtons) guideButtons.style.display = 'none';
        
        if (selectedVehicle.value === 'truck') {
            // 트럭일 때: 박스/팔레트 선택 표시
            if (truckItemSelection) truckItemSelection.style.display = 'block';
            if (normalItemSelection) normalItemSelection.style.display = 'none';
        } else {
            // 다마스/라보일 때: 박스선택, 개수, 팔레트선택, 개수 표시
            if (truckItemSelection) truckItemSelection.style.display = 'none';
            if (normalItemSelection) normalItemSelection.style.display = 'block';
            if (damasLaboItemSelection) damasLaboItemSelection.style.display = 'grid';
            if (otherVehicleItemSelection) otherVehicleItemSelection.style.display = 'none';
        }
    } else {
        // 다른 배송수단일 때: 규격 안내 버튼 표시, 박스/행낭/쇼핑백 선택 표시
        if (guideButtons) guideButtons.style.display = 'grid';
        if (truckItemSelection) truckItemSelection.style.display = 'none';
        if (normalItemSelection) normalItemSelection.style.display = 'block';
        if (damasLaboItemSelection) damasLaboItemSelection.style.display = 'none';
        if (otherVehicleItemSelection) otherVehicleItemSelection.style.display = 'flex';
        
        // 개수 필드 상태 업데이트
        setTimeout(function() {
            if (typeof toggleQuantityFields === 'function') {
                toggleQuantityFields();
            }
        }, 100);
    }
}

// 전달사항에 물품종류 선택 값 추가/업데이트
function updateDeliveryInstructionsWithItemTypes() {
    const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
    if (!selectedVehicle || (selectedVehicle.value === 'damas' || selectedVehicle.value === 'labo' || selectedVehicle.value === 'truck')) {
        return; // 다마스/라보/트럭일 때는 전달사항에 추가하지 않음
    }
    
    // 전달사항 필드 찾기
    const deliveryContentField = document.getElementById('special_instructions') || 
                                 document.getElementById('delivery_content') || 
                                 document.getElementById('deliveryInstructions') ||
                                 document.querySelector('textarea[name="special_instructions"]') ||
                                 document.querySelector('textarea[name="delivery_content"]') ||
                                 document.querySelector('textarea[name="deliveryInstructions"]');
    
    if (!deliveryContentField) return;
    
    const parts = [];
    
    // 박스선택
    const boxSelection = document.getElementById('box_selection_other');
    const boxQuantity = document.getElementById('box_quantity_other');
    const boxOverload = document.getElementById('box_medium_overload_check_other');
    if (boxSelection && boxQuantity && boxSelection.value && parseInt(boxQuantity.value) > 0) {
        const boxTextMap = { 'small': '소박스', 'medium': '중박스', 'large': '대박스' };
        let boxText = boxTextMap[boxSelection.value] + ' ' + boxQuantity.value + '개';
        if (boxOverload && boxOverload.checked && boxSelection.value === 'medium') {
            boxText += ' (과적)';
        }
        parts.push(boxText);
    }
    
    // 행낭선택
    const pouchSelection = document.getElementById('pouch_selection_other');
    const pouchQuantity = document.getElementById('pouch_quantity_other');
    const pouchOverload = document.getElementById('pouch_medium_overload_check_other');
    if (pouchSelection && pouchQuantity && pouchSelection.value && parseInt(pouchQuantity.value) > 0) {
        const pouchTextMap = { 'small': '소행낭', 'medium': '중행낭', 'large': '대행낭' };
        let pouchText = pouchTextMap[pouchSelection.value] + ' ' + pouchQuantity.value + '개';
        if (pouchOverload && pouchOverload.checked && pouchSelection.value === 'medium') {
            pouchText += ' (과적)';
        }
        parts.push(pouchText);
    }
    
    // 쇼핑백선택
    const shoppingBagSelection = document.getElementById('shopping_bag_selection_other');
    const shoppingBagQuantity = document.getElementById('shopping_bag_quantity_other');
    if (shoppingBagSelection && shoppingBagQuantity && shoppingBagSelection.value && parseInt(shoppingBagQuantity.value) > 0) {
        const bagTextMap = { 'small': '소쇼핑팩', 'medium': '중쇼핑백', 'large': '대쇼핑백' };
        const bagText = bagTextMap[shoppingBagSelection.value] + ' ' + shoppingBagQuantity.value + '개';
        parts.push(bagText);
    }
    
    // 기존 전달사항에서 물품종류 관련 텍스트 제거
    let currentValue = deliveryContentField.value || '';
    // 물품종류 관련 패턴 제거 (소/중/대 + 박스/행낭/쇼핑백/쇼핑팩)
    currentValue = currentValue.replace(/[소중대]박스\s*\d+개\s*(\(과적\))?/g, '').trim();
    currentValue = currentValue.replace(/[소중대]행낭\s*\d+개\s*(\(과적\))?/g, '').trim();
    currentValue = currentValue.replace(/[소중대]쇼핑[백팩]\s*\d+개/g, '').trim();
    // 연속된 슬래시와 공백 정리
    currentValue = currentValue.replace(/(\s*\/\s*)+/g, ' / ').trim();
    currentValue = currentValue.replace(/^\s*\/\s*|\s*\/\s*$/g, '').trim();
    
    // 새로운 물품종류 정보 추가
    if (parts.length > 0) {
        const itemTypeText = parts.join(' / ');
        if (currentValue) {
            deliveryContentField.value = currentValue + ' / ' + itemTypeText;
        } else {
            deliveryContentField.value = itemTypeText;
        }
    } else {
        deliveryContentField.value = currentValue;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleItemTypeSelection();
    
    // 배송수단 변경 시 물품종류 선택 옵션 업데이트
    document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleItemTypeSelection();
            updateDeliveryInstructionsWithItemTypes();
            setTimeout(toggleQuantityFields, 100);
        });
    });
    
    // 개수 입력 필드 활성화/비활성화 함수
    function toggleQuantityFields() {
        const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
        if (!selectedVehicle || (selectedVehicle.value === 'damas' || selectedVehicle.value === 'labo' || selectedVehicle.value === 'truck')) {
            return; // 다마스/라보/트럭일 때는 처리하지 않음
        }
        
        // 박스 개수 필드
        const boxSelection = document.getElementById('box_selection_other');
        const boxQuantity = document.getElementById('box_quantity_other');
        if (boxSelection && boxQuantity) {
            if (boxSelection.value) {
                boxQuantity.disabled = false;
                boxQuantity.classList.remove('bg-gray-100', 'cursor-not-allowed');
                boxQuantity.classList.add('bg-white');
            } else {
                boxQuantity.disabled = true;
                boxQuantity.value = '0';
                boxQuantity.classList.remove('bg-white');
                boxQuantity.classList.add('bg-gray-100', 'cursor-not-allowed');
            }
        }
        
        // 행낭 개수 필드
        const pouchSelection = document.getElementById('pouch_selection_other');
        const pouchQuantity = document.getElementById('pouch_quantity_other');
        if (pouchSelection && pouchQuantity) {
            if (pouchSelection.value) {
                pouchQuantity.disabled = false;
                pouchQuantity.classList.remove('bg-gray-100', 'cursor-not-allowed');
                pouchQuantity.classList.add('bg-white');
            } else {
                pouchQuantity.disabled = true;
                pouchQuantity.value = '0';
                pouchQuantity.classList.remove('bg-white');
                pouchQuantity.classList.add('bg-gray-100', 'cursor-not-allowed');
            }
        }
        
        // 쇼핑백 개수 필드
        const shoppingBagSelection = document.getElementById('shopping_bag_selection_other');
        const shoppingBagQuantity = document.getElementById('shopping_bag_quantity_other');
        if (shoppingBagSelection && shoppingBagQuantity) {
            if (shoppingBagSelection.value) {
                shoppingBagQuantity.disabled = false;
                shoppingBagQuantity.classList.remove('bg-gray-100', 'cursor-not-allowed');
                shoppingBagQuantity.classList.add('bg-white');
            } else {
                shoppingBagQuantity.disabled = true;
                shoppingBagQuantity.value = '0';
                shoppingBagQuantity.classList.remove('bg-white');
                shoppingBagQuantity.classList.add('bg-gray-100', 'cursor-not-allowed');
            }
        }
    }
    
    // 다른 배송수단일 때 물품종류 선택 값 변경 시 전달사항 업데이트
    const itemTypeFields = [
        'box_selection_other', 'box_quantity_other', 'box_medium_overload_check_other',
        'pouch_selection_other', 'pouch_quantity_other', 'pouch_medium_overload_check_other',
        'shopping_bag_selection_other', 'shopping_bag_quantity_other'
    ];
    
    itemTypeFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                toggleQuantityFields();
                updateDeliveryInstructionsWithItemTypes();
            });
            field.addEventListener('input', function() {
                updateDeliveryInstructionsWithItemTypes();
            });
        }
    });
    
    // 배송수단 변경 시에도 개수 필드 상태 업데이트
    document.querySelectorAll('input[name="delivery_method"]').forEach(radio => {
        const originalHandler = radio.onchange;
        radio.addEventListener('change', function() {
            setTimeout(toggleQuantityFields, 100); // UI 업데이트 후 실행
        });
    });
    
    // 초기 상태 설정
    setTimeout(toggleQuantityFields, 200);
    
    <?php if ($showOverloadCheckbox && $showBoxSelection): ?>
    // 박스선택 중형 선택 시 과적 체크박스 표시/숨김
    const boxSelection = document.getElementById('box_selection');
    const boxMediumOverload = document.getElementById('box_medium_overload');
    
    if (boxSelection && boxMediumOverload) {
        if (boxSelection.value === 'medium') {
            boxMediumOverload.style.display = 'block';
        }
        
        boxSelection.addEventListener('change', function() {
            if (this.value === 'medium') {
                boxMediumOverload.style.display = 'block';
            } else {
                boxMediumOverload.style.display = 'none';
                const checkbox = document.getElementById('box_medium_overload_check');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if ($showOverloadCheckbox && $showPouchSelection): ?>
    // 행낭선택 중형 선택 시 과적 체크박스 표시/숨김 (다마스/라보용)
    const pouchSelection = document.getElementById('pouch_selection');
    const pouchMediumOverload = document.getElementById('pouch_medium_overload');
    
    if (pouchSelection && pouchMediumOverload) {
        if (pouchSelection.value === 'medium') {
            pouchMediumOverload.style.display = 'block';
        }
        
        pouchSelection.addEventListener('change', function() {
            if (this.value === 'medium') {
                pouchMediumOverload.style.display = 'block';
            } else {
                pouchMediumOverload.style.display = 'none';
                const checkbox = document.getElementById('pouch_medium_overload_check');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    }
    
    // 행낭선택 중형 선택 시 과적 체크박스 표시/숨김 (다른 배송수단용)
    const pouchSelectionOther = document.getElementById('pouch_selection_other');
    const pouchMediumOverloadOther = document.getElementById('pouch_medium_overload_other');
    
    if (pouchSelectionOther && pouchMediumOverloadOther) {
        if (pouchSelectionOther.value === 'medium') {
            pouchMediumOverloadOther.style.display = 'block';
        }
        
        pouchSelectionOther.addEventListener('change', function() {
            if (this.value === 'medium') {
                pouchMediumOverloadOther.style.display = 'block';
            } else {
                pouchMediumOverloadOther.style.display = 'none';
                const checkbox = document.getElementById('pouch_medium_overload_check_other');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
            updateDeliveryInstructionsWithItemTypes();
        });
    }
    <?php endif; ?>
    
    // 박스선택 중형 선택 시 과적 체크박스 표시/숨김 (다른 배송수단용)
    const boxSelectionOther = document.getElementById('box_selection_other');
    const boxMediumOverloadOther = document.getElementById('box_medium_overload_other');
    
    if (boxSelectionOther && boxMediumOverloadOther) {
        if (boxSelectionOther.value === 'medium') {
            boxMediumOverloadOther.style.display = 'block';
        }
        
        boxSelectionOther.addEventListener('change', function() {
            if (this.value === 'medium') {
                boxMediumOverloadOther.style.display = 'block';
            } else {
                boxMediumOverloadOther.style.display = 'none';
                const checkbox = document.getElementById('box_medium_overload_check_other');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
            updateDeliveryInstructionsWithItemTypes();
        });
    }
});
</script>

