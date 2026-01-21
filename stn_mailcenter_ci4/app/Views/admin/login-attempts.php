<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 통계 카드 -->
    <div class="grid grid-cols-4 gap-3 mb-4">
        <div class="bg-blue-50 rounded p-2 border border-blue-200 text-center">
            <div class="text-lg font-bold text-blue-600"><?= number_format($statistics['total_attempts']) ?></div>
            <div class="text-xs text-blue-800">전체 시도 (7일)</div>
        </div>
        <div class="bg-green-50 rounded p-2 border border-green-200 text-center">
            <div class="text-lg font-bold text-green-600"><?= number_format($statistics['success_attempts']) ?></div>
            <div class="text-xs text-green-800">성공</div>
        </div>
        <div class="bg-red-50 rounded p-2 border border-red-200 text-center">
            <div class="text-lg font-bold text-red-600"><?= number_format($statistics['failed_attempts']) ?></div>
            <div class="text-xs text-red-800">실패</div>
        </div>
        <div class="bg-gray-50 rounded p-2 border border-gray-200 text-center">
            <div class="text-lg font-bold text-gray-600"><?= number_format($statistics['unique_ips']) ?></div>
            <div class="text-xs text-gray-800">고유 IP</div>
        </div>
    </div>

    <!-- 검색 영역 -->
    <div class="search-compact">
        <form method="get" id="search-form">
            <div class="search-filter-container">
                <div class="search-filter-item">
                    <label class="search-filter-label">사용자 ID</label>
                    <input type="text" name="user_id" value="<?= esc($filters['user_id'] ?? '') ?>" class="search-filter-input" placeholder="사용자 ID">
                </div>
                <div class="search-filter-item">
                    <label class="search-filter-label">IP 주소</label>
                    <input type="text" name="ip_address" value="<?= esc($filters['ip_address'] ?? '') ?>" class="search-filter-input" placeholder="IP 주소">
                </div>
                <div class="search-filter-item">
                    <label class="search-filter-label">결과</label>
                    <select name="is_success" class="search-filter-select">
                        <option value="">전체</option>
                        <option value="1" <?= ($filters['is_success'] ?? '') === '1' ? 'selected' : '' ?>>성공</option>
                        <option value="0" <?= ($filters['is_success'] ?? '') === '0' ? 'selected' : '' ?>>실패</option>
                    </select>
                </div>
                <div class="search-filter-item">
                    <label class="search-filter-label">시작일</label>
                    <input type="date" name="date_from" value="<?= esc($filters['date_from'] ?? '') ?>" class="search-filter-input">
                </div>
                <div class="search-filter-item">
                    <label class="search-filter-label">종료일</label>
                    <input type="date" name="date_to" value="<?= esc($filters['date_to'] ?? '') ?>" class="search-filter-input">
                </div>
                <div class="search-filter-button-wrapper">
                    <button type="submit" class="search-button">🔍 검색</button>
                    <a href="<?= base_url('admin/login-attempts') ?>" class="search-button" style="background: #6b7280 !important;">초기화</a>
                </div>
            </div>
        </form>
    </div>

    <!-- 결과 건수 -->
    <div class="mb-2 text-xs text-gray-600">
        총 <span class="font-semibold"><?= number_format($total_count) ?></span>건
    </div>

    <!-- 테이블 -->
    <div class="list-table-container">
        <table class="list-table-compact">
            <thead>
                <tr>
                    <th style="width:40px;">번호</th>
                    <th>일시</th>
                    <th>사용자 ID</th>
                    <th>내부 IP</th>
                    <th>외부 IP</th>
                    <th>유형</th>
                    <th class="text-center">결과</th>
                    <th>실패 사유</th>
                    <th>User-Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attempts)): ?>
                    <tr>
                        <td colspan="9" class="text-center">조회된 데이터가 없습니다.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $per_page = $pagination['per_page'] ?? 20;
                    $rowNum = $total_count - (($current_page - 1) * $per_page);
                    ?>
                    <?php foreach ($attempts as $attempt): ?>
                        <tr class="<?= $attempt['is_success'] ? '' : 'bg-red-50' ?>">
                            <td class="text-center"><?= $rowNum-- ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($attempt['created_at'])) ?></td>
                            <td class="font-medium"><?= esc($attempt['user_id']) ?></td>
                            <td><?= esc($attempt['ip_address']) ?></td>
                            <td><?= esc($attempt['forwarded_ip'] ?? '-') ?></td>
                            <td>
                                <span class="status-badge <?= $attempt['attempt_type'] === 'daumdata' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                    <?= esc($attempt['attempt_type']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($attempt['is_success']): ?>
                                    <span class="status-badge bg-green-100 text-green-800">성공</span>
                                <?php else: ?>
                                    <span class="status-badge bg-red-100 text-red-800">실패</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($attempt['failure_reason'] ?? '-') ?></td>
                            <td class="truncate" style="max-width:200px;" title="<?= esc($attempt['user_agent'] ?? '') ?>">
                                <?= esc(substr($attempt['user_agent'] ?? '', 0, 40)) ?><?= strlen($attempt['user_agent'] ?? '') > 40 ? '...' : '' ?>
                            </td>
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
                $baseUrl = base_url('admin/login-attempts');
                $queryParams = array_filter($filters);
                $queryString = http_build_query($queryParams);
                ?>

                <!-- 처음 -->
                <?php if ($current_page > 1): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=1" class="nav-button">처음</a>
                <?php else: ?>
                    <span class="nav-button disabled">처음</span>
                <?php endif; ?>

                <!-- 이전 -->
                <?php if ($current_page > 1): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $current_page - 1 ?>" class="nav-button">이전</a>
                <?php else: ?>
                    <span class="nav-button disabled">이전</span>
                <?php endif; ?>

                <!-- 페이지 번호 -->
                <?php
                $startPage = max(1, $current_page - 2);
                $endPage = min($pagination['total_pages'], $current_page + 2);
                ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i === $current_page): ?>
                        <span class="page-number active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $i ?>" class="page-number"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- 다음 -->
                <?php if ($current_page < $pagination['total_pages']): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $current_page + 1 ?>" class="nav-button">다음</a>
                <?php else: ?>
                    <span class="nav-button disabled">다음</span>
                <?php endif; ?>

                <!-- 마지막 -->
                <?php if ($current_page < $pagination['total_pages']): ?>
                    <a href="<?= $baseUrl ?>?<?= $queryString ?>&page=<?= $pagination['total_pages'] ?>" class="nav-button">마지막</a>
                <?php else: ?>
                    <span class="nav-button disabled">마지막</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>