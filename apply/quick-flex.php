<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 플렉스(소화물) 접수';
$content_header = [
    'title' => '플렉스(소화물) 접수',
    'description' => '플렉스 서비스를 이용한 소화물 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<form class="order-form" id="orderForm">
    <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
    
    <!-- 플렉스 서비스 특화 항목 -->
    <section class="form-section">
        <h2 class="section-title">플렉스 서비스 정보</h2>
        <div class="form-group">
            <label for="flexType">플렉스 유형</label>
            <select id="flexType" name="flexType">
                <option value="">선택하세요</option>
                <option value="bicycle">자전거</option>
                <option value="walking">도보</option>
                <option value="public">대중교통</option>
            </select>
        </div>
        <div class="form-group">
            <label for="deliveryTime">배송 시간대</label>
            <select id="deliveryTime" name="deliveryTime">
                <option value="morning">오전 (9-12시)</option>
                <option value="afternoon">오후 (12-18시)</option>
                <option value="evening">저녁 (18-21시)</option>
                <option value="flexible">시간 유연</option>
            </select>
        </div>
        <div class="form-group">
            <label for="specialInstructions">특별 지시사항</label>
            <textarea id="specialInstructions" name="specialInstructions" placeholder="특별한 지시사항이 있으시면 입력해주세요"></textarea>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn-submit">접수하기</button>
        <button type="button" class="btn-cancel">취소</button>
    </div>
</form>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>