<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 list-page-container">
<script>
// 거래처 목록 저장 (모달 검색용)
let companyListData = [];

// 거래처 검색 모달 열기
function openCompanySearchModal() {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }

    const modal = document.getElementById('companySearchModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        // 검색 입력창 포커스
        setTimeout(() => {
            const searchInput = document.getElementById('companySearchInput');
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            // 목록 초기 표시
            filterCompanyList('');
        }, 100);
    }
}

// 거래처 검색 모달 닫기
function closeCompanySearchModal() {
    const modal = document.getElementById('companySearchModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 거래처 목록 필터링
function filterCompanyList(searchText) {
    const listContainer = document.getElementById('companySearchList');
    if (!listContainer) return;

    const searchLower = searchText.toLowerCase().trim();

    // 전체(콜센터 설정) 옵션 + 필터링된 목록
    let html = '<div class="company-item" data-code="" onclick="selectCompanyFromModal(\'\', \'전체\')">전체</div>';

    let count = 0;
    companyListData.forEach(company => {
        const compName = company.comp_name || company.comp_code;
        const compCode = company.comp_code;

        // 검색어가 없거나 매치되면 표시
        if (!searchLower || compName.toLowerCase().includes(searchLower) || compCode.toLowerCase().includes(searchLower)) {
            html += `<div class="company-item" data-code="${escapeHtml(compCode)}" onclick="selectCompanyFromModal('${escapeHtml(compCode)}', '${escapeHtml(compName)}')">
                <span class="company-name">${escapeHtml(compName)}</span>
                <span class="company-code">${escapeHtml(compCode)}</span>
            </div>`;
            count++;
        }
    });

    if (count === 0 && searchLower) {
        html += '<div class="text-gray-500 text-center py-4">검색 결과가 없습니다</div>';
    }

    listContainer.innerHTML = html;

    // 검색 결과 수 표시
    const countEl = document.getElementById('companySearchCount');
    if (countEl) {
        countEl.textContent = searchLower ? `${count}건 검색됨` : `총 ${companyListData.length}건`;
    }
}

// 모달에서 거래처 선택
function selectCompanyFromModal(compCode, compName) {
    // 선택된 거래처 표시
    const displayEl = document.getElementById('selectedCompanyText');
    if (displayEl) {
        displayEl.textContent = compName || '전체';
    }

    // 히든 필드에 값 저장
    const hiddenInput = document.getElementById('selected_comp_code');
    if (hiddenInput) {
        hiddenInput.value = compCode;
    }

    // 모달 닫기
    closeCompanySearchModal();

    // 페이지 이동
    const ccCode = document.getElementById('cc_code_select').value;
    const url = new URL(window.location.href);

    if (ccCode) {
        url.searchParams.set('cc_code', ccCode);
    }

    if (compCode) {
        url.searchParams.set('comp_code', compCode);
    } else {
        url.searchParams.delete('comp_code');
    }

    window.location.href = url.toString();
}

// HTML 이스케이프
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 페이지 로드 후 거래처 목록 초기화 및 모달 이벤트 설정
document.addEventListener('DOMContentLoaded', function() {
    // URL 파라미터에서 코드 가져오기
    const urlParams = new URLSearchParams(window.location.search);
    const ccCode = urlParams.get('cc_code');
    const compCode = urlParams.get('comp_code');

    if (ccCode) {
        // 거래처 선택 영역 표시
        const companyContainer = document.getElementById('company_select_container');
        if (companyContainer) {
            companyContainer.style.display = 'flex';
        }

        // 서버에서 이미 거래처 목록이 렌더링되었는지 확인 (PHP에서 전달된 company_list 사용)
        <?php if (!empty($company_list)): ?>
        // 서버에서 렌더링된 거래처 목록을 companyListData에 저장
        companyListData = <?= json_encode($company_list) ?>;

        // 선택된 거래처 표시 업데이트
        <?php if (!empty($selected_comp_code)): ?>
        const selectedCompany = companyListData.find(c => c.comp_code === '<?= $selected_comp_code ?>');
        if (selectedCompany) {
            const displayEl = document.getElementById('selectedCompanyText');
            if (displayEl) {
                displayEl.textContent = selectedCompany.comp_name || selectedCompany.comp_code;
            }
        }
        // 배송사유 설정 영역 표시 및 불러오기
        showDeliveryReasonSetting('<?= $selected_comp_code ?>');
        <?php endif; ?>
        <?php else: ?>
        // 서버에서 로드되지 않음 - AJAX로 로드
        loadCompaniesByCc(ccCode, compCode);
        <?php endif; ?>

        // compCode가 있으면 배송사유 설정 영역 표시
        if (compCode) {
            const deliveryContainer = document.getElementById('delivery_reason_setting_container');
            if (deliveryContainer) {
                deliveryContainer.style.display = 'flex';
            }
        }
    }

    // 검색 모달 외부 클릭 시 닫기
    const companyModal = document.getElementById('companySearchModal');
    if (companyModal) {
        companyModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCompanySearchModal();
            }
        });
    }

    // 검색 입력 시 필터링
    const searchInput = document.getElementById('companySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterCompanyList(this.value);
        });

        // Enter 키 시 첫번째 결과 선택, Escape 키 시 모달 닫기
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const firstItem = document.querySelector('#companySearchList .company-item');
                if (firstItem) {
                    firstItem.click();
                }
            } else if (e.key === 'Escape') {
                closeCompanySearchModal();
            }
        });
    }
});

// 콜센터별 거래처 목록 로드 (AJAX) - 선택된 거래처 코드 전달
// openModal: true이면 로드 완료 후 거래처 검색 모달 자동 열기
function loadCompaniesByCc(ccCode, selectedCompCode = null, openModal = false) {
    if (!ccCode) {
        return;
    }

    fetch(`<?= base_url('admin/getCompaniesByCcForOrderType') ?>?cc_code=${encodeURIComponent(ccCode)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 건수가 다르면 동기화 확인 (모달 레이어팝업 사용)
            if (data.need_sync) {
                showConfirmModal(
                    '거래처 동기화',
                    `거래처 리스트를 동기화하시겠습니까?\n\nAPI: ${data.api_count}건 / DB: ${data.db_count}건`,
                    function() {
                        // 확인 - 동기화 실행
                        syncCompanies(ccCode, selectedCompCode, openModal);
                    },
                    function() {
                        // 취소 - DB 데이터로 표시
                        updateCompanySelect(data.companies, selectedCompCode, openModal);
                    }
                );
                return;
            }

            // 동기화 안 함 - DB 데이터로 표시
            updateCompanySelect(data.companies, selectedCompCode, openModal);
        }
    })
    .catch(error => {
        console.error('Error loading companies:', error);
        // 에러 시에도 모달 열기 옵션이 있으면 모달 열기
        if (openModal) {
            openCompanySearchModal();
        }
    });
}

// 진행률 모달 표시
function showProgressModal() {
    let modal = document.getElementById('syncProgressModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'syncProgressModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-[90%]">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">거래처 동기화</h3>
                <div class="mb-3">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span id="syncProgressMessage">준비 중...</span>
                        <span id="syncProgressPercent">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div id="syncProgressBar" class="bg-blue-500 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
                <div id="syncProgressDetail" class="text-xs text-gray-500 mt-2"></div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    modal.style.display = 'flex';
}

// 진행률 모달 업데이트
function updateProgressModal(percent, message, detail = '') {
    const bar = document.getElementById('syncProgressBar');
    const percentEl = document.getElementById('syncProgressPercent');
    const msgEl = document.getElementById('syncProgressMessage');
    const detailEl = document.getElementById('syncProgressDetail');

    if (bar) bar.style.width = `${percent}%`;
    if (percentEl) percentEl.textContent = `${percent}%`;
    if (msgEl) msgEl.textContent = message;
    if (detailEl) detailEl.textContent = detail;
}

// 진행률 모달 숨기기
function hideProgressModal() {
    const modal = document.getElementById('syncProgressModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 거래처 동기화 실행 (SSE 실시간 진행률)
function syncCompanies(ccCode, selectedCompCode = null, openModal = false) {
    // 진행률 모달 표시
    showProgressModal();
    updateProgressModal(0, '동기화 시작...', '');

    const displayEl = document.getElementById('selectedCompanyText');
    if (displayEl) {
        displayEl.textContent = '동기화 중...';
    }

    // EventSource로 SSE 연결
    const eventSource = new EventSource(`<?= base_url('admin/syncCompaniesWithProgress') ?>?cc_code=${encodeURIComponent(ccCode)}`);

    eventSource.addEventListener('start', function(e) {
        const data = JSON.parse(e.data);
        updateProgressModal(2, data.message, '');
    });

    eventSource.addEventListener('progress', function(e) {
        const data = JSON.parse(e.data);
        let detail = '';
        if (data.current !== undefined && data.total !== undefined) {
            detail = `처리: ${data.current} / ${data.total}건`;
        }
        if (data.page !== undefined && data.totalPage !== undefined) {
            detail += ` (${data.page}/${data.totalPage} 페이지)`;
        }
        updateProgressModal(data.percent, data.message, detail);
    });

    eventSource.addEventListener('complete', function(e) {
        eventSource.close();
        const data = JSON.parse(e.data);

        // 진행률 100%로 업데이트
        updateProgressModal(100, '완료!', '');

        // 약간의 딜레이 후 모달 닫기
        setTimeout(() => {
            hideProgressModal();

            if (data.success) {
                // 동기화 결과 표시
                if (data.stats) {
                    let msg = `총 ${data.stats.total}건 (신규: ${data.stats.inserted}건, 업데이트: ${data.stats.updated}건)`;
                    if (data.stats.deactivated > 0) {
                        msg += `\n거래종료: ${data.stats.deactivated}건`;
                    }
                    showSuccessModal('동기화 완료', msg);
                }
                // 거래처 목록 업데이트
                updateCompanySelect(data.companies, selectedCompCode, openModal);
            } else {
                showErrorModal('동기화 실패', data.message || '알 수 없는 오류');
            }
        }, 500);
    });

    eventSource.addEventListener('error', function(e) {
        eventSource.close();
        hideProgressModal();

        try {
            const data = JSON.parse(e.data);
            showErrorModal('동기화 오류', data.message || '동기화 중 오류가 발생했습니다.');
        } catch {
            showErrorModal('동기화 오류', '동기화 중 오류가 발생했습니다.');
        }

        if (displayEl) {
            displayEl.textContent = '전체';
        }
    });

    eventSource.onerror = function(e) {
        eventSource.close();
        hideProgressModal();
        showErrorModal('연결 오류', '서버와의 연결이 끊어졌습니다.');
        if (displayEl) {
            displayEl.textContent = '전체';
        }
    };
}

// 거래처 select 업데이트 (모달 검색용 데이터 저장)
function updateCompanySelect(companies, selectedCompCode = null, openModal = false) {
    // 거래처 목록 저장 (모달 검색용)
    companyListData = companies || [];

    // 거래처 선택 영역 표시
    const companyContainer = document.getElementById('company_select_container');
    if (companyContainer) {
        companyContainer.style.display = 'flex';
    }

    // 선택된 거래처 표시 업데이트
    if (selectedCompCode) {
        const selectedCompany = companyListData.find(c => c.comp_code === selectedCompCode);
        if (selectedCompany) {
            const displayEl = document.getElementById('selectedCompanyText');
            if (displayEl) {
                displayEl.textContent = selectedCompany.comp_name || selectedCompany.comp_code;
            }
            // 히든 필드에 값 저장
            const hiddenInput = document.getElementById('selected_comp_code');
            if (hiddenInput) {
                hiddenInput.value = selectedCompCode;
            }
            // 배송사유 설정 표시
            showDeliveryReasonSetting(selectedCompCode);
        }
    } else {
        const displayEl = document.getElementById('selectedCompanyText');
        if (displayEl) {
            displayEl.textContent = '전체';
        }
    }

    // 모달 자동 열기 옵션이 있으면 모달 열기
    if (openModal) {
        openCompanySearchModal();
    }
}
</script>

    <!-- 콜센터 및 거래처 선택 (daumdata 로그인 user_type=1인 경우만 표시) -->
    <?php if (isset($login_type) && $login_type === 'daumdata' && isset($user_type) && $user_type == '1' && !empty($cc_list)): ?>
    <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">콜센터 선택:</label>
                <select id="cc_code_select" class="search-filter-select" onchange="changeCcCode()">
                    <option value="">전체 (마스터 설정)</option>
                    <?php foreach ($cc_list as $cc): ?>
                    <option value="<?= htmlspecialchars($cc['cc_code']) ?>" <?= (isset($selected_cc_code) && $selected_cc_code === $cc['cc_code']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cc['cc_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2" id="company_select_container" style="display: <?= !empty($selected_cc_code) ? 'flex' : 'none' ?>;">
                <label class="text-sm font-medium text-gray-700">거래처 선택:</label>
                <input type="hidden" id="selected_comp_code" name="comp_code" value="<?= htmlspecialchars($selected_comp_code ?? '') ?>">
                <div class="flex items-center gap-1">
                    <span id="selectedCompanyDisplay"
                          onclick="openCompanySearchModal()"
                          class="text-sm text-blue-600 min-w-[120px] cursor-pointer hover:text-blue-800 hover:underline flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span id="selectedCompanyText">
                        <?php if (!empty($selected_comp_code) && !empty($company_list)): ?>
                            <?php
                            $selectedCompanyName = '전체';
                            foreach ($company_list as $company) {
                                if ($company['comp_code'] === $selected_comp_code) {
                                    $selectedCompanyName = $company['comp_name'] ?? $company['comp_code'];
                                    break;
                                }
                            }
                            echo htmlspecialchars($selectedCompanyName);
                            ?>
                        <?php else: ?>
                            전체
                        <?php endif; ?>
                        </span>
                    </span>
                </div>
            </div>
            <!-- 배송사유 사용 설정 (거래처 선택 시에만 표시) -->
            <div class="flex items-center gap-2" id="delivery_reason_setting_container" style="display: <?= !empty($selected_comp_code) ? 'flex' : 'none' ?>;">
                <label class="text-sm font-medium text-gray-700">배송사유 사용:</label>
                <select id="use_delivery_reason_select" class="search-filter-select" onchange="updateDeliveryReasonSetting()">
                    <option value="N">미사용</option>
                    <option value="Y">사용</option>
                </select>
                <span id="delivery_reason_save_status" class="text-xs text-green-600 hidden">저장됨</span>
            </div>
            <?php if (isset($selected_cc_code) && $selected_cc_code): ?>
            <span class="text-sm text-gray-600">
                현재 선택:
                <?php if (isset($selected_comp_code) && $selected_comp_code): ?>
                    거래처 (<?= htmlspecialchars($selected_comp_code) ?>)
                <?php else: ?>
                    콜센터 (<?= htmlspecialchars($selected_cc_code) ?>)
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 통계 카드 
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">총 주문유형</p>
                    <p class="text-2xl font-bold text-blue-900" id="total-count"><?= $stats['total'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">활성화된 유형</p>
                    <p class="text-2xl font-bold text-green-900" id="active-count"><?= $stats['active'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600">비활���화된 유형</p>
                    <p class="text-2xl font-bold text-gray-900" id="inactive-count"><?= $stats['inactive'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>
    //-->
    <?php
    // 데이터가 없으면 빈 배열로 초기화
    $service_types_grouped = $service_types_grouped ?? [];

    // 카테고리 순서 정의 (메일룸서비스 제외)
    $categoryOrder = ['퀵서비스', '연계배송서비스', '택배서비스', '우편서비스', '일반서비스', '생활서비스', '해외특송서비스'];
    $categoryLabels = [
        '퀵서비스' => '퀵서비스',
        'quick' => '퀵서비스',
        '연계배송서비스' => '연계배송서비스',
        'linked' => '연계배송서비스',
        '택배서비스' => '택배서비스',
        'parcel' => '택배서비스',
        '우편서비스' => '우편서비스',
        'postal' => '우편서비스',
        '일반서비스' => '일반서비스',
        'general' => '일반서비스',
        '생활서비스' => '생활서비스',
        'life' => '생활서비스',
        '해외특송서비스' => '해외특송서비스',
        'overseas' => '해외특송서비스',
        '메일룸서비스' => '메일룸서비스',
        'mailroom' => '메일룸서비스'
    ];

    // 메일룸은 service_types에서 제거되어 더 이상 $service_types_grouped에 포함되지 않음
    // 메일룸 권한은 $has_mailroom_permission 변수로 별도 관리됨
    ?>

    <!-- 주문유형 그리드 -->
    <div class="mb-6">
        <!-- 메인 서비스 그리드 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2" id="service-types-grid">
        <?php
        // 카테고리별로 그룹화된 데이터 정렬 (메일룸 제외)
        $sortedCategories = [];
        if (!empty($service_types_grouped)) {
            foreach ($categoryOrder as $cat) {
                foreach ($service_types_grouped as $category => $services) {
                    $label = $categoryLabels[$category] ?? $category;
                    if ($label === $cat && !isset($sortedCategories[$category])) {
                        $sortedCategories[$category] = $services;
                    }
                }
            }

            // 나머지 카테고리 추가
            foreach ($service_types_grouped as $category => $services) {
                if (!isset($sortedCategories[$category])) {
                    $sortedCategories[$category] = $services;
                }
            }
        }

        if (empty($sortedCategories)): ?>
        <div class="col-span-full text-center py-8 text-gray-500">
            등록된 주문유형이 없습니다. "새 주문유형 추가" 버튼을 클릭하여 추가하세요.
        </div>
        <?php else:
        foreach ($sortedCategories as $category => $services):
            $categoryLabel = $categoryLabels[$category] ?? $category;
        ?>
        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-2"><?= $categoryLabel ?></h3>
            <div class="space-y-1 sortable-service-list" data-category="<?= htmlspecialchars($category) ?>">
                <?php foreach ($services as $service): ?>
                <?php 
                // 콜센터 선택 시 마스터 비활성화 서비스 체크
                $isMasterDisabled = (isset($selected_cc_code) && $selected_cc_code && isset($service['master_is_active']) && !$service['master_is_active']);
                ?>
                <div class="sortable-service-item <?= $isMasterDisabled ? 'opacity-60' : 'hover:bg-gray-100' ?> py-1 px-2 rounded transition-colors"
                     style="display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; flex-wrap: nowrap !important;"
                     data-service-id="<?= $service['id'] ?>"
                     data-sort-order="<?= $service['sort_order'] ?? 0 ?>">
                    <div style="display: flex !important; align-items: center !important; flex: 1 !important; min-width: 0 !important;">
                        <svg class="w-4 h-4 text-gray-400 mr-2 drag-handle cursor-move flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" onclick="event.stopPropagation();">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                        </svg>
                        <span class="text-sm <?= $isMasterDisabled ? 'text-gray-400' : 'text-gray-600' ?> service-name-clickable flex-1 truncate"
                              data-service-id="<?= $service['id'] ?>"
                              data-service-name="<?= htmlspecialchars($service['service_name']) ?>"
                              data-service-category="<?= htmlspecialchars($service['service_category']) ?>"
                              style="cursor: pointer;"
                              onclick="openEditModal(<?= $service['id'] ?>, '<?= htmlspecialchars($service['service_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($service['service_category'], ENT_QUOTES) ?>', <?= (isset($service['is_external_link']) && $service['is_external_link'] == 1) ? 'true' : 'false' ?>, <?= !empty($service['external_url']) ? "'" . htmlspecialchars(addslashes($service['external_url']), ENT_QUOTES) . "'" : "''" ?>)">
                            <?= htmlspecialchars($service['service_name']) ?>
                            <?php if (!empty($service['is_external_link']) && $service['is_external_link'] == 1): ?>
                                <span class="text-xs text-blue-500 ml-1">(외부링크)</span>
                            <?php endif; ?>
                            <?php if ($isMasterDisabled): ?>
                                <svg class="w-4 h-4 text-red-500 ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="마스터에서 비활성화된 서비스입니다">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                </svg>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php
                    // 메일룸서비스는 오픈여부 토글 없이 계약여부만 표시
                    $isMailroomService = ($category === '메일룸서비스') || (isset($service['service_name']) && $service['service_name'] === '메일룸');
                    ?>
                    <?php if (!$isMailroomService): ?>
                    <!-- 오픈여부 토글 (파란색) - 메일룸 제외 -->
                    <label class="relative inline-flex items-center ml-2 <?= $isMasterDisabled ? 'cursor-not-allowed' : 'cursor-pointer' ?>" style="flex-shrink: 0 !important;" onclick="<?= $isMasterDisabled ? 'return false;' : 'event.stopPropagation();' ?>" title="오픈여부">
                        <input type="checkbox"
                               class="sr-only peer service-status-toggle"
                               data-service-id="<?= $service['id'] ?>"
                               <?= (isset($service['is_enabled']) && $service['is_enabled']) || (!isset($service['is_enabled']) && isset($service['is_active']) && $service['is_active'] == 1) ? 'checked' : '' ?>
                               <?= $isMasterDisabled ? 'disabled title="마스터에서 비활성화된 서비스는 변경할 수 없습니다"' : '' ?>>
                        <div class="w-11 h-6 <?= $isMasterDisabled ? 'bg-gray-100 opacity-50' : 'bg-gray-200' ?> peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all <?= $isMasterDisabled ? '' : 'peer-checked:bg-blue-600' ?>"></div>
                    </label>
                    <?php endif; ?>
                    <?php if (!empty($show_contract_settings) || $isMailroomService): ?>
                    <!-- 계약여부 토글 (디폴트=계약/녹색, 체크=미계약/빨강) -->
                    <label class="relative inline-flex items-center ml-2 cursor-pointer" style="flex-shrink: 0 !important;" onclick="event.stopPropagation();" title="계약여부 (빨강=미계약)">
                        <input type="checkbox"
                               class="sr-only peer contract-status-toggle"
                               data-service-id="<?= $service['id'] ?>"
                               <?= (isset($service['is_uncontracted']) && $service['is_uncontracted']) ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-green-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500 peer-checked:ring-red-300"></div>
                    </label>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        endforeach;
        endif;
        ?>
        </div>
    </div>
    <!-- // 주문유형 그리드 끝 -->

    <!-- 액션 버튼들 (한 줄 배치) -->
    <div class="flex items-center justify-between gap-3 flex-nowrap">
        <!-- 새 주문유형 추가 버튼 (왼쪽) -->
        <button onclick="openCreateModal()" class="bg-green-600 text-white px-3 py-1.5 rounded text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 whitespace-nowrap flex-shrink-0">
            새 주문유형 추가
        </button>

        <!-- 설정 패널들 + 저장 버튼 (오른쪽) -->
        <div class="flex items-center gap-2 flex-nowrap">
            <?php if (!empty($show_contract_settings)): ?>
            <!-- 메일룸권한 사용 패널 (빨간색) -->
            <div class="flex items-center gap-2 px-2 py-1 bg-red-50 rounded border border-red-300 flex-nowrap flex-shrink-0">
                <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <span class="text-xs font-semibold text-red-700 whitespace-nowrap">메일룸권한 사용</span>
                <label class="relative inline-flex items-center cursor-pointer" onclick="event.stopPropagation();" title="메일룸 사용 권한">
                    <input type="checkbox"
                           class="sr-only peer mailroom-permission-toggle"
                           <?= !empty($has_mailroom_permission) ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500"></div>
                </label>
            </div>
            <?php endif; ?>

            <!-- 오픈여부 설정 패널 (파란색) -->
            <div class="flex items-center gap-2 px-2 py-1 bg-blue-50 rounded border border-blue-200 flex-nowrap flex-shrink-0">
                <span class="text-xs font-semibold text-blue-700 whitespace-nowrap">오픈여부</span>
                <button onclick="activateAll()" class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700 whitespace-nowrap">전체 활성화</button>
                <button onclick="deactivateAll()" class="bg-gray-600 text-white px-2 py-1 rounded text-xs hover:bg-gray-700 whitespace-nowrap">전체 비활성화</button>
            </div>

            <?php if (!empty($show_contract_settings)): ?>
            <!-- 계약여부 설정 패널 (녹색=계약, 빨강=미계약) -->
            <div class="flex items-center gap-2 px-2 py-1 bg-gray-50 rounded border border-gray-200 flex-nowrap flex-shrink-0">
                <span class="text-xs font-semibold text-gray-700 whitespace-nowrap">계약여부</span>
                <button onclick="activateAllContracts()" class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700 whitespace-nowrap">전체 계약</button>
                <button onclick="deactivateAllContracts()" class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700 whitespace-nowrap">전체 미계약</button>
            </div>
            <?php endif; ?>

            <!-- 통합 저장 버튼 -->
            <button onclick="saveAllSettings()" class="bg-indigo-600 text-white px-3 py-1.5 rounded text-sm font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 whitespace-nowrap flex-shrink-0">
                설정 저장
            </button>
        </div>
    </div>
    </div>

<!-- 새 주문유형 추가 레이어 팝업 -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">새 주문유형 추가</h3>
            <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
                </div>
        
        <form id="createServiceForm" onsubmit="createServiceType(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">주문 그룹 유형</label>
                <select id="create_category" name="service_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">선택하세요</option>
                    <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="__new__">+ 새 그룹 추가</option>
                </select>
                </div>
            
            <div id="new-category-input" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">새 그룹명</label>
                <input type="text" id="new_category" name="new_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="새 그룹명을 입력하세요">
                </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">서비스명 <span class="text-red-500">*</span></label>
                <input type="text" id="create_service_name" name="service_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="서비스명을 입력하세요" required>
            </div>
            
            <div class="mb-4">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="create_is_external_link" name="is_external_link" class="sr-only peer" onchange="toggleExternalUrlInput('create')">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">외부 링크 서비스</span>
                </label>
            </div>
            
            <div id="create-external-url-container" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">외부 링크 URL <span class="text-red-500">*</span></label>
                <input type="url" id="create_external_url" name="external_url" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://example.com" pattern="https?://.+">
                <p class="mt-1 text-xs text-gray-500">외부 사이트의 전체 URL을 입력하세요 (http:// 또는 https:// 포함)</p>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeCreateModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">취소</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">저장</button>
                </div>
        </form>
            </div>
        </div>

<!-- 서비스명 수정 레이어 팝업 -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">서비스명 수정</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
                </div>
        
        <form id="editServiceForm" onsubmit="updateServiceType(event)">
            <input type="hidden" id="edit_service_id" name="service_id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">주문 그룹 유형</label>
                <select id="edit_category" name="service_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">선택하세요</option>
                    <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="__new__">+ 새 그룹 추가</option>
                </select>
                </div>
            
            <div id="edit-new-category-input" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">새 그룹명</label>
                <input type="text" id="edit_new_category" name="new_category" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="새 그룹명을 입력하세요">
                </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">서비스명 <span class="text-red-500">*</span></label>
                <input type="text" id="edit_service_name" name="service_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="서비스명을 입력하세요" required>
            </div>
            
            <div class="mb-4">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="edit_is_external_link" name="is_external_link" class="sr-only peer" onchange="toggleExternalUrlInput('edit')">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">외부 링크 서비스</span>
                </label>
            </div>
            
            <div id="edit-external-url-container" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">외부 링크 URL <span class="text-red-500">*</span></label>
                <input type="url" id="edit_external_url" name="external_url" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://example.com" pattern="https?://.+">
                <p class="mt-1 text-xs text-gray-500">외부 사이트의 전체 URL을 입력하세요 (http:// 또는 https:// 포함)</p>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">취소</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">저장</button>
                </div>
        </form>
        </div>
    </div>

<!-- 거래처 검색 모달 -->
<div id="companySearchModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4" onclick="event.stopPropagation();">
        <div class="flex justify-between items-center px-4 py-3 bg-gray-50 border-b border-gray-200 rounded-t-lg">
            <div>
                <h3 class="text-sm font-medium text-gray-800">거래처 선택</h3>
                <p class="text-xs text-gray-500 mt-0.5">검색하여 선택하세요</p>
            </div>
            <button onclick="closeCompanySearchModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-3">
            <div class="relative mb-2">
                <input type="text" id="companySearchInput" class="w-full border border-gray-300 rounded px-2 py-1.5 pr-8 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="거래처명 또는 코드 검색...">
                <svg class="w-4 h-4 text-gray-400 absolute right-2 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <div id="companySearchList" class="max-h-72 overflow-y-auto border border-gray-200 rounded">
                <!-- 거래처 목록이 여기에 동적으로 생성됨 -->
            </div>
            <div class="text-xs text-blue-600 mt-2" id="companySearchCount">검색 결과</div>
        </div>
    </div>
</div>

<!-- Sortable.js 라이브러리 -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// 상태 변경 추적을 위한 변수
let statusChanges = {};

// 새 주문유형 추가 모달 열기
function openCreateModal() {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('create_category').value = '';
    document.getElementById('new_category').value = '';
    document.getElementById('create_service_name').value = '';
    document.getElementById('new-category-input').classList.add('hidden');
    
    // 외부 링크 필드 초기화
    document.getElementById('create_is_external_link').checked = false;
    document.getElementById('create_external_url').value = '';
    document.getElementById('create-external-url-container').classList.add('hidden');
    document.getElementById('create_external_url').required = false;
}

// 새 주문유형 추가 모달 닫기
function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 외부 링크 URL 입력 필드 표시/숨김
function toggleExternalUrlInput(mode) {
    const checkbox = document.getElementById(mode + '_is_external_link');
    const container = document.getElementById(mode + '-external-url-container');
    const urlInput = document.getElementById(mode + '_external_url');
    
    if (checkbox.checked) {
        container.classList.remove('hidden');
        urlInput.required = true;
    } else {
        container.classList.add('hidden');
        urlInput.required = false;
        urlInput.value = '';
    }
}

// 서비스명 수정 모달 열기
function openEditModal(serviceId, serviceName, serviceCategory, isExternalLink = false, externalUrl = '') {
    // 레이어 팝업이 열릴 때 사이드바 처리
    if (typeof window.hideSidebarForModal === 'function') {
        window.hideSidebarForModal();
    }
    if (typeof window.lowerSidebarZIndex === 'function') {
        window.lowerSidebarZIndex();
    }
    
    // 먼저 모든 필드 초기화
    document.getElementById('edit_service_id').value = '';
    document.getElementById('edit_service_name').value = '';
    document.getElementById('edit_category').value = '';
    document.getElementById('edit_new_category').value = '';
    document.getElementById('edit-new-category-input').classList.add('hidden');
    document.getElementById('edit_is_external_link').checked = false;
    document.getElementById('edit_external_url').value = '';
    document.getElementById('edit-external-url-container').classList.add('hidden');
    document.getElementById('edit_external_url').required = false;
    
    // 이제 실제 값 설정
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('edit_service_id').value = serviceId || '';
    document.getElementById('edit_service_name').value = serviceName || '';
    document.getElementById('edit_category').value = serviceCategory || '';
    
    // 외부 링크 필드 설정 (명시적으로 boolean 변환)
    // isExternalLink가 문자열 'true', boolean true, 숫자 1, 문자열 '1' 중 하나면 true
    let isExternal = false;
    if (typeof isExternalLink !== 'undefined' && isExternalLink !== null) {
        isExternal = (isExternalLink === true || isExternalLink === 1 || isExternalLink === '1' || isExternalLink === 'true');
    }
    
    // externalUrl이 유효한 문자열인 경우에만 사용
    let externalUrlValue = '';
    if (externalUrl && typeof externalUrl === 'string' && externalUrl.trim() !== '' && externalUrl !== 'null' && externalUrl !== 'undefined') {
        externalUrlValue = externalUrl.trim();
    }
    
    // 체크박스 상태 설정
    document.getElementById('edit_is_external_link').checked = isExternal;
    
    // URL 필드 설정
    if (isExternal) {
        document.getElementById('edit_external_url').value = externalUrlValue || '';
        document.getElementById('edit-external-url-container').classList.remove('hidden');
        document.getElementById('edit_external_url').required = true;
    } else {
        document.getElementById('edit_external_url').value = ''; // 외부 링크가 아니면 URL도 초기화
        document.getElementById('edit-external-url-container').classList.add('hidden');
        document.getElementById('edit_external_url').required = false;
    }
}

// 서비스명 수정 모달 닫기
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    
    // 모든 필드 초기화
    document.getElementById('edit_service_id').value = '';
    document.getElementById('edit_service_name').value = '';
    document.getElementById('edit_category').value = '';
    document.getElementById('edit_new_category').value = '';
    document.getElementById('edit-new-category-input').classList.add('hidden');
    document.getElementById('edit_is_external_link').checked = false;
    document.getElementById('edit_external_url').value = '';
    document.getElementById('edit-external-url-container').classList.add('hidden');
    document.getElementById('edit_external_url').required = false;
    
    // 레이어 팝업이 닫힐 때 사이드바 z-index 복원
    if (typeof window.restoreSidebarZIndex === 'function') {
        window.restoreSidebarZIndex();
    }
}

// 새 그룹 추가 옵션 선택 시 입력 필드 표시
document.getElementById('create_category').addEventListener('change', function() {
    if (this.value === '__new__') {
        document.getElementById('new-category-input').classList.remove('hidden');
        document.getElementById('new_category').required = true;
    } else {
        document.getElementById('new-category-input').classList.add('hidden');
        document.getElementById('new_category').required = false;
    }
});

document.getElementById('edit_category').addEventListener('change', function() {
    if (this.value === '__new__') {
        document.getElementById('edit-new-category-input').classList.remove('hidden');
        document.getElementById('edit_new_category').required = true;
    } else {
        document.getElementById('edit-new-category-input').classList.add('hidden');
        document.getElementById('edit_new_category').required = false;
    }
});

// 새 서비스 타입 생성
function createServiceType(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('createServiceForm'));
    
    fetch('<?= base_url('admin/createServiceType') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '서비스 타입 생성에 실패했습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('서비스 타입 생성 중 오류가 발생했습니다.');
    });
}

// 서비스 타입 수정
function updateServiceType(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('editServiceForm'));
    
    fetch('<?= base_url('admin/updateServiceType') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || '서비스 타입 수정에 실패했습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('서비스 타입 수정 중 오류가 발생했습니다.');
    });
}

// 전체 활성화 (UI만 변경)
function activateAll() {
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        toggle.checked = true;
    });
    // console.log('모든 서비스가 활성화 상태로 변경되었습니다. (설정 저장을 눌러주세요.)');
}

// 전체 비활성화 (UI만 변경)
function deactivateAll() {
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        toggle.checked = false;
    });
}

// 계약 전체 활성화 (UI만 변경) - 미체크=계약이므로 false
function activateAllContracts() {
    document.querySelectorAll('.contract-status-toggle').forEach(toggle => {
        toggle.checked = false;  // 미체크 = 계약(녹색)
    });
}

// 계약 전체 비활성화 (UI만 변경) - 체크=미계약이므로 true
function deactivateAllContracts() {
    document.querySelectorAll('.contract-status-toggle').forEach(toggle => {
        toggle.checked = true;  // 체크 = 미계약(빨강)
    });
}

// 계약여부 설정 저장
function saveContractSettings() {
    const compCode = document.getElementById('selected_comp_code')?.value;
    const ccCode = document.getElementById('cc_code_select')?.value;

    if (!compCode) {
        showErrorModal('저장 오류', '거래처를 선택해주세요.');
        return;
    }

    // 계약 상태 수집
    const contracts = [];
    // 메인 그리드 계약 토글 (체크 반대: 미체크=계약, 체크=미계약)
    document.querySelectorAll('.contract-status-toggle').forEach(toggle => {
        contracts.push({
            service_type_id: toggle.dataset.serviceId,
            is_uncontracted: toggle.checked  // 체크=미계약=1
        });
    });
    // 메일룸서비스 권한 토글 (원래대로: 체크=권한있음)
    document.querySelectorAll('.mailroom-contract-toggle').forEach(toggle => {
        contracts.push({
            service_type_id: toggle.dataset.serviceId,
            is_uncontracted: !toggle.checked  // 체크=권한있음=0
        });
    });

    // 저장 요청
    fetch('<?= base_url('admin/batchUpdateContractStatus') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            comp_code: compCode,
            cc_code: ccCode,
            contracts: contracts
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal('저장 완료', '계약여부 설정이 저장되었습니다.');
        } else {
            showErrorModal('저장 실패', data.message || '알 수 없는 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('저장 오류', '계약여부 저장 중 오류가 발생했습니다.');
    });
}

// 통합 저장 (오픈여부 + 계약여부)
async function saveAllSettings() {
    const compCode = document.getElementById('selected_comp_code')?.value;
    const ccCode = document.getElementById('cc_code_select')?.value;

    let hasError = false;
    let savedCount = 0;

    // 1. 오픈여부 저장
    const statusUpdates = [];
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        const serviceId = toggle.dataset.serviceId;
        const isActive = toggle.checked ? 1 : 0;
        statusUpdates.push({
            service_id: serviceId,
            is_active: isActive
        });
    });

    if (statusUpdates.length > 0) {
        const formData = new FormData();
        formData.append('status_updates', JSON.stringify(statusUpdates));

        if (ccCode) {
            formData.append('cc_code', ccCode);
        }
        if (compCode) {
            formData.append('comp_code', compCode);
        }

        try {
            const response = await fetch('<?= base_url('admin/batchUpdateServiceStatus') ?>', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                savedCount++;
            } else {
                hasError = true;
                console.error('오픈여부 저장 실패:', data.message);
            }
        } catch (error) {
            hasError = true;
            console.error('오픈여부 저장 오류:', error);
        }
    }

    // 2. 계약여부 저장 (거래처가 선택된 경우에만)
    if (compCode) {
        const contracts = [];
        // 메인 그리드 계약 토글 (체크 반대: 미체크=계약, 체크=미계약)
        document.querySelectorAll('.contract-status-toggle').forEach(toggle => {
            contracts.push({
                service_type_id: toggle.dataset.serviceId,
                is_uncontracted: toggle.checked  // 체크=미계약=1
            });
        });

        // 메일룸 권한 (별도 파라미터로 전송, service_type_id 없음)
        let mailroomPermission = null;
        const mailroomToggle = document.querySelector('.mailroom-permission-toggle');
        if (mailroomToggle) {
            mailroomPermission = mailroomToggle.checked; // 체크=권한있음=true
        }

        try {
            const response = await fetch('<?= base_url('admin/batchUpdateContractStatus') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    comp_code: compCode,
                    cc_code: ccCode,
                    contracts: contracts,
                    mailroom_permission: mailroomPermission
                })
            });
            const data = await response.json();
            if (data.success) {
                savedCount++;
            } else {
                hasError = true;
                console.error('계약여부 저장 실패:', data.message);
            }
        } catch (error) {
            hasError = true;
            console.error('계약여부 저장 오류:', error);
        }
    }

    // 결과 표시
    if (hasError) {
        showErrorModal('저장 오류', '일부 설정 저장에 실패했습니다. 다시 시도해주세요.');
    } else if (savedCount > 0) {
        showSuccessModal('저장 완료', '설정이 저장되었습니다.');
    } else {
        alert('저장할 설정이 없습니다.');
    }
}

// 콜센터 선택 변경
function changeCcCode() {
    const ccCode = document.getElementById('cc_code_select').value;
    const url = new URL(window.location.href);

    // 거래처 선택 초기화
    companyListData = [];
    const displayEl = document.getElementById('selectedCompanyText');
    if (displayEl) {
        displayEl.textContent = '전체';
    }
    const hiddenInput = document.getElementById('selected_comp_code');
    if (hiddenInput) {
        hiddenInput.value = '';
    }

    if (ccCode) {
        // 콜센터가 선택되면 거래처 목록 로드 (API 동기화 확인 포함) 후 모달 열기
        document.getElementById('company_select_container').style.display = 'flex';
        // loadCompaniesByCc의 세번째 파라미터 true: 로드 완료 후 모달 자동 열기
        loadCompaniesByCc(ccCode, null, true);
    } else {
        // 콜센터 선택 해제 시 마스터 설정으로 이동
        document.getElementById('company_select_container').style.display = 'none';
        url.searchParams.delete('cc_code');
        url.searchParams.delete('comp_code');
        window.location.href = url.toString();
    }
}

// 거래처 선택 변경 (더 이상 사용되지 않음 - 모달에서 직접 처리)

// 배송사유 설정 영역 표시 및 현재 설정 불러오기
function showDeliveryReasonSetting(compCode) {
    const container = document.getElementById('delivery_reason_setting_container');
    if (container) {
        container.style.display = 'flex';
    }

    // 현재 설정 불러오기
    fetch(`<?= base_url('admin/getCompanyDeliveryReasonSetting') ?>?comp_code=${encodeURIComponent(compCode)}`, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('use_delivery_reason_select');
            if (select) {
                select.value = data.use_delivery_reason || 'N';
            }
        }
    })
    .catch(error => console.error('Error loading delivery reason setting:', error));
}

// 배송사유 설정 영역 숨기기
function hideDeliveryReasonSetting() {
    const container = document.getElementById('delivery_reason_setting_container');
    if (container) {
        container.style.display = 'none';
    }
}

// 배송사유 사용 설정 업데이트
function updateDeliveryReasonSetting() {
    // URL 파라미터에서 comp_code 가져오기 (selectbox보다 우선)
    const urlParams = new URLSearchParams(window.location.search);
    let compCode = urlParams.get('comp_code');

    // URL에 없으면 selectbox에서 가져오기
    if (!compCode) {
        const hiddenInput = document.getElementById('selected_comp_code');
        compCode = hiddenInput ? hiddenInput.value : '';
    }

    const useDeliveryReason = document.getElementById('use_delivery_reason_select').value;

    if (!compCode) {
        alert('거래처를 먼저 선택해주세요.');
        return;
    }

    const formData = new FormData();
    formData.append('comp_code', compCode);
    formData.append('use_delivery_reason', useDeliveryReason);

    fetch('<?= base_url("admin/updateCompanyDeliveryReasonSetting") ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        const statusEl = document.getElementById('delivery_reason_save_status');
        if (data.success) {
            if (statusEl) {
                statusEl.classList.remove('hidden');
                setTimeout(() => statusEl.classList.add('hidden'), 2000);
            }
        } else {
            alert(data.message || '설정 저장에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('설정 저장 중 오류가 발생했습��다.');
    });
}

// 콜센터별 거래처 목록 로드 (AJAX)

// 설정 저장 (일괄 상태 업데이트)
function saveSettings() {
    // 모든 토글 상태 수집
    const statusUpdates = [];
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        const serviceId = toggle.dataset.serviceId;
        const isActive = toggle.checked ? 1 : 0;
        statusUpdates.push({
            service_id: serviceId,
            is_active: isActive
        });
    });
    
    if (statusUpdates.length === 0) {
        alert('저장할 설정이 없습니다.');
        return;
    }
    
    // console.log('저장할 데이터:', statusUpdates);
    
    const formData = new FormData();
    formData.append('status_updates', JSON.stringify(statusUpdates));
    
    // 콜센터 코드가 선택되어 있으면 함께 전송
    const ccCodeSelect = document.getElementById('cc_code_select');
    if (ccCodeSelect && ccCodeSelect.value) {
        formData.append('cc_code', ccCodeSelect.value);
        // console.log('콜센터 코드:', ccCodeSelect.value);
    }
    
    // 거래처 코드가 선택되어 있으면 함께 전송
    const compCodeHidden = document.getElementById('selected_comp_code');
    if (compCodeHidden && compCodeHidden.value) {
        formData.append('comp_code', compCodeHidden.value);
        // console.log('거래처 코드:', compCodeSelect.value);
    }
    
    fetch('<?= base_url('admin/batchUpdateServiceStatus') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // console.log('응답 상태:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        // console.log('서버 응답:', data);
        if (data.success) {
            alert(data.message);
            // 현재 URL 파라미터 유지하면서 리로드
            const url = new URL(window.location.href);
            window.location.href = url.toString();
        } else {
            alert(data.message || '설정 저장에 실패했습니다.');
        }
    })
    .catch(error => {
        // console.error('Error:', error);
        alert('설정 저장 중 오류가 발생했습니다: ' + error.message);
    });
}

// 모달 외부 클릭 시 닫기 기능 제거 (X 버튼만으로 닫기)
// 외부 클릭으로 인한 실수 방지를 위해 제거

// 드래그 앤 드롭으로 순서 변경 초기화
function initSortable() {
    // Sortable.js가 로드되었는지 확인
    if (typeof Sortable === 'undefined') {
        // console.error('Sortable.js가 로드되지 않았습니다.');
        setTimeout(initSortable, 100); // 재시도
        return;
    }
    
    const sortableLists = document.querySelectorAll('.sortable-service-list');
    
    if (sortableLists.length === 0) {
        // console.warn('정렬 가능한 목록을 찾을 수 없습니다.');
        return;
    }
    
    sortableLists.forEach(list => {
        new Sortable(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            forceFallback: false,
            onEnd: function(evt) {
                // 순서 변경 후 새로운 순서 저장
                updateServiceOrder(list);
            }
        });
    });
    
    // console.log('Sortable 초기화 완료:', sortableLists.length, '개 목록');
}

// 페이지 로드 완료 후 초기화
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initSortable();
        // 참고: 거래처 목록 로드는 상단 DOMContentLoaded 이벤트에서 처리됨
    });
} else {
    // 이미 로드 완료된 경우 즉시 실행
    initSortable();
    // 참고: 거래��� 목록 로드는 상단 DOMContentLoaded 이벤트에서 처리됨
}

// 서비스 순서 업데이트
function updateServiceOrder(listElement) {
    const items = listElement.querySelectorAll('.sortable-service-item');
    const sortUpdates = [];
    
    items.forEach((item, index) => {
        const serviceId = item.dataset.serviceId;
        const category = listElement.dataset.category;
        sortUpdates.push({
            service_id: serviceId,
            sort_order: index + 1,
            category: category
        });
    });
    
    if (sortUpdates.length === 0) {
        return;
    }
    
    // 서버에 순서 업데이트 요청
    const formData = new FormData();
    formData.append('sort_updates', JSON.stringify(sortUpdates));
    
    fetch('<?= base_url('admin/updateServiceSortOrder') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // console.log('순서가 저장되었습니다.', data);
        } else {
            // console.error('순서 저장 실패:', data.message);
            alert('순서 저장에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        // console.error('순서 저장 중 오류:', error);
        alert('순서 저장 중 오류가 발생했습니다. 콘솔을 확인해주세요.');
    });
}
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
    background: #f3f4f6;
}

.sortable-chosen {
    cursor: grabbing;
}

.sortable-drag {
    opacity: 0.8;
}

/* 거래처 검색 모달 스타일 */
.company-item {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    color: #374151;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.15s ease;
}

.company-item:last-child {
    border-bottom: none;
}

.company-item:hover {
    background-color: #eff6ff;
}

.company-item[data-code=""] {
    font-weight: 500;
    color: #6b7280;
    background-color: #f9fafb;
}

.company-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.company-name {
    flex: 1;
}

.company-code {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-left: 0.5rem;
    font-family: monospace;
}

.company-item:hover .company-code {
    color: #6b7280;
}

#companySearchList::-webkit-scrollbar {
    width: 6px;
}

#companySearchList::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#companySearchList::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#companySearchList::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<?= $this->include('forms/alert-modal') ?>

<?= $this->endSection() ?>
