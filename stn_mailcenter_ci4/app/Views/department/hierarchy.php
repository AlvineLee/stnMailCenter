<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 액션 버튼들 -->
    <div class="mb-4 flex gap-2">
        <a href="<?= base_url('department') ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            ← 목록으로
        </a>
        <button onclick="expandAll()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            전체 펼치기
        </button>
        <button onclick="collapseAll()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-600 border border-transparent rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            전체 접기
        </button>
    </div>

    <!-- 부서 계층 구조 -->
    <div class="tree-container">
        <div id="hierarchyTree">
            <!-- STN 네트워크 -->
            <div class="tree-node" data-level="0">
                <div class="tree-node-content" onclick="toggleTreeNode(this)">
                    <div class="tree-toggle">+</div>
                    <div class="tree-icon level-0">🏢</div>
                    <div class="tree-label level-0">STN 네트워크</div>
                    <div class="tree-code">본점</div>
                </div>
                <div class="tree-children">
                    <!-- 개발팀 -->
                    <div class="tree-node" data-level="1">
                        <div class="tree-node-content" onclick="toggleTreeNode(this)">
                            <div class="tree-toggle">+</div>
                            <div class="tree-icon level-1">💻</div>
                            <div class="tree-label level-1">개발팀</div>
                            <div class="tree-code">DEPT001</div>
                        </div>
                        <div class="tree-children">
                            <!-- 프론트엔드팀 -->
                            <div class="tree-node" data-level="2">
                                <div class="tree-node-content">
                                    <div class="tree-toggle">•</div>
                                    <div class="tree-icon level-2">🎨</div>
                                    <div class="tree-label level-2">프론트엔드팀</div>
                                    <div class="tree-code">DEPT001-1</div>
                                </div>
                            </div>
                            <!-- 백엔드팀 -->
                            <div class="tree-node" data-level="2">
                                <div class="tree-node-content">
                                    <div class="tree-toggle">•</div>
                                    <div class="tree-icon level-2">⚙️</div>
                                    <div class="tree-label level-2">백엔드팀</div>
                                    <div class="tree-code">DEPT001-2</div>
                                </div>
                            </div>
                            <!-- QA팀 -->
                            <div class="tree-node" data-level="2">
                                <div class="tree-node-content">
                                    <div class="tree-toggle">•</div>
                                    <div class="tree-icon level-2">🔍</div>
                                    <div class="tree-label level-2">QA팀</div>
                                    <div class="tree-code">DEPT001-3</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 마케팅팀 -->
                    <div class="tree-node" data-level="1">
                        <div class="tree-node-content" onclick="toggleTreeNode(this)">
                            <div class="tree-toggle">+</div>
                            <div class="tree-icon level-1">📢</div>
                            <div class="tree-label level-1">마케팅팀</div>
                            <div class="tree-code">DEPT002</div>
                        </div>
                        <div class="tree-children">
                            <!-- 디지털마케팅팀 -->
                            <div class="tree-node" data-level="2">
                                <div class="tree-node-content">
                                    <div class="tree-toggle">•</div>
                                    <div class="tree-icon level-2">📱</div>
                                    <div class="tree-label level-2">디지털마케팅팀</div>
                                    <div class="tree-code">DEPT002-1</div>
                                </div>
                            </div>
                            <!-- 브랜드마케팅팀 -->
                            <div class="tree-node" data-level="2">
                                <div class="tree-node-content">
                                    <div class="tree-toggle">•</div>
                                    <div class="tree-icon level-2">🎯</div>
                                    <div class="tree-label level-2">브랜드마케팅팀</div>
                                    <div class="tree-code">DEPT002-2</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 영업팀 -->
                    <div class="tree-node" data-level="1">
                        <div class="tree-node-content" onclick="toggleTreeNode(this)">
                            <div class="tree-toggle">+</div>
                            <div class="tree-icon level-1">💼</div>
                            <div class="tree-label level-1">영업팀</div>
                            <div class="tree-code">DEPT003</div>
                        </div>
                        <div class="tree-children">
                            <!-- 국내영업팀 -->
                            <div class="tree-node" data-level="2">
                                <div class="tree-node-content">
                                    <div class="tree-toggle">•</div>
                                    <div class="tree-icon level-2">🏠</div>
                                    <div class="tree-label level-2">국내영업팀</div>
                                    <div class="tree-code">DEPT003-1</div>
                                </div>
                            </div>
                            <!-- 해외영업팀 -->
                            <div class="tree-node" data-level="2">
                                <div class="tree-node-content">
                                    <div class="tree-toggle">•</div>
                                    <div class="tree-icon level-2">🌍</div>
                                    <div class="tree-label level-2">해외영업팀</div>
                                    <div class="tree-code">DEPT003-2</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 인사팀 -->
                    <div class="tree-node" data-level="1">
                        <div class="tree-node-content">
                            <div class="tree-toggle">•</div>
                            <div class="tree-icon level-1">👥</div>
                            <div class="tree-label level-1">인사팀</div>
                            <div class="tree-code">DEPT004</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STN 서울지사 -->
            <div class="tree-node" data-level="0">
                <div class="tree-node-content" onclick="toggleTreeNode(this)">
                    <div class="tree-toggle">+</div>
                    <div class="tree-icon level-0">🏪</div>
                    <div class="tree-label level-0">STN 서울지사</div>
                    <div class="tree-code">지사</div>
                </div>
                <div class="tree-children">
                    <!-- 서울 영업팀 -->
                    <div class="tree-node" data-level="1">
                        <div class="tree-node-content">
                            <div class="tree-toggle">•</div>
                            <div class="tree-icon level-1">🏢</div>
                            <div class="tree-label level-1">서울 영업팀</div>
                            <div class="tree-code">DEPT005</div>
                        </div>
                    </div>
                    <!-- 서울 마케팅팀 -->
                    <div class="tree-node" data-level="1">
                        <div class="tree-node-content">
                            <div class="tree-toggle">•</div>
                            <div class="tree-icon level-1">📈</div>
                            <div class="tree-label level-1">서울 마케팅팀</div>
                            <div class="tree-code">DEPT006</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STN 강남대리점 -->
            <div class="tree-node" data-level="0">
                <div class="tree-node-content">
                    <div class="tree-toggle">•</div>
                    <div class="tree-icon level-0">🏬</div>
                    <div class="tree-label level-0">STN 강남대리점</div>
                    <div class="tree-code">대리점</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTreeNode(element) {
    const children = element.nextElementSibling;
    const toggleIcon = element.querySelector('.tree-toggle');
    
    if (children && toggleIcon) {
        if (children.style.display === 'none' || children.style.display === '') {
            children.style.display = 'block';
            toggleIcon.textContent = '-';
            element.classList.add('expanded');
        } else {
            children.style.display = 'none';
            toggleIcon.textContent = '+';
            element.classList.remove('expanded');
        }
    }
}

function expandAll() {
    document.querySelectorAll('.tree-children').forEach(child => {
        child.style.display = 'block';
    });
    document.querySelectorAll('.tree-toggle').forEach(icon => {
        if (icon.textContent === '+') {
            icon.textContent = '-';
        }
    });
    document.querySelectorAll('.tree-node-content').forEach(node => {
        node.classList.add('expanded');
    });
}

function collapseAll() {
    document.querySelectorAll('.tree-children').forEach(child => {
        child.style.display = 'none';
    });
    document.querySelectorAll('.tree-toggle').forEach(icon => {
        if (icon.textContent === '-') {
            icon.textContent = '+';
        }
    });
    document.querySelectorAll('.tree-node-content').forEach(node => {
        node.classList.remove('expanded');
    });
}

// 페이지 로드 시 초기화 (모든 노드를 접힌 상태로 시작)
document.addEventListener('DOMContentLoaded', function() {
    collapseAll();
});
</script>
<?= $this->endSection() ?>
