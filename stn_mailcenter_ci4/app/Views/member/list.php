<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <!-- 사용자 정보 섹션 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- 왼쪽 컬럼 -->
        <div class="space-y-4">
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
                <label class="form-label">주소</label>
                <div class="flex space-x-2 mb-2">
                    <input type="text" 
                           id="address_zonecode" 
                           value="<?= esc($user['address_zonecode'] ?? '') ?>" 
                           placeholder="우편번호"
                           class="form-input w-24">
                    <button type="button" 
                            onclick="openAddressSearch()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1 text-xs font-medium rounded transition-colors whitespace-nowrap">
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

        <!-- 오른쪽 컬럼 -->
        <div class="space-y-4">
            <div class="form-field">
                <label class="form-label">상호명</label>
                <input type="text" 
                       value="<?= esc($user['customer_name'] ?? '') ?>" 
                       readonly 
                       class="form-input bg-gray-50 text-gray-600">
            </div>
        </div>
    </div>

    <!-- 담당자 정보 (한 줄 배치) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
            </div>

    <!-- 비밀번호 변경 섹션 -->
    <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">비밀번호 변경</h3>
        <div class="space-y-4 max-w-md">
            <div class="form-field">
                <label class="form-label required">현재 비밀번호</label>
                <input type="password" 
                       id="current-password" 
                       class="form-input">
            </div>
            <div class="form-field">
                <label class="form-label required">새 비밀번호</label>
                <input type="password" 
                       id="new-password" 
                       class="form-input">
            </div>
            <div class="form-field">
                <label class="form-label required">새 비밀번호 확인</label>
                <input type="password" 
                       id="new-password-confirm" 
                       class="form-input">
            </div>
        </div>
    </div>

    <!-- 저장 버튼 -->
    <div class="mt-6 flex justify-center">
        <button onclick="saveAll()" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-8 py-3 rounded-md transition-colors">
            저장
        </button>
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

<!-- 다음 주소 검색 API 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>

<script>
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
    const requestData = {
        real_name: realName,
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
                console.error('Validation errors:', data.errors);
            }
            showWarningModal(errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
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
    }
});
</script>
<?= $this->endSection() ?>
