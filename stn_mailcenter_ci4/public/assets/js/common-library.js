/**
 * 공통 라이브러리 함수
 * 
 * 맵 뷰 팝업 열기 함수
 * 
 * @param {string} insungOrderNumber - 인성 주문번호
 * @param {boolean} isRiding - 운행 중 여부 (기사 위치 표시용)
 */
function openMapView(insungOrderNumber, isRiding) {
    if (!insungOrderNumber) {
        alert('주문번호가 없습니다.');
        return;
    }
    
    // 새 창에서 맵 뷰 페이지 열기
    const width = 1200;
    const height = 800;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;
    
    window.open(
        `/delivery/map-view?idx=${encodeURIComponent(insungOrderNumber)}`,
        'mapView',
        `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
    );
}

