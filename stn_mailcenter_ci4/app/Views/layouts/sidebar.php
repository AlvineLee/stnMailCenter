<aside class="sidebar">
    <div class="sidebar-header">
        <?php
        // 서브도메인 설정 가져오기 (우선순위 1)
        $subdomainConfig = config('Subdomain');
        $subdomainInfo = $subdomainConfig->getCurrentConfig();
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $isSubdomain = ($currentSubdomain !== 'default');
        
        $logoPath = null;
        $logoAlt = 'DaumData';
        $logoText = null;
        $logoThemeColor = '#667eea';
        
        // 서브도메인으로 접근한 경우: 서브도메인 로고 우선 사용
        if ($isSubdomain && !empty($subdomainInfo['logo_path'])) {
            $logoPath = base_url($subdomainInfo['logo_path']);
            $logoAlt = $subdomainInfo['name'];
        } elseif ($isSubdomain) {
            // 서브도메인인데 로고 이미지가 없으면 텍스트 로고 사용
            $logoText = $subdomainInfo['logo_text'];
            $logoAlt = $subdomainInfo['name'];
            $logoThemeColor = $subdomainInfo['theme_color'];
        } else {
            // 기본 도메인인 경우: 고객사 로고 조회
            $loginType = session()->get('login_type');
            
            if ($loginType === 'daumdata') {
                // daumdata 로그인: 세션에서 로고 경로 확인
                $companyLogoPathFromSession = session()->get('company_logo_path');
                if ($companyLogoPathFromSession) {
                    $logoPath = base_url($companyLogoPathFromSession);
                }
            } else {
                // STN 로그인: 본점 로고 조회
                $customerId = session()->get('customer_id');
                if ($customerId) {
                    $customerHierarchyModel = new \App\Models\CustomerHierarchyModel();
                    
                    // 사용자가 속한 본점 ID 찾기
                    $headOfficeId = $customerHierarchyModel->getHeadOfficeId($customerId);
                    if ($headOfficeId) {
                        $headOffice = $customerHierarchyModel->getCustomerById($headOfficeId);
                        if ($headOffice && !empty($headOffice['logo_path'])) {
                            $logoPath = base_url($headOffice['logo_path']);
                        }
                    }
                }
            }
        }
        ?>
        <?php if ($logoPath): ?>
            <!-- 로고 이미지가 있을 경우: 로고만 풀로 차지 -->
            <a href="<?= base_url('/') ?>" class="logo-full">
                <img src="<?= $logoPath ?>" alt="<?= esc($logoAlt) ?>" class="logo-full-image">
            </a>
        <?php elseif ($logoText): ?>
            <!-- 서브도메인 텍스트 로고 -->
            <a href="<?= base_url('/') ?>" class="logo-full">
                <div class="logo-text-container" style="background: linear-gradient(135deg, <?= esc($logoThemeColor) ?> 0%, <?= esc($logoThemeColor) ?>dd 100%); color: white; padding: 1rem; text-align: center; font-weight: bold; font-size: 1.25rem; border-radius: 8px;">
                    <?= esc($logoText) ?>
                </div>
            </a>
        <?php else: ?>
            <!-- 로고가 없을 경우: 기본 DaumData 로고 이미지가 풀로 차지 -->
            <a href="<?= base_url('/') ?>" class="logo-full">
                <img src="<?= base_url('assets/images/logo/daumdata_logo_1.png') ?>" alt="DaumData" class="logo-full-image">
            </a>
        <?php endif; ?>
    </div>
    
    <div class="user-info">
        <div class="user-details">
            <span class="user-name"><?= session()->get('real_name') ?? session()->get('user_name') ?? session()->get('username') ?? 'Guest' ?></span>
        </div>
        <a href="<?= base_url('auth/logout') ?>" class="logout-link">🚪 로그아웃</a>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php 
            // 헬퍼 로드
            helper('menu');
            
            // 슈퍼어드민 체크
            $loginType = session()->get('login_type');
            $userRole = session()->get('user_role');
            $userType = session()->get('user_type');
            
            $isSuperAdmin = false;
            if ($loginType === 'daumdata' && $userType == '1') {
                $isSuperAdmin = true;
            } elseif (!$loginType || $loginType === 'stn') {
                if ($userRole === 'super_admin') {
                    $isSuperAdmin = true;
                }
            }
            
            // 슈퍼어드민이 메인도메인 접근 시 주문접수/배송조회/이용내역상세조회 숨김
            $showOrderMenus = !($isSuperAdmin && $currentSubdomain === 'default');
            
            if ($showOrderMenus):
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
                    <a href="<?= base_url('history/list') ?>" class="nav-link">
                        <span class="nav-icon">📋</span>
                        <span class="nav-text">이용내역상세조회</span>
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="<?= base_url('member/list') ?>" class="nav-link">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">회원정보</span>
                </a>
            </li>
            <?php
            // daumdata 로그인 메뉴
            if ($loginType === 'daumdata'):
                // 고객 관리 메뉴 (user_type = 1만)
                if ($userType == '1'):
            ?>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">고객 관리</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('insung/company-list') ?>">고객사 관리</a></li>
                    <li><a href="<?= base_url('insung/user-list') ?>">고객사 회원정보</a></li>
                </ul>
            </li>
            <?php
                endif;
                
                // 콜센터 관리 메뉴 (user_type = 1)
                if ($userType == '1'):
            ?>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">콜센터 관리</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('insung/cc-list') ?>">콜센터 목록</a></li>
                </ul>
            </li>
            <?php
                endif;
                
                // 관리자 설정 메뉴 (user_type = 1) - 통합된 관리자 설정 메뉴
                if ($userType == '1'):
            ?>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">관리자 설정</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('admin/order-type') ?>">오더유형 설정</a></li>
                    <li><a href="<?= base_url('admin/pricing') ?>">요금설정</a></li>
                    <li><a href="<?= base_url('admin/order-list') ?>">전체내역조회</a></li>
                    <li><a href="<?= base_url('admin/company-list') ?>">거래처관리</a></li>
                </ul>
            </li>
            <?php
                endif;
                
                // 콜센터 관리자 메뉴 (user_type = 3) - 거래처관리만
                if ($userType == '3'):
            ?>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">관리자</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('admin/company-list-cc') ?>">거래처관리</a></li>
                </ul>
            </li>
            <?php
                endif;
            else:
                // STN 로그인 메뉴
            ?>
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
            <?php if ($userRole === 'super_admin'): ?>
            
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
            
            
            
            <!-- 관리자 설정 - 통합된 관리자 설정 메뉴 -->
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">관리자 설정</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('admin/order-type') ?>">오더유형 설정</a></li>
                    <li><a href="<?= base_url('admin/pricing') ?>">요금설정</a></li>
                    <li><a href="<?= base_url('shipping-company') ?>">운송사 관리</a></li>
                    <li><a href="<?= base_url('admin/notification') ?>">알림설정</a></li>
                    <li><a href="<?= base_url('admin/order-list') ?>">전체내역조회</a></li>
                    <li><a href="<?= base_url('admin/company-list') ?>">거래처관리</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
