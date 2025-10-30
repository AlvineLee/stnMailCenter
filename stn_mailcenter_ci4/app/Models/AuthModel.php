<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthModel extends Model
{
    protected $table = 'tbl_users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'customer_id',
        'department_id',
        'username',
        'password',
        'real_name',
        'email',
        'phone',
        'department',
        'position',
        'user_role',
        'status',
        'is_active',
        'last_login_at',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 사용자 인증
     */
    public function authenticate($username, $password)
    {
        // 디버깅: 사용자 조회 테스트
        $userFromDb = $this->db->table('tbl_users')
                              ->where('username', $username)
                              ->where('status', 'active')
                              ->get()
                              ->getRowArray();
        
        log_message('debug', 'User lookup: username=' . $username . ', found=' . ($userFromDb ? 'yes' : 'no'));
        if ($userFromDb) {
            log_message('debug', 'User data: ' . json_encode($userFromDb));
            log_message('debug', 'Password verify: ' . (password_verify($password, $userFromDb['password']) ? 'success' : 'failed'));
        }
        
        // 사용자 조회
        $user = $this->where('username', $username)
                    ->where('status', 'active')
                    ->first();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }

    /**
     * 고객사 정보 조회
     */
    public function getCustomerInfo($customerId)
    {
        return $this->db->table('tbl_customer_hierarchy')
                       ->where('id', $customerId)
                       ->get()
                       ->getRowArray();
    }

    /**
     * 사용자 정보 업데이트 (마지막 로그인 시간 등)
     */
    public function updateUserInfo($userId, $updateData)
    {
        return $this->update($userId, $updateData);
    }
}
