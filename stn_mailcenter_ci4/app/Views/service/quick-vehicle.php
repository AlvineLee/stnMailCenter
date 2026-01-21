<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="quick-vehicle">
        <input type="hidden" name="service_name" value="차량(화물)">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="flex-1 w-full min-w-0">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 차량 전용 정보 (배송수단, 물품종류, 전달사항) -->
            <div class="flex-1 w-full min-w-0">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
            <!-- 배송수단 (공통 컴포넌트) -->
            <?= $this->include('forms/vehicle-selection-section', [
                'defaultVehicle' => 'damas',
                'truckCapacityStyle' => 'select' // quick-vehicle은 select 박스 방식
            ]) ?>
            
            <!-- 물품종류 -->
            <?= $this->include('forms/item-type-section', [
                'itemTypeName' => 'item_type',
                'itemTypeId' => 'item_type',
                'defaultValue' => '화물',
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

<!-- 상차방법/하차방법 선택 모달 -->
<?= $this->include('forms/loading-unloading-modal') ?>

<!-- 주문 폼 유효성 검사 스크립트 -->
<script src="<?= base_url('assets/js/order-form-validation.js') ?>"></script>

<?= $this->endSection() ?>

<script>
// 페이지 로드 시 초기 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    
    // 박스선택 중형 선택 시 과적 체크박스 표시/숨김
    const boxSelection = document.getElementById('box_selection');
    const boxMediumOverload = document.getElementById('box_medium_overload');
    
    if (boxSelection && boxMediumOverload) {
        if (boxSelection.value === 'medium') {
            boxMediumOverload.style.display = 'block';
        }
        
        boxSelection.addEventListener('change', function() {
            if (this.value === 'medium') {
                boxMediumOverload.style.display = 'block';
            } else {
                boxMediumOverload.style.display = 'none';
                const checkbox = document.getElementById('box_medium_overload_check');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    }
    
    // 행낭선택 중형 선택 시 과적 체크박스 표시/숨김
    const pouchSelection = document.getElementById('pouch_selection');
    const pouchMediumOverload = document.getElementById('pouch_medium_overload');
    
    if (pouchSelection && pouchMediumOverload) {
        if (pouchSelection.value === 'medium') {
            pouchMediumOverload.style.display = 'block';
        }
        
        pouchSelection.addEventListener('change', function() {
            if (this.value === 'medium') {
                pouchMediumOverload.style.display = 'block';
            } else {
                pouchMediumOverload.style.display = 'none';
                const checkbox = document.getElementById('pouch_medium_overload_check');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    }
});
</script>

<?= $this->include('layouts/footer') ?>
