<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // 서브도메인 설정 가져오기
    $subdomainConfig = config('Subdomain');
    $subdomainInfo = $subdomainConfig->getCurrentConfig();
    $isUnregisteredSubdomain = $subdomainConfig->isUnregisteredSubdomain();
    $detectedSubdomain = $subdomainConfig->getDetectedSubdomain();
    $pageTitle = $title ?? $subdomainInfo['name'] ?? 'DaumData';
    ?>
    <title><?= esc($pageTitle) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // 스와이프 제스처로 사이드바 제어
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;
            let isProcessing = false; // 중복 처리 방지 플래그
            const minSwipeDistance = 50; // 최소 스와이프 거리
            
            // 터치 시작
            $(document).on('touchstart', function(e) {
                if (isProcessing) return; // 이미 처리 중이면 무시
                touchStartX = e.originalEvent.touches[0].clientX;
                touchStartY = e.originalEvent.touches[0].clientY;
            });
            
            // 터치 종료
            $(document).on('touchend', function(e) {
                if (isProcessing) return; // 이미 처리 중이면 무시
                
                touchEndX = e.originalEvent.changedTouches[0].clientX;
                touchEndY = e.originalEvent.changedTouches[0].clientY;
                
                const deltaX = touchEndX - touchStartX;
                const deltaY = touchEndY - touchStartY;
                const absDeltaX = Math.abs(deltaX);
                const absDeltaY = Math.abs(deltaY);
                
                // 수평 스와이프가 수직 스와이프보다 크고, 최소 거리 이상일 때만 처리
                if (absDeltaX > absDeltaY && absDeltaX > minSwipeDistance) {
                    isProcessing = true; // 처리 시작
                    
                    // 왼쪽 화면 끝(20px 이내)에서 오른쪽으로 스와이프하면 메뉴 열기
                    if (touchStartX <= 20 && deltaX > 0 && !$('.sidebar').hasClass('open')) {
                        $('.sidebar').addClass('open');
                    }
                    // 사이드바가 열려있고, 사이드바 영역에서 왼쪽으로 스와이프하면 메뉴 닫기
                    else if ($('.sidebar').hasClass('open') && deltaX < 0) {
                        const sidebar = $('.sidebar')[0];
                        if (sidebar) {
                            const sidebarRect = sidebar.getBoundingClientRect();
                            // 터치 시작점이 사이드바 영역 내에 있는지 확인
                            if (touchStartX >= sidebarRect.left && touchStartX <= sidebarRect.right) {
                                $('.sidebar').removeClass('open');
                            }
                        }
                    }
                    
                    // 300ms 후 플래그 해제 (중복 방지)
                    setTimeout(function() {
                        isProcessing = false;
                    }, 300);
                }
            });
            
            // 햄버거 메뉴 버튼 클릭 이벤트
            $('#mobileMenuToggle').on('click', function(e) {
                e.stopPropagation(); // 이벤트 전파 방지
                const $sidebar = $('.sidebar');
                const isOpen = $sidebar.hasClass('open');
                
                if (isOpen) {
                    $sidebar.removeClass('open');
                    $(this).removeClass('active');
                } else {
                    $sidebar.addClass('open');
                    $(this).addClass('active');
                }
            });
            
            // 사이드바가 닫힐 때 햄버거 버튼도 비활성화
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuToggle').length && $('.sidebar').hasClass('open')) {
                    $('.sidebar').removeClass('open');
                    $('#mobileMenuToggle').removeClass('active');
                }
            });
            
            // 사이드바 닫기 버튼 기능
            $('#sidebarClose').on('click', function() {
                $('.sidebar').removeClass('open');
                $('#mobileMenuToggle').removeClass('active');
            });
            
            // 레이어 팝업이 열릴 때 사이드바 숨기기 (공통 함수)
            window.hideSidebarForModal = function() {
                if (window.innerWidth <= 1023) {
                    $('.sidebar').removeClass('open');
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
                // console.log('Submenu link clicked:', href);
                
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
                
                // console.log('Top-level menu clicked:', $(this).attr('href'));
                
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
            // console.log('Current path:', currentPath);
            
            // 먼저 모든 메뉴 닫기
            $('.nav-item.has-submenu').removeClass('active').find('.nav-arrow').text('v');
            $('.submenu .has-submenu').removeClass('active').find('.nav-arrow').text('v');
            $('.submenu a').removeClass('active');
            $('.nav-item').removeClass('active');
            
            // 서비스 페이지인 경우 해당 서비스 메뉴 활성화
            if (currentPath.includes('/service/')) {
                const serviceName = currentPath.split('/service/')[1];
                // console.log('Service name:', serviceName);
                
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
                        return $(this).find('.nav-text').text() === '배송조회';
                    });
                    
                    if ($deliveryMenu.length) {
                        $deliveryMenu.addClass('active');
                    }
                }
                // 이용내역상세조회 페이지인 경우
                else if (currentPath.includes('/history/')) {
                    const $historyMenu = $('.nav-item:not(.has-submenu)').filter(function() {
                        return $(this).find('.nav-text').text() === '이용내역상세조회';
                    });
                    
                    if ($historyMenu.length) {
                        $historyMenu.addClass('active');
                    }
                }
                // 회원정보 페이지인 경우
                else if (currentPath.includes('/member/')) {
                    const $memberMenu = $('.nav-item:not(.has-submenu)').filter(function() {
                        return $(this).find('.nav-text').text() === '회원정보';
                    });
                    
                    if ($memberMenu.length) {
                        $memberMenu.addClass('active');
                    }
                }
                // 인성(insung) 관련 페이지인 경우 (고객사 관리, 고객사 회원정보)
                else if (currentPath.includes('/insung/')) {
                    const $customerMenu = $('.nav-item.has-submenu').filter(function() {
                        return $(this).find('.nav-text').text() === '고객 관리';
                    });
                    
                    if ($customerMenu.length) {
                        $customerMenu.addClass('active');
                        $customerMenu.find('.nav-arrow').text('^');
                    }
                    
                    // 현재 인성 페이지 링크 활성화
                    if (currentPath.includes('/insung/user-list')) {
                        const $userListLink = $('.submenu a[href*="insung/user-list"]');
                        if ($userListLink.length) {
                            $userListLink.addClass('active');
                        }
                    } else if (currentPath.includes('/insung/company-list')) {
                        const $companyListLink = $('.submenu a[href*="insung/company-list"]');
                        if ($companyListLink.length) {
                            $companyListLink.addClass('active');
                        }
                    } else if (currentPath.includes('/insung/cc-list')) {
                        // 콜센터 목록은 콜센터 관리 메뉴에 속함
                        const $callCenterMenu = $('.nav-item.has-submenu').filter(function() {
                            return $(this).find('.nav-text').text() === '콜센터 관리';
                        });
                        
                        if ($callCenterMenu.length) {
                            $callCenterMenu.addClass('active');
                            $callCenterMenu.find('.nav-arrow').text('^');
                        }
                        
                        const $ccListLink = $('.submenu a[href*="insung/cc-list"]');
                        if ($ccListLink.length) {
                            $ccListLink.addClass('active');
                        }
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
                    // 관리자 설정 메뉴 또는 관리자 메뉴 활성화
                    const $adminMenu = $('.nav-item.has-submenu').filter(function() {
                        return $(this).find('.nav-text').text() === '관리자 설정' || $(this).find('.nav-text').text() === '관리자';
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
    <script>
    // 공통 AJAX 응답 처리 함수 (JSON 파싱 에러 방지)
    window.safeJsonParse = async function(response) {
        const contentType = response.headers.get('content-type');
        
        // Content-Type이 JSON이 아니면 에러 처리
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            // console.error('Non-JSON response received:', {
            //     status: response.status,
            //     statusText: response.statusText,
            //     contentType: contentType,
            //     body: text.substring(0, 500) // 처음 500자만 로그
            // });
            
            // HTML 에러 페이지인 경우
            if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                throw new Error('서버에서 HTML 응답을 반환했습니다. (에러 페이지일 수 있습니다)');
            }
            
            throw new Error('JSON이 아닌 응답을 받았습니다: ' + response.statusText);
        }
        
        return response.json();
    };
    
    // 전역 에러 핸들러 (처리되지 않은 Promise rejection)
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && event.reason.message && event.reason.message.includes('Unexpected token')) {
            // console.error('JSON 파싱 에러 감지:', event.reason);
            // console.error('이 에러는 서버가 HTML을 반환했을 때 발생합니다.');
            // 사용자에게는 조용히 처리 (콘솔에만 로그)
            event.preventDefault(); // 기본 에러 표시 방지
        }
    });
    </script>
</head>
<body>
    <!-- 햄버거 메뉴 버튼 - 1023px 이하에서만 표시 (PC 모드에서 브라우저를 줄였을 때 메뉴 활성화용) -->
    <button id="mobileMenuToggle" class="mobile-menu-toggle" aria-label="메뉴 열기">
        <svg class="hamburger-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    
    <div class="container">
        <?= $this->include('layouts/sidebar') ?>
        
        <main class="main-content">
            <?php if (isset($content_header)): ?>
            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-300 py-6 px-6 mb-3 w-full">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900"><?= $content_header['title'] ?></h1>
                        <p class="text-sm text-gray-900 ml-3"><?= $content_header['description'] ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if (isset($content_header['action_button']) && !empty($content_header['action_button'])): ?>
                        <a href="<?= base_url($content_header['action_button']['url']) ?>" 
                           class="px-4 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium transition-colors whitespace-nowrap">
                            <?= esc($content_header['action_button']['label']) ?>
                        </a>
                        <?php endif; ?>
                        <?php if (isset($content_header['back_button']) && !empty($content_header['back_button'])): ?>
                        <a href="<?= base_url($content_header['back_button']['url']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded whitespace-nowrap"
                           style="height: 24px; font-size: 12px; padding: 4px 12px; font-weight: 600; border: 1px solid #e2e8f0;">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="7" fill="#7DD3FC" stroke="#7DD3FC" stroke-width="1.5"/>
                                <path d="M20 20l-4-4" stroke="#92400E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?= esc($content_header['back_button']['label']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
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
    
    <?php if ($isUnregisteredSubdomain): ?>
    <!-- 등록되지 않은 서브도메인 경고 레이어 팝업 -->
    <div id="unregisteredSubdomainModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden" onclick="closeUnregisteredSubdomainModal()">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">서비스 안내</h3>
                    <button onclick="closeUnregisteredSubdomainModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-lg font-semibold text-gray-900">등록되지 않은 서비스입니다</p>
                    </div>
                    <p class="text-gray-700 mb-2">
                        접속하신 서브도메인 <strong class="text-gray-900"><?= esc($detectedSubdomain ?? '') ?>.daumdata.com</strong>은(는) 등록되지 않은 서비스입니다.
                    </p>
                    <p class="text-gray-700">
                        서비스 이용을 원하시면 관리자에게 문의해 주세요.
                    </p>
                </div>
                <div class="flex justify-end">
                    <button onclick="closeUnregisteredSubdomainModal()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        확인
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // 등록되지 않은 서브도메인 경고 레이어 팝업 열기
        function showUnregisteredSubdomainModal() {
            // 레이어 팝업이 열릴 때 사이드바 처리
            if (typeof window.hideSidebarForModal === 'function') {
                window.hideSidebarForModal();
            }
            if (typeof window.lowerSidebarZIndex === 'function') {
                window.lowerSidebarZIndex();
            }
            
            document.getElementById('unregisteredSubdomainModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        // 등록되지 않은 서브도메인 경고 레이어 팝업 닫기
        function closeUnregisteredSubdomainModal() {
            document.getElementById('unregisteredSubdomainModal').classList.add('hidden');
            document.body.style.overflow = '';
            
            if (typeof window.restoreSidebarZIndex === 'function') {
                window.restoreSidebarZIndex();
            }
        }
        
        // 페이지 로드 시 자동으로 팝업 표시
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($isUnregisteredSubdomain): ?>
            showUnregisteredSubdomainModal();
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>