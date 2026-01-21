<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>

    <div class="w-full max-w-full flex flex-col md:flex-row gap-4 box-border">
        <!-- 왼쪽 패널: 기본정보 -->
        <div class="flex-1 w-full min-w-0">
            <div class="mb-1">
                <section class="bg-blue-50 rounded-lg shadow-sm border-2 border-blue-300 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">기본정보</h2>
                    <div class="space-y-3">
                        <div class="form-field">
                            <label class="form-label">아이디</label>
                            <input type="text" 
                                   value="<?= esc($user['username'] ?? '') ?>" 
                                   readonly 
                                   class="form-input bg-gray-50 text-gray-600">
                        </div>
                        <div class="form-field">
                            <label class="form-label">소속 빌딩</label>
                            <input type="text" 
                                   value="<?= esc($user['customer_name'] ?? '') ?>" 
                                   readonly 
                                   class="form-input bg-gray-50 text-gray-600">
                        </div>
                        <div class="form-field">
                            <label class="form-label">상호명</label>
                            <input type="text" 
                                   value="<?= esc($user['customer_name'] ?? '') ?>" 
                                   readonly 
                                   class="form-input bg-gray-50 text-gray-600">
                        </div>
                        <div class="form-field">
                            <label class="form-label">소속부서</label>
                            <div class="flex gap-3 items-end">
                                <select id="user_dept" class="form-input flex-1">
                                    <option value="">부서를 선택하세요</option>
                                </select>
                                <?php if (($user['user_class'] ?? '') == '4'): ?>
                                <button type="button"
                                        id="settlement-depts-btn"
                                        onclick="openSettlementDeptsPopup()"
                                        class="flex-1 bg-white border border-gray-300 rounded text-left text-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        style="padding: 6px 8px;">
                                    <span id="settlement-depts-display">정산관리부서 (복수선택가능)</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-field">
                            <label class="form-label">담당자</label>
                            <input type="text" 
                                   id="real_name"
                                   value="<?= esc($user['real_name'] ?? '') ?>" 
                                   class="form-input">
                        </div>
                        <div class="form-field">
                            <label class="form-label">담당자 연락처</label>
                            <input type="text" 
                                   id="phone"
                                   value="<?= esc($user['phone'] ?? '') ?>" 
                                   class="form-input">
                        </div>
                        <div class="form-field">
                            <label class="form-label">주소</label>
                            <div class="flex space-x-2 mb-2">
                                <input type="text" 
                                       id="address_zonecode" 
                                       value="<?= esc($user['address_zonecode'] ?? '') ?>" 
                                       placeholder="우편번호"
                                       class="form-input w-24">
                                <button type="button" 
                                        onclick="openAddressSearch()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors whitespace-nowrap">
                                    주소검색
                                </button>
                            </div>
                            <input type="text" 
                                   id="address" 
                                   value="<?= esc($user['address'] ?? '') ?>" 
                                   placeholder="주소"
                                   class="form-input mb-2">
                            <input type="text" 
                                   id="address_detail" 
                                   value="<?= esc($user['address_detail'] ?? '') ?>" 
                                   placeholder="상세주소"
                                   class="form-input">
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- 가운데 패널: 비밀번호정보 -->
        <div class="flex-1 w-full min-w-0">
            <div class="mb-1">
                <section class="bg-gray-50 rounded-lg shadow-sm border-2 border-gray-300 p-3">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">비밀번호정보</h2>
                    <div class="space-y-3">
                        <div class="form-field">
                            <label class="form-label required">현재 비밀번호</label>
                            <input type="password" 
                                   id="current-password" 
                                   class="form-input"
                                   placeholder="현재 비밀번호를 입력하세요">
                        </div>
                        <div class="form-field">
                            <label class="form-label required">새 비밀번호</label>
                            <input type="password" 
                                   id="new-password" 
                                   class="form-input"
                                   placeholder="새 비밀번호를 입력하세요">
                        </div>
                        <div class="form-field">
                            <label class="form-label required">새 비밀번호 확인</label>
                            <input type="password" 
                                   id="new-password-confirm" 
                                   class="form-input"
                                   placeholder="새 비밀번호를 다시 입력하세요">
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- 오른쪽 패널: 저장/취소 버튼 -->
        <div class="w-full md:w-64 flex-shrink-0 max-w-full box-border">
            <div class="sticky top-4">
                <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border-2 border-gray-300 p-4 box-border">
                    <button onclick="saveAll()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap">
                        저장
                    </button>
                    <button type="button" 
                            onclick="location.reload()" 
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap">
                        취소
                    </button>
                </div>
            </div>
        </div>
    </div>


<!-- 경고 메시지 모달 -->
<div id="warningModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm" style="z-index: 10000 !important;">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800">알림</h3>
        </div>
        <div class="px-6 py-4">
            <p id="warningMessage" class="text-gray-700 whitespace-pre-line"></p>
    </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
            <button onclick="closeWarningModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                확인
            </button>
        </div>
    </div>
</div>

<!-- 정산관리부서 선택 레이어 팝업 -->
<div id="settlementDeptsPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4" style="z-index: 9999 !important; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md max-h-[85vh] flex flex-col" style="z-index: 10000 !important;">
        <!-- 헤더 -->
        <div class="sticky top-0 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 p-2 rounded-t-lg flex-shrink-0">
            <div class="flex justify-between items-center">
                <h2 class="text-xs font-bold text-gray-800" style="font-size: 12px !important; font-weight: 600 !important;">정산관리부서 선택</h2>
                <button onclick="closeSettlementDeptsPopup()" class="text-gray-500 hover:text-gray-700 text-lg font-bold w-6 h-6 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors" style="font-size: 18px !important; width: 24px !important; height: 24px !important;">&times;</button>
            </div>
            <p class="text-xs text-gray-600 mt-1" style="font-size: 11px !important;">복수 선택 가능합니다</p>
        </div>
        
        <!-- 본문 (스크롤 가능) -->
        <div class="flex-1 overflow-y-auto p-2 min-h-0" style="padding: 8px !important;">
            <div id="settlement-depts-list" class="space-y-1" style="gap: 4px !important;">
                <!-- 체크박스 목록이 여기에 동적으로 생성됩니다 -->
            </div>
        </div>
        
        <!-- 푸터 -->
        <div class="sticky bottom-0 bg-white border-t border-gray-200 p-2 rounded-b-lg flex-shrink-0" style="padding: 8px !important;">
            <div class="flex justify-between items-center mb-2" style="margin-bottom: 8px !important;">
                <span class="text-xs text-gray-600" style="font-size: 12px !important;">
                    선택된 부서: <span id="settlement-depts-count" class="font-semibold text-blue-600" style="font-weight: 600 !important;">0</span>개
                </span>
            </div>
            <div class="flex gap-2" style="gap: 8px !important;">
                <button onclick="selectAllSettlementDepts()" 
                        class="flex-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium transition-colors"
                        style="padding: 4px 12px !important; font-size: 12px !important; height: 24px !important; border-radius: 6px !important; font-weight: 600 !important; background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #e2e8f0 !important;">
                    전체 선택
                </button>
                <button onclick="deselectAllSettlementDepts()" 
                        class="flex-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium transition-colors"
                        style="padding: 4px 12px !important; font-size: 12px !important; height: 24px !important; border-radius: 6px !important; font-weight: 600 !important; background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #e2e8f0 !important;">
                    전체 해제
                </button>
                <button onclick="confirmSettlementDepts()" 
                        class="flex-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition-colors"
                        style="padding: 4px 12px !important; font-size: 12px !important; height: 24px !important; border-radius: 6px !important; font-weight: 600 !important;">
                    확인
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 다음 주소 검색 API 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>

<script>
// 페이지 로드 시 부서 목록 및 정산관리부서 목록 불러오기
document.addEventListener('DOMContentLoaded', function() {
    // 정산관리부서 목록을 먼저 불러온 후, 부서 목록을 불러와서 체크박스 생성
    Promise.all([
        fetch('<?= base_url('member/getSettlementDepts') ?>', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => response.json()),
        fetch('<?= base_url('member/getDepartmentList') ?>', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => response.json())
    ]).then(([settlementData, deptData]) => {
        // 정산관리부서 목록 저장
        if (settlementData.success && settlementData.data) {
            window.selectedSettlementDepts = settlementData.data.map(dept => dept.dept_name);
        } else {
            window.selectedSettlementDepts = [];
        }
        
        // 부서 목록 처리
        if (deptData.success && deptData.data && deptData.data.length > 0) {
            const deptSelect = document.getElementById('user_dept');
            const currentDept = '<?= esc($user['user_dept'] ?? '', 'js') ?>';
            
            // 기존 옵션 제거 (첫 번째 "부서를 선택하세요" 제외)
            while (deptSelect.children.length > 1) {
                deptSelect.removeChild(deptSelect.lastChild);
            }
            
            // 부서 목록 추가
            deptData.data.forEach(function(dept) {
                if (dept && dept.department_name) {
                    const deptName = dept.department_name.trim();
                    if (deptName) {
                        const option = document.createElement('option');
                        option.value = deptName;
                        option.textContent = deptName;
                        if (deptName === currentDept) {
                            option.selected = true;
                        }
                        deptSelect.appendChild(option);
                    }
                }
            });
            
            // 정산관리부서 체크박스 생성 (팝업용)
            window.deptListData = deptData.data; // 전역 변수에 저장
            loadSettlementDeptsCheckboxes(deptData.data);
            updateSettlementDeptsDisplay(); // 버튼 텍스트 업데이트
        } else {
            console.warn('부서 목록이 비어있습니다:', deptData);
        }
    }).catch(error => {
        console.error('데이터 로드 실패:', error);
    });
});

// 정산관리부서 팝업 열기
function openSettlementDeptsPopup() {
    const popup = document.getElementById('settlementDeptsPopup');
    if (!popup) return;
    
    // 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 팝업 표시
    popup.classList.remove('hidden');
    popup.classList.add('flex');
    
    // 체크박스 목록이 없으면 생성
    if (window.deptListData && window.deptListData.length > 0) {
        loadSettlementDeptsCheckboxes(window.deptListData);
    }
    
    // 선택된 개수 업데이트
    updateSettlementDeptsCount();
}

// 정산관리부서 팝업 닫기
function closeSettlementDeptsPopup() {
    const popup = document.getElementById('settlementDeptsPopup');
    if (!popup) return;
    
    popup.classList.add('hidden');
    popup.classList.remove('flex');
    
    // 사이드바 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 정산관리부서 체크박스 생성
function loadSettlementDeptsCheckboxes(deptList) {
    const container = document.getElementById('settlement-depts-list');
    if (!container) return;
    
    // 기존 체크박스 제거
    container.innerHTML = '';
    
    if (!deptList || deptList.length === 0) {
        container.innerHTML = '<div class="text-sm text-gray-400 text-center py-8">부서 목록이 없습니다.</div>';
        return;
    }
    
    // 선택된 정산관리부서 목록 (전역 변수)
    const selectedDepts = window.selectedSettlementDepts || [];
    
    // 체크박스 생성
    deptList.forEach(function(dept) {
        if (dept && dept.department_name) {
            const deptName = dept.department_name.trim();
            if (deptName) {
                const label = document.createElement('label');
                label.className = 'flex items-center space-x-2 cursor-pointer hover:bg-blue-50 rounded border border-gray-200 transition-colors';
                label.style.cssText = 'padding: 4px 8px !important; gap: 8px !important; border-radius: 4px !important;';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = deptName;
                checkbox.className = 'text-blue-600 border-gray-300 rounded focus:ring-blue-500';
                checkbox.style.cssText = 'width: 14px !important; height: 14px !important;';
                
                // 선택된 부서인지 확인
                if (selectedDepts.includes(deptName)) {
                    checkbox.checked = true;
                }
                
                // 체크박스 변경 이벤트
                checkbox.addEventListener('change', function() {
                    updateSettlementDeptsCount();
                });
                
                const span = document.createElement('span');
                span.className = 'text-gray-700 flex-1';
                span.style.cssText = 'font-size: 12px !important; color: #374151 !important;';
                span.textContent = deptName;
                
                label.appendChild(checkbox);
                label.appendChild(span);
                container.appendChild(label);
            }
        }
    });
}

// 선택된 정산관리부서 개수 업데이트
function updateSettlementDeptsCount() {
    const checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]:checked');
    const count = checkboxes.length;
    const countElement = document.getElementById('settlement-depts-count');
    if (countElement) {
        countElement.textContent = count;
    }
}

// 전체 선택
function selectAllSettlementDepts() {
    const checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = true);
    updateSettlementDeptsCount();
}

// 전체 해제
function deselectAllSettlementDepts() {
    const checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
    updateSettlementDeptsCount();
}

// 선택 확인 (팝업 닫기 및 버튼 텍스트 업데이트)
function confirmSettlementDepts() {
    const checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]:checked');
    window.selectedSettlementDepts = Array.from(checkboxes).map(cb => cb.value);
    
    updateSettlementDeptsDisplay();
    closeSettlementDeptsPopup();
}

// 정산관리부서 버튼 텍스트 업데이트
function updateSettlementDeptsDisplay() {
    const displayElement = document.getElementById('settlement-depts-display');
    const selectedDepts = window.selectedSettlementDepts || [];
    
    if (!displayElement) return;
    
    if (selectedDepts.length === 0) {
        displayElement.textContent = '정산관리부서 (복수선택가능)';
        displayElement.className = 'text-gray-500';
    } else {
        displayElement.textContent = `정산관리부서 (${selectedDepts.length}개 선택됨)`;
        displayElement.className = 'text-gray-700 font-medium';
    }
}

// 경고 모달 열기
function showWarningModal(message) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('warningMessage').textContent = message;
    document.getElementById('warningModal').classList.remove('hidden');
}

// 경고 모달 닫기
function closeWarningModal() {
    document.getElementById('warningModal').classList.add('hidden');
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 다음 주소 검색 API
function openAddressSearch() {
    new daum.Postcode({
        oncomplete: function(data) {
            let addr = '';
            let extraAddr = '';
            let detailAddr = '';

            if (data.userSelectedType === 'R') {
                addr = data.roadAddress;
            } else {
                addr = data.jibunAddress;
            }

            if (data.userSelectedType === 'R') {
                if (data.bname !== '' && /[동|로|가]$/g.test(data.bname)) {
                    extraAddr += data.bname;
                }
                if (data.buildingName !== '' && data.apartment === 'Y') {
                    extraAddr += (extraAddr !== '' ? ', ' + data.buildingName : data.buildingName);
                }
                if (extraAddr !== '') {
                    extraAddr = ' (' + extraAddr + ')';
                }
            }

            // 상세주소 필드에 건물명 등 추가 정보 설정
            if (data.buildingName && data.buildingName !== '') {
                detailAddr = data.buildingName;
            }
            
            // 법정동명이 있고 건물명이 없는 경우
            if (!detailAddr && data.bname && data.bname !== '') {
                detailAddr = data.bname;
            }

            // 주소 필드에 값 설정
            document.getElementById('address_zonecode').value = data.zonecode;
            document.getElementById('address').value = addr + extraAddr;
            
            const detailField = document.getElementById('address_detail');
            if (detailField) {
                detailField.value = detailAddr;
                // 상세주소 필드에 포커스 (사용자가 추가 입력 가능하도록)
                setTimeout(function() {
                    detailField.focus();
                }, 100);
            }
        }
    }).open();
}

// 모든 정보 저장 (담당자명, 연락처, 주소, 비밀번호)
function saveAll() {
    const realName = document.getElementById('real_name').value;
    const phone = document.getElementById('phone').value;
    const zonecode = document.getElementById('address_zonecode').value;
    const address = document.getElementById('address').value;
    const addressDetail = document.getElementById('address_detail').value;
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const newPasswordConfirm = document.getElementById('new-password-confirm').value;

    // 기본 정보 유효성 검사
    if (!realName) {
        showWarningModal('담당자명을 입력해주세요.');
        return;
    }

    // 비밀번호 변경이 입력된 경우 검증
    let passwordChange = false;
    if (currentPassword || newPassword || newPasswordConfirm) {
        passwordChange = true;
        
        if (!currentPassword) {
            showWarningModal('현재 비밀번호를 입력해주세요.');
            return;
        }

        if (!newPassword || newPassword.length < 4) {
            showWarningModal('새 비밀번호는 최소 4자 이상이어야 합니다.');
            return;
        }

        if (newPassword !== newPasswordConfirm) {
            showWarningModal('새 비밀번호가 일치하지 않습니다.');
            return;
        }
    }

    // 사용자 정보 저장 요청
    const userDept = document.getElementById('user_dept').value;
    
    // 정산관리부서 선택값 수집
    const settlementDeptCheckboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]:checked');
    const settlementDepts = Array.from(settlementDeptCheckboxes).map(cb => cb.value);
    
    const requestData = {
        real_name: realName,
        user_dept: userDept,
        settlement_depts: settlementDepts,
        phone: phone,
        address_zonecode: zonecode,
        address: address,
        address_detail: addressDetail
    };

    // 비밀번호 변경이 있는 경우에만 추가
    if (passwordChange) {
        requestData.current_password = currentPassword;
        requestData.new_password = newPassword;
        requestData.new_password_confirm = newPasswordConfirm;
    }

    fetch('<?= base_url('member/updateUserInfo') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showWarningModal(data.message || '정보가 성공적으로 저장되었습니다.');
            // 비밀번호 필드 초기화
            if (passwordChange) {
                document.getElementById('current-password').value = '';
                document.getElementById('new-password').value = '';
                document.getElementById('new-password-confirm').value = '';
            }
            // 페이지 새로고침하여 최신 정보 표시
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            // 에러 메시지 구성
            let errorMessage = data.message || '정보 저장에 실패했습니다.';
            if (data.errors) {
                const errorList = Object.values(data.errors).join('\n');
                if (errorList) {
                    errorMessage += '\n\n' + errorList;
                }
                // console.error('Validation errors:', data.errors);
            }
            showWarningModal(errorMessage);
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        showWarningModal('정보 저장 중 오류가 발생했습니다.');
    });
}

// ESC 키로 팝업 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const warningModal = document.getElementById('warningModal');
        if (warningModal && !warningModal.classList.contains('hidden')) {
            closeWarningModal();
        }
        
        const settlementDeptsPopup = document.getElementById('settlementDeptsPopup');
        if (settlementDeptsPopup && !settlementDeptsPopup.classList.contains('hidden')) {
            closeSettlementDeptsPopup();
        }
    }
});

// 팝업 외부 클릭 시 닫히지 않도록 (오버레이 클릭 방지)
document.addEventListener('click', function(e) {
    const settlementDeptsPopup = document.getElementById('settlementDeptsPopup');
    if (settlementDeptsPopup && !settlementDeptsPopup.classList.contains('hidden')) {
        // 팝업 내부 클릭은 무시
        if (e.target.closest('.bg-white.rounded-lg.shadow-2xl')) {
            return;
        }
        // 오버레이 클릭도 무시 (닫지 않음)
        // closeSettlementDeptsPopup(); // 주석 처리하여 외부 클릭 시 닫히지 않도록
    }
});
</script>
<?= $this->endSection() ?>
