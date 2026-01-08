<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('/admin/company-list-cc', ['method' => 'GET']) ?>
        <div class="search-filter-container search-single-field">
            <div class="search-filter-item">
                <label class="search-filter-label">거래처명</label>
                <input type="text" name="search_compname" value="<?= esc($search_compname ?? '') ?>" placeholder="거래처명 또는 담당자명 검색" class="search-filter-input">
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button">🔍 검색</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 거래처 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($company_list)): ?>
            <div class="text-center py-8 text-gray-500">
                조회된 거래처가 없습니다.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">업체명</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">담당자</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">전화번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">주소</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">메모</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">선택</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php 
                    $num = 1; 
                    foreach ($company_list as $company): 
                        // 빈 데이터 필터링: comp_code와 comp_name이 모두 없으면 스킵
                        if (empty($company['comp_code']) && empty($company['comp_name'])) {
                            continue;
                        }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-center"><?= $num++ ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['comp_name'] ?? '') ?></td>
                        <td class="px-4 py-2 text-sm text-center"><?= esc($company['comp_owner'] ?? '') ?></td>
                        <td class="px-4 py-2 text-sm text-center"><?= esc($company['comp_tel'] ?? '') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['comp_addr'] ?? '') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($company['comp_memo'] ?? '') ?></td>
                        <td class="px-4 py-2 text-sm text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" onclick="location.href='<?= base_url('admin/company-edit?comp_code=' . urlencode($company['comp_code'])) ?>'" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm font-medium whitespace-nowrap">
                                    ✏️ 정보수정
                                </button>
                                <button type="button" onclick="location.href='<?= base_url('admin/company-customer-list?comp_code=' . urlencode($company['comp_code'])) ?>'" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm font-medium whitespace-nowrap">
                                    👥 고객관리
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

