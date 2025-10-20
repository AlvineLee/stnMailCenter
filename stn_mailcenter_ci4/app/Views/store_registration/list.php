<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">


    <!-- 상태별 통계 -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-600">전체</p>
                    <p class="text-lg font-bold text-gray-900"><?= $status_counts['total'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-600">대기중</p>
                    <p class="text-lg font-bold text-gray-900"><?= $status_counts['pending'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-600">심사중</p>
                    <p class="text-lg font-bold text-gray-900"><?= $status_counts['under_review'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-600">승인</p>
                    <p class="text-lg font-bold text-gray-900"><?= $status_counts['approved'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-xs font-medium text-gray-600">거부</p>
                    <p class="text-lg font-bold text-gray-900"><?= $status_counts['rejected'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 검색 및 필터 영역 -->
    <div class="bg-gray-50 rounded-lg search-compact">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">검색</label>
                <input type="text" id="searchKeyword" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="회사명, 사업자번호, 대표자명, 이메일로 검색" value="<?= esc($filters['keyword']) ?>">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">상태</label>
                <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체</option>
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>대기중</option>
                    <option value="under_review" <?= $filters['status'] === 'under_review' ? 'selected' : '' ?>>심사중</option>
                    <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>승인</option>
                    <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>거부</option>
                </select>
            </div>
            <div>
                <button onclick="searchRegistrations()" class="search-button">
                    검색
                </button>
            </div>
        </div>
    </div>

    <!-- 검색 결과 정보 -->
    <div class="mb-3 px-4 py-2 bg-gray-50 rounded-md">
        <div class="text-sm text-gray-700">
            총 <span class="font-medium text-gray-900"><?= count($registrations) ?></span>건의 검색결과가 있습니다.
        </div>
    </div>

    <!-- 입점신청 목록 테이블 -->
    <div class="list-table-container">
        <table>
            <thead>
                <tr>
                    <th>신청번호</th>
                    <th>회사정보</th>
                    <th>대표자</th>
                    <th>상태</th>
                    <th>신청일</th>
                    <th>처리일</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registrations)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            입점신청이 없습니다.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td>#<?= str_pad($registration['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <span class="font-medium text-gray-900"><?= esc($registration['company_name']) ?></span>
                                <span class="text-xs text-blue-600 ml-2">(<?= esc($registration['business_number']) ?>)</span>
                            </td>
                            <td>
                                <span class="text-gray-900"><?= esc($registration['representative_name']) ?></span>
                                <span class="text-xs text-green-600 ml-2">(<?= esc($registration['representative_email']) ?>)</span>
                            </td>
                            <td>
                                <?php
                                $statusConfig = [
                                    'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => '대기중'],
                                    'under_review' => ['class' => 'bg-blue-100 text-blue-800', 'text' => '심사중'],
                                    'approved' => ['class' => 'bg-green-100 text-green-800', 'text' => '승인'],
                                    'rejected' => ['class' => 'bg-red-100 text-red-800', 'text' => '거부']
                                ];
                                $config = $statusConfig[$registration['status']] ?? $statusConfig['pending'];
                                ?>
                                <span class="status-badge <?= $config['class'] ?>">
                                    <?= $config['text'] ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($registration['created_at'])) ?></td>
                            <td>
                                <?php
                                $processedDate = $registration['approved_at'] ?? $registration['reviewed_at'];
                                echo $processedDate ? date('Y-m-d', strtotime($processedDate)) : '-';
                                ?>
                            </td>
                            <td class="action-buttons">
                                <button onclick="viewRegistration(<?= $registration['id'] ?>)">상세보기</button>
                                <?php if ($registration['status'] === 'pending'): ?>
                                    <button onclick="approveRegistration(<?= $registration['id'] ?>)">승인</button>
                                    <button onclick="rejectRegistration(<?= $registration['id'] ?>)">거부</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <div class="list-pagination flex justify-center">
        <div class="pagination flex space-x-2">
            <?= $pagination->render() ?>
        </div>
    </div>
</div>

<!-- 상세보기 레이어 팝업 -->
<div id="detailPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[85vh] overflow-y-auto mx-2">
        <!-- 헤더 -->
        <div class="sticky top-0 bg-white border-b border-gray-200 p-3 rounded-t-lg">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">입점신청 상세보기</h2>
                <button onclick="closeDetailPopup()" class="text-gray-500 hover:text-gray-700 text-xl font-bold w-6 h-6 flex items-center justify-center rounded-full hover:bg-gray-100">&times;</button>
            </div>
        </div>
        
        <!-- 본문 -->
        <div id="detailContent" class="p-4">
            <!-- AJAX로 로드될 내용 -->
        </div>
    </div>
</div>

<!-- 승인/거부 처리 레이어 팝업 -->
<div id="actionPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm mx-2">
        <!-- 헤더 -->
        <div class="flex justify-between items-center p-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
            <h2 id="actionTitle" class="text-base font-semibold text-gray-800">처리</h2>
            <button onclick="closeActionPopup()" class="text-gray-500 hover:text-gray-700 text-lg font-bold w-6 h-6 flex items-center justify-center rounded-full hover:bg-gray-100">&times;</button>
        </div>
        
        <!-- 본문 -->
        <div class="p-4">
            <form id="actionForm">
                <input type="hidden" id="actionId" name="id">
                <input type="hidden" id="actionType" name="status">
                
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">처리 의견</label>
                    <textarea id="actionNotes" name="notes" rows="3" 
                              class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-500 focus:border-gray-500"
                              placeholder="처리 의견을 입력해주세요..."></textarea>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeActionPopup()" 
                            class="px-3 py-1 text-xs font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        취소
                    </button>
                    <button type="submit" id="actionSubmit"
                            class="px-3 py-1 text-xs font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        처리하기
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 상세보기 팝업 열기
function viewRegistration(id) {
    fetch(`<?= base_url('store-registration/view') ?>/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('detailContent').innerHTML = data.html;
                document.getElementById('detailPopup').classList.remove('hidden');
                document.getElementById('detailPopup').classList.add('flex');
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('오류가 발생했습니다.');
        });
}

// 상세보기 팝업 닫기
function closeDetailPopup() {
    document.getElementById('detailPopup').classList.add('hidden');
    document.getElementById('detailPopup').classList.remove('flex');
}

// 상세보기에서 승인 처리
function approveFromDetail(id) {
    document.getElementById('actionId').value = id;
    document.getElementById('actionType').value = 'approved';
    document.getElementById('actionTitle').textContent = '입점신청 승인';
    document.getElementById('actionSubmit').textContent = '승인하기';
    document.getElementById('actionSubmit').className = 'px-4 py-2 text-sm font-semibold text-white bg-green-600 border border-green-600 rounded hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-500';
    
    // 상세보기 팝업 닫기
    closeDetailPopup();
    
    // 액션 팝업 열기
    document.getElementById('actionPopup').classList.remove('hidden');
    document.getElementById('actionPopup').classList.add('flex');
}

// 상세보기에서 거부 처리
function rejectFromDetail(id) {
    document.getElementById('actionId').value = id;
    document.getElementById('actionType').value = 'rejected';
    document.getElementById('actionTitle').textContent = '입점신청 거부';
    document.getElementById('actionSubmit').textContent = '거부하기';
    document.getElementById('actionSubmit').className = 'px-4 py-2 text-sm font-semibold text-white bg-red-600 border border-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-1 focus:ring-red-500';
    
    // 상세보기 팝업 닫기
    closeDetailPopup();
    
    // 액션 팝업 열기
    document.getElementById('actionPopup').classList.remove('hidden');
    document.getElementById('actionPopup').classList.add('flex');
}

// 승인 처리
function approveRegistration(id) {
    document.getElementById('actionId').value = id;
    document.getElementById('actionType').value = 'approved';
    document.getElementById('actionTitle').textContent = '입점신청 승인';
    document.getElementById('actionSubmit').textContent = '승인하기';
    document.getElementById('actionSubmit').className = 'px-4 py-2 text-sm font-semibold text-white bg-green-600 border border-green-600 rounded hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-500';
    
    document.getElementById('actionPopup').classList.remove('hidden');
    document.getElementById('actionPopup').classList.add('flex');
}

// 거부 처리
function rejectRegistration(id) {
    document.getElementById('actionId').value = id;
    document.getElementById('actionType').value = 'rejected';
    document.getElementById('actionTitle').textContent = '입점신청 거부';
    document.getElementById('actionSubmit').textContent = '거부하기';
    document.getElementById('actionSubmit').className = 'px-4 py-2 text-sm font-semibold text-white bg-red-600 border border-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-1 focus:ring-red-500';
    
    document.getElementById('actionPopup').classList.remove('hidden');
    document.getElementById('actionPopup').classList.add('flex');
}

// 액션 팝업 닫기
function closeActionPopup() {
    document.getElementById('actionPopup').classList.add('hidden');
    document.getElementById('actionPopup').classList.remove('flex');
    document.getElementById('actionForm').reset();
}

// 액션 폼 제출
document.getElementById('actionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = document.getElementById('actionSubmit');
    
    submitButton.disabled = true;
    submitButton.textContent = '처리중...';
    
    fetch('<?= base_url('store-registration/update-status') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeActionPopup();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = document.getElementById('actionType').value === 'approved' ? '승인하기' : '거부하기';
    });
});

// 새로고침
function refreshList() {
    location.reload();
}

// 검색 기능
function searchRegistrations() {
    const keyword = document.getElementById('searchKeyword').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (keyword) params.append('keyword', keyword);
    if (status) params.append('status', status);
    
    window.location.href = '<?= base_url('store-registration') ?>' + (params.toString() ? '?' + params.toString() : '');
}

// 페이지 이동
function goToPage(page) {
    const keyword = document.getElementById('searchKeyword').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = new URLSearchParams();
    if (keyword) params.append('keyword', keyword);
    if (status) params.append('status', status);
    params.append('page', page);
    
    window.location.href = '<?= base_url('store-registration') ?>?' + params.toString();
}

// 오버레이 클릭 시 팝업 닫기
document.getElementById('detailPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailPopup();
    }
});

document.getElementById('actionPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        closeActionPopup();
    }
});

// ESC 키로 팝업 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailPopup();
        closeActionPopup();
    }
});
</script>

<?= $this->endSection() ?>