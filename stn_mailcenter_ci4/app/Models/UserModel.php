<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'tbl_users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'customer_id',
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
        'department_id',
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
        'customer_id' => 'required|integer',
        'username' => 'required|min_length[3]|max_length[50]|is_unique[tbl_users.username]',
        'password' => 'required|min_length[4]',
        'real_name' => 'required|min_length[2]|max_length[50]',
        'email' => 'permit_empty|valid_email|is_unique[tbl_users.email]',
        'phone' => 'permit_empty|min_length[10]|max_length[20]',
        'department' => 'permit_empty|max_length[50]',
        'position' => 'permit_empty|max_length[50]',
        'user_role' => 'required|in_list[super_admin,admin,manager,user]',
        'status' => 'required|in_list[active,inactive,suspended]',
        'is_active' => 'permit_empty|in_list[0,1]',
        'department_id' => 'permit_empty|integer'
    ];

    protected $validationMessages = [
        'customer_id' => [
            'required' => '고객사는 필수입니다.',
            'integer' => '올바른 고객사 ID가 아닙니다.'
        ],
        'username' => [
            'required' => '사용자명은 필수입니다.',
            'min_length' => '사용자명은 최소 3자 이상이어야 합니다.',
            'max_length' => '사용자명은 최대 50자까지 가능합니다.',
            'is_unique' => '이미 사용 중인 사용자명입니다.'
        ],
        'password' => [
            'required' => '비밀번호는 필수입니다.',
            'min_length' => '비밀번호는 최소 4자 이상이어야 합니다.'
        ],
        'real_name' => [
            'required' => '실명은 필수입니다.',
            'min_length' => '실명은 최소 2자 이상이어야 합니다.',
            'max_length' => '실명은 최대 50자까지 가능합니다.'
        ],
        'email' => [
            'valid_email' => '올바른 이메일 형식이 아닙니다.',
            'is_unique' => '이미 사용 중인 이메일입니다.'
        ],
        'phone' => [
            'min_length' => '연락처는 최소 10자 이상이어야 합니다.',
            'max_length' => '연락처는 최대 20자까지 가능합니다.'
        ],
        'user_role' => [
            'required' => '사용자 역할은 필수입니다.',
            'in_list' => '올바른 사용자 역할이 아닙니다.'
        ],
        'status' => [
            'required' => '사용자 상태는 필수입니다.',
            'in_list' => '올바른 사용자 상태가 아닙니다.'
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
        // 디버깅: 사용자 조회
        $user = $this->where('username', $username)
                    ->where('status', 'active')
                    ->first();

        // 디버깅 로그
        log_message('debug', 'UserModel authenticate: username=' . $username . ', user_found=' . ($user ? 'yes' : 'no'));
        
        if ($user) {
            log_message('debug', 'User data: ' . json_encode([
                'id' => $user['id'],
                'username' => $user['username'],
                'status' => $user['status'],
                'is_active' => $user['is_active'],
                'user_role' => $user['user_role']
            ]));
            
            $passwordValid = password_verify($password, $user['password']);
            log_message('debug', 'Password verification: ' . ($passwordValid ? 'success' : 'failed'));
            
            if ($passwordValid) {
                return $user;
            }
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

    /**
     * 고객사별 사용자 목록 조회
     */
    public function getUsersByCustomer($customerId, $activeOnly = true)
    {
        $builder = $this->builder();
        $builder->where('customer_id', $customerId);
        
        if ($activeOnly) {
            $builder->where('status', 'active');
        }
        
        $builder->orderBy('company_name', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 부서별 사용자 목록 조회
     */
    public function getUsersByDepartment($departmentId, $activeOnly = true)
    {
        $builder = $this->builder();
        $builder->where('department_id', $departmentId);
        
        if ($activeOnly) {
            $builder->where('status', 'active');
        }
        
        $builder->orderBy('company_name', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 사용자 부서 변경
     */
    public function changeUserDepartment($userId, $departmentId)
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return false;
        }

        // 부서 변경 시 사용자-부서 연결 테이블도 업데이트
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // 기존 부서 연결 제거
            $userDeptBuilder = $db->table('tbl_user_departments');
            $userDeptBuilder->where('user_id', $userId);
            $userDeptBuilder->delete();
            
            // 새 부서 연결 생성
            if ($departmentId) {
                $userDeptBuilder->insert([
                    'user_id' => $userId,
                    'department_id' => $departmentId,
                    'is_primary' => 1,
                    'assigned_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // 사용자 테이블 업데이트
            $this->update($userId, ['department_id' => $departmentId]);
            
            $db->transComplete();
            
            return $db->transStatus() !== false;
            
        } catch (\Exception $e) {
            $db->transRollback();
            return false;
        }
    }

    /**
     * 사용자 등록 (부서 정보 포함)
     */
    public function registerUser($userData)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // 비밀번호 해시화
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // 사용자 생성
            $userId = $this->insert($userData);
            
            if (!$userId) {
                throw new \Exception('사용자 생성에 실패했습니다.');
            }
            
            // 부서가 지정된 경우 사용자-부서 연결 생성
            if (!empty($userData['department_id'])) {
                $userDeptBuilder = $db->table('tbl_user_departments');
                $userDeptBuilder->insert([
                    'user_id' => $userId,
                    'department_id' => $userData['department_id'],
                    'is_primary' => 1,
                    'assigned_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \Exception('사용자 등록 중 오류가 발생했습니다.');
            }
            
            return $userId;
            
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * 사용자 상세 정보 조회 (부서 정보 포함)
     */
    public function getUserWithDepartment($userId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_users u');
        
        $builder->select('
            u.*,
            ch.company_name as customer_name,
            ch.hierarchy_level,
            d.department_name,
            d.department_code,
            d.manager_name as dept_manager_name
        ');
        
        $builder->join('tbl_customer_hierarchy ch', 'u.customer_id = ch.id', 'left');
        $builder->join('tbl_departments d', 'u.department_id = d.id', 'left');
        $builder->where('u.id', $userId);
        
        return $builder->get()->getRowArray();
    }

    /**
     * 부서별 사용자 통계
     */
    public function getDepartmentUserStats($departmentId)
    {
        $builder = $this->builder();
        $builder->select('
            COUNT(*) as total_users,
            COUNT(CASE WHEN status = "active" THEN 1 END) as active_users,
            COUNT(CASE WHEN status = "inactive" THEN 1 END) as inactive_users
        ');
        
        $builder->where('department_id', $departmentId);
        
        return $builder->get()->getRowArray();
    }
}
