// 주문 폼 JavaScript (jQuery)
$(document).ready(function() {
    const $orderForm = $('#orderForm');
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
    
    // 폼 제출 처리
    $orderForm.on('submit', function(e) {
        e.preventDefault();
        
        // 폼 유효성 검사
        const $requiredFields = $orderForm.find('[required]');
        let isValid = true;
        
        $requiredFields.each(function() {
            if (!$(this).val().trim()) {
                $(this).css('border-color', '#e53e3e');
                isValid = false;
            } else {
                $(this).css('border-color', '#e2e8f0');
            }
        });
        
        if (!isValid) {
            alert('필수 항목을 모두 입력해주세요.');
            return;
        }
        
        // 폼 데이터 수집
        const formData = new FormData(this);
        const orderData = {};
        
        for (let [key, value] of formData.entries()) {
            if (orderData[key]) {
                if (Array.isArray(orderData[key])) {
                    orderData[key].push(value);
                } else {
                    orderData[key] = [orderData[key], value];
                }
            } else {
                orderData[key] = value;
            }
        }
        
        // 주문 접수 처리 (실제로는 서버로 전송)
        // console.log('주문 데이터:', orderData);
        
        // 성공 메시지
        alert('주문이 성공적으로 접수되었습니다!');
        $orderForm[0].reset();
        
        // 첫 번째 아이템만 남기고 나머지 제거
        const $itemRows = $itemContainer.find('.item-row');
        $itemRows.slice(1).remove();
        updateRemoveButtons();
    });
    
    // 취소 버튼
    $('.btn-cancel').on('click', function() {
        if (confirm('작성 중인 내용이 사라집니다. 정말 취소하시겠습니까?')) {
            $orderForm[0].reset();
            // 첫 번째 아이템만 남기고 나머지 제거
            const $itemRows = $itemContainer.find('.item-row');
            $itemRows.slice(1).remove();
            updateRemoveButtons();
        }
    });
    
    // 초기화
    updateRemoveButtons();
});