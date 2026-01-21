<?php
// 일양 주문 상세 레이어팝업 컴포넌트
// 사용법: $this->include('forms/ilyang-order-detail-modal')
// JavaScript API:
//   viewIlyangOrderDetail(orderId, apiEndpoint) - 일양 주문 상세 팝업 표시
//   closeIlyangOrderDetail() - 팝업 닫기
// apiEndpoint: '/history/getIlyangOrderDetail' 또는 '/delivery/getIlyangOrderDetail'
?>

<!-- 일양 주문 상세 레이어 팝업 -->
<div id="ilyangOrderDetailModal" class="fixed inset-0 hidden flex items-center justify-center p-4 order-detail-modal" style="z-index: 9999; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col order-detail-modal-content" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-orange-50 border-b border-orange-200 px-6 py-4 flex justify-between items-center flex-shrink-0 rounded-t-lg">
            <h3 class="text-lg font-bold text-orange-800">일양 주문 상세 정보</h3>
            <button type="button" onclick="closeIlyangOrderDetail()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-2 overflow-y-auto flex-1">
            <div id="ilyangOrderDetailContent" class="modal-content">
                <!-- 내용은 populateIlyangOrderDetail()에서 동적으로 생성됩니다 -->
            </div>
        </div>
    </div>
</div>

<style>
/* 일양 주문 상세 팝업 모바일 반응형 */
@media (max-width: 767px) {
    .ilyang-detail-grid-row {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
// 일양 주문 상세 팝업용 API 엔드포인트 (기본값)
let ilyangApiEndpoint = '/history/getIlyangOrderDetail';

function viewIlyangOrderDetail(orderId, apiEndpoint) {
    // API 엔드포인트 설정
    if (apiEndpoint) {
        ilyangApiEndpoint = apiEndpoint;
    }

    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }

    // 로딩 상태 표시
    showIlyangOrderDetailLoading();

    // AJAX로 일양 주문 상세 정보 가져오기
    fetch(`${ilyangApiEndpoint}?order_id=${encodeURIComponent(orderId)}`, {
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
        console.log('Ilyang API Response:', data);
        if (data.success) {
            try {
                populateIlyangOrderDetail(data.data);
                // 모달 표시
                document.getElementById('ilyangOrderDetailModal').classList.remove('hidden');
                document.getElementById('ilyangOrderDetailModal').classList.add('flex');
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('populateIlyangOrderDetail Error:', error);
                showIlyangOrderDetailError('주문 정보 표시 중 오류가 발생했습니다: ' + error.message);
            }
        } else {
            showIlyangOrderDetailError(data.message || '주문 정보를 가져올 수 없습니다.');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showIlyangOrderDetailError('주문 정보 조회 중 오류가 발생했습니다: ' + error.message);
    });
}

function populateIlyangOrderDetail(orderData) {
    // 헬퍼 함수: 값이 있으면 표시, 없으면 '-'
    const getValue = (value) => {
        if (value === null || value === undefined || value === '') return '-';
        if (typeof value === 'object') return JSON.stringify(value);
        return value;
    };

    // 결제타입 라벨
    const payTypeLabels = {
        '11': '신용',
        '21': '선불',
        '22': '착불'
    };

    // 상태 라벨
    const stateLabels = {
        'pending': '대기중',
        'processing': '접수완료',
        'completed': '배송완료',
        'cancelled': '취소',
        '접수': '접수',
        '집하': '집하',
        '간선상차': '간선상차',
        '간선하차': '간선하차',
        '배송출고': '배송출고',
        '배달완료': '배달완료',
        '미배달': '미배달'
    };

    // 섹션별 필드 정의
    const sections = {
        '기본 정보': [
            { key: 'ily_awb_no', label: '운송장번호' },
            { key: 'ily_cus_ordno', label: '주문번호' },
            { key: 'ily_shp_date', label: '발송일자' },
            { key: 'service_type_name', label: '서비스유형' },
            { key: 'state', label: '배송상태' },
            { key: 'ily_rec_type', label: '수신타입' }
        ],
        '발송인 정보': [
            { key: 'ily_snd_name', label: '회사명' },
            { key: 'ily_snd_man_name', label: '담당자명' },
            { key: 'ily_snd_tel1', label: '전화번호1' },
            { key: 'ily_snd_tel2', label: '전화번호2' },
            { key: 'ily_snd_zip', label: '우편번호' },
            { key: 'ily_snd_addr', label: '주소' },
            { key: 'ily_snd_center', label: '센터코드' }
        ],
        '수취인 정보': [
            { key: 'ily_rcv_name', label: '회사명' },
            { key: 'ily_rcv_man_name', label: '담당자명' },
            { key: 'ily_rcv_tel1', label: '전화번호1' },
            { key: 'ily_rcv_tel2', label: '전화번호2' },
            { key: 'ily_rcv_zip', label: '우편번호' },
            { key: 'ily_rcv_addr', label: '주소' },
            { key: 'ily_rcv_center', label: '센터코드' }
        ],
        '상품 정보': [
            { key: 'ily_god_name', label: '상품명' },
            { key: 'ily_god_price', label: '상품가격', suffix: '원' },
            { key: 'ily_box_qty', label: '박스수량', suffix: '개' },
            { key: 'ily_box_wgt', label: '박스중량', suffix: 'kg' }
        ],
        '배송 정보': [
            { key: 'ily_pay_type', label: '결제타입' },
            { key: 'ily_amt_cash', label: '운임금액', suffix: '원' },
            { key: 'ily_dlv_rmks', label: '배송비고' },
            { key: 'ily_dlv_mesg', label: '배송메시지' }
        ],
        '계정 정보': [
            { key: 'ily_cus_acno', label: '고객계좌번호' },
            { key: 'ily_cus_apild', label: '고객API ID' },
            { key: 'ily_org_awbno', label: '원본운송장번호' }
        ]
    };

    // 섹션별 패널 생성 함수
    const createSectionPanel = (sectionTitle, fieldDefs) => {
        const sectionFields = [];

        for (const fieldDef of fieldDefs) {
            const key = fieldDef.key;
            const label = fieldDef.label;

            let value = orderData[key] ?? null;

            if (value !== null && value !== undefined && value !== '') {
                // 특정 필드 값 변환
                if (key === 'ily_pay_type') {
                    value = payTypeLabels[value] || value;
                } else if (key === 'state') {
                    value = stateLabels[value] || value;
                } else if (key === 'ily_shp_date' && value.length === 8) {
                    // YYYYMMDD -> YYYY-MM-DD 변환
                    value = value.substring(0, 4) + '-' + value.substring(4, 6) + '-' + value.substring(6, 8);
                }

                // suffix가 있으면 추가
                if (fieldDef.suffix && value !== '-') {
                    value = value + ' ' + fieldDef.suffix;
                }

                sectionFields.push({ key, label, value });
            }
        }

        // 필드가 있는 섹션만 패널 반환
        if (sectionFields.length > 0) {
            return `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); height: 100%;">
                    <div style="font-size: 14px; font-weight: 600; color: #ea580c; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #fed7aa;">
                        ${sectionTitle}
                    </div>
                    <div>
                        ${sectionFields.map(field => `
                            <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6; font-size: 12px; line-height: 1.6;">
                                <span style="font-weight: 600; color: #374151; display: inline-block; min-width: 100px;">${field.label}</span>
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
    const basicPanel = createSectionPanel('기본 정보', sections['기본 정보']);
    const senderPanel = createSectionPanel('발송인 정보', sections['발송인 정보']);
    const receiverPanel = createSectionPanel('수취인 정보', sections['수취인 정보']);
    const productPanel = createSectionPanel('상품 정보', sections['상품 정보']);
    const deliveryPanel = createSectionPanel('배송 정보', sections['배송 정보']);
    const accountPanel = createSectionPanel('계정 정보', sections['계정 정보']);

    let content = '<div style="padding: 8px; background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%); border-radius: 8px; width: 100%; box-sizing: border-box;">';

    // 첫 번째 행: 기본 정보 | 상품 정보
    if (basicPanel || productPanel) {
        content += '<div class="ilyang-detail-grid-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; align-items: stretch; width: 100%;">';
        content += (basicPanel || '<div></div>');
        content += (productPanel || '<div></div>');
        content += '</div>';
    }

    // 두 번째 행: 발송인 정보 | 수취인 정보
    if (senderPanel || receiverPanel) {
        content += '<div class="ilyang-detail-grid-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; align-items: stretch; width: 100%;">';
        content += (senderPanel || '<div></div>');
        content += (receiverPanel || '<div></div>');
        content += '</div>';
    }

    // 세 번째 행: 배송 정보 | 계정 정보
    if (deliveryPanel || accountPanel) {
        content += '<div class="ilyang-detail-grid-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 0; align-items: stretch; width: 100%;">';
        content += (deliveryPanel || '<div></div>');
        content += (accountPanel || '<div></div>');
        content += '</div>';
    }

    content += '</div>';

    document.getElementById('ilyangOrderDetailContent').innerHTML = content;
}

function showIlyangOrderDetailLoading() {
    const content = document.getElementById('ilyangOrderDetailContent');
    content.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">주문 정보를 불러오는 중...</div>';

    document.getElementById('ilyangOrderDetailModal').classList.remove('hidden');
    document.getElementById('ilyangOrderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function showIlyangOrderDetailError(message) {
    const content = document.getElementById('ilyangOrderDetailContent');
    content.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="color: #ef4444; margin-bottom: 16px;">⚠️</div>
            <div style="color: #ef4444; font-weight: 600; margin-bottom: 8px;">오류 발생</div>
            <div style="color: #6b7280;">${message}</div>
        </div>
    `;

    document.getElementById('ilyangOrderDetailModal').classList.remove('hidden');
    document.getElementById('ilyangOrderDetailModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeIlyangOrderDetail() {
    document.getElementById('ilyangOrderDetailModal').classList.add('hidden');
    document.getElementById('ilyangOrderDetailModal').classList.remove('flex');
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
    const modal = document.getElementById('ilyangOrderDetailModal');
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
        closeIlyangOrderDetail();
    }
});

// 배경 클릭 시 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('ilyangOrderDetailModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeIlyangOrderDetail();
            }
        });
    }
});
</script>