<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">

    <!-- 대리점 정보 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">총 대리점 수</p>
                    <p class="text-2xl font-bold text-blue-900">15</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">계약중</p>
                    <p class="text-2xl font-bold text-green-900">12</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-600">계약만료</p>
                    <p class="text-2xl font-bold text-yellow-900">2</p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-purple-600">월 수수료</p>
                    <p class="text-2xl font-bold text-purple-900">₩1.8M</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 대리점 목록 테이블 -->
    <div>
        <table>
            <thead>
                <tr>
                    <th>대리점명</th>
                    <th>지역</th>
                    <th>대표자</th>
                    <th>연락처</th>
                    <th>계약일</th>
                    <th>상태</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>강남대리점</td>
                    <td>서울 강남</td>
                    <td>김강남</td>
                    <td>010-1111-2222</td>
                    <td>2024-01-01</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">계약중</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>상세</button>
                    </td>
                </tr>
                <tr>
                    <td>서초대리점</td>
                    <td>서울 서초</td>
                    <td>이서초</td>
                    <td>010-3333-4444</td>
                    <td>2024-01-05</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">계약중</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>상세</button>
                    </td>
                </tr>
                <tr>
                    <td>송파대리점</td>
                    <td>서울 송파</td>
                    <td>박송파</td>
                    <td>010-5555-6666</td>
                    <td>2023-12-15</td>
                    <td><span class="status-badge" style="background: #fef3c7; color: #92400e;">계약만료</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>상세</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 액션 버튼 -->
    <div class="mt-6 flex justify-end">
        <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            대리점 추가
        </button>
    </div>
</div>
<?= $this->endSection() ?>
