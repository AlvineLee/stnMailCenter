<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen overflow-x-hidden">
    <div class="w-full max-w-full flex flex-col md:flex-row gap-4 px-2 md:px-4 box-border pt-1">
        <!-- 왼쪽: 공통 폼 -->
        <div class="flex-1 w-full min-w-0">
            <?= form_open('service/submitServiceOrder', ['class' => 'order-form', 'id' => 'orderForm']) ?>
                <input type="hidden" name="service_type" value="<?= $service_type ?>">
                <input type="hidden" name="service_name" value="<?= $service_name ?>">
                <!-- 요금 관련 hidden 필드 -->
                <input type="hidden" name="total_amount" id="total_amount" value="0">
                <input type="hidden" name="add_cost" id="add_cost" value="0">
                <input type="hidden" name="discount_cost" id="discount_cost" value="0">
                <input type="hidden" name="delivery_cost" id="delivery_cost" value="0">
                <?= $this->include('forms/common-form') ?>
            <?= form_close() ?>
        </div>
        
        <!-- 오른쪽: 제출 버튼 -->
        <div class="w-full md:w-64 flex-shrink-0 max-w-full box-border">
            <div class="sticky top-4">
                <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border border-gray-200 p-4 box-border">
                    <button type="submit" form="orderForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap"><?= $service_name ?> 주문접수</button>
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap">취소</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
