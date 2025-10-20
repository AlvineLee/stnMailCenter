<?php
// 공통 폼 컴포넌트 - 주문자정보, 출발지, 도착지만 포함
?>

<!-- 주문자 정보 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">주문자 정보</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="space-y-1">
                <input type="text" id="company_name" name="company_name" value="<?= old('company_name', session()->get('customer_name', '')) ?>" required
                       placeholder="업체명"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="tel" id="contact" name="contact" value="<?= old('contact', session()->get('phone', '')) ?>" required
                       placeholder="연락처"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
            <div class="space-y-1">
                <input type="text" id="address" name="address" value="<?= old('address', '') ?>"
                       placeholder="주소"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            </div>
        </div>
    </section>
</div>

<!-- 출발지 정보 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">출발지 정보</h2>
        <div class="space-y-2">
            <input type="text" id="departure_address" name="departure_address" value="<?= old('departure_address') ?>" placeholder="출발지 주소 *" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <input type="text" id="departure_detail" name="departure_detail" value="<?= old('departure_detail') ?>" placeholder="상세주소"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <input type="tel" id="departure_contact" name="departure_contact" value="<?= old('departure_contact') ?>" placeholder="출발지 연락처"
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
        <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">도착지 정보</h2>
        
        <!-- 라디오 버튼 그룹 -->
        <div class="flex space-x-4 mb-3">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="destination_type" value="mailroom" <?= old('destination_type', 'mailroom') === 'mailroom' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-xs font-medium text-gray-600">메일룸배송</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="destination_type" value="direct" <?= old('destination_type') === 'direct' ? 'checked' : '' ?> class="text-gray-600 focus:ring-gray-500">
                <span class="text-xs font-medium text-gray-600">직접배송</span>
            </label>
        </div>
        
        <div class="space-y-2">
            <input type="text" id="destination_address" name="destination_address" value="<?= old('destination_address') ?>" placeholder="도착지 주소"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <input type="text" id="detail_address" name="detail_address" value="<?= old('detail_address') ?>" placeholder="상세주소"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
            <input type="tel" id="destination_contact" name="destination_contact" value="<?= old('destination_contact') ?>" placeholder="도착지 연락처"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent bg-white">
        </div>
    </section>
</div>

<script>
// 도착지 타입 변경 시 필드 활성화/비활성화
document.querySelectorAll('input[name="destination_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const destinationAddress = document.getElementById('destination_address');
        const detailAddress = document.getElementById('detail_address');

        if (this.value === 'direct') {
            destinationAddress.disabled = false;
            destinationAddress.classList.remove('bg-gray-100');
            destinationAddress.classList.add('bg-white');
            detailAddress.disabled = false;
            detailAddress.classList.remove('bg-gray-100');
            detailAddress.classList.add('bg-white');
        } else {
            destinationAddress.disabled = true;
            destinationAddress.classList.add('bg-gray-100');
            destinationAddress.classList.remove('bg-white');
            detailAddress.disabled = true;
            detailAddress.classList.add('bg-gray-100');
            detailAddress.classList.remove('bg-white');
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
});
</script>