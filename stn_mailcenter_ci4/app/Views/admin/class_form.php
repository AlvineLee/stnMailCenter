<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-2xl mx-auto">

    <!-- 헤더 -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800"><?= $isEdit ? '권한 수정' : '권한 등록' ?></h2>
        <p class="text-xs text-gray-500 mt-1">사용자 권한 정보를 <?= $isEdit ? '수정' : '등록' ?>합니다.</p>
    </div>

    <!-- 폼 -->
    <form action="<?= base_url('admin/class-save') ?>" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="is_edit" value="<?= $isEdit ? '1' : '0' ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="original_class_id" value="<?= esc($classInfo['class_id']) ?>">
        <?php endif; ?>

        <div class="space-y-4">
            <!-- 권한 ID -->
            <div>
                <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">
                    권한 ID <span class="text-red-500">*</span>
                </label>
                <input type="number" id="class_id" name="class_id"
                       value="<?= old('class_id', $classInfo['class_id'] ?? '') ?>"
                       class="form-input w-full text-sm"
                       placeholder="예: 1, 2, 3, 9 등"
                       required
                       <?= $isEdit ? '' : '' ?>>
                <p class="mt-1 text-xs text-gray-500">
                    <?= $isEdit ? '권한 ID를 변경할 수 있습니다. (기존 데이터는 삭제 후 새로 생성됩니다)' : 'tbl_users_list의 user_class 필드에 사용될 값입니다. 중복되지 않는 값을 입력하세요.' ?>
                </p>
            </div>

            <!-- 권한명 -->
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700 mb-1">
                    권한명 <span class="text-red-500">*</span>
                </label>
                <input type="text" id="class_name" name="class_name"
                       value="<?= old('class_name', $classInfo['class_name'] ?? '') ?>"
                       class="form-input w-full text-sm"
                       placeholder="예: 거래처 관리자, 메일룸 담당자 등"
                       maxlength="50"
                       required>
            </div>

            <!-- 권한 설명 -->
            <div>
                <label for="class_desc" class="block text-sm font-medium text-gray-700 mb-1">
                    권한 설명
                </label>
                <textarea id="class_desc" name="class_desc"
                          class="form-input w-full text-sm"
                          placeholder="이 권한에 대한 설명을 입력하세요"
                          rows="3"><?= old('class_desc', $classInfo['class_desc'] ?? '') ?></textarea>
            </div>

            <!-- 권한 레벨 -->
            <div>
                <label for="permission_level" class="block text-sm font-medium text-gray-700 mb-1">
                    권한 레벨 <span class="text-red-500">*</span>
                </label>
                <input type="number" id="permission_level" name="permission_level"
                       value="<?= old('permission_level', $classInfo['permission_level'] ?? 50) ?>"
                       class="form-input w-full text-sm"
                       placeholder="예: 10, 20, 30 등"
                       min="0"
                       required>
                <p class="mt-1 text-xs text-gray-500">숫자가 낮을수록 높은 권한을 의미합니다. (예: 슈퍼관리자=10, 일반사용자=50)</p>
            </div>

            <!-- 활성화 여부 -->
            <div>
                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">
                    활성화 여부 <span class="text-red-500">*</span>
                </label>
                <select id="is_active" name="is_active" class="form-input w-full text-sm" required>
                    <option value="1" <?= old('is_active', $classInfo['is_active'] ?? '1') == '1' ? 'selected' : '' ?>>활성</option>
                    <option value="0" <?= old('is_active', $classInfo['is_active'] ?? '1') == '0' ? 'selected' : '' ?>>비활성</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">비활성화된 권한은 사용자 등록 시 선택할 수 없습니다.</p>
            </div>
        </div>

        <!-- 버튼 -->
        <div class="mt-6 flex justify-end gap-2">
            <a href="<?= base_url('admin/class-list') ?>"
               class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300 transition">
                취소
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">
                <?= $isEdit ? '수정' : '등록' ?>
            </button>
        </div>
    </form>

    <!-- 안내사항 -->
    <div class="mt-6 p-3 bg-blue-50 rounded border border-blue-200">
        <h4 class="text-xs font-semibold text-blue-900 mb-1">참고사항</h4>
        <ul class="text-xs text-blue-800 space-y-1">
            <li>• 현재 사용 중인 권한 ID: 1(거래처 관리자), 2(관리자), 3(부서장), 4(정산담당자), 5(일반 사용자), 9(메일룸 담당자)</li>
            <li>• 새로운 권한을 추가할 경우 기존 ID와 중복되지 않도록 주의하세요.</li>
            <li>• 권한 레벨은 시스템 내 권한 순서를 나타내며, 조회 시 정렬 기준으로 사용됩니다.</li>
        </ul>
    </div>
</div>
<?= $this->endSection() ?>
