<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">
    <!-- 알림 설정 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12.828 7H4.828z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">활성 알림</p>
                    <p class="text-2xl font-bold text-blue-900">8</p>
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
                    <p class="text-sm font-medium text-green-600">이메일 알림</p>
                    <p class="text-2xl font-bold text-green-900">5</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-600">SMS 알림</p>
                    <p class="text-2xl font-bold text-yellow-900">3</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 알림 설정 그리드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 주문 관련 알림 -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">주문 관련 알림</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-600">새 주문 접수</span>
                        <p class="text-xs text-gray-500">새로운 주문이 접수될 때 알림</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-600">주문 상태 변경</span>
                        <p class="text-xs text-gray-500">주문 상태가 변경될 때 알림</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-600">배송 완료</span>
                        <p class="text-xs text-gray-500">배송이 완료될 때 알림</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- 시스템 관련 알림 -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">시스템 관련 알림</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-600">시스템 점검</span>
                        <p class="text-xs text-gray-500">시스템 점검 예정 시 알림</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-600">보안 경고</span>
                        <p class="text-xs text-gray-500">보안 관련 이벤트 발생 시 알림</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-600">데이터 백업</span>
                        <p class="text-xs text-gray-500">데이터 백업 완료 시 알림</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- 알림 방법 설정 -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4 border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">알림 방법 설정</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">이메일 주소</label>
                <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="admin@example.com" value="admin@stn.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SMS 번호</label>
                <input type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="010-1234-5678" value="010-1234-5678">
            </div>
        </div>
    </div>

    <!-- 액션 버튼 -->
    <div class="mt-6 flex justify-end">
        <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            설정 저장
        </button>
    </div>
</div>
<?= $this->endSection() ?>
