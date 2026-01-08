<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <style>
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
        
        #employeeTable tbody td {
            cursor: pointer;
        }
        
        #employeeTable tbody td:last-child {
            cursor: default;
        }
        
        #employeeTable tbody td:not(:last-child):hover {
            background-color: #f1f5f9 !important;
        }
        
        /* 팝업 전용 스타일 - 행 높이 조정 */
        .search-company-popup .list-table-container .list-table td,
        .search-company-popup .list-table-container .list-table tbody tr {
            height: 15pt !important;
            line-height: 15pt !important;
            min-height: 15pt !important;
            max-height: 15pt !important;
            padding: 2px 8px !important;
        }
        
        .search-company-popup .list-table-container {
            overflow: visible !important;
            max-height: none !important;
        }
        
        /* DataTables 행 높이 조정 */
        .search-company-popup #employeeTable tbody td {
            height: 15pt !important;
            line-height: 15pt !important;
            padding: 2px 8px !important;
            font-size: 12px !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
        }
        
        .search-company-popup #employeeTable thead th {
            height: 20px !important;
            padding: 3px 8px !important;
            font-size: 12px !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
        }
        
        /* DataTables 컨트롤 영역 컴팩트하게 */
        .search-company-popup .dataTables_wrapper {
            margin-top: 8px !important;
        }
        
        .search-company-popup .dataTables_wrapper .dataTables_length,
        .search-company-popup .dataTables_wrapper .dataTables_filter {
            margin-bottom: 8px !important;
        }
        
        /* 선택 버튼 스타일 조정 */
        .search-company-popup #employeeTable tbody td button.action-buttons {
            padding: 2px 6px !important;
            font-size: 11px !important;
            height: 18px !important;
            min-width: 40px !important;
        }
    </style>
</head>
<body class="search-company-popup" style="margin: 0; padding: 10px 30px 30px; background: #fff;">
    <div class="list-page-container">
        <!-- 타이틀 -->
        <div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0;">
            <h2 style="font-size: 14px; font-weight: 600; color: #334155; margin: 0;">직원목록</h2>
        </div>

        <!-- Hidden input for comp_code -->
        <input type="hidden" id="comp_code" value="<?= esc($comp_code ?? '') ?>">

        <!-- 로딩 영역 -->
        <div id="loading" style="display: none; text-align: center; padding: 30px; color: #64748b; font-size: 12px;">
            직원 목록을 불러오는 중...
        </div>

        <!-- 결과 영역 -->
        <div id="resultSection" style="display: none; margin-top: 0;">
            <div class="list-table-container">
                <table class="list-table" id="employeeTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>고객명</th>
                            <th>부서명</th>
                            <th>담당</th>
                            <th>연락처</th>
                            <th>선택</th>
                        </tr>
                    </thead>
                    <tbody id="employeeList">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 에러 메시지 -->
        <div id="errorMessage" class="error-message" style="display: none; margin-top: 12px; padding: 8px; background: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c33; font-size: 12px;"></div>
        
        <?php if (session()->getFlashdata('error')): ?>
        <div class="error-message" style="margin-top: 12px; padding: 8px; background: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c33; font-size: 12px;">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // 고객 상세 정보 캐시
        var employeeDetailCache = {};

        // 전역 변수로 직원 데이터 저장
        var employeesData = [];
        var employeeTable = null;

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

        // 직원 목록 로드
        function loadEmployeeList() {
            const compCode = document.getElementById('comp_code').value.trim();
            
            if (!compCode) {
                document.getElementById('errorMessage').textContent = '거래처코드가 없습니다.';
                document.getElementById('errorMessage').style.display = 'block';
                return;
            }

            // 로딩 표시
            document.getElementById('loading').style.display = 'block';
            document.getElementById('resultSection').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';

            const apiUrl = '<?= base_url('search-company/searchEmployee') ?>?ajax=list&comp_code=' + encodeURIComponent(compCode);

            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';

                if (data.error) {
                    document.getElementById('errorMessage').textContent = data.message || '직원 목록을 불러오는데 실패했습니다.';
                    document.getElementById('errorMessage').style.display = 'block';
                    return;
                }

                employeesData = data.data || [];

                if (!employeesData.length) {
                    document.getElementById('errorMessage').textContent = '조회된 직원이 없습니다.';
                    document.getElementById('errorMessage').style.display = 'block';
                    return;
                }

                // DataTables용 데이터 배열 생성
                var tableData = [];
                for (var i = 0; i < employeesData.length; i++) {
                    var employee = employeesData[i];
                    tableData.push([
                        escapeHtml(employee.c_name || ''),
                        escapeHtml(employee.dept_name || ''),
                        escapeHtml(employee.charge_name || ''),
                        escapeHtml(employee.c_telno || ''),
                        '<button type="button" class="action-buttons" data-index="' + i + '">선택</button>'
                    ]);
                }

                // 기존 DataTable이 있으면 제거
                if (employeeTable && $.fn.DataTable.isDataTable('#employeeTable')) {
                    employeeTable.destroy();
                }

                // 쿠키에서 저장된 페이지당 항목 수 가져오기
                var savedPageLength = parseInt(getCookie('employeeSearch_pageLength')) || 25;

                // DataTables 초기화
                employeeTable = $('#employeeTable').DataTable({
                    "data": tableData,
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
                    "order": [[0, "asc"]],
                    "columnDefs": [
                        { "orderable": false, "targets": 4 }
                    ],
                    "createdRow": function(row, data, dataIndex) {
                        // 마지막 컬럼(선택 버튼)을 제외한 모든 셀에 클릭 가능 스타일 적용
                        $(row).find('td').not(':last-child').css({
                            'cursor': 'pointer'
                        });
                    }
                });

                // 페이지당 항목 수 변경 시 쿠키에 저장
                employeeTable.on('length.dt', function(e, settings, len) {
                    setCookie('employeeSearch_pageLength', len, 365);
                });

                // 행 클릭 이벤트
                $('#employeeTable tbody').off('click', 'tr').on('click', 'tr', function() {
                    var data = employeeTable.row(this).data();
                    if (data) {
                        var rowIndex = $(this).find('button[data-index]').data('index');
                        if (rowIndex !== undefined && employeesData[rowIndex]) {
                            selectEmployeeByIndex(rowIndex);
                        }
                    }
                });

                // 선택 버튼 클릭 이벤트
                $('#employeeTable tbody').off('click', 'button[data-index]').on('click', 'button[data-index]', function(e) {
                    e.stopPropagation();
                    var index = $(this).data('index');
                    if (index !== undefined && employeesData[index]) {
                        selectEmployeeByIndex(index);
                    }
                });

                document.getElementById('resultSection').style.display = 'block';
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('errorMessage').textContent = '오류가 발생했습니다: ' + error.message;
                document.getElementById('errorMessage').style.display = 'block';
            });
        }

        // 인덱스로 직원 선택
        function selectEmployeeByIndex(index) {
            if (!employeesData[index]) {
                return;
            }

            const employee = employeesData[index];
            selectEmployee(employee.c_name, employee.c_telno, employee.o_ccode);
        }

        // 직원 선택 시 상세정보 가져와서 setEmployee 호출
        function selectEmployee(c_name, c_telno, o_ccode) {
            // 캐시에 있으면 바로 사용
            if (employeeDetailCache[o_ccode]) {
                const d = employeeDetailCache[o_ccode];
                setEmployee(c_name, c_telno, d.c_dept, d.c_charge, d.c_dong, d.c_addr, d.c_addr2, d.c_fulladdr, d.c_lon, d.c_lat, d.c_sido, d.c_gungu, o_ccode);
                return;
            }

            // AJAX로 상세정보 가져오기
            const apiUrl = '<?= base_url('search-company/searchEmployee') ?>?ajax=detail&c_code=' + encodeURIComponent(o_ccode);

            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    if (data.disabled) {
                        alert('사용중지된 고객입니다.');
                    } else {
                        alert('고객 정보를 불러오는데 실패했습니다: ' + (data.message || '알 수 없는 오류'));
                    }
                    return;
                }

                const d = data.data;
                employeeDetailCache[o_ccode] = d;
                setEmployee(c_name, c_telno, d.c_dept, d.c_charge, d.c_dong, d.c_addr, d.c_addr2, d.c_fulladdr, d.c_lon, d.c_lat, d.c_sido, d.c_gungu, o_ccode);
            })
            .catch(error => {
                alert('고객 정보를 불러오는데 실패했습니다.');
                console.error('Error:', error);
            });
        }

        // 부모창 폼 필드에 데이터 채우기
        function setEmployee(name, telno, c_dept, c_charge, c_dong, c_addr, c_addr2, c_fulladdr, c_lon, c_lat, c_sido, c_gungu, o_ccode) {
            if (!window.opener) {
                return;
            }

            const doc = window.opener.document;
            const form = doc.getElementById('orderForm') || doc.forms[0];

            if (!form) {
                return;
            }

            // 주문자 정보(보이는 필드) - STN_LOGIS 호환 (id 기준)
            const c_name_elem = doc.getElementById('c_name');
            const c_telno_elem = doc.getElementById('c_telno');
            const c_dept_elem = doc.getElementById('c_dept');
            const c_charge_elem = doc.getElementById('c_charge');
            
            // 우리쪽 변수명 (id 기준)
            const company_name_elem = doc.getElementById('company_name');
            const contact_elem = doc.getElementById('contact');
            const dept_elem = doc.getElementById('dept');
            const charge_elem = doc.getElementById('charge');
            
            // 우리쪽 변수명 필드 (company_name, contact, dept, charge) - 우선 세팅
            if (company_name_elem) {
                company_name_elem.value = name;
                hideLabel(company_name_elem);
                company_name_elem.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (contact_elem) {
                contact_elem.value = telno;
                hideLabel(contact_elem);
                contact_elem.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (dept_elem) {
                dept_elem.value = c_dept;
                hideLabel(dept_elem);
                dept_elem.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (charge_elem) {
                charge_elem.value = c_charge;
                hideLabel(charge_elem);
                charge_elem.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            // STN_LOGIS 호환 필드 (c_name, c_telno, c_dept, c_charge) - hidden 필드
            if (c_name_elem) {
                c_name_elem.value = name;
            }
            if (c_telno_elem) {
                c_telno_elem.value = telno;
            }
            if (c_dept_elem) {
                c_dept_elem.value = c_dept;
            }
            if (c_charge_elem) {
                c_charge_elem.value = c_charge;
            }

            // 기존 필드명 (name 기준) - STN_LOGIS 참조
            if (form.f_name) {
                form.f_name.value = name;
            }
            if (form.f_telno) {
                form.f_telno.value = telno;
            }
            if (form.f_dept) {
                form.f_dept.value = c_dept;
            }
            if (form.f_charge) {
                form.f_charge.value = c_charge;
            }
            if (form.f_dong) {
                form.f_dong.value = c_dong;
            }
            if (form.f_addr) {
                form.f_addr.value = c_addr;
            }
            if (form.f_addr2) {
                form.f_addr2.value = c_addr2;
            }
            if (form.f_fulladdr) {
                form.f_fulladdr.value = c_fulladdr;
            }
            if (form.f_lon) {
                form.f_lon.value = c_lon;
            }
            if (form.f_lat) {
                form.f_lat.value = c_lat;
            }
            if (form.f_sido) {
                form.f_sido.value = c_sido;
            }
            if (form.f_gungu) {
                form.f_gungu.value = c_gungu;
            }
            if (form.o_ccode) {
                form.o_ccode.value = o_ccode;
            }

            // 팝업 닫기
            window.close();
        }

        // label 숨기기 처리
        function hideLabel(inputElem) {
            if (inputElem && inputElem.nextElementSibling && inputElem.nextElementSibling.classList.contains('required-label')) {
                if (inputElem.value) {
                    inputElem.nextElementSibling.style.display = 'none';
                }
            }
        }

        // HTML 이스케이프
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 페이지 로드 시 자동 조회
        document.addEventListener('DOMContentLoaded', function() {
            // comp_code가 있으면 자동 조회
            const compCode = document.getElementById('comp_code').value.trim();
            if (compCode) {
                loadEmployeeList();
            }
        });
    </script>
</body>
</html>
