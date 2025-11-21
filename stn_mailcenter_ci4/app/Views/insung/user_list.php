<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('insung/user-list', ['method' => 'GET', 'id' => 'searchForm']) ?>
        <div class="search-filter-container">
            <div class="search-filter-item">
                <label class="search-filter-label">콜센터</label>
                <select name="cc_code" id="cc_code_select" class="search-filter-select">
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
                <label class="search-filter-label">고객사</label>
                <select name="comp_code" id="comp_code_select" class="search-filter-select">
                    <option value="all" <?= ($comp_code_filter ?? 'all') === 'all' ? 'selected' : '' ?>>전체</option>
                    <?php if (!empty($company_list)): ?>
                        <?php foreach ($company_list as $company): ?>
                            <option value="<?= esc($company['comp_code']) ?>" <?= ($comp_code_filter ?? 'all') === $company['comp_code'] ? 'selected' : '' ?>>
                                <?= esc($company['comp_name'] ?? $company['comp_code']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">회원명</label>
                <input type="text" name="search_name" id="search_name" class="search-filter-select" value="<?= esc($search_name ?? '') ?>" placeholder="회원명 입력">
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">아이디</label>
                <input type="text" name="search_id" id="search_id" class="search-filter-select" value="<?= esc($search_id ?? '') ?>" placeholder="아이디 입력">
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button">🔍 검색</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 검색 결과 정보 -->
    <div class="mb-4 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                <?php if (isset($pagination) && $pagination): ?>
                    총 <?= number_format($pagination['total_count']) ?>건 중 
                    <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>건 표시
                <?php else: ?>
                    검색 결과가 없습니다.
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="list-table-container">
        <?php if (empty($user_list)): ?>
            <div class="text-center py-8 text-gray-500">
                회원 데이터가 없습니다.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200" style="table-layout: fixed;">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 6%;">번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 11%;">아이디</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 9%;">이름</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 9%;">부서</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 14%;">고객사</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 11%;">연락처</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 24%;">주소</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b" style="width: 16%;">권한</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php 
                    // 역순 번호 계산 (전체 개수에서 현재 페이지의 시작 인덱스를 빼고, 현재 항목의 인덱스를 뺌)
                    $totalCount = $pagination['total_count'] ?? 0;
                    $currentPage = $pagination['current_page'] ?? 1;
                    $perPage = $pagination['per_page'] ?? 20;
                    $startNumber = $totalCount - (($currentPage - 1) * $perPage);
                    $rowNumber = $startNumber;
                    foreach ($user_list as $user): 
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-center"><?= $rowNumber-- ?></td>
                        <td class="px-4 py-2 text-sm" style="word-break: break-all;"><?= esc($user['user_id'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_dept'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['comp_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_tel1'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm" style="word-break: break-all;"><?= esc($user['user_addr'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm">
                            <?php
                            $userTypeLabels = [
                                '1' => '메인 사이트 관리자',
                                '3' => '콜센터 관리자',
                                '5' => '일반 고객'
                            ];
                            $userType = $user['user_type'] ?? '5';
                            ?>
                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                <?= $userTypeLabels[$userType] ?? '일반 고객' ?>
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
        base_url('insung/user-list'),
        array_filter([
            'cc_code' => ($cc_code_filter ?? 'all') !== 'all' ? $cc_code_filter : null,
            'comp_code' => ($comp_code_filter ?? 'all') !== 'all' ? $comp_code_filter : null,
            'search_name' => !empty($search_name) ? $search_name : null,
            'search_id' => !empty($search_id) ? $search_id : null
        ], function($value) {
            return $value !== null && $value !== '';
        })
    );
    echo $paginationHelper->renderWithCurrentStyle();
    ?>
    <?php endif; ?>
</div>

<script>
// 콜센터 선택 시 고객사 목록 동적 업데이트
document.getElementById('cc_code_select').addEventListener('change', function() {
    const ccCode = this.value;
    const compCodeSelect = document.getElementById('comp_code_select');
    
    // 고객사 선택 초기화
    compCodeSelect.innerHTML = '<option value="all">전체</option>';
    
    if (ccCode === 'all') {
        // 전체 선택 시 모든 고객사 로드
        fetch(`<?= base_url('insung/getCompaniesByCcForSelect') ?>?cc_code=all`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.companies) {
                    data.companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.comp_code;
                        option.textContent = company.comp_name || company.comp_code;
                        compCodeSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    } else {
        // 특정 콜센터 선택 시 해당 콜센터의 고객사만 로드
        fetch(`<?= base_url('insung/getCompaniesByCcForSelect') ?>?cc_code=${encodeURIComponent(ccCode)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.companies) {
                    data.companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.comp_code;
                        option.textContent = company.comp_name || company.comp_code;
                        compCodeSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
});
</script>
<?= $this->endSection() ?>

