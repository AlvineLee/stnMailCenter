<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">

    <!-- 부서 등록 폼 -->
    <div class="form-container">
        <div class="form-section">
            <div class="form-section-title">부서 정보</div>
            
            <div class="form-grid">
                <!-- 왼쪽 컬럼 -->
                <div>
                    <div class="form-field">
                        <select name="customer_id" required class="form-select">
                            <option value="">고객사 (STN 네트워크, STN 서울지사 등)</option>
                            <option value="1">STN 네트워크</option>
                            <option value="2">STN 서울지사</option>
                            <option value="3">STN 강남대리점</option>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <input type="text" name="department_code" required placeholder="부서 코드 (예: DEPT001)" class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <input type="text" name="department_name" required placeholder="부서명 (예: 개발팀)" class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <select name="parent_department_id" class="form-select">
                            <option value="">상위 부서 (없으면 선택 안함)</option>
                            <option value="1">개발팀</option>
                            <option value="2">마케팅팀</option>
                            <option value="3">영업팀</option>
                        </select>
                    </div>
                </div>
                
                <!-- 오른쪽 컬럼 -->
                <div>
                    <div class="form-field">
                        <input type="text" name="manager_name" placeholder="부서장 이름 (예: 김개발)" class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <input type="tel" name="manager_contact" placeholder="부서장 연락처 (예: 010-1234-5678)" class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <input type="email" name="manager_email" placeholder="부서장 이메일 (예: manager@company.com)" class="form-input">
                    </div>
                    
                    <div class="form-field">
                        <input type="text" name="cost_center" placeholder="코스트 센터 (예: CC001)" class="form-input">
                    </div>
                </div>
            </div>
            
            <!-- 예산 및 메모 -->
            <div class="form-field">
                <div class="input-group">
                    <input type="number" name="budget_limit" placeholder="월 예산 한도 (예: 10000000)" class="form-input">
                    <span class="input-suffix">원</span>
                </div>
            </div>
            
            <div class="form-field">
                <textarea name="notes" rows="4" placeholder="메모 (부서 관련 메모를 입력하세요)" class="form-textarea"></textarea>
            </div>
        </div>
    </div>

    <!-- 액션 버튼들 -->
    <div class="form-actions">
        <a href="<?= base_url('department') ?>" class="form-button form-button-secondary">
            목록으로
        </a>
        <button type="submit" class="form-button form-button-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            부서 등록
        </button>
    </div>
</div>

<script>
document.getElementById('departmentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // 프로토타입에서는 실제 제출하지 않음
    alert('프로토타입 모드: 부서 등록 기능은 실제 DB 연결 후 사용 가능합니다.');
});

// 폼 유효성 검사
function validateForm() {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            field.style.background = '#fef2f2';
            isValid = false;
        } else {
            field.style.borderColor = '#d1d5db';
            field.style.background = 'white';
        }
    });
    
    return isValid;
}

// 실시간 유효성 검사
document.querySelectorAll('[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.style.borderColor = '#ef4444';
            this.style.background = '#fef2f2';
        } else {
            this.style.borderColor = '#d1d5db';
            this.style.background = 'white';
        }
    });
    
    field.addEventListener('input', function() {
        if (this.value.trim()) {
            this.style.borderColor = '#d1d5db';
            this.style.background = 'white';
        }
    });
});

// 입력 필드 포커스 효과
document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(field => {
    field.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
    });
    
    field.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
    });
});
</script>
<?= $this->endSection() ?>