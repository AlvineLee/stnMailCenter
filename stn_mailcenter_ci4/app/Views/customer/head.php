<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <div class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold text-gray-800 mb-1"><?= $content_header['title'] ?? '본점관리' ?></h2>
            <p class="text-xs text-gray-600"><?= $content_header['description'] ?? '본점 정보를 등록 및 관리할 수 있습니다.' ?></p>
        </div>
        <button onclick="openCreateModal()" class="form-button form-button-primary">
            + 본점 등록
        </button>
    </div>

    <!-- 본점 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($customers)): ?>
            <div class="text-center py-8 text-gray-500">
                등록된 본점이 없습니다.
            </div>
        <?php else: ?>
        <table class="w-full">
            <thead>
                <tr>
                    <th>본점코드(아이디)</th>
                    <th>본점명</th>
                    <th>주소</th>
                    <th>담당자</th>
                    <th>연락처</th>
                    <th>메모</th>
                    <th class="text-center">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <?php 
                // 첫 번째 사용자 정보 (담당자 정보)
                $mainUser = !empty($customer['users']) ? $customer['users'][0] : null;
                ?>
                <tr>
                    <td><?= htmlspecialchars($mainUser['username'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['customer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['address'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($mainUser['real_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($mainUser['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['memo'] ?? '-') ?></td>
                    <td class="action-buttons text-center">
                        <button onclick="editCustomer(<?= $customer['id'] ?>)" class="form-button form-button-secondary">수정</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- 본점 등록 레이어 팝업 (고객사 + 사용자 계정 함께 생성) -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">본점 등록</h3>
            <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="createCustomerForm" onsubmit="createCustomer(event)" class="p-4">
            <!-- 기본 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">기본 정보</h4>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-input" 
                               placeholder="본점코드(아이디) *" 
                               required>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="비밀번호 *" 
                               required>
                    </div>
                </div>
            </div>
            
            <!-- 본점 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">본점 정보</h4>
                
                <div class="mb-3">
                    <input type="text" 
                           id="customer_name" 
                           name="customer_name" 
                           class="form-input" 
                           placeholder="본점명">
                </div>
                
                <div class="mb-3">
                    <input type="text" 
                           id="address" 
                           name="address" 
                           class="form-input" 
                           placeholder="주소">
                </div>
                
                <div class="mb-3">
                    <textarea id="memo" 
                              name="memo" 
                              class="form-input" 
                              rows="3"
                              placeholder="메모"></textarea>
                </div>
            </div>
            
            <!-- 담당자 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">담당자 정보</h4>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="main_contact_name" 
                               name="main_contact_name" 
                               class="form-input" 
                               placeholder="담당자 이름 *" 
                               required>
                        <input type="text" 
                               id="main_contact_phone" 
                               name="main_contact_phone" 
                               class="form-input" 
                               placeholder="담당자 연락처 *" 
                               required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="sub_contact_name" 
                               name="sub_contact_name" 
                               class="form-input" 
                               placeholder="추가 담당자 이름">
                        <input type="text" 
                               id="sub_contact_phone" 
                               name="sub_contact_phone" 
                               class="form-input" 
                               placeholder="추가 담당자 연락처">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="form-button form-button-secondary">취소</button>
                <button type="submit" class="form-button form-button-primary">확인</button>
            </div>
        </form>
    </div>
</div>

<!-- 본점 수정 레이어 팝업 -->
<div id="editHeadOfficeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">본점 수정</h3>
            <button onclick="closeEditHeadOfficeModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editHeadOfficeForm" onsubmit="updateHeadOffice(event)" class="p-4">
            <input type="hidden" id="edit_customer_id">
            
            <!-- 본점 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">본점 정보</h4>
                
                <div class="mb-3">
                    <input type="text" 
                           id="edit_customer_name" 
                           name="customer_name" 
                           class="form-input" 
                           placeholder="본점명">
                </div>
                
                <div class="mb-3">
                    <input type="text" 
                           id="edit_address" 
                           name="address" 
                           class="form-input" 
                           placeholder="주소">
                </div>
                
                <div class="mb-3">
                    <textarea id="edit_memo" 
                              name="memo" 
                              class="form-input" 
                              rows="3"
                              placeholder="메모"></textarea>
                </div>
            </div>
            
            <!-- 담당자 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">담당자 정보</h4>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="edit_main_contact_name" 
                               name="main_contact_name" 
                               class="form-input" 
                               placeholder="담당자 이름 *" 
                               required>
                        <input type="text" 
                               id="edit_main_contact_phone" 
                               name="main_contact_phone" 
                               class="form-input" 
                               placeholder="담당자 연락처 *" 
                               required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="edit_sub_contact_name" 
                               name="sub_contact_name" 
                               class="form-input" 
                               placeholder="추가 담당자 이름">
                        <input type="text" 
                               id="edit_sub_contact_phone" 
                               name="sub_contact_phone" 
                               class="form-input" 
                               placeholder="추가 담당자 연락처">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeEditHeadOfficeModal()" class="form-button form-button-secondary">취소</button>
                <button type="submit" class="form-button form-button-primary">확인</button>
            </div>
        </form>
    </div>
</div>

<!-- 사용자 계정 추가 레이어 팝업 -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">
                계정 추가 - <span id="modal-customer-name"></span>
            </h3>
            <button onclick="closeAddUserModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="addUserForm" onsubmit="addUserAccount(event)" class="p-4">
            <input type="hidden" id="add_user_customer_id">
            
            <div class="mb-3">
                <label class="form-label">
                    아이디 <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="add_username" 
                       name="username" 
                       class="form-input" 
                       placeholder="아이디를 입력하세요" 
                       required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">
                    비밀번호 <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       id="add_password" 
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
                       id="add_real_name" 
                       name="real_name" 
                       class="form-input" 
                       placeholder="담당자 실명을 입력하세요" 
                       required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">이메일</label>
                <input type="email" 
                       id="add_email" 
                       name="email" 
                       class="form-input" 
                       placeholder="이메일을 입력하세요">
            </div>
            
            <div class="mb-3">
                <label class="form-label">연락처</label>
                <input type="text" 
                       id="add_phone" 
                       name="phone" 
                       class="form-input" 
                       placeholder="연락처를 입력하세요">
            </div>
            
            <div class="mb-3">
                <label class="form-label">부서</label>
                <input type="text" 
                       id="add_department" 
                       name="department" 
                       class="form-input" 
                       placeholder="부서명을 입력하세요">
            </div>
            
            <div class="mb-3">
                <label class="form-label">직위</label>
                <input type="text" 
                       id="add_position" 
                       name="position" 
                       class="form-input" 
                       placeholder="직위를 입력하세요">
            </div>
            
            <div class="mb-3">
                <label class="form-label">
                    역할 <span class="text-red-500">*</span>
                </label>
                <select id="add_user_role" 
                        name="user_role" 
                        class="form-input" 
                        required>
                    <option value="admin">관리자</option>
                    <option value="manager">매니저</option>
                    <option value="user">일반사용자</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeAddUserModal()" class="form-button form-button-secondary">취소</button>
                <button type="submit" class="form-button form-button-primary">확인</button>
            </div>
        </form>
    </div>
</div>

<!-- 사용자 계정 수정/상세 레이어 팝업 -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex-1 min-w-0">
                <span id="modal-user-title">사용자 계정 정보</span>
            </h3>
            <button onclick="closeEditUserModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editUserForm" onsubmit="updateUserAccount(event)" class="p-4">
            <input type="hidden" id="edit_user_id">
            <input type="hidden" id="edit_is_view" value="false">
            
            <div class="mb-3">
                <label class="form-label">아이디</label>
                <input type="text" 
                       id="edit_username" 
                       class="form-input" 
                       readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label">
                    담당자명 <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="edit_real_name" 
                       name="real_name" 
                       class="form-input" 
                       required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">이메일</label>
                <input type="email" 
                       id="edit_email" 
                       name="email" 
                       class="form-input">
            </div>
            
            <div class="mb-3">
                <label class="form-label">연락처</label>
                <input type="text" 
                       id="edit_phone" 
                       name="phone" 
                       class="form-input">
            </div>
            
            <div class="mb-3">
                <label class="form-label">부서</label>
                <input type="text" 
                       id="edit_department" 
                       name="department" 
                       class="form-input">
            </div>
            
            <div class="mb-3">
                <label class="form-label">직위</label>
                <input type="text" 
                       id="edit_position" 
                       name="position" 
                       class="form-input">
            </div>
            
            <div class="mb-3">
                <label class="form-label">
                    역할
                </label>
                <select id="edit_user_role" 
                        name="user_role" 
                        class="form-input">
                    <option value="admin">관리자</option>
                    <option value="manager">매니저</option>
                    <option value="user">일반사용자</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">비밀번호 변경</label>
                <input type="password" 
                       id="edit_password" 
                       name="password" 
                       class="form-input" 
                       placeholder="변경하려면 새 비밀번호를 입력하세요">
                <p class="text-xs text-gray-500 mt-1">비워두면 변경하지 않습니다.</p>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeEditUserModal()" class="form-button form-button-secondary">닫기</button>
                <button type="submit" id="edit-submit-btn" class="form-button form-button-primary hidden">저장</button>
            </div>
        </form>
    </div>
</div>

<script>
// 본점 등록 모달 열기
function openCreateModal() {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('createCustomerForm').reset();
}

// 본점 등록 모달 닫기
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createCustomerForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 본점 등록 (고객사 + 사용자 계정 함께 생성)
function createCustomer(event) {
    event.preventDefault();
    
    const formData = {
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
        customer_name: document.getElementById('customer_name').value,
        address: document.getElementById('address').value,
        memo: document.getElementById('memo').value,
        main_contact_name: document.getElementById('main_contact_name').value,
        main_contact_phone: document.getElementById('main_contact_phone').value,
        sub_contact_name: document.getElementById('sub_contact_name').value,
        sub_contact_phone: document.getElementById('sub_contact_phone').value
    };
    
    fetch('<?= base_url('customer/createHeadOffice') ?>', {
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
            alert(data.message || '본점 등록에 실패했습니다.');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('본점 등록 중 오류가 발생했습니다.');
    });
}

// 사용자 계정 추가 모달 열기
function openAddUserModal(customerId, customerName) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('add_user_customer_id').value = customerId;
    document.getElementById('modal-customer-name').textContent = customerName;
    document.getElementById('addUserModal').classList.remove('hidden');
    document.getElementById('addUserForm').reset();
}

// 사용자 계정 추가 모달 닫기
function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
    document.getElementById('addUserForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 사용자 계정 추가
function addUserAccount(event) {
    event.preventDefault();
    
    const formData = {
        customer_id: document.getElementById('add_user_customer_id').value,
        username: document.getElementById('add_username').value,
        password: document.getElementById('add_password').value,
        real_name: document.getElementById('add_real_name').value,
        email: document.getElementById('add_email').value,
        phone: document.getElementById('add_phone').value,
        department: document.getElementById('add_department').value,
        position: document.getElementById('add_position').value,
        user_role: document.getElementById('add_user_role').value
    };
    
    fetch('<?= base_url('customer/createUserAccount') ?>', {
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
            closeAddUserModal();
            location.reload();
        } else {
            alert(data.message || '사용자 계정 추가에 실패했습니다.');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('사용자 계정 추가 중 오류가 발생했습니다.');
    });
}

// 사용자 계정 수정 모달 열기
function editUserAccount(userId) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('edit_is_view').value = 'false';
    document.getElementById('modal-user-title').textContent = '사용자 계정 수정';
    document.getElementById('edit-submit-btn').classList.remove('hidden');
    
    // 수정 모드: 모든 필드 편집 가능
    document.querySelectorAll('#editUserForm input, #editUserForm select').forEach(input => {
        if (input.id !== 'edit_username') {
            input.removeAttribute('readonly');
        }
    });
    
    loadUserAccountInfo(userId);
}

// 사용자 계정 상세 보기
function viewUserAccount(userId) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('edit_is_view').value = 'true';
    document.getElementById('modal-user-title').textContent = '사용자 계정 상세';
    document.getElementById('edit-submit-btn').classList.add('hidden');
    
    // 상세 보기 모드: 모든 필드 읽기 전용
    document.querySelectorAll('#editUserForm input, #editUserForm select').forEach(input => {
        input.setAttribute('readonly', 'readonly');
    });
    
    loadUserAccountInfo(userId);
}

// 사용자 계정 정보 로드
function loadUserAccountInfo(userId) {
    fetch('<?= base_url('customer/getUserAccountInfo') ?>/' + userId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const info = data.data;
            
            document.getElementById('edit_user_id').value = info.id;
            document.getElementById('edit_username').value = info.username || '';
            document.getElementById('edit_real_name').value = info.real_name || '';
            document.getElementById('edit_email').value = info.email || '';
            document.getElementById('edit_phone').value = info.phone || '';
            document.getElementById('edit_department').value = info.department || '';
            document.getElementById('edit_position').value = info.position || '';
            document.getElementById('edit_user_role').value = info.user_role || 'user';
            
            document.getElementById('editUserModal').classList.remove('hidden');
        } else {
            alert(data.message || '사용자 정보를 불러올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('사용자 정보 조회 중 오류가 발생했습니다.');
    });
}

// 사용자 계정 수정 모달 닫기
function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
    document.getElementById('editUserForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 본점 수정 모달 열기
function editCustomer(customerId) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    loadHeadOfficeInfo(customerId);
}

// 본점 정보 로드
function loadHeadOfficeInfo(customerId) {
    fetch('<?= base_url('customer/getCustomerInfo') ?>/' + customerId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const info = data.data;
            
            document.getElementById('edit_customer_id').value = info.id;
            document.getElementById('edit_customer_name').value = info.customer_name || '';
            document.getElementById('edit_address').value = info.address || '';
            document.getElementById('edit_memo').value = info.memo || '';
            
            // 사용자 정보도 로드
            fetch('<?= base_url('customer/getUsersByCustomer') ?>/' + customerId, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(userData => {
                if (userData.success && userData.data && userData.data.length > 0) {
                    const mainUser = userData.data[0];
                    document.getElementById('edit_main_contact_name').value = mainUser.real_name || '';
                    document.getElementById('edit_main_contact_phone').value = mainUser.phone || '';
                    
                    if (userData.data.length > 1) {
                        const subUser = userData.data[1];
                        document.getElementById('edit_sub_contact_name').value = subUser.real_name || '';
                        document.getElementById('edit_sub_contact_phone').value = subUser.phone || '';
                    }
                }
                
                document.getElementById('editHeadOfficeModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error loading users:', error);
                document.getElementById('editHeadOfficeModal').classList.remove('hidden');
            });
        } else {
            alert(data.message || '본점 정보를 불러올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('본점 정보 조회 중 오류가 발생했습니다.');
    });
}

// 본점 수정 모달 닫기
function closeEditHeadOfficeModal() {
    document.getElementById('editHeadOfficeModal').classList.add('hidden');
    document.getElementById('editHeadOfficeForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 본점 수정
function updateHeadOffice(event) {
    event.preventDefault();
    
    const customerId = document.getElementById('edit_customer_id').value;
    const formData = {
        customer_name: document.getElementById('edit_customer_name').value,
        address: document.getElementById('edit_address').value,
        memo: document.getElementById('edit_memo').value,
        main_contact_name: document.getElementById('edit_main_contact_name').value,
        main_contact_phone: document.getElementById('edit_main_contact_phone').value,
        sub_contact_name: document.getElementById('edit_sub_contact_name').value,
        sub_contact_phone: document.getElementById('edit_sub_contact_phone').value
    };
    
    fetch('<?= base_url('customer/updateHeadOffice') ?>/' + customerId, {
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
            closeEditHeadOfficeModal();
            location.reload();
        } else {
            alert(data.message || '본점 정보 수정에 실패했습니다.');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('본점 정보 수정 중 오류가 발생했습니다.');
    });
}

// 사용자 계정 수정
function updateUserAccount(event) {
    event.preventDefault();
    
    const isView = document.getElementById('edit_is_view').value === 'true';
    if (isView) {
        closeEditUserModal();
        return;
    }
    
    const userId = document.getElementById('edit_user_id').value;
    const formData = {
        real_name: document.getElementById('edit_real_name').value,
        email: document.getElementById('edit_email').value,
        phone: document.getElementById('edit_phone').value,
        department: document.getElementById('edit_department').value,
        position: document.getElementById('edit_position').value,
        user_role: document.getElementById('edit_user_role').value
    };
    
    const password = document.getElementById('edit_password').value;
    if (password) {
        formData.password = password;
    }
    
    fetch('<?= base_url('customer/updateUserAccount') ?>/' + userId, {
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
            closeEditUserModal();
            location.reload();
        } else {
            alert(data.message || '사용자 정보 수정에 실패했습니다.');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('사용자 정보 수정 중 오류가 발생했습니다.');
    });
}

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
// 외부 클릭으로 인한 실수 방지를 위해 제거
</script>

<?= $this->endSection() ?>
