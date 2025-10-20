<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
        <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                    <input type="hidden" name="service_type" value="quick-vehicle">
                    <input type="hidden" name="service_name" value="차량(화물)">
                    
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
            </div>
        </div>
        
        <!-- 가운데: 차량 전용 정보 (배송수단, 물품종류, 전달사항) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
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
                                    <input type="radio" name="delivery_method" value="truck" <?= old('delivery_method', 'truck') === 'truck' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/25.png') ?>" alt="트럭" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">트럭</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="delivery_method" value="van" <?= old('delivery_method') === 'van' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/50.png') ?>" alt="밴" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">밴</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="delivery_method" value="cargo" <?= old('delivery_method') === 'cargo' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/74.png') ?>" alt="화물차" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">화물차</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 트럭 선택 시 상세 항목 -->
                        <div id="truckDetails" class="space-y-3" style="display: none;">
                            <!-- 차량무게 -->
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <label class="block text-xs font-medium text-gray-600 mb-2">차량무게 *</label>
                                <div class="grid grid-cols-4 gap-1">
                                    <?php 
                                    $truck_capacities = \App\Config\TruckOptions::getCapacities();
                                    foreach ($truck_capacities as $key => $value): 
                                    ?>
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="truck_capacity" value="<?= $key ?>" class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-xs text-gray-700"><?= $value ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- 차량종류 -->
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <label class="block text-xs font-medium text-gray-600 mb-2">차량종류 *</label>
                                <div class="grid grid-cols-4 gap-1">
                                    <?php 
                                    $truck_body_types = \App\Config\TruckOptions::getBodyTypes();
                                    foreach ($truck_body_types as $key => $value): 
                                    ?>
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="truck_body_type" value="<?= $key ?>" class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-xs text-gray-700"><?= $value ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="urgency_level" class="block text-xs font-medium text-gray-600">긴급도 *</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="urgency_level" value="normal" <?= old('urgency_level', 'normal') === 'normal' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">일반</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="urgency_level" value="urgent" <?= old('urgency_level') === 'urgent' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">긴급</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="urgency_level" value="super_urgent" <?= old('urgency_level') === 'super_urgent' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">초긴급</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="delivery_route" class="block text-xs font-medium text-gray-600">배송경로 *</label>
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
                </section>
            </div>
            
            <!-- 물품종류 -->
            <div class="mb-2">
                <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">물품종류</h2>
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label for="item_type" class="block text-xs font-medium text-gray-600">물품종류 *</label>
                            <input type="text" id="item_type" name="item_type" value="<?= old('item_type', '화물') ?>" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                박스 규격 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                행낭 규격 안내
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-5 gap-2">
                            <div class="space-y-1">
                                <label for="box_selection" class="block text-xs font-medium text-gray-600">박스선택</label>
                                <select id="box_selection" name="box_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                    <option value="">선택</option>
                                    <option value="small" <?= old('box_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                                    <option value="medium" <?= old('box_selection') === 'medium' ? 'selected' : '' ?>>중형</option>
                                    <option value="large" <?= old('box_selection') === 'large' ? 'selected' : '' ?>>대형</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label for="box_quantity" class="block text-xs font-medium text-gray-600">개수</label>
                                <input type="number" id="box_quantity" name="box_quantity" value="<?= old('box_quantity', '0') ?>" min="0"
                                       class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            </div>
                            <div class="space-y-1">
                                <label for="pouch_selection" class="block text-xs font-medium text-gray-600">행낭선택</label>
                                <select id="pouch_selection" name="pouch_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                    <option value="">선택</option>
                                    <option value="small" <?= old('pouch_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                                    <option value="medium" <?= old('pouch_selection') === 'medium' ? 'selected' : '' ?>>중형</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label for="pouch_quantity" class="block text-xs font-medium text-gray-600">개수</label>
                                <input type="number" id="pouch_quantity" name="pouch_quantity" value="<?= old('pouch_quantity', '0') ?>" min="0"
                                       class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            </div>
                            <div class="space-y-1">
                                <label for="shopping_bag_selection" class="block text-xs font-medium text-gray-600">쇼핑백선택</label>
                                <select id="shopping_bag_selection" name="shopping_bag_selection" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                    <option value="">선택</option>
                                    <option value="small" <?= old('shopping_bag_selection') === 'small' ? 'selected' : '' ?>>소형</option>
                                    <option value="large" <?= old('shopping_bag_selection') === 'large' ? 'selected' : '' ?>>대형</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- 전달사항 -->
            <div class="mb-2">
                <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">전달사항</h2>
                    <div class="space-y-1">
                        <p class="text-xs text-gray-600 font-medium">전달사항을 입력해주세요</p>
                        <textarea id="special_instructions" name="special_instructions" placeholder="전달하실 내용을 입력하세요." 
                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent h-20 resize-none bg-white"><?= old('special_instructions') ?></textarea>
                    </div>
                </section>
            </div>
            </div>
        </div>
        
        <!-- 오른쪽: 지급구분 -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= $this->include('forms/common-paytype') ?>
            </div>
        </div> 
    </div>
    
    <?= form_close() ?>
</div>
<?= $this->endSection() ?>

<script>
// 배송수단 변경 시 트럭 상세 항목 표시/숨김
function toggleVehicleDetails() {
    const truckDetails = document.getElementById('truckDetails');
    const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
    
    if (selectedVehicle && selectedVehicle.value === 'truck') {
        truckDetails.style.display = 'block';
    } else {
        truckDetails.style.display = 'none';
    }
}

// 페이지 로드 시 초기 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    toggleVehicleDetails();
});
</script>

<?= $this->include('layouts/footer') ?>
