<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('/delivery/list', ['method' => 'GET']) ?>
        <div class="flex">
            <div class="flex-1">
                <label>검색</label>
                <select name="search_type">
                    <?php foreach ($search_type_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $search_type === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label>검색어</label>
                <input type="text" name="search_keyword" value="<?= esc($search_keyword) ?>" placeholder="검색어 입력">
            </div>
            <div class="flex-1">
                <label>배송상태</label>
                <select name="status">
                    <?php foreach ($status_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $status_filter === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label>서비스</label>
                <select name="service">
                    <option value="all" <?= $service_filter === 'all' ? 'selected' : '' ?>>전체</option>
                    <?php foreach ($service_types as $service): ?>
                        <option value="<?= $service['service_category'] ?>" <?= $service_filter === $service['service_category'] ? 'selected' : '' ?>>
                            <?= ucfirst($service['service_category']) ?> (<?= $service['count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="search-button">검색</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 검색 결과 정보 -->
    <div class="mb-4 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                <?php if (isset($pagination) && $pagination): ?>
                    총 <?= number_format($pagination['total_count']) ?>건 중 
                    <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>건 표시
                <?php else: ?>
                    검색 결과가 없습니다.
                <?php endif; ?>
            </div>
            <?php if (isset($db_error)): ?>
                <div class="text-sm text-red-600">
                    <?= esc($db_error) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 배송 목록 테이블 -->
    <div class="list-table-container">
        <?php if (isset($error)): ?>
            <div class="text-center py-8 text-red-600">
                <?= esc($error) ?>
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="text-center py-8 text-gray-500">
                검색 결과가 없습니다.
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>주문번호</th>
                    <th>서비스</th>
                    <th>고객사</th>
                    <th>출발지</th>
                    <th>도착지</th>
                    <th>상태</th>
                    <th>주문일시</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= esc($order['order_number']) ?></td>
                    <td><?= esc($order['service_name']) ?></td>
                    <td><?= esc($order['customer_name']) ?></td>
                    <td><?= esc($order['departure_address']) ?></td>
                    <td><?= esc($order['destination_address']) ?></td>
                    <td>
                        <?php
                        $statusLabels = [
                            'pending' => '대기중',
                            'processing' => '처리중',
                            'completed' => '완료',
                            'cancelled' => '취소'
                        ];
                        $statusLabel = $statusLabels[$order['status']] ?? $order['status'];
                        ?>
                        <span class="status-badge status-<?= $order['status'] ?>"><?= $statusLabel ?></span>
                    </td>
                    <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                    <td class="action-buttons">
                        <button onclick="viewOrderDetail('<?= esc($order['encrypted_order_number']) ?>')">상세</button>
                        <?php if ($order['status'] === 'pending'): ?>
                            <button onclick="cancelOrder(<?= $order['id'] ?>)">취소</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if (isset($pagination) && $pagination): ?>
    <div class="list-pagination flex justify-center">
        <div class="pagination flex space-x-2">
            <?php if ($pagination['has_prev']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" class="nav-button">이전</a>
            <?php else: ?>
                <span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">이전</span>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $pagination['current_page'] - 2);
            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
                $isActive = $i == $pagination['current_page'];
                $queryParams = array_merge($_GET, ['page' => $i]);
            ?>
                <a href="?<?= http_build_query($queryParams) ?>" class="page-number <?= $isActive ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($pagination['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" class="nav-button">다음</a>
            <?php else: ?>
                <span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">다음</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- 주문 상세 팝업 모달 -->
<div id="orderDetailModal" class="modal-overlay" style="display: none; z-index: 9999 !important;">
    <div class="modal-container">
        <div class="modal-header">
            <h3>주문 상세 정보</h3>
            <button class="modal-close" onclick="closeOrderDetail()">&times;</button>
        </div>
        <div class="modal-content">
            <div class="detail-section">
                <h4>기본 정보</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>주문번호</label>
                        <span id="detail-order-number">-</span>
                    </div>
                    <div class="detail-item">
                        <label>서비스</label>
                        <span id="detail-service">-</span>
                    </div>
                    <div class="detail-item">
                        <label>고객사</label>
                        <span id="detail-customer">-</span>
                    </div>
                    <div class="detail-item">
                        <label>상태</label>
                        <span id="detail-status">-</span>
                    </div>
                    <div class="detail-item">
                        <label>주문일시</label>
                        <span id="detail-created-at">-</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>배송 정보</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>출발지</label>
                        <span id="detail-departure">-</span>
                    </div>
                    <div class="detail-item">
                        <label>도착지</label>
                        <span id="detail-destination">-</span>
                    </div>
                    <div class="detail-item">
                        <label>예상 배송일</label>
                        <span id="detail-delivery-date">-</span>
                    </div>
                    <div class="detail-item">
                        <label>배송비</label>
                        <span id="detail-delivery-fee">-</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>추가 정보</h4>
                <div class="detail-grid">
                    <div class="detail-item full-width">
                        <label>특이사항</label>
                        <span id="detail-notes">-</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="form-button form-button-secondary" onclick="closeOrderDetail()">닫기</button>
        </div>
    </div>
</div>

<style>
/* 모달 오버레이 */
.modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.5) !important;
    z-index: 1000 !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
}

/* 모달 컨테이너 */
.modal-container {
    background: white !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
    max-width: 600px !important;
    width: 90% !important;
    max-height: 80vh !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
}

/* 모달 헤더 */
.modal-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 16px 20px !important;
    border-bottom: 1px solid #e5e7eb !important;
    background: #f8fafc !important;
}

.modal-header h3 {
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    margin: 0 !important;
}

.modal-close {
    background: none !important;
    border: none !important;
    font-size: 24px !important;
    color: #6b7280 !important;
    cursor: pointer !important;
    padding: 0 !important;
    width: 24px !important;
    height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 4px !important;
    transition: all 0.2s ease !important;
}

.modal-close:hover {
    background: #f3f4f6 !important;
    color: #374151 !important;
}

/* 모달 콘텐츠 */
.modal-content {
    padding: 20px !important;
    overflow-y: auto !important;
    flex: 1 !important;
}

.detail-section {
    margin-bottom: 24px !important;
}

.detail-section:last-child {
    margin-bottom: 0 !important;
}

.detail-section h4 {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    margin: 0 0 12px 0 !important;
    padding-bottom: 8px !important;
    border-bottom: 1px solid #e5e7eb !important;
}

.detail-grid {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 16px !important;
}

.detail-item {
    display: flex !important;
    flex-direction: column !important;
}

.detail-item.full-width {
    grid-column: 1 / -1 !important;
}

.detail-item label {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #6b7280 !important;
    margin-bottom: 4px !important;
}

.detail-item span {
    font-size: 13px !important;
    color: #374151 !important;
    padding: 6px 8px !important;
    background: #f9fafb !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 4px !important;
    min-height: 20px !important;
    word-break: break-word !important;
}

/* 모달 푸터 */
.modal-footer {
    padding: 16px 20px !important;
    border-top: 1px solid #e5e7eb !important;
    background: #f8fafc !important;
    display: flex !important;
    justify-content: flex-end !important;
    gap: 8px !important;
}

/* 반응형 */
@media (max-width: 768px) {
    .modal-container {
        width: 95% !important;
        max-height: 90vh !important;
    }
    
    .detail-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function viewOrderDetail(encryptedOrderNumber) {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 로딩 상태 표시
    showLoadingState();
    
    // AJAX로 주문 상세 정보 가져오기 (이미 암호화된 주문번호 사용)
    fetch(`/delivery/getOrderDetail?order_number=${encryptedOrderNumber}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            populateOrderDetail(data.data);
            // 모달 표시
            document.getElementById('orderDetailModal').style.setProperty('display', 'flex', 'important');
            document.body.style.overflow = 'hidden';
        } else {
            showError(data.message || '주문 정보를 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('주문 정보 조회 중 오류가 발생했습니다.');
    })
    .finally(() => {
        hideLoadingState();
    });
}

function populateOrderDetail(orderData) {
    // 모달 콘텐츠를 원래 상태로 복원
    restoreModalContent();
    
    // 기본 정보
    const orderNumberEl = document.getElementById('detail-order-number');
    const serviceEl = document.getElementById('detail-service');
    const customerEl = document.getElementById('detail-customer');
    const statusEl = document.getElementById('detail-status');
    const createdAtEl = document.getElementById('detail-created-at');
    
    if (orderNumberEl) orderNumberEl.textContent = orderData.order_number || '-';
    if (serviceEl) serviceEl.textContent = orderData.service_name || '-';
    if (customerEl) customerEl.textContent = orderData.customer_name || '-';
    
    // 상태 배지 생성
    if (statusEl) {
        statusEl.innerHTML = `<span class="status-badge status-${orderData.status}">${orderData.status_label}</span>`;
    }
    
    // 날짜 포맷팅
    const createdDate = orderData.created_at ? new Date(orderData.created_at).toLocaleString('ko-KR') : '-';
    if (createdAtEl) createdAtEl.textContent = createdDate;
    
    // 배송 정보
    const departureAddress = orderData.departure_address || '-';
    const destinationAddress = orderData.destination_address || '-';
    const deliveryDate = orderData.delivery_time ? new Date(orderData.delivery_time).toLocaleDateString('ko-KR') : '-';
    const totalAmount = orderData.total_amount || '0원';
    
    const departureEl = document.getElementById('detail-departure');
    const destinationEl = document.getElementById('detail-destination');
    const deliveryDateEl = document.getElementById('detail-delivery-date');
    const deliveryFeeEl = document.getElementById('detail-delivery-fee');
    const notesEl = document.getElementById('detail-notes');
    
    if (departureEl) departureEl.textContent = departureAddress;
    if (destinationEl) destinationEl.textContent = destinationAddress;
    if (deliveryDateEl) deliveryDateEl.textContent = deliveryDate;
    if (deliveryFeeEl) deliveryFeeEl.textContent = totalAmount;
    if (notesEl) notesEl.textContent = orderData.notes || '-';
}

function showLoadingState() {
    // 로딩 상태 표시 (모달 내부에 로딩 메시지)
    const modalContent = document.querySelector('.modal-content');
    modalContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">주문 정보를 불러오는 중...</div>';
    
    // 모달 표시
    document.getElementById('orderDetailModal').style.setProperty('display', 'flex', 'important');
    document.body.style.overflow = 'hidden';
}

function hideLoadingState() {
    // 로딩 상태는 populateOrderDetail에서 실제 내용으로 대체됨
}

function showError(message) {
    // 에러 메시지 표시
    const modalContent = document.querySelector('.modal-content');
    modalContent.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="color: #ef4444; margin-bottom: 16px;">⚠️</div>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">오류 발생</div>
            <div style="color: #6b7280;">${message}</div>
        </div>
    `;
    
    // 모달 표시
    document.getElementById('orderDetailModal').style.setProperty('display', 'flex', 'important');
    document.body.style.overflow = 'hidden';
}

function closeOrderDetail() {
    document.getElementById('orderDetailModal').style.setProperty('display', 'none', 'important');
    document.body.style.overflow = 'auto';
    
    // 모달 콘텐츠를 원래 상태로 복원
    restoreModalContent();
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

function restoreModalContent() {
    const modalContent = document.querySelector('.modal-content');
    modalContent.innerHTML = `
        <div class="detail-section">
            <h4>기본 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>주문번호</label>
                    <span id="detail-order-number">-</span>
                </div>
                <div class="detail-item">
                    <label>서비스</label>
                    <span id="detail-service">-</span>
                </div>
                <div class="detail-item">
                    <label>고객사</label>
                    <span id="detail-customer">-</span>
                </div>
                <div class="detail-item">
                    <label>상태</label>
                    <span id="detail-status">-</span>
                </div>
                <div class="detail-item">
                    <label>주문일시</label>
                    <span id="detail-created-at">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>배송 정보</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>출발지</label>
                    <span id="detail-departure">-</span>
                </div>
                <div class="detail-item">
                    <label>도착지</label>
                    <span id="detail-destination">-</span>
                </div>
                <div class="detail-item">
                    <label>예상 배송일</label>
                    <span id="detail-delivery-date">-</span>
                </div>
                <div class="detail-item">
                    <label>배송비</label>
                    <span id="detail-delivery-fee">-</span>
                </div>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>추가 정보</h4>
            <div class="detail-grid">
                <div class="detail-item full-width">
                    <label>특이사항</label>
                    <span id="detail-notes">-</span>
                </div>
            </div>
        </div>
    `;
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeOrderDetail();
    }
});

// 모달 외부 클릭시 닫기
document.getElementById('orderDetailModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeOrderDetail();
    }
});

function cancelOrder(orderId) {
    // 주문 취소 기능 (추후 구현)
    if (confirm('정말로 이 주문을 취소하시겠습니까?')) {
        alert('주문 취소: ' + orderId);
    }
}
</script>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>