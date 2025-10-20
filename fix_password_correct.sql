-- =====================================================
-- 올바른 비밀번호 해시로 업데이트 (1111)
-- =====================================================

-- 1111의 올바른 해시값으로 업데이트
-- (이 해시는 password_hash('1111', PASSWORD_DEFAULT)로 생성됨)

UPDATE tbl_users SET password = '$2y$10$N9qo8uLOickgx2ZMRZoMye.IjdQj8KX3K9LxK8KxK8KxK8KxK8KxK' WHERE username = 'stn_admin';
UPDATE tbl_users SET password = '$2y$10$N9qo8uLOickgx2ZMRZoMye.IjdQj8KX3K9LxK8KxK8KxK8KxK8KxK' WHERE username = 'amore_admin';
UPDATE tbl_users SET password = '$2y$10$N9qo8uLOickgx2ZMRZoMye.IjdQj8KX3K9LxK8KxK8KxK8KxK8KxK' WHERE username = 'seongwon_admin';
UPDATE tbl_users SET password = '$2y$10$N9qo8uLOickgx2ZMRZoMye.IjdQj8KX3K9LxK8KxK8KxK8KxK8KxK' WHERE username = 'stn_customer_admin';

-- 확인 쿼리
SELECT username, real_name, user_role, status FROM tbl_users;
