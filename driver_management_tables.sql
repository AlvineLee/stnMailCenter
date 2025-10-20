-- =====================================================
-- 배달기사 관리 시스템 테이블 추가
-- =====================================================

-- 28. 배달기사 마스터 테이블
CREATE TABLE tbl_drivers (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '배달기사 고유 ID',
    driver_code VARCHAR(20) UNIQUE NOT NULL COMMENT '기사 코드 (자동 생성): DRV-0001 형식',
    driver_name VARCHAR(50) NOT NULL COMMENT '기사명',
    phone VARCHAR(20) NOT NULL COMMENT '연락처',
    email VARCHAR(100) COMMENT '이메일',
    license_number VARCHAR(50) COMMENT '운전면허번호',
    license_type ENUM('type1', 'type2', 'type2_auto', 'type1_large') COMMENT '면허 종류',
    license_expiry_date DATE COMMENT '면허 만료일',
    
    -- 소속 정보
    company_name VARCHAR(100) COMMENT '소속 회사명',
    company_type ENUM('internal', 'external', 'contractor') DEFAULT 'internal' COMMENT '소속 유형: 내부, 외부, 계약직',
    
    -- 차량 정보
    vehicle_type ENUM('motorcycle', 'sedan', 'van', 'truck', 'cargo', 'bicycle', 'scooter', 'walking', 'subway') NOT NULL COMMENT '주요 운송수단',
    vehicle_number VARCHAR(20) COMMENT '차량번호',
    vehicle_model VARCHAR(100) COMMENT '차량 모델',
    vehicle_capacity VARCHAR(50) COMMENT '차량 적재량',
    
    -- 서비스 가능 지역
    service_areas JSON COMMENT '서비스 가능 지역 (JSON 배열)',
    service_radius INT DEFAULT 10 COMMENT '서비스 반경 (km)',
    
    -- 근무 정보
    work_schedule JSON COMMENT '근무 스케줄 (JSON)',
    hourly_rate DECIMAL(8,2) COMMENT '시간당 요금',
    commission_rate DECIMAL(5,2) COMMENT '수수료율 (%)',
    
    -- 상태 정보
    status ENUM('active', 'inactive', 'suspended', 'on_duty', 'off_duty') DEFAULT 'active' COMMENT '기사 상태',
    current_location_lat DECIMAL(10, 8) COMMENT '현재 위치 위도',
    current_location_lng DECIMAL(11, 8) COMMENT '현재 위치 경도',
    last_location_update TIMESTAMP NULL COMMENT '마지막 위치 업데이트 시간',
    
    -- 평가 정보
    rating DECIMAL(3,2) DEFAULT 0.00 COMMENT '평균 평점 (0.00-5.00)',
    total_orders INT DEFAULT 0 COMMENT '총 주문 수',
    completed_orders INT DEFAULT 0 COMMENT '완료된 주문 수',
    cancelled_orders INT DEFAULT 0 COMMENT '취소된 주문 수',
    
    -- 계약 정보
    contract_start_date DATE COMMENT '계약 시작일',
    contract_end_date DATE COMMENT '계약 종료일',
    contract_type ENUM('full_time', 'part_time', 'contract', 'freelance') DEFAULT 'contract' COMMENT '계약 유형',
    
    -- 시스템 정보
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_driver_code (driver_code) COMMENT '기사코드 검색용 인덱스',
    INDEX idx_driver_phone (phone) COMMENT '연락처 검색용 인덱스',
    INDEX idx_driver_vehicle_type (vehicle_type) COMMENT '운송수단별 조회용 인덱스',
    INDEX idx_driver_status (status) COMMENT '상태별 조회용 인덱스',
    INDEX idx_driver_company (company_name) COMMENT '소속회사별 조회용 인덱스',
    INDEX idx_driver_active (is_active) COMMENT '활성기사 조회용 인덱스',
    INDEX idx_driver_location (current_location_lat, current_location_lng) COMMENT '위치별 조회용 인덱스',
    INDEX idx_driver_rating (rating) COMMENT '평점별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='배달기사 마스터 테이블';

-- 29. 배달기사 서비스 타입 연결 테이블
CREATE TABLE tbl_driver_service_types (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '연결 고유 ID',
    driver_id INT NOT NULL COMMENT '배달기사 ID',
    service_type_id INT NOT NULL COMMENT '서비스 타입 ID',
    is_available BOOLEAN DEFAULT TRUE COMMENT '해당 서비스 제공 가능 여부',
    priority_order INT DEFAULT 1 COMMENT '우선순위 (1=최우선)',
    special_notes TEXT COMMENT '특별 사항',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_dst_driver (driver_id) COMMENT '기사별 서비스 조회용 인덱스',
    INDEX idx_dst_service (service_type_id) COMMENT '서비스별 기사 조회용 인덱스',
    INDEX idx_dst_available (is_available) COMMENT '제공가능 서비스 조회용 인덱스',
    UNIQUE KEY unique_driver_service (driver_id, service_type_id) COMMENT '기사-서비스 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='배달기사 서비스 타입 연결 테이블';

-- 30. 배달기사 근무 기록 테이블
CREATE TABLE tbl_driver_work_logs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '근무기록 고유 ID',
    driver_id INT NOT NULL COMMENT '배달기사 ID',
    order_id INT NOT NULL COMMENT '주문 ID',
    work_type ENUM('pickup', 'delivery', 'round_trip', 'standby') NOT NULL COMMENT '근무 유형',
    start_time TIMESTAMP NOT NULL COMMENT '시작 시간',
    end_time TIMESTAMP NULL COMMENT '종료 시간',
    start_location_lat DECIMAL(10, 8) COMMENT '시작 위치 위도',
    start_location_lng DECIMAL(11, 8) COMMENT '시작 위치 경도',
    end_location_lat DECIMAL(10, 8) COMMENT '종료 위치 위도',
    end_location_lng DECIMAL(11, 8) COMMENT '종료 위치 경도',
    distance_km DECIMAL(8,2) COMMENT '이동 거리 (km)',
    duration_minutes INT COMMENT '소요 시간 (분)',
    fuel_cost DECIMAL(8,2) COMMENT '연료비',
    toll_cost DECIMAL(8,2) COMMENT '통행료',
    additional_cost DECIMAL(8,2) COMMENT '추가 비용',
    total_cost DECIMAL(8,2) COMMENT '총 비용',
    notes TEXT COMMENT '근무 메모',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '기록 생성일시',
    
    -- 인덱스 설정
    INDEX idx_dwl_driver (driver_id) COMMENT '기사별 근무기록 조회용 인덱스',
    INDEX idx_dwl_order (order_id) COMMENT '주문별 근무기록 조회용 인덱스',
    INDEX idx_dwl_work_type (work_type) COMMENT '근무유형별 조회용 인덱스',
    INDEX idx_dwl_start_time (start_time) COMMENT '시작시간별 조회용 인덱스',
    INDEX idx_dwl_duration (duration_minutes) COMMENT '소요시간별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='배달기사 근무 기록 테이블';

-- 31. 배달기사 평가 테이블
CREATE TABLE tbl_driver_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '평가 고유 ID',
    driver_id INT NOT NULL COMMENT '배달기사 ID',
    order_id INT NOT NULL COMMENT '주문 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID',
    user_id INT NOT NULL COMMENT '평가자 사용자 ID',
    rating INT NOT NULL COMMENT '평점 (1-5)',
    punctuality_rating INT COMMENT '시간 준수 평점 (1-5)',
    service_rating INT COMMENT '서비스 품질 평점 (1-5)',
    communication_rating INT COMMENT '소통 평점 (1-5)',
    review_text TEXT COMMENT '리뷰 내용',
    is_anonymous BOOLEAN DEFAULT FALSE COMMENT '익명 평가 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '평가 작성일시',
    
    -- 인덱스 설정
    INDEX idx_dr_driver (driver_id) COMMENT '기사별 평가 조회용 인덱스',
    INDEX idx_dr_order (order_id) COMMENT '주문별 평가 조회용 인덱스',
    INDEX idx_dr_customer (customer_id) COMMENT '고객사별 평가 조회용 인덱스',
    INDEX idx_dr_rating (rating) COMMENT '평점별 조회용 인덱스',
    INDEX idx_dr_created (created_at) COMMENT '평가일별 조회용 인덱스',
    UNIQUE KEY unique_order_rating (order_id) COMMENT '주문당 평가 1개 제한'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='배달기사 평가 테이블';

-- 32. 배달기사 위치 추적 테이블
CREATE TABLE tbl_driver_location_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '위치추적 고유 ID',
    driver_id INT NOT NULL COMMENT '배달기사 ID',
    latitude DECIMAL(10, 8) NOT NULL COMMENT '위도',
    longitude DECIMAL(11, 8) NOT NULL COMMENT '경도',
    accuracy DECIMAL(8,2) COMMENT '정확도 (미터)',
    speed DECIMAL(6,2) COMMENT '속도 (km/h)',
    heading DECIMAL(6,2) COMMENT '방향 (도)',
    altitude DECIMAL(8,2) COMMENT '고도 (미터)',
    battery_level INT COMMENT '배터리 잔량 (%)',
    is_charging BOOLEAN DEFAULT FALSE COMMENT '충전 중 여부',
    network_type ENUM('wifi', '4g', '5g', '3g', 'unknown') COMMENT '네트워크 타입',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '기록 시간',
    
    -- 인덱스 설정
    INDEX idx_dlt_driver (driver_id) COMMENT '기사별 위치 조회용 인덱스',
    INDEX idx_dlt_recorded (recorded_at) COMMENT '기록시간별 조회용 인덱스',
    INDEX idx_dlt_location (latitude, longitude) COMMENT '위치별 조회용 인덱스',
    INDEX idx_dlt_driver_time (driver_id, recorded_at) COMMENT '기사-시간 복합 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='배달기사 위치 추적 테이블';

-- =====================================================
-- 기존 테이블 수정 (배달기사 정보 연결)
-- =====================================================

-- tbl_orders_quick 테이블에 driver_id 추가
ALTER TABLE tbl_orders_quick ADD COLUMN driver_id INT COMMENT '배달기사 ID (tbl_drivers 참조)' AFTER order_id;
ALTER TABLE tbl_orders_quick ADD INDEX idx_quick_driver (driver_id) COMMENT '배달기사별 주문 조회용 인덱스';

-- =====================================================
-- 배달기사 관리 관련 뷰 생성
-- =====================================================

-- 배달기사 현황 조회 뷰
CREATE VIEW v_driver_status AS
SELECT 
    d.id,
    d.driver_code,
    d.driver_name,
    d.phone,
    d.vehicle_type,
    d.vehicle_number,
    d.status,
    d.current_location_lat,
    d.current_location_lng,
    d.rating,
    d.total_orders,
    d.completed_orders,
    d.cancelled_orders,
    CASE 
        WHEN d.status = 'on_duty' THEN '근무중'
        WHEN d.status = 'off_duty' THEN '근무종료'
        WHEN d.status = 'active' THEN '대기중'
        WHEN d.status = 'inactive' THEN '비활성'
        WHEN d.status = 'suspended' THEN '정지'
        ELSE '알 수 없음'
    END as status_text,
    CASE 
        WHEN d.last_location_update IS NULL THEN '위치 미확인'
        WHEN TIMESTAMPDIFF(MINUTE, d.last_location_update, NOW()) > 30 THEN '위치 오래됨'
        ELSE '위치 확인됨'
    END as location_status,
    TIMESTAMPDIFF(MINUTE, d.last_location_update, NOW()) as minutes_since_location_update
FROM tbl_drivers d
WHERE d.is_active = TRUE;

-- 배달기사 서비스 가능 현황 뷰
CREATE VIEW v_driver_service_availability AS
SELECT 
    d.id as driver_id,
    d.driver_code,
    d.driver_name,
    d.phone,
    d.vehicle_type,
    d.status,
    st.service_code,
    st.service_name,
    st.service_category,
    dst.is_available,
    dst.priority_order,
    dst.special_notes
FROM tbl_drivers d
JOIN tbl_driver_service_types dst ON d.id = dst.driver_id
JOIN tbl_service_types st ON dst.service_type_id = st.id
WHERE d.is_active = TRUE 
  AND d.status IN ('active', 'on_duty')
  AND dst.is_available = TRUE
  AND st.is_active = TRUE;

-- 배달기사 실적 요약 뷰
CREATE VIEW v_driver_performance_summary AS
SELECT 
    d.id as driver_id,
    d.driver_code,
    d.driver_name,
    d.vehicle_type,
    d.rating,
    d.total_orders,
    d.completed_orders,
    d.cancelled_orders,
    CASE 
        WHEN d.total_orders > 0 THEN ROUND((d.completed_orders / d.total_orders) * 100, 2)
        ELSE 0
    END as completion_rate,
    CASE 
        WHEN d.total_orders > 0 THEN ROUND((d.cancelled_orders / d.total_orders) * 100, 2)
        ELSE 0
    END as cancellation_rate,
    COUNT(dr.id) as total_ratings,
    AVG(dr.rating) as avg_rating,
    AVG(dr.punctuality_rating) as avg_punctuality,
    AVG(dr.service_rating) as avg_service,
    AVG(dr.communication_rating) as avg_communication
FROM tbl_drivers d
LEFT JOIN tbl_driver_ratings dr ON d.id = dr.driver_id
WHERE d.is_active = TRUE
GROUP BY d.id, d.driver_code, d.driver_name, d.vehicle_type, d.rating, d.total_orders, d.completed_orders, d.cancelled_orders;

-- 배달기사 근무 통계 뷰
CREATE VIEW v_driver_work_statistics AS
SELECT 
    d.id as driver_id,
    d.driver_code,
    d.driver_name,
    DATE(dwl.start_time) as work_date,
    COUNT(*) as total_works,
    SUM(CASE WHEN dwl.work_type = 'pickup' THEN 1 ELSE 0 END) as pickup_count,
    SUM(CASE WHEN dwl.work_type = 'delivery' THEN 1 ELSE 0 END) as delivery_count,
    SUM(CASE WHEN dwl.work_type = 'round_trip' THEN 1 ELSE 0 END) as round_trip_count,
    SUM(dwl.duration_minutes) as total_work_minutes,
    SUM(dwl.distance_km) as total_distance_km,
    SUM(dwl.total_cost) as total_cost,
    AVG(dwl.duration_minutes) as avg_duration_minutes,
    AVG(dwl.distance_km) as avg_distance_km
FROM tbl_drivers d
LEFT JOIN tbl_driver_work_logs dwl ON d.id = dwl.driver_id
WHERE d.is_active = TRUE
  AND dwl.start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY d.id, d.driver_code, d.driver_name, DATE(dwl.start_time);

-- =====================================================
-- 초기 데이터 삽입 (배달기사 관리)
-- =====================================================

-- 배달기사 등록 예시
INSERT INTO tbl_drivers (driver_code, driver_name, phone, email, license_number, license_type, vehicle_type, vehicle_number, vehicle_model, status, service_areas, work_schedule, hourly_rate, commission_rate) VALUES
('DRV-0001', '김기사', '010-1234-5678', 'driver1@example.com', '11-12-345678-90', 'type2', 'motorcycle', '12가3456', '현대 스쿠터', 'active', '["강남구", "서초구", "송파구"]', '{"monday": {"start": "09:00", "end": "18:00"}, "tuesday": {"start": "09:00", "end": "18:00"}}', 15000.00, 5.0),
('DRV-0002', '이기사', '010-2345-6789', 'driver2@example.com', '11-23-456789-01', 'type1', 'van', '23나7890', '기아 봉고', 'active', '["마포구", "용산구", "중구"]', '{"monday": {"start": "08:00", "end": "20:00"}, "tuesday": {"start": "08:00", "end": "20:00"}}', 20000.00, 4.5),
('DRV-0003', '박기사', '010-3456-7890', 'driver3@example.com', '11-34-567890-12', 'type2_auto', 'truck', '34다1234', '현대 포터', 'on_duty', '["영등포구", "금천구", "구로구"]', '{"monday": {"start": "07:00", "end": "19:00"}, "tuesday": {"start": "07:00", "end": "19:00"}}', 25000.00, 4.0);

-- 배달기사 서비스 타입 연결 예시
INSERT INTO tbl_driver_service_types (driver_id, service_type_id, is_available, priority_order) VALUES
(1, 1, TRUE, 1), -- 김기사 - 퀵오토바이
(1, 3, TRUE, 2), -- 김기사 - 퀵플렉스
(2, 2, TRUE, 1), -- 이기사 - 퀵차량
(2, 4, TRUE, 2), -- 이기사 - 퀵이사
(3, 2, TRUE, 1), -- 박기사 - 퀵차량
(3, 4, TRUE, 2); -- 박기사 - 퀵이사

-- =====================================================
-- 배달기사 관리 시스템 완료
-- =====================================================
