<!-- 배송형태 및 배송방법 -->
<div class="space-y-3">
    <div class="space-y-2">
        <label for="urgency_level" class="block text-xs font-medium text-gray-600">배송형태 *</label>
        <div class="flex space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="urgency_level" value="normal" <?= old('urgency_level', 'normal') === 'normal' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">일반</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="urgency_level" value="urgent" <?= old('urgency_level') === 'urgent' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">긴급</span>
            </label>
        </div>
    </div>
    
    <div class="space-y-2">
        <label for="delivery_route" class="block text-xs font-medium text-gray-600">배송방법 *</label>
        <div class="flex space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="delivery_route" value="one_way" <?= old('delivery_route', 'one_way') === 'one_way' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">편도</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="delivery_route" value="round_trip" <?= old('delivery_route') === 'round_trip' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">왕복</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="delivery_route" value="via" <?= old('delivery_route') === 'via' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">경유</span>
            </label>
        </div>
    </div>
</div>

