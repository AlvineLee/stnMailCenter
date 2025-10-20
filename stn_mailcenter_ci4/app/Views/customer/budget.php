<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">

    <!-- 예산 현황 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">총 예산</p>
                    <p class="text-2xl font-bold text-green-900">₩50M</p>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">사용 예산</p>
                    <p class="text-2xl font-bold text-blue-900">₩32M</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-600">잔여 예산</p>
                    <p class="text-2xl font-bold text-yellow-900">₩18M</p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-purple-600">사용률</p>
                    <p class="text-2xl font-bold text-purple-900">64%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 예산 목록 테이블 -->
    <div>
        <table>
            <thead>
                <tr>
                    <th>부서</th>
                    <th>예산 항목</th>
                    <th>총 예산</th>
                    <th>사용 금액</th>
                    <th>잔여 금액</th>
                    <th>사용률</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>운영팀</td>
                    <td>인건비</td>
                    <td>₩20,000,000</td>
                    <td>₩15,000,000</td>
                    <td>₩5,000,000</td>
                    <td><span class="status-badge" style="background: #dcfce7; color: #166534;">75%</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>상세</button>
                    </td>
                </tr>
                <tr>
                    <td>마케팅팀</td>
                    <td>광고비</td>
                    <td>₩10,000,000</td>
                    <td>₩8,500,000</td>
                    <td>₩1,500,000</td>
                    <td><span class="status-badge" style="background: #fef3c7; color: #92400e;">85%</span></td>
                    <td class="action-buttons">
                        <button>수정</button>
                        <button>상세</button>
                    </td>
                </tr>
                <tr>
                    <td>개발팀</td>
                    <td>장비비</td>
                    <td>₩8,500,000</td>
                    <td>₩4,200,000</td>
                    <td>₩4,300,000</td>
                    <td><span class="status-badge" style="background: #dbeafe; color: #1e40af;">49%</span></td>
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
            예산 추가
        </button>
    </div>
</div>
<?= $this->endSection() ?>
