<!-- 배송형태 및 배송방법 -->
<?php
// 파라미터 기본값 설정
$deliveryTypeName = $deliveryTypeName ?? 'deliveryType';
$deliveryMethodName = $deliveryMethodName ?? 'deliveryMethod';
$deliveryTypeDefault = $deliveryTypeDefault ?? 'normal';
$deliveryMethodDefault = $deliveryMethodDefault ?? 'one_way';
$showExpressOption = $showExpressOption ?? true; // 배송형태에 '급송' 옵션 표시 여부
?>
<div class="space-y-3">
    <div class="space-y-2">
        <label for="<?= $deliveryTypeName ?>" class="block text-xs font-medium text-gray-600">배송형태 *</label>
        <div class="flex space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="<?= $deliveryTypeName ?>" value="normal" <?= old($deliveryTypeName, $deliveryTypeDefault) === 'normal' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">일반</span>
            </label>
            <?php if ($showExpressOption): ?>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="<?= $deliveryTypeName ?>" value="express" <?= old($deliveryTypeName) === 'express' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">급송</span>
            </label>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="space-y-2">
        <label for="<?= $deliveryMethodName ?>" class="block text-xs font-medium text-gray-600">배송방법 *</label>
        <div class="flex space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="<?= $deliveryMethodName ?>" value="one_way" <?= old($deliveryMethodName, $deliveryMethodDefault) === 'one_way' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">편도</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="<?= $deliveryMethodName ?>" value="round_trip" <?= old($deliveryMethodName) === 'round_trip' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">왕복</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="<?= $deliveryMethodName ?>" value="via" <?= old($deliveryMethodName) === 'via' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">경유</span>
            </label>
        </div>
    </div>
</div>

