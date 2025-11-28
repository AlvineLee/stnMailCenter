<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <div class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold text-gray-800 mb-1"><?= $content_header['title'] ?? '운송사 관리' ?></h2>
            <p class="text-xs text-gray-600"><?= $content_header['description'] ?? '해외특송 및 택배서비스를 제공하는 운송사를 관리합니다.' ?></p>
        </div>
        <button onclick="openCreateModal()" class="form-button form-button-primary">
            + 운송사 등록
        </button>
    </div>

    <!-- 운송사 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($companies)): ?>
            <div class="text-center py-8 text-gray-500">
                등록된 운송사가 없습니다.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>운송사</th>
                    <th>플랫폼사용코드</th>
                    <th class="text-center">사용여부</th>
                    <th class="text-center">이용기간</th>
                    <th class="text-center">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?= htmlspecialchars($company['company_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($company['platform_code'] ?? '-') ?></td>
                    <td class="text-center">
                        <span class="status-badge status-<?= ($company['is_active'] == 1) ? 'active' : 'inactive' ?>">
                            <?= ($company['is_active'] == 1) ? 'O' : 'X' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php
                        $startDate = !empty($company['contract_start_date']) ? date('Y-m-d', strtotime($company['contract_start_date'])) : '-';
                        $endDate = !empty($company['contract_end_date']) ? date('Y-m-d', strtotime($company['contract_end_date'])) : '-';
                        
                        if ($startDate !== '-' && $endDate !== '-') {
                            echo $startDate . ' ~ ' . $endDate;
                        } elseif ($startDate !== '-') {
                            echo $startDate . ' ~';
                        } elseif ($endDate !== '-') {
                            echo '~ ' . $endDate;
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="action-buttons text-center">
                        <button onclick="editCompany(<?= $company['id'] ?>)" class="form-button form-button-secondary">
                            수정
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- 운송사 등록/수정 레이어 팝업 -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex-1 min-w-0" id="modal-title">
                운송사 등록
            </h3>
            <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="companyForm" class="p-6">
            <input type="hidden" id="company_id" name="company_id">
            
            <div class="space-y-4">
                <!-- 운송사명 -->
                <div>
                    <label for="company_name" class="form-label">운송사명 <span class="text-red-500">*</span></label>
                    <input type="text" id="company_name" name="company_name" required
                           class="form-input" placeholder="예: 일양, DHL">
                </div>
                
                <!-- 플랫폼사용코드 -->
                <div>
                    <label for="platform_code" class="form-label">플랫폼사용코드 <span class="text-red-500">*</span></label>
                    <input type="text" id="platform_code" name="platform_code" required
                           class="form-input" placeholder="예: ILY, DHL" style="text-transform: uppercase;">
                </div>
                
                <!-- 사용여부 -->
                <div>
                    <label class="form-label">사용여부 <span class="text-red-500">*</span></label>
                    <div class="flex space-x-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="is_active" value="1" checked class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">예</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="is_active" value="0" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">아니오</span>
                        </label>
                    </div>
                </div>
                
                <!-- 이용기간 -->
                <div>
                    <label class="form-label">이용기간</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="contract_start_date" class="block text-xs text-gray-600 mb-1">시작일</label>
                            <input type="date" id="contract_start_date" name="contract_start_date"
                                   class="form-input">
                        </div>
                        <div>
                            <label for="contract_end_date" class="block text-xs text-gray-600 mb-1">종료일</label>
                            <input type="date" id="contract_end_date" name="contract_end_date"
                                   class="form-input">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions mt-6">
                <button type="button" onclick="closeCreateModal()" class="form-button form-button-secondary">
                    취소
                </button>
                <button type="submit" class="form-button form-button-primary">
                    <span id="submit-button-text">등록</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentCompanyId = null;

function openCreateModal() {
    currentCompanyId = null;
    document.getElementById('modal-title').textContent = '운송사 등록';
    document.getElementById('submit-button-text').textContent = '등록';
    document.getElementById('companyForm').reset();
    document.getElementById('company_id').value = '';
    document.getElementById('createModal').classList.remove('hidden');
    window.hideSidebarForModal();
    window.lowerSidebarZIndex();
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    window.restoreSidebarZIndex();
}

function editCompany(id) {
    currentCompanyId = id;
    document.getElementById('modal-title').textContent = '운송사 수정';
    document.getElementById('submit-button-text').textContent = '수정';
    
    fetch(`/shipping-company/get/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const company = data.data;
                document.getElementById('company_id').value = company.id;
                document.getElementById('company_name').value = company.company_name || '';
                document.getElementById('platform_code').value = company.platform_code || '';
                
                // 사용여부 라디오 버튼
                const isActive = company.is_active == 1 ? '1' : '0';
                document.querySelector(`input[name="is_active"][value="${isActive}"]`).checked = true;
                
                // 이용기간
                if (company.contract_start_date) {
                    const startDate = new Date(company.contract_start_date);
                    document.getElementById('contract_start_date').value = startDate.toISOString().split('T')[0];
                }
                if (company.contract_end_date) {
                    const endDate = new Date(company.contract_end_date);
                    document.getElementById('contract_end_date').value = endDate.toISOString().split('T')[0];
                }
                
                document.getElementById('createModal').classList.remove('hidden');
                window.hideSidebarForModal();
                window.lowerSidebarZIndex();
            }
        })
        .catch(error => {
            // console.error('Error:', error);
            alert('운송사 정보를 불러오는데 실패했습니다.');
        });
}

// 플랫폼사용코드를 대문자로 변환
document.getElementById('platform_code')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

// 폼 제출 처리
document.getElementById('companyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // company_code는 company_name과 동일하게 설정 (또는 별도 입력 필드 필요시 추가)
    if (!data.company_code) {
        data.company_code = data.company_name.toLowerCase().replace(/\s+/g, '-');
    }
    
    const url = currentCompanyId 
        ? `/shipping-company/update/${currentCompanyId}`
        : '/shipping-company/create';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || '운송사가 저장되었습니다.');
            closeCreateModal();
            location.reload();
        } else {
            alert(data.message || '운송사 저장에 실패했습니다.');
            if (data.errors) {
                // console.error('Validation errors:', data.errors);
            }
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('운송사 저장 중 오류가 발생했습니다.');
    });
});
</script>
<?= $this->endSection() ?>
