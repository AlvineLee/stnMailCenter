<?php
// 공통 지급구분 컴포넌트
?>

<!-- 지급구분 -->
<div class="w-full">
    <div class="sticky top-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">지급구분</h3>
            
            <!-- 지급방법 선택 -->
            <div class="space-y-2 mb-4">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="payment_type" value="cash_on_delivery" <?= old('payment_type', 'cash_on_delivery') === 'cash_on_delivery' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">착불(현금)</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="payment_type" value="cash_in_advance" <?= old('payment_type') === 'cash_in_advance' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">선불(현금)</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="payment_type" value="bank_transfer" <?= old('payment_type') === 'bank_transfer' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">송금</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="payment_type" value="credit_transaction" <?= old('payment_type') === 'credit_transaction' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">신용거래</span>
                </label>
            </div>
            
            <!-- 버튼 영역 -->
            <div class="flex flex-col space-y-2">
                <button type="submit" form="orderForm" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-md text-sm font-semibold transition-colors duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    📦 주문 접수하기
                </button>
                <button type="button" class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                    예약하기
                </button>
                <button type="button" class="w-full bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                    취소
                </button>
            </div>
    </div>
</div>
