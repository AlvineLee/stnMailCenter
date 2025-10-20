<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">
    <!-- 검색 및 필터 영역 -->
    <div class="bg-gray-50 rounded-lg search-compact">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">고객사</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">전체 고객사</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= $customer['id'] ?>" <?= (isset($selected_customer_id) && $selected_customer_id == $customer['id']) ? 'selected' : '' ?>>
                            <?= $customer['company_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">부서명</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="부서명으로 검색">
            </div>
            <div>
                <button class="search-button">
                    검색
                </button>
            </div>
            <div>
                <a href="<?= base_url('department/create') ?>" class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" style="text-decoration: none;">
                    + 부서 등록
                </a>
            </div>
        </div>
    </div>

    <!-- 테이블 컨테이너 -->
    <div class="list-table-container">
        <table id="departmentTable">
            <thead>
                <tr>
                    <th data-column="department_code" draggable="true" class="draggable-header">
                        부서코드
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="department_name" draggable="true" class="draggable-header">
                        부서명
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="manager_name" draggable="true" class="draggable-header">
                        부서장
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="manager_contact" draggable="true" class="draggable-header">
                        연락처
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="is_active" draggable="true" class="draggable-header">
                        상태
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="created_at" draggable="true" class="draggable-header">
                        등록일
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                    <th data-column="actions" draggable="true" class="draggable-header">
                        관리
                        <span class="drag-handle">⋮⋮</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $department): ?>
                <tr>
                    <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px; font-weight: 600;">
                        <?= $department['department_code'] ?>
                    </td>
                    <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= $department['department_name'] ?>
                    </td>
                    <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= $department['manager_name'] ?>
                    </td>
                    <td style="text-align: left; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= $department['manager_contact'] ?>
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <span class="status-badge" style="padding: 2px 6px; border-radius: 2px; font-size: 10px; font-weight: 600; text-transform: uppercase; <?= $department['is_active'] ? 'background: #dcfce7; color: #166534;' : 'background: #fecaca; color: #991b1b;' ?>">
                            <?= $department['is_active'] ? '활성' : '비활성' ?>
                        </span>
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <?= date('Y-m-d', strtotime($department['created_at'])) ?>
                    </td>
                    <td style="text-align: center; font-size: 12px; height: 18px; padding: 2px 8px;">
                        <div class="action-buttons" style="display: flex; gap: 4px; justify-content: center;">
                            <a href="<?= base_url('department/show/' . $department['id']) ?>" style="padding: 2px 6px; font-size: 11px; height: 20px; min-width: 40px; display: inline-block; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; text-decoration: none; text-align: center;">상세</a>
                            <a href="<?= base_url('department/edit/' . $department['id']) ?>" style="padding: 2px 6px; font-size: 11px; height: 20px; min-width: 40px; display: inline-block; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; text-decoration: none; text-align: center;">수정</a>
                            <button onclick="deleteDepartment(<?= $department['id'] ?>)" style="padding: 2px 6px; font-size: 11px; height: 20px; min-width: 40px; display: inline-block; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 600; cursor: pointer;">삭제</button>
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
    const table = document.getElementById('departmentTable');
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

function deleteDepartment(id) {
    if (confirm('정말로 이 부서를 삭제하시겠습니까?')) {
        // 프로토타입에서는 실제 삭제하지 않음
        alert('프로토타입 모드: 부서 삭제 기능은 실제 DB 연결 후 사용 가능합니다.');
    }
}
</script>
<?= $this->endSection() ?>
