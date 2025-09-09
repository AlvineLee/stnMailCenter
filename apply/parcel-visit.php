<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 방문택배 접수';
$content_header = [
    'title' => '방문택배 접수',
    'description' => '방문택배 서비스를 이용한 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<form class="order-form" id="orderForm">
    <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
    
    <!-- 방문택배 서비스 특화 항목 -->
    <section class="form-section">
        <h2 class="section-title">방문택배 서비스 정보</h2>
        <div class="form-group">
            <label for="visitDate">방문 날짜</label>
            <input type="date" id="visitDate" name="visitDate">
        </div>
        <div class="form-group">
            <label for="visitTime">방문 시간대</label>
            <select id="visitTime" name="visitTime">
                <option value="">선택하세요</option>
                <option value="morning">오전 (9-12시)</option>
                <option value="afternoon">오후 (12-18시)</option>
                <option value="evening">저녁 (18-21시)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="packageType">포장 유형</label>
            <select id="packageType" name="packageType">
                <option value="">선택하세요</option>
                <option value="envelope">봉투</option>
                <option value="box">박스</option>
                <option value="bag">가방</option>
                <option value="tube">튜브</option>
            </select>
        </div>
        <div class="form-group">
            <label for="specialHandling">특별 취급</label>
            <select id="specialHandling" name="specialHandling">
                <option value="none">없음</option>
                <option value="fragile">파손주의</option>
                <option value="urgent">긴급</option>
                <option value="signature">서명필요</option>
            </select>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn-submit">접수하기</button>
        <button type="button" class="btn-cancel">취소</button>
    </div>
</form>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>