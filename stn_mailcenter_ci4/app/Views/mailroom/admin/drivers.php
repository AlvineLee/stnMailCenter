<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<!-- 헤더 영역 -->
<div class="page-header-section mb-4 px-3 py-3 bg-white rounded-lg border border-gray-200 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-800">기사 관리</h1>
            <p class="text-xs text-gray-500">메일룸 서비스 배송 기사를 관리합니다.</p>
        </div>
        <span class="text-xs text-gray-400">건물 관리에서 QR코드를 통해 기사 등록을 받을 수 있습니다.</span>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="mb-3 px-3 py-2 text-xs bg-green-50 border border-green-200 text-green-700 rounded"><?= session()->getFlashdata('message') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-3 px-3 py-2 text-xs bg-red-50 border border-red-200 text-red-700 rounded"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <?php
    $pendingDrivers = array_filter($drivers, fn($d) => $d['status'] === 'pending');
    $activeDrivers = array_filter($drivers, fn($d) => $d['status'] === 'active');
    $inactiveDrivers = array_filter($drivers, fn($d) => $d['status'] === 'inactive');
    ?>

    <!-- 승인 대기 기사 -->
    <?php if (!empty($pendingDrivers)): ?>
    <div class="px-3 py-2 bg-yellow-50 border border-yellow-300 rounded">
        <div class="flex items-center gap-2 mb-2">
            <span class="px-2 py-0.5 text-xs font-semibold text-white bg-yellow-500 rounded-full"><?= count($pendingDrivers) ?></span>
            <span class="text-sm font-semibold text-yellow-800">승인 대기</span>
        </div>
        <div class="list-table-container">
            <table class="list-table-compact">
                <thead>
                    <tr>
                        <th style="width:100px;">이름</th>
                        <th style="width:120px;">연락처</th>
                        <th>신청건물</th>
                        <th style="width:130px;">신청일</th>
                        <th style="width:100px;" class="text-center">승인/거절</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingDrivers as $driver): ?>
                        <tr>
                            <td class="font-medium"><?= esc($driver['driver_name']) ?></td>
                            <td><?= esc($driver['phone'] ?? '-') ?></td>
                            <td>
                                <?php
                                $buildingName = '-';
                                if (!empty($driver['building_id'])) {
                                    foreach ($buildings as $b) {
                                        if ($b['id'] == $driver['building_id']) {
                                            $buildingName = $b['building_name'];
                                            break;
                                        }
                                    }
                                }
                                echo esc($buildingName);
                                ?>
                            </td>
                            <td class="text-gray-400"><?= date('Y-m-d H:i', strtotime($driver['created_at'] ?? 'now')) ?></td>
                            <td class="text-center"><span style="display:inline-flex;gap:4px;"><button onclick="approveDriver(<?= $driver['id'] ?>)" class="px-2 py-0.5 text-xs text-white bg-green-500 rounded hover:bg-green-600">승인</button><button onclick="rejectDriver(<?= $driver['id'] ?>)" class="px-2 py-0.5 text-xs text-white bg-red-500 rounded hover:bg-red-600">거절</button></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- 콘텐츠 영역 -->
<div class="list-page-container">
    <div class="mb-2 text-xs text-gray-600">
        총 <span class="font-semibold"><?= count($activeDrivers) + count($inactiveDrivers) ?></span>명
    </div>

    <?php if (empty($activeDrivers) && empty($inactiveDrivers)): ?>
        <div class="py-12 text-center text-gray-500 text-sm bg-white border border-gray-200 rounded">
            등록된 기사가 없습니다. QR 코드를 기사님에게 공유하여 등록을 받으세요.
        </div>
    <?php else: ?>
        <div class="list-table-container">
            <table class="list-table-compact">
                <thead>
                    <tr>
                        <th style="width:80px;" class="text-center">기사코드</th>
                        <th style="width:100px;">이름</th>
                        <th style="width:120px;">연락처</th>
                        <th>담당 건물</th>
                        <th style="width:50px;" class="text-center">상태</th>
                        <th class="text-center">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_merge($activeDrivers, $inactiveDrivers) as $driver): ?>
                        <tr>
                            <td class="text-center"><span style="font-family:monospace;color:#2563eb;"><?= esc($driver['driver_code']) ?></span></td>
                            <td class="font-medium"><?= esc($driver['driver_name']) ?></td>
                            <td><?= esc($driver['phone'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($driver['buildings'])): ?>
                                    <?php foreach ($driver['buildings'] as $building): ?>
                                        <span class="inline-block px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded mr-1"><?= esc($building['building_name']) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">미배정</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($driver['status'] === 'active'): ?>
                                    <span class="status-badge" style="background:#dcfce7;color:#166534;">활성</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background:#f3f4f6;color:#6b7280;">비활성</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span style="display:inline-flex;gap:6px;flex-wrap:nowrap;"><button onclick="openBuildingsModal(<?= $driver['id'] ?>, '<?= esc($driver['driver_name']) ?>', <?= htmlspecialchars(json_encode(array_column($driver['buildings'] ?? [], 'id'))) ?>)" class="text-blue-500 hover:underline text-xs">배정</button><?php if ($driver['status'] === 'active'): ?><button onclick="toggleStatus(<?= $driver['id'] ?>, 'inactive')" class="text-yellow-600 hover:text-yellow-700 text-xs">OFF</button><?php else: ?><button onclick="toggleStatus(<?= $driver['id'] ?>, 'active')" class="text-green-600 hover:text-green-700 text-xs">ON</button><?php endif; ?><button onclick="deleteDriver(<?= $driver['id'] ?>)" class="text-red-500 hover:text-red-600 text-xs">삭제</button></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 건물 배정 모달 -->
<div id="buildingsModal" class="modal-overlay hidden">
    <div class="modal-content" style="width:400px;max-width:90vw;">
        <div class="modal-header">
            <h3 id="buildingsModalTitle" class="text-base font-semibold">담당 건물 배정</h3>
            <button onclick="closeBuildingsModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form id="buildingsForm" method="post" class="p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="driver_id" id="assignDriverId">
            <div class="max-h-64 overflow-y-auto">
                <?php foreach ($buildings as $building): ?>
                    <label class="flex items-center gap-2 p-2 border border-gray-200 rounded mb-2 cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="building_ids[]" value="<?= $building['id'] ?>" class="building-checkbox w-4 h-4">
                        <span class="text-sm text-gray-700"><?= esc($building['building_name']) ?></span>
                    </label>
                <?php endforeach; ?>
                <?php if (empty($buildings)): ?>
                    <p class="text-center text-gray-400 text-sm py-4">등록된 건물이 없습니다.</p>
                <?php endif; ?>
            </div>
        </form>
        <div class="flex gap-2 justify-end p-4 border-t border-gray-200">
            <button type="button" onclick="closeBuildingsModal()" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">취소</button>
            <button type="submit" form="buildingsForm" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600">저장</button>
        </div>
    </div>
</div>

<style>
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal-overlay.hidden { display:none; }
.modal-content { background:#fff; border-radius:8px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); max-height:90vh; overflow:hidden; display:flex; flex-direction:column; }
.modal-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid #e5e7eb; }
</style>

<script>
function approveDriver(id) {
    if (confirm('이 기사를 승인하시겠습니까?')) {
        location.href = '/mailroom/drivers/approve/' + id;
    }
}

function rejectDriver(id) {
    if (confirm('이 기사 등록을 거절하시겠습니까?')) {
        location.href = '/mailroom/drivers/reject/' + id;
    }
}

function toggleStatus(id, status) {
    location.href = '/mailroom/drivers/status/' + id + '/' + status;
}

function deleteDriver(id) {
    if (confirm('이 기사를 삭제하시겠습니까?')) {
        location.href = '/mailroom/drivers/delete/' + id;
    }
}

function openBuildingsModal(driverId, driverName, assignedBuildingIds) {
    document.getElementById('buildingsModalTitle').textContent = driverName + ' - 담당 건물 배정';
    document.getElementById('assignDriverId').value = driverId;
    document.getElementById('buildingsForm').action = '/mailroom/drivers/assign-buildings/' + driverId;

    document.querySelectorAll('.building-checkbox').forEach(checkbox => {
        checkbox.checked = assignedBuildingIds.includes(parseInt(checkbox.value));
    });

    document.getElementById('buildingsModal').classList.remove('hidden');
}

function closeBuildingsModal() {
    document.getElementById('buildingsModal').classList.add('hidden');
}

document.getElementById('buildingsModal').addEventListener('click', function(e) {
    if (e.target === this) closeBuildingsModal();
});
</script>
<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>