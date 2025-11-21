<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<?php
// 임시 디버그: m_code, cc_code 확인
$loginType = session()->get('login_type');
if ($loginType === 'daumdata') {
    $mCode = session()->get('m_code');
    $ccCode = session()->get('cc_code');
    $ckey = session()->get('ckey');
    $ukey = session()->get('ukey');
    $akey = session()->get('akey');
?>
<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded">
    <p class="font-bold">[임시 디버그] 세션 값 확인</p>
    <p><strong>m_code:</strong> <?= htmlspecialchars($mCode ?? '없음') ?></p>
    <p><strong>cc_code:</strong> <?= htmlspecialchars($ccCode ?? '없음') ?></p>
    <p><strong>token:</strong> <?= htmlspecialchars(session()->get('token') ?? '없음') ?></p>
    <p><strong>ckey:</strong> <?= htmlspecialchars($ckey ?? '없음') ?></p>
    <p><strong>ukey:</strong> <?= htmlspecialchars($ukey ?? '없음') ?></p>
    <p><strong>akey:</strong> <?= htmlspecialchars($akey ?? '없음') ?></p>
    <p><strong>login_type:</strong> <?= htmlspecialchars($loginType ?? '없음') ?></p>
    
    <!-- API 인증 테스트 섹션 -->
    <div class="mt-4 pt-4 border-t border-yellow-600">
        <p class="font-bold mb-2">인성 API 인증 테스트</p>
        <div class="space-y-2">
            <button id="testInsungApi" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                API 인증 테스트 실행
            </button>
            <button id="testTokenRefresh" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm">
                토큰 갱신 테스트
            </button>
        </div>
        <div id="apiTestResult" class="mt-3 p-3 bg-white rounded border border-yellow-300 hidden">
            <h4 class="font-semibold mb-2 text-sm">테스트 결과:</h4>
            <pre id="apiTestResultContent" class="text-xs overflow-auto max-h-40 whitespace-pre-wrap"></pre>
        </div>
    </div>
</div>
<?php } ?>
<div class="space-y-6">
    <!-- 통계 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">총 주문</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_orders'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">대기중</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['pending_orders'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">완료</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['completed_orders'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">오늘</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['today_orders'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 메인 콘텐츠 영역 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- 최근 주문 -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">오늘의 주문</h3>
                        <a href="<?= base_url('delivery/list') ?>" class="text-sm text-blue-600 hover:text-blue-800">전체보기</a>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-2">
                        <?php foreach ($recent_orders as $order): ?>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg" style="height: 24px;">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <div class="flex-shrink-0">
                                        <span class="text-xs font-medium text-gray-900"><?= $order['id'] ?></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs font-medium text-gray-900"><?= $order['service'] ?? '-' ?> - <?= $order['customer'] ?? '-' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-1 py-0.5 text-xs font-semibold rounded-full <?php 
                                    switch($order['status']) {
                                        case '배송완료': echo 'bg-gray-100 text-gray-700'; break;
                                        case '배송중': echo 'bg-gray-200 text-gray-800'; break;
                                        case '접수대기': echo 'bg-gray-50 text-gray-600'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                ?>"><?= $order['status'] ?></span>
                                <span class="text-xs text-gray-500"><?= $order['date'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 빠른 액션 -->
        <div class="space-y-4">
            <!-- 빠른 주문접수 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">빠른 주문접수</h3>
                <div class="space-y-3">
                    <a href="<?= base_url('service/quick-motorcycle') ?>" class="block w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-900">오토바이(소화물)</span>
                        </div>
                    </a>
                    <a href="<?= base_url('service/quick-vehicle') ?>" class="block w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-900">차량(화물)</span>
                        </div>
                    </a>
                    <a href="<?= base_url('service/quick-flex') ?>" class="block w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-900">플렉스(소화물)</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- 시스템 상태 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">시스템 상태</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">서버 상태</span>
                        <span class="flex items-center text-sm text-green-600">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            정상
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">데이터베이스</span>
                        <span class="flex items-center text-sm text-green-600">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            정상
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">API 연결</span>
                        <span class="flex items-center text-sm text-green-600">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            정상
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($loginType === 'daumdata'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // API 인증 테스트
    const testInsungApiBtn = document.getElementById('testInsungApi');
    const testTokenRefreshBtn = document.getElementById('testTokenRefresh');
    const apiTestResult = document.getElementById('apiTestResult');
    const apiTestResultContent = document.getElementById('apiTestResultContent');
    
    // CSRF 토큰 가져오기
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const csrfHeader = document.querySelector('meta[name="csrf-header"]')?.getAttribute('content') || 'X-CSRF-TOKEN';
    
    if (testInsungApiBtn) {
        testInsungApiBtn.addEventListener('click', function() {
            apiTestResult.classList.remove('hidden');
            apiTestResultContent.textContent = '테스트 실행 중...';
            
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            
            if (csrfToken) {
                headers[csrfHeader] = csrfToken;
            }
            
            fetch('<?= base_url('dashboard/test-insung-api') ?>', {
                method: 'POST',
                headers: headers
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                apiTestResultContent.textContent = JSON.stringify(data, null, 2);
                apiTestResult.classList.remove('border-yellow-300', 'bg-green-50', 'bg-red-50');
                if (data.success) {
                    apiTestResult.classList.add('border-green-300', 'bg-green-50');
                } else {
                    apiTestResult.classList.add('border-red-300', 'bg-red-50');
                }
            })
            .catch(error => {
                apiTestResultContent.textContent = '에러: ' + error.message;
                apiTestResult.classList.remove('border-yellow-300', 'bg-green-50');
                apiTestResult.classList.add('border-red-300', 'bg-red-50');
            });
        });
    }
    
    if (testTokenRefreshBtn) {
        testTokenRefreshBtn.addEventListener('click', function() {
            apiTestResult.classList.remove('hidden');
            apiTestResultContent.textContent = '토큰 갱신 테스트 실행 중...';
            
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            
            if (csrfToken) {
                headers[csrfHeader] = csrfToken;
            }
            
            fetch('<?= base_url('dashboard/test-token-refresh') ?>', {
                method: 'POST',
                headers: headers
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                apiTestResultContent.textContent = JSON.stringify(data, null, 2);
                apiTestResult.classList.remove('border-yellow-300', 'bg-green-50', 'bg-red-50');
                if (data.success) {
                    apiTestResult.classList.add('border-green-300', 'bg-green-50');
                    // 토큰 갱신 성공 시 1초 후 페이지 새로고침
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    apiTestResult.classList.add('border-red-300', 'bg-red-50');
                }
            })
            .catch(error => {
                apiTestResultContent.textContent = '에러: ' + error.message;
                apiTestResult.classList.remove('border-yellow-300', 'bg-green-50');
                apiTestResult.classList.add('border-red-300', 'bg-red-50');
            });
        });
    }
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>