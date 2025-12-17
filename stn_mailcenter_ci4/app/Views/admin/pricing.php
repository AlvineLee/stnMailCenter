<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    
    <!-- 설명 -->
    <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-semibold text-blue-900 mb-1">요금 설정 안내</h3>
        <ul class="text-xs text-blue-800 space-y-0.5">
            <li>• 거리 구간을 검색하거나 테이블에서 선택하여 요금을 설정하세요.</li>
            <li>• 모든 변경사항은 "저장" 버튼을 클릭하기 전까지는 메모리에만 저장됩니다.</li>
        </ul>
    </div>

    <!-- 구간 검색 및 선택 영역 -->
    <div class="mb-4 bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- 왼쪽: 구간 검색/선택 -->
            <div class="flex-1 lg:w-1/3 bg-white rounded-lg p-4 border border-gray-200">
                <h3 class="text-sm font-semibold mb-3">구간 선택</h3>
                
                <!-- 빠른 검색 -->
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">거리 검색</label>
                    <div class="flex gap-2">
                        <input type="number" 
                               id="search-distance" 
                               class="form-input flex-1" 
                               placeholder="거리(km) 입력"
                               min="0"
                               onkeyup="searchSegmentByDistance(this.value)">
                        <button type="button" 
                                onclick="searchSegmentByDistance(document.getElementById('search-distance').value)"
                                class="px-3 py-1.5 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">
                            검색
                        </button>
                    </div>
                </div>

                <!-- 구간 범위 선택 -->
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">구간 범위 선택</label>
                    <select id="segment-select" 
                            class="form-select w-full"
                            onchange="selectSegmentByIndex(this.value)">
                        <option value="">구간을 선택하세요</option>
                    </select>
                </div>

                <!-- 현재 선택된 구간 정보 -->
                <div id="selected-segment-info" class="p-3 bg-blue-50 rounded border border-blue-200 hidden">
                    <p class="text-xs font-semibold text-blue-900 mb-1">선택된 구간</p>
                    <p class="text-sm font-bold text-blue-700" id="selected-segment-text">-</p>
                </div>

                <!-- 구간 추가 버튼 -->
                <div class="mt-3">
                    <button type="button" 
                            onclick="openAddSegmentModal()" 
                            class="w-full px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm font-medium">
                        + 새 구간 추가
                    </button>
                </div>
            </div>

            <!-- 오른쪽: 요금 설정 패널 -->
            <div id="pricing-panel" class="flex-1 lg:w-2/3 bg-white rounded-lg p-4 border border-gray-200 hidden">
                <h3 class="text-sm font-semibold mb-4">요금 설정</h3>
                <div class="space-y-3">
                    <!-- 트럭 기본 요금 -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">트럭 기본 요금</label>
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   id="truck_base_price" 
                                   class="form-input currency-input flex-1" 
                                   placeholder="0"
                                   oninput="formatNumberInput(this)"
                                   onblur="formatCurrencyInput(this); updateCurrentSegmentData()">
                            <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                        </div>
                    </div>

                    <!-- 오토바이 -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">오토바이</label>
                        <div class="flex gap-2">
                            <select id="bike_calc_type" 
                                    class="form-select"
                                    style="flex: 1 1 50%;"
                                    onchange="formatBikeDamasLaboBlur(document.getElementById('bike_value')); updateCurrentSegmentData()">
                                <option value="fixed">고정금액</option>
                                <option value="percent">비율(%)</option>
                            </select>
                            <div class="flex items-center gap-1" style="flex: 1 1 50%;">
                                <input type="text" 
                                       id="bike_value" 
                                       class="form-input currency-input flex-1" 
                                       placeholder="0"
                                       oninput="formatBikeDamasLaboInput(this)"
                                       onblur="formatBikeDamasLaboBlur(this); updateCurrentSegmentData()">
                                <span id="bike_unit" class="text-xs text-gray-700 whitespace-nowrap">원</span>
                            </div>
                        </div>
                    </div>

                    <!-- 다마스 -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">다마스</label>
                        <div class="flex gap-2">
                            <select id="damas_calc_type" 
                                    class="form-select"
                                    style="flex: 1 1 50%;"
                                    onchange="formatBikeDamasLaboBlur(document.getElementById('damas_value')); updateCurrentSegmentData()">
                                <option value="fixed">고정금액</option>
                                <option value="percent">비율(%)</option>
                            </select>
                            <div class="flex items-center gap-1" style="flex: 1 1 50%;">
                                <input type="text" 
                                       id="damas_value" 
                                       class="form-input currency-input flex-1" 
                                       placeholder="0"
                                       oninput="formatBikeDamasLaboInput(this)"
                                       onblur="formatBikeDamasLaboBlur(this); updateCurrentSegmentData()">
                                <span id="damas_unit" class="text-xs text-gray-700 whitespace-nowrap">원</span>
                            </div>
                        </div>
                    </div>

                    <!-- 라보 -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">라보</label>
                        <div class="flex gap-2">
                            <select id="labo_calc_type" 
                                    class="form-select"
                                    style="flex: 1 1 50%;"
                                    onchange="formatBikeDamasLaboBlur(document.getElementById('labo_value')); updateCurrentSegmentData()">
                                <option value="fixed">고정금액</option>
                                <option value="percent">비율(%)</option>
                            </select>
                            <div class="flex items-center gap-1" style="flex: 1 1 50%;">
                                <input type="text" 
                                       id="labo_value" 
                                       class="form-input currency-input flex-1" 
                                       placeholder="0"
                                       oninput="formatBikeDamasLaboInput(this)"
                                       onblur="formatBikeDamasLaboBlur(this); updateCurrentSegmentData()">
                                <span id="labo_unit" class="text-xs text-gray-700 whitespace-nowrap">원</span>
                            </div>
                        </div>
                    </div>

                    <!-- 트럭 톤수별 요금 -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">트럭 톤수별 요금</label>
                        <button type="button" 
                                onclick="openTruckTonnageModal()" 
                                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">
                            톤수별 설정
                        </button>
                        <div id="tonnage-preview" class="mt-1 text-xs text-gray-600">
                            미설정
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 구간 목록 테이블 -->
    <div class="mb-4 bg-white rounded-lg border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold">전체 구간 목록</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">번호</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">거리 범위</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">트럭 기본</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">오토바이</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">다마스</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">라보</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-700">작업</th>
                    </tr>
                </thead>
                <tbody id="segment-table-body">
                    <!-- 구간 목록이 동적으로 생성됩니다 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 저장 버튼 -->
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
        <button type="button" onclick="savePricing()" class="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600 font-medium">
            저장
        </button>
        <button type="button" onclick="resetPricing()" class="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 font-medium">
            초기화
        </button>
    </div>
</div>

<!-- 구간 추가 모달 -->
<div id="add-segment-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">새 구간 추가</h3>
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">시작 거리 (km)</label>
                <input type="number" 
                       id="new-segment-start" 
                       class="form-input w-full" 
                       min="0" 
                       placeholder="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">끝 거리 (km)</label>
                <input type="number" 
                       id="new-segment-dest" 
                       class="form-input w-full" 
                       min="0" 
                       placeholder="10">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <button type="button" 
                    onclick="closeAddSegmentModal()" 
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                취소
            </button>
            <button type="button" 
                    onclick="addNewSegment()" 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                추가
            </button>
        </div>
    </div>
</div>

<!-- 트럭 톤수별 요금 설정 모달 -->
<div id="truck-tonnage-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="z-index: 10000 !important;" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">트럭 톤수별 요금 설정</h3>
            <button type="button" 
                    onclick="closeTruckTonnageModal()" 
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                ×
            </button>
        </div>
        
        <!-- 차량종류 및 기본 요금 설정 -->
        <div class="mb-4 p-4 bg-gray-50 rounded border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">차량종류 *</label>
                    <select id="truck-type-select" 
                            class="form-select w-full">
                        <option value="">선택</option>
                        <?php 
                        $truck_body_types = \App\Config\TruckOptions::getBodyTypes();
                        foreach ($truck_body_types as $key => $value): 
                        ?>
                        <option value="<?= $key ?>"><?= $value ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">1톤 트럭 기본 요금</label>
                    <div class="flex items-center gap-2">
                        <input type="text" 
                               id="base-1ton-price" 
                               class="form-input currency-input flex-1" 
                               placeholder="기본 요금 입력"
                               oninput="formatNumberInput(this)"
                               onblur="formatCurrencyInput(this)">
                        <span class="text-sm text-gray-700 whitespace-nowrap">원</span>
                    </div>
                </div>
            </div>
            
            <!-- 자동 계산 옵션 -->
            <div class="mt-4 p-3 bg-white rounded border border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-2">자동 계산 옵션</label>
                <div class="flex flex-col sm:flex-row gap-3 items-end">
                    <div class="flex-1 min-w-0">
                        <select id="calc-option" 
                                class="form-select w-full"
                                onchange="toggleCalcInput()">
                            <option value="percent">톤당 n% 증가</option>
                            <option value="fixed">톤당 n원 증가</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   id="calc-value" 
                                   class="form-input currency-input flex-1" 
                                   placeholder="입력"
                                   value="9.5"
                                   oninput="formatCalcValueInput(this)"
                                   onblur="formatCalcValueBlur(this)">
                            <span id="calc-unit" class="text-sm text-gray-700 whitespace-nowrap">%</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button type="button" 
                                onclick="calculateTonnagePrices()" 
                                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm whitespace-nowrap">
                            자동 계산
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-4 p-3 bg-blue-50 rounded border border-blue-200">
            <p class="text-xs text-blue-800">
                각 톤수별 요금을 입력하거나 자동 계산을 사용하세요. 입력하지 않은 톤수는 기본 요금을 사용합니다.
            </p>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">1.4톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-1.4" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">2.5톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-2.5" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">3.5톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-3.5" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">5톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-5" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">8톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-8" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">11톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-11" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">14톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-14" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">15톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-15" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">18톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-18" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
            <div class="tonnage-input-group">
                <label class="block text-xs font-medium text-gray-700 mb-1">25톤</label>
                <div class="flex items-center gap-1">
                    <input type="text" 
                           id="tonnage-25" 
                           class="form-input currency-input flex-1" 
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
            <button type="button" 
                    onclick="closeTruckTonnageModal()" 
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                취소
            </button>
            <button type="button" 
                    onclick="saveTruckTonnage()" 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                저장
            </button>
        </div>
    </div>
</div>

<style>
.segment-table-row {
    cursor: pointer;
    transition: background-color 0.2s;
}

.segment-table-row:hover {
    background-color: #f9fafb;
}

.segment-table-row.active {
    background-color: #dbeafe;
}

.segment-table-row.active td {
    border-color: #3b82f6;
}
</style>

<script>
// 전역 변수 - 모든 데이터는 메모리에 저장
let distanceSegments = []; // 구간 정보
let segmentPricingData = {}; // 구간별 요금 데이터 (메모리)
let truckTonnagesData = {}; // 구간별 톤수 데이터 (메모리)
let maxDistance = 500;
let currentSelectedSegmentIndex = -1; // 현재 선택된 구간 인덱스

// 숫자에 컴마 추가
function formatNumberWithComma(value) {
    if (!value) return '';
    const numStr = String(value).replace(/,/g, '');
    if (isNaN(numStr)) return '';
    return parseInt(numStr).toLocaleString('ko-KR');
}

// 컴마 제거
function removeComma(value) {
    if (!value) return '';
    return String(value).replace(/,/g, '').replace(/원/g, '').trim();
}

// 숫자 입력 필드 포맷팅 (입력 중)
function formatNumberInput(input) {
    const cursorPos = input.selectionStart;
    const oldValue = input.value;
    const numStr = removeComma(oldValue);
    
    if (numStr === '' || numStr === '0') {
        input.value = '';
        return;
    }
    
    if (isNaN(numStr)) {
        input.value = oldValue.substring(0, cursorPos - 1);
        return;
    }
    
    const formatted = formatNumberWithComma(numStr);
    input.value = formatted;
    
    // 커서 위치 복원
    const newCursorPos = formatted.length - (oldValue.length - cursorPos);
    input.setSelectionRange(newCursorPos, newCursorPos);
}

// 금액 입력 필드 blur 처리 (원 표시는 이미 HTML에 있음)
function formatCurrencyInput(input) {
    const numStr = removeComma(input.value);
    if (numStr === '' || numStr === '0') {
        input.value = '';
    } else {
        input.value = formatNumberWithComma(numStr);
    }
}

// 자동 계산 옵션 입력 처리
function formatCalcValueInput(input) {
    const calcOption = document.getElementById('calc-option').value;
    const cursorPos = input.selectionStart;
    const oldValue = input.value;
    
    if (calcOption === 'percent') {
        // 퍼센트: 소수점 허용
        const numStr = String(oldValue).replace(/,/g, '');
        if (numStr === '' || numStr === '0') {
            input.value = '';
            return;
        }
        if (isNaN(numStr)) {
            input.value = oldValue.substring(0, cursorPos - 1);
            return;
        }
        input.value = parseFloat(numStr) || '';
    } else {
        // 원: 정수만, 컴마 추가
        formatNumberInput(input);
    }
}

// 자동 계산 옵션 blur 처리
function formatCalcValueBlur(input) {
    const calcOption = document.getElementById('calc-option').value;
    if (calcOption === 'fixed') {
        formatCurrencyInput(input);
    }
}

// 오토바이/다마스/라보 입력 처리
function formatBikeDamasLaboInput(input) {
    const inputId = input.id;
    const calcTypeId = inputId.replace('_value', '_calc_type');
    const calcType = document.getElementById(calcTypeId).value;
    
    if (calcType === 'percent') {
        // 비율: 소수점 허용, 컴마 없음
        const numStr = String(input.value).replace(/,/g, '').replace(/%/g, '');
        if (numStr === '' || numStr === '0') {
            input.value = '';
            return;
        }
        if (isNaN(numStr)) {
            input.value = input.value.substring(0, input.value.length - 1);
            return;
        }
        input.value = parseFloat(numStr) || '';
    } else {
        // 고정금액: 정수만, 컴마 추가
        formatNumberInput(input);
    }
}

// 오토바이/다마스/라보 blur 처리
function formatBikeDamasLaboBlur(input) {
    const inputId = input.id;
    const calcTypeId = inputId.replace('_value', '_calc_type');
    const unitId = inputId.replace('_value', '_unit');
    const calcType = document.getElementById(calcTypeId).value;
    const unitSpan = document.getElementById(unitId);
    
    if (calcType === 'percent') {
        // 비율: % 표시
        const numStr = removeComma(input.value);
        if (numStr === '' || numStr === '0') {
            input.value = '';
        } else {
            input.value = parseFloat(numStr) || '';
        }
        if (unitSpan) unitSpan.textContent = '%';
    } else {
        // 고정금액: 원 표시, 컴마 추가
        formatCurrencyInput(input);
        if (unitSpan) unitSpan.textContent = '원';
    }
}

// 초기 데이터 로드 (DB에서 한 번만)
document.addEventListener('DOMContentLoaded', function() {
    loadInitialData();
});

// 초기 데이터 로드
function loadInitialData() {
    <?php if (!empty($pay_info_list)): ?>
    const dbData = <?= json_encode($pay_info_list, JSON_UNESCAPED_UNICODE) ?>;
    
    // DB 데이터를 메모리 구조로 변환
    dbData.forEach(function(item) {
        const start = parseInt(item.p_start_km);
        const dest = parseInt(item.p_dest_km);
        const key = `${start}_${dest}`;
        
        // 구간 추가
        if (!distanceSegments.find(s => s.start === start && s.dest === dest)) {
            distanceSegments.push({
                start: start,
                dest: dest
            });
        }
        
        // 요금 데이터 저장 (메모리)
        segmentPricingData[key] = {
            truck_base_price: parseInt(item.p_truck_base_price) || 0,
            bike_calc_type: item.p_bike_calc_type || 'fixed',
            bike_value: parseInt(item.p_bike_value) || 0,
            damas_calc_type: item.p_damas_calc_type || 'fixed',
            damas_value: parseInt(item.p_damas_value) || 0,
            labo_calc_type: item.p_labo_calc_type || 'fixed',
            labo_value: parseInt(item.p_labo_value) || 0
        };
        
        // 톤수 데이터 저장 (메모리)
        if (item.p_truck_tonnages) {
            truckTonnagesData[key] = JSON.parse(item.p_truck_tonnages);
        }
        
        // 최대 거리 업데이트
        if (dest > maxDistance) {
            maxDistance = Math.ceil(dest / 50) * 50;
        }
    });
    
    distanceSegments.sort((a, b) => a.start - b.start);
    
    renderSegmentSelect();
    renderSegmentTable();
    
    // 첫 번째 구간 자동 선택
    if (distanceSegments.length > 0) {
        selectSegment(0);
    }
    <?php else: ?>
    addDistanceSegment(0, 10);
    addDistanceSegment(10, 50);
    addDistanceSegment(50, 100);
    <?php endif; ?>
}

// 거리 구간 추가
function addDistanceSegment(start = null, dest = null) {
    if (start === null) {
        if (distanceSegments.length > 0) {
            const lastSegment = distanceSegments[distanceSegments.length - 1];
            start = lastSegment.dest;
            dest = Math.min(start + 10, maxDistance);
        } else {
            start = 0;
            dest = 10;
        }
    }
    
    distanceSegments.push({
        start: start,
        dest: dest
    });
    
    distanceSegments.sort((a, b) => a.start - b.start);
    
    // 새 구간의 기본 데이터 초기화 (메모리)
    const key = `${start}_${dest}`;
    segmentPricingData[key] = {
        truck_base_price: 0,
        bike_calc_type: 'fixed',
        bike_value: 0,
        damas_calc_type: 'fixed',
        damas_value: 0,
        labo_calc_type: 'fixed',
        labo_value: 0
    };
    truckTonnagesData[key] = {};
    
    renderSegmentSelect();
    renderSegmentTable();
    
    // 새로 추가된 구간 선택
    const newIndex = distanceSegments.findIndex(s => s.start === start && s.dest === dest);
    if (newIndex >= 0) {
        selectSegment(newIndex);
    }
}

// 거리 구간 삭제
function removeDistanceSegment(index) {
    if (confirm('이 구간을 삭제하시겠습니까?')) {
        const segment = distanceSegments[index];
        const key = `${segment.start}_${segment.dest}`;
        
        // 메모리에서 삭제
        delete segmentPricingData[key];
        delete truckTonnagesData[key];
        
        distanceSegments.splice(index, 1);
        renderSegmentSelect();
        renderSegmentTable();
        
        // 현재 선택된 구간이 삭제되면 첫 번째 구간 선택
        if (currentSelectedSegmentIndex === index) {
            if (distanceSegments.length > 0) {
                selectSegment(0);
            } else {
                hidePricingPanel();
            }
        } else if (currentSelectedSegmentIndex > index) {
            currentSelectedSegmentIndex--;
            selectSegment(currentSelectedSegmentIndex);
        }
    }
}

// 구간 선택 드롭다운 렌더링
function renderSegmentSelect() {
    const select = document.getElementById('segment-select');
    select.innerHTML = '<option value="">구간을 선택하세요</option>';
    
    distanceSegments.forEach((segment, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = `구간${index + 1}: ${segment.start} ~ ${segment.dest} km`;
        if (currentSelectedSegmentIndex === index) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

// 구간 테이블 렌더링
function renderSegmentTable() {
    const tbody = document.getElementById('segment-table-body');
    tbody.innerHTML = '';
    
    if (distanceSegments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">구간을 추가해주세요.</td></tr>';
        return;
    }
    
    distanceSegments.forEach((segment, index) => {
        const key = `${segment.start}_${segment.dest}`;
        const pricing = segmentPricingData[key] || {
            truck_base_price: 0,
            bike_calc_type: 'fixed',
            bike_value: 0,
            damas_calc_type: 'fixed',
            damas_value: 0,
            labo_calc_type: 'fixed',
            labo_value: 0
        };
        
        const row = document.createElement('tr');
        row.className = `segment-table-row ${currentSelectedSegmentIndex === index ? 'active' : ''}`;
        row.onclick = () => selectSegment(index);
        
        // 트럭 기본 요금
        const truckBasePrice = pricing.truck_base_price > 0 ? formatNumberWithComma(pricing.truck_base_price) : '';
        // 오토바이
        const bikeValue = pricing.bike_value > 0 ? (pricing.bike_calc_type === 'percent' ? pricing.bike_value : formatNumberWithComma(pricing.bike_value)) : '';
        const bikeUnit = pricing.bike_calc_type === 'percent' ? '%' : '원';
        // 다마스
        const damasValue = pricing.damas_value > 0 ? (pricing.damas_calc_type === 'percent' ? pricing.damas_value : formatNumberWithComma(pricing.damas_value)) : '';
        const damasUnit = pricing.damas_calc_type === 'percent' ? '%' : '원';
        // 라보
        const laboValue = pricing.labo_value > 0 ? (pricing.labo_calc_type === 'percent' ? pricing.labo_value : formatNumberWithComma(pricing.labo_value)) : '';
        const laboUnit = pricing.labo_calc_type === 'percent' ? '%' : '원';
        
        row.innerHTML = `
            <td class="px-4 py-2">${index + 1}</td>
            <td class="px-4 py-2 font-medium">${segment.start} ~ ${segment.dest} km</td>
            <td class="px-4 py-2">
                <div class="flex items-center gap-1">
                    <input type="text" 
                           class="form-input currency-input w-full text-xs" 
                           value="${truckBasePrice}"
                           placeholder="0"
                           oninput="formatNumberInput(this)"
                           onblur="formatCurrencyInput(this)"
                           onclick="event.stopPropagation()"
                           data-segment-index="${index}"
                           data-field="truck_base_price">
                    <span class="text-xs text-gray-700 whitespace-nowrap">원</span>
                </div>
            </td>
            <td class="px-4 py-2">
                <div class="flex items-center gap-1">
                    <input type="text" 
                           class="form-input currency-input w-full text-xs" 
                           value="${bikeValue}"
                           placeholder="0"
                           oninput="formatBikeDamasLaboInput(this)"
                           onblur="formatBikeDamasLaboBlur(this)"
                           onclick="event.stopPropagation()"
                           data-segment-index="${index}"
                           data-field="bike_value"
                           data-calc-type="${pricing.bike_calc_type}">
                    <span class="text-xs text-gray-700 whitespace-nowrap">${bikeUnit}</span>
                </div>
            </td>
            <td class="px-4 py-2">
                <div class="flex items-center gap-1">
                    <input type="text" 
                           class="form-input currency-input w-full text-xs" 
                           value="${damasValue}"
                           placeholder="0"
                           oninput="formatBikeDamasLaboInput(this)"
                           onblur="formatBikeDamasLaboBlur(this)"
                           onclick="event.stopPropagation()"
                           data-segment-index="${index}"
                           data-field="damas_value"
                           data-calc-type="${pricing.damas_calc_type}">
                    <span class="text-xs text-gray-700 whitespace-nowrap">${damasUnit}</span>
                </div>
            </td>
            <td class="px-4 py-2">
                <div class="flex items-center gap-1">
                    <input type="text" 
                           class="form-input currency-input w-full text-xs" 
                           value="${laboValue}"
                           placeholder="0"
                           oninput="formatBikeDamasLaboInput(this)"
                           onblur="formatBikeDamasLaboBlur(this)"
                           onclick="event.stopPropagation()"
                           data-segment-index="${index}"
                           data-field="labo_value"
                           data-calc-type="${pricing.labo_calc_type}">
                    <span class="text-xs text-gray-700 whitespace-nowrap">${laboUnit}</span>
                </div>
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" 
                        onclick="event.stopPropagation(); applySegmentPricing(${index})" 
                        class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">
                    적용
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

// 구간별 요금 적용 (테이블에서 직접 수정한 값 저장)
function applySegmentPricing(index) {
    if (index < 0 || index >= distanceSegments.length) return;
    
    const segment = distanceSegments[index];
    const key = `${segment.start}_${segment.dest}`;
    
    // 테이블의 입력 필드에서 값 가져오기
    const truckBasePriceInput = document.querySelector(`input[data-segment-index="${index}"][data-field="truck_base_price"]`);
    const bikeValueInput = document.querySelector(`input[data-segment-index="${index}"][data-field="bike_value"]`);
    const damasValueInput = document.querySelector(`input[data-segment-index="${index}"][data-field="damas_value"]`);
    const laboValueInput = document.querySelector(`input[data-segment-index="${index}"][data-field="labo_value"]`);
    
    // 기존 calc_type 유지 (테이블에는 표시하지 않지만 메모리에는 저장되어 있음)
    const existingPricing = segmentPricingData[key] || {
        bike_calc_type: 'fixed',
        damas_calc_type: 'fixed',
        labo_calc_type: 'fixed'
    };
    
    // 메모리에 저장 (컴마 제거하여 숫자로 변환)
    segmentPricingData[key] = {
        truck_base_price: parseInt(removeComma(truckBasePriceInput?.value || '')) || 0,
        bike_calc_type: existingPricing.bike_calc_type,
        bike_value: parseFloat(removeComma(bikeValueInput?.value || '')) || 0,
        damas_calc_type: existingPricing.damas_calc_type,
        damas_value: parseFloat(removeComma(damasValueInput?.value || '')) || 0,
        labo_calc_type: existingPricing.labo_calc_type,
        labo_value: parseFloat(removeComma(laboValueInput?.value || '')) || 0
    };
    
    // 테이블 다시 렌더링 (적용된 값으로 업데이트)
    renderSegmentTable();
    
    // 현재 선택된 구간이면 패널도 업데이트
    if (currentSelectedSegmentIndex === index) {
        showPricingPanel(segment);
    }
    
    alert('요금이 적용되었습니다.');
}

// 거리로 구간 검색
function searchSegmentByDistance(distance) {
    const dist = parseInt(distance);
    if (isNaN(dist) || dist < 0) {
        return;
    }
    
    // 해당 거리를 포함하는 구간 찾기
    const index = distanceSegments.findIndex(segment => segment.start <= dist && segment.dest >= dist);
    
    if (index >= 0) {
        selectSegment(index);
        // 드롭다운도 업데이트
        document.getElementById('segment-select').value = index;
    } else {
        alert(`거리 ${dist}km를 포함하는 구간을 찾을 수 없습니다.`);
    }
}

// 인덱스로 구간 선택 (드롭다운에서)
function selectSegmentByIndex(indexStr) {
    const index = parseInt(indexStr);
    if (!isNaN(index) && index >= 0 && index < distanceSegments.length) {
        selectSegment(index);
    }
}

// 구간 선택
function selectSegment(index) {
    if (index < 0 || index >= distanceSegments.length) return;
    
    currentSelectedSegmentIndex = index;
    const segment = distanceSegments[index];
    
    // 선택된 구간 정보 표시
    const infoDiv = document.getElementById('selected-segment-info');
    const infoText = document.getElementById('selected-segment-text');
    infoDiv.classList.remove('hidden');
    infoText.textContent = `구간${index + 1}: ${segment.start} ~ ${segment.dest} km`;
    
    // 드롭다운 업데이트
    document.getElementById('segment-select').value = index;
    
    // 요금 패널 표시 및 데이터 로드 (메모리에서)
    showPricingPanel(segment);
    
    // 테이블 다시 렌더링 (active 상태 업데이트)
    renderSegmentTable();
}

// 요금 패널 표시 (메모리 데이터 사용)
function showPricingPanel(segment) {
    const panel = document.getElementById('pricing-panel');
    panel.classList.remove('hidden');
    
    const key = `${segment.start}_${segment.dest}`;
    const data = segmentPricingData[key] || {
        truck_base_price: 0,
        bike_calc_type: 'fixed',
        bike_value: 0,
        damas_calc_type: 'fixed',
        damas_value: 0,
        labo_calc_type: 'fixed',
        labo_value: 0
    };
    
    // 데이터 입력 (메모리에서) - 컴마 포맷팅하여 표시
    document.getElementById('truck_base_price').value = data.truck_base_price > 0 ? formatNumberWithComma(data.truck_base_price) : '';
    document.getElementById('bike_calc_type').value = data.bike_calc_type;
    document.getElementById('bike_value').value = data.bike_value > 0 ? (data.bike_calc_type === 'percent' ? data.bike_value : formatNumberWithComma(data.bike_value)) : '';
    document.getElementById('damas_calc_type').value = data.damas_calc_type;
    document.getElementById('damas_value').value = data.damas_value > 0 ? (data.damas_calc_type === 'percent' ? data.damas_value : formatNumberWithComma(data.damas_value)) : '';
    document.getElementById('labo_calc_type').value = data.labo_calc_type;
    document.getElementById('labo_value').value = data.labo_value > 0 ? (data.labo_calc_type === 'percent' ? data.labo_value : formatNumberWithComma(data.labo_value)) : '';
    
    // 단위 표시 업데이트
    formatBikeDamasLaboBlur(document.getElementById('bike_value'));
    formatBikeDamasLaboBlur(document.getElementById('damas_value'));
    formatBikeDamasLaboBlur(document.getElementById('labo_value'));
    
    // 톤수 미리보기 (메모리에서)
    const tonnages = truckTonnagesData[key] || {};
    const preview = document.getElementById('tonnage-preview');
    preview.textContent = Object.keys(tonnages).length > 0 ? `${Object.keys(tonnages).length}개 톤수 설정됨` : '미설정';
}

// 요금 패널 숨기기
function hidePricingPanel() {
    document.getElementById('pricing-panel').classList.add('hidden');
    document.getElementById('selected-segment-info').classList.add('hidden');
}

// 현재 구간 데이터 업데이트 (메모리에만 저장)
function updateCurrentSegmentData() {
    if (currentSelectedSegmentIndex < 0) return;
    
    const segment = distanceSegments[currentSelectedSegmentIndex];
    const key = `${segment.start}_${segment.dest}`;
    
    // 메모리에 저장 (DB 통신 없음) - 컴마 제거하여 숫자로 변환
    segmentPricingData[key] = {
        truck_base_price: parseInt(removeComma(document.getElementById('truck_base_price').value)) || 0,
        bike_calc_type: document.getElementById('bike_calc_type').value,
        bike_value: parseFloat(removeComma(document.getElementById('bike_value').value)) || 0,
        damas_calc_type: document.getElementById('damas_calc_type').value,
        damas_value: parseFloat(removeComma(document.getElementById('damas_value').value)) || 0,
        labo_calc_type: document.getElementById('labo_calc_type').value,
        labo_value: parseFloat(removeComma(document.getElementById('labo_value').value)) || 0
    };
    
    // 테이블 업데이트 (변경사항 반영)
    renderSegmentTable();
}

// 구간 추가 모달 열기
function openAddSegmentModal() {
    document.getElementById('add-segment-modal').classList.remove('hidden');
    document.getElementById('new-segment-start').value = '';
    document.getElementById('new-segment-dest').value = '';
}

// 구간 추가 모달 닫기
function closeAddSegmentModal() {
    document.getElementById('add-segment-modal').classList.add('hidden');
}

// 새 구간 추가
function addNewSegment() {
    const start = parseInt(document.getElementById('new-segment-start').value);
    const dest = parseInt(document.getElementById('new-segment-dest').value);
    
    if (isNaN(start) || isNaN(dest) || start < 0 || dest <= start) {
        alert('올바른 거리 범위를 입력해주세요. (시작 < 끝)');
        return;
    }
    
    // 중복 체크
    if (distanceSegments.find(s => (s.start <= start && s.dest > start) || (s.start < dest && s.dest >= dest))) {
        alert('이미 존재하는 구간과 겹칩니다.');
        return;
    }
    
    addDistanceSegment(start, dest);
    closeAddSegmentModal();
}

// 트럭 톤수별 요금 모달 열기
function openTruckTonnageModal() {
    if (currentSelectedSegmentIndex < 0) return;
    
    // 공통 레이어 팝업 기능 사용
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    const segment = distanceSegments[currentSelectedSegmentIndex];
    const key = `${segment.start}_${segment.dest}`;
    const tonnages = truckTonnagesData[key] || {};
    const pricing = segmentPricingData[key] || {};
    
    // 현재 구간의 트럭 기본 요금을 가져와서 1톤 트럭 기본 요금 필드에 설정 (컴마 포맷팅)
    const truckBasePrice = pricing.truck_base_price || 0;
    const basePriceInput = document.getElementById('base-1ton-price');
    if (basePriceInput) {
        basePriceInput.value = truckBasePrice > 0 ? formatNumberWithComma(truckBasePrice) : '';
    }
    
    // 기존 데이터를 입력 필드에 로드 (컴마 포맷팅)
    const tonnageList = ['1.4', '2.5', '3.5', '5', '8', '11', '14', '15', '18', '25'];
    tonnageList.forEach(tonnage => {
        const input = document.getElementById(`tonnage-${tonnage}`);
        if (input) {
            const value = tonnages[tonnage] || '';
            input.value = value > 0 ? formatNumberWithComma(value) : '';
        }
    });
    
    // 선택 필드 초기화
    document.getElementById('truck-type-select').value = '';
    document.getElementById('calc-option').value = 'percent';
    document.getElementById('calc-value').value = '9.5';
    toggleCalcInput();
    
    const modal = document.getElementById('truck-tonnage-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

// 트럭 톤수별 요금 모달 닫기
function closeTruckTonnageModal() {
    const modal = document.getElementById('truck-tonnage-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
    
    // 공통 레이어 팝업 기능 사용
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 계산 옵션에 따른 입력 필드 단위 변경
function toggleCalcInput() {
    const calcOption = document.getElementById('calc-option').value;
    const calcUnit = document.getElementById('calc-unit');
    const calcValue = document.getElementById('calc-value');
    
    if (calcOption === 'percent') {
        calcUnit.textContent = '%';
        // 퍼센트: 소수점 허용, 컴마 없음
        const currentValue = removeComma(calcValue.value);
        if (!currentValue || currentValue === '0') {
            calcValue.value = '9.5';
        } else {
            calcValue.value = parseFloat(currentValue) || '9.5';
        }
    } else {
        calcUnit.textContent = '원';
        // 원: 정수만, 컴마 추가
        const currentValue = removeComma(calcValue.value);
        if (!currentValue || currentValue === '0') {
            calcValue.value = '';
        } else {
            calcValue.value = formatNumberWithComma(parseInt(currentValue));
        }
    }
}

// 톤수별 요금 자동 계산 (옵션에 따라 톤당 n% 증가 또는 톤당 n원 증가)
function calculateTonnagePrices() {
    const basePriceStr = removeComma(document.getElementById('base-1ton-price').value);
    const basePrice = parseFloat(basePriceStr);
    if (!basePrice || basePrice <= 0) {
        alert('1톤 트럭 기본 요금을 입력해주세요.');
        return;
    }
    
    const calcOption = document.getElementById('calc-option').value;
    const calcValueStr = calcOption === 'fixed' ? removeComma(document.getElementById('calc-value').value) : document.getElementById('calc-value').value;
    const calcValue = parseFloat(calcValueStr);
    
    if (!calcValue || calcValue <= 0) {
        alert('증가율 또는 증가액을 입력해주세요.');
        return;
    }
    
    // 톤수 순서 (1톤 기준으로 계산)
    const tonnageOrder = [
        { key: '1.4', value: 1.4 },
        { key: '2.5', value: 2.5 },
        { key: '3.5', value: 3.5 },
        { key: '5', value: 5 },
        { key: '8', value: 8 },
        { key: '11', value: 11 },
        { key: '14', value: 14 },
        { key: '15', value: 15 },
        { key: '18', value: 18 },
        { key: '25', value: 25 }
    ];
    
    // 1톤 기준으로 각 톤수별 요금 계산
    tonnageOrder.forEach(item => {
        const tonnageDiff = item.value - 1; // 1톤 대비 차이
        let calculatedPrice;
        
        if (calcOption === 'percent') {
            // 톤당 n% 증가
            const increaseRate = 1 + (tonnageDiff * (calcValue / 100));
            calculatedPrice = Math.round(basePrice * increaseRate);
        } else {
            // 톤당 n원 증가
            calculatedPrice = Math.round(basePrice + (tonnageDiff * calcValue));
        }
        
        const input = document.getElementById(`tonnage-${item.key}`);
        if (input) {
            input.value = calculatedPrice > 0 ? formatNumberWithComma(calculatedPrice) : '';
        }
    });
}

// 트럭 톤수별 요금 저장
function saveTruckTonnage() {
    if (currentSelectedSegmentIndex < 0) return;
    
    const segment = distanceSegments[currentSelectedSegmentIndex];
    const key = `${segment.start}_${segment.dest}`;
    const tonnages = {};
    
    // 입력 필드에서 데이터 수집 (컴마 제거하여 숫자로 변환)
    const tonnageList = ['1.4', '2.5', '3.5', '5', '8', '11', '14', '15', '18', '25'];
    tonnageList.forEach(tonnage => {
        const input = document.getElementById(`tonnage-${tonnage}`);
        if (input && input.value) {
            const value = parseInt(removeComma(input.value));
            if (value > 0) {
                tonnages[tonnage] = value;
            }
        }
    });
    
    // 메모리에 저장 (DB 통신 없음)
    truckTonnagesData[key] = tonnages;
    
    // 미리보기 업데이트
    const preview = document.getElementById('tonnage-preview');
    const count = Object.keys(tonnages).length;
    preview.textContent = count > 0 ? `${count}개 톤수 설정됨` : '미설정';
    
    closeTruckTonnageModal();
}

// 요금 설정 저장 (저장 버튼 클릭 시에만 DB 통신)
function savePricing() {
    // 현재 패널의 데이터도 메모리에 저장
    if (currentSelectedSegmentIndex >= 0) {
        updateCurrentSegmentData();
    }
    
    // 메모리 데이터를 서버로 전송할 형식으로 변환
    const segments = [];
    
    distanceSegments.forEach((segment) => {
        const key = `${segment.start}_${segment.dest}`;
        const pricing = segmentPricingData[key] || {
            truck_base_price: 0,
            bike_calc_type: 'fixed',
            bike_value: 0,
            damas_calc_type: 'fixed',
            damas_value: 0,
            labo_calc_type: 'fixed',
            labo_value: 0
        };
        const tonnages = truckTonnagesData[key] || {};
        
        segments.push({
            start_km: segment.start,
            dest_km: segment.dest,
            truck_base_price: pricing.truck_base_price,
            bike_calc_type: pricing.bike_calc_type,
            bike_value: pricing.bike_value,
            damas_calc_type: pricing.damas_calc_type,
            damas_value: pricing.damas_value,
            labo_calc_type: pricing.labo_calc_type,
            labo_value: pricing.labo_value,
            truck_tonnages: tonnages
        });
    });
    
    // 저장 버튼 클릭 시에만 DB 통신
    fetch('<?= base_url('admin/savePricing') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            comp_gbn: 'K',
            segments: segments
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('요금 설정이 저장되었습니다.');
            location.reload();
        } else {
            alert('저장에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        alert('저장 중 오류가 발생했습니다.');
    });
}

// 초기화
function resetPricing() {
    if (confirm('모든 설정을 초기화하시겠습니까?')) {
        distanceSegments = [];
        segmentPricingData = {};
        truckTonnagesData = {};
        currentSelectedSegmentIndex = -1;
        hidePricingPanel();
        loadInitialData();
    }
}
</script>

<?= $this->endSection() ?>
