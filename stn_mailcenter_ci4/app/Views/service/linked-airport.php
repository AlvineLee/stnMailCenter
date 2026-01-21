<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="linked-airport">
        <input type="hidden" name="service_name" value="공항">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="flex-1 w-full min-w-0">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 공항 전용 정보 (배송수단, 물품종류, 전달사항) -->
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
                                    <input type="radio" name="vehicleType" value="airplane" <?= old('vehicleType', 'airplane') === 'airplane' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/12.png') ?>" alt="항공" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">항공</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="airport" <?= old('vehicleType') === 'airport' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/13.png') ?>" alt="공항" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">공항</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="truck" <?= old('vehicleType') === 'truck' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/25.png') ?>" alt="화물트럭" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">화물트럭</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <?= $this->include('forms/delivery-type-method-section', [
                            'deliveryTypeName' => 'deliveryType',
                            'deliveryMethodName' => 'deliveryMethod'
                        ]) ?>
                        
                        <div class="space-y-2">
                            <label for="airportRoute" class="block text-xs font-medium text-gray-600">공항 노선 *</label>
                            <select id="airportRoute" name="airportRoute" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                                <option value="">노선을 선택하세요</option>
                                <option value="icn-gimpo" <?= old('airportRoute') === 'icn-gimpo' ? 'selected' : '' ?>>인천-김포</option>
                                <option value="icn-gimhae" <?= old('airportRoute') === 'icn-gimhae' ? 'selected' : '' ?>>인천-김해</option>
                                <option value="icn-jeju" <?= old('airportRoute') === 'icn-jeju' ? 'selected' : '' ?>>인천-제주</option>
                                <option value="icn-cheongju" <?= old('airportRoute') === 'icn-cheongju' ? 'selected' : '' ?>>인천-청주</option>
                                <option value="icn-daegu" <?= old('airportRoute') === 'icn-daegu' ? 'selected' : '' ?>>인천-대구</option>
                                <option value="icn-muan" <?= old('airportRoute') === 'icn-muan' ? 'selected' : '' ?>>인천-무안</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="flightType" class="block text-xs font-medium text-gray-600">항공편 유형 *</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="flightType" value="domestic" <?= old('flightType', 'domestic') === 'domestic' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">국내선</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="flightType" value="international" <?= old('flightType') === 'international' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                    <span class="text-sm font-medium text-gray-700">국제선</span>
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
                            <input type="text" id="itemType" name="itemType" value="<?= old('itemType', '소화물') ?>" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                항공 운임 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                위탁 수하물 규정
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
