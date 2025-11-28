<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 검색 및 필터 영역 -->
    <div class="search-compact">
        <?= form_open('insung/user-list', ['method' => 'GET', 'id' => 'searchForm']) ?>
        <div class="search-filter-container">
            <div class="search-filter-item">
                <label class="search-filter-label">고객사</label>
                <select name="comp_code" id="comp_code_select" class="search-filter-select">
                    <option value="all" <?= ($comp_code_filter ?? 'all') === 'all' ? 'selected' : '' ?>>전체 (<?= number_format($total_company_count ?? 0) ?>)</option>
                    <?php if (!empty($company_list)): ?>
                        <?php foreach ($company_list as $company): ?>
                            <option value="<?= esc($company['comp_code']) ?>" <?= ($comp_code_filter ?? 'all') === $company['comp_code'] ? 'selected' : '' ?>>
                                <?= esc($company['comp_name'] ?? $company['comp_code']) ?> (<?= number_format($company['user_count'] ?? 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">회사명</label>
                <input type="text" name="comp_name" id="comp_name" class="search-filter-select" value="<?= esc($comp_name ?? '') ?>" placeholder="회사명 입력">
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">아이디</label>
                <input type="text" name="user_id" id="user_id" class="search-filter-select" value="<?= esc($user_id ?? '') ?>" placeholder="아이디 입력">
            </div>
            <div class="search-filter-item">
                <label class="search-filter-label">회원명</label>
                <input type="text" name="user_name" id="user_name" class="search-filter-select" value="<?= esc($user_name ?? '') ?>" placeholder="회원명 입력">
            </div>
            <div class="search-filter-button-wrapper">
                <button type="submit" class="search-button" style="background: #8b5cf6; margin-right: 8px;">🔍 검색</button>
                <button type="button" onclick="openInsungMemberSearch()" class="search-button" >인성API회원조회</button>
                
            </div>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 검색 결과 정보 -->
    <div class="mb-4 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-900">
                <?php if (isset($pagination) && $pagination): ?>
                    총 <?= number_format($pagination['total_count']) ?>건 중 
                    <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>건 표시
                <?php else: ?>
                    검색 결과가 없습니다.
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="list-table-container">
        <?php if (empty($user_list)): ?>
            <div class="text-center py-8 text-gray-900">
                회원 데이터가 없습니다.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200" style="table-layout: fixed;">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 6%;">번호</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 11%;">아이디</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 9%;">이름</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 9%;">부서</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 14%;">고객사</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 11%;">연락처</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 24%;">주소</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-900 uppercase border-b" style="width: 16%;">권한</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php 
                    // 역순 번호 계산 (전체 개수에서 현재 페이지의 시작 인덱스를 빼고, 현재 항목의 인덱스를 뺌)
                    $totalCount = $pagination['total_count'] ?? 0;
                    $currentPage = $pagination['current_page'] ?? 1;
                    $perPage = $pagination['per_page'] ?? 20;
                    $startNumber = $totalCount - (($currentPage - 1) * $perPage);
                    $rowNumber = $startNumber;
                    foreach ($user_list as $user): 
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-center"><?= $rowNumber-- ?></td>
                        <td class="px-4 py-2 text-sm" style="word-break: break-all;"><?= esc($user['user_id'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_dept'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['comp_name'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm"><?= esc($user['user_tel1'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm" style="word-break: break-all;"><?= esc($user['user_addr'] ?? '-') ?></td>
                        <td class="px-4 py-2 text-sm">
                            <?php
                            $userTypeLabels = [
                                '1' => '메인 사이트 관리자',
                                '3' => '콜센터 관리자',
                                '5' => '일반 고객'
                            ];
                            $userType = $user['user_type'] ?? '5';
                            ?>
                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                <?= $userTypeLabels[$userType] ?? '일반 고객' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if (isset($pagination) && $pagination && $pagination['total_pages'] > 1): ?>
    <?php
    // 공통 페이징 라이브러리 사용
    $paginationHelper = new \App\Libraries\PaginationHelper(
        $pagination['total_count'],
        $pagination['per_page'],
        $pagination['current_page'],
        base_url('insung/user-list'),
        array_filter([
            'comp_code' => ($comp_code_filter ?? 'all') !== 'all' ? $comp_code_filter : null,
            'comp_name' => !empty($comp_name) ? $comp_name : null,
            'user_id' => !empty($user_id) ? $user_id : null,
            'user_name' => !empty($user_name) ? $user_name : null
        ], function($value) {
            return $value !== null && $value !== '';
        })
    );
    echo $paginationHelper->renderWithCurrentStyle();
    ?>
    <?php endif; ?>
</div>

<!-- 인성회원조회 레이어팝업 -->
<div id="insungMemberModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-7xl max-h-[90vh] flex flex-col" style="z-index: 10000 !important;" onclick="event.stopPropagation()">
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center flex-shrink-0 rounded-t-lg" style="z-index: 10;">
            <h3 class="text-lg font-bold text-gray-900">인성회원조회</h3>
            <button type="button" onclick="closeInsungMemberSearch()" class="text-gray-900 hover:text-gray-900 flex-shrink-0 ml-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="px-4 pt-4 pb-0 flex-shrink-0">
            <div class="search-compact" style="margin-bottom: 0; padding-bottom: 0;">
                <form id="insungMemberSearchForm" onsubmit="searchInsungMembers(event)">
                    <div class="search-filter-container" style="display: flex; flex-wrap: nowrap !important; gap: 8px; align-items: center; width: 100%;">
                        <div class="search-filter-item" style="flex: 0 0 auto; display: flex; align-items: center; gap: 6px;">
                            <label class="search-filter-label" style="margin: 0; white-space: nowrap; font-size: 12px;">고객사</label>
                            <select name="comp_no" id="insung_comp_no_select" class="search-filter-select" style="min-width: 160px; width: 160px;">
                                <option value="">전체</option>
                                <?php if (!empty($company_list)): ?>
                                    <?php foreach ($company_list as $company): ?>
                                        <option value="<?= esc($company['comp_code']) ?>"
                                            data-m-code="<?= esc($company['m_code'] ?? '') ?>"
                                            data-cc-code="<?= esc($company['cc_code'] ?? '') ?>"
                                            data-token="<?= esc($company['token'] ?? '') ?>"
                                            data-api-idx="<?= esc($company['api_idx'] ?? '') ?>">
                                            <?= esc($company['comp_name'] ?? $company['comp_code']) ?> (<?= number_format($company['user_count'] ?? 0) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="search-filter-item" style="flex: 0 0 auto; display: flex; align-items: center; gap: 6px;">
                            <label class="search-filter-label" style="margin: 0; white-space: nowrap; font-size: 12px;">회사명</label>
                            <input type="text" name="comp_name" id="insung_comp_name" class="search-filter-select" placeholder="회사명 입력" style="min-width: 100px; width: 100px;">
                        </div>
                        <div class="search-filter-item" style="flex: 0 0 auto; display: flex; align-items: center; gap: 6px;">
                            <label class="search-filter-label" style="margin: 0; white-space: nowrap; font-size: 12px;">아이디</label>
                            <input type="text" name="user_id" id="insung_user_id" class="search-filter-select" placeholder="아이디 입력" style="min-width: 100px; width: 100px;">
                        </div>
                        <div class="search-filter-item" style="flex: 0 0 auto; display: flex; align-items: center; gap: 6px;">
                            <label class="search-filter-label" style="margin: 0; white-space: nowrap; font-size: 12px;">회원명</label>
                            <input type="text" name="user_name" id="insung_user_name" class="search-filter-select" placeholder="회원명 입력" style="min-width: 100px; width: 100px;">
                        </div>
                        <div class="search-filter-item" style="flex: 0 0 auto; display: flex; align-items: center; gap: 6px;">
                            <label class="search-filter-label" style="margin: 0; white-space: nowrap; font-size: 12px;">페이지당 건수</label>
                            <select id="insung_page_limit" class="search-filter-select" style="min-width: 70px; width: 70px;">
                                <option value="25">25개</option>
                                <option value="50">50개</option>
                                <option value="100" selected>100개</option>
                                <option value="200">200개</option>
                                <option value="500">500개</option>
                            </select>
                        </div>
                        <div class="search-filter-button-wrapper" style="flex-shrink: 0; display: flex; align-items: center; margin-left: 0;">
                            <button type="submit" class="search-button" style="margin: 0; padding: 6px 12px; font-size: 12px; height: auto;">🔍 검색</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="insungMemberResult" class="flex-1 overflow-y-auto px-4 pt-0" style="min-height: 0; padding-top: 0;">
            <div class="text-center py-8 text-gray-900">
                검색 조건을 입력하고 검색 버튼을 클릭하세요.
            </div>
        </div>
        
        <div id="insungMemberPagination" class="p-4 flex-shrink-0 border-t border-gray-200">
            <!-- 일괄처리 버튼과 페이징이 여기에 동적으로 추가됨 -->
        </div>
    </div>
</div>


<script>
// 인성회원조회 레이어팝업 열기
function openInsungMemberSearch() {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('insungMemberModal').classList.remove('hidden');
    document.getElementById('insungMemberModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
    
    // 메인 검색 폼의 값을 레이어팝업 검색 폼에 복사
    const mainCompCode = document.getElementById('comp_code_select')?.value || '';
    const mainCompName = document.getElementById('comp_name')?.value || '';
    const mainUserId = document.getElementById('user_id')?.value || '';
    const mainUserName = document.getElementById('user_name')?.value || '';
    
    // 레이어팝업 검색 폼에 값 설정
    const insungCompNoSelect = document.getElementById('insung_comp_no_select');
    if (insungCompNoSelect && mainCompCode && mainCompCode !== 'all') {
        insungCompNoSelect.value = mainCompCode;
    }
    document.getElementById('insung_comp_name').value = mainCompName;
    document.getElementById('insung_user_id').value = mainUserId;
    document.getElementById('insung_user_name').value = mainUserName;
    
    document.getElementById('insungMemberResult').innerHTML = '<div class="text-center py-8 text-gray-900">검색 조건을 입력하고 검색 버튼을 클릭하세요.</div>';
    const paginationDiv = document.getElementById('insungMemberPagination');
    if (paginationDiv) {
        paginationDiv.innerHTML = '';
    }
}

// 인성회원조회 레이어팝업 닫기
function closeInsungMemberSearch() {
    document.getElementById('insungMemberModal').classList.add('hidden');
    document.getElementById('insungMemberModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 현재 검색 조건 및 페이지 정보 저장
let currentSearchParams = {
    comp_no: '',
    comp_name: '',
    user_id: '',
    user_name: '',
    m_code: '',
    cc_code: '',
    token: '',
    api_idx: '',
    page: 1,
    limit: 100
};

// 현재 페이지의 회원 데이터 저장 (일괄처리용)
let currentPageMembers = [];

// 인성회원조회 검색 (페이지 파라미터 추가)
function searchInsungMembers(event, page = 1) {
    if (event) {
        event.preventDefault();
    }
    
    // comp_no는 고객사 선택 드롭다운에서 선택한 comp_code 값 (tbl_company_list.comp_code)
    const compNoSelect = document.getElementById('insung_comp_no_select');
    if (!compNoSelect) {
        alert('고객사 선택 드롭다운을 찾을 수 없습니다.');
        return;
    }
    
    const compNo = compNoSelect.value || '';
    
    // comp_no가 비어있으면 에러
    if (!compNo || compNo === '') {
        alert('고객사를 선택해주세요.');
        return;
    }
    
    const selectedOption = compNoSelect.options[compNoSelect.selectedIndex];
    
    // 선택한 고객사의 API 정보 가져오기 (data 속성에서)
    const mCode = selectedOption ? selectedOption.getAttribute('data-m-code') : '';
    const ccCode = selectedOption ? selectedOption.getAttribute('data-cc-code') : '';
    const token = selectedOption ? selectedOption.getAttribute('data-token') : '';
    const apiIdx = selectedOption ? selectedOption.getAttribute('data-api-idx') : '';
    
    // FormData에서 다른 검색 조건 가져오기
    const form = document.getElementById('insungMemberSearchForm');
    const formData = form ? new FormData(form) : new FormData();
    
    // 페이지당 건수 가져오기
    const pageLimit = parseInt(document.getElementById('insung_page_limit')?.value || '100');
    
    // 검색 조건 저장
    currentSearchParams = {
        comp_no: compNo,
        comp_name: formData.get('comp_name') || '',
        user_id: formData.get('user_id') || '',
        user_name: formData.get('user_name') || '',
        m_code: mCode,
        cc_code: ccCode,
        token: token,
        api_idx: apiIdx,
        page: page,
        limit: pageLimit
    };
    
    // 디버깅: 전달되는 파라미터 확인
    // console.log('Insung Member Search Params:', currentSearchParams);
    
    const resultDiv = document.getElementById('insungMemberResult');
    const paginationDiv = document.getElementById('insungMemberPagination');
    
    // 이전 데이터가 있는지 확인 (페이지 이동인 경우)
    const hasPreviousData = resultDiv && resultDiv.innerHTML && 
                            !resultDiv.innerHTML.includes('검색 조건을 입력하고') && 
                            !resultDiv.innerHTML.includes('검색 중...') &&
                            !resultDiv.innerHTML.includes('검색 결과가 없습니다') &&
                            !resultDiv.innerHTML.includes('오류:');
    
    // 첫 검색이 아니고 이전 데이터가 있으면 유지, 없으면 로딩 표시
    if (!hasPreviousData) {
        resultDiv.innerHTML = '<div class="text-center py-8"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div><p>검색 중...</p></div>';
        if (paginationDiv) {
            paginationDiv.innerHTML = '';
        }
    }
    // 페이지 이동인 경우 이전 데이터와 페이징은 그대로 유지 (새 데이터가 로드되면 교체됨)
    
    fetch('<?= base_url('insung/getInsungMemberList') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(currentSearchParams)
    })
    .then(async response => {
        // 응답 상태 확인
        if (!response.ok) {
            // 에러 응답도 JSON으로 파싱 시도
            let errorData = null;
            let errorMessage = `HTTP ${response.status} 에러가 발생했습니다.`;
            
            try {
                // 먼저 텍스트로 읽기 (response body는 한 번만 읽을 수 있음)
                const text = await response.text();
                // console.log('Error response text:', text);
                
                try {
                    // JSON 파싱 시도
                    errorData = JSON.parse(text);
                    // console.log('Parsed error data:', errorData);
                    // 실제 API 메시지를 우선적으로 사용
                    errorMessage = errorData.message || errorData.msg || errorMessage;
                } catch (parseError) {
                    // JSON 파싱 실패 시 텍스트를 메시지로 사용
                    // console.log('JSON parse failed, using text as message');
                    errorMessage = text || errorMessage;
                }
            } catch (e) {
                // 텍스트 읽기 실패
                // console.error('Failed to read error response:', e);
            }
            
            throw { 
                status: response.status, 
                data: errorData, 
                message: errorMessage 
            };
        }
        return response.json();
    })
    .then(data => {
        // 디버깅: 응답 데이터 로그
        // console.log('API Response:', data);
        if (data.success) {
            if (data.members && data.members.length > 0) {
                let html = '<div class="overflow-x-auto" style="margin-top: 0; padding-top: 0;"><table style="background: #fafafa; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px; width: 100%; table-layout: fixed; margin-top: 0;"><thead style="position: sticky; top: 0; z-index: 5;"><tr>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 60px;">번호</th>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 100px;">사용자코드</th>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 120px;">아이디</th>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 100px;">이름</th>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 100px;">부서</th>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 150px;">회사명</th>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 150px;">연락처</th>';
                html += '<th style="background: #f3f4f6; text-align: center; font-size: 11px; height: 20px; padding: 3px 8px; width: 100px;">작업</th>';
                html += '</tr></thead><tbody>';
                
                // 페이지 정보
                const pageInfo = data.page_info || {};
                const currentPage = parseInt(pageInfo.current_page || page || 1);
                const totalPages = parseInt(pageInfo.total_page || 1);
                const totalCount = parseInt(data.total_count || data.members.length);
                const displayArticle = parseInt(pageInfo.display_article || 20);
                const startIndex = (currentPage - 1) * displayArticle;
                
                // 등록된 c_code 목록 (서버에서 한 번에 조회한 결과)
                const registeredCCodes = data.registered_c_codes || [];
                const registeredCCodesSet = new Set(registeredCCodes); // 빠른 조회를 위해 Set 사용
                
                // comp_no 저장 (사용 버튼 클릭 시 필요)
                const compNoSelectForMember = document.getElementById('insung_comp_no_select');
                const compNo = compNoSelectForMember ? compNoSelectForMember.value : '';
                
                // 현재 페이지의 회원 데이터 저장 (일괄처리용)
                currentPageMembers = [];
                
                data.members.forEach((member, index) => {
                    const memberData = typeof member === 'object' ? member : JSON.parse(member);
                    // API 응답 필드명 매핑: API 문서 기준
                    // user_id, cust_name, dept_name, company_name, tel_no1, tel_no2, c_code, company_code, charge_name, use_state
                    const cCode = memberData.c_code || '-';
                    const userId = memberData.user_id || '';
                    const userName = memberData.cust_name || memberData.user_name || '-';
                    const deptName = memberData.dept_name || memberData.user_dept || '-';
                    const companyName = memberData.company_name || memberData.comp_name || '-';
                    const telNo1 = memberData.tel_no1 || memberData.user_tel1 || '-';
                    const telNo2 = memberData.tel_no2 || '';
                    const companyCode = memberData.company_code || compNo || '';
                    const chargeName = memberData.charge_name || '';
                    
                    // 아이디가 있는지 확인
                    const hasUserId = userId && userId.trim() !== '';
                    
                    // c_code가 DB에 등록되어 있는지 확인
                    const isRegistered = cCode && cCode !== '-' && registeredCCodesSet.has(cCode);
                    
                    // 아이디가 있고 아직 등록되지 않은 회원만 currentPageMembers에 저장 (일괄처리용)
                    if (hasUserId && !isRegistered) {
                        currentPageMembers.push({
                            c_code: cCode,
                            user_id: userId,
                            user_name: userName,
                            dept_name: deptName,
                            company_code: companyCode,
                            tel_no1: telNo1,
                            tel_no2: telNo2,
                            charge_name: chargeName
                        });
                    }
                    
                    // 짝수/홀수 행 배경색 적용 (style_guide.md 기준)
                    const rowBg = index % 2 === 0 ? '#fafafa' : '#f5f5f5';
                    
                    html += `<tr style="background: ${rowBg};" onmouseover="this.style.background='#f9fafb';" onmouseout="this.style.background='${rowBg}';">`;
                    html += `<td style="text-align: center; font-size: 12px; height: 20px; padding: 2px 8px; width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${totalCount - startIndex - index}</td>`;
                    html += `<td style="text-align: left; font-size: 12px; height: 20px; padding: 2px 8px; width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${cCode}</td>`;
                    html += `<td style="text-align: left; font-size: 12px; height: 20px; padding: 2px 8px; width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${userId || '-'}</td>`;
                    html += `<td style="text-align: left; font-size: 12px; height: 20px; padding: 2px 8px; width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${userName}</td>`;
                    html += `<td style="text-align: left; font-size: 12px; height: 20px; padding: 2px 8px; width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${deptName}</td>`;
                    html += `<td style="text-align: left; font-size: 12px; height: 20px; padding: 2px 8px; width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${companyName}</td>`;
                    html += `<td style="text-align: left; font-size: 12px; height: 20px; padding: 2px 8px; width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${telNo1}${telNo2 ? ' / ' + telNo2 : ''}</td>`;
                    html += `<td style="text-align: center; font-size: 12px; height: 20px; padding: 2px 8px; vertical-align: middle; width: 100px;">`;
                    if (isRegistered) {
                        // 이미 등록된 경우 "등록완료" 비활성화된 버튼 스타일로 표시
                        html += `<button disabled class="form-button" style="padding: 0px 8px; font-size: 11px; height: 20px; line-height: 20px; box-sizing: border-box; background: #e5e7eb !important; color: #111827 !important; border: 1px solid #d1d5db !important; cursor: not-allowed; opacity: 0.7; margin: 0 auto; display: block;">등록완료</button>`;
                    } else if (hasUserId) {
                        // 아이디가 있고 아직 등록되지 않은 경우 '사용' 버튼 표시 (리스트 높이와 동일하게 20px)
                        html += `<button onclick="useInsungMember('${cCode}', '${userId}', '${userName}', '${deptName}', '${companyCode}', '${telNo1}', '${telNo2 || ''}', '${chargeName}')" class="form-button form-button-primary" style="padding: 0px 8px; font-size: 11px; height: 20px; line-height: 20px; box-sizing: border-box; margin: 0 auto; display: block;">사용</button>`;
                    } else {
                        html += '<span style="display: block; text-align: center;">-</span>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                
                // 리스트만 resultDiv에 표시
                resultDiv.innerHTML = html;
                
                // 일괄처리 버튼과 페이징은 별도 영역에 표시
                const paginationDiv = document.getElementById('insungMemberPagination');
                let paginationHtml = '';
                
                // 일괄처리 버튼과 총 건수 텍스트를 같은 줄에 배치
                paginationHtml += `<div style="display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">`;
                
                // 일괄처리 버튼 추가 (아이디가 있는 회원이 있을 때만)
                if (currentPageMembers.length > 0) {
                    paginationHtml += `<button onclick="batchProcessCurrentPage()" class="form-button form-button-primary" style="padding: 6px 20px; font-size: 13px; height: auto;">일괄처리 (${currentPageMembers.length}건)</button>`;
                }
                
                // 총 건수 텍스트
                if (totalPages > 1) {
                    paginationHtml += `<span style="font-size: 13px; color: #111827;">총 ${totalCount.toLocaleString()}건 (${currentPage}/${totalPages} 페이지)</span>`;
                } else {
                    paginationHtml += `<span style="font-size: 13px; color: #111827;">총 ${totalCount.toLocaleString()}건</span>`;
                }
                
                paginationHtml += `</div>`;
                
                // 페이징 UI 추가 (리스트 템플릿 사용)
                if (totalPages > 1) {
                    paginationHtml += '<div class="list-pagination">';
                    paginationHtml += '<div class="pagination">';
                    
                    // 처음 버튼
                    if (currentPage > 1) {
                        paginationHtml += `<a href="javascript:void(0)" onclick="searchInsungMembers(null, 1)" class="nav-button">처음</a>`;
                    } else {
                        paginationHtml += '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">처음</span>';
                    }
                    
                    // 이전 버튼
                    if (currentPage > 1) {
                        paginationHtml += `<a href="javascript:void(0)" onclick="searchInsungMembers(null, ${currentPage - 1})" class="nav-button">이전</a>`;
                    } else {
                        paginationHtml += '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">이전</span>';
                    }
                    
                    // 페이지 번호 (최대 5개 표시, delivery/list.php와 동일한 로직)
                    const showPages = 5;
                    const halfPages = Math.floor(showPages / 2);
                    let startPage = Math.max(1, currentPage - halfPages);
                    let endPage = Math.min(totalPages, startPage + showPages - 1);
                    
                    // 실제 표시할 페이지 범위 재조정 (총 페이지가 5개 미만인 경우)
                    if (totalPages < showPages) {
                        startPage = 1;
                        endPage = totalPages;
                    } else if (endPage - startPage < showPages - 1) {
                        startPage = Math.max(1, endPage - showPages + 1);
                    }
                    
                    for (let i = startPage; i <= endPage; i++) {
                        const isActive = i === currentPage;
                        paginationHtml += `<a href="javascript:void(0)" onclick="searchInsungMembers(null, ${i})" class="page-number ${isActive ? 'active' : ''}">${i}</a>`;
                    }
                    
                    // 다음 버튼
                    if (currentPage < totalPages) {
                        paginationHtml += `<a href="javascript:void(0)" onclick="searchInsungMembers(null, ${currentPage + 1})" class="nav-button">다음</a>`;
                    } else {
                        paginationHtml += '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">다음</span>';
                    }
                    
                    // 마지막 버튼
                    if (currentPage < totalPages) {
                        paginationHtml += `<a href="javascript:void(0)" onclick="searchInsungMembers(null, ${totalPages})" class="nav-button">마지막</a>`;
                    } else {
                        paginationHtml += '<span class="nav-button" style="opacity: 0.5; cursor: not-allowed;">마지막</span>';
                    }
                    
                    paginationHtml += '</div>';
                    paginationHtml += '</div>';
                }
                
                if (paginationDiv) {
                    paginationDiv.innerHTML = paginationHtml;
                }
            } else {
                resultDiv.innerHTML = '<div class="text-center py-8 text-gray-900">검색 결과가 없습니다.</div>';
                const paginationDiv = document.getElementById('insungMemberPagination');
                if (paginationDiv) {
                    paginationDiv.innerHTML = '';
                }
            }
        } else {
            // 실제 API 메시지를 우선적으로 표시
            const errorMessage = data.message || data.msg || '알 수 없는 오류가 발생했습니다.';
            resultDiv.innerHTML = `<div class="text-center py-8 text-red-500">오류: ${errorMessage}</div>`;
            const paginationDiv = document.getElementById('insungMemberPagination');
            if (paginationDiv) {
                paginationDiv.innerHTML = '';
            }
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        // console.error('Error data:', error.data);
        // console.error('Error message:', error.message);
        // 실제 API 메시지를 우선적으로 표시
        const errorMessage = error.message || error.data?.message || error.data?.msg || '검색 중 오류가 발생했습니다.';
        // console.log('Final error message:', errorMessage);
        resultDiv.innerHTML = `<div class="text-center py-8 text-red-500">오류: ${errorMessage}</div>`;
        const paginationDiv = document.getElementById('insungMemberPagination');
        if (paginationDiv) {
            paginationDiv.innerHTML = '';
        }
    });
}

// 현재 페이지 일괄처리 (아이디가 있는 회원만)
function batchProcessCurrentPage() {
    if (!currentPageMembers || currentPageMembers.length === 0) {
        alert('처리할 회원이 없습니다.');
        return;
    }
    
    const count = currentPageMembers.length;
    if (!confirm(`현재 페이지에서 아이디가 있는 회원 ${count}건을 일괄 처리하시겠습니까?`)) {
        return;
    }
    
    // 일괄처리 시작
    const resultDiv = document.getElementById('insungMemberResult');
    const originalContent = resultDiv.innerHTML;
    
    // 로딩 표시
    resultDiv.innerHTML = `
        <div class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-900">일괄 처리 중... (0/${count}건)</p>
        </div>
    `;
    
    // 일괄처리 API 호출
    fetch('<?= base_url('insung/batchUseInsungMembers') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            members: currentPageMembers
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                // 실제 API 메시지를 우선적으로 사용
                const apiMessage = data.message || data.msg || `HTTP ${response.status} 에러가 발생했습니다.`;
                throw { status: response.status, data: data, message: apiMessage };
            }).catch(async () => {
                // JSON 파싱 실패 시 텍스트로 읽기 시도
                try {
                    const text = await response.text();
                    throw { status: response.status, message: text || `HTTP ${response.status} 에러가 발생했습니다.` };
                } catch (e) {
                    throw { status: response.status, message: `HTTP ${response.status} 에러가 발생했습니다.` };
                }
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`일괄 처리가 완료되었습니다.\n성공: ${data.success_count || count}건\n실패: ${data.fail_count || 0}건`);
            // 현재 페이지 다시 로드 (페이징 영역도 함께 업데이트됨)
            searchInsungMembers(null, currentSearchParams.page);
        } else {
            alert('오류: ' + (data.message || data.msg || '일괄 처리에 실패했습니다.'));
            resultDiv.innerHTML = originalContent;
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        const errorMessage = error.message || error.data?.message || error.data?.msg || '일괄 처리 중 오류가 발생했습니다.';
        alert('오류: ' + errorMessage);
        resultDiv.innerHTML = originalContent;
    });
}

// 인성회원 사용 버튼 클릭 (tbl_users_list에 insert duplicate update)
function useInsungMember(cCode, userId, userName, deptName, companyCode, telNo1, telNo2, chargeName) {
    if (!cCode || cCode === '-') {
        alert('사용자코드가 없습니다.');
        return;
    }
    
    if (!userId || userId.trim() === '') {
        alert('아이디가 없습니다.');
        return;
    }
    
    if (!confirm(`"${userName}" 회원을 시스템에 등록하시겠습니까?`)) {
        return;
    }
    
    const memberData = {
        user_ccode: cCode,
        user_id: userId,
        user_name: userName,
        user_dept: deptName || '',
        user_tel1: telNo1 || '',
        user_company: companyCode || '',
        user_type: '5' // 기본값
    };
    
    // telNo2가 있으면 user_tel2 필드에 저장 (필드가 있다고 가정)
    if (telNo2 && telNo2.trim() !== '') {
        memberData.user_tel2 = telNo2;
    }
    
    fetch('<?= base_url('insung/useInsungMember') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(memberData)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                // 실제 API 메시지를 우선적으로 사용
                const apiMessage = data.message || data.msg || `HTTP ${response.status} 에러가 발생했습니다.`;
                throw { status: response.status, data: data, message: apiMessage };
            }).catch(async () => {
                // JSON 파싱 실패 시 텍스트로 읽기 시도
                try {
                    const text = await response.text();
                    throw { status: response.status, message: text || `HTTP ${response.status} 에러가 발생했습니다.` };
                } catch (e) {
                    throw { status: response.status, message: `HTTP ${response.status} 에러가 발생했습니다.` };
                }
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('회원이 성공적으로 등록되었습니다.');
            // 필요시 리스트 새로고침
            // searchInsungMembers(null, currentSearchParams.page);
        } else {
            alert('오류: ' + (data.message || data.msg || '회원 등록에 실패했습니다.'));
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        const errorMessage = error.message || error.data?.message || error.data?.msg || '회원 등록 중 오류가 발생했습니다.';
        alert('오류: ' + errorMessage);
    });
}

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)

// 콜센터 선택 시 고객사 목록 동적 업데이트 (cc_code_select가 있는 경우에만)
const ccCodeSelect = document.getElementById('cc_code_select');
if (ccCodeSelect) {
    ccCodeSelect.addEventListener('change', function() {
        const ccCode = this.value;
        const compCodeSelect = document.getElementById('comp_code_select');
        
        if (!compCodeSelect) return;
        
        // 고객사 선택 초기화
        compCodeSelect.innerHTML = '<option value="all">전체</option>';
        
        if (ccCode === 'all') {
            // 전체 선택 시 모든 고객사 로드
            fetch(`<?= base_url('insung/getCompaniesByCcForSelect') ?>?cc_code=all`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.companies) {
                        data.companies.forEach(company => {
                            const option = document.createElement('option');
                            option.value = company.comp_code;
                            const userCount = company.user_count || 0;
                            option.textContent = (company.comp_name || company.comp_code) + ' (' + userCount.toLocaleString() + ')';
                            // API 정보를 data 속성으로 추가 (레이어팝업용이 아니므로 선택적)
                            compCodeSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    // console.error('Error:', error);
                });
        } else {
            // 특정 콜센터 선택 시 해당 콜센터의 고객사만 로드
            fetch(`<?= base_url('insung/getCompaniesByCcForSelect') ?>?cc_code=${encodeURIComponent(ccCode)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.companies) {
                        data.companies.forEach(company => {
                            const option = document.createElement('option');
                            option.value = company.comp_code;
                            const userCount = company.user_count || 0;
                            option.textContent = (company.comp_name || company.comp_code) + ' (' + userCount.toLocaleString() + ')';
                            // API 정보를 data 속성으로 추가 (레이어팝업용이 아니므로 선택적)
                            compCodeSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    // console.error('Error:', error);
                });
        }
    });
}
</script>
<?= $this->endSection() ?>

