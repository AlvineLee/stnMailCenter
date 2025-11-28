<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'display: contents;']) ?>
        <input type="hidden" name="service_type" value="mailroom">
        <input type="hidden" name="service_name" value="메일룸서비스">
        
        <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
            <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
            <div class="w-full lg:w-1/3">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                    <!-- 운송장 바코드 등록 (메일룸 서비스 전용) -->
                    <div class="mb-3">
                        <section class="bg-yellow-50 rounded-lg shadow-sm border border-yellow-200 p-3">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3 min-w-0">
                                <label for="tracking_barcode" class="text-sm font-semibold text-gray-700 whitespace-nowrap flex-shrink-0">
                                    운송장 바코드등록
                                </label>
                                <input 
                                    type="text" 
                                    id="tracking_barcode" 
                                    name="tracking_barcode" 
                                    value="<?= old('tracking_barcode', '') ?>" 
                                    placeholder="바코드를 스캔하세요"
                                    autocomplete="off"
                                    class="flex-1 min-w-0 w-full sm:w-auto px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 bg-white transition-colors"
                                >
                            </div>
                        </section>
                    </div>
                    
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
                </div>
            </div>
        
            <!-- 가운데: 메일룸 전용 정보 (배송수단, 물품종류, 전달사항) -->
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
                                    <input type="radio" name="vehicleType" value="envelope" <?= old('vehicleType', 'envelope') === 'envelope' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/18.png') ?>" alt="우편물" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">우편물</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="mailbox" <?= old('vehicleType') === 'mailbox' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/66.png') ?>" alt="우체통" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">우체통</span>
                                    </div>
                                </label>
                                <label class="vehicle-option cursor-pointer">
                                    <input type="radio" name="vehicleType" value="document" <?= old('vehicleType') === 'document' ? 'checked' : '' ?> class="hidden">
                                    <div class="vehicle-card p-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-all duration-200 flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 mb-1 flex items-center justify-center">
                                            <img src="<?= base_url('assets/icons/67.png') ?>" alt="서류" class="w-12 h-12 object-contain">
                                        </div>
                                        <span class="text-xs font-medium text-gray-700">서류</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <?= $this->include('forms/delivery-type-method-section', [
                            'deliveryTypeName' => 'deliveryType',
                            'deliveryMethodName' => 'deliveryMethod'
                        ]) ?>
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
                            <input type="text" id="itemType" name="itemType" value="<?= old('itemType', '우편물') ?>" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                우편물 규격 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                등기우편 안내
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

<!-- 운송장 바코드 등록 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const barcodeInput = document.getElementById('tracking_barcode');
    
    if (barcodeInput) {
        // 페이지 로드 시 바코드 입력 필드에 자동 포커스
        setTimeout(function() {
            barcodeInput.focus();
        }, 100);
        
        // 바코드 리더기 지원: 빠른 입력 감지 및 Enter 키 처리
        let barcodeTimer = null;
        let barcodeValue = '';
        
        barcodeInput.addEventListener('keydown', function(e) {
            // Enter 키가 눌리면 바코드 입력 완료로 간주
            if (e.key === 'Enter') {
                e.preventDefault();
                // 바코드 값이 있으면 처리
                if (barcodeInput.value.trim()) {
                    // console.log('바코드 스캔 완료:', barcodeInput.value);
                    // 여기에 바코드 처리 로직 추가 가능
                }
            }
        });
        
        // 바코드 리더기는 보통 빠르게 연속 입력하므로
        // 입력이 멈춘 후 일정 시간이 지나면 자동으로 처리할 수도 있음
        barcodeInput.addEventListener('input', function(e) {
            clearTimeout(barcodeTimer);
            
            // 입력이 멈춘 후 500ms 후에 자동 처리 (선택사항)
            barcodeTimer = setTimeout(function() {
                if (barcodeInput.value.trim().length > 5) { // 최소 길이 체크
                    // console.log('바코드 자동 인식:', barcodeInput.value);
                }
            }, 500);
        });
        
        // 다른 필드로 이동했다가 돌아오면 다시 포커스 (선택사항)
        barcodeInput.addEventListener('blur', function() {
            // 필요시 자동으로 다시 포커스
            // setTimeout(function() {
            //     barcodeInput.focus();
            // }, 100);
        });
    }
});
</script>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
