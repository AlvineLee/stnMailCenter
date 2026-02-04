<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">

    <!-- 설명 -->
    <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-semibold text-blue-900 mb-1">시스템 설정 안내</h3>
        <p class="text-xs text-blue-800">로그인 보안 설정, 배송사유, 권한 관리 등을 할 수 있습니다.</p>
    </div>

    <!-- 2컬럼 레이아웃 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- 좌측: 로그인 보안 설정 -->
        <div class="bg-blue-50 rounded-lg border-2 border-blue-200 p-4">
            <h3 class="text-sm font-semibold text-blue-900 mb-3">로그인 실패 시 접근제한 설정</h3>

            <form id="settingsForm">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="login_max_attempts" class="text-sm text-gray-700">최대 로그인 시도 횟수</label>
                        <div class="flex items-center gap-1">
                            <input type="number" id="login_max_attempts" name="login_max_attempts"
                                   value="<?= esc($settings['login_max_attempts'] ?? 5) ?>"
                                   min="1" max="20" class="form-input w-16 text-center text-sm" required>
                            <span class="text-xs text-gray-500">회</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label for="login_lockout_minutes" class="text-sm text-gray-700">로그인 잠금 시간</label>
                        <div class="flex items-center gap-1">
                            <input type="number" id="login_lockout_minutes" name="login_lockout_minutes"
                                   value="<?= esc($settings['login_lockout_minutes'] ?? 5) ?>"
                                   min="1" max="60" class="form-input w-16 text-center text-sm" required>
                            <span class="text-xs text-gray-500">분</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label for="login_attempt_window" class="text-sm text-gray-700">시도 횟수 체크 시간</label>
                        <div class="flex items-center gap-1">
                            <input type="number" id="login_attempt_window" name="login_attempt_window"
                                   value="<?= esc($settings['login_attempt_window'] ?? 30) ?>"
                                   min="5" max="120" class="form-input w-16 text-center text-sm" required>
                            <span class="text-xs text-gray-500">분</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-2 bg-yellow-50 rounded border border-yellow-200">
                    <p class="text-xs text-yellow-700">예: 5회, 5분, 30분 설정 시 → 30분 내 5회 실패하면 5분간 잠금</p>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">설정 저장</button>
                </div>
            </form>
        </div>

        <!-- 우측: 배송사유 설정 -->
        <div class="bg-blue-50 rounded-lg border-2 border-blue-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-blue-900">배송사유 설정</h3>
                <button type="button" onclick="openDeliveryReasonModal()"
                        class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition">+ 추가</button>
            </div>

            <!-- 배송사유 테이블 (헤더 고정 + 바디 스크롤) -->
            <div class="bg-white rounded border border-gray-200">
                <!-- 고정 헤더 -->
                <table class="w-full text-xs table-fixed">
                    <thead class="bg-gray-100">
                        <tr style="height:20px;">
                            <th class="px-2 text-center font-medium text-gray-700" style="width:50px; padding:3px 8px; font-size:11px;">코드</th>
                            <th class="px-2 text-left font-medium text-gray-700" style="width:100px; padding:3px 8px; font-size:11px;">배송사유명</th>
                            <th class="px-2 text-center font-medium text-gray-700" style="width:45px; padding:3px 8px; font-size:11px;">순서</th>
                            <th class="px-2 text-center font-medium text-gray-700" style="width:45px; padding:3px 8px; font-size:11px;">상태</th>
                            <th class="px-2 text-center font-medium text-gray-700" style="width:90px; padding:3px 8px; font-size:11px;">관리</th>
                        </tr>
                    </thead>
                </table>
                <!-- 스크롤 바디 -->
                <div class="overflow-auto" style="max-height: 150px;">
                    <table class="w-full text-xs table-fixed">
                        <tbody>
                            <?php if (!empty($delivery_reasons)): ?>
                                <?php foreach ($delivery_reasons as $reason): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50" style="height:15pt; line-height:15pt;">
                                    <td class="px-2 text-center" style="width:50px; padding:2px 8px; font-size:12px;"><?= esc($reason['reason_code']) ?></td>
                                    <td class="px-2 truncate" style="width:100px; padding:2px 8px; font-size:12px;"><?= esc($reason['reason_name']) ?></td>
                                    <td class="px-2 text-center" style="width:45px; padding:2px 8px; font-size:12px;"><?= esc($reason['sort_order']) ?></td>
                                    <td class="px-2 text-center" style="width:45px; padding:2px 8px;">
                                        <span class="px-1 rounded" style="font-size:10px; <?= $reason['is_active'] === 'Y' ? 'background:#dcfce7; color:#15803d;' : 'background:#e5e7eb; color:#6b7280;' ?>"><?= $reason['is_active'] ?></span>
                                    </td>
                                    <td class="px-2 text-center" style="width:90px; padding:2px 8px;">
                                        <div class="inline-flex gap-1">
                                            <button type="button" onclick="editDeliveryReason(<?= esc($reason['id']) ?>, '<?= esc($reason['reason_code']) ?>', '<?= esc($reason['reason_name']) ?>', <?= esc($reason['sort_order']) ?>, '<?= esc($reason['is_active']) ?>')" style="padding:2px 6px; font-size:11px; height:20px; min-width:40px;" class="bg-blue-500 text-white rounded hover:bg-blue-600">수정</button>
                                            <button type="button" onclick="deleteDeliveryReason(<?= esc($reason['id']) ?>, '<?= esc($reason['reason_name']) ?>')" style="padding:2px 6px; font-size:11px; height:20px; min-width:40px;" class="bg-red-500 text-white rounded hover:bg-red-600">삭제</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="px-2 py-4 text-center text-gray-500">등록된 배송사유가 없습니다.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="mt-2 text-xs text-gray-600">오더유형에서 거래처별 "배송사유 사용" Y 설정 시 주문 접수에 적용됩니다.</p>
        </div>

    </div>

    <!-- 권한 관리 섹션 -->
    <div class="mt-4 bg-blue-50 rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-blue-900">권한(user_class) 관리</h3>
            <button type="button" onclick="openClassModal()"
                    class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition">+ 추가</button>
        </div>

        <!-- 권한 테이블 (헤더 고정 + 바디 스크롤) -->
        <div class="bg-white rounded border border-gray-200">
            <!-- 고정 헤더 -->
            <table class="w-full text-xs table-fixed">
                <thead class="bg-gray-100">
                    <tr style="height:20px;">
                        <th class="px-2 text-center font-medium text-gray-700" style="width:60px; padding:3px 8px; font-size:11px;">ID</th>
                        <th class="px-2 text-left font-medium text-gray-700" style="width:120px; padding:3px 8px; font-size:11px;">권한명</th>
                        <th class="px-2 text-left font-medium text-gray-700" style="padding:3px 8px; font-size:11px;">설명</th>
                        <th class="px-2 text-center font-medium text-gray-700" style="width:60px; padding:3px 8px; font-size:11px;">레벨</th>
                        <th class="px-2 text-center font-medium text-gray-700" style="width:45px; padding:3px 8px; font-size:11px;">상태</th>
                        <th class="px-2 text-center font-medium text-gray-700" style="width:90px; padding:3px 8px; font-size:11px;">관리</th>
                    </tr>
                </thead>
            </table>
            <!-- 스크롤 바디 -->
            <div class="overflow-auto" style="max-height: 150px;">
                <table class="w-full text-xs table-fixed">
                    <tbody>
                        <?php if (!empty($classes)): ?>
                            <?php foreach ($classes as $class): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50" style="height:15pt; line-height:15pt;">
                                <td class="px-2 text-center font-mono font-bold text-blue-600" style="width:60px; padding:2px 8px; font-size:12px;"><?= esc($class['class_id']) ?></td>
                                <td class="px-2 truncate" style="width:120px; padding:2px 8px; font-size:12px;"><?= esc($class['class_name']) ?></td>
                                <td class="px-2 truncate" style="padding:2px 8px; font-size:12px;"><?= esc($class['class_desc'] ?? '-') ?></td>
                                <td class="px-2 text-center" style="width:60px; padding:2px 8px; font-size:12px;"><?= esc($class['permission_level']) ?></td>
                                <td class="px-2 text-center" style="width:45px; padding:2px 8px;">
                                    <span class="px-1 rounded" style="font-size:10px; <?= $class['is_active'] ? 'background:#dcfce7; color:#15803d;' : 'background:#e5e7eb; color:#6b7280;' ?>"><?= $class['is_active'] ? 'Y' : 'N' ?></span>
                                </td>
                                <td class="px-2 text-center" style="width:90px; padding:2px 8px;">
                                    <div class="inline-flex gap-1">
                                        <button type="button" onclick='editClass(<?= json_encode($class, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' style="padding:2px 6px; font-size:11px; height:20px; min-width:40px;" class="bg-blue-500 text-white rounded hover:bg-blue-600">수정</button>
                                        <button type="button" onclick="deleteClass(<?= esc($class['class_id']) ?>, '<?= esc($class['class_name']) ?>')" style="padding:2px 6px; font-size:11px; height:20px; min-width:40px;" class="bg-red-500 text-white rounded hover:bg-red-600">삭제</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-2 py-4 text-center text-gray-500">등록된 권한이 없습니다.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-2 text-xs text-gray-600">tbl_users_list의 user_class 필드에 사용되는 권한 정보입니다.</p>
    </div>
</div>

<!-- 배송사유 추가/수정 모달 -->
<div id="deliveryReasonModal" class="fixed inset-0 hidden flex items-center justify-center p-4" style="z-index: 9999; background: rgba(0, 0, 0, 0.5);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm" onclick="event.stopPropagation()">
        <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
            <h3 id="deliveryReasonModalTitle" class="text-base font-semibold text-gray-800">배송사유 추가</h3>
            <button type="button" onclick="closeDeliveryReasonModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="deliveryReasonForm" onsubmit="submitDeliveryReason(event)">
            <input type="hidden" id="reason_id" name="id" value="">
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="reason_code" class="block text-xs font-medium text-gray-700 mb-1">코드 <span class="text-red-500">*</span></label>
                        <input type="text" id="reason_code" name="reason_code" maxlength="20" required class="form-input w-full text-sm" placeholder="01">
                    </div>
                    <div>
                        <label for="sort_order" class="block text-xs font-medium text-gray-700 mb-1">정렬순서</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="0" class="form-input w-full text-sm">
                    </div>
                </div>
                <div>
                    <label for="reason_name" class="block text-xs font-medium text-gray-700 mb-1">배송사유명 <span class="text-red-500">*</span></label>
                    <input type="text" id="reason_name" name="reason_name" maxlength="100" required class="form-input w-full text-sm" placeholder="계약서/견적서">
                </div>
                <div>
                    <label for="is_active" class="block text-xs font-medium text-gray-700 mb-1">사용여부</label>
                    <select id="is_active" name="is_active" class="form-input w-24 text-sm">
                        <option value="Y">사용</option>
                        <option value="N">미사용</option>
                    </select>
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="closeDeliveryReasonModal()" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 transition">취소</button>
                <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">저장</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('<?= base_url('admin/save-settings') ?>', {
        method: 'POST',
        body: new FormData(this),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => alert(data.message || (data.success ? '저장되었습니다.' : '저장 실패')))
    .catch(() => alert('설정 저장 중 오류가 발생했습니다.'));
});

let isEditMode = false;

function openDeliveryReasonModal() {
    isEditMode = false;
    document.getElementById('deliveryReasonModalTitle').textContent = '배송사유 추가';
    document.getElementById('deliveryReasonForm').reset();
    document.getElementById('reason_id').value = '';
    document.getElementById('deliveryReasonModal').classList.remove('hidden');
    document.getElementById('deliveryReasonModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function editDeliveryReason(id, code, name, sortOrder, isActive) {
    isEditMode = true;
    document.getElementById('deliveryReasonModalTitle').textContent = '배송사유 수정';
    document.getElementById('reason_id').value = id;
    document.getElementById('reason_code').value = code;
    document.getElementById('reason_name').value = name;
    document.getElementById('sort_order').value = sortOrder;
    document.getElementById('is_active').value = isActive;
    document.getElementById('deliveryReasonModal').classList.remove('hidden');
    document.getElementById('deliveryReasonModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeDeliveryReasonModal() {
    document.getElementById('deliveryReasonModal').classList.add('hidden');
    document.getElementById('deliveryReasonModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
}

function submitDeliveryReason(e) {
    e.preventDefault();
    const url = isEditMode ? '<?= base_url('admin/updateDeliveryReason') ?>' : '<?= base_url('admin/addDeliveryReason') ?>';
    fetch(url, {
        method: 'POST',
        body: new FormData(document.getElementById('deliveryReasonForm')),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) { alert(isEditMode ? '수정되었습니다.' : '추가되었습니다.'); closeDeliveryReasonModal(); location.reload(); }
        else { alert(data.message || '처리 실패'); }
    })
    .catch(() => alert('처리 중 오류가 발생했습니다.'));
}

function deleteDeliveryReason(id, name) {
    if (!confirm(`"${name}" 삭제하시겠습니까?`)) return;
    const formData = new FormData();
    formData.append('id', id);
    fetch('<?= base_url('admin/deleteDeliveryReason') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => { if (data.success) { alert('삭제되었습니다.'); location.reload(); } else { alert(data.message || '삭제 실패'); } })
    .catch(() => alert('삭제 중 오류가 발생했습니다.'));
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDeliveryReasonModal(); });
document.getElementById('deliveryReasonModal').addEventListener('click', e => { if (e.target === document.getElementById('deliveryReasonModal')) closeDeliveryReasonModal(); });

// ========== 권한 관리 JavaScript ==========
let isClassEditMode = false;

function openClassModal() {
    isClassEditMode = false;
    document.getElementById('classModalTitle').textContent = '권한 추가';
    document.getElementById('classForm').reset();
    document.getElementById('class_id').value = '';
    document.getElementById('original_class_id').value = '';
    document.getElementById('classModal').classList.remove('hidden');
    document.getElementById('classModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function editClass(classData) {
    isClassEditMode = true;
    document.getElementById('classModalTitle').textContent = '권한 수정';
    document.getElementById('class_id').value = classData.class_id;
    document.getElementById('original_class_id').value = classData.class_id;
    document.getElementById('class_name').value = classData.class_name;
    document.getElementById('class_desc').value = classData.class_desc || '';
    document.getElementById('permission_level').value = classData.permission_level;
    document.getElementById('class_is_active').value = classData.is_active ? '1' : '0';
    document.getElementById('classModal').classList.remove('hidden');
    document.getElementById('classModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeClassModal() {
    document.getElementById('classModal').classList.add('hidden');
    document.getElementById('classModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
}

function submitClass(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('classForm'));
    formData.append('is_edit', isClassEditMode ? '1' : '0');

    fetch('<?= base_url('admin/class-save') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(isClassEditMode ? '수정되었습니다.' : '추가되었습니다.');
            closeClassModal();
            location.reload();
        }
        else { alert(data.message || '처리 실패'); }
    })
    .catch(() => alert('처리 중 오류가 발생했습니다.'));
}

function deleteClass(classId, className) {
    if (!confirm(`"${className}" (ID: ${classId}) 권한을 삭제하시겠습니까?\n\n이 권한을 사용하는 사용자가 있으면 삭제할 수 없습니다.`)) return;
    const formData = new FormData();
    formData.append('class_id', classId);
    fetch('<?= base_url('admin/class-delete') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => { if (data.success) { alert('삭제되었습니다.'); location.reload(); } else { alert(data.message || '삭제 실패'); } })
    .catch(() => alert('삭제 중 오류가 발생했습니다.'));
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeClassModal(); });
</script>

<!-- 권한 추가/수정 모달 -->
<div id="classModal" class="fixed inset-0 hidden flex items-center justify-center p-4" style="z-index: 9999; background: rgba(0, 0, 0, 0.5);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md" onclick="event.stopPropagation()">
        <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
            <h3 id="classModalTitle" class="text-base font-semibold text-gray-800">권한 추가</h3>
            <button type="button" onclick="closeClassModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="classForm" onsubmit="submitClass(event)">
            <input type="hidden" id="original_class_id" name="original_class_id" value="">
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="class_id" class="block text-xs font-medium text-gray-700 mb-1">권한 ID <span class="text-red-500">*</span></label>
                        <input type="number" id="class_id" name="class_id" required class="form-input w-full text-sm" placeholder="1">
                    </div>
                    <div>
                        <label for="permission_level" class="block text-xs font-medium text-gray-700 mb-1">권한 레벨 <span class="text-red-500">*</span></label>
                        <input type="number" id="permission_level" name="permission_level" min="0" value="50" required class="form-input w-full text-sm">
                    </div>
                </div>
                <div>
                    <label for="class_name" class="block text-xs font-medium text-gray-700 mb-1">권한명 <span class="text-red-500">*</span></label>
                    <input type="text" id="class_name" name="class_name" maxlength="50" required class="form-input w-full text-sm" placeholder="메일룸 담당자">
                </div>
                <div>
                    <label for="class_desc" class="block text-xs font-medium text-gray-700 mb-1">권한 설명</label>
                    <textarea id="class_desc" name="class_desc" rows="2" class="form-input w-full text-sm" placeholder="이 권한에 대한 설명"></textarea>
                </div>
                <div>
                    <label for="class_is_active" class="block text-xs font-medium text-gray-700 mb-1">사용여부</label>
                    <select id="class_is_active" name="is_active" class="form-input w-24 text-sm">
                        <option value="1">사용</option>
                        <option value="0">미사용</option>
                    </select>
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="closeClassModal()" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 transition">취소</button>
                <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">저장</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>