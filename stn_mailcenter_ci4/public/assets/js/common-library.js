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

/**
 * 전화번호 마스킹 처리 함수
 * 앞 3자리와 뒤 4자리를 제외한 가운데 숫자만 마스킹
 * 
 * @param {string} phone - 전화번호
 * @param {string} userType - 사용자 타입 ('1', '3', '5')
 * @param {string} loginUserId - 로그인한 사용자 ID
 * @param {string} orderInsungUserId - 주문의 insung_user_id
 * @returns {string} 마스킹된 전화번호 (조건에 맞을 경우)
 */
function maskPhone(phone, userType, loginUserId, orderInsungUserId) {
    if (!phone || phone === '-') {
        return phone;
    }
    
    // user_type=5이고 로그인 user_id != 주문의 insung_user_id일 때만 마스킹 (본인이 접수한 것이 아닌 경우)
    if (userType === '5' && loginUserId && orderInsungUserId && loginUserId !== orderInsungUserId) {
        // 전화번호에서 숫자만 추출
        const numbers = phone.replace(/\D/g, '');
        const numberLength = numbers.length;
        
        if (numberLength < 7) {
            // 7자리 미만이면 마스킹하지 않음
            return phone;
        }
        
        // 앞 3자리
        const first3 = numbers.slice(0, 3);
        // 뒤 4자리
        const last4 = numbers.slice(-4);
        // 가운데 부분 (마스킹 대상)
        const middle = numbers.slice(3, numberLength - 4);
        const middleMasked = '*'.repeat(middle.length);
        
        // 항상 하이픈 포함하여 마스킹 (010-XXXX-5678 형식)
        return `${first3}-${middleMasked}-${last4}`;
    }
    return phone;
}

