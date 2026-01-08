<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>최근사용기록</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <style>
        /* 본인이 등록한 주문 강조 스타일 */
        .my-order-row {
            background-color: #fef3c7 !important; /* 연한 노란색 배경 */
            border-left: 3px solid #f59e0b; /* 주황색 왼쪽 테두리 */
        }
        .my-order-row:hover {
            background-color: #fde68a !important; /* 호버 시 더 진한 노란색 */
        }
        
        /* DataTables 스타일 가이드 적용 */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            font-size: 12px !important;
            color: #64748b !important;
        }
        
        .dataTables_wrapper .dataTables_length select {
            padding: 4px 12px !important;
            font-size: 12px !important;
            height: 24px !important;
            border-radius: 6px !important;
            border: 1px solid #e2e8f0 !important;
            background: #f1f5f9 !important;
            color: #475569 !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            padding: 4px 12px !important;
            font-size: 12px !important;
            height: 24px !important;
            border-radius: 6px !important;
            border: 1px solid #e2e8f0 !important;
            background: #fff !important;
            color: #475569 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 4px 8px !important;
            font-size: 11px !important;
            height: 22px !important;
            min-width: 22px !important;
            border-radius: 50% !important;
            margin: 0 2px !important;
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #e2e8f0 !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e2e8f0 !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            background: #f8fafc !important;
            color: #94a3b8 !important;
            border: 1px solid #e2e8f0 !important;
            cursor: not-allowed !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.previous,
        .dataTables_wrapper .dataTables_paginate .paginate_button.next {
            border-radius: 6px !important;
            min-width: 50px !important;
        }
        
        /* 선택 버튼 스타일 */
        .popup-action-btn {
            padding: 4px 12px;
            font-size: 11px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .popup-action-btn:hover {
            background: #2563eb;
        }
        /* 클릭 가능한 주소 스타일 */
        #recentOrdersTable tbody td[onclick] {
            cursor: pointer;
            color: #2563eb;
            text-decoration: underline;
        }
        
        /* 테이블 너비 고정 */
        #recentOrdersTable {
            width: 100% !important;
            table-layout: fixed;
        }
        
        /* 컬럼 너비 조정 */
        #recentOrdersTable th,
        #recentOrdersTable td {
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body style="margin: 0; padding: 10px; font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc;">
<div class="popup-container">
    <div class="popup-header">
        <div class="popup-buttons">
            <button type="button" class="popup-nav-btn popup-nav-btn-active">최근사용기록</button>
            <button type="button" class="popup-nav-btn" onclick="location.href='<?= base_url('bookmark/popup?type=' . $type) ?>'">내 즐겨찾기</button>
        </div>
    </div>
    
    <div class="list-table-container">
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 32px 0; color: #64748b;">
                최근 접수 내역이 없습니다.
            </div>
        <?php else: ?>
        <!-- 설명문 -->
        <div style="padding: 8px 12px; background-color: #f1f5f9; border-radius: 4px; margin-bottom: 12px; font-size: 12px; color: #475569;">
            <span style="color: #3b82f6; font-weight: 600;">💡 안내:</span> '추가' 버튼 클릭하면, 즐겨찾기에 추가됩니다.
        </div>
        <div class="overflow-x-auto">
            <table class="list-table" id="recentOrdersTable">
                <thead>
                    <tr>
                        <th style="width: 140px;">접수일시</th>
                        <th style="width: 120px;">접수자</th>
                        <th style="width: 60px;">선택</th>
                        <th style="width: 300px;">출발지</th>
                        <th style="width: 35px;">추가</th>
                        <th style="width: 300px;">도착지</th>
                        <th style="width: 35px;">추가</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $loginType = session()->get('login_type');
                    $currentInsungUserId = ($loginType === 'daumdata') ? session()->get('user_id') : null;
                    foreach ($orders as $index => $order): 
                        // insung_user_id가 본인과 같은지 확인
                        $isMyOrder = ($loginType === 'daumdata' && $currentInsungUserId && 
                                     !empty($order['insung_user_id']) && 
                                     $order['insung_user_id'] === $currentInsungUserId);
                    ?>
                        <tr data-order-index="<?= $index ?>"
                            <?php if ($isMyOrder): ?>class="my-order-row"<?php endif; ?>
                            data-departure-company-name="<?= esc($order['departure_company_name'] ?? '', 'attr') ?>"
                            data-departure-contact="<?= esc($order['departure_contact'] ?? '', 'attr') ?>"
                            data-departure-department="<?= esc($order['departure_department'] ?? '', 'attr') ?>"
                            data-departure-manager="<?= esc($order['departure_manager'] ?? '', 'attr') ?>"
                            data-departure-dong="<?= esc($order['departure_dong'] ?? '', 'attr') ?>"
                            data-departure-address="<?= esc($order['departure_address'] ?? '', 'attr') ?>"
                            data-departure-detail="<?= esc($order['departure_detail'] ?? '', 'attr') ?>"
                            data-departure-lon="<?= esc($order['departure_lon'] ?? '', 'attr') ?>"
                            data-departure-lat="<?= esc($order['departure_lat'] ?? '', 'attr') ?>"
                            data-destination-company-name="<?= esc($order['destination_company_name'] ?? '', 'attr') ?>"
                            data-destination-contact="<?= esc($order['destination_contact'] ?? '', 'attr') ?>"
                            data-destination-department="<?= esc($order['destination_department'] ?? '', 'attr') ?>"
                            data-destination-manager="<?= esc($order['destination_manager'] ?? '', 'attr') ?>"
                            data-destination-dong="<?= esc($order['destination_dong'] ?? '', 'attr') ?>"
                            data-destination-address="<?= esc($order['destination_address'] ?? '', 'attr') ?>"
                            data-destination-detail="<?= esc($order['detail_address'] ?? $order['destination_detail'] ?? '', 'attr') ?>"
                            data-destination-lon="<?= esc($order['destination_lon'] ?? '', 'attr') ?>"
                            data-destination-lat="<?= esc($order['destination_lat'] ?? '', 'attr') ?>">
                            <!-- 접수일시 -->
                            <td style="width: 140px;"><?= esc($order['save_date'] ? date('Y-m-d H:i', strtotime($order['save_date'])) : '') ?></td>
                            <!-- 접수자 이름 -->
                            <td style="width: 120px;"><?= esc($order['receiver_name'] ?? '') ?></td>
                            <!-- 선택 버튼 (출발지 + 도착지 모두 세팅) -->
                            <td style="width: 60px; text-align: center;">
                                <?php if ((!empty($order['departure_company_name']) || !empty($order['departure_address'])) && 
                                         (!empty($order['destination_company_name']) || !empty($order['destination_address']))): ?>
                                <button type="button" class="popup-action-btn" onclick="set_both_info(<?= $index ?>)">출도착지선택</button>
                                <?php endif; ?>
                            </td>
                            <!-- 출발지 정보 (클릭 가능) -->
                            <td style="width: 300px;<?php if (!empty($order['departure_company_name']) || !empty($order['departure_address'])): ?> cursor: pointer; color: #2563eb; text-decoration: underline;<?php endif; ?>" 
                                <?php if (!empty($order['departure_company_name']) || !empty($order['departure_address'])): ?>
                                onclick="set_order_info(<?= $index ?>, 'departure')"
                                <?php 
                                // 마우스 오버 시 표시할 정보 (dong, address)
                                $departureTooltip = '';
                                if (!empty($order['departure_dong'])) {
                                    $departureTooltip .= '기준동명: ' . esc($order['departure_dong'], 'attr');
                                }
                                if (!empty($order['departure_address'])) {
                                    if (!empty($departureTooltip)) $departureTooltip .= "\n";
                                    $departureTooltip .= '주소: ' . esc($order['departure_address'], 'attr');
                                }
                                if (!empty($departureTooltip)) {
                                    echo 'title="' . $departureTooltip . '"';
                                }
                                ?>
                                <?php endif; ?>>
                                <?php
                                // 출발지 정보 표시: company_name, department, manager
                                $departureInfo = [];
                                if (!empty($order['departure_company_name'])) {
                                    $departureInfo[] = esc($order['departure_company_name']);
                                }
                                if (!empty($order['departure_department'])) {
                                    $departureInfo[] = esc($order['departure_department']);
                                }
                                if (!empty($order['departure_manager'])) {
                                    $departureInfo[] = esc($order['departure_manager']);
                                }
                                echo implode(' / ', $departureInfo);
                                
                                // 주소 간단 표시
                                $departureAddress = '';
                                if (!empty($order['departure_dong'])) {
                                    $departureDong = $order['departure_dong'];
                                    // 슬래시가 있으면 뒤의 값만 사용
                                    if (strpos($departureDong, '/') !== false) {
                                        $departureDong = trim(explode('/', $departureDong)[1] ?? $departureDong);
                                    }
                                    $departureAddress = $departureDong;
                                }
                                if (!empty($order['departure_address'])) {
                                    $addressParts = explode(' ', $order['departure_address']);
                                    $departureAddress .= (!empty($departureAddress) ? ' ' : '') . $addressParts[0];
                                    if (count($addressParts) > 1) {
                                        $departureAddress .= ' ' . $addressParts[1];
                                    }
                                }
                                if (!empty($departureAddress)) {
                                    echo '<br><span style="font-size: 11px; color: #64748b;">' . esc($departureAddress) . '</span>';
                                }
                                ?>
                            </td>
                            <!-- 출발지 추가 버튼 -->
                            <td style="width: 35px; text-align: center; padding: 4px;">
                                <?php if (!empty($order['departure_company_name']) || !empty($order['departure_address'])): ?>
                                <button type="button" class="popup-action-btn" style="background: #10b981; font-size: 10px; padding: 2px 6px;" 
                                        onclick="addToBookmark(<?= $index ?>, 'departure', event)">
                                    추가
                                </button>
                                <?php endif; ?>
                            </td>
                            <!-- 도착지 정보 (클릭 가능) -->
                            <td style="width: 300px;<?php if (!empty($order['destination_company_name']) || !empty($order['destination_address'])): ?> cursor: pointer; color: #2563eb; text-decoration: underline;<?php endif; ?>" 
                                <?php if (!empty($order['destination_company_name']) || !empty($order['destination_address'])): ?>
                                onclick="set_order_info(<?= $index ?>, 'destination')"
                                <?php 
                                // 마우스 오버 시 표시할 정보 (dong, address)
                                $destinationTooltip = '';
                                if (!empty($order['destination_dong'])) {
                                    $destinationTooltip .= '기준동명: ' . esc($order['destination_dong'], 'attr');
                                }
                                if (!empty($order['destination_address'])) {
                                    if (!empty($destinationTooltip)) $destinationTooltip .= "\n";
                                    $destinationTooltip .= '주소: ' . esc($order['destination_address'], 'attr');
                                }
                                if (!empty($destinationTooltip)) {
                                    echo 'title="' . $destinationTooltip . '"';
                                }
                                ?>
                                <?php endif; ?>>
                        <?php
                                // 도착지 정보 표시: company_name, department, manager
                                $destinationInfo = [];
                                if (!empty($order['destination_company_name'])) {
                                    $destinationInfo[] = esc($order['destination_company_name']);
                                }
                                if (!empty($order['destination_department'])) {
                                    $destinationInfo[] = esc($order['destination_department']);
                                }
                                if (!empty($order['destination_manager'])) {
                                    $destinationInfo[] = esc($order['destination_manager']);
                                }
                                echo implode(' / ', $destinationInfo);
                                
                                // 주소 간단 표시
                                $destinationAddress = '';
                                if (!empty($order['destination_dong'])) {
                                    $destinationDong = $order['destination_dong'];
                                    // 슬래시가 있으면 뒤의 값만 사용
                                    if (strpos($destinationDong, '/') !== false) {
                                        $destinationDong = trim(explode('/', $destinationDong)[1] ?? $destinationDong);
                                    }
                                    $destinationAddress = $destinationDong;
                                }
                                if (!empty($order['destination_address'])) {
                                    $addressParts = explode(' ', $order['destination_address']);
                                    $destinationAddress .= (!empty($destinationAddress) ? ' ' : '') . $addressParts[0];
                                    if (count($addressParts) > 1) {
                                        $destinationAddress .= ' ' . $addressParts[1];
                                    }
                                }
                                if (!empty($destinationAddress)) {
                                    echo '<br><span style="font-size: 11px; color: #64748b;">' . esc($destinationAddress) . '</span>';
                                }
                                ?>
                            </td>
                            <!-- 도착지 추가 버튼 -->
                            <td style="width: 35px; text-align: center; padding: 4px;">
                                <?php if (!empty($order['destination_company_name']) || !empty($order['destination_address'])): ?>
                                <button type="button" class="popup-action-btn" style="background: #10b981; font-size: 10px; padding: 2px 6px;" 
                                        onclick="addToBookmark(<?= $index ?>, 'destination', event)">
                                    추가
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// 쿠키 관련 함수
function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// 부모창에서 팝업을 띄운 컨텍스트 (S: 출발지, D: 도착지)
var popupContext = '<?= esc($type ?? 'S', 'attr') ?>'; // 'S' 또는 'D'

// 팝업 크기 조절 함수 (전역으로 정의)
function adjustPopupSize() {
    // 팝업 창이 아니면 실행하지 않음
    if (!window.opener && window === window.top) {
        return;
    }
    
    try {
        // 실제 DOM 요소의 높이 측정
        var headerHeight = $('.popup-header').outerHeight(true) || 60;
        var controlsHeight = $('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter').parent().outerHeight(true) || 60;
        var tableHeaderHeight = $('#recentOrdersTable thead').outerHeight(true) || 40;
        var tableBodyHeight = $('#recentOrdersTable tbody').outerHeight(true) || 0;
        var paginationHeight = $('.dataTables_wrapper .dataTables_paginate').outerHeight(true) || 40;
        var infoHeight = $('.dataTables_wrapper .dataTables_info').outerHeight(true) || 20;
        
        // 전체 컨텐츠 높이 계산
        var contentHeight = headerHeight + controlsHeight + tableHeaderHeight + tableBodyHeight + paginationHeight + infoHeight + 40; // 여유 공간 40px
        
        // 최소/최대 높이 제한
        var minHeight = 300;
        var maxHeight = window.screen.height - 50; // 화면 높이에서 여유 공간 제외
        var finalHeight = Math.max(minHeight, Math.min(contentHeight, maxHeight));
        
        // 팝업 창 크기 조절 (너비는 고정)
        window.resizeTo(1363, finalHeight);
        
        console.log('Popup resized to:', 1363, 'x', finalHeight, 'Content height:', contentHeight);
    } catch (e) {
        // 팝업이 다른 도메인이거나 크기 조절이 불가능한 경우 무시
        console.log('Popup resize error:', e);
    }
}

// DataTables 초기화
$(document).ready(function() {
    // 쿠키에서 저장된 페이지당 항목 수 가져오기
    var savedPageLength = parseInt(getCookie('recentOrders_pageLength')) || 25;
    
    var table = $('#recentOrdersTable').DataTable({
        "pageLength": savedPageLength,
        "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        "language": {
            "search": "검색:",
            "lengthMenu": "_MENU_ 개씩 보기",
            "info": "_TOTAL_ 개 중 _START_ - _END_",
            "infoEmpty": "데이터가 없습니다",
            "infoFiltered": "(전체 _MAX_ 개 중 필터링)",
            "paginate": {
                "first": "처음",
                "last": "마지막",
                "next": "다음",
                "previous": "이전"
            },
            "zeroRecords": "검색 결과가 없습니다"
        },
        "order": [[0, "desc"]], // 접수일시 최신순
        "columnDefs": [
            { "orderable": false, "targets": [2, 3, 4, 5, 6] } // 선택 버튼, 출발지, 출발지 추가, 도착지, 도착지 추가는 정렬 불가
        ],
        "initComplete": function(settings, json) {
            // DataTables 초기화 완료 후 팝업 크기 조절
            setTimeout(function() {
                adjustPopupSize();
            }, 200);
        }
    });
    
    // 페이지당 항목 수 변경 시 쿠키에 저장 및 팝업 크기 조절
    table.on('length.dt', function(e, settings, len) {
        setCookie('recentOrders_pageLength', len, 365);
        setTimeout(function() {
            adjustPopupSize();
        }, 200);
    });
    
    // 검색 시 팝업 크기 조절
    table.on('search.dt', function() {
        setTimeout(function() {
            adjustPopupSize();
        }, 200);
    });
    
    // 페이지 변경 시 팝업 크기 조절
    table.on('page.dt', function() {
        setTimeout(function() {
            adjustPopupSize();
        }, 200);
    });
});

// 주문 정보 설정 (팝업 컨텍스트에 따라 부모창의 출발지 또는 도착지 필드에 세팅)
function set_order_info(index, dataType) {
    if (!window.opener) {
        return;
    }
    
    const doc = window.opener.document;
    
    // data attribute에서 데이터 읽기
    const row = document.querySelector('tr[data-order-index="' + index + '"]');
    if (!row) {
        return;
    }
    
    // 팝업 컨텍스트에 따라 부모창의 대상 필드 결정
    // popupContext === 'S' (출발지 버튼으로 팝업 열림) → 부모창 출발지 필드에 세팅
    // popupContext === 'D' (도착지 버튼으로 팝업 열림) → 부모창 도착지 필드에 세팅
    const targetPrefix = (popupContext === 'S') ? 'departure' : 'destination';
    
    // dataType에 따라 출발지 또는 도착지 데이터 읽기
    if (dataType === 'departure') {
        const company_name = row.getAttribute('data-departure-company-name') || '';
        const contact = row.getAttribute('data-departure-contact') || '';
        const department = row.getAttribute('data-departure-department') || '';
        const manager = row.getAttribute('data-departure-manager') || '';
        const dong = row.getAttribute('data-departure-dong') || '';
        const address = row.getAttribute('data-departure-address') || '';
        const detail = row.getAttribute('data-departure-detail') || '';
        const lon = row.getAttribute('data-departure-lon') || '';
        const lat = row.getAttribute('data-departure-lat') || '';
        
        // manager가 없으면 company_name 사용
        const finalManager = manager || company_name;
        
        // dong 값에서 슬래시가 있으면 슬래시 뒤의 값만 사용
        let finalDetail = detail;
        // detail이 없거나, detail이 dong과 같으면 dong에서 슬래시 처리
        if (!finalDetail || finalDetail === dong) {
            if (dong && dong.indexOf('/') !== -1) {
                // 슬래시가 있으면 슬래시 뒤의 값만 사용
                finalDetail = dong.split('/').pop().trim();
            } else if (dong) {
                // 슬래시가 없으면 전체 값 사용
                finalDetail = dong;
            }
        }
        
        // 팝업 컨텍스트에 따라 부모창의 출발지 또는 도착지 필드에 세팅
        if (doc.getElementById(targetPrefix + '_company_name')) {
            doc.getElementById(targetPrefix + '_company_name').value = company_name;
        }
        if (doc.getElementById(targetPrefix + '_contact')) {
            doc.getElementById(targetPrefix + '_contact').value = contact;
        }
        if (doc.getElementById(targetPrefix + '_department')) {
            doc.getElementById(targetPrefix + '_department').value = department;
        }
        if (doc.getElementById(targetPrefix + '_manager')) {
            doc.getElementById(targetPrefix + '_manager').value = finalManager;
        }
        if (doc.getElementById(targetPrefix + '_dong')) {
            doc.getElementById(targetPrefix + '_dong').value = dong;
        }
        if (doc.getElementById(targetPrefix + '_address')) {
            doc.getElementById(targetPrefix + '_address').value = address;
        }
        if (doc.getElementById(targetPrefix + '_detail')) {
            doc.getElementById(targetPrefix + '_detail').value = finalDetail || '';
        }
        if (doc.getElementById(targetPrefix + '_lon')) {
            doc.getElementById(targetPrefix + '_lon').value = lon;
        }
        if (doc.getElementById(targetPrefix + '_lat')) {
            doc.getElementById(targetPrefix + '_lat').value = lat;
        }
    } else {
        // 도착지 데이터 읽기
        const company_name = row.getAttribute('data-destination-company-name') || '';
        const contact = row.getAttribute('data-destination-contact') || '';
        const department = row.getAttribute('data-destination-department') || '';
        const manager = row.getAttribute('data-destination-manager') || '';
        const dong = row.getAttribute('data-destination-dong') || '';
        const address = row.getAttribute('data-destination-address') || '';
        const detail = row.getAttribute('data-destination-detail') || '';
        const lon = row.getAttribute('data-destination-lon') || '';
        const lat = row.getAttribute('data-destination-lat') || '';
        
        // manager가 없으면 company_name 사용
        const finalManager = manager || company_name;
        
        // dong 값에서 슬래시가 있으면 슬래시 뒤의 값만 사용
        let finalDetail = detail;
        // detail이 없거나, detail이 dong과 같으면 dong에서 슬래시 처리
        if (!finalDetail || finalDetail === dong) {
            if (dong && dong.indexOf('/') !== -1) {
                // 슬래시가 있으면 슬래시 뒤의 값만 사용
                finalDetail = dong.split('/').pop().trim();
            } else if (dong) {
                // 슬래시가 없으면 전체 값 사용
                finalDetail = dong;
            }
        }
        
        // 팝업 컨텍스트에 따라 부모창의 출발지 또는 도착지 필드에 세팅
        if (doc.getElementById(targetPrefix + '_company_name')) {
            doc.getElementById(targetPrefix + '_company_name').value = company_name;
        }
        if (doc.getElementById(targetPrefix + '_contact')) {
            doc.getElementById(targetPrefix + '_contact').value = contact;
        }
        if (doc.getElementById(targetPrefix + '_department')) {
            doc.getElementById(targetPrefix + '_department').value = department;
        }
        if (doc.getElementById(targetPrefix + '_manager')) {
            doc.getElementById(targetPrefix + '_manager').value = finalManager;
        }
        if (doc.getElementById(targetPrefix + '_dong')) {
            doc.getElementById(targetPrefix + '_dong').value = dong;
        }
        if (doc.getElementById(targetPrefix + '_address')) {
            doc.getElementById(targetPrefix + '_address').value = address;
        }
        // 도착지의 경우 detail_address 필드도 확인
        if (targetPrefix === 'destination') {
            if (doc.getElementById('destination_detail')) {
                doc.getElementById('destination_detail').value = finalDetail || '';
            } else if (doc.getElementById('detail_address')) {
                doc.getElementById('detail_address').value = finalDetail || '';
            }
        } else {
            if (doc.getElementById(targetPrefix + '_detail')) {
                doc.getElementById(targetPrefix + '_detail').value = finalDetail || '';
            }
        }
        if (doc.getElementById(targetPrefix + '_lon')) {
            doc.getElementById(targetPrefix + '_lon').value = lon;
        }
        if (doc.getElementById(targetPrefix + '_lat')) {
            doc.getElementById(targetPrefix + '_lat').value = lat;
        }
    }
    
    // price_set 함수가 있으면 호출
    if (typeof window.opener.price_set === 'function') {
        window.opener.price_set();
    }
    
    window.close();
}

// 출발지와 도착지 정보를 모두 세팅하는 함수
function set_both_info(index) {
    if (!window.opener) {
        return;
    }
    
    const doc = window.opener.document;
    
    // data attribute에서 데이터 읽기
    const row = document.querySelector('tr[data-order-index="' + index + '"]');
    if (!row) {
        return;
    }
    
    // 출발지 데이터 읽기
    const departure_company_name = row.getAttribute('data-departure-company-name') || '';
    const departure_contact = row.getAttribute('data-departure-contact') || '';
    const departure_department = row.getAttribute('data-departure-department') || '';
    const departure_manager = row.getAttribute('data-departure-manager') || '';
    const departure_dong = row.getAttribute('data-departure-dong') || '';
    const departure_address = row.getAttribute('data-departure-address') || '';
    const departure_detail = row.getAttribute('data-departure-detail') || '';
    const departure_lon = row.getAttribute('data-departure-lon') || '';
    const departure_lat = row.getAttribute('data-departure-lat') || '';
    
    // 도착지 데이터 읽기
    const destination_company_name = row.getAttribute('data-destination-company-name') || '';
    const destination_contact = row.getAttribute('data-destination-contact') || '';
    const destination_department = row.getAttribute('data-destination-department') || '';
    const destination_manager = row.getAttribute('data-destination-manager') || '';
    const destination_dong = row.getAttribute('data-destination-dong') || '';
    const destination_address = row.getAttribute('data-destination-address') || '';
    const destination_detail = row.getAttribute('data-destination-detail') || '';
    const destination_lon = row.getAttribute('data-destination-lon') || '';
    const destination_lat = row.getAttribute('data-destination-lat') || '';
    
    // manager가 없으면 company_name 사용
    const finalDepartureManager = departure_manager || departure_company_name;
    const finalDestinationManager = destination_manager || destination_company_name;
    
    // departure_dong 값에서 슬래시가 있으면 슬래시 뒤의 값만 사용
    let finalDepartureDetail = departure_detail;
    // detail이 없거나, detail이 dong과 같으면 dong에서 슬래시 처리
    if (!finalDepartureDetail || finalDepartureDetail === departure_dong) {
        if (departure_dong && departure_dong.indexOf('/') !== -1) {
            // 슬래시가 있으면 슬래시 뒤의 값만 사용
            finalDepartureDetail = departure_dong.split('/').pop().trim();
        } else if (departure_dong) {
            // 슬래시가 없으면 전체 값 사용
            finalDepartureDetail = departure_dong;
        }
    }
    
    // destination_dong 값에서 슬래시가 있으면 슬래시 뒤의 값만 사용
    let finalDestinationDetail = destination_detail;
    // detail이 없거나, detail이 dong과 같으면 dong에서 슬래시 처리
    if (!finalDestinationDetail || finalDestinationDetail === destination_dong) {
        if (destination_dong && destination_dong.indexOf('/') !== -1) {
            // 슬래시가 있으면 슬래시 뒤의 값만 사용
            finalDestinationDetail = destination_dong.split('/').pop().trim();
        } else if (destination_dong) {
            // 슬래시가 없으면 전체 값 사용
            finalDestinationDetail = destination_dong;
        }
    }
    
    // 출발지 정보 세팅
    if (doc.getElementById('departure_company_name')) {
        doc.getElementById('departure_company_name').value = departure_company_name;
    }
    if (doc.getElementById('departure_contact')) {
        doc.getElementById('departure_contact').value = departure_contact;
    }
    if (doc.getElementById('departure_department')) {
        doc.getElementById('departure_department').value = departure_department;
    }
    if (doc.getElementById('departure_manager')) {
        doc.getElementById('departure_manager').value = finalDepartureManager;
    }
    if (doc.getElementById('departure_dong')) {
        doc.getElementById('departure_dong').value = departure_dong;
    }
    if (doc.getElementById('departure_address')) {
        doc.getElementById('departure_address').value = departure_address;
    }
    if (doc.getElementById('departure_detail')) {
        doc.getElementById('departure_detail').value = finalDepartureDetail || '';
    }
    if (doc.getElementById('departure_lon')) {
        doc.getElementById('departure_lon').value = departure_lon;
    }
    if (doc.getElementById('departure_lat')) {
        doc.getElementById('departure_lat').value = departure_lat;
    }
    
    // 도착지 정보 세팅
    if (doc.getElementById('destination_company_name')) {
        doc.getElementById('destination_company_name').value = destination_company_name;
    }
    if (doc.getElementById('destination_contact')) {
        doc.getElementById('destination_contact').value = destination_contact;
    }
    if (doc.getElementById('destination_department')) {
        doc.getElementById('destination_department').value = destination_department;
    }
    if (doc.getElementById('destination_manager')) {
        doc.getElementById('destination_manager').value = finalDestinationManager;
    }
    if (doc.getElementById('destination_dong')) {
        doc.getElementById('destination_dong').value = destination_dong;
    }
    if (doc.getElementById('destination_address')) {
        doc.getElementById('destination_address').value = destination_address;
    }
    if (doc.getElementById('destination_detail')) {
        doc.getElementById('destination_detail').value = finalDestinationDetail || '';
    } else if (doc.getElementById('detail_address')) {
        doc.getElementById('detail_address').value = finalDestinationDetail || '';
    }
    if (doc.getElementById('destination_lon')) {
        doc.getElementById('destination_lon').value = destination_lon;
    }
    if (doc.getElementById('destination_lat')) {
        doc.getElementById('destination_lat').value = destination_lat;
    }
    
    // price_set 함수가 있으면 호출
    if (typeof window.opener.price_set === 'function') {
        window.opener.price_set();
    }
    
    window.close();
}

// 즐겨찾기에 추가하는 함수
function addToBookmark(index, dataType, event) {
    // 이벤트 전파 중지 (부모 요소의 onclick 방지)
    if (event) {
        event.stopPropagation();
    }
    
    const row = document.querySelector('tr[data-order-index="' + index + '"]');
    if (!row) {
        alert('주문 정보를 찾을 수 없습니다.');
        return;
    }
    
    // 출발지 또는 도착지 데이터 읽기
    let bookmarkData = {};
    if (dataType === 'departure') {
        bookmarkData = {
            c_name: row.getAttribute('data-departure-company-name') || '',
            c_tel: row.getAttribute('data-departure-contact') || '',
            c_dept: row.getAttribute('data-departure-department') || '',
            c_charge: row.getAttribute('data-departure-manager') || '',
            c_dong: row.getAttribute('data-departure-dong') || '',
            c_addr: row.getAttribute('data-departure-address') || '', // 지번 주소 (addr_jibun)
            c_addr2: row.getAttribute('data-departure-detail') || '', // 도로명 주소 (addr_road)
            lon: row.getAttribute('data-departure-lon') || '',
            lat: row.getAttribute('data-departure-lat') || ''
        };
    } else {
        bookmarkData = {
            c_name: row.getAttribute('data-destination-company-name') || '',
            c_tel: row.getAttribute('data-destination-contact') || '',
            c_dept: row.getAttribute('data-destination-department') || '',
            c_charge: row.getAttribute('data-destination-manager') || '',
            c_dong: row.getAttribute('data-destination-dong') || '',
            c_addr: row.getAttribute('data-destination-address') || '', // 지번 주소 (addr_jibun)
            c_addr2: row.getAttribute('data-destination-detail') || '', // 도로명 주소 (addr_road)
            lon: row.getAttribute('data-destination-lon') || '',
            lat: row.getAttribute('data-destination-lat') || ''
        };
    }
    
    // 필수 필드 확인
    if (!bookmarkData.c_name) {
        alert('회사명이 없어 즐겨찾기에 추가할 수 없습니다.');
        return;
    }
    
    // AJAX로 즐겨찾기 추가
    $.ajax({
        url: '<?= base_url('bookmark/add') ?>',
        type: 'POST',
        data: bookmarkData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('즐겨찾기에 추가되었습니다.');
            } else {
                alert(response.message || '즐겨찾기 추가에 실패했습니다.');
            }
        },
        error: function(xhr, status, error) {
            console.error('즐겨찾기 추가 오류:', error);
            alert('즐겨찾기 추가 중 오류가 발생했습니다.');
        }
    });
}
</script>
</body>
</html>
