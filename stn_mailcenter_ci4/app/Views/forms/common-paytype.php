<?php
// 공통 지급구분 컴포넌트
// 인성 API credit 값: 숫자 또는 한글 문자열로 반환됨
// 숫자: 1=선불, 2=착불, 3=신용, 4=송금, 5/6/7=카드
// 한글: '선불', '착불', '신용', '송금', '카드'
// 컨트롤러에서 실시간 API로 조회한 credit 값 우선 사용, 없으면 세션 값 사용
$credit = $credit ?? session()->get('credit');

// 각 지급구분별 활성화 여부 (숫자와 한글 모두 지원)
$isPrepaidEnabled = ($credit == '1' || $credit === '선불');      // 선불
$isCodEnabled = ($credit == '2' || $credit === '착불');          // 착불
$isCreditEnabled = ($credit == '3' || $credit === '신용');       // 신용
$isTransferEnabled = ($credit == '4' || $credit === '송금');     // 송금
$isCardEnabled = ($credit == '5' || $credit == '6' || $credit == '7' || $credit === '카드');  // 카드

// 기본 선택값 결정 (활성화된 첫 번째 옵션)
$defaultPayment = '';
if ($isPrepaidEnabled) $defaultPayment = 'cash_in_advance';
elseif ($isCodEnabled) $defaultPayment = 'cash_on_delivery';
elseif ($isCreditEnabled) $defaultPayment = 'credit_transaction';
elseif ($isTransferEnabled) $defaultPayment = 'bank_transfer';
elseif ($isCardEnabled) $defaultPayment = 'card_payment';
?>

<!-- 지급구분 -->
<div class="w-full">
    <div class="sticky top-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">지급구분</h3>

            <!-- 지급방법 선택 -->
            <div class="space-y-2 mb-4">
                <label class="flex items-center space-x-2 <?= $isPrepaidEnabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' ?>">
                    <input type="radio" name="payment_type" value="cash_in_advance" <?= old('payment_type', $defaultPayment) === 'cash_in_advance' ? 'checked' : '' ?> <?= $isPrepaidEnabled ? '' : 'disabled' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">선불</span>
                </label>
                <label class="flex items-center space-x-2 <?= $isCreditEnabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' ?>">
                    <input type="radio" name="payment_type" value="credit_transaction" <?= old('payment_type', $defaultPayment) === 'credit_transaction' ? 'checked' : '' ?> <?= $isCreditEnabled ? '' : 'disabled' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">신용</span>
                </label>
                <label class="flex items-center space-x-2 <?= $isCardEnabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' ?>">
                    <input type="radio" name="payment_type" value="card_payment" <?= old('payment_type', $defaultPayment) === 'card_payment' ? 'checked' : '' ?> <?= $isCardEnabled ? '' : 'disabled' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">카드</span>
                </label>
                <label class="flex items-center space-x-2 <?= $isCodEnabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' ?>">
                    <input type="radio" name="payment_type" value="cash_on_delivery" <?= old('payment_type', $defaultPayment) === 'cash_on_delivery' ? 'checked' : '' ?> <?= $isCodEnabled ? '' : 'disabled' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">착불</span>
                </label>
                <label class="flex items-center space-x-2 <?= $isTransferEnabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' ?>">
                    <input type="radio" name="payment_type" value="bank_transfer" <?= old('payment_type', $defaultPayment) === 'bank_transfer' ? 'checked' : '' ?> <?= $isTransferEnabled ? '' : 'disabled' ?> class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">송금</span>
                </label>
            </div>
            
            <!-- 예약 날짜/시간 필드 (숨김) -->
            <input type="hidden" name="order_date" id="order_date" value="">
            <input type="hidden" name="order_time" id="order_time" value="">
            
            <!-- 버튼 영역 -->
            <div class="flex flex-col space-y-2">
                <button type="submit" form="orderForm" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-md text-sm font-semibold transition-colors duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    📦 주문 접수하기
                </button>
                <button type="button" id="reservationBtn" class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                    📅 예약하기
                </button>
                <button type="button" class="w-full bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                    취소
                </button>
            </div>
    </div>
</div>

<!-- 예약 날짜/시간 선택 레이어 팝업 -->
<div id="reservationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">예약 날짜/시간 선택</h3>
                <button type="button" id="closeReservationModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <!-- 날짜 선택 -->
                <div>
                    <label for="reservationDate" class="block text-sm font-medium text-gray-700 mb-2">예약 날짜</label>
                    <input type="date" id="reservationDate" name="reservationDate" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <!-- 시간 선택 -->
                <div>
                    <label for="reservationTime" class="block text-sm font-medium text-gray-700 mb-2">예약 시간</label>
                    <input type="time" id="reservationTime" name="reservationTime" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <!-- 선택된 날짜/시간 표시 -->
                <div id="selectedDateTime" class="p-3 bg-blue-50 rounded-md text-sm text-blue-800 hidden">
                    <span class="font-medium">선택된 예약 시간:</span>
                    <span id="displayDateTime"></span>
                </div>
            </div>
            
            <div class="flex space-x-3 mt-6">
                <button type="button" id="confirmReservation" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                    예약 확정
                </button>
                <button type="button" id="cancelReservation" 
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                    취소
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reservationBtn = document.getElementById('reservationBtn');
    const reservationModal = document.getElementById('reservationModal');
    const closeModal = document.getElementById('closeReservationModal');
    const cancelReservation = document.getElementById('cancelReservation');
    const confirmReservation = document.getElementById('confirmReservation');
    const reservationDate = document.getElementById('reservationDate');
    const reservationTime = document.getElementById('reservationTime');
    const selectedDateTime = document.getElementById('selectedDateTime');
    const displayDateTime = document.getElementById('displayDateTime');
    const orderDateInput = document.getElementById('order_date');
    const orderTimeInput = document.getElementById('order_time');
    
    // 오늘 날짜를 최소 날짜로 설정
    const today = new Date().toISOString().split('T')[0];
    reservationDate.min = today;
    
    // 예약 버튼 클릭
    reservationBtn.addEventListener('click', function() {
        // 오늘 날짜로 초기화
        reservationDate.value = today;
        
        // 현재 시간을 기본값으로 설정
        const now = new Date();
        const currentHour = now.getHours().toString().padStart(2, '0');
        const currentMinute = now.getMinutes().toString().padStart(2, '0');
        reservationTime.value = currentHour + ':' + currentMinute;
        
        selectedDateTime.classList.add('hidden');
        reservationModal.classList.remove('hidden');
    });
    
    // 모달 닫기
    function closeModalFunc() {
        reservationModal.classList.add('hidden');
    }
    
    closeModal.addEventListener('click', closeModalFunc);
    cancelReservation.addEventListener('click', closeModalFunc);
    
    // 날짜 변경 시 시간 옵션 업데이트
    reservationDate.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        const isToday = selectedDate.toDateString() === today.toDateString();
        
        if (isToday) {
            // 오늘 날짜인 경우 현재 시간 이후만 선택 가능
            const now = new Date();
            const currentHour = now.getHours().toString().padStart(2, '0');
            const currentMinute = now.getMinutes().toString().padStart(2, '0');
            reservationTime.min = currentHour + ':' + currentMinute;
        } else {
            // 다른 날짜인 경우 24시간 선택 가능
            reservationTime.min = '00:00';
        }
        
        // 시간이 현재 시간보다 이전이면 초기화
        if (isToday && reservationTime.value && reservationTime.value < reservationTime.min) {
            reservationTime.value = '';
        }
        
        updateSelectedDateTime();
    });
    
    // 시간 변경 시 선택된 시간 표시 업데이트
    reservationTime.addEventListener('change', updateSelectedDateTime);
    
    // 선택된 날짜/시간 표시 업데이트
    function updateSelectedDateTime() {
        if (reservationDate.value && reservationTime.value) {
            const date = new Date(reservationDate.value);
            const time = reservationTime.value;
            const dateStr = date.toLocaleDateString('ko-KR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long'
            });
            const timeStr = time + ':00';
            
            displayDateTime.textContent = `${dateStr} ${timeStr}`;
            selectedDateTime.classList.remove('hidden');
        } else {
            selectedDateTime.classList.add('hidden');
        }
    }
    
    // 예약 확정
    confirmReservation.addEventListener('click', function() {
        if (!reservationDate.value || !reservationTime.value) {
            alert('날짜와 시간을 모두 선택해주세요.');
            return;
        }
        
        // 유효성 검사
        const selectedDate = new Date(reservationDate.value);
        const today = new Date();
        const isToday = selectedDate.toDateString() === today.toDateString();
        
        if (isToday) {
            const now = new Date();
            const selectedDateTime = new Date(reservationDate.value + 'T' + reservationTime.value);
            
            if (selectedDateTime <= now) {
                alert('오늘 날짜를 선택한 경우 현재 시간 이후의 시간을 선택해주세요.');
                return;
            }
        }
        
        // 폼에 값 설정
        orderDateInput.value = reservationDate.value;
        orderTimeInput.value = reservationTime.value;
        
        // 예약 버튼 텍스트 변경
        const dateStr = selectedDate.toLocaleDateString('ko-KR', {
            month: 'short',
            day: 'numeric'
        });
        const timeStr = reservationTime.value;
        reservationBtn.innerHTML = `📅 예약: ${dateStr} ${timeStr}`;
        reservationBtn.classList.remove('bg-gray-500', 'hover:bg-gray-600');
        reservationBtn.classList.add('bg-orange-500', 'hover:bg-orange-600');
        
        // 모달 닫기
        closeModalFunc();
        
        // 주문 접수하기 버튼을 예약 접수로 변경
        const submitBtn = document.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '📅 예약 접수하기';
        submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        submitBtn.classList.add('bg-orange-600', 'hover:bg-orange-700');
    });
    
    // 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
    // 외부 클릭으로 인한 실수 방지를 위해 제거
});
</script>
