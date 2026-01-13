<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 검색 영역 -->
    <div class="search-compact">
        <?= form_open('/admin/company-customer-list', ['method' => 'GET']) ?>
        <input type="hidden" name="comp_code" value="<?= esc($comp_code ?? $company_info['comp_code'] ?? '') ?>">
        <div class="search-filter-container search-single-field">
            <div class="search-filter-item">
                <label class="search-filter-label">검색어</label>
                <input type="text" name="search_keyword" value="<?= esc($search_keyword ?? '') ?>" 
                       placeholder="아이디, 이름, 부서, 전화번호로 검색" 
                       class="search-filter-input">
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button">🔍 검색</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 고객 목록 테이블 -->
    <div class="list-table-container">
        <?php if (empty($user_list)): ?>
            <div class="text-center py-8 text-gray-500">
                조회된 고객이 없습니다.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">아이디</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">비밀번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">부서명</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">담당자</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">전화1</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">전화2</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">주소</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">메모</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">등급</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">주소확인</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">사용유무</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($user_list as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm">
                            <?php
                            $formUrl = 'admin/company-customer-form?comp_code=' . urlencode($comp_code ?? $company_info['comp_code'] ?? '') . '&mode=edit&user_id=' . urlencode($user['user_id'] ?? '');
                            // 검색 파라미터가 있으면 함께 전달
                            if (!empty($search_keyword)) {
                                $formUrl .= '&search_keyword=' . urlencode($search_keyword);
                            }
                            ?>
                            <a href="<?= base_url($formUrl) ?>" 
                               class="text-blue-600 hover:text-blue-800 hover:underline">
                                <?= esc($user['user_id'] ?? '') ?>
                            </a>
                        </td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_pass'] ?? '') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_dept'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_tel1'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_tel2'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm">
                            <?php 
                            $addr = trim(($user['user_addr'] ?? '') . ' ' . ($user['user_addr_detail'] ?? ''));
                            echo esc($addr ?: '-');
                            ?>
                        </td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_memo'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm text-center">
                            <?php
                            $userClass = $user['user_class'] ?? '5';
                            $badgeClass = 'bg-gray-100 text-gray-800';
                            if ($userClass == '1' || $userClass == '2') {
                                $badgeClass = 'bg-purple-100 text-purple-800';
                            } elseif ($userClass == '3') {
                                $badgeClass = 'bg-blue-100 text-blue-800';
                            } elseif ($userClass == '4') {
                                $badgeClass = 'bg-green-100 text-green-800';
                            }
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded <?= $badgeClass ?>">
                                <?= esc($user['user_class_label'] ?? '일반') ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-center">
                            <?php 
                            $hasAddr = !empty($user['user_addr']) || !empty($user['user_addr_detail']);
                            echo $hasAddr ? '✅' : '-';
                            ?>
                        </td>
                        <td class="px-4 py-2 text-sm text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">
                                사용
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 페이징 -->
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="list-pagination">
            <div class="pagination">
                <?php
                $compCodeParam = '?comp_code=' . urlencode($comp_code ?? $company_info['comp_code'] ?? '');
                $searchParam = !empty($search_keyword) ? '&search_keyword=' . urlencode($search_keyword) : '';
                $queryString = $compCodeParam . $searchParam;
                
                // 처음 버튼
                if ($pagination['current_page'] > 1): ?>
                    <a href="<?= base_url('admin/company-customer-list' . $queryString . '&page=1') ?>" class="nav-button">처음</a>
                <?php else: ?>
                    <span class="nav-button disabled">처음</span>
                <?php endif; ?>
                
                <!-- 이전 버튼 -->
                <?php if ($pagination['current_page'] > 1): ?>
                    <a href="<?= base_url('admin/company-customer-list' . $queryString . '&page=' . ($pagination['current_page'] - 1)) ?>" class="nav-button">이전</a>
                <?php else: ?>
                    <span class="nav-button disabled">이전</span>
                <?php endif; ?>
                
                <?php
                // 페이지 번호 표시
                $startPage = max(1, $pagination['current_page'] - 2);
                $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                        <span class="page-number active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= base_url('admin/company-customer-list' . $queryString . '&page=' . $i) ?>" class="page-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <!-- 다음 버튼 -->
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="<?= base_url('admin/company-customer-list' . $queryString . '&page=' . ($pagination['current_page'] + 1)) ?>" class="nav-button">다음</a>
                <?php else: ?>
                    <span class="nav-button disabled">다음</span>
                <?php endif; ?>
                
                <!-- 끝 버튼 -->
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                    <a href="<?= base_url('admin/company-customer-list' . $queryString . '&page=' . $pagination['total_pages']) ?>" class="nav-button">끝</a>
                <?php else: ?>
                    <span class="nav-button disabled">끝</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
