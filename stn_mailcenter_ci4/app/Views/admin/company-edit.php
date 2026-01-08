<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>

<?= form_open('/admin/company-save', ['name' => 'company_form', 'id' => 'company_form', 'method' => 'post']) ?>
<input type="hidden" name="comp_code" value="<?= esc($company_info['comp_code'] ?? '') ?>">

    <div class="w-full max-w-full flex flex-col md:flex-row gap-4 box-border">
    <!-- 1번 패널: 기본정보 -->
    <div class="flex-1 w-full min-w-0">
        <div class="mb-1">
            <section class="bg-blue-50 rounded-lg shadow-sm border-2 border-blue-300 p-3">
                <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">기본정보</h2>
                
                <div class="space-y-3">
                    <div class="form-field">
                        <label class="form-label required">거래처명</label>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-sm font-medium">STN</span>
                            <input type="text" 
                                   id="comp_name" 
                                   name="comp_name" 
                                   value="<?= esc($company_info['comp_name'] ?? '') ?>" 
                                   required
                                   class="form-input flex-1">
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="form-label required">대표자명</label>
                        <input type="text" 
                               id="representative_name" 
                               name="representative_name" 
                               value="<?= esc($company_info['representative_name'] ?? '') ?>" 
                               class="form-input"
                               required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">담당자명</label>
                        <input type="text" 
                               id="comp_owner" 
                               name="comp_owner" 
                               value="<?= esc($company_info['comp_owner'] ?? '') ?>" 
                               class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label required">전화번호</label>
                        <input type="text" 
                               id="comp_tel" 
                               name="comp_tel" 
                               value="<?= esc($company_info['comp_tel'] ?? '') ?>" 
                               class="form-input"
                               required>
                        <p class="text-xs text-red-600 mt-1">[※ 01012345678 형식으로 하이픈(-)을 제외하고 넣어주세요.]</p>
                    </div>
                    <div class="form-field">
                        <label class="form-label required">주소</label>
                        <div class="flex space-x-2 mb-2">
                            <input type="text"
                                   id="comp_dong"
                                   name="comp_dong"
                                   value="<?= esc($company_info['comp_dong'] ?? '') ?>"
                                   placeholder="동명"
                                   class="form-input flex-1">
                            <button type="button"
                                    onclick="execDaumPostcode()"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors whitespace-nowrap">
                                주소검색
                            </button>
                        </div>
                        <input type="text"
                               id="comp_addr"
                               name="comp_addr"
                               value="<?= esc($company_info['comp_addr'] ?? '') ?>"
                               placeholder="기본주소"
                               class="form-input mb-2"
                               required>
                        <input type="text"
                               id="comp_addr_detail"
                               name="comp_addr_detail"
                               value="<?= esc($company_info['comp_addr_detail'] ?? '') ?>"
                               placeholder="상세주소"
                               class="form-input">
                        <input type="hidden" name="sido" id="sido" value="<?= esc($company_info['sido'] ?? '') ?>">
                        <input type="hidden" name="gungu" id="gungu" value="<?= esc($company_info['gungu'] ?? '') ?>">
                        <input type="hidden" name="dong2" id="dong2">
                        <input type="hidden" name="fulladdr" id="fulladdr" value="<?= esc($company_info['comp_addr'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label class="form-label">메모</label>
                        <input type="text" 
                               id="comp_memo" 
                               name="comp_memo" 
                               value="<?= esc($company_info['comp_memo'] ?? '') ?>" 
                               class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">거래구분</label>
                        <select id="comp_type" 
                                name="comp_type" 
                                class="form-input">
                            <option value="1" <?= (($company_info['comp_type'] ?? $company_info['comp_gbn'] ?? '3') == '1') ? 'selected' : '' ?>>1: 현금</option>
                            <option value="3" <?= (($company_info['comp_type'] ?? $company_info['comp_gbn'] ?? '3') == '3') ? 'selected' : '' ?>>3: 신용</option>
                            <option value="5" <?= (($company_info['comp_type'] ?? $company_info['comp_gbn'] ?? '3') == '5') ? 'selected' : '' ?>>5: 월결제</option>
                            <option value="7" <?= (($company_info['comp_type'] ?? $company_info['comp_gbn'] ?? '3') == '7') ? 'selected' : '' ?>>7: 카드</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- 2번 패널: 일반고객 배송조회 제한 -->
    <div class="flex-1 w-full min-w-0">
        <div class="mb-1">
            <section class="bg-gray-50 rounded-lg shadow-sm border-2 border-gray-300 p-3">
                <h2 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-300">일반고객 배송조회 제한</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-field">
                        <label class="form-label">조회권한</label>
                        <select id="delivery_inquiry_permission" 
                                name="delivery_inquiry_permission" 
                                class="form-input">
                            <option value="1" <?= (($company_info['delivery_inquiry_permission'] ?? '1') == '1') ? 'selected' : '' ?>>전체조회</option>
                            <option value="3" <?= (($company_info['delivery_inquiry_permission'] ?? '1') == '3') ? 'selected' : '' ?>>본인오더조회</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">요금조회</label>
                        <select id="fee_inquiry" 
                                name="fee_inquiry" 
                                class="form-input">
                            <option value="1" <?= (($company_info['fee_inquiry'] ?? '1') == '1') ? 'selected' : '' ?>>전체조회</option>
                            <option value="3" <?= (($company_info['fee_inquiry'] ?? '1') == '3') ? 'selected' : '' ?>>기본요금조회</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">요금계산방식</label>
                        <select id="fee_calc_method" 
                                name="fee_calc_method" 
                                class="form-input">
                            <option value="1" <?= (($company_info['fee_calc_method'] ?? '1') == '1') ? 'selected' : '' ?>>동대동방식</option>
                            <option value="3" <?= (($company_info['fee_calc_method'] ?? '1') == '3') ? 'selected' : '' ?>>거리요금방식</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">오더승인여부</label>
                        <select id="order_approval_type" 
                                name="order_approval_type" 
                                class="form-input">
                            <option value="1" <?= (($company_info['order_approval_type'] ?? '1') == '1') ? 'selected' : '' ?>>일반방식</option>
                            <option value="3" <?= (($company_info['order_approval_type'] ?? '1') == '3') ? 'selected' : '' ?>>관리자승인방식</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- 3번 패널: 버튼 영역 -->
    <div class="w-full md:w-64 flex-shrink-0 max-w-full box-border">
        <div class="mb-1">
            <div class="flex flex-col space-y-2 bg-white rounded-lg shadow-sm border-2 border-gray-300 p-4 box-border">
                <button type="submit" 
                        form="company_form"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap">
                    ✓ 수정하기
                </button>
                <button type="button" 
                        onclick="location.href='<?= base_url('admin/company-list-cc') ?>'" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm font-medium transition-colors w-full md:w-auto box-border whitespace-nowrap">
                    취소
                </button>
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

            document.getElementById("comp_addr").value = data.jibunAddress;
            document.getElementById("comp_dong").value = data.bname;
            document.getElementById("sido").value = data.sido;
            document.getElementById("gungu").value = data.sigungu;
            document.getElementById("dong2").value = data.bname1;
            document.getElementById("fulladdr").value = data.jibunAddress;
        }
    }).open();
}
</script>

<?= $this->endSection() ?>

