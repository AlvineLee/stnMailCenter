<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 검색 영역 -->
    <div class="search-compact">
        <?= form_open('insung/company-list', ['method' => 'GET']) ?>
        <div class="search-filter-container">
            <div class="search-filter-item">
                <select name="cc_code" class="search-filter-input">
                    <option value="all" <?= ($cc_code_filter ?? 'all') === 'all' ? 'selected' : '' ?>>콜센터 전체</option>
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
                <input type="text" name="search_name" value="<?= esc($search_name ?? '') ?>" class="search-filter-input" placeholder="거래처명">
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button">검색</button>
                <a href="<?= base_url('insung/company-list') ?>" class="search-button" style="background: #6b7280 !important;">초기화</a>
                <button type="button" onclick="openCompanyLogoModal()" id="bulkLogoBtn" class="search-button" style="background: #059669 !important;" disabled>로고 업로드</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 결과 건수 -->
    <div class="mb-2 text-xs text-gray-600">
        <?php if (isset($pagination_info) && $pagination_info): ?>
            총 <span class="font-semibold"><?= number_format($pagination_info['total_count']) ?></span>건
        <?php else: ?>
            검색 결과가 없습니다.
        <?php endif; ?>
    </div>

    <!-- 테이블 -->
    <div class="list-table-container">
        <table class="list-table-compact">
            <thead>
                <tr>
                    <th style="width:30px;">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    </th>
                    <th style="width:50px;">로고</th>
                    <th style="width:70px;">콜센터코드</th>
                    <th style="width:90px;">콜센터명</th>
                    <th style="width:70px;">거래처코드</th>
                    <th style="width:110px;">거래처명</th>
                    <th style="width:50px;">대표자</th>
                    <th style="width:90px;">사업자번호</th>
                    <th style="width:90px;">연락처</th>
                    <th>주소</th>
                    <th style="width:45px;">상태</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($company_list)): ?>
                    <tr>
                        <td colspan="11" class="text-center">조회된 데이터가 없습니다.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($company_list as $company): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox"
                                       class="company-checkbox"
                                       value="<?= esc($company['comp_idx'] ?? $company['comp_code']) ?>"
                                       data-comp-code="<?= esc($company['comp_code']) ?>"
                                       onchange="updateBulkLogoButton()">
                            </td>
                            <td>
                                <?php if (!empty($company['logo_path'])): ?>
                                    <a href="javascript:void(0)"
                                       onclick="showLogoPreview('<?= base_url($company['logo_path']) ?>', '<?= esc($company['comp_name']) ?>')"
                                       class="text-green-600 hover:underline">있음</a>
                                <?php else: ?>
                                    <span class="text-gray-400">없음</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($company['cc_code'] ?? '-') ?></td>
                            <td><?= esc($company['cc_name'] ?? '-') ?></td>
                            <td><?= esc($company['comp_code'] ?? '-') ?></td>
                            <td><?= esc($company['comp_name'] ?? '-') ?></td>
                            <td><?= esc($company['comp_owner'] ?? '-') ?></td>
                            <td><?= esc($company['business_number'] ?? '-') ?></td>
                            <td><?= esc($company['contact_phone'] ?? '-') ?></td>
                            <td><?= esc($company['address'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="inline-block px-1 py-0.5 text-xs rounded <?= esc($company['status_class'] ?? 'bg-gray-100 text-gray-800') ?>">
                                    <?= esc($company['status_label'] ?? '비활성') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <?php if (isset($pagination_helper) && $pagination_helper && ($pagination['total_pages'] ?? 1) > 1): ?>
        <?= $pagination_helper->renderWithCurrentStyle() ?>
    <?php endif; ?>

</div>

<!-- 로고 미리보기 모달 -->
<div id="logoPreviewModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 9999 !important; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl" style="z-index: 10000 !important;">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900" id="logoPreviewTitle">로고 미리보기</h3>
            <button type="button" onclick="closeLogoPreviewModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6 text-center">
            <img id="logoPreviewImage" src="" alt="로고 미리보기" class="max-w-full max-h-96 mx-auto">
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="closeLogoPreviewModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">닫기</button>
        </div>
    </div>
</div>

<!-- 경고 메시지 모달 -->
<div id="warningModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 9999 !important; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-sm" style="z-index: 10000 !important;">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">알림</h3>
            <button type="button" onclick="closeWarningModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="px-6 py-4">
            <p id="warningMessage" class="text-gray-700"></p>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="closeWarningModal()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">확인</button>
        </div>
    </div>
</div>

<!-- 거래처 로고 일괄 업로드 모달 -->
<div id="companyLogoModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 9999 !important; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md" style="z-index: 10000 !important;">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">거래처 로고 일괄 업로드</h3>
            <button type="button" onclick="closeCompanyLogoModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="companyLogoUploadForm" onsubmit="uploadCompanyLogos(event)" class="px-6 py-4">
            <input type="hidden" id="selected-comp-codes" value="">

            <div id="selected-companies-list" class="mb-4 max-h-24 overflow-y-auto border border-gray-200 rounded p-2 text-xs text-gray-600">
                선택된 거래처가 없습니다.
            </div>

            <div id="company-logo-upload-area" class="mb-4 border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors" tabindex="0">
                <img id="company-logo-preview-img" src="" alt="로고 미리보기" class="max-w-full max-h-32 mx-auto mb-2 hidden">
                <div id="company-logo-preview-placeholder" class="flex flex-col items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-xs text-gray-500 mt-2">클릭 또는 드래그앤드롭 또는 Ctrl+V</p>
                </div>
            </div>

            <input type="file" id="company_logo_file" name="logo_file" accept="image/*" class="hidden">
        </form>

        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="closeCompanyLogoModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">취소</button>
            <button type="button" onclick="document.getElementById('companyLogoUploadForm').dispatchEvent(new Event('submit'))" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">업로드</button>
        </div>
    </div>
</div>

<script>
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.company-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateBulkLogoButton();
}

function updateBulkLogoButton() {
    const checked = document.querySelectorAll('.company-checkbox:checked').length;
    document.getElementById('bulkLogoBtn').disabled = checked === 0;
}

function showWarningModal(message) {
    if (typeof window.hideSidebarForModal === 'function') window.hideSidebarForModal();
    if (typeof window.lowerSidebarZIndex === 'function') window.lowerSidebarZIndex();
    document.getElementById('warningMessage').textContent = message;
    document.getElementById('warningModal').classList.remove('hidden');
    document.getElementById('warningModal').classList.add('flex');
}

function closeWarningModal() {
    document.getElementById('warningModal').classList.add('hidden');
    document.getElementById('warningModal').classList.remove('flex');
    if (typeof window.restoreSidebarZIndex === 'function') window.restoreSidebarZIndex();
}

function openCompanyLogoModal() {
    const checkboxes = document.querySelectorAll('.company-checkbox:checked');
    if (checkboxes.length === 0) { showWarningModal('거래처를 선택해주세요.'); return; }

    if (typeof window.hideSidebarForModal === 'function') window.hideSidebarForModal();
    if (typeof window.lowerSidebarZIndex === 'function') window.lowerSidebarZIndex();

    const selected = Array.from(checkboxes).map(cb => {
        const row = cb.closest('tr');
        return { name: row.querySelector('td:nth-child(6)').textContent.trim(), idx: cb.value, code: cb.dataset.compCode || cb.value };
    });

    document.getElementById('selected-comp-codes').value = JSON.stringify(selected.map(c => c.idx));
    document.getElementById('selected-companies-list').innerHTML = selected.map(c => `<div>${c.name} (${c.code})</div>`).join('');

    document.getElementById('company-logo-preview-img').style.display = 'none';
    document.getElementById('company-logo-preview-placeholder').style.display = 'flex';
    document.getElementById('company_logo_file').value = '';
    delete document.getElementById('company-logo-upload-area').dataset.clipboardImage;

    document.getElementById('companyLogoModal').classList.remove('hidden');
    document.getElementById('companyLogoModal').classList.add('flex');
    setTimeout(() => document.getElementById('company-logo-upload-area').focus(), 100);
}

function closeCompanyLogoModal() {
    document.getElementById('companyLogoModal').classList.add('hidden');
    document.getElementById('companyLogoModal').classList.remove('flex');
    document.getElementById('companyLogoUploadForm').reset();
    document.getElementById('company-logo-preview-img').style.display = 'none';
    document.getElementById('company-logo-preview-placeholder').style.display = 'flex';
    delete document.getElementById('company-logo-upload-area').dataset.clipboardImage;
    if (typeof window.restoreSidebarZIndex === 'function') window.restoreSidebarZIndex();
}

document.getElementById('company_logo_file')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.match('image.*')) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('company-logo-preview-img').src = e.target.result;
            document.getElementById('company-logo-preview-img').style.display = 'block';
            document.getElementById('company-logo-preview-placeholder').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('company-logo-upload-area')?.addEventListener('click', e => {
    if (e.target.tagName !== 'IMG') document.getElementById('company_logo_file').click();
});

document.addEventListener('paste', function(e) {
    if (document.getElementById('companyLogoModal').classList.contains('hidden')) return;
    const items = e.clipboardData?.items;
    if (!items) return;
    for (let item of items) {
        if (item.type.indexOf('image') !== -1) {
            const blob = item.getAsFile();
            if (blob) {
                e.preventDefault();
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('company-logo-preview-img').src = e.target.result;
                    document.getElementById('company-logo-preview-img').style.display = 'block';
                    document.getElementById('company-logo-preview-placeholder').style.display = 'none';
                    document.getElementById('company-logo-upload-area').dataset.clipboardImage = e.target.result;
                };
                reader.readAsDataURL(blob);
                break;
            }
        }
    }
});

const uploadArea = document.getElementById('company-logo-upload-area');
if (uploadArea) {
    uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('border-blue-400', 'bg-blue-50'); });
    uploadArea.addEventListener('dragleave', e => { e.preventDefault(); uploadArea.classList.remove('border-blue-400', 'bg-blue-50'); });
    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-400', 'bg-blue-50');
        const file = e.dataTransfer.files[0];
        if (file?.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('company-logo-preview-img').src = e.target.result;
                document.getElementById('company-logo-preview-img').style.display = 'block';
                document.getElementById('company-logo-preview-placeholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('company_logo_file').files = dt.files;
        }
    });
}

function uploadCompanyLogos(event) {
    event.preventDefault();
    const compCodes = JSON.parse(document.getElementById('selected-comp-codes').value || '[]');
    if (!compCodes.length) { showWarningModal('거래처를 선택해주세요.'); return; }

    const clipboardImage = document.getElementById('company-logo-upload-area').dataset.clipboardImage;
    const fileInput = document.getElementById('company_logo_file');

    if (clipboardImage) {
        fetch('<?= base_url('insung/uploadCompanyLogos') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ comp_codes: compCodes, image_data: clipboardImage })
        })
        .then(r => r.json())
        .then(data => {
            closeCompanyLogoModal();
            showWarningModal(data.message || (data.success ? '업로드 완료' : '업로드 실패'));
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(() => { closeCompanyLogoModal(); showWarningModal('업로드 중 오류가 발생했습니다.'); });
    } else if (fileInput.files[0]) {
        const formData = new FormData();
        formData.append('logo_file', fileInput.files[0]);
        compCodes.forEach(code => formData.append('comp_codes[]', code));
        fetch('<?= base_url('insung/uploadCompanyLogos') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            closeCompanyLogoModal();
            showWarningModal(data.message || (data.success ? '업로드 완료' : '업로드 실패'));
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(() => { closeCompanyLogoModal(); showWarningModal('업로드 중 오류가 발생했습니다.'); });
    } else {
        showWarningModal('이미지를 선택해주세요.');
    }
}

function showLogoPreview(logoPath, companyName) {
    if (typeof window.hideSidebarForModal === 'function') window.hideSidebarForModal();
    if (typeof window.lowerSidebarZIndex === 'function') window.lowerSidebarZIndex();
    document.getElementById('logoPreviewTitle').textContent = companyName + ' - 로고';
    document.getElementById('logoPreviewImage').src = logoPath;
    document.getElementById('logoPreviewModal').classList.remove('hidden');
    document.getElementById('logoPreviewModal').classList.add('flex');
}

function closeLogoPreviewModal() {
    document.getElementById('logoPreviewModal').classList.add('hidden');
    document.getElementById('logoPreviewModal').classList.remove('flex');
    if (typeof window.restoreSidebarZIndex === 'function') window.restoreSidebarZIndex();
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (!document.getElementById('logoPreviewModal').classList.contains('hidden')) closeLogoPreviewModal();
        if (!document.getElementById('companyLogoModal').classList.contains('hidden')) closeCompanyLogoModal();
        if (!document.getElementById('warningModal').classList.contains('hidden')) closeWarningModal();
    }
});
</script>
<?= $this->endSection() ?>