<?php
// 공통 폼 컴포넌트 - 주문자정보, 출발지, 도착지만 포함

// 로그인한 사용자 정보 조회 (주소, 부서 정보 포함)
$userInfo = null;
if (session()->get('is_logged_in')) {
    $userId = session()->get('user_id');
    if ($userId) {
        $userModel = new \App\Models\UserModel();
        $userInfo = $userModel->getUserAccountInfo($userId);
    }
}
?>

<!-- 주문자 정보 -->
<div class="mb-1">
    <section class="bg-blue-50 rounded-lg shadow-sm border border-blue-200 p-3">
        <div class="flex items-center justify-between mb-2 pb-1 border-b border-gray-300">
            <h2 class="text-sm font-semibold text-gray-700">주문자 정보</h2>
            <?php if (session()->get('login_type') === 'daumdata'): ?>
            <button type="button" id="employeeSearchBtn" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                직원검색
            </button>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div class="space-y-1">
                <?php 
                // daumdata 로그인 시 comp_name, STN 로그인 시 customer_name 사용
                $loginType = session()->get('login_type');
                $companyName = '';
                if ($loginType === 'daumdata') {
                    $companyName = session()->get('comp_name', '');
                } else {
                    $companyName = session()->get('customer_name', '');
                }
                ?>
                <input type="text" id="company_name" name="company_name" value="<?= old('company_name', $companyName) ?>" required
                       placeholder="회사명" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                <!-- STN_LOGIS 호환 필드 -->
                <input type="hidden" id="c_name" name="c_name" value="<?= old('company_name', $companyName) ?>">
            </div>
            <div class="space-y-1">
                <?php 
                // daumdata 로그인 시 user_tel1, STN 로그인 시 phone 사용
                $contact = '';
                if ($loginType === 'daumdata') {
                    $contact = session()->get('user_tel1', '');
                } else {
                    $contact = session()->get('phone', '');
                }
                ?>
                <input type="tel" id="contact" name="contact" value="<?= old('contact', $contact) ?>" required
                       placeholder="연락처"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                <!-- STN_LOGIS 호환 필드 -->
                <input type="hidden" id="c_telno" name="c_telno" value="<?= old('contact', $contact) ?>">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div class="space-y-1">
                <?php 
                // daumdata 로그인 시 user_dept 사용
                $dept = '';
                if ($loginType === 'daumdata') {
                    $dept = session()->get('user_dept', '');
                }
                ?>
                <input type="text" id="dept" name="dept" value="<?= old('dept', $dept) ?>" 
                       placeholder="부서" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                <!-- STN_LOGIS 호환 필드 -->
                <input type="hidden" id="c_dept" name="c_dept" value="<?= old('dept', $dept) ?>">
            </div>
            <div class="space-y-1">
                <?php 
                // daumdata 로그인 시 user_name 사용
                $charge = '';
                if ($loginType === 'daumdata') {
                    $charge = session()->get('user_name', '');
                }
                ?>
                <input type="text" id="charge" name="charge" value="<?= old('charge', $charge) ?>" 
                       placeholder="담당" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                <!-- STN_LOGIS 호환 필드 -->
                <input type="hidden" id="c_charge" name="c_charge" value="<?= old('charge', $charge) ?>">
            </div>
        </div>
    </section>
</div>

<!-- 출발지 정보 -->
<div class="mb-1">
    <section class="bg-red-50/30 rounded-lg shadow-sm border border-red-200/50 p-3">
        <div class="flex items-center justify-between mb-3 pb-1 border-b border-gray-300">
            <h2 class="text-sm font-semibold text-gray-700">출발지</h2>
            <button type="button" id="swapAddressesBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors flex items-center space-x-1" onclick="swapDepartureDestination()" title="출발지와 도착지 정보 교환">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <!-- 위쪽 원형 화살표 (시계 방향) - 출발지 (빨간색) -->
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" stroke="#ff4444" d="M 12 4 A 8 8 0 0 1 20 12" fill="none"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" stroke="#ff4444" d="M 20 12 L 16 8 M 20 12 L 16 16" fill="none"/>
                    <!-- 아래쪽 원형 화살표 (반시계 방향) - 도착지 (초록색) -->
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" stroke="#44ff44" d="M 12 20 A 8 8 0 0 1 4 12" fill="none"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" stroke="#44ff44" d="M 4 12 L 8 8 M 4 12 L 8 16" fill="none"/>
                </svg>
                <span>출도착지 교환</span>
            </button>
        </div>
        
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
            <div class="flex space-x-2 relative">
                <button type="button" id="recent_departure_btn" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors" onclick="select_pop('<?= base_url('bookmark/recent-popup?type=S') ?>', 'RECENT_S', 1363, 919)">
                    최근접수내역
                </button>
                <!-- 최근 접수 내역 팝오버 (출발지) -->
                <div id="recentOrdersPopup" class="hidden absolute z-50 w-[280px] max-w-[calc(100vw-2rem)] bg-white border border-gray-300 rounded-lg shadow-2xl mt-1 max-h-80 opacity-0 transform translate-y-[-10px] transition-all duration-300 ease-out flex flex-col overflow-hidden" style="top: 100%; right: 0; left: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
                    <div class="flex-shrink-0">
                        <div id="recentOrdersHeader" class="grid grid-cols-2 gap-2 text-xs font-semibold text-gray-700 px-3 py-2 bg-gray-50 border-b border-gray-200 rounded-t-lg"></div>
                    </div>
                    <div id="recentOrdersList" class="overflow-y-auto flex-1 space-y-1 px-2 pb-2">
                        <div class="text-xs text-gray-500 px-2 py-1">로딩 중...</div>
                    </div>
                </div>
                <button type="button" id="bookmark_departure_btn" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors" onclick="select_pop('<?= base_url('bookmark/popup?type=S') ?>', 'BOOKMARK_S', 750, 500)">
                    즐겨찾기
                </button>
            </div>
        </div>
        
        <!-- 입력 필드들 -->
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="departure_company_name" name="departure_company_name" value="<?= old('departure_company_name') ?>" placeholder="상호(이름)" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="tel" id="departure_contact" name="departure_contact" value="<?= old('departure_contact') ?>" placeholder="연락처" required
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="departure_department" name="departure_department" value="<?= old('departure_department') ?>" placeholder="부서" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="text" id="departure_manager" name="departure_manager" value="<?= old('departure_manager') ?>" placeholder="담당" required lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="space-y-2">
            <div class="flex space-x-2">
                <input type="text" id="departure_detail" name="departure_detail" value="<?= old('departure_detail') ?>" placeholder="상세주소" required lang="ko"
                       class="flex-1 px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                <button type="button" id="departure_address_search" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                    주소검색
                </button>
            </div>
            <input type="text" id="departure_address" name="departure_address" value="<?= old('departure_address') ?>" placeholder="주소" required lang="ko"
                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <!-- 기준동 필드 (hidden) -->
            <input type="hidden" id="departure_dong" name="departure_dong" value="<?= old('departure_dong') ?>">
            <!-- 루비 버전 참조: s_dong2 필드 (data.bname1 값) -->
            <input type="hidden" id="departure_dong2" name="departure_dong2" value="<?= old('departure_dong2') ?>">
            <!-- 루비 버전 참조: s_fulladdr 필드 (지번 주소, 좌표 조회용) -->
            <input type="hidden" id="departure_fulladdr" name="departure_fulladdr" value="<?= old('departure_fulladdr') ?>">
            
            <!-- 해외특송 서비스인 경우 국가/지역 필드 추가 -->
            <?php if (isset($service_type) && $service_type === 'international'): ?>
            <input type="text" id="departure_country" name="departure_country" value="<?= old('departure_country') ?>" placeholder="국가/지역" lang="ko"
                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- 경유지 정보 (배송방법이 경유일 때만 표시) -->
<div class="mb-1" id="waypointSection" style="display: none;">
    <section class="bg-blue-25 rounded-lg shadow-sm border border-blue-100 p-3">
        <h2 class="text-sm font-semibold text-blue-600 mb-3 pb-1 border-b border-blue-200">경유지 정보</h2>
        <div class="space-y-2">
            <input type="text" id="waypoint_address" name="waypoint_address" value="<?= old('waypoint_address') ?>" placeholder="경유지 주소 *" lang="ko"
                   class="w-full px-3 py-1 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white">
            <input type="text" id="waypoint_detail" name="waypoint_detail" value="<?= old('waypoint_detail') ?>" placeholder="상세주소" lang="ko"
                   class="w-full px-3 py-1 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white">
            <input type="tel" id="waypoint_contact" name="waypoint_contact" value="<?= old('waypoint_contact') ?>" placeholder="경유지 연락처" lang="ko"
                   class="w-full px-3 py-1 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white">
            <textarea id="waypoint_notes" name="waypoint_notes" rows="2" placeholder="경유지 특이사항" lang="ko"
                      class="w-full px-3 py-2 text-sm border border-blue-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white resize-none"><?= old('waypoint_notes') ?></textarea>
        </div>
    </section>
</div>

<!-- 도착지 정보 -->
<div class="mb-1">
    <section class="bg-green-50/30 rounded-lg shadow-sm border border-green-200/50 p-3">
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
            <div class="flex space-x-2 relative">
                <button type="button" id="recent_destination_btn" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors" onclick="select_pop('<?= base_url('bookmark/recent-popup?type=D') ?>', 'RECENT_D', 1363, 919)">
                    최근접수내역
                </button>
                <!-- 최근 접수 내역 팝오버 (도착지) -->
                <div id="recentDestinationOrdersPopup" class="hidden absolute z-50 w-[280px] max-w-[calc(100vw-2rem)] bg-white border border-gray-300 rounded-lg shadow-2xl mt-1 max-h-80 opacity-0 transform translate-y-[-10px] transition-all duration-300 ease-out flex flex-col overflow-hidden" style="top: 100%; right: 0; left: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
                    <div class="flex-shrink-0">
                        <div id="recentDestinationOrdersHeader" class="grid grid-cols-2 gap-2 text-xs font-semibold text-gray-700 px-3 py-2 bg-gray-50 border-b border-gray-200 rounded-t-lg"></div>
                    </div>
                    <div id="recentDestinationOrdersList" class="overflow-y-auto flex-1 space-y-1 px-2 pb-2">
                        <div class="text-xs text-gray-500 px-2 py-1">로딩 중...</div>
                    </div>
                </div>
                <button type="button" id="bookmark_destination_btn" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors" onclick="select_pop('<?= base_url('bookmark/popup?type=D') ?>', 'BOOKMARK_D', 750, 500)">
                    즐겨찾기
                </button>
            </div>
        </div>
        
        <!-- 입력 필드들 -->
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="destination_company_name" name="destination_company_name" value="<?= old('destination_company_name') ?>" placeholder="상호(이름)" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="tel" id="destination_contact" name="destination_contact" value="<?= old('destination_contact') ?>" placeholder="연락처"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="space-y-1">
                <input type="text" id="destination_department" name="destination_department" value="<?= old('destination_department') ?>" placeholder="부서" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="text" id="destination_manager" name="destination_manager" value="<?= old('destination_manager') ?>" placeholder="담당" lang="ko"
                       class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
        
        <div class="space-y-2">
            <div class="flex space-x-2">
                <input type="text" id="destination_detail" name="destination_detail" value="<?= old('destination_detail') ?>" placeholder="상세주소" required lang="ko"
                       class="flex-1 px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
                <button type="button" id="destination_address_search" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                    주소검색
                </button>
            </div>
            <input type="text" id="destination_address" name="destination_address" value="<?= old('destination_address') ?>" placeholder="주소" required lang="ko"
                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <!-- 기준동 필드 (hidden) -->
            <input type="hidden" id="destination_dong" name="destination_dong" value="<?= old('destination_dong') ?>">
            <!-- 루비 버전 참조: d_dong2 필드 (data.bname1 값) -->
            <input type="hidden" id="destination_dong2" name="destination_dong2" value="<?= old('destination_dong2') ?>">
            <!-- 루비 버전 참조: d_fulladdr 필드 (지번 주소, 좌표 조회용) -->
            <input type="hidden" id="destination_fulladdr" name="destination_fulladdr" value="<?= old('destination_fulladdr') ?>">
            
            <!-- 해외특송 서비스인 경우 국가/지역 필드 추가 -->
            <?php if (isset($service_type) && $service_type === 'international'): ?>
            <input type="text" id="destination_country" name="destination_country" value="<?= old('destination_country') ?>" placeholder="국가/지역" lang="ko"
                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
// 출발지와 도착지 정보 교환
function swapDepartureDestination() {
    // 교환할 필드 목록
    const fieldsToSwap = [
        { departure: 'departure_company_name', destination: 'destination_company_name' },
        { departure: 'departure_contact', destination: 'destination_contact' },
        { departure: 'departure_department', destination: 'destination_department' },
        { departure: 'departure_manager', destination: 'destination_manager' },
        { departure: 'departure_dong', destination: 'destination_dong' },
        { departure: 'departure_address', destination: 'destination_address' },
        { departure: 'departure_detail', destination: 'destination_detail' },
        { departure: 'departure_dong2', destination: 'destination_dong2' },
        { departure: 'departure_fulladdr', destination: 'destination_fulladdr' },
        { departure: 'use_orderer_info_departure', destination: 'use_orderer_info_destination' }
    ];
    
    // 해외특송인 경우 국가 필드도 교환
    const departureCountry = document.getElementById('departure_country');
    const destinationCountry = document.getElementById('destination_country');
    if (departureCountry && destinationCountry) {
        const tempCountry = departureCountry.value;
        departureCountry.value = destinationCountry.value;
        destinationCountry.value = tempCountry;
    }
    
    // 각 필드 교환
    fieldsToSwap.forEach(function(fieldPair) {
        const departureField = document.getElementById(fieldPair.departure);
        const destinationField = document.getElementById(fieldPair.destination);
        
        if (departureField && destinationField) {
            // 체크박스인 경우
            if (departureField.type === 'checkbox') {
                const tempChecked = departureField.checked;
                departureField.checked = destinationField.checked;
                destinationField.checked = tempChecked;
            } else {
                // 일반 입력 필드인 경우
                const tempValue = departureField.value;
                departureField.value = destinationField.value;
                destinationField.value = tempValue;
            }
        }
    });
    
    // detail_address 필드도 확인 (STN_LOGIS 호환)
    const detailAddress = document.getElementById('detail_address');
    const destinationDetail = document.getElementById('destination_detail');
    if (detailAddress && destinationDetail) {
        const tempDetail = detailAddress.value;
        detailAddress.value = destinationDetail.value;
        destinationDetail.value = tempDetail;
    }
}

// 다음 주소 검색 API
function openDaumAddressSearch(type) {
    new daum.Postcode({
        oncomplete: function(data) {
            let addr = '';
            let extraAddr = '';
            let detailAddr = ''; // 상세주소용

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

            // 상세주소 필드에 건물명 등 추가 정보 설정
            if (data.buildingName && data.buildingName !== '') {
                detailAddr = data.buildingName;
            }
            
            // 법정동명이 있고 건물명이 없는 경우
            if (!detailAddr && data.bname && data.bname !== '') {
                detailAddr = data.bname;
            }

            // 주소 필드에 값 설정
            // 루비 버전 참조: $s_dong2 또는 $s_dong (동 이름) 사용, 우편번호가 아님
            // data.bname1이 있으면 우선 사용, 없으면 data.bname 사용
            // 루비 버전: s_dongname에 data.bname, s_dong2에 data.bname1 설정
            const dongName = data.bname || ''; // departure_dong에 bname 설정
            const dongName2 = data.bname1 || ''; // departure_dong2에 bname1 설정
            
            // 루비 버전 참조: s_fulladdr는 지번 주소 (jibunAddress 또는 autoJibunAddress)
            const jibunAddr = data.jibunAddress || data.autoJibunAddress || '';
            
            if (type === 'departure') {
                document.getElementById('departure_dong').value = dongName; // 동 이름 사용 (우편번호 아님)
                const dong2Field = document.getElementById('departure_dong2');
                if (dong2Field) {
                    dong2Field.value = dongName2; // bname1 값 설정
                }
                document.getElementById('departure_address').value = addr + extraAddr;
                // 루비 버전 참조: departure_fulladdr에 지번 주소 저장 (좌표 조회용)
                const fulladdrField = document.getElementById('departure_fulladdr');
                if (fulladdrField) {
                    fulladdrField.value = jibunAddr;
                }
                // 상세주소 필드가 있으면 건물명 등 설정 (없으면 빈 값)
                const detailField = document.getElementById('departure_detail');
                if (detailField) {
                    detailField.value = detailAddr;
                    // 상세주소 필드에 포커스 (사용자가 추가 입력 가능하도록)
                    setTimeout(function() {
                        detailField.focus();
                    }, 100);
                }
            } else if (type === 'destination') {
                document.getElementById('destination_dong').value = dongName; // 동 이름 사용 (우편번호 아님)
                const dong2Field = document.getElementById('destination_dong2');
                if (dong2Field) {
                    dong2Field.value = dongName2; // bname1 값 설정
                }
                document.getElementById('destination_address').value = addr + extraAddr;
                // 루비 버전 참조: destination_fulladdr에 지번 주소 저장 (좌표 조회용)
                const fulladdrField = document.getElementById('destination_fulladdr');
                if (fulladdrField) {
                    fulladdrField.value = jibunAddr;
                }
                // 상세주소 필드가 있으면 건물명 등 설정 (없으면 빈 값)
                const detailField = document.getElementById('destination_detail');
                if (detailField) {
                    detailField.value = detailAddr;
                    // 상세주소 필드에 포커스 (사용자가 추가 입력 가능하도록)
                    setTimeout(function() {
                        detailField.focus();
                    }, 100);
                }
            }
        }
    }).open();
}

// 세션 정보와 사용자 정보를 JavaScript 변수로 전달
<?php
$loginType = session()->get('login_type');
$sessionCustomerName = '';
$sessionPhone = '';
$sessionRealName = '';
$sessionDepartment = '';
$sessionZonecode = '';
$sessionDong = ''; // 동 이름 (우편번호 아님)
$sessionAddress = '';
$sessionAddressDetail = '';

if ($loginType === 'daumdata') {
    // daumdata 로그인 시 세션 값 사용
    $sessionCustomerName = session()->get('comp_name', '');
    $sessionPhone = session()->get('user_tel1', '');
    $sessionRealName = session()->get('user_name', '');
    $sessionDepartment = session()->get('user_dept', '');
    // daumdata 로그인 시 주소 정보
    // user_dong은 동 이름이므로 그대로 사용
    $sessionDong = session()->get('user_dong', ''); // 동 이름 사용
    $sessionZonecode = ''; // zonecode는 주소 검색으로 얻어야 하므로 빈 값
    $sessionAddress = session()->get('user_addr', '');
    $sessionAddressDetail = session()->get('user_addr_detail', '');
} else {
    // STN 로그인 시
    $sessionCustomerName = session()->get('customer_name', '');
    $sessionPhone = session()->get('phone', '');
    $sessionRealName = session()->get('real_name', '');
    $sessionDepartment = $userInfo['department'] ?? '';
    $sessionZonecode = $userInfo['address_zonecode'] ?? '';
    // userInfo에서 동 이름 가져오기 (user_dong 필드가 있는 경우)
    $sessionDong = $userInfo['user_dong'] ?? '';
    $sessionAddress = $userInfo['address'] ?? '';
    $sessionAddressDetail = $userInfo['address_detail'] ?? '';
}
?>
const sessionData = {
    customer_name: '<?= esc($sessionCustomerName, 'js') ?>',
    phone: '<?= esc($sessionPhone, 'js') ?>',
    real_name: '<?= esc($sessionRealName, 'js') ?>',
    department: '<?= esc($sessionDepartment, 'js') ?>',
    address_zonecode: '<?= esc($sessionZonecode, 'js') ?>',
    dong: '<?= esc($sessionDong, 'js') ?>', // 동 이름 (우편번호 아님)
    address: '<?= esc($sessionAddress, 'js') ?>',
    address_detail: '<?= esc($sessionAddressDetail, 'js') ?>'
};

// 주문자 정보 자동 입력 기능 (세션 정보 사용)
function copyOrdererInfo(type) {
    // 세션 정보 우선 사용, 없으면 주문자 정보 필드에서 가져오기
    const companyName = sessionData.customer_name || document.getElementById('company_name').value;
    const contact = sessionData.phone || document.getElementById('contact').value;
    const department = sessionData.department || '';
    const manager = sessionData.real_name || '';
    const dongName = sessionData.dong || ''; // 동 이름 사용 (우편번호 아님)
    const address = sessionData.address || '';
    const addressDetail = sessionData.address_detail || '';
    
    if (type === 'departure') {
        document.getElementById('departure_company_name').value = companyName;
        document.getElementById('departure_contact').value = contact;
        if (document.getElementById('departure_department')) {
            document.getElementById('departure_department').value = department;
        }
        if (document.getElementById('departure_manager')) {
            document.getElementById('departure_manager').value = manager;
        }
        if (document.getElementById('departure_dong')) {
            document.getElementById('departure_dong').value = dongName; // 동 이름 사용 (우편번호 아님)
        }
        if (document.getElementById('departure_address')) {
            document.getElementById('departure_address').value = address;
        }
        if (document.getElementById('departure_detail')) {
            // addressDetail이 있으면 사용, 없으면 dongName 사용 (슬래시 처리 포함)
            let detailValue = addressDetail;
            if (!detailValue && dongName) {
                // dongName에서 슬래시가 있으면 슬래시 뒤의 값만 사용
                if (dongName.indexOf('/') !== -1) {
                    detailValue = dongName.split('/').pop().trim();
                } else {
                    detailValue = dongName;
                }
            }
            document.getElementById('departure_detail').value = detailValue || '';
        }
    } else if (type === 'destination') {
        document.getElementById('destination_company_name').value = companyName;
        document.getElementById('destination_contact').value = contact;
        if (document.getElementById('destination_department')) {
            document.getElementById('destination_department').value = department;
        }
        if (document.getElementById('destination_manager')) {
            document.getElementById('destination_manager').value = manager;
        }
        if (document.getElementById('destination_dong')) {
            document.getElementById('destination_dong').value = dongName; // 동 이름 사용 (우편번호 아님)
        }
        if (document.getElementById('destination_address')) {
            document.getElementById('destination_address').value = address;
        }
        if (document.getElementById('destination_detail')) {
            // addressDetail이 있으면 사용, 없으면 dongName 사용 (슬래시 처리 포함)
            let detailValue = addressDetail;
            if (!detailValue && dongName) {
                // dongName에서 슬래시가 있으면 슬래시 뒤의 값만 사용
                if (dongName.indexOf('/') !== -1) {
                    detailValue = dongName.split('/').pop().trim();
                } else {
                    detailValue = dongName;
                }
            }
            document.getElementById('destination_detail').value = detailValue || '';
        } else if (document.getElementById('detail_address')) {
            // detail_address 필드도 확인 (STN_LOGIS 호환)
            let detailValue = addressDetail;
            if (!detailValue && dongName) {
                // dongName에서 슬래시가 있으면 슬래시 뒤의 값만 사용
                if (dongName.indexOf('/') !== -1) {
                    detailValue = dongName.split('/').pop().trim();
                } else {
                    detailValue = dongName;
                }
            }
            document.getElementById('detail_address').value = detailValue || '';
        }
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

// required 속성이 있는 필드에 빨간색 별표 자동 추가
function addRequiredStars() {
    // 모든 required 속성이 있는 input, textarea, select 필드 찾기
    const requiredFields = document.querySelectorAll('input[required], textarea[required], select[required]');
    
    requiredFields.forEach(field => {
        // 이미 별표가 추가된 필드는 제외
        if (field.dataset.requiredStarAdded === 'true') {
            return;
        }
        
        // 필드가 화면에 보이는지 확인 (display: none인 필드는 제외)
        const style = window.getComputedStyle(field);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return;
        }
        
        // 부모 요소가 relative인지 확인하고, 없으면 추가
        let parent = field.parentElement;
        let needsWrapper = true;
        
        // 부모가 relative 클래스를 가지고 있거나 position이 relative인지 확인
        if (parent && (parent.classList.contains('relative') || getComputedStyle(parent).position === 'relative')) {
            needsWrapper = false;
        }
        
        // wrapper가 필요하면 추가
        if (needsWrapper) {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative';
            parent.insertBefore(wrapper, field);
            wrapper.appendChild(field);
            parent = wrapper;
        }
        
        // 필드에 오른쪽 패딩 추가 (별표 공간 확보) - Tailwind의 pr-6 클래스 추가
        if (!field.classList.contains('pr-6')) {
            field.classList.add('pr-6');
        }
        
        // 별표 추가
        const star = document.createElement('span');
        star.className = 'required-star absolute right-2 top-1/2 transform -translate-y-1/2 text-red-500 text-sm pointer-events-none';
        star.textContent = '*';
        parent.appendChild(star);
        
        // 처리 완료 표시
        field.dataset.requiredStarAdded = 'true';
    });
}

// 페이지 로드 시 초기 실행
document.addEventListener('DOMContentLoaded', function() {
    toggleWaypointSection();
    
    // required 필드에 별표 추가
    addRequiredStars();
    
    // 폼 요소 가져오기 (한 번만 선언)
    const orderForm = document.getElementById('orderForm');
    
    // 동적으로 추가되는 필드도 처리하기 위해 MutationObserver 사용
    if (orderForm) {
        const observer = new MutationObserver(function(mutations) {
            addRequiredStars();
        });
        
        // 폼 요소를 관찰
        observer.observe(orderForm, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['required']
        });
    }
    
    // 배송방법 라디오 버튼 변경 이벤트 리스너 추가
    document.querySelectorAll('input[name="delivery_route"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleWaypointSection();
            // 경유지 섹션이 표시되면 required 별표도 업데이트
            setTimeout(addRequiredStars, 100);
        });
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
    
    // 쿠키 저장/불러오기 유틸리티 함수
    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + encodeURIComponent(JSON.stringify(value)) + ';expires=' + expires.toUTCString() + ';path=/';
    }
    
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) {
                try {
                    return JSON.parse(decodeURIComponent(c.substring(nameEQ.length, c.length)));
                } catch (e) {
                    return null;
                }
            }
        }
        return null;
    }
    
    // 출발지 정보를 쿠키에 저장
    function saveDepartureInfo() {
        const departureInfo = {
            company_name: document.getElementById('departure_company_name')?.value || '',
            contact: document.getElementById('departure_contact')?.value || '',
            department: document.getElementById('departure_department')?.value || '',
            manager: document.getElementById('departure_manager')?.value || '',
            dong: document.getElementById('departure_dong')?.value || '',
            address: document.getElementById('departure_address')?.value || '',
            detail: document.getElementById('departure_detail')?.value || '',
            country: document.getElementById('departure_country')?.value || ''
        };
        setCookie('recent_departure_info', departureInfo, 30); // 30일 저장
    }
    
    // 도착지 정보를 쿠키에 저장
    function saveDestinationInfo() {
        const destinationInfo = {
            company_name: document.getElementById('destination_company_name')?.value || '',
            contact: document.getElementById('destination_contact')?.value || '',
            department: document.getElementById('destination_department')?.value || '',
            manager: document.getElementById('destination_manager')?.value || '',
            dong: document.getElementById('destination_dong')?.value || '',
            address: document.getElementById('destination_address')?.value || '',
            detail: document.getElementById('destination_detail')?.value || '',
            country: document.getElementById('destination_country')?.value || ''
        };
        setCookie('recent_destination_info', destinationInfo, 30); // 30일 저장
    }
    
    // 쿠키에서 출발지 정보 불러오기
    function loadDepartureInfo() {
        const departureInfo = getCookie('recent_departure_info');
        if (departureInfo) {
            if (document.getElementById('departure_company_name')) {
                document.getElementById('departure_company_name').value = departureInfo.company_name || '';
            }
            if (document.getElementById('departure_contact')) {
                document.getElementById('departure_contact').value = departureInfo.contact || '';
            }
            if (document.getElementById('departure_department')) {
                document.getElementById('departure_department').value = departureInfo.department || '';
            }
            if (document.getElementById('departure_manager')) {
                document.getElementById('departure_manager').value = departureInfo.manager || '';
            }
            if (document.getElementById('departure_dong')) {
                document.getElementById('departure_dong').value = departureInfo.dong || '';
            }
            if (document.getElementById('departure_address')) {
                document.getElementById('departure_address').value = departureInfo.address || '';
            }
            if (document.getElementById('departure_detail')) {
                document.getElementById('departure_detail').value = departureInfo.detail || '';
            }
            if (document.getElementById('departure_country') && departureInfo.country) {
                document.getElementById('departure_country').value = departureInfo.country || '';
            }
        }
    }
    
    // 쿠키에서 도착지 정보 불러오기
    function loadDestinationInfo() {
        const destinationInfo = getCookie('recent_destination_info');
        if (destinationInfo) {
            if (document.getElementById('destination_company_name')) {
                document.getElementById('destination_company_name').value = destinationInfo.company_name || '';
            }
            if (document.getElementById('destination_contact')) {
                document.getElementById('destination_contact').value = destinationInfo.contact || '';
            }
            if (document.getElementById('destination_department')) {
                document.getElementById('destination_department').value = destinationInfo.department || '';
            }
            if (document.getElementById('destination_manager')) {
                document.getElementById('destination_manager').value = destinationInfo.manager || '';
            }
            if (document.getElementById('destination_dong')) {
                document.getElementById('destination_dong').value = destinationInfo.dong || '';
            }
            if (document.getElementById('destination_address')) {
                document.getElementById('destination_address').value = destinationInfo.address || '';
            }
            if (document.getElementById('destination_detail')) {
                document.getElementById('destination_detail').value = destinationInfo.detail || '';
            }
            if (document.getElementById('destination_country') && destinationInfo.country) {
                document.getElementById('destination_country').value = destinationInfo.country || '';
            }
        }
    }
    
    // 팝업 열기 함수
    function select_pop(url, name, w, h) {
        if (window.innerWidth < w) {
            w = window.innerWidth - 20;
        }
        // 팝업 크기 확인용 로그 (개발 후 제거 가능)
        console.log('Opening popup:', name, 'Size:', w + 'x' + h);
        var options = 'top=10, left=10, width=' + w + ', height=' + h + ', status=no, menubar=no, toolbar=no, resizable=yes, scrollbars=yes';
        window.open(url, name, options);
    }
    
    // 전역 함수로 등록 (팝업에서 호출 가능하도록)
    window.select_pop = select_pop;
    
    // 최근접수내역 버튼 클릭 이벤트 (onclick이 없을 때만)
    const recentDepartureBtn = document.getElementById('recent_departure_btn');
    if (recentDepartureBtn && !recentDepartureBtn.onclick) {
        recentDepartureBtn.addEventListener('click', function() {
            loadDepartureInfo();
        });
    }
    
    // 도착지 최근접수내역 버튼 이벤트 설정
    const recentDestinationBtn = document.getElementById('recent_destination_btn');
    const recentDestinationOrdersPopup = document.getElementById('recentDestinationOrdersPopup');
    const recentDestinationOrdersList = document.getElementById('recentDestinationOrdersList');
    let destinationHoverTimeout = null;
    let isDestinationPopupVisible = false;
    
    if (recentDestinationBtn && recentDestinationOrdersPopup && recentDestinationOrdersList) {
        // 데스크톱: hover 이벤트
        recentDestinationBtn.addEventListener('mouseenter', function() {
            clearTimeout(destinationHoverTimeout);
            destinationHoverTimeout = setTimeout(function() {
                if (!isDestinationPopupVisible) {
                    loadRecentDestinationOrders();
                }
            }, 300); // 300ms 후 표시 (출발지와 동일)
        });
        
        // 마우스 이탈 시
        recentDestinationBtn.addEventListener('mouseleave', function() {
            clearTimeout(destinationHoverTimeout);
            destinationHoverTimeout = setTimeout(function() {
                if (!isDestinationPopupVisible) {
                    hideRecentDestinationOrdersPopup();
                }
            }, 200); // 200ms 후 숨김
        });
        
        // 팝오버에 마우스 진입 시 유지
        recentDestinationOrdersPopup.addEventListener('mouseenter', function() {
            clearTimeout(destinationHoverTimeout);
        });
        
        // 팝오버에서 마우스 이탈 시 숨김
        recentDestinationOrdersPopup.addEventListener('mouseleave', function() {
            hideRecentDestinationOrdersPopup();
        });
        
        // 모바일/터치: 클릭 이벤트로 팝오버 표시
        recentDestinationBtn.addEventListener('click', function(e) {
            const existingOnclick = recentDestinationBtn.getAttribute('onclick');
            if (existingOnclick && existingOnclick.includes('select_pop')) {
                if (!isDestinationPopupVisible) {
                    loadRecentDestinationOrders();
                }
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            if (!isDestinationPopupVisible) {
                loadRecentDestinationOrders();
            } else {
                hideRecentDestinationOrdersPopup();
            }
        });
        
        // 모바일: 팝오버 외부 클릭 시 닫기
        document.addEventListener('click', function(e) {
            if (isDestinationPopupVisible && recentDestinationOrdersPopup && !recentDestinationOrdersPopup.contains(e.target) && !recentDestinationBtn.contains(e.target)) {
                hideRecentDestinationOrdersPopup();
            }
        });
    }
    
    // 폼 제출 시 쿠키에 저장
    // 비용은 서버단에서 무조건 거리 기반으로 계산하므로 클라이언트에서 설정할 필요 없음
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            // 요금 필드는 서버에서 계산하므로 0으로 설정 (또는 설정하지 않음)
            const totalAmountInput = document.getElementById('total_amount');
            const addCostInput = document.getElementById('add_cost');
            const discountCostInput = document.getElementById('discount_cost');
            const deliveryCostInput = document.getElementById('delivery_cost');
            
            // 서버에서 거리 기반으로 계산하므로 모두 0으로 설정
            if (totalAmountInput) {
                totalAmountInput.value = '0';
            }
            if (addCostInput) {
                addCostInput.value = '0';
            }
            if (discountCostInput) {
                discountCostInput.value = '0';
            }
            if (deliveryCostInput) {
                deliveryCostInput.value = '0';
            }
            
            saveDepartureInfo();
            saveDestinationInfo();
        });
    }
    
    // 한글 입력 모드 활성화 함수
    function enableKoreanInput(field) {
        if (!field) return;
        
        // lang 속성 확인
        if (!field.hasAttribute('lang')) {
            field.setAttribute('lang', 'ko');
        }
        
        // 포커스 시 한글 입력 모드 활성화 시도
        field.addEventListener('focus', function() {
            // IME 모드 활성화를 위한 시도 (브라우저/OS에 따라 다를 수 있음)
            try {
                // Chrome/Edge에서 한글 입력 모드 활성화
                if (navigator.userAgent.indexOf('Chrome') !== -1 || navigator.userAgent.indexOf('Edg') !== -1) {
                    // inputmode 속성으로 힌트 제공
                    if (field.tagName === 'INPUT' && field.type === 'text') {
                        field.setAttribute('inputmode', 'text');
                    }
                }
            } catch (e) {
                // 에러 무시
            }
        });
    }
    
    // 모든 텍스트 입력 필드에 한글 입력 모드 활성화 적용
    const textInputs = document.querySelectorAll('input[type="text"][lang="ko"], textarea[lang="ko"]');
    textInputs.forEach(enableKoreanInput);
    
    // 동적으로 추가되는 필드도 처리
    if (orderForm) {
        const inputObserver = new MutationObserver(function(mutations) {
            const newInputs = document.querySelectorAll('input[type="text"][lang="ko"], textarea[lang="ko"]');
            newInputs.forEach(function(input) {
                if (!input.dataset.koreanInputEnabled) {
                    enableKoreanInput(input);
                    input.dataset.koreanInputEnabled = 'true';
                }
            });
        });
        
        inputObserver.observe(orderForm, {
            childList: true,
            subtree: true
        });
    }
    
    // 직원검색 버튼 이벤트
    const employeeSearchBtn = document.getElementById('employeeSearchBtn');
    if (employeeSearchBtn) {
        employeeSearchBtn.addEventListener('click', function() {
            const url = '<?= base_url('search-company/employee-search') ?>';
            const width = 850;
            const height = 700;
            const left = (window.screen.width / 2) - (width / 2);
            const top = (window.screen.height / 2) - (height / 2);
            window.open(url, 'employeeSearch', `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`);
        });
    }
    
    // 우리쪽 변수명 필드 변경 시 STN_LOGIS 호환 필드도 자동 업데이트
    const companyNameField = document.getElementById('company_name');
    const contactField = document.getElementById('contact');
    const deptField = document.getElementById('dept');
    const chargeField = document.getElementById('charge');
    
    if (companyNameField) {
        companyNameField.addEventListener('input', function() {
            const cNameField = document.getElementById('c_name');
            if (cNameField) {
                cNameField.value = this.value;
            }
        });
    }
    
    if (contactField) {
        contactField.addEventListener('input', function() {
            const cTelnoField = document.getElementById('c_telno');
            if (cTelnoField) {
                cTelnoField.value = this.value;
            }
        });
    }
    
    if (deptField) {
        deptField.addEventListener('input', function() {
            const cDeptField = document.getElementById('c_dept');
            if (cDeptField) {
                cDeptField.value = this.value;
            }
        });
    }
    
    if (chargeField) {
        chargeField.addEventListener('input', function() {
            const cChargeField = document.getElementById('c_charge');
            if (cChargeField) {
                cChargeField.value = this.value;
            }
        });
    }
    
    // 최근접수내역 버튼 hover 이벤트 - 최근 접수 내역 표시
    const recentOrdersPopup = document.getElementById('recentOrdersPopup');
    const recentOrdersList = document.getElementById('recentOrdersList');
    let hoverTimeout = null;
    let isPopupVisible = false;
    
    // recentDepartureBtn은 위에서 이미 선언되었으므로 재사용
    if (recentDepartureBtn && recentOrdersPopup && recentOrdersList) {
        // 데스크톱: hover 이벤트
        recentDepartureBtn.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            hoverTimeout = setTimeout(function() {
                if (!isPopupVisible) {
                    loadRecentOrders();
                }
            }, 300); // 300ms 후 표시
        });
        
        // 마우스 이탈 시
        recentDepartureBtn.addEventListener('mouseleave', function() {
            clearTimeout(hoverTimeout);
            hoverTimeout = setTimeout(function() {
                if (!isPopupVisible) {
                    hideRecentOrdersPopup();
                }
            }, 200); // 200ms 후 숨김
        });
        
        // 팝오버에 마우스 진입 시 유지
        recentOrdersPopup.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
        });
        
        // 팝오버에서 마우스 이탈 시 숨김
        recentOrdersPopup.addEventListener('mouseleave', function() {
            hideRecentOrdersPopup();
        });
        
        // 모바일/터치: 클릭 이벤트로 팝오버 표시
        recentDepartureBtn.addEventListener('click', function(e) {
            // 기존 onclick이 있으면 실행하지 않음 (select_pop 함수가 있는 경우)
            const existingOnclick = recentDepartureBtn.getAttribute('onclick');
            if (existingOnclick && existingOnclick.includes('select_pop')) {
                // 기존 onclick은 그대로 실행되지만, 팝오버도 함께 표시
                if (!isPopupVisible) {
                    loadRecentOrders();
                }
                return;
            }
            
            // 기존 onclick이 없으면 팝오버만 토글
            e.preventDefault();
            e.stopPropagation();
            if (!isPopupVisible) {
                loadRecentOrders();
            } else {
                hideRecentOrdersPopup();
            }
        });
        
        // 모바일: 팝오버 외부 클릭 시 닫기
        document.addEventListener('click', function(e) {
            if (isPopupVisible && recentOrdersPopup && !recentOrdersPopup.contains(e.target) && !recentDepartureBtn.contains(e.target)) {
                hideRecentOrdersPopup();
            }
        });
        
    }
    
    // 팝오버 위치 조정 함수 (화면 밖으로 나가지 않도록)
    function adjustPopupPosition() {
        if (!recentOrdersPopup || !isPopupVisible) return;
        
        const popup = recentOrdersPopup;
        const rect = popup.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        
        // PC 모드: 오른쪽 끝을 버튼의 오른쪽 끝과 맞춤
        popup.style.right = '0';
        popup.style.left = 'auto';
        
        // 아래로 넘어가면 위로 조정
        if (rect.bottom > viewportHeight) {
            popup.style.top = 'auto';
            popup.style.bottom = '100%';
            popup.style.marginTop = '0';
            popup.style.marginBottom = '0.25rem';
        } else {
            popup.style.top = '100%';
            popup.style.bottom = 'auto';
            popup.style.marginTop = '0.25rem';
            popup.style.marginBottom = '0';
        }
    }
    
    // 최근 주문 목록 로드 (출발지)
    function loadRecentOrders() {
        if (isPopupVisible) {
            return;
        }
        
        fetch('/service/getRecentOrdersForDeparture?type=departure', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                displayRecentDepartureOrders(data.data);
                showRecentOrdersPopup();
            } else {
                recentOrdersList.innerHTML = '<div class="text-xs text-gray-500 px-2 py-1">최근 접수 내역이 없습니다.</div>';
                showRecentOrdersPopup();
            }
        })
        .catch(error => {
            console.error('최근 주문 조회 실패:', error);
            recentOrdersList.innerHTML = '<div class="text-xs text-red-500 px-2 py-1">조회 중 오류가 발생했습니다.</div>';
            showRecentOrdersPopup();
        });
    }
    
    // 최근 주문 목록 표시 (출발지 - 2열 그리드)
    function displayRecentDepartureOrders(orders) {
        // 헤더 설정
        const recentOrdersHeader = document.getElementById('recentOrdersHeader');
        if (recentOrdersHeader) {
            recentOrdersHeader.innerHTML = '<div>접수일</div><div>출발지</div>';
        }
        
        // 데이터 리스트
        let html = '';
        orders.forEach(function(order) {
            const saveDate = order.save_date || '';
            const departureCompany = order.departure_company_name || '';
            
            // 데이터를 JSON으로 인코딩하여 저장
            const orderData = JSON.stringify(order);
            
            html += `
                <div class="recent-order-item grid grid-cols-2 gap-2 py-1.5 hover:bg-gray-50 cursor-pointer rounded" 
                     data-order='${escapeHtml(orderData)}'>
                    <div class="text-xs text-gray-600">${escapeHtml(saveDate)}</div>
                    <div class="text-xs font-medium text-gray-800 truncate" title="${escapeHtml(departureCompany)}">${escapeHtml(departureCompany)}</div>
                </div>
            `;
        });
        
        recentOrdersList.innerHTML = html;
        
        // 클릭 이벤트 추가
        const items = recentOrdersList.querySelectorAll('.recent-order-item');
        items.forEach(function(item) {
            item.addEventListener('click', function() {
                try {
                    const orderData = JSON.parse(this.getAttribute('data-order'));
                    fillDepartureFields(orderData);
                } catch (e) {
                    console.error('주문 데이터 파싱 실패:', e);
                }
                
                // 팝오버 숨김
                hideRecentOrdersPopup();
            });
        });
    }
    
    // 출발지 필드만 자동 입력 함수
    function fillDepartureFields(order) {
        setFieldValue('departure_company_name', order.departure_company_name);
        setFieldValue('departure_contact', order.departure_contact);
        setFieldValue('departure_department', order.departure_department);
        setFieldValue('departure_manager', order.departure_manager);
        setFieldValue('departure_detail', order.departure_detail);
        setFieldValue('departure_address', order.departure_address);
        setFieldValue('departure_dong', order.departure_dong);
        setFieldValue('departure_lat', order.departure_lat);
        setFieldValue('departure_lon', order.departure_lon);
    }
    
    // 최근 주문 목록 로드 (도착지)
    function loadRecentDestinationOrders() {
        if (isDestinationPopupVisible) {
            return;
        }
        
        fetch('/service/getRecentOrdersForDeparture?type=destination', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                displayRecentDestinationOrders(data.data);
                showRecentDestinationOrdersPopup();
            } else {
                recentDestinationOrdersList.innerHTML = '<div class="text-xs text-gray-500 px-2 py-1">최근 접수 내역이 없습니다.</div>';
                showRecentDestinationOrdersPopup();
            }
        })
        .catch(error => {
            console.error('최근 주문 조회 실패:', error);
            recentDestinationOrdersList.innerHTML = '<div class="text-xs text-red-500 px-2 py-1">조회 중 오류가 발생했습니다.</div>';
            showRecentDestinationOrdersPopup();
        });
    }
    
    // 최근 주문 목록 표시 (도착지 - 2열 그리드)
    function displayRecentDestinationOrders(orders) {
        // 헤더 설정
        const recentDestinationOrdersHeader = document.getElementById('recentDestinationOrdersHeader');
        if (recentDestinationOrdersHeader) {
            recentDestinationOrdersHeader.innerHTML = '<div>접수일</div><div>도착지</div>';
        }
        
        // 데이터 리스트
        let html = '';
        orders.forEach(function(order) {
            const saveDate = order.save_date || '';
            const destinationCompany = order.destination_company_name || '';
            
            // 데이터를 JSON으로 인코딩하여 저장
            const orderData = JSON.stringify(order);
            
            html += `
                <div class="recent-order-item grid grid-cols-2 gap-2 py-1.5 hover:bg-gray-50 cursor-pointer rounded" 
                     data-order='${escapeHtml(orderData)}'>
                    <div class="text-xs text-gray-600">${escapeHtml(saveDate)}</div>
                    <div class="text-xs font-medium text-gray-800 truncate" title="${escapeHtml(destinationCompany)}">${escapeHtml(destinationCompany)}</div>
                </div>
            `;
        });
        
        recentDestinationOrdersList.innerHTML = html;
        
        // 클릭 이벤트 추가
        const items = recentDestinationOrdersList.querySelectorAll('.recent-order-item');
        items.forEach(function(item) {
            item.addEventListener('click', function() {
                try {
                    const orderData = JSON.parse(this.getAttribute('data-order'));
                    fillDestinationFields(orderData);
                } catch (e) {
                    console.error('주문 데이터 파싱 실패:', e);
                }
                
                // 팝오버 숨김
                hideRecentDestinationOrdersPopup();
            });
        });
    }
    
    // 도착지 필드만 자동 입력 함수
    function fillDestinationFields(order) {
        setFieldValue('destination_company_name', order.destination_company_name);
        setFieldValue('destination_contact', order.destination_contact);
        setFieldValue('destination_department', order.destination_department);
        setFieldValue('destination_manager', order.destination_manager);
        setFieldValue('destination_detail', order.detail_address || order.destination_detail);
        setFieldValue('destination_address', order.destination_address);
        setFieldValue('destination_dong', order.destination_dong);
        setFieldValue('destination_lon', order.destination_lon);
        setFieldValue('destination_lat', order.destination_lat);
    }
    
    // 도착지 팝오버 표시
    function showRecentDestinationOrdersPopup() {
        if (recentDestinationOrdersPopup) {
            recentDestinationOrdersPopup.classList.remove('hidden');
            // 위치 조정을 먼저 수행 (표시 전에 위치 계산)
            adjustDestinationPopupPosition();
            setTimeout(function() {
                recentDestinationOrdersPopup.classList.remove('opacity-0', 'translate-y-[-10px]');
                recentDestinationOrdersPopup.classList.add('opacity-100', 'translate-y-0');
                // 표시 후 다시 위치 조정 (레이아웃이 완료된 후)
                setTimeout(function() {
                    adjustDestinationPopupPosition();
                }, 50);
            }, 10);
            isDestinationPopupVisible = true;
        }
    }
    
    // 도착지 팝오버 숨김
    function hideRecentDestinationOrdersPopup() {
        if (recentDestinationOrdersPopup) {
            recentDestinationOrdersPopup.classList.remove('opacity-100', 'translate-y-0');
            recentDestinationOrdersPopup.classList.add('opacity-0', 'translate-y-[-10px]');
            setTimeout(function() {
                recentDestinationOrdersPopup.classList.add('hidden');
            }, 300);
            isDestinationPopupVisible = false;
        }
    }
    
    // 도착지 팝오버 위치 조정
    function adjustDestinationPopupPosition() {
        if (!recentDestinationOrdersPopup) return;
        
        const popup = recentDestinationOrdersPopup;
        const button = recentDestinationBtn;
        if (!button) return;
        
        // 팝오버가 hidden 상태일 때도 위치 계산을 위해 임시로 표시
        const wasHidden = popup.classList.contains('hidden');
        if (wasHidden) {
            popup.classList.remove('hidden');
            popup.style.visibility = 'hidden';
        }
        
        const buttonRect = button.getBoundingClientRect();
        const popupRect = popup.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        
        // PC 모드: 오른쪽 끝을 버튼의 오른쪽 끝과 맞춤
        popup.style.right = '0';
        popup.style.left = 'auto';
        
        // 아래로 넘어가면 위로 조정
        if (popupRect.bottom > viewportHeight) {
            popup.style.top = 'auto';
            popup.style.bottom = '100%';
            popup.style.marginTop = '0';
            popup.style.marginBottom = '0.25rem';
        } else {
            popup.style.top = '100%';
            popup.style.bottom = 'auto';
            popup.style.marginTop = '0.25rem';
            popup.style.marginBottom = '0';
        }
        
        // 왼쪽으로 넘어가면 오른쪽 정렬
        const newRect = popup.getBoundingClientRect();
        if (newRect.left < 0) {
            popup.style.left = '0';
            popup.style.right = 'auto';
        }
        
        // 오른쪽으로 넘어가면 왼쪽 정렬
        if (newRect.right > viewportWidth) {
            popup.style.right = '0';
            popup.style.left = 'auto';
        }
        
        if (wasHidden) {
            popup.style.visibility = '';
        }
    }
    
    // 필드 값 설정 헬퍼 함수
    function setFieldValue(fieldId, value) {
        if (value) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = value;
            }
        }
    }
    
    // 팝오버 표시
    function showRecentOrdersPopup() {
        if (recentOrdersPopup) {
            recentOrdersPopup.classList.remove('hidden');
            // 애니메이션을 위해 약간의 지연 후 opacity와 transform 적용
            setTimeout(function() {
                recentOrdersPopup.classList.remove('opacity-0', 'translate-y-[-10px]');
                recentOrdersPopup.classList.add('opacity-100', 'translate-y-0');
                // 위치 조정
                adjustPopupPosition();
            }, 10);
            isPopupVisible = true;
        }
    }
    
    // 팝오버 숨김
    function hideRecentOrdersPopup() {
        if (recentOrdersPopup) {
            // 애니메이션 효과 적용
            recentOrdersPopup.classList.remove('opacity-100', 'translate-y-0');
            recentOrdersPopup.classList.add('opacity-0', 'translate-y-[-10px]');
            // 애니메이션 완료 후 hidden 처리
            setTimeout(function() {
                recentOrdersPopup.classList.add('hidden');
            }, 300); // transition duration과 동일하게
            isPopupVisible = false;
        }
    }
    
    // HTML 이스케이프 함수
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<!-- 다음 주소 검색 API 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>

<!-- Validation 에러 메시지 레이어팝업 -->
<?= $this->include('forms/validation-error-modal') ?>