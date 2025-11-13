<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <div class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold text-gray-800 mb-1"><?= $content_header['title'] ?? '지사관리' ?></h2>
            <p class="text-xs text-gray-600"><?= $content_header['description'] ?? '지사 정보를 관리할 수 있습니다.' ?></p>
        </div>
        <button onclick="openCreateModal()" class="form-button form-button-primary">
            + 지사 등록
        </button>
    </div>

    <!-- 지사 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($customers)): ?>
            <div class="text-center py-8 text-gray-500">
                등록된 지사가 없습니다.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>지사명</th>
                    <th>상위 본점</th>
                    <th>고객사 코드</th>
                    <th>주소</th>
                    <th>연락처</th>
                    <th>대표자</th>
                    <th class="text-center">상태</th>
                    <th class="text-center">등록일</th>
                    <th class="text-center">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?= htmlspecialchars($customer['customer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['parent_customer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['customer_code'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['address'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['contact_phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['representative_name'] ?? '-') ?></td>
                    <td class="text-center">
                        <span class="status-badge status-<?= ($customer['is_active'] == 1) ? 'active' : 'inactive' ?>">
                            <?= ($customer['is_active'] == 1) ? '활성' : '비활성' ?>
                        </span>
                    </td>
                    <td class="text-center"><?= $customer['created_at'] ? date('Y-m-d', strtotime($customer['created_at'])) : '-' ?></td>
                    <td class="action-buttons text-center">
                        <button onclick="editCustomer(<?= $customer['id'] ?>)" class="form-button form-button-secondary">수정</button>
                        <button onclick="viewCustomer(<?= $customer['id'] ?>)" class="form-button form-button-secondary">상세</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- 지사 등록 레이어 팝업 -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">지사 등록</h3>
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
                               placeholder="아이디 *" 
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
            
            <!-- 지사 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">지사 정보</h4>
                
                <div class="mb-3">
                    <input type="text" 
                           id="customer_name" 
                           name="customer_name" 
                           class="form-input" 
                           placeholder="지사명 *" 
                           required>
                </div>
            </div>
            
            <!-- 권한 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">권한 정보</h4>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <select id="user_role" 
                                name="user_role" 
                                class="form-input" 
                                required>
                            <option value="">권한 선택 *</option>
                            <option value="admin">관리자</option>
                            <option value="manager">매니저</option>
                            <option value="user">일반사용자</option>
                        </select>
                        <input type="text" 
                               id="parent_customer_name" 
                               name="parent_customer_name" 
                               class="form-input" 
                               placeholder="소속본점"
                               readonly>
                        <input type="hidden" id="parent_id" name="parent_id">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">소속본점은 상위본점명이 자동으로 설정됩니다.</p>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="form-button form-button-secondary">취소</button>
                <button type="submit" class="form-button form-button-primary">확인</button>
            </div>
        </form>
    </div>
</div>

<!-- 지사 수정/상세 레이어 팝업 -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex-1 min-w-0">
                <span id="modal-title">지사 정보</span>
            </h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editCustomerForm" onsubmit="updateCustomer(event)" class="p-4">
            <input type="hidden" id="edit_customer_id">
            <input type="hidden" id="edit_is_view" value="false">
            
            <!-- 기본 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">기본 정보</h4>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="edit_username" 
                               name="username" 
                               class="form-input" 
                               placeholder="아이디 *" 
                               readonly>
                        <input type="password" 
                               id="edit_password" 
                               name="password" 
                               class="form-input" 
                               placeholder="비밀번호">
                    </div>
                </div>
            </div>
            
            <!-- 지사 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">지사 정보</h4>
                
                <div class="mb-3">
                    <input type="text" 
                           id="edit_customer_name" 
                           name="customer_name" 
                           class="form-input" 
                           placeholder="지사명 *" 
                           required>
                </div>
            </div>
            
            <!-- 권한 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">권한 정보</h4>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <select id="edit_user_role" 
                                name="user_role" 
                                class="form-input" 
                                required>
                            <option value="">권한 선택 *</option>
                            <option value="admin">관리자</option>
                            <option value="manager">매니저</option>
                            <option value="user">일반사용자</option>
                        </select>
                        <input type="text" 
                               id="edit_parent_customer_name" 
                               name="parent_customer_name" 
                               class="form-input" 
                               placeholder="소속본점"
                               readonly>
                        <input type="hidden" id="edit_parent_id" name="parent_id">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">소속본점은 상위본점명이 자동으로 설정됩니다.</p>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeEditModal()" class="form-button form-button-secondary">닫기</button>
                <button type="submit" id="edit-submit-btn" class="form-button form-button-primary hidden">저장</button>
            </div>
        </form>
    </div>
</div>

<script>
// 지사 등록 모달 열기
function openCreateModal() {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 상위 본점 목록이 있으면 첫 번째를 기본값으로 설정
    const parentCustomers = <?= json_encode($parent_customers ?? []) ?>;
    if (parentCustomers.length > 0) {
        document.getElementById('parent_id').value = parentCustomers[0].id;
        document.getElementById('parent_customer_name').value = parentCustomers[0].customer_name;
    }
    
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('createCustomerForm').reset();
    
    // 리셋 후 다시 기본값 설정
    if (parentCustomers.length > 0) {
        document.getElementById('parent_id').value = parentCustomers[0].id;
        document.getElementById('parent_customer_name').value = parentCustomers[0].customer_name;
    }
}

// 지사 등록 모달 닫기
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createCustomerForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 지사 수정 모달 열기
function editCustomer(customerId) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('edit_is_view').value = 'false';
    document.getElementById('modal-title').textContent = '지사 수정';
    document.getElementById('edit-submit-btn').classList.remove('hidden');
    
    // 수정 모드: 일부 필드만 편집 가능
    document.querySelectorAll('#editCustomerForm input, #editCustomerForm select').forEach(input => {
        // 아이디와 소속본점은 readonly 유지
        if (input.id !== 'edit_username' && input.id !== 'edit_parent_customer_name') {
            input.removeAttribute('readonly');
        }
    });
    
    loadCustomerInfo(customerId);
}

// 지사 상세 보기
function viewCustomer(customerId) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('edit_is_view').value = 'true';
    document.getElementById('modal-title').textContent = '지사 상세';
    document.getElementById('edit-submit-btn').classList.add('hidden');
    
    // 상세 보기 모드: 모든 필드 읽기 전용
    document.querySelectorAll('#editCustomerForm input, #editCustomerForm select').forEach(input => {
        input.setAttribute('readonly', 'readonly');
    });
    
    loadCustomerInfo(customerId);
}

// 고객사 정보 로드
function loadCustomerInfo(customerId) {
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
            document.getElementById('edit_parent_id').value = info.parent_id || '';
            document.getElementById('edit_customer_name').value = info.customer_name || '';
            document.getElementById('edit_parent_customer_name').value = info.parent_customer_name || '';
            
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
                    const user = userData.data[0];
                    document.getElementById('edit_username').value = user.username || '';
                    document.getElementById('edit_user_role').value = user.user_role || '';
                }
                
                document.getElementById('editModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error loading users:', error);
                document.getElementById('editModal').classList.remove('hidden');
            });
        } else {
            alert(data.message || '고객사 정보를 불러올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('고객사 정보 조회 중 오류가 발생했습니다.');
    });
}

// 지사 수정 모달 닫기
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editCustomerForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 지사 등록
function createCustomer(event) {
    event.preventDefault();
    
    const formData = {
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
        customer_name: document.getElementById('customer_name').value,
        parent_id: document.getElementById('parent_id').value,
        user_role: document.getElementById('user_role').value
    };
    
    fetch('<?= base_url('customer/createBranch') ?>', {
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
            alert(data.message || '지사 등록에 실패했습니다.');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('지사 등록 중 오류가 발생했습니다.');
    });
}

// 지사 수정
function updateCustomer(event) {
    event.preventDefault();
    
    const isView = document.getElementById('edit_is_view').value === 'true';
    if (isView) {
        closeEditModal();
        return;
    }
    
    const customerId = document.getElementById('edit_customer_id').value;
    const formData = {
        customer_name: document.getElementById('edit_customer_name').value,
        user_role: document.getElementById('edit_user_role').value
    };
    
    // 비밀번호가 입력된 경우에만 포함
    const password = document.getElementById('edit_password').value;
    if (password) {
        formData.password = password;
    }
    
    fetch('<?= base_url('customer/updateBranch') ?>/' + customerId, {
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
            closeEditModal();
            location.reload();
        } else {
            alert(data.message || '지사 수정에 실패했습니다.');
            if (data.errors) {
                console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('지사 수정 중 오류가 발생했습니다.');
    });
}

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
// 외부 클릭으로 인한 실수 방지를 위해 제거
</script>

<?= $this->endSection() ?>
