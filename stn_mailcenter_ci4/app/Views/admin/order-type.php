<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">

    <!-- 콜센터 선택 (daumdata 로그인 user_type=1인 경우만 표시) -->
    <?php if (isset($login_type) && $login_type === 'daumdata' && isset($user_type) && $user_type == '1' && !empty($cc_list)): ?>
    <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">콜센터 선택:</label>
            <select id="cc_code_select" class="search-filter-select" onchange="changeCcCode()">
                <option value="">전체 (마스터 설정)</option>
                <?php foreach ($cc_list as $cc): ?>
                <option value="<?= htmlspecialchars($cc['cc_code']) ?>" <?= (isset($selected_cc_code) && $selected_cc_code === $cc['cc_code']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cc['cc_name']) ?> (<?= htmlspecialchars($cc['cc_code']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($selected_cc_code) && $selected_cc_code): ?>
            <span class="text-sm text-gray-600">현재 선택: <?= htmlspecialchars($selected_cc_code) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 통계 카드 
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">총 주문유형</p>
                    <p class="text-2xl font-bold text-blue-900" id="total-count"><?= $stats['total'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">활성화된 유형</p>
                    <p class="text-2xl font-bold text-green-900" id="active-count"><?= $stats['active'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">비활성화된 유형</p>
                    <p class="text-2xl font-bold text-gray-900" id="inactive-count"><?= $stats['inactive'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>
    //-->
    <!-- 주문유형 그리드 (DB 데이터로 동적 생성) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mb-6" id="service-types-grid">
        <?php 
        // 데이터가 없으면 빈 배열로 초기화
        $service_types_grouped = $service_types_grouped ?? [];
        
        // 카테고리 순서 정의
        $categoryOrder = ['퀵서비스', '연계배송서비스', '택배서비스', '우편서비스', '일반서비스', '생활서비스'];
        $categoryLabels = [
            '퀵서비스' => '퀵서비스',
            'quick' => '퀵서비스',
            '연계배송서비스' => '연계배송서비스',
            'linked' => '연계배송서비스',
            '택배서비스' => '택배서비스',
            'parcel' => '택배서비스',
            '우편서비스' => '우편서비스',
            'postal' => '우편서비스',
            '일반서비스' => '일반서비스',
            'general' => '일반서비스',
            '생활서비스' => '생활서비스',
            'life' => '생활서비스'
        ];
        
        // 카테고리별로 그룹화된 데이터 정렬
        $sortedCategories = [];
        if (!empty($service_types_grouped)) {
            foreach ($categoryOrder as $cat) {
                foreach ($service_types_grouped as $category => $services) {
                    $label = $categoryLabels[$category] ?? $category;
                    if ($label === $cat && !isset($sortedCategories[$category])) {
                        $sortedCategories[$category] = $services;
                    }
                }
            }
            
            // 나머지 카테고리 추가
            foreach ($service_types_grouped as $category => $services) {
                if (!isset($sortedCategories[$category])) {
                    $sortedCategories[$category] = $services;
                }
            }
        }
        
        if (empty($sortedCategories)): ?>
        <div class="col-span-full text-center py-8 text-gray-500">
            등록된 주문유형이 없습니다. "새 주문유형 추가" 버튼을 클릭하여 추가하세요.
                </div>
        <?php else:
        foreach ($sortedCategories as $category => $services): 
            $categoryLabel = $categoryLabels[$category] ?? $category;
        ?>
        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-2"><?= $categoryLabel ?></h3>
            <div class="space-y-1 sortable-service-list" data-category="<?= htmlspecialchars($category) ?>">
                <?php foreach ($services as $service): ?>
                <?php 
                // 콜센터 선택 시 마스터 비활성화 서비스 체크
                $isMasterDisabled = (isset($selected_cc_code) && $selected_cc_code && isset($service['master_is_active']) && !$service['master_is_active']);
                ?>
                <div class="flex items-center justify-between sortable-service-item <?= $isMasterDisabled ? 'opacity-60' : 'hover:bg-gray-100' ?> py-1 px-2 rounded transition-colors" 
                     data-service-id="<?= $service['id'] ?>"
                     data-sort-order="<?= $service['sort_order'] ?? 0 ?>">
                    <div class="flex items-center flex-1">
                        <svg class="w-4 h-4 text-gray-400 mr-2 drag-handle cursor-move" fill="none" stroke="currentColor" viewBox="0 0 24 24" onclick="event.stopPropagation();">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                        </svg>
                        <span class="text-sm <?= $isMasterDisabled ? 'text-gray-400' : 'text-gray-600' ?> service-name-clickable flex-1" 
                              data-service-id="<?= $service['id'] ?>"
                              data-service-name="<?= htmlspecialchars($service['service_name']) ?>"
                              data-service-category="<?= htmlspecialchars($service['service_category']) ?>"
                              style="cursor: pointer;"
                              onclick="openEditModal(<?= $service['id'] ?>, '<?= htmlspecialchars($service['service_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($service['service_category'], ENT_QUOTES) ?>', <?= (isset($service['is_external_link']) && $service['is_external_link'] == 1) ? 'true' : 'false' ?>, <?= !empty($service['external_url']) ? "'" . htmlspecialchars(addslashes($service['external_url']), ENT_QUOTES) . "'" : "''" ?>)">
                            <?= htmlspecialchars($service['service_name']) ?>
                            <?php if (!empty($service['is_external_link']) && $service['is_external_link'] == 1): ?>
                                <span class="text-xs text-blue-500 ml-1">(외부링크)</span>
                            <?php endif; ?>
                            <?php if ($isMasterDisabled): ?>
                                <svg class="w-4 h-4 text-red-500 ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="마스터에서 비활성화된 서비스입니다">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                </svg>
                            <?php endif; ?>
                        </span>
                    </div>
                    <label class="relative inline-flex items-center ml-2 <?= $isMasterDisabled ? 'cursor-not-allowed' : 'cursor-pointer' ?>" onclick="<?= $isMasterDisabled ? 'return false;' : 'event.stopPropagation();' ?>">
                        <input type="checkbox" 
                               class="sr-only peer service-status-toggle" 
                               data-service-id="<?= $service['id'] ?>"
                               <?= (isset($service['is_enabled']) && $service['is_enabled']) || (!isset($service['is_enabled']) && isset($service['is_active']) && $service['is_active'] == 1) ? 'checked' : '' ?>
                               <?= $isMasterDisabled ? 'disabled title="마스터에서 비활성화된 서비스는 변경할 수 없습니다"' : '' ?>>
                        <div class="w-11 h-6 <?= $isMasterDisabled ? 'bg-gray-100 opacity-50' : 'bg-gray-200' ?> peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all <?= $isMasterDisabled ? '' : 'peer-checked:bg-blue-600' ?>"></div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php 
        endforeach;
        endif; 
        ?>
        </div>

    <!-- 액션 버튼들 -->
    <div class="flex justify-between items-center">
        <button onclick="openCreateModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            새 주문유형 추가
        </button>
        <div class="flex space-x-2">
            <button onclick="activateAll()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                전체 활성화
            </button>
            <button onclick="deactivateAll()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                전체 비활성화
            </button>
            <button onclick="saveSettings()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                설정 저장
            </button>
                </div>
            </div>
        </div>

<!-- 새 주문유형 추가 레이어 팝업 -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">새 주문유형 추가</h3>
            <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
                </div>
        
        <form id="createServiceForm" onsubmit="createServiceType(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">주문 그룹 유형</label>
                <select id="create_category" name="service_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">선택하세요</option>
                    <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="__new__">+ 새 그룹 추가</option>
                </select>
                </div>
            
            <div id="new-category-input" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">새 그룹명</label>
                <input type="text" id="new_category" name="new_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="새 그룹명을 입력하세요">
                </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">서비스명 <span class="text-red-500">*</span></label>
                <input type="text" id="create_service_name" name="service_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="서비스명을 입력하세요" required>
            </div>
            
            <div class="mb-4">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="create_is_external_link" name="is_external_link" class="sr-only peer" onchange="toggleExternalUrlInput('create')">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">외부 링크 서비스</span>
                </label>
            </div>
            
            <div id="create-external-url-container" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">외부 링크 URL <span class="text-red-500">*</span></label>
                <input type="url" id="create_external_url" name="external_url" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://example.com" pattern="https?://.+">
                <p class="mt-1 text-xs text-gray-500">외부 사이트의 전체 URL을 입력하세요 (http:// 또는 https:// 포함)</p>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeCreateModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">취소</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">저장</button>
                </div>
        </form>
            </div>
        </div>

<!-- 서비스명 수정 레이어 팝업 -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">서비스명 수정</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
                </div>
        
        <form id="editServiceForm" onsubmit="updateServiceType(event)">
            <input type="hidden" id="edit_service_id" name="service_id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">주문 그룹 유형</label>
                <select id="edit_category" name="service_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">선택하세요</option>
                    <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="__new__">+ 새 그룹 추가</option>
                </select>
                </div>
            
            <div id="edit-new-category-input" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">새 그룹명</label>
                <input type="text" id="edit_new_category" name="new_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="새 그룹명을 입력하세요">
                </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">서비스명 <span class="text-red-500">*</span></label>
                <input type="text" id="edit_service_name" name="service_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="서비스명을 입력하세요" required>
            </div>
            
            <div class="mb-4">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="edit_is_external_link" name="is_external_link" class="sr-only peer" onchange="toggleExternalUrlInput('edit')">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">외부 링크 서비스</span>
                </label>
            </div>
            
            <div id="edit-external-url-container" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">외부 링크 URL <span class="text-red-500">*</span></label>
                <input type="url" id="edit_external_url" name="external_url" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://example.com" pattern="https?://.+">
                <p class="mt-1 text-xs text-gray-500">외부 사이트의 전체 URL을 입력하세요 (http:// 또는 https:// 포함)</p>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">취소</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">저장</button>
                </div>
        </form>
        </div>
    </div>

<!-- Sortable.js 라이브러리 -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// 상태 변경 추적을 위한 변수
let statusChanges = {};

// 새 주문유형 추가 모달 열기
function openCreateModal() {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('create_category').value = '';
    document.getElementById('new_category').value = '';
    document.getElementById('create_service_name').value = '';
    document.getElementById('new-category-input').classList.add('hidden');
    
    // 외부 링크 필드 초기화
    document.getElementById('create_is_external_link').checked = false;
    document.getElementById('create_external_url').value = '';
    document.getElementById('create-external-url-container').classList.add('hidden');
    document.getElementById('create_external_url').required = false;
}

// 새 주문유형 추가 모달 닫기
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 외부 링크 URL 입력 필드 표시/숨김
function toggleExternalUrlInput(mode) {
    const checkbox = document.getElementById(mode + '_is_external_link');
    const container = document.getElementById(mode + '-external-url-container');
    const urlInput = document.getElementById(mode + '_external_url');
    
    if (checkbox.checked) {
        container.classList.remove('hidden');
        urlInput.required = true;
    } else {
        container.classList.add('hidden');
        urlInput.required = false;
        urlInput.value = '';
    }
}

// 서비스명 수정 모달 열기
function openEditModal(serviceId, serviceName, serviceCategory, isExternalLink = false, externalUrl = '') {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 먼저 모든 필드 초기화
    document.getElementById('edit_service_id').value = '';
    document.getElementById('edit_service_name').value = '';
    document.getElementById('edit_category').value = '';
    document.getElementById('edit_new_category').value = '';
    document.getElementById('edit-new-category-input').classList.add('hidden');
    document.getElementById('edit_is_external_link').checked = false;
    document.getElementById('edit_external_url').value = '';
    document.getElementById('edit-external-url-container').classList.add('hidden');
    document.getElementById('edit_external_url').required = false;
    
    // 이제 실제 값 설정
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('edit_service_id').value = serviceId || '';
    document.getElementById('edit_service_name').value = serviceName || '';
    document.getElementById('edit_category').value = serviceCategory || '';
    
    // 외부 링크 필드 설정 (명시적으로 boolean 변환)
    // isExternalLink가 문자열 'true', boolean true, 숫자 1, 문자열 '1' 중 하나면 true
    let isExternal = false;
    if (typeof isExternalLink !== 'undefined' && isExternalLink !== null) {
        isExternal = (isExternalLink === true || isExternalLink === 1 || isExternalLink === '1' || isExternalLink === 'true');
    }
    
    // externalUrl이 유효한 문자열인 경우에만 사용
    let externalUrlValue = '';
    if (externalUrl && typeof externalUrl === 'string' && externalUrl.trim() !== '' && externalUrl !== 'null' && externalUrl !== 'undefined') {
        externalUrlValue = externalUrl.trim();
    }
    
    // 체크박스 상태 설정
    document.getElementById('edit_is_external_link').checked = isExternal;
    
    // URL 필드 설정
    if (isExternal) {
        document.getElementById('edit_external_url').value = externalUrlValue || '';
        document.getElementById('edit-external-url-container').classList.remove('hidden');
        document.getElementById('edit_external_url').required = true;
    } else {
        document.getElementById('edit_external_url').value = ''; // 외부 링크가 아니면 URL도 초기화
        document.getElementById('edit-external-url-container').classList.add('hidden');
        document.getElementById('edit_external_url').required = false;
    }
}

// 서비스명 수정 모달 닫기
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    
    // 모든 필드 초기화
    document.getElementById('edit_service_id').value = '';
    document.getElementById('edit_service_name').value = '';
    document.getElementById('edit_category').value = '';
    document.getElementById('edit_new_category').value = '';
    document.getElementById('edit-new-category-input').classList.add('hidden');
    document.getElementById('edit_is_external_link').checked = false;
    document.getElementById('edit_external_url').value = '';
    document.getElementById('edit-external-url-container').classList.add('hidden');
    document.getElementById('edit_external_url').required = false;
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 새 그룹 추가 옵션 선택 시 입력 필드 표시
document.getElementById('create_category').addEventListener('change', function() {
    if (this.value === '__new__') {
        document.getElementById('new-category-input').classList.remove('hidden');
        document.getElementById('new_category').required = true;
    } else {
        document.getElementById('new-category-input').classList.add('hidden');
        document.getElementById('new_category').required = false;
    }
});

document.getElementById('edit_category').addEventListener('change', function() {
    if (this.value === '__new__') {
        document.getElementById('edit-new-category-input').classList.remove('hidden');
        document.getElementById('edit_new_category').required = true;
    } else {
        document.getElementById('edit-new-category-input').classList.add('hidden');
        document.getElementById('edit_new_category').required = false;
    }
});

// 새 서비스 타입 생성
function createServiceType(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('createServiceForm'));
    
    fetch('<?= base_url('admin/createServiceType') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '서비스 타입 생성에 실패했습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('서비스 타입 생성 중 오류가 발생했습니다.');
    });
}

// 서비스 타입 수정
function updateServiceType(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('editServiceForm'));
    
    fetch('<?= base_url('admin/updateServiceType') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '서비스 타입 수정에 실패했습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('서비스 타입 수정 중 오류가 발생했습니다.');
    });
}

// 전체 활성화 (UI만 변경)
function activateAll() {
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        toggle.checked = true;
    });
    // console.log('모든 서비스가 활성화 상태로 변경되었습니다. (설정 저장을 눌러주세요.)');
}

// 전체 비활성화 (UI만 변경)
function deactivateAll() {
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        toggle.checked = false;
    });
    // console.log('모든 서비스가 비활성화 상태로 변경되었습니다. (설정 저장을 눌러주세요.)');
}

// 콜센터 선택 변경
function changeCcCode() {
    const ccCode = document.getElementById('cc_code_select').value;
    const url = new URL(window.location.href);
    
    if (ccCode) {
        url.searchParams.set('cc_code', ccCode);
    } else {
        url.searchParams.delete('cc_code');
    }
    
    window.location.href = url.toString();
}

// 설정 저장 (일괄 상태 업데이트)
function saveSettings() {
    // 모든 토글 상태 수집
    const statusUpdates = [];
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        const serviceId = toggle.dataset.serviceId;
        const isActive = toggle.checked ? 1 : 0;
        statusUpdates.push({
            service_id: serviceId,
            is_active: isActive
        });
    });
    
    if (statusUpdates.length === 0) {
        alert('저장할 설정이 없습니다.');
        return;
    }
    
    // console.log('저장할 데이터:', statusUpdates);
    
    const formData = new FormData();
    formData.append('status_updates', JSON.stringify(statusUpdates));
    
    // 콜센터 코드가 선택되어 있으면 함께 전송
    const ccCodeSelect = document.getElementById('cc_code_select');
    if (ccCodeSelect && ccCodeSelect.value) {
        formData.append('cc_code', ccCodeSelect.value);
        // console.log('콜센터 코드:', ccCodeSelect.value);
    }
    
    fetch('<?= base_url('admin/batchUpdateServiceStatus') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // console.log('응답 상태:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        // console.log('서버 응답:', data);
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '설정 저장에 실패했습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('설정 저장 중 오류가 발생했습니다: ' + error.message);
    });
}

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
// 외부 클릭으로 인한 실수 방지를 위해 제거

// 드래그 앤 드롭으로 순서 변경 초기화
function initSortable() {
    // Sortable.js가 로드되었는지 확인
    if (typeof Sortable === 'undefined') {
        // console.error('Sortable.js가 로드되지 않았습니다.');
        setTimeout(initSortable, 100); // 재시도
        return;
    }
    
    const sortableLists = document.querySelectorAll('.sortable-service-list');
    
    if (sortableLists.length === 0) {
        // console.warn('정렬 가능한 목록을 찾을 수 없습니다.');
        return;
    }
    
    sortableLists.forEach(list => {
        new Sortable(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            forceFallback: false,
            onEnd: function(evt) {
                // 순서 변경 후 새로운 순서 저장
                updateServiceOrder(list);
            }
        });
    });
    
    // console.log('Sortable 초기화 완료:', sortableLists.length, '개 목록');
}

// 페이지 로드 완료 후 초기화
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSortable);
} else {
    // 이미 로드 완료된 경우 즉시 실행
    initSortable();
}

// 서비스 순서 업데이트
function updateServiceOrder(listElement) {
    const items = listElement.querySelectorAll('.sortable-service-item');
    const sortUpdates = [];
    
    items.forEach((item, index) => {
        const serviceId = item.dataset.serviceId;
        const category = listElement.dataset.category;
        sortUpdates.push({
            service_id: serviceId,
            sort_order: index + 1,
            category: category
        });
    });
    
    if (sortUpdates.length === 0) {
        return;
    }
    
    // 서버에 순서 업데이트 요청
    const formData = new FormData();
    formData.append('sort_updates', JSON.stringify(sortUpdates));
    
    fetch('<?= base_url('admin/updateServiceSortOrder') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // console.log('순서가 저장되었습니다.', data);
        } else {
            // console.error('순서 저장 실패:', data.message);
            alert('순서 저장에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        // console.error('순서 저장 중 오류:', error);
        alert('순서 저장 중 오류가 발생했습니다. 콘솔을 확인해주세요.');
    });
}
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
    background: #f3f4f6;
}

.sortable-chosen {
    cursor: grabbing;
}

.sortable-drag {
    opacity: 0.8;
}
</style>

<?= $this->endSection() ?>
