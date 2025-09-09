<?php
session_start();
// 로그인 체크 (실제 구현에서는 데이터베이스 연동 필요)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'STN Network - 주문접수';
$content_header = [
    'title' => '주문접수',
    'description' => '새로운 주문을 접수해주세요'
];

include __DIR__ . '/includes/common_header.php';
?>

<div class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="w-full flex gap-4 px-4">
        <!-- 왼쪽: 공통 폼 -->
        <div class="flex-1">
            <form class="order-form" id="orderForm">
                <?php include __DIR__ . '/includes/forms/common-form.php'; ?>
            </form>
        </div>
        
        <!-- 오른쪽: 제출 버튼 -->
        <div class="w-64">
            <div class="sticky top-4">
                <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <button type="submit" form="orderForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">주문접수</button>
                    <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors">취소</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/common_footer.php'; ?>