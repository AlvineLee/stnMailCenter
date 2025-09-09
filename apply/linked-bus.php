<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 고속버스(제로데이) 접수';
$content_header = [
    'title' => '고속버스(제로데이) 접수',
    'description' => '고속버스를 이용한 제로데이 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<form class="order-form" id="orderForm">
    <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
    
    <!-- 고속버스 서비스 특화 항목 -->
    <section class="form-section">
        <h2 class="section-title">고속버스 서비스 정보</h2>
        <div class="form-group">
            <label for="busRoute">버스 노선</label>
            <select id="busRoute" name="busRoute">
                <option value="">선택하세요</option>
                <option value="seoul-busan">서울-부산</option>
                <option value="seoul-daegu">서울-대구</option>
                <option value="seoul-gwangju">서울-광주</option>
                <option value="seoul-daejeon">서울-대전</option>
            </select>
        </div>
        <div class="form-group">
            <label for="departureTime">출발 시간</label>
            <input type="time" id="departureTime" name="departureTime">
        </div>
        <div class="form-group">
            <label for="arrivalTime">도착 시간</label>
            <input type="time" id="arrivalTime" name="arrivalTime">
        </div>
        <div class="form-group">
            <label for="busCompany">버스 회사</label>
            <select id="busCompany" name="busCompany">
                <option value="">선택하세요</option>
                <option value="kobus">코버스</option>
                <option value="express">고속버스</option>
                <option value="intercity">시외버스</option>
            </select>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn-submit">접수하기</button>
        <button type="button" class="btn-cancel">취소</button>
    </div>
</form>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>