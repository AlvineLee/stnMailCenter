/**
 * 주문 폼 유효성 검사 공통 스크립트
 * 엔터키 제출 방지 및 필수 필드 검증
 */
(function() {
    'use strict';
    
    // DOM 로드 완료 후 실행
    document.addEventListener('DOMContentLoaded', function() {
        const orderForm = document.getElementById('orderForm');
        
        if (!orderForm) {
            // orderForm이 없는 페이지에서는 실행하지 않음
            return;
        }
        
        // 1. 엔터키로 폼 제출 방지
        orderForm.addEventListener('keydown', function(e) {
            // 엔터키가 눌렸을 때
            if (e.key === 'Enter' || e.keyCode === 13) {
                const target = e.target;
                
                // textarea에서는 엔터 허용 (줄바꿈)
                if (target.tagName === 'TEXTAREA') {
                    return true;
                }
                
                // input 필드에서 엔터키는 제출 방지
                if (target.tagName === 'INPUT' && target.type !== 'submit' && target.type !== 'button') {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        });
        
        // 2. 폼 제출 시 유효성 검사
        orderForm.addEventListener('submit', function(e) {
            // 필수 필드 정의 (공통 필드)
            const requiredFields = [
                { id: 'company_name', name: '회사명' },
                { id: 'contact', name: '연락처' },
                { id: 'departure_contact', name: '출발지 연락처' },
                { id: 'departure_manager', name: '출발지 담당' },
                { id: 'departure_address', name: '출발지 주소' },
                { id: 'departure_detail', name: '출발지 상세주소' },
                { id: 'destination_contact', name: '도착지 연락처' },
                { id: 'destination_manager', name: '도착지 담당' },
                { id: 'destination_address', name: '도착지 주소' },
                { id: 'destination_detail', name: '도착지 상세주소' }
            ];
            
            // 라디오 버튼 필수 필드 체크 (공통)
            const requiredRadioFields = [];
            
            // 배송수단 필드가 있는 경우에만 필수로 추가
            const deliveryMethod = document.querySelector('input[name="delivery_method"]');
            if (deliveryMethod) {
                requiredRadioFields.push({ name: 'delivery_method', fieldName: '배송수단' });
            }
            
            // 서비스별 필수 필드 추가 (동적 확장 가능)
            const serviceType = document.querySelector('input[name="service_type"]');
            if (serviceType) {
                const serviceTypeValue = serviceType.value;
                
                // 물품종류 필드가 있고 required 속성이 있는 경우 필수로 추가
                const itemTypeField = document.getElementById('item_type');
                if (itemTypeField && itemTypeField.hasAttribute('required')) {
                    requiredFields.push({ id: 'item_type', name: '물품종류' });
                }
                
                // 퀵접수 서비스인 경우
                if (serviceTypeValue && serviceTypeValue.startsWith('quick-')) {
                    // 배송형태 필수
                    const urgencyLevel = document.querySelector('input[name="urgency_level"]');
                    if (urgencyLevel) {
                        requiredRadioFields.push({ name: 'urgency_level', fieldName: '배송형태' });
                    }
                    
                    // 배송방법 필수
                    const deliveryRoute = document.querySelector('input[name="delivery_route"]');
                    if (deliveryRoute) {
                        requiredRadioFields.push({ name: 'delivery_route', fieldName: '배송방법' });
                        
                        // 경유지 주소 체크 (배송방법이 'via'인 경우)
                        const checkedRoute = document.querySelector('input[name="delivery_route"]:checked');
                        if (checkedRoute && checkedRoute.value === 'via') {
                            const waypointAddress = document.getElementById('waypoint_address');
                            if (waypointAddress) {
                                requiredFields.push({ id: 'waypoint_address', name: '경유지 주소' });
                            }
                        }
                    }
                }
                
                // 택배 서비스인 경우 (parcel-*)
                if (serviceTypeValue && serviceTypeValue.startsWith('parcel-')) {
                    // 택배 서비스는 물품종류가 대부분 필수
                    if (itemTypeField && !itemTypeField.hasAttribute('required')) {
                        // required 속성이 없어도 필드가 있으면 필수로 간주
                        const existingItemType = requiredFields.find(f => f.id === 'item_type');
                        if (!existingItemType) {
                            requiredFields.push({ id: 'item_type', name: '물품종류' });
                        }
                    }
                }
                
                // 해외특송 서비스인 경우
                if (serviceTypeValue === 'international') {
                    // 해외특송은 국가/지역 필드가 있을 수 있음
                    const departureCountry = document.getElementById('departure_country');
                    const destinationCountry = document.getElementById('destination_country');
                    if (departureCountry && departureCountry.hasAttribute('required')) {
                        requiredFields.push({ id: 'departure_country', name: '출발지 국가/지역' });
                    }
                    if (destinationCountry && destinationCountry.hasAttribute('required')) {
                        requiredFields.push({ id: 'destination_country', name: '도착지 국가/지역' });
                    }
                }
            }
            
            // 필수 input 필드 검증
            for (let field of requiredFields) {
                const element = document.getElementById(field.id);
                if (!element) continue;
                
                // disabled 상태인 필드는 검증 제외
                if (element.disabled) continue;
                
                const value = element.value ? element.value.trim() : '';
                
                if (value === '') {
                    // 필수 필드가 비어있으면 제출 방지 및 포커스
                    e.preventDefault();
                    element.focus();
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // 시각적 피드백 (빨간 테두리)
                    element.classList.add('border-red-500', 'ring-2', 'ring-red-300');
                    setTimeout(function() {
                        element.classList.remove('border-red-500', 'ring-2', 'ring-red-300');
                    }, 3000);
                    
                    alert(`"${field.name}" 필드는 필수 입력 항목입니다.`);
                    return false;
                }
            }
            
            // 라디오 버튼 필수 필드 검증
            for (let field of requiredRadioFields) {
                const checked = document.querySelector(`input[name="${field.name}"]:checked`);
                if (!checked) {
                    // 라디오 버튼이 선택되지 않았으면 제출 방지 및 첫 번째 라디오 버튼에 포커스
                    e.preventDefault();
                    const firstRadio = document.querySelector(`input[name="${field.name}"]`);
                    if (firstRadio) {
                        firstRadio.focus();
                        firstRadio.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    alert(`"${field.fieldName}" 필드는 필수 선택 항목입니다.`);
                    return false;
                }
            }
            
            // 모든 검증 통과 시 폼 제출 허용 (기본 동작 진행)
        });
    });
})();

