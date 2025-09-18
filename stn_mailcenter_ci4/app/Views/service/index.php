<?= $this->include('layouts/header') ?>

<div class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="w-full flex gap-4 px-4">
        <!-- 왼쪽: 공통 폼 -->
        <div class="flex-1">
            <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                <input type="hidden" name="service_type" value="<?= $service_type ?>">
                <input type="hidden" name="service_name" value="<?= $service_name ?>">
                <?= $this->include('forms/common-form') ?>
            <?= form_close() ?>
        </div>
        
        <!-- 오른쪽: 제출 버튼 -->
        <div class="w-64">
            <div class="sticky top-4">
                <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <button type="submit" form="orderForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors"><?= $service_name ?> 주문접수</button>
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors">취소</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->include('layouts/footer') ?>
