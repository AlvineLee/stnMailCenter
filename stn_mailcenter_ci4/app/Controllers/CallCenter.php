<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ServiceTypeModel;
use App\Models\AdminModel;
use App\Models\UserServicePermissionModel;

class CallCenter extends BaseController
{
    protected $userModel;
    protected $serviceTypeModel;
    protected $adminModel;
    protected $userServicePermissionModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->serviceTypeModel = new ServiceTypeModel();
        $this->adminModel = new AdminModel();
        $this->userServicePermissionModel = new UserServicePermissionModel();
    }

    /**
     * 빌딩 콜센터 관리 (계정 리스트)
     */
    public function building()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // 슈퍼관리자 권한 체크
        $userRole = session()->get('user_role');
        if ($userRole !== 'super_admin') {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        // 슈퍼관리자가 아닌 모든 사용자 조회
        $users = $this->userModel->getNonSuperAdminUsers();

        $data = [
            'title' => '빌딩 콜센터 관리',
            'content_header' => [
                'title' => '빌딩 콜센터 관리',
                'description' => '계정별 오더유형을 설정하고 관리할 수 있습니다.'
            ],
            'users' => $users
        ];

        return view('call_center/building', $data);
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
        $defaultCategories = ['퀵서비스', '연계배송서비스', '택배서비스', '우편서비스', '일반서비스', '생활서비스'];
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

    /**
     * 사용자 전체 서비스 활성화
     */
    public function activateAllUserServices()
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
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자 ID가 필요합니다.']);
        }

        // 사용자 정보 조회
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자를 찾을 수 없습니다.']);
        }

        // 사용자의 모든 서비스 권한 활성화 (tbl_user_service_permissions 사용)
        $result = $this->userServicePermissionModel->activateAllUserServices($userId);

        if ($result !== false) {
            return $this->response->setJSON(['success' => true, 'message' => '모든 서비스가 활성화되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '활성화에 실패했습니다.']);
        }
    }

    /**
     * 사용자 전체 서비스 비활성화
     */
    public function deactivateAllUserServices()
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
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자 ID가 필요합니다.']);
        }

        // 사용자 정보 조회
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자를 찾을 수 없습니다.']);
        }

        // 사용자의 모든 서비스 권한 비활성화 (tbl_user_service_permissions 사용)
        $result = $this->userServicePermissionModel->deactivateAllUserServices($userId);

        if ($result !== false) {
            return $this->response->setJSON(['success' => true, 'message' => '모든 서비스가 비활성화되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '비활성화에 실패했습니다.']);
        }
    }
}

