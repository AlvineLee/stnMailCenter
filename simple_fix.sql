
-- =====================================================
-- 간단한 로그인 문제 해결
-- =====================================================

-- 1. 기존 사용자 삭제
DELETE FROM tbl_users WHERE username IN ('stn_admin', 'amore_admin', 'seongwon_admin', 'stn_customer_admin');

-- 2. 평문 비밀번호로 사용자 생성 (UserModel이 자동으로 해시화)
-- STN네트웍 본사 관리자
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

-- 3. 확인 쿼리
SELECT id, username, real_name, user_role, status, is_active FROM tbl_users;
