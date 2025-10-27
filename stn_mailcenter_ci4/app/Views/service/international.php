<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
        <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                    <input type="hidden" name="service_type" value="international">
                    <input type="hidden" name="service_name" value="해외특송서비스">
                    
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?php $service_type = 'international'; ?>
                    <?= $this->include('forms/common-form') ?>
            </div>
        </div>
        
        <!-- 가운데: 해외특송 전용 정보 (기타 정보, 전달사항) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
            <!-- 기타 정보 -->
            <div class="mb-2">
                <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">기타 정보</h2>
                    <div class="space-y-3">
                        <!-- VAT/TAX ID -->
                        <div class="space-y-1">
                            <label for="vat_tax_id" class="block text-xs font-medium text-gray-600">VAT/TAX ID</label>
                            <input type="text" id="vat_tax_id" name="vat_tax_id" value="<?= old('vat_tax_id') ?>" 
                                   placeholder="OSS 번호는 아래 세관신고 단계에서 입력해주세요."
                                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <!-- 멀티오더 생성 등록 버튼 -->
                        <div class="space-y-2">
                            <button type="button" id="multiOrderBtn" class="w-full bg-white border border-gray-300 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                멀티오더 생성 등록
                            </button>
                        </div>
                        
                        <!-- 물품 상세 정보 -->
                        <div class="space-y-2">
                            <h3 class="text-xs font-medium text-gray-600">물품 상세 정보</h3>
                            <div id="productItems">
                                <!-- 첫 번째 물품 행 -->
                                <div class="product-item border border-gray-200 rounded-md p-3 bg-white">
                                    <div class="grid grid-cols-3 gap-2 mb-2">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">발송품명</label>
                                            <input type="text" name="product_name[]" value="<?= old('product_name.0') ?>" 
                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">물품개수</label>
                                            <input type="number" name="product_quantity[]" value="<?= old('product_quantity.0') ?>" min="1"
                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">무게(kg)</label>
                                            <input type="number" name="product_weight[]" value="<?= old('product_weight.0') ?>" step="0.1" min="0"
                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">가로(cm)</label>
                                            <input type="number" name="product_width[]" value="<?= old('product_width.0') ?>" step="0.1" min="0"
                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">세로(cm)</label>
                                            <input type="number" name="product_length[]" value="<?= old('product_length.0') ?>" step="0.1" min="0"
                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">높이(cm)</label>
                                            <input type="number" name="product_height[]" value="<?= old('product_height.0') ?>" step="0.1" min="0"
                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">HS-code</label>
                                            <input type="text" name="product_hs_code[]" value="<?= old('product_hs_code.0') ?>" 
                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                    </div>
                                    <div class="mt-2 flex justify-end">
                                        <button type="button" class="remove-product-btn bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs font-medium transition-colors">
                                            행 삭제
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 포장종류 추가 버튼 -->
                            <button type="button" id="addProductBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                                포장종류 추가
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
                        <textarea id="deliveryInstructions" name="deliveryInstructions" placeholder="전달사항을 입력해주세요" 
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

<?= $this->include('forms/multi-order-modal', ['service_name' => '해외특송']) ?>

<script>
// 해외특송 서비스 JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const addProductBtn = document.getElementById('addProductBtn');
    const productItems = document.getElementById('productItems');
    const multiOrderBtn = document.getElementById('multiOrderBtn');
    let productIndex = 1; // 첫 번째는 이미 있으므로 1부터 시작
    
    // 포장종류 추가 버튼 (이벤트 리스너 중복 방지)
    if (addProductBtn && !addProductBtn.hasAttribute('data-listener-added')) {
        addProductBtn.addEventListener('click', function() {
            const newProductItem = createProductItem(productIndex);
            productItems.appendChild(newProductItem);
            productIndex++;
            updateRemoveButtons(); // 버튼 상태 업데이트
        });
        addProductBtn.setAttribute('data-listener-added', 'true');
    }
    
    // 포장종류 행 생성 함수
    function createProductItem(index) {
        const div = document.createElement('div');
        div.className = 'product-item border border-gray-200 rounded-md p-3 bg-white mt-2';
        div.innerHTML = `
            <div class="grid grid-cols-3 gap-2 mb-2">
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600">발송품명</label>
                    <input type="text" name="product_name[]" value="" 
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600">물품개수</label>
                    <input type="number" name="product_quantity[]" value="1" min="1"
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600">무게(kg)</label>
                    <input type="number" name="product_weight[]" value="" step="0.1" min="0"
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
            </div>
            <div class="grid grid-cols-4 gap-2">
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600">가로(cm)</label>
                    <input type="number" name="product_width[]" value="" step="0.1" min="0"
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600">세로(cm)</label>
                    <input type="number" name="product_length[]" value="" step="0.1" min="0"
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600">높이(cm)</label>
                    <input type="number" name="product_height[]" value="" step="0.1" min="0"
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600">HS-code</label>
                    <input type="text" name="product_hs_code[]" value="" 
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                </div>
            </div>
            <div class="mt-2 flex justify-end">
                <button type="button" class="remove-product-btn bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs font-medium transition-colors">
                    행 삭제
                </button>
            </div>
        `;
        
        // 행 삭제 버튼 이벤트 추가
        const removeBtn = div.querySelector('.remove-product-btn');
        removeBtn.addEventListener('click', function() {
            // 최소 1개는 유지
            if (document.querySelectorAll('.product-item').length > 1) {
                div.remove();
                updateRemoveButtons(); // 삭제 후 버튼 상태 업데이트
            } else {
                alert('최소 1개의 물품 정보는 필요합니다.');
            }
        });
        
        return div;
    }
    
    // 기존 행 삭제 버튼 이벤트 추가 (중복 방지)
    document.querySelectorAll('.remove-product-btn').forEach(btn => {
        if (!btn.hasAttribute('data-listener-added')) {
            btn.addEventListener('click', function() {
                // 최소 1개는 유지
                if (document.querySelectorAll('.product-item').length > 1) {
                    this.closest('.product-item').remove();
                    updateRemoveButtons(); // 삭제 후 버튼 상태 업데이트
                } else {
                    alert('최소 1개의 물품 정보는 필요합니다.');
                }
            });
            btn.setAttribute('data-listener-added', 'true');
        }
    });
    
    // 페이지 로드 시 초기 상태 설정
    updateRemoveButtons();
    
    // 행 삭제 버튼 상태 업데이트 함수
    function updateRemoveButtons() {
        const productItems = document.querySelectorAll('.product-item');
        const removeButtons = document.querySelectorAll('.remove-product-btn');
        
        removeButtons.forEach(btn => {
            if (productItems.length <= 1) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.classList.remove('hover:bg-gray-200');
                btn.style.pointerEvents = 'none'; // 클릭 이벤트 완전 차단
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.classList.add('hover:bg-gray-200');
                btn.style.pointerEvents = 'auto'; // 클릭 이벤트 허용
            }
        });
    }
});
</script>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
