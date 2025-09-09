<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 사다주기 접수';
$content_header = [
    'title' => '사다주기 접수',
    'description' => '사다주기 서비스를 이용한 구매 대행을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<form class="order-form" id="orderForm">
    <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
    
    <!-- 사다주기 서비스 특화 항목 -->
    <section class="form-section">
        <h2 class="section-title">사다주기 서비스 정보</h2>
        <div class="form-group">
            <label for="storeName">구매처</label>
            <input type="text" id="storeName" name="storeName" placeholder="구매할 상점명을 입력하세요">
        </div>
        <div class="form-group">
            <label for="storeAddress">상점 주소</label>
            <input type="text" id="storeAddress" name="storeAddress" placeholder="상점 주소를 입력하세요">
        </div>
        <div class="form-group">
            <label for="itemName">구매할 물품</label>
            <input type="text" id="itemName" name="itemName" placeholder="구매할 물품명을 입력하세요">
        </div>
        <div class="form-group">
            <label for="itemPrice">예상 가격</label>
            <input type="number" id="itemPrice" name="itemPrice" placeholder="예상 가격을 입력하세요" min="0">
        </div>
        <div class="form-group">
            <label for="purchaseInstructions">구매 지시사항</label>
            <textarea id="purchaseInstructions" name="purchaseInstructions" placeholder="구매 시 특별한 지시사항이 있으시면 입력해주세요"></textarea>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn-submit">접수하기</button>
        <button type="button" class="btn-cancel">취소</button>
    </div>
</form>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>