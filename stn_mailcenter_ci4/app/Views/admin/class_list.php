<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">

    <!-- 헤더 -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">권한(user_class) 관리</h2>
            <p class="text-xs text-gray-500 mt-1">사용자 권한 정보를 관리합니다. 여기서 등록한 권한은 사용자 등록 시 선택할 수 있습니다.</p>
        </div>
        <a href="<?= base_url('admin/class-form') ?>" class="px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition">
            <i class="fas fa-plus mr-1"></i> 권한 등록
        </a>
    </div>

    <!-- 권한 테이블 -->
    <div class="bg-white rounded border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-b" style="width:100px;">권한 ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b" style="width:150px;">권한명</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b">권한 설명</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-b" style="width:100px;">권한 레벨</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-b" style="width:80px;">상태</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-b" style="width:150px;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($classes)): ?>
                    <?php foreach ($classes as $class): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3 text-center font-mono font-bold text-blue-600"><?= esc($class['class_id']) ?></td>
                        <td class="px-4 py-3 font-medium text-gray-800"><?= esc($class['class_name']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= esc($class['class_desc'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center text-gray-700"><?= esc($class['permission_level']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($class['is_active']): ?>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">활성</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">비활성</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex gap-2">
                                <a href="<?= base_url('admin/class-form/' . $class['class_id']) ?>"
                                   class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition">
                                    수정
                                </a>
                                <button type="button" onclick="deleteClass(<?= $class['class_id'] ?>, '<?= esc($class['class_name']) ?>')"
                                        class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition">
                                    삭제
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            등록된 권한이 없습니다.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 안내사항 -->
    <div class="mt-4 p-3 bg-yellow-50 rounded border border-yellow-200">
        <h4 class="text-xs font-semibold text-yellow-900 mb-1">주의사항</h4>
        <ul class="text-xs text-yellow-800 space-y-1">
            <li>• 권한 ID는 수동으로 관리됩니다. 기존 코드와 충돌하지 않도록 주의하세요.</li>
            <li>• 해당 권한을 사용하는 사용자가 있으면 삭제할 수 없습니다.</li>
            <li>• 권한 레벨은 숫자가 낮을수록 높은 권한을 의미합니다.</li>
        </ul>
    </div>
</div>

<script>
function deleteClass(classId, className) {
    if (!confirm(`권한 "${className}" (ID: ${classId})을(를) 삭제하시겠습니까?\n\n이 권한을 사용하는 사용자가 있으면 삭제할 수 없습니다.`)) {
        return;
    }

    const formData = new FormData();
    formData.append('class_id', classId);

    fetch('<?= base_url('admin/class-delete') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '삭제 실패');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    });
}
</script>

<?= $this->endSection() ?>
