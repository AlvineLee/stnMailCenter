-- =====================================================
-- 로그인 문제 해결을 위한 데이터 수정
-- =====================================================

-- 1. 기존 사용자 데이터 삭제 (있다면)
DELETE FROM tbl_users WHERE username IN ('stn_admin', 'amore_admin', 'seongwon_admin', 'stn_customer_admin');

-- 2. 올바른 해시값으로 사용자 계정 재생성
-- 비밀번호 "1111"의 올바른 해시값 생성
-- PHP에서 password_hash('1111', PASSWORD_DEFAULT)로 생성된 값

-- STN네트웍 본사 관리자 (슈퍼관리자)
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(1, 'stn_admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'STN관리자', 'admin@stn.co.kr', '02-1234-5678', '관리팀', '대표', 'super_admin', 'active', TRUE);

-- 아모레퍼시픽 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(2, 'amore_admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '아모레관리자', 'admin@amorepacific.com', '02-2345-6789', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 성원애드피아 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(3, 'seongwon_admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '성원관리자', 'admin@seongwon.com', '02-3456-7890', '관리팀', '팀장', 'admin', 'active', TRUE);

-- 에스티엔 관리자
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(4, 'stn_customer_admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '에스티엔관리자', 'admin@stn-customer.com', '02-4567-8901', '관리팀', '팀장', 'admin', 'active', TRUE);

-- =====================================================
-- 대안: 평문 비밀번호로 임시 설정 (테스트용)
-- =====================================================

-- 주의: 이 방법은 테스트용이며, 실제 운영에서는 사용하지 마세요!
-- UserModel의 hashPassword 콜백이 자동으로 해시화해줍니다.

-- 임시로 평문 비밀번호 설정 (UserModel이 자동으로 해시화)
-- UPDATE tbl_users SET password = '1111' WHERE username = 'stn_admin';

-- =====================================================
-- 디버깅용 쿼리
-- =====================================================

-- 사용자 목록 확인
SELECT id, username, real_name, user_role, status, is_active, created_at FROM tbl_users;

-- 특정 사용자 확인
SELECT * FROM tbl_users WHERE username = 'stn_admin';

-- 고객사 정보 확인
SELECT * FROM tbl_customer_hierarchy WHERE id = 1;
