<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\CustomerHierarchyModel;
use App\Models\ServiceTypeModel;
use App\Models\UserServicePermissionModel;

class GroupCompany extends BaseController
{
    protected $userModel;
    protected $customerHierarchyModel;
    protected $serviceTypeModel;
    protected $userServicePermissionModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->customerHierarchyModel = new CustomerHierarchyModel();
        $this->serviceTypeModel = new ServiceTypeModel();
        $this->userServicePermissionModel = new UserServicePermissionModel();
        helper('form');
    }

    /**
     * 그룹사 관리 메인 페이지 (슈퍼어드민만 접근 가능)
     * 고객사 본점 계정 생성
     */
    public function index()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // 슈퍼어드민 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return redirect()->to('/dashboard')->with('error', '접근 권한이 없습니다.');
        }

        // 슈퍼관리자를 제외한 모든 사용자 계정 조회 (고객사 정보 포함)
        $users = $this->userModel->getUsersWithCustomerInfo(session()->get('user_id'));

        $data = [
            'title' => '그룹사 관리',
            'content_header' => [
                'title' => '그룹사 관리',
                'description' => '고객사 본점 계정을 생성 및 관리할 수 있습니다.'
            ],
            'users' => $users
        ];

        return view('group_company/index', $data);
    }

    /**
     * 그룹사 본점 계정 생성 (AJAX)
     */
    public function createHeadOfficeAccount()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼어드민 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'company_name' => 'required|max_length[100]',
            'username' => 'required|min_length[3]|max_length[50]|is_unique[tbl_users.username]',
            'password' => 'required|min_length[4]',
            'real_name' => 'required|min_length[2]|max_length[50]',
            'contact_phone' => 'permit_empty|max_length[20]',
            'address' => 'permit_empty',
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
            $customerCode = $this->customerHierarchyModel->generateCustomerCode($inputData['company_name']);
            
            // 2. 고객사 계층 구조에 본점 생성
            $customerData = [
                'customer_code' => $customerCode,
                'customer_name' => $inputData['company_name'],
                'contact_phone' => !empty($inputData['contact_phone']) ? $inputData['contact_phone'] : null,
                'address' => !empty($inputData['address']) ? $inputData['address'] : null,
                'memo' => !empty($inputData['memo']) ? $inputData['memo'] : null
            ];

            $customerId = $this->customerHierarchyModel->createHeadOffice($customerData);

            if (!$customerId) {
                throw new \Exception('고객사 생성에 실패했습니다.');
            }

            // 3. 본점 계정 생성
            // 비밀번호는 UserModel의 beforeInsert에서 자동으로 해시 처리됨
            $userData = [
                'customer_id' => $customerId,
                'username' => $inputData['username'],
                'password' => $inputData['password'], // 평문 비밀번호 (UserModel이 자동 해시)
                'real_name' => $inputData['real_name'],
                'phone' => !empty($inputData['contact_phone']) ? $inputData['contact_phone'] : null,
                'user_role' => 'admin', // 본점 관리자는 admin 권한
                'status' => 'active',
                'is_active' => 1
            ];

            $userId = $this->userModel->insert($userData);

            if (!$userId) {
                throw new \Exception('사용자 계정 생성에 실패했습니다.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('데이터베이스 트랜잭션 오류가 발생했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '그룹사 본점 계정이 성공적으로 생성되었습니다.',
                'data' => [
                    'customer_id' => $customerId,
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'GroupCompany::createHeadOfficeAccount - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 계정 정보 조회 (AJAX)
     */
    public function getAccountInfo($userId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼어드민 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        // 계정 정보 조회
        $accountInfo = $this->userModel->getUserAccountInfo($userId);

        if (!$accountInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '계정 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $accountInfo
        ]);
    }

    /**
     * 비밀번호 변경 (AJAX)
     */
    public function changePassword()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼어드민 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'user_id' => 'required|integer',
            'new_password' => 'required|min_length[4]'
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
            $user = $this->userModel->find($inputData['user_id']);
            
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '사용자를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }

            // 비밀번호 변경 (UserModel의 beforeUpdate에서 자동 해시 처리됨)
            $updateResult = $this->userModel->update($inputData['user_id'], [
                'password' => $inputData['new_password'] // 평문 비밀번호 (UserModel이 자동 해시)
            ]);

            if (!$updateResult) {
                throw new \Exception('비밀번호 변경에 실패했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '비밀번호가 성공적으로 변경되었습니다.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'GroupCompany::changePassword - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 그룹사 로고 업로드/수정 (AJAX)
     */
    public function uploadLogo()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼어드민 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        // JSON 요청 처리 (클립보드 이미지)
        $inputData = $this->request->getJSON(true);
        
        try {
            $customerId = null;
            $logoPath = null;

            // 클립보드에서 붙여넣은 이미지 처리
            if (!empty($inputData['image_data'])) {
                // base64 이미지 데이터인지 확인
                if (preg_match('/^data:image\/(\w+);base64,/', $inputData['image_data'], $matches)) {
                    $customerId = $inputData['customer_id'] ?? null;
                    
                    if (!$customerId) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => '고객사 ID가 필요합니다.'
                        ])->setStatusCode(400);
                    }

                    // base64 데이터 추출
                    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $inputData['image_data']);
                    $imageData = base64_decode($imageData);
                    
                    if ($imageData === false) {
                        throw new \Exception('이미지 디코딩에 실패했습니다.');
                    }

                    // 파일 업로드 경로
                    $uploadPath = FCPATH . 'uploads/logos/';
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    // 파일명 생성
                    $imageType = $matches[1]; // png, jpeg, gif 등
                    $fileName = 'logo_' . $customerId . '_' . time() . '_' . uniqid() . '.' . $imageType;
                    $filePath = $uploadPath . $fileName;

                    // 파일 저장
                    if (file_put_contents($filePath, $imageData) === false) {
                        throw new \Exception('이미지 파일 저장에 실패했습니다.');
                    }

                    $logoPath = 'uploads/logos/' . $fileName;
                }
            }
            // 파일 업로드 처리
            elseif ($file = $this->request->getFile('logo_file')) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $customerId = $this->request->getPost('customer_id');
                    
                    if (!$customerId) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => '고객사 ID가 필요합니다.'
                        ])->setStatusCode(400);
                    }

                    // 파일 유효성 검사 (이미지만 허용)
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file->getMimeType(), $allowedTypes)) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => '이미지 파일만 업로드 가능합니다.'
                        ])->setStatusCode(400);
                    }

                    // 파일 업로드 경로
                    $uploadPath = FCPATH . 'uploads/logos/';
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    // 파일명 생성
                    $extension = $file->getExtension();
                    $fileName = 'logo_' . $customerId . '_' . time() . '_' . uniqid() . '.' . $extension;

                    // 파일 이동
                    if (!$file->move($uploadPath, $fileName)) {
                        throw new \Exception('이미지 파일 업로드에 실패했습니다.');
                    }

                    $logoPath = 'uploads/logos/' . $fileName;
                }
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '이미지 파일이 제공되지 않았습니다.'
                ])->setStatusCode(400);
            }

            if (!$logoPath || !$customerId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '이미지 처리에 실패했습니다.'
                ])->setStatusCode(400);
            }

            // 기존 로고 파일 삭제
            $existingCustomer = $this->customerHierarchyModel->getCustomerById($customerId);
            if ($existingCustomer && !empty($existingCustomer['logo_path'])) {
                $oldLogoPath = FCPATH . $existingCustomer['logo_path'];
                if (file_exists($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }

            // DB에 로고 경로 저장
            $updateResult = $this->customerHierarchyModel->updateCustomer($customerId, [
                'logo_path' => $logoPath
            ]);

            if (!$updateResult) {
                throw new \Exception('로고 정보 저장에 실패했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '로고가 성공적으로 업로드되었습니다.',
                'data' => [
                    'logo_path' => base_url($logoPath)
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'GroupCompany::uploadLogo - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 그룹사 로고 삭제 (AJAX)
     */
    public function deleteLogo($customerId)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼어드민 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        try {
            // customerId 유효성 검사
            if (empty($customerId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '고객사 ID가 필요합니다.'
                ])->setStatusCode(400);
            }

            // 고객사 정보 조회
            $customer = $this->customerHierarchyModel->getCustomerById($customerId);
            
            if (!$customer) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '고객사 정보를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }

            // 기존 로고 파일 삭제
            if (!empty($customer['logo_path'])) {
                $oldLogoPath = FCPATH . $customer['logo_path'];
                if (file_exists($oldLogoPath)) {
                    if (!@unlink($oldLogoPath)) {
                        log_message('warning', "GroupCompany::deleteLogo - Failed to delete logo file: {$oldLogoPath}");
                        // 파일 삭제 실패해도 DB 업데이트는 진행
                    }
                }
            }

            // DB에서 로고 경로 삭제
            $updateResult = $this->customerHierarchyModel->updateCustomer($customerId, [
                'logo_path' => null
            ]);

            if (!$updateResult) {
                log_message('error', "GroupCompany::deleteLogo - Update failed for customer ID: {$customerId}");
                throw new \Exception('로고 삭제에 실패했습니다. 데이터베이스 업데이트 오류.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '로고가 성공적으로 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'GroupCompany::deleteLogo - ' . $e->getMessage());
            log_message('error', 'GroupCompany::deleteLogo - Stack trace: ' . $e->getTraceAsString());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 사용자별 서비스 권한 조회 (AJAX)
     */
    public function getUserServicePermissions()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        $userId = $this->request->getGet('user_id');
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자 ID가 필요합니다.']);
        }

        // 사용자 정보 조회
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자를 찾을 수 없습니다.']);
        }

        // 모든 서비스 타입 조회 (카테고리별 그룹화)
        $serviceTypesGrouped = $this->serviceTypeModel->getServiceTypesGroupedByCategory();

        // 사용자의 현재 서비스 권한 조회 (tbl_user_service_permissions 사용)
        $userPermissions = $this->userServicePermissionModel->getUserServicePermissions($userId);
        
        // 권한을 service_type_id를 키로 하는 배열로 변환 (boolean으로 명시적 변환)
        $permissionMap = [];
        foreach ($userPermissions as $permission) {
            // DB에서 가져온 값(0 또는 1)을 boolean으로 변환
            $permissionMap[$permission['service_type_id']] = (bool)$permission['is_enabled'];
        }

        // 서비스 타입에 권한 정보 추가 (없으면 기본값 false)
        // 마스터 활성화 여부(is_active)가 우선순위
        foreach ($serviceTypesGrouped as $category => &$services) {
            foreach ($services as &$service) {
                // 마스터가 비활성화되어 있으면 무조건 false
                if (isset($service['is_active']) && $service['is_active'] == 0) {
                    $service['is_enabled'] = false;
                } else {
                    // 마스터가 활성화되어 있을 때만 계정별 권한 확인
                    $service['is_enabled'] = isset($permissionMap[$service['id']]) ? $permissionMap[$service['id']] : false;
                }
            }
        }

        // 기본 카테고리 목록
        $defaultCategories = ['퀵서비스', '연계배송서비스', '택배서비스', '우편서비스', '일반서비스', '생활서비스', '메일룸서비스', '해외특송서비스'];
        $categories = $this->serviceTypeModel->getDistinctCategories();
        if (empty($categories)) {
            $categories = $defaultCategories;
        } else {
            $categories = array_unique(array_merge($defaultCategories, $categories));
            $categories = array_values($categories);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'user' => $user,
                'service_types_grouped' => $serviceTypesGrouped,
                'categories' => $categories
            ]
        ]);
    }

    /**
     * 사용자별 서비스 권한 업데이트 (AJAX)
     */
    public function updateUserServicePermissions()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        $userId = $this->request->getPost('user_id');
        $statusUpdates = $this->request->getPost('status_updates');

        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자 ID가 필요합니다.']);
        }

        // 사용자 정보 조회
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자를 찾을 수 없습니다.']);
        }

        // JSON 문자열인 경우 파싱
        if (is_string($statusUpdates)) {
            $statusUpdates = json_decode($statusUpdates, true);
        }

        if (empty($statusUpdates)) {
            return $this->response->setJSON(['success' => false, 'message' => '업데이트할 데이터가 없습니다.']);
        }

        // UserServicePermissionModel을 통한 일괄 업데이트
        $result = $this->userServicePermissionModel->batchUpdateUserPermissions($userId, $statusUpdates);

        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => count($statusUpdates) . '개의 서비스 권한이 업데이트되었습니다.'
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '권한 업데이트에 실패했습니다.']);
        }
    }
}

