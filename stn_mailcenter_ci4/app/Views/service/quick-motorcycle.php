<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
        <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                    <input type="hidden" name="service_type" value="quick-motorcycle">
                    <input type="hidden" name="service_name" value="오토바이(소화물)">
                    
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
            </div>
        </div>
        
        <!-- 가운데: 오토바이 전용 정보 (배송수단, 물품종류, 전달사항) -->
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
                                    <input type="radio" name="delivery_method" value="motorcycle" <?= old('delivery_method', 'motorcycle') === 'motorcycle' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/49.png') ?>" alt="오토바이" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">오토바이</span>
                                    </div>
                                </label>
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
                            <input type="text" id="item_type" name="item_type" value="<?= old('item_type', '서류') ?>" required
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

<?= $this->include('layouts/footer') ?>