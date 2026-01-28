<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 타이틀 및 처리상태별 필터 배지 -->
    <div class="mb-4 px-2 md:px-4 py-2 bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <!-- 좌측: 상태 필터 배지 -->
            <div class="flex flex-wrap items-center gap-1" id="statusFilterBadges">
                <span class="status-filter-badge active cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all text-center"
                      data-status="" onclick="filterByStatus('')"
                      style="background: #e5e7eb; color: #374151;">
                    전체 <span id="totalOrders" class="font-bold">0</span>
                </span>
                <span class="status-filter-badge cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all hover:opacity-80 text-center"
                      data-status="접수" onclick="filterByStatus('접수')"
                      style="background: #fffacd; color: #856404; border: 1px solid #f5deb3;">
                    접수 <span id="acceptedOrders" class="font-bold">0</span>
                </span>
                <span class="status-filter-badge cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all hover:opacity-80 text-center"
                      data-status="배차" onclick="filterByStatus('배차')"
                      style="background: #f5deb3; color: #8b4513; border: 1px solid #deb887;">
                    배차 <span id="dispatchedOrders" class="font-bold">0</span>
                </span>
                <span class="status-filter-badge cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all hover:opacity-80 text-center"
                      data-status="대기" onclick="filterByStatus('대기')"
                      style="background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1;">
                    대기 <span id="waitingOrders" class="font-bold">0</span>
                </span>
                <span class="status-filter-badge cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all hover:opacity-80 text-center"
                      data-status="배송" onclick="filterByStatus('배송')"
                      style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
                    운행 <span id="deliveryOrders" class="font-bold">0</span>
                </span>
                <span class="status-filter-badge cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all hover:opacity-80 text-center"
                      data-status="예약" onclick="filterByStatus('예약')"
                      style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                    예약 <span id="reservedOrders" class="font-bold">0</span>
                </span>
                <span class="status-filter-badge cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all hover:opacity-80 text-center"
                      data-status="완료" onclick="filterByStatus('완료')"
                      style="background: #ffffff; color: #333333; border: 1px solid #e0e0e0;">
                    완료 <span id="completedOrders" class="font-bold">0</span>
                </span>
                <span class="status-filter-badge cursor-pointer px-2 py-1 rounded-full text-xs font-medium transition-all hover:opacity-80 text-center"
                      data-status="취소" onclick="filterByStatus('취소')"
                      style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;">
                    취소 <span id="cancelledOrders" class="font-bold">0</span>
                </span>
            </div>
            <!-- 우측: 콜센터 필터 + 자동갱신 버튼 -->
            <div class="flex items-center gap-2">
                <select id="callCenterFilter" onchange="filterByCallCenter(this.value)" class="h-[26px] px-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="">전체 콜센터</option>
                    <?php foreach ($call_centers as $cc): ?>
                    <option value="<?= esc($cc['api_name'] ?? '') ?>"><?= esc($cc['api_name'] ?? $cc['cc_name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="flex items-center">
                    <button type="button" id="btnAutoRefresh" class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-200 rounded-l hover:bg-gray-300 focus:outline-none transition-colors">
                        <span id="autoRefreshBtnText">🔁 자동갱신</span>
                    </button>
                    <select id="refreshIntervalSelect" class="h-[26px] px-1 text-xs font-medium text-gray-700 bg-gray-200 rounded-r border-l border-gray-300 hover:bg-gray-300 focus:outline-none cursor-pointer">
                        <option value="5000">5초</option>
                        <option value="7000">7초</option>
                        <option value="10000">10초</option>
                        <option value="30000">30초</option>
                        <option value="60000">1분</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- 대시보드 섹션 (숨김 처리) -->
    <div class="mb-4" id="dashboardWrapper" style="display: none !important;">
        <!-- 대시보드 헤더 (토글 버튼) -->
        <div class="flex items-center justify-between px-3 py-2 bg-gray-100 rounded-t-lg border border-gray-200 cursor-pointer hover:bg-gray-200 transition-colors"
             onclick="toggleDashboard()">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-700">📊 대시보드</span>
                <span class="text-xs text-gray-500" id="dashboardSummaryText"></span>
            </div>
            <span id="dashboardToggleIcon" class="text-gray-500 transition-transform">▼</span>
        </div>

        <!-- 대시보드 컨텐츠 (접기/펼치기 대상) -->
        <div id="dashboardContent" class="border border-t-0 border-gray-200 rounded-b-lg overflow-hidden">
            <!-- 통계 요약 카드 -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 p-3 bg-white" id="summaryCards">
                <div class="bg-blue-600 text-white rounded-lg p-3 text-center">
                    <div class="text-xs opacity-80">총 콜센터</div>
                    <div class="text-2xl" id="totalCallCenters">0</div>
                </div>
                <div class="bg-green-600 text-white rounded-lg p-3 text-center">
                    <div class="text-xs opacity-80">조회 성공</div>
                    <div class="text-2xl" id="successCount">0</div>
                </div>
            </div>

            <!-- 에러 목록 -->
            <div class="bg-red-50 border-t border-red-200 p-4" id="errorSection" style="display: none;">
                <div class="text-sm text-red-800 mb-2">조회 오류 목록</div>
                <ul id="errorList" class="text-sm text-red-600 list-disc list-inside"></ul>
            </div>

            <!-- 콜센터별 주문 현황 -->
            <div class="bg-white border-t border-gray-200 p-4" id="callCenterSection" style="display: none;">
                <div class="text-sm font-semibold text-gray-700 mb-3">콜센터별 주문 현황</div>
                <div id="callCenterList" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 text-sm"></div>
            </div>
        </div>
    </div>

    <!-- 주문 목록 테이블 (헤더 고정, 데이터 영역만 스크롤) -->
    <style>
        /* 상태 필터 배지 스타일 */
        .status-filter-badge {
            transition: all 0.2s ease;
        }
        .status-filter-badge:hover {
            opacity: 0.8;
        }
        .status-filter-badge.active {
            box-shadow: 0 0 0 2px #3b82f6;
            transform: scale(1.05);
        }
    </style>
    <style>
        .table-scroll-container {
            position: relative;
            max-height: calc(100vh - 350px);
            min-height: 400px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
        }
        .table-scroll-container table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed; /* 열 너비 고정 */
        }
        .table-scroll-container thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table-scroll-container thead th {
            position: sticky;
            top: 0;
            background: #f9fafb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-bottom: 2px solid #d1d5db;
        }
        .table-scroll-container tbody tr:hover {
            background-color: #f3f4f6;
        }
        .table-scroll-container tbody td {
            border-bottom: 1px solid #e5e7eb;
        }
        .loading-more-indicator {
            text-align: center;
            padding: 12px;
            color: #6b7280;
            font-size: 0.875rem;
            background: #fef3c7;
        }
    </style>
    <div class="list-table-container" id="tableContainer">
        <div class="table-scroll-container" id="tableScrollContainer">
            <table class="min-w-full bg-white" id="ordersTable">
                <thead class="bg-gray-50" id="ordersTableHead">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">#</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody" class="divide-y divide-gray-200">
                    <tr>
                        <td colspan="1" class="px-4 py-8 text-center text-gray-500">
                            데이터 로딩중...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// 전역 변수
let allOrders = [];
let filteredOrders = [];
let displayedCount = 0;
let allColumns = [];  // 모든 필드명 저장

// 현재 선택된 상태 필터
let currentStatusFilter = '';

// 현재 선택된 콜센터 필터
let currentCallCenterFilter = '';

// 자동갱신 관련 변수
let autoRefreshEnabled = false;
let autoRefreshIntervalId = null;
let autoRefreshStatusFilter = [];  // 자동갱신 시 필터할 상태 목록
let isRefreshing = false;  // 현재 갱신 중인지 여부
let currentAbortController = null;  // 현재 요청의 AbortController

// 선택된 자동갱신 간격 가져오기
function getRefreshInterval() {
    const select = document.getElementById('refreshIntervalSelect');
    return parseInt(select.value) || 10000;
}

// 간격 텍스트 가져오기
function getRefreshIntervalText() {
    const select = document.getElementById('refreshIntervalSelect');
    return select.options[select.selectedIndex].text;
}

document.addEventListener('DOMContentLoaded', function() {
    // 대시보드 상태 복원
    restoreDashboardState();

    // 캐시된 주문 로드
    loadCachedOrders();

    // 자동갱신 버튼 클릭 이벤트
    document.getElementById('btnAutoRefresh').addEventListener('click', function() {
        toggleAutoRefresh();
    });
});


function loadCachedOrders() {
    const startTime = performance.now();  // 시작 시간

    // 로딩 표시
    const tbody = document.getElementById('ordersTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="100" class="px-4 py-8 text-center text-gray-500">
                Redis 캐시 데이터 로딩중...
            </td>
        </tr>
    `;

    fetch('<?= base_url('insung-order/getCachedOrders') ?>')
        .then(response => response.json())
        .then(data => {
            const fetchTime = performance.now();  // fetch 완료 시간

            if (data.success && data.data && data.data.length > 0) {
                allOrders = data.data;

                // summary 표시 (개별 상태별)
                updateStatusCounts(allOrders);

                // 현재 필터 상태 유지하며 렌더링
                applyFilter();

            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="100" class="px-4 py-8 text-center text-gray-500">
                            캐시된 주문이 없습니다. [자동갱신] 버튼을 클릭하세요.
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="100" class="px-4 py-8 text-center text-gray-500">
                        캐시 로드 실패. [자동갱신] 버튼을 클릭하세요.
                    </td>
                </tr>
            `;
        });
}

// 주문 갱신 (API에서 가져와서 Redis에 저장)
function refreshOrders() {
    // 이미 갱신 중이면 건너뛰기
    if (isRefreshing) {
        return;
    }

    const startTime = performance.now();  // 시작 시간
    isRefreshing = true;

    // 이전 요청 취소
    if (currentAbortController) {
        currentAbortController.abort();
    }
    currentAbortController = new AbortController();

    // 오늘 날짜로 조회
    const today = new Date().toISOString().split('T')[0];
    const formData = new FormData();
    formData.append('from_date', today);
    formData.append('to_date', today);

    fetch('<?= base_url('insung-order/fetchOrders') ?>', {
        method: 'POST',
        body: formData,
        signal: currentAbortController.signal
    })
    .then(response => response.json())
    .then(data => {
        const fetchTime = performance.now();  // fetch 완료 시간

        if (data.success) {
            allOrders = data.data || [];
            filteredOrders = allOrders;
            updateSummary(data.summary);
            updateErrors(data.summary?.errors || []);
            updateCallCenterSummary(data.summary?.by_call_center || {});

            // 현재 필터 상태 유지하며 렌더링 (자동갱신 중일 때 필터 유지)
            applyFilter();
        }
    })
    .catch(error => {
        // 에러 무시 (AbortError 포함)
    })
    .finally(() => {
        isRefreshing = false;
        currentAbortController = null;
    });
}

function updateSummary(summary) {
    if (!summary) return;

    // 대시보드 숨김 처리 (표시하지 않음)
    // document.getElementById('dashboardWrapper').style.display = 'block';

    document.getElementById('totalCallCenters').textContent = summary.total_call_centers || 0;
    document.getElementById('successCount').textContent = summary.success_count || 0;

    // 상태별 카운트는 실제 주문 데이터 기준으로 계산
    updateStatusCounts(allOrders);

    // 대시보드 요약 텍스트 업데이트
    updateDashboardSummaryText();
}

// 상태별 주문 건수 계산 및 표시
function updateStatusCounts(orders) {
    const counts = {
        total: 0,
        '접수': 0,
        '배차': 0,
        '대기': 0,
        '배송': 0,
        '예약': 0,
        '완료': 0,
        '취소': 0
    };

    orders.forEach(order => {
        const state = String(order.order_state || order.state || '').trim();
        counts.total++;

        if (state === '접수') {
            counts['접수']++;
        } else if (state === '배차') {
            counts['배차']++;
        } else if (state === '대기') {
            counts['대기']++;
        } else if (state === '배송') {
            counts['배송']++;
        } else if (state === '예약') {
            counts['예약']++;
        } else if (state === '완료') {
            counts['완료']++;
        } else if (state === '취소') {
            counts['취소']++;
        } else {
            // 기타 상태는 접수로 분류
            counts['접수']++;
        }
    });

    // DOM 업데이트
    document.getElementById('totalOrders').textContent = counts.total;
    document.getElementById('acceptedOrders').textContent = counts['접수'];
    document.getElementById('dispatchedOrders').textContent = counts['배차'];
    document.getElementById('waitingOrders').textContent = counts['대기'];
    document.getElementById('deliveryOrders').textContent = counts['배송'];
    document.getElementById('reservedOrders').textContent = counts['예약'];
    document.getElementById('completedOrders').textContent = counts['완료'];
    document.getElementById('cancelledOrders').textContent = counts['취소'];
}

function updateErrors(errors) {
    const section = document.getElementById('errorSection');
    const list = document.getElementById('errorList');

    if (!errors || errors.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    list.innerHTML = errors.map(err => `<li>${err}</li>`).join('');
}

function updateCallCenterSummary(byCallCenter) {
    const section = document.getElementById('callCenterSection');
    const list = document.getElementById('callCenterList');

    if (!byCallCenter || Object.keys(byCallCenter).length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    let html = '';
    for (const [name, count] of Object.entries(byCallCenter)) {
        html += `<div class="flex justify-between items-center bg-gray-50 px-2 py-1 rounded">
            <span class="text-gray-700 truncate" title="${name}">${name}</span>
            <span class="font-semibold text-blue-600 ml-2">${count}</span>
        </div>`;
    }
    list.innerHTML = html;

    // 대시보드 요약 텍스트 업데이트
    updateDashboardSummaryText();
}

// API 응답 순서대로 컬럼 정렬 순서 정의
const columnOrder = [
    'serial_number',
    'order_state',
    'order_date',
    'customer_name',
    'customer_department',
    'car_type',
    'delivery_type',
    'departure_department',
    'departure_staff',
    'departure_customer',
    'departure_dong_name',
    'departure_address',
    'destination_customer',
    'destination_dong_name',
    'destination_address',
    'payment_type_code',
    'total_cost',
    'summary',
    'basic_cost',
    'addition_cost',
    'discount_cost',
    'delivery_cost',
    'start_c_code',
    'dest_c_code',
    'happy_call',
    'customer_code',
    'rider_code',
    'rider_id',
    'rider_name',
    'rider_mobile',
    'rider_lon',
    'rider_lat',
    'distance',
    'order_regist_type',
    // 추가 필드 (시스템 필드)
    'cc_code',
    'api_name',
    '_status_type',
    'created_at',
    'updated_at'
];

// 숨길 컬럼 목록
const hiddenColumns = [
    'rider_id',
    'rider_lon',
    'rider_lat',
    'rider_mobile',
    'start_c_code',
    'departure_address',
    'destination_address',
    'summary',
    'dest_c_code',
    '_status_type',
    'cc_code',
    'created_at',
    'updated_at',
    'customer_code'
];

// 정렬 상태 변수 (기본값: order_date 내림차순)
let currentSortColumn = 'order_date';
let currentSortDirection = 'desc'; // 'asc' or 'desc'

// 모든 주문에서 컬럼(필드) 목록 추출
function extractAllColumns(orders) {
    const columnSet = new Set();
    orders.forEach(order => {
        Object.keys(order).forEach(key => columnSet.add(key));
    });

    // 숨김 컬럼 제외
    const filteredCols = Array.from(columnSet).filter(col => !hiddenColumns.includes(col));

    // 지정된 순서대로 정렬
    return filteredCols.sort((a, b) => {
        const idxA = columnOrder.indexOf(a);
        const idxB = columnOrder.indexOf(b);
        // 순서에 없는 컬럼은 맨 뒤로
        if (idxA === -1 && idxB === -1) return a.localeCompare(b);
        if (idxA === -1) return 1;
        if (idxB === -1) return -1;
        return idxA - idxB;
    });
}

// 컬럼명 한글 매핑
const columnLabels = {
    'from_date': '조회 시작일',
    'to_date': '조회 종료일',
    'total_record': '전체 레코드수',
    'total_page': '전체 페이지수',
    'current_page': '현재 페이지',
    'display_article': '화면출력 레코드',
    'current_display_article': '현재 화면출력 레코드',
    'serial_number': '오더 고유번호',
    'order_state': '처리상태',
    'order_date': '접수일',
    'customer_name': '접수자명',
    'customer_department': '접수자 부서명',
    'car_type': '차량',
    'delivery_type': '구분',
    'departure_department': '출발지 부서',
    'departure_staff': '출발지 담당',
    'departure_customer': '출발지 고객명',
    'departure_dong_name': '출발지 동명',
    'departure_address': '출발지 상세주소',
    'destination_customer': '도착지 고객명',
    'destination_dong_name': '도착지 동명',
    'destination_address': '도착지 상세주소',
    'payment_type_code': '지급구분',
    'total_cost': '발생요금',
    'summary': '전달내용',
    'basic_cost': '기본요금',
    'addition_cost': '추가요금',
    'discount_cost': '할인요금',
    'delivery_cost': '탁송요금',
    'start_c_code': '출발지 고객코드',
    'dest_c_code': '도착지 고객코드',
    'happy_call': '해피콜 회신번호',
    'customer_code': '접수자 코드',
    'rider_code': '기사 코드',
    'rider_id': '기사 아이디',
    'rider_name': '기사 성명',
    'rider_mobile': '기사 연락처',
    'rider_lon': '기사 위치좌표(경도)',
    'rider_lat': '기사 위치좌표(위도)',
    'distance': '출발지·도착지 거리',
    'order_regist_type': '접수유형',
    'cc_code': '콜센터 코드',
    'api_name': '콜센터명',
    '_status_type': '상태구분',
    'created_at': '생성일시',
    'updated_at': '수정일시'
};

// 컬럼별 고정 너비 설정 (table-layout: fixed 사용 시 필요)
const columnWidths = {
    'serial_number': '120px',
    'order_state': '80px',
    'order_date': '140px',
    'customer_name': '100px',
    'customer_department': '100px',
    'car_type': '60px',
    'delivery_type': '60px',
    'departure_department': '100px',
    'departure_staff': '80px',
    'departure_customer': '100px',
    'departure_dong_name': '80px',
    'destination_customer': '100px',
    'destination_dong_name': '80px',
    'payment_type_code': '70px',
    'total_cost': '80px',
    'basic_cost': '80px',
    'addition_cost': '80px',
    'discount_cost': '80px',
    'delivery_cost': '80px',
    'happy_call': '100px',
    'rider_code': '80px',
    'rider_name': '80px',
    'distance': '60px',
    'order_regist_type': '80px',
    'api_name': '120px'
};
const defaultColumnWidth = '100px';

// 테이블 헤더 동적 생성 (정렬 기능 포함)
function renderTableHeader() {
    const thead = document.getElementById('ordersTableHead');
    const headerRow = thead.querySelector('tr');

    // 헤더 셀 공통 인라인 스타일 (sticky 고정 - !important 포함)
    const thBaseStyle = 'position: sticky !important; top: 0 !important; background: #f9fafb !important; z-index: 20 !important; box-shadow: 0 2px 4px rgba(0,0,0,0.15); border-bottom: 2px solid #d1d5db;';

    // # 컬럼 (고정 너비 40px)
    let headerHtml = `<th class="px-3 py-2 text-left text-xs font-medium text-gray-700" style="${thBaseStyle} width: 50px; min-width: 50px; max-width: 50px;">#</th>`;

    allColumns.forEach(col => {
        const label = columnLabels[col] || col;
        const sortIcon = getSortIcon(col);
        const width = columnWidths[col] || defaultColumnWidth;
        const thStyle = `${thBaseStyle} width: ${width}; min-width: ${width}; max-width: ${width}; overflow: hidden; text-overflow: ellipsis;`;
        headerHtml += `<th class="px-3 py-2 text-left text-xs font-medium text-gray-700 whitespace-nowrap cursor-pointer select-none" style="${thStyle}" title="${col}" data-column="${col}" onclick="sortByColumn('${col}')">${label} <span class="sort-icon">${sortIcon}</span></th>`;
    });

    headerRow.innerHTML = headerHtml;
}

// 정렬 아이콘 반환
function getSortIcon(column) {
    if (currentSortColumn !== column) {
        return '⇅'; // 정렬되지 않은 상태
    }
    return currentSortDirection === 'asc' ? '▲' : '▼';
}

// 컬럼 정렬 함수
function sortByColumn(column) {
    // 같은 컬럼 클릭 시 방향 전환, 다른 컬럼 클릭 시 오름차순 시작
    if (currentSortColumn === column) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
    }

    // 정렬 수행 (applyDefaultSort 사용)
    applyDefaultSort();

    // 테이블 다시 렌더링
    renderOrdersWithPaging();
}

// 점진적 로딩 설정
const INITIAL_LOAD_COUNT = 100; // 처음에 표시할 건수
const LOAD_DELAY_MS = 1000;     // 나머지 로딩 지연 시간 (1초)
let loadingTimeoutId = null;    // 로딩 타이머 ID

// 처리상태에 따른 배지 스타일 반환 (displayText: UI 표시용 텍스트)
function getStateStyle(orderState) {
    const state = String(orderState || '').trim();

    // 접수
    if (state === '접수') {
        return { bg: '#fffacd', color: '#856404', border: '#f5deb3', displayText: '접수' };
    }
    // 배차
    if (state === '배차') {
        return { bg: '#f5deb3', color: '#8b4513', border: '#deb887', displayText: '배차' };
    }
    // 대기
    if (state === '대기') {
        return { bg: '#e2e8f0', color: '#475569', border: '#cbd5e1', displayText: '대기' };
    }
    // 배송 -> 운행으로 표시
    if (state === '배송') {
        return { bg: '#d4edda', color: '#155724', border: '#c3e6cb', displayText: '운행' };
    }
    // 예약
    if (state === '예약') {
        return { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb', displayText: '예약' };
    }
    // 완료
    if (state === '완료') {
        return { bg: '#ffffff', color: '#333333', border: '#e0e0e0', displayText: '완료' };
    }
    // 취소
    if (state === '취소') {
        return { bg: '#fee2e2', color: '#dc2626', border: '#fecaca', displayText: '취소' };
    }
    // 기본
    return { bg: '#f9fafb', color: '#374151', border: '#e5e7eb', displayText: state };
}

// 접수 상태 지연 레벨 계산 (10분 단위)
function getDelayLevel(orderDate) {
    if (!orderDate) return 0;

    const now = new Date();
    const orderTime = parseDateTimeValue(orderDate);
    if (!orderTime) return 0;

    const diffMs = now - orderTime;
    const diffMinutes = Math.floor(diffMs / (1000 * 60));

    if (diffMinutes >= 60) return 4;  // 1시간 이상: 지연4 (흑적색)
    if (diffMinutes >= 30) return 3;  // 30분 이상: 지연3
    if (diffMinutes >= 20) return 2;  // 20분 이상: 지연2
    if (diffMinutes >= 10) return 1;  // 10분 이상: 지연1
    return 0;  // 10분 미만: 정상
}

// 지연 레벨별 스타일
function getDelayStyle(level) {
    switch (level) {
        case 1: return { bg: '#fef3c7', color: '#d97706', border: '#fcd34d', label: '지연1' };  // 연한 주황
        case 2: return { bg: '#fed7aa', color: '#ea580c', border: '#fb923c', label: '지연2' };  // 주황
        case 3: return { bg: '#fecaca', color: '#dc2626', border: '#f87171', label: '지연3' };  // 빨강
        case 4: return { bg: '#fecaca', color: '#7f1d1d', border: '#f87171', label: '지연4' };  // 흑적색 (1시간+)
        default: return null;
    }
}

// 주문 행 내부 셀 HTML 생성 함수
function generateOrderCellsHtml(order, rowNum) {
    // # 컬럼 (고정 너비)
    let cellsHtml = `<td class="px-3 py-2 text-xs border-b" style="width: 50px; min-width: 50px; max-width: 50px; overflow: hidden;">${rowNum}</td>`;
    const orderState = String(order.order_state || order.state || '').trim();

    // 접수 상태일 때 지연 레벨 계산 (serial_number, order_date에 색상 적용용)
    let delayColor = null;
    if (orderState === '접수') {
        const delayLevel = getDelayLevel(order.order_date);
        if (delayLevel > 0) {
            const delayStyle = getDelayStyle(delayLevel);
            delayColor = delayStyle ? delayStyle.color : null;
        }
    }

    allColumns.forEach(col => {
        let value = order[col];

        // null/undefined 처리
        if (value === null || value === undefined) {
            value = '-';
        } else if (typeof value === 'object') {
            value = JSON.stringify(value);
        }

        const width = columnWidths[col] || defaultColumnWidth;
        const cellStyle = `width: ${width}; min-width: ${width}; max-width: ${width}; overflow: hidden; text-overflow: ellipsis;`;

        // 값이 너무 긴 경우 줄임 (컬럼 너비에 맞춰 조정)
        const maxLen = parseInt(width) / 8; // 대략적인 글자 수 계산
        const displayValue = String(value).length > maxLen ? String(value).substring(0, Math.floor(maxLen)) + '...' : value;

        // order_state 컬럼에 배지 스타일 적용 (배송 -> 운행으로 표시)
        if (col === 'order_state' || col === 'state') {
            const style = getStateStyle(value);
            cellsHtml += `<td class="px-3 py-2 text-xs border-b whitespace-nowrap" style="${cellStyle}">
                <span class="status-badge px-2 py-0.5 rounded text-xs font-medium" style="background: ${style.bg}; color: ${style.color}; border: 1px solid ${style.border};">${style.displayText}</span>
            </td>`;
        } else if (col === 'serial_number') {
            // serial_number 클릭 시 주문 상세 팝업 (지연 시 색상 변경)
            const colorStyle = delayColor ? `color: ${delayColor};` : 'color: #2563eb;';
            cellsHtml += `<td class="px-3 py-2 text-xs border-b whitespace-nowrap" style="${cellStyle}">
                <a href="javascript:void(0)" onclick="viewInsungOrderDetail('${String(value).replace(/'/g, "\\'")}', '<?= base_url('history/getOrderDetail') ?>')" class="hover:underline cursor-pointer" style="${colorStyle}" title="${String(value).replace(/"/g, '&quot;')}">${displayValue}</a>
            </td>`;
        } else if (col === 'order_date' && delayColor) {
            // 접수 상태에서 지연 시 order_date 폰트 색상 변경
            cellsHtml += `<td class="px-3 py-2 text-xs border-b whitespace-nowrap" style="${cellStyle} color: ${delayColor};" title="${String(value).replace(/"/g, '&quot;')}">${displayValue}</td>`;
        } else {
            cellsHtml += `<td class="px-3 py-2 text-xs border-b whitespace-nowrap" style="${cellStyle}" title="${String(value).replace(/"/g, '&quot;')}">${displayValue}</td>`;
        }
    });

    return cellsHtml;
}

// 주문 행 전체 HTML 생성 함수
function generateOrderRowHtml(order, rowNum) {
    return `<tr class="hover:bg-gray-50">${generateOrderCellsHtml(order, rowNum)}</tr>`;
}

// 전체 주문 렌더링 (점진적 로딩: 처음 100건 먼저, 나머지 1초 후)
function renderOrdersWithPaging() {
    const tbody = document.getElementById('ordersTableBody');
    const totalCount = filteredOrders.length;

    // 기존 타이머 취소
    if (loadingTimeoutId) {
        clearTimeout(loadingTimeoutId);
        loadingTimeoutId = null;
    }

    if (!filteredOrders || totalCount === 0) {
        // 컬럼 초기화
        allColumns = [];
        renderTableHeader();
        tbody.innerHTML = `
            <tr>
                <td colspan="1" class="px-4 py-8 text-center text-gray-500">
                    조회된 주문이 없습니다.
                </td>
            </tr>
        `;
        return;
    }

    // 모든 컬럼 추출 및 헤더 렌더링
    allColumns = extractAllColumns(filteredOrders);
    renderTableHeader();

    // 처음 INITIAL_LOAD_COUNT 건만 먼저 렌더링
    const initialCount = Math.min(INITIAL_LOAD_COUNT, totalCount);
    const initialOrders = filteredOrders.slice(0, initialCount);

    const initialHtml = initialOrders.map((order, index) => {
        const rowNum = totalCount - index;
        return generateOrderRowHtml(order, rowNum);
    }).join('');

    tbody.innerHTML = initialHtml;
    displayedCount = initialCount;

    // 나머지 데이터가 있으면 1초 후에 로딩
    if (totalCount > INITIAL_LOAD_COUNT) {
        loadingTimeoutId = setTimeout(() => {
            const remainingOrders = filteredOrders.slice(INITIAL_LOAD_COUNT);

            // DocumentFragment를 사용하여 성능 최적화
            const fragment = document.createDocumentFragment();

            remainingOrders.forEach((order, index) => {
                const actualIndex = INITIAL_LOAD_COUNT + index;
                const rowNum = totalCount - actualIndex;

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.innerHTML = generateOrderCellsHtml(order, rowNum);
                fragment.appendChild(tr);
            });

            tbody.appendChild(fragment);
            displayedCount = totalCount;
        }, LOAD_DELAY_MS);
    }
}


// 상태 필터링 함수 (배지 클릭용)
function filterByStatus(status) {
    currentStatusFilter = status;
    autoRefreshStatusFilter = [];  // 다중 필터 초기화

    // 배지 활성화 상태 업데이트
    document.querySelectorAll('.status-filter-badge').forEach(badge => {
        const badgeStatus = badge.getAttribute('data-status');
        if (badgeStatus === status) {
            badge.classList.add('active');
            badge.style.boxShadow = '0 0 0 2px #3b82f6';
            badge.style.transform = 'scale(1.05)';
        } else {
            badge.classList.remove('active');
            badge.style.boxShadow = 'none';
            badge.style.transform = 'scale(1)';
        }
    });

    applyFilter();
}

// 필터 적용 (단일/다중 필터 지원 + 콜센터 필터)
function applyFilter() {
    // 1단계: 상태 필터링
    let tempOrders;
    if (currentStatusFilter === '') {
        // 전체
        tempOrders = allOrders;
    } else if (currentStatusFilter === '__multiple__' && autoRefreshStatusFilter.length > 0) {
        // 다중 상태 필터링 (자동갱신용)
        tempOrders = allOrders.filter(o => {
            const orderState = String(o.order_state || o.state || '').trim();
            return autoRefreshStatusFilter.includes(orderState);
        });
    } else {
        // 단일 상태 필터링
        tempOrders = allOrders.filter(o => {
            const orderState = String(o.order_state || o.state || '').trim();
            return orderState === currentStatusFilter;
        });
    }

    // 2단계: 콜센터 필터링
    if (currentCallCenterFilter !== '') {
        filteredOrders = tempOrders.filter(o => {
            const apiName = String(o.api_name || '').trim();
            return apiName === currentCallCenterFilter;
        });
    } else {
        filteredOrders = tempOrders;
    }

    // 기본 정렬 적용 (order_date 내림차순)
    applyDefaultSort();

    renderOrdersWithPaging();
}

// 콜센터 필터링 함수
function filterByCallCenter(callCenterName) {
    currentCallCenterFilter = callCenterName;
    applyFilter();
}

// 기존 호환성 유지
function filterOrders(status) {
    currentStatusFilter = status;
    applyFilter();
}

// 날짜/시간 문자열인지 확인하고 Date 객체로 변환
function parseDateTimeValue(val) {
    if (!val) return null;
    const str = String(val).trim();

    // "2026-01-22 13:10" 또는 "2026-01-22T13:10" 형식
    if (/^\d{4}-\d{2}-\d{2}[\sT]\d{2}:\d{2}/.test(str)) {
        return new Date(str.replace(' ', 'T'));
    }
    // "20260122" 형식 (YYYYMMDD)
    if (/^\d{8}$/.test(str)) {
        return new Date(str.substring(0, 4) + '-' + str.substring(4, 6) + '-' + str.substring(6, 8));
    }
    // "2026-01-22" 형식
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        return new Date(str);
    }
    return null;
}

// 현재 정렬 기준으로 정렬 적용
function applyDefaultSort() {
    filteredOrders.sort((a, b) => {
        let valA = a[currentSortColumn];
        let valB = b[currentSortColumn];

        // null/undefined 처리 (맨 뒤로)
        if (valA === null || valA === undefined) valA = '';
        if (valB === null || valB === undefined) valB = '';

        // 날짜/시간 컬럼 처리 (order_date 등)
        if (currentSortColumn === 'order_date' || currentSortColumn.includes('date')) {
            const dateA = parseDateTimeValue(valA);
            const dateB = parseDateTimeValue(valB);

            if (dateA && dateB) {
                const diff = dateA.getTime() - dateB.getTime();
                return currentSortDirection === 'asc' ? diff : -diff;
            }
        }

        // 숫자 비교 시도 (순수 숫자만)
        const strA = String(valA).trim();
        const strB = String(valB).trim();
        if (/^-?\d+\.?\d*$/.test(strA) && /^-?\d+\.?\d*$/.test(strB)) {
            const numA = parseFloat(strA);
            const numB = parseFloat(strB);
            return currentSortDirection === 'asc' ? numA - numB : numB - numA;
        }

        // 문자열 비교
        if (currentSortDirection === 'asc') {
            return strA.toLowerCase().localeCompare(strB.toLowerCase(), 'ko');
        } else {
            return strB.toLowerCase().localeCompare(strA.toLowerCase(), 'ko');
        }
    });
}

function formatNumber(num) {
    return new Intl.NumberFormat('ko-KR').format(num);
}

// 대시보드 접기/펼치기 상태
let isDashboardCollapsed = false;

// 대시보드 토글 함수
function toggleDashboard() {
    const content = document.getElementById('dashboardContent');
    const icon = document.getElementById('dashboardToggleIcon');

    isDashboardCollapsed = !isDashboardCollapsed;

    if (isDashboardCollapsed) {
        content.style.display = 'none';
        icon.textContent = '▶';
        icon.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'block';
        icon.textContent = '▼';
        icon.style.transform = 'rotate(0deg)';
    }

    // 상태 저장 (localStorage)
    localStorage.setItem('insungDashboardCollapsed', isDashboardCollapsed);
}

// 대시보드 상태 복원
function restoreDashboardState() {
    const saved = localStorage.getItem('insungDashboardCollapsed');
    if (saved === 'true') {
        isDashboardCollapsed = true;
        const content = document.getElementById('dashboardContent');
        const icon = document.getElementById('dashboardToggleIcon');
        content.style.display = 'none';
        icon.textContent = '▶';
    }
}

// 대시보드 요약 텍스트 업데이트
function updateDashboardSummaryText() {
    const totalCC = document.getElementById('totalCallCenters').textContent;
    const successCC = document.getElementById('successCount').textContent;
    const summaryText = document.getElementById('dashboardSummaryText');
    summaryText.textContent = `(총 ${totalCC}개 콜센터 / 성공 ${successCC}개)`;
}

// 자동갱신 토글 함수
function toggleAutoRefresh() {
    const btn = document.getElementById('btnAutoRefresh');
    const btnText = document.getElementById('autoRefreshBtnText');
    const intervalSelect = document.getElementById('refreshIntervalSelect');

    autoRefreshEnabled = !autoRefreshEnabled;

    if (autoRefreshEnabled) {
        // 자동갱신 시작
        btn.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300', 'rounded-l');
        btn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'rounded');
        btnText.textContent = `⏹ ${getRefreshIntervalText()}`;

        // 간격 선택 숨김
        intervalSelect.style.display = 'none';

        // 접수, 대기 상태만 필터링
        filterByMultipleStatus(['접수', '대기']);

        // 즉시 한 번 갱신
        refreshOrders();

        // 선택된 간격마다 갱신
        autoRefreshIntervalId = setInterval(() => {
            refreshOrders();
        }, getRefreshInterval());

    } else {
        // 자동갱신 중지
        btn.classList.remove('bg-green-600', 'text-white', 'hover:bg-green-700', 'rounded');
        btn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300', 'rounded-l');
        btnText.textContent = '🔁 자동갱신';

        // 간격 선택 표시
        intervalSelect.style.display = '';

        // 타이머 중지
        if (autoRefreshIntervalId) {
            clearInterval(autoRefreshIntervalId);
            autoRefreshIntervalId = null;
        }

        // 진행 중인 요청 취소
        if (currentAbortController) {
            currentAbortController.abort();
            currentAbortController = null;
        }
        isRefreshing = false;

        // 전체 필터로 복원
        filterByStatus('');
    }
}

// 다중 상태 필터링 (자동갱신용)
function filterByMultipleStatus(statuses) {
    autoRefreshStatusFilter = statuses;
    currentStatusFilter = '__multiple__';  // 특수 값으로 다중 필터 표시

    // 배지 UI 업데이트
    document.querySelectorAll('.status-filter-badge').forEach(badge => {
        const badgeStatus = badge.dataset.status;
        if (statuses.includes(badgeStatus)) {
            badge.classList.add('active');
            badge.style.boxShadow = '0 0 0 2px #3b82f6';
            badge.style.transform = 'scale(1.05)';
        } else {
            badge.classList.remove('active');
            badge.style.boxShadow = 'none';
            badge.style.transform = 'scale(1)';
        }
    });

    applyFilter();
}
</script>

<?= $this->include('forms/insung-order-detail-modal') ?>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>