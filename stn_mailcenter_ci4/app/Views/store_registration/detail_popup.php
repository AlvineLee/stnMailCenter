<div class="space-y-4">
    <!-- 액션 버튼 영역 -->
    <div class="flex justify-end gap-2 mb-4">
        <?php if ($registration['status'] === 'pending'): ?>
            <button onclick="approveFromDetail(<?= $registration['id'] ?>)" 
                    class="px-4 py-2 text-sm font-semibold text-white bg-green-600 border border-green-600 rounded hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-500">
                승인
            </button>
            <button onclick="rejectFromDetail(<?= $registration['id'] ?>)" 
                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 border border-red-600 rounded hover:bg-red-700 focus:outline-none focus:ring-1 focus:ring-red-500">
                거부
            </button>
        <?php endif; ?>
        <button onclick="closeDetailPopup()" 
                class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-500">
            닫기
        </button>
    </div>

    <!-- 기본 정보 섹션 -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-200">기본 정보</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">신청자 타입</label>
                <p class="text-sm text-gray-900 font-medium">
                    <?php
                    $types = [
                        'new_company' => '신규 회사',
                        'existing_company_branch' => '기존 회사 지사',
                        'existing_company_agency' => '기존 회사 대리점'
                    ];
                    echo $types[$registration['applicant_type']] ?? $registration['applicant_type'];
                    ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">계층 레벨</label>
                <p class="text-sm text-gray-900 font-medium">
                    <?php
                    $levels = [
                        'head_office' => '본사',
                        'branch' => '지사',
                        'agency' => '대리점'
                    ];
                    echo $levels[$registration['hierarchy_level']] ?? $registration['hierarchy_level'];
                    ?>
                </p>
            </div>
        </div>
    </div>

    <!-- 회사 정보 섹션 -->
    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">회사 정보</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">회사명</label>
                <p class="text-sm text-gray-900"><?= esc($registration['company_name']) ?></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">사업자등록번호</label>
                <p class="text-sm text-gray-900"><?= esc($registration['business_number']) ?></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">업종</label>
                <p class="text-sm text-gray-900"><?= esc($registration['business_type'] ?? '-') ?></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">직원 수</label>
                <p class="text-sm text-gray-900"><?= $registration['employee_count'] ? number_format($registration['employee_count']) . '명' : '-' ?></p>
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">회사 주소</label>
            <p class="text-sm text-gray-900"><?= esc($registration['company_address']) ?></p>
        </div>
    </div>

    <!-- 대표자 정보 섹션 -->
    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">대표자 정보</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">대표자명</label>
                <p class="text-sm text-gray-900"><?= esc($registration['representative_name']) ?></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">연락처</label>
                <p class="text-sm text-gray-900"><?= esc($registration['representative_phone']) ?></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">이메일</label>
                <p class="text-sm text-gray-900"><?= esc($registration['representative_email']) ?></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">연매출액</label>
                <p class="text-sm text-gray-900">
                    <?= $registration['annual_revenue'] ? number_format($registration['annual_revenue']) . '원' : '-' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- 서비스 정보 섹션 -->
    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">서비스 정보</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">주력 서비스 카테고리</label>
                <p class="text-sm text-gray-900">
                    <?php
                    $categories = [
                        'quick' => '퀵서비스',
                        'parcel' => '택배서비스',
                        'life' => '생활서비스',
                        'general' => '일반서비스',
                        'special' => '특수서비스'
                    ];
                    echo $categories[$registration['primary_service_category']] ?? $registration['primary_service_category'];
                    ?>
                </p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">예상 월 주문량</label>
                <p class="text-sm text-gray-900">
                    <?= $registration['expected_monthly_orders'] ? number_format($registration['expected_monthly_orders']) . '건' : '-' ?>
                </p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">희망 계약기간</label>
                <p class="text-sm text-gray-900">
                    <?= $registration['contract_period'] ? $registration['contract_period'] . '개월' : '-' ?>
                </p>
            </div>
        </div>
        <?php if ($registration['special_requirements']): ?>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">특별 요구사항</label>
            <p class="text-sm text-gray-900"><?= esc($registration['special_requirements']) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- 첨부파일 섹션 -->
    <?php if ($registration['business_license_file'] || $registration['company_profile_file']): ?>
    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">첨부파일</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <?php if ($registration['business_license_file']): ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">사업자등록증</label>
                <a href="<?= base_url('uploads/store_registration/' . $registration['business_license_file']) ?>" 
                   target="_blank" class="text-sm text-blue-600 hover:text-blue-800 underline">
                    <?= esc($registration['business_license_file']) ?>
                </a>
            </div>
            <?php endif; ?>
            <?php if ($registration['company_profile_file']): ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">회사 소개서</label>
                <a href="<?= base_url('uploads/store_registration/' . $registration['company_profile_file']) ?>" 
                   target="_blank" class="text-sm text-blue-600 hover:text-blue-800 underline">
                    <?= esc($registration['company_profile_file']) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 처리 현황 섹션 -->
    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-700 mb-1 py-1 px-2 bg-gray-100 rounded">처리 현황</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">신청일</label>
                <p class="text-sm text-gray-900"><?= date('Y-m-d H:i', strtotime($registration['created_at'])) ?></p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">현재 상태</label>
                <?php
                $statusConfig = [
                    'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => '대기중'],
                    'under_review' => ['class' => 'bg-blue-100 text-blue-800', 'text' => '심사중'],
                    'approved' => ['class' => 'bg-green-100 text-green-800', 'text' => '승인'],
                    'rejected' => ['class' => 'bg-red-100 text-red-800', 'text' => '거부']
                ];
                $config = $statusConfig[$registration['status']] ?? $statusConfig['pending'];
                ?>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $config['class'] ?>">
                    <?= $config['text'] ?>
                </span>
            </div>
            <?php if ($registration['reviewed_at']): ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">심사일</label>
                <p class="text-sm text-gray-900"><?= date('Y-m-d H:i', strtotime($registration['reviewed_at'])) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($registration['approved_at']): ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">승인일</label>
                <p class="text-sm text-gray-900"><?= date('Y-m-d H:i', strtotime($registration['approved_at'])) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($registration['reviewer_name']): ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">심사자</label>
                <p class="text-sm text-gray-900"><?= esc($registration['reviewer_name']) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($registration['approver_name']): ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">승인자</label>
                <p class="text-sm text-gray-900"><?= esc($registration['approver_name']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($registration['notes']): ?>
        <div class="mt-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">처리 의견</label>
            <p class="text-sm text-gray-900"><?= esc($registration['notes']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($registration['rejection_reason']): ?>
        <div class="mt-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">거부 사유</label>
            <p class="text-sm text-red-600"><?= esc($registration['rejection_reason']) ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
