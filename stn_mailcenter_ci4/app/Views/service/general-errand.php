<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="general-errand">
        <input type="hidden" name="service_name" value="개인심부름">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="flex-1 w-full min-w-0">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 개인심부름 전용 정보 (배송수단, 물품종류, 전달사항) -->
            <div class="flex-1 w-full min-w-0">
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
                                    <input type="radio" name="vehicleType" value="errand" <?= old('vehicleType', 'errand') === 'errand' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/1.png') ?>" alt="심부름" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">심부름</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="motorcycle" <?= old('vehicleType') === 'motorcycle' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/49.png') ?>" alt="오토바이" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">오토바이</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="car" <?= old('vehicleType') === 'car' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/47.png') ?>" alt="승용차" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">승용차</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <?= $this->include('forms/delivery-type-method-section', [
                            'deliveryTypeName' => 'deliveryType',
                            'deliveryMethodName' => 'deliveryMethod'
                        ]) ?>
                        
                        <div class="space-y-2">
                            <label for="errandType" class="block text-xs font-medium text-gray-600">심부름 유형 *</label>
                            <select id="errandType" name="errandType" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                                <option value="">심부름 유형을 선택하세요</option>
                                <option value="delivery" <?= old('errandType') === 'delivery' ? 'selected' : '' ?>>물건 전달</option>
                                <option value="pickup" <?= old('errandType') === 'pickup' ? 'selected' : '' ?>>물건 픽업</option>
                                <option value="purchase" <?= old('errandType') === 'purchase' ? 'selected' : '' ?>>물건 구매</option>
                                <option value="payment" <?= old('errandType') === 'payment' ? 'selected' : '' ?>>결제 대행</option>
                                <option value="document" <?= old('errandType') === 'document' ? 'selected' : '' ?>>서류 제출</option>
                                <option value="other" <?= old('errandType') === 'other' ? 'selected' : '' ?>>기타</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="estimatedTime" class="block text-xs font-medium text-gray-600">예상 소요시간 *</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="estimatedTime" value="30min" <?= old('estimatedTime', '30min') === '30min' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">30분</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="estimatedTime" value="1hour" <?= old('estimatedTime') === '1hour' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">1시간</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="estimatedTime" value="2hours" <?= old('estimatedTime') === '2hours' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">2시간</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="estimatedTime" value="half_day" <?= old('estimatedTime') === 'half_day' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">반나절</span>
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
                            <input type="text" id="itemType" name="itemType" value="<?= old('itemType', '심부름물') ?>" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                심부름 요금 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                심부름 범위 안내
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
                        <textarea id="deliveryInstructions" name="deliveryInstructions" placeholder="전달하실 내용을 입력하세요." lang="ko"
                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent h-20 resize-none bg-white"><?= old('deliveryInstructions') ?></textarea>
                    </div>
                </section>
            </div>
            </div>
        </div>
        
        <!-- 오른쪽: 지급구분 -->
        <div class="w-full lg:w-64 flex-shrink-0 max-w-full box-border">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= $this->include('forms/common-paytype') ?>
            </div>
        </div> 
        </div>
    <?= form_close() ?>
</div>

<!-- 주문 폼 유효성 검사 스크립트 -->
<script src="<?= base_url('assets/js/order-form-validation.js') ?>"></script>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
