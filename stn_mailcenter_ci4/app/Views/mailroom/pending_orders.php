<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<!-- 헤더 영역 -->
<div class="page-header-section mb-3 px-3 py-3 bg-white rounded-lg border border-gray-200 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-800">메일룸 접수 대기 주문</h1>
            <p class="text-xs text-gray-500">메일룸 계약 거래처의 주문을 검토하고 접수 처리합니다.</p>
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
        <div class="text-xs text-yellow-500">접수 대기</div>
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
            접수 대기 중인 주문이 없습니다.
        </div>
    <?php else: ?>
        <!-- 일괄 처리 버튼 -->
        <div class="flex gap-2 mb-3">
            <button onclick="approveSelected()" class="px-3 py-1.5 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed" id="btn-approve-selected" disabled>
                <i class="fas fa-check mr-1"></i> 선택 접수
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
                                <button onclick="approveOrder(<?= $order['id'] ?>)" class="px-2 py-1 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600 whitespace-nowrap">
                                    <i class="fas fa-check mr-1"></i>접수
                                </button>
                                <button onclick="showOrderDetail(<?= $order['id'] ?>)" class="px-2 py-1 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600 whitespace-nowrap">
                                    <i class="fas fa-eye mr-1"></i>상세
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

// 단일 주문 접수
async function approveOrder(orderId) {
    if (!confirm('이 주문을 접수하시겠습니까?')) return;

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
            alert('주문이 접수되었습니다.');
            location.reload();
        } else {
            alert('접수 실패: ' + (result.message || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('접수 처리 중 오류가 발생했습니다.');
    }
}

// 선택된 주문 일괄 접수
async function approveSelected() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('접수할 주문을 선택하세요.');
        return;
    }

    if (!confirm(`${checkboxes.length}건의 주문을 접수하시겠습니까?`)) return;

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

    alert(`접수 완료: ${successCount}건, 실패: ${failCount}건`);
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
            <!-- 기본 정보 -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600">주문번호</label>
                    <div class="font-mono text-blue-600">${order.order_number || 'N/A'}</div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600">서비스</label>
                    <div class="font-medium">${order.service_name || '일반'}</div>
                </div>
            </div>

            <!-- 출발지 정보 -->
            <div class="border-t pt-3">
                <label class="text-xs font-semibold text-gray-600 mb-2 block">📍 출발지</label>
                <div class="bg-blue-50 rounded p-2 space-y-1">
                    <div class="font-medium text-gray-800">${order.departure_company_name || '-'}</div>
                    <div class="text-gray-600">${order.departure_address || ''} ${order.departure_detail || ''}</div>
                    ${order.departure_manager || order.departure_department ? `
                    <div class="text-gray-600">
                        ${order.departure_department || ''} ${order.departure_manager || ''}
                    </div>
                    ` : ''}
                    <div class="text-gray-700 font-medium">📞 ${order.departure_contact || '-'}</div>
                </div>
            </div>

            <!-- 도착지 정보 -->
            <div class="border-t pt-3">
                <label class="text-xs font-semibold text-gray-600 mb-2 block">📍 도착지</label>
                <div class="bg-green-50 rounded p-2 space-y-1">
                    <div class="font-medium text-gray-800">${order.destination_company_name || '-'}</div>
                    <div class="text-gray-600">${order.destination_address || ''} ${order.detail_address || ''}</div>
                    ${order.destination_manager || order.destination_department ? `
                    <div class="text-gray-600">
                        ${order.destination_department || ''} ${order.destination_manager || ''}
                    </div>
                    ` : ''}
                    <div class="text-gray-700 font-medium">📞 ${order.destination_contact || '-'}</div>
                </div>
            </div>

            <!-- 물품 정보 -->
            <div class="border-t pt-3">
                <label class="text-xs font-semibold text-gray-600 mb-2 block">📦 물품 정보</label>
                <div class="bg-yellow-50 rounded p-2 space-y-1">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-xs text-gray-600">품목:</span>
                            <span class="font-medium text-gray-800 ml-1">${order.item_type || '일반'}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600">수량:</span>
                            <span class="font-medium text-gray-800 ml-1">${order.quantity || 1} ${order.unit || '개'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 전달사항/비고 -->
            ${order.delivery_content || order.notes ? `
            <div class="border-t pt-3">
                <label class="text-xs font-semibold text-gray-600 mb-2 block">📝 전달사항/비고</label>
                <div class="bg-gray-50 rounded p-2 text-gray-700">
                    ${order.delivery_content || order.notes || '-'}
                </div>
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