// 메인 JavaScript 파일 (jQuery)
$(document).ready(function() {
    // 모바일 메뉴 토글
    $('#mobileMenuToggle').on('click', function() {
        $('.sidebar').toggleClass('open');
    });
    
    // 사이드바 메뉴 활성화
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    
    $('.nav-link').each(function() {
        const href = $(this).attr('href');
        if (href === currentPage || (currentPage === '' && href === 'index.php')) {
            $(this).closest('.nav-item').addClass('active');
        }
    });
    
    // 로그아웃 확인
    $('.logout-link').on('click', function(e) {
        if (!confirm('정말 로그아웃 하시겠습니까?')) {
            e.preventDefault();
        }
    });
    
    // 서브메뉴 토글 기능
    $('[data-toggle="submenu"]').on('click', function(e) {
        e.preventDefault();
        const $navItem = $(this).closest('.nav-item, .has-submenu');
        const isActive = $navItem.hasClass('active');
        const $arrow = $(this).find('.nav-arrow');
        
        // 같은 레벨의 다른 활성 메뉴들 닫기 (부모가 같은 경우만)
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
    
    // 주문접수 메뉴는 기본적으로 열려있도록 설정
    const $orderMenu = $('.nav-item.has-submenu').filter(function() {
        return $(this).find('.nav-text').text() === '주문접수';
    });
    
    if ($orderMenu.length) {
        $orderMenu.addClass('active');
        // 주문접수 메뉴의 화살표 방향도 업데이트
        $orderMenu.find('.nav-arrow').text('v');
    }
});