<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminModel;

class Admin extends BaseController
{
    protected $adminModel;
    
    public function __construct()
    {
        $this->adminModel = new AdminModel();
    }
    
    public function orderType()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        // 서비스 타입 목록 조회
        $serviceTypes = $this->adminModel->getServiceTypes();
        
        // 고객사별 서비스 권한 조회
        $servicePermissions = [];
        if ($userRole === 'super_admin') {
            // 슈퍼관리자는 모든 고객사의 권한 조회
            $servicePermissions = $this->adminModel->getAllServicePermissions();
        } else {
            // 일반 관리자는 자신의 고객사 권한만 조회
            $servicePermissions = $this->adminModel->getServicePermissionsByCustomer($customerId);
        }
        
        // 고객사 목록 조회 (슈퍼관리자용)
        $customers = [];
        if ($userRole === 'super_admin') {
            $customers = $this->adminModel->getActiveCustomers();
        }
        
        $data = [
            'title' => '오더유형설정',
            'content_header' => [
                'title' => '오더유형설정',
                'description' => '주문 유형을 설정하고 관리할 수 있습니다.'
            ],
            'service_types' => $serviceTypes,
            'service_permissions' => $servicePermissions,
            'customers' => $customers,
            'user_role' => $userRole,
            'customer_id' => $customerId
        ];

        return view('admin/order-type', $data);
    }
    
    /**
     * 서비스 권한 업데이트
     */
    public function updateServicePermission()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.']);
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        $permissionId = $this->request->getPost('permission_id');
        $isEnabled = $this->request->getPost('is_enabled') === 'true';
        $maxDailyOrders = (int)$this->request->getPost('max_daily_orders');
        $maxMonthlyOrders = (int)$this->request->getPost('max_monthly_orders');
        $specialInstructions = $this->request->getPost('special_instructions');
        
        // 권한 체크
        if ($userRole !== 'super_admin') {
            // 일반 관리자는 자신의 고객사 권한만 수정 가능
            $permission = $this->adminModel->getServicePermissionById($permissionId, $customerId);
            
            if (!$permission) {
                return $this->response->setJSON(['success' => false, 'message' => '권한이 없습니다.']);
            }
        }
        
        // 권한 업데이트
        $updateData = [
            'is_enabled' => $isEnabled,
            'max_daily_orders' => $maxDailyOrders,
            'max_monthly_orders' => $maxMonthlyOrders,
            'special_instructions' => $specialInstructions,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->adminModel->updateServicePermission($permissionId, $updateData);
        
        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => '서비스 권한이 업데이트되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '업데이트에 실패했습니다.']);
        }
    }
    
    /**
     * 새로운 서비스 권한 생성
     */
    public function createServicePermission()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.']);
        }
        
        $userRole = session()->get('user_role');
        $customerId = session()->get('customer_id');
        
        $targetCustomerId = $this->request->getPost('customer_id');
        $serviceTypeId = $this->request->getPost('service_type_id');
        $isEnabled = $this->request->getPost('is_enabled') === 'true';
        $maxDailyOrders = (int)$this->request->getPost('max_daily_orders');
        $maxMonthlyOrders = (int)$this->request->getPost('max_monthly_orders');
        $specialInstructions = $this->request->getPost('special_instructions');
        
        // 권한 체크
        if ($userRole !== 'super_admin') {
            // 일반 관리자는 자신의 고객사에만 권한 생성 가능
            $targetCustomerId = $customerId;
        }
        
        // 중복 체크
        $existing = $this->adminModel->checkDuplicatePermission($targetCustomerId, $serviceTypeId);
        
        if ($existing) {
            return $this->response->setJSON(['success' => false, 'message' => '이미 존재하는 서비스 권한입니다.']);
        }
        
        // 권한 생성
        $insertData = [
            'customer_id' => $targetCustomerId,
            'service_type_id' => $serviceTypeId,
            'is_enabled' => $isEnabled,
            'max_daily_orders' => $maxDailyOrders,
            'max_monthly_orders' => $maxMonthlyOrders,
            'special_instructions' => $specialInstructions,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->adminModel->createServicePermission($insertData);
        
        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => '서비스 권한이 생성되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '생성에 실패했습니다.']);
        }
    }

    public function notification()
    {
        $data = [
            'title' => '알림설정',
            'content_header' => [
                'title' => '알림설정',
                'description' => '시스템 알림을 설정하고 관리할 수 있습니다.'
            ]
        ];

        return view('admin/notification', $data);
    }
}
