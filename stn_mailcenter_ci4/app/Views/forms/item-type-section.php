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
$defaultValue = $defaultValue ?? '1'; // 1:서류봉투, 2:소박스, 3:중박스, 4:대박스
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
                <label class="block text-xs font-medium text-gray-600">물품종류 *</label>
                <div class="flex flex-wrap gap-3">
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="<?= esc($itemTypeName) ?>" id="<?= esc($itemTypeId) ?>_1" value="1" <?= old($itemTypeName, '1') === '1' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500" required>
                        <span class="text-sm font-medium text-gray-700">서류봉투</span>
                    </label>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="<?= esc($itemTypeName) ?>" id="<?= esc($itemTypeId) ?>_2" value="2" <?= old($itemTypeName) === '2' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                        <span class="text-sm font-medium text-gray-700">소박스</span>
                    </label>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="<?= esc($itemTypeName) ?>" id="<?= esc($itemTypeId) ?>_3" value="3" <?= old($itemTypeName) === '3' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                        <span class="text-sm font-medium text-gray-700">중박스</span>
                    </label>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="<?= esc($itemTypeName) ?>" id="<?= esc($itemTypeId) ?>_4" value="4" <?= old($itemTypeName) === '4' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                        <span class="text-sm font-medium text-gray-700">대박스</span>
                    </label>
                </div>
            </div>

            <?php /*
            ================================================================================
            아래 박스/행낭/쇼핑백 선택 UI는 나중에 복원될 수 있으므로 주석 처리합니다.
            ================================================================================

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

            ================================================================================
            주석 처리 끝
            ================================================================================
            */ ?>
        </div>
    </section>
</div>

<script>
// 물품종류 라디오 버튼 클릭 시 전달사항에 자동 입력
document.addEventListener('DOMContentLoaded', function() {
    // 물품종류 값과 텍스트 매핑
    const itemTypeTextMap = {
        '1': '서류봉투',
        '2': '소박스',
        '3': '중박스',
        '4': '대박스'
    };

    // 이전에 입력된 물품종류 텍스트를 저장
    let previousItemTypeText = '';

    // 물품종류 라디오 버튼들
    const itemTypeRadios = document.querySelectorAll('input[name="<?= esc($itemTypeName) ?>"]');

    // 전달사항 필드 찾기
    function getSpecialInstructionsField() {
        return document.getElementById('special_instructions') ||
               document.getElementById('delivery_content') ||
               document.getElementById('deliveryInstructions') ||
               document.querySelector('textarea[name="special_instructions"]') ||
               document.querySelector('textarea[name="delivery_content"]') ||
               document.querySelector('textarea[name="deliveryInstructions"]');
    }

    // 물품종류 텍스트 업데이트 함수
    function updateItemTypeInSpecialInstructions(itemTypeValue, setFocus = false) {
        const specialInstructions = getSpecialInstructionsField();
        if (!specialInstructions) return;

        const itemTypeName = itemTypeTextMap[itemTypeValue];
        const newItemTypeText = itemTypeName + '( 개)';
        let currentValue = specialInstructions.value || '';

        // 이전 물품종류 텍스트 패턴 제거 (서류봉투, 소박스, 중박스, 대박스 + ( 개))
        const itemTypePattern = /(서류봉투|소박스|중박스|대박스)\(\s*\d*\s*개\)/g;
        currentValue = currentValue.replace(itemTypePattern, '').trim();

        // 연속된 공백 정리
        currentValue = currentValue.replace(/\s+/g, ' ').trim();

        // 새로운 물품종류 텍스트 추가 (맨 앞에)
        if (currentValue) {
            specialInstructions.value = newItemTypeText + ' ' + currentValue;
        } else {
            specialInstructions.value = newItemTypeText;
        }

        previousItemTypeText = newItemTypeText;

        // 포커스 설정 및 커서 위치 조정 (사용자가 클릭한 경우에만)
        if (setFocus) {
            specialInstructions.focus();
            // 커서 위치: "서류봉투(" 다음, " 개)" 앞
            const cursorPosition = itemTypeName.length + 1; // 물품종류명 + "(" 길이
            specialInstructions.setSelectionRange(cursorPosition, cursorPosition);
        }
    }

    // 라디오 버튼 변경 이벤트 리스너
    itemTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                updateItemTypeInSpecialInstructions(this.value, true); // 클릭 시 포커스 설정
            }
        });
    });

    // 페이지 로드 시 초기 선택된 값으로 전달사항 업데이트
    const checkedRadio = document.querySelector('input[name="<?= esc($itemTypeName) ?>"]:checked');
    if (checkedRadio) {
        // 약간의 지연 후 실행 (전달사항 필드가 로드된 후)
        setTimeout(function() {
            updateItemTypeInSpecialInstructions(checkedRadio.value);
        }, 100);
    }
});
</script>

