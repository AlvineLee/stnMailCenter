// 공통 폼 JavaScript (jQuery)
$(document).ready(function() {
    const $destinationTypeRadios = $('input[name="destinationType"]');
    const $destinationAddress = $('#destinationAddress');
    const $detailAddress = $('#detailAddress');
    const $addItemBtn = $('#addItemBtn');
    const $itemContainer = $('#itemContainer');
    const $addCompanyBtn = $('#addCompanyBtn');
    
    // 도착지 타입 변경 이벤트
    $destinationTypeRadios.on('change', function() {
        if ($(this).val() === 'direct') {
            $destinationAddress.prop('disabled', false).prop('required', true);
            $detailAddress.prop('disabled', false).prop('required', true);
        } else {
            $destinationAddress.prop('disabled', true).prop('required', false).val('');
            $detailAddress.prop('disabled', true).prop('required', false).val('');
        }
    });
    
    // 아이템 추가 기능
    $addItemBtn.on('click', function() {
        const itemRow = `
            <div class="item-row">
                <div class="form-group">
                    <label for="itemType">물품정보 *</label>
                    <select name="itemType[]" required>
                        <option value="document">서류봉투</option>
                        <option value="package">소포</option>
                        <option value="envelope">편지</option>
                        <option value="other">기타</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">수량/단위</label>
                    <div class="quantity-group">
                        <input type="number" name="quantity[]" value="1" min="1" required>
                        <select name="unit[]">
                            <option value="개">개</option>
                            <option value="박스">박스</option>
                            <option value="봉지">봉지</option>
                            <option value="장">장</option>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn-remove-item" onclick="removeItem(this)">삭제</button>
            </div>
        `;
        
        $itemContainer.append(itemRow);
        updateRemoveButtons();
    });
    
    // 아이템 삭제 함수
    window.removeItem = function(button) {
        $(button).closest('.item-row').remove();
        updateRemoveButtons();
    };
    
    // 삭제 버튼 표시/숨김 업데이트
    function updateRemoveButtons() {
        const $itemRows = $itemContainer.find('.item-row');
        const $removeButtons = $itemContainer.find('.btn-remove-item');
        
        $removeButtons.each(function() {
            if ($itemRows.length > 1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    // 상호 검색 추가 버튼
    $addCompanyBtn.on('click', function() {
        alert('상호 검색 기능은 추후 구현 예정입니다.');
    });
    
    // 초기화
    updateRemoveButtons();
});