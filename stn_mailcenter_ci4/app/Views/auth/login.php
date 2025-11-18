<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 text-white rounded-lg flex items-center justify-center font-bold text-lg mx-auto mb-3">
                    STN
                </div>
                <h1 class="text-xl font-bold text-gray-800">STN Network</h1>
                <p class="text-gray-500 text-sm">ONE'CALL</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-blue-100 text-blue-800 p-3 rounded-lg mb-5 text-sm"><?= $error ?></div>
            <?php endif; ?>
            
            <?= form_open('auth/processLogin', ['class' => 'space-y-3', 'id' => 'loginForm']) ?>
                <!-- 로그인 타입 선택 -->
                <div class="flex gap-2 mb-3">
                    <label class="flex items-center space-x-2 cursor-pointer flex-1">
                        <input type="radio" name="login_type" value="stn" checked class="text-blue-600 focus:ring-blue-500">
                        <span class="text-xs text-gray-700">STN 로그인</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer flex-1">
                        <input type="radio" name="login_type" value="daumdata" class="text-blue-600 focus:ring-blue-500">
                        <span class="text-xs text-gray-700">다음데이터 로그인</span>
                    </label>
                </div>
                
                <div>
                    <input type="text" id="username" name="username" value="<?= old('username') ?>" placeholder="아이디" required class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <input type="password" id="password" name="password" placeholder="비밀번호" required class="w-full px-3 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex gap-2 mt-4">
                    <button type="submit" class="flex-1 bg-gray-100 text-gray-700 border border-gray-300 py-2 px-3 rounded text-sm font-semibold hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500 transition-colors">
                        로그인
                    </button>
                    <button type="button" onclick="openRegistrationPopup()" class="flex-1 bg-gray-100 text-gray-700 border border-gray-300 py-2 px-3 rounded text-sm font-semibold hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500 transition-colors">
                        입점신청
                    </button>
                </div>
            <?= form_close() ?>
            
            <!-- <div class="bg-blue-50 text-blue-800 p-3 rounded mt-4 text-xs">
                <strong>데모 계정:</strong><br>
                아이디: admin<br>
                비밀번호: admin
            </div> -->
        </div>
    </div>

    <!-- 입점신청 레이어팝업 - Tailwind CSS -->
    <div id="registrationPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4">
            <!-- 헤더 -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-lg font-semibold text-gray-800">입점신청</h2>
                <button onclick="closeRegistrationPopup()" class="text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
            </div>
            
            <!-- 본문 -->
            <div class="p-4 bg-gray-50">
                <div id="messageContainer"></div>
                
                <?= form_open_multipart('store-registration/submit', ['class' => 'space-y-4', 'id' => 'registrationForm']) ?>
                    <!-- 기본 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">기본 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <select name="applicant_type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">신청자 타입 (예: 신규 회사)</option>
                                    <option value="new_company">신규 회사</option>
                                    <option value="existing_company_branch">기존 회사 지사</option>
                                    <option value="existing_company_agency">기존 회사 대리점</option>
                                </select>
                            </div>
                            <div>
                                <select name="hierarchy_level" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">계층 레벨 (예: 본사)</option>
                                    <option value="head_office">본사</option>
                                    <option value="branch">지사</option>
                                    <option value="agency">대리점</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 회사 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">회사 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                            <div>
                                <input type="text" name="company_name" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="회사명 (예: STN Network)" required>
                            </div>
                            <div>
                                <input type="text" name="business_number" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="사업자등록번호 (예: 000-00-00000)" required>
                            </div>
                            <div>
                                <input type="text" name="business_type" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="업종 (예: 물류업)">
                            </div>
                            <div>
                                <input type="number" name="employee_count" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="직원 수 (예: 50명)" min="1">
                            </div>
                        </div>
                        <div>
                            <textarea name="company_address" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 min-h-[60px] resize-y" placeholder="회사 주소 (예: 서울시 강남구 테헤란로 123)" required></textarea>
                        </div>
                    </div>

                    <!-- 대표자 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">대표자 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <input type="text" name="representative_name" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="대표자명 (예: 홍길동)" required>
                            </div>
                            <div>
                                <input type="tel" name="representative_phone" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="대표자 연락처 (예: 010-0000-0000)" required>
                            </div>
                            <div>
                                <input type="email" name="representative_email" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="대표자 이메일 (예: ceo@company.com)" required>
                            </div>
                            <div>
                                <input type="number" name="annual_revenue" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="연매출액 (예: 1000000000원)" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- 서비스 정보 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">서비스 정보</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                            <div>
                                <select name="primary_service_category" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">주력 서비스 카테고리 (예: 퀵서비스)</option>
                                    <option value="quick">퀵서비스</option>
                                    <option value="parcel">택배서비스</option>
                                    <option value="life">생활서비스</option>
                                    <option value="general">일반서비스</option>
                                    <option value="special">특수서비스</option>
                                </select>
                            </div>
                            <div>
                                <input type="number" name="expected_monthly_orders" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="예상 월 주문량 (예: 100건)" min="1">
                            </div>
                            <div>
                                <input type="number" name="contract_period" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="희망 계약기간 (예: 12개월)" min="1">
                            </div>
                        </div>
                        <div>
                            <textarea name="special_requirements" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 min-h-[60px] resize-y" placeholder="특별 요구사항 (예: 24시간 서비스 필요)"></textarea>
                        </div>
                    </div>

                    <!-- 첨부파일 섹션 -->
                    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
                        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">첨부파일</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div class="relative">
                                <input type="file" name="business_license_file" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <label class="flex items-center gap-1 px-2 py-1 text-xs border border-gray-300 rounded bg-gray-50 cursor-pointer hover:bg-gray-100">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    사업자등록증 (예: business_license.pdf)
                                </label>
                            </div>
                            <div class="relative">
                                <input type="file" name="company_profile_file" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <label class="flex items-center gap-1 px-2 py-1 text-xs border border-gray-300 rounded bg-gray-50 cursor-pointer hover:bg-gray-100">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    회사 소개서 (예: company_profile.pdf)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 버튼 영역 -->
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" onclick="closeRegistrationPopup()" class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">
                            취소
                        </button>
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500 flex items-center gap-1">
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
        function openRegistrationPopup() {
            document.getElementById('registrationPopup').classList.remove('hidden');
            document.getElementById('registrationPopup').classList.add('flex');
        }
        
        function closeRegistrationPopup() {
            document.getElementById('registrationPopup').classList.add('hidden');
            document.getElementById('registrationPopup').classList.remove('flex');
        }

        // 폼 제출 처리
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const messageContainer = document.getElementById('messageContainer');
            
            // 로딩 상태
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
                    messageContainer.innerHTML = '<div class="p-3 mb-4 text-sm text-blue-800 bg-blue-100 border border-blue-200 rounded-md">' + data.message + '</div>';
                    form.reset();
                    setTimeout(() => {
                        closeRegistrationPopup();
                    }, 2000);
                } else {
                    messageContainer.innerHTML = '<div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-md">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageContainer.innerHTML = '<div class="p-3 mb-4 text-sm text-red-800 bg-red-100 border border-red-200 rounded-md">오류가 발생했습니다. 다시 시도해주세요.</div>';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> 신청하기';
            });
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

        // 오버레이 클릭 시 팝업 닫기
        document.getElementById('registrationPopup').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRegistrationPopup();
            }
        });

        // ESC 키로 팝업 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRegistrationPopup();
            }
        });
    </script>
</body>
</html>
