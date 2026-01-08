<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? '위치 조회') ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=<?= getenv('KAKAO_MAP_API_KEY') ?? 'a2180855daef22f5e4386a9ee1ea78b7' ?>"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            overflow: hidden;
        }
        
        .map-container {
            width: 100%;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        #map {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f0f0f0;
        }
        
        .map-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 12px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .map-header-left {
            display: flex;
            align-items: center;
        }
        
        .map-header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .map-header h2 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .map-legend {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 12px;
            color: #333;
            font-weight: 500;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .legend-item span {
            color: #333;
            font-weight: 500;
        }
        
        .legend-marker-img {
            width: 30px;
            height: auto;
            display: inline-block;
            vertical-align: middle;
        }
        
        .close-btn {
            background: #6b7280;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .close-btn:hover {
            background: #4b5563;
        }
        
        .no-data {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 20;
        }
        
        .no-data h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #111827;
        }
        
        .no-data p {
            font-size: 14px;
        }
        
        /* 카카오맵 인포윈도우 커스텀 스타일 */
        .custom-info-window {
            padding: 10px;
            min-width: 200px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .info-window-title {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-window-content {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
        }
        
        .info-window-row {
            margin-bottom: 4px;
        }
        
        .info-window-label {
            font-weight: 500;
            color: #333;
            display: inline-block;
            min-width: 50px;
        }
    </style>
</head>
<body>
    <div class="map-container">
        <div class="map-header">
            <div class="map-header-left">
                <h2>위치정보확인</h2>
            </div>
            <div class="map-header-right">
                <?php if ($start_lon && $start_lat && $dest_lon && $dest_lat): ?>
                <div class="map-legend">
                    <div class="legend-item">
                        <img src="<?= base_url('assets/images/m.gif') ?>" alt="전체" class="legend-marker-img">
                        <span>전체</span>
                    </div>
                    <div class="legend-item">
                        <img src="<?= base_url('assets/images/s.gif') ?>" alt="출발지" class="legend-marker-img">
                        <span>출발지</span>
                    </div>
                    <div class="legend-item">
                        <img src="<?= base_url('assets/images/e.gif') ?>" alt="도착지" class="legend-marker-img">
                        <span>도착지</span>
                    </div>
                    <?php if ($is_riding && $rider_lon && $rider_lat): ?>
                    <div class="legend-item">
                        <img src="<?= base_url('assets/images/r.gif') ?>" alt="기사" class="legend-marker-img">
                        <span>기사위치</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <button class="close-btn" onclick="window.close()">닫기</button>
            </div>
        </div>
        
        <div id="map"></div>
        
        <?php if (!$start_lon || !$start_lat || !$dest_lon || !$dest_lat): ?>
        <div class="no-data">
            <h3>위치 정보가 없습니다</h3>
            <p>주문의 출발지 또는 도착지 좌표 정보가 없어 맵을 표시할 수 없습니다.</p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($start_lon && $start_lat && $dest_lon && $dest_lat): ?>
        // 카카오맵 API 로드 대기 함수
        let retryCount = 0;
        const maxRetries = 50; // 5초 대기
        
        function initKakaoMap() {
            if (typeof kakao === 'undefined' || typeof kakao.maps === 'undefined') {
                retryCount++;
                if (retryCount > maxRetries) {
                    console.error('카카오맵 API가 5초 내에 로드되지 않았습니다.');
                    console.error('API 키:', '<?= getenv('KAKAO_MAP_API_KEY') ?? 'NOT_SET' ?>');
                    console.error('현재 도메인:', window.location.hostname);
                    alert('카카오맵 API를 로드할 수 없습니다. 브라우저 콘솔을 확인하세요.');
                    return;
                }
                setTimeout(initKakaoMap, 100);
                return;
            }
            
            console.log('카카오맵 API 로드 완료, 지도 초기화 시작');
            
            // 출발지와 도착지 좌표 (stnlogis와 동일하게 변수명 사용)
            // stnlogis에서는 $s_lat이 위도, $s_lon이 경도로 사용됨
            // 하지만 인성 API의 start_lon이 실제로는 위도일 수 있고, start_lat이 경도일 수 있음
            const s_lat = parseFloat(<?= $start_lat ?? 'null' ?>);
            const s_lon = parseFloat(<?= $start_lon ?? 'null' ?>);
            const e_lat = parseFloat(<?= $dest_lat ?? 'null' ?>);
            const e_lon = parseFloat(<?= $dest_lon ?? 'null' ?>);
            
            console.log('좌표 정보 (stnlogis 변수명):', { s_lat, s_lon, e_lat, e_lon });
            
            // 좌표 유효성 검사
            if (isNaN(s_lat) || isNaN(s_lon) || isNaN(e_lat) || isNaN(e_lon)) {
                console.error('유효하지 않은 좌표:', { s_lat, s_lon, e_lat, e_lon });
                alert('좌표 정보가 올바르지 않습니다: ' + JSON.stringify({ s_lat, s_lon, e_lat, e_lon }));
                return;
            }
            
            if (s_lat === 0 || s_lon === 0 || e_lat === 0 || e_lon === 0) {
                console.error('좌표가 0입니다:', { s_lat, s_lon, e_lat, e_lon });
                alert('좌표 정보가 없습니다.');
                return;
            }
            
            // 기사 위치 (운행 중일 때만)
            const r_lat = <?= $is_riding && $rider_lat ? $rider_lat : 'null' ?>;
            const r_lon = <?= $is_riding && $rider_lon ? $rider_lon : 'null' ?>;
            
            // 출발지/도착지 정보
            const departureAddress = <?= json_encode($departure_address ?? '') ?>;
            const departureCompanyName = <?= json_encode($departure_company_name ?? '') ?>;
            const departureTel = <?= json_encode($departure_tel ?? '') ?>;
            const destinationAddress = <?= json_encode($destination_address ?? '') ?>;
            const destinationCompanyName = <?= json_encode($destination_company_name ?? '') ?>;
            const destinationTel = <?= json_encode($destination_tel ?? '') ?>;
            const riderName = <?= json_encode($rider_name ?? '') ?>;
            const riderCode = <?= json_encode($rider_code ?? '') ?>;
            const riderTel = <?= json_encode($rider_tel ?? '') ?>;
            
            // 중심 좌표 계산 (stnlogis와 동일: $set_lat, $set_lon)
            // stnlogis에서는 기본적으로 출발지 좌표를 중심으로 사용
            const set_lat = s_lat;
            const set_lon = s_lon;
            
            // 맵 초기화
            const container = document.getElementById('map');
            if (!container) {
                console.error('지도 컨테이너를 찾을 수 없습니다.');
                return;
            }
            
            // 지도 컨테이너 크기 확인 및 설정
            const containerRect = container.getBoundingClientRect();
            console.log('지도 컨테이너 크기:', { width: containerRect.width, height: containerRect.height });
            
            // 헤더 높이 계산
            const header = document.querySelector('.map-header');
            const headerHeight = header ? header.offsetHeight : 60;
            
            // 지도 컨테이너 크기 강제 설정
            container.style.width = '100%';
            container.style.height = 'calc(100vh - ' + headerHeight + 'px)';
            container.style.top = headerHeight + 'px';
            
            // 다시 크기 확인
            const newRect = container.getBoundingClientRect();
            console.log('설정 후 지도 컨테이너 크기:', { width: newRect.width, height: newRect.height });
            
            if (newRect.width === 0 || newRect.height === 0) {
                console.error('지도 컨테이너 크기가 여전히 0입니다.');
                alert('지도 컨테이너 크기를 설정할 수 없습니다.');
                return;
            }
            
            try {
                // stnlogis와 동일: center에 $set_lat, $set_lon 사용
                const options = {
                    center: new kakao.maps.LatLng(set_lat, set_lon), // stnlogis: LatLng($set_lat, $set_lon)
                    level: 3, // stnlogis와 동일
                    mapTypeId: kakao.maps.MapTypeId.ROADMAP // stnlogis와 동일
                };
                const map = new kakao.maps.Map(container, options);
                console.log('지도 초기화 완료', { set_lat, set_lon, level: 3 });
                
                // 지도에 확대 축소 컨트롤 추가 (stnlogis와 동일)
                const zoomControl = new kakao.maps.ZoomControl();
                map.addControl(zoomControl, kakao.maps.ControlPosition.RIGHT);
                
                // 지도가 제대로 렌더링되었는지 확인
                setTimeout(function() {
                    const mapBounds = map.getBounds();
                    console.log('지도 범위:', mapBounds);
                    if (!mapBounds) {
                        console.error('지도가 제대로 렌더링되지 않았습니다.');
                        map.relayout();
                    }
                }, 500);
        
                // 마커 이미지 설정 (stnlogis와 동일한 크기)
                const imageSrc = {
                    start: '<?= base_url('assets/images/s.gif') ?>',      // 출발지
                    dest: '<?= base_url('assets/images/e.gif') ?>',      // 도착지
                    rider: '<?= base_url('assets/images/r.gif') ?>'      // 기사
                };
                
                // 마커 이미지 크기 (stnlogis와 동일: 40x40)
                const imageSize = new kakao.maps.Size(40, 40);
                const imageOption = { offset: new kakao.maps.Point(20, 40) };
                
                // 출발지 마커 (stnlogis와 동일: LatLng($s_lat, $s_lon))
                const startMarkerImage = new kakao.maps.MarkerImage(imageSrc.start, imageSize, imageOption);
                const startMarker = new kakao.maps.Marker({
                    position: new kakao.maps.LatLng(s_lat, s_lon), // stnlogis: LatLng($s_lat, $s_lon)
                    image: startMarkerImage,
                    map: map
                });
                
                // 도착지 마커 (stnlogis와 동일: LatLng($e_lat, $e_lon))
                const destMarkerImage = new kakao.maps.MarkerImage(imageSrc.dest, imageSize, imageOption);
                const destMarker = new kakao.maps.Marker({
                    position: new kakao.maps.LatLng(e_lat, e_lon), // stnlogis: LatLng($e_lat, $e_lon)
                    image: destMarkerImage,
                    map: map
                });
                
                // 기사 위치 마커 (운행 중일 때만, stnlogis와 동일: LatLng($r_lat, $r_lon))
                let riderMarker = null;
                if (r_lat !== null && r_lon !== null) {
                    const riderMarkerImage = new kakao.maps.MarkerImage(imageSrc.rider, imageSize, imageOption);
                    riderMarker = new kakao.maps.Marker({
                        position: new kakao.maps.LatLng(r_lat, r_lon), // stnlogis: LatLng($r_lat, $r_lon)
                        image: riderMarkerImage,
                        map: map
                    });
                }
                
                // 모든 마커가 보이도록 지도 범위 조정 (stnlogis와 동일)
                const bounds = new kakao.maps.LatLngBounds();
                bounds.extend(new kakao.maps.LatLng(s_lat, s_lon));
                bounds.extend(new kakao.maps.LatLng(e_lat, e_lon));
                if (r_lat !== null && r_lon !== null) {
                    bounds.extend(new kakao.maps.LatLng(r_lat, r_lon));
                }
                
                // 지도 범위 설정
                map.setBounds(bounds);
                
                // 지도 크기 재조정 (헤더가 absolute로 겹쳐져 있어서 필요)
                setTimeout(function() {
                    map.relayout();
                }, 200);
                
                // CustomOverlay 생성 함수 (stnlogis와 동일한 방식)
                function createCustomOverlay(position, title, companyName, tel, mode) {
                    const html = `
                        <div class="custom-info-window">
                            <table width="200" border="0" align="center" cellspacing="10" style="margin:0;">
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
                                            <tr>
                                                <td bgcolor="#f8f8f8" colspan="2" align="center" style="border-top:none;">
                                                    <span style="font-size:13px;"><b>${title}</b></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td bgcolor="#FFFFFF" width="60" align="center"><b>${mode === 'R' ? '기사' : '상호'}</b></td>
                                                <td bgcolor="#FFFFFF" style="padding:0 5px 0 5px">${companyName || ''}</td>
                                            </tr>
                                            <tr>
                                                <td bgcolor="#FFFFFF" width="60" align="center"><b>연락처</b></td>
                                                <td bgcolor="#FFFFFF" style="padding:0 5px 0 5px">${tel || ''}</td>
                                            </tr>
                                            <tr>
                                                <td bgcolor="#FFFFFF" colspan="2" align="center" style="padding:3px 0">
                                                    <input type="button" value="닫기" style="font-size:12px" onclick="closeInfo('${mode}');">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    `;
                    
                    return new kakao.maps.CustomOverlay({
                        map: null, // 초기에는 표시하지 않음
                        content: html,
                        position: position,
                        xAnchor: 0.5,
                        yAnchor: 0
                    });
                }
                
                // 출발지 CustomOverlay (stnlogis와 동일: LatLng($s_lat, $s_lon))
                const startOverlay = createCustomOverlay(
                    new kakao.maps.LatLng(s_lat, s_lon),
                    '출발지정보',
                    departureCompanyName,
                    departureTel,
                    'S'
                );
                
                // 도착지 CustomOverlay (stnlogis와 동일: LatLng($e_lat, $e_lon))
                const destOverlay = createCustomOverlay(
                    new kakao.maps.LatLng(e_lat, e_lon),
                    '도착지정보',
                    destinationCompanyName,
                    destinationTel,
                    'E'
                );
                
                // 기사 CustomOverlay (stnlogis와 동일: LatLng($r_lat, $r_lon))
                let riderOverlay = null;
                if (riderMarker) {
                    const riderDisplayName = riderName + (riderCode ? '(' + riderCode + ')' : '');
                    riderOverlay = createCustomOverlay(
                        new kakao.maps.LatLng(r_lat, r_lon),
                        '기사정보',
                        riderDisplayName,
                        riderTel,
                        'R'
                    );
                }
                
                // 닫기 함수 (전역으로 선언)
                window.closeInfo = function(mode) {
                    if (mode === 'S') {
                        startOverlay.setMap(null);
                    } else if (mode === 'E') {
                        destOverlay.setMap(null);
                    } else if (mode === 'R') {
                        if (riderOverlay) riderOverlay.setMap(null);
                    }
                };
                
                // 마커 클릭 시 CustomOverlay 표시 (stnlogis와 동일)
                kakao.maps.event.addListener(startMarker, 'click', function() {
                    startOverlay.setMap(map);
                });
                
                kakao.maps.event.addListener(destMarker, 'click', function() {
                    destOverlay.setMap(map);
                });
                
                if (riderMarker && riderOverlay) {
                    kakao.maps.event.addListener(riderMarker, 'click', function() {
                        riderOverlay.setMap(map);
                    });
                }
                
                // 초기 로드 시 모든 CustomOverlay 자동 표시 (마커 바로 아래)
                setTimeout(function() {
                    startOverlay.setMap(map);
                    destOverlay.setMap(map);
                    if (riderMarker && riderOverlay) {
                        riderOverlay.setMap(map);
                    }
                }, 300);
                
            } catch (error) {
                console.error('지도 초기화 중 오류 발생:', error);
            }
        }
        
        // 페이지 로드 시 지도 초기화
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initKakaoMap);
        } else {
            initKakaoMap();
        }
        
        <?php else: ?>
        // 좌표 정보가 없는 경우
        document.getElementById('map').style.display = 'none';
        <?php endif; ?>
    </script>
</body>
</html>
