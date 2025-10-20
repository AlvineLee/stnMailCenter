<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 액션 버튼들 -->
    <div class="mb-4 flex gap-2">
        <a href="<?= base_url('billing') ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            ← 목록으로
        </a>
        <button onclick="createCustomerGroupBilling()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            + 고객묶음 청구서 생성
        </button>
    </div>

    <!-- 검색 및 필터 영역 -->
    <div class="bg-gray-50 rounded-lg search-compact">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">고객 그룹</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체 그룹</option>
                    <option value="stn_group">STN 그룹</option>
                    <option value="partner_group">파트너 그룹</option>
                    <option value="agency_group">대리점 그룹</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">계층 레벨</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체</option>
                    <option value="head_office">본점</option>
                    <option value="branch">지사</option>
                    <option value="agency">대리점</option>
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
            총 <span class="font-medium text-gray-900">2</span>건의 검색결과가 있습니다.
        </div>
    </div>

    <!-- 고객묶음 청구 목록 테이블 -->
    <div class="list-table-container">
        <table>
            <thead>
                <tr>
                    <th>청구번호</th>
                    <th>고객 그룹</th>
                    <th>포함 고객사</th>
                    <th>총 부서 수</th>
                    <th>청구기간</th>
                    <th>청구금액</th>
                    <th>상태</th>
                    <th>생성일</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>BILL-CUSTOMER-2024-001</td>
                    <td>STN 그룹</td>
                    <td>STN 네트워크, STN 서울지사 (2개)</td>
                    <td>6개 부서</td>
                    <td>2024-01-01 ~ 2024-01-31</td>
                    <td>12,500,000원</td>
                    <td><span class="status-badge" style="background: #fef3c7; color: #92400e;">미청구</span></td>
                    <td>2024-02-01</td>
                    <td class="action-buttons">
                        <button>상세</button>
                        <button>수정</button>
                        <button>삭제</button>
                    </td>
                </tr>
                <tr>
                    <td>BILL-CUSTOMER-2024-002</td>
                    <td>대리점 그룹</td>
                    <td>STN 강남대리점, STN 부산대리점 (2개)</td>
                    <td>4개 부서</td>
                    <td>2024-01-01 ~ 2024-01-31</td>
                    <td>8,200,000원</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">청구완료</span></td>
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
function createCustomerGroupBilling() {
    alert('프로토타입 모드: 고객묶음 청구서 생성 기능은 실제 DB 연결 후 사용 가능합니다.');
}
</script>
<?= $this->endSection() ?>
