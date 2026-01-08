<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>내 즐겨찾기</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body style="margin: 0; padding: 10px; font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc;">
<div class="popup-container">
    <div class="popup-header">
        <div class="popup-buttons">
            <button type="button" class="popup-nav-btn" onclick="location.href='<?= base_url('bookmark/recent-popup?type=' . $type) ?>'">최근사용기록</button>
            <button type="button" class="popup-nav-btn popup-nav-btn-active">내 즐겨찾기</button>
        </div>
    </div>
    
    <?php if (!empty($apiError)): ?>
        <div class="error-message"><?= esc($apiError) ?></div>
    <?php endif; ?>
    
    <div class="list-table-container">
        <?php if (empty($bookmarks)): ?>
            <div style="text-align: center; padding: 32px 0; color: #64748b;">
                즐겨찾기가 없습니다.
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
                    <?php foreach ($bookmarks as $bookmark): ?>
                        <tr onclick="set_value('<?= esc($type) ?>', '<?= esc($bookmark['c_name'] ?? $bookmark['company_name'] ?? '', 'js') ?>', '<?= esc($bookmark['c_telno'], 'js') ?>', '<?= esc($bookmark['dept_name'] ?? '', 'js') ?>', '<?= esc($bookmark['charge_name'] ?? '', 'js') ?>', '<?= esc($bookmark['c_dong'] ?? '', 'js') ?>', '<?= esc($bookmark['c_addr'] ?? '', 'js') ?>', '<?= esc($bookmark['address2'] ?? '', 'js') ?>', '<?= esc($bookmark['c_code'] ?? '', 'js') ?>', '<?= esc($bookmark['c_sido'] ?? '', 'js') ?>', '<?= esc($bookmark['c_gungu'] ?? '', 'js') ?>', '<?= esc($bookmark['lon'] ?? '', 'js') ?>', '<?= esc($bookmark['lat'] ?? '', 'js') ?>')">
                            <td class="w150"><?= esc($bookmark['c_name'] ?? $bookmark['company_name'] ?? '') ?></td>
                            <td class="w100"><?= esc($bookmark['dept_name'] ?? '') ?></td>
                            <td class="w100"><?= esc($bookmark['charge_name'] ?? '') ?></td>
                            <td class="w100"><?= esc($bookmark['c_telno'] ?? '') ?></td>
                            <td class="w90"><?= esc($bookmark['addr_road'] ?? $bookmark['c_dong'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
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
