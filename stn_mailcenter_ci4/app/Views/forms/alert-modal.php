<?php
// 범용 Alert/Confirm 레이어팝업 컴포넌트
// 사용법: $this->include('forms/alert-modal')
// JavaScript API:
//   showAlertModal(title, message, type) - 경고/정보 메시지 표시
//   showConfirmModal(title, message, onConfirm, onCancel) - 확인/취소 선택
//   showSuccessModal(title, message) - 성공 메시지 표시
//   showErrorModal(title, message) - 에러 메시지 표시
?>

<!-- Alert/Confirm 레이어팝업 -->
<div id="alertModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center" style="z-index: 9999 !important;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <!-- 헤더 -->
        <div id="alertModalHeader" class="flex justify-between items-center p-4 border-b border-gray-200 rounded-t-lg">
            <div class="flex items-center space-x-2">
                <!-- 아이콘 (동적으로 변경) -->
                <div id="alertModalIcon"></div>
                <h3 id="alertModalTitle" class="text-lg font-semibold"></h3>
            </div>
            <button type="button" id="closeAlertModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- 본문 -->
        <div class="p-6">
            <p id="alertModalMessage" class="text-sm text-gray-700 whitespace-pre-line"></p>
        </div>

        <!-- 푸터 -->
        <div id="alertModalFooter" class="flex justify-end p-4 border-t border-gray-200 bg-gray-50 rounded-b-lg gap-3">
            <button type="button" id="alertModalCancelBtn" class="hidden px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded text-sm font-medium transition-colors">
                취소
            </button>
            <button type="button" id="alertModalConfirmBtn" class="px-6 py-2 rounded text-sm font-medium transition-colors">
                확인
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    // 모달 관련 변수
    let confirmCallback = null;
    let cancelCallback = null;

    // 아이콘 템플릿
    const icons = {
        info: `<svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>`,
        success: `<svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>`,
        error: `<svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>`,
        warning: `<svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                  </svg>`,
        confirm: `<svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>`
    };

    // 스타일 설정
    const styles = {
        info: {
            header: 'bg-blue-50',
            title: 'text-blue-800',
            button: 'bg-blue-600 hover:bg-blue-700 text-white'
        },
        success: {
            header: 'bg-green-50',
            title: 'text-green-800',
            button: 'bg-green-600 hover:bg-green-700 text-white'
        },
        error: {
            header: 'bg-red-50',
            title: 'text-red-800',
            button: 'bg-red-600 hover:bg-red-700 text-white'
        },
        warning: {
            header: 'bg-yellow-50',
            title: 'text-yellow-800',
            button: 'bg-yellow-600 hover:bg-yellow-700 text-white'
        },
        confirm: {
            header: 'bg-blue-50',
            title: 'text-blue-800',
            button: 'bg-blue-600 hover:bg-blue-700 text-white'
        }
    };

    // 모달 열기
    function openModal(title, message, type, showCancel) {
        const modal = document.getElementById('alertModal');
        const header = document.getElementById('alertModalHeader');
        const iconEl = document.getElementById('alertModalIcon');
        const titleEl = document.getElementById('alertModalTitle');
        const messageEl = document.getElementById('alertModalMessage');
        const confirmBtn = document.getElementById('alertModalConfirmBtn');
        const cancelBtn = document.getElementById('alertModalCancelBtn');

        // 스타일 초기화
        header.className = 'flex justify-between items-center p-4 border-b border-gray-200 rounded-t-lg';
        titleEl.className = 'text-lg font-semibold';
        confirmBtn.className = 'px-6 py-2 rounded text-sm font-medium transition-colors';

        // 타입에 따른 스타일 적용
        const style = styles[type] || styles.info;
        header.classList.add(style.header);
        titleEl.classList.add(style.title);
        confirmBtn.className += ' ' + style.button;

        // 콘텐츠 설정
        iconEl.innerHTML = icons[type] || icons.info;
        titleEl.textContent = title;
        messageEl.textContent = message;

        // 취소 버튼 표시/숨김
        if (showCancel) {
            cancelBtn.classList.remove('hidden');
        } else {
            cancelBtn.classList.add('hidden');
        }

        // 사이드바 z-index 낮추기
        if (typeof window.lowerSidebarZIndex === 'function') {
            window.lowerSidebarZIndex();
        }

        // 모달 표시
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        // 확인 버튼에 포커스
        confirmBtn.focus();
    }

    // 모달 닫기
    function closeModal(isConfirm) {
        const modal = document.getElementById('alertModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';

        // 사이드바 z-index 복원
        if (typeof window.restoreSidebarZIndex === 'function') {
            window.restoreSidebarZIndex();
        }

        // 콜백 실행
        if (isConfirm && confirmCallback) {
            confirmCallback();
        } else if (!isConfirm && cancelCallback) {
            cancelCallback();
        }

        // 콜백 초기화
        confirmCallback = null;
        cancelCallback = null;
    }

    // 전역 함수 등록
    window.showAlertModal = function(title, message, type = 'info') {
        confirmCallback = null;
        cancelCallback = null;
        openModal(title, message, type, false);
    };

    window.showSuccessModal = function(title, message) {
        window.showAlertModal(title, message, 'success');
    };

    window.showErrorModal = function(title, message) {
        window.showAlertModal(title, message, 'error');
    };

    window.showWarningModal = function(title, message) {
        window.showAlertModal(title, message, 'warning');
    };

    window.showConfirmModal = function(title, message, onConfirm, onCancel) {
        confirmCallback = onConfirm || null;
        cancelCallback = onCancel || null;
        openModal(title, message, 'confirm', true);
    };

    // 이벤트 리스너 등록
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('alertModal');
        const closeBtn = document.getElementById('closeAlertModal');
        const confirmBtn = document.getElementById('alertModalConfirmBtn');
        const cancelBtn = document.getElementById('alertModalCancelBtn');

        // 닫기 버튼
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                closeModal(false);
            });
        }

        // 확인 버튼
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                closeModal(true);
            });
        }

        // 취소 버튼
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                closeModal(false);
            });
        }

        // 배경 클릭 시 닫기
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(false);
                }
            });
        }

        // ESC 키로 닫기
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal(false);
            }
        });

        // Enter 키로 확인
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && modal && !modal.classList.contains('hidden')) {
                closeModal(true);
            }
        });
    });
})();
</script>