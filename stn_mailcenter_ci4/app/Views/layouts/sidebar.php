<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= base_url('/') ?>" class="logo">
            <div class="logo-icon">STN</div>
            <div class="logo-text">
                <div class="company-name">STN Network</div>
                <div class="service-name">ONE'CALL</div>
            </div>
        </a>
        <!-- 모바일 닫기 버튼 -->
        <button id="sidebarClose" class="lg:hidden absolute top-4 right-4 p-2 text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <div class="user-info">
        <div class="user-details">
            <span class="user-name"><?= session()->get('real_name') ?? session()->get('username') ?? 'Guest' ?></span>
            <span class="customer-name"><?= session()->get('customer_name') ?? '' ?></span>
        </div>
        <a href="<?= base_url('auth/logout') ?>" class="logout-link">➡ logout</a>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">✓</span>
                    <span class="nav-text">주문접수</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('service/mailroom') ?>"><img src="<?= base_url('assets/icons/18.png') ?>" class="menu-icon" alt=""> 메일룸서비스</a></li>
                    <li class="has-submenu">
                        <a href="#" data-toggle="submenu"><img src="<?= base_url('assets/icons/71.png') ?>" class="menu-icon" alt=""> 퀵서비스 <span class="nav-arrow">v</span></a>
                        <ul class="submenu">
                            <li><a href="<?= base_url('service/quick-motorcycle') ?>"><img src="<?= base_url('assets/icons/49.png') ?>" class="menu-icon" alt=""> 오토바이(소화물)</a></li>
                            <li><a href="<?= base_url('service/quick-vehicle') ?>"><img src="<?= base_url('assets/icons/25.png') ?>" class="menu-icon" alt=""> 차량(화물)</a></li>
                            <li><a href="<?= base_url('service/quick-flex') ?>"><img src="<?= base_url('assets/icons/169.png') ?>" class="menu-icon" alt=""> 플렉스(소화물)</a></li>
                            <li><a href="<?= base_url('service/quick-moving') ?>"><img src="<?= base_url('assets/icons/200.png') ?>" class="menu-icon" alt=""> 이사짐화물(소형)</a></li>
                        </ul>
                    </li>
                    <li><a href="<?= base_url('service/international') ?>"><img src="<?= base_url('assets/icons/12.png') ?>" class="menu-icon" alt=""> 해외특송서비스</a></li>
                    <li class="has-submenu">
                        <a href="#" data-toggle="submenu"><img src="<?= base_url('assets/icons/linked.svg') ?>" class="menu-icon" alt=""> 연계배송서비스 <span class="nav-arrow">v</span></a>
                        <ul class="submenu">
                            <li><a href="<?= base_url('service/linked-bus') ?>"><img src="<?= base_url('assets/icons/bus.svg') ?>" class="menu-icon" alt=""> 고속버스(제로데이)</a></li>
                            <li><a href="<?= base_url('service/linked-ktx') ?>"><img src="<?= base_url('assets/icons/train.svg') ?>" class="menu-icon" alt=""> KTX</a></li>
                            <li><a href="<?= base_url('service/linked-airport') ?>"><img src="<?= base_url('assets/icons/airport.svg') ?>" class="menu-icon" alt=""> 공항</a></li>
                            <li><a href="<?= base_url('service/linked-shipping') ?>"><img src="<?= base_url('assets/icons/ship.svg') ?>" class="menu-icon" alt=""> 해운</a></li>
                        </ul>
                    </li>
                    <li class="has-submenu">
                        <a href="#" data-toggle="submenu"><img src="<?= base_url('assets/icons/parcel.svg') ?>" class="menu-icon" alt=""> 택배서비스 <span class="nav-arrow">v</span></a>
                        <ul class="submenu">
                            <li><a href="<?= base_url('service/parcel-visit') ?>"><img src="<?= base_url('assets/icons/walking.svg') ?>" class="menu-icon" alt=""> 방문택배</a></li>
                            <li><a href="<?= base_url('service/parcel-same-day') ?>"><img src="<?= base_url('assets/icons/clock.svg') ?>" class="menu-icon" alt=""> 당일택배</a></li>
                            <li><a href="<?= base_url('service/parcel-convenience') ?>"><img src="<?= base_url('assets/icons/store.svg') ?>" class="menu-icon" alt=""> 편의점택배</a></li>
                            <li><a href="<?= base_url('service/parcel-bag') ?>"><img src="<?= base_url('assets/icons/bag.svg') ?>" class="menu-icon" alt=""> 행낭</a></li>
                        </ul>
                    </li>
                    <li><a href="<?= base_url('service/postal') ?>"><img src="<?= base_url('assets/icons/envelope.svg') ?>" class="menu-icon" alt=""> 우편서비스</a></li>
                    <li class="has-submenu">
                        <a href="#" data-toggle="submenu"><img src="<?= base_url('assets/icons/general.svg') ?>" class="menu-icon" alt=""> 일반서비스 <span class="nav-arrow">v</span></a>
                        <ul class="submenu">
                            <li><a href="<?= base_url('service/general-document') ?>"><img src="<?= base_url('assets/icons/document.svg') ?>" class="menu-icon" alt=""> 사내문서</a></li>
                            <li><a href="<?= base_url('service/general-errand') ?>"><img src="<?= base_url('assets/icons/people.svg') ?>" class="menu-icon" alt=""> 개인심부름</a></li>
                            <li><a href="<?= base_url('service/general-tax') ?>"><img src="<?= base_url('assets/icons/tax.svg') ?>" class="menu-icon" alt=""> 세무컨설팅</a></li>
                        </ul>
                    </li>
                    <li class="has-submenu">
                        <a href="#" data-toggle="submenu"><img src="<?= base_url('assets/icons/life.svg') ?>" class="menu-icon" alt=""> 생활서비스 <span class="nav-arrow">v</span></a>
                        <ul class="submenu">
                            <li><a href="<?= base_url('service/life-buy') ?>"><img src="<?= base_url('assets/icons/shopping.svg') ?>" class="menu-icon" alt=""> 사다주기</a></li>
                            <li><a href="<?= base_url('service/life-taxi') ?>"><img src="<?= base_url('assets/icons/taxi.svg') ?>" class="menu-icon" alt=""> 택시</a></li>
                            <li><a href="<?= base_url('service/life-driver') ?>"><img src="<?= base_url('assets/icons/car.svg') ?>" class="menu-icon" alt=""> 대리운전</a></li>
                            <li><a href="<?= base_url('service/life-wreath') ?>"><img src="<?= base_url('assets/icons/flower.svg') ?>" class="menu-icon" alt=""> 화환</a></li>
                            <li><a href="<?= base_url('service/life-accommodation') ?>"><img src="<?= base_url('assets/icons/hotel.svg') ?>" class="menu-icon" alt=""> 숙박</a></li>
                            <li><a href="<?= base_url('service/life-stationery') ?>"><img src="<?= base_url('assets/icons/pencil.svg') ?>" class="menu-icon" alt=""> 문구</a></li>
                        </ul>
                    </li>
                </ul>
            </li>
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
            <li class="nav-item has-submenu">
                <a href="#" class="nav-link" data-toggle="submenu">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-text">관리자 설정</span>
                    <span class="nav-arrow">v</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?= base_url('admin/order-type') ?>">오더유형 설정</a></li>
                    <li><a href="<?= base_url('admin/notification') ?>">알림설정</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</aside>
