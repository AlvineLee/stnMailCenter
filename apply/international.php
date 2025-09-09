<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 해외특송서비스 접수';
$content_header = [
    'title' => '해외특송서비스 접수',
    'description' => '해외 특송 서비스를 이용한 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<form class="order-form" id="orderForm">
    <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
    
    <!-- 해외특송서비스 특화 항목 -->
    <section class="form-section">
        <h2 class="section-title">해외특송 서비스 정보</h2>
        <div class="form-group">
            <label for="destinationCountry">목적지 국가</label>
            <select id="destinationCountry" name="destinationCountry">
                <option value="">선택하세요</option>
                <option value="US">미국</option>
                <option value="JP">일본</option>
                <option value="CN">중국</option>
                <option value="EU">유럽</option>
                <option value="other">기타</option>
            </select>
        </div>
        <div class="form-group">
            <label for="shippingMethod">배송 방법</label>
            <select id="shippingMethod" name="shippingMethod">
                <option value="">선택하세요</option>
                <option value="air">항공</option>
                <option value="sea">해운</option>
                <option value="express">특급</option>
            </select>
        </div>
        <div class="form-group">
            <label for="customsValue">관세 신고 가격 (USD)</label>
            <input type="number" id="customsValue" name="customsValue" placeholder="관세 신고 가격을 입력하세요" min="0" step="0.01">
        </div>
        <div class="form-group">
            <label for="customsDescription">관세 신고 내용</label>
            <textarea id="customsDescription" name="customsDescription" placeholder="관세 신고 내용을 입력하세요"></textarea>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn-submit">접수하기</button>
        <button type="button" class="btn-cancel">취소</button>
    </div>
</form>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>