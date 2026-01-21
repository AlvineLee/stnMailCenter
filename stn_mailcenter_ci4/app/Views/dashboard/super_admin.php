<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<?php
$todayFormatted = date('Y년 m월 d일');
?>
<div class="space-y-4">
    <!-- 날짜 기준 안내 -->
    <div class="bg-purple-50 border-l-4 border-purple-500 text-purple-700 px-4 py-2 rounded">
        <p class="text-sm font-medium">👑 슈퍼관리자 대시보드 | <?= $todayFormatted ?></p>
    </div>

    <!-- 전체 현황 요약 카드 -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <!-- 총 콜센터(퀵사) 수 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">콜센터(퀵사)</p>
                    <p class="text-2xl font-bold text-indigo-600"><?= number_format($summary['total_call_centers'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <!-- 총 거래처 수 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">총 거래처</p>
                    <p class="text-2xl font-bold text-blue-600"><?= number_format($summary['total_companies'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <!-- 활성 거래처 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">활성 거래처</p>
                    <p class="text-2xl font-bold text-green-600"><?= number_format($summary['active_companies'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <!-- 비활성 거래처 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">비활성 거래처</p>
                    <p class="text-2xl font-bold text-gray-600"><?= number_format($summary['inactive_companies'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <!-- 총 사용자 수 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">총 사용자</p>
                    <p class="text-2xl font-bold text-purple-600"><?= number_format($summary['total_users'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 메인 콘텐츠 영역 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- 콜센터별 거래처 수 현황 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">콜센터별 거래처 현황</h3>
                <p class="text-xs text-gray-500 mt-1">콜센터(퀵사)별 소속 거래처 수</p>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto max-h-80 overflow-y-auto">
                    <table class="min-w-full bg-white border border-gray-200 text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">순위</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터코드</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터명</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase border-b">거래처 수</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase border-b">활성</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase border-b">비활성</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($company_count_by_call_center)): ?>
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-gray-500">데이터가 없습니다.</td>
                            </tr>
                            <?php else: ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($company_count_by_call_center as $cc): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-center">
                                    <?php if ($rank <= 3): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-100 text-yellow-800 font-bold text-xs"><?= $rank ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-500"><?= $rank ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-gray-600"><?= esc($cc['cc_code'] ?? '-') ?></td>
                                <td class="px-3 py-2 font-medium text-gray-900"><?= esc($cc['cc_name'] ?? '-') ?></td>
                                <td class="px-3 py-2 text-center font-semibold text-blue-600"><?= number_format($cc['company_count'] ?? 0) ?></td>
                                <td class="px-3 py-2 text-center text-green-600"><?= number_format($cc['active_count'] ?? 0) ?></td>
                                <td class="px-3 py-2 text-center text-gray-500"><?= number_format($cc['inactive_count'] ?? 0) ?></td>
                            </tr>
                            <?php $rank++; ?>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 사용자 수 많은 거래처 TOP 10 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">사용자 많은 거래처 TOP 10</h3>
                <p class="text-xs text-gray-500 mt-1">등록된 직원 수가 많은 거래처 순위</p>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto max-h-80 overflow-y-auto">
                    <table class="min-w-full bg-white border border-gray-200 text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">순위</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">거래처명</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase border-b">사용자 수</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase border-b">상태</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($companies_by_user_count)): ?>
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">데이터가 없습니다.</td>
                            </tr>
                            <?php else: ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($companies_by_user_count as $company): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-center">
                                    <?php if ($rank <= 3): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-800 font-bold text-xs"><?= $rank ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-500"><?= $rank ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900"><?= esc($company['comp_name'] ?? '-') ?></div>
                                    <div class="text-xs text-gray-500"><?= esc($company['comp_code'] ?? '') ?></div>
                                </td>
                                <td class="px-3 py-2 text-gray-600 text-xs"><?= esc($company['cc_name'] ?? '-') ?></td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                        <?= number_format($company['user_count'] ?? 0) ?>명
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <?php if (($company['use_yn'] ?? '') === 'Y'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">활성</span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">비활성</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php $rank++; ?>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 신규 등록 거래처 TOP 10 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">신규 등록 거래처 TOP 10</h3>
                    <p class="text-xs text-gray-500 mt-1">최근 등록된 거래처 목록</p>
                </div>
                <a href="<?= base_url('insung/company-list') ?>" class="text-xs text-blue-600 hover:text-blue-800">전체보기</a>
            </div>
        </div>
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">순위</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">거래처코드</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">거래처명</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">대표자</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">콜센터</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase border-b">상태</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase border-b">등록일</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($recently_registered_companies)): ?>
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-gray-500">데이터가 없습니다.</td>
                        </tr>
                        <?php else: ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($recently_registered_companies as $company): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-center">
                                <?php if ($rank === 1): ?>
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-800 font-bold text-xs">NEW</span>
                                <?php elseif ($rank <= 3): ?>
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-800 font-bold text-xs"><?= $rank ?></span>
                                <?php else: ?>
                                <span class="text-gray-500"><?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-gray-600"><?= esc($company['comp_code'] ?? '-') ?></td>
                            <td class="px-3 py-2 font-medium text-gray-900"><?= esc($company['comp_name'] ?? '-') ?></td>
                            <td class="px-3 py-2 text-gray-600"><?= esc($company['comp_owner'] ?? '-') ?></td>
                            <td class="px-3 py-2 text-gray-600 text-xs"><?= esc($company['cc_name'] ?? '-') ?></td>
                            <td class="px-3 py-2 text-center">
                                <?php if (($company['use_yn'] ?? '') === 'Y'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">활성</span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">비활성</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-gray-600 text-xs">
                                <?php
                                $createdAt = $company['created_at'] ?? '';
                                if ($createdAt) {
                                    echo date('Y-m-d', strtotime($createdAt));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php $rank++; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 빠른 바로가기 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h3 class="text-base font-semibold text-gray-900 mb-3">빠른 바로가기</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="<?= base_url('insung/company-list') ?>" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <div class="p-2 bg-blue-100 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">거래처 관리</span>
            </a>
            <a href="<?= base_url('insung/cc-list') ?>" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <div class="p-2 bg-indigo-100 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">콜센터 관리</span>
            </a>
            <a href="<?= base_url('admin/api-list') ?>" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <div class="p-2 bg-green-100 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">API 연계센터</span>
            </a>
            <a href="<?= base_url('admin/login-attempts') ?>" class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <div class="p-2 bg-purple-100 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">로그인 기록</span>
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>