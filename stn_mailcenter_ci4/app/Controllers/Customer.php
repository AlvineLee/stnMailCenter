<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerHierarchyModel;
use App\Models\UserModel;

class Customer extends BaseController
{
    protected $customerHierarchyModel;
    protected $userModel;

    public function __construct()
    {
        $this->customerHierarchyModel = new CustomerHierarchyModel();
        $this->userModel = new UserModel();
        helper('form');
    }

    public function head()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');
        
        // 로그인한 사용자가 속한 그룹의 본점 목록 조회
        $headOffices = $this->customerHierarchyModel->getCustomersByUserGroup($userId, 'head_office');
        
        // 각 본점별 사용자 계정 목록 조회
        $customersWithUsers = [];
        foreach ($headOffices as $headOffice) {
            $users = $this->userModel->getUsersByCustomer($headOffice['id']);
            $headOffice['users'] = $users;
            $headOffice['user_count'] = count($users);
            $customersWithUsers[] = $headOffice;
        }

        $data = [
            'title' => '본점관리',
            'content_header' => [
                'title' => '본점관리',
                'description' => '본점 정보를 등록 및 관리할 수 있습니다.'
            ],
            'customers' => $customersWithUsers,
            'level' => 'head_office'
        ];

        return view('customer/head', $data);
    }

    public function branch()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');
        
        // 로그인한 사용자가 속한 그룹의 지사 목록 조회
        $branches = $this->customerHierarchyModel->getCustomersByUserGroup($userId, 'branch');
        
        // 로그인한 사용자가 속한 그룹의 본점 목록 조회 (지사 등록 시 상위 선택용)
        $parentCustomers = $this->customerHierarchyModel->getCustomersByUserGroup($userId, 'head_office');
        
        // 각 지사별 사용자 계정 목록 조회
        $customersWithUsers = [];
        foreach ($branches as $branch) {
            $users = $this->userModel->getUsersByCustomer($branch['id']);
            $branch['users'] = $users;
            $branch['user_count'] = count($users);
            $customersWithUsers[] = $branch;
        }

        $data = [
            'title' => '지사관리',
            'content_header' => [
                'title' => '지사관리',
                'description' => '지사 담당자 계정을 생성 및 관리할 수 있습니다.'
            ],
            'customers' => $customersWithUsers,
            'parent_customers' => $parentCustomers,
            'level' => 'branch'
        ];

        return view('customer/branch', $data);
    }

    public function agency()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');
        
        // 로그인한 사용자가 속한 그룹의 대리점 목록 조회
        $agencies = $this->customerHierarchyModel->getCustomersByUserGroup($userId, 'agency');
        
        // 로그인한 사용자가 속한 그룹의 본점, 지사 목록 조회 (대리점 등록 시 상위 선택용)
        $headOffices = $this->customerHierarchyModel->getCustomersByUserGroup($userId, 'head_office');
        $branches = $this->customerHierarchyModel->getCustomersByUserGroup($userId, 'branch');
        $parentCustomers = array_merge($headOffices, $branches);
        
        // 각 대리점별 사용자 계정 목록 조회
        $customersWithUsers = [];
        foreach ($agencies as $agency) {
            $users = $this->userModel->getUsersByCustomer($agency['id']);
            $agency['users'] = $users;
            $agency['user_count'] = count($users);
            $customersWithUsers[] = $agency;
        }

        $data = [
            'title' => '대리점관리',
            'content_header' => [
                'title' => '대리점관리',
                'description' => '대리점 담당자 계정을 생성 및 관리할 수 있습니다.'
            ],
            'customers' => $customersWithUsers,
            'parent_customers' => $parentCustomers,
            'level' => 'agency'
        ];

        return view('customer/agency', $data);
    }

    public function budget()
    {
        $data = [
            'title' => '예산관리',
            'content_header' => [
                'title' => '예산관리',
                'description' => '예산 정보를 관리할 수 있습니다.'
            ]
        ];

        return view('customer/budget', $data);
    }

    public function items()
    {
        $data = [
            'title' => '항목관리',
            'content_header' => [
                'title' => '항목관리',
                'description' => '요금 및 청구 항목을 관리할 수 있습니다.'
            ],
            'fee_items' => [
                '악천후', '심야', '주말', '명절', '고객할증', '경유', '상하차',
                '야간', '조조', '동승', '초급송', '과적', '엘리베이터없음'
            ],
            'billing_items' => [
                'No', '일자', '회사명', '부서명', '사용자', '연락처',
                '출발회사명', '출발동', '도착회사명', '도착동', '행정지역',
                '오더방법', '기본요금', '추가요금', '대납', '수화물대',
                '요금합계', '할인요금', '정산금액', '도착연락처',
                '도착지상세주소', '배차시간', '픽업시간', '완료시간',
                '배송사유', '적요', '거리(Km)', '출발지상세', '운송수단',
                '적용구간', '배송기사', '사번', '부서코드', 'ID'
            ]
        ];

        return view('customer/items', $data);
    }

    /**
     * 고객사별 사용자 계정 목록 조회 (AJAX)
     */
    public function getUsersByCustomer($customerId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 사용자 목록 조회
        $users = $this->userModel->getUsersByCustomer($customerId);

        return $this->response->setJSON([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * 고객사별 사용자 계정 생성 (AJAX)
     */
    public function createUserAccount()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'customer_id' => 'required|integer',
            'username' => 'required|min_length[3]|max_length[50]|is_unique[tbl_users.username]',
            'password' => 'required|min_length[4]',
            'real_name' => 'required|min_length[2]|max_length[50]',
            'email' => 'permit_empty|valid_email|max_length[100]',
            'phone' => 'permit_empty|max_length[20]',
            'department' => 'permit_empty|max_length[50]',
            'position' => 'permit_empty|max_length[50]',
            'user_role' => 'required|in_list[admin,manager,user]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        try {
            // 사용자 계정 생성 (UserModel의 beforeInsert에서 비밀번호 해시 처리)
            $userData = [
                'customer_id' => $inputData['customer_id'],
                'username' => $inputData['username'],
                'password' => $inputData['password'], // 평문 비밀번호 (UserModel이 자동 해시)
                'real_name' => $inputData['real_name'],
                'email' => !empty($inputData['email']) ? $inputData['email'] : null,
                'phone' => !empty($inputData['phone']) ? $inputData['phone'] : null,
                'department' => !empty($inputData['department']) ? $inputData['department'] : null,
                'position' => !empty($inputData['position']) ? $inputData['position'] : null,
                'user_role' => $inputData['user_role'],
                'status' => 'active',
                'is_active' => 1
            ];

            $userId = $this->userModel->insert($userData);

            if (!$userId) {
                throw new \Exception('사용자 계정 생성에 실패했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '사용자 계정이 성공적으로 생성되었습니다.',
                'data' => ['user_id' => $userId]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Customer::createUserAccount - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 사용자 계정 정보 수정 (AJAX)
     */
    public function updateUserAccount($userId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'real_name' => 'permit_empty|min_length[2]|max_length[50]',
            'email' => 'permit_empty|valid_email|max_length[100]',
            'phone' => 'permit_empty|max_length[20]',
            'department' => 'permit_empty|max_length[50]',
            'position' => 'permit_empty|max_length[50]',
            'user_role' => 'permit_empty|in_list[admin,manager,user]',
            'status' => 'permit_empty|in_list[active,inactive,suspended]',
            'is_active' => 'permit_empty|in_list[0,1]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        try {
            // 사용자 존재 확인
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '사용자를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }

            // 사용자 정보 수정
            $updateData = [];
            if (isset($inputData['real_name'])) {
                $updateData['real_name'] = $inputData['real_name'];
            }
            if (isset($inputData['email'])) {
                $updateData['email'] = $inputData['email'];
            }
            if (isset($inputData['phone'])) {
                $updateData['phone'] = $inputData['phone'];
            }
            if (isset($inputData['department'])) {
                $updateData['department'] = $inputData['department'];
            }
            if (isset($inputData['position'])) {
                $updateData['position'] = $inputData['position'];
            }
            if (isset($inputData['user_role'])) {
                $updateData['user_role'] = $inputData['user_role'];
            }
            if (isset($inputData['status'])) {
                $updateData['status'] = $inputData['status'];
            }
            if (isset($inputData['is_active'])) {
                $updateData['is_active'] = $inputData['is_active'] ? 1 : 0;
            }
            if (isset($inputData['password']) && !empty($inputData['password'])) {
                $updateData['password'] = $inputData['password']; // 평문 비밀번호 (UserModel이 자동 해시)
            }

            if (empty($updateData)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '수정할 정보가 없습니다.'
                ])->setStatusCode(400);
            }

            $updateResult = $this->userModel->update($userId, $updateData);

            if (!$updateResult) {
                throw new \Exception('사용자 정보 수정에 실패했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '사용자 정보가 성공적으로 수정되었습니다.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Customer::updateUserAccount - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 사용자 계정 정보 조회 (AJAX)
     */
    public function getUserAccountInfo($userId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 사용자 정보 조회
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '사용자 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * 본점 고객사 생성 (고객사 + 사용자 계정 함께 생성)
     */
    public function createHeadOffice()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required|min_length[3]|max_length[50]|is_unique[tbl_users.username]',
            'password' => 'required|min_length[4]',
            'customer_name' => 'permit_empty|max_length[100]',
            'address' => 'permit_empty',
            'memo' => 'permit_empty',
            'main_contact_name' => 'required|min_length[2]|max_length[50]',
            'main_contact_phone' => 'required|max_length[20]',
            'sub_contact_name' => 'permit_empty|max_length[50]',
            'sub_contact_phone' => 'permit_empty|max_length[20]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $userId = session()->get('user_id');
            
            // 로그인한 사용자가 속한 본점 ID 찾기 (같은 그룹으로 제한)
            $userCustomerId = session()->get('customer_id');
            $headOfficeId = $this->customerHierarchyModel->getHeadOfficeId($userCustomerId);
            
            // 새 본점을 같은 그룹에 추가하려면 parent_id는 null이지만, 
            // 본점은 독립적이므로 그냥 생성 (실제로는 그룹 구분이 필요하면 추가 로직 필요)
            
            // 1. 고객사 코드 생성 (본점명이 없으면 아이디 사용)
            $customerName = !empty($inputData['customer_name']) ? $inputData['customer_name'] : $inputData['username'];
            $customerCode = $this->customerHierarchyModel->generateCustomerCode($customerName);
            
            // 2. 본점 고객사 생성
            $customerData = [
                'customer_code' => $customerCode,
                'customer_name' => $customerName,
                'address' => !empty($inputData['address']) ? $inputData['address'] : null,
                'memo' => !empty($inputData['memo']) ? $inputData['memo'] : null
            ];

            $customerId = $this->customerHierarchyModel->createHeadOffice($customerData);

            if (!$customerId) {
                throw new \Exception('본점 생성에 실패했습니다.');
            }

            // 3. 사용자 계정 생성
            $userData = [
                'customer_id' => $customerId,
                'username' => $inputData['username'],
                'password' => $inputData['password'], // 평문 비밀번호 (UserModel이 자동 해시)
                'real_name' => $inputData['main_contact_name'],
                'phone' => $inputData['main_contact_phone'],
                'user_role' => 'admin',
                'status' => 'active',
                'is_active' => 1
            ];

            $newUserId = $this->userModel->insert($userData);

            if (!$newUserId) {
                throw new \Exception('사용자 계정 생성에 실패했습니다.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('데이터베이스 트랜잭션 오류가 발생했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '본점이 성공적으로 등록되었습니다.',
                'data' => [
                    'customer_id' => $customerId,
                    'user_id' => $newUserId
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Customer::createHeadOffice - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 본점 고객사 수정
     */
    public function updateHeadOffice($customerId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'customer_name' => 'max_length[100]',
            'main_contact_name' => 'required|max_length[50]',
            'main_contact_phone' => 'required|max_length[20]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. 고객사 정보 수정
            $customerData = [
                'customer_name' => $inputData['customer_name'] ?? null,
                'address' => $inputData['address'] ?? null,
                'memo' => $inputData['memo'] ?? null
            ];
            
            // null 값 제거
            $customerData = array_filter($customerData, function($value) {
                return $value !== null;
            });

            if (!empty($customerData)) {
                $updateResult = $this->customerHierarchyModel->updateCustomer($customerId, $customerData);
                if (!$updateResult) {
                    throw new \Exception('본점 정보 수정에 실패했습니다.');
                }
            }

            // 2. 사용자 계정 정보 수정 (담당자 정보)
            $users = $this->userModel->getUsersByCustomer($customerId);
            
            if (!empty($users) && count($users) > 0) {
                // 첫 번째 사용자 (담당자) 정보 수정
                $mainUser = $users[0];
                $mainUserData = [
                    'real_name' => $inputData['main_contact_name'],
                    'phone' => $inputData['main_contact_phone']
                ];
                $this->userModel->update($mainUser['id'], $mainUserData);

                // 두 번째 사용자 (추가 담당자) 정보 수정 또는 생성
                if (!empty($inputData['sub_contact_name']) && !empty($inputData['sub_contact_phone'])) {
                    if (count($users) > 1) {
                        // 기존 추가 담당자 수정
                        $subUser = $users[1];
                        $subUserData = [
                            'real_name' => $inputData['sub_contact_name'],
                            'phone' => $inputData['sub_contact_phone']
                        ];
                        $this->userModel->update($subUser['id'], $subUserData);
                    } else {
                        // 새 추가 담당자 생성
                        $subUserData = [
                            'customer_id' => $customerId,
                            'username' => $mainUser['username'] . '_sub',
                            'password' => 'temp1234', // 임시 비밀번호
                            'real_name' => $inputData['sub_contact_name'],
                            'phone' => $inputData['sub_contact_phone'],
                            'user_role' => 'user',
                            'status' => 'active',
                            'is_active' => 1
                        ];
                        $this->userModel->insert($subUserData);
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('데이터베이스 트랜잭션 오류가 발생했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '본점 정보가 성공적으로 수정되었습니다.'
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Customer::updateHeadOffice - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 지사 고객사 생성 (고객사 + 사용자 계정 함께 생성)
     */
    public function createBranch()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required|min_length[3]|max_length[50]|is_unique[tbl_users.username]',
            'password' => 'required|min_length[4]',
            'customer_name' => 'required|max_length[100]',
            'parent_id' => 'required|integer',
            'user_role' => 'required|in_list[admin,manager,user]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. 고객사 코드 생성
            $customerCode = $this->customerHierarchyModel->generateCustomerCode($inputData['customer_name']);
            
            // 2. 지사 고객사 생성
            $customerData = [
                'customer_code' => $customerCode,
                'customer_name' => $inputData['customer_name'],
                'parent_id' => $inputData['parent_id']
            ];

            $customerId = $this->customerHierarchyModel->createBranch($customerData);

            if (!$customerId) {
                throw new \Exception('지사 생성에 실패했습니다.');
            }

            // 3. 사용자 계정 생성
            $userData = [
                'customer_id' => $customerId,
                'username' => $inputData['username'],
                'password' => $inputData['password'], // 평문 비밀번호 (UserModel이 자동 해시)
                'real_name' => $inputData['customer_name'], // 지사명을 실명으로 사용
                'user_role' => $inputData['user_role'],
                'status' => 'active',
                'is_active' => 1
            ];

            $newUserId = $this->userModel->insert($userData);

            if (!$newUserId) {
                throw new \Exception('사용자 계정 생성에 실패했습니다.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('데이터베이스 트랜잭션 오류가 발생했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '지사가 성공적으로 등록되었습니다.',
                'data' => [
                    'customer_id' => $customerId,
                    'user_id' => $newUserId
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Customer::createBranch - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 지사 고객사 수정
     */
    public function updateBranch($customerId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'customer_name' => 'required|max_length[100]',
            'user_role' => 'required|in_list[admin,manager,user]'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. 고객사 정보 수정
            $customerData = [
                'customer_name' => $inputData['customer_name']
            ];
            
            $updateResult = $this->customerHierarchyModel->updateCustomer($customerId, $customerData);
            if (!$updateResult) {
                throw new \Exception('지사 정보 수정에 실패했습니다.');
            }

            // 2. 사용자 계정 정보 수정 (권한 정보)
            $users = $this->userModel->getUsersByCustomer($customerId);
            
            if (!empty($users) && count($users) > 0) {
                $mainUser = $users[0];
                $userData = [
                    'user_role' => $inputData['user_role']
                ];
                
                // 비밀번호가 입력된 경우에만 포함
                if (isset($inputData['password']) && !empty($inputData['password'])) {
                    $userData['password'] = $inputData['password']; // 평문 비밀번호 (UserModel이 자동 해시)
                }
                
                $this->userModel->update($mainUser['id'], $userData);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('데이터베이스 트랜잭션 오류가 발생했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '지사 정보가 성공적으로 수정되었습니다.'
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Customer::updateBranch - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 대리점 고객사 생성 (고객사 + 사용자 계정 함께 생성)
     */
    public function createAgency()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'username' => 'required|min_length[3]|max_length[50]|is_unique[tbl_users.username]',
            'password' => 'required|min_length[4]',
            'customer_name' => 'required|max_length[100]',
            'representative_name' => 'required|min_length[2]|max_length[50]',
            'contact_phone' => 'required|max_length[20]',
            'address' => 'required|max_length[200]',
            'address_detail' => 'required|max_length[200]',
            'parent_id' => 'required|integer',
            'department' => 'permit_empty|max_length[50]',
            'position' => 'permit_empty|max_length[50]',
            'memo' => 'permit_empty'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. 고객사 코드 생성
            $customerCode = $this->customerHierarchyModel->generateCustomerCode($inputData['customer_name']);
            
            // 2. 대리점 고객사 생성 (주소 합치기)
            $fullAddress = trim($inputData['address'] . ' ' . $inputData['address_detail']);
            $customerData = [
                'customer_code' => $customerCode,
                'customer_name' => $inputData['customer_name'],
                'parent_id' => $inputData['parent_id'],
                'representative_name' => $inputData['representative_name'],
                'contact_phone' => $inputData['contact_phone'],
                'address' => $fullAddress,
                'memo' => !empty($inputData['memo']) ? $inputData['memo'] : null
            ];

            $customerId = $this->customerHierarchyModel->createAgency($customerData);

            if (!$customerId) {
                throw new \Exception('대리점 생성에 실패했습니다.');
            }

            // 3. 사용자 계정 생성
            $userData = [
                'customer_id' => $customerId,
                'username' => $inputData['username'],
                'password' => $inputData['password'], // 평문 비밀번호 (UserModel이 자동 해시)
                'real_name' => $inputData['representative_name'],
                'phone' => $inputData['contact_phone'],
                'department' => !empty($inputData['department']) ? $inputData['department'] : null,
                'position' => !empty($inputData['position']) ? $inputData['position'] : null,
                'user_role' => 'admin',
                'status' => 'active',
                'is_active' => 1
            ];

            $newUserId = $this->userModel->insert($userData);

            if (!$newUserId) {
                throw new \Exception('사용자 계정 생성에 실패했습니다.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('데이터베이스 트랜잭션 오류가 발생했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '대리점이 성공적으로 등록되었습니다.',
                'data' => [
                    'customer_id' => $customerId,
                    'user_id' => $newUserId
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Customer::createAgency - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 고객사 정보 조회 (AJAX)
     */
    public function getCustomerInfo($customerId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 고객사 정보 조회
        $customer = $this->customerHierarchyModel->getCustomerById($customerId);

        if (!$customer) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '고객사 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        // 상위 고객사명 추가
        if (!empty($customer['parent_id'])) {
            $parentCustomer = $this->customerHierarchyModel->getCustomerById($customer['parent_id']);
            if ($parentCustomer) {
                $customer['parent_customer_name'] = $parentCustomer['customer_name'];
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $customer
        ]);
    }

}
