<!-- 일양 배송정보 상세 팝업 모달 -->
<div id="ilyangDetailModal" class="fixed inset-0 hidden flex items-center justify-center p-4" style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 9999;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">📦 일양 배송정보 상세</h3>
            <button type="button" onclick="closeIlyangDetail()" class="text-gray-500 hover:text-gray-700 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div id="ilyang-detail-content">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                    <p class="mt-4 text-gray-600">배송정보를 조회하는 중입니다...</p>
                </div>
            </div>
        </div>
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex justify-end gap-2">
            <button class="form-button form-button-secondary" onclick="closeIlyangDetail()">닫기</button>
        </div>
    </div>
</div>
