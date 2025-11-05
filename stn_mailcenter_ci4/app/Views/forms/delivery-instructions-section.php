<?php
/**
 * 전달사항 섹션 공통 컴포넌트
 * 
 * @var string $fieldName 필드명 (기본값: 'special_instructions')
 * @var string $fieldId 필드 ID (기본값: 'special_instructions')
 * @var string $placeholder placeholder 텍스트 (기본값: '전달하실 내용을 입력하세요.')
 */

// 기본값 설정
$fieldName = $fieldName ?? 'special_instructions';
$fieldId = $fieldId ?? 'special_instructions';
$placeholder = $placeholder ?? '전달하실 내용을 입력하세요.';
?>

<!-- 전달사항 -->
<div class="mb-2">
    <section class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-2 pb-1 border-b border-gray-300">전달사항</h2>
        <div class="space-y-1">
            <p class="text-xs text-gray-600 font-medium">전달사항을 입력해주세요</p>
            <textarea id="<?= esc($fieldId) ?>" name="<?= esc($fieldName) ?>" placeholder="<?= esc($placeholder) ?>" lang="ko"
                      class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent h-20 resize-none bg-white"><?= old($fieldName) ?></textarea>
        </div>
    </section>
</div>

