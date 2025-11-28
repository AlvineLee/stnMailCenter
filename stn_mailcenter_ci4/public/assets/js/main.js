// 공통 AJAX 응답 처리 함수 (JSON 파싱 에러 방지)
// header.php에 이미 정의되어 있지만, 여기서도 사용 가능하도록 확인
if (typeof window.safeJsonParse === 'undefined') {
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
}

// 메인 JavaScript 파일 (jQuery)
$(document).ready(function() {
    // 모바일 메뉴 토글 - 스와이프 제스처로 대체됨 (header.php에서 처리)
    
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