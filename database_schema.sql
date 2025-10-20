-- =====================================================
-- STN MailCenter 주문접수 시스템 데이터베이스 스키마
-- 설계일: 2025년
-- 목적: 24개 서비스 타입의 확장 가능한 주문접수 시스템
-- 특징: FK 없는 고성능 설계, 서비스별 전용 테이블 구조
-- =====================================================

-- 1. 서비스 타입 마스터 테이블
-- 모든 주문접수 서비스의 기본 정보를 관리
CREATE TABLE tbl_service_types (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '서비스 타입 고유 ID',
    service_code VARCHAR(50) UNIQUE NOT NULL COMMENT '서비스 코드 (URL 경로용): quick-motorcycle, international 등',
    service_name VARCHAR(100) NOT NULL COMMENT '서비스 표시명: 퀵오토바이, 해외특송서비스 등',
    service_category VARCHAR(50) COMMENT '서비스 카테고리: quick, parcel, life, general, special',
    is_active BOOLEAN DEFAULT TRUE COMMENT '서비스 활성화 여부 (비활성화 시 주문 접수 불가)',
    sort_order INT DEFAULT 0 COMMENT '서비스 목록 정렬 순서',
    description TEXT COMMENT '서비스 설명',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '서비스 등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '서비스 수정일시',
    
    -- 인덱스 설정
    INDEX idx_service_code (service_code) COMMENT '서비스 코드 검색용 인덱스',
    INDEX idx_service_category (service_category) COMMENT '카테고리별 서비스 조회용 인덱스',
    INDEX idx_service_active (is_active) COMMENT '활성 서비스 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='주문접수 서비스 타입 마스터 테이블';

-- 2. 주문 기본 정보 테이블 (공통 필드)
-- 모든 주문의 공통 정보를 저장하는 메인 테이블
CREATE TABLE tbl_orders (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '주문 고유 ID',
    user_id INT NOT NULL COMMENT '주문자 사용자 ID (users 테이블 참조)',
    service_type_id INT NOT NULL COMMENT '서비스 타입 ID (service_types 테이블 참조)',
    order_number VARCHAR(50) UNIQUE NOT NULL COMMENT '주문번호 (자동 생성): ORD-YYYYMMDD-0001 형식',
    
    -- 주문자 정보 (공통)
    company_name VARCHAR(100) COMMENT '주문자 회사명',
    contact VARCHAR(20) COMMENT '주문자 연락처',
    address TEXT COMMENT '주문자 주소',
    
    -- 출발지 정보 (공통)
    departure_address TEXT COMMENT '출발지 주소',
    departure_detail VARCHAR(255) COMMENT '출발지 상세주소',
    departure_contact VARCHAR(20) COMMENT '출발지 연락처',
    
    -- 경유지 정보 (배송방법이 경유일 때)
    waypoint_address TEXT COMMENT '경유지 주소',
    waypoint_detail VARCHAR(255) COMMENT '경유지 상세주소',
    waypoint_contact VARCHAR(20) COMMENT '경유지 연락처',
    waypoint_notes TEXT COMMENT '경유지 특이사항',
    
    -- 도착지 정보 (공통)
    destination_type ENUM('direct', 'mailroom') DEFAULT 'direct' COMMENT '도착지 타입: 직접배송, 메일룸배송',
    mailroom VARCHAR(100) COMMENT '메일룸명 (destination_type이 mailroom일 때)',
    destination_address TEXT COMMENT '도착지 주소',
    detail_address VARCHAR(255) COMMENT '도착지 상세주소',
    destination_contact VARCHAR(20) COMMENT '도착지 연락처',
    
    -- 배송 물품 정보 (공통)
    item_type VARCHAR(50) COMMENT '물품 종류',
    quantity INT COMMENT '물품 수량',
    unit VARCHAR(20) COMMENT '수량 단위: 개, 박스, kg 등',
    delivery_content TEXT COMMENT '배송 내용 상세',
    
    -- 주문 상태 및 금액
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending' COMMENT '주문 상태',
    total_amount DECIMAL(10,2) DEFAULT 0 COMMENT '총 금액',
    payment_type ENUM('cash_on_delivery', 'cash_in_advance', 'bank_transfer', 'credit_transaction') COMMENT '결제 방식',
    notes TEXT COMMENT '주문 특이사항 및 메모',
    
    -- 시스템 필드
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '주문 접수일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '주문 수정일시',
    
    -- 인덱스 설정 (FK 없이 성능 최적화)
    INDEX idx_orders_user_id (user_id) COMMENT '사용자별 주문 조회용 인덱스',
    INDEX idx_orders_service_type (service_type_id) COMMENT '서비스별 주문 조회용 인덱스',
    INDEX idx_orders_status (status) COMMENT '상태별 주문 조회용 인덱스',
    INDEX idx_orders_created_at (created_at) COMMENT '날짜별 주문 조회용 인덱스',
    INDEX idx_orders_order_number (order_number) COMMENT '주문번호 검색용 인덱스',
    INDEX idx_orders_user_service (user_id, service_type_id) COMMENT '사용자-서비스 복합 조회용 인덱스',
    INDEX idx_orders_status_created (status, created_at) COMMENT '상태-날짜 복합 조회용 인덱스',
    INDEX idx_orders_payment_type (payment_type) COMMENT '결제방식별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='주문 기본 정보 테이블 (공통 필드)';

-- 3. 퀵 서비스 전용 테이블
-- 퀵오토바이, 퀵차량, 퀵플렉스, 퀵이사 서비스의 특화 데이터
CREATE TABLE tbl_orders_quick (
    order_id INT PRIMARY KEY COMMENT '주문 ID (orders 테이블과 1:1 관계)',
    delivery_method ENUM('motorcycle', 'vehicle', 'flex', 'moving') NOT NULL COMMENT '배송 수단: 오토바이, 차량, 플렉스, 이사',
    urgency_level ENUM('normal', 'urgent', 'super_urgent') DEFAULT 'normal' COMMENT '긴급도: 일반, 긴급, 초긴급',
    estimated_time INT COMMENT '예상 소요시간 (분)',
    pickup_time DATETIME COMMENT '픽업 예정 시간',
    delivery_time DATETIME COMMENT '배송 완료 예정 시간',
    driver_contact VARCHAR(20) COMMENT '기사 연락처',
    vehicle_info VARCHAR(100) COMMENT '차량 정보 (차량번호, 모델 등)',
    
    -- 주소 및 배송 정보 (JSON에서 분리된 컬럼들)
    departure_address VARCHAR(255) DEFAULT '' COMMENT '출발지 주소',
    destination_address VARCHAR(255) DEFAULT '' COMMENT '도착지 주소',
    delivery_instructions TEXT DEFAULT '' COMMENT '배송 지시사항',
    delivery_route VARCHAR(50) DEFAULT '' COMMENT '배송 경로 (one_way, round_trip)',
    
    -- 패키지 정보 (JSON에서 분리된 컬럼들)
    box_selection VARCHAR(50) DEFAULT '' COMMENT '박스 선택 (small, medium, large)',
    box_quantity INT DEFAULT 0 COMMENT '박스 수량',
    pouch_selection VARCHAR(50) DEFAULT '' COMMENT '행낭 선택',
    pouch_quantity INT DEFAULT 0 COMMENT '행낭 수량',
    shopping_bag_selection VARCHAR(50) DEFAULT '' COMMENT '쇼핑백 선택',
    
    -- 기존 JSON 필드 (호환성을 위해 유지)
    special_instructions TEXT COMMENT '특별 지시사항 (JSON 형태, 호환성용)',
    additional_fee DECIMAL(8,2) DEFAULT 0 COMMENT '추가 요금 (야간, 주말 할증 등)',
    
    -- 인덱스 설정
    INDEX idx_quick_delivery_method (delivery_method) COMMENT '배송수단별 조회용 인덱스',
    INDEX idx_quick_urgency (urgency_level) COMMENT '긴급도별 조회용 인덱스',
    INDEX idx_quick_pickup_time (pickup_time) COMMENT '픽업시간별 조회용 인덱스',
    INDEX idx_quick_driver_contact (driver_contact) COMMENT '기사 연락처 검색용 인덱스',
    INDEX idx_quick_departure_address (departure_address) COMMENT '출발지 주소 검색용 인덱스',
    INDEX idx_quick_destination_address (destination_address) COMMENT '도착지 주소 검색용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='퀵 서비스 전용 데이터 테이블';

-- 4. 택배 서비스 전용 테이블
-- 방문택배, 당일택배, 편의점택배, 택배백 서비스의 특화 데이터
CREATE TABLE tbl_orders_parcel (
    order_id INT PRIMARY KEY COMMENT '주문 ID (orders 테이블과 1:1 관계)',
    parcel_type ENUM('visit', 'same_day', 'convenience', 'bag') NOT NULL COMMENT '택배 타입: 방문, 당일, 편의점, 행낭',
    weight DECIMAL(5,2) COMMENT '물품 무게 (kg)',
    dimensions VARCHAR(50) COMMENT '물품 크기 (가로x세로x높이 cm)',
    insurance_amount DECIMAL(10,2) DEFAULT 0 COMMENT '보험 금액',
    signature_required BOOLEAN DEFAULT FALSE COMMENT '서명 필요 여부',
    fragile BOOLEAN DEFAULT FALSE COMMENT '파손 주의 물품 여부',
    tracking_number VARCHAR(100) COMMENT '송장번호',
    courier_company VARCHAR(50) COMMENT '택배회사',
    estimated_delivery_date DATE COMMENT '예상 배송일',
    delivery_attempts INT DEFAULT 0 COMMENT '배송 시도 횟수',
    return_reason VARCHAR(255) COMMENT '반송 사유',
    
    -- 인덱스 설정
    INDEX idx_parcel_type (parcel_type) COMMENT '택배타입별 조회용 인덱스',
    INDEX idx_parcel_weight (weight) COMMENT '무게별 조회용 인덱스',
    INDEX idx_parcel_tracking (tracking_number) COMMENT '송장번호 검색용 인덱스',
    INDEX idx_parcel_courier (courier_company) COMMENT '택배회사별 조회용 인덱스',
    INDEX idx_parcel_delivery_date (estimated_delivery_date) COMMENT '배송일별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='택배 서비스 전용 데이터 테이블';

-- 5. 생활 서비스 전용 테이블
-- 사다주기, 택시, 대리운전, 화환, 숙박, 문구 서비스의 특화 데이터
CREATE TABLE tbl_orders_life (
    order_id INT PRIMARY KEY COMMENT '주문 ID (orders 테이블과 1:1 관계)',
    service_subtype ENUM('buy', 'taxi', 'driver', 'wreath', 'accommodation', 'stationery') NOT NULL COMMENT '생활서비스 세부타입',
    delivery_type ENUM('normal', 'express') DEFAULT 'normal' COMMENT '배송 형태: 일반, 급송',
    delivery_method ENUM('one_way', 'round_trip') DEFAULT 'one_way' COMMENT '배송 방법: 편도, 왕복',
    pickup_time DATETIME COMMENT '픽업/이용 예정 시간',
    estimated_duration INT COMMENT '예상 소요시간 (분)',
    service_provider VARCHAR(100) COMMENT '서비스 제공업체',
    provider_contact VARCHAR(20) COMMENT '제공업체 연락처',
    service_location VARCHAR(255) COMMENT '서비스 제공 장소',
    special_requirements TEXT COMMENT '특별 요구사항',
    additional_services JSON COMMENT '추가 서비스 정보 (JSON 형태)',
    
    -- 인덱스 설정
    INDEX idx_life_subtype (service_subtype) COMMENT '서비스 세부타입별 조회용 인덱스',
    INDEX idx_life_pickup_time (pickup_time) COMMENT '픽업시간별 조회용 인덱스',
    INDEX idx_life_provider (service_provider) COMMENT '제공업체별 조회용 인덱스',
    INDEX idx_life_delivery_type (delivery_type) COMMENT '배송형태별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='생활 서비스 전용 데이터 테이블';

-- 6. 일반 서비스 전용 테이블
-- 사내문서, 개인심부름, 세무컨설팅 서비스의 특화 데이터
CREATE TABLE tbl_orders_general (
    order_id INT PRIMARY KEY COMMENT '주문 ID (orders 테이블과 1:1 관계)',
    service_subtype ENUM('document', 'errand', 'tax') NOT NULL COMMENT '일반서비스 세부타입',
    document_type VARCHAR(100) COMMENT '문서 종류 (사내문서 서비스용)',
    errand_type VARCHAR(100) COMMENT '심부름 종류 (개인심부름 서비스용)',
    tax_category VARCHAR(100) COMMENT '세무 카테고리 (세무컨설팅 서비스용)',
    deadline DATETIME COMMENT '완료 마감일시',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal' COMMENT '우선순위',
    assigned_to VARCHAR(100) COMMENT '담당자',
    completion_notes TEXT COMMENT '완료 메모',
    attachments JSON COMMENT '첨부파일 정보 (JSON 형태)',
    
    -- 인덱스 설정
    INDEX idx_general_subtype (service_subtype) COMMENT '서비스 세부타입별 조회용 인덱스',
    INDEX idx_general_deadline (deadline) COMMENT '마감일별 조회용 인덱스',
    INDEX idx_general_priority (priority) COMMENT '우선순위별 조회용 인덱스',
    INDEX idx_general_assigned (assigned_to) COMMENT '담당자별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='일반 서비스 전용 데이터 테이블';

-- 7. 해외/연계 서비스 전용 테이블
-- 해외특송, 연계버스, 연계KTX, 연계공항, 연계해운, 우편 서비스의 특화 데이터
CREATE TABLE tbl_orders_special (
    order_id INT PRIMARY KEY COMMENT '주문 ID (orders 테이블과 1:1 관계)',
    service_subtype ENUM('international', 'linked_bus', 'linked_ktx', 'linked_airport', 'linked_shipping', 'postal') NOT NULL COMMENT '특수서비스 세부타입',
    country_code VARCHAR(3) COMMENT '국가 코드 (해외특송용)',
    customs_declaration TEXT COMMENT '통관 신고서 내용',
    tracking_number VARCHAR(100) COMMENT '추적번호/송장번호',
    linked_service_info JSON COMMENT '연계 서비스 정보 (JSON 형태)',
    departure_terminal VARCHAR(100) COMMENT '출발 터미널/역',
    arrival_terminal VARCHAR(100) COMMENT '도착 터미널/역',
    departure_time DATETIME COMMENT '출발 시간',
    arrival_time DATETIME COMMENT '도착 시간',
    seat_number VARCHAR(20) COMMENT '좌석번호',
    ticket_number VARCHAR(50) COMMENT '티켓번호',
    special_handling TEXT COMMENT '특별 취급 사항',
    
    -- 인덱스 설정
    INDEX idx_special_subtype (service_subtype) COMMENT '서비스 세부타입별 조회용 인덱스',
    INDEX idx_special_country (country_code) COMMENT '국가별 조회용 인덱스',
    INDEX idx_special_tracking (tracking_number) COMMENT '추적번호 검색용 인덱스',
    INDEX idx_special_departure_time (departure_time) COMMENT '출발시간별 조회용 인덱스',
    INDEX idx_special_terminals (departure_terminal, arrival_terminal) COMMENT '터미널별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='해외/연계 서비스 전용 데이터 테이블';

-- =====================================================
-- 초기 데이터 삽입 (서비스 타입 마스터 데이터)
-- =====================================================

-- 퀵 서비스 카테고리
INSERT INTO tbl_service_types (service_code, service_name, service_category, sort_order, description) VALUES
('quick-motorcycle', '퀵오토바이', 'quick', 1, '오토바이를 이용한 빠른 배송 서비스'),
('quick-vehicle', '퀵차량', 'quick', 2, '차량을 이용한 배송 서비스'),
('quick-flex', '퀵플렉스', 'quick', 3, '유연한 배송 옵션을 제공하는 서비스'),
('quick-moving', '퀵이사', 'quick', 4, '소규모 이사 서비스');

-- 택배 서비스 카테고리
INSERT INTO tbl_service_types (service_code, service_name, service_category, sort_order, description) VALUES
('parcel-visit', '방문택배', 'parcel', 5, '고객 방문을 통한 택배 서비스'),
('parcel-same-day', '당일택배', 'parcel', 6, '당일 배송이 가능한 택배 서비스'),
('parcel-convenience', '편의점택배', 'parcel', 7, '편의점을 통한 택배 서비스'),
('parcel-bag', '택배백', 'parcel', 8, '택배백을 이용한 소포 서비스');

-- 생활 서비스 카테고리
INSERT INTO tbl_service_types (service_code, service_name, service_category, sort_order, description) VALUES
('life-buy', '사다주기', 'life', 9, '생활용품 구매 대행 서비스'),
('life-taxi', '택시', 'life', 10, '택시 호출 서비스'),
('life-driver', '대리운전', 'life', 11, '대리운전 서비스'),
('life-wreath', '화환', 'life', 12, '화환 주문 서비스'),
('life-accommodation', '숙박', 'life', 13, '숙박 예약 서비스'),
('life-stationery', '문구', 'life', 14, '문구 주문 서비스');

-- 일반 서비스 카테고리
INSERT INTO tbl_service_types (service_code, service_name, service_category, sort_order, description) VALUES
('general-document', '사내문서', 'general', 15, '사내 문서 배송 서비스'),
('general-errand', '개인심부름', 'general', 16, '개인 심부름 서비스'),
('general-tax', '세무컨설팅', 'general', 17, '세무 컨설팅 서비스');

-- 해외/연계 서비스 카테고리
INSERT INTO tbl_service_types (service_code, service_name, service_category, sort_order, description) VALUES
('international', '해외특송', 'special', 18, '해외 특송 서비스'),
('linked-bus', '연계버스', 'special', 19, '버스 연계 서비스'),
('linked-ktx', '연계KTX', 'special', 20, 'KTX 연계 서비스'),
('linked-airport', '연계공항', 'special', 21, '공항 연계 서비스'),
('linked-shipping', '연계해운', 'special', 22, '해운 연계 서비스'),
('postal', '우편', 'special', 23, '우편 서비스'),
('mailroom', '메일룸', 'special', 24, '메일룸 서비스');

-- =====================================================
-- 성능 최적화를 위한 추가 인덱스
-- =====================================================

-- 복합 인덱스 (자주 함께 조회되는 컬럼들)
CREATE INDEX idx_orders_user_status_created ON tbl_orders(user_id, status, created_at) 
COMMENT '사용자별 상태별 날짜순 조회용 복합 인덱스';

CREATE INDEX idx_orders_service_status_created ON tbl_orders(service_type_id, status, created_at) 
COMMENT '서비스별 상태별 날짜순 조회용 복합 인덱스';

-- =====================================================
-- 뷰 생성 (자주 사용되는 조회 쿼리 최적화)
-- =====================================================

-- 주문과 서비스 정보를 함께 조회하는 뷰
CREATE VIEW v_orders_with_service AS
SELECT 
    o.id,
    o.order_number,
    o.user_id,
    o.company_name,
    o.contact,
    o.status,
    o.total_amount,
    o.created_at,
    st.service_code,
    st.service_name,
    st.service_category
FROM tbl_orders o
LEFT JOIN tbl_service_types st ON o.service_type_id = st.id
WHERE o.status != 'cancelled';

-- 퀵 서비스 주문 상세 조회 뷰
CREATE VIEW v_quick_orders AS
SELECT 
    o.*,
    st.service_name,
    q.delivery_method,
    q.urgency_level,
    q.estimated_time,
    q.pickup_time
FROM tbl_orders o
JOIN tbl_service_types st ON o.service_type_id = st.id
JOIN tbl_orders_quick q ON o.id = q.order_id
WHERE st.service_category = 'quick';

-- =====================================================
-- 고객사 계층 구조 및 권한 관리 테이블
-- =====================================================

-- 8. 고객사 계층 구조 테이블 (본점-지사-대리점)
CREATE TABLE tbl_customer_hierarchy (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '고객사 고유 ID',
    parent_id INT NULL COMMENT '상위 고객사 ID (본점은 NULL, 지사는 본점 ID, 대리점은 지사 ID)',
    customer_code VARCHAR(50) UNIQUE NOT NULL COMMENT '고객사 코드 (자동 생성)',
    customer_name VARCHAR(100) NOT NULL COMMENT '고객사명',
    hierarchy_level ENUM('head_office', 'branch', 'agency') NOT NULL COMMENT '계층 레벨: 본점, 지사, 대리점',
    business_number VARCHAR(20) COMMENT '사업자등록번호',
    representative_name VARCHAR(50) COMMENT '대표자명',
    contact_phone VARCHAR(20) COMMENT '대표 연락처',
    contact_email VARCHAR(100) COMMENT '대표 이메일',
    address TEXT COMMENT '주소',
    contract_start_date DATE COMMENT '계약 시작일',
    contract_end_date DATE COMMENT '계약 종료일',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_customer_parent (parent_id) COMMENT '상위 고객사 조회용 인덱스',
    INDEX idx_customer_code (customer_code) COMMENT '고객사 코드 검색용 인덱스',
    INDEX idx_customer_level (hierarchy_level) COMMENT '계층 레벨별 조회용 인덱스',
    INDEX idx_customer_active (is_active) COMMENT '활성 고객사 조회용 인덱스',
    INDEX idx_customer_contract (contract_start_date, contract_end_date) COMMENT '계약기간 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='고객사 계층 구조 관리 테이블';

-- 9. 사용자 테이블 (고객사 소속 사용자)
CREATE TABLE tbl_users (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '사용자 고유 ID',
    customer_id INT NOT NULL COMMENT '소속 고객사 ID',
    username VARCHAR(50) UNIQUE NOT NULL COMMENT '사용자명 (로그인 ID)',
    password VARCHAR(255) NOT NULL COMMENT '암호화된 비밀번호',
    real_name VARCHAR(50) NOT NULL COMMENT '실명',
    email VARCHAR(100) COMMENT '이메일',
    phone VARCHAR(20) COMMENT '연락처',
    department VARCHAR(50) COMMENT '부서',
    position VARCHAR(50) COMMENT '직급',
    user_role ENUM('super_admin', 'admin', 'manager', 'user') DEFAULT 'user' COMMENT '사용자 역할',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' COMMENT '사용자 상태',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
    last_login_at TIMESTAMP NULL COMMENT '마지막 로그인 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_users_customer (customer_id) COMMENT '고객사별 사용자 조회용 인덱스',
    INDEX idx_users_username (username) COMMENT '사용자명 검색용 인덱스',
    INDEX idx_users_role (user_role) COMMENT '역할별 사용자 조회용 인덱스',
    INDEX idx_users_status (status) COMMENT '상태별 사용자 조회용 인덱스',
    INDEX idx_users_active (is_active) COMMENT '활성 사용자 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='사용자 정보 테이블';

-- 10. 고객사별 서비스 권한 관리 테이블
CREATE TABLE tbl_customer_service_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '권한 고유 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID',
    service_type_id INT NOT NULL COMMENT '서비스 타입 ID',
    is_enabled BOOLEAN DEFAULT TRUE COMMENT '서비스 활성화 여부',
    max_daily_orders INT DEFAULT 0 COMMENT '일일 최대 주문 수 (0은 무제한)',
    max_monthly_orders INT DEFAULT 0 COMMENT '월간 최대 주문 수 (0은 무제한)',
    special_instructions TEXT COMMENT '특별 지시사항',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '권한 부여일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '권한 수정일시',
    
    -- 인덱스 설정
    UNIQUE KEY unique_customer_service (customer_id, service_type_id) COMMENT '고객사-서비스 중복 방지',
    INDEX idx_csp_customer (customer_id) COMMENT '고객사별 권한 조회용 인덱스',
    INDEX idx_csp_service (service_type_id) COMMENT '서비스별 권한 조회용 인덱스',
    INDEX idx_csp_enabled (is_enabled) COMMENT '활성화된 권한 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='고객사별 서비스 권한 관리 테이블';

-- 11. API 연동 설정 테이블
CREATE TABLE tbl_api_integrations (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'API 연동 고유 ID',
    service_type_id INT NOT NULL COMMENT '서비스 타입 ID',
    api_provider VARCHAR(100) NOT NULL COMMENT 'API 제공업체명',
    api_name VARCHAR(100) NOT NULL COMMENT 'API 서비스명',
    api_endpoint VARCHAR(500) COMMENT 'API 엔드포인트 URL',
    api_key VARCHAR(255) COMMENT 'API 키 (암호화 저장)',
    api_secret VARCHAR(255) COMMENT 'API 시크릿 (암호화 저장)',
    auth_type ENUM('api_key', 'oauth2', 'basic_auth', 'bearer_token') DEFAULT 'api_key' COMMENT '인증 방식',
    request_method ENUM('GET', 'POST', 'PUT', 'DELETE') DEFAULT 'POST' COMMENT '요청 방식',
    request_format ENUM('json', 'xml', 'form') DEFAULT 'json' COMMENT '요청 데이터 형식',
    response_format ENUM('json', 'xml') DEFAULT 'json' COMMENT '응답 데이터 형식',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'API 활성화 여부',
    timeout_seconds INT DEFAULT 30 COMMENT '타임아웃 시간 (초)',
    retry_count INT DEFAULT 3 COMMENT '재시도 횟수',
    webhook_url VARCHAR(500) COMMENT '웹훅 URL (상태 업데이트용)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_api_service (service_type_id) COMMENT '서비스별 API 조회용 인덱스',
    INDEX idx_api_provider (api_provider) COMMENT '제공업체별 API 조회용 인덱스',
    INDEX idx_api_active (is_active) COMMENT '활성 API 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='API 연동 설정 테이블';

-- 12. API 연동 로그 테이블
CREATE TABLE tbl_api_integration_logs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '로그 고유 ID',
    order_id INT NOT NULL COMMENT '주문 ID',
    api_integration_id INT NOT NULL COMMENT 'API 연동 ID',
    request_data JSON COMMENT '요청 데이터 (JSON)',
    response_data JSON COMMENT '응답 데이터 (JSON)',
    status ENUM('pending', 'success', 'failed', 'timeout') DEFAULT 'pending' COMMENT 'API 호출 상태',
    error_message TEXT COMMENT '에러 메시지',
    response_time_ms INT COMMENT '응답 시간 (밀리초)',
    retry_count INT DEFAULT 0 COMMENT '재시도 횟수',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'API 호출일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '상태 업데이트일시',
    
    -- 인덱스 설정
    INDEX idx_ail_order (order_id) COMMENT '주문별 API 로그 조회용 인덱스',
    INDEX idx_ail_api (api_integration_id) COMMENT 'API별 로그 조회용 인덱스',
    INDEX idx_ail_status (status) COMMENT '상태별 로그 조회용 인덱스',
    INDEX idx_ail_created (created_at) COMMENT '날짜별 로그 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='API 연동 로그 테이블';

-- 13. 주문 API 전송 상태 테이블
CREATE TABLE tbl_order_api_status (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '상태 고유 ID',
    order_id INT NOT NULL COMMENT '주문 ID',
    api_integration_id INT NOT NULL COMMENT 'API 연동 ID',
    external_order_id VARCHAR(100) COMMENT '외부 시스템 주문 ID',
    status ENUM('pending', 'sent', 'confirmed', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending' COMMENT '전송 상태',
    tracking_number VARCHAR(100) COMMENT '외부 시스템 추적번호',
    estimated_delivery_date DATETIME COMMENT '예상 배송일',
    actual_delivery_date DATETIME COMMENT '실제 배송일',
    status_message TEXT COMMENT '상태 메시지',
    last_sync_at TIMESTAMP NULL COMMENT '마지막 동기화 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    UNIQUE KEY unique_order_api (order_id, api_integration_id) COMMENT '주문-API 중복 방지',
    INDEX idx_oas_order (order_id) COMMENT '주문별 API 상태 조회용 인덱스',
    INDEX idx_oas_api (api_integration_id) COMMENT 'API별 상태 조회용 인덱스',
    INDEX idx_oas_status (status) COMMENT '상태별 조회용 인덱스',
    INDEX idx_oas_external_id (external_order_id) COMMENT '외부 주문ID 검색용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='주문 API 전송 상태 관리 테이블';

-- 14. 시스템 설정 테이블
CREATE TABLE tbl_system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '설정 고유 ID',
    setting_key VARCHAR(100) UNIQUE NOT NULL COMMENT '설정 키',
    setting_value TEXT COMMENT '설정 값',
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string' COMMENT '설정 값 타입',
    description TEXT COMMENT '설정 설명',
    is_public BOOLEAN DEFAULT FALSE COMMENT '공개 설정 여부 (사용자에게 노출)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_settings_key (setting_key) COMMENT '설정 키 검색용 인덱스',
    INDEX idx_settings_public (is_public) COMMENT '공개 설정 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='시스템 설정 관리 테이블';

-- =====================================================
-- 기존 테이블 수정 (orders 테이블에 customer_id 추가)
-- =====================================================

-- orders 테이블에 customer_id 컬럼 추가
ALTER TABLE tbl_orders ADD COLUMN customer_id INT NOT NULL COMMENT '고객사 ID' AFTER user_id;
ALTER TABLE tbl_orders ADD INDEX idx_orders_customer (customer_id) COMMENT '고객사별 주문 조회용 인덱스';

-- =====================================================
-- 초기 데이터 삽입 (시스템 설정)
-- =====================================================

-- 시스템 기본 설정
INSERT INTO tbl_system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('system_name', 'STN MailCenter', 'string', '시스템명', TRUE),
('version', '1.0.0', 'string', '시스템 버전', TRUE),
('max_file_size', '10485760', 'number', '최대 파일 업로드 크기 (바이트)', FALSE),
('api_timeout', '30', 'number', 'API 타임아웃 시간 (초)', FALSE),
('order_number_prefix', 'ORD', 'string', '주문번호 접두사', FALSE),
('enable_api_integration', 'true', 'boolean', 'API 연동 활성화 여부', FALSE);

-- =====================================================
-- 추가 뷰 생성 (권한 및 계층 구조 관련)
-- =====================================================

-- 사용자별 접근 가능한 서비스 조회 뷰
CREATE VIEW v_user_accessible_services AS
SELECT DISTINCT
    u.id as user_id,
    u.username,
    u.real_name,
    u.customer_id,
    ch.customer_name,
    ch.hierarchy_level,
    st.id as service_type_id,
    st.service_code,
    st.service_name,
    st.service_category,
    csp.is_enabled,
    csp.max_daily_orders,
    csp.max_monthly_orders
FROM tbl_users u
JOIN tbl_customer_hierarchy ch ON u.customer_id = ch.id
JOIN tbl_customer_service_permissions csp ON ch.id = csp.customer_id
JOIN tbl_service_types st ON csp.service_type_id = st.id
WHERE u.is_active = TRUE 
  AND ch.is_active = TRUE 
  AND csp.is_enabled = TRUE 
  AND st.is_active = TRUE;

-- 고객사 계층 구조 조회 뷰 (하위 고객사 포함)
CREATE VIEW v_customer_hierarchy_tree AS
SELECT 
    h.id,
    h.customer_code,
    h.customer_name,
    h.hierarchy_level,
    h.parent_id,
    p.customer_name as parent_name,
    p.hierarchy_level as parent_level,
    CASE 
        WHEN h.hierarchy_level = 'head_office' THEN 1
        WHEN h.hierarchy_level = 'branch' THEN 2
        WHEN h.hierarchy_level = 'agency' THEN 3
    END as level_depth
FROM tbl_customer_hierarchy h
LEFT JOIN tbl_customer_hierarchy p ON h.parent_id = p.id
WHERE h.is_active = TRUE;

-- 주문과 API 상태 통합 조회 뷰
CREATE VIEW v_orders_with_api_status AS
SELECT 
    o.id,
    o.order_number,
    o.customer_id,
    o.user_id,
    o.service_type_id,
    o.status as order_status,
    o.created_at,
    st.service_name,
    ai.api_provider,
    ai.api_name,
    oas.status as api_status,
    oas.external_order_id,
    oas.tracking_number,
    oas.estimated_delivery_date,
    oas.last_sync_at
FROM tbl_orders o
LEFT JOIN tbl_service_types st ON o.service_type_id = st.id
LEFT JOIN tbl_order_api_status oas ON o.id = oas.order_id
LEFT JOIN tbl_api_integrations ai ON oas.api_integration_id = ai.id;

-- =====================================================
-- 입점관리 시스템 테이블
-- =====================================================

-- 15. 입점 신청 관리 테이블
CREATE TABLE tbl_store_registration_requests (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '입점신청 고유 ID',
    applicant_type ENUM('new_company', 'existing_company_branch', 'existing_company_agency') NOT NULL COMMENT '신청자 타입',
    company_name VARCHAR(100) NOT NULL COMMENT '회사명',
    business_number VARCHAR(20) NOT NULL COMMENT '사업자등록번호',
    representative_name VARCHAR(50) NOT NULL COMMENT '대표자명',
    representative_phone VARCHAR(20) NOT NULL COMMENT '대표자 연락처',
    representative_email VARCHAR(100) NOT NULL COMMENT '대표자 이메일',
    company_address TEXT NOT NULL COMMENT '회사 주소',
    business_type VARCHAR(100) COMMENT '업종',
    employee_count INT COMMENT '직원 수',
    annual_revenue DECIMAL(15,2) COMMENT '연매출액',
    hierarchy_level ENUM('head_office', 'branch', 'agency') NOT NULL COMMENT '신청하는 계층 레벨',
    parent_customer_id INT NULL COMMENT '상위 고객사 ID (지사/대리점 신청 시)',
    primary_service_category ENUM('quick', 'parcel', 'life', 'general', 'special') NOT NULL COMMENT '주력 서비스 카테고리',
    expected_monthly_orders INT COMMENT '예상 월 주문량',
    contract_period INT COMMENT '희망 계약기간 (개월)',
    special_requirements TEXT COMMENT '특별 요구사항',
    business_license_file VARCHAR(255) COMMENT '사업자등록증 파일 경로',
    company_profile_file VARCHAR(255) COMMENT '회사 소개서 파일 경로',
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled') DEFAULT 'pending' COMMENT '승인 상태',
    review_notes TEXT COMMENT '심사 메모',
    reviewed_by INT NULL COMMENT '심사자 사용자 ID',
    reviewed_at TIMESTAMP NULL COMMENT '심사일시',
    approved_by INT NULL COMMENT '승인자 사용자 ID',
    approved_at TIMESTAMP NULL COMMENT '승인일시',
    rejection_reason TEXT COMMENT '거부 사유',
    contract_start_date DATE COMMENT '계약 시작일',
    contract_end_date DATE COMMENT '계약 종료일',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '신청일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_srr_business_number (business_number) COMMENT '사업자등록번호 검색용 인덱스',
    INDEX idx_srr_status (status) COMMENT '상태별 신청 조회용 인덱스',
    INDEX idx_srr_hierarchy (hierarchy_level) COMMENT '계층별 신청 조회용 인덱스',
    INDEX idx_srr_created (created_at) COMMENT '신청일별 조회용 인덱스',
    INDEX idx_srr_parent (parent_customer_id) COMMENT '상위 고객사별 신청 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='입점 신청 관리 테이블';

-- 16. 입점 신청 서비스 선택 테이블
CREATE TABLE tbl_store_registration_services (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '서비스 선택 고유 ID',
    registration_request_id INT NOT NULL COMMENT '입점신청 ID',
    service_type_id INT NOT NULL COMMENT '서비스 타입 ID',
    priority_order INT NOT NULL COMMENT '우선순위 (1=최우선, 2=보조, 3=필요시)',
    expected_monthly_orders INT COMMENT '예상 월 주문량',
    expected_monthly_amount DECIMAL(15,2) COMMENT '예상 월 주문 금액',
    notes TEXT COMMENT '서비스 사용 계획 및 메모',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
    
    -- 인덱스 설정
    INDEX idx_srs_registration (registration_request_id) COMMENT '입점신청별 서비스 조회용 인덱스',
    INDEX idx_srs_service (service_type_id) COMMENT '서비스별 신청 조회용 인덱스',
    INDEX idx_srs_priority (priority_order) COMMENT '우선순위별 조회용 인덱스',
    UNIQUE KEY unique_registration_service (registration_request_id, service_type_id) COMMENT '신청-서비스 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='입점 신청 서비스 선택 테이블';

-- 17. 입점 심사 평가 테이블
CREATE TABLE tbl_store_evaluation (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '평가 고유 ID',
    registration_request_id INT NOT NULL COMMENT '입점신청 ID',
    evaluator_id INT NOT NULL COMMENT '평가자 사용자 ID',
    evaluation_criteria JSON COMMENT '평가 기준 및 점수 (JSON)',
    total_score DECIMAL(5,2) COMMENT '총점',
    evaluation_notes TEXT COMMENT '평가 의견',
    recommendation ENUM('approve', 'reject', 'conditional_approve') COMMENT '추천 의견',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '평가일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_se_request (registration_request_id) COMMENT '신청별 평가 조회용 인덱스',
    INDEX idx_se_evaluator (evaluator_id) COMMENT '평가자별 평가 조회용 인덱스',
    INDEX idx_se_recommendation (recommendation) COMMENT '추천의견별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='입점 심사 평가 테이블';

-- 18. 입점 계약 관리 테이블
CREATE TABLE tbl_store_contracts (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '계약 고유 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID',
    registration_request_id INT NOT NULL COMMENT '입점신청 ID',
    contract_number VARCHAR(50) UNIQUE NOT NULL COMMENT '계약번호',
    contract_type ENUM('head_office', 'branch', 'agency') NOT NULL COMMENT '계약 타입',
    contract_start_date DATE NOT NULL COMMENT '계약 시작일',
    contract_end_date DATE NOT NULL COMMENT '계약 종료일',
    contract_amount DECIMAL(15,2) COMMENT '계약 금액',
    commission_rate DECIMAL(5,2) COMMENT '수수료율 (%)',
    minimum_guarantee DECIMAL(15,2) COMMENT '최소 보장 금액',
    payment_terms VARCHAR(100) COMMENT '결제 조건',
    contract_terms TEXT COMMENT '계약 조건',
    special_conditions TEXT COMMENT '특별 조건',
    contract_file VARCHAR(255) COMMENT '계약서 파일 경로',
    status ENUM('active', 'suspended', 'terminated', 'expired') DEFAULT 'active' COMMENT '계약 상태',
    terminated_at TIMESTAMP NULL COMMENT '계약 종료일시',
    termination_reason TEXT COMMENT '계약 종료 사유',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '계약 체결일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '계약 수정일시',
    
    -- 인덱스 설정
    INDEX idx_sc_customer (customer_id) COMMENT '고객사별 계약 조회용 인덱스',
    INDEX idx_sc_contract_number (contract_number) COMMENT '계약번호 검색용 인덱스',
    INDEX idx_sc_status (status) COMMENT '계약상태별 조회용 인덱스',
    INDEX idx_sc_dates (contract_start_date, contract_end_date) COMMENT '계약기간별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='입점 계약 관리 테이블';

-- 19. 입점 실적 관리 테이블
CREATE TABLE tbl_store_performance (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '실적 고유 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID',
    performance_year INT NOT NULL COMMENT '실적 년도',
    performance_month INT NOT NULL COMMENT '실적 월',
    total_orders INT DEFAULT 0 COMMENT '총 주문 수',
    total_amount DECIMAL(15,2) DEFAULT 0 COMMENT '총 주문 금액',
    commission_amount DECIMAL(15,2) DEFAULT 0 COMMENT '수수료 금액',
    payment_amount DECIMAL(15,2) DEFAULT 0 COMMENT '정산 금액',
    service_breakdown JSON COMMENT '서비스별 실적 (JSON)',
    performance_rating ENUM('excellent', 'good', 'average', 'poor') COMMENT '실적 등급',
    notes TEXT COMMENT '실적 메모',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '실적 등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '실적 수정일시',
    
    -- 인덱스 설정
    UNIQUE KEY unique_customer_month (customer_id, performance_year, performance_month) COMMENT '고객사-월별 실적 중복 방지',
    INDEX idx_sp_customer (customer_id) COMMENT '고객사별 실적 조회용 인덱스',
    INDEX idx_sp_period (performance_year, performance_month) COMMENT '기간별 실적 조회용 인덱스',
    INDEX idx_sp_rating (performance_rating) COMMENT '등급별 실적 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='입점 실적 관리 테이블';

-- 20. 입점 정산 관리 테이블
CREATE TABLE tbl_store_settlements (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '정산 고유 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID',
    settlement_period VARCHAR(10) NOT NULL COMMENT '정산 기간 (YYYY-MM)',
    total_orders INT DEFAULT 0 COMMENT '총 주문 수',
    total_amount DECIMAL(15,2) DEFAULT 0 COMMENT '총 주문 금액',
    commission_rate DECIMAL(5,2) COMMENT '수수료율 (%)',
    commission_amount DECIMAL(15,2) DEFAULT 0 COMMENT '수수료 금액',
    settlement_amount DECIMAL(15,2) DEFAULT 0 COMMENT '정산 금액',
    payment_method ENUM('bank_transfer', 'check', 'cash') DEFAULT 'bank_transfer' COMMENT '정산 방법',
    payment_date DATE COMMENT '정산일',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending' COMMENT '정산 상태',
    payment_reference VARCHAR(100) COMMENT '정산 참조번호',
    notes TEXT COMMENT '정산 메모',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '정산 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '정산 수정일시',
    
    -- 인덱스 설정
    UNIQUE KEY unique_customer_period (customer_id, settlement_period) COMMENT '고객사-기간별 정산 중복 방지',
    INDEX idx_ss_customer (customer_id) COMMENT '고객사별 정산 조회용 인덱스',
    INDEX idx_ss_period (settlement_period) COMMENT '기간별 정산 조회용 인덱스',
    INDEX idx_ss_status (payment_status) COMMENT '정산상태별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='입점 정산 관리 테이블';

-- 21. 입점 알림 관리 테이블
CREATE TABLE tbl_store_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '알림 고유 ID',
    customer_id INT NULL COMMENT '대상 고객사 ID (NULL이면 전체)',
    user_id INT NULL COMMENT '대상 사용자 ID (NULL이면 고객사 전체)',
    notification_type ENUM('registration_approved', 'registration_rejected', 'contract_expiring', 'performance_alert', 'settlement_ready', 'system_announcement') NOT NULL COMMENT '알림 타입',
    title VARCHAR(200) NOT NULL COMMENT '알림 제목',
    message TEXT NOT NULL COMMENT '알림 내용',
    related_id INT NULL COMMENT '관련 데이터 ID (신청ID, 계약ID 등)',
    is_read BOOLEAN DEFAULT FALSE COMMENT '읽음 여부',
    read_at TIMESTAMP NULL COMMENT '읽은 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '알림 생성일시',
    
    -- 인덱스 설정
    INDEX idx_sn_customer (customer_id) COMMENT '고객사별 알림 조회용 인덱스',
    INDEX idx_sn_user (user_id) COMMENT '사용자별 알림 조회용 인덱스',
    INDEX idx_sn_type (notification_type) COMMENT '알림타입별 조회용 인덱스',
    INDEX idx_sn_read (is_read) COMMENT '읽음상태별 조회용 인덱스',
    INDEX idx_sn_created (created_at) COMMENT '생성일별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='입점 알림 관리 테이블';

-- =====================================================
-- 입점관리 관련 뷰 생성
-- =====================================================

-- 입점 신청 현황 조회 뷰
CREATE VIEW v_store_registration_status AS
SELECT 
    srr.id,
    srr.company_name,
    srr.business_number,
    srr.representative_name,
    srr.hierarchy_level,
    srr.primary_service_category,
    srr.status,
    srr.created_at,
    srr.reviewed_at,
    srr.approved_at,
    ch.customer_name as parent_company_name,
    u.real_name as reviewer_name,
    u2.real_name as approver_name,
    DATEDIFF(NOW(), srr.created_at) as days_since_application,
    (SELECT COUNT(*) FROM tbl_store_registration_services srs WHERE srs.registration_request_id = srr.id) as requested_services_count
FROM tbl_store_registration_requests srr
LEFT JOIN tbl_customer_hierarchy ch ON srr.parent_customer_id = ch.id
LEFT JOIN tbl_users u ON srr.reviewed_by = u.id
LEFT JOIN tbl_users u2 ON srr.approved_by = u2.id;

-- 입점 신청 서비스 선택 상세 뷰
CREATE VIEW v_store_registration_services_detail AS
SELECT 
    srs.id,
    srs.registration_request_id,
    srr.company_name,
    srr.business_number,
    srs.service_type_id,
    st.service_code,
    st.service_name,
    st.service_category,
    srs.priority_order,
    srs.expected_monthly_orders,
    srs.expected_monthly_amount,
    srs.notes,
    srs.created_at
FROM tbl_store_registration_services srs
JOIN tbl_store_registration_requests srr ON srs.registration_request_id = srr.id
JOIN tbl_service_types st ON srs.service_type_id = st.id
ORDER BY srs.registration_request_id, srs.priority_order;

-- 고객사별 실적 요약 뷰
CREATE VIEW v_customer_performance_summary AS
SELECT 
    ch.id as customer_id,
    ch.customer_name,
    ch.hierarchy_level,
    sp.performance_year,
    sp.performance_month,
    sp.total_orders,
    sp.total_amount,
    sp.commission_amount,
    sp.performance_rating,
    sc.contract_number,
    sc.contract_end_date,
    DATEDIFF(sc.contract_end_date, NOW()) as days_until_expiry
FROM tbl_customer_hierarchy ch
LEFT JOIN tbl_store_performance sp ON ch.id = sp.customer_id
LEFT JOIN tbl_store_contracts sc ON ch.id = sc.customer_id AND sc.status = 'active'
WHERE ch.is_active = TRUE;

-- 정산 대기 목록 뷰
CREATE VIEW v_pending_settlements AS
SELECT 
    ch.id as customer_id,
    ch.customer_name,
    ch.hierarchy_level,
    ss.settlement_period,
    ss.total_orders,
    ss.total_amount,
    ss.commission_amount,
    ss.settlement_amount,
    ss.payment_status,
    ss.payment_date,
    DATEDIFF(NOW(), ss.created_at) as days_since_created
FROM tbl_customer_hierarchy ch
JOIN tbl_store_settlements ss ON ch.id = ss.customer_id
WHERE ss.payment_status = 'pending'
ORDER BY ss.created_at ASC;

-- =====================================================
-- 입점관리 시스템 초기 데이터
-- =====================================================

-- 입점 심사 기준 설정
INSERT INTO tbl_system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('store_evaluation_criteria', '{"business_stability": 30, "financial_status": 25, "service_capability": 25, "market_potential": 20}', 'json', '입점 심사 기준 및 가중치', FALSE),
('minimum_monthly_orders', '100', 'number', '최소 월 주문량 기준', FALSE),
('default_commission_rate', '5.0', 'number', '기본 수수료율 (%)', FALSE),
('contract_default_period', '12', 'number', '기본 계약기간 (개월)', FALSE),
('settlement_cycle', 'monthly', 'string', '정산 주기 (monthly, quarterly)', FALSE),
('service_selection_required', 'true', 'boolean', '입점 신청 시 서비스 선택 필수 여부', FALSE),
('max_services_per_application', '10', 'number', '신청 가능한 최대 서비스 수', FALSE);

-- 입점 신청 서비스 선택 예시 데이터 (참고용)
-- tbl_store_registration_services 테이블 사용 예시:
-- 
-- 입점 신청 ID 1번이 다음 서비스들을 선택한 경우:
-- INSERT INTO tbl_store_registration_services VALUES
-- (1, 1, 1, 1, 200, 2000000, '주력 서비스로 퀵오토바이 이용', NOW()),
-- (2, 1, 2, 2, 100, 1500000, '보조 서비스로 퀵차량 이용', NOW()),
-- (3, 1, 5, 3, 50, 500000, '필요시 방문택배 이용', NOW()),
-- (4, 1, 6, 4, 30, 300000, '긴급시 당일택배 이용', NOW());










-- =====================================================
-- 알림 시스템 테이블
-- =====================================================

-- 22. 알림 채널 관리 테이블
CREATE TABLE tbl_notification_channels (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '알림 채널 고유 ID',
    channel_name VARCHAR(50) UNIQUE NOT NULL COMMENT '채널명',
    channel_type ENUM('email', 'sms', 'push', 'webhook', 'in_app') NOT NULL COMMENT '채널 타입',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
    config JSON COMMENT '채널 설정 (JSON)',
    priority INT DEFAULT 1 COMMENT '우선순위 (1: 높음, 5: 낮음)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_nc_type (channel_type) COMMENT '채널타입별 조회용 인덱스',
    INDEX idx_nc_active (is_active) COMMENT '활성 채널 조회용 인덱스',
    INDEX idx_nc_priority (priority) COMMENT '우선순위별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='알림 채널 관리 테이블';

-- 23. 알림 템플릿 관리 테이블
CREATE TABLE tbl_notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '템플릿 고유 ID',
    template_code VARCHAR(100) UNIQUE NOT NULL COMMENT '템플릿 코드',
    template_name VARCHAR(200) NOT NULL COMMENT '템플릿명',
    template_type ENUM('order', 'store', 'system', 'marketing') NOT NULL COMMENT '템플릿 타입',
    channel_type ENUM('email', 'sms', 'push', 'webhook', 'in_app') NOT NULL COMMENT '채널 타입',
    subject VARCHAR(500) COMMENT '제목 (이메일용)',
    content TEXT NOT NULL COMMENT '알림 내용',
    variables JSON COMMENT '사용 가능한 변수 목록 (JSON)',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    -- 인덱스 설정
    INDEX idx_nt_code (template_code) COMMENT '템플릿 코드 검색용 인덱스',
    INDEX idx_nt_type (template_type) COMMENT '템플릿타입별 조회용 인덱스',
    INDEX idx_nt_channel (channel_type) COMMENT '채널타입별 조회용 인덱스',
    INDEX idx_nt_active (is_active) COMMENT '활성 템플릿 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='알림 템플릿 관리 테이블';

-- 24. 통합 알림 큐 테이블
CREATE TABLE tbl_notification_queue (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '알림 큐 고유 ID',
    notification_type ENUM('order', 'store', 'system', 'marketing') NOT NULL COMMENT '알림 타입',
    recipient_type ENUM('user', 'customer', 'admin', 'all') NOT NULL COMMENT '수신자 타입',
    recipient_id INT NULL COMMENT '수신자 ID (NULL이면 전체)',
    recipient_email VARCHAR(100) COMMENT '수신자 이메일',
    recipient_phone VARCHAR(20) COMMENT '수신자 전화번호',
    template_id INT NOT NULL COMMENT '템플릿 ID',
    channel_type ENUM('email', 'sms', 'push', 'webhook', 'in_app') NOT NULL COMMENT '전송 채널',
    subject VARCHAR(500) COMMENT '제목',
    content TEXT NOT NULL COMMENT '알림 내용',
    variables JSON COMMENT '템플릿 변수 (JSON)',
    priority INT DEFAULT 3 COMMENT '우선순위 (1: 긴급, 5: 일반)',
    scheduled_at TIMESTAMP NULL COMMENT '예약 발송 시간 (NULL이면 즉시)',
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending' COMMENT '처리 상태',
    retry_count INT DEFAULT 0 COMMENT '재시도 횟수',
    max_retries INT DEFAULT 3 COMMENT '최대 재시도 횟수',
    error_message TEXT COMMENT '에러 메시지',
    sent_at TIMESTAMP NULL COMMENT '발송 완료 시간',
    related_id INT NULL COMMENT '관련 데이터 ID (주문ID, 신청ID 등)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '큐 등록일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '상태 업데이트일시',
    
    -- 인덱스 설정
    INDEX idx_nq_status (status) COMMENT '상태별 조회용 인덱스',
    INDEX idx_nq_priority (priority) COMMENT '우선순위별 조회용 인덱스',
    INDEX idx_nq_scheduled (scheduled_at) COMMENT '예약시간별 조회용 인덱스',
    INDEX idx_nq_recipient (recipient_type, recipient_id) COMMENT '수신자별 조회용 인덱스',
    INDEX idx_nq_channel (channel_type) COMMENT '채널별 조회용 인덱스',
    INDEX idx_nq_created (created_at) COMMENT '등록일별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='통합 알림 큐 테이블';

-- 25. 알림 발송 로그 테이블
CREATE TABLE tbl_notification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '로그 고유 ID',
    queue_id INT NOT NULL COMMENT '알림 큐 ID',
    channel_type ENUM('email', 'sms', 'push', 'webhook', 'in_app') NOT NULL COMMENT '발송 채널',
    recipient_email VARCHAR(100) COMMENT '수신자 이메일',
    recipient_phone VARCHAR(20) COMMENT '수신자 전화번호',
    subject VARCHAR(500) COMMENT '제목',
    content TEXT COMMENT '발송 내용',
    status ENUM('success', 'failed', 'bounced', 'delivered', 'read') NOT NULL COMMENT '발송 상태',
    response_data JSON COMMENT '발송 응답 데이터 (JSON)',
    error_code VARCHAR(50) COMMENT '에러 코드',
    error_message TEXT COMMENT '에러 메시지',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '발송 시간',
    delivered_at TIMESTAMP NULL COMMENT '전달 시간',
    read_at TIMESTAMP NULL COMMENT '읽은 시간',
    
    -- 인덱스 설정
    INDEX idx_nl_queue (queue_id) COMMENT '큐별 로그 조회용 인덱스',
    INDEX idx_nl_channel (channel_type) COMMENT '채널별 로그 조회용 인덱스',
    INDEX idx_nl_status (status) COMMENT '상태별 로그 조회용 인덱스',
    INDEX idx_nl_sent (sent_at) COMMENT '발송일별 로그 조회용 인덱스',
    INDEX idx_nl_recipient (recipient_email, recipient_phone) COMMENT '수신자별 로그 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='알림 발송 로그 테이블';

-- 26. 사용자 알림 설정 테이블
CREATE TABLE tbl_user_notification_settings (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '설정 고유 ID',
    user_type ENUM('user', 'admin') NOT NULL COMMENT '사용자 타입',
    user_id INT NOT NULL COMMENT '사용자 ID',
    notification_type ENUM('order', 'store', 'system', 'marketing') NOT NULL COMMENT '알림 타입',
    email_enabled BOOLEAN DEFAULT TRUE COMMENT '이메일 알림 활성화',
    sms_enabled BOOLEAN DEFAULT FALSE COMMENT 'SMS 알림 활성화',
    push_enabled BOOLEAN DEFAULT TRUE COMMENT '푸시 알림 활성화',
    in_app_enabled BOOLEAN DEFAULT TRUE COMMENT '앱 내 알림 활성화',
    quiet_hours_start TIME COMMENT '방해 금지 시간 시작',
    quiet_hours_end TIME COMMENT '방해 금지 시간 종료',
    timezone VARCHAR(50) DEFAULT 'Asia/Seoul' COMMENT '시간대',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '설정 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '설정 수정일시',
    
    -- 인덱스 설정
    UNIQUE KEY unique_user_notification (user_type, user_id, notification_type) COMMENT '사용자-알림타입 중복 방지',
    INDEX idx_uns_user (user_type, user_id) COMMENT '사용자별 설정 조회용 인덱스',
    INDEX idx_uns_type (notification_type) COMMENT '알림타입별 설정 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='사용자 알림 설정 테이블';

-- 27. 실시간 알림 세션 관리 테이블
CREATE TABLE tbl_notification_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '세션 고유 ID',
    user_type ENUM('user', 'admin') NOT NULL COMMENT '사용자 타입',
    user_id INT NOT NULL COMMENT '사용자 ID',
    session_id VARCHAR(100) UNIQUE NOT NULL COMMENT '웹소켓 세션 ID',
    connection_info JSON COMMENT '연결 정보 (IP, User-Agent 등)',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성 상태',
    last_ping_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '마지막 핑 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '연결 생성일시',
    disconnected_at TIMESTAMP NULL COMMENT '연결 해제일시',
    
    -- 인덱스 설정
    INDEX idx_ns_user (user_type, user_id) COMMENT '사용자별 세션 조회용 인덱스',
    INDEX idx_ns_session (session_id) COMMENT '세션ID 검색용 인덱스',
    INDEX idx_ns_active (is_active) COMMENT '활성 세션 조회용 인덱스',
    INDEX idx_ns_ping (last_ping_at) COMMENT '핑시간별 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='실시간 알림 세션 관리 테이블';

-- =====================================================
-- 알림 시스템 초기 데이터
-- =====================================================

-- 알림 채널 등록
INSERT INTO tbl_notification_channels (channel_name, channel_type, config, priority) VALUES
('이메일 기본', 'email', '{"smtp_host": "smtp.gmail.com", "smtp_port": 587, "encryption": "tls"}', 1),
('SMS 기본', 'sms', '{"api_url": "https://api.sms.com", "api_key": "your_api_key"}', 2),
('푸시 알림', 'push', '{"fcm_server_key": "your_fcm_key", "apns_cert": "path/to/cert.pem"}', 3),
('웹훅', 'webhook', '{"timeout": 30, "retry_count": 3}', 4),
('앱 내 알림', 'in_app', '{"storage_days": 30}', 5);

-- 알림 템플릿 등록
INSERT INTO tbl_notification_templates (template_code, template_name, template_type, channel_type, subject, content, variables) VALUES
-- 주문 관련 템플릿
('order_created', '주문 접수 완료', 'order', 'email', '[STN] 주문이 접수되었습니다', '안녕하세요 {{user_name}}님.\n\n주문번호 {{order_number}}가 성공적으로 접수되었습니다.\n\n주문 상세:\n- 서비스: {{service_name}}\n- 금액: {{total_amount}}원\n- 접수일시: {{created_at}}\n\n감사합니다.', '["user_name", "order_number", "service_name", "total_amount", "created_at"]'),

('order_status_changed', '주문 상태 변경', 'order', 'sms', NULL, '[STN] 주문번호 {{order_number}} 상태가 {{status}}로 변경되었습니다.', '["order_number", "status"]'),


-- 입점 관련 템플릿
('store_registration_approved', '입점 승인', 'store', 'email', '[STN] 입점 신청이 승인되었습니다', '축하합니다 {{company_name}}님!\n\n입점 신청이 승인되었습니다.\n\n- 계약번호: {{contract_number}}\n- 계약기간: {{contract_period}}\n- 수수료율: {{commission_rate}}%\n\n서비스를 시작해보세요.', '["company_name", "contract_number", "contract_period", "commission_rate"]'),

-- 시스템 관련 템플릿
('system_maintenance', '시스템 점검 안내', 'system', 'email', '[STN] 시스템 점검 안내', '안녕하세요.\n\n{{maintenance_date}} {{maintenance_time}}에 시스템 점검이 예정되어 있습니다.\n\n점검 시간: {{duration}}분\n영향 범위: {{affected_services}}\n\n불편을 드려 죄송합니다.', '["maintenance_date", "maintenance_time", "duration", "affected_services"]');

-- =====================================================
-- 알림 시스템 뷰 생성
-- =====================================================

-- 알림 큐 현황 조회 뷰
CREATE VIEW v_notification_queue_status AS
SELECT 
    nq.id,
    nq.notification_type,
    nq.recipient_type,
    nq.recipient_id,
    nq.channel_type,
    nq.priority,
    nq.status,
    nq.scheduled_at,
    nq.retry_count,
    nq.max_retries,
    nq.created_at,
    nt.template_name,
    DATEDIFF(NOW(), nq.created_at) as days_since_created
FROM tbl_notification_queue nq
LEFT JOIN tbl_notification_templates nt ON nq.template_id = nt.id;

-- 사용자별 알림 설정 요약 뷰
CREATE VIEW v_user_notification_summary AS
SELECT 
    uns.user_type,
    uns.user_id,
    COUNT(*) as total_settings,
    SUM(CASE WHEN email_enabled = TRUE THEN 1 ELSE 0 END) as email_enabled_count,
    SUM(CASE WHEN sms_enabled = TRUE THEN 1 ELSE 0 END) as sms_enabled_count,
    SUM(CASE WHEN push_enabled = TRUE THEN 1 ELSE 0 END) as push_enabled_count,
    SUM(CASE WHEN in_app_enabled = TRUE THEN 1 ELSE 0 END) as in_app_enabled_count
FROM tbl_user_notification_settings uns
GROUP BY uns.user_type, uns.user_id;

-- 알림 발송 통계 뷰
CREATE VIEW v_notification_statistics AS
SELECT 
    DATE(nl.sent_at) as sent_date,
    nl.channel_type,
    nl.status,
    COUNT(*) as count,
    AVG(CASE WHEN nl.delivered_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, nl.sent_at, nl.delivered_at) END) as avg_delivery_time_seconds
FROM tbl_notification_logs nl
WHERE nl.sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(nl.sent_at), nl.channel_type, nl.status;

-- ==============================================
-- 청구관리 및 부서관리 테이블
-- ==============================================

-- 부서 관리 테이블
CREATE TABLE tbl_departments (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '부서 고유 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID (tbl_customer_hierarchy 참조)',
    department_code VARCHAR(20) NOT NULL COMMENT '부서 코드 (고객사 내에서 유일)',
    department_name VARCHAR(100) NOT NULL COMMENT '부서명',
    parent_department_id INT COMMENT '상위 부서 ID (자기참조)',
    department_level INT DEFAULT 1 COMMENT '부서 레벨 (1=최상위, 2=중간, 3=하위)',
    manager_name VARCHAR(50) COMMENT '부서장 이름',
    manager_contact VARCHAR(20) COMMENT '부서장 연락처',
    manager_email VARCHAR(100) COMMENT '부서장 이메일',
    cost_center VARCHAR(20) COMMENT '코스트 센터 코드',
    budget_limit DECIMAL(15,2) COMMENT '월 예산 한도',
    is_active BOOLEAN DEFAULT TRUE COMMENT '부서 활성화 여부',
    notes TEXT COMMENT '부서 관련 메모',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '부서 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '부서 수정일시',
    
    -- 인덱스 설정
    INDEX idx_dept_customer (customer_id) COMMENT '고객사별 부서 조회용 인덱스',
    INDEX idx_dept_code (customer_id, department_code) COMMENT '부서코드 조회용 인덱스',
    INDEX idx_dept_parent (parent_department_id) COMMENT '상위부서별 하위부서 조회용 인덱스',
    INDEX idx_dept_level (department_level) COMMENT '부서레벨별 조회용 인덱스',
    INDEX idx_dept_active (is_active) COMMENT '활성부서 조회용 인덱스',
    UNIQUE KEY unique_customer_dept_code (customer_id, department_code) COMMENT '고객사 내 부서코드 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='부서 관리 테이블';

-- 사용자-부서 연결 테이블 (사용자는 하나의 부서에만 소속)
CREATE TABLE tbl_user_departments (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '사용자-부서 연결 고유 ID',
    user_id INT NOT NULL COMMENT '사용자 ID (tbl_users 참조)',
    department_id INT NOT NULL COMMENT '부서 ID (tbl_departments 참조)',
    is_primary BOOLEAN DEFAULT TRUE COMMENT '주 소속 부서 여부',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '부서 배정일시',
    assigned_by INT COMMENT '배정한 관리자 ID',
    notes TEXT COMMENT '배정 관련 메모',
    
    -- 인덱스 설정
    INDEX idx_ud_user (user_id) COMMENT '사용자별 부서 조회용 인덱스',
    INDEX idx_ud_department (department_id) COMMENT '부서별 사용자 조회용 인덱스',
    INDEX idx_ud_primary (is_primary) COMMENT '주 소속 부서 조회용 인덱스',
    UNIQUE KEY unique_user_primary_dept (user_id, is_primary) COMMENT '사용자당 주 소속 부서 1개 제한'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='사용자-부서 연결 테이블';

-- 청구 관리 테이블
CREATE TABLE tbl_billing_requests (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '청구 요청 고유 ID',
    billing_number VARCHAR(50) UNIQUE NOT NULL COMMENT '청구번호 (자동 생성): BILL-YYYYMMDD-0001 형식',
    billing_type ENUM('department', 'department_group', 'customer_group') NOT NULL COMMENT '청구 유형: 부서별, 부서묶음, 고객묶음',
    billing_period_start DATE NOT NULL COMMENT '청구 기간 시작일',
    billing_period_end DATE NOT NULL COMMENT '청구 기간 종료일',
    billing_date DATE NOT NULL COMMENT '청구서 발행일',
    due_date DATE NOT NULL COMMENT '청구서 납기일',
    
    -- 청구 대상 정보
    customer_id INT COMMENT '고객사 ID (고객묶음 청구시)',
    
    -- 청구 금액 정보
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '총 청구 금액',
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '세금 금액',
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '최종 청구 금액 (총액 + 세금)',
    currency VARCHAR(3) DEFAULT 'KRW' COMMENT '통화 코드',
    
    -- 청구 상태
    status ENUM('draft', 'pending', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft' COMMENT '청구 상태',
    payment_status ENUM('unpaid', 'partial', 'paid', 'overdue') DEFAULT 'unpaid' COMMENT '결제 상태',
    payment_method ENUM('bank_transfer', 'credit_card', 'cash', 'check') COMMENT '결제 방법',
    payment_date DATE COMMENT '결제 완료일',
    
    -- 청구서 정보
    billing_file_path VARCHAR(500) COMMENT '청구서 파일 경로',
    billing_file_name VARCHAR(255) COMMENT '청구서 파일명',
    billing_notes TEXT COMMENT '청구서 메모',
    
    -- 시스템 정보
    created_by INT NOT NULL COMMENT '청구서 생성자 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '청구서 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '청구서 수정일시',
    
    -- 인덱스 설정
    INDEX idx_billing_number (billing_number) COMMENT '청구번호 검색용 인덱스',
    INDEX idx_billing_type (billing_type) COMMENT '청구유형별 조회용 인덱스',
    INDEX idx_billing_period (billing_period_start, billing_period_end) COMMENT '청구기간별 조회용 인덱스',
    INDEX idx_billing_date (billing_date) COMMENT '청구일별 조회용 인덱스',
    INDEX idx_billing_due_date (due_date) COMMENT '납기일별 조회용 인덱스',
    INDEX idx_billing_customer (customer_id) COMMENT '고객사별 청구 조회용 인덱스',
    INDEX idx_billing_status (status) COMMENT '청구상태별 조회용 인덱스',
    INDEX idx_billing_payment_status (payment_status) COMMENT '결제상태별 조회용 인덱스',
    INDEX idx_billing_created_by (created_by) COMMENT '생성자별 청구 조회용 인덱스'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='청구 관리 테이블';

-- 청구-부서 관계 테이블 (부서별/부서묶음 청구시)
CREATE TABLE tbl_billing_departments (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '관계 고유 ID',
    billing_request_id INT NOT NULL COMMENT '청구 요청 ID',
    department_id INT NOT NULL COMMENT '부서 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    
    -- 인덱스 설정
    INDEX idx_bd_billing_request (billing_request_id) COMMENT '청구요청별 조회용 인덱스',
    INDEX idx_bd_department (department_id) COMMENT '부서별 조회용 인덱스',
    UNIQUE KEY unique_billing_department (billing_request_id, department_id) COMMENT '청구-부서 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='청구-부서 관계 테이블';

-- 청구-고객사 관계 테이블 (고객묶음 청구시)
CREATE TABLE tbl_billing_customers (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '관계 고유 ID',
    billing_request_id INT NOT NULL COMMENT '청구 요청 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    
    -- 인덱스 설정
    INDEX idx_bc_billing_request (billing_request_id) COMMENT '청구요청별 조회용 인덱스',
    INDEX idx_bc_customer (customer_id) COMMENT '고객사별 조회용 인덱스',
    UNIQUE KEY unique_billing_customer (billing_request_id, customer_id) COMMENT '청구-고객사 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='청구-고객사 관계 테이블';

-- 청구 상세 내역 테이블 (주문별 청구 내역)
CREATE TABLE tbl_billing_details (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '청구 상세 고유 ID',
    billing_request_id INT NOT NULL COMMENT '청구 요청 ID (tbl_billing_requests 참조)',
    order_id INT NOT NULL COMMENT '주문 ID (tbl_orders 참조)',
    department_id INT COMMENT '부서 ID (tbl_departments 참조)',
    customer_id INT NOT NULL COMMENT '고객사 ID (tbl_customer_hierarchy 참조)',
    
    -- 주문 정보
    order_number VARCHAR(50) NOT NULL COMMENT '주문번호',
    service_type_name VARCHAR(100) NOT NULL COMMENT '서비스 타입명',
    order_date DATE NOT NULL COMMENT '주문일',
    completion_date DATE COMMENT '완료일',
    
    -- 청구 금액 정보
    base_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '기본 금액',
    discount_amount DECIMAL(15,2) DEFAULT 0 COMMENT '할인 금액',
    additional_fee DECIMAL(15,2) DEFAULT 0 COMMENT '추가 수수료',
    tax_amount DECIMAL(15,2) DEFAULT 0 COMMENT '세금 금액',
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '최종 청구 금액',
    
    -- 청구 상태
    is_included BOOLEAN DEFAULT TRUE COMMENT '청구서 포함 여부',
    billing_notes TEXT COMMENT '청구 상세 메모',
    
    -- 시스템 정보
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '청구 상세 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '청구 상세 수정일시',
    
    -- 인덱스 설정
    INDEX idx_bd_billing_request (billing_request_id) COMMENT '청구요청별 상세 조회용 인덱스',
    INDEX idx_bd_order (order_id) COMMENT '주문별 청구 조회용 인덱스',
    INDEX idx_bd_department (department_id) COMMENT '부서별 청구 조회용 인덱스',
    INDEX idx_bd_customer (customer_id) COMMENT '고객사별 청구 조회용 인덱스',
    INDEX idx_bd_order_date (order_date) COMMENT '주문일별 청구 조회용 인덱스',
    INDEX idx_bd_included (is_included) COMMENT '청구서 포함 여부별 조회용 인덱스',
    UNIQUE KEY unique_billing_order (billing_request_id, order_id) COMMENT '청구요청-주문 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='청구 상세 내역 테이블';

-- 청구 설정 테이블 (청구 정책 및 규칙)
CREATE TABLE tbl_billing_settings (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '청구 설정 고유 ID',
    customer_id INT COMMENT '고객사 ID (NULL이면 전체 기본 설정)',
    department_id INT COMMENT '부서 ID (NULL이면 고객사 전체 설정)',
    setting_type ENUM('billing_cycle', 'payment_terms', 'discount_rate', 'tax_rate', 'late_fee') NOT NULL COMMENT '설정 유형',
    setting_key VARCHAR(100) NOT NULL COMMENT '설정 키',
    setting_value TEXT NOT NULL COMMENT '설정 값 (JSON 형태)',
    is_active BOOLEAN DEFAULT TRUE COMMENT '설정 활성화 여부',
    effective_date DATE NOT NULL COMMENT '설정 적용 시작일',
    expiry_date DATE COMMENT '설정 적용 종료일',
    created_by INT NOT NULL COMMENT '설정 생성자 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '설정 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '설정 수정일시',
    
    -- 인덱스 설정
    INDEX idx_bs_customer (customer_id) COMMENT '고객사별 설정 조회용 인덱스',
    INDEX idx_bs_department (department_id) COMMENT '부서별 설정 조회용 인덱스',
    INDEX idx_bs_type (setting_type) COMMENT '설정유형별 조회용 인덱스',
    INDEX idx_bs_key (setting_key) COMMENT '설정키별 조회용 인덱스',
    INDEX idx_bs_active (is_active) COMMENT '활성설정 조회용 인덱스',
    INDEX idx_bs_effective (effective_date, expiry_date) COMMENT '적용기간별 조회용 인덱스',
    UNIQUE KEY unique_customer_dept_type_key (customer_id, department_id, setting_type, setting_key, effective_date) COMMENT '고객사-부서-유형-키-날짜 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='청구 설정 테이블';

-- 청구서 발송 관리 테이블 (청구사항 및 발송사항)
CREATE TABLE tbl_billing_dispatch (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '청구서 발송 고유 ID',
    billing_request_id INT NOT NULL COMMENT '청구 요청 ID (tbl_billing_requests 참조)',
    
    -- 청구사항 (Billing Details)
    deadline DATE COMMENT '마감일',
    statement_dispatch_date DATE COMMENT '내역서 발송일(청구일)',
    statement_recipient_email VARCHAR(255) COMMENT '내역서 수신메일주소 - 담당자',
    statement_cc_email VARCHAR(255) COMMENT '내역서 수신메일주소 - 참조메일주소',
    tax_invoice_issue_date DATE COMMENT '세금계산서 발행일',
    tax_invoice_issue_email VARCHAR(255) COMMENT '세금계산서 발행 메일주소',
    
    -- 발송사항 (Dispatch Details)
    email_subject VARCHAR(255) COMMENT '메일 제목 (서비스별 커스터마이징)',
    email_sender_name VARCHAR(100) COMMENT '발신인 이름',
    email_sender_contact VARCHAR(20) COMMENT '발신인 연락처',
    email_template_id INT COMMENT '사용된 이메일 템플릿 ID',
    
    -- 양식 커스터마이징
    item_display_format ENUM('standard', 'detailed', 'summary', 'custom') DEFAULT 'standard' COMMENT '항목표시 양식',
    custom_form_template TEXT COMMENT '커스텀 양식 템플릿 (JSON)',
    custom_form_requested BOOLEAN DEFAULT FALSE COMMENT '별도 양식 요청 여부',
    
    -- 발송 상태
    statement_sent BOOLEAN DEFAULT FALSE COMMENT '내역서 발송 완료 여부',
    statement_sent_at TIMESTAMP NULL COMMENT '내역서 발송일시',
    tax_invoice_sent BOOLEAN DEFAULT FALSE COMMENT '세금계산서 발송 완료 여부',
    tax_invoice_sent_at TIMESTAMP NULL COMMENT '세금계산서 발송일시',
    
    -- 시스템 정보
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '발송 정보 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '발송 정보 수정일시',
    
    -- 인덱스 설정
    INDEX idx_bd_billing_request (billing_request_id) COMMENT '청구요청별 발송 조회용 인덱스',
    INDEX idx_bd_deadline (deadline) COMMENT '마감일별 조회용 인덱스',
    INDEX idx_bd_statement_date (statement_dispatch_date) COMMENT '내역서 발송일별 조회용 인덱스',
    INDEX idx_bd_tax_invoice_date (tax_invoice_issue_date) COMMENT '세금계산서 발행일별 조회용 인덱스',
    INDEX idx_bd_statement_sent (statement_sent) COMMENT '내역서 발송상태별 조회용 인덱스',
    INDEX idx_bd_tax_invoice_sent (tax_invoice_sent) COMMENT '세금계산서 발송상태별 조회용 인덱스',
    UNIQUE KEY unique_billing_dispatch (billing_request_id) COMMENT '청구요청당 발송정보 1개 제한'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='청구서 발송 관리 테이블';

-- 청구 단위 설정 테이블 (청구 기능 활성화 제어)
CREATE TABLE tbl_billing_unit_settings (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '청구 단위 설정 고유 ID',
    customer_id INT COMMENT '고객사 ID (NULL이면 전체 기본 설정)',
    department_id INT COMMENT '부서 ID (NULL이면 고객사 전체 설정)',
    department_group_id INT COMMENT '부서묶음 ID (NULL이면 개별 부서 설정)',
    customer_group_id INT COMMENT '고객묶음 ID (NULL이면 개별 고객 설정)',
    
    -- 청구 기능 활성화
    billing_enabled BOOLEAN DEFAULT FALSE COMMENT '청구 기능 활성화 여부',
    billing_level ENUM('customer', 'department', 'department_group', 'customer_group') NOT NULL COMMENT '청구 단위 레벨',
    
    -- 청구 정책
    billing_cycle ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly' COMMENT '청구 주기',
    billing_day_of_month INT DEFAULT 1 COMMENT '월 청구일 (1-31)',
    payment_terms_days INT DEFAULT 30 COMMENT '결제 조건 (일수)',
    
    -- 청구서 설정
    auto_generate BOOLEAN DEFAULT FALSE COMMENT '자동 청구서 생성 여부',
    require_approval BOOLEAN DEFAULT TRUE COMMENT '청구서 승인 필요 여부',
    approval_workflow JSON COMMENT '승인 워크플로우 (JSON)',
    
    -- 발송 설정
    auto_send_statement BOOLEAN DEFAULT FALSE COMMENT '내역서 자동 발송 여부',
    auto_send_tax_invoice BOOLEAN DEFAULT FALSE COMMENT '세금계산서 자동 발송 여부',
    default_email_template_id INT COMMENT '기본 이메일 템플릿 ID',
    
    -- 시스템 정보
    is_active BOOLEAN DEFAULT TRUE COMMENT '설정 활성화 여부',
    effective_date DATE NOT NULL COMMENT '설정 적용 시작일',
    expiry_date DATE COMMENT '설정 적용 종료일',
    created_by INT NOT NULL COMMENT '설정 생성자 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '설정 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '설정 수정일시',
    
    -- 인덱스 설정
    INDEX idx_bus_customer (customer_id) COMMENT '고객사별 청구단위 설정 조회용 인덱스',
    INDEX idx_bus_department (department_id) COMMENT '부서별 청구단위 설정 조회용 인덱스',
    INDEX idx_bus_department_group (department_group_id) COMMENT '부서묶음별 청구단위 설정 조회용 인덱스',
    INDEX idx_bus_customer_group (customer_group_id) COMMENT '고객묶음별 청구단위 설정 조회용 인덱스',
    INDEX idx_bus_billing_level (billing_level) COMMENT '청구단위 레벨별 조회용 인덱스',
    INDEX idx_bus_billing_enabled (billing_enabled) COMMENT '청구기능 활성화별 조회용 인덱스',
    INDEX idx_bus_active (is_active) COMMENT '활성설정 조회용 인덱스',
    INDEX idx_bus_effective (effective_date, expiry_date) COMMENT '적용기간별 조회용 인덱스',
    UNIQUE KEY unique_billing_unit (customer_id, department_id, department_group_id, customer_group_id, billing_level, effective_date) COMMENT '청구단위 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='청구 단위 설정 테이블';

-- 부서묶음 관리 테이블
CREATE TABLE tbl_department_groups (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '부서묶음 고유 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID (tbl_customer_hierarchy 참조)',
    group_code VARCHAR(20) NOT NULL COMMENT '부서묶음 코드',
    group_name VARCHAR(100) NOT NULL COMMENT '부서묶음명',
    description TEXT COMMENT '부서묶음 설명',
    is_active BOOLEAN DEFAULT TRUE COMMENT '부서묶음 활성화 여부',
    created_by INT NOT NULL COMMENT '생성자 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '부서묶음 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '부서묶음 수정일시',
    
    -- 인덱스 설정
    INDEX idx_dg_customer (customer_id) COMMENT '고객사별 부서묶음 조회용 인덱스',
    INDEX idx_dg_code (customer_id, group_code) COMMENT '부서묶음코드 조회용 인덱스',
    INDEX idx_dg_active (is_active) COMMENT '활성부서묶음 조회용 인덱스',
    UNIQUE KEY unique_customer_group_code (customer_id, group_code) COMMENT '고객사 내 부서묶음코드 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='부서묶음 관리 테이블';

-- 부서묶음-부서 관계 테이블
CREATE TABLE tbl_department_group_members (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '관계 고유 ID',
    department_group_id INT NOT NULL COMMENT '부서묶음 ID',
    department_id INT NOT NULL COMMENT '부서 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    
    -- 인덱스 설정
    INDEX idx_dgm_group (department_group_id) COMMENT '부서묶음별 조회용 인덱스',
    INDEX idx_dgm_department (department_id) COMMENT '부서별 조회용 인덱스',
    UNIQUE KEY unique_group_department (department_group_id, department_id) COMMENT '부서묶음-부서 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='부서묶음-부서 관계 테이블';

-- 고객묶음 관리 테이블
CREATE TABLE tbl_customer_groups (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '고객묶음 고유 ID',
    group_code VARCHAR(20) NOT NULL COMMENT '고객묶음 코드',
    group_name VARCHAR(100) NOT NULL COMMENT '고객묶음명',
    description TEXT COMMENT '고객묶음 설명',
    is_active BOOLEAN DEFAULT TRUE COMMENT '고객묶음 활성화 여부',
    created_by INT NOT NULL COMMENT '생성자 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '고객묶음 생성일시',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '고객묶음 수정일시',
    
    -- 인덱스 설정
    INDEX idx_cg_code (group_code) COMMENT '고객묶음코드 조회용 인덱스',
    INDEX idx_cg_active (is_active) COMMENT '활성고객묶음 조회용 인덱스',
    UNIQUE KEY unique_group_code (group_code) COMMENT '고객묶음코드 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='고객묶음 관리 테이블';

-- 고객묶음-고객사 관계 테이블
CREATE TABLE tbl_customer_group_members (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '관계 고유 ID',
    customer_group_id INT NOT NULL COMMENT '고객묶음 ID',
    customer_id INT NOT NULL COMMENT '고객사 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    
    -- 인덱스 설정
    INDEX idx_cgm_group (customer_group_id) COMMENT '고객묶음별 조회용 인덱스',
    INDEX idx_cgm_customer (customer_id) COMMENT '고객사별 조회용 인덱스',
    UNIQUE KEY unique_group_customer (customer_group_id, customer_id) COMMENT '고객묶음-고객사 중복 방지'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='고객묶음-고객사 관계 테이블';

-- ==============================================
-- 기존 테이블 수정 (부서 정보 추가)
-- ==============================================

-- tbl_users 테이블에 부서 관련 필드 추가
ALTER TABLE tbl_users ADD COLUMN department_id INT COMMENT '소속 부서 ID (tbl_departments 참조)' AFTER customer_id;
ALTER TABLE tbl_users ADD INDEX idx_users_department (department_id) COMMENT '부서별 사용자 조회용 인덱스';

-- tbl_orders 테이블에 부서 정보 추가
ALTER TABLE tbl_orders ADD COLUMN department_id INT COMMENT '주문 부서 ID (tbl_departments 참조)' AFTER customer_id;
ALTER TABLE tbl_orders ADD INDEX idx_orders_department (department_id) COMMENT '부서별 주문 조회용 인덱스';

-- ==============================================
-- 청구관리 및 부서관리 관련 뷰
-- ==============================================

-- 부서별 사용자 현황 뷰
CREATE VIEW v_department_users AS
SELECT 
    d.id as department_id,
    d.customer_id,
    d.department_code,
    d.department_name,
    d.manager_name,
    d.manager_contact,
    d.manager_email,
    d.is_active as department_active,
    COUNT(ud.user_id) as total_users,
    COUNT(CASE WHEN u.status = 'active' THEN 1 END) as active_users,
    COUNT(CASE WHEN u.status = 'inactive' THEN 1 END) as inactive_users,
    d.created_at as department_created_at
FROM tbl_departments d
LEFT JOIN tbl_user_departments ud ON d.id = ud.department_id AND ud.is_primary = TRUE
LEFT JOIN tbl_users u ON ud.user_id = u.id
GROUP BY d.id, d.customer_id, d.department_code, d.department_name, d.manager_name, d.manager_contact, d.manager_email, d.is_active, d.created_at;

-- 부서별 청구 현황 뷰 (JSON 제거, 관계 테이블 사용)
CREATE VIEW v_department_billing_summary AS
SELECT 
    d.id as department_id,
    d.customer_id,
    d.department_code,
    d.department_name,
    COUNT(DISTINCT br.id) as total_billing_requests,
    COUNT(DISTINCT CASE WHEN br.status = 'paid' THEN br.id END) as paid_billing_requests,
    COUNT(DISTINCT CASE WHEN br.status = 'pending' THEN br.id END) as pending_billing_requests,
    COUNT(DISTINCT CASE WHEN br.status = 'overdue' THEN br.id END) as overdue_billing_requests,
    COALESCE(SUM(br.final_amount), 0) as total_billing_amount,
    COALESCE(SUM(CASE WHEN br.status = 'paid' THEN br.final_amount ELSE 0 END), 0) as paid_amount,
    COALESCE(SUM(CASE WHEN br.status = 'pending' THEN br.final_amount ELSE 0 END), 0) as pending_amount,
    COALESCE(SUM(CASE WHEN br.status = 'overdue' THEN br.final_amount ELSE 0 END), 0) as overdue_amount,
    MAX(br.billing_date) as last_billing_date
FROM tbl_departments d
LEFT JOIN tbl_billing_departments bd ON d.id = bd.department_id
LEFT JOIN tbl_billing_requests br ON bd.billing_request_id = br.id
GROUP BY d.id, d.customer_id, d.department_code, d.department_name;

-- 고객사별 청구 현황 뷰 (JSON 제거, 관계 테이블 사용)
CREATE VIEW v_customer_billing_summary AS
SELECT 
    ch.id as customer_id,
    ch.customer_name,
    ch.hierarchy_level,
    COUNT(DISTINCT br.id) as total_billing_requests,
    COUNT(DISTINCT CASE WHEN br.status = 'paid' THEN br.id END) as paid_billing_requests,
    COUNT(DISTINCT CASE WHEN br.status = 'pending' THEN br.id END) as pending_billing_requests,
    COUNT(DISTINCT CASE WHEN br.status = 'overdue' THEN br.id END) as overdue_billing_requests,
    COALESCE(SUM(br.final_amount), 0) as total_billing_amount,
    COALESCE(SUM(CASE WHEN br.status = 'paid' THEN br.final_amount ELSE 0 END), 0) as paid_amount,
    COALESCE(SUM(CASE WHEN br.status = 'pending' THEN br.final_amount ELSE 0 END), 0) as pending_amount,
    COALESCE(SUM(CASE WHEN br.status = 'overdue' THEN br.final_amount ELSE 0 END), 0) as overdue_amount,
    MAX(br.billing_date) as last_billing_date
FROM tbl_customer_hierarchy ch
LEFT JOIN tbl_billing_requests br ON br.customer_id = ch.id
LEFT JOIN tbl_billing_customers bc ON ch.id = bc.customer_id
LEFT JOIN tbl_billing_requests br2 ON bc.billing_request_id = br2.id
GROUP BY ch.id, ch.customer_name, ch.hierarchy_level;

-- 청구서 상세 정보 뷰
CREATE VIEW v_billing_request_details AS
SELECT 
    br.id as billing_request_id,
    br.billing_number,
    br.billing_type,
    br.billing_period_start,
    br.billing_period_end,
    br.billing_date,
    br.due_date,
    br.total_amount,
    br.tax_amount,
    br.final_amount,
    br.status,
    br.payment_status,
    br.payment_method,
    br.payment_date,
    br.billing_notes,
    br.created_at,
    -- 고객사 정보
    ch.customer_name,
    ch.hierarchy_level,
    -- 부서 정보 (부서별/부서묶음 청구시)
    CASE 
        WHEN br.billing_type IN ('department', 'department_group') THEN
            (SELECT GROUP_CONCAT(d.department_name SEPARATOR ', ')
             FROM tbl_departments d 
             JOIN tbl_billing_departments bd ON d.id = bd.department_id
             WHERE bd.billing_request_id = br.id)
        ELSE NULL
    END as department_names,
    -- 생성자 정보
    u.username as created_by_name,
    u.email as created_by_email,
    -- 상세 내역 개수
    COUNT(bd.id) as detail_count,
    -- 주문 개수
    COUNT(DISTINCT bd.order_id) as order_count
FROM tbl_billing_requests br
LEFT JOIN tbl_customer_hierarchy ch ON br.customer_id = ch.id
LEFT JOIN tbl_users u ON br.created_by = u.id
LEFT JOIN tbl_billing_details bd ON br.id = bd.billing_request_id
GROUP BY br.id, br.billing_number, br.billing_type, br.billing_period_start, br.billing_period_end, 
         br.billing_date, br.due_date, br.total_amount, br.tax_amount, br.final_amount, 
         br.status, br.payment_status, br.payment_method, br.payment_date, br.billing_notes, 
         br.created_at, ch.customer_name, ch.hierarchy_level, u.username, u.email;

-- ==============================================
-- 초기 데이터 삽입
-- ==============================================

-- 청구 설정 기본값
INSERT INTO tbl_billing_settings (customer_id, department_id, setting_type, setting_key, setting_value, effective_date, created_by) VALUES
(NULL, NULL, 'billing_cycle', 'monthly', '{"cycle": "monthly", "day_of_month": 1}', CURDATE(), 1),
(NULL, NULL, 'payment_terms', 'default', '{"days": 30, "description": "청구일로부터 30일"}', CURDATE(), 1),
(NULL, NULL, 'tax_rate', 'vat', '{"rate": 0.1, "description": "부가세 10%"}', CURDATE(), 1),
(NULL, NULL, 'late_fee', 'default', '{"rate": 0.02, "description": "연체료 월 2%"}', CURDATE(), 1);

-- =====================================================
-- 초기 사용자 및 고객사 데이터 삽입
-- =====================================================

-- 1. 고객사 계층 구조 생성
INSERT INTO tbl_customer_hierarchy (parent_id, customer_code, customer_name, hierarchy_level, is_active) VALUES
(NULL, 'STN001', 'STN네트웍', 'head_office', TRUE),
(1, 'AMORE001', '아모레퍼시픽', 'branch', TRUE),
(1, 'SEONGWON001', '성원애드피아', 'branch', TRUE),
(1, 'STNCUST001', '에스티엔', 'branch', TRUE);

-- 2. 사용자 계정 생성 (비밀번호: 1111 - UserModel이 자동 해시화)
-- STN네트웍 본사 관리자 (슈퍼관리자)
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(1, 'stn_admin', '1111', 'STN관리자', 'admin@stn.co.kr', '02-1234-5678', '관리팀', '대표', 'super_admin', 'active', TRUE);

-- 아모레퍼시픽 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(2, 'amore_admin', '1111', '아모레관리자', 'admin@amorepacific.com', '02-2345-6789', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 성원애드피아 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(3, 'seongwon_admin', '1111', '성원관리자', 'admin@seongwon.com', '02-3456-7890', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 에스티엔 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(4, 'stn_customer_admin', '1111', '에스티엔관리자', 'admin@stn-customer.com', '02-4567-8901', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 3. 고객사별 서비스 권한 설정
-- STN네트웍 본사 - 모든 서비스 권한 (예시)
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders) VALUES
(1, 1, TRUE, 0, 0), (1, 2, TRUE, 0, 0), (1, 3, TRUE, 0, 0), (1, 4, TRUE, 0, 0),
(1, 5, TRUE, 0, 0), (1, 6, TRUE, 0, 0), (1, 7, TRUE, 0, 0), (1, 8, TRUE, 0, 0),
(1, 9, TRUE, 0, 0), (1, 10, TRUE, 0, 0), (1, 11, TRUE, 0, 0), (1, 12, TRUE, 0, 0),
(1, 13, TRUE, 0, 0), (1, 14, TRUE, 0, 0), (1, 15, TRUE, 0, 0), (1, 16, TRUE, 0, 0),
(1, 17, TRUE, 0, 0), (1, 18, TRUE, 0, 0), (1, 19, TRUE, 0, 0), (1, 20, TRUE, 0, 0),
(1, 21, TRUE, 0, 0), (1, 22, TRUE, 0, 0), (1, 23, TRUE, 0, 0), (1, 24, TRUE, 0, 0);

-- 아모레퍼시픽 - 퀵 서비스 전용 (service_type_id 1, 2, 3, 4)
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders) VALUES
(2, 1, TRUE, 50, 1000), (2, 2, TRUE, 30, 500), (2, 3, TRUE, 20, 300), (2, 4, TRUE, 10, 100);

-- 성원애드피아 - 택배 서비스 전용 (service_type_id 5, 6, 7, 8)
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders) VALUES
(3, 5, TRUE, 100, 2000), (3, 6, TRUE, 50, 1000), (3, 7, TRUE, 20, 400), (3, 8, TRUE, 10, 200);

-- 에스티엔 - 생활 서비스 전용 (service_type_id 9, 10, 11, 12, 13, 14)
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders) VALUES
(4, 9, TRUE, 10, 200), (4, 10, TRUE, 5, 100), (4, 11, TRUE, 5, 100), (4, 12, TRUE, 2, 50), (4, 13, TRUE, 2, 50), (4, 14, TRUE, 2, 50);

-- =====================================================
-- 스키마 생성 완료
-- =====================================================
