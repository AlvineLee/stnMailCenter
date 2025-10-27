<?php
// 멀티오더 생성 등록 레이어 팝업 공통 컴포넌트
// 사용법: <?= $this->include('forms/multi-order-modal', ['service_name' => '방문택배']) ?>

$service_name = $service_name ?? '서비스';
?>

<!-- 멀티오더 생성 등록 레이어 팝업 -->
<div id="multiOrderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div class="p-6">
            <!-- 헤더 -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-800">멀티오더 생성 등록 (<?= $service_name ?>)</h3>
                <button type="button" id="closeMultiOrderModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- 엑셀 파일 업로드 섹션 -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">엑셀 파일(.xlsx)</label>
                    <div class="flex items-center space-x-3">
                        <button type="button" id="selectFileBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                            파일 선택
                        </button>
                        <input type="file" id="excelFile" accept=".xlsx" class="hidden">
                        <div class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-500">
                            <span id="fileName">선택된 파일 없음</span>
                        </div>
                    </div>
                </div>
                
                <!-- 액션 버튼들 -->
                <div class="flex space-x-3">
                    <button type="button" id="uploadPreviewBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                        업로드 및 미리보기
                    </button>
                    <button type="button" id="downloadExampleBtn" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-2 rounded text-sm font-medium transition-colors">
                        예제파일 다운로드
                    </button>
                </div>
                
                <!-- 현재 주문자 정보 -->
                <div class="bg-gray-50 rounded-md p-3">
                    <p class="text-sm text-gray-700">
                        현재 주문자(로그인 기준): 병원/회사명 = 엘지화학, 연락처 = 01010002000
                    </p>
                </div>
                
                <!-- 미리보기 테이블 (업로드 후 표시) -->
                <div id="previewSection" class="hidden">
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700">업로드된 데이터 미리보기</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-700">#</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-700">사용</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-700">출발지</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-700">도착지</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-700">출발좌표</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-700">도착좌표</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody" class="divide-y divide-gray-200">
                                    <!-- 동적으로 생성될 데이터 -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- 원시 필드 전체 (접을 수 있는 섹션) -->
                    <div class="mt-4">
                        <button type="button" id="toggleRawFields" class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                            <span id="rawFieldsIcon">▶</span>
                            <span class="ml-1">원시 필드 전체</span>
                        </button>
                        <div id="rawFieldsContent" class="hidden mt-2 bg-gray-50 rounded-md p-3">
                            <pre id="rawFieldsData" class="text-xs text-gray-600 whitespace-pre-wrap"></pre>
                        </div>
                    </div>
                    
                    <!-- 하단 액션 버튼들 -->
                    <div class="flex space-x-3 mt-6">
                        <button type="button" id="bulkRegisterBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                            이 내용으로 <span id="itemCount">0</span>건 일괄 등록
                        </button>
                        <button type="button" id="reuploadBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                            다시 업로드
                        </button>
                        <button type="button" id="goToOrderBtn" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-2 rounded text-sm font-medium transition-colors">
                            주문 페이지로
                        </button>
                    </div>
                </div>
                
                <!-- 안내 메시지 (업로드 전에만 표시) -->
                <div id="infoMessage" class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <p class="text-sm text-blue-800">
                        엑셀을 업로드하면 여기에서 미리보기와 좌표 보정 결과를 확인할 수 있습니다.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 멀티오더 생성 등록 모달 관련 JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const multiOrderBtn = document.getElementById('multiOrderBtn');
    const multiOrderModal = document.getElementById('multiOrderModal');
    const closeMultiOrderModal = document.getElementById('closeMultiOrderModal');
    const selectFileBtn = document.getElementById('selectFileBtn');
    const excelFile = document.getElementById('excelFile');
    const fileName = document.getElementById('fileName');
    const uploadPreviewBtn = document.getElementById('uploadPreviewBtn');
    const downloadExampleBtn = document.getElementById('downloadExampleBtn');
    const toggleRawFields = document.getElementById('toggleRawFields');
    const rawFieldsContent = document.getElementById('rawFieldsContent');
    const rawFieldsIcon = document.getElementById('rawFieldsIcon');
    const bulkRegisterBtn = document.getElementById('bulkRegisterBtn');
    const reuploadBtn = document.getElementById('reuploadBtn');
    const goToOrderBtn = document.getElementById('goToOrderBtn');
    
    // 멀티오더 버튼 클릭 시 모달 열기
    if (multiOrderBtn) {
        multiOrderBtn.addEventListener('click', function() {
            multiOrderModal.classList.remove('hidden');
        });
    }
    
    // 모달 닫기
    closeMultiOrderModal.addEventListener('click', function() {
        multiOrderModal.classList.add('hidden');
    });
    
    // 모달 배경 클릭 시 닫기
    multiOrderModal.addEventListener('click', function(e) {
        if (e.target === multiOrderModal) {
            multiOrderModal.classList.add('hidden');
        }
    });
    
    // 파일 선택 버튼 클릭
    selectFileBtn.addEventListener('click', function() {
        excelFile.click();
    });
    
    // 파일 선택 시 파일명 표시
    excelFile.addEventListener('change', function() {
        if (this.files.length > 0) {
            fileName.textContent = this.files[0].name;
        } else {
            fileName.textContent = '선택된 파일 없음';
        }
    });
    
    // 업로드 및 미리보기 버튼
    uploadPreviewBtn.addEventListener('click', function() {
        if (excelFile.files.length === 0) {
            alert('파일을 선택해주세요.');
            return;
        }
        
        // 파일 업로드 및 파싱 시뮬레이션
        const file = excelFile.files[0];
        console.log('업로드된 파일:', file.name);
        
        // 샘플 데이터 (실제로는 서버에서 엑셀 파싱 결과를 받아옴)
        const sampleData = [
            {
                id: 1,
                use: true,
                departure: {
                    name: '아모레퍼시픽',
                    phone: '01010001000',
                    address: '서울 용산구 한강로2가 424'
                },
                destination: {
                    name: '출발담',
                    phone: '01040004000',
                    address: '서울 서초구 잠원로 60, 101동 1001호'
                },
                departureCoords: '-',
                destinationCoords: '-'
            },
            {
                id: 2,
                use: true,
                departure: {
                    name: '아모레퍼시픽2',
                    phone: '01010002000',
                    address: '서울 서초구 잠원로 60'
                },
                destination: {
                    name: '출발담',
                    phone: '01040004000',
                    address: '논현동 서울 강남구 논현로 123'
                },
                departureCoords: '-',
                destinationCoords: '-'
            }
        ];
        
        // 미리보기 테이블 생성
        displayPreviewData(sampleData);
        
        // 안내 메시지 숨기고 미리보기 섹션 표시
        document.getElementById('infoMessage').classList.add('hidden');
        document.getElementById('previewSection').classList.remove('hidden');
    });
    
    // 미리보기 데이터 표시 함수
    function displayPreviewData(data) {
        const tbody = document.getElementById('previewTableBody');
        const itemCount = document.getElementById('itemCount');
        
        tbody.innerHTML = '';
        itemCount.textContent = data.length;
        
        data.forEach((item, index) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            
            row.innerHTML = `
                <td class="px-3 py-2 text-gray-600">${item.id}</td>
                <td class="px-3 py-2">
                    <input type="checkbox" ${item.use ? 'checked' : ''} class="text-blue-600 focus:ring-blue-500">
                </td>
                <td class="px-3 py-2">
                    <div class="text-sm">
                        <div class="font-medium text-gray-900">${item.departure.name} (${item.departure.phone})</div>
                        <div class="text-gray-500">full: ${item.departure.address}</div>
                    </div>
                </td>
                <td class="px-3 py-2">
                    <div class="text-sm">
                        <div class="font-medium text-gray-900">${item.destination.name} (${item.destination.phone})</div>
                        <div class="text-gray-500">full: ${item.destination.address}</div>
                    </div>
                </td>
                <td class="px-3 py-2 text-gray-500">${item.departureCoords}</td>
                <td class="px-3 py-2 text-gray-500">${item.destinationCoords}</td>
            `;
            
            tbody.appendChild(row);
        });
        
        // 원시 필드 데이터 설정
        const rawFieldsData = document.getElementById('rawFieldsData');
        rawFieldsData.textContent = JSON.stringify(data, null, 2);
    }
    
    // 원시 필드 토글
    toggleRawFields.addEventListener('click', function() {
        if (rawFieldsContent.classList.contains('hidden')) {
            rawFieldsContent.classList.remove('hidden');
            rawFieldsIcon.textContent = '▼';
        } else {
            rawFieldsContent.classList.add('hidden');
            rawFieldsIcon.textContent = '▶';
        }
    });
    
    // 일괄 등록 버튼
    bulkRegisterBtn.addEventListener('click', function() {
        const checkedItems = document.querySelectorAll('#previewTableBody input[type="checkbox"]:checked');
        if (checkedItems.length === 0) {
            alert('등록할 항목을 선택해주세요.');
            return;
        }
        alert(`${checkedItems.length}건의 <?= $service_name ?> 주문이 등록되었습니다.`);
        multiOrderModal.classList.add('hidden');
    });
    
    // 다시 업로드 버튼
    reuploadBtn.addEventListener('click', function() {
        excelFile.value = '';
        fileName.textContent = '선택된 파일 없음';
        document.getElementById('previewSection').classList.add('hidden');
        document.getElementById('infoMessage').classList.remove('hidden');
    });
    
    // 주문 페이지로 버튼
    goToOrderBtn.addEventListener('click', function() {
        multiOrderModal.classList.add('hidden');
        // 실제로는 주문 페이지로 이동
        alert('주문 페이지로 이동합니다.');
    });
    
    // 예제파일 다운로드 버튼
    downloadExampleBtn.addEventListener('click', function() {
        alert('예제파일 다운로드 기능을 구현합니다.');
    });
});
</script>
