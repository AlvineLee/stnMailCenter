<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 검색 영역 -->
    <div class="search-compact">
        <form method="get" id="search-form">
            <div class="search-filter-container">
                <div class="search-filter-item">
                    <select name="api_gbn" class="search-filter-input">
                        <option value="">구분 전체</option>
                        <option value="M" <?= ($filters['api_gbn'] ?? '') === 'M' ? 'selected' : '' ?>>M (메인)</option>
                        <option value="S" <?= ($filters['api_gbn'] ?? '') === 'S' ? 'selected' : '' ?>>S (서브)</option>
                    </select>
                </div>
                <div class="search-filter-item">
                    <input type="text" name="cccode" value="<?= esc($filters['cccode'] ?? '') ?>" class="search-filter-input" placeholder="콜센터코드">
                </div>
                <div class="search-filter-item">
                    <input type="text" name="api_name" value="<?= esc($filters['api_name'] ?? '') ?>" class="search-filter-input" placeholder="API명">
                </div>
                <div class="search-filter-button-wrapper">
                    <button type="submit" class="search-button">검색</button>
                    <a href="<?= base_url('admin/api-list') ?>" class="search-button" style="background: #6b7280 !important;">초기화</a>
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
                    <th style="width:40px;">구분</th>
                    <th style="width:70px;">콜센터코드</th>
                    <th style="width:140px;">API명</th>
                    <th style="width:70px;">API코드</th>
                    <th style="width:70px;">mcode</th>
                    <th>토큰 (암호화)</th>
                    <!-- 토큰 갱신 기능 일시 비활성화
                    <th style="width:60px;">갱신</th>
                    -->
                </tr>
            </thead>
            <tbody>
                <?php if (empty($api_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center">조회된 데이터가 없습니다.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $per_page = $pagination['per_page'] ?? 20;
                    $rowNum = ($total_count ?? 0) - (($pagination['current_page'] ?? 1) - 1) * $per_page;
                    ?>
                    <?php foreach ($api_list as $api): ?>
                        <tr class="cursor-pointer" onclick="viewApiDetail(<?= esc($api['idx']) ?>)">
                            <td class="text-center"><?= $rowNum-- ?></td>
                            <td class="text-center">
                                <?php if (trim($api['api_gbn'] ?? '') === 'M'): ?>
                                    <span class="inline-block px-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded">M</span>
                                <?php else: ?>
                                    <span class="inline-block px-1 text-xs font-semibold bg-gray-100 text-gray-600 rounded"><?= esc(trim($api['api_gbn'] ?? '-')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($api['cccode'] ?? '-') ?></td>
                            <td><?= esc($api['api_name'] ?? '-') ?></td>
                            <td><?= esc($api['api_code'] ?? '-') ?></td>
                            <td><?= esc($api['mcode'] ?? '-') ?></td>
                            <td class="truncate" style="max-width:300px;" title="<?= esc($api['token_encrypted'] ?? '') ?>">
                                <?= esc($api['token_encrypted'] ?? '-') ?>
                            </td>
                            <!-- 토큰 갱신 기능 일시 비활성화
                            <td class="text-center" onclick="event.stopPropagation();">
                                <button type="button" onclick="refreshToken(<?= esc($api['idx']) ?>)" class="px-1.5 py-0.5 text-xs bg-green-600 text-white rounded hover:bg-green-700">갱신</button>
                            </td>
                            -->
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
                $baseUrl = base_url('admin/api-list');
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

<!-- API 상세 모달 -->
<div id="apiModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 9999 !important; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl" style="z-index: 10000 !important;">
        <!-- 모달 헤더 -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">API 상세 정보</h3>
            <button type="button" onclick="closeApiModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- 모달 바디 -->
        <div class="px-6 py-4">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">구분</label>
                    <div id="detail_api_gbn" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm">-</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">콜센터코드 (cccode)</label>
                    <div id="detail_cccode" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm">-</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">API명</label>
                    <div id="detail_api_name" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm">-</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">API코드</label>
                    <div id="detail_api_code" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm">-</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">mcode</label>
                    <div id="detail_mcode" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm">-</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">idx</label>
                    <div id="detail_idx" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm">-</div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500 mb-1">토큰 (암호화)</label>
                <div id="detail_token" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm break-all max-h-32 overflow-y-auto">-</div>
            </div>
        </div>

        <!-- 모달 푸터 -->
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <!-- 토큰 갱신 기능 일시 비활성화
            <button type="button" onclick="refreshTokenFromModal()" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">토큰 갱신</button>
            -->
            <button type="button" onclick="closeApiModal()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">닫기</button>
        </div>
    </div>
</div>

<script>
let currentApiIdx = null;

function viewApiDetail(idx) {
    fetch(`<?= base_url('admin/getApiDetail') ?>?idx=${idx}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const api = data.api;
                currentApiIdx = api.idx;

                document.getElementById('detail_api_gbn').textContent = api.api_gbn || '-';
                document.getElementById('detail_cccode').textContent = api.cccode || '-';
                document.getElementById('detail_api_name').textContent = api.api_name || '-';
                document.getElementById('detail_api_code').textContent = api.api_code || '-';
                document.getElementById('detail_mcode').textContent = api.mcode || '-';
                document.getElementById('detail_idx').textContent = api.idx || '-';

                // 암호화된 토큰 표시
                const tokenEncrypted = api.token_encrypted || '';
                if (tokenEncrypted) {
                    document.getElementById('detail_token').textContent = tokenEncrypted;
                } else {
                    document.getElementById('detail_token').textContent = '(토큰 없음)';
                }

                // 사이드바 처리
                if (typeof window.hideSidebarForModal === 'function') {
                    window.hideSidebarForModal();
                }
                if (typeof window.lowerSidebarZIndex === 'function') {
                    window.lowerSidebarZIndex();
                }

                document.getElementById('apiModal').classList.remove('hidden');
                document.getElementById('apiModal').classList.add('flex');
                document.body.style.overflow = 'hidden';
            } else {
                alert(data.message || 'API 정보를 불러오는데 실패했습니다.');
            }
        })
        .catch(error => {
            alert('API 정보를 불러오는 중 오류가 발생했습니다.');
        });
}

function closeApiModal() {
    document.getElementById('apiModal').classList.add('hidden');
    document.getElementById('apiModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
    currentApiIdx = null;

    // 사이드바 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

function refreshToken(idx) {
    if (!confirm('해당 API의 토큰을 갱신하시겠습니까?')) return;

    fetch(`<?= base_url('admin/refreshApiToken') ?>`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ idx: idx })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '토큰 갱신에 실패했습니다.');
        }
    })
    .catch(error => {
        alert('토큰 갱신 중 오류가 발생했습니다.');
    });
}

function refreshTokenFromModal() {
    if (currentApiIdx) {
        refreshToken(currentApiIdx);
    }
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('apiModal').classList.contains('hidden')) {
        closeApiModal();
    }
});
</script>
<?= $this->endSection() ?>