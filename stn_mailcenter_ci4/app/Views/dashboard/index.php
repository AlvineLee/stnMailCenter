<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<?php
// 임시 디버그: m_code, cc_code 확인
/*
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
*/
$loginType = session()->get('login_type');
$today = date('Y-m-d');
$todayFormatted = date('Y년 m월 d일');
?>
<div class="space-y-4">
    <!-- 날짜 기준 안내 -->
    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 px-4 py-2 rounded">
        <p class="text-sm font-medium">📅 오늘 날짜 기준: <strong><?= $todayFormatted ?></strong></p>
    </div>
    
    <!-- 통계 카드 -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 sm:gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-gray-100 rounded-lg">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">총 주문</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['total_orders'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 rounded-lg" style="background-color: #d1ecf1;">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" style="color: #0c5460;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">예약</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['reservation_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 rounded-lg" style="background-color: #fffacd;">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" style="color: #856404;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">접수</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['reception_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 rounded-lg" style="background-color: #f5deb3;">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" style="color: #8b4513;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">배차</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['dispatch_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 rounded-lg" style="background-color: #d4edda;">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" style="color: #155724;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">운행</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['driving_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 rounded-lg" style="background-color: #ffffff; border: 1px solid #e0e0e0;">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" style="color: #333333;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">완료</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['completed_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 rounded-lg" style="background-color: #f5deb3;">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" style="color: #8b4513;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">대기</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['waiting_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 rounded-lg" style="background-color: #fee2e2;">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-2 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">취소</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-900"><?= $stats['cancelled_orders'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 메인 콘텐츠 영역 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- 최근 주문 -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-4 py-2 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">오늘의 주문</h3>
                        </div>
                        <a href="<?= base_url('delivery/list') ?>" class="text-xs text-blue-600 hover:text-blue-800">전체보기</a>
                    </div>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">인성주문번호</th>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">접수일자</th>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">예약일</th>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">상태</th>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">담당자명</th>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">출발지고객명</th>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">도착지고객명</th>
                                    <th class="px-3 py-1.5 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b">배송수단</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recent_orders as $order): ?>
                                <?php
                                // 상태값 처리 (배송조회와 동일한 로직)
                                $statusLabel = '-';
                                $statusClass = '';
                                
                                if (($order['order_system'] ?? '') === 'insung') {
                                    // 인성 API 주문: state 값 처리
                                    $stateValue = $order['state'] ?? '';
                                    $stateLabels = [
                                        '10' => '접수',
                                        '11' => '배차',
                                        '12' => '운행',
                                        '20' => '대기',
                                        '30' => '완료',
                                        '40' => '취소',
                                        '50' => '문의',
                                        '90' => '예약'
                                    ];
                                    
                                    // 한글 상태값도 처리
                                    if (in_array($stateValue, ['예약', '접수', '배차', '운행', '완료', '대기', '취소', '문의'])) {
                                        $statusLabel = $stateValue;
                                        $labelToCode = array_flip($stateLabels);
                                        $stateCode = $labelToCode[$stateValue] ?? $stateValue;
                                        $statusClass = 'state-' . $stateCode;
                                    } elseif (isset($stateLabels[$stateValue])) {
                                        $statusLabel = $stateLabels[$stateValue];
                                        $statusClass = 'state-' . $stateValue;
                                    } else {
                                        $statusLabel = $stateValue ?: '-';
                                        $statusClass = 'state-' . preg_replace('/\s+/', '', $statusLabel);
                                    }
                                } else {
                                    // 일반 주문: status 값 처리
                                    $statusLabels = [
                                        'pending' => '대기중',
                                        'processing' => '접수완료',
                                        'completed' => '배송중',
                                        'delivered' => '배송완료',
                                        'cancelled' => '취소',
                                        'api_failed' => 'API실패'
                                    ];
                                    $statusValue = $order['status'] ?? '';
                                    $statusLabel = $statusLabels[$statusValue] ?? ($statusValue ?: '-');
                                    $statusClass = 'status-' . $statusValue;
                                }
                                
                                // 주문번호 표시 (인성 주문번호 우선)
                                $displayOrderNumber = '-';
                                if (($order['order_system'] ?? '') === 'insung' && !empty($order['insung_order_number'])) {
                                    $displayOrderNumber = $order['insung_order_number'];
                                } elseif (!empty($order['insung_order_number'])) {
                                    $displayOrderNumber = $order['insung_order_number'];
                                } elseif (!empty($order['order_number'])) {
                                    $displayOrderNumber = $order['order_number'];
                                }
                                
                                // 접수일자
                                $orderDate = $order['order_date'] ?? '';
                                $orderTime = $order['order_time'] ?? '';
                                $receptionDate = '';
                                if ($orderDate && $orderTime) {
                                    $receptionDate = $orderDate . ' ' . $orderTime;
                                } elseif ($orderDate) {
                                    $receptionDate = $orderDate;
                                } else {
                                    $receptionDate = '-';
                                }
                                
                                // 배송수단
                                $deliveryMethod = $order['car_type'] ?? $order['delivery_method'] ?? '-';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-1.5 text-sm sm:text-xs"><?= esc($displayOrderNumber) ?></td>
                                    <td class="px-3 py-1.5 text-sm sm:text-xs"><?= esc($receptionDate) ?></td>
                                    <td class="px-3 py-1.5 text-sm sm:text-xs"><?= esc($order['reserve_date'] ?? '-') ?></td>
                                    <td class="px-3 py-1.5 text-sm sm:text-xs">
                                        <span class="status-badge <?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span>
                                    </td>
                                    <td class="px-3 py-1.5 text-sm sm:text-xs"><?= esc($order['customer_duty'] ?? '-') ?></td>
                                    <td class="px-3 py-1.5 text-sm sm:text-xs"><?= esc($order['departure_company_name'] ?? '-') ?></td>
                                    <td class="px-3 py-1.5 text-sm sm:text-xs"><?= esc($order['destination_company_name'] ?? '-') ?></td>
                                    <td class="px-3 py-1.5 text-sm sm:text-xs"><?= esc($deliveryMethod) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
            <!--
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
                    <div class="pt-3 border-t border-gray-200">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">DB 서버</span>
                                <span class="text-xs font-medium text-gray-900"><?= htmlspecialchars($db_info['hostname'] ?? 'unknown') ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">DB 이름</span>
                                <span class="text-xs font-medium text-gray-900"><?= htmlspecialchars($db_info['database'] ?? 'unknown') ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">포트</span>
                                <span class="text-xs font-medium text-gray-900"><?= htmlspecialchars($db_info['port'] ?? '3306') ?></span>
                            </div>
                            <?php if (isset($db_info['source'])): ?>
                            <div class="pt-2 mt-2 border-t border-gray-200">
                                <div class="text-xs text-gray-400 space-y-1">
                                    <div>설정 위치:</div>
                                    <div class="pl-2">
                                        <div>• env 파일: <?= htmlspecialchars($db_info['source']['env_file_exists']) ?></div>
                                        <div>• 환경변수: <?= htmlspecialchars($db_info['source']['env_hostname']) ?></div>
                                        <div>• Config 값: <?= htmlspecialchars($db_info['source']['config_hostname']) ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            -->
        </div>
    </div>
</div>

<?php if ($loginType === 'daumdata'): ?>
<!--
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
-->
<?php endif; ?>

<?= $this->endSection() ?>