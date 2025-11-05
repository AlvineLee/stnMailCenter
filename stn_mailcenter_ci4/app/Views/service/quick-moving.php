<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="quick-moving">
        <input type="hidden" name="service_name" value="이사짐화물(소형)">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="w-full lg:w-1/3">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 이사짐 전용 정보 (배송수단, 물품종류, 전달사항) -->
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
                                    <input type="radio" name="delivery_method" value="damas" <?= old('delivery_method', 'damas') === 'damas' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/201.png') ?>" alt="다마스" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">다마스</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="delivery_method" value="labo" <?= old('delivery_method') === 'labo' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/202.png') ?>" alt="라보" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">라보</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="delivery_method" value="truck" <?= old('delivery_method') === 'truck' ? 'checked' : '' ?> class="hidden" onchange="toggleVehicleDetails()">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/25.png') ?>" alt="트럭" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">트럭</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 트럭 선택 시 상세 항목 -->
                        <div id="truckDetails" class="space-y-3" style="display: none;">
                            <!-- 차량무게 -->
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <label class="block text-xs font-medium text-gray-600 mb-2">차량무게</label>
                                <div class="grid grid-cols-4 gap-1">
                                    <?php foreach ($truck_capacities as $key => $value): ?>
                                    <label class="flex items-center space-x-1 cursor-pointer p-1 hover:bg-gray-50 rounded">
                                        <input type="radio" name="truck_capacity" value="<?= $key ?>" <?= old('truck_capacity') === $key ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-xs text-gray-700"><?= $value ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- 차량종류 -->
                            <div class="bg-white border border-gray-200 rounded-lg p-3">
                                <label class="block text-xs font-medium text-gray-600 mb-2">차량종류</label>
                                <div class="grid grid-cols-4 gap-1">
                                    <?php foreach ($truck_body_types as $key => $value): ?>
                                    <label class="flex items-center space-x-1 cursor-pointer p-1 hover:bg-gray-50 rounded">
                                        <input type="radio" name="truck_body_type" value="<?= $key ?>" <?= old('truck_body_type') === $key ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-xs text-gray-700"><?= $value ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
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
                'defaultValue' => '이사짐',
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
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= $this->include('forms/common-paytype') ?>
            </div>
        </div> 
        </div>
    <?= form_close() ?>
</div>

<!-- 주문 폼 유효성 검사 스크립트 -->
<script src="<?= base_url('assets/js/order-form-validation.js') ?>"></script>

<style>
/* 배송수단 선택 시 포커싱 스타일 */
.vehicle-option input[type="radio"]:checked + .vehicle-card {
    border-color: #3b82f6;
    background-color: #eff6ff;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.vehicle-option input[type="radio"]:checked + .vehicle-card img {
    transform: scale(1.1);
}

.vehicle-card {
    transition: all 0.2s ease-in-out;
}

.vehicle-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>

<script>
// 배송수단 변경 시 트럭 상세 항목 표시/숨김
function toggleVehicleDetails() {
    const truckDetails = document.getElementById('truckDetails');
    const selectedVehicle = document.querySelector('input[name="delivery_method"]:checked');
    
    if (selectedVehicle && selectedVehicle.value === 'truck') {
        truckDetails.style.display = 'block';
    } else {
        truckDetails.style.display = 'none';
        // 트럭이 아닌 경우 차량무게, 차량종류 값 초기화
        document.querySelectorAll('input[name="truck_capacity"]').forEach(radio => radio.checked = false);
        document.querySelectorAll('input[name="truck_body_type"]').forEach(radio => radio.checked = false);
    }
}

// 페이지 로드 시 초기 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    toggleVehicleDetails();
    
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

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
