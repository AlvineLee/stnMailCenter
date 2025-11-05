<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <div class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold text-gray-800 mb-1"><?= $content_header['title'] ?? '그룹사 관리' ?></h2>
            <p class="text-xs text-gray-600"><?= $content_header['description'] ?? '고객사 본점 계정을 생성 및 관리할 수 있습니다.' ?></p>
        </div>
        <button onclick="openCreateModal()" class="form-button form-button-primary">
            + 그룹사 본점 계정 생성
        </button>
    </div>

    <!-- 사용자 계정 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($users)): ?>
            <div class="text-center py-8 text-gray-500">
                등록된 계정이 없습니다.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>아이디</th>
                    <th>실명</th>
                    <th>고객사명</th>
                    <th>계층레벨</th>
                    <th class="text-center">역할</th>
                    <th class="text-center">상태</th>
                    <th class="text-center">등록일</th>
                    <th class="text-center">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['real_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($user['customer_name'] ?? '-') ?></td>
                    <td>
                        <?php
                        $levelMap = [
                            'head_office' => '본점',
                            'branch' => '지사',
                            'agency' => '대리점'
                        ];
                        echo $levelMap[$user['hierarchy_level'] ?? ''] ?? '-';
                        ?>
                    </td>
                    <td class="text-center">
                        <span class="status-badge"><?= htmlspecialchars($user['user_role'] ?? '-') ?></span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge status-<?= ($user['status'] === 'active') ? 'active' : 'inactive' ?>">
                            <?= ($user['status'] === 'active') ? '활성' : '비활성' ?>
                        </span>
                    </td>
                    <td class="text-center"><?= $user['created_at'] ? date('Y-m-d', strtotime($user['created_at'])) : '-' ?></td>
                    <td class="action-buttons text-center">
                        <?php if ($user['hierarchy_level'] === 'head_office'): ?>
                            <button onclick="manageLogo(<?= $user['customer_id'] ?>, '<?= htmlspecialchars($user['customer_name']) ?>')" class="form-button form-button-secondary">
                                로고관리
                            </button>
                        <?php endif; ?>
                        <button onclick="openOrderTypeModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['real_name'], ENT_QUOTES) ?>')" class="form-button form-button-secondary">
                            오더유형 설정
                        </button>
                        <button onclick="viewAccount(<?= $user['id'] ?>)" class="form-button form-button-secondary">
                            계정정보
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- 계정 정보 조회 레이어 팝업 -->
<div id="accountInfoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex-1 min-w-0">
                계정 정보 - <span id="modal-account-name" class="whitespace-nowrap"></span>
            </h3>
            <button onclick="closeAccountInfoModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="accountInfoForm" class="p-4">
            <!-- 기본 정보 -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1">기본 정보</h4>
                
                <div class="mb-3">
                    <label class="form-label">
                        본점명 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="info-company_name" 
                           class="form-input" 
                           readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        아이디 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="info-username" 
                           class="form-input" 
                           readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        담당자명 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="info-real_name" 
                           class="form-input" 
                           readonly>
                </div>
            </div>
            
            <!-- 본점 정보 -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1">본점 정보</h4>
                
                <div class="mb-3">
                    <label class="form-label">주소</label>
                    <input type="text" 
                           id="info-address" 
                           class="form-input" 
                           readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">연락처</label>
                    <input type="text" 
                           id="info-contact_phone" 
                           class="form-input" 
                           readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">메모</label>
                    <textarea id="info-memo" 
                              rows="2" 
                              class="form-textarea" 
                              readonly></textarea>
                </div>
            </div>
            
            <!-- 비밀번호 변경 섹션 -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1">비밀번호 변경</h4>
                
                <div class="mb-3">
                    <label class="form-label">
                        새 비밀번호
                    </label>
                    <input type="password" 
                           id="info-new-password" 
                           class="form-input" 
                           placeholder="새 비밀번호를 입력하세요"
                           minlength="4">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        새 비밀번호 확인
                    </label>
                    <input type="password" 
                           id="info-new-password-confirm" 
                           class="form-input" 
                           placeholder="새 비밀번호를 다시 입력하세요"
                           minlength="4">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="changePassword()" class="form-button form-button-primary">비밀번호 변경</button>
                <button type="button" onclick="closeAccountInfoModal()" class="form-button form-button-secondary">닫기</button>
            </div>
        </form>
    </div>
</div>

<!-- 그룹사 본점 계정 생성 레이어 팝업 -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">그룹사 본점 계정 생성</h3>
            <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="createAccountForm" onsubmit="createHeadOfficeAccount(event)" class="p-4">
            <!-- 기본 정보 -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1">기본 정보</h4>
                
                <div class="mb-3">
                    <label class="form-label">
                        본점명 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="company_name" 
                           name="company_name" 
                           class="form-input" 
                           placeholder="예: CJ대한통운" 
                           required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        아이디 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-input" 
                           placeholder="예: CJ대한통운본점" 
                           required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        비밀번호 <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-input" 
                           placeholder="비밀번호를 입력하세요" 
                           required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        담당자명 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="real_name" 
                           name="real_name" 
                           class="form-input" 
                           placeholder="담당자 실명을 입력하세요" 
                           required>
                </div>
            </div>
            
            <!-- 본점 정보 -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1">본점 정보</h4>
                
                <div class="mb-3">
                    <label class="form-label">주소</label>
                    <input type="text" 
                           id="address" 
                           name="address" 
                           class="form-input" 
                           placeholder="주소를 입력하세요">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">연락처</label>
                    <input type="text" 
                           id="contact_phone" 
                           name="contact_phone" 
                           class="form-input" 
                           placeholder="연락처를 입력하세요">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">메모</label>
                    <textarea id="memo" 
                              name="memo" 
                              rows="2" 
                              class="form-textarea" 
                              placeholder="메모를 입력하세요"></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="form-button form-button-secondary">취소</button>
                <button type="submit" class="form-button form-button-primary">확인</button>
            </div>
        </form>
    </div>
</div>

<script>
// 그룹사 본점 계정 생성 모달 열기
function openCreateModal() {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('createAccountForm').reset();
}

// 그룹사 본점 계정 생성 모달 닫기
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createAccountForm').reset();
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 그룹사 본점 계정 생성
function createHeadOfficeAccount(event) {
    event.preventDefault();
    
    const formData = {
        company_name: document.getElementById('company_name').value,
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
        real_name: document.getElementById('real_name').value,
        contact_phone: document.getElementById('contact_phone').value,
        address: document.getElementById('address').value,
        memo: document.getElementById('memo').value
    };
    
    fetch('<?= base_url('group-company/createHeadOfficeAccount') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeCreateModal();
            location.reload();
        } else {
            alert(data.message || '계정 생성에 실패했습니다.');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('계정 생성 중 오류가 발생했습니다.');
    });
}

// 계정 정보 보기
function viewAccount(userId) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 계정 정보 조회
    fetch('<?= base_url('group-company/getAccountInfo') ?>/' + userId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const info = data.data;
            
            // 모달 제목 업데이트
            document.getElementById('modal-account-name').textContent = info.real_name || info.username || '-';
            
            // 입력 필드에 값 설정
            document.getElementById('info-company_name').value = info.customer_name || '';
            document.getElementById('info-username').value = info.username || '';
            document.getElementById('info-real_name').value = info.real_name || '';
            document.getElementById('info-address').value = info.customer_address || '';
            document.getElementById('info-contact_phone').value = info.customer_contact_phone || info.phone || '';
            document.getElementById('info-memo').value = ''; // 메모는 DB에 저장되지 않으므로 비움
            
            // 비밀번호 필드 초기화 및 사용자 ID 저장
            document.getElementById('info-new-password').value = '';
            document.getElementById('info-new-password-confirm').value = '';
            document.getElementById('accountInfoModal').setAttribute('data-user-id', info.id);
            
            // 모달 표시
            document.getElementById('accountInfoModal').classList.remove('hidden');
        } else {
            alert(data.message || '계정 정보를 불러올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('계정 정보 조회 중 오류가 발생했습니다.');
    });
}

// 계정 정보 모달 닫기
function closeAccountInfoModal() {
    document.getElementById('accountInfoModal').classList.add('hidden');
    
    // 비밀번호 필드 초기화
    document.getElementById('info-new-password').value = '';
    document.getElementById('info-new-password-confirm').value = '';
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 비밀번호 변경
function changePassword() {
    const userId = document.getElementById('accountInfoModal').getAttribute('data-user-id');
    const newPassword = document.getElementById('info-new-password').value;
    const newPasswordConfirm = document.getElementById('info-new-password-confirm').value;
    
    // 유효성 검사
    if (!newPassword || newPassword.length < 4) {
        alert('비밀번호는 최소 4자 이상이어야 합니다.');
        return;
    }
    
    if (newPassword !== newPasswordConfirm) {
        alert('비밀번호가 일치하지 않습니다.');
        return;
    }
    
    if (!userId) {
        alert('사용자 정보를 찾을 수 없습니다.');
        return;
    }
    
    // 비밀번호 변경 요청
    fetch('<?= base_url('group-company/changePassword') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            user_id: userId,
            new_password: newPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || '비밀번호가 성공적으로 변경되었습니다.');
            // 비밀번호 필드 초기화
            document.getElementById('info-new-password').value = '';
            document.getElementById('info-new-password-confirm').value = '';
        } else {
            alert(data.message || '비밀번호 변경에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('비밀번호 변경 중 오류가 발생했습니다.');
    });
}

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
// 외부 클릭으로 인한 실수 방지를 위해 제거

// 로고 관리 모달 열기
function manageLogo(customerId, customerName) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('logo_customer_id').value = customerId;
    document.getElementById('modal-logo-customer-name').textContent = customerName;
    
    // 기존 로고 조회 및 표시
    fetch('<?= base_url('customer/getCustomerInfo') ?>/' + customerId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const customer = data.data;
            const logoPreviewImg = document.getElementById('logo-preview-img');
            const placeholder = document.getElementById('logo-preview-placeholder');
            
            if (customer.logo_path) {
                logoPreviewImg.src = '<?= base_url() ?>/' + customer.logo_path;
                logoPreviewImg.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                logoPreviewImg.style.display = 'none';
                placeholder.style.display = 'flex';
            }
            
            // 클립보드 데이터 초기화
            const uploadArea = document.getElementById('logo-upload-area');
            if (uploadArea) {
                delete uploadArea.dataset.clipboardImage;
            }
        }
        
        document.getElementById('logoModal').classList.remove('hidden');
        
        // 모달이 열린 후 포커스를 로고 업로드 영역으로 이동 (paste 이벤트 활성화)
        setTimeout(() => {
            const uploadArea = document.getElementById('logo-upload-area');
            if (uploadArea) {
                uploadArea.focus();
            }
        }, 100);
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('logoModal').classList.remove('hidden');
        
        setTimeout(() => {
            const uploadArea = document.getElementById('logo-upload-area');
            if (uploadArea) {
                uploadArea.focus();
            }
        }, 100);
    });
}

// 로고 관리 모달 닫기
function closeLogoModal() {
    document.getElementById('logoModal').classList.add('hidden');
    document.getElementById('logoUploadForm').reset();
    document.getElementById('logo_file').value = '';
    
    // 클립보드 데이터 초기화
    const uploadArea = document.getElementById('logo-upload-area');
    if (uploadArea) {
        delete uploadArea.dataset.clipboardImage;
    }
    
    // 미리보기 초기화
    document.getElementById('logo-preview-img').style.display = 'none';
    const placeholder = document.getElementById('logo-preview-placeholder');
    if (placeholder) {
        placeholder.innerHTML = `
            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-sm text-gray-500">클릭하여 이미지 선택</p>
            <p class="text-xs text-gray-400">또는 드래그 앤 드롭</p>
            <p class="text-xs text-gray-400">또는 화면 캡처/복사 후 붙여넣기 (Ctrl+V)</p>
        `;
        placeholder.style.display = 'flex';
    }
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 로고 파일 선택
document.getElementById('logo_file')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // 파일 유효성 검사 (이미지만)
        if (!file.type.match('image.*')) {
            alert('이미지 파일만 선택 가능합니다.');
            this.value = '';
            return;
        }
        
        // 미리보기
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logo-preview-img').src = e.target.result;
            document.getElementById('logo-preview-img').style.display = 'block';
            document.getElementById('logo-preview-placeholder').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// 로고 업로드 영역 클릭 (파일 선택)
document.getElementById('logo-upload-area')?.addEventListener('click', function(e) {
    // 이미지 클릭 시 파일 선택 창 열기
    if (e.target.tagName !== 'IMG') {
        document.getElementById('logo_file').click();
    }
});

// 전체 문서에 paste 이벤트 추가 (모달이 열려있을 때만 작동)
document.addEventListener('paste', function(e) {
    const logoModal = document.getElementById('logoModal');
    if (!logoModal || logoModal.classList.contains('hidden')) {
        return; // 로고 모달이 열려있지 않으면 무시
    }
    
    const items = e.clipboardData?.items;
    if (!items) return;
    
    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        
        // 이미지 타입인지 확인
        if (item.type.indexOf('image') !== -1) {
            const blob = item.getAsFile();
            
            if (blob) {
                e.preventDefault();
                e.stopPropagation();
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // base64 데이터로 변환
                    const base64Data = e.target.result;
                    
                    // 미리보기 업데이트
                    const previewImg = document.getElementById('logo-preview-img');
                    const placeholder = document.getElementById('logo-preview-placeholder');
                    
                    if (previewImg && placeholder) {
                        previewImg.src = base64Data;
                        previewImg.style.display = 'block';
                        placeholder.style.display = 'none';
                    }
                    
                    // 클립보드 데이터 저장 (저장 버튼 클릭 시 업로드)
                    const uploadArea = document.getElementById('logo-upload-area');
                    if (uploadArea) {
                        uploadArea.dataset.clipboardImage = base64Data;
                    }
                };
                reader.readAsDataURL(blob);
                break;
            }
        }
    }
});

// 드래그 앤 드롭으로 이미지 업로드
const logoUploadArea = document.getElementById('logo-upload-area');
if (logoUploadArea) {
    // 드래그 오버 방지 (기본 동작 막기)
    logoUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('border-blue-400', 'bg-blue-50');
    });
    
    logoUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-blue-400', 'bg-blue-50');
    });
    
    // 드롭 이벤트
    logoUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            
            // 이미지 파일인지 확인
            if (!file.type.match('image.*')) {
                alert('이미지 파일만 업로드 가능합니다.');
                return;
            }
            
            // FileReader로 미리보기
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logo-preview-img').src = e.target.result;
                document.getElementById('logo-preview-img').style.display = 'block';
                document.getElementById('logo-preview-placeholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
            
            // 파일 입력 요소에 파일 설정
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            document.getElementById('logo_file').files = dataTransfer.files;
        }
    });
}

// 클립보드 이미지 업로드 (저장 버튼 클릭 시 호출)
function uploadLogoFromClipboard(base64Data) {
    const customerId = document.getElementById('logo_customer_id').value;
    
    fetch('<?= base_url('group-company/uploadLogo') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            customer_id: customerId,
            image_data: base64Data
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeLogoModal();
            location.reload();
        } else {
            alert(data.message || '로고 업로드에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('로고 업로드 중 오류가 발생했습니다.');
    });
}

// 로고 파일 업로드
function uploadLogoFile(event) {
    event.preventDefault();
    
    const customerId = document.getElementById('logo_customer_id').value;
    const fileInput = document.getElementById('logo_file');
    const uploadArea = document.getElementById('logo-upload-area');
    const clipboardImage = uploadArea?.dataset.clipboardImage;
    
    // 클립보드에서 붙여넣은 이미지가 있으면 먼저 처리
    if (clipboardImage) {
        uploadLogoFromClipboard(clipboardImage);
        return;
    }
    
    // 파일 입력이 있으면 파일 업로드
    if (fileInput.files && fileInput.files[0]) {
        const formData = new FormData();
        formData.append('logo_file', fileInput.files[0]);
        formData.append('customer_id', customerId);
        
        fetch('<?= base_url('group-company/uploadLogo') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeLogoModal();
                location.reload();
            } else {
                alert(data.message || '로고 업로드에 실패했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('로고 업로드 중 오류가 발생했습니다.');
        });
    } else {
        alert('이미지 파일을 선택하거나 붙여넣어주세요.');
    }
}

// 로고 삭제
function deleteLogo() {
    const customerId = document.getElementById('logo_customer_id').value;
    
    if (!confirm('로고를 삭제하시겠습니까?')) {
        return;
    }
    
    fetch('<?= base_url('group-company/deleteLogo') ?>/' + customerId, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeLogoModal();
            location.reload();
        } else {
            alert(data.message || '로고 삭제에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('로고 삭제 중 오류가 발생했습니다.');
    });
}

// 로고 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
// 외부 클릭으로 인한 실수 방지를 위해 제거

// ========== 오더유형 설정 관련 코드 ==========
let currentUserId = null;
let initialServiceStates = {}; // 초기 상태 저장 (변경 감지용)

// 계정별 오더유형 설정 모달 열기
function openOrderTypeModal(userId, userName) {
    currentUserId = userId;
    document.getElementById('modal-user-name').textContent = userName;
    
    // 레이어 팝업이 열릴 때 사이드바 숨기기 (모바일/작은 화면에서)
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    
    // 레이어 팝업이 열릴 때 사이드바 z-index 낮추기 (데스크톱에서도 적용)
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('orderTypeModal').classList.remove('hidden');
    document.getElementById('modal-loading').classList.remove('hidden');
    document.getElementById('modal-content').classList.add('hidden');
    
    // 사용자의 서비스 권한 조회
    fetch(`<?= base_url('group-company/getUserServicePermissions') ?>?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modal-loading').classList.add('hidden');
            
            if (data.success) {
                document.getElementById('modal-content').classList.remove('hidden');
                renderServiceTypesGrid(data.data.service_types_grouped);
                // 초기 상태 저장
                saveInitialStates();
            } else {
                alert(data.message || '데이터를 불러오는데 실패했습니다.');
                closeOrderTypeModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modal-loading').classList.add('hidden');
            alert('데이터를 불러오는 중 오류가 발생했습니다.');
            closeOrderTypeModal();
        });
}

// 초기 상태 저장 (변경 감지용)
function saveInitialStates() {
    initialServiceStates = {};
    document.querySelectorAll('.service-status-toggle-modal').forEach(toggle => {
        const serviceTypeId = toggle.dataset.serviceTypeId;
        initialServiceStates[serviceTypeId] = toggle.checked;
    });
}

// 오더유형 설정 모달 닫기
function closeOrderTypeModal() {
    document.getElementById('orderTypeModal').classList.add('hidden');
    currentUserId = null;
    document.getElementById('service-types-grid-modal').innerHTML = '';
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 서비스 타입 그리드 렌더링
function renderServiceTypesGrid(serviceTypesGrouped) {
    const container = document.getElementById('service-types-grid-modal');
    container.innerHTML = '';
    
    if (!serviceTypesGrouped || Object.keys(serviceTypesGrouped).length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">등록된 주문유형이 없습니다.</div>';
        return;
    }
    
    // 카테고리 라벨 매핑
    const categoryLabels = {
        '퀵서비스': '퀵서비스',
        'quick': '퀵서비스',
        '연계배송서비스': '연계배송서비스',
        'linked': '연계배송서비스',
        '택배서비스': '택배서비스',
        'parcel': '택배서비스',
        '우편서비스': '우편서비스',
        'postal': '우편서비스',
        '일반서비스': '일반서비스',
        'general': '일반서비스',
        '생활서비스': '생활서비스',
        'life': '생활서비스',
        '메일룸서비스': '메일룸서비스',
        'mailroom': '메일룸서비스',
        '해외특송서비스': '해외특송서비스',
        'international': '해외특송서비스'
    };
    
    // 카테고리 순서 정의
    const categoryOrder = ['메일룸서비스', '퀵서비스', '해외특송서비스', '연계배송서비스', '택배서비스', '우편서비스', '일반서비스', '생활서비스'];
    
    // 카테고리별로 정렬하여 렌더링
    const sortedCategories = [];
    categoryOrder.forEach(cat => {
        Object.keys(serviceTypesGrouped).forEach(key => {
            const label = categoryLabels[key] || key;
            if (label === cat && !sortedCategories.includes(key)) {
                sortedCategories.push(key);
            }
        });
    });
    
    // 나머지 카테고리 추가
    Object.keys(serviceTypesGrouped).forEach(key => {
        if (!sortedCategories.includes(key)) {
            sortedCategories.push(key);
        }
    });
    
    sortedCategories.forEach(category => {
        const categoryLabel = categoryLabels[category] || category;
        const services = serviceTypesGrouped[category];
        
        const categoryCard = document.createElement('div');
        categoryCard.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200';
        categoryCard.innerHTML = `
            <h3 class="text-sm font-semibold text-gray-700 mb-2">${categoryLabel}</h3>
            <div class="space-y-1.5">
                ${services.map(service => {
                    // 마스터 활성화 여부 확인
                    const isMasterActive = service.is_active === 1 || service.is_active === true || service.is_active === '1';
                    // is_enabled 값을 명시적으로 boolean으로 변환 (마스터가 활성화된 경우만 의미 있음)
                    const isEnabled = isMasterActive && (service.is_enabled === true || service.is_enabled === 1 || service.is_enabled === '1');
                    // 마스터가 비활성화되면 체크박스도 비활성화
                    const isDisabled = !isMasterActive;
                    const disabledClass = isDisabled ? 'opacity-50' : '';
                    const disabledTextClass = isDisabled ? 'text-gray-400' : '';
                    const disabledLabelClass = isDisabled ? 'cursor-not-allowed' : 'cursor-pointer';
                    const disabledToggleClass = isDisabled ? 'opacity-50 cursor-not-allowed' : '';
                    const masterDisabledText = isDisabled ? ' <span class="text-xs text-red-500">(마스터 비활성화)</span>' : '';
                    return '<div class="flex items-center justify-between py-1 ' + disabledClass + '">' +
                        '<span class="text-xs text-gray-600 ' + disabledTextClass + '">' + escapeHtml(service.service_name) + masterDisabledText + '</span>' +
                        '<label class="relative inline-flex items-center ' + disabledLabelClass + '">' +
                        '<input type="checkbox" class="sr-only peer service-status-toggle-modal" data-service-type-id="' + service.id + '"' +
                        (isEnabled ? ' checked' : '') + (isDisabled ? ' disabled' : '') + '>' +
                        '<div class="w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600 ' + disabledToggleClass + '"></div>' +
                        '</label>' +
                        '</div>';
                }).join('')}
            </div>
        `;
        
        container.appendChild(categoryCard);
    });
}

// 사용자 서비스 설정 저장
function saveUserServiceSettings() {
    if (!currentUserId) {
        alert('사용자 ID가 없습니다.');
        return;
    }
    
    // 변경된 토글만 수집 (초기 상태와 비교)
    const statusUpdates = [];
    document.querySelectorAll('.service-status-toggle-modal').forEach(toggle => {
        const serviceTypeId = toggle.dataset.serviceTypeId;
        const currentState = toggle.checked;
        const initialState = initialServiceStates[serviceTypeId] !== undefined ? initialServiceStates[serviceTypeId] : false;
        
        // 상태가 변경된 경우만 추가
        if (currentState !== initialState) {
            statusUpdates.push({
                service_type_id: parseInt(serviceTypeId),
                is_enabled: currentState ? 1 : 0
            });
        }
    });
    
    if (statusUpdates.length === 0) {
        alert('변경된 설정이 없습니다.');
        return;
    }
    
    const formData = new FormData();
    formData.append('user_id', currentUserId);
    formData.append('status_updates', JSON.stringify(statusUpdates));
    
    fetch('<?= base_url('group-company/updateUserServicePermissions') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // 초기 상태 업데이트
            saveInitialStates();
        } else {
            alert(data.message || '설정 저장에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('설정 저장 중 오류가 발생했습니다.');
    });
}

// 사용자 전체 서비스 활성화 (UI만 변경)
function activateAllUserServices() {
    if (!currentUserId) {
        alert('사용자 ID가 없습니다.');
        return;
    }
    
    // 모든 토글을 활성화 (UI만 변경)
    document.querySelectorAll('.service-status-toggle-modal').forEach(toggle => {
        toggle.checked = true;
    });
}

// 사용자 전체 서비스 비활성화 (UI만 변경)
function deactivateAllUserServices() {
    if (!currentUserId) {
        alert('사용자 ID가 없습니다.');
        return;
    }
    
    // 모든 토글을 비활성화 (UI만 변경)
    document.querySelectorAll('.service-status-toggle-modal').forEach(toggle => {
        toggle.checked = false;
    });
}

// HTML 이스케이프 함수
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<!-- 계정별 오더유형 설정 레이어 팝업 -->
<div id="orderTypeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-7xl max-h-[85vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex-1 min-w-0">
                오더유형 설정 - <span id="modal-user-name" class="whitespace-nowrap"></span>
            </h3>
            <button onclick="closeOrderTypeModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="p-4">
            <!-- 로딩 표시 -->
            <div id="modal-loading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">데이터를 불러오는 중...</p>
            </div>
            
            <!-- 서비스 타입 그리드 (동적으로 로드) -->
            <div id="modal-content" class="hidden">
                <!-- 주문유형 그리드 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4" id="service-types-grid-modal">
                    <!-- 동적으로 생성됨 -->
                </div>
                
                <!-- 액션 버튼들 -->
                <div class="form-actions">
                    <button onclick="activateAllUserServices()" class="form-button form-button-secondary">
                        전체 활성화
                    </button>
                    <button onclick="deactivateAllUserServices()" class="form-button form-button-secondary">
                        전체 비활성화
                    </button>
                    <button onclick="saveUserServiceSettings()" class="form-button form-button-primary">
                        설정 저장
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 로고 관리 레이어 팝업 -->
<div id="logoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">
                로고 관리 - <span id="modal-logo-customer-name"></span>
            </h3>
            <button onclick="closeLogoModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="logoUploadForm" onsubmit="uploadLogoFile(event)" class="p-4">
            <input type="hidden" id="logo_customer_id">
            
            <!-- 로고 미리보기 -->
            <div class="mb-4">
                <label class="form-label">로고 미리보기</label>
                <div id="logo-upload-area" 
                     class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-gray-400 transition-colors" 
                     style="min-height: 200px; display: flex; align-items: center; justify-content: center;"
                     tabindex="0">
                    <div id="logo-preview-placeholder" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-500">클릭하여 이미지 선택</p>
                        <p class="text-xs text-gray-400">또는 드래그 앤 드롭</p>
                        <p class="text-xs text-gray-400">또는 화면 캡처/복사 후 붙여넣기 (Ctrl+V)</p>
                        <p class="text-xs text-red-500 mt-2">💡 팁: 모달이 열린 상태에서 아무 곳이나 클릭한 후 Ctrl+V를 누르세요</p>
                    </div>
                    <img id="logo-preview-img" src="" alt="로고 미리보기" style="max-width: 100%; max-height: 300px; display: none; border-radius: 8px; object-fit: contain;">
                </div>
                <input type="file" id="logo_file" name="logo_file" accept="image/*" class="hidden">
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="deleteLogo()" class="form-button form-button-secondary">삭제</button>
                <button type="button" onclick="closeLogoModal()" class="form-button form-button-secondary">닫기</button>
                <button type="submit" class="form-button form-button-primary">저장</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

