<aside class="sidebar">
    <div class="sidebar-header">
        <?php
        // 로그인한 사용자의 고객사 로고 조회 (본점 로고)
        $customerLogoPath = null;
        $customerId = session()->get('customer_id');
        if ($customerId) {
            $customerHierarchyModel = new \App\Models\CustomerHierarchyModel();
            
            // 사용자가 속한 본점 ID 찾기
            $headOfficeId = $customerHierarchyModel->getHeadOfficeId($customerId);
            if ($headOfficeId) {
                $headOffice = $customerHierarchyModel->getCustomerById($headOfficeId);
                if ($headOffice && !empty($headOffice['logo_path'])) {
                    $customerLogoPath = base_url($headOffice['logo_path']);
                }
            }
        }
        ?>
        <?php if ($customerLogoPath): ?>
            <!-- 로고가 있을 경우: 로고만 풀로 차지 -->
            <a href="<?= base_url('/') ?>" class="logo-full">
                <img src="<?= $customerLogoPath ?>" alt="회사 로고" class="logo-full-image">
            </a>
        <?php else: ?>
            <!-- 로고가 없을 경우: 기본 STN 로고 이미지가 풀로 차지 -->
            <a href="<?= base_url('/') ?>" class="logo-full">
                <img src="<?= base_url('assets/images/logo/logo_STN.png') ?>" alt="STN Network" class="logo-full-image">
            </a>
        <?php endif; ?>
    </div>
    
    <div class="user-info">
        <div class="user-details">
            <span class="user-name"><?= session()->get('real_name') ?? session()->get('username') ?? 'Guest' ?></span>
        </div>
        <a href="<?= base_url('auth/logout') ?>" class="logout-link">➡ logout</a>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php 
            // 헬퍼 로드
            helper('menu');
            
            // 사용자의 서비스 권한 조회
            $userPermissions = getUserServicePermissions();
            
            // DB에서 서비스 타입을 가져와서 동적으로 메뉴 구조 생성
            $menuItems = buildDynamicServiceMenu($userPermissions);
            
            // 권한이 있는 서비스가 하나라도 있으면 주문접수 메뉴 표시
            if (!empty($menuItems)):
            ?>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">✓</span>
                    <span class="nav-text">주문접수</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <?php foreach ($menuItems as $item): ?>
                        <?php if ($item['type'] === 'single'): ?>
                            <?php 
                            $isExternal = !empty($item['menu']['is_external_link']);
                            $menuUrl = $isExternal ? $item['menu']['url'] : base_url($item['menu']['url']);
                            $target = $isExternal ? 'target="_blank" rel="noopener noreferrer"' : '';
                            ?>
                            <li><a href="<?= $menuUrl ?>" <?= $target ?>><img src="<?= base_url($item['menu']['icon']) ?>" class="menu-icon" alt=""> <?= $item['menu']['name'] ?></a></li>
                        <?php elseif ($item['type'] === 'submenu'): ?>
                            <li class="has-submenu">
                                <a href="#" data-toggle="submenu"><img src="<?= base_url($item['menu']['icon']) ?>" class="menu-icon" alt=""> <?= $item['menu']['name'] ?> <span class="nav-arrow">v</span></a>
                                <ul class="submenu">
                                    <?php foreach ($item['children'] as $childKey => $childMenu): ?>
                                    <?php 
                                    $isExternal = !empty($childMenu['is_external_link']);
                                    $childUrl = $isExternal ? $childMenu['url'] : base_url($childMenu['url']);
                                    $target = $isExternal ? 'target="_blank" rel="noopener noreferrer"' : '';
                                    ?>
                                    <li><a href="<?= $childUrl ?>" <?= $target ?>><img src="<?= base_url($childMenu['icon']) ?>" class="menu-icon" alt=""> <?= $childMenu['name'] ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="<?= base_url('delivery/list') ?>" class="nav-link">
                    <span class="nav-icon">🚚</span>
                    <span class="nav-text">배송조회</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('member/list') ?>" class="nav-link">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">회원정보</span>
                </a>
            </li>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">고객 관리</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('customer/head') ?>">본점 관리</a></li>
                    <li><a href="<?= base_url('customer/branch') ?>">지사 관리</a></li>
                    <li><a href="<?= base_url('customer/agency') ?>">대리점 관리</a></li>
                    <li><a href="<?= base_url('customer/budget') ?>">예산 관리</a></li>
                    <li class="has-submenu">
                        <a href="#" data-toggle="submenu">🏢 부서 관리 <span class="nav-arrow">v</span></a>
                        <ul class="submenu">
                            <li><a href="<?= base_url('department') ?>">부서 목록</a></li>
                            <li><a href="<?= base_url('department/create') ?>">부서 등록</a></li>
                            <li><a href="<?= base_url('department/hierarchy') ?>">부서 계층</a></li>
                        </ul>
                    </li>
                    <li class="has-submenu">
                        <a href="#" data-toggle="submenu">💰 청구 관리 <span class="nav-arrow">v</span></a>
                        <ul class="submenu">
                            <li><a href="<?= base_url('billing') ?>">청구 현황</a></li>
                            <li><a href="<?= base_url('billing/department') ?>">부서별 청구</a></li>
                            <li><a href="<?= base_url('billing/department-group') ?>">부서묶음 청구</a></li>
                            <li><a href="<?= base_url('billing/customer-group') ?>">고객묶음 청구</a></li>
                            <li><a href="<?= base_url('billing/create') ?>">청구서 생성</a></li>
                            <li><a href="<?= base_url('billing/history') ?>">청구 내역</a></li>
                        </ul>
                    </li>
                    <li><a href="<?= base_url('customer/items') ?>">📋 항목관리</a></li>
                    <li><a href="<?= base_url('store-registration') ?>">🏪 입점관리</a></li>
                </ul>
            </li>
            <?php if (session()->get('user_role') === 'super_admin'): ?>
            
            <!-- 콜센터 관리 -->
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">콜센터 관리</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('call-center/building') ?>">🏢 빌딩 콜센터 관리</a></li>
                    <!--
                    <li><a href="<?= base_url('call-center/driver') ?>">👥 기사관리</a></li>
                    <li><a href="<?= base_url('call-center/settlement') ?>">🧮 정산관리</a></li>
                    <li><a href="<?= base_url('call-center/billing') ?>">📋 청구관리</a></li>
                    <li><a href="<?= base_url('call-center/receivables') ?>">💰 미수관리</a></li>
                    <li><a href="<?= base_url('call-center/fee') ?>">💵 요금관리</a></li>
                    <li><a href="<?= base_url('call-center/permission') ?>">🔐 권한 설정 관리</a></li>
                    <li><a href="<?= base_url('call-center/tax') ?>">📊 세무 관리</a></li>
                    <li><a href="<?= base_url('call-center/management-info') ?>">📈 경영정보관리</a></li>
                    <li><a href="<?= base_url('call-center/auto-dispatch') ?>">🚗 자동배차관리</a></li>
                    <li><a href="<?= base_url('hub-center') ?>">🏗️ 허브센타관리</a></li>
                    //-->
                    <li><a href="<?= base_url('group-company') ?>">🏢 그룹사 관리</a></li>
                    <li><a href="<?= base_url('logistics-representative') ?>">👑 나도 물류 대표</a></li>
                </ul>
            </li>
            
            
            
            <!-- 관리자 설정 -->
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">관리자 설정</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('admin/order-type') ?>">오더유형 설정</a></li>
                    <li><a href="<?= base_url('shipping-company') ?>">운송사 관리</a></li>
                    <li><a href="<?= base_url('admin/notification') ?>">알림설정</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
