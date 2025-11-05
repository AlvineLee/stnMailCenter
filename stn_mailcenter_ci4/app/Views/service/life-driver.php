<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="life-driver">
        <input type="hidden" name="service_name" value="대리운전">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="w-full lg:w-1/3">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 대리운전 전용 정보 (기타 정보, 요금/거리 합계, 전달사항) -->
            <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
            <!-- 기타 정보 -->
            <div class="mb-2">
                <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">기타 정보</h2>
                    <div class="space-y-3">
                        <!-- 콜타입 -->
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-600">콜타입 *</label>
                            <div class="flex gap-2">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="callType" value="driver" <?= old('callType', 'driver') === 'driver' ? 'checked' : '' ?> class="hidden">
                                    <div class="p-2 border-2 rounded-md text-center transition-all duration-200 <?= old('callType', 'driver') === 'driver' ? 'border-blue-500 bg-blue-50 text-blue-700 font-semibold' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?>">
                                        <span class="text-sm">대리</span>
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="callType" value="consignment" <?= old('callType') === 'consignment' ? 'checked' : '' ?> class="hidden">
                                    <div class="p-2 border-2 rounded-md text-center transition-all duration-200 <?= old('callType') === 'consignment' ? 'border-blue-500 bg-blue-50 text-blue-700 font-semibold' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?>">
                                        <span class="text-sm">탁송</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- 요금/거리 합계 -->
            <div class="mb-2">
                <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
                    <div class="flex items-center justify-between mb-2 pb-1 border-b border-gray-300">
                        <h2 class="text-sm font-semibold text-gray-700">요금/거리 합계</h2>
                        <button type="button" class="px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded transition-colors">
                            안내
                        </button>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <!-- 합계 -->
                        <div class="border border-dotted border-gray-300 rounded p-2">
                            <div class="flex flex-col gap-1">
                                <label for="total_fare" class="text-xs font-medium text-gray-600">합계</label>
                                <div class="flex items-center gap-1">
                                    <input type="text" id="total_fare" name="total_fare" value="<?= old('total_fare', '0') ?>" readonly
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md bg-gray-100 text-gray-700 text-right">
                                    <span class="text-xs text-gray-500">원</span>
                                </div>
                            </div>
                        </div>
                        <!-- 거리 -->
                        <div class="border border-dotted border-gray-300 rounded p-2">
                            <div class="flex flex-col gap-1">
                                <label for="distance" class="text-xs font-medium text-gray-600">거리</label>
                                <div class="flex items-center gap-1">
                                    <input type="text" id="distance" name="distance" value="<?= old('distance', '0.0') ?>" readonly
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md bg-gray-100 text-gray-700 text-right">
                                    <span class="text-xs text-gray-500">km</span>
                                </div>
                            </div>
                        </div>
                        <!-- 현금 -->
                        <div class="border border-dotted border-gray-300 rounded p-2">
                            <div class="flex flex-col gap-1">
                                <label for="cash_fare" class="text-xs font-medium text-gray-600">현금</label>
                                <div class="flex items-center gap-1">
                                    <input type="text" id="cash_fare" name="cash_fare" value="<?= old('cash_fare', '0') ?>" readonly
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md bg-gray-100 text-gray-700 text-right">
                                    <span class="text-xs text-gray-500">원</span>
                                </div>
                            </div>
                        </div>
                        <!-- 후불 -->
                        <div class="border border-dotted border-gray-300 rounded p-2">
                            <div class="flex flex-col gap-1">
                                <label for="postpaid_fare" class="text-xs font-medium text-gray-600">후불</label>
                                <div class="flex items-center gap-1">
                                    <input type="text" id="postpaid_fare" name="postpaid_fare" value="<?= old('postpaid_fare', '0') ?>" readonly
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md bg-gray-100 text-gray-700 text-right">
                                    <span class="text-xs text-gray-500">원</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- 전달사항 -->
            <?= $this->include('forms/delivery-instructions-section', [
                'fieldName' => 'deliveryInstructions',
                'fieldId' => 'deliveryInstructions',
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

<script>
// 콜타입 선택 시 버튼 스타일 변경
document.addEventListener('DOMContentLoaded', function() {
    const callTypeInputs = document.querySelectorAll('input[name="callType"]');
    
    callTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            // 모든 라벨의 스타일 초기화
            callTypeInputs.forEach(inp => {
                const label = inp.closest('label');
                const div = label.querySelector('div');
                div.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-700', 'font-semibold');
                div.classList.add('border-gray-300', 'bg-white', 'text-gray-700');
            });
            
            // 선택된 라벨에 스타일 적용
            const selectedLabel = this.closest('label');
            const selectedDiv = selectedLabel.querySelector('div');
            selectedDiv.classList.remove('border-gray-300', 'bg-white', 'text-gray-700');
            selectedDiv.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-700', 'font-semibold');
        });
    });
});
</script>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
