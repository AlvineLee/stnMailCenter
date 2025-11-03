<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <div class="mb-4">
        <h2 class="text-lg font-bold text-gray-800 mb-1">빌딩 콜센터 계정 목록</h2>
        <p class="text-xs text-gray-600">빌딩 콜센터 계정을 관리합니다.</p>
    </div>

    <!-- 계정 리스트 테이블 -->
    <div class="list-table-container">
        <?php if (empty($users)): ?>
            <div class="text-center py-8 text-gray-500">
                등록된 계정이 없습니다.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>아이디</th>
                    <th>실명</th>
                    <th>고객사</th>
                    <th class="text-center">역할</th>
                    <th class="text-center">상태</th>
                    <th class="text-center">등록일</th>
                    <th class="text-center">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['real_name']) ?></td>
                    <td><?= htmlspecialchars($user['customer_name'] ?? '-') ?></td>
                    <td class="text-center">
                        <span class="status-badge"><?= htmlspecialchars($user['user_role']) ?></span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge status-<?= $user['status'] ?>"><?= htmlspecialchars($user['status']) ?></span>
                    </td>
                    <td class="text-center"><?= $user['created_at'] ? date('Y-m-d', strtotime($user['created_at'])) : '-' ?></td>
                    <td class="action-buttons text-center">
                        <!-- 빌딩 콜센터 관리에서는 오더유형 설정 제거 -->
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>


<?= $this->endSection() ?>

