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
});