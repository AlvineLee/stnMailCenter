<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">

    <!-- 검색 및 필터 영역 -->
    <div class="bg-gray-50 rounded-lg search-compact">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">회원명</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="회원명 입력">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">회원등급</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체</option>
                    <option value="vip">VIP</option>
                    <option value="gold">골드</option>
                    <option value="silver">실버</option>
                    <option value="bronze">브론즈</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">가입일</label>
                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">상태</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체</option>
                    <option value="active">활성</option>
                    <option value="inactive">비활성</option>
                    <option value="suspended">정지</option>
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
            총 <span class="font-medium text-gray-900">3</span>건의 검색결과가 있습니다.
        </div>
    </div>

    <!-- 회원 목록 테이블 -->
    <div class="list-table-container">
        <table>
            <thead>
                <tr>
                    <th>회원번호</th>
                    <th>회원명</th>
                    <th>연락처</th>
                    <th>이메일</th>
                    <th>등급</th>
                    <th>가입일</th>
                    <th>상태</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>M001</td>
                    <td>김철수</td>
                    <td>010-1234-5678</td>
                    <td>kim@example.com</td>
                    <td><span class="status-badge" style="background: #e9d5ff; color: #7c3aed;">VIP</span></td>
                    <td>2024-01-01</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">활성</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>정지</button>
                    </td>
                </tr>
                <tr>
                    <td>M002</td>
                    <td>이영희</td>
                    <td>010-2345-6789</td>
                    <td>lee@example.com</td>
                    <td><span class="status-badge" style="background: #fef3c7; color: #92400e;">골드</span></td>
                    <td>2024-01-05</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">활성</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>정지</button>
                    </td>
                </tr>
                <tr>
                    <td>M003</td>
                    <td>박민수</td>
                    <td>010-3456-7890</td>
                    <td>park@example.com</td>
                    <td><span class="status-badge" style="background: #f3f4f6; color: #374151;">실버</span></td>
                    <td>2024-01-10</td>
                    <td><span class="status-badge" style="background: #fee2e2; color: #dc2626;">정지</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>활성화</button>
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
<?= $this->endSection() ?>
