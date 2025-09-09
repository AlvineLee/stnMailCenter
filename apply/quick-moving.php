<?php
session_start();
// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'STN Network - 이사짐화물(소형) 접수';
$content_header = [
    'title' => '이사짐화물(소형) 접수',
    'description' => '소형 이사짐화물 배송을 접수해주세요'
];

include __DIR__ . '/../includes/common_header.php';
?>

<form class="order-form" id="orderForm">
    <?php include __DIR__ . '/../includes/forms/common-form.php'; ?>
    
    <!-- 이사짐화물 서비스 특화 항목 -->
    <section class="form-section">
        <h2 class="section-title">이사짐화물 서비스 정보</h2>
        <div class="form-group">
            <label for="movingType">이사 유형</label>
            <select id="movingType" name="movingType">
                <option value="">선택하세요</option>
                <option value="partial">부분 이사</option>
                <option value="full">전체 이사</option>
                <option value="office">사무실 이사</option>
            </select>
        </div>
        <div class="form-group">
            <label for="itemCount">짐 개수</label>
            <input type="number" id="itemCount" name="itemCount" placeholder="짐의 개수를 입력하세요" min="1">
        </div>
        <div class="form-group">
            <label for="movingDate">이사 날짜</label>
            <input type="date" id="movingDate" name="movingDate">
        </div>
        <div class="form-group">
            <label for="packingService">포장 서비스</label>
            <select id="packingService" name="packingService">
                <option value="none">포장 없음</option>
                <option value="basic">기본 포장</option>
                <option value="premium">프리미엄 포장</option>
            </select>
        </div>
    </section>

    <div class="form-actions">
        <button type="submit" class="btn-submit">접수하기</button>
        <button type="button" class="btn-cancel">취소</button>
    </div>
</form>

<?php include __DIR__ . '/../includes/common_footer.php'; ?>