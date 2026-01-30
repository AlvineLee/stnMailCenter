<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<style>
/* 삭제된 주문 스타일 */
tr.deleted-order td {
    text-decoration: line-through !important;
    color: #dc2626 !important;
    opacity: 0.8;
}
tr.deleted-order td a {
    text-decoration: line-through !important;
    color: #dc2626 !important;
}
tr.deleted-order td .status-badge {
    text-decoration: line-through !important;
    opacity: 0.7;
}
tr.deleted-order:hover {
    background-color: #fee2e2 !important;
}
</style>
<div class="list-page-container">

    <!-- 검색 조건 펼치기/접기 버튼 -->
    <div class="mb-3 flex justify-end">
        <button type="button" id="toggleSearchBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <span id="toggleSearchText">🔍 검색 조건 펼치기</span>
        </button>
    </div>

    <!-- 검색 및 필터 영역 -->
    <div class="search-compact" id="searchFilterArea" style="display: none;">
        <?= form_open('/delivery/list', ['method' => 'GET']) ?>
        <div class="search-filter-container">
            <div class="search-filter-item">
                <label class="search-filter-label">기간 시작</label>
                <input type="date" name="start_date" value="<?= esc($start_date ?? date('Y-m-d')) ?>" class="search-filter-input">
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">기간 종료</label>
                <input type="date" name="end_date" value="<?= esc($end_date ?? date('Y-m-d')) ?>" class="search-filter-input">
            </div>
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
                        <option value="<?= $service['service_category'] ?? '' ?>" <?= $service_filter === $service['service_category'] ? 'selected' : '' ?>>
                            <?= ucfirst($service['service_category'] ?? '') ?> (<?= $service['count'] ?? 0 ?>)
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

    <!-- 검색 조건 토글 스크립트 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleSearchBtn');
        const searchArea = document.getElementById('searchFilterArea');
        const toggleText = document.getElementById('toggleSearchText');
        
        // 기본적으로는 접혀진 상태 유지 (URL 파라미터와 관계없이)
        
        toggleBtn.addEventListener('click', function() {
            if (searchArea.style.display === 'none' || searchArea.style.display === '') {
                searchArea.style.display = 'block';
                toggleText.textContent = '🔽 검색 조건 접기';
            } else {
                searchArea.style.display = 'none';
                toggleText.textContent = '🔍 검색 조건 펼치기';
            }
        });
    });
    </script>

    <!-- 검색 결과 정보 -->
    <div class="mb-4 px-2 md:px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="text-sm text-gray-700">
                <?php if (isset($pagination) && $pagination): ?>
                    <?php 
                    $paginationInfo = $pagination->getPaginationInfo();
                    ?>
                    총 <?= number_format($paginationInfo['total_items']) ?>건 중 
                    <?= number_format($paginationInfo['start_item']) ?>-<?= number_format($paginationInfo['end_item']) ?>건 표시
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
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr id="table-header-row">
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b delivery-list-header" data-column-index="0">번호</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="1" draggable="true">접수일자</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="2" draggable="true">예약일</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="3" draggable="true">상태</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="4" draggable="true">회사명</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="5" draggable="true">완료시간</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="6" draggable="true">접수부서</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="7" draggable="true">접수담당</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="8" draggable="true">도착지담당명</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="9" draggable="true">전달내용</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="10" draggable="true">상품</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="11" draggable="true">라이더연락처</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="12" draggable="true">주문번호</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="13" draggable="true">출발지고객명</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="14" draggable="true">출발지담당명</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="15" draggable="true">출발지동</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="16" draggable="true">도착지고객명</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="17" draggable="true">도착지동</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="18" draggable="true">지불</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="19" draggable="true">배송</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="20" draggable="true">배송수단</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="21" draggable="true">기사번호</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="22" draggable="true">기사이름</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b delivery-list-header delivery-list-cell-action" data-column-index="23">액션</th>
                </tr>
            </thead>
                <tbody class="divide-y divide-gray-200">
                <?php foreach ($orders as $order): ?>
                <?php
                // 삭제된 주문인지 확인
                $isDeleted = ($order['is_del'] ?? '') === 'Y';
                $deletedRowClass = $isDeleted ? 'deleted-order' : '';
                $deletedRowStyle = $isDeleted ? 'background-color: #fef2f2 !important;' : '';
                ?>
                <tr class="hover:bg-gray-50 <?= $deletedRowClass ?>" style="<?= $deletedRowStyle ?>">
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="0"><?= esc($order['row_number'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="1">
                        <?php
                        $orderDate = $order['order_date'] ?? '';
                        $orderTime = $order['order_time'] ?? '';
                        if ($orderDate && $orderTime) {
                            echo esc($orderDate . ' ' . $orderTime);
                        } elseif ($orderDate) {
                            echo esc($orderDate);
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="2"><?= esc($order['reserve_date'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="3">
                        <?php if ($order['show_map_on_click'] ?? false): ?>
                            <span class="status-badge <?= esc($order['status_class'] ?? '') ?>" style="cursor: pointer;" onclick="openMapView('<?= esc($order['insung_order_number_for_map'] ?? '') ?>', <?= ($order['is_riding'] ?? false) ? 'true' : 'false' ?>)"><?= esc($order['status_label'] ?? '-') ?></span>
                        <?php elseif ($order['show_ilyang_detail'] ?? false): ?>
                            <span class="status-badge <?= esc($order['status_class'] ?? '') ?>" style="cursor: pointer;" onclick="viewIlyangDetail('<?= esc($order['ilyang_tracking_number'] ?? '') ?>')"><?= esc($order['status_label'] ?? '-') ?></span>
                        <?php else: ?>
                            <span class="status-badge <?= esc($order['status_class'] ?? '') ?>"><?= esc($order['status_label'] ?? '-') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="4"><?= esc($order['company_name'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="5"><?= esc($order['complete_time'] ? date('Y-m-d H:i', strtotime($order['complete_time'])) : '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="6"><?= esc($order['customer_department'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="7"><?= esc($order['customer_duty'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="8"><?= esc($order['destination_manager'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="9"><?= esc($order['delivery_content'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="10"><?= esc($order['item_type'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="11"><?= esc($order['rider_tel_number'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm delivery-list-cell-order-number" data-column-index="12">
                        <span class="delivery-list-cell-order-number-content">
                            <?php if ($order['show_insung_order_click'] ?? false): ?>
                                <a href="javascript:void(0)" onclick="viewInsungOrderDetail('<?= esc($order['display_order_number']) ?>')" class="text-blue-600 hover:text-blue-800 no-underline cursor-pointer"><?= esc($order['display_order_number']) ?></a>
                            <?php elseif ($order['show_ilyang_order_click'] ?? false): ?>
                                <a href="javascript:void(0)" onclick="viewIlyangOrderDetail('<?= esc($order['id']) ?>', '/delivery/getIlyangOrderDetail')" class="text-orange-600 hover:text-orange-800 no-underline cursor-pointer"><?= esc($order['display_order_number']) ?></a>
                            <?php else: ?>
                                <?= esc($order['display_order_number'] ?? '-') ?>
                            <?php endif; ?>
                            <?php if ($order['show_waybill_button'] ?? false): ?>
                                <button onclick="printWaybill('<?= esc($order['order_number'] ?? '') ?>', '<?= esc($order['shipping_tracking_number'] ?? '') ?>')"
                                        class="form-button form-button-secondary delivery-list-waybill-button">
                                    송장출력
                                </button>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="14"><?= esc($order['departure_manager'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="15"><?= esc($order['departure_dong'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="16"><?= esc($order['destination_company_name'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="17"><?= esc($order['destination_dong'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="18">
                        <?= esc($order['payment_type_label'] ?? '-') ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="19">
                        <?= esc($order['general_status_label'] ?? '-') ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="20"><?= esc($order['car_type'] ?? ($order['delivery_method'] ?? '-')) ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="21"><?= esc($order['rider_code_no'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="22"><?= esc($order['rider_name'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm delivery-list-cell-action" data-column-index="23">
                        <span class="delivery-list-cell-action-buttons">
                            <button onclick="viewOrderDetail('<?= esc($order['encrypted_order_number'] ?? '') ?>')" class="delivery-list-cell-action-button">상세</button>
                            <?php if (($order['status'] ?? '') === 'pending'): ?>
                                <button onclick="cancelOrder(<?= $order['id'] ?? 0 ?>)" class="delivery-list-cell-action-button">취소</button>
                            <?php endif; ?>
                            <?php if ($order['show_map_on_click'] ?? false): ?>
                                <button onclick="openMapView('<?= esc($order['insung_order_number_for_map'] ?? '') ?>', <?= ($order['is_riding'] ?? false) ? 'true' : 'false' ?>)" class="delivery-list-cell-action-button">
                                    🗺️ 위치
                                </button>
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
        <?= $pagination->render() ?>
    <?php endif; ?>
</div>

<!-- 주문 상세 팝업 모달 -->
<div id="orderDetailModal" class="fixed inset-0 hidden flex items-center justify-center p-4 order-detail-modal" style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto order-detail-modal-content" onclick="event.stopPropagation()">
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

<?php echo view('delivery/ilyang_detail_modal'); ?>

<script src="<?= base_url('assets/js/common-library.js') ?>"></script>
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
    
    // 마스킹 처리는 컨트롤러에서 이미 완료되었으므로 프론트엔드에서는 그냥 표시만 함
    
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
    
    // 컨텐츠 크기에 맞게 팝업 크기 조정 (max-width: 800px + padding + 여유공간)
    const popupWidth = 860;  // 800px 컨텐츠 + 40px padding + 20px 여유
    const popupHeight = 700; // 컨텐츠에 맞게 조정된 높이
    
    // 새 창에서 송장출력 페이지 열기
    const popup = window.open(
        `/delivery/printWaybill?order_number=${encodeURIComponent(orderNumber)}&tracking_number=${encodeURIComponent(trackingNumber)}`, 
        '_blank', 
        `width=${popupWidth},height=${popupHeight},scrollbars=yes,resizable=yes`
    );
    
    // 팝업이 로드된 후 컨텐츠 크기에 맞게 조정
    if (popup) {
        popup.addEventListener('load', function() {
            try {
                const contentHeight = popup.document.body.scrollHeight;
                const contentWidth = popup.document.body.scrollWidth;
                // 컨텐츠 크기 + 여유공간(40px)으로 조정
                const adjustedHeight = Math.min(contentHeight + 40, screen.height - 100);
                const adjustedWidth = Math.min(contentWidth + 40, screen.width - 100);
                popup.resizeTo(adjustedWidth, adjustedHeight);
            } catch (e) {
                // 크로스 오리진 제한으로 인한 오류는 무시
            }
        });
    }
}

// 송장출력 함수 (주문상세 모달에서)
function printWaybillFromDetail() {
    const orderNumber = window.currentOrderNumber;
    const trackingNumber = window.currentTrackingNumber;
    
    if (!orderNumber || !trackingNumber) {
        alert('송장번호가 없습니다.');
        return;
    }
    
    // 컨텐츠 크기에 맞게 팝업 크기 조정 (max-width: 800px + padding + 여유공간)
    const popupWidth = 860;  // 800px 컨텐츠 + 40px padding + 20px 여유
    const popupHeight = 700; // 컨텐츠에 맞게 조정된 높이
    
    // 새 창에서 송장출력 페이지 열기
    const popup = window.open(
        `/delivery/printWaybill?order_number=${encodeURIComponent(orderNumber)}&tracking_number=${encodeURIComponent(trackingNumber)}`, 
        '_blank', 
        `width=${popupWidth},height=${popupHeight},scrollbars=yes,resizable=yes`
    );
    
    // 팝업이 로드된 후 컨텐츠 크기에 맞게 조정
    if (popup) {
        popup.addEventListener('load', function() {
            try {
                const contentHeight = popup.document.body.scrollHeight;
                const contentWidth = popup.document.body.scrollWidth;
                // 컨텐츠 크기 + 여유공간(40px)으로 조정
                const adjustedHeight = Math.min(contentHeight + 40, screen.height - 100);
                const adjustedWidth = Math.min(contentWidth + 40, screen.width - 100);
                popup.resizeTo(adjustedWidth, adjustedHeight);
            } catch (e) {
                // 크로스 오리진 제한으로 인한 오류는 무시
            }
        });
    }
}

function cancelOrder(orderId) {
    // 주문 취소 기능 (추후 구현)
    if (confirm('정말로 이 주문을 취소하시겠습니까?')) {
        alert('주문 취소: ' + orderId);
    }
}

// 일양 배송정보 상세 조회
function viewIlyangDetail(trackingNumber) {
    if (!trackingNumber) {
        alert('운송장번호가 없습니다.');
        return;
    }
    
    // 모달 열기
    const modal = document.getElementById('ilyangDetailModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    // 로딩 상태 표시
    const content = document.getElementById('ilyang-detail-content');
    content.innerHTML = `
        <div class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
            <p class="mt-4 text-gray-600">배송정보를 조회하는 중입니다...</p>
        </div>
    `;
    
    // API 호출
    fetch(`/delivery/getIlyangDeliveryDetail?tracking_number=${encodeURIComponent(trackingNumber)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            populateIlyangDetail(data.data);
        } else {
            content.innerHTML = `
                <div class="text-center py-8">
                    <p class="text-red-600">${data.message || '배송정보를 조회할 수 없습니다.'}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="text-center py-8">
                <p class="text-red-600">배송정보 조회 중 오류가 발생했습니다.</p>
            </div>
        `;
    });
}

// 일양 배송정보 상세 내용 표시
function populateIlyangDetail(data) {
    const content = document.getElementById('ilyang-detail-content');
    
    // API 응답 구조 파싱
    const head = data.head || {};
    const traces = data.body?.trace || [];
    
    if (traces.length === 0) {
        content.innerHTML = `
            <div class="text-center py-8">
                <p class="text-gray-600">배송정보가 없습니다.</p>
            </div>
        `;
        return;
    }
    
    // 첫 번째 배송정보 추출 (일반적으로 1건)
    const trace = traces[0];
    const hawbNo = trace.hawb_no || '';
    const orderNo = trace.order_no || '';
    const sendnm = trace.sendnm || '';
    const recevnm = trace.recevnm || '';
    const eventymd = trace.eventymd || '';
    const eventnm = trace.eventnm || '';
    const signernm = trace.signernm || '';
    const itemlist = trace.itemlist || [];
    
    // 배송 추적 이력 추출
    let traceHistory = [];
    itemlist.forEach(itemGroup => {
        const items = itemGroup.item || [];
        const itemsArray = Array.isArray(items) ? items : [items];
        itemsArray.forEach(item => {
            if (item) {
                traceHistory.push(item);
            }
        });
    });
    
    // 날짜+시간 기준으로 정렬 (최신순)
    traceHistory.sort((a, b) => {
        const dateA = (a.status_date || '') + ' ' + (a.status_time || '');
        const dateB = (b.status_date || '') + ' ' + (b.status_time || '');
        return dateB.localeCompare(dateA);
    });
    
    // 배송상태 코드 매핑
    const traceCodeMap = {
        'PU': '발송사무소 인수',
        'AR': '배송경유지 도착',
        'BG': '배송경유지 출고',
        'WC': '직원 배송중',
        'DL': '배달완료',
        'EX': '미배달'
    };
    
    const nonDeliveryCodeMap = {
        'BA': '주소불명',
        'CA': '폐문부재',
        'CM': '이사불명',
        'NH': '수취인부재',
        'RD': '수취거절',
        'ND': '배달누락'
    };
    
    let html = `
        <div class="space-y-6">
            <!-- 기본 정보 -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-bold mb-4 text-gray-800">기본 정보</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-600">운송장번호</label>
                        <p class="text-base font-bold text-gray-900">${hawbNo || '-'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">주문번호</label>
                        <p class="text-base text-gray-900">${orderNo || '-'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">배송일자</label>
                        <p class="text-base text-gray-900">${eventymd || '-'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">배송결과</label>
                        <p class="text-base text-gray-900">${eventnm || '-'}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-600">수취인</label>
                        <p class="text-base text-gray-900">${signernm || '-'}</p>
                    </div>
                </div>
            </div>
            
            <!-- 발송인/수취인 정보 -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
                    <h4 class="text-lg font-bold mb-3 text-blue-800">발송인</h4>
                    <p class="text-base font-semibold text-gray-900">${sendnm || '-'}</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 border-2 border-green-200">
                    <h4 class="text-lg font-bold mb-3 text-green-800">수취인</h4>
                    <p class="text-base font-semibold text-gray-900">${recevnm || '-'}</p>
                </div>
            </div>
            
            <!-- 배송 추적 이력 -->
            <div class="bg-white rounded-lg border border-gray-200">
                <h4 class="text-lg font-bold mb-4 p-4 bg-gray-100 border-b border-gray-200 text-gray-800">배송 추적 이력</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">순서</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">날짜</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">시간</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">배송지점</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">지점코드</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">배송상태</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">상태설명</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">미배송사유</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    if (traceHistory.length === 0) {
        html += `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">배송 추적 이력이 없습니다.</td>
            </tr>
        `;
    } else {
        traceHistory.forEach((item, index) => {
            const statusDate = item.status_date || '-';
            const statusTime = item.status_time || '-';
            const station = item.station || '-';
            const empnm = item.empnm || '-';
            const tracecode = item.tracecode || '-';
            const tracestatus = item.tracestatus || traceCodeMap[tracecode] || '-';
            const nondlcode = item.nondlcode || '-';
            const nondelivreasnnm = item.nondelivreasnnm || nonDeliveryCodeMap[nondlcode] || '-';
            
            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900">${traceHistory.length - index}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${statusDate}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${statusTime}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${station}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${empnm}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${tracecode === 'DL' ? 'bg-green-100 text-green-800' : tracecode === 'EX' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'}">
                            ${tracecode}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">${tracestatus}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${nondelivreasnnm}</td>
                </tr>
            `;
        });
    }
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- API 응답 정보 -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-bold mb-4 text-gray-800">API 응답 정보</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="font-semibold text-gray-600">응답코드</label>
                        <p class="text-gray-900">${head.returnCode || '-'}</p>
                    </div>
                    <div>
                        <label class="font-semibold text-gray-600">응답설명</label>
                        <p class="text-gray-900">${head.returnDesc || '-'}</p>
                    </div>
                    <div>
                        <label class="font-semibold text-gray-600">전체 건수</label>
                        <p class="text-gray-900">${head.totalCount || 0}</p>
                    </div>
                    <div>
                        <label class="font-semibold text-gray-600">성공 건수</label>
                        <p class="text-gray-900">${head.successCount || 0}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
}

// 일양 배송정보 상세 모달 닫기
function closeIlyangDetail() {
    const modal = document.getElementById('ilyangDetailModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

// 테이블 헤더 드래그 앤 드롭 기능
(function() {
    let draggedElement = null;
    let draggedIndex = null;
    
    // 서버에서 전달된 컬럼 순서 (PHP 변수)
    const serverColumnOrder = <?= json_encode($column_order ?? null) ?>;

    // 저장된 컬럼 순서 불러오기 (서버에서 전달된 값 사용)
    function loadColumnOrder() {
        return serverColumnOrder;
    }

    // 컬럼 순서 저장하기 (API 호출)
    function saveColumnOrder(order) {
        fetch('/delivery/saveColumnOrder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                column_order: order
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to save column order:', data.message);
            }
        })
        .catch(error => {
            console.error('Error saving column order:', error);
        });
    }

    // 현재 컬럼 순서 가져오기
    function getCurrentColumnOrder() {
        const headerRow = document.getElementById('table-header-row');
        if (!headerRow) return null;
        
        const headers = Array.from(headerRow.querySelectorAll('th'));
        return headers.map(th => parseInt(th.getAttribute('data-column-index')));
    }

    // 컬럼 순서 적용하기
    function applyColumnOrder(order) {
        if (!order || order.length === 0) return;
        
        const headerRow = document.getElementById('table-header-row');
        const tbody = document.querySelector('tbody');
        
        if (!headerRow || !tbody) return;

        // 헤더 순서 재정렬
        const headers = Array.from(headerRow.querySelectorAll('th'));
        const headerMap = new Map();
        headers.forEach(th => {
            const index = parseInt(th.getAttribute('data-column-index'));
            headerMap.set(index, th);
        });

        // 순서대로 헤더 재배치
        order.forEach(index => {
            const th = headerMap.get(index);
            if (th) {
                headerRow.appendChild(th);
            }
        });

        // 데이터 셀 순서 재정렬
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            const cellMap = new Map();
            cells.forEach(td => {
                const index = parseInt(td.getAttribute('data-column-index'));
                cellMap.set(index, td);
            });

            // 순서대로 셀 재배치
            order.forEach(index => {
                const td = cellMap.get(index);
                if (td) {
                    row.appendChild(td);
                }
            });
        });
    }

    // 드래그 시작
    function handleDragStart(e) {
        if (!e.target.classList.contains('draggable-header')) {
            return;
        }
        
        // 드래그 시작 시 정렬 클릭 이벤트 방지
        e.target.setAttribute('data-dragging', 'true');
        
        draggedElement = e.target;
        draggedIndex = parseInt(e.target.getAttribute('data-column-index'));
        e.target.style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', e.target.innerHTML);
    }

    // 드래그 오버
    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        
        const target = e.target.closest('.draggable-header');
        if (target && target !== draggedElement) {
            e.dataTransfer.dropEffect = 'move';
        }
        
        return false;
    }

    // 드래그 엔터
    function handleDragEnter(e) {
        const target = e.target.closest('.draggable-header');
        if (target && target !== draggedElement) {
            target.style.backgroundColor = '#e5e7eb';
        }
    }

    // 드래그 리브
    function handleDragLeave(e) {
        const target = e.target.closest('.draggable-header');
        if (target) {
            target.style.backgroundColor = '';
        }
    }

    // 드롭
    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }

        const target = e.target.closest('.draggable-header');
        if (!target || target === draggedElement || !draggedElement) {
            return false;
        }

        const targetIndex = parseInt(target.getAttribute('data-column-index'));
        const currentOrder = getCurrentColumnOrder();
        
        if (!currentOrder) return false;

        // 순서 변경
        const draggedPos = currentOrder.indexOf(draggedIndex);
        const targetPos = currentOrder.indexOf(targetIndex);
        
        currentOrder.splice(draggedPos, 1);
        currentOrder.splice(targetPos, 0, draggedIndex);

        // 순서 적용
        applyColumnOrder(currentOrder);
        
        // 저장
        saveColumnOrder(currentOrder);

        // 스타일 초기화
        draggedElement.style.opacity = '';
        target.style.backgroundColor = '';
        
        draggedElement = null;
        draggedIndex = null;

        return false;
    }

    // 드래그 종료
    function handleDragEnd(e) {
        if (draggedElement) {
            draggedElement.style.opacity = '';
            draggedElement.removeAttribute('data-dragging');
        }
        
        // 모든 헤더의 배경색 초기화 및 드래그 속성 제거
        document.querySelectorAll('.draggable-header').forEach(th => {
            th.style.backgroundColor = '';
            th.removeAttribute('data-dragging');
        });
        
        draggedElement = null;
        draggedIndex = null;
    }

    // 정렬 기능
    // 컬럼 인덱스와 DB 필드 매핑
    const columnFieldMap = {
        1: { field: 'order_date', secondary: 'order_time' }, // 접수일자
        2: 'reserve_date', // 예약일
        3: 'state', // 상태
        4: 'company_name', // 회사명
        5: 'complete_time', // 완료시간
        6: 'customer_department', // 접수부서
        7: 'customer_duty', // 접수담당
        8: 'destination_manager', // 도착지담당명
        9: 'delivery_content', // 전달내용
        10: 'item_type', // 상품
        11: 'rider_tel_number', // 라이더연락처
        12: 'order_number', // 주문번호
        13: 'departure_customer_name', // 출발지고객명
        14: 'departure_manager', // 출발지담당명
        15: 'departure_dong', // 출발지동
        16: 'destination_customer_name', // 도착지고객명
        17: 'destination_dong', // 도착지동
        18: 'payment_method', // 지불
        19: 'delivery_method', // 배송
        20: 'delivery_vehicle', // 배송수단
        21: 'rider_id', // 기사번호
        22: 'rider_name' // 기사이름
    };

    // 현재 정렬 상태
    let currentSortColumn = null;
    let currentSortDirection = null; // 'asc' or 'desc'

    // URL에서 정렬 파라미터 읽기
    function getSortFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderBy = urlParams.get('order_by');
        const orderDir = urlParams.get('order_dir');
        return { orderBy, orderDir };
    }

    // 정렬 상태 업데이트 (UI)
    function updateSortUI(columnIndex, direction) {
        // 모든 헤더에서 정렬 클래스 제거
        document.querySelectorAll('.draggable-header').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });

        // 현재 정렬 컬럼에 클래스 추가
        const header = document.querySelector(`.draggable-header[data-column-index="${columnIndex}"]`);
        if (header) {
            if (direction === 'asc') {
                header.classList.add('sort-asc');
            } else if (direction === 'desc') {
                header.classList.add('sort-desc');
            }
        }
    }

    // 정렬 클릭 핸들러
    function handleSortClick(e) {
        // 드래그 중이면 정렬 동작 안 함
        const header = e.target.closest('.draggable-header');
        if (!header) return;
        
        if (header.getAttribute('data-dragging') === 'true') {
            return;
        }

        const columnIndex = parseInt(header.getAttribute('data-column-index'));
        if (!columnIndex || columnIndex === 0) return; // 번호 컬럼은 제외

        // 현재 정렬 상태 확인
        const { orderBy, orderDir } = getSortFromURL();
        let newDirection = 'asc';

        // 같은 컬럼을 클릭하면 방향 전환
        if (orderBy && parseInt(orderBy) === columnIndex) {
            newDirection = orderDir === 'asc' ? 'desc' : 'asc';
        }

        // URL 업데이트 및 페이지 리로드
        const url = new URL(window.location.href);
        url.searchParams.set('order_by', columnIndex);
        url.searchParams.set('order_dir', newDirection);
        url.searchParams.set('page', '1'); // 정렬 변경 시 첫 페이지로
        window.location.href = url.toString();
    }

    // 초기화
    function init() {
        // 저장된 순서 불러오기 (처음 로그인한 사용자는 null이므로 기본 순서 유지)
        const savedOrder = loadColumnOrder();
        if (savedOrder && Array.isArray(savedOrder) && savedOrder.length > 0) {
            applyColumnOrder(savedOrder);
        }
        // savedOrder가 null이면 기본 HTML 순서 그대로 사용

        // 드래그 이벤트 리스너 등록
        const headerRow = document.getElementById('table-header-row');
        if (headerRow) {
            headerRow.addEventListener('dragstart', handleDragStart);
            headerRow.addEventListener('dragover', handleDragOver);
            headerRow.addEventListener('dragenter', handleDragEnter);
            headerRow.addEventListener('dragleave', handleDragLeave);
            headerRow.addEventListener('drop', handleDrop);
            headerRow.addEventListener('dragend', handleDragEnd);
        }

        // 정렬 클릭 이벤트 등록
        document.querySelectorAll('.draggable-header').forEach(header => {
            header.addEventListener('click', handleSortClick);
        });

        // URL에서 정렬 상태 읽어서 UI 업데이트
        const { orderBy, orderDir } = getSortFromURL();
        if (orderBy && orderDir) {
            updateSortUI(parseInt(orderBy), orderDir);
        }
    }

    // DOM 로드 완료 후 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

// 인성 API 주문 동기화 (리스트 페이지 접근 시에만 실행)
// 개별 데이터 업데이트 API 주석처리
<?php if (false && in_array(session()->get('login_type'), ['daumdata', 'stn'])): ?>
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

<?= $this->include('forms/insung-order-detail-modal') ?>
<?= $this->include('forms/ilyang-order-detail-modal') ?>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>