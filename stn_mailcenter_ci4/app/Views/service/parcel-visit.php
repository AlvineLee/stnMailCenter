<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="parcel-visit">
        <input type="hidden" name="service_name" value="방문택배">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="flex-1 w-full min-w-0">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 방문택배 전용 정보 (배송수단, 물품종류, 전달사항) -->
            <div class="flex-1 w-full min-w-0">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
            <!-- 배송수단 -->
            <div class="mb-2">
                <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">배송수단</h2>
                    <div class="space-y-3">
                        <!-- 상단 액션 버튼들 -->
                        <div class="space-y-2">
                            <div class="flex flex-col space-y-2">
                                <button type="button" class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm font-medium text-blue-600 hover:bg-gray-50 transition-colors">
                                    QR코드 등록
                                </button>
                                <button type="button" class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm font-medium text-blue-600 hover:bg-gray-50 transition-colors">
                                    바코드 등록
                                </button>
                                <button type="button" id="multiOrderBtn" class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                    멀티오더 생성 등록
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- 물품종류 -->
            <?= $this->include('forms/item-type-section', [
                'itemTypeName' => 'itemType',
                'itemTypeId' => 'itemType',
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
                'fieldName' => 'deliveryInstructions',
                'fieldId' => 'deliveryInstructions',
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

<?= $this->include('forms/multi-order-modal', ['service_name' => '방문택배']) ?>

<!-- 주문 폼 유효성 검사 스크립트 -->
<script src="<?= base_url('assets/js/order-form-validation.js') ?>"></script>


<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
