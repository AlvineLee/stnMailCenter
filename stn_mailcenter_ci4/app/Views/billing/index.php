<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">

    <!-- 검색 영역 -->
    <div class="bg-gray-50 rounded-lg search-compact">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">청구 유형</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체 유형</option>
                    <?php foreach ($billing_types as $key => $value): ?>
                        <option value="<?= $key ?>" <?= (isset($filters['billing_type']) && $filters['billing_type'] == $key) ? 'selected' : '' ?>>
                            <?= $value ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">청구 상태</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체 상태</option>
                    <?php foreach ($billing_statuses as $key => $value): ?>
                        <option value="<?= $key ?>" <?= (isset($filters['status']) && $filters['status'] == $key) ? 'selected' : '' ?>>
                            <?= $value ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">시작일</label>
                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $filters['start_date'] ?? '' ?>">
            </div>
            <div>
                <button class="search-button">검색</button>
            </div>
        </div>
    </div>

    <!-- 테이블 컨테이너 -->
    <div class="list-table-container">
        <table id="billingTable">
            <thead>
                <tr>
                    <th data-column="billing_number" draggable="true" class="draggable-header">
                        청구번호
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="billing_type" draggable="true" class="draggable-header">
                        청구유형
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="customer_name" draggable="true" class="draggable-header">
                        고객사
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="department_names" draggable="true" class="draggable-header">
                        부서
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="final_amount" draggable="true" class="draggable-header">
                        청구금액
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="status" draggable="true" class="draggable-header">
                        상태
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="due_date" draggable="true" class="draggable-header">
                        납기일
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="actions" draggable="true" class="draggable-header">
                        관리
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($billings as $billing): ?>
                <tr>
                    <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px; font-weight: 600;">
                        <?= $billing['billing_number'] ?>
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= $billing_types[$billing['billing_type']] ?>
                    </td>
                    <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= $billing['customer_name'] ?>
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= $billing['department_names'] ?? '-' ?>
                    </td>
                    <td style="text-align: right; font-size: 12px; height: 18px; padding: 2px 8px; font-weight: 600;">
                        <?= number_format($billing['final_amount']) ?>원
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <span class="status-badge" style="padding: 2px 6px; border-radius: 2px; font-size: 10px; font-weight: 600; text-transform: uppercase; <?= $billing['status'] == 'paid' ? 'background: #dcfce7; color: #166534;' : ($billing['status'] == 'sent' ? 'background: #dbeafe; color: #1e40af;' : 'background: #fef3c7; color: #92400e;') ?>">
                            <?= $billing_statuses[$billing['status']] ?>
                        </span>
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= $billing['due_date'] ?>
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <div class="action-buttons" style="display: flex; gap: 4px; justify-content: center; flex-wrap: wrap;">
                            <a href="<?= base_url('billing/show/' . $billing['id']) ?>" style="padding: 2px 6px; font-size: 11px; height: 20px; min-width: 40px; display: inline-block; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; text-decoration: none; text-align: center;">상세</a>
                            <a href="<?= base_url('billing/edit/' . $billing['id']) ?>" style="padding: 2px 6px; font-size: 11px; height: 20px; min-width: 40px; display: inline-block; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; text-decoration: none; text-align: center;">수정</a>
                            <?php if ($billing['status'] == 'draft'): ?>
                                <button onclick="sendBilling(<?= $billing['id'] ?>)" style="padding: 2px 6px; font-size: 11px; height: 20px; min-width: 40px; display: inline-block; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; cursor: pointer;">발송</button>
                            <?php endif; ?>
                            <button onclick="deleteBilling(<?= $billing['id'] ?>)" style="padding: 2px 6px; font-size: 11px; height: 20px; min-width: 40px; display: inline-block; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; cursor: pointer;">삭제</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <div class="list-pagination flex justify-center">
        <div class="pagination flex space-x-2">
            <button class="nav-button">이전</button>
            <button class="page-number active">1</button>
            <button class="page-number">2</button>
            <button class="page-number">3</button>
            <button class="nav-button">다음</button>
        </div>
    </div>
</div>

<script>
// 드래그 앤 드롭 기능
let draggedElement = null;
let draggedIndex = -1;

document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('billingTable');
    const headers = table.querySelectorAll('th[draggable="true"]');
    
    headers.forEach((header, index) => {
        header.addEventListener('dragstart', function(e) {
            draggedElement = this;
            draggedIndex = index;
            this.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.outerHTML);
        });
        
        header.addEventListener('dragend', function(e) {
            this.style.opacity = '1';
            draggedElement = null;
            draggedIndex = -1;
        });
        
        header.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        
        header.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedElement && draggedElement !== this) {
                const targetIndex = Array.from(headers).indexOf(this);
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');
                
                // 헤더 순서 변경
                if (draggedIndex < targetIndex) {
                    this.parentNode.insertBefore(draggedElement, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(draggedElement, this);
                }
                
                // 데이터 행의 셀 순서도 변경
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const draggedCell = cells[draggedIndex];
                    const targetCell = cells[targetIndex];
                    
                    if (draggedCell && targetCell) {
                        if (draggedIndex < targetIndex) {
                            targetCell.parentNode.insertBefore(draggedCell, targetCell.nextSibling);
                        } else {
                            targetCell.parentNode.insertBefore(draggedCell, targetCell);
                        }
                    }
                });
                
                // 드래그 아이콘 업데이트
                updateDragIcons();
            }
        });
        
        // 드래그 아이콘에 호버 효과
        header.addEventListener('mouseenter', function() {
            if (this.draggable) {
                this.style.background = '#e2e8f0';
            }
        });
        
        header.addEventListener('mouseleave', function() {
            if (this.draggable) {
                this.style.background = '#f3f4f6';
            }
        });
    });
    
    function updateDragIcons() {
        headers.forEach(header => {
            const icon = header.querySelector('span');
            if (icon) {
                icon.style.color = '#94a3b8';
            }
        });
    }
});

function sendBilling(id) {
    if (confirm('청구서를 발송하시겠습니까?')) {
        // 프로토타입에서는 실제 발송하지 않음
        alert('프로토타입 모드: 청구서 발송 기능은 실제 DB 연결 후 사용 가능합니다.');
    }
}

function deleteBilling(id) {
    if (confirm('정말로 이 청구서를 삭제하시겠습니까?')) {
        // 프로토타입에서는 실제 삭제하지 않음
        alert('프로토타입 모드: 청구서 삭제 기능은 실제 DB 연결 후 사용 가능합니다.');
    }
}
</script>
<?= $this->endSection() ?>
