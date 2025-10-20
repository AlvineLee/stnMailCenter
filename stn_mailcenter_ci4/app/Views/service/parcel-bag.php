<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
        <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                    <input type="hidden" name="service_type" value="parcel-bag">
                    <input type="hidden" name="service_name" value="행낭">
                    
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
            </div>
        </div>
        
        <!-- 가운데: 행낭 전용 정보 (배송수단, 물품종류, 전달사항) -->
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
                                    <input type="radio" name="vehicleType" value="bag" <?= old('vehicleType', 'bag') === 'bag' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/3.png') ?>" alt="행낭" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">행낭</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="truck" <?= old('vehicleType') === 'truck' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/25.png') ?>" alt="트럭" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">트럭</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="delivery" <?= old('vehicleType') === 'delivery' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/1.png') ?>" alt="배송원" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">배송원</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="deliveryType" class="block text-xs font-medium text-gray-600">배송형태 *</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="deliveryType" value="normal" <?= old('deliveryType', 'normal') === 'normal' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">일반</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="deliveryType" value="express" <?= old('deliveryType') === 'express' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">급송</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="deliveryMethod" class="block text-xs font-medium text-gray-600">배송방법 *</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="deliveryMethod" value="one_way" <?= old('deliveryMethod', 'one_way') === 'one_way' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">편도</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="deliveryMethod" value="round_trip" <?= old('deliveryMethod') === 'round_trip' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">왕복</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="deliveryMethod" value="via" <?= old('deliveryMethod') === 'via' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">경유</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="bagType" class="block text-xs font-medium text-gray-600">행낭 유형 *</label>
                            <select id="bagType" name="bagType" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                                <option value="">행낭 유형을 선택하세요</option>
                                <option value="small" <?= old('bagType') === 'small' ? 'selected' : '' ?>>소형 (5kg 이하)</option>
                                <option value="medium" <?= old('bagType') === 'medium' ? 'selected' : '' ?>>중형 (10kg 이하)</option>
                                <option value="large" <?= old('bagType') === 'large' ? 'selected' : '' ?>>대형 (15kg 이하)</option>
                                <option value="extra_large" <?= old('bagType') === 'extra_large' ? 'selected' : '' ?>>특대형 (20kg 이하)</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="bagMaterial" class="block text-xs font-medium text-gray-600">행낭 재질 *</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="bagMaterial" value="paper" <?= old('bagMaterial', 'paper') === 'paper' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">종이</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="bagMaterial" value="plastic" <?= old('bagMaterial') === 'plastic' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">비닐</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="bagMaterial" value="fabric" <?= old('bagMaterial') === 'fabric' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">천</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="deliverySchedule" class="block text-xs font-medium text-gray-600">배송 일정 *</label>
                            <select id="deliverySchedule" name="deliverySchedule" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                                <option value="">배송 일정을 선택하세요</option>
                                <option value="same_day" <?= old('deliverySchedule') === 'same_day' ? 'selected' : '' ?>>당일 배송</option>
                                <option value="next_day" <?= old('deliverySchedule') === 'next_day' ? 'selected' : '' ?>>익일 배송</option>
                                <option value="2_days" <?= old('deliverySchedule') === '2_days' ? 'selected' : '' ?>>2일 배송</option>
                                <option value="3_days" <?= old('deliverySchedule') === '3_days' ? 'selected' : '' ?>>3일 배송</option>
                                <option value="weekend" <?= old('deliverySchedule') === 'weekend' ? 'selected' : '' ?>>주말 배송</option>
                            </select>
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
                            <label for="itemType" class="block text-xs font-medium text-gray-600">물품종류 *</label>
                            <input type="text" id="itemType" name="itemType" value="<?= old('itemType', '소화물') ?>" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                행낭 요금 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                행낭 규격 안내
                            </button>
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
                        <textarea id="deliveryInstructions" name="deliveryInstructions" placeholder="전달하실 내용을 입력하세요." 
                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent h-20 resize-none bg-white"><?= old('deliveryInstructions') ?></textarea>
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
</div>

<?= form_close() ?>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
