<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'username',
        'password',
        'email',
        'company_name',
        'contact',
        'address',
        'status',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
        'password' => 'required|min_length[6]',
        'email' => 'required|valid_email|is_unique[users.email]',
        'company_name' => 'required|min_length[2]|max_length[100]',
        'contact' => 'required|min_length[10]|max_length[20]'
    ];

    protected $validationMessages = [
        'username' => [
            'required' => '사용자명은 필수입니다.',
            'min_length' => '사용자명은 최소 3자 이상이어야 합니다.',
            'max_length' => '사용자명은 최대 50자까지 가능합니다.',
            'is_unique' => '이미 사용 중인 사용자명입니다.'
        ],
        'password' => [
            'required' => '비밀번호는 필수입니다.',
            'min_length' => '비밀번호는 최소 6자 이상이어야 합니다.'
        ],
        'email' => [
            'required' => '이메일은 필수입니다.',
            'valid_email' => '올바른 이메일 형식이 아닙니다.',
            'is_unique' => '이미 사용 중인 이메일입니다.'
        ],
        'company_name' => [
            'required' => '회사명은 필수입니다.',
            'min_length' => '회사명은 최소 2자 이상이어야 합니다.',
            'max_length' => '회사명은 최대 100자까지 가능합니다.'
        ],
        'contact' => [
            'required' => '연락처는 필수입니다.',
            'min_length' => '연락처는 최소 10자 이상이어야 합니다.',
            'max_length' => '연락처는 최대 20자까지 가능합니다.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * 비밀번호 해시화
     */
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * 사용자 인증
     */
    public function authenticate($username, $password)
    {
        $user = $this->where('username', $username)
                    ->where('status', 'active')
                    ->first();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /**
     * 사용자명으로 사용자 조회
     */
    public function findByUsername($username)
    {
        return $this->where('username', $username)->first();
    }

    /**
     * 활성 사용자 목록 조회
     */
    public function getActiveUsers()
    {
        return $this->where('status', 'active')
                   ->orderBy('company_name', 'ASC')
                   ->findAll();
    }
}
