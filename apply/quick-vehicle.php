<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 차량(화물) 접수';
$content_header = [
    'title' => '차량(화물) 접수',
    'description' => '차량을 이용한 화물 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<div class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <form class="order-form max-w-6xl mx-auto" id="orderForm">
        <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
        
        <!-- 차량 서비스 특화 항목 -->
        <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b border-blue-200">차량 서비스 정보</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                    <label for="vehicleSize" class="block text-sm font-semibold text-gray-700">차량 크기</label>
                    <select id="vehicleSize" name="vehicleSize" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">선택하세요</option>
                        <option value="small">소형 (1톤 이하)</option>
                        <option value="medium">중형 (1-3톤)</option>
                        <option value="large">대형 (3톤 이상)</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label for="cargoVolume" class="block text-sm font-semibold text-gray-700">화물 부피 (m³)</label>
                    <input type="number" id="cargoVolume" name="cargoVolume" placeholder="화물 부피를 입력하세요" min="0" step="0.1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="space-y-2">
                    <label for="loadingType" class="block text-sm font-semibold text-gray-700">적재 방식</label>
                    <select id="loadingType" name="loadingType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="manual">수동 적재</option>
                        <option value="crane">크레인 적재</option>
                        <option value="forklift">지게차 적재</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- 제출 버튼 -->
        <div class="flex justify-end space-x-4 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md font-medium transition-colors">취소</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors">접수하기</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>