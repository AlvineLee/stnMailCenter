<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="w-full flex gap-4 px-4">
        <!-- 왼쪽: 폼 영역 -->
        <div class="flex-1">
            <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                <input type="hidden" name="service_type" value="quick-motorcycle">
                <input type="hidden" name="service_name" value="오토바이(소화물)">
                
                <!-- 1. 공통 폼 (주문자정보, 출발지정보, 도착지정보) -->
                <?= $this->include('forms/common-form') ?>
                
                <!-- 2. 오토바이 배송정보 -->
                <div class="mb-4">
                    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                        <h2 class="text-base font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">기타정보</h2>
                        
                        <!-- 배송수단 표시 -->
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600 mb-2">배송수단</h3>
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center space-x-2 bg-white rounded-lg p-3 border border-gray-200">
                                    <img src="/assets/icons/motorcycle.png" alt="오토바이" class="w-8 h-8">
                                    <span class="text-sm font-medium text-gray-700">오토바이</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                            <!-- 배송형태 (4/12) -->
                            <div class="lg:col-span-4">
                                <h3 class="text-sm font-medium text-gray-600 mb-2">배송형태</h3>
                                <div class="flex space-x-2">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="deliveryType" value="general" <?= old('deliveryType', 'general') === 'general' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-sm font-medium text-gray-700">일반</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="deliveryType" value="express" class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-sm font-medium text-gray-700">급송</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- 배송방법 (4/12) -->
                            <div class="lg:col-span-4">
                                <h3 class="text-sm font-medium text-gray-600 mb-2">배송방법</h3>
                                <div class="flex space-x-2">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="deliveryMethod" value="oneway" <?= old('deliveryMethod', 'oneway') === 'oneway' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-sm font-medium text-gray-700">편도</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="deliveryMethod" value="roundtrip" class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-sm font-medium text-gray-700">왕복</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="radio" name="deliveryMethod" value="via" class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-sm font-medium text-gray-700">경유</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- 물품종류 (4/12) -->
                            <div class="lg:col-span-4">
                                <h3 class="text-sm font-medium text-gray-600 mb-2">물품종류</h3>
                                <div class="flex space-x-2 mb-2">
                                    <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded text-xs font-medium transition-colors">박스 규격 안내</button>
                                    <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded text-xs font-medium transition-colors">행낭 규격 안내</button>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <input type="radio" name="itemType" value="document" <?= old('itemType', 'document') === 'document' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                        <span class="text-sm font-medium text-gray-700">서류</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">박스선택</label>
                                            <select name="boxType" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                                <option value="">선택</option>
                                                <option value="small">소형</option>
                                                <option value="medium">중형</option>
                                                <option value="large">대형</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">개수</label>
                                            <input type="number" name="boxQuantity" value="<?= old('boxQuantity', '0') ?>" min="0" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">행낭선택</label>
                                            <select name="pouchType" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                                <option value="">선택</option>
                                                <option value="small">소형</option>
                                                <option value="medium">중형</option>
                                                <option value="large">대형</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">개수</label>
                                            <input type="number" name="pouchQuantity" value="<?= old('pouchQuantity', '0') ?>" min="0" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">쇼핑백선</label>
                                            <select name="shoppingBagType" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                                <option value="">선택</option>
                                                <option value="small">소형</option>
                                                <option value="medium">중형</option>
                                                <option value="large">대형</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="block text-xs font-medium text-gray-600">개수</label>
                                            <input type="number" name="shoppingBagQuantity" value="<?= old('shoppingBagQuantity', '0') ?>" min="0" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 전달사항 -->
                        <div class="mt-4">
                            <h3 class="text-sm font-medium text-gray-600 mb-2">전달사항</h3>
                            <textarea name="specialInstructions" placeholder="전달사항을 입력해주세요" 
                                      class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent h-20 resize-none bg-white"><?= old('specialInstructions') ?></textarea>
                        </div>
                    </section>
                </div>
                
                <!-- 3. 지급구분 (공통) -->
                <div class="mb-4">
                    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                        <h2 class="text-base font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">지급구분</h2>
                        <div class="flex space-x-2">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="paymentType" value="cod_cash" class="text-gray-600 focus:ring-gray-500">
                                <span class="text-sm font-medium text-gray-700">착불(현금)</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="paymentType" value="prepaid_cash" <?= old('paymentType', 'prepaid_cash') === 'prepaid_cash' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                                <span class="text-sm font-medium text-gray-700">선불(현금)</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="paymentType" value="transfer" class="text-gray-600 focus:ring-gray-500">
                                <span class="text-sm font-medium text-gray-700">송금</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="radio" name="paymentType" value="credit" class="text-gray-600 focus:ring-gray-500">
                                <span class="text-sm font-medium text-gray-700">신용거래</span>
                            </label>
                        </div>
                    </section>
                </div>
                
            <?= form_close() ?>
        </div>
        
        <!-- 오른쪽: 제출 버튼 -->
        <div class="w-64">
            <div class="sticky top-4">
                <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <button type="submit" form="orderForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">오토바이(소화물) 주문접수</button>
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors">취소</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
