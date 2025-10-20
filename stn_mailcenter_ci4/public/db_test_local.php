<?php
// 로컬 MariaDB 연결 테스트
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>로컬 MariaDB 연결 테스트</h2>";

try {
    // 직접 mysqli 연결 테스트
    $mysqli = new mysqli('localhost', 'root', 'rbghkd75!@#$', 'mailcenter');
    
    if ($mysqli->connect_error) {
        throw new Exception("연결 실패: " . $mysqli->connect_error);
    }
    
    echo "<p style='color: green;'>✅ MariaDB 연결 성공!</p>";
    
    // 데이터베이스 정보 조회
    $result = $mysqli->query("SELECT DATABASE() as current_db, NOW() as current_datetime");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>현재 데이터베이스:</strong> " . $row['current_db'] . "</p>";
        echo "<p><strong>현재 시간:</strong> " . $row['current_datetime'] . "</p>";
    }
    
    // 테이블 목록 조회
    $result = $mysqli->query("SHOW TABLES");
    if ($result) {
        echo "<h3>생성된 테이블 목록:</h3>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    // 서비스 타입 테이블 확인
    $result = $mysqli->query("SELECT COUNT(*) as count FROM tbl_service_types");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>서비스 타입 개수:</strong> " . $row['count'] . "</p>";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 오류: " . $e->getMessage() . "</p>";
}
?>
