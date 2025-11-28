<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('/delivery/list', ['method' => 'GET']) ?>
        <div class="search-filter-container">
            <div class="search-filter-item">
                <label class="search-filter-label">검색</label>
                <select name="search_type" class="search-filter-select">
                    <?php foreach ($search_type_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $search_type === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">검색어</label>
                <input type="text" name="search_keyword" value="<?= esc($search_keyword) ?>" placeholder="검색어 입력" class="search-filter-input">
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">배송상태</label>
                <select name="status" class="search-filter-select">
                    <?php foreach ($status_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $status_filter === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">서비스</label>
                <select name="service" class="search-filter-select">
                    <option value="all" <?= $service_filter === 'all' ? 'selected' : '' ?>>전체</option>
                    <?php foreach ($service_types as $service): ?>
                        <option value="<?= $service['service_category'] ?>" <?= $service_filter === $service['service_category'] ? 'selected' : '' ?>>
                            <?= ucfirst($service['service_category']) ?> (<?= $service['count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button">🔍 검색</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 검색 결과 정보 -->
    <div class="mb-4 px-2 md:px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="text-sm text-gray-700">
                <?php if (isset($pagination) && $pagination): ?>
                    총 <?= number_format($pagination['total_count']) ?>건 중 
                    <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>건 표시
                <?php else: ?>
                    검색 결과가 없습니다.
                <?php endif; ?>
            </div>
            <?php if (isset($db_error)): ?>
                <div class="text-sm text-red-600">
                    <?= esc($db_error) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 배송 목록 테이블 -->
    <div class="list-table-container">
        <?php if (isset($error)): ?>
            <div class="text-center py-8 text-red-600">
                <?= esc($error) ?>
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="text-center py-8 text-gray-500">
                검색 결과가 없습니다.
            </div>
        <?php else: ?>
        <style>
        /* 브라우저 전체 스크롤 방지 - 하지만 테이블 스크롤은 허용 */
        body {
            overflow-x: hidden !important;
        }
        
        html {
            overflow-x: hidden !important;
        }
        
        /* 페이지 전체 컨테이너 - 브라우저 스크롤 방지 */
        .list-page-container {
            width: 100%;
            max-width: calc(100vw - 280px); /* sidebar 너비(약 280px) 제외 */
            overflow-x: hidden;
            box-sizing: border-box;
            position: relative;
        }
        
        /* 메인 컨텐츠 영역 */
        .list-page-container > * {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* 모바일에서는 sidebar가 없으므로 100% */
        @media (max-width: 1023px) {
            .list-page-container {
                max-width: 100vw;
            }
        }
        
        /* 검색 영역 - 항상 보이도록 */
        .search-compact {
            width: 100%;
            max-width: 100%;
            overflow-x: visible;
            box-sizing: border-box;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        
        /* 검색 필터 컨테이너 */
        .search-filter-container {
            display: flex;
            gap: 16px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        /* 검색 필터 아이템 */
        .search-filter-item {
            flex: 1;
            min-width: 150px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        /* 검색 버튼 래퍼 */
        .search-filter-button-wrapper {
            display: flex;
            align-items: flex-end;
            flex-shrink: 0;
        }
        
        /* 검색 필터 라벨 */
        .search-filter-label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            letter-spacing: 0.2px;
        }
        
        /* 검색 필터 입력 필드 */
        .search-filter-input,
        .search-filter-select {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            color: #1e293b;
            transition: all 0.2s ease;
        }
        
        .search-filter-input:focus,
        .search-filter-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: #ffffff;
        }
        
        .search-filter-input::placeholder {
            color: #94a3b8;
        }
        
        /* 검색 버튼 */
        .search-button {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            background: #6366f1;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            width: auto;
            min-width: auto;
        }
        
        .search-button:hover {
            background: #4f46e5;
        }
        
        .search-button:active {
            background: #4338ca;
        }
        
        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .search-filter-container {
                flex-direction: column;
            }
            
            .search-filter-item {
                width: 100%;
                min-width: 100%;
            }
            
            .search-button {
                width: 100%;
            }
        }
        
        /* 검색 결과 정보 - 항상 보이도록 */
        .mb-4 {
            width: 100%;
            max-width: 100%;
            overflow-x: visible;
            box-sizing: border-box;
        }
        
        /* 테이블 컨테이너 - 스크롤 영역 */
        .list-table-container {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            box-sizing: border-box;
        }
        
        /* 테이블 래퍼 - 내부 스크롤만 */
        .delivery-list-table-wrapper {
            position: relative;
            overflow-x: auto !important;
            overflow-y: auto !important;
            width: 100%;
            max-width: calc(100vw - 280px); /* sidebar 너비(약 280px) 제외 */
            max-height: calc(100vh - 300px);
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            box-sizing: border-box;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }
        
        /* 모바일에서는 sidebar가 없으므로 100% */
        @media (max-width: 1023px) {
            .delivery-list-table-wrapper {
                max-width: 100vw;
            }
        }
        
        /* 스크롤바 스타일링 */
        .delivery-list-table-wrapper::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        .delivery-list-table-wrapper::-webkit-scrollbar-track {
            background: #f7fafc;
        }
        
        .delivery-list-table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        
        .delivery-list-table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        /* 테이블 - 최소 너비 설정 */
        .delivery-list-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: max-content;
            table-layout: auto;
            position: relative;
        }
        
        /* 고정 컬럼: 주문번호 (왼쪽) */
        .delivery-list-table th:first-child,
        .delivery-list-table td:first-child {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
            z-index: 10;
            background: #fff;
            border-right: 2px solid #e5e7eb;
            min-width: 200px;
            max-width: 200px;
        }
        
        .delivery-list-table thead th:first-child {
            z-index: 12;
            background: #f8fafc;
        }
        
        .delivery-list-table tbody tr:hover td:first-child {
            background: #f9fafb;
        }
        
        /* 고정 컬럼: 상태 (오른쪽) */
        .delivery-list-table th.status-col,
        .delivery-list-table td.status-col {
            position: -webkit-sticky;
            position: sticky;
            right: 150px;
            z-index: 20;
            background: #fff !important;
            border-left: 2px solid #e5e7eb;
            min-width: 100px;
            max-width: 100px;
            box-shadow: -2px 0 4px rgba(0, 0, 0, 0.05);
        }
        
        .delivery-list-table thead th.status-col {
            z-index: 21;
            background: #f8fafc !important;
        }
        
        .delivery-list-table tbody tr:hover td.status-col {
            background: #f9fafb !important;
        }
        
        /* 고정 컬럼: 액션 (오른쪽 끝) */
        .delivery-list-table th.action-col,
        .delivery-list-table td.action-col {
            position: -webkit-sticky;
            position: sticky;
            right: 0;
            z-index: 20;
            background: #fff !important;
            border-left: 2px solid #e5e7eb;
            min-width: 150px;
            max-width: 150px;
            white-space: nowrap;
            box-shadow: -2px 0 4px rgba(0, 0, 0, 0.05);
        }
        
        .delivery-list-table thead th.action-col {
            z-index: 21;
            background: #f8fafc !important;
        }
        
        .delivery-list-table tbody tr:hover td.action-col {
            background: #f9fafb !important;
        }
        
        /* 액션 버튼 영역 - 개행 방지 */
        .delivery-list-table td.action-col {
            white-space: nowrap;
        }
        
        .delivery-list-table td.action-col span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        
        .delivery-list-table td.action-col button {
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        /* 테이블 헤더/셀 기본 스타일 */
        .delivery-list-table th,
        .delivery-list-table td {
            padding: 8px 12px;
            text-align: left;
            white-space: nowrap;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .delivery-list-table th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 12px;
            position: sticky;
            top: 0;
            z-index: 9;
        }
        
        .delivery-list-table td {
            font-size: 12px;
        }
        
        .delivery-list-table tbody tr:hover {
            background: #f9fafb;
        }
        
        /* 페이징 영역 - 항상 보이도록, 중앙 정렬 */
        .list-pagination {
            width: 100%;
            max-width: 100%;
            overflow-x: visible;
            box-sizing: border-box;
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .list-pagination .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            width: 100%;
        }
        </style>
        
        <div class="delivery-list-table-wrapper">
        <table class="delivery-list-table">
            <thead>
                <tr>
                    <th style="min-width: 200px;">주문번호</th>
                    <th>주문자회사명</th>
                    <th>주문자연락처</th>
                    <th>출발지상호</th>
                    <th>출발지연락처</th>
                    <th>도착지상호</th>
                    <th>도착지연락처</th>
                    <th>물품종류</th>
                    <th>수량</th>
                    <th>주문일자</th>
                    <th>주문시간</th>
                    <th class="status-col" style="min-width: 100px;">상태</th>
                    <th class="action-col" style="min-width: 120px;">액션</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td style="white-space: nowrap;">
                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                            <?= esc($order['order_number'] ?? '-') ?>
                            <?php 
                            // 접수완료 상태이고 송장번호가 있을 때 송장출력 버튼 표시
                            // 해외특송 또는 택배 서비스인지 확인
                            $serviceName = $order['service_name'] ?? '';
                            $serviceCategory = $order['service_category'] ?? '';
                            $serviceCode = $order['service_code'] ?? '';
                            $trackingNumber = $order['shipping_tracking_number'] ?? '';
                            
                            $isShippingService = (
                                $serviceCategory === 'international' || 
                                $serviceCategory === 'parcel' ||
                                $serviceCategory === 'special' ||
                                $serviceCategory === '해외특송서비스' ||
                                $serviceCode === 'international' ||
                                $serviceCode === 'parcel-visit' ||
                                $serviceCode === 'parcel-same-day' ||
                                $serviceCode === 'parcel-convenience' ||
                                $serviceCode === 'parcel-night' ||
                                $serviceCode === 'parcel-bag' ||
                                strpos($serviceName, '해외특송') !== false ||
                                strpos($serviceName, '택배') !== false ||
                                strpos($serviceName, '편의점') !== false ||
                                strpos($serviceName, '방문택배') !== false ||
                                strpos($serviceName, '당일택배') !== false ||
                                strpos($serviceName, '야간배송') !== false
                            );
                            
                            $showWaybillBtn = (
                                ($order['status'] ?? '') === 'processing' &&
                                !empty($trackingNumber) &&
                                $trackingNumber !== '' &&
                                $isShippingService
                            );
                            
                            if ($showWaybillBtn): ?>
                                <button onclick="printWaybill('<?= esc($order['order_number']) ?>', '<?= esc($trackingNumber) ?>')" 
                                        class="form-button form-button-secondary" style="padding: 2px 8px; font-size: 11px; height: 20px; display: inline-block;">
                                    송장출력
                                </button>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td><?= esc($order['company_name'] ?? '-') ?></td>
                    <td><?= esc($order['contact'] ?? '-') ?></td>
                    <td><?= esc($order['departure_company_name'] ?? '-') ?></td>
                    <td><?= esc($order['departure_contact'] ?? '-') ?></td>
                    <td><?= esc($order['destination_company_name'] ?? '-') ?></td>
                    <td><?= esc($order['destination_contact'] ?? '-') ?></td>
                    <td><?= esc($order['item_type'] ?? '-') ?></td>
                    <td><?= esc($order['quantity'] ?? '-') ?></td>
                    <td><?= esc($order['order_date'] ?? '-') ?></td>
                    <td><?= esc($order['order_time'] ?? '-') ?></td>
                    <td class="status-col">
                        <?php
                        $statusLabels = [
                            'pending' => '대기중',
                            'processing' => '접수완료',
                            'completed' => '배송중',
                            'delivered' => '배송완료',
                            'cancelled' => '취소',
                            'api_failed' => 'API실패'
                        ];
                        $statusLabel = $statusLabels[$order['status'] ?? ''] ?? ($order['status'] ?? '-');
                        ?>
                        <span class="status-badge status-<?= esc($order['status'] ?? '') ?>"><?= $statusLabel ?></span>
                    </td>
                    <td class="action-col">
                        <span style="display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">
                            <button onclick="viewOrderDetail('<?= esc($order['encrypted_order_number'] ?? '') ?>')" style="white-space: nowrap; flex-shrink: 0;">상세</button>
                            <?php if (($order['status'] ?? '') === 'pending'): ?>
                                <button onclick="cancelOrder(<?= $order['id'] ?? 0 ?>)" style="white-space: nowrap; flex-shrink: 0;">취소</button>
                            <?php endif; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if (isset($pagination) && $pagination): ?>
    <div class="list-pagination">
        <div class="pagination">
            <?php if ($pagination['has_prev']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="nav-button">처음</a>
            <?php else: ?>
                <span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">처음</span>
            <?php endif; ?>
            
            <?php if ($pagination['has_prev']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" class="nav-button">이전</a>
            <?php else: ?>
                <span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">이전</span>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $pagination['current_page'] - 2);
            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
                $isActive = $i == $pagination['current_page'];
                $queryParams = array_merge($_GET, ['page' => $i]);
            ?>
                <a href="?<?= http_build_query($queryParams) ?>" class="page-number <?= $isActive ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($pagination['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" class="nav-button">다음</a>
            <?php else: ?>
                <span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">다음</span>
            <?php endif; ?>
            
            <?php if ($pagination['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']])) ?>" class="nav-button">마지막</a>
            <?php else: ?>
                <span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">마지막</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- 주문 상세 팝업 모달 -->
<div id="orderDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">주문 상세 정보</h3>
            <button type="button" onclick="closeOrderDetail()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-4">
            <!-- 내용은 restoreModalContent()에서 동적으로 생성됩니다 -->
            <div class="modal-content">
            </div>
        </div>
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex justify-end gap-2">
            <button class="form-button form-button-secondary" onclick="closeOrderDetail()">닫기</button>
        </div>
    </div>
</div>

<style>
/* 모달 콘텐츠 */
.modal-content {
    padding: 0 !important;
}

.detail-section {
    margin-bottom: 24px !important;
}

.detail-section:last-child {
    margin-bottom: 0 !important;
}

.detail-section h4 {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    margin: 0 0 12px 0 !important;
    padding-bottom: 8px !important;
    border-bottom: 1px solid #e5e7eb !important;
}

.detail-grid {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 16px !important;
}

.detail-item {
    display: flex !important;
    flex-direction: column !important;
}

.detail-item.full-width {
    grid-column: 1 / -1 !important;
}

.detail-item label {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #6b7280 !important;
    margin-bottom: 4px !important;
}

.detail-item span {
    font-size: 13px !important;
    color: #374151 !important;
    padding: 6px 8px !important;
    background: #f9fafb !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 4px !important;
    min-height: 20px !important;
    word-break: break-word !important;
}

/* 반응형 */
@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function viewOrderDetail(encryptedOrderNumber) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 로딩 상태 표시
    showLoadingState();
    
    // AJAX로 주문 상세 정보 가져오기 (이미 암호화된 주문번호 사용)
    fetch(`/delivery/getOrderDetail?order_number=${encryptedOrderNumber}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            populateOrderDetail(data.data);
            // 모달 표시
            document.getElementById('orderDetailModal').classList.remove('hidden');
            document.getElementById('orderDetailModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        } else {
            showError(data.message || '주문 정보를 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        showError('주문 정보 조회 중 오류가 발생했습니다.');
    })
    .finally(() => {
        hideLoadingState();
    });
}

function populateOrderDetail(orderData) {
    // 모달 콘텐츠를 원래 상태로 복원
    restoreModalContent();
    
    // 헬퍼 함수: 값이 있으면 표시, 없으면 '-'
    const getValue = (value) => {
        if (value === null || value === undefined || value === '') return '-';
        return value;
    };
    
    // 헬퍼 함수: 날짜 포맷팅
    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        try {
            return new Date(dateStr).toLocaleString('ko-KR');
        } catch (e) {
            return dateStr;
        }
    };
    
    // 헬퍼 함수: 날짜만 포맷팅
    const formatDateOnly = (dateStr) => {
        if (!dateStr) return '-';
        try {
            return new Date(dateStr).toLocaleDateString('ko-KR');
        } catch (e) {
            return dateStr;
        }
    };
    
    // 헬퍼 함수: 숫자 포맷팅 (금액)
    const formatAmount = (amount) => {
        if (!amount || amount === 0) return '0원';
        return new Intl.NumberFormat('ko-KR').format(amount) + '원';
    };
    
    // 헬퍼 함수: 숫자 포맷팅 (거리)
    const formatDistance = (distance) => {
        if (!distance || distance === 0) return '0.0km';
        return distance + 'km';
    };
    
    // 헬퍼 함수: 불린값 포맷팅
    const formatBoolean = (value) => {
        if (value === null || value === undefined) return '-';
        return value ? '예' : '아니오';
    };
    
    // 기본 정보
    setElementText('detail-order-number', getValue(orderData.order_number));
    setElementText('detail-service', getValue(orderData.service_name));
    setElementText('detail-customer', getValue(orderData.customer_name));
    setElementText('detail-user-name', getValue(orderData.user_name));
    setElementText('detail-created-at', formatDate(orderData.created_at));
    setElementText('detail-updated-at', formatDate(orderData.updated_at));
    
    // 상태 변경 select box에 현재 상태값 설정
    const statusSelect = document.getElementById('status-select');
    if (statusSelect) {
        statusSelect.value = orderData.status || 'pending';
    }
    
    // 송장출력 버튼 표시
    const waybillPrintSection = document.getElementById('waybill-print-section');
    const serviceCategory = orderData.service_category || '';
    const serviceCode = orderData.service_code || '';
    const trackingNumber = orderData.shipping_tracking_number || '';
    
    if (waybillPrintSection) {
        const isEligible = (
            orderData.status === 'processing' &&
            trackingNumber &&
            trackingNumber.trim() !== '' &&
            (serviceCategory === 'international' || 
             serviceCategory === 'parcel' ||
             serviceCode === 'parcel-visit' ||
             serviceCode === 'parcel-same-day' ||
             serviceCode === 'parcel-convenience' ||
             serviceCode === 'parcel-night' ||
             serviceCode === 'parcel-bag')
        );
        
        if (isEligible) {
            waybillPrintSection.style.display = 'block';
        } else {
            waybillPrintSection.style.display = 'none';
        }
    }
    
    // 주문번호 및 송장번호 저장 (전역 변수)
    window.currentOrderNumber = orderData.order_number;
    window.currentTrackingNumber = trackingNumber || '';
    
    // 주문자 정보
    setElementText('detail-company-name', getValue(orderData.company_name));
    setElementText('detail-contact', getValue(orderData.contact));
    setElementText('detail-address', getValue(orderData.address));
    
    // 출발지 정보
    setElementText('detail-departure-company-name', getValue(orderData.departure_company_name));
    setElementText('detail-departure-contact', getValue(orderData.departure_contact));
    setElementText('detail-departure-department', getValue(orderData.departure_department));
    setElementText('detail-departure-manager', getValue(orderData.departure_manager));
    setElementText('detail-departure-dong', getValue(orderData.departure_dong));
    setElementText('detail-departure-address', getValue(orderData.departure_address));
    setElementText('detail-departure-detail', getValue(orderData.departure_detail));
    
    // 경유지 정보 (값이 있으면 표시)
    const waypointSection = document.getElementById('waypoint-section');
    if (waypointSection && (orderData.waypoint_address || orderData.waypoint_detail || orderData.waypoint_contact || orderData.waypoint_notes)) {
        waypointSection.style.display = 'block';
        setElementText('detail-waypoint-address', getValue(orderData.waypoint_address));
        setElementText('detail-waypoint-detail', getValue(orderData.waypoint_detail));
        setElementText('detail-waypoint-contact', getValue(orderData.waypoint_contact));
        setElementText('detail-waypoint-notes', getValue(orderData.waypoint_notes));
    } else if (waypointSection) {
        waypointSection.style.display = 'none';
    }
    
    // 도착지 정보
    setElementText('detail-destination-type', getValue(orderData.destination_type));
    setElementText('detail-mailroom', getValue(orderData.mailroom));
    setElementText('detail-destination-company-name', getValue(orderData.destination_company_name));
    setElementText('detail-destination-contact', getValue(orderData.destination_contact));
    setElementText('detail-destination-department', getValue(orderData.destination_department));
    setElementText('detail-destination-manager', getValue(orderData.destination_manager));
    setElementText('detail-destination-dong', getValue(orderData.destination_dong));
    setElementText('detail-destination-address', getValue(orderData.destination_address));
    setElementText('detail-detail-address', getValue(orderData.detail_address));
    
    // 물품 정보
    setElementText('detail-item-type', getValue(orderData.item_type));
    setElementText('detail-quantity', getValue(orderData.quantity));
    setElementText('detail-unit', getValue(orderData.unit));
    setElementText('detail-delivery-content', getValue(orderData.delivery_content));
    
    // 과적 정보 (값이 있으면 표시)
    const overloadSection = document.getElementById('overload-section');
    if (overloadSection && (orderData.box_medium_overload || orderData.pouch_medium_overload || orderData.bag_medium_overload)) {
        overloadSection.style.display = 'block';
        setElementText('detail-box-medium-overload', formatBoolean(orderData.box_medium_overload));
        setElementText('detail-pouch-medium-overload', formatBoolean(orderData.pouch_medium_overload));
        setElementText('detail-bag-medium-overload', formatBoolean(orderData.bag_medium_overload));
    } else if (overloadSection) {
        overloadSection.style.display = 'none';
    }
    
    // 대리운전 정보 (값이 있으면 표시)
    const driverSection = document.getElementById('driver-section');
    if (driverSection && (orderData.call_type || orderData.total_fare || orderData.postpaid_fare || orderData.distance || orderData.cash_fare)) {
        driverSection.style.display = 'block';
        setElementText('detail-call-type', getValue(orderData.call_type) === 'driver' ? '대리' : (getValue(orderData.call_type) === 'consignment' ? '탁송' : getValue(orderData.call_type)));
        setElementText('detail-total-fare', formatAmount(orderData.total_fare));
        setElementText('detail-postpaid-fare', formatAmount(orderData.postpaid_fare));
        setElementText('detail-distance', formatDistance(orderData.distance));
        setElementText('detail-cash-fare', formatAmount(orderData.cash_fare));
    } else if (driverSection) {
        driverSection.style.display = 'none';
    }
    
    // 배송/결제 정보
    setElementText('detail-total-amount', formatAmount(orderData.total_amount));
    setElementText('detail-payment-type', getValue(orderData.payment_type));
    setElementText('detail-order-date', formatDateOnly(orderData.order_date));
    setElementText('detail-order-time', getValue(orderData.order_time));
    setElementText('detail-notification-service', formatBoolean(orderData.notification_service));
    
    // 운송 정보 (값이 있으면 표시)
    const shippingSection = document.getElementById('shipping-section');
    if (shippingSection && (orderData.shipping_platform_code || orderData.shipping_tracking_number)) {
        shippingSection.style.display = 'block';
        setElementText('detail-shipping-platform-code', getValue(orderData.shipping_platform_code));
        setElementText('detail-shipping-tracking-number', getValue(orderData.shipping_tracking_number));
    } else if (shippingSection) {
        shippingSection.style.display = 'none';
    }
    
    // 퀵서비스 정보 (값이 있으면 표시)
    const quickSection = document.getElementById('quick-section');
    if (quickSection && (orderData.delivery_method || orderData.urgency_level || orderData.estimated_time || 
        orderData.pickup_time || orderData.delivery_time || orderData.driver_contact || orderData.vehicle_info ||
        orderData.delivery_route || orderData.delivery_instructions || orderData.box_selection || 
        orderData.box_quantity || orderData.pouch_selection || orderData.pouch_quantity || 
        orderData.shopping_bag_selection || orderData.additional_fee)) {
        quickSection.style.display = 'block';
        setElementText('detail-delivery-method', getValue(orderData.delivery_method));
        setElementText('detail-urgency-level', getValue(orderData.urgency_level));
        setElementText('detail-estimated-time', getValue(orderData.estimated_time) !== '-' ? getValue(orderData.estimated_time) + '분' : '-');
        setElementText('detail-pickup-time', formatDate(orderData.pickup_time));
        setElementText('detail-delivery-time', formatDate(orderData.delivery_time));
        setElementText('detail-driver-contact', getValue(orderData.driver_contact));
        setElementText('detail-vehicle-info', getValue(orderData.vehicle_info));
        setElementText('detail-delivery-route', getValue(orderData.delivery_route));
        setElementText('detail-delivery-instructions', getValue(orderData.delivery_instructions));
        setElementText('detail-box-selection', getValue(orderData.box_selection));
        setElementText('detail-box-quantity', getValue(orderData.box_quantity));
        setElementText('detail-pouch-selection', getValue(orderData.pouch_selection));
        setElementText('detail-pouch-quantity', getValue(orderData.pouch_quantity));
        setElementText('detail-shopping-bag-selection', getValue(orderData.shopping_bag_selection));
        setElementText('detail-additional-fee', formatAmount(orderData.additional_fee));
    } else if (quickSection) {
        quickSection.style.display = 'none';
    }
    
    // 기타 정보
    setElementText('detail-notes', getValue(orderData.notes));
}

// 헬퍼 함수: 요소에 텍스트 설정
function setElementText(id, text) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = text;
    }
}

function showLoadingState() {
    // 로딩 상태 표시 (모달 내부에 로딩 메시지)
    const modalContent = document.querySelector('.modal-content');
    modalContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">주문 정보를 불러오는 중...</div>';
    
    // 모달 표시
    document.getElementById('orderDetailModal').classList.remove('hidden');
    document.getElementById('orderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function hideLoadingState() {
    // 로딩 상태는 populateOrderDetail에서 실제 내용으로 대체됨
}

function showError(message) {
    // 에러 메시지 표시
    const modalContent = document.querySelector('.modal-content');
    modalContent.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="color: #ef4444; margin-bottom: 16px;">⚠️</div>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">오류 발생</div>
            <div style="color: #6b7280;">${message}</div>
        </div>
    `;
    
    // 모달 표시
    document.getElementById('orderDetailModal').classList.remove('hidden');
    document.getElementById('orderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeOrderDetail() {
    document.getElementById('orderDetailModal').classList.add('hidden');
    document.getElementById('orderDetailModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
    
    // 모달 콘텐츠를 원래 상태로 복원
    restoreModalContent();
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

function restoreModalContent() {
    const modalContent = document.querySelector('.modal-content');
    modalContent.innerHTML = `
        <div class="detail-section">
            <h4>기본 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>주문번호</label>
                    <span id="detail-order-number">-</span>
                </div>
                <div class="detail-item">
                    <label>서비스</label>
                    <span id="detail-service">-</span>
                </div>
                <div class="detail-item">
                    <label>고객사</label>
                    <span id="detail-customer">-</span>
                </div>
                <div class="detail-item">
                    <label>주문자</label>
                    <span id="detail-user-name">-</span>
                </div>
                <div class="detail-item">
                    <label>상태</label>
                    <div id="status-change-section" style="display: flex; align-items: center; gap: 8px;">
                        <select id="status-select" class="form-input" style="width: 150px;">
                            <option value="pending">대기중</option>
                            <option value="processing">접수완료</option>
                            <option value="completed">배송중</option>
                            <option value="delivered">배송완료</option>
                            <option value="api_failed">API실패</option>
                        </select>
                        <button onclick="updateOrderStatus()" class="form-button form-button-primary" style="padding: 4px 12px; white-space: nowrap;">변경</button>
                    </div>
                    <div id="waybill-print-section" style="display: none; margin-top: 8px;">
                        <button onclick="printWaybillFromDetail()" class="form-button form-button-secondary" style="padding: 4px 12px;">
                            송장출력
                        </button>
                    </div>
                </div>
                <div class="detail-item">
                    <label>생성일시</label>
                    <span id="detail-created-at">-</span>
                </div>
                <div class="detail-item">
                    <label>수정일시</label>
                    <span id="detail-updated-at">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>주문자 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>회사명</label>
                    <span id="detail-company-name">-</span>
                </div>
                <div class="detail-item">
                    <label>연락처</label>
                    <span id="detail-contact">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>주소</label>
                    <span id="detail-address">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>출발지 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>상호</label>
                    <span id="detail-departure-company-name">-</span>
                </div>
                <div class="detail-item">
                    <label>연락처</label>
                    <span id="detail-departure-contact">-</span>
                </div>
                <div class="detail-item">
                    <label>부서</label>
                    <span id="detail-departure-department">-</span>
                </div>
                <div class="detail-item">
                    <label>담당</label>
                    <span id="detail-departure-manager">-</span>
                </div>
                <div class="detail-item">
                    <label>동</label>
                    <span id="detail-departure-dong">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>주소</label>
                    <span id="detail-departure-address">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>상세주소</label>
                    <span id="detail-departure-detail">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section" id="waypoint-section" style="display: none;">
            <h4>경유지 정보</h4>
            <div class="detail-grid">
                <div class="detail-item full-width">
                    <label>주소</label>
                    <span id="detail-waypoint-address">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>상세주소</label>
                    <span id="detail-waypoint-detail">-</span>
                </div>
                <div class="detail-item">
                    <label>연락처</label>
                    <span id="detail-waypoint-contact">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>특이사항</label>
                    <span id="detail-waypoint-notes">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>도착지 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>타입</label>
                    <span id="detail-destination-type">-</span>
                </div>
                <div class="detail-item">
                    <label>메일룸</label>
                    <span id="detail-mailroom">-</span>
                </div>
                <div class="detail-item">
                    <label>상호</label>
                    <span id="detail-destination-company-name">-</span>
                </div>
                <div class="detail-item">
                    <label>연락처</label>
                    <span id="detail-destination-contact">-</span>
                </div>
                <div class="detail-item">
                    <label>부서</label>
                    <span id="detail-destination-department">-</span>
                </div>
                <div class="detail-item">
                    <label>담당</label>
                    <span id="detail-destination-manager">-</span>
                </div>
                <div class="detail-item">
                    <label>동</label>
                    <span id="detail-destination-dong">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>주소</label>
                    <span id="detail-destination-address">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>상세주소</label>
                    <span id="detail-detail-address">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>물품 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>물품종류</label>
                    <span id="detail-item-type">-</span>
                </div>
                <div class="detail-item">
                    <label>수량</label>
                    <span id="detail-quantity">-</span>
                </div>
                <div class="detail-item">
                    <label>단위</label>
                    <span id="detail-unit">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>배송내용</label>
                    <span id="detail-delivery-content">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section" id="overload-section" style="display: none;">
            <h4>과적 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>박스 중형 과적</label>
                    <span id="detail-box-medium-overload">-</span>
                </div>
                <div class="detail-item">
                    <label>행낭 중형 과적</label>
                    <span id="detail-pouch-medium-overload">-</span>
                </div>
                <div class="detail-item">
                    <label>행낭 중형 과적(택배)</label>
                    <span id="detail-bag-medium-overload">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section" id="driver-section" style="display: none;">
            <h4>대리운전 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>콜타입</label>
                    <span id="detail-call-type">-</span>
                </div>
                <div class="detail-item">
                    <label>합계</label>
                    <span id="detail-total-fare">-</span>
                </div>
                <div class="detail-item">
                    <label>거리</label>
                    <span id="detail-distance">-</span>
                </div>
                <div class="detail-item">
                    <label>현금</label>
                    <span id="detail-cash-fare">-</span>
                </div>
                <div class="detail-item">
                    <label>후불</label>
                    <span id="detail-postpaid-fare">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>배송/결제 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>총액</label>
                    <span id="detail-total-amount">-</span>
                </div>
                <div class="detail-item">
                    <label>결제방식</label>
                    <span id="detail-payment-type">-</span>
                </div>
                <div class="detail-item">
                    <label>주문일자</label>
                    <span id="detail-order-date">-</span>
                </div>
                <div class="detail-item">
                    <label>주문시간</label>
                    <span id="detail-order-time">-</span>
                </div>
                <div class="detail-item">
                    <label>알림서비스</label>
                    <span id="detail-notification-service">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section" id="shipping-section" style="display: none;">
            <h4>운송 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>플랫폼코드</label>
                    <span id="detail-shipping-platform-code">-</span>
                </div>
                <div class="detail-item">
                    <label>송장번호</label>
                    <span id="detail-shipping-tracking-number">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section" id="quick-section" style="display: none;">
            <h4>퀵서비스 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>배송수단</label>
                    <span id="detail-delivery-method">-</span>
                </div>
                <div class="detail-item">
                    <label>배송형태</label>
                    <span id="detail-urgency-level">-</span>
                </div>
                <div class="detail-item">
                    <label>예상소요시간</label>
                    <span id="detail-estimated-time">-</span>
                </div>
                <div class="detail-item">
                    <label>픽업시간</label>
                    <span id="detail-pickup-time">-</span>
                </div>
                <div class="detail-item">
                    <label>배송시간</label>
                    <span id="detail-delivery-time">-</span>
                </div>
                <div class="detail-item">
                    <label>기사연락처</label>
                    <span id="detail-driver-contact">-</span>
                </div>
                <div class="detail-item">
                    <label>차량정보</label>
                    <span id="detail-vehicle-info">-</span>
                </div>
                <div class="detail-item">
                    <label>배송방법</label>
                    <span id="detail-delivery-route">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>배송지시사항</label>
                    <span id="detail-delivery-instructions">-</span>
                </div>
                <div class="detail-item">
                    <label>박스선택</label>
                    <span id="detail-box-selection">-</span>
                </div>
                <div class="detail-item">
                    <label>박스수량</label>
                    <span id="detail-box-quantity">-</span>
                </div>
                <div class="detail-item">
                    <label>행낭선택</label>
                    <span id="detail-pouch-selection">-</span>
                </div>
                <div class="detail-item">
                    <label>행낭수량</label>
                    <span id="detail-pouch-quantity">-</span>
                </div>
                <div class="detail-item">
                    <label>쇼핑백선택</label>
                    <span id="detail-shopping-bag-selection">-</span>
                </div>
                <div class="detail-item">
                    <label>추가요금</label>
                    <span id="detail-additional-fee">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>기타 정보</h4>
            <div class="detail-grid">
                <div class="detail-item full-width">
                    <label>특이사항</label>
                    <span id="detail-notes">-</span>
                </div>
            </div>
        </div>
    `;
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeOrderDetail();
    }
});

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)

// 주문 상태 변경 함수
function updateOrderStatus() {
    const statusSelect = document.getElementById('status-select');
    const newStatus = statusSelect ? statusSelect.value : null;
    const orderNumber = window.currentOrderNumber;
    
    if (!newStatus || !orderNumber) {
        alert('상태를 선택해주세요.');
        return;
    }
    
    if (!confirm('주문 상태를 변경하시겠습니까?')) {
        return;
    }
    
    fetch('/delivery/updateStatus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            order_number: orderNumber,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('주문 상태가 변경되었습니다.');
            // 모달 새로고침
            viewOrderDetail(btoa(orderNumber)); // 간단한 인코딩
            // 리스트 새로고침
            location.reload();
        } else {
            alert('상태 변경 실패: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('상태 변경 중 오류가 발생했습니다.');
    });
}

// 송장출력 함수 (리스트에서)
function printWaybill(orderNumber, trackingNumber) {
    if (!trackingNumber) {
        alert('송장번호가 없습니다.');
        return;
    }
    
    // 새 창에서 송장출력 페이지 열기
    window.open(`/delivery/printWaybill?order_number=${encodeURIComponent(orderNumber)}&tracking_number=${encodeURIComponent(trackingNumber)}`, '_blank', 'width=800,height=1000');
}

// 송장출력 함수 (주문상세 모달에서)
function printWaybillFromDetail() {
    const orderNumber = window.currentOrderNumber;
    const trackingNumber = window.currentTrackingNumber;
    
    if (!orderNumber || !trackingNumber) {
        alert('송장번호가 없습니다.');
        return;
    }
    
    // 새 창에서 송장출력 페이지 열기
    window.open(`/delivery/printWaybill?order_number=${encodeURIComponent(orderNumber)}&tracking_number=${encodeURIComponent(trackingNumber)}`, '_blank', 'width=800,height=1000');
}

function cancelOrder(orderId) {
    // 주문 취소 기능 (추후 구현)
    if (confirm('정말로 이 주문을 취소하시겠습니까?')) {
        alert('주문 취소: ' + orderId);
    }
}

// 인성 API 주문 동기화 (리스트 페이지 접근 시에만 실행)
<?php if (in_array(session()->get('login_type'), ['daumdata', 'stn'])): ?>
// 동기화 중 플래그 (중복 실행 방지)
let isSyncing = false;
let syncIndicator = null;

document.addEventListener('DOMContentLoaded', function() {
    // URL 파라미터로 새로고침 여부 확인 (동기화 후 자동 새로고침인지 체크)
    const urlParams = new URLSearchParams(window.location.search);
    const isAutoReload = urlParams.get('synced') === '1';
    
    // 자동 새로고침이 아닌 경우에만 동기화 실행
    if (!isAutoReload) {
        // 페이지 로드 후 약간의 지연을 두고 동기화 실행
        setTimeout(function() {
            syncInsungOrders();
        }, 1500);
    }
});

function syncInsungOrders() {
    // 이미 동기화 중이면 실행하지 않음
    if (isSyncing) {
        return;
    }
    
    isSyncing = true;
    
    // 기존 인디케이터가 있으면 제거
    if (syncIndicator && syncIndicator.parentNode) {
        syncIndicator.parentNode.removeChild(syncIndicator);
    }
    
    const startTime = Date.now();
    
    fetch('/delivery/syncInsungOrders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // 동기화할 주문이 없으면 종료
        if (data.success && data.total_count === 0) {
            isSyncing = false;
            return;
        }
        
        // 동기화 완료 처리 (UI 표시 없이 백그라운드에서 처리)
        if (data.success) {
            // 동기화된 주문이 있으면 리스트 새로고침 (3초 후)
            // synced=1 파라미터를 추가하여 자동 새로고침임을 표시
            if (data.synced_count > 0) {
                setTimeout(function() {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('synced', '1');
                    window.location.href = currentUrl.toString();
                }, 3000);
            }
        }
        // 동기화 실패 시에도 UI 표시하지 않음 (백그라운드 처리)
    })
    .catch(error => {
        // console.error('Sync error:', error);
        // 에러 발생 시에도 UI 표시하지 않음 (백그라운드 처리)
    })
    .finally(() => {
        isSyncing = false;
    });
}
<?php endif; ?>
</script>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>