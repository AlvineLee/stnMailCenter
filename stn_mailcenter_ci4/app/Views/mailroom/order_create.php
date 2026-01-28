<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="w-full flex flex-col p-4 max-w-4xl mx-auto">
    <!-- 헤더 -->
    <div class="mb-4">
        <div class="flex items-center gap-3">
            <a href="/mailroom" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold text-gray-800">배송 접수</h1>
        </div>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
            <ul class="list-disc list-inside">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/mailroom/store" method="post" class="space-y-6">
        <?= csrf_field() ?>

        <!-- 출발지 정보 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm">1</span>
                출발지 정보
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">건물 <span class="text-red-500">*</span></label>
                    <select name="from_building_id" id="fromBuilding" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">건물 선택</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?= $building['id'] ?>" <?= old('from_building_id') == $building['id'] ? 'selected' : '' ?>>
                                <?= esc($building['building_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">층</label>
                    <select name="from_floor_id" id="fromFloor"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">층 선택</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">회사/부서명</label>
                    <input type="text" name="from_company" value="<?= old('from_company') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="회사명 또는 부서명">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">담당자명</label>
                    <input type="text" name="from_contact_name" value="<?= old('from_contact_name') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="발송 담당자">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">연락처</label>
                    <input type="tel" name="from_contact_phone" value="<?= old('from_contact_phone') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="010-0000-0000">
                </div>
            </div>
        </div>

        <!-- 도착지 정보 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-sm">2</span>
                도착지 정보
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">건물 <span class="text-red-500">*</span></label>
                    <select name="to_building_id" id="toBuilding" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">건물 선택</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?= $building['id'] ?>" <?= old('to_building_id') == $building['id'] ? 'selected' : '' ?>>
                                <?= esc($building['building_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">층</label>
                    <select name="to_floor_id" id="toFloor"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">층 선택</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">회사/부서명</label>
                    <input type="text" name="to_company" value="<?= old('to_company') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="회사명 또는 부서명">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">담당자명</label>
                    <input type="text" name="to_contact_name" value="<?= old('to_contact_name') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="수령 담당자">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">연락처</label>
                    <input type="tel" name="to_contact_phone" value="<?= old('to_contact_phone') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="010-0000-0000">
                </div>
            </div>
        </div>

        <!-- 물품 정보 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-orange-600 text-white rounded-full flex items-center justify-center text-sm">3</span>
                물품 정보
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">물품 설명 <span class="text-red-500">*</span></label>
                    <input type="text" name="item_description" value="<?= old('item_description') ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                           placeholder="서류, 박스, 샘플 등">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">수량</label>
                    <input type="number" name="item_count" value="<?= old('item_count', 1) ?>" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">우선순위</label>
                    <select name="priority"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="normal" <?= old('priority') == 'normal' ? 'selected' : '' ?>>일반</option>
                        <option value="urgent" <?= old('priority') == 'urgent' ? 'selected' : '' ?>>긴급</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">메모</label>
                    <textarea name="memo" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                              placeholder="배송 시 참고사항"><?= old('memo') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 버튼 -->
        <div class="flex gap-3">
            <a href="/mailroom" class="flex-1 py-3 bg-gray-200 text-gray-700 text-center font-semibold rounded-lg hover:bg-gray-300 transition">
                취소
            </a>
            <button type="submit" class="flex-1 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                접수하기
            </button>
        </div>
    </form>
</div>

<script>
    // 건물 선택 시 층 목록 로드
    document.getElementById('fromBuilding').addEventListener('change', function() {
        loadFloors(this.value, 'fromFloor');
    });

    document.getElementById('toBuilding').addEventListener('change', function() {
        loadFloors(this.value, 'toFloor');
    });

    function loadFloors(buildingId, targetId) {
        const select = document.getElementById(targetId);
        select.innerHTML = '<option value="">층 선택</option>';

        if (!buildingId) return;

        fetch('/mailroom/floors/' + buildingId)
            .then(response => response.json())
            .then(floors => {
                floors.forEach(floor => {
                    const option = document.createElement('option');
                    option.value = floor.id;
                    option.textContent = floor.floor_name;
                    select.appendChild(option);
                });
            })
            .catch(error => console.error('층 목록 로드 실패:', error));
    }
</script>
<?= $this->endSection() ?>