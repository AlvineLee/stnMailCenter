<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <div class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold text-gray-800 mb-1"><?= $content_header['title'] ?? '대리점관리' ?></h2>
            <p class="text-xs text-gray-600"><?= $content_header['description'] ?? '대리점 정보를 관리할 수 있습니다.' ?></p>
        </div>
        <button onclick="openCreateModal()" class="form-button form-button-primary">
            + 대리점 등록
        </button>
    </div>

    <!-- 대리점 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($customers)): ?>
            <div class="text-center py-8 text-gray-500">
                등록된 대리점이 없습니다.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>대리점명</th>
                    <th>상위 고객사</th>
                    <th>고객사 코드</th>
                    <th>대표자</th>
                    <th>연락처</th>
                    <th>계약일</th>
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
                    <td><?= htmlspecialchars($customer['representative_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($customer['contact_phone'] ?? '-') ?></td>
                    <td><?= $customer['contract_start_date'] ? date('Y-m-d', strtotime($customer['contract_start_date'])) : '-' ?></td>
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

<!-- 대리점 등록 레이어 팝업 -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">대리점 등록</h3>
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
                    <div class="grid grid-cols-3 gap-2">
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
                        <input type="password" 
                               id="password_confirm" 
                               name="password_confirm" 
                               class="form-input" 
                               placeholder="비밀번호 확인 *" 
                               required>
                    </div>
                </div>
            </div>
            
            <!-- 업체 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">업체 정보</h4>
                
                <div class="mb-3">
                    <input type="text" 
                           id="customer_name" 
                           name="customer_name" 
                           class="form-input" 
                           placeholder="업체명 *" 
                           required>
                </div>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="representative_name" 
                               name="representative_name" 
                               class="form-input" 
                               placeholder="담당자명 *" 
                               required>
                        <input type="text" 
                               id="contact_phone" 
                               name="contact_phone" 
                               class="form-input" 
                               placeholder="연락처 *" 
                               required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="department" 
                               name="department" 
                               class="form-input" 
                               placeholder="부서">
                        <input type="text" 
                               id="position" 
                               name="position" 
                               class="form-input" 
                               placeholder="담당">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="mb-2">
                        <button type="button" 
                                onclick="searchAddress()" 
                                class="form-button form-button-secondary">
                            주소검색
                        </button>
                    </div>
                    <input type="text" 
                           id="address" 
                           name="address" 
                           class="form-input" 
                           placeholder="주소 *" 
                           required>
                </div>
                
                <div class="mb-3">
                    <input type="text" 
                           id="address_detail" 
                           name="address_detail" 
                           class="form-input" 
                           placeholder="상세주소 *" 
                           required>
                </div>
            </div>
            
            <!-- 대리점 정보 -->
            <div class="mb-2 bg-gray-50 border border-gray-200 rounded-lg px-4 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">대리점 정보</h4>
                
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" 
                               id="head_office_name" 
                               name="head_office_name" 
                               class="form-input" 
                               placeholder="본점명"
                               readonly>
                        <select id="parent_id" 
                                name="parent_id" 
                                class="form-input" 
                                required>
                            <option value="">지사 선택 *</option>
                            <?php 
                            $headOffices = [];
                            $branches = [];
                            foreach ($parent_customers ?? [] as $parent): 
                                if ($parent['hierarchy_level'] === 'head_office') {
                                    $headOffices[] = $parent;
                                } else {
                                    $branches[] = $parent;
                                }
                            endforeach; 
                            ?>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>" data-head-office-id="<?= $branch['parent_id'] ?? '' ?>"><?= htmlspecialchars($branch['customer_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" id="head_office_id" name="head_office_id">
                </div>
                
                <div class="mb-3">
                    <textarea id="memo" 
                              name="memo" 
                              class="form-input" 
                              rows="3"
                              placeholder="메모"></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeCreateModal()" class="form-button form-button-secondary">취소</button>
                <button type="submit" class="form-button form-button-primary">확인</button>
            </div>
        </form>
    </div>
</div>

<!-- 대리점 수정/상세 레이어 팝업 -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex-1 min-w-0">
                <span id="modal-title">대리점 정보</span>
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
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1">기본 정보</h4>
                
                <div class="mb-3">
                    <label class="form-label">
                        상위 고객사
                    </label>
                    <select id="edit_parent_id" 
                            name="parent_id" 
                            class="form-input">
                        <option value="">선택하세요</option>
                        <?php foreach ($parent_customers ?? [] as $parent): ?>
                        <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['customer_name']) ?> (<?= $parent['hierarchy_level'] === 'head_office' ? '본점' : '지사' ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        대리점명 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="edit_customer_name" 
                           name="customer_name" 
                           class="form-input" 
                           placeholder="예: 강남대리점" 
                           required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">고객사 코드</label>
                    <input type="text" 
                           id="edit_customer_code" 
                           class="form-input" 
                           readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">사업자번호</label>
                    <input type="text" 
                           id="edit_business_number" 
                           name="business_number" 
                           class="form-input" 
                           placeholder="사업자번호를 입력하세요">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">대표자명</label>
                    <input type="text" 
                           id="edit_representative_name" 
                           name="representative_name" 
                           class="form-input" 
                           placeholder="대표자명을 입력하세요">
                </div>
            </div>
            
            <!-- 대리점 정보 -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2 border-b pb-1">대리점 정보</h4>
                
                <div class="mb-3">
                    <label class="form-label">주소</label>
                    <input type="text" 
                           id="edit_address" 
                           name="address" 
                           class="form-input" 
                           placeholder="주소를 입력하세요">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">연락처</label>
                    <input type="text" 
                           id="edit_contact_phone" 
                           name="contact_phone" 
                           class="form-input" 
                           placeholder="연락처를 입력하세요">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">이메일</label>
                    <input type="email" 
                           id="edit_contact_email" 
                           name="contact_email" 
                           class="form-input" 
                           placeholder="이메일을 입력하세요">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">계약 시작일</label>
                    <input type="date" 
                           id="edit_contract_start_date" 
                           name="contract_start_date" 
                           class="form-input">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">계약 종료일</label>
                    <input type="date" 
                           id="edit_contract_end_date" 
                           name="contract_end_date" 
                           class="form-input">
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
// 주소 검색 (다음 주소 API 연동 예정)
function searchAddress() {
    // TODO: 다음 주소 API 연동
    alert('주소 검색 기능은 준비 중입니다.');
}

// 지사 선택 시 본점명 자동 설정
document.addEventListener('DOMContentLoaded', function() {
    const parentSelect = document.getElementById('parent_id');
    if (parentSelect) {
        parentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const headOfficeId = selectedOption.getAttribute('data-head-office-id');
            // 본점명은 서버에서 조회하거나 별도 처리 필요
            // 임시로 지사명 표시
        });
    }
});

// 대리점 등록 모달 열기
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

// 대리점 등록 모달 닫기
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createCustomerForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 대리점 수정 모달 열기
function editCustomer(customerId) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('edit_is_view').value = 'false';
    document.getElementById('modal-title').textContent = '대리점 수정';
    document.getElementById('edit-submit-btn').classList.remove('hidden');
    
    // 수정 모드: 모든 필드 편집 가능
    document.querySelectorAll('#editCustomerForm input, #editCustomerForm select').forEach(input => {
        if (input.id !== 'edit_customer_code') {
            input.removeAttribute('readonly');
        }
    });
    
    loadCustomerInfo(customerId);
}

// 대리점 상세 보기
function viewCustomer(customerId) {
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('edit_is_view').value = 'true';
    document.getElementById('modal-title').textContent = '대리점 상세';
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
            document.getElementById('edit_customer_code').value = info.customer_code || '';
            document.getElementById('edit_business_number').value = info.business_number || '';
            document.getElementById('edit_representative_name').value = info.representative_name || '';
            document.getElementById('edit_address').value = info.address || '';
            document.getElementById('edit_contact_phone').value = info.contact_phone || '';
            document.getElementById('edit_contact_email').value = info.contact_email || '';
            document.getElementById('edit_contract_start_date').value = info.contract_start_date || '';
            document.getElementById('edit_contract_end_date').value = info.contract_end_date || '';
            
            document.getElementById('editModal').classList.remove('hidden');
        } else {
            alert(data.message || '고객사 정보를 불러올 수 없습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('고객사 정보 조회 중 오류가 발생했습니다.');
    });
}

// 대리점 수정 모달 닫기
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editCustomerForm').reset();
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 대리점 등록
function createCustomer(event) {
    event.preventDefault();
    
    // 비밀번호 확인 검증
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    if (password !== passwordConfirm) {
        alert('비밀번호가 일치하지 않습니다.');
        return;
    }
    
    const formData = {
        username: document.getElementById('username').value,
        password: password,
        customer_name: document.getElementById('customer_name').value,
        representative_name: document.getElementById('representative_name').value,
        contact_phone: document.getElementById('contact_phone').value,
        department: document.getElementById('department').value,
        position: document.getElementById('position').value,
        address: document.getElementById('address').value,
        address_detail: document.getElementById('address_detail').value,
        parent_id: document.getElementById('parent_id').value,
        memo: document.getElementById('memo').value
    };
    
    fetch('<?= base_url('customer/createAgency') ?>', {
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
            alert(data.message || '대리점 등록에 실패했습니다.');
            if (data.errors) {
                // console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('대리점 등록 중 오류가 발생했습니다.');
    });
}

// 대리점 수정
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
        business_number: document.getElementById('edit_business_number').value,
        representative_name: document.getElementById('edit_representative_name').value,
        contact_phone: document.getElementById('edit_contact_phone').value,
        contact_email: document.getElementById('edit_contact_email').value,
        address: document.getElementById('edit_address').value,
        contract_start_date: document.getElementById('edit_contract_start_date').value,
        contract_end_date: document.getElementById('edit_contract_end_date').value
    };
    
    fetch('<?= base_url('customer/updateCustomer') ?>/' + customerId, {
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
            alert(data.message || '대리점 수정에 실패했습니다.');
            if (data.errors) {
                // console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('대리점 수정 중 오류가 발생했습니다.');
    });
}

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
// 외부 클릭으로 인한 실수 방지를 위해 제거
</script>

<?= $this->endSection() ?>
