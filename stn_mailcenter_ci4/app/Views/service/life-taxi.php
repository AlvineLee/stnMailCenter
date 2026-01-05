<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?> 
<?= form_open('service/submitServiceOrder', ['class' => 'order-form w-full', 'id' => 'orderForm', 'style' => 'height: 100%; display: flex; flex-direction: column;']) ?>
        <input type="hidden" name="service_type" value="life-taxi">
        <input type="hidden" name="service_name" value="택시">
        
    <!-- 주문자 정보 (숨김 필드) -->
    <?php 
    $loginType = session()->get('login_type');
    $companyName = ($loginType === 'daumdata') ? session()->get('comp_name', '') : session()->get('customer_name', '');
    $contact = ($loginType === 'daumdata') ? session()->get('user_tel1', '') : session()->get('phone', '');
    ?>
    <input type="hidden" name="company_name" value="<?= htmlspecialchars($companyName) ?>">
    <input type="hidden" name="contact" value="<?= htmlspecialchars($contact) ?>">
    
    <!-- 라운딩 패널 컨테이너 -->
    <div class="w-full bg-white rounded-lg shadow-lg overflow-hidden flex flex-col flex-1" style="height: 100%; min-height: 0;">
        <!-- 상단: 출발지/도착지 입력 영역 -->
        <div class="bg-white p-4 flex-shrink-0 w-full">
            <div class="w-full space-y-4" id="input-container" style="max-width: 100%;">
                <!-- 출발지 -->
                <div class="space-y-2 w-full">
                    <label class="block text-sm font-medium text-gray-700">출발지</label>
                    <div class="flex items-center space-x-2 w-full">
                        <input type="text" 
                               id="departure_address" 
                               name="departure_address" 
                               readonly
                               placeholder="현재 위치를 가져오는 중..." 
                               class="flex-1 w-full px-4 py-2 text-sm border border-gray-300 rounded-md bg-gray-50 cursor-pointer"
                               onclick="openDepartureAddressSearch()">
                        <input type="hidden" id="departure_lat" name="departure_lat">
                        <input type="hidden" id="departure_lng" name="departure_lng">
                        <input type="hidden" id="departure_zonecode" name="departure_zonecode">
                        <input type="hidden" id="departure_detail" name="departure_detail">
                </div>
            </div>
        
                <!-- 도착지 -->
                <div class="space-y-2 w-full">
                    <label class="block text-sm font-medium text-gray-700">도착지</label>
                    <div class="flex items-center space-x-2 w-full">
                        <input type="text" 
                               id="destination_address" 
                               name="destination_address" 
                               readonly
                               placeholder="도착지를 선택하세요" 
                               class="flex-1 w-full px-4 py-2 text-sm border border-gray-300 rounded-md bg-gray-50 cursor-pointer"
                               onclick="openDestinationAddressSearch()">
                        <input type="hidden" id="destination_lat" name="destination_lat">
                        <input type="hidden" id="destination_lng" name="destination_lng">
                        <input type="hidden" id="destination_zonecode" name="destination_zonecode">
                        <input type="hidden" id="destination_detail" name="destination_detail">
                            </div>
                        </div>
                        
                <!-- 경로 정보 (출발지와 도착지가 모두 입력되면 표시) -->
                <div id="routeInfo" class="hidden bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">경로 정보</h3>
                        <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">거리:</span>
                            <span id="routeDistance" class="text-sm font-medium text-blue-600">-</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">예상요금:</span>
                            <span id="routeFare" class="text-sm font-medium text-green-600">-</span>
                        </div>
                        </div>
                    <button type="button" 
                            id="callButton" 
                            disabled
                            onclick="callTaxi()"
                            class="w-full mt-4 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-3 rounded-md font-medium transition-colors">
                        호출하기
                    </button>
                            </div>
                        </div>
                    </div>
        
        <!-- 하단: 지도 영역 -->
        <div class="flex-1 relative min-h-0 flex justify-center p-4">
            <div class="w-full" id="map-container" style="width: 100%; max-width: 100%;">
                <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 p-4 h-full">
                    <div id="map" class="w-full h-full relative rounded-md overflow-hidden" style="min-height: 400px; height: calc(100vh - 350px);">
                <!-- 배차 중 오버레이 -->
                <div id="dispatch_overlay" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white bg-opacity-98 p-6 rounded-lg shadow-lg text-center z-50 hidden" style="min-width: 250px;">
                    <h4 class="mb-3 text-lg font-semibold">기사님을 배차중입니다.</h4>
                    <button type="button" id="virtual_dispatch_btn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md font-medium" onclick="virtualDispatch(event)">가상 배차</button>
            </div>
            
                <!-- 배차 완료 오버레이 -->
                <div id="driver_info_overlay" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white bg-opacity-98 p-6 rounded-lg shadow-lg z-50 hidden" style="min-width: 300px; cursor: move;">
                    <div class="bg-gray-100 p-3 mb-4 rounded-t-lg -mx-6 -mt-6 font-bold">배차 완료</div>
                    <div class="space-y-3">
                        <div>
                            <span class="font-semibold text-gray-700">기사님 성함:</span>
                            <span id="driver_name" class="text-blue-600 font-bold ml-2">홍길동</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">전화번호:</span>
                            <span id="driver_phone" class="text-green-600 font-bold ml-2">010-1000-2000</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">기사님과의 거리:</span>
                            <span id="walking_distance" class="text-yellow-600 font-bold ml-2">-</span>
                        </div>
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-md font-medium mt-4" onclick="callCustomerService()">
                            <i class="fas fa-phone mr-2"></i> 고객센터 전화하기
                            </button>
                        <button type="button" class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-md font-medium mt-2" onclick="closeDriverInfo(event)">
                            <i class="fas fa-times mr-2"></i> 닫기
                            </button>
                        </div>
                    </div>
            </div>
            </div>
        </div>
            </div>
        </div> 
    <!-- 라운딩 패널 컨테이너 닫기 -->
    <?= form_close() ?>

<!-- 다음 주소검색 API -->
<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<!-- 카카오맵 API -->
<script type="text/javascript" src="https://dapi.kakao.com/v2/maps/sdk.js?appkey=<?= getenv('KAKAO_MAP_API_KEY') ?? 'a2180855daef22f5e4386a9ee1ea78b7' ?>&libraries=services"></script>

<script>
// 전역 변수
let map;
let departureMarker;
let destinationMarker;
let driverMarker;
let routePolyline;
let driverToStartPolyline;
let geocoder;
let currentLocation = null;
let driverBlinkInterval = null; // 기사 마커 깜빡임 인터벌

// 카카오맵 초기화
function initMap() {
    // 카카오맵 API가 로드되었는지 확인
    if (typeof kakao === 'undefined' || typeof kakao.maps === 'undefined') {
        console.error('카카오맵 API가 로드되지 않았습니다. API 키를 확인하세요.');
        setTimeout(initMap, 100); // 100ms 후 재시도
        return;
    }
    
    const container = document.getElementById('map');
    if (!container) {
        console.error('지도 컨테이너를 찾을 수 없습니다. #map 요소가 존재하는지 확인하세요.');
        return;
    }
    
    // 지도 컨테이너 크기 확인
    const containerRect = container.getBoundingClientRect();
    if (containerRect.width === 0 || containerRect.height === 0) {
        console.warn('지도 컨테이너 크기가 0입니다. CSS를 확인하세요.', {
            width: containerRect.width,
            height: containerRect.height
        });
    }
    
    try {
        const options = {
            center: new kakao.maps.LatLng(37.5665, 126.9780), // 서울시청 기본 위치
            level: 3
        };
        
        map = new kakao.maps.Map(container, options);
        geocoder = new kakao.maps.services.Geocoder();
        
        console.log('지도 초기화 완료');
        
        // 지도 초기화 성공 후 위치 가져오기 (200ms 지연)
        setTimeout(function() {
            getCurrentLocation();
        }, 200);
    } catch (error) {
        console.error('지도 초기화 중 오류 발생:', error);
        console.error('API 키:', '<?= getenv('KAKAO_MAP_API_KEY') ?? 'NOT_SET' ?>');
        console.error('현재 도메인:', window.location.hostname);
    }
}

// 현재 위치 가져오기
function getCurrentLocation() {
    // geocoder가 준비되지 않았으면 기본 위치만 설정
    if (!geocoder) {
        // console.warn('Geocoder가 아직 준비되지 않았습니다. 기본 위치를 사용합니다.');
        setDefaultLocation(37.5665, 126.9780);
        return;
    }
    
    // 기본 위치 설정 (서울시청)
    const defaultLat = 37.5665;
    const defaultLon = 126.9780;
    
    // 타임아웃 설정 (10초)
    const timeout = setTimeout(function() {
        document.getElementById('departure_address').value = '위치 정보를 가져올 수 없습니다. 기본 위치를 사용합니다.';
        setDefaultLocation(defaultLat, defaultLon);
    }, 10000); // 10초 타임아웃
    
    if (navigator.geolocation) {
        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                clearTimeout(timeout);
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                currentLocation = { lat, lng };
                
                // console.log('위치 정보 획득:', lat, lng);
                
                // 좌표를 주소로 변환
                if (geocoder) {
                    geocoder.coord2Address(lng, lat, function(result, status) {
                        if (status === kakao.maps.services.Status.OK) {
                            const addr = result[0].address.address_name;
                            document.getElementById('departure_address').value = addr;
                            document.getElementById('departure_lat').value = lat;
                            document.getElementById('departure_lng').value = lng;
                            document.getElementById('departure_zonecode').value = result[0].address.zone_no || '';
                            
                            // 출발지 마커 표시
                            if (map) {
                                setDepartureMarker(lat, lng);
                                // 지도 중심을 현재 위치로 이동
                                map.setCenter(new kakao.maps.LatLng(lat, lng));
                            }
                        } else {
                            // 주소 변환 실패 시 좌표만 사용
                            document.getElementById('departure_address').value = '위도: ' + lat.toFixed(6) + ', 경도: ' + lng.toFixed(6);
                            document.getElementById('departure_lat').value = lat;
                            document.getElementById('departure_lng').value = lng;
                            if (map) {
                                setDepartureMarker(lat, lng);
                                map.setCenter(new kakao.maps.LatLng(lat, lng));
                            }
                        }
                    });
                } else {
                    // geocoder가 없으면 좌표만 사용
                    document.getElementById('departure_address').value = '위도: ' + lat.toFixed(6) + ', 경도: ' + lng.toFixed(6);
                    document.getElementById('departure_lat').value = lat;
                    document.getElementById('departure_lng').value = lng;
                    if (map) {
                        setDepartureMarker(lat, lng);
                        map.setCenter(new kakao.maps.LatLng(lat, lng));
                    }
                }
            },
            function(error) {
                clearTimeout(timeout);
                // console.error('위치 정보를 가져올 수 없습니다:', error);
                
                let errorMsg = '위치 정보를 가져올 수 없습니다.';
                if (error.code === error.TIMEOUT) {
                    errorMsg = '위치 정보 요청 시간이 초과되었습니다.';
                } else if (error.code === error.PERMISSION_DENIED) {
                    errorMsg = '위치 정보 권한이 거부되었습니다.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    errorMsg = '위치 정보를 사용할 수 없습니다.';
                }
                
                document.getElementById('departure_address').value = errorMsg + ' 기본 위치를 사용합니다.';
                setDefaultLocation(defaultLat, defaultLon);
            },
            options
        );
    } else {
        clearTimeout(timeout);
        document.getElementById('departure_address').value = '이 브라우저는 위치 정보를 지원하지 않습니다. 기본 위치를 사용합니다.';
        setDefaultLocation(defaultLat, defaultLon);
    }
}

// 기본 위치 설정
function setDefaultLocation(lat, lng) {
    if (geocoder) {
        geocoder.coord2Address(lng, lat, function(result, status) {
            if (status === kakao.maps.services.Status.OK) {
                const address = result[0].road_address || result[0].address;
                if (address) {
                    const fullAddress = address.address_name || 
                        (address.region_1depth_name + ' ' + 
                         address.region_2depth_name + ' ' + 
                         address.region_3depth_name);
                    
                    document.getElementById('departure_address').value = fullAddress + ' (기본 위치)';
                    document.getElementById('departure_zonecode').value = address.zone_no || '';
                } else {
                    document.getElementById('departure_address').value = '서울특별시 중구 세종대로 110 (기본 위치)';
                }
            } else {
                document.getElementById('departure_address').value = '서울특별시 중구 세종대로 110 (기본 위치)';
            }
        });
    } else {
        document.getElementById('departure_address').value = '서울특별시 중구 세종대로 110 (기본 위치)';
    }
    
    document.getElementById('departure_lat').value = lat;
    document.getElementById('departure_lng').value = lng;
    
    if (map) {
        setDepartureMarker(lat, lng);
        map.setCenter(new kakao.maps.LatLng(lat, lng));
    }
    
    // 기본 위치도 currentLocation에 저장
    currentLocation = { lat, lng };
}

// 출발지 마커 설정
function setDepartureMarker(lat, lng) {
    if (!map) {
        // console.warn('지도가 초기화되지 않았습니다. 마커를 설정할 수 없습니다.');
        return;
    }
    
    // 기존 마커 제거
    if (departureMarker) {
        departureMarker.setMap(null);
    }
    
    const markerPosition = new kakao.maps.LatLng(lat, lng);
    
    // 커스텀 마커 이미지 (빨간색 깃발)
    const imageSrc = 'https://t1.daumcdn.net/localimg/localimages/07/mapapidoc/red_b.png';
    const imageSize = new kakao.maps.Size(50, 45);
    const imageOption = { offset: new kakao.maps.Point(15, 43) };
    const markerImage = new kakao.maps.MarkerImage(imageSrc, imageSize, imageOption);
    
    departureMarker = new kakao.maps.Marker({
        position: markerPosition,
        image: markerImage
    });
    
    departureMarker.setMap(map);
    
    // 폼에 좌표 저장
    document.getElementById('departure_lat').value = lat;
    document.getElementById('departure_lng').value = lng;
    
    // 지도 범위 조정
    const bounds = new kakao.maps.LatLngBounds();
    bounds.extend(markerPosition);
    if (destinationMarker) {
        bounds.extend(destinationMarker.getPosition());
    }
    map.setBounds(bounds);
    
    // 지도 중심 이동
    map.setCenter(markerPosition);
}

// 출발지 주소 검색
function openDepartureAddressSearch() {
    new daum.Postcode({
        oncomplete: function(data) {
            let addr = '';
            if (data.userSelectedType === 'R') {
                addr = data.roadAddress;
            } else {
                addr = data.jibunAddress;
            }
            
            document.getElementById('departure_address').value = addr;
            document.getElementById('departure_zonecode').value = data.zonecode;
            
            // 주소로 좌표 검색
            geocoder.addressSearch(addr, function(result, status) {
                if (status === kakao.maps.services.Status.OK) {
                    const coords = new kakao.maps.LatLng(result[0].y, result[0].x);
                    
                    // 출발지 마커 표시
                    setDepartureMarker(result[0].y, result[0].x);
                    
                    // currentLocation 업데이트
                    currentLocation = {
                        lat: result[0].y,
                        lng: result[0].x
                    };
                    
                    // 경로 계산 (도착지가 설정되어 있는 경우)
                    const destLat = parseFloat(document.getElementById('destination_lat').value);
                    const destLng = parseFloat(document.getElementById('destination_lng').value);
                    if (destLat && destLng && !isNaN(destLat) && !isNaN(destLng)) {
                        calculateRoute(result[0].y, result[0].x, destLat, destLng);
                    }
                }
            });
        }
    }).open();
}

// 도착지 주소 검색
function openDestinationAddressSearch() {
    new daum.Postcode({
        oncomplete: function(data) {
            let addr = '';
            if (data.userSelectedType === 'R') {
                addr = data.roadAddress;
            } else {
                addr = data.jibunAddress;
            }
            
            document.getElementById('destination_address').value = addr;
            document.getElementById('destination_zonecode').value = data.zonecode;
            
            // 주소로 좌표 검색
            geocoder.addressSearch(addr, function(result, status) {
                if (status === kakao.maps.services.Status.OK) {
                    const coords = new kakao.maps.LatLng(result[0].y, result[0].x);
                    
                    // 도착지 마커 표시
                    setDestinationMarker(result[0].y, result[0].x);
                    
                    // 경로 계산
                    const startLat = parseFloat(document.getElementById('departure_lat').value);
                    const startLng = parseFloat(document.getElementById('departure_lng').value);
                    if (startLat && startLng && !isNaN(startLat) && !isNaN(startLng)) {
                        calculateRoute(startLat, startLng, result[0].y, result[0].x);
                    } else if (currentLocation) {
                        calculateRoute(currentLocation.lat, currentLocation.lng, result[0].y, result[0].x);
                    }
                }
            });
        }
    }).open();
}

// 도착지 마커 설정
function setDestinationMarker(lat, lng) {
    if (!map) {
        // console.warn('지도가 초기화되지 않았습니다. 마커를 설정할 수 없습니다.');
        return;
    }
    
    // 기존 마커 제거
    if (destinationMarker) {
        destinationMarker.setMap(null);
    }
    
    const markerPosition = new kakao.maps.LatLng(lat, lng);
    
    // 커스텀 마커 이미지 (파란색 깃발)
    const imageSrc = 'https://t1.daumcdn.net/localimg/localimages/07/mapapidoc/blue_b.png';
    const imageSize = new kakao.maps.Size(50, 45);
    const imageOption = { offset: new kakao.maps.Point(15, 43) };
    const markerImage = new kakao.maps.MarkerImage(imageSrc, imageSize, imageOption);
    
    destinationMarker = new kakao.maps.Marker({
        position: markerPosition,
        image: markerImage
    });
    
    destinationMarker.setMap(map);
    
    // 폼에 좌표 저장
    document.getElementById('destination_lat').value = lat;
    document.getElementById('destination_lng').value = lng;
    
    // 지도 범위 조정
    const bounds = new kakao.maps.LatLngBounds();
    if (departureMarker) {
        bounds.extend(departureMarker.getPosition());
    }
    bounds.extend(markerPosition);
    map.setBounds(bounds);
}

// 경로 계산
function calculateRoute(startLat, startLng, endLat, endLng) {
    if (!map) {
        // console.warn('지도가 초기화되지 않았습니다. 경로를 그릴 수 없습니다.');
        return;
    }
    
    const startPoint = new kakao.maps.LatLng(startLat, startLng);
    const endPoint = new kakao.maps.LatLng(endLat, endLng);
    
    // console.log('경로 그리기 시작:', { startLat, startLng, endLat, endLng });
    
    if (!startLat || !startLng || isNaN(startLat) || isNaN(startLng)) {
        // console.warn('출발지 좌표가 유효하지 않습니다.');
        return;
    }
    
    if (!endLat || !endLng || isNaN(endLat) || isNaN(endLng)) {
        // console.warn('도착지 좌표가 유효하지 않습니다.');
        return;
    }

    // 기존 경로선 제거
    if (routePolyline) {
        routePolyline.setMap(null);
        routePolyline = null;
    }
    
    // 카카오맵 길찾기 API 사용
    try {
        if (kakao.maps.services && kakao.maps.services.Directions) {
            const directions = new kakao.maps.services.Directions();
            
            directions.route({
                origin: startPoint,
                destination: endPoint,
                priority: kakao.maps.services.Directions.Priority.SHORTEST
            }, function(result, status) {
                // console.log('Directions API 응답:', { status, result });
                
                if (status === kakao.maps.services.Status.OK) {
                    try {
                        // 경로선 그리기
                        const linePath = [];
                        
                        if (result.routes && result.routes.length > 0) {
                            const route = result.routes[0];
                            
                            if (route.sections && route.sections.length > 0) {
                                for (let i = 0; i < route.sections.length; i++) {
                                    const section = route.sections[i];
                                    if (section.roads && section.roads.length > 0) {
                                        for (let j = 0; j < section.roads.length; j++) {
                                            const road = section.roads[j];
                                            if (road.vertexes && road.vertexes.length > 0) {
                                                for (let k = 0; k < road.vertexes.length; k += 2) {
                                                    if (k + 1 < road.vertexes.length) {
                                                        linePath.push(new kakao.maps.LatLng(road.vertexes[k + 1], road.vertexes[k]));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        if (linePath.length > 0) {
                            // console.log('경로선 그리기:', linePath.length, '개 포인트');
                            routePolyline = new kakao.maps.Polyline({
                                path: linePath,
                                strokeWeight: 5,
                                strokeColor: '#FF6B6B',
                                strokeOpacity: 0.8,
                                strokeStyle: 'solid'
                            });
                            
                            routePolyline.setMap(map);
                            
                            // 지도 범위 조정
                            const bounds = new kakao.maps.LatLngBounds();
                            bounds.extend(startPoint);
                            bounds.extend(endPoint);
                            map.setBounds(bounds);
                            
                            // 경로 정보 표시
                            const distance = result.routes[0].summary.distance; // 미터
                            const distanceKm = (distance / 1000).toFixed(2);
                            const estimatedFare = calculateTaxiFare(distance);
                            
                            document.getElementById('routeDistance').textContent = distanceKm + ' km';
                            document.getElementById('routeFare').textContent = estimatedFare.toLocaleString() + '원';
                            
                            // 경로 정보 영역 표시
                            document.getElementById('routeInfo').classList.remove('hidden');
                            
                            // 호출하기 버튼 활성화
                            document.getElementById('callButton').disabled = false;
                        } else {
                            // console.warn('경로 포인트가 없습니다. 직선으로 표시합니다.');
                            drawStraightLine(startPoint, endPoint);
                        }
                    } catch (error) {
                        // console.error('경로 그리기 중 오류:', error);
                        drawStraightLine(startPoint, endPoint);
                    }
                } else {
                    // console.warn('Directions API 실패:', status);
                    drawStraightLine(startPoint, endPoint);
                }
            });
        } else {
            // console.warn('Directions 서비스를 사용할 수 없습니다. 직선으로 표시합니다.');
            drawStraightLine(startPoint, endPoint);
        }
    } catch (error) {
        // console.error('Directions API 초기화 실패:', error);
        drawStraightLine(startPoint, endPoint);
    }
}

// 직선 경로 그리기
function drawStraightLine(startPoint, endPoint) {
    const linePath = [startPoint, endPoint];
    
    routePolyline = new kakao.maps.Polyline({
        path: linePath,
        strokeWeight: 5,
        strokeColor: '#FF6B6B',
        strokeOpacity: 0.7,
        strokeStyle: 'solid'
    });
    
    routePolyline.setMap(map);
    
    // 지도 범위 조정
    const bounds = new kakao.maps.LatLngBounds();
    bounds.extend(startPoint);
    bounds.extend(endPoint);
    map.setBounds(bounds);
    
    // 거리 계산 및 표시 (직선 거리)
    const distance = calculateDistance(
        startPoint.getLat(),
        startPoint.getLng(),
        endPoint.getLat(),
        endPoint.getLng()
    ) * 1000; // 미터 단위
    const distanceKm = (distance / 1000).toFixed(2);
    const estimatedFare = calculateTaxiFare(distance);
    
    document.getElementById('routeDistance').textContent = distanceKm + ' km';
    document.getElementById('routeFare').textContent = estimatedFare.toLocaleString() + '원';
    
    // 경로 정보 영역 표시
    document.getElementById('routeInfo').classList.remove('hidden');
    
    // 호출하기 버튼 활성화
    document.getElementById('callButton').disabled = false;
}

// 택시 요금 계산 (1km당 1.5천원, 올림 처리)
function calculateTaxiFare(distanceMeters) {
    const distanceKm = distanceMeters / 1000;
    const baseFare = distanceKm * 1500; // 1km당 1.5천원
    // 천원 단위로 올림
    return Math.ceil(baseFare / 1000) * 1000;
}

// 두 지점 간 거리 계산 (하버사인 공식, km 단위)
function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371; // 지구 반경 (km)
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;
    return distance;
}

// 택시 호출하기
function callTaxi() {
    document.getElementById('dispatch_overlay').classList.remove('hidden');
}

// 가상 배차
function virtualDispatch(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const startLat = parseFloat(document.getElementById('departure_lat').value);
    const startLng = parseFloat(document.getElementById('departure_lng').value);
    
    if (!startLat || !startLng || isNaN(startLat) || isNaN(startLng)) {
        alert('출발지 위치가 설정되지 않았습니다.');
        return false;
    }

    // 기사 위치를 출발지 근처에 랜덤하게 생성 (약 200-500m 거리)
    const offsetLat = (Math.random() * 0.01) - 0.005; // 약 ±500m
    const offsetLng = (Math.random() * 0.01) - 0.005;
    const driverLat = startLat + offsetLat;
    const driverLng = startLng + offsetLng;

    // 기사 마커 표시
    setDriverMarker(driverLat, driverLng);

    // 도보 거리 계산 및 표시
    const walkingDistance = calculateDistance(startLat, startLng, driverLat, driverLng) * 1000; // m 단위
    const walkingDistanceText = walkingDistance < 1000 
        ? Math.round(walkingDistance) + 'm' 
        : (walkingDistance / 1000).toFixed(2) + 'km';
    
    document.getElementById('walking_distance').textContent = walkingDistanceText;

    // 기사 위치 → 출발지 경로 그리기
    setTimeout(function() {
        drawDriverToStartRoute();
    }, 200);

    // 배차 중 오버레이 숨기고 기사 정보 표시
    document.getElementById('dispatch_overlay').classList.add('hidden');
    document.getElementById('driver_info_overlay').classList.remove('hidden');
    
    // 드래그 기능 활성화
    enableDragForDriverInfo();
    
    return false;
}

// 기사 마커 설정
function setDriverMarker(lat, lng) {
    if (!map) {
        // console.warn('지도가 초기화되지 않았습니다.');
        return;
    }

    // 기존 깜빡임 인터벌 정리
    if (driverBlinkInterval) {
        clearInterval(driverBlinkInterval);
        driverBlinkInterval = null;
    }

    // 기존 기사 마커 제거
    if (driverMarker) {
        driverMarker.setMap(null);
    }

    const driverPosition = new kakao.maps.LatLng(lat, lng);
    // 기사 마커 - 택시 기사 표시 (출발지 빨강, 도착지 파랑과 구분)
    const driverImageSrc = 'https://stnonecall.com/images/driver_marker.png';
    const driverImageSize = new kakao.maps.Size(50, 50);
    const driverImageOption = { offset: new kakao.maps.Point(25, 50) };
    const driverImage = new kakao.maps.MarkerImage(driverImageSrc, driverImageSize, driverImageOption);

    driverMarker = new kakao.maps.Marker({
        position: driverPosition,
        image: driverImage,
        title: '기사님 위치',
        zIndex: 1000 // 다른 마커보다 위에 표시
    });

    driverMarker.setMap(map);

    // 기사 마커 반짝임 효과 (마커를 주기적으로 숨기고 보이게 함)
    let isVisible = true;
    
    function startBlink() {
        driverBlinkInterval = setInterval(function() {
            if (driverMarker) {
                if (isVisible) {
                    driverMarker.setMap(null);
                    isVisible = false;
                } else {
                    driverMarker.setMap(map);
                    isVisible = true;
                }
            } else {
                // 마커가 제거되면 인터벌 정리
                if (driverBlinkInterval) {
                    clearInterval(driverBlinkInterval);
                    driverBlinkInterval = null;
                }
            }
        }, 800);
    }
    
    setTimeout(function() {
        startBlink();
    }, 100);

    // 지도 범위 조정 (출발지, 도착지, 기사 위치 모두 포함)
    const bounds = new kakao.maps.LatLngBounds();
    const startLat = parseFloat(document.getElementById('departure_lat').value);
    const startLng = parseFloat(document.getElementById('departure_lng').value);
    bounds.extend(new kakao.maps.LatLng(startLat, startLng));
    bounds.extend(driverPosition);
    
    // 도착지도 포함 (도착지가 설정되어 있는 경우)
    const endLat = parseFloat(document.getElementById('destination_lat').value);
    const endLng = parseFloat(document.getElementById('destination_lng').value);
    if (endLat && endLng && !isNaN(endLat) && !isNaN(endLng)) {
        bounds.extend(new kakao.maps.LatLng(endLat, endLng));
    }
    
    map.setBounds(bounds);
}

// 기사 위치 → 출발지 경로 그리기
function drawDriverToStartRoute() {
    if (!map) {
        // console.warn('지도가 초기화되지 않았습니다.');
        return;
    }
    
    const startLat = parseFloat(document.getElementById('departure_lat').value);
    const startLng = parseFloat(document.getElementById('departure_lng').value);
    
    if (!driverMarker) {
        // console.warn('기사 마커가 설정되지 않았습니다.');
        return;
    }
    
    const driverPosition = driverMarker.getPosition();
    const startPoint = new kakao.maps.LatLng(startLat, startLng);
    
    // 기존 경로선 제거
    if (driverToStartPolyline) {
        driverToStartPolyline.setMap(null);
    }
    
    // 직선 경로 그리기 (점선 스타일)
    const linePath = [driverPosition, startPoint];
    
    driverToStartPolyline = new kakao.maps.Polyline({
        path: linePath,
        strokeWeight: 4,
        strokeColor: '#FFA500',
        strokeOpacity: 0.8,
        strokeStyle: 'dashed'
    });
    
    driverToStartPolyline.setMap(map);
}

// 배차완료 팝업창 드래그 기능
function enableDragForDriverInfo() {
    const overlay = document.getElementById('driver_info_overlay');
    if (!overlay) return;
    
    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    
    // 초기 위치 저장 (중앙 정렬 해제)
    if (!overlay.dataset.initialized) {
        setTimeout(function() {
            const mapElement = document.getElementById('map');
            const mapRect = mapElement.getBoundingClientRect();
            const overlayRect = overlay.getBoundingClientRect();
            const centerX = (mapRect.width - overlayRect.width) / 2;
            const centerY = (mapRect.height - overlayRect.height) / 2;
            
            overlay.style.transform = 'none';
            overlay.style.top = centerY + 'px';
            overlay.style.left = centerX + 'px';
            overlay.dataset.initialized = 'true';
        }, 100);
    }
    
    overlay.addEventListener('mousedown', dragStart);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', dragEnd);
    
    function dragStart(e) {
        if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
            return; // 버튼 클릭 시 드래그 방지
        }
        
        const mapElement = document.getElementById('map');
        const mapRect = mapElement.getBoundingClientRect();
        const overlayRect = overlay.getBoundingClientRect();
        
        // 현재 오버레이의 map 기준 위치 계산
        const currentLeft = parseFloat(overlay.style.left) || (mapRect.width - overlayRect.width) / 2;
        const currentTop = parseFloat(overlay.style.top) || (mapRect.height - overlayRect.height) / 2;
        
        // 마우스 위치를 map 기준으로 변환
        initialX = e.clientX - mapRect.left - currentLeft;
        initialY = e.clientY - mapRect.top - currentTop;
        
        if (e.target === overlay || e.target.closest('.bg-gray-100')) {
            isDragging = true;
            overlay.style.cursor = 'grabbing';
        }
    }
    
    function drag(e) {
        if (isDragging) {
            e.preventDefault();
            const mapElement = document.getElementById('map');
            const mapRect = mapElement.getBoundingClientRect();
            
            // 마우스 위치를 map 기준으로 변환
            currentX = e.clientX - mapRect.left - initialX;
            currentY = e.clientY - mapRect.top - initialY;
            
            // map 경계 내에서만 이동
            const overlayRect = overlay.getBoundingClientRect();
            const maxX = mapRect.width - overlayRect.width;
            const maxY = mapRect.height - overlayRect.height;
            
            currentX = Math.max(0, Math.min(currentX, maxX));
            currentY = Math.max(0, Math.min(currentY, maxY));
            
            overlay.style.left = currentX + 'px';
            overlay.style.top = currentY + 'px';
        }
    }
    
    function dragEnd(e) {
        isDragging = false;
        overlay.style.cursor = 'move';
    }
}

// 고객센터 전화하기
function callCustomerService() {
    const phoneNumber = '1588-0000';
    if (confirm('고객센터(' + phoneNumber + ')로 전화하시겠습니까?')) {
        window.location.href = 'tel:' + phoneNumber;
    }
}

// 배차완료 팝업창 닫기
function closeDriverInfo(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    document.getElementById('driver_info_overlay').classList.add('hidden');
    
    // 도착지 정보 유지를 위해 지도 범위 재조정 (출발지, 도착지 포함)
    if (map && departureMarker && destinationMarker) {
        const bounds = new kakao.maps.LatLngBounds();
        bounds.extend(departureMarker.getPosition());
        bounds.extend(destinationMarker.getPosition());
        
        // 기사 마커가 있으면 포함
        if (driverMarker) {
            bounds.extend(driverMarker.getPosition());
        }
        
        map.setBounds(bounds);
    }
    
    return false;
}

// 페이지 로드 시 초기화 (카카오맵 API 로드 대기)
function waitForKakaoMap() {
    if (typeof kakao !== 'undefined' && typeof kakao.maps !== 'undefined') {
        console.log('카카오맵 API 로드 완료');
        initMap();
    } else {
        // 5초 후에도 로드되지 않으면 오류 메시지 표시
        if (!window.kakaoMapLoadTimeout) {
            window.kakaoMapLoadTimeout = setTimeout(function() {
                console.error('카카오맵 API가 5초 내에 로드되지 않았습니다.');
                console.error('API 키:', '<?= getenv('KAKAO_MAP_API_KEY') ?? 'NOT_SET' ?>');
                console.error('현재 도메인:', window.location.hostname);
                console.error('카카오 개발자 센터에 도메인이 등록되어 있는지 확인하세요.');
            }, 5000);
        }
        setTimeout(waitForKakaoMap, 100);
    }
}

// 지도 컨테이너 너비를 입력 필드 너비에 맞추기 (반응형 지원)
let resizeTimeout;
let resizeObserver = null;

function adjustMapContainerWidth() {
    const inputContainer = document.getElementById('input-container');
    const mapContainer = document.getElementById('map-container');
    
    if (inputContainer && mapContainer) {
        // 입력 컨테이너의 실제 너비를 가져와서 지도 컨테이너에 적용
        const inputWidth = inputContainer.offsetWidth;
        const currentMapWidth = mapContainer.offsetWidth;
        
        // 너비가 실제로 변경되었을 때만 업데이트
        if (Math.abs(inputWidth - currentMapWidth) > 1) {
            mapContainer.style.width = inputWidth + 'px';
            mapContainer.style.maxWidth = inputWidth + 'px';
            
            // 지도 크기 재조정
            if (map) {
                setTimeout(function() {
                    map.relayout();
                }, 50);
            }
        }
    }
}

window.addEventListener('DOMContentLoaded', function() {
    waitForKakaoMap();
    
    const inputContainer = document.getElementById('input-container');
    const mapContainer = document.getElementById('map-container');
    
    // 지도 컨테이너 너비 조정 (초기 로드)
    setTimeout(function() {
        adjustMapContainerWidth();
    }, 200);
    
    // ResizeObserver를 사용하여 입력 컨테이너 크기 변화 감지
    if (inputContainer && typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(function(entries) {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                adjustMapContainerWidth();
            }, 50);
        });
        resizeObserver.observe(inputContainer);
    }
    
    // 윈도우 리사이즈 시 너비 재조정 (debounce 적용, fallback)
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            adjustMapContainerWidth();
        }, 100);
    });
});

// 페이지 언로드 시 ResizeObserver 정리
window.addEventListener('beforeunload', function() {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});
</script>

<?= $this->endSection() ?>
