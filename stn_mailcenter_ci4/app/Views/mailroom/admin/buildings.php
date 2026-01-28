<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<!-- 헤더 영역 -->
<div class="page-header-section mb-4 px-3 py-3 bg-white rounded-lg border border-gray-200 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-800">건물 관리</h1>
            <p class="text-xs text-gray-500">메일룸 서비스에 등록된 건물을 관리합니다.</p>
        </div>
        <button onclick="openAddModal()" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600">+ 건물 추가</button>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="mb-3 px-3 py-2 text-xs bg-green-50 border border-green-200 text-green-700 rounded"><?= session()->getFlashdata('message') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-3 px-3 py-2 text-xs bg-red-50 border border-red-200 text-red-700 rounded"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
</div>

<!-- 콘텐츠 영역 -->
<div class="list-page-container">
    <div class="mb-2 text-xs text-gray-600">
        총 <span class="font-semibold"><?= count($buildings) ?></span>건
    </div>

    <?php if (empty($buildings)): ?>
        <div class="py-12 text-center text-gray-500 text-sm bg-white border border-gray-200 rounded">
            등록된 건물이 없습니다. '+ 건물 추가' 버튼으로 건물을 등록하세요.
        </div>
    <?php else: ?>
        <div class="list-table-container">
            <table class="list-table-compact">
                <thead>
                    <tr>
                        <th style="width:80px;" class="text-center">건물코드</th>
                        <th style="width:140px;">건물명</th>
                        <th>주소</th>
                        <th style="width:60px;" class="text-center">상태</th>
                        <th style="width:60px;" class="text-center">기사QR</th>
                        <th style="width:60px;" class="text-center">층관리</th>
                        <th class="text-center">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buildings as $building): ?>
                        <tr>
                            <td class="text-center"><span style="font-family:monospace;color:#2563eb;"><?= esc($building['building_code']) ?></span></td>
                            <td><?= esc($building['building_name']) ?></td>
                            <td><?= esc($building['address'] ?? '-') ?></td>
                            <td class="text-center">
                                <?php if ($building['status'] === 'active'): ?>
                                    <span class="status-badge" style="background:#dcfce7;color:#166534;">활성</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background:#f3f4f6;color:#6b7280;">비활성</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><button onclick="openQrModal(<?= $building['id'] ?>, '<?= esc($building['building_name']) ?>')" class="text-blue-500 hover:underline text-xs">QR보기</button></td>
                            <td class="text-center"><button onclick="openFloorsModal(<?= $building['id'] ?>, '<?= esc($building['building_name']) ?>')" class="text-blue-500 hover:underline text-xs">층관리</button></td>
                            <td class="text-center"><span style="display:inline-flex;gap:6px;flex-wrap:nowrap;"><button onclick="openEditModal(<?= htmlspecialchars(json_encode($building)) ?>)" class="text-gray-500 hover:text-blue-500 text-xs">수정</button><button onclick="deleteBuilding(<?= $building['id'] ?>)" class="text-gray-500 hover:text-red-500 text-xs">삭제</button></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 건물 추가/수정 모달 -->
<div id="buildingModal" class="modal-overlay hidden">
    <div class="modal-content" style="width:400px;max-width:90vw;">
        <div class="modal-header">
            <h3 id="modalTitle" class="text-base font-semibold">건물 추가</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form id="buildingForm" method="post" class="p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="buildingId">
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">건물코드 <span class="text-red-500">*</span></label>
                <input type="text" name="building_code" id="buildingCode" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:border-blue-500 focus:outline-none" placeholder="예: STN">
            </div>
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">건물명 <span class="text-red-500">*</span></label>
                <input type="text" name="building_name" id="buildingName" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:border-blue-500 focus:outline-none" placeholder="예: STN타워">
            </div>
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">주소</label>
                <input type="text" name="address" id="buildingAddress" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:border-blue-500 focus:outline-none" placeholder="주소 입력">
            </div>
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">상태</label>
                <select name="status" id="buildingStatus" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:border-blue-500 focus:outline-none">
                    <option value="active">활성</option>
                    <option value="inactive">비활성</option>
                </select>
            </div>
        </form>
        <div class="flex gap-2 justify-end p-4 border-t border-gray-200">
            <button type="button" onclick="closeModal()" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">취소</button>
            <button type="submit" form="buildingForm" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600">저장</button>
        </div>
    </div>
</div>

<!-- 기사 등록 QR 모달 -->
<div id="qrModal" class="modal-overlay hidden">
    <div class="modal-content" style="width:360px;max-width:90vw;">
        <div class="modal-header">
            <h3 id="qrModalTitle" class="text-base font-semibold">기사 등록 QR 코드</h3>
            <button onclick="closeQrModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <div class="p-4 text-center">
            <div class="inline-block p-3 bg-white border border-gray-200 rounded mb-3">
                <img id="qrCodeImage" src="" alt="QR Code" style="width:160px;height:160px;">
            </div>
            <p class="text-xs text-gray-500 mb-1">기사님이 이 QR코드를 스캔하여 직접 등록할 수 있습니다.</p>
            <p class="text-xs text-gray-400 break-all" id="registerUrl"></p>
        </div>
        <div class="p-4 border-t border-gray-200">
            <button onclick="closeQrModal()" class="w-full px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">닫기</button>
        </div>
    </div>
</div>

<!-- 층 관리 모달 -->
<div id="floorsModal" class="modal-overlay hidden">
    <div class="modal-content" style="width:450px;max-width:90vw;">
        <div class="modal-header">
            <h3 id="floorsModalTitle" class="text-base font-semibold">층 관리</h3>
            <button onclick="closeFloorsModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <div class="p-4">
            <div id="floorsList" class="mb-3 max-h-64 overflow-y-auto"></div>
            <div class="flex gap-2">
                <input type="text" id="newFloorName" class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded focus:border-blue-500 focus:outline-none" placeholder="층 이름 (예: 1층, B1)">
                <button onclick="addFloor()" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600">추가</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal-overlay.hidden { display:none; }
.modal-content { background:#fff; border-radius:8px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); max-height:90vh; overflow:hidden; display:flex; flex-direction:column; }
.modal-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid #e5e7eb; }
.floor-item { display:flex; justify-content:space-between; align-items:center; padding:8px 10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:4px; margin-bottom:6px; font-size:13px; }
.floors-empty { text-align:center; padding:16px; color:#9ca3af; font-size:12px; }
</style>

<script>
let currentBuildingId = null;

function openQrModal(buildingId, buildingName) {
    const registerUrl = window.location.origin + '/mailroom/driver-register/' + buildingId;
    const qrUrl = '/mailroom/qr/driver-register/' + buildingId;
    document.getElementById('qrModalTitle').textContent = buildingName + ' - 기사 등록 QR';
    document.getElementById('qrCodeImage').src = qrUrl;
    document.getElementById('registerUrl').textContent = registerUrl;
    document.getElementById('qrModal').classList.remove('hidden');
}

function closeQrModal() {
    document.getElementById('qrModal').classList.add('hidden');
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = '건물 추가';
    document.getElementById('buildingForm').action = '/mailroom/buildings/store';
    document.getElementById('buildingId').value = '';
    document.getElementById('buildingCode').value = '';
    document.getElementById('buildingName').value = '';
    document.getElementById('buildingAddress').value = '';
    document.getElementById('buildingStatus').value = 'active';
    document.getElementById('buildingModal').classList.remove('hidden');
}

function openEditModal(building) {
    document.getElementById('modalTitle').textContent = '건물 수정';
    document.getElementById('buildingForm').action = '/mailroom/buildings/update/' + building.id;
    document.getElementById('buildingId').value = building.id;
    document.getElementById('buildingCode').value = building.building_code;
    document.getElementById('buildingName').value = building.building_name;
    document.getElementById('buildingAddress').value = building.address || '';
    document.getElementById('buildingStatus').value = building.status;
    document.getElementById('buildingModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('buildingModal').classList.add('hidden');
}

function deleteBuilding(id) {
    if (confirm('이 건물을 삭제하시겠습니까?')) {
        location.href = '/mailroom/buildings/delete/' + id;
    }
}

function openFloorsModal(buildingId, buildingName) {
    currentBuildingId = buildingId;
    document.getElementById('floorsModalTitle').textContent = buildingName + ' - 층 관리';
    loadFloors(buildingId);
    document.getElementById('floorsModal').classList.remove('hidden');
}

function closeFloorsModal() {
    document.getElementById('floorsModal').classList.add('hidden');
}

function loadFloors(buildingId) {
    fetch('/mailroom/floors/' + buildingId)
        .then(response => response.json())
        .then(floors => {
            const container = document.getElementById('floorsList');
            if (floors.length === 0) {
                container.innerHTML = '<div class="floors-empty">등록된 층이 없습니다.</div>';
            } else {
                container.innerHTML = floors.map(floor => `
                    <div class="floor-item">
                        <span>${floor.floor_name}</span>
                        <button onclick="deleteFloor(${floor.id})" class="text-red-500 hover:underline text-xs">삭제</button>
                    </div>
                `).join('');
            }
        });
}

function addFloor() {
    const name = document.getElementById('newFloorName').value.trim();
    if (!name) { alert('층 이름을 입력해주세요.'); return; }

    fetch('/mailroom/floors/store', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ building_id: currentBuildingId, floor_name: name })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            document.getElementById('newFloorName').value = '';
            loadFloors(currentBuildingId);
        } else {
            alert(result.message || '추가 실패');
        }
    });
}

function deleteFloor(floorId) {
    if (confirm('이 층을 삭제하시겠습니까?')) {
        fetch('/mailroom/floors/delete/' + floorId, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                loadFloors(currentBuildingId);
            } else {
                alert(result.message || '삭제 실패');
            }
        });
    }
}

document.getElementById('buildingModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.getElementById('floorsModal').addEventListener('click', function(e) { if (e.target === this) closeFloorsModal(); });
document.getElementById('qrModal').addEventListener('click', function(e) { if (e.target === this) closeQrModal(); });
</script>
<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>