<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('/history/list', ['method' => 'GET', 'id' => 'searchForm']) ?>
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
                <label class="search-filter-label">기간 시작</label>
                <input type="date" name="start_date" value="<?= esc($start_date) ?>" class="search-filter-input">
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">기간 종료</label>
                <input type="date" name="end_date" value="<?= esc($end_date) ?>" class="search-filter-input">
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">상태</label>
                <select name="status" class="search-filter-select">
                    <?php foreach ($status_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $status_filter === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-filter-button-wrapper">
                <input type="hidden" name="page" value="1" id="searchPageInput">
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
                    <?php 
                    $paginationInfo = $pagination->getPaginationInfo();
                    ?>
                    총 <?= number_format($paginationInfo['total_items']) ?>건 중 
                    <?= number_format($paginationInfo['start_item']) ?>-<?= number_format($paginationInfo['end_item']) ?>건 표시
                <?php else: ?>
                    검색 결과가 없습니다.
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 이용내역 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($orders)): ?>
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
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="2" draggable="true">전표</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="3" draggable="true">상태</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="4" draggable="true">주문번호</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="5" draggable="true">의뢰자</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="6" draggable="true">의뢰담당</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="7" draggable="true">출발지</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="8" draggable="true">출발동</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="9" draggable="true">출발담당</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="10" draggable="true">출발부서</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="11" draggable="true">출발전화번호</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="12" draggable="true">출발상세</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="13" draggable="true">도착지</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="14" draggable="true">도착동</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="15" draggable="true">도착담당</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="16" draggable="true">도착전화번호</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="17" draggable="true">도착상세</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="18" draggable="true">왕복</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="19" draggable="true">형태</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="20" draggable="true">차종</th>
                        <th class="px-4 py-2 text-right text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="21" draggable="true" style="text-align: right !important;">기본요금</th>
                        <th class="px-4 py-2 text-right text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="22" draggable="true" style="text-align: right !important;">추가</th>
                        <th class="px-4 py-2 text-right text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="23" draggable="true" style="text-align: right !important;">탁송료</th>
                        <th class="px-4 py-2 text-right text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="24" draggable="true" style="text-align: right !important;">정산금액</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="25" draggable="true">상품</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="26" draggable="true">적요</th>
                        <th class="px-4 py-2 text-left text-sm sm:text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="27" draggable="true">채널</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <?php foreach ($orders as $order): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="0"><?= esc($order['row_number'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="1"><?= esc($order['formatted_order_datetime'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="2">
                        <?php 
                        // 완료된 주문인지 확인 (state='30' 또는 status_label='완료')
                        $isCompleted = false;
                        if (($order['order_system'] ?? '') === 'insung') {
                            $isCompleted = ($order['state'] ?? '') === '30' || ($order['status_label'] ?? '') === '완료';
                        } else {
                            $isCompleted = ($order['status'] ?? '') === 'delivered' || ($order['status_label'] ?? '') === '배송완료';
                        }
                        
                        if ($isCompleted && !empty($order['display_order_number']) && $order['display_order_number'] !== '-' && ($order['order_system'] ?? '') === 'insung'): 
                        ?>
                            <span class="status-badge" style="cursor: pointer; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;" onclick="viewOrderSign('<?= esc($order['display_order_number']) ?>')">
                                Sign
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="3">
                        <?php if ($order['show_map_on_click'] ?? false): ?>
                            <span class="status-badge <?= esc($order['status_class'] ?? '') ?>" style="cursor: pointer;" onclick="openMapView('<?= esc($order['insung_order_number_for_map'] ?? '') ?>', <?= ($order['is_riding'] ?? false) ? 'true' : 'false' ?>)"><?= esc($order['status_label'] ?? '-') ?></span>
                        <?php else: ?>
                            <span class="status-badge <?= esc($order['status_class'] ?? '') ?>"><?= esc($order['status_label'] ?? '-') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="4">
                        <?php if (!empty($order['display_order_number']) && $order['display_order_number'] !== '-' && ($order['order_system'] ?? '') === 'insung'): ?>
                            <a href="javascript:void(0)" onclick="viewInsungOrderDetail('<?= esc($order['display_order_number']) ?>')" class="text-blue-600 hover:text-blue-800 no-underline cursor-pointer">
                                <?= esc($order['display_order_number']) ?>
                            </a>
                        <?php else: ?>
                            <?= esc($order['display_order_number'] ?? '-') ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="5"><?= esc($order['company_name'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="6"><?= esc($order['customer_duty'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="7">
                        <?php 
                        $departureAddr = $order['departure_address'] ?? '-';
                        if ($departureAddr !== '-' && mb_strlen($departureAddr, 'UTF-8') > 20) {
                            echo esc(mb_substr($departureAddr, 0, 20, 'UTF-8') . '...');
                        } else {
                            echo esc($departureAddr);
                        }
                        ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="8"><?= esc($order['departure_dong'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="9"><?= esc($order['departure_manager'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="10"><?= esc($order['departure_department'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="11"><?= esc($order['departure_contact'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="12"><?= esc($order['departure_detail'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="13">
                        <?php 
                        $destinationAddr = $order['destination_address'] ?? '-';
                        if ($destinationAddr !== '-' && mb_strlen($destinationAddr, 'UTF-8') > 20) {
                            echo esc(mb_substr($destinationAddr, 0, 20, 'UTF-8') . '...');
                        } else {
                            echo esc($destinationAddr);
                        }
                        ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="14"><?= esc($order['destination_dong'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="15"><?= esc($order['destination_manager'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="16"><?= esc($order['destination_contact'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="17">
                        <?php 
                        $detailAddr = $order['detail_address'] ?? '-';
                        if ($detailAddr !== '-' && mb_strlen($detailAddr, 'UTF-8') > 20) {
                            echo esc(mb_substr($detailAddr, 0, 20, 'UTF-8') . '...');
                        } else {
                            echo esc($detailAddr);
                        }
                        ?>
                    </td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="18"><?= esc($order['delivery_route_label'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="19"><?= esc($order['service_category'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="20"><?= esc($order['car_type'] ?? ($order['car_kind'] ?? '-')) ?></td>
                    <td class="px-4 py-2 text-sm text-right" data-column-index="21" style="text-align: right !important;"><?= esc($order['total_fare_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm text-right" data-column-index="22" style="text-align: right !important;"><?= esc($order['add_cost_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm text-right" data-column-index="23" style="text-align: right !important;"><?= esc($order['delivery_cost_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm text-right" data-column-index="24" style="text-align: right !important;"><?= esc($order['total_amount_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="25"><?= esc($order['item_type'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="26"><?= esc($order['delivery_content'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-base sm:text-sm" data-column-index="27"><?= esc($order['channel_label'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

<script src="<?= base_url('assets/js/common-library.js') ?>"></script>
<script>
// 검색 폼 제출 시 페이지 리셋
(function() {
    const searchForm = document.getElementById('searchForm');
    const searchPageInput = document.getElementById('searchPageInput');
    
    if (searchForm && searchPageInput) {
        searchForm.addEventListener('submit', function(e) {
            // 검색 버튼 클릭 시 항상 1페이지로 리셋
            searchPageInput.value = '1';
        });
    }
})();

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
        fetch('/history/saveColumnOrder', {
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

    // 정렬 기능
    // 컬럼 인덱스와 DB 필드 매핑
    const columnFieldMap = {
        1: { field: 'order_date', secondary: 'order_time' }, // 접수일자
        2: null, // 전표 (정렬 불가)
        3: 'state', // 상태
        4: 'order_number', // 주문번호
        5: 'company_name', // 의뢰자
        6: 'customer_duty', // 의뢰담당
        7: 'departure_address', // 출발지
        8: 'departure_dong', // 출발동
        9: 'departure_manager', // 출발담당
        10: 'departure_department', // 출발부서
        11: 'departure_contact', // 출발전화번호
        12: 'departure_detail', // 출발상세
        13: 'destination_address', // 도착지
        14: 'destination_dong', // 도착동
        15: 'destination_manager', // 도착담당
        16: 'destination_contact', // 도착전화번호
        17: 'detail_address', // 도착상세
        18: 'quick_delivery_route', // 왕복
        19: 'service_category', // 형태
        20: 'car_type', // 차종
        21: 'total_fare', // 기본요금
        22: 'add_cost', // 추가
        23: 'delivery_cost', // 탁송료
        24: 'total_amount', // 정산금액
        25: 'item_type', // 상품
        26: 'delivery_content', // 적요
        27: 'order_regist_type' // 채널
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
</script>

    <!-- 페이지네이션 -->
    <?php if (isset($pagination) && $pagination): ?>
        <?= $pagination->render() ?>
    <?php endif; ?>
</div>

<!-- 인성 API 주문 상세 팝업 모달 -->
<div id="insungOrderDetailModal" class="fixed inset-0 hidden flex items-center justify-center p-4 order-detail-modal" style="z-index: 9999; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col order-detail-modal-content" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center flex-shrink-0 rounded-t-lg">
            <h3 class="text-lg font-bold text-gray-800">인성 주문 상세 정보</h3>
            <button type="button" onclick="closeInsungOrderDetail()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-2 overflow-y-auto flex-1">
            <div id="insungOrderDetailContent" class="modal-content">
                <!-- 내용은 populateInsungOrderDetail()에서 동적으로 생성됩니다 -->
            </div>
        </div>
    </div>
</div>

<script>
function viewInsungOrderDetail(serialNumber) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 로딩 상태 표시
    showInsungOrderDetailLoading();
    
    // AJAX로 인성 API 주문 상세 정보 가져오기
    fetch(`/history/getOrderDetail?serial_number=${encodeURIComponent(serialNumber)}`, {
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
        console.log('API Response:', data); // 디버깅용
        if (data.success) {
            try {
                populateInsungOrderDetail(data.data);
                // 모달 표시
                document.getElementById('insungOrderDetailModal').classList.remove('hidden');
                document.getElementById('insungOrderDetailModal').classList.add('flex');
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('populateInsungOrderDetail Error:', error);
                console.error('Error stack:', error.stack);
                showInsungOrderDetailError('주문 정보 표시 중 오류가 발생했습니다: ' + error.message);
            }
        } else {
            showInsungOrderDetailError(data.message || '주문 정보를 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showInsungOrderDetailError('주문 정보 조회 중 오류가 발생했습니다: ' + error.message);
    })
    .finally(() => {
        hideInsungOrderDetailLoading();
    });
}

function populateInsungOrderDetail(orderData) {
    // 헬퍼 함수: 값이 있으면 표시, 없으면 '-'
    const getValue = (value) => {
        if (value === null || value === undefined || value === '') return '-';
        if (typeof value === 'object') return JSON.stringify(value);
        return value;
    };
    
    // 마스킹 처리는 컨트롤러에서 이미 완료되었으므로 프론트엔드에서는 그냥 표시만 함
    
    // 필드명 한글 매핑 (처리결과 엘리먼트 -> 상세설명)
    // 이미지에서 제공된 매핑 정보 기반
    const fieldLabels = {
        // 접수자 정보
        'customer_name': '접수자 이름',
        'customer_tel_number': '접수자 전화번호',
        'customer_department': '접수자 부서명',
        'customer_duty': '접수자 담당명',
        
        // 기사 정보
        'rider_code_no': '오더 처리 기사 고유번호',
        'rider_name': '오더 처리 기사 이름',
        'rider_tel_number': '오더 처리 기사 연락처',
        'rider_lon': '기사 경도좌표',
        'rider_lat': '기사 위도좌표',
        
        // 오더 정보
        'serial_number': '오더 고유번호(주문번호)',
        'order_time': '접수시간',
        'allocation_time': '배차시간',
        'pickup_time': '픽업시간',
        'resolve_time': '예약시간',
        'complete_time': '완료시간',
        'reason': '배송사유',
        'order_regist_type': '접수유형',
        
        // 배송지 정보
        'departure_dong_name': '출발지 동명',
        'departure_address': '출발지 상세주소',
        'departure_tel_number': '출발지 연락처',
        'departure_company_name': '출발지 상호·이름',
        'destination_dong_name': '도착지 동명',
        'destination_address': '도착지 상세주소',
        'destination_tel_number': '도착지 연락처',
        'destination_company_name': '도착지 상호·이름',
        'summary': '전달내용',
        
        // 배송·요금 정보
        'car_type': '배송수단',
        'cargo_type': '차종톤수',
        'cargo_name': '차종구분명',
        'payment': '지불수단',
        'state': '배송상태',
        'save_state': 'DB저장 배송상태',
        'total_cost': '지급금액',
        'basic_cost': '기본요금',
        'addition_cost': '추가요금',
        'discount_cost': '할인요금',
        'delivery_cost': '탁송요금',
        
        // 출·도착지 정보
        'rider_lon': '기사 경도좌표',
        'rider_lat': '기사 위도좌표',
        'start_lon': '출발지 경도좌표',
        'start_lat': '출발지 위도좌표',
        'dest_lon': '도착지 경도좌표',
        'dest_lat': '도착지 위도좌표',
        'doc': '배송방법',
        'item_type': '물품종류',
        'sfast': '배송선택',
        'start_c_code': '출발지 고객코드',
        'dest_c_code': '도착지 고객코드',
        'start_department': '출발지 부서',
        'start_duty': '출발지 담당',
        'dest_department': '도착지 부서',
        'dest_duty': '도착지 담당',
        'happy_call': '해피콜 회신번호',
        'distince': '출발지 도착지 거리'
    };
    
    // 상태 값 변환
    const stateLabels = {
        '10': '접수',
        '11': '배차',
        '12': '운행',
        '20': '대기',
        '30': '완료',
        '40': '취소',
        '50': '문의',
        '90': '예약'
    };
    
    const orderRegistTypeLabels = {
        'A': 'API접수',
        'I': '인터넷접수',
        'T': '전화접수'
    };
    
    // 비고 컬럼 기준 섹션별 필드 그룹화 (이미지에서 제공된 정보 기반)
    const sections = {
        '접수자 정보': [
            { key: 'customer_name', label: '접수자 이름' },
            { key: 'customer_tel_number', label: '접수자 전화번호' },
            { key: 'customer_department', label: '접수자 부서명' },
            { key: 'customer_duty', label: '접수자 담당명' }
        ],
        '기사 정보': [
            { key: 'rider_code_no', label: '오더 처리 기사 고유번호' },
            { key: 'rider_name', label: '오더 처리 기사 이름' },
            { key: 'rider_tel_number', label: '오더 처리 기사 연락처' }
        ],
        '오더 정보': [
            { key: 'serial_number', label: '오더 고유번호(주문번호)' },
            { key: 'order_time', label: '접수시간' },
            { key: 'allocation_time', label: '배차시간' },
            { key: 'pickup_time', label: '픽업시간' },
            { key: 'resolve_time', label: '예약시간' },
            { key: 'complete_time', label: '완료시간' },
            { key: 'reason', label: '배송사유' },
            { key: 'order_regist_type', label: '접수유형' }
        ],
        '배송지 정보': [
            { key: 'departure_dong_name', label: '출발지 동명' },
            { key: 'departure_address', label: '출발지 상세주소' },
            { key: 'departure_tel_number', label: '출발지 연락처' },
            { key: 'departure_company_name', label: '출발지 상호·이름' },
            { key: 'destination_dong_name', label: '도착지 동명' },
            { key: 'destination_address', label: '도착지 상세주소' },
            { key: 'destination_tel_number', label: '도착지 연락처' },
            { key: 'destination_company_name', label: '도착지 상호·이름' },
            { key: 'summary', label: '전달내용' }
        ],
        '배송정보': [
            { key: 'car_type', label: '배송수단' },
            { key: 'cargo_type', label: '차종톤수' },
            { key: 'cargo_name', label: '차종구분명' },
            { key: 'payment', label: '지불수단' },
            { key: 'state', label: '배송상태' },
            { key: 'save_state', label: 'DB저장 배송상태' }
        ],
        '출·도착지 정보': [
            { key: 'doc', label: '배송방법' },
            { key: 'item_type', label: '물품종류' },
            { key: 'sfast', label: '배송선택' },
            { key: 'start_c_code', label: '출발지 고객코드' },
            { key: 'dest_c_code', label: '도착지 고객코드' },
            { key: 'start_department', label: '출발지 부서' },
            { key: 'start_duty', label: '출발지 담당' },
            { key: 'dest_department', label: '도착지 부서' },
            { key: 'dest_duty', label: '도착지 담당' },
            { key: 'happy_call', label: '해피콜 회신번호' },
            { key: 'distince', label: '출발지 도착지 거리', suffix: 'Km' }
        ]
    };
    
    // 섹션별로 필드 구성 및 패널 생성 함수
    const createSectionPanel = (sectionTitle, fieldDefs) => {
        const sectionFields = [];
        
        for (const fieldDef of fieldDefs) {
            const key = fieldDef.key;
            const label = fieldDef.label;
            
            // orderData에서 키를 찾기 (다양한 변형 시도)
            let value = null;
            
            // 직접 키 매칭
            if (orderData.hasOwnProperty(key)) {
                value = orderData[key];
            } else {
                // 중첩된 키 찾기 (item_0_customer_name 같은 형태)
                for (const dataKey in orderData) {
                    if (dataKey.includes(key) || dataKey.endsWith('_' + key)) {
                        value = orderData[dataKey];
                        break;
                    }
                }
            }
            
            if (value !== null && value !== undefined && value !== '') {
                // 특정 필드 값 변환
                if (key === 'state' || key === 'save_state') {
                    value = stateLabels[value] || value;
                } else if (key === 'order_regist_type') {
                    value = orderRegistTypeLabels[value] || value;
                }
                
                // 마스킹 처리는 컨트롤러에서 이미 완료되었으므로 추가 처리 불필요
                
                // suffix가 있으면 추가 (예: 거리에 Km)
                if (fieldDef.suffix) {
                    value = value + ' ' + fieldDef.suffix;
                }
                
                sectionFields.push({ key, label, value });
            }
        }
        
        // 필드가 있는 섹션만 패널 반환
        if (sectionFields.length > 0) {
            return `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); height: 100%;">
                    <div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">
                        ${sectionTitle}
                    </div>
                    <div>
                        ${sectionFields.map(field => `
                            <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6; font-size: 12px; line-height: 1.6;">
                                <span style="font-weight: 600; color: #374151; display: inline-block; min-width: 140px;">${field.label}</span>
                                <span style="color: #6b7280;">: ${getValue(field.value)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        return '';
    };
    
    // 레이아웃에 맞게 섹션 배치
    // 첫 번째 행: 접수자 정보 | 오더 정보
    // 두 번째 행: 출·도착지 정보 | 배송정보
    // 세 번째 행: 배송지 정보 (전체 너비)
    
    const customerPanel = createSectionPanel('접수자 정보', sections['접수자 정보']);
    const orderPanel = createSectionPanel('오더 정보', sections['오더 정보']);
    const locationPanel = createSectionPanel('출·도착지 정보', sections['출·도착지 정보']);
    const deliveryPanel = createSectionPanel('배송정보', sections['배송정보']);
    const addressPanel = createSectionPanel('배송지 정보', sections['배송지 정보']);
    
    let content = '<div style="padding: 8px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 8px; width: 100%; box-sizing: border-box;">';
    
    // 첫 번째 행: 접수자 정보 | 오더 정보
    if (customerPanel || orderPanel) {
        content += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; align-items: stretch; width: 100%;">';
        content += (customerPanel || '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);"><div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">접수자 정보</div><div style="color: #6b7280; font-size: 12px;">정보 없음</div></div>');
        content += (orderPanel || '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);"><div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">오더 정보</div><div style="color: #6b7280; font-size: 12px;">정보 없음</div></div>');
        content += '</div>';
    }
    
    // 두 번째 행: 출·도착지 정보 | 배송정보
    if (locationPanel || deliveryPanel) {
        content += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; align-items: stretch; width: 100%;">';
        content += (locationPanel || '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);"><div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">출·도착지 정보</div><div style="color: #6b7280; font-size: 12px;">정보 없음</div></div>');
        content += (deliveryPanel || '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);"><div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">배송정보</div><div style="color: #6b7280; font-size: 12px;">정보 없음</div></div>');
        content += '</div>';
    }
    
    // 세 번째 행: 배송지 정보 (전체 너비)
    if (addressPanel) {
        content += '<div style="margin-bottom: 0; width: 100%;">';
        content += addressPanel;
        content += '</div>';
    }
    
    content += '</div>';
    
    document.getElementById('insungOrderDetailContent').innerHTML = content;
}

function showInsungOrderDetailLoading() {
    const content = document.getElementById('insungOrderDetailContent');
    content.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">주문 정보를 불러오는 중...</div>';
    
    document.getElementById('insungOrderDetailModal').classList.remove('hidden');
    document.getElementById('insungOrderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function hideInsungOrderDetailLoading() {
    // 로딩 상태는 populateInsungOrderDetail에서 실제 내용으로 대체됨
}

function showInsungOrderDetailError(message) {
    const content = document.getElementById('insungOrderDetailContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="color: #ef4444; margin-bottom: 16px;">⚠️</div>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">오류 발생</div>
            <div style="color: #6b7280;">${message}</div>
        </div>
    `;
    
    document.getElementById('insungOrderDetailModal').classList.remove('hidden');
    document.getElementById('insungOrderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeInsungOrderDetail() {
    document.getElementById('insungOrderDetailModal').classList.add('hidden');
    document.getElementById('insungOrderDetailModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
    
    // 레이어 팝업이 닫힐 때 사이드바 복원
    if (typeof window.showSidebarForModal === 'function') {
        window.showSidebarForModal();
    }
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 모달 외부 클릭 시 닫기 방지 (공통 스타일 사용으로 자동 처리됨)
</script>

<!-- 인수증 레이어 팝업 -->
<div id="orderSignModal" class="fixed inset-0 hidden flex items-center justify-center p-4 order-detail-modal" style="z-index: 9999; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col order-detail-modal-content" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center flex-shrink-0 rounded-t-lg">
            <h3 class="text-lg font-bold text-gray-800">인수증</h3>
            <button type="button" onclick="closeOrderSign()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-2 overflow-y-auto flex-1">
            <div id="orderSignContent" class="modal-content">
                <!-- 내용은 populateOrderSign()에서 동적으로 생성됩니다 -->
            </div>
        </div>
    </div>
</div>

<script>
function viewOrderSign(serialNumber) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 로딩 상태 표시
    showOrderSignLoading();
    
    // AJAX로 인수증 정보 가져오기
    fetch(`/history/getOrderSign?serial_number=${encodeURIComponent(serialNumber)}`, {
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
        console.log('Order Sign API Response:', data); // 디버깅용
        if (data.success) {
            console.log('Order Sign Data:', data.data); // 디버깅용
            populateOrderSign(data.data);
            // 모달 표시
            document.getElementById('orderSignModal').classList.remove('hidden');
            document.getElementById('orderSignModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        } else {
            showOrderSignError(data.message || '인수증 정보를 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showOrderSignError('인수증 정보 조회 중 오류가 발생했습니다: ' + error.message);
    })
    .finally(() => {
        hideOrderSignLoading();
    });
}

function populateOrderSign(signData) {
    const content = document.getElementById('orderSignContent');
    
    // 디버깅용 로그
    console.log('populateOrderSign - signData:', signData);
    console.log('departure_sign:', signData?.departure_sign);
    console.log('destination_sign:', signData?.destination_sign);
    console.log('receipt_url:', signData?.receipt_url);
    
    // escapeHtml 함수 정의 (없을 경우)
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    let html = '<div style="padding: 8px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 8px; width: 100%; box-sizing: border-box;">';
    html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; align-items: stretch; width: 100%;">';
    
    // 출발지 사인 패널
    html += '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); height: 100%;">';
    html += '<div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">출발지 사인</div>';
    const departureSign = signData?.departure_sign || '';
    if (departureSign && departureSign.trim() !== '') {
        html += `<img src="${escapeHtml(departureSign)}" alt="출발지 사인" style="max-width: 100%; height: auto; border: 1px solid #e5e7eb; border-radius: 4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"><div style="display: none; color: #6b7280; font-size: 12px; padding: 20px; text-align: center;">이미지를 불러올 수 없습니다.</div>`;
    } else {
        html += '<div style="color: #6b7280; font-size: 12px; padding: 20px; text-align: center;">출발지 사인 정보가 없습니다.</div>';
    }
    html += '</div>';
    
    // 도착지 사인 패널
    html += '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); height: 100%;">';
    html += '<div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">도착지 사인</div>';
    const destinationSign = signData?.destination_sign || '';
    if (destinationSign && destinationSign.trim() !== '') {
        html += `<img src="${escapeHtml(destinationSign)}" alt="도착지 사인" style="max-width: 100%; height: auto; border: 1px solid #e5e7eb; border-radius: 4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"><div style="display: none; color: #6b7280; font-size: 12px; padding: 20px; text-align: center;">이미지를 불러올 수 없습니다.</div>`;
    } else {
        html += '<div style="color: #6b7280; font-size: 12px; padding: 20px; text-align: center;">도착지 사인 정보가 없습니다.</div>';
    }
    html += '</div>';
    
    html += '</div>';
    
    html += '</div>';
    
    content.innerHTML = html;
}

function showOrderSignLoading() {
    const content = document.getElementById('orderSignContent');
    content.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">인수증 정보를 불러오는 중...</div>';
    
    document.getElementById('orderSignModal').classList.remove('hidden');
    document.getElementById('orderSignModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function hideOrderSignLoading() {
    // 로딩 상태는 populateOrderSign에서 실제 내용으로 대체됨
}

function showOrderSignError(message) {
    const content = document.getElementById('orderSignContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="color: #ef4444; margin-bottom: 16px;">⚠️</div>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">오류 발생</div>
            <div style="color: #6b7280;">${escapeHtml(message)}</div>
        </div>
    `;
    
    document.getElementById('orderSignModal').classList.remove('hidden');
    document.getElementById('orderSignModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeOrderSign() {
    document.getElementById('orderSignModal').classList.add('hidden');
    document.getElementById('orderSignModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
    
    // 레이어 팝업이 닫힐 때 사이드바 복원
    if (typeof window.showSidebarForModal === 'function') {
        window.showSidebarForModal();
    }
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}
</script>

<?= $this->endSection() ?>
