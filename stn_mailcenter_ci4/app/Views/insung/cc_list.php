<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 검색 영역 -->
    <div class="search-compact">
        <form method="get" id="search-form">
            <div class="search-filter-container">
                <div class="search-filter-item">
                    <input type="text" name="cc_code" value="<?= esc($filters['cc_code'] ?? '') ?>" class="search-filter-input" placeholder="회사코드">
                </div>
                <div class="search-filter-item">
                    <input type="text" name="cc_name" value="<?= esc($filters['cc_name'] ?? '') ?>" class="search-filter-input" placeholder="회사명">
                </div>
                <div class="search-filter-item">
                    <input type="text" name="api_cccode" value="<?= esc($filters['api_cccode'] ?? '') ?>" class="search-filter-input" placeholder="API콜센터코드">
                </div>
                <div class="search-filter-button-wrapper">
                    <button type="submit" class="search-button">검색</button>
                    <a href="<?= base_url('insung/cc-list') ?>" class="search-button" style="background: #6b7280 !important;">초기화</a>
                    <button type="button" onclick="openCcModal()" class="search-button bg-green-100 text-green-800 border-green-200">+ 콜센터 추가</button>
                </div>
            </div>
        </form>
    </div>

    <!-- 결과 건수 -->
    <div class="mb-2 text-xs text-gray-600">
        총 <span class="font-semibold"><?= number_format($total_count ?? 0) ?></span>건
    </div>

    <!-- 테이블 -->
    <div class="list-table-container">
        <table class="list-table-compact">
            <thead>
                <tr>
                    <th style="width:40px;">번호</th>
                    <th style="width:70px;">회사코드</th>
                    <th style="width:120px;">회사명</th>
                    <th style="width:180px;">퀵API연계콜센타</th>
                    <th style="width:80px;">퀵아이디</th>
                    <th style="width:90px;">연락처</th>
                    <th style="width:70px;">기준동명</th>
                    <th>메모</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cc_list)): ?>
                    <tr>
                        <td colspan="8" class="text-center">조회된 데이터가 없습니다.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $startNum = (($pagination['current_page'] ?? 1) - 1) * ($pagination['per_page'] ?? 20) + 1;
                    $rowNum = $startNum;
                    ?>
                    <?php foreach ($cc_list as $cc): ?>
                        <tr class="cursor-pointer" onclick="editCc(<?= esc($cc['idx']) ?>)">
                            <td class="text-center"><?= $rowNum++ ?></td>
                            <td><?= esc($cc['cc_code'] ?? '-') ?></td>
                            <td><?= esc($cc['cc_name'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($cc['api_cccode'])): ?>
                                    [<?= esc($cc['api_cccode']) ?>] <?= esc($cc['api_name'] ?? '') ?> (<?= esc($cc['api_code'] ?? '') ?>)
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= esc($cc['cc_quickid'] ?? '-') ?></td>
                            <td><?= esc($cc['cc_telno'] ?? '-') ?></td>
                            <td><?= esc($cc['cc_dongname'] ?? '-') ?></td>
                            <td><?= esc($cc['cc_memo'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 페이징 -->
    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="list-pagination">
            <div class="pagination">
                <?php
                $baseUrl = base_url('insung/cc-list');
                $queryParams = array_filter($filters);
                $queryString = http_build_query($queryParams);
                $currentPage = $pagination['current_page'];
                $totalPages = $pagination['total_pages'];
                ?>

                <!-- 처음 -->
                <?php if ($currentPage > 1): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=1" class="nav-button">처음</a>
                <?php else: ?>
                    <span class="nav-button disabled">처음</span>
                <?php endif; ?>

                <!-- 이전 -->
                <?php if ($currentPage > 1): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $currentPage - 1 ?>" class="nav-button">이전</a>
                <?php else: ?>
                    <span class="nav-button disabled">이전</span>
                <?php endif; ?>

                <!-- 페이지 번호 -->
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i === $currentPage): ?>
                        <span class="page-number active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $i ?>" class="page-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- 다음 -->
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $currentPage + 1 ?>" class="nav-button">다음</a>
                <?php else: ?>
                    <span class="nav-button disabled">다음</span>
                <?php endif; ?>

                <!-- 마지막 -->
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $totalPages ?>" class="nav-button">마지막</a>
                <?php else: ?>
                    <span class="nav-button disabled">마지막</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- 콜센터 등록/수정 모달 -->
<div id="ccModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 9999 !important; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl" style="z-index: 10000 !important;">
        <!-- 모달 헤더 -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">콜센터 등록</h3>
            <button type="button" onclick="closeCcModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- 모달 바디 -->
        <div class="px-6 py-4">
            <form id="ccForm">
                <input type="hidden" id="form_mode" name="mode" value="add">
                <input type="hidden" id="form_idx" name="idx" value="">

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">회사코드 <span class="text-red-500">*</span></label>
                        <input type="text" id="cc_code" name="cc_code" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="회사코드">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">회사명 <span class="text-red-500">*</span></label>
                        <input type="text" id="cc_name" name="cc_name" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="회사명">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API연계센타 <span class="text-red-500">*</span></label>
                        <select id="cc_apicode" name="cc_apicode" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">선택하세요</option>
                            <?php if (!empty($api_list)): ?>
                                <?php foreach ($api_list as $api): ?>
                                    <option value="<?= esc($api['idx']) ?>">[<?= esc($api['cccode']) ?>] <?= esc($api['api_name']) ?> (<?= esc($api['api_code']) ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">퀵아이디 <span class="text-red-500">*</span></label>
                        <input type="text" id="cc_quickid" name="cc_quickid" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="퀵아이디">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">연락처</label>
                        <input type="text" id="cc_telno" name="cc_telno" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="연락처">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">메모</label>
                        <input type="text" id="cc_memo" name="cc_memo" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="메모">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">주소 <span class="text-red-500">*</span></label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="cc_dongname" name="cc_dongname" class="w-24 px-3 py-2 border border-gray-300 rounded-md text-sm bg-gray-50" placeholder="동명" readonly>
                        <input type="text" id="cc_addr" name="cc_addr" class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm bg-gray-50" placeholder="주소" readonly>
                        <button type="button" onclick="execDaumPostcode()" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">주소검색</button>
                    </div>
                    <input type="text" id="cc_addr_detail" name="cc_addr_detail" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="상세주소">
                </div>
            </form>
        </div>

        <!-- 모달 푸터 -->
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button type="button" onclick="closeCcModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">취소</button>
            <button type="button" id="btnSubmit" onclick="submitForm()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">등록</button>
        </div>
    </div>
</div>

<!-- 카카오 주소검색 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
function execDaumPostcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            document.getElementById('cc_addr').value = data.jibunAddress || data.roadAddress;
            document.getElementById('cc_dongname').value = data.bname;
            document.getElementById('cc_addr_detail').focus();
        }
    }).open();
}

function openCcModal() {
    // 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }

    document.getElementById('ccForm').reset();
    document.getElementById('form_mode').value = 'add';
    document.getElementById('form_idx').value = '';
    document.getElementById('modalTitle').textContent = '콜센터 등록';
    document.getElementById('btnSubmit').textContent = '등록';
    document.getElementById('ccModal').classList.remove('hidden');
    document.getElementById('ccModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeCcModal() {
    document.getElementById('ccModal').classList.add('hidden');
    document.getElementById('ccModal').classList.remove('flex');
    document.body.style.overflow = 'auto';

    // 사이드바 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

function editCc(idx) {
    fetch(`<?= base_url('insung/getCcDetail') ?>?idx=${idx}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cc = data.cc;
                document.getElementById('form_mode').value = 'edit';
                document.getElementById('form_idx').value = cc.idx;
                document.getElementById('cc_code').value = cc.cc_code || '';
                document.getElementById('cc_name').value = cc.cc_name || '';
                document.getElementById('cc_apicode').value = cc.cc_apicode || '';
                document.getElementById('cc_quickid').value = cc.cc_quickid || '';
                document.getElementById('cc_telno').value = cc.cc_telno || '';
                document.getElementById('cc_memo').value = cc.cc_memo || '';
                document.getElementById('cc_dongname').value = cc.cc_dongname || '';
                document.getElementById('cc_addr').value = cc.cc_addr || '';
                document.getElementById('cc_addr_detail').value = cc.cc_addr_detail || '';

                // 사이드바 처리
                if (typeof window.hideSidebarForModal === 'function') {
                    window.hideSidebarForModal();
                }
                if (typeof window.lowerSidebarZIndex === 'function') {
                    window.lowerSidebarZIndex();
                }

                document.getElementById('modalTitle').textContent = '콜센터 수정';
                document.getElementById('btnSubmit').textContent = '수정';
                document.getElementById('ccModal').classList.remove('hidden');
                document.getElementById('ccModal').classList.add('flex');
                document.body.style.overflow = 'hidden';
            } else {
                alert(data.message || '콜센터 정보를 불러오는데 실패했습니다.');
            }
        })
        .catch(error => {
            alert('콜센터 정보를 불러오는 중 오류가 발생했습니다.');
        });
}

function submitForm() {
    const form = document.getElementById('ccForm');
    const formData = new FormData(form);
    const mode = document.getElementById('form_mode').value;

    // 필수 필드 검증
    const ccCode = document.getElementById('cc_code').value.trim();
    const ccName = document.getElementById('cc_name').value.trim();
    const ccApicode = document.getElementById('cc_apicode').value;
    const ccQuickid = document.getElementById('cc_quickid').value.trim();
    const ccDongname = document.getElementById('cc_dongname').value.trim();

    if (!ccCode) { alert('회사코드를 입력해주세요.'); return; }
    if (!ccName) { alert('회사명을 입력해주세요.'); return; }
    if (!ccApicode) { alert('API연계센타를 선택해주세요.'); return; }
    if (!ccQuickid) { alert('퀵아이디를 입력해주세요.'); return; }
    if (!ccDongname) { alert('주소검색 후 입력해주세요.'); return; }

    const url = mode === 'add' ? '<?= base_url('insung/addCcList') ?>' : '<?= base_url('insung/updateCcList') ?>';

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        alert('처리 중 오류가 발생했습니다.');
    });
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('ccModal').classList.contains('hidden')) {
        closeCcModal();
    }
});
</script>
<?= $this->endSection() ?>