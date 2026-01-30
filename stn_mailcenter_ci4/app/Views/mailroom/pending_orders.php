<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<!-- 헤더 영역 -->
<div class="page-header-section mb-3 px-3 py-3 bg-white rounded-lg border border-gray-200 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-800">메일룸 승인 대기 주문</h1>
            <p class="text-xs text-gray-500">메일룸 계약 거래처의 주문을 검토하고 승인/반려 처리합니다.</p>
        </div>
        <a href="/mailroom" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-1"></i> 대시보드로 돌아가기
        </a>
    </div>
    <?php if (session()->getFlashdata('message')): ?>
        <div class="mt-3 px-3 py-2 text-xs bg-green-50 border border-green-200 text-green-700 rounded"><?= session()->getFlashdata('message') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="mt-3 px-3 py-2 text-xs bg-red-50 border border-red-200 text-red-700 rounded"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
</div>

<!-- 통계 카드 -->
<div class="grid grid-cols-2 gap-3 mb-3">
    <div class="p-3 text-center rounded-lg bg-yellow-50 border border-yellow-200">
        <div class="text-2xl font-bold text-yellow-600"><?= count($orders ?? []) ?></div>
        <div class="text-xs text-yellow-500">승인 대기</div>
    </div>
    <div class="p-3 text-center rounded-lg bg-blue-50 border border-blue-200">
        <div class="text-2xl font-bold text-blue-600">
            <span id="selected-count">0</span>
        </div>
        <div class="text-xs text-blue-500">선택됨</div>
    </div>
</div>

<!-- 주문 목록 -->
<div class="list-page-container">
    <?php if (empty($orders)): ?>
        <div class="py-12 text-center text-gray-500 text-sm bg-white border border-gray-200 rounded">
            승인 대기 중인 주문이 없습니다.
        </div>
    <?php else: ?>
        <!-- 일괄 처리 버튼 -->
        <div class="flex gap-2 mb-3">
            <button onclick="approveSelected()" class="px-3 py-1.5 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed" id="btn-approve-selected" disabled>
                <i class="fas fa-check mr-1"></i> 선택 승인
            </button>
            <button onclick="rejectSelected()" class="px-3 py-1.5 text-xs font-medium text-white bg-red-500 rounded hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed" id="btn-reject-selected" disabled>
                <i class="fas fa-times mr-1"></i> 선택 반려
            </button>
            <button onclick="toggleSelectAll()" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200">
                <i class="fas fa-check-double mr-1"></i> 전체 선택/해제
            </button>
        </div>

        <div class="list-table-container">
            <table class="list-table-compact">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
                        <th style="width:80px;">주문번호</th>
                        <th style="width:100px;">서비스</th>
                        <th style="width:120px;">거래처</th>
                        <th style="width:120px;">접수일시</th>
                        <th style="width:150px;">출발지</th>
                        <th style="width:150px;">도착지</th>
                        <th style="width:80px;">품목</th>
                        <th style="width:120px;">처리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr class="order-row" data-order-id="<?= $order['id'] ?>">
                        <td>
                            <input type="checkbox" class="order-checkbox" value="<?= $order['id'] ?>" onchange="updateSelectedCount()">
                        </td>
                        <td>
                            <span class="text-xs font-mono text-gray-600"><?= esc($order['order_number'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded"><?= esc($order['service_name'] ?? '일반') ?></span>
                        </td>
                        <td>
                            <span class="text-xs"><?= esc($order['company_info'] ?? $order['company_name'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <span class="text-xs text-gray-500"><?= date('Y-m-d H:i', strtotime($order['save_date'] ?? $order['created_at'])) ?></span>
                        </td>
                        <td>
                            <div class="text-xs">
                                <div class="font-medium"><?= esc($order['departure_company_name'] ?? '') ?></div>
                                <div class="text-gray-500 truncate" style="max-width:140px;" title="<?= esc($order['departure_address'] ?? '') ?>"><?= esc($order['departure_address'] ?? '') ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="text-xs">
                                <div class="font-medium"><?= esc($order['destination_company_name'] ?? '') ?></div>
                                <div class="text-gray-500 truncate" style="max-width:140px;" title="<?= esc($order['destination_address'] ?? '') ?>"><?= esc($order['destination_address'] ?? '') ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="text-xs"><?= esc($order['item_type'] ?? '일반') ?></span>
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <button onclick="approveOrder(<?= $order['id'] ?>)" class="px-2 py-1 text-xs text-white bg-green-500 rounded hover:bg-green-600" title="승인">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="showRejectModal(<?= $order['id'] ?>)" class="px-2 py-1 text-xs text-white bg-red-500 rounded hover:bg-red-600" title="반려">
                                    <i class="fas fa-times"></i>
                                </button>
                                <button onclick="showOrderDetail(<?= $order['id'] ?>)" class="px-2 py-1 text-xs text-white bg-gray-500 rounded hover:bg-gray-600" title="상세보기">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 반려 사유 입력 모달 -->
<div id="reject-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg p-5 w-96 max-w-full">
        <h3 class="text-base font-semibold mb-3">반려 사유 입력</h3>
        <input type="hidden" id="reject-order-id">
        <textarea id="reject-reason" class="w-full px-3 py-2 border border-gray-300 rounded text-sm" rows="3" placeholder="반려 사유를 입력하세요..."></textarea>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="closeRejectModal()" class="px-3 py-1.5 text-xs text-gray-600 bg-gray-100 rounded hover:bg-gray-200">취소</button>
            <button onclick="confirmReject()" class="px-3 py-1.5 text-xs text-white bg-red-500 rounded hover:bg-red-600">반려 확인</button>
        </div>
    </div>
</div>

<!-- 주문 상세 모달 -->
<div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg p-5 w-[600px] max-w-full max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-base font-semibold">주문 상세 정보</h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="detail-content">
            <!-- 동적으로 채워짐 -->
        </div>
    </div>
</div>

<script>
// 선택 개수 업데이트
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selected-count').textContent = count;

    // 버튼 활성화/비활성화
    document.getElementById('btn-approve-selected').disabled = count === 0;
    document.getElementById('btn-reject-selected').disabled = count === 0;
}

// 전체 선택/해제
function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.order-checkbox');

    // 현재 상태 반전
    const newState = !selectAll.checked || (selectAll.indeterminate);
    selectAll.checked = newState;
    selectAll.indeterminate = false;

    checkboxes.forEach(cb => cb.checked = newState);
    updateSelectedCount();
}

// 단일 주문 승인
async function approveOrder(orderId) {
    if (!confirm('이 주문을 승인하시겠습니까?')) return;

    try {
        const response = await fetch('/service/mailroom-approve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ order_id: orderId })
        });

        const result = await response.json();

        if (result.success) {
            alert('주문이 승인되었습니다.');
            location.reload();
        } else {
            alert('승인 실패: ' + (result.message || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('승인 처리 중 오류가 발생했습니다.');
    }
}

// 선택된 주문 일괄 승인
async function approveSelected() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('승인할 주문을 선택하세요.');
        return;
    }

    if (!confirm(`${checkboxes.length}건의 주문을 승인하시겠습니까?`)) return;

    let successCount = 0;
    let failCount = 0;

    for (const checkbox of checkboxes) {
        try {
            const response = await fetch('/service/mailroom-approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ order_id: checkbox.value })
            });

            const result = await response.json();
            if (result.success) {
                successCount++;
            } else {
                failCount++;
            }
        } catch (error) {
            failCount++;
        }
    }

    alert(`승인 완료: ${successCount}건, 실패: ${failCount}건`);
    location.reload();
}

// 반려 모달 표시
function showRejectModal(orderId) {
    document.getElementById('reject-order-id').value = orderId;
    document.getElementById('reject-reason').value = '';
    document.getElementById('reject-modal').style.display = 'flex';
}

// 반려 모달 닫기
function closeRejectModal() {
    document.getElementById('reject-modal').style.display = 'none';
}

// 반려 확인
async function confirmReject() {
    const orderId = document.getElementById('reject-order-id').value;
    const reason = document.getElementById('reject-reason').value;

    try {
        const response = await fetch('/service/mailroom-reject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ order_id: orderId, reject_reason: reason })
        });

        const result = await response.json();

        if (result.success) {
            alert('주문이 반려되었습니다.');
            closeRejectModal();
            location.reload();
        } else {
            alert('반려 실패: ' + (result.message || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('반려 처리 중 오류가 발생했습니다.');
    }
}

// 선택된 주문 일괄 반려
async function rejectSelected() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('반려할 주문을 선택하세요.');
        return;
    }

    const reason = prompt(`${checkboxes.length}건의 주문을 반려합니다. 반려 사유를 입력하세요:`);
    if (reason === null) return;

    let successCount = 0;
    let failCount = 0;

    for (const checkbox of checkboxes) {
        try {
            const response = await fetch('/service/mailroom-reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ order_id: checkbox.value, reject_reason: reason })
            });

            const result = await response.json();
            if (result.success) {
                successCount++;
            } else {
                failCount++;
            }
        } catch (error) {
            failCount++;
        }
    }

    alert(`반려 완료: ${successCount}건, 실패: ${failCount}건`);
    location.reload();
}

// 주문 상세 보기
function showOrderDetail(orderId) {
    // 현재 페이지의 데이터에서 해당 주문 찾기
    const orders = <?= json_encode($orders ?? []) ?>;
    const order = orders.find(o => o.id == orderId);

    if (!order) {
        alert('주문 정보를 찾을 수 없습니다.');
        return;
    }

    const content = `
        <div class="space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500">주문번호</label>
                    <div class="font-mono">${order.order_number || 'N/A'}</div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">서비스</label>
                    <div>${order.service_name || '일반'}</div>
                </div>
            </div>
            <div class="border-t pt-3">
                <label class="text-xs text-gray-500">출발지</label>
                <div class="font-medium">${order.departure_company_name || ''}</div>
                <div class="text-gray-600">${order.departure_address || ''} ${order.departure_detail || ''}</div>
                <div class="text-gray-500">${order.departure_contact || ''}</div>
            </div>
            <div class="border-t pt-3">
                <label class="text-xs text-gray-500">도착지</label>
                <div class="font-medium">${order.destination_company_name || ''}</div>
                <div class="text-gray-600">${order.destination_address || ''} ${order.detail_address || ''}</div>
                <div class="text-gray-500">${order.destination_contact || ''}</div>
            </div>
            <div class="border-t pt-3 grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-500">품목</label>
                    <div>${order.item_type || '일반'}</div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">수량</label>
                    <div>${order.quantity || 1} ${order.unit || '개'}</div>
                </div>
            </div>
            ${order.notes ? `
            <div class="border-t pt-3">
                <label class="text-xs text-gray-500">비고</label>
                <div class="text-gray-600">${order.notes}</div>
            </div>
            ` : ''}
        </div>
    `;

    document.getElementById('detail-content').innerHTML = content;
    document.getElementById('detail-modal').style.display = 'flex';
}

// 상세 모달 닫기
function closeDetailModal() {
    document.getElementById('detail-modal').style.display = 'none';
}
</script>
<?= $this->endSection() ?>