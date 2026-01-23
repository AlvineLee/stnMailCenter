<aside class="sidebar">
    <div class="sidebar-header">
        <?php
        // 사이드바 데이터가 컨트롤러에서 전달되지 않은 경우 기본값 설정
        // (점진적 마이그레이션을 위한 폴백)
        if (!isset($sidebar)) {
            $sidebar = [
                'logoPath' => null,
                'logoAlt' => 'DaumData',
                'logoText' => null,
                'logoThemeColor' => '#667eea',
                'isSubdomain' => false,
                'currentSubdomain' => 'default',
                'loginType' => session()->get('login_type'),
                'userRole' => session()->get('user_role'),
                'userType' => session()->get('user_type'),
                'userClass' => session()->get('user_class'),
                'isLoggedIn' => session()->get('is_logged_in'),
                'apiName' => session()->get('api_name'),
                'ccCompName' => session()->get('cc_comp_name'),
                'realName' => session()->get('real_name') ?? session()->get('user_name') ?? session()->get('username') ?? 'Guest',
                'isSuperAdmin' => false,
                'showOrderMenus' => true,
            ];

            // 폴백: 서브도메인 설정 가져오기
            $subdomainConfig = config('Subdomain');
            $subdomainInfo = $subdomainConfig->getCurrentConfig();
            $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
            $isSubdomain = ($currentSubdomain !== 'default');

            $sidebar['isSubdomain'] = $isSubdomain;
            $sidebar['currentSubdomain'] = $currentSubdomain;

            // 폴백: 로고 경로 결정
            if ($isSubdomain && !empty($subdomainInfo['logo_path'])) {
                $sidebar['logoPath'] = base_url($subdomainInfo['logo_path']);
                $sidebar['logoAlt'] = $subdomainInfo['name'];
            } elseif ($isSubdomain) {
                $sidebar['logoText'] = $subdomainInfo['logo_text'];
                $sidebar['logoAlt'] = $subdomainInfo['name'];
                $sidebar['logoThemeColor'] = $subdomainInfo['theme_color'];
            } else {
                $loginType = session()->get('login_type');
                if ($loginType === 'daumdata') {
                    $companyLogoPathFromSession = session()->get('company_logo_path');
                    if ($companyLogoPathFromSession) {
                        $sidebar['logoPath'] = base_url($companyLogoPathFromSession);
                    }
                } else {
                    $customerId = session()->get('customer_id');
                    if ($customerId) {
                        $customerHierarchyModel = new \App\Models\CustomerHierarchyModel();
                        $headOfficeId = $customerHierarchyModel->getHeadOfficeId($customerId);
                        if ($headOfficeId) {
                            $headOffice = $customerHierarchyModel->getCustomerById($headOfficeId);
                            if ($headOffice && !empty($headOffice['logo_path'])) {
                                $sidebar['logoPath'] = base_url($headOffice['logo_path']);
                            }
                        }
                    }
                }
            }

            // 폴백: user_class가 세션에 없으면 DB에서 조회
            if (empty($sidebar['userClass']) && $sidebar['loginType'] === 'daumdata') {
                $userId = session()->get('user_id');
                if ($userId) {
                    $db = \Config\Database::connect();
                    $userBuilder = $db->table('tbl_users_list');
                    $userBuilder->select('user_class');
                    $userBuilder->where('user_id', $userId);
                    $userQuery = $userBuilder->get();
                    if ($userQuery !== false) {
                        $userResult = $userQuery->getRowArray();
                        if ($userResult && isset($userResult['user_class'])) {
                            $sidebar['userClass'] = $userResult['user_class'];
                        }
                    }
                }
            }

            // 폴백: 슈퍼어드민 여부 판단
            $isSuperAdmin = false;
            if ($sidebar['loginType'] === 'daumdata' && $sidebar['userType'] == '1') {
                $isSuperAdmin = true;
            } elseif (!$sidebar['loginType'] || $sidebar['loginType'] === 'stn') {
                if ($sidebar['userRole'] === 'super_admin') {
                    $isSuperAdmin = true;
                }
            }
            $sidebar['isSuperAdmin'] = $isSuperAdmin;
            $sidebar['showOrderMenus'] = !($isSuperAdmin && $currentSubdomain === 'default');
        }

        // 변수 추출 (가독성을 위해)
        $logoPath = $sidebar['logoPath'] ?? null;
        $logoAlt = $sidebar['logoAlt'] ?? 'DaumData';
        $logoText = $sidebar['logoText'] ?? null;
        $logoThemeColor = $sidebar['logoThemeColor'] ?? '#667eea';
        $isSubdomain = $sidebar['isSubdomain'] ?? false;
        $currentSubdomain = $sidebar['currentSubdomain'] ?? 'default';
        $loginType = $sidebar['loginType'] ?? null;
        $userRole = $sidebar['userRole'] ?? null;
        $userType = $sidebar['userType'] ?? null;
        $userClass = $sidebar['userClass'] ?? null;
        $isLoggedIn = $sidebar['isLoggedIn'] ?? false;
        $apiName = $sidebar['apiName'] ?? null;
        $ccCompName = $sidebar['ccCompName'] ?? null;
        $realName = $sidebar['realName'] ?? 'Guest';
        $isSuperAdmin = $sidebar['isSuperAdmin'] ?? false;
        $showOrderMenus = $sidebar['showOrderMenus'] ?? true;
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
            <?php if (!$isSubdomain && $isLoggedIn && ($apiName || $ccCompName)): ?>
                <!-- 메인도메인 로그인: D 로고 + 콜센터명 -->
                <a href="<?= base_url('/') ?>" class="logo-full" style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 45px; height: 50px; overflow: hidden; flex-shrink: 0; border-radius: 8px; background: #f3f4f6;">
                        <img src="<?= base_url('assets/images/logo/daumdata_logo_1.png') ?>" alt="D" style="height: 50px; width: auto; object-fit: cover; object-position: left center;">
                    </div>
                    <div style="flex: 1; min-width: 0; line-height: 1.3; color: #374151;">
                        <?php if ($apiName): ?>
                            <div style="font-size: 18px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= esc($apiName) ?></div>
                        <?php endif; ?>
                        <?php if ($ccCompName): ?>
                            <div style="font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">(<?= esc($ccCompName) ?>)</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php else: ?>
                <!-- 로고가 없을 경우: 기본 DaumData 로고 이미지가 풀로 차지 -->
                <a href="<?= base_url('/') ?>" class="logo-full">
                    <img src="<?= base_url('assets/images/logo/daumdata_logo_1.png') ?>" alt="DaumData" class="logo-full-image">
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="user-info">
        <div class="user-details">
            <span class="user-name"><?= esc($realName) ?></span>
        </div>
        <a href="<?= base_url('auth/logout') ?>" class="logout-link">로그아웃</a>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php
            // 헬퍼 로드
            helper('menu');

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
                    <span class="nav-text">내정보수정</span>
                </a>
            </li>
            <?php
            // daumdata 로그인 메뉴
            if ($loginType === 'daumdata'):
                // 거래처 코드 2338395 전용 메뉴 (인성주문)
                $userCompCode = session()->get('user_company');
                if ($userCompCode == '2338395'):
            ?>
            <li class="nav-item">
                <a href="<?= base_url('insung-order/list') ?>" class="nav-link">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">인성주문</span>
                </a>
            </li>
            <?php
                endif;

                // 거래처관리 메뉴 (user_type = 1만)
                if ($userType == '1'):
            ?>
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">거래처관리</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('insung/company-list') ?>">거래처 관리</a></li>
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
                    <!-- 통계 메뉴는 데이터 누적 후 활성화 예정 -->
                    <!-- <li><a href="<?= base_url('insung/stats') ?>">📊 퀵사별 통계</a></li> -->
                </ul>
            </li>
            <?php
                endif;

                // 관리자 설정 메뉴 (user_type = 1)
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
                    <li><a href="<?= base_url('admin/settings') ?>">설정</a></li>
                    <li><a href="<?= base_url('admin/login-attempts') ?>">로그인 기록</a></li>
                    <li><a href="<?= base_url('admin/api-list') ?>">인성API연계센터 관리</a></li>
                </ul>
            </li>
            <?php
                endif;

                // 콜센터 관리자 메뉴 (user_type = 3) 또는 거래처 관리자 메뉴 (user_class = 1)
                if ($userType == '3' || $userClass == '1'):
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
                    <li><a href="<?= base_url('admin/pricing') ?>">요금설정</a></li>
                    <li><a href="<?= base_url('shipping-company') ?>">운송사 관리</a></li>
                    <li><a href="<?= base_url('admin/notification') ?>">알림설정</a></li>
                    <li><a href="<?= base_url('admin/settings') ?>">설정</a></li>
                    <li><a href="<?= base_url('admin/login-attempts') ?>">로그인 기록</a></li>
                    <li><a href="<?= base_url('admin/api-list') ?>">인성API연계센터 관리</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
