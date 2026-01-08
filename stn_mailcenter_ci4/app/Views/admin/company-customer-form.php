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
                            <option value="5" <?= (($customer_info['user_class'] ?? '5') == '5') ? 'selected' : '' ?>>일반</option>
                            <option value="3" <?= (($customer_info['user_class'] ?? '') == '3') ? 'selected' : '' ?>>부서장</option>
                            <option value="1" <?= (($customer_info['user_class'] ?? '') == '1') ? 'selected' : '' ?>>관리자</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">부서명</label>
                        <input type="text" 
                               id="user_dept" 
                               name="user_dept" 
                               value="<?= esc($customer_info['user_dept'] ?? '') ?>" 
                               class="form-input">
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

<!-- 카카오 주소검색 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
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
        
    if(errmsg) {
        alert(errmsg);
        return;
    } else {
        n.submit();
    }
}
</script>

<?= $this->endSection() ?>
