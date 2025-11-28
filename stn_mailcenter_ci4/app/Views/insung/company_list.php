<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('insung/company-list', ['method' => 'GET']) ?>
        <div class="search-filter-container">
            <div class="search-filter-item">
                <label class="search-filter-label">콜센터</label>
                <select name="cc_code" class="search-filter-select">
                    <option value="all" <?= ($cc_code_filter ?? 'all') === 'all' ? 'selected' : '' ?>>전체</option>
                    <?php if (!empty($cc_list)): ?>
                        <?php foreach ($cc_list as $cc): ?>
                            <option value="<?= esc($cc['cc_code']) ?>" <?= ($cc_code_filter ?? 'all') === $cc['cc_code'] ? 'selected' : '' ?>>
                                <?= esc($cc['cc_name'] ?? $cc['cc_code']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">고객사명</label>
                <input type="text" 
                       name="search_name" 
                       value="<?= esc($search_name ?? '') ?>" 
                       placeholder="고객사명 입력"
                       class="search-filter-select">
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button">🔍 검색</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 검색 결과 정보 -->
    <div class="mb-4 px-2 md:px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="text-sm text-gray-700">
                <?php if (isset($pagination) && $pagination): ?>
                    총 <?= number_format($pagination['total_count']) ?>건 중 
                    <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>건 표시
                <?php else: ?>
                    검색 결과가 없습니다.
                <?php endif; ?>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button onclick="openCompanyLogoModal()" 
                        id="bulkLogoBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm disabled:bg-gray-400 disabled:cursor-not-allowed flex-1 sm:flex-none"
                        disabled>
                    <span class="hidden sm:inline">선택한 고객사 로고 업로드</span>
                    <span class="sm:hidden">로고 업로드</span>
                </button>
            </div>
        </div>
    </div>

    <div class="list-table-container">
        <?php if (empty($company_list)): ?>
            <div class="text-center py-8 text-gray-500">
                고객사 데이터가 없습니다.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase border-b">
                            <input type="checkbox" 
                                   id="selectAll" 
                                   onchange="toggleSelectAll(this)"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">로고</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터 코드</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터명</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">고객사 코드</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">고객사명</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">대표자</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">사업자번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">연락처</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">주소</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">상태</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($company_list as $company): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-center">
                            <input type="checkbox" 
                                   class="company-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                   value="<?= esc($company['comp_idx'] ?? $company['comp_code']) ?>"
                                   data-comp-code="<?= esc($company['comp_code']) ?>"
                                   onchange="updateBulkLogoButton()">
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <?php if (!empty($company['logo_path'])): ?>
                                <a href="javascript:void(0)" 
                                   onclick="showLogoPreview('<?= base_url($company['logo_path']) ?>', '<?= esc($company['comp_name']) ?>')" 
                                   class="text-green-600 hover:text-green-800 hover:underline cursor-pointer">
                                    로고 있음
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400">로고 없음</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['cc_code'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['cc_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['comp_code'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['comp_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['comp_owner'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['business_number'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['contact_phone'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['address'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm">
                            <span class="px-2 py-1 text-xs rounded <?= ($company['is_active'] ?? 0) == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ($company['is_active'] ?? 0) == 1 ? '활성' : '비활성' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if (isset($pagination) && $pagination && $pagination['total_pages'] > 1): ?>
    <?php
    // 공통 페이징 라이브러리 사용
    $paginationHelper = new \App\Libraries\PaginationHelper(
        $pagination['total_count'],
        $pagination['per_page'],
        $pagination['current_page'],
        base_url('insung/company-list'),
        array_filter([
            'cc_code' => ($cc_code_filter ?? 'all') !== 'all' ? $cc_code_filter : null,
            'search_name' => !empty($search_name) ? $search_name : null
        ], function($value) {
            return $value !== null && $value !== '';
        })
    );
    echo $paginationHelper->renderWithCurrentStyle();
    ?>
    <?php endif; ?>
</div>

<!-- 로고 미리보기 모달 -->
<div id="logoPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800" id="logoPreviewTitle">로고 미리보기</h3>
            <button onclick="closeLogoPreviewModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6 text-center">
            <img id="logoPreviewImage" src="" alt="로고 미리보기" class="max-w-full max-h-96 mx-auto">
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
            <button onclick="closeLogoPreviewModal()" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 text-sm">
                닫기
            </button>
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
            <p id="warningMessage" class="text-gray-700"></p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
            <button onclick="closeWarningModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                확인
            </button>
        </div>
    </div>
</div>

<!-- 고객사 로고 일괄 업로드 모달 -->
<div id="companyLogoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md" style="z-index: 10000 !important;">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">
                고객사 로고 일괄 업로드
            </h3>
            <button onclick="closeCompanyLogoModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="companyLogoUploadForm" onsubmit="uploadCompanyLogos(event)" class="p-6">
            <!-- 선택된 고객사 코드 저장 (hidden) -->
            <input type="hidden" id="selected-comp-codes" value="">
            
            <!-- 선택된 고객사 목록 표시 -->
            <div id="selected-companies-list" class="mb-4 max-h-32 overflow-y-auto border border-gray-200 rounded p-2 text-sm text-gray-600">
                선택된 고객사가 없습니다.
            </div>
            
            <!-- 로고 업로드 영역 -->
            <div id="company-logo-upload-area" class="mb-4 border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors" tabindex="0">
                <img id="company-logo-preview-img" src="" alt="로고 미리보기" class="max-w-full max-h-48 mx-auto mb-2 hidden">
                <div id="company-logo-preview-placeholder" class="flex flex-col items-center justify-center">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-sm text-gray-500 mt-2">클릭하여 이미지 선택</p>
                    <p class="text-xs text-gray-400">또는 드래그 앤 드롭</p>
                    <p class="text-xs text-gray-400">또는 화면 캡처/복사 후 붙여넣기 (Ctrl+V)</p>
                </div>
            </div>
            
            <input type="file" id="company_logo_file" name="logo_file" accept="image/*" class="hidden">
            
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCompanyLogoModal()" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 text-sm">
                    취소
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                    업로드
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// 전체 선택/해제
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.company-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkLogoButton();
}

// 일괄 로고 업로드 버튼 활성화/비활성화
function updateBulkLogoButton() {
    const checkboxes = document.querySelectorAll('.company-checkbox:checked');
    const bulkLogoBtn = document.getElementById('bulkLogoBtn');
    if (checkboxes.length > 0) {
        bulkLogoBtn.disabled = false;
    } else {
        bulkLogoBtn.disabled = true;
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

// 고객사 로고 모달 열기
function openCompanyLogoModal() {
    const checkboxes = document.querySelectorAll('.company-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningModal('고객사를 선택해주세요.');
        return;
    }
    
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 선택된 고객사 목록 표시 및 idx 저장
    const selectedCompanies = Array.from(checkboxes).map(cb => {
        const row = cb.closest('tr');
        const compName = row.querySelector('td:nth-child(6)').textContent.trim(); // 체크박스(1), 로고(2), 콜센터코드(3), 콜센터명(4), 고객사코드(5), 고객사명(6)
        const compIdx = cb.value;
        const compCode = cb.dataset.compCode || compIdx; // comp_code가 없으면 comp_idx 사용
        return { name: compName, idx: compIdx, code: compCode };
    });
    
    // 선택된 고객사 idx를 hidden input에 저장
    const compIdxs = selectedCompanies.map(c => c.idx);
    document.getElementById('selected-comp-codes').value = JSON.stringify(compIdxs);
    
    const listElement = document.getElementById('selected-companies-list');
    if (selectedCompanies.length > 0) {
        listElement.innerHTML = selectedCompanies.map(c => `<div>${c.name} (${c.code})</div>`).join('');
    } else {
        listElement.innerHTML = '선택된 고객사가 없습니다.';
    }
    
    // 미리보기 초기화
    document.getElementById('company-logo-preview-img').style.display = 'none';
    document.getElementById('company-logo-preview-placeholder').style.display = 'flex';
    document.getElementById('company_logo_file').value = '';
    
    // 클립보드 데이터 초기화
    const uploadArea = document.getElementById('company-logo-upload-area');
    if (uploadArea) {
        delete uploadArea.dataset.clipboardImage;
    }
    
    document.getElementById('companyLogoModal').classList.remove('hidden');
    
    // 모달이 열린 후 포커스를 로고 업로드 영역으로 이동
    setTimeout(() => {
        const uploadArea = document.getElementById('company-logo-upload-area');
        if (uploadArea) {
            uploadArea.focus();
        }
    }, 100);
}

// 고객사 로고 모달 닫기
function closeCompanyLogoModal() {
    document.getElementById('companyLogoModal').classList.add('hidden');
    document.getElementById('companyLogoUploadForm').reset();
    document.getElementById('company_logo_file').value = '';
    
    // 클립보드 데이터 초기화
    const uploadArea = document.getElementById('company-logo-upload-area');
    if (uploadArea) {
        delete uploadArea.dataset.clipboardImage;
    }
    
    // 미리보기 초기화
    document.getElementById('company-logo-preview-img').style.display = 'none';
    const placeholder = document.getElementById('company-logo-preview-placeholder');
    if (placeholder) {
        placeholder.style.display = 'flex';
    }
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 로고 파일 선택
document.getElementById('company_logo_file')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (!file.type.match('image.*')) {
            alert('이미지 파일만 선택 가능합니다.');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('company-logo-preview-img').src = e.target.result;
            document.getElementById('company-logo-preview-img').style.display = 'block';
            document.getElementById('company-logo-preview-placeholder').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// 로고 업로드 영역 클릭
document.getElementById('company-logo-upload-area')?.addEventListener('click', function(e) {
    if (e.target.tagName !== 'IMG') {
        document.getElementById('company_logo_file').click();
    }
});

// 클립보드 붙여넣기
document.addEventListener('paste', function(e) {
    const logoModal = document.getElementById('companyLogoModal');
    if (!logoModal || logoModal.classList.contains('hidden')) {
        return;
    }
    
    const items = e.clipboardData?.items;
    if (!items) return;
    
    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        if (item.type.indexOf('image') !== -1) {
            const blob = item.getAsFile();
            if (blob) {
                e.preventDefault();
                e.stopPropagation();
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64Data = e.target.result;
                    const previewImg = document.getElementById('company-logo-preview-img');
                    const placeholder = document.getElementById('company-logo-preview-placeholder');
                    
                    if (previewImg && placeholder) {
                        previewImg.src = base64Data;
                        previewImg.style.display = 'block';
                        placeholder.style.display = 'none';
                    }
                    
                    const uploadArea = document.getElementById('company-logo-upload-area');
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

// 드래그 앤 드롭
const companyLogoUploadArea = document.getElementById('company-logo-upload-area');
if (companyLogoUploadArea) {
    companyLogoUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('border-blue-400', 'bg-blue-50');
    });
    
    companyLogoUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-blue-400', 'bg-blue-50');
    });
    
    companyLogoUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (!file.type.match('image.*')) {
                alert('이미지 파일만 업로드 가능합니다.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('company-logo-preview-img').src = e.target.result;
                document.getElementById('company-logo-preview-img').style.display = 'block';
                document.getElementById('company-logo-preview-placeholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            document.getElementById('company_logo_file').files = dataTransfer.files;
        }
    });
}

// 고객사 로고 일괄 업로드
function uploadCompanyLogos(event) {
    event.preventDefault();
    
    // 모달이 열릴 때 저장된 고객사 코드 사용
    const selectedCompCodesValue = document.getElementById('selected-comp-codes').value;
    if (!selectedCompCodesValue) {
        showWarningModal('고객사를 선택해주세요.');
        return;
    }
    
    const compCodes = JSON.parse(selectedCompCodesValue);
    if (!compCodes || compCodes.length === 0) {
        showWarningModal('고객사를 선택해주세요.');
        return;
    }
    const fileInput = document.getElementById('company_logo_file');
    const uploadArea = document.getElementById('company-logo-upload-area');
    const clipboardImage = uploadArea?.dataset.clipboardImage;
    
    if (clipboardImage) {
        // 클립보드 이미지 업로드
        fetch('<?= base_url('insung/uploadCompanyLogos') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                comp_codes: compCodes,
                image_data: clipboardImage
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                closeCompanyLogoModal();
                showWarningModal(data.message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                closeCompanyLogoModal();
                showWarningModal(data.message || '로고 업로드에 실패했습니다.');
            }
        })
        .catch(error => {
            // console.error('Error:', error);
            closeCompanyLogoModal();
            showWarningModal('로고 업로드 중 오류가 발생했습니다. 콘솔을 확인해주세요.');
        });
        return;
    }
    
    if (fileInput.files && fileInput.files[0]) {
        const formData = new FormData();
        formData.append('logo_file', fileInput.files[0]);
        compCodes.forEach(code => {
            formData.append('comp_codes[]', code);
        });
        
        fetch('<?= base_url('insung/uploadCompanyLogos') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                closeCompanyLogoModal();
                showWarningModal(data.message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                closeCompanyLogoModal();
                showWarningModal(data.message || '로고 업로드에 실패했습니다.');
            }
        })
        .catch(error => {
            // console.error('Error:', error);
            closeCompanyLogoModal();
            showWarningModal('로고 업로드 중 오류가 발생했습니다. 콘솔을 확인해주세요.');
        });
    } else {
        showWarningModal('이미지 파일을 선택하거나 붙여넣어주세요.');
    }
}

// 로고 미리보기 모달 열기
function showLogoPreview(logoPath, companyName) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('logoPreviewTitle').textContent = companyName + ' - 로고 미리보기';
    document.getElementById('logoPreviewImage').src = logoPath;
    document.getElementById('logoPreviewModal').classList.remove('hidden');
}

// 로고 미리보기 모달 닫기
function closeLogoPreviewModal() {
    document.getElementById('logoPreviewModal').classList.add('hidden');
    
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// ESC 키로 팝업 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const logoPreviewModal = document.getElementById('logoPreviewModal');
        if (logoPreviewModal && !logoPreviewModal.classList.contains('hidden')) {
            closeLogoPreviewModal();
        }
        const companyModal = document.getElementById('companyLogoModal');
        if (companyModal && !companyModal.classList.contains('hidden')) {
            closeCompanyLogoModal();
        }
        const warningModal = document.getElementById('warningModal');
        if (warningModal && !warningModal.classList.contains('hidden')) {
            closeWarningModal();
        }
    }
});
</script>
<?= $this->endSection() ?>

