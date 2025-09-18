<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Test extends BaseController
{
    public function db()
    {
        try {
            $db = \Config\Database::connect();
            
            // 간단한 쿼리 실행
            $query = $db->query("SELECT 1 as test");
            $result = $query->getRow();
            
            if ($result) {
                echo "✅ 데이터베이스 연결 성공!<br>";
                echo "테스트 결과: " . $result->test . "<br>";
                echo "데이터베이스: " . $db->getDatabase() . "<br>";
            } else {
                echo "❌ 데이터베이스 연결 실패";
            }
            
        } catch (\Exception $e) {
            echo "❌ 데이터베이스 연결 오류: " . $e->getMessage();
        }
    }
    
    public function tables()
    {
        try {
            $db = \Config\Database::connect();
            
            // 테이블 목록 조회
            $query = $db->query("SHOW TABLES");
            $tables = $query->getResult();
            
            echo "📋 데이터베이스 테이블 목록:<br><br>";
            
            if (empty($tables)) {
                echo "테이블이 없습니다.";
            } else {
                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    echo "• " . $tableName . "<br>";
                }
            }
            
        } catch (\Exception $e) {
            echo "❌ 테이블 조회 오류: " . $e->getMessage();
        }
    }
}
