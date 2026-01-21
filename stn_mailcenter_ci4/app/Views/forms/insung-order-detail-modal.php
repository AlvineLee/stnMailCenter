<?php
// 인성 주문 상세 레이어팝업 컴포넌트
// 사용법: $this->include('forms/insung-order-detail-modal')
// JavaScript API:
//   viewInsungOrderDetail(serialNumber, apiEndpoint) - 인성 주문 상세 팝업 표시
//   closeInsungOrderDetail() - 팝업 닫기
// apiEndpoint: '/history/getOrderDetail' 또는 '/delivery/getOrderDetail' (기본: '/history/getOrderDetail')
?>

<!-- 인성 API 주문 상세 팝업 모달 -->
<div id="insungOrderDetailModal" class="fixed inset-0 hidden flex items-center justify-center p-4 order-detail-modal" style="z-index: 9999; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col order-detail-modal-content" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center flex-shrink-0 rounded-t-lg">
            <h3 class="text-lg font-bold text-gray-800">인성 주문 상세 정보</h3>
            <button type="button" onclick="closeInsungOrderDetail()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-2 overflow-y-auto flex-1">
            <div id="insungOrderDetailContent" class="modal-content">
                <!-- 내용은 populateInsungOrderDetail()에서 동적으로 생성됩니다 -->
            </div>
        </div>
    </div>
</div>

<style>
/* 인성 주문 상세 팝업 모바일 반응형 */
@media (max-width: 767px) {
    .insung-detail-grid-row {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
// 인성 주문 상세 팝업용 API 엔드포인트 (기본값)
let insungApiEndpoint = '/history/getOrderDetail';

function viewInsungOrderDetail(serialNumber, apiEndpoint) {
    // API 엔드포인트 설정
    if (apiEndpoint) {
        insungApiEndpoint = apiEndpoint;
    }

    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }

    // 로딩 상태 표시
    showInsungOrderDetailLoading();

    // AJAX로 인성 API 주문 상세 정보 가져오기
    fetch(`${insungApiEndpoint}?serial_number=${encodeURIComponent(serialNumber)}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Insung API Response:', data);
        if (data.success) {
            try {
                populateInsungOrderDetail(data.data);
                // 모달 표시
                document.getElementById('insungOrderDetailModal').classList.remove('hidden');
                document.getElementById('insungOrderDetailModal').classList.add('flex');
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('populateInsungOrderDetail Error:', error);
                showInsungOrderDetailError('주문 정보 표시 중 오류가 발생했습니다: ' + error.message);
            }
        } else {
            showInsungOrderDetailError(data.message || '주문 정보를 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showInsungOrderDetailError('주문 정보 조회 중 오류가 발생했습니다: ' + error.message);
    });
}

function populateInsungOrderDetail(orderData) {
    // 헬퍼 함수: 값이 있으면 표시, 없으면 '-'
    const getValue = (value) => {
        if (value === null || value === undefined || value === '') return '-';
        if (typeof value === 'object') return JSON.stringify(value);
        return value;
    };

    // 상태 값 변환
    const stateLabels = {
        '10': '접수',
        '11': '배차',
        '12': '운행',
        '20': '대기',
        '30': '완료',
        '40': '취소',
        '50': '문의',
        '90': '예약'
    };

    const orderRegistTypeLabels = {
        'A': 'API접수',
        'I': '인터넷접수',
        'T': '전화접수'
    };

    // 섹션별 필드 그룹화
    const sections = {
        '접수자 정보': [
            { key: 'customer_name', label: '접수자 이름' },
            { key: 'customer_tel_number', label: '접수자 전화번호' },
            { key: 'customer_department', label: '접수자 부서명' },
            { key: 'customer_duty', label: '접수자 담당명' }
        ],
        '기사 정보': [
            { key: 'rider_code_no', label: '오더 처리 기사 고유번호' },
            { key: 'rider_name', label: '오더 처리 기사 이름' },
            { key: 'rider_tel_number', label: '오더 처리 기사 연락처' }
        ],
        '오더 정보': [
            { key: 'serial_number', label: '오더 고유번호(주문번호)' },
            { key: 'order_time', label: '접수시간' },
            { key: 'allocation_time', label: '배차시간' },
            { key: 'pickup_time', label: '픽업시간' },
            { key: 'resolve_time', label: '예약시간' },
            { key: 'complete_time', label: '완료시간' },
            { key: 'reason', label: '배송사유' },
            { key: 'order_regist_type', label: '접수유형' }
        ],
        '배송지 정보': [
            { key: 'departure_dong_name', label: '출발지 동명' },
            { key: 'departure_address', label: '출발지 상세주소' },
            { key: 'departure_tel_number', label: '출발지 연락처' },
            { key: 'departure_company_name', label: '출발지 상호·이름' },
            { key: 'destination_dong_name', label: '도착지 동명' },
            { key: 'destination_address', label: '도착지 상세주소' },
            { key: 'destination_tel_number', label: '도착지 연락처' },
            { key: 'destination_company_name', label: '도착지 상호·이름' },
            { key: 'summary', label: '전달내용' }
        ],
        '배송정보': [
            { key: 'car_type', label: '배송수단' },
            { key: 'cargo_type', label: '차종톤수' },
            { key: 'cargo_name', label: '차종구분명' },
            { key: 'payment', label: '지불수단' },
            { key: 'state', label: '배송상태' },
            { key: 'save_state', label: 'DB저장 배송상태' }
        ],
        '출·도착지 정보': [
            { key: 'doc', label: '배송방법' },
            { key: 'item_type', label: '물품종류' },
            { key: 'sfast', label: '배송선택' },
            { key: 'start_c_code', label: '출발지 고객코드' },
            { key: 'dest_c_code', label: '도착지 고객코드' },
            { key: 'start_department', label: '출발지 부서' },
            { key: 'start_duty', label: '출발지 담당' },
            { key: 'dest_department', label: '도착지 부서' },
            { key: 'dest_duty', label: '도착지 담당' },
            { key: 'happy_call', label: '해피콜 회신번호' },
            { key: 'distince', label: '출발지 도착지 거리', suffix: 'Km' }
        ]
    };

    // 섹션별 패널 생성 함수
    const createSectionPanel = (sectionTitle, fieldDefs) => {
        const sectionFields = [];

        for (const fieldDef of fieldDefs) {
            const key = fieldDef.key;
            const label = fieldDef.label;

            // orderData에서 키를 찾기
            let value = null;

            if (orderData.hasOwnProperty(key)) {
                value = orderData[key];
            } else {
                // 중첩된 키 찾기 (item_0_customer_name 같은 형태)
                for (const dataKey in orderData) {
                    if (dataKey.includes(key) || dataKey.endsWith('_' + key)) {
                        value = orderData[dataKey];
                        break;
                    }
                }
            }

            if (value !== null && value !== undefined && value !== '') {
                // 특정 필드 값 변환
                if (key === 'state' || key === 'save_state') {
                    value = stateLabels[value] || value;
                } else if (key === 'order_regist_type') {
                    value = orderRegistTypeLabels[value] || value;
                }

                // suffix가 있으면 추가
                if (fieldDef.suffix) {
                    value = value + ' ' + fieldDef.suffix;
                }

                sectionFields.push({ key, label, value });
            }
        }

        // 필드가 있는 섹션만 패널 반환
        if (sectionFields.length > 0) {
            return `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); height: 100%;">
                    <div style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">
                        ${sectionTitle}
                    </div>
                    <div>
                        ${sectionFields.map(field => `
                            <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6; font-size: 12px; line-height: 1.6;">
                                <span style="font-weight: 600; color: #374151; display: inline-block; min-width: 140px;">${field.label}</span>
                                <span style="color: #6b7280;">: ${getValue(field.value)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        return '';
    };

    // 레이아웃 구성
    const customerPanel = createSectionPanel('접수자 정보', sections['접수자 정보']);
    const orderPanel = createSectionPanel('오더 정보', sections['오더 정보']);
    const locationPanel = createSectionPanel('출·도착지 정보', sections['출·도착지 정보']);
    const deliveryPanel = createSectionPanel('배송정보', sections['배송정보']);
    const addressPanel = createSectionPanel('배송지 정보', sections['배송지 정보']);

    let content = '<div style="padding: 8px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-radius: 8px; width: 100%; box-sizing: border-box;">';

    // 첫 번째 행: 접수자 정보 | 오더 정보
    if (customerPanel || orderPanel) {
        content += '<div class="insung-detail-grid-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; align-items: stretch; width: 100%;">';
        content += (customerPanel || '<div></div>');
        content += (orderPanel || '<div></div>');
        content += '</div>';
    }

    // 두 번째 행: 출·도착지 정보 | 배송정보
    if (locationPanel || deliveryPanel) {
        content += '<div class="insung-detail-grid-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; align-items: stretch; width: 100%;">';
        content += (locationPanel || '<div></div>');
        content += (deliveryPanel || '<div></div>');
        content += '</div>';
    }

    // 세 번째 행: 배송지 정보 (전체 너비)
    if (addressPanel) {
        content += '<div style="margin-bottom: 0; width: 100%;">';
        content += addressPanel;
        content += '</div>';
    }

    content += '</div>';

    document.getElementById('insungOrderDetailContent').innerHTML = content;
}

function showInsungOrderDetailLoading() {
    const content = document.getElementById('insungOrderDetailContent');
    content.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">주문 정보를 불러오는 중...</div>';

    document.getElementById('insungOrderDetailModal').classList.remove('hidden');
    document.getElementById('insungOrderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function showInsungOrderDetailError(message) {
    const content = document.getElementById('insungOrderDetailContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="color: #ef4444; margin-bottom: 16px;">⚠️</div>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">오류 발생</div>
            <div style="color: #6b7280;">${message}</div>
        </div>
    `;

    document.getElementById('insungOrderDetailModal').classList.remove('hidden');
    document.getElementById('insungOrderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeInsungOrderDetail() {
    document.getElementById('insungOrderDetailModal').classList.add('hidden');
    document.getElementById('insungOrderDetailModal').classList.remove('flex');
    document.body.style.overflow = 'auto';

    // 레이어 팝업이 닫힐 때 사이드바 복원
    if (typeof window.showSidebarForModal === 'function') {
        window.showSidebarForModal();
    }
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// ESC 키로 닫기
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('insungOrderDetailModal');
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
        closeInsungOrderDetail();
    }
});

// 배경 클릭 시 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('insungOrderDetailModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeInsungOrderDetail();
            }
        });
    }
});
</script>