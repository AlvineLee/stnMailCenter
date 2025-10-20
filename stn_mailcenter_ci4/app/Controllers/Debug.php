<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Debug extends BaseController
{
    public function login()
    {
        // 보안을 위해 개발 환경에서만 실행
        if (ENVIRONMENT !== 'development') {
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }
        
        $db = \Config\Database::connect();
        
        $data = [
            'title' => '로그인 디버깅',
            'debug_info' => []
        ];
        
        // 1. 데이터베이스 연결 확인
        try {
            $db->query("SELECT 1");
            $data['debug_info']['db_connection'] = '✅ 데이터베이스 연결 성공';
        } catch (\Exception $e) {
            $data['debug_info']['db_connection'] = '❌ 데이터베이스 연결 실패: ' . $e->getMessage();
            return view('debug/login', $data);
        }
        
        // 2. 테이블 존재 확인
        $tables = ['tbl_users', 'tbl_customer_hierarchy'];
        foreach ($tables as $table) {
            try {
                $result = $db->query("SHOW TABLES LIKE '$table'")->getResult();
                $data['debug_info']['tables'][$table] = count($result) > 0 ? '✅ 존재' : '❌ 없음';
            } catch (\Exception $e) {
                $data['debug_info']['tables'][$table] = '❌ 확인 실패: ' . $e->getMessage();
            }
        }
        
        // 3. 사용자 데이터 확인
        try {
            $users = $db->table('tbl_users')->get()->getResultArray();
            $data['debug_info']['user_count'] = count($users);
            $data['debug_info']['users'] = $users;
        } catch (\Exception $e) {
            $data['debug_info']['user_error'] = '❌ 사용자 데이터 조회 실패: ' . $e->getMessage();
        }
        
        // 4. 고객사 데이터 확인
        try {
            $customers = $db->table('tbl_customer_hierarchy')->get()->getResultArray();
            $data['debug_info']['customer_count'] = count($customers);
            $data['debug_info']['customers'] = $customers;
        } catch (\Exception $e) {
            $data['debug_info']['customer_error'] = '❌ 고객사 데이터 조회 실패: ' . $e->getMessage();
        }
        
        // 5. stn_admin 사용자 상세 확인
        try {
            $stnAdmin = $db->table('tbl_users')
                          ->where('username', 'stn_admin')
                          ->get()
                          ->getRowArray();
            
            if ($stnAdmin) {
                $data['debug_info']['stn_admin'] = $stnAdmin;
                $data['debug_info']['password_test'] = password_verify('1111', $stnAdmin['password']) ? '✅ 성공' : '❌ 실패';
            } else {
                $data['debug_info']['stn_admin'] = null;
                $data['debug_info']['stn_admin_error'] = '❌ stn_admin 사용자를 찾을 수 없습니다';
            }
        } catch (\Exception $e) {
            $data['debug_info']['stn_admin_error'] = '❌ stn_admin 사용자 조회 실패: ' . $e->getMessage();
        }
        
        // 6. 비밀번호 해시 테스트
        $testPassword = '1111';
        $testHash = password_hash($testPassword, PASSWORD_DEFAULT);
        $data['debug_info']['password_test'] = [
            'test_password' => $testPassword,
            'generated_hash' => $testHash,
            'verification' => password_verify($testPassword, $testHash) ? '✅ 성공' : '❌ 실패'
        ];
        
        return view('debug/login', $data);
    }
    
    public function createUser()
    {
        // 보안을 위해 개발 환경에서만 실행
        if (ENVIRONMENT !== 'development') {
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }
        
        $db = \Config\Database::connect();
        
        try {
            // 기존 사용자 삭제
            $db->table('tbl_users')->where('username', 'stn_admin')->delete();
            
            // 새 사용자 생성
            $userData = [
                'customer_id' => 1,
                'username' => 'stn_admin',
                'password' => '1111', // UserModel이 자동으로 해시화
                'real_name' => 'STN관리자',
                'email' => 'admin@stn.co.kr',
                'phone' => '02-1234-5678',
                'department' => '관리팀',
                'position' => '대표',
                'user_role' => 'super_admin',
                'status' => 'active',
                'is_active' => TRUE,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $db->table('tbl_users')->insert($userData);
            
            if ($result) {
                return redirect()->to('/debug/login')->with('success', 'stn_admin 사용자가 생성되었습니다.');
            } else {
                return redirect()->to('/debug/login')->with('error', '사용자 생성에 실패했습니다.');
            }
            
        } catch (\Exception $e) {
            return redirect()->to('/debug/login')->with('error', '오류: ' . $e->getMessage());
        }
    }
}
