<?php
// 1111의 올바른 해시값 생성
$password = '1111';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "Verification: " . (password_verify($password, $hash) ? 'SUCCESS' : 'FAILED') . "\n";

// SQL 업데이트 쿼리 생성
echo "\n-- SQL 업데이트 쿼리:\n";
echo "UPDATE tbl_users SET password = '" . $hash . "' WHERE username = 'stn_admin';\n";
echo "UPDATE tbl_users SET password = '" . $hash . "' WHERE username = 'amore_admin';\n";
echo "UPDATE tbl_users SET password = '" . $hash . "' WHERE username = 'seongwon_admin';\n";
echo "UPDATE tbl_users SET password = '" . $hash . "' WHERE username = 'stn_customer_admin';\n";
?>
