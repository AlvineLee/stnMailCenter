<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="list-page-container">
    <!-- 상단 버튼 영역 -->
    <div class="mb-4 flex justify-end">
        <a href="<?= base_url('admin/company-add') ?>" class="form-button form-button-primary">
            ➕ 신규거래처등록
        </a>
    </div>

    <!-- 검색 영역 -->
    <div class="mb-4 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <?= form_open('/admin/company-list', ['method' => 'GET', 'class' => 'flex items-end gap-3']) ?>
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">거래처명</label>
            <input type="text" name="search_compname" value="<?= esc($search_compname ?? '') ?>" placeholder="거래처명 검색" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <button type="submit" class="form-button form-button-secondary">
                🔍 조회
            </button>
        </div>
        <?= form_close() ?>
    </div>

    <!-- 거래처 목록 테이블 -->
    <div class="list-table-container">
        <table class="list-table">
            <thead>
                <tr>
                    <th class="w40">번호</th>
                    <th class="w200">업체명</th>
                    <th class="w100">담당자</th>
                    <th class="w120">전화번호</th>
                    <th class="w300">주소</th>
                    <th class="w200">메모</th>
                    <th class="w200">선택</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($company_list)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4">조회된 거래처가 없습니다.</td>
                </tr>
                <?php else: ?>
                <?php $num = 1; foreach ($company_list as $company): ?>
                <tr>
                    <td class="text-center"><?= $num++ ?></td>
                    <td class="text-left"><?= esc($company['corp_name']) ?></td>
                    <td class="text-center"><?= esc($company['owner']) ?></td>
                    <td class="text-center"><?= esc($company['tel_no']) ?></td>
                    <td class="text-left"><?= esc($company['address'] ?? '') ?></td>
                    <td class="text-left"><?= esc($company['memo'] ?? '') ?></td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" onclick="location.href='<?= base_url('admin/company-add?comp_code=' . $company['comp_no'] . '&mode=edit') ?>'" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm font-medium whitespace-nowrap">
                                ✏️ 정보수정
                            </button>
                            <button type="button" onclick="location.href='<?= base_url('insung/user-list?sel_comp_code=' . $company['comp_no']) ?>'" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm font-medium whitespace-nowrap">
                                👥 고객관리
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

