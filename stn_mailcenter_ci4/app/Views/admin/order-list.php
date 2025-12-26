<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('/admin/order-list', ['method' => 'GET', 'id' => 'search-form']) ?>
        <div class="search-filter-container">
            <div class="search-filter-item">
                <label class="search-filter-label">거래처</label>
                <select name="sel_comp_code" id="sel_comp_code" class="search-filter-select">
                    <option value="1" <?= $sel_comp_code == '1' ? 'selected' : '' ?>>전체거래처</option>
                    <?php foreach ($company_list as $company): ?>
                    <option value="<?= esc($company['comp_no']) ?>" <?= $sel_comp_code == $company['comp_no'] ? 'selected' : '' ?>>
                        <?= esc($company['cc_code'] ?? '') ?>_<?= esc($company['corp_name']) ?> [<?= esc($company['owner']) ?>] - <?= esc($company['tel_no']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">상태</label>
                <select name="state" id="state" class="search-filter-select">
                    <option value="">:: 전체 ::</option>
                    <option value="10" <?= $state == '10' ? 'selected' : '' ?>>접수</option>
                    <option value="11" <?= $state == '11' ? 'selected' : '' ?>>배차</option>
                    <option value="12" <?= $state == '12' ? 'selected' : '' ?>>운행</option>
                    <option value="30" <?= $state == '30' ? 'selected' : '' ?>>완료</option>
                    <option value="40" <?= $state == '40' ? 'selected' : '' ?>>취소</option>
                </select>
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">조회기간</label>
                <input type="text" name="from_date" id="from_date" value="<?= esc($from_date) ?>" class="search-filter-input" readonly>
                <span class="mx-2">~</span>
                <input type="text" name="to_date" id="to_date" value="<?= esc($to_date) ?>" class="search-filter-input" readonly>
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button" id="btn_search">🔍 조회/재조회</button>
                <button type="button" class="search-button" onclick="doExcel()" id="btn_excel">📊 엑셀변환</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 안내 메시지 -->
    <div class="mb-4 px-4 py-2 bg-blue-50 rounded-lg border border-blue-200">
        <div class="text-sm text-blue-800">
            <i class="fas fa-info-circle"></i> 원하시는 오더에서 <span class="font-bold">'오른쪽 마우스 버튼'</span>을 누르시면 여러 옵션(정보조회, 위치정보, 재접수, 문의, 취소)을 확인하실 수 있습니다.
        </div>
    </div>

    <!-- 주문 목록 테이블 -->
    <div class="list-table-container">
        <div id="orderGrid" class="ddataGrid" style="overflow-x: auto;">
            <div class="spinner-box" id="loading-spinner">
                <div class="spinner-border text-danger" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <table id="datatable" class="list-table" style="opacity:0; min-width: 100%;" oncontextmenu="return false">
                <thead>
                    <tr>
                        <th class="w40">번호</th>
                        <th class="w160">거래처명</th>
                        <th class="w80">ID</th>
                        <th class="w40">상태</th>
                        <th class="w120">접수일자</th>
                        <th class="w90">픽업시간</th>
                        <th class="w90">완료시간</th>
                        <th class="w80">주문번호</th>
                        <th class="w120">의뢰자</th>
                        <th class="w80">의뢰담당</th>
                        <th class="w120">출발지</th>
                        <th class="w120">출발동</th>
                        <th class="w120">출발담당</th>
                        <th class="w120">출발부서</th>
                        <th class="w100">출발전화번호</th>
                        <th class="w300 w-lg1">출발상세</th>
                        <th class="w120">도착지</th>
                        <th class="w120">도착동</th>
                        <th class="w120">도착담당</th>
                        <th class="w100">도착전화번호</th>
                        <th class="w300 w-lg1">도착상세</th>
                        <th class="w80">왕복</th>
                        <th class="w100">형태</th>
                        <th class="w80">차종</th>
                        <th class="w80">지급구분</th>
                        <th class="w100">기본요금</th>
                        <th class="w100">추가</th>
                        <th class="w100">탁송료</th>
                        <th class="w100">정산금액</th>
                        <th class="w400 w-lg2">적요</th>
                        <th class="w200">기사이름</th>
                    </tr>
                </thead>
                <tbody id="order-tbody">
                    <!-- 데이터는 JavaScript로 동적 로드 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 페이징 영역 -->
    <div id="pagination-container" class="list-pagination" style="display: none;">
        <div class="pagination" id="pagination">
            <!-- 페이징 버튼은 JavaScript로 동적 생성 -->
        </div>
    </div>

    <!-- 팝업 메뉴 -->
    <div id="popup-menu" class="popup-menu" style="display: none;"></div>
</div>

<!-- 주문 상세 팝업 모달 -->
<div id="orderDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4 order-detail-modal" style="z-index: 9999;">
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
            <div class="modal-content">
                <!-- 내용은 populateOrderDetail()에서 동적으로 생성됩니다 -->
            </div>
        </div>
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex justify-end gap-2">
            <button class="form-button form-button-secondary" onclick="closeOrderDetail()">닫기</button>
        </div>
    </div>
</div>

<style>
.detail-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.detail-section:last-child {
    border-bottom: none;
}

.detail-section h4 {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 16px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-item label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 4px;
}

.detail-item span {
    font-size: 14px;
    color: #111827;
    word-break: break-word;
}

.form-button {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.form-button-secondary {
    background-color: #6b7280;
    color: white;
}

.form-button-secondary:hover {
    background-color: #4b5563;
}

/* 테이블 좌우 스크롤 */
#orderGrid {
    overflow-x: auto;
    overflow-y: visible;
    width: 100%;
}

#orderGrid::-webkit-scrollbar {
    height: 8px;
}

#orderGrid::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

#orderGrid::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

#orderGrid::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* 페이징 버튼 스타일 */
.pagination .nav-button.disabled,
.pagination .page-number.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination button.nav-button,
.pagination button.page-number {
    cursor: pointer;
    border: none;
    background: #f1f5f9;
    color: #475569;
    transition: all 0.2s;
}

.pagination button.nav-button:hover,
.pagination button.page-number:hover {
    background: #e2e8f0;
    color: #111827;
}

.pagination .page-number.active {
    background: #e2e8f0 !important;
    color: #111827 !important;
}
</style>

<!-- jQuery UI Datepicker CSS -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css">
<!-- jQuery UI Datepicker JS -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
let currentPage = 1;
let totalPages = 1;
let orderData = [];

$(document).ready(function() {
    // 날짜 선택기 초기화 (jQuery UI Datepicker)
    $('#from_date, #to_date').datepicker({
        dateFormat: 'yy-mm-dd',
        prevText: '이전 달',
        nextText: '다음 달',
        monthNames: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],
        monthNamesShort: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],
        dayNames: ['일', '월', '화', '수', '목', '금', '토'],
        dayNamesShort: ['일', '월', '화', '수', '목', '금', '토'],
        dayNamesMin: ['일', '월', '화', '수', '목', '금', '토'],
        showMonthAfterYear: true,
        yearSuffix: '년'
    });

    // 검색 폼 제출
    $('#search-form').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadOrderList();
    });

    // 초기 로드
    loadOrderList();

    // 우클릭 메뉴
    $(document).on('contextmenu', '#datatable tbody tr', function(e) {
        $('#datatable tbody tr').removeClass('active');
        $(this).addClass('active');
        closePopupMenu();

        var winWidth = $(document).width();
        var winHeight = $(document).height();
        var posX = e.pageX;
        var posY = e.pageY;
        var menuWidth = $('#popup-menu').width();
        var menuHeight = $('#popup-menu').height();
        var secMargin = 10;
        var serialNumber = $(this).find('.ord_info').data('nm');

        let ajaxURL = '/ajax/order_menu.html?gofile=1&idx=' + serialNumber;

        var posLeft, posTop;
        if (posX + menuWidth + secMargin >= winWidth && posY + menuHeight + secMargin >= winHeight) {
            posLeft = posX - menuWidth - secMargin + 'px';
            posTop = posY - menuHeight - secMargin + 'px';
        } else if (posX + menuWidth + secMargin >= winWidth) {
            posLeft = posX - menuWidth - secMargin + 'px';
            posTop = posY + secMargin + 'px';
        } else if (posY + menuHeight + secMargin >= winHeight) {
            posLeft = posX + secMargin + 'px';
            posTop = posY - menuHeight - secMargin + 'px';
        } else {
            posLeft = posX + secMargin + 'px';
            posTop = posY - secMargin - menuHeight + 'px';
        }

        $('#popup-menu').css({
            'position': 'absolute',
            'z-index': '9999',
            'left': posLeft,
            'top': posTop,
            'display': 'block'
        }).load(ajaxURL);
    });
});

function loadOrderList() {
    $('#loading-spinner').show();
    $('#datatable').css('opacity', '0');

    var formData = {
        sel_comp_code: $('#sel_comp_code').val(),
        state: $('#state').val(),
        from_date: $('#from_date').val(),
        to_date: $('#to_date').val(),
        page: currentPage
    };

    $.ajax({
        url: '<?= base_url("admin/order-list-ajax") ?>',
        type: 'POST',
        data: formData,
        dataType: 'json',
        timeout: 60000, // 60초 타임아웃
        success: function(response) {
            $('#loading-spinner').hide();
            if (response && response.success && response.data) {
                orderData = response.data.orders || [];
                totalPages = response.data.total_page || 1;
                renderOrderTable(orderData);
                renderPagination();
                $('#datatable').css('opacity', '1');
            } else {
                $('#loading-spinner').hide();
                $('#datatable').css('opacity', '1');
                var errorMsg = response && response.message ? response.message : '알 수 없는 오류';
                console.error('주문 목록 조회 실패:', response);
                alert('주문 목록을 불러오는데 실패했습니다: ' + errorMsg);
                $('#order-tbody').html('<tr><td colspan="31" class="text-center py-4 text-red-600">조회 실패: ' + errorMsg + '</td></tr>');
                $('#pagination-container').hide();
            }
        },
        error: function(xhr, status, error) {
            $('#loading-spinner').hide();
            $('#datatable').css('opacity', '1');
            var errorMsg = '알 수 없는 오류';
            if (status === 'timeout') {
                errorMsg = '요청 시간이 초과되었습니다. 잠시 후 다시 시도해주세요.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 0) {
                errorMsg = '서버에 연결할 수 없습니다.';
            } else if (xhr.status === 500) {
                errorMsg = '서버 오류가 발생했습니다.';
            } else if (xhr.status === 403) {
                errorMsg = '접근 권한이 없습니다.';
            } else if (xhr.status === 401) {
                errorMsg = '로그인이 필요합니다.';
            } else {
                errorMsg = error || '알 수 없는 오류';
            }
            console.error('AJAX 오류:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            alert('주문 목록을 불러오는데 실패했습니다: ' + errorMsg);
            $('#order-tbody').html('<tr><td colspan="31" class="text-center py-4 text-red-600">조회 실패: ' + errorMsg + '</td></tr>');
        }
    });
}

function renderPagination() {
    var paginationHtml = '';
    
    if (totalPages <= 1) {
        $('#pagination-container').hide();
        return;
    }
    
    $('#pagination-container').show();
    
    // 처음 버튼
    if (currentPage > 1) {
        paginationHtml += '<button onclick="goToPage(1)" class="nav-button">처음</button>';
    } else {
        paginationHtml += '<span class="nav-button disabled">처음</span>';
    }
    
    // 이전 버튼
    if (currentPage > 1) {
        paginationHtml += '<button onclick="goToPage(' + (currentPage - 1) + ')" class="nav-button">이전</button>';
    } else {
        paginationHtml += '<span class="nav-button disabled">이전</span>';
    }
    
    // 페이지 번호 버튼 (최대 5개 표시)
    var showPages = 5;
    var halfPages = Math.floor(showPages / 2);
    var startPage = 1;
    var endPage = totalPages;
    
    if (totalPages > showPages) {
        if (currentPage <= halfPages + 1) {
            startPage = 1;
            endPage = showPages;
        } else if (currentPage >= totalPages - halfPages) {
            startPage = totalPages - showPages + 1;
            endPage = totalPages;
        } else {
            startPage = currentPage - halfPages;
            endPage = currentPage + halfPages;
        }
    }
    
    for (var i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHtml += '<span class="page-number active">' + i + '</span>';
        } else {
            paginationHtml += '<button onclick="goToPage(' + i + ')" class="page-number">' + i + '</button>';
        }
    }
    
    // 다음 버튼
    if (currentPage < totalPages) {
        paginationHtml += '<button onclick="goToPage(' + (currentPage + 1) + ')" class="nav-button">다음</button>';
    } else {
        paginationHtml += '<span class="nav-button disabled">다음</span>';
    }
    
    // 마지막 버튼
    if (currentPage < totalPages) {
        paginationHtml += '<button onclick="goToPage(' + totalPages + ')" class="nav-button">마지막</button>';
    } else {
        paginationHtml += '<span class="nav-button disabled">마지막</span>';
    }
    
    $('#pagination').html(paginationHtml);
}

function goToPage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadOrderList();
}

function renderOrderTable(orders) {
    var tbody = $('#order-tbody');
    tbody.empty();

    if (orders.length === 0) {
        tbody.append('<tr><td colspan="31" class="text-center py-4">조회된 주문이 없습니다.</td></tr>');
        $('#pagination-container').hide();
        return;
    }

    orders.forEach(function(order, index) {
        var bgClass = '';
        if (order.order_state == '접수') bgClass = 'state_10';
        else if (order.order_state == '배차') bgClass = 'state_11';
        else if (order.order_state == '운행') bgClass = 'state_12';
        else if (order.order_state == '완료') bgClass = 'state_30';
        else if (order.order_state == '예약') bgClass = 'state_50';
        else if (order.order_state == '취소') bgClass = 'state_40';
        else if (order.order_state == '대기') bgClass = 'state_00';

        var sumCost = (parseInt(order.basic_cost?.replace(/,/g, '') || 0) + 
                      parseInt(order.addition_cost?.replace(/,/g, '') || 0) + 
                      parseInt(order.delivery_cost?.replace(/,/g, '') || 0)).toLocaleString();

        var row = '<tr>' +
            '<td class="w40 ord_info" data-nm="' + (order.serial_number || '') + '"></td>' +
            '<td class="w160" title="' + (order.comp_name || '') + '">' + (order.comp_name || '') + '</td>' +
            '<td class="w80" title="' + (order.user_id || '') + '">' + (order.user_id || '') + '</td>' +
            '<td class="w40 ' + bgClass + '"><span><a onclick="popupMapView(\'' + (order.serial_number || '') + '\')">' + (order.order_state || '') + '</a></span></td>' +
            '<td class="w120" title="' + (order.order_date || '') + '">' + (order.order_date || '') + '</td>' +
            '<td class="w90" title="' + (order.pickup_time || '') + '">' + (order.pickup_time || '') + '</td>' +
            '<td class="w90" title="' + (order.complete_time || '') + '">' + (order.complete_time || '') + '</td>' +
            '<td class="w80"><a onclick="popupOrderView(\'' + (order.serial_number || '') + '\')"><b>' + (order.serial_number || '') + '</b></a></td>' +
            '<td class="w120" title="' + (order.customer_name || '') + '">' + (order.customer_name || '') + '</td>' +
            '<td class="w80">' + (order.departure_staff || '') + '</td>' +
            '<td class="w120">' + (order.departure_customer || '') + '</td>' +
            '<td class="w120">' + (order.departure_dong_name || '') + '</td>' +
            '<td class="w120">' + (order.departure_staff || '') + '</td>' +
            '<td class="w120">' + (order.departure_department || '') + '</td>' +
            '<td class="w100">' + (order.departure_tel || '') + '</td>' +
            '<td class="w300 w-lg1" title="' + (order.departure_address || '') + '">' + (order.departure_address || '') + '</td>' +
            '<td class="w120">' + (order.destination_customer || '') + '</td>' +
            '<td class="w120">' + (order.destination_dong_name || '') + '</td>' +
            '<td class="w120">' + (order.destination_staff || '') + '</td>' +
            '<td class="w100">' + (order.destination_tel || '') + '</td>' +
            '<td class="w300 w-lg1" title="' + (order.destination_address || '') + '">' + (order.destination_address || '') + '</td>' +
            '<td class="w90">' + (order.delivery_type || '') + '</td>' +
            '<td class="w100">' + (order.delivery_item_text || '') + '</td>' +
            '<td class="w80">' + (order.car_type || '') + '</td>' +
            '<td class="w80">' + (order.pay_gbn || '') + '</td>' +
            '<td class="w100 right">' + (order.basic_cost || '') + '</td>' +
            '<td class="w100 right">' + (order.addition_cost || '') + '</td>' +
            '<td class="w100 right">' + (order.delivery_cost || '') + '</td>' +
            '<td class="w100 right">' + sumCost + '</td>' +
            '<td class="w400 w-lg2" title="' + (order.summary || '') + '">' + (order.summary || '') + '</td>' +
            '<td class="w80">' + (order.rider_info || '') + ' ' + (order.rider_name || '') + '</td>' +
            '</tr>';
        tbody.append(row);
    });
}

function closePopupMenu() {
    $('#popup-menu').hide();
}

function popupOrderView(serial) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 로딩 상태 표시
    showOrderDetailLoading();
    
    // AJAX로 주문 상세 정보 가져오기
    fetch(`<?= base_url('admin/order-detail') ?>?idx=${serial}`, {
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
            showOrderDetailError(data.message || '주문 정보를 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showOrderDetailError('주문 정보 조회 중 오류가 발생했습니다.');
    })
    .finally(() => {
        hideOrderDetailLoading();
    });
}

function showOrderDetailLoading() {
    const modalContent = document.querySelector('#orderDetailModal .modal-content');
    if (modalContent) {
        modalContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">주문 정보를 불러오는 중...</div>';
    }
    document.getElementById('orderDetailModal').classList.remove('hidden');
    document.getElementById('orderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function hideOrderDetailLoading() {
    // 로딩 상태는 populateOrderDetail에서 실제 내용으로 대체됨
}

function showOrderDetailError(message) {
    const modalContent = document.querySelector('#orderDetailModal .modal-content');
    if (modalContent) {
        modalContent.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #ef4444; margin-bottom: 16px;">⚠️</div>
                <div style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">오류 발생</div>
                <div style="color: #6b7280;">${message}</div>
            </div>
        `;
    }
    document.getElementById('orderDetailModal').classList.remove('hidden');
    document.getElementById('orderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeOrderDetail() {
    document.getElementById('orderDetailModal').classList.add('hidden');
    document.getElementById('orderDetailModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
    
    // 모달 콘텐츠를 원래 상태로 복원
    restoreOrderDetailContent();
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

function restoreOrderDetailContent() {
    const modalContent = document.querySelector('#orderDetailModal .modal-content');
    if (modalContent) {
        modalContent.innerHTML = '';
    }
}

function populateOrderDetail(orderData) {
    restoreOrderDetailContent();
    
    // 헬퍼 함수: 값이 있으면 표시, 없으면 '-'
    const getValue = (value) => {
        if (value === null || value === undefined || value === '') return '-';
        return value;
    };
    
    // 헬퍼 함수: 날짜 포맷팅
    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        try {
            // YYYYMMDDHHmmss 형식 처리
            if (dateStr.length === 14) {
                const year = dateStr.substring(0, 4);
                const month = dateStr.substring(4, 6);
                const day = dateStr.substring(6, 8);
                const hour = dateStr.substring(8, 10);
                const minute = dateStr.substring(10, 12);
                const second = dateStr.substring(12, 14);
                return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
            }
            return new Date(dateStr).toLocaleString('ko-KR');
        } catch (e) {
            return dateStr;
        }
    };
    
    // 헬퍼 함수: 숫자 포맷팅 (금액)
    const formatAmount = (amount) => {
        if (!amount || amount === 0 || amount === '0') return '0원';
        const num = typeof amount === 'string' ? parseInt(amount.replace(/[^0-9]/g, '')) : amount;
        return new Intl.NumberFormat('ko-KR').format(num) + '원';
    };
    
    const modalContent = document.querySelector('#orderDetailModal .modal-content');
    if (!modalContent) return;
    
    // 인성 API 응답 구조에 맞게 HTML 생성
    modalContent.innerHTML = `
        <div class="detail-section">
            <h4>기본 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>주문번호</label>
                    <span>${getValue(orderData.serial_number)}</span>
                </div>
                <div class="detail-item">
                    <label>상태</label>
                    <span>${getValue(orderData.order_state)}</span>
                </div>
                <div class="detail-item">
                    <label>고객명</label>
                    <span>${getValue(orderData.customer_name)}</span>
                </div>
                <div class="detail-item">
                    <label>고객 전화</label>
                    <span>${getValue(orderData.customer_tel)}</span>
                </div>
                <div class="detail-item">
                    <label>부서</label>
                    <span>${getValue(orderData.customer_department)}</span>
                </div>
                <div class="detail-item">
                    <label>직책</label>
                    <span>${getValue(orderData.customer_duty)}</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>주문 시간 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>접수시간</label>
                    <span>${formatDate(getValue(orderData.order_time))}</span>
                </div>
                <div class="detail-item">
                    <label>배차시간</label>
                    <span>${formatDate(getValue(orderData.allocation_time))}</span>
                </div>
                <div class="detail-item">
                    <label>픽업시간</label>
                    <span>${formatDate(getValue(orderData.pickup_time))}</span>
                </div>
                <div class="detail-item">
                    <label>해결시간</label>
                    <span>${formatDate(getValue(orderData.resolve_time))}</span>
                </div>
                <div class="detail-item">
                    <label>완료시간</label>
                    <span>${formatDate(getValue(orderData.complete_time))}</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>출발지 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>동명</label>
                    <span>${getValue(orderData.departure_dong_name)}</span>
                </div>
                <div class="detail-item">
                    <label>상호</label>
                    <span>${getValue(orderData.departure_company_name)}</span>
                </div>
                <div class="detail-item">
                    <label>전화번호</label>
                    <span>${getValue(orderData.departure_tel)}</span>
                </div>
                <div class="detail-item">
                    <label>부서</label>
                    <span>${getValue(orderData.departure_department)}</span>
                </div>
                <div class="detail-item">
                    <label>담당</label>
                    <span>${getValue(orderData.departure_staff)}</span>
                </div>
                <div class="detail-item full-width">
                    <label>주소</label>
                    <span>${getValue(orderData.departure_address)}</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>도착지 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>동명</label>
                    <span>${getValue(orderData.destination_dong_name)}</span>
                </div>
                <div class="detail-item">
                    <label>상호</label>
                    <span>${getValue(orderData.destination_company_name)}</span>
                </div>
                <div class="detail-item">
                    <label>전화번호</label>
                    <span>${getValue(orderData.destination_tel)}</span>
                </div>
                <div class="detail-item">
                    <label>부서</label>
                    <span>${getValue(orderData.destination_department)}</span>
                </div>
                <div class="detail-item">
                    <label>담당</label>
                    <span>${getValue(orderData.destination_staff)}</span>
                </div>
                <div class="detail-item full-width">
                    <label>주소</label>
                    <span>${getValue(orderData.destination_address)}</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>기사 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>기사코드</label>
                    <span>${getValue(orderData.rider_code)}</span>
                </div>
                <div class="detail-item">
                    <label>기사명</label>
                    <span>${getValue(orderData.rider_name)}</span>
                </div>
                <div class="detail-item">
                    <label>기사 전화</label>
                    <span>${getValue(orderData.rider_tel)}</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>금액 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>기본요금</label>
                    <span>${formatAmount(orderData.basic_cost)}</span>
                </div>
                <div class="detail-item">
                    <label>추가요금</label>
                    <span>${formatAmount(orderData.addition_cost)}</span>
                </div>
                <div class="detail-item">
                    <label>탁송료</label>
                    <span>${formatAmount(orderData.delivery_cost)}</span>
                </div>
                <div class="detail-item">
                    <label>총액</label>
                    <span>${formatAmount(orderData.total_cost)}</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>기타 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>배송형태</label>
                    <span>${getValue(orderData.delivery_type)}</span>
                </div>
                <div class="detail-item">
                    <label>차종</label>
                    <span>${getValue(orderData.car_type)}</span>
                </div>
                <div class="detail-item">
                    <label>지급구분</label>
                    <span>${getValue(orderData.payment_type)}</span>
                </div>
                <div class="detail-item">
                    <label>거리</label>
                    <span>${getValue(orderData.distance)}</span>
                </div>
                <div class="detail-item full-width">
                    <label>적요</label>
                    <span>${getValue(orderData.summary)}</span>
                </div>
            </div>
        </div>
    `;
}

function popupMapView(serial) {
    var url = '/main/popup/map_view.html?idx=' + serial;
    var sizeWidth = 1000;
    var sizeHeight = 600;
    if (window.innerWidth <= 1000) sizeWidth = window.innerWidth - 50;
    if (window.innerHeight <= 600) sizeHeight = window.innerHeight - 50;
    var popupTop = (screen.height - sizeHeight) / 2;
    var popupLeft = (screen.width - sizeWidth) / 2;
    var status = 'status=yes, menubar=no, scrollbars=no, resizable=no, director=no, left=' + popupLeft + ', top=' + popupTop + ', width=' + sizeWidth + ', height=' + sizeHeight;
    window.open(url, 'MapView', status);
}

function doExcel() {
    var excelURL = '/xlsxwriter/admin_order_excel.html?type=excel' +
        '&sel_comp_code=' + $('#sel_comp_code').val() +
        '&state=' + $('#state').val() +
        '&from_date=' + $('#from_date').val() +
        '&to_date=' + $('#to_date').val();
    location = excelURL;
}
</script>

<?= $this->endSection() ?>

