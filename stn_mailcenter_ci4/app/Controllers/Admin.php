<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\ServiceTypeModel;
use App\Models\UserServicePermissionModel;

class Admin extends BaseController
{
    protected $adminModel;
    protected $serviceTypeModel;
    protected $userServicePermissionModel;
    
    public function __construct()
    {
        $this->adminModel = new AdminModel();
        $this->serviceTypeModel = new ServiceTypeModel();
        $this->userServicePermissionModel = new UserServicePermissionModel();
    }
    
    public function orderType()
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
        
        // 서비스 타입 목록 조회 (카테고리별 그룹화)
        $serviceTypesGrouped = $this->serviceTypeModel->getServiceTypesGroupedByCategory();
        if (empty($serviceTypesGrouped)) {
            $serviceTypesGrouped = [];
        }
        
        // 전체 통계
        $stats = $this->serviceTypeModel->getOverallStats();
        if (empty($stats)) {
            $stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
        }
        
        // 기존 카테고리 목록 (select box용)
        $categories = $this->serviceTypeModel->getDistinctCategories();
        
        // 기본 카테고리 목록 (DB에 없을 경우 사용)
        $defaultCategories = ['퀵서비스', '연계배송서비스', '택배서비스', '우편서비스', '일반서비스', '생활서비스'];
        
        // DB에서 가져온 카테고리와 기본 카테고리를 합침 (중복 제거)
        if (empty($categories)) {
            $categories = $defaultCategories;
        } else {
            // DB 카테고리와 기본 카테고리 병합 (중복 제거)
            $categories = array_unique(array_merge($defaultCategories, $categories));
            // 배열 인덱스 재정렬
            $categories = array_values($categories);
        }
        
        $data = [
            'title' => '오더유형설정',
            'content_header' => [
                'title' => '오더유형설정',
                'description' => '주문 유형을 설정하고 관리할 수 있습니다.'
            ],
            'service_types_grouped' => $serviceTypesGrouped,
            'stats' => $stats,
            'categories' => $categories
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

    /**
     * 새 서비스 타입 생성
     */
    public function createServiceType()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }
        
        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }
        
        $serviceCategory = $this->request->getPost('service_category');
        $serviceName = $this->request->getPost('service_name');
        $newCategory = $this->request->getPost('new_category');
        
        // 새 카테고리 입력 시
        if (!empty($newCategory)) {
            $serviceCategory = $newCategory;
        }
        
        // 유효성 검증
        if (empty($serviceCategory) || empty($serviceName)) {
            return $this->response->setJSON(['success' => false, 'message' => '그룹과 서비스명은 필수입니다.']);
        }
        
        // service_code 생성 (한글을 영문으로 변환)
        $serviceCode = $this->generateServiceCode($serviceName);
        
        // 중복 체크
        $existing = $this->serviceTypeModel->where('service_code', $serviceCode)->first();
        if ($existing) {
            // 중복 시 숫자 추가
            $counter = 1;
            while ($this->serviceTypeModel->where('service_code', $serviceCode . '-' . $counter)->first()) {
                $counter++;
            }
            $serviceCode = $serviceCode . '-' . $counter;
        }
        
        // 서비스 타입 생성
        $insertData = [
            'service_code' => $serviceCode,
            'service_name' => $serviceName,
            'service_category' => $serviceCategory,
            'is_active' => 1,
            'sort_order' => 0
        ];
        
        $result = $this->serviceTypeModel->insert($insertData);
        
        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => '서비스 타입이 생성되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '서비스 타입 생성에 실패했습니다.']);
        }
    }

    /**
     * 서비스 타입 수정
     */
    public function updateServiceType()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }
        
        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }
        
        $serviceId = $this->request->getPost('service_id');
        $serviceName = $this->request->getPost('service_name');
        $serviceCategory = $this->request->getPost('service_category');
        $newCategory = $this->request->getPost('new_category');
        
        if (empty($serviceId) || empty($serviceName)) {
            return $this->response->setJSON(['success' => false, 'message' => '서비스 ID와 서비스명은 필수입니다.']);
        }
        
        // 기존 서비스 조회
        $service = $this->serviceTypeModel->find($serviceId);
        if (!$service) {
            return $this->response->setJSON(['success' => false, 'message' => '서비스를 찾을 수 없습니다.']);
        }
        
        // 새 카테고리 입력 시
        if (!empty($newCategory)) {
            $serviceCategory = $newCategory;
        }
        
        // 업데이트 데이터
        $updateData = [
            'service_name' => $serviceName
        ];
        
        if (!empty($serviceCategory)) {
            $updateData['service_category'] = $serviceCategory;
        }
        
        $result = $this->serviceTypeModel->update($serviceId, $updateData);
        
        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => '서비스 타입이 수정되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '서비스 타입 수정에 실패했습니다.']);
        }
    }

    /**
     * 서비스 타입 일괄 상태 업데이트
     */
    public function batchUpdateServiceStatus()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }
        
        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }
        
        $statusUpdates = $this->request->getPost('status_updates');
        
        if (empty($statusUpdates)) {
            return $this->response->setJSON(['success' => false, 'message' => '업데이트할 데이터가 없습니다.']);
        }
        
        // JSON 문자열인 경우 파싱
        if (is_string($statusUpdates)) {
            $statusUpdates = json_decode($statusUpdates, true);
        }
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($statusUpdates as $update) {
            $serviceId = $update['service_id'] ?? null;
            $isActive = $update['is_active'] ?? 0;
            
            if ($serviceId) {
                $result = $this->serviceTypeModel->update($serviceId, ['is_active' => $isActive ? 1 : 0]);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
        }
        
        if ($successCount > 0) {
            return $this->response->setJSON([
                'success' => true, 
                'message' => "{$successCount}개의 서비스 상태가 업데이트되었습니다." . ($failCount > 0 ? " ({$failCount}개 실패)" : "")
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '상태 업데이트에 실패했습니다.']);
        }
    }

    /**
     * 모든 서비스 비활성화
     */
    public function deactivateAllServices()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }
        
        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }
        
        $result = $this->serviceTypeModel->deactivateAll();
        
        if ($result !== false) {
            return $this->response->setJSON(['success' => true, 'message' => '모든 서비스가 비활성화되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '비활성화에 실패했습니다.']);
        }
    }

    /**
     * 서비스 타입 순서 업데이트 (드래그 앤 드롭)
     */
    public function updateServiceSortOrder()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }
        
        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }
        
        $sortUpdates = $this->request->getPost('sort_updates');
        
        if (empty($sortUpdates)) {
            return $this->response->setJSON(['success' => false, 'message' => '업데이트할 데이터가 없습니다.']);
        }
        
        // JSON 문자열인 경우 파싱
        if (is_string($sortUpdates)) {
            $sortUpdates = json_decode($sortUpdates, true);
        }
        
        // ServiceTypeModel의 일괄 순서 업데이트 메서드 호출
        $result = $this->serviceTypeModel->batchUpdateSortOrder($sortUpdates);
        
        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => '순서가 업데이트되었습니다.'
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '순서 업데이트에 실패했습니다.']);
        }
    }

    /**
     * 서비스 코드 생성 (한글 이름을 영문 코드로 변환)
     */
    private function generateServiceCode($serviceName)
    {
        // 간단한 한글-영문 매핑 (실제로는 더 정교한 변환이 필요할 수 있음)
        $mapping = [
            '퀵' => 'quick',
            '오토바이' => 'motorcycle',
            '차량' => 'vehicle',
            '플렉스' => 'flex',
            '이사' => 'moving',
            '해외' => 'international',
            '특송' => 'express',
            '택배' => 'parcel',
            '방문' => 'visit',
            '당일' => 'same-day',
            '편의점' => 'convenience',
            '행낭' => 'bag',
            '우편' => 'postal',
            '일반' => 'general',
            '문서' => 'document',
            '심부름' => 'errand',
            '세무' => 'tax',
            '생활' => 'life',
            '사다주기' => 'buy',
            '택시' => 'taxi',
            '대리운전' => 'driver',
            '화환' => 'wreath',
            '숙박' => 'accommodation',
            '문구' => 'stationery',
            '연계' => 'linked',
            '배송' => 'delivery',
            '고속버스' => 'bus',
            '공항' => 'airport',
            '해운' => 'shipping',
            '메일룸' => 'mailroom',
            '서비스' => 'service'
        ];
        
        $serviceCode = strtolower($serviceName);
        
        // 한글 부분을 영문으로 변환 시도
        foreach ($mapping as $korean => $english) {
            $serviceCode = str_replace($korean, $english, $serviceCode);
        }
        
        // 영문자와 숫자만 남기고 나머지는 제거
        $serviceCode = preg_replace('/[^a-z0-9\-]/', '-', $serviceCode);
        $serviceCode = preg_replace('/-+/', '-', $serviceCode);
        $serviceCode = trim($serviceCode, '-');
        
        // 빈 문자열이면 기본값 사용
        if (empty($serviceCode)) {
            $serviceCode = 'service-' . time();
        }
        
        return $serviceCode;
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
