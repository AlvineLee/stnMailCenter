<?php
// 공통 폼 컴포넌트 (Tailwind CSS + 1줄 레이아웃 + 컴팩트 스타일)
// 주문자 정보, 출발지 정보, 도착지 정보, 물품정보, 접수내용
?>

<!-- 주문자 정보 -->
<section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
    <h2 class="text-base font-semibold text-gray-800 mb-3 pb-1 border-b border-blue-200">주문자 정보</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="space-y-1">
            <label for="companyName" class="block text-xs font-medium text-gray-700">업체명 *</label>
            <input type="text" id="companyName" name="companyName" value="은하코퍼레이션" required
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
        </div>
        <div class="space-y-1">
            <label for="contact" class="block text-xs font-medium text-gray-700">연락처 *</label>
            <input type="tel" id="contact" name="contact" required
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
        </div>
        <div class="space-y-1">
            <label for="address" class="block text-xs font-medium text-gray-700">주소</label>
            <input type="text" id="address" name="address" value="서울 강남구 논현동 은하빌딩 15층 C호"
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
        </div>
    </div>
</section>

<!-- 출발지 정보 -->
<section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
    <h2 class="text-base font-semibold text-gray-800 mb-3 pb-1 border-b border-blue-200">출발지 정보</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="space-y-1">
            <label for="departureAddress" class="block text-xs font-medium text-gray-700">출발지 주소 *</label>
            <input type="text" id="departureAddress" name="departureAddress" placeholder="출발지 주소를 입력하세요" required
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
        </div>
        <div class="space-y-1">
            <label for="departureDetail" class="block text-xs font-medium text-gray-700">상세주소</label>
            <input type="text" id="departureDetail" name="departureDetail" placeholder="상세주소를 입력하세요"
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
        </div>
        <div class="space-y-1">
            <label for="departureContact" class="block text-xs font-medium text-gray-700">연락처</label>
            <input type="tel" id="departureContact" name="departureContact" placeholder="출발지 연락처"
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
        </div>
    </div>
</section>

<!-- 도착지 정보 -->
<section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
    <h2 class="text-base font-semibold text-gray-800 mb-3 pb-1 border-b border-blue-200">도착지 정보</h2>
    
    <!-- 라디오 버튼 그룹 -->
    <div class="flex space-x-4 mb-3">
        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="radio" name="destinationType" value="company" checked class="text-blue-600 focus:ring-blue-500">
            <span class="text-xs font-medium text-gray-700">상호선택</span>
        </label>
        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="radio" name="destinationType" value="direct" class="text-blue-600 focus:ring-blue-500">
            <span class="text-xs font-medium text-gray-700">직접입력</span>
        </label>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="space-y-1">
            <label for="mailroom" class="block text-xs font-medium text-gray-700">메일룸</label>
            <select id="mailroom" name="mailroom" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
                <option value="">메일룸 선택</option>
                <option value="mailroom1">메일룸 1</option>
                <option value="mailroom2">메일룸 2</option>
            </select>
        </div>
        <div class="space-y-1">
            <label for="destinationAddress" class="block text-xs font-medium text-gray-700">주소 *</label>
            <input type="text" id="destinationAddress" name="destinationAddress" placeholder="직접 주소 입력" disabled
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-gray-100">
        </div>
        <div class="space-y-1">
            <label for="detailAddress" class="block text-xs font-medium text-gray-700">상세주소 *</label>
            <input type="text" id="detailAddress" name="detailAddress" placeholder="상세주소 입력" disabled
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-gray-100">
        </div>
    </div>
    
    <div class="mt-3">
        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors" id="addCompanyBtn">상호 검색 추가</button>
    </div>
    
    <div class="mt-3">
        <div class="space-y-1">
            <label for="destinationContact" class="block text-xs font-medium text-gray-700">연락처</label>
            <input type="tel" id="destinationContact" name="destinationContact" placeholder="도착지 연락처"
                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
        </div>
    </div>
</section>

<!-- 물품정보 -->
<section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
    <h2 class="text-base font-semibold text-gray-800 mb-3 pb-1 border-b border-blue-200">물품정보</h2>
    <div class="item-container" id="itemContainer">
        <div class="item-row bg-gray-50 rounded p-3 mb-3">
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <div class="space-y-1">
                    <label for="itemType" class="block text-xs font-medium text-gray-700">물품정보 *</label>
                    <select id="itemType" name="itemType[]" required class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
                        <option value="document">서류봉투</option>
                        <option value="package">소포</option>
                        <option value="envelope">편지</option>
                        <option value="other">기타</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="quantity" class="block text-xs font-medium text-gray-700">수량</label>
                    <input type="number" id="quantity" name="quantity[]" value="1" min="1" required
                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
                </div>
                <div class="space-y-1">
                    <label for="unit" class="block text-xs font-medium text-gray-700">단위</label>
                    <select id="unit" name="unit[]" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent bg-white">
                        <option value="개">개</option>
                        <option value="박스">박스</option>
                        <option value="봉지">봉지</option>
                        <option value="장">장</option>
                    </select>
                </div>
            </div>
            <button type="button" class="btn-remove-item mt-2 bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs font-medium transition-colors" onclick="removeItem(this)" style="display: none;">삭제</button>
        </div>
    </div>
    <button type="button" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors" id="addItemBtn">제품추가</button>
</section>

<!-- 접수내용 -->
<section class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
    <h2 class="text-base font-semibold text-gray-800 mb-3 pb-1 border-b border-blue-200">접수내용</h2>
    <div class="space-y-1">
        <p class="text-xs text-gray-600 font-medium">전달내용 * 물품종류와 수량을 입력해주세요.</p>
        <textarea id="deliveryContent" name="deliveryContent" placeholder="전달하실 내용을 입력하세요." required
                  class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent h-16 resize-none bg-white"></textarea>
    </div>
</section>