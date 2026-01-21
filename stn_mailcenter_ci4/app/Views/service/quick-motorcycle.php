<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="quick-motorcycle">
        <input type="hidden" name="service_name" value="오토바이(소화물)">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="flex-1 w-full min-w-0">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 오토바이 전용 정보 (배송수단, 물품종류, 전달사항) -->
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
                        
                        <?= $this->include('forms/quick-delivery-options-section') ?>
                    </div>
                </section>
            </div>
            
            <!-- 물품종류 -->
            <?= $this->include('forms/item-type-section', [
                'itemTypeName' => 'item_type',
                'itemTypeId' => 'item_type',
                'defaultValue' => '서류',
                'showBoxSelection' => true,
                'showPouchSelection' => true,
                'showShoppingBagSelection' => true,
                'showOverloadCheckbox' => true,
                'boxGuideButtonText' => '박스 규격 안내',
                'pouchGuideButtonText' => '행낭 규격 안내'
            ]) ?>
            
            <!-- 전달사항 -->
            <?= $this->include('forms/delivery-instructions-section', [
                'fieldName' => 'special_instructions',
                'fieldId' => 'special_instructions',
                'placeholder' => '전달하실 내용을 입력하세요.'
            ]) ?>
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