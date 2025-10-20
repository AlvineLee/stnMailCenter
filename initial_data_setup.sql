-- =====================================================
-- STN MailCenter 초기 데이터 설정
-- =====================================================

-- 1. 고객사 계층 구조 데이터 생성
-- STN네트웍 본사
INSERT INTO tbl_customer_hierarchy (customer_code, customer_name, hierarchy_level, business_number, representative_name, contact_phone, contact_email, address, contract_start_date, contract_end_date, is_active) VALUES
('STN-001', 'STN네트웍', 'head_office', '123-45-67890', 'STN대표', '02-1234-5678', 'admin@stn.co.kr', '서울특별시 강남구 테헤란로 123', '2024-01-01', '2025-12-31', TRUE);

-- 고객사 3개 (지사 레벨)
INSERT INTO tbl_customer_hierarchy (parent_id, customer_code, customer_name, hierarchy_level, business_number, representative_name, contact_phone, contact_email, address, contract_start_date, contract_end_date, is_active) VALUES
(1, 'AMR-001', '아모레퍼시픽', 'branch', '234-56-78901', '아모레대표', '02-2345-6789', 'contact@amorepacific.com', '서울특별시 용산구 한강대로 456', '2024-01-01', '2025-12-31', TRUE),
(1, 'SWA-001', '성원애드피아', 'branch', '345-67-89012', '성원대표', '02-3456-7890', 'contact@seongwon.com', '서울특별시 마포구 월드컵로 789', '2024-01-01', '2025-12-31', TRUE),
(1, 'STN-002', '에스티엔', 'branch', '456-78-90123', '에스티엔대표', '02-4567-8901', 'contact@stn.com', '서울특별시 서초구 서초대로 321', '2024-01-01', '2025-12-31', TRUE);

-- 2. 사용자 계정 생성 (비밀번호: 1111)
-- STN네트웍 본사 관리자 (슈퍼관리자)
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(1, 'stn_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'STN관리자', 'admin@stn.co.kr', '02-1234-5678', '관리팀', '대표', 'super_admin', 'active', TRUE);

-- 아모레퍼시픽 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(2, 'amore_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '아모레관리자', 'admin@amorepacific.com', '02-2345-6789', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 성원애드피아 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(3, 'seongwon_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '성원관리자', 'admin@seongwon.com', '02-3456-7890', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 에스티엔 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(4, 'stn_customer_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '에스티엔관리자', 'admin@stn-customer.com', '02-4567-8901', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 3. 고객사별 서비스 권한 설정
-- STN네트웍 본사 - 모든 서비스 권한
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders, special_instructions) 
SELECT 1, id, TRUE, 0, 0, '본사 - 모든 서비스 관리 권한' FROM tbl_service_types WHERE is_active = TRUE;

-- 아모레퍼시픽 - 퀵 서비스 권한
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders, special_instructions) 
SELECT 2, id, TRUE, 100, 3000, '아모레퍼시픽 - 퀵 서비스 전용' FROM tbl_service_types WHERE service_category = 'quick' AND is_active = TRUE;

-- 성원애드피아 - 택배 서비스 권한
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders, special_instructions) 
SELECT 3, id, TRUE, 200, 6000, '성원애드피아 - 택배 서비스 전용' FROM tbl_service_types WHERE service_category = 'parcel' AND is_active = TRUE;

-- 에스티엔 - 생활 서비스 권한
INSERT INTO tbl_customer_service_permissions (customer_id, service_type_id, is_enabled, max_daily_orders, max_monthly_orders, special_instructions) 
SELECT 4, id, TRUE, 50, 1500, '에스티엔 - 생활 서비스 전용' FROM tbl_service_types WHERE service_category = 'life' AND is_active = TRUE;

-- 4. 시스템 설정 추가
INSERT INTO tbl_system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('default_password', '1111', 'string', '기본 비밀번호', FALSE),
('super_admin_customer_id', '1', 'number', '슈퍼관리자 고객사 ID', FALSE),
('enable_customer_service_control', 'true', 'boolean', '고객사별 서비스 제어 활성화', FALSE),
('enable_customer_order_filter', 'true', 'boolean', '고객사별 주문 필터링 활성화', FALSE);

-- =====================================================
-- 초기 데이터 설정 완료
-- =====================================================

-- 계정 정보 요약:
-- 1. STN네트웍 본사: stn_admin / 1111 (슈퍼관리자)
-- 2. 아모레퍼시픽: amore_admin / 1111 (관리자)
-- 3. 성원애드피아: seongwon_admin / 1111 (관리자)
-- 4. 에스티엔: stn_customer_admin / 1111 (관리자)
