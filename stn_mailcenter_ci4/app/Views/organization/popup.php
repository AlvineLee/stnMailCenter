<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>조직도</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body style="margin: 0; padding: 10px; font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; height: 100vh; overflow: hidden;">
<div class="popup-container">
    <div class="popup-header">
        <div class="popup-buttons">
            <button type="button" class="popup-nav-btn" onclick="location.href='<?= base_url('bookmark/recent-popup?type=' . $type) ?>'">최근사용기록</button>
            <button type="button" class="popup-nav-btn" onclick="location.href='<?= base_url('bookmark/popup?type=' . $type) ?>'">내 즐겨찾기</button>
            <button type="button" class="popup-nav-btn popup-nav-btn-active">조직도</button>
        </div>
    </div>
    
    <?php if (!empty($apiError)): ?>
        <div class="error-message"><?= esc($apiError) ?></div>
    <?php endif; ?>
    
    <?php if (isset($pagination) && $pagination): ?>
        <div style="margin-bottom: 12px; padding: 8px; background: #f8fafc; border-radius: 4px; font-size: 12px; color: #64748b;">
            총 <?= number_format($pagination['total_count']) ?>건 중 
            <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>-<?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count'])) ?>건 표시
        </div>
    <?php endif; ?>
    
    <div class="list-table-container">
        <?php if (empty($organizations)): ?>
            <div style="text-align: center; padding: 32px 0; color: #64748b;">
                조직도 데이터가 없습니다.
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="list-table">
                <thead>
                    <tr>
                        <th class="w150">상호명</th>
                        <th class="w100">부서명</th>
                        <th class="w100">담당</th>
                        <th class="w100">연락처</th>
                        <th class="w90">기준동명</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizations as $org): ?>
                        <tr onclick="set_value('<?= esc($type) ?>', '<?= esc($org['c_name'], 'js') ?>', '<?= esc($org['c_telno'], 'js') ?>', '<?= esc($org['dept_name'] ?? '', 'js') ?>', '<?= esc($org['charge_name'] ?? '', 'js') ?>', '<?= esc($org['c_dong'] ?? '', 'js') ?>', '<?= esc($org['c_addr'] ?? '', 'js') ?>', '<?= esc($org['address2'] ?? '', 'js') ?>', '<?= esc($org['c_code'] ?? '', 'js') ?>', '<?= esc($org['c_sido'] ?? '', 'js') ?>', '<?= esc($org['c_gungu'] ?? '', 'js') ?>', '<?= esc($org['lon'] ?? '', 'js') ?>', '<?= esc($org['lat'] ?? '', 'js') ?>')">
                            <td class="w150"><?= esc($org['c_name']) ?></td>
                            <td class="w100"><?= esc($org['dept_name'] ?? '') ?></td>
                            <td class="w100"><?= esc($org['charge_name'] ?? '') ?></td>
                            <td class="w100"><?= esc($org['c_telno']) ?></td>
                            <td class="w90"><?= esc($org['c_dong'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($pagination) && $pagination && $pagination['total_pages'] > 1): ?>
    <div class="list-pagination" style="margin-top: 16px;">
        <div class="pagination">
            <?php if ($pagination['has_prev']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="nav-button">처음</a>
            <?php else: ?>
                <span class="nav-button disabled">처음</span>
            <?php endif; ?>
            
            <?php if ($pagination['has_prev']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" class="nav-button">이전</a>
            <?php else: ?>
                <span class="nav-button disabled">이전</span>
            <?php endif; ?>
            
            <?php
            // 항상 5개 페이지 번호를 표시하도록 계산
            $showPages = 5;
            $halfPages = floor($showPages / 2);
            $startPage = max(1, $pagination['current_page'] - $halfPages);
            $endPage = min($pagination['total_pages'], $startPage + $showPages - 1);
            
            // 끝 페이지가 조정되면 시작 페이지도 조정
            if ($endPage - $startPage < $showPages - 1) {
                $startPage = max(1, $endPage - $showPages + 1);
            }
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <?php if ($i == $pagination['current_page']): ?>
                    <span class="page-number active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-number"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($pagination['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" class="nav-button">다음</a>
            <?php else: ?>
                <span class="nav-button disabled">다음</span>
            <?php endif; ?>
            
            <?php if ($pagination['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['total_pages']])) ?>" class="nav-button">끝</a>
            <?php else: ?>
                <span class="nav-button disabled">끝</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function set_value(set_type, c_name, c_telno, dept_name, charge_name, c_dong, c_addr, c_address2, serial, c_sido, c_gungu, lon, lat) {
    if (set_type == 'S') {
        // 출발지
        if (opener.document.getElementById('departure_company_name')) {
            opener.document.getElementById('departure_company_name').value = c_name;
            opener.document.getElementById('departure_contact').value = c_telno;
            opener.document.getElementById('departure_manager').value = charge_name;
            opener.document.getElementById('departure_dong').value = c_dong;
            opener.document.getElementById('departure_address').value = c_addr;
            if (opener.document.getElementById('departure_detail')) {
                opener.document.getElementById('departure_detail').value = c_address2;
            }
            if (opener.document.getElementById('departure_department')) {
                opener.document.getElementById('departure_department').value = dept_name;
            }
            if (opener.document.getElementById('departure_lon')) {
                opener.document.getElementById('departure_lon').value = lon;
            }
            if (opener.document.getElementById('departure_lat')) {
                opener.document.getElementById('departure_lat').value = lat;
            }
        }
    } else if (set_type == 'D') {
        // 도착지
        if (opener.document.getElementById('destination_company_name')) {
            opener.document.getElementById('destination_company_name').value = c_name;
            opener.document.getElementById('destination_contact').value = c_telno;
            opener.document.getElementById('destination_manager').value = charge_name;
            opener.document.getElementById('destination_dong').value = c_dong;
            opener.document.getElementById('destination_address').value = c_addr;
            if (opener.document.getElementById('destination_detail')) {
                opener.document.getElementById('destination_detail').value = c_address2;
            } else if (opener.document.getElementById('detail_address')) {
                opener.document.getElementById('detail_address').value = c_address2;
            }
            if (opener.document.getElementById('destination_department')) {
                opener.document.getElementById('destination_department').value = dept_name;
            }
            if (opener.document.getElementById('destination_lon')) {
                opener.document.getElementById('destination_lon').value = lon;
            }
            if (opener.document.getElementById('destination_lat')) {
                opener.document.getElementById('destination_lat').value = lat;
            }
        }
    } else if (set_type == 'A') {
        // 경유지
        if (opener.document.getElementById('waypoint_address')) {
            opener.document.getElementById('waypoint_address').value = c_addr;
            opener.document.getElementById('waypoint_detail').value = c_address2;
            opener.document.getElementById('waypoint_contact').value = c_telno;
        }
    }
    
    // price_set 함수가 있으면 호출
    if (typeof opener.price_set === 'function') {
        opener.price_set();
    }
    
    self.close();
}
</script>
</body>
</html>
