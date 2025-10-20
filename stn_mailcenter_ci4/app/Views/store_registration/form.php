<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/store-registration.css') ?>">
</head>
<body>
    <div class="popup-overlay" id="popupOverlay">
        <div class="popup-container">
            <div class="popup-header">
                <h2 class="popup-title">입점신청</h2>
                <button class="popup-close" onclick="closePopup()">&times;</button>
            </div>
            
            <div class="popup-body">
                <div id="messageContainer"></div>
                
                <?= form_open_multipart('store-registration/submit', ['class' => 'registration-form', 'id' => 'registrationForm']) ?>
                    <!-- 기본 정보 섹션 -->
                    <div class="form-section">
                        <h3 class="section-title">기본 정보</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label class="form-label">신청자 타입 *</label>
                                <select name="applicant_type" class="form-select" required>
                                    <option value="">선택해주세요</option>
                                    <option value="new_company">신규 회사</option>
                                    <option value="existing_company_branch">기존 회사 지사</option>
                                    <option value="existing_company_agency">기존 회사 대리점</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label class="form-label">계층 레벨 *</label>
                                <select name="hierarchy_level" class="form-select" required>
                                    <option value="">선택해주세요</option>
                                    <option value="head_office">본사</option>
                                    <option value="branch">지사</option>
                                    <option value="agency">대리점</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 회사 정보 섹션 -->
                    <div class="form-section">
                        <h3 class="section-title">회사 정보</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label class="form-label">회사명 *</label>
                                <input type="text" name="company_name" class="form-input" placeholder="회사명을 입력해주세요" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label">사업자등록번호 *</label>
                                <input type="text" name="business_number" class="form-input" placeholder="000-00-00000" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label">업종</label>
                                <input type="text" name="business_type" class="form-input" placeholder="업종을 입력해주세요">
                            </div>
                            <div class="form-field">
                                <label class="form-label">직원 수</label>
                                <input type="number" name="employee_count" class="form-input" placeholder="직원 수를 입력해주세요" min="1">
                            </div>
                        </div>
                        <div class="form-grid full">
                            <div class="form-field">
                                <label class="form-label">회사 주소 *</label>
                                <textarea name="company_address" class="form-textarea" placeholder="회사 주소를 입력해주세요" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 대표자 정보 섹션 -->
                    <div class="form-section">
                        <h3 class="section-title">대표자 정보</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label class="form-label">대표자명 *</label>
                                <input type="text" name="representative_name" class="form-input" placeholder="대표자명을 입력해주세요" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label">대표자 연락처 *</label>
                                <input type="tel" name="representative_phone" class="form-input" placeholder="010-0000-0000" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label">대표자 이메일 *</label>
                                <input type="email" name="representative_email" class="form-input" placeholder="email@example.com" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label">연매출액 (원)</label>
                                <input type="number" name="annual_revenue" class="form-input" placeholder="연매출액을 입력해주세요" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- 서비스 정보 섹션 -->
                    <div class="form-section">
                        <h3 class="section-title">서비스 정보</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label class="form-label">주력 서비스 카테고리 *</label>
                                <select name="primary_service_category" class="form-select" required>
                                    <option value="">선택해주세요</option>
                                    <option value="quick">퀵서비스</option>
                                    <option value="parcel">택배서비스</option>
                                    <option value="life">생활서비스</option>
                                    <option value="general">일반서비스</option>
                                    <option value="special">특수서비스</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label class="form-label">예상 월 주문량</label>
                                <input type="number" name="expected_monthly_orders" class="form-input" placeholder="월 주문량을 입력해주세요" min="1">
                            </div>
                            <div class="form-field">
                                <label class="form-label">희망 계약기간 (개월)</label>
                                <input type="number" name="contract_period" class="form-input" placeholder="계약기간을 입력해주세요" min="1">
                            </div>
                            <div class="form-field" id="parentCustomerField" style="display: none;">
                                <label class="form-label">상위 고객사</label>
                                <select name="parent_customer_id" class="form-select">
                                    <option value="">선택해주세요</option>
                                    <?php if (isset($parent_customers)): ?>
                                        <?php foreach ($parent_customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>"><?= $customer['name'] ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid full">
                            <div class="form-field">
                                <label class="form-label">특별 요구사항</label>
                                <textarea name="special_requirements" class="form-textarea" placeholder="특별 요구사항이 있으시면 입력해주세요"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 첨부파일 섹션 -->
                    <div class="form-section">
                        <h3 class="section-title">첨부파일</h3>
                        <div class="form-grid">
                            <div class="form-field">
                                <label class="form-label">사업자등록증</label>
                                <div class="file-upload">
                                    <input type="file" name="business_license_file" accept=".pdf,.jpg,.jpeg,.png">
                                    <label class="file-upload-label">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        파일 선택
                                    </label>
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="form-label">회사 소개서</label>
                                <div class="file-upload">
                                    <input type="file" name="company_profile_file" accept=".pdf,.jpg,.jpeg,.png">
                                    <label class="file-upload-label">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        파일 선택
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 버튼 영역 -->
                    <div class="form-actions">
                        <button type="button" class="form-button form-button-secondary" onclick="closePopup()">
                            취소
                        </button>
                        <button type="submit" class="form-button form-button-primary">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            신청하기
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <script>
        // 폼 제출 처리
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const messageContainer = document.getElementById('messageContainer');
            
            // 로딩 상태
            form.classList.add('loading');
            submitButton.disabled = true;
            submitButton.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> 처리중...';
            
            // AJAX 요청
            fetch('<?= base_url('store-registration/submit') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageContainer.innerHTML = '<div class="message success">' + data.message + '</div>';
                    form.reset();
                    setTimeout(() => {
                        closePopup();
                    }, 2000);
                } else {
                    messageContainer.innerHTML = '<div class="message error">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageContainer.innerHTML = '<div class="message error">오류가 발생했습니다. 다시 시도해주세요.</div>';
            })
            .finally(() => {
                form.classList.remove('loading');
                submitButton.disabled = false;
                submitButton.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> 신청하기';
            });
        });

        // 신청자 타입 변경 시 상위 고객사 필드 표시/숨김
        document.querySelector('select[name="applicant_type"]').addEventListener('change', function() {
            const parentCustomerField = document.getElementById('parentCustomerField');
            if (this.value === 'existing_company_branch' || this.value === 'existing_company_agency') {
                parentCustomerField.style.display = 'block';
            } else {
                parentCustomerField.style.display = 'none';
            }
        });

        // 파일 선택 시 파일명 표시
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.nextElementSibling;
                if (this.files.length > 0) {
                    label.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> ' + this.files[0].name;
                } else {
                    label.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg> 파일 선택';
                }
            });
        });

        // 팝업 닫기
        function closePopup() {
            document.getElementById('popupOverlay').style.display = 'none';
        }

        // 오버레이 클릭 시 팝업 닫기
        document.getElementById('popupOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });

        // ESC 키로 팝업 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePopup();
            }
        });
    </script>
</body>
</html>
