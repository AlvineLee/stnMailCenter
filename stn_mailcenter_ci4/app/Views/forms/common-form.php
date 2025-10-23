<?php
// 공통 폼 컴포넌트 - 주문자정보, 출발지, 도착지만 포함
?>

<!-- 주문자 정보 -->
<div class="mb-2">
    <section class="bg-blue-50 rounded-lg shadow-sm border border-blue-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">주문자 정보</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="space-y-1">
                <input type="text" id="company_name" name="company_name" value="<?= old('company_name', session()->get('company_name', '')) ?>" required
                       placeholder="회사명"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="tel" id="contact" name="contact" value="<?= old('contact', session()->get('phone', '')) ?>" required
                       placeholder="연락처"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1 flex items-center">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="notification_service" name="notification_service" value="1" <?= old('notification_service') ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">알림서비스</span>
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                    </svg>
                </label>
            </div>
        </div>
    </section>
</div>

<!-- 출발지 정보 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">출발지</h2>
        
        <!-- 상단 버튼들 -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="use_orderer_info_departure" name="use_orderer_info_departure" value="1" class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">주문자정보</span>
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                    </svg>
                </label>
            </div>
            <div class="flex space-x-2">
                <button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                    최근접수내역
                </button>
                <button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                    즐겨찾기
                </button>
            </div>
        </div>
        
        <!-- 입력 필드들 -->
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="departure_company_name" name="departure_company_name" value="<?= old('departure_company_name') ?>" placeholder="상호(이름) *" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="tel" id="departure_contact" name="departure_contact" value="<?= old('departure_contact') ?>" placeholder="연락처 *" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="departure_department" name="departure_department" value="<?= old('departure_department') ?>" placeholder="부서"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="text" id="departure_manager" name="departure_manager" value="<?= old('departure_manager') ?>" placeholder="담당"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="space-y-2">
            <div class="flex space-x-2">
                <input type="text" id="departure_dong" name="departure_dong" value="<?= old('departure_dong') ?>" placeholder="기준동"
                       class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                <button type="button" id="departure_address_search" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                    주소검색
                </button>
            </div>
            <input type="text" id="departure_address" name="departure_address" value="<?= old('departure_address') ?>" placeholder="주소"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
        </div>
    </section>
</div>

<!-- 경유지 정보 (배송방법이 경유일 때만 표시) -->
<div class="mb-2" id="waypointSection" style="display: none;">
    <section class="bg-blue-25 rounded-lg shadow-sm border border-blue-100 p-3">
        <h2 class="text-sm font-semibold text-blue-600 mb-3 pb-1 border-b border-blue-200">경유지 정보</h2>
        <div class="space-y-2">
            <input type="text" id="waypoint_address" name="waypoint_address" value="<?= old('waypoint_address') ?>" placeholder="경유지 주소 *"
                   class="w-full px-3 py-2 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white">
            <input type="text" id="waypoint_detail" name="waypoint_detail" value="<?= old('waypoint_detail') ?>" placeholder="상세주소"
                   class="w-full px-3 py-2 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white">
            <input type="tel" id="waypoint_contact" name="waypoint_contact" value="<?= old('waypoint_contact') ?>" placeholder="경유지 연락처"
                   class="w-full px-3 py-2 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white">
            <textarea id="waypoint_notes" name="waypoint_notes" rows="2" placeholder="경유지 특이사항"
                      class="w-full px-3 py-2 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white resize-none"><?= old('waypoint_notes') ?></textarea>
        </div>
    </section>
</div>

<!-- 도착지 정보 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">도착지</h2>
        
        <!-- 상단 버튼들 -->
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="use_orderer_info_destination" name="use_orderer_info_destination" value="1" class="text-gray-600 focus:ring-gray-500">
                    <span class="text-sm font-medium text-gray-700">주문자정보</span>
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                    </svg>
                </label>
            </div>
            <div class="flex space-x-2">
                <button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                    최근접수내역
                </button>
                <button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                    즐겨찾기
                </button>
            </div>
        </div>
        
        <!-- 라디오 버튼 그룹 -->
        <div class="flex space-x-4 mb-3">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="destination_type" value="mailroom" <?= old('destination_type', 'mailroom') === 'mailroom' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">메일룸배송</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="destination_type" value="direct" <?= old('destination_type') === 'direct' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-sm font-medium text-gray-700">직접배송</span>
            </label>
        </div>
        
        <!-- 입력 필드들 -->
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="destination_company_name" name="destination_company_name" value="<?= old('destination_company_name') ?>" placeholder="상호(이름) *" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="tel" id="destination_contact" name="destination_contact" value="<?= old('destination_contact') ?>" placeholder="연락처 *" required
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="destination_department" name="destination_department" value="<?= old('destination_department') ?>" placeholder="부서"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="text" id="destination_manager" name="destination_manager" value="<?= old('destination_manager') ?>" placeholder="담당"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="space-y-2">
            <div class="flex space-x-2">
                <input type="text" id="destination_dong" name="destination_dong" value="<?= old('destination_dong') ?>" placeholder="기준동"
                       class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                <button type="button" id="destination_address_search" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                    주소검색
                </button>
            </div>
            <input type="text" id="destination_address" name="destination_address" value="<?= old('destination_address') ?>" placeholder="주소"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
        </div>
    </section>
</div>

<script>
// 다음 주소 검색 API
function openDaumAddressSearch(type) {
    new daum.Postcode({
        oncomplete: function(data) {
            let addr = '';
            let extraAddr = '';

            if (data.userSelectedType === 'R') {
                addr = data.roadAddress;
            } else {
                addr = data.jibunAddress;
            }

            if (data.userSelectedType === 'R') {
                if (data.bname !== '' && /[동|로|가]$/g.test(data.bname)) {
                    extraAddr += data.bname;
                }
                if (data.buildingName !== '' && data.apartment === 'Y') {
                    extraAddr += (extraAddr !== '' ? ', ' + data.buildingName : data.buildingName);
                }
                if (extraAddr !== '') {
                    extraAddr = ' (' + extraAddr + ')';
                }
            }

            // 주소 필드에 값 설정
            if (type === 'departure') {
                document.getElementById('departure_dong').value = data.zonecode;
                document.getElementById('departure_address').value = addr + extraAddr;
            } else if (type === 'destination') {
                document.getElementById('destination_dong').value = data.zonecode;
                document.getElementById('destination_address').value = addr + extraAddr;
            }
        }
    }).open();
}

// 주문자 정보 자동 입력 기능
function copyOrdererInfo(type) {
    const companyName = document.getElementById('company_name').value;
    const contact = document.getElementById('contact').value;
    
    if (type === 'departure') {
        document.getElementById('departure_company_name').value = companyName;
        document.getElementById('departure_contact').value = contact;
    } else if (type === 'destination') {
        document.getElementById('destination_company_name').value = companyName;
        document.getElementById('destination_contact').value = contact;
    }
}

// 도착지 타입 변경 시 필드 활성화/비활성화
document.querySelectorAll('input[name="destination_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const destinationAddress = document.getElementById('destination_address');
        const destinationDong = document.getElementById('destination_dong');

        if (this.value === 'direct') {
            destinationAddress.disabled = false;
            destinationAddress.classList.remove('bg-gray-100');
            destinationAddress.classList.add('bg-white');
            destinationDong.disabled = false;
            destinationDong.classList.remove('bg-gray-100');
            destinationDong.classList.add('bg-white');
        } else {
            destinationAddress.disabled = true;
            destinationAddress.classList.add('bg-gray-100');
            destinationAddress.classList.remove('bg-white');
            destinationDong.disabled = true;
            destinationDong.classList.add('bg-gray-100');
            destinationDong.classList.remove('bg-white');
        }
    });
});

// 배송방법 변경 시 경유지 정보 섹션 표시/숨김
function toggleWaypointSection() {
    const waypointSection = document.getElementById('waypointSection');
    const deliveryRoute = document.querySelector('input[name="delivery_route"]:checked');
    
    if (deliveryRoute && deliveryRoute.value === 'via') {
        waypointSection.style.display = 'block';
        // 경유지 주소 필수 입력으로 변경
        document.getElementById('waypoint_address').required = true;
    } else {
        waypointSection.style.display = 'none';
        // 경유지 주소 필수 입력 해제
        document.getElementById('waypoint_address').required = false;
    }
}

// 페이지 로드 시 초기 실행
document.addEventListener('DOMContentLoaded', function() {
    toggleWaypointSection();
    
    // 배송방법 라디오 버튼 변경 이벤트 리스너 추가
    document.querySelectorAll('input[name="delivery_route"]').forEach(radio => {
        radio.addEventListener('change', toggleWaypointSection);
    });
    
    // 주소 검색 버튼 이벤트
    document.getElementById('departure_address_search').addEventListener('click', function() {
        openDaumAddressSearch('departure');
    });
    
    document.getElementById('destination_address_search').addEventListener('click', function() {
        openDaumAddressSearch('destination');
    });
    
    // 주문자 정보 자동 입력 체크박스 이벤트
    document.getElementById('use_orderer_info_departure').addEventListener('change', function() {
        if (this.checked) {
            copyOrdererInfo('departure');
        }
    });
    
    document.getElementById('use_orderer_info_destination').addEventListener('change', function() {
        if (this.checked) {
            copyOrdererInfo('destination');
        }
    });
});
</script>

<!-- 다음 주소 검색 API 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>