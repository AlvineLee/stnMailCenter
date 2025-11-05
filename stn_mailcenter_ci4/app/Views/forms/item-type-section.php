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
            
            <?php if ($showBoxSelection || $showPouchSelection): ?>
            <div class="grid grid-cols-2 gap-2">
                <?php if ($showBoxSelection): ?>
                <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                    <?= esc($boxGuideButtonText) ?>
                </button>
                <?php endif; ?>
                <?php if ($showPouchSelection): ?>
                <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                    <?= esc($pouchGuideButtonText) ?>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($showBoxSelection || $showPouchSelection || $showShoppingBagSelection): ?>
            <div class="grid grid-cols-5 gap-2">
                <?php if ($showBoxSelection): ?>
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
                <?php endif; ?>
                
                <?php if ($showPouchSelection): ?>
                <div class="space-y-1">
                    <label for="pouch_selection" class="block text-xs font-medium text-gray-600">행낭선택</label>
                    <select id="pouch_selection" name="pouch_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                        <option value="">선택</option>
                        <option value="small" <?= old('pouch_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                        <option value="medium" <?= old('pouch_selection') === 'medium' ? 'selected' : '' ?>>중형</option>
                    </select>
                    <?php if ($showOverloadCheckbox): ?>
                    <!-- 중형 선택 시 과적 체크박스 -->
                    <div id="pouch_medium_overload" style="display: none;" class="mt-1">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" id="pouch_medium_overload_check" name="pouch_medium_overload" value="1" <?= old('pouch_medium_overload') ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                            <span class="text-xs font-medium text-gray-700">과적</span>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="space-y-1">
                    <label for="pouch_quantity" class="block text-xs font-medium text-gray-600">개수</label>
                    <input type="number" id="pouch_quantity" name="pouch_quantity" value="<?= old('pouch_quantity', '0') ?>" min="0"
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
                <?php endif; ?>
                
                <?php if ($showShoppingBagSelection): ?>
                <div class="space-y-1">
                    <label for="shopping_bag_selection" class="block text-xs font-medium text-gray-600">쇼핑백선택</label>
                    <select id="shopping_bag_selection" name="shopping_bag_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                        <option value="">선택</option>
                        <option value="small" <?= old('shopping_bag_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                        <option value="large" <?= old('shopping_bag_selection') === 'large' ? 'selected' : '' ?>>대형</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($showOverloadCheckbox && ($showBoxSelection || $showPouchSelection)): ?>
<script>
// 박스선택/행낭선택 중형 선택 시 과적 체크박스 표시/숨김
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($showBoxSelection): ?>
    const boxSelection = document.getElementById('box_selection');
    const boxMediumOverload = document.getElementById('box_medium_overload');
    
    if (boxSelection && boxMediumOverload) {
        // 초기 상태 설정
        if (boxSelection.value === 'medium') {
            boxMediumOverload.style.display = 'block';
        }
        
        // 변경 이벤트 리스너
        boxSelection.addEventListener('change', function() {
            if (this.value === 'medium') {
                boxMediumOverload.style.display = 'block';
            } else {
                boxMediumOverload.style.display = 'none';
                // 체크박스 해제
                const checkbox = document.getElementById('box_medium_overload_check');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if ($showPouchSelection): ?>
    // 행낭선택 중형 선택 시 과적 체크박스 표시/숨김
    const pouchSelection = document.getElementById('pouch_selection');
    const pouchMediumOverload = document.getElementById('pouch_medium_overload');
    
    if (pouchSelection && pouchMediumOverload) {
        // 초기 상태 설정
        if (pouchSelection.value === 'medium') {
            pouchMediumOverload.style.display = 'block';
        }
        
        // 변경 이벤트 리스너
        pouchSelection.addEventListener('change', function() {
            if (this.value === 'medium') {
                pouchMediumOverload.style.display = 'block';
            } else {
                pouchMediumOverload.style.display = 'none';
                // 체크박스 해제
                const checkbox = document.getElementById('pouch_medium_overload_check');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>

