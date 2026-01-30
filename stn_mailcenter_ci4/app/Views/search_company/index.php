<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="search-company-popup" style="margin: 0; padding: 10px 30px 30px; background: #fff;">
    <div class="list-page-container">
        <!-- 검색 영역 -->
        <div class="search-compact">
            <form id="searchForm" method="post">
                <input type="hidden" name="api_idx" value="<?= esc($api_idx) ?>">
                <div class="input-group">
                    <!-- 거래처코드는 항상 텍스트 입력으로 처리 -->
                    <input type="text" name="comp_code" id="comp_code" class="form-input" placeholder="거래처코드" value="" required>
                    <input type="text" name="charge_name" id="charge_name" class="form-input" placeholder="담당자명" value="">
                    <input type="text" name="tel_no" id="tel_no" class="form-input" placeholder="전화번호" value="">
                    <button type="submit" class="search-button">조회하기</button>
                </div>
                <div class="help-text">
                    (퀵 업체에서 부여받은 거래처 코드로 조회 하시기 바랍니다.)
                </div>
            </form>
        </div>

        <!-- 결과 영역 -->
        <div id="resultSection" style="display: none; margin-top: 0;">
            <div id="compName" class="comp-name"></div>
            <div id="paginationInfo" style="margin-bottom: 12px; padding: 8px; background: #f8fafc; border-radius: 4px; font-size: 12px; color: #64748b;">
            </div>
            <div class="list-table-container">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; min-width: 50px;">번호</th>
                            <th class="w110">부서</th>
                            <th class="w110">담당</th>
                            <th class="w110">전화번호</th>
                            <th class="w110">아이디</th>
                            <th class="w110">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody id="memberList">
                    </tbody>
                </table>
            </div>
            <div id="pagination" class="list-pagination" style="margin-top: 16px; display: none;">
            </div>
        </div>

        <!-- 로딩 및 에러 메시지 -->
        <div id="loading" class="loading-message" style="display: none;">조회 중...</div>
        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>

    <script>
        let currentPage = 1;
        let currentLimit = 15;
        let currentSearchParams = {};

        function searchMembers(page = 1) {
            const compCode = document.getElementById('comp_code').value.trim();
            const chargeName = document.getElementById('charge_name').value.trim();
            const telNo = document.getElementById('tel_no').value.trim();
            const apiIdx = <?= esc($api_idx) ?>;
            
            if (!compCode) {
                alert('거래처코드를 입력해주세요.');
                return;
            }

            // 검색 조건이 변경되었는지 확인
            const searchChanged = (
                currentSearchParams.comp_code !== compCode ||
                currentSearchParams.charge_name !== chargeName ||
                currentSearchParams.tel_no !== telNo
            );
            
            // 검색 조건이 변경되었으면 1페이지로 리셋
            if (searchChanged) {
                page = 1;
            }

            // 로딩 표시
            document.getElementById('loading').style.display = 'block';
            document.getElementById('resultSection').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';

            // 검색 파라미터 저장
            currentSearchParams = {
                api_idx: apiIdx,
                comp_code: compCode,
                charge_name: chargeName,
                tel_no: telNo,
                page: page,
                limit: currentLimit
            };

            // 거래처 정보 조회 (첫 페이지일 때만)
            if (page === 1) {
                fetch('<?= base_url('search-company/getCompanyInfo') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        api_idx: apiIdx,
                        comp_code: compCode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('compName').textContent = data.comp_name;
                        // 회원 리스트 조회
                        return fetchMembers();
                    } else {
                        throw new Error(data.message || '거래처 정보 조회 실패');
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('errorMessage').textContent = error.message || '오류가 발생했습니다.';
                    document.getElementById('errorMessage').style.display = 'block';
                });
            } else {
                // 페이지 변경 시에는 바로 회원 리스트 조회
                fetchMembers();
            }
        }

        function fetchMembers() {
            fetch('<?= base_url('search-company/search') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(currentSearchParams)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                
                // 에러 메시지 숨기기
                document.getElementById('errorMessage').style.display = 'none';
                
                if (data.success) {
                    const memberList = document.getElementById('memberList');
                    memberList.innerHTML = '';

                    if (data.members && data.members.length > 0) {
                        // 역순 번호 계산 (전체 건수에서 역순)
                        const totalCount = data.pagination ? data.pagination.total_count : data.members.length;
                        const currentPage = data.pagination ? data.pagination.current_page : 1;
                        const perPage = data.pagination ? data.pagination.per_page : 15;
                        
                        data.members.forEach(function(member, index) {
                            const row = document.createElement('tr');
                            
                            // 역순 번호 계산: 전체 건수 - (현재 페이지 - 1) * 페이지당 건수 - 인덱스
                            const reverseNumber = totalCount - (currentPage - 1) * perPage - index;
                            
                            // 전화번호 포맷팅
                            let telDisplay = '';
                            const telNumbers = [];
                            if (member.tel_no1) {
                                telNumbers.push(formatTel(member.tel_no1));
                            }
                            if (member.tel_no2) {
                                telNumbers.push(formatTel(member.tel_no2));
                            }
                            telDisplay = telNumbers.join(', ');
                            
                            // use_state가 'Y'가 아닌 경우 버튼 비활성화 (사용중인 경우는 수정 가능)
                            const isRegistered = member.is_registered === true || member.is_registered === 'true';
                            const isDisabled = member.use_state !== 'Y';
                            const buttonClass = isDisabled ? 'action-buttons disabled' : (isRegistered ? 'action-buttons registered' : 'action-buttons');
                            const buttonStyle = isDisabled ? 'opacity: 0.5; cursor: not-allowed;' : (isRegistered ? 'background: #e0f2fe; border-color: #7dd3fc;' : '');
                            const buttonOnClick = isDisabled ? '' : `onclick="useMember('${member.c_code}', ${currentSearchParams.api_idx})"`;
                            const buttonText = isRegistered ? '사용중' : '사용하기';
                            
                            // use_state가 "중지"인 경우 아이디만 표시하고 나머지는 숨김
                            const isStopped = member.use_state === '중지' || member.use_state === 'N' || (member.use_state && member.use_state !== 'Y');
                            const deptNameDisplay = isStopped ? '' : escapeHtml(member.dept_name || '');
                            const chargeNameDisplay = isStopped ? '' : escapeHtml(member.charge_name || '');
                            const telDisplayFinal = isStopped ? '' : telDisplay;
                            const buttonDisplay = isStopped ? 'none' : '';
                            
                            row.innerHTML = `
                                <td style="width: 50px; min-width: 50px;">${reverseNumber}</td>
                                <td class="w110">${deptNameDisplay}</td>
                                <td class="w110">${chargeNameDisplay}</td>
                                <td class="w110">${telDisplayFinal}</td>
                                <td class="w110">${escapeHtml(member.user_id || '')}</td>
                                <td class="w110" style="display: ${buttonDisplay};">
                                    <button type="button" class="${buttonClass}" ${buttonOnClick} style="${buttonStyle}" ${isDisabled ? 'disabled' : ''}>${buttonText}</button>
                                </td>
                            `;
                            memberList.appendChild(row);
                        });
                        
                        // 페이징 정보 표시 (항상 갱신)
                        if (data.pagination) {
                            displayPagination(data.pagination);
                        } else {
                            // 페이징 정보가 없으면 숨김
                            document.getElementById('pagination').style.display = 'none';
                            document.getElementById('paginationInfo').style.display = 'none';
                        }
                        
                        document.getElementById('resultSection').style.display = 'block';
                    } else {
                        // 데이터가 없을 때 페이징 정보도 숨김
                        document.getElementById('pagination').style.display = 'none';
                        document.getElementById('paginationInfo').style.display = 'none';
                        document.getElementById('errorMessage').textContent = '조회된 회원이 없습니다.';
                        document.getElementById('errorMessage').style.display = 'block';
                    }
                } else {
                    throw new Error(data.message || '회원 리스트 조회 실패');
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('errorMessage').textContent = error.message || '오류가 발생했습니다.';
                document.getElementById('errorMessage').style.display = 'block';
            });
        }

        function displayPagination(pagination) {
            const paginationInfo = document.getElementById('paginationInfo');
            const paginationDiv = document.getElementById('pagination');
            
            // 페이징 정보 초기화 (이전 페이징 정보 제거)
            paginationDiv.innerHTML = '';
            paginationDiv.style.display = 'none';
            paginationInfo.style.display = 'none';
            
            if (pagination && pagination.total_count > 0) {
                paginationInfo.textContent = `총 ${pagination.total_count.toLocaleString()}명`;
                paginationInfo.style.display = 'block';
            } else {
                paginationInfo.style.display = 'none';
            }

            // 페이징 없이 전체 표시
            paginationDiv.style.display = 'none';
        }

        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            searchMembers(1);
        });

        function useMember(cCode, apiIdx) {
            // 회원등록 팝업 열기
            const url = '<?= base_url('search-company/register') ?>?ccode=' + encodeURIComponent(cCode) + '&api_idx=' + apiIdx;
            window.location.href = url;
        }

        function formatTel(tel) {
            if (!tel) return '';
            // 전화번호 마스킹 (예: 010-1234-5678 -> 010-****-5678)
            const cleaned = tel.replace(/[^0-9]/g, '');
            if (cleaned.length === 11) {
                return cleaned.substring(0, 3) + '-****-' + cleaned.substring(7);
            } else if (cleaned.length === 10) {
                return cleaned.substring(0, 3) + '-****-' + cleaned.substring(6);
            }
            return tel;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

