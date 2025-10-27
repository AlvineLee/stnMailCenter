<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<div class="w-full flex flex-col">
    <!-- 메인 콘텐츠 영역 -->
    <div class="w-full flex flex-col lg:flex-row gap-4 flex-1">
        <!-- 왼쪽: 공통 폼 (주문자정보, 출발지, 도착지) -->
        <div class="w-full lg:w-1/3">
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4">
                <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                    <input type="hidden" name="service_type" value="parcel-same-day">
                    <input type="hidden" name="service_name" value="당일택배">
                    
                    <!-- 공통 폼 (주문자정보, 출발지, 도착지) -->
                    <?= $this->include('forms/common-form') ?>
            </div>
        </div>
        
        <!-- 가운데: 당일택배 전용 정보 (배송수단, 물품종류, 전달사항) -->
        <div class="w-full lg:w-1/3">
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
            <div class="mb-2">
                <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">물품종류</h2>
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label for="itemType" class="block text-xs font-medium text-gray-600">물품종류 *</label>
                            <input type="text" id="itemType" name="itemType" value="<?= old('itemType', '서류') ?>" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                박스 규격 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                행낭 규격 안내
                            </button>
                            <button type="button" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                쇼핑백 규격 안내
                            </button>
                        </div>
                        
                        <!-- 박스/행낭/쇼핑백 선택 -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="space-y-1">
                                <label class="block text-xs font-medium text-gray-600">박스선택/개수</label>
                                <div class="flex space-x-1">
                                    <select name="box_type" class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        <option value="">선택</option>
                                        <option value="small">소형</option>
                                        <option value="medium">중형</option>
                                        <option value="large">대형</option>
                                    </select>
                                    <input type="number" name="box_quantity" value="1" min="1" 
                                           class="w-16 px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-xs font-medium text-gray-600">행낭선택/개수</label>
                                <div class="flex space-x-1">
                                    <select name="bag_type" class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        <option value="">선택</option>
                                        <option value="small">소형</option>
                                        <option value="medium">중형</option>
                                        <option value="large">대형</option>
                                    </select>
                                    <input type="number" name="bag_quantity" value="1" min="1" 
                                           class="w-16 px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-xs font-medium text-gray-600">쇼핑백선택/개수</label>
                                <div class="flex space-x-1">
                                    <select name="shopping_bag_type" class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                        <option value="">선택</option>
                                        <option value="small">소형</option>
                                        <option value="medium">중형</option>
                                        <option value="large">대형</option>
                                    </select>
                                    <input type="number" name="shopping_bag_quantity" value="1" min="1" 
                                           class="w-16 px-2 py-1 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                                </div>
                            </div>
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
                        <textarea id="deliveryInstructions" name="deliveryInstructions" placeholder="전달하실 내용을 입력하세요." 
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

<?= $this->include('forms/multi-order-modal', ['service_name' => '당일택배']) ?>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
