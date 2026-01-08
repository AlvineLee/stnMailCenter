<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('/history/list', ['method' => 'GET']) ?>
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
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b delivery-list-header" data-column-index="0">번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="1" draggable="true">접수일자</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="2" draggable="true">전표</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="3" draggable="true">상태</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="4" draggable="true">주문번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="5" draggable="true">의뢰자</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="6" draggable="true">의뢰담당</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="7" draggable="true">출발지</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="8" draggable="true">출발동</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="9" draggable="true">출발담당</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="10" draggable="true">출발부서</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="11" draggable="true">출발전화번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="12" draggable="true">출발상세</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="13" draggable="true">도착지</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="14" draggable="true">도착동</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="15" draggable="true">도착담당</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="16" draggable="true">도착전화번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="17" draggable="true">도착상세</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="18" draggable="true">왕복</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="19" draggable="true">형태</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="20" draggable="true">차종</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="21" draggable="true">기본요금</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="22" draggable="true">추가</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="23" draggable="true">탁송료</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="24" draggable="true">정산금액</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="25" draggable="true">상품</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="26" draggable="true">적요</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b draggable-header delivery-list-header draggable" data-column-index="27" draggable="true">채널</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <?php foreach ($orders as $order): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm" data-column-index="0"><?= esc($order['row_number'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="1"><?= esc($order['formatted_order_datetime'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="2">-</td>
                    <td class="px-4 py-2 text-sm" data-column-index="3">
                        <?php if ($order['show_map_on_click'] ?? false): ?>
                            <span class="status-badge <?= esc($order['status_class'] ?? '') ?>" style="cursor: pointer;" onclick="openMapView('<?= esc($order['insung_order_number_for_map'] ?? '') ?>', <?= ($order['is_riding'] ?? false) ? 'true' : 'false' ?>)"><?= esc($order['status_label'] ?? '-') ?></span>
                        <?php else: ?>
                            <span class="status-badge <?= esc($order['status_class'] ?? '') ?>"><?= esc($order['status_label'] ?? '-') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 text-sm" data-column-index="4"><?= esc($order['display_order_number'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="5"><?= esc($order['company_name'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="6"><?= esc($order['customer_duty'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="7"><?= esc($order['departure_address'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="8"><?= esc($order['departure_dong'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="9"><?= esc($order['departure_manager'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="10"><?= esc($order['departure_department'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="11"><?= esc($order['departure_contact'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="12"><?= esc($order['departure_detail'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="13"><?= esc($order['destination_address'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="14"><?= esc($order['destination_dong'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="15"><?= esc($order['destination_manager'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="16"><?= esc($order['destination_contact'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="17"><?= esc($order['detail_address'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="18"><?= esc($order['delivery_route_label'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="19"><?= esc($order['service_category'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="20"><?= esc($order['car_type'] ?? ($order['car_kind'] ?? '-')) ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="21"><?= esc($order['total_fare_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="22"><?= esc($order['add_cost_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="23"><?= esc($order['delivery_cost_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="24"><?= esc($order['total_amount_formatted'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="25"><?= esc($order['item_type'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="26"><?= esc($order['delivery_content'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-sm" data-column-index="27"><?= esc($order['channel_label'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

<script src="<?= base_url('assets/js/common-library.js') ?>"></script>
<script>
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

<?= $this->endSection() ?>
