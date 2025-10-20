<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
        <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                    <input type="hidden" name="service_type" value="life-driver">
                    <input type="hidden" name="service_name" value="대리운전">
                    
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
            </div>
        </div>
        
        <!-- 가운데: 대리운전 전용 정보 (배송수단, 물품종류, 전달사항) -->
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
                                    <input type="radio" name="vehicleType" value="car" <?= old('vehicleType', 'car') === 'car' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/47.png') ?>" alt="승용차" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">승용차</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="taxi" <?= old('vehicleType') === 'taxi' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/91.png') ?>" alt="택시" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">택시</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="driver" <?= old('vehicleType') === 'driver' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/112.png') ?>" alt="운전기사" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">운전기사</span>
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
                            <label for="driverType" class="block text-xs font-medium text-gray-600">대리운전 유형 *</label>
                            <select id="driverType" name="driverType" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                                <option value="">대리운전 유형을 선택하세요</option>
                                <option value="standard" <?= old('driverType') === 'standard' ? 'selected' : '' ?>>일반대리운전</option>
                                <option value="premium" <?= old('driverType') === 'premium' ? 'selected' : '' ?>>프리미엄대리운전</option>
                                <option value="luxury" <?= old('driverType') === 'luxury' ? 'selected' : '' ?>>럭셔리대리운전</option>
                                <option value="van" <?= old('driverType') === 'van' ? 'selected' : '' ?>>밴대리운전</option>
                                <option value="bus" <?= old('driverType') === 'bus' ? 'selected' : '' ?>>버스대리운전</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="vehicleType" class="block text-xs font-medium text-gray-600">차량 유형 *</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="vehicleType" value="sedan" <?= old('vehicleType', 'sedan') === 'sedan' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">세단</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="vehicleType" value="suv" <?= old('vehicleType') === 'suv' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">SUV</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="vehicleType" value="van" <?= old('vehicleType') === 'van' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">밴</span>
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
                            <label for="itemType" class="block text-xs font-medium text-gray-600">물품종류 *</label>
                            <input type="text" id="itemType" name="itemType" value="<?= old('itemType', '승객') ?>" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                대리운전 요금 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                대리운전 안전 안내
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
