<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<?php if (!empty($tables_not_exist)): ?>
    <!-- 테이블 미생성 안내 -->
    <div class="list-page-container">
        <div class="p-8 text-center bg-yellow-50 border border-yellow-300 rounded-lg">
            <div class="text-yellow-500 mb-3">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="inline-block">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h2 class="text-base font-semibold text-yellow-800 mb-1">데이터베이스 초기화 필요</h2>
            <p class="text-sm text-yellow-700 mb-4">메일룸 서비스 테이블이 아직 생성되지 않았습니다.</p>
            <div class="inline-block bg-gray-900 text-green-400 px-4 py-3 rounded font-mono text-xs text-left mb-3">
                <code>$ cd /path/to/project</code><br>
                <code>$ php spark migrate</code>
            </div>
            <p class="text-xs text-gray-500">마이그레이션 실행 후 페이지를 새로고침해주세요.</p>
        </div>
    </div>
<?php else: ?>
    <!-- 헤더 영역 -->
    <div class="page-header-section mb-3 px-3 py-3 bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">메일룸 대시보드</h1>
                <p class="text-xs text-gray-500">배송 현황을 실시간으로 확인합니다.</p>
            </div>
            <a href="/mailroom/create" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600">+ 배송 접수</a>
        </div>
        <?php if (session()->getFlashdata('message')): ?>
            <div class="mt-3 px-3 py-2 text-xs bg-green-50 border border-green-200 text-green-700 rounded"><?= session()->getFlashdata('message') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="mt-3 px-3 py-2 text-xs bg-red-50 border border-red-200 text-red-700 rounded"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
    </div>

    <!-- 통계 카드 -->
    <div class="grid grid-cols-4 gap-3 mb-3">
        <div class="p-3 text-center rounded-lg bg-red-50 border border-red-200">
            <div class="text-2xl font-bold text-red-600"><?= $stats['urgent'] ?? 0 ?></div>
            <div class="text-xs text-red-500">긴급</div>
        </div>
        <div class="p-3 text-center rounded-lg bg-yellow-50 border border-yellow-200">
            <div class="text-2xl font-bold text-yellow-600"><?= ($stats['pending'] ?? 0) + ($stats['confirmed'] ?? 0) ?></div>
            <div class="text-xs text-yellow-500">대기중</div>
        </div>
        <div class="p-3 text-center rounded-lg bg-blue-50 border border-blue-200">
            <div class="text-2xl font-bold text-blue-600"><?= $stats['picked'] ?? 0 ?></div>
            <div class="text-xs text-blue-500">배송중</div>
        </div>
        <div class="p-3 text-center rounded-lg bg-green-50 border border-green-200">
            <div class="text-2xl font-bold text-green-600"><?= $stats['delivered'] ?? 0 ?></div>
            <div class="text-xs text-green-500">완료</div>
        </div>
    </div>

    <!-- 필터 -->
    <div class="flex items-end gap-3 p-3 mb-3 bg-white border border-gray-200 rounded-lg flex-wrap">
        <div class="flex flex-col gap-1">
            <label class="text-xs text-gray-500">건물</label>
            <select id="buildingFilter" class="px-2 py-1.5 text-sm border border-gray-300 rounded min-w-[140px] focus:border-blue-500 focus:outline-none">
                <option value="">전체</option>
                <?php foreach ($buildings as $building): ?>
                    <option value="<?= $building['id'] ?>" <?= $selected_building == $building['id'] ? 'selected' : '' ?>><?= esc($building['building_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-gray-500">날짜</label>
            <input type="date" id="dateFilter" value="<?= $selected_date ?>" class="px-2 py-1.5 text-sm border border-gray-300 rounded focus:border-blue-500 focus:outline-none">
        </div>
        <button onclick="applyFilter()" class="px-3 py-1.5 text-xs font-medium text-white bg-gray-700 rounded hover:bg-gray-800">조회</button>
    </div>

    <!-- 콘텐츠 영역 -->
    <div class="list-page-container">
        <div class="mb-2 text-xs text-gray-600">
            주문 목록 <?php if ($selected_building): ?><span class="text-gray-400">(<?= date('Y-m-d', strtotime($selected_date)) ?>)</span><?php endif; ?>
        </div>

        <?php if (empty($orders) && !$selected_building): ?>
            <div class="py-12 text-center text-gray-500 text-sm bg-white border border-gray-200 rounded">
                건물을 선택하면 해당 건물의 주문 목록이 표시됩니다.
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="py-12 text-center text-gray-500 text-sm bg-white border border-gray-200 rounded">
                조회된 주문이 없습니다.
            </div>
        <?php else: ?>
            <div class="list-table-container">
                <table class="list-table-compact">
                    <thead>
                        <tr>
                            <th style="width:100px;">주문번호</th>
                            <th style="width:140px;">출발지</th>
                            <th style="width:140px;">도착지</th>
                            <th>물품</th>
                            <th style="width:60px;" class="text-center">상태</th>
                            <th style="width:80px;">기사</th>
                            <th style="width:50px;">접수</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr onclick="location.href='/mailroom/detail/<?= $order['id'] ?>'" class="cursor-pointer">
                                <td>
                                    <span class="text-blue-600 font-medium"><?= esc($order['order_no']) ?></span>
                                    <?php if ($order['priority'] === 'urgent'): ?>
                                        <span class="status-badge ml-1" style="background:#fef2f2;color:#dc2626;">긴급</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-sm font-medium"><?= esc($order['from_building_name'] ?? '') ?></div>
                                    <div class="text-xs text-gray-400"><?= esc($order['from_floor_name'] ?? '') ?> <?= esc($order['from_company'] ?? '') ?></div>
                                </td>
                                <td>
                                    <div class="text-sm font-medium"><?= esc($order['to_building_name'] ?? '') ?></div>
                                    <div class="text-xs text-gray-400"><?= esc($order['to_floor_name'] ?? '') ?> <?= esc($order['to_company'] ?? '') ?></div>
                                </td>
                                <td><?= esc($order['item_description']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $statusStyle = [
                                        'pending' => 'background:#fef3c7;color:#d97706;',
                                        'confirmed' => 'background:#dbeafe;color:#2563eb;',
                                        'picked' => 'background:#e0e7ff;color:#4f46e5;',
                                        'delivered' => 'background:#dcfce7;color:#16a34a;',
                                        'cancelled' => 'background:#fee2e2;color:#dc2626;'
                                    ];
                                    $statusText = [
                                        'pending' => '접수',
                                        'confirmed' => '확인',
                                        'picked' => '픽업',
                                        'delivered' => '완료',
                                        'cancelled' => '취소'
                                    ];
                                    ?>
                                    <span class="status-badge" style="<?= $statusStyle[$order['status']] ?? '' ?>"><?= $statusText[$order['status']] ?? $order['status'] ?></span>
                                </td>
                                <td><?= esc($order['driver_name'] ?? '-') ?></td>
                                <td class="text-gray-400"><?= date('H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function applyFilter() {
    const buildingId = document.getElementById('buildingFilter').value;
    const date = document.getElementById('dateFilter').value;
    let url = '/mailroom?';
    if (buildingId) url += 'building_id=' + buildingId + '&';
    if (date) url += 'date=' + date;
    location.href = url;
}
</script>
<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>