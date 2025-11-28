<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 액션 버튼들 -->
    <div class="mb-4 flex gap-2">
        <a href="<?= base_url('billing') ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            ← 목록으로
        </a>
        <button onclick="createBilling()" class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            + 청구서 생성
        </button>
    </div>

    <!-- 검색 및 필터 영역 -->
    <div class="bg-gray-50 rounded-lg search-compact">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">고객사</label>
                <select id="customerSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체 고객사</option>
                    <?php foreach ($customers as $customer): ?>
                    <option value="<?= $customer['id'] ?>" <?= isset($selected_customer_id) && $selected_customer_id == $customer['id'] ? 'selected' : '' ?>>
                        <?= $customer['company_name'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">부서</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체 부서</option>
                    <?php foreach ($departments as $department): ?>
                    <option value="<?= $department['id'] ?>"><?= $department['department_name'] ?> (<?= $department['department_code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">청구 상태</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체</option>
                    <option value="pending">미청구</option>
                    <option value="billed">청구완료</option>
                    <option value="paid">결제완료</option>
                </select>
            </div>
            <div>
                <button class="search-button">
                    검색
                </button>
            </div>
        </div>
    </div>

    <!-- 검색 결과 정보 -->
    <div class="mb-3 px-4 py-2 bg-gray-50 rounded-md">
        <div class="text-sm text-gray-700">
            총 <span class="font-medium text-gray-900">5</span>건의 검색결과가 있습니다.
        </div>
    </div>

    <!-- 부서별 청구 목록 테이블 -->
    <div class="list-table-container">
        <table>
            <thead>
                <tr>
                    <th>청구번호</th>
                    <th>고객사</th>
                    <th>부서</th>
                    <th>청구기간</th>
                    <th>청구금액</th>
                    <th>상태</th>
                    <th>생성일</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>BILL-2024-001</td>
                    <td>STN 네트워크</td>
                    <td>개발팀 (DEPT001)</td>
                    <td>2024-01-01 ~ 2024-01-31</td>
                    <td>2,500,000원</td>
                    <td><span class="status-badge" style="background: #fef3c7; color: #92400e;">미청구</span></td>
                    <td>2024-02-01</td>
                    <td class="action-buttons">
                        <button>상세</button>
                        <button>수정</button>
                        <button>삭제</button>
                    </td>
                </tr>
                <tr>
                    <td>BILL-2024-002</td>
                    <td>STN 네트워크</td>
                    <td>마케팅팀 (DEPT002)</td>
                    <td>2024-01-01 ~ 2024-01-31</td>
                    <td>1,800,000원</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">청구완료</span></td>
                    <td>2024-02-01</td>
                    <td class="action-buttons">
                        <button>상세</button>
                        <button>수정</button>
                        <button>삭제</button>
                    </td>
                </tr>
                <tr>
                    <td>BILL-2024-003</td>
                    <td>STN 네트워크</td>
                    <td>영업팀 (DEPT003)</td>
                    <td>2024-01-01 ~ 2024-01-31</td>
                    <td>3,200,000원</td>
                    <td><span class="status-badge" style="background: #dbeafe; color: #1e40af;">결제완료</span></td>
                    <td>2024-02-01</td>
                    <td class="action-buttons">
                        <button>상세</button>
                        <button>수정</button>
                        <button>삭제</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <div class="list-pagination flex justify-center">
        <div class="pagination flex space-x-2">
            <button class="nav-button">이전</button>
            <button class="page-number active">1</button>
            <button class="page-number">2</button>
            <button class="page-number">3</button>
            <button class="nav-button">다음</button>
        </div>
    </div>
</div>

<script>
function createBilling() {
    alert('프로토타입 모드: 청구서 생성 기능은 실제 DB 연결 후 사용 가능합니다.');
}

// 고객사 선택 시 부서 목록 업데이트
document.getElementById('customerSelect').addEventListener('change', function() {
    const customerId = this.value;
    if (customerId) {
        // 프로토타입에서는 실제 AJAX 호출하지 않음
        // console.log('고객사 선택:', customerId);
    }
});
</script>
<?= $this->endSection() ?>
