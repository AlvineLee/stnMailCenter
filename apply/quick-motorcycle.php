<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 오토바이(소화물) 접수';
$content_header = [
    'title' => '오토바이(소화물) 접수',
    'description' => '오토바이를 이용한 소화물 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<div class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="w-full flex gap-4 px-4">
        <!-- 왼쪽: 공통 폼 -->
        <div class="flex-1">
            <form class="order-form" id="orderForm">
                <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
            </form>
        </div>
        
        <!-- 오른쪽: 특화 항목 -->
        <div class="w-64">
            <div class="sticky top-4">
                <!-- 오토바이 서비스 특화 항목 -->
                <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                    <h2 class="text-base font-semibold text-gray-800 mb-3 pb-1 border-b border-blue-200">오토바이 서비스 정보</h2>
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label for="vehicleType" class="block text-xs font-medium text-gray-700">차량 유형</label>
                            <select id="vehicleType" name="vehicleType" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                <option value="">선택하세요</option>
                                <option value="scooter">스쿠터</option>
                                <option value="motorcycle">오토바이</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label for="cargoWeight" class="block text-xs font-medium text-gray-700">화물 무게 (kg)</label>
                            <input type="number" id="cargoWeight" name="cargoWeight" placeholder="화물 무게를 입력하세요" min="0" max="50"
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="space-y-1">
                            <label for="urgency" class="block text-xs font-medium text-gray-700">긴급도</label>
                            <select id="urgency" name="urgency" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                <option value="normal">일반</option>
                                <option value="urgent">긴급</option>
                                <option value="asap">최우선</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- 제출 버튼 -->
                <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <button type="submit" form="orderForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">접수하기</button>
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors">취소</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>