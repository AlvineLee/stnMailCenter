<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">

    <!-- 본점 정보 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">총 본점 수</p>
                    <p class="text-2xl font-bold text-blue-900">1</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">직원 수</p>
                    <p class="text-2xl font-bold text-green-900">25</p>
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
                    <p class="text-sm font-medium text-purple-600">월 매출</p>
                    <p class="text-2xl font-bold text-purple-900">₩2.5M</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 본점 정보 테이블 -->
    <div>
        <table>
            <thead>
                <tr>
                    <th>본점명</th>
                    <th>주소</th>
                    <th>연락처</th>
                    <th>담당자</th>
                    <th>상태</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>STN 본점</td>
                    <td>서울시 강남구 테헤란로 123</td>
                    <td>02-1234-5678</td>
                    <td>김본점</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">운영중</span></td>
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
            본점 추가
        </button>
    </div>
</div>
<?= $this->endSection() ?>
