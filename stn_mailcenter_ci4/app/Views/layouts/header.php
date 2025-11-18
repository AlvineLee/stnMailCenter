<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'STN Network' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // 햄버거 메뉴 토글 기능
            $('#mobileMenuToggle').on('click', function() {
                $('.sidebar').toggleClass('open');
                $(this).toggleClass('active');
            });
            
            // 사이드바 닫기 버튼 기능
            $('#sidebarClose').on('click', function() {
                $('.sidebar').removeClass('open');
                $('#mobileMenuToggle').removeClass('active');
            });
            
            // 사이드바 외부 클릭 시 메뉴 닫기
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.sidebar, #mobileMenuToggle').length) {
                    $('.sidebar').removeClass('open');
                    $('#mobileMenuToggle').removeClass('active');
                }
            });
            
            // 레이어 팝업이 열릴 때 사이드바 숨기기 (공통 함수)
            window.hideSidebarForModal = function() {
                if (window.innerWidth <= 1023) {
                    $('.sidebar').removeClass('open');
                    $('#mobileMenuToggle').removeClass('active');
                }
            };
            
            // 레이어 팝업이 열릴 때 사이드바 z-index 낮추기 (오버레이 효과를 위해)
            window.lowerSidebarZIndex = function() {
                if (typeof $ !== 'undefined') {
                    $('.sidebar').css('z-index', '1');
                } else {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) sidebar.style.zIndex = '1';
                }
            };
            
            // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
            window.restoreSidebarZIndex = function() {
                if (typeof $ !== 'undefined') {
                    $('.sidebar').css('z-index', '1000');
                } else {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) sidebar.style.zIndex = '1000';
                }
            };
            
            // 서브메뉴 토글 기능
            $('[data-toggle="submenu"]').on('click', function(e) {
                e.preventDefault();
                const $navItem = $(this).closest('.nav-item, .has-submenu');
                const isActive = $navItem.hasClass('active');
                const $arrow = $(this).find('.nav-arrow');
                
                // 같은 레벨의 다른 활성 메뉴들 닫기
                const $parent = $navItem.parent();
                if ($parent.length) {
                    $parent.find('.active').not($navItem).each(function() {
                        $(this).removeClass('active');
                        $(this).find('.nav-arrow').text('v');
                    });
                }
                
                // 현재 메뉴 토글
                if (isActive) {
                    $navItem.removeClass('active');
                    $arrow.text('v');
                } else {
                    $navItem.addClass('active');
                    $arrow.text('^');
                }
            });
            
            // 서브메뉴 링크 클릭 시 해당 메뉴 활성화
            $('.submenu a[href]').on('click', function() {
                const href = $(this).attr('href');
                console.log('Submenu link clicked:', href);
                
                // 다른 모든 서브메뉴 닫기 (현재 클릭한 링크의 부모 제외)
                const $currentSubmenu = $(this).closest('.submenu');
                $('.submenu').not($currentSubmenu).find('.has-submenu').removeClass('active').find('.nav-arrow').text('v');
                
                // 현재 링크의 부모 메뉴 항목 찾기
                const $parentNavItem = $(this).closest('.nav-item.has-submenu');
                if ($parentNavItem.length) {
                    // 같은 레벨의 다른 활성 메뉴들만 닫기
                    const $siblings = $parentNavItem.siblings('.nav-item.has-submenu');
                    $siblings.removeClass('active').find('.nav-arrow').text('v');
                    // 현재 부모 메뉴는 열어둠
                    $parentNavItem.addClass('active');
                    $parentNavItem.find('.nav-arrow').text('^');
                }
                
                // 현재 클릭된 링크 활성화
                $('.submenu a').removeClass('active');
                setTimeout(() => {
                    $(this).addClass('active');
                }, 10);
            });
            
            // 서브메뉴가 없는 대메뉴 클릭 시 다른 메뉴들 닫기
            $('.nav-link[href]').on('click', function() {
                const $navItem = $(this).closest('.nav-item');
                
                // 서브메뉴가 있는 메뉴는 제외
                if ($navItem.hasClass('has-submenu')) {
                    return;
                }
                
                console.log('Top-level menu clicked:', $(this).attr('href'));
                
                // 모든 서브메뉴 닫기
                $('.submenu .has-submenu').removeClass('active').find('.nav-arrow').text('v');
                $('.nav-item.has-submenu').removeClass('active').find('.nav-arrow').text('v');
                
                // 모든 서브메뉴 링크 비활성화
                $('.submenu a').removeClass('active');
                
                // 현재 클릭된 메뉴만 활성화
                $('.nav-item').removeClass('active');
                $navItem.addClass('active');
            });
            
            // 현재 페이지에 맞는 메뉴 활성화
            const currentPath = window.location.pathname;
            console.log('Current path:', currentPath);
            
            // 서비스 페이지인 경우 해당 서비스 메뉴 활성화
            if (currentPath.includes('/service/')) {
                const serviceName = currentPath.split('/service/')[1];
                console.log('Service name:', serviceName);
                
                // 다른 모든 서브메뉴 닫기 (현재 서비스에 해당하는 것만 열기 위해)
                $('.submenu .has-submenu').removeClass('active').find('.nav-arrow').text('v');
                
                // 주문접수 메뉴 열기 (서비스 페이지이므로)
                const $orderMenu = $('.nav-item.has-submenu').filter(function() {
                    return $(this).find('.nav-text').text() === '주문접수';
                });
                if ($orderMenu.length) {
                    $orderMenu.addClass('active');
                    $orderMenu.find('.nav-arrow').text('^');
                }
                
                // 서비스 카테고리별 메뉴 열기 (우선순위 순서로)
                if (serviceName.includes('parcel-')) {
                    // 택배서비스 메뉴 열기
                    const $parcelMenu = $('.submenu .has-submenu').filter(function() {
                        return $(this).find('a').text().includes('택배서비스');
                    });
                    
                    if ($parcelMenu.length) {
                        $parcelMenu.addClass('active');
                        $parcelMenu.find('.nav-arrow').text('^');
                    }
                } else if (serviceName.includes('linked-')) {
                    // 연계배송서비스 메뉴 열기
                    const $linkedMenu = $('.submenu .has-submenu').filter(function() {
                        return $(this).find('a').text().includes('연계배송서비스');
                    });
                    
                    if ($linkedMenu.length) {
                        $linkedMenu.addClass('active');
                        $linkedMenu.find('.nav-arrow').text('^');
                    }
                } else if (serviceName.includes('life-')) {
                    // 생활서비스 메뉴 열기
                    const $lifeMenu = $('.submenu .has-submenu').filter(function() {
                        return $(this).find('a').text().includes('생활서비스');
                    });
                    
                    if ($lifeMenu.length) {
                        $lifeMenu.addClass('active');
                        $lifeMenu.find('.nav-arrow').text('^');
                    }
                } else if (serviceName.includes('general-')) {
                    // 일반서비스 메뉴 열기
                    const $generalMenu = $('.submenu .has-submenu').filter(function() {
                        return $(this).find('a').text().includes('일반서비스');
                    });
                    
                    if ($generalMenu.length) {
                        $generalMenu.addClass('active');
                        $generalMenu.find('.nav-arrow').text('^');
                    }
                }
                // postal은 독립적인 서비스이므로 별도 처리하지 않음 (주문접수 메뉴만 열린 상태)
                else if (serviceName.includes('quick-')) {
                    // 퀵서비스 메뉴 열기 (서비스가 퀵서비스인 경우)
                    const $quickMenu = $('.submenu .has-submenu').filter(function() {
                        return $(this).find('a').text().includes('퀵서비스');
                    });
                    
                    if ($quickMenu.length) {
                        $quickMenu.addClass('active');
                        $quickMenu.find('.nav-arrow').text('^');
                    }
                }
                // international은 독립적인 서비스이므로 별도 처리하지 않음 (주문접수 메뉴만 열린 상태)
                // mailroom은 독립적인 서비스이므로 별도 처리하지 않음 (주문접수 메뉴만 열린 상태)
                
                // 현재 서비스 링크 활성화
                const $currentLink = $(`.submenu a[href*="${serviceName}"]`);
                if ($currentLink.length) {
                    $currentLink.addClass('active');
                }
            } else {
                // 배송조회 페이지인 경우
                if (currentPath.includes('/delivery/')) {
                    const $deliveryMenu = $('.nav-item:not(.has-submenu)').filter(function() {
                        return $(this).find('.nav-text').text() === '배송조회(리스트)';
                    });
                    
                    if ($deliveryMenu.length) {
                        $deliveryMenu.addClass('active');
                    }
                }
                // 회원정보 페이지인 경우
                else if (currentPath.includes('/member/')) {
                    const $memberMenu = $('.nav-item:not(.has-submenu)').filter(function() {
                        return $(this).find('.nav-text').text() === '회원정보(리스트)';
                    });
                    
                    if ($memberMenu.length) {
                        $memberMenu.addClass('active');
                    }
                }
                // 고객관리 관련 페이지인 경우 (고객관리, 부서관리, 청구관리, 항목관리, 입점관리)
                else if (currentPath.includes('/customer/') || currentPath.includes('/department/') || currentPath.includes('/billing/') || currentPath.includes('/store-registration')) {
                    const $customerMenu = $('.nav-item.has-submenu').filter(function() {
                        return $(this).find('.nav-text').text() === '고객 관리';
                    });
                    
                    if ($customerMenu.length) {
                        $customerMenu.addClass('active');
                        $customerMenu.find('.nav-arrow').text('^');
                    }
                    
                    // 고객관리 페이지인 경우
                    if (currentPath.includes('/customer/')) {
                        const currentCustomerPage = currentPath.split('/customer/')[1];
                        const $currentCustomerLink = $(`.submenu a[href*="${currentCustomerPage}"]`);
                        if ($currentCustomerLink.length) {
                            $currentCustomerLink.addClass('active');
                        }
                    }
                    // 부서관리 페이지인 경우
                    else if (currentPath.includes('/department/')) {
                        // 부서관리 서브메뉴 펼치기
                        const $departmentSubmenu = $customerMenu.find('.submenu .has-submenu').filter(function() {
                            return $(this).find('a').text().includes('부서 관리');
                        });
                        if ($departmentSubmenu.length) {
                            $departmentSubmenu.find('a').addClass('active');
                            $departmentSubmenu.find('.nav-arrow').text('^');
                        }
                        
                        // 현재 부서관리 서브메뉴 링크 활성화
                        const currentDepartmentPage = currentPath.split('/department/')[1];
                        const $currentDepartmentLink = $(`.submenu a[href*="${currentDepartmentPage}"]`);
                        if ($currentDepartmentLink.length) {
                            $currentDepartmentLink.addClass('active');
                        }
                    }
                    // 청구관리 페이지인 경우
                    else if (currentPath.includes('/billing/')) {
                        // 청구관리 서브메뉴 펼치기
                        const $billingSubmenu = $customerMenu.find('.submenu .has-submenu').filter(function() {
                            return $(this).find('a').text().includes('청구 관리');
                        });
                        if ($billingSubmenu.length) {
                            $billingSubmenu.find('a').addClass('active');
                            $billingSubmenu.find('.nav-arrow').text('^');
                        }
                        
                        // 현재 청구관리 서브메뉴 링크 활성화
                        const currentBillingPage = currentPath.split('/billing/')[1];
                        const $currentBillingLink = $(`.submenu a[href*="${currentBillingPage}"]`);
                        if ($currentBillingLink.length) {
                            $currentBillingLink.addClass('active');
                        }
                    }
                    // 입점관리 페이지인 경우
                    else if (currentPath.includes('/store-registration')) {
                        const $storeRegistrationLink = $('.submenu a[href*="store-registration"]');
                        if ($storeRegistrationLink.length) {
                            $storeRegistrationLink.addClass('active');
                        }
                    }
                }
                // 콜센터 관리 페이지인 경우
                else if (currentPath.includes('/call-center/') || currentPath.includes('/hub-center') || currentPath.includes('/group-company') || currentPath.includes('/logistics-representative')) {
                    const $callCenterMenu = $('.nav-item.has-submenu').filter(function() {
                        return $(this).find('.nav-text').text() === '콜센터 관리';
                    });
                    
                    if ($callCenterMenu.length) {
                        $callCenterMenu.addClass('active');
                        $callCenterMenu.find('.nav-arrow').text('^');
                    }
                    
                    // 현재 콜센터 관리 서브메뉴 링크 활성화
                    let $currentLink = null;
                    if (currentPath.includes('/call-center/')) {
                        const currentCallCenterPage = currentPath.split('/call-center/')[1];
                        $currentLink = $(`.submenu a[href*="call-center/${currentCallCenterPage}"]`);
                    } else if (currentPath.includes('/hub-center')) {
                        $currentLink = $(`.submenu a[href*="hub-center"]`);
                    } else if (currentPath.includes('/group-company')) {
                        $currentLink = $(`.submenu a[href*="group-company"]`);
                    } else if (currentPath.includes('/logistics-representative')) {
                        $currentLink = $(`.submenu a[href*="logistics-representative"]`);
                    }
                    
                    if ($currentLink && $currentLink.length) {
                        $currentLink.addClass('active');
                    }
                }
                // 관리자설정 페이지인 경우
                else if (currentPath.includes('/admin/')) {
                    const $adminMenu = $('.nav-item.has-submenu').filter(function() {
                        return $(this).find('.nav-text').text() === '관리자 설정';
                    });
                    
                    if ($adminMenu.length) {
                        $adminMenu.addClass('active');
                        $adminMenu.find('.nav-arrow').text('^');
                    }
                    
                    // 현재 관리자설정 서브메뉴 링크 활성화
                    const currentAdminPage = currentPath.split('/admin/')[1];
                    const $currentAdminLink = $(`.submenu a[href*="${currentAdminPage}"]`);
                    if ($currentAdminLink.length) {
                        $currentAdminLink.addClass('active');
                    }
                }
                // 인성 시스템 관련 페이지인 경우 (insung)
                else if (currentPath.includes('/insung/')) {
                    // 고객 관리 메뉴 활성화
                    const $customerMenu = $('.nav-item.has-submenu').filter(function() {
                        return $(this).find('.nav-text').text() === '고객 관리';
                    });
                    
                    if ($customerMenu.length) {
                        $customerMenu.addClass('active');
                        $customerMenu.find('.nav-arrow').text('^');
                    }
                    
                    // 콜센터 관리 메뉴 활성화 (user_type = 1인 경우)
                    const $callCenterMenu = $('.nav-item.has-submenu').filter(function() {
                        return $(this).find('.nav-text').text() === '콜센터 관리';
                    });
                    
                    if ($callCenterMenu.length) {
                        $callCenterMenu.addClass('active');
                        $callCenterMenu.find('.nav-arrow').text('^');
                    }
                    
                    // 현재 인성 시스템 서브메뉴 링크 활성화
                    const currentInsungPage = currentPath.split('/insung/')[1];
                    let $currentLink = null;
                    
                    if (currentInsungPage === 'cc-list') {
                        $currentLink = $(`.submenu a[href*="insung/cc-list"]`);
                    } else if (currentInsungPage === 'company-list') {
                        $currentLink = $(`.submenu a[href*="insung/company-list"]`);
                    } else if (currentInsungPage === 'user-list') {
                        $currentLink = $(`.submenu a[href*="insung/user-list"]`);
                    }
                    
                    if ($currentLink && $currentLink.length) {
                        $currentLink.addClass('active');
                    }
                }
                // 기본적으로는 아무 메뉴도 펼치지 않음 (홈페이지 등)
                else {
                    // 아무것도 하지 않음
                }
            }
        });
    </script>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/order.css') ?>">
</head>
<body>
    <!-- 햄버거 메뉴 버튼 -->
    <button id="mobileMenuToggle" class="fixed top-4 left-4 z-50 lg:hidden bg-white p-2 rounded-md shadow-lg border border-gray-200">
        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
    
    <div class="container">
        <?= $this->include('layouts/sidebar') ?>
        
        <main class="main-content">
            <?php if (isset($content_header)): ?>
            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-300 py-6 px-6 mb-3 w-full">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800"><?= $content_header['title'] ?></h1>
                    <p class="text-sm text-gray-600 ml-3"><?= $content_header['description'] ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('success')): ?>
            <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 transition-opacity duration-1000">
                <?= session()->getFlashdata('success') ?>
            </div>
            <script>
            // 5초 후 서서히 숨기기
            document.addEventListener('DOMContentLoaded', function() {
                const successMessage = document.getElementById('successMessage');
                if (successMessage) {
                    setTimeout(function() {
                        successMessage.style.opacity = '0';
                        successMessage.style.transition = 'opacity 1s ease-out';
                        setTimeout(function() {
                            successMessage.style.display = 'none';
                        }, 1000);
                    }, 5000);
                }
            });
            </script>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= session()->getFlashdata('error') ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?= $this->renderSection('content') ?>
        </main>
    </div>