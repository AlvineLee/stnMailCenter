<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 메일룸서비스 접수';
$content_header = [
    'title' => '메일룸서비스 접수',
    'description' => '메일룸 서비스를 이용한 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<div class="bg-gradient-to-br min-h-screen">
    <div class="w-full flex gap-4 ">
        <!-- 왼쪽: 공통 폼 -->
        <form class="order-form" id="orderForm">
            <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
        </form>
        
        <!-- 오른쪽: 특화 항목 -->
        <div class="w-64">
            <div class="sticky top-4">
                <!-- 메일룸서비스 특화 항목 -->
                <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                    <h2 class="text-base font-semibold text-gray-800 mb-3 pb-1 border-b border-blue-200">메일룸 서비스 정보</h2>
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label for="mailroomType" class="block text-xs font-medium text-gray-700">메일룸 유형</label>
                            <select id="mailroomType" name="mailroomType" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                <option value="">선택하세요</option>
                                <option value="document">문서</option>
                                <option value="parcel">소포</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label for="mailroomLocation" class="block text-xs font-medium text-gray-700">위치</label>
                            <input type="text" id="mailroomLocation" name="mailroomLocation" placeholder="메일룸 위치를 입력하세요"
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="space-y-1">
                            <label for="pickupTime" class="block text-xs font-medium text-gray-700">픽업 희망 시간</label>
                            <input type="datetime-local" id="pickupTime" name="pickupTime"
                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent">
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