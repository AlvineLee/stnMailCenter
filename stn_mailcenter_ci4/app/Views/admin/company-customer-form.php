<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="w-full max-w-full flex flex-col md:flex-row gap-4 box-border">
    <!-- 왼쪽 패널: 기본정보 -->
    <div class="flex-1 w-full min-w-0">
        <div class="mb-1">
            <section class="bg-blue-50 rounded-lg shadow-sm border-2 border-blue-300 p-3">
                <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">기본정보</h2>
                
                <?= form_open('/admin/company-customer-save', ['name' => 'member_form', 'method' => 'post']) ?>
                <input type="hidden" name="mode" value="<?= ($mode === 'edit' ? 'editok' : 'add') ?>">
                <input type="hidden" name="comp_code" value="<?= esc($comp_code) ?>">
                <?php if (!empty($search_keyword)): ?>
                <input type="hidden" name="search_keyword" value="<?= esc($search_keyword) ?>">
                <?php endif; ?>
                <?php if ($mode === 'edit' && !empty($customer_info)): ?>
                <input type="hidden" name="user_idx" value="<?= esc($customer_info['user_id'] ?? '') ?>">
                <input type="hidden" name="user_ccode" value="<?= esc($customer_info['user_ccode'] ?? '') ?>">
                <?php endif; ?>
                <input type="hidden" name="sido" id="sido" value="<?= esc($customer_info['sido'] ?? '') ?>">
                <input type="hidden" name="gungu" id="gungu" value="<?= esc($customer_info['gungu'] ?? '') ?>">
                <input type="hidden" name="dong2" id="dong2">
                <input type="hidden" name="fulladdr" id="fulladdr" value="<?= esc($customer_info['user_addr1'] ?? '') ?>">
                
                <div class="space-y-3">
                    <div class="form-field">
                        <label class="form-label <?= ($mode === 'edit') ? '' : 'required' ?>">아이디</label>
                        <input type="text" 
                               id="user_id" 
                               name="user_id" 
                               value="<?= esc($customer_info['user_id'] ?? '') ?>" 
                               <?= ($mode === 'edit') ? 'readonly' : 'required' ?>
                               class="form-input <?= ($mode === 'edit') ? 'bg-gray-50 text-gray-600' : '' ?>">
                    </div>
                    <div class="form-field">
                        <label class="form-label">사용자등급</label>
                        <select id="user_class" name="user_class" class="form-input">
                            <?php if (!empty($active_classes)): ?>
                                <?php foreach ($active_classes as $class): ?>
                                    <option value="<?= esc($class['class_id']) ?>"
                                            <?= (($customer_info['user_class'] ?? '5') == $class['class_id']) ? 'selected' : '' ?>>
                                        <?= esc($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="5">일반 사용자</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">소속부서</label>
                        <div class="flex gap-3 items-end">
                            <input type="text" 
                                   id="user_dept" 
                                   name="user_dept" 
                                   value="<?= esc($customer_info['user_dept'] ?? '') ?>" 
                                   class="form-input flex-1">
                            <button type="button" 
                                    id="settlement-depts-btn" 
                                    onclick="openSettlementDeptsPopup()"
                                    class="flex-1 bg-white border border-gray-300 rounded text-left text-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    style="display: none; padding: 6px 8px;">
                                <span id="settlement-depts-display">정산관리부서 (복수선택가능)</span>
                            </button>
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="form-label required">담당자</label>
                        <input type="text" 
                               id="user_name" 
                               name="user_name" 
                               value="<?= esc($customer_info['user_name'] ?? '') ?>" 
                               required
                               class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">메모</label>
                        <input type="text" 
                               id="user_memo" 
                               name="user_memo" 
                               value="<?= esc($customer_info['user_memo'] ?? '') ?>" 
                               class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label required">전화번호1</label>
                        <input type="text" 
                               id="user_tel1" 
                               name="user_tel1" 
                               value="<?= esc($customer_info['user_tel1'] ?? '') ?>" 
                               required
                               class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">전화번호2</label>
                        <input type="text" 
                               id="user_tel2" 
                               name="user_tel2" 
                               value="<?= esc($customer_info['user_tel2'] ?? '') ?>" 
                               class="form-input">
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- 가운데 패널: 비밀번호 및 주소정보 -->
    <div class="flex-1 w-full min-w-0">
        <div class="mb-1">
            <section class="bg-gray-50 rounded-lg shadow-sm border-2 border-gray-300 p-3">
                <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">비밀번호 및 주소정보</h2>
                <div class="space-y-3">
                    <div class="form-field">
                        <label class="form-label <?= ($mode === 'edit') ? '' : 'required' ?>">비밀번호</label>
                        <input type="password" 
                               id="user_pass" 
                               name="user_pass" 
                               placeholder="비밀번호를 입력하세요" 
                               <?= ($mode === 'edit') ? '' : 'required' ?>
                               class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label <?= ($mode === 'edit') ? '' : 'required' ?>">비밀번호 확인</label>
                        <input type="password" 
                               id="user_pass2" 
                               name="user_pass2" 
                               placeholder="비밀번호를 한번더 입력하세요" 
                               <?= ($mode === 'edit') ? '' : 'required' ?>
                               class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label required">주소</label>
                        <div class="flex space-x-2 mb-2">
                            <input type="text" 
                                   id="user_dong" 
                                   name="user_dong" 
                                   value="<?= esc($customer_info['user_dong'] ?? '') ?>" 
                                   placeholder="동명"
                                   class="form-input flex-1">
                            <button type="button" 
                                    onclick="execDaumPostcode()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors whitespace-nowrap">
                                주소검색
                            </button>
                        </div>
                        <input type="text" 
                               id="user_addr1" 
                               name="user_addr1" 
                               value="<?= esc($customer_info['user_addr1'] ?? '') ?>" 
                               placeholder="기본주소"
                               required
                               class="form-input mb-2">
                        <textarea id="user_addr2" 
                                  name="user_addr2" 
                                  rows="2" 
                                  placeholder="상세주소를 입력하세요" 
                                  class="form-input resize-none"><?= esc($customer_info['user_addr2'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- 오른쪽 패널: 저장/취소 버튼 -->
    <div class="w-full md:w-64 flex-shrink-0 max-w-full box-border">
        <div class="sticky top-4">
            <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border-2 border-gray-300 p-4 box-border">
                <button type="button" 
                        onclick="addsubmit()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap">
                    ✓ <?= ($mode === 'edit' ? '수정하기' : '등록하기') ?>
                </button>
                <button type="button" 
                        onclick="location.href='<?= base_url('admin/company-customer-list?comp_code=' . urlencode($comp_code)) ?>'" 
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap">
                    취소
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<!-- 정산관리부서 선택 레이어 팝업 -->
<div id="settlementDeptsPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4" style="z-index: 9999 !important; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md max-h-[85vh] flex flex-col" style="z-index: 10000 !important;">
        <!-- 헤더 -->
        <div class="sticky top-0 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 p-2 rounded-t-lg flex-shrink-0">
            <div class="flex justify-between items-center">
                <h2 class="text-xs font-bold text-gray-800" style="font-size: 12px !important; font-weight: 600 !important;">정산관리부서 선택</h2>
                <button onclick="closeSettlementDeptsPopup()" class="text-gray-500 hover:text-gray-700 text-lg font-bold w-6 h-6 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors" style="font-size: 18px !important; width: 24px !important; height: 24px !important;">&times;</button>
            </div>
            <p class="text-xs text-gray-600 mt-1" style="font-size: 11px !important;">복수 선택 가능합니다</p>
        </div>
        
        <!-- 본문 (스크롤 가능) -->
        <div class="flex-1 overflow-y-auto p-2 min-h-0" style="padding: 8px !important;">
            <div id="settlement-depts-list" class="space-y-1" style="gap: 4px !important;">
                <!-- 체크박스 목록이 여기에 동적으로 생성됩니다 -->
            </div>
        </div>
        
        <!-- 푸터 -->
        <div class="sticky bottom-0 bg-white border-t border-gray-200 p-2 rounded-b-lg flex-shrink-0" style="padding: 8px !important;">
            <div class="flex justify-between items-center mb-2" style="margin-bottom: 8px !important;">
                <span class="text-xs text-gray-600" style="font-size: 12px !important;">
                    선택된 부서: <span id="settlement-depts-count" class="font-semibold text-blue-600" style="font-weight: 600 !important;">0</span>개
                </span>
            </div>
            <div class="flex gap-2" style="gap: 8px !important;">
                <button onclick="selectAllSettlementDepts()" 
                        class="flex-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium transition-colors"
                        style="padding: 4px 12px !important; font-size: 12px !important; height: 24px !important; border-radius: 6px !important; font-weight: 600 !important; background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #e2e8f0 !important;">
                    전체 선택
                </button>
                <button onclick="deselectAllSettlementDepts()" 
                        class="flex-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium transition-colors"
                        style="padding: 4px 12px !important; font-size: 12px !important; height: 24px !important; border-radius: 6px !important; font-weight: 600 !important; background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #e2e8f0 !important;">
                    전체 해제
                </button>
                <button onclick="confirmSettlementDepts()" 
                        class="flex-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium transition-colors"
                        style="padding: 4px 12px !important; font-size: 12px !important; height: 24px !important; border-radius: 6px !important; font-weight: 600 !important;">
                    확인
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 카카오 주소검색 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// 정산관리부서 관련 전역 변수
var selectedSettlementDepts = [];
var deptListData = [];

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 사용자등급 변경 시 정산관리부서 필드 표시/숨김
    var userClassSelect = document.getElementById('user_class');
    var settlementDeptsBtn = document.getElementById('settlement-depts-btn');
    
    function toggleSettlementDeptsField() {
        if (userClassSelect.value === '4') {
            if (settlementDeptsBtn) {
                settlementDeptsBtn.style.display = 'block';
            }
            // 정산관리부서 목록 로드
            loadSettlementDeptsData();
        } else {
            if (settlementDeptsBtn) {
                settlementDeptsBtn.style.display = 'none';
            }
            selectedSettlementDepts = [];
        }
    }
    
    // 초기 상태 설정
    toggleSettlementDeptsField();
    
    // 사용자등급 변경 이벤트
    userClassSelect.addEventListener('change', toggleSettlementDeptsField);
    
    // 수정 모드일 때 기존 정산관리부서 목록 로드
    <?php if ($mode === 'edit' && !empty($customer_info['user_id'])): ?>
    var userId = '<?= esc($customer_info['user_id'] ?? '', 'js') ?>';
    if (userClassSelect.value === '4' && userId) {
        loadExistingSettlementDepts(userId);
    }
    <?php endif; ?>
});

// 정산관리부서 데이터 로드
function loadSettlementDeptsData() {
    Promise.all([
        fetch('<?= base_url('admin/getDepartmentList') ?>', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => response.json())
    ]).then(([deptData]) => {
        if (deptData.success && deptData.data && deptData.data.length > 0) {
            deptListData = deptData.data;
        } else {
            deptListData = [];
        }
    }).catch(error => {
        console.error('부서 목록 로드 실패:', error);
        deptListData = [];
    });
}

// 기존 정산관리부서 목록 로드 (수정 모드)
function loadExistingSettlementDepts(userId) {
    // user_id로 user_idx 조회 후 정산관리부서 목록 조회
    fetch('<?= base_url('admin/getSettlementDeptsByUserId') ?>?user_id=' + encodeURIComponent(userId), {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            selectedSettlementDepts = data.data.map(dept => dept.dept_name);
            updateSettlementDeptsDisplay();
        } else {
            selectedSettlementDepts = [];
        }
    })
    .catch(error => {
        console.error('정산관리부서 목록 조회 실패:', error);
        selectedSettlementDepts = [];
    });
}

// 정산관리부서 팝업 열기
function openSettlementDeptsPopup() {
    var popup = document.getElementById('settlementDeptsPopup');
    if (!popup) return;
    
    // 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 팝업 표시
    popup.classList.remove('hidden');
    popup.classList.add('flex');
    
    // 체크박스 목록 생성
    if (deptListData && deptListData.length > 0) {
        loadSettlementDeptsCheckboxes(deptListData);
    } else {
        // 부서 목록이 없으면 다시 로드
        loadSettlementDeptsData().then(() => {
            if (deptListData && deptListData.length > 0) {
                loadSettlementDeptsCheckboxes(deptListData);
            }
        });
    }
    
    // 선택된 개수 업데이트
    updateSettlementDeptsCount();
}

// 정산관리부서 팝업 닫기
function closeSettlementDeptsPopup() {
    var popup = document.getElementById('settlementDeptsPopup');
    if (!popup) return;
    
    popup.classList.add('hidden');
    popup.classList.remove('flex');
    
    // 사이드바 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 정산관리부서 체크박스 생성
function loadSettlementDeptsCheckboxes(deptList) {
    var container = document.getElementById('settlement-depts-list');
    if (!container) return;
    
    // 기존 체크박스 제거
    container.innerHTML = '';
    
    if (!deptList || deptList.length === 0) {
        container.innerHTML = '<div class="text-sm text-gray-400 text-center py-8" style="font-size: 12px !important;">부서 목록이 없습니다.</div>';
        return;
    }
    
    // 체크박스 생성
    deptList.forEach(function(dept) {
        if (dept && dept.department_name) {
            var deptName = dept.department_name.trim();
            if (deptName) {
                var label = document.createElement('label');
                label.className = 'flex items-center space-x-2 cursor-pointer hover:bg-blue-50 rounded border border-gray-200 transition-colors';
                label.style.cssText = 'padding: 4px 8px !important; gap: 8px !important; border-radius: 4px !important;';
                
                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = deptName;
                checkbox.className = 'text-blue-600 border-gray-300 rounded focus:ring-blue-500';
                checkbox.style.cssText = 'width: 14px !important; height: 14px !important;';
                
                // 선택된 부서인지 확인
                if (selectedSettlementDepts.includes(deptName)) {
                    checkbox.checked = true;
                }
                
                // 체크박스 변경 이벤트
                checkbox.addEventListener('change', function() {
                    updateSettlementDeptsCount();
                });
                
                var span = document.createElement('span');
                span.className = 'text-gray-700 flex-1';
                span.style.cssText = 'font-size: 12px !important; color: #374151 !important;';
                span.textContent = deptName;
                
                label.appendChild(checkbox);
                label.appendChild(span);
                container.appendChild(label);
            }
        }
    });
}

// 선택된 정산관리부서 개수 업데이트
function updateSettlementDeptsCount() {
    var checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]:checked');
    var count = checkboxes.length;
    var countElement = document.getElementById('settlement-depts-count');
    if (countElement) {
        countElement.textContent = count;
    }
}

// 전체 선택
function selectAllSettlementDepts() {
    var checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
    updateSettlementDeptsCount();
}

// 전체 해제
function deselectAllSettlementDepts() {
    var checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    updateSettlementDeptsCount();
}

// 선택 확인 (팝업 닫기 및 버튼 텍스트 업데이트)
function confirmSettlementDepts() {
    var checkboxes = document.querySelectorAll('#settlement-depts-list input[type="checkbox"]:checked');
    selectedSettlementDepts = Array.from(checkboxes).map(function(cb) {
        return cb.value;
    });
    
    updateSettlementDeptsDisplay();
    closeSettlementDeptsPopup();
}

// 정산관리부서 버튼 텍스트 업데이트
function updateSettlementDeptsDisplay() {
    var displayElement = document.getElementById('settlement-depts-display');
    
    if (!displayElement) return;
    
    if (selectedSettlementDepts.length === 0) {
        displayElement.textContent = '정산관리부서 (복수선택가능)';
        displayElement.className = 'text-gray-500';
    } else {
        displayElement.textContent = '정산관리부서 (' + selectedSettlementDepts.length + '개 선택됨)';
        displayElement.className = 'text-gray-700 font-medium';
    }
}

// ESC 키로 팝업 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var settlementDeptsPopup = document.getElementById('settlementDeptsPopup');
        if (settlementDeptsPopup && !settlementDeptsPopup.classList.contains('hidden')) {
            closeSettlementDeptsPopup();
        }
    }
});

function execDaumPostcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            var roadAddr = data.roadAddress;
            var extraRoadAddr = '';

            if(data.bname !== '' && /[동|로|가]$/g.test(data.bname)){
                extraRoadAddr += data.bname;
            }
            if(data.buildingName !== '' && data.apartment === 'Y'){
               extraRoadAddr += (extraRoadAddr !== '' ? ', ' + data.buildingName : data.buildingName);
            }
            if(extraRoadAddr !== ''){
                extraRoadAddr = ' (' + extraRoadAddr + ')';
            }

            document.getElementById("user_addr1").value = data.jibunAddress;
            document.getElementById("user_dong").value = data.bname;
            document.getElementById("sido").value = data.sido;
            document.getElementById("gungu").value = data.sigungu;
            document.getElementById("dong2").value = data.bname1;
            document.getElementById("fulladdr").value = data.jibunAddress;
        }
    }).open();
}

String.prototype.replaceAll = function( searchStr, replaceStr ) {
    var temp = this;
    while( temp.indexOf( searchStr ) != -1 ) {
        temp = temp.replace( searchStr, replaceStr );
    }
    return temp;
}

function addsubmit() {
    var errmsg;
    var n = document.member_form;

    if(n.user_name.value == n.user_pass.value)
        errmsg = "고객명과 비밀번호를 다르게 입력하세요.";
    if(n.user_id.value == n.user_pass.value)
        errmsg = "아이디와 비밀번호를 다르게 입력하세요.";
    if(!n.user_addr1.value)
        errmsg = "주소를 입력하세요.";
    if(!n.user_tel1.value)
        errmsg = "전화번호1을 입력하세요.";
    if(isNaN(n.user_tel1.value.replaceAll('-',''))) 
        errmsg = '전화번호1은 숫자로만 입력하세요.';
    if(n.user_tel2.value && isNaN(n.user_tel2.value.replaceAll('-',''))) 
        errmsg = '전화번호2는 숫자로만 입력하세요.';
    if(!n.user_name.value)
        errmsg = "담당자명을 입력하세요.";
    
    <?php if ($mode !== 'edit'): ?>
    if(!n.user_pass.value)
        errmsg = "비밀번호를 입력하세요.";
    if(n.user_pass.value.length < 5 || n.user_pass.value.length > 30)
        errmsg = "비밀번호는 5자리 이상 30자리 이하로 입력하세요.";
    <?php else: ?>
    if(n.user_pass.value.length && (n.user_pass.value.length < 5 || n.user_pass.value.length > 30))
        errmsg = "비밀번호는 5자리 이상 30자리 이하로 입력하세요.";
    <?php endif; ?>

    if(n.user_pass.value != n.user_pass2.value)
        errmsg = "입력된 두 비밀번호가 틀립니다.";
    if(!n.user_id.value)
        errmsg = "아이디를 입력하세요.";
    
    // 정산관리부서 데이터를 hidden input에 추가
    var settlementDeptsInput = document.getElementById('settlement_depts_hidden');
    if (!settlementDeptsInput) {
        settlementDeptsInput = document.createElement('input');
        settlementDeptsInput.type = 'hidden';
        settlementDeptsInput.id = 'settlement_depts_hidden';
        settlementDeptsInput.name = 'settlement_depts';
        n.appendChild(settlementDeptsInput);
    }
    settlementDeptsInput.value = JSON.stringify(selectedSettlementDepts);
        
    if(errmsg) {
        alert(errmsg);
        return;
    } else {
        n.submit();
    }
}
</script>

<?= $this->endSection() ?>
