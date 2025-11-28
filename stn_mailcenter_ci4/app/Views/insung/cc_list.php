<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 검색 결과 정보 -->
    <div class="mb-4 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                <?php if (!empty($cc_list)): ?>
                    총 <?= number_format(count($cc_list)) ?>건 표시
                <?php else: ?>
                    검색 결과가 없습니다.
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="list-table-container">
        <?php if (empty($cc_list)): ?>
            <div class="text-center py-8 text-gray-500">
                콜센터 데이터가 없습니다.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터 코드</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터명</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">소속 고객사</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">연락처</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">이메일</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">주소</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($cc_list as $cc): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm"><?= esc($cc['cc_code'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($cc['cc_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm text-center">
                            <?php if (($cc['company_count'] ?? 0) > 0): ?>
                                <button onclick="showCompanyList('<?= esc($cc['cc_code']) ?>', '<?= esc($cc['cc_name'] ?? $cc['cc_code']) ?>')" 
                                        class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded hover:bg-blue-200 cursor-pointer">
                                    <?= number_format($cc['company_count'] ?? 0) ?>개
                                </button>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-600 rounded">
                                    <?= number_format($cc['company_count'] ?? 0) ?>개
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 text-sm"><?= esc($cc['contact_phone'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($cc['contact_email'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($cc['address'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 고객사 목록 레이어 팝업 -->
<div id="companyListModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[85vh] overflow-y-auto" style="z-index: 10000 !important;" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex-1 min-w-0">
                소속 고객사 목록 - <span id="modal-cc-name" class="whitespace-nowrap"></span>
            </h3>
            <button onclick="closeCompanyListModal()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="p-6">
            <!-- 로딩 표시 -->
            <div id="companyListLoading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-sm text-gray-600">로딩 중...</p>
            </div>
            
            <!-- 고객사 목록 -->
            <div id="companyListContent" class="hidden">
                <div class="overflow-x-auto">
                    <table style="background: #fafafa; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; width: 100%;">
                        <thead>
                            <tr>
                                <th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px;">고객사 코드</th>
                                <th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px;">고객사명</th>
                                <th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px;">대표자</th>
                                <th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px;">연락처</th>
                            </tr>
                        </thead>
                        <tbody id="companyListBody">
                            <!-- AJAX로 로드될 내용 -->
                        </tbody>
                    </table>
                </div>
                
                <div id="companyListEmpty" class="hidden text-center py-8 text-gray-500">
                    소속 고객사가 없습니다.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showCompanyList(ccCode, ccName) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('modal-cc-name').textContent = ccName;
    document.getElementById('companyListModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('companyListLoading').classList.remove('hidden');
    document.getElementById('companyListContent').classList.add('hidden');
    document.getElementById('companyListBody').innerHTML = '';
    document.getElementById('companyListEmpty').classList.add('hidden');
    
    // AJAX로 고객사 목록 조회
    fetch(`<?= base_url('insung/getCompaniesByCc') ?>?cc_code=${encodeURIComponent(ccCode)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('companyListLoading').classList.add('hidden');
            
            if (data.success && data.companies && data.companies.length > 0) {
                document.getElementById('companyListContent').classList.remove('hidden');
                
                let html = '';
                data.companies.forEach((company, index) => {
                    // style_guide.md 기준: 짝수/홀수 행 배경색 적용
                    const rowBg = index % 2 === 0 ? '#fafafa' : '#f5f5f5';
                    html += `
                        <tr style="background: ${rowBg};" onmouseover="this.style.background='#f9fafb';" onmouseout="this.style.background='${rowBg}';">
                            <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">${company.comp_code || '-'}</td>
                            <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">${company.comp_name || '-'}</td>
                            <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">${company.comp_owner || '-'}</td>
                            <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">${company.contact_phone || '-'}</td>
                        </tr>
                    `;
                });
                document.getElementById('companyListBody').innerHTML = html;
            } else {
                document.getElementById('companyListContent').classList.remove('hidden');
                document.getElementById('companyListEmpty').classList.remove('hidden');
            }
        })
        .catch(error => {
            // console.error('Error:', error);
            document.getElementById('companyListLoading').classList.add('hidden');
            alert('고객사 목록을 불러오는 중 오류가 발생했습니다.');
        });
}

function closeCompanyListModal() {
    document.getElementById('companyListModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 모달 외부 클릭 시 닫기
document.getElementById('companyListModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCompanyListModal();
    }
});

// ESC 키로 팝업 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('companyListModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeCompanyListModal();
        }
    }
});
</script>
<?= $this->endSection() ?>

