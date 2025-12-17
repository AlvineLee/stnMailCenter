<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\ServiceTypeModel;
use App\Models\UserServicePermissionModel;
use App\Models\InsungCcListModel;
use App\Models\CcServicePermissionModel;
use App\Models\CompanyServicePermissionModel;
use App\Models\InsungCompanyListModel;

class Admin extends BaseController
{
    protected $adminModel;
    protected $serviceTypeModel;
    protected $userServicePermissionModel;
    protected $insungCcListModel;
    protected $ccServicePermissionModel;
    protected $companyServicePermissionModel;
    protected $insungCompanyListModel;
    
    public function __construct()
    {
        $this->adminModel = new AdminModel();
        $this->serviceTypeModel = new ServiceTypeModel();
        $this->userServicePermissionModel = new UserServicePermissionModel();
        $this->insungCcListModel = new InsungCcListModel();
        $this->ccServicePermissionModel = new CcServicePermissionModel();
        $this->companyServicePermissionModel = new CompanyServicePermissionModel();
        $this->insungCompanyListModel = new InsungCompanyListModel();
    }
    
    public function orderType()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
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
        
        // 콜센터 목록 조회 (daumdata 로그인인 경우)
        $ccList = [];
        $companyList = [];
        $selectedCcCode = $this->request->getGet('cc_code');
        $selectedCompCode = $this->request->getGet('comp_code');
        
        if ($loginType === 'daumdata' && $userType == '1') {
            $ccList = $this->insungCcListModel->getAllCcList();
            
            // 선택된 콜센터가 있으면 해당 콜센터의 거래처 목록 조회
            if ($selectedCcCode) {
                $companyList = $this->insungCompanyListModel->getAllCompanyListWithCc($selectedCcCode);
                $companyList = $companyList['companies'] ?? [];
            }
            
            // 권한 조회 우선순위: 거래처 > 콜센터 > 마스터
            $permissionMap = [];
            $permissionSource = 'master'; // 'master', 'cc', 'company'
            
            // 거래처가 선택된 경우: 거래처 권한 우선 조회
            if ($selectedCompCode && $selectedCcCode) {
                $companyPermissions = $this->companyServicePermissionModel->getCompanyServicePermissions($selectedCompCode);
                if (!empty($companyPermissions)) {
                    // 거래처 권한이 있으면 거래처 권한 사용
                    foreach ($companyPermissions as $permission) {
                        $permissionMap[$permission['service_type_id']] = (bool)$permission['is_enabled'];
                    }
                    $permissionSource = 'company';
                } else {
                    // 거래처 권한이 없으면 콜센터 권한 상속
                    $ccPermissions = $this->ccServicePermissionModel->getCcServicePermissions($selectedCcCode);
                    if (!empty($ccPermissions)) {
                        foreach ($ccPermissions as $permission) {
                            $permissionMap[$permission['service_type_id']] = (bool)$permission['is_enabled'];
                        }
                        $permissionSource = 'cc';
                    }
                }
            } elseif ($selectedCcCode) {
                // 거래처가 선택되지 않고 콜센터만 선택된 경우: 콜센터 권한 사용
                $ccPermissions = $this->ccServicePermissionModel->getCcServicePermissions($selectedCcCode);
                if (!empty($ccPermissions)) {
                    foreach ($ccPermissions as $permission) {
                        $permissionMap[$permission['service_type_id']] = (bool)$permission['is_enabled'];
                    }
                    $permissionSource = 'cc';
                }
            }
            
            // 서비스 타입에 권한 정보 추가
            foreach ($serviceTypesGrouped as $category => &$services) {
                foreach ($services as &$service) {
                    // 마스터 상태 저장 (뷰에서 표시용)
                    $service['master_is_active'] = isset($service['is_active']) ? (bool)$service['is_active'] : false;
                    
                    // 마스터가 비활성화되어 있으면 무조건 false
                    if (isset($service['is_active']) && $service['is_active'] == 0) {
                        $service['is_enabled'] = false;
                    } else {
                        // 마스터가 활성화되어 있을 때 권한 확인
                        if ($permissionSource === 'company' || $permissionSource === 'cc') {
                            // 거래처 또는 콜센터 권한이 있으면 해당 권한 사용
                            $service['is_enabled'] = isset($permissionMap[$service['id']]) ? $permissionMap[$service['id']] : false;
                        } else {
                            // 마스터 설정 사용 (기본값)
                            $service['is_enabled'] = isset($service['is_active']) ? (bool)$service['is_active'] : false;
                        }
                    }
                }
            }
        }
        
        $data = [
            'title' => '오더유형설정',
            'content_header' => [
                'title' => '오더유형설정',
                'description' => '주문 유형을 설정하고 관리할 수 있습니다.'
            ],
            'service_types_grouped' => $serviceTypesGrouped,
            'stats' => $stats,
            'categories' => $categories,
            'cc_list' => $ccList,
            'company_list' => $companyList,
            'selected_cc_code' => $selectedCcCode,
            'selected_comp_code' => $selectedCompCode,
            'login_type' => $loginType,
            'user_type' => $userType
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
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
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
        
        // 외부 링크 정보
        $isExternalLink = $this->request->getPost('is_external_link') === 'on' || $this->request->getPost('is_external_link') === '1';
        $externalUrl = $this->request->getPost('external_url');
        
        // 외부 링크가 활성화되었는데 URL이 없으면 에러
        if ($isExternalLink && empty($externalUrl)) {
            return $this->response->setJSON(['success' => false, 'message' => '외부 링크 서비스인 경우 URL은 필수입니다.']);
        }
        
        // 외부 링크가 아닌 경우 URL 초기화
        if (!$isExternalLink) {
            $externalUrl = null;
        }
        
        // 서비스 타입 생성
        $insertData = [
            'service_code' => $serviceCode,
            'service_name' => $serviceName,
            'service_category' => $serviceCategory,
            'is_active' => 1,
            'sort_order' => 0,
            'is_external_link' => $isExternalLink ? 1 : 0,
            'external_url' => $externalUrl
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
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
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
        
        // 외부 링크 정보
        $isExternalLink = $this->request->getPost('is_external_link') === 'on' || $this->request->getPost('is_external_link') === '1';
        $externalUrl = $this->request->getPost('external_url');
        
        // 외부 링크가 활성화되었는데 URL이 없으면 에러
        if ($isExternalLink && empty($externalUrl)) {
            return $this->response->setJSON(['success' => false, 'message' => '외부 링크 서비스인 경우 URL은 필수입니다.']);
        }
        
        // 외부 링크가 아닌 경우 URL 초기화
        if (!$isExternalLink) {
            $externalUrl = null;
        }
        
        // 업데이트 데이터
        $updateData = [
            'service_name' => $serviceName,
            'is_external_link' => $isExternalLink ? 1 : 0,
            'external_url' => $externalUrl
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
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }
        
        $ccCode = $this->request->getPost('cc_code');
        $compCode = $this->request->getPost('comp_code');
        $statusUpdates = $this->request->getPost('status_updates');
        
        // 디버깅 로그
        log_message('debug', 'batchUpdateServiceStatus - ccCode: ' . ($ccCode ?? 'null'));
        log_message('debug', 'batchUpdateServiceStatus - compCode: ' . ($compCode ?? 'null'));
        log_message('debug', 'batchUpdateServiceStatus - statusUpdates (raw): ' . print_r($statusUpdates, true));
        
        if (empty($statusUpdates)) {
            return $this->response->setJSON(['success' => false, 'message' => '업데이트할 데이터가 없습니다.']);
        }
        
        // JSON 문자열인 경우 파싱
        if (is_string($statusUpdates)) {
            $statusUpdates = json_decode($statusUpdates, true);
        }
        
        log_message('debug', 'batchUpdateServiceStatus - statusUpdates (parsed): ' . print_r($statusUpdates, true));
        
        // daumdata 로그인 user_type=1인 경우
        if ($loginType === 'daumdata' && $userType == '1') {
            // 거래처가 선택된 경우: 거래처 권한 저장
            if ($compCode && $ccCode) {
                $permissions = [];
                foreach ($statusUpdates as $update) {
                    $serviceId = $update['service_id'] ?? null;
                    $isActive = $update['is_active'] ?? 0;
                    
                    if ($serviceId) {
                        $permissions[] = [
                            'service_type_id' => $serviceId,
                            'is_enabled' => $isActive ? 1 : 0
                        ];
                    }
                }
                
                $result = $this->companyServicePermissionModel->batchUpdateCompanyServicePermissions($compCode, $permissions);
                
                if ($result) {
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => '거래처별 서비스 권한이 저장되었습니다.'
                    ]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => '거래처별 서비스 권한 저장에 실패했습니다.']);
                }
            }
            // 개별 콜센터 선택인 경우
            elseif ($ccCode) {
                // 해당 콜센터의 서비스 권한만 저장
                $permissions = [];
                foreach ($statusUpdates as $update) {
                    $serviceId = $update['service_id'] ?? null;
                    $isActive = $update['is_active'] ?? 0;
                    
                    if ($serviceId) {
                        $permissions[] = [
                            'service_type_id' => $serviceId,
                            'is_enabled' => $isActive ? 1 : 0
                        ];
                    }
                }
                
                $result = $this->ccServicePermissionModel->batchUpdateCcServicePermissions($ccCode, $permissions);
                
                if ($result) {
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => '콜센터별 서비스 권한이 저장되었습니다.'
                    ]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => '콜센터별 서비스 권한 저장에 실패했습니다.']);
                }
            } else {
                // 전체 (마스터 설정)인 경우: 마스터 업데이트 + 모든 콜센터에 동일하게 적용
                $successCount = 0;
                $failCount = 0;
                
                // 1. 마스터 서비스 타입 상태 업데이트
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
                
                // 2. 모든 콜센터에 동일하게 적용
                $permissions = [];
                foreach ($statusUpdates as $update) {
                    $serviceId = $update['service_id'] ?? null;
                    $isActive = $update['is_active'] ?? 0;
                    
                    if ($serviceId) {
                        $permissions[] = [
                            'service_type_id' => $serviceId,
                            'is_enabled' => $isActive ? 1 : 0
                        ];
                    }
                }
                
                $allCcResult = $this->ccServicePermissionModel->batchUpdateAllCcServicePermissions($permissions);
                
                if ($successCount > 0) {
                    $message = "{$successCount}개의 서비스 상태가 업데이트되었습니다.";
                    if ($allCcResult) {
                        $message .= " 모든 콜센터에 동일하게 적용되었습니다.";
                    } else {
                        $message .= " (콜센터 적용 중 일부 실패)";
                    }
                    if ($failCount > 0) {
                        $message .= " ({$failCount}개 실패)";
                    }
                    
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => $message
                    ]);
                } else {
                    return $this->response->setJSON(['success' => false, 'message' => '상태 업데이트에 실패했습니다.']);
                }
            }
        }
        
        // STN 로그인인 경우: 마스터 서비스 타입 상태만 업데이트
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
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
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
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
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

    /**
     * 요금 설정 관리
     */
    public function pricing()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        $payInfoModel = new \App\Models\PayInfoModel();
        
        // 기존 요금 정보 조회 (p_comp_gbn = 'K')
        $payInfoList = $payInfoModel->getPayInfoByCompGbn('K');
        
        // 거리 기준점 추출 (슬라이더용)
        $distancePoints = [0]; // 시작점
        foreach ($payInfoList as $info) {
            if (!in_array($info['p_start_km'], $distancePoints)) {
                $distancePoints[] = $info['p_start_km'];
            }
            if (!in_array($info['p_dest_km'], $distancePoints)) {
                $distancePoints[] = $info['p_dest_km'];
            }
        }
        sort($distancePoints);
        
        $data = [
            'title' => '요금설정',
            'content_header' => [
                'title' => '요금설정',
                'description' => '거리별 차량 요금을 설정하고 관리할 수 있습니다.'
            ],
            'pay_info_list' => $payInfoList,
            'distance_points' => $distancePoints
        ];

        return view('admin/pricing', $data);
    }

    /**
     * 요금 설정 저장 (AJAX)
     */
    public function savePricing()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        $compGbn = $this->request->getPost('comp_gbn') ?? 'K';
        $segments = $this->request->getPost('segments');
        
        // JSON 문자열인 경우 파싱
        if (is_string($segments)) {
            $segments = json_decode($segments, true);
        }
        
        if (empty($segments) || !is_array($segments)) {
            return $this->response->setJSON(['success' => false, 'message' => '저장할 데이터가 없습니다.']);
        }

        $payInfoModel = new \App\Models\PayInfoModel();
        $result = $payInfoModel->savePayInfoSegments($compGbn, $segments);
        
        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => '요금 설정이 저장되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '요금 설정 저장에 실패했습니다.']);
        }
    }

    /**
     * 전체내역조회
     */
    public function orderList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        // API 정보 조회
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        
        // 세션에 없으면 DB에서 조회
        if (empty($mCode) || empty($ccCode) || empty($token)) {
            $insungApiListModel = new \App\Models\InsungApiListModel();
            if ($loginType === 'daumdata' && $userType == '1') {
                // daumdata 로그인: 세션의 cc_code로 조회
                $ccCodeFromSession = session()->get('cc_code');
                if ($ccCodeFromSession) {
                    $apiInfo = $insungApiListModel->getApiInfoByCode($ccCodeFromSession);
                    if ($apiInfo) {
                        $mCode = $apiInfo['mcode'] ?? '';
                        $ccCode = $apiInfo['cccode'] ?? '';
                        $token = $apiInfo['token'] ?? '';
                        $apiIdx = $apiInfo['idx'] ?? null;
                    }
                }
            } else {
                // STN 로그인: 기본값 사용
                $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode('4540', '7829');
                if ($apiInfo) {
                    $mCode = $apiInfo['mcode'] ?? '4540';
                    $ccCode = $apiInfo['cccode'] ?? '7829';
                    $token = $apiInfo['token'] ?? '';
                    $apiIdx = $apiInfo['idx'] ?? null;
                }
            }
        }

        // 검색 파라미터
        $selCompCode = $this->request->getGet('sel_comp_code') ?? '1';
        $state = $this->request->getGet('state') ?? '';
        $fromDate = $this->request->getGet('from_date') ?? date('Y-m-d');
        $toDate = $this->request->getGet('to_date') ?? date('Y-m-d');

        // 거래처 목록 조회 (API)
        $companyList = [];
        if ($mCode && $ccCode && $token) {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // 콜센터 정보 조회 (cc_code로)
            $ccCodeForSearch = $ccCode;
            if ($loginType === 'daumdata' && $userType == '1') {
                $ccCodeForSearch = session()->get('cc_code') ?? $ccCode;
            }
            
            //$compNamePrefix = $ccCodeForSearch . '_';
            $compNamePrefix = '';
            $companyListResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', $compNamePrefix, 1, 999999, $apiIdx);
            
            if ($companyListResult) {
                // 응답 구조 파싱 (getCompanyList는 객체/배열을 직접 반환)
                $companyData = $companyListResult;
                
                // 응답 코드 확인
                $code = '';
                if (is_object($companyData) && isset($companyData->Result)) {
                    $resultArray = is_array($companyData->Result) ? $companyData->Result : [$companyData->Result];
                    if (isset($resultArray[0]->result_info[0]->code)) {
                        $code = $resultArray[0]->result_info[0]->code;
                    } elseif (isset($resultArray[0]->code)) {
                        $code = $resultArray[0]->code;
                    }
                } elseif (is_array($companyData) && isset($companyData[0])) {
                    if (is_object($companyData[0]) && isset($companyData[0]->code)) {
                        $code = $companyData[0]->code;
                    } elseif (is_array($companyData[0]) && isset($companyData[0]['code'])) {
                        $code = $companyData[0]['code'];
                    }
                }
                
                // 성공 코드 확인 (1000)
                if ($code === '1000') {
                    // 응답 구조 파싱
                    if (is_object($companyData) && isset($companyData->Result)) {
                        $resultArray = is_array($companyData->Result) ? $companyData->Result : [$companyData->Result];
                        if (isset($resultArray[2]->items[0]->item)) {
                            $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
                            foreach ($items as $item) {
                                $companyList[] = [
                                    'comp_no' => $item->comp_no ?? '',
                                    'corp_name' => $item->corp_name ?? '',
                                    'owner' => $item->owner ?? '',
                                    'tel_no' => $item->tel_no ?? '',
                                    'cc_code' => $ccCodeForSearch ?? $ccCode ?? ''
                                ];
                            }
                        }
                    } elseif (is_array($companyData) && isset($companyData[2])) {
                        // 배열 형태 응답 처리
                        if (isset($companyData[2]->items[0]->item)) {
                            $items = is_array($companyData[2]->items[0]->item) ? $companyData[2]->items[0]->item : [$companyData[2]->items[0]->item];
                            foreach ($items as $item) {
                                $companyList[] = [
                                    'comp_no' => $item->comp_no ?? '',
                                    'corp_name' => $item->corp_name ?? '',
                                    'owner' => $item->owner ?? '',
                                    'tel_no' => $item->tel_no ?? '',
                                    'cc_code' => $ccCodeForSearch ?? $ccCode ?? ''
                                ];
                            }
                        }
                    }
                }
            }
        }

        $data = [
            'title' => '전체내역조회',
            'content_header' => [
                'title' => '전체내역조회',
                'description' => '전체 주문 내역을 조회할 수 있습니다.'
            ],
            'company_list' => $companyList,
            'sel_comp_code' => $selCompCode,
            'state' => $state,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'm_code' => $mCode,
            'cc_code' => $ccCode,
            'token' => $token,
            'api_idx' => $apiIdx
        ];

        return view('admin/order-list', $data);
    }

    /**
     * 거래처관리
     */
    public function companyList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        // API 정보 조회
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        
        // 세션에 없으면 DB에서 조회
        if (empty($mCode) || empty($ccCode) || empty($token)) {
            $insungApiListModel = new \App\Models\InsungApiListModel();
            if ($loginType === 'daumdata' && $userType == '1') {
                // daumdata 로그인: 세션의 cc_code로 조회
                $ccCodeFromSession = session()->get('cc_code');
                if ($ccCodeFromSession) {
                    $apiInfo = $insungApiListModel->getApiInfoByCode($ccCodeFromSession);
                    if ($apiInfo) {
                        $mCode = $apiInfo['mcode'] ?? '';
                        $ccCode = $apiInfo['cccode'] ?? '';
                        $token = $apiInfo['token'] ?? '';
                        $apiIdx = $apiInfo['idx'] ?? null;
                    }
                }
            } else {
                // STN 로그인: 기본값 사용
                $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode('4540', '7829');
                if ($apiInfo) {
                    $mCode = $apiInfo['mcode'] ?? '4540';
                    $ccCode = $apiInfo['cccode'] ?? '7829';
                    $token = $apiInfo['token'] ?? '';
                    $apiIdx = $apiInfo['idx'] ?? null;
                }
            }
        }

        // 검색 파라미터
        $searchCompName = $this->request->getGet('search_compname') ?? '';

        // 콜센터 코드로 거래처명 prefix 설정 (사용하지 않음)
        // $ccCodeForSearch = $ccCode;
        // if ($loginType === 'daumdata' && $userType == '1') {
        //     $ccCodeForSearch = session()->get('cc_code') ?? $ccCode;
        // }
        
        // $compNamePrefix = $ccCodeForSearch . '_';
        // comp_name에 prefix를 붙이지 않음
        // if (!empty($searchCompName)) {
        //     if ($searchCompName != $compNamePrefix) {
        //         $searchCompName = str_replace($compNamePrefix, '', $searchCompName);
        //         $searchCompName = $compNamePrefix . $searchCompName;
        //     } else {
        //         $searchCompName = $searchCompName . '_';
        //     }
        // } else {
        //     $searchCompName = $compNamePrefix;
        // }

        // 거래처 목록 조회 (API) - 모든 페이지 순회
        $companyList = [];
        if ($mCode && $ccCode && $token) {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // 첫 번째 페이지 호출하여 total_page 확인 (limit을 크게 설정하여 모든 데이터 가져오기 시도)
            $firstPageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', $searchCompName, 1, 999999, $apiIdx);
            
            if ($firstPageResult) {
                $companyData = $firstPageResult;
                
                // 응답 코드 확인
                $code = '';
                $totalPage = 1;
                $totalRecord = 0;
                
                // 디버깅: 응답 구조 로그 (Result[1] 구조 확인)
                if (is_object($companyData) && isset($companyData->Result[1])) {
                    log_message('debug', "Admin::companyList - Result[1] 구조: " . json_encode($companyData->Result[1], JSON_UNESCAPED_UNICODE));
                } elseif (is_array($companyData) && isset($companyData[1])) {
                    log_message('debug', "Admin::companyList - 배열[1] 구조: " . json_encode($companyData[1], JSON_UNESCAPED_UNICODE));
                }
                
                if (is_object($companyData) && isset($companyData->Result)) {
                    // Result[0]에서 코드 확인
                    if (isset($companyData->Result[0]->result_info[0]->code)) {
                        $code = $companyData->Result[0]->result_info[0]->code;
                    } elseif (isset($companyData->Result[0]->code)) {
                        $code = $companyData->Result[0]->code;
                    }
                    // Result[1]에서 total_page 확인 (page_info 배열 안에 있음)
                    if (isset($companyData->Result[1])) {
                        $pageInfoData = $companyData->Result[1];
                        // page_info 배열 확인
                        if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                            $pageInfo = $pageInfoData->page_info[0];
                            if (isset($pageInfo->total_page)) {
                                $totalPage = (int)$pageInfo->total_page;
                            }
                            if (isset($pageInfo->total_record)) {
                                $totalRecord = (int)$pageInfo->total_record;
                            }
                        } elseif (isset($pageInfoData->total_page)) {
                            // page_info 배열이 아닌 경우 직접 접근
                            $totalPage = (int)$pageInfoData->total_page;
                            if (isset($pageInfoData->total_record)) {
                                $totalRecord = (int)$pageInfoData->total_record;
                            }
                        }
                    }
                } elseif (is_array($companyData)) {
                    // 배열 형태 응답
                    if (isset($companyData[0])) {
                        if (is_object($companyData[0])) {
                            if (isset($companyData[0]->result_info[0]->code)) {
                                $code = $companyData[0]->result_info[0]->code;
                            } elseif (isset($companyData[0]->code)) {
                                $code = $companyData[0]->code;
                            }
                        } elseif (is_array($companyData[0])) {
                            if (isset($companyData[0]['code'])) {
                                $code = $companyData[0]['code'];
                            }
                        }
                    }
                    // 배열 인덱스 [1]에서 total_page 확인 (page_info 배열 안에 있음)
                    if (isset($companyData[1])) {
                        $pageInfoData = is_object($companyData[1]) ? $companyData[1] : (object)$companyData[1];
                        // page_info 배열 확인
                        if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                            $pageInfo = $pageInfoData->page_info[0];
                            if (isset($pageInfo->total_page)) {
                                $totalPage = (int)$pageInfo->total_page;
                            }
                            if (isset($pageInfo->total_record)) {
                                $totalRecord = (int)$pageInfo->total_record;
                            }
                        } elseif (isset($pageInfoData->total_page)) {
                            // page_info 배열이 아닌 경우 직접 접근
                            $totalPage = (int)$pageInfoData->total_page;
                            if (isset($pageInfoData->total_record)) {
                                $totalRecord = (int)$pageInfoData->total_record;
                            }
                        }
                    }
                }
                
                log_message('info', "Admin::companyList - totalPage={$totalPage}, totalRecord={$totalRecord}, code={$code}");
                
                // 성공 코드 확인 (1000)
                if ($code === '1000') {
                    // totalRecord가 0이거나 totalPage가 1이면 첫 페이지 결과만 사용
                    // 그 외에는 모든 페이지 순회
                    if ($totalRecord > 0 && $totalPage > 1) {
                        // 모든 페이지 순회
                        for ($page = 1; $page <= $totalPage; $page++) {
                            $pageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', $searchCompName, $page, 100, $apiIdx);
                        
                        if ($pageResult) {
                            $pageData = $pageResult;
                            
                            // 응답 구조 파싱
                            if (is_object($pageData) && isset($pageData->Result)) {
                                $resultArray = is_array($pageData->Result) ? $pageData->Result : [$pageData->Result];
                                if (isset($resultArray[2]->items[0]->item)) {
                                    $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
                                    foreach ($items as $item) {
                                        $companyList[] = [
                                            'comp_no' => $item->comp_no ?? '',
                                            'corp_name' => $item->corp_name ?? '',
                                            'owner' => $item->owner ?? '',
                                            'tel_no' => $item->tel_no ?? '',
                                            'address' => $item->adddress ?? ''
                                        ];
                                    }
                                }
                            } elseif (is_array($pageData) && isset($pageData[2])) {
                                // 배열 형태 응답 처리
                                if (isset($pageData[2]->items[0]->item)) {
                                    $items = is_array($pageData[2]->items[0]->item) ? $pageData[2]->items[0]->item : [$pageData[2]->items[0]->item];
                                    foreach ($items as $item) {
                                        $companyList[] = [
                                            'comp_no' => $item->comp_no ?? '',
                                            'corp_name' => $item->corp_name ?? '',
                                            'owner' => $item->owner ?? '',
                                            'tel_no' => $item->tel_no ?? '',
                                            'address' => $item->adddress ?? ''
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    } else {
                        // totalRecord가 0이거나 totalPage가 1이면 첫 페이지 결과만 사용
                        // 첫 페이지 결과 파싱
                        if (is_object($companyData) && isset($companyData->Result)) {
                            $resultArray = is_array($companyData->Result) ? $companyData->Result : [$companyData->Result];
                            if (isset($resultArray[2]->items[0]->item)) {
                                $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
                                foreach ($items as $item) {
                                    $companyList[] = [
                                        'comp_no' => $item->comp_no ?? '',
                                        'corp_name' => $item->corp_name ?? '',
                                        'owner' => $item->owner ?? '',
                                        'tel_no' => $item->tel_no ?? '',
                                        'address' => $item->adddress ?? ''
                                    ];
                                }
                            }
                        } elseif (is_array($companyData) && isset($companyData[2])) {
                            // 배열 형태 응답 처리
                            if (isset($companyData[2]->items[0]->item)) {
                                $items = is_array($companyData[2]->items[0]->item) ? $companyData[2]->items[0]->item : [$companyData[2]->items[0]->item];
                                foreach ($items as $item) {
                                    $companyList[] = [
                                        'comp_no' => $item->comp_no ?? '',
                                        'corp_name' => $item->corp_name ?? '',
                                        'owner' => $item->owner ?? '',
                                        'tel_no' => $item->tel_no ?? '',
                                        'address' => $item->adddress ?? ''
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        $data = [
            'title' => '거래처관리',
            'content_header' => [
                'title' => '거래처관리',
                'description' => '거래처 목록을 조회하고 관리할 수 있습니다.'
            ],
            'company_list' => $companyList,
            'search_compname' => $searchCompName,
            'm_code' => $mCode,
            'cc_code' => $ccCode,
            'token' => $token,
            'api_idx' => $apiIdx
        ];

        return view('admin/company-list', $data);
    }

    /**
     * 전체내역조회 AJAX 엔드포인트
     */
    public function orderListAjax()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }
        
        // 권한 체크
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        // API 정보 조회
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        $userId = session()->get('user_id'); // 로그인한 사용자 ID
        
        if (empty($mCode) || empty($ccCode) || empty($token)) {
            $insungApiListModel = new \App\Models\InsungApiListModel();
            if ($loginType === 'daumdata' && $userType == '1') {
                $ccCodeFromSession = session()->get('cc_code');
                if ($ccCodeFromSession) {
                    $apiInfo = $insungApiListModel->getApiInfoByCode($ccCodeFromSession);
                    if ($apiInfo) {
                        $mCode = $apiInfo['mcode'] ?? '';
                        $ccCode = $apiInfo['cccode'] ?? '';
                        $token = $apiInfo['token'] ?? '';
                        $apiIdx = $apiInfo['idx'] ?? null;
                    }
                }
            } else {
                $apiInfo = $insungApiListModel->getApiInfoByMcodeCccode('4540', '7829');
                if ($apiInfo) {
                    $mCode = $apiInfo['mcode'] ?? '4540';
                    $ccCode = $apiInfo['cccode'] ?? '7829';
                    $token = $apiInfo['token'] ?? '';
                    $apiIdx = $apiInfo['idx'] ?? null;
                }
            }
        }

        // 검색 파라미터
        $selCompCode = $this->request->getPost('sel_comp_code') ?? '1';
        $state = $this->request->getPost('state') ?? '';
        $fromDate = $this->request->getPost('from_date') ?? date('Y-m-d');
        $toDate = $this->request->getPost('to_date') ?? date('Y-m-d');
        $page = (int)($this->request->getPost('page') ?? 1);

        if (!$mCode || !$ccCode || !$token) {
            return $this->response->setJSON(['success' => false, 'message' => 'API 정보가 없습니다.']);
        }

        try {
            log_message('info', "Admin::orderListAjax - 시작: sel_comp_code={$selCompCode}, state={$state}, from_date={$fromDate}, to_date={$toDate}, page={$page}");
            log_message('info', "Admin::orderListAjax - API 정보: mCode={$mCode}, ccCode={$ccCode}, apiIdx=" . ($apiIdx ?? 'null'));
            
            $insungApiService = new \App\Libraries\InsungApiService();
            $orders = [];
            $totalPage = 1;
            $totalRecord = 0;

            // 선택된 거래처가 있으면 해당 거래처만 조회 (API 페이지네이션 직접 활용)
            if ($selCompCode != '1') {
                log_message('info', "Admin::orderListAjax - 선택된 거래처: {$selCompCode}, API 페이지네이션 직접 활용");
                
                try {
                    // 거래처명 조회
                    $compName = '';
                    $companyInfoResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, $selCompCode, '', 1, 1, $apiIdx);
                    if ($companyInfoResult) {
                        $companyInfoData = $companyInfoResult;
                        if (is_object($companyInfoData) && isset($companyInfoData->Result)) {
                            $resultArray = is_array($companyInfoData->Result) ? $companyInfoData->Result : [$companyInfoData->Result];
                            if (isset($resultArray[2]->items[0]->item[0]->corp_name)) {
                                $compName = $resultArray[2]->items[0]->item[0]->corp_name;
                            }
                        } elseif (is_array($companyInfoData) && isset($companyInfoData[2])) {
                            if (isset($companyInfoData[2]->items[0]->item[0]->corp_name)) {
                                $compName = $companyInfoData[2]->items[0]->item[0]->corp_name;
                            }
                        }
                    }
                    
                    // 주문 목록 조회 (API 페이지네이션 직접 활용: limit=15, page=요청한 페이지)
                    $compNo = $selCompCode;
                    $orderListResult = $insungApiService->getOrderList($mCode, $ccCode, $token, '', $fromDate, $toDate, $state, null, null, $compNo, 15, $page, $apiIdx);
                    
                    if ($orderListResult && isset($orderListResult['success']) && $orderListResult['success']) {
                        $orderData = $orderListResult['data'] ?? null;
                        
                        if ($orderData && is_array($orderData) && count($orderData) > 1) {
                            // 응답 코드 확인
                            $orderCode = '';
                            $orderMsg = '';
                            if (isset($orderData[0])) {
                                if (is_object($orderData[0])) {
                                    $orderCode = $orderData[0]->code ?? '';
                                    $orderMsg = $orderData[0]->msg ?? '';
                                } elseif (is_array($orderData[0])) {
                                    $orderCode = $orderData[0]['code'] ?? '';
                                    $orderMsg = $orderData[0]['msg'] ?? '';
                                }
                            }
                            
                            // NO-DATA 응답인 경우
                            if (strpos($orderMsg, 'NO-DATA') !== false) {
                                return $this->response->setJSON([
                                    'success' => true,
                                    'data' => [
                                        'orders' => [],
                                        'total_record' => 0,
                                        'total_page' => 1,
                                        'current_page' => $page,
                                        'items_per_page' => 15
                                    ]
                                ]);
                            }
                            
                            // 페이지 정보 추출
                            $pageInfo = $orderData[1] ?? null;
                            if ($pageInfo) {
                                if (is_object($pageInfo)) {
                                    $totalRecord = (int)($pageInfo->total_record ?? 0);
                                    $totalPage = (int)($pageInfo->total_page ?? 1);
                                } elseif (is_array($pageInfo)) {
                                    $totalRecord = (int)($pageInfo['total_record'] ?? 0);
                                    $totalPage = (int)($pageInfo['total_page'] ?? 1);
                                }
                            }
                            
                            // 주문 데이터 파싱 (orderData[2]부터)
                            for ($i = 2; $i < count($orderData); $i++) {
                                if (isset($orderData[$i])) {
                                    $order = $orderData[$i];
                                    if (is_object($order) || is_array($order)) {
                                        $orderObj = is_object($order) ? $order : (object)$order;
                                        if (isset($orderObj->serial_number) || isset($orderObj->user_id) || isset($orderObj->order_date)) {
                                            $orders[] = [
                                                'serial_number' => $order->serial_number ?? '',
                                                'comp_name' => $compName,
                                                'user_id' => $order->user_id ?? '',
                                                'order_state' => $order->order_state ?? '',
                                                'order_date' => $order->order_date ?? '',
                                                'pickup_time' => isset($order->pickup_time) ? date('m-d H:i', strtotime($order->pickup_time)) : '',
                                                'complete_time' => $order->complete_time ?? '',
                                                'customer_name' => $order->customer_name ?? '',
                                                'departure_staff' => $order->departure_staff ?? '',
                                                'departure_customer' => $order->departure_customer ?? '',
                                                'departure_dong_name' => $order->departure_dong_name ?? '',
                                                'departure_department' => $order->departure_department ?? '',
                                                'departure_tel' => $order->departure_tel ?? '',
                                                'departure_address' => $order->departure_address ?? '',
                                                'destination_customer' => $order->destination_customer ?? '',
                                                'destination_dong_name' => $order->destination_dong_name ?? '',
                                                'destination_staff' => $order->destination_staff ?? '',
                                                'destination_tel' => $order->destination_tel ?? '',
                                                'destination_address' => $order->destination_address ?? '',
                                                'delivery_type' => $order->delivery_type ?? '',
                                                'delivery_item_text' => $order->delivery_item_text ?? '',
                                                'car_type' => $order->car_type ?? '',
                                                'pay_gbn' => $order->payment_type ?? '',
                                                'basic_cost' => $order->basic_cost ?? '',
                                                'addition_cost' => $order->addition_cost ?? '',
                                                'delivery_cost' => $order->delivery_cost ?? '',
                                                'summary' => $order->summary ?? '',
                                                'rider_info' => $order->rider_info ?? '',
                                                'rider_name' => $order->rider_name ?? ''
                                            ];
                                        }
                                    }
                                }
                            }
                            
                            log_message('info', "Admin::orderListAjax - 완료: 거래처={$selCompCode}, total_record={$totalRecord}, total_page={$totalPage}, 현재 페이지={$page}, 주문 수=" . count($orders));
                            
                            return $this->response->setJSON([
                                'success' => true,
                                'data' => [
                                    'orders' => $orders,
                                    'total_record' => $totalRecord,
                                    'total_page' => $totalPage,
                                    'current_page' => $page,
                                    'items_per_page' => 15
                                ]
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', "Admin::orderListAjax - 선택된 거래처 조회 중 오류: " . $e->getMessage());
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '주문 목록 조회 중 오류가 발생했습니다: ' . $e->getMessage()
                    ])->setStatusCode(500);
                }
            } else {
                // 전체 거래처 조회 - comp_no 없이 주문 목록 API 직접 호출
                log_message('info', "Admin::orderListAjax - 전체 거래처 조회 시작 (comp_no 없이 주문 목록 API 직접 호출)");
                
                try {
                    // 주문 목록 조회 (comp_no 파라미터 없이, API 페이지네이션 직접 활용)
                    $orderListResult = $insungApiService->getOrderList($mCode, $ccCode, $token, '', $fromDate, $toDate, $state, null, null, null, 15, $page, $apiIdx);
                    
                    log_message('info', "Admin::orderListAjax - API 호출 결과: " . (isset($orderListResult['success']) ? ('success=' . ($orderListResult['success'] ? 'true' : 'false')) : 'success 키 없음'));
                    
                    if (!$orderListResult) {
                        log_message('error', "Admin::orderListAjax - API 호출 실패: orderListResult가 null 또는 false");
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => 'API 호출에 실패했습니다.'
                        ])->setStatusCode(500);
                    }
                    
                    if (isset($orderListResult['success']) && !$orderListResult['success']) {
                        $errorMsg = $orderListResult['message'] ?? '알 수 없는 오류';
                        log_message('error', "Admin::orderListAjax - API 호출 실패: {$errorMsg}");
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => $errorMsg
                        ])->setStatusCode(500);
                    }
                    
                    if ($orderListResult && isset($orderListResult['success']) && $orderListResult['success']) {
                        $orderData = $orderListResult['data'] ?? null;
                        
                        if (!$orderData || !is_array($orderData)) {
                            log_message('warning', "Admin::orderListAjax - orderData가 없거나 배열이 아님: " . gettype($orderData));
                            return $this->response->setJSON([
                                'success' => true,
                                'data' => [
                                    'orders' => [],
                                    'total_record' => 0,
                                    'total_page' => 1,
                                    'current_page' => $page,
                                    'items_per_page' => 15
                                ]
                            ]);
                        }
                        
                        if (count($orderData) <= 1) {
                            log_message('info', "Admin::orderListAjax - 주문 데이터 없음 (배열 크기 1 이하)");
                            return $this->response->setJSON([
                                'success' => true,
                                'data' => [
                                    'orders' => [],
                                    'total_record' => 0,
                                    'total_page' => 1,
                                    'current_page' => $page,
                                    'items_per_page' => 15
                                ]
                            ]);
                        }
                        
                        // 응답 코드 확인
                        $orderCode = '';
                        $orderMsg = '';
                        if (isset($orderData[0])) {
                            if (is_object($orderData[0])) {
                                $orderCode = $orderData[0]->code ?? '';
                                $orderMsg = $orderData[0]->msg ?? '';
                            } elseif (is_array($orderData[0])) {
                                $orderCode = $orderData[0]['code'] ?? '';
                                $orderMsg = $orderData[0]['msg'] ?? '';
                            }
                        }
                        
                        log_message('info', "Admin::orderListAjax - 응답 코드: {$orderCode}, 메시지: {$orderMsg}");
                        
                        // NO-DATA 응답인 경우
                        if (strpos($orderMsg, 'NO-DATA') !== false) {
                            log_message('info', "Admin::orderListAjax - 주문 데이터 없음 (NO-DATA)");
                            return $this->response->setJSON([
                                'success' => true,
                                'data' => [
                                    'orders' => [],
                                    'total_record' => 0,
                                    'total_page' => 1,
                                    'current_page' => $page,
                                    'items_per_page' => 15
                                ]
                            ]);
                        }
                        
                        // 페이지 정보 추출
                        $pageInfo = $orderData[1] ?? null;
                        if ($pageInfo) {
                            if (is_object($pageInfo)) {
                                $totalRecord = (int)($pageInfo->total_record ?? 0);
                                $totalPage = (int)($pageInfo->total_page ?? 1);
                            } elseif (is_array($pageInfo)) {
                                $totalRecord = (int)($pageInfo['total_record'] ?? 0);
                                $totalPage = (int)($pageInfo['total_page'] ?? 1);
                            }
                        }
                        
                        // 주문 데이터 파싱 (orderData[2]부터)
                        for ($i = 2; $i < count($orderData); $i++) {
                            if (isset($orderData[$i])) {
                                $order = $orderData[$i];
                                if (is_object($order) || is_array($order)) {
                                    $orderObj = is_object($order) ? $order : (object)$order;
                                    if (isset($orderObj->serial_number) || isset($orderObj->user_id) || isset($orderObj->order_date)) {
                                        $orders[] = [
                                            'serial_number' => $order->serial_number ?? '',
                                            'comp_name' => $order->comp_name ?? '',
                                            'user_id' => $order->user_id ?? '',
                                            'order_state' => $order->order_state ?? '',
                                            'order_date' => $order->order_date ?? '',
                                            'pickup_time' => isset($order->pickup_time) ? date('m-d H:i', strtotime($order->pickup_time)) : '',
                                            'complete_time' => $order->complete_time ?? '',
                                            'customer_name' => $order->customer_name ?? '',
                                            'departure_staff' => $order->departure_staff ?? '',
                                            'departure_customer' => $order->departure_customer ?? '',
                                            'departure_dong_name' => $order->departure_dong_name ?? '',
                                            'departure_department' => $order->departure_department ?? '',
                                            'departure_tel' => $order->departure_tel ?? '',
                                            'departure_address' => $order->departure_address ?? '',
                                            'destination_customer' => $order->destination_customer ?? '',
                                            'destination_dong_name' => $order->destination_dong_name ?? '',
                                            'destination_staff' => $order->destination_staff ?? '',
                                            'destination_tel' => $order->destination_tel ?? '',
                                            'destination_address' => $order->destination_address ?? '',
                                            'delivery_type' => $order->delivery_type ?? '',
                                            'delivery_item_text' => $order->delivery_item_text ?? '',
                                            'car_type' => $order->car_type ?? '',
                                            'pay_gbn' => $order->payment_type ?? '',
                                            'basic_cost' => $order->basic_cost ?? '',
                                            'addition_cost' => $order->addition_cost ?? '',
                                            'delivery_cost' => $order->delivery_cost ?? '',
                                            'summary' => $order->summary ?? '',
                                            'rider_info' => $order->rider_info ?? '',
                                            'rider_name' => $order->rider_name ?? ''
                                        ];
                                    }
                                }
                            }
                        }
                        
                        log_message('info', "Admin::orderListAjax - 완료: 전체 거래처, total_record={$totalRecord}, total_page={$totalPage}, 현재 페이지={$page}, 주문 수=" . count($orders));
                        
                        return $this->response->setJSON([
                            'success' => true,
                            'data' => [
                                'orders' => $orders,
                                'total_record' => $totalRecord,
                                'total_page' => $totalPage,
                                'current_page' => $page,
                                'items_per_page' => 15
                            ]
                        ]);
                    }
                    
                    // 위 조건들을 모두 통과하지 못한 경우
                    log_message('warning', "Admin::orderListAjax - 예상치 못한 응답 구조: " . json_encode($orderListResult, JSON_UNESCAPED_UNICODE));
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '예상치 못한 API 응답 형식입니다.'
                    ])->setStatusCode(500);
                    
                } catch (\Exception $e) {
                    log_message('error', "Admin::orderListAjax - 전체 거래처 조회 중 오류: " . $e->getMessage());
                    log_message('error', "Admin::orderListAjax - 예외 스택: " . $e->getTraceAsString());
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '주문 목록 조회 중 오류가 발생했습니다: ' . $e->getMessage()
                    ])->setStatusCode(500);
                }
                
                // 기존 거래처별 순회 로직 (주석 처리 - comp_no 없이 API 직접 호출로 변경)
                /*
                $compCodeArray = [];
                foreach ($compCodeArray as $compCode) {
                if ($processedCount >= $maxCompanies) {
                    log_message('info', "Admin::orderListAjax - 최대 거래처 수 도달, 중단");
                    break;
                }
                
                try {
                    log_message('info', "Admin::orderListAjax - 거래처 처리 중: compCode={$compCode}");
                    
                    // 거래처의 user_id 조회
                    $customerListResult = $insungApiService->getCustomerAttachedList($mCode, $ccCode, $token, $compCode, '', '', '', '', '', '', '', 1, 500, $apiIdx);
                    log_message('info', "Admin::orderListAjax - 고객 목록 조회 결과: compCode={$compCode}, " . (is_object($customerListResult) || is_array($customerListResult) ? '성공' : '실패'));
                    
                    $searchUserId = '';
                    if ($customerListResult) {
                        // 응답 구조 파싱 (getCustomerAttachedList는 객체/배열을 직접 반환)
                        $customerData = $customerListResult;
                        
                        // 응답 코드 확인
                        $customerCode = '';
                        $customerMsg = '';
                        if (is_array($customerData) && isset($customerData[0])) {
                            if (is_object($customerData[0])) {
                                $customerCode = $customerData[0]->code ?? '';
                                $customerMsg = $customerData[0]->msg ?? '';
                            } elseif (is_array($customerData[0])) {
                                $customerCode = $customerData[0]['code'] ?? '';
                                $customerMsg = $customerData[0]['msg'] ?? '';
                            }
                        }
                        
                        log_message('debug', "Admin::orderListAjax - 고객 목록 응답 코드: {$customerCode}, 메시지: {$customerMsg}");
                        
                        // 응답이 배열인 경우
                        if (is_array($customerData) && count($customerData) > 2) {
                            log_message('debug', "Admin::orderListAjax - 고객 데이터 배열 크기: " . count($customerData));
                            
                            // 첫 번째 고객 데이터 구조 확인
                            if (isset($customerData[2])) {
                                $firstCustomer = $customerData[2];
                                $firstCustomerObj = is_object($firstCustomer) ? $firstCustomer : (object)$firstCustomer;
                                log_message('debug', "Admin::orderListAjax - 첫 번째 고객 데이터 구조: " . json_encode($firstCustomerObj, JSON_UNESCAPED_UNICODE));
                                log_message('debug', "Admin::orderListAjax - 첫 번째 고객 user_id: " . ($firstCustomerObj->user_id ?? '없음') . ", use_state: " . ($firstCustomerObj->use_state ?? '없음'));
                            }
                            
                            // 먼저 use_state == 'Y'인 고객 찾기
                            for ($i = 2; $i < count($customerData); $i++) {
                                if (isset($customerData[$i])) {
                                    $customer = is_object($customerData[$i]) ? $customerData[$i] : (object)$customerData[$i];
                                    
                                    if (isset($customer->user_id) && isset($customer->use_state) && $customer->use_state == 'Y') {
                                        $searchUserId = $customer->user_id;
                                        log_message('info', "Admin::orderListAjax - use_state=Y인 user_id 찾음: {$searchUserId}");
                                        break;
                                    }
                                }
                            }
                            
                            // use_state=Y인 고객이 없으면 첫 번째 user_id 사용
                            if (empty($searchUserId)) {
                                for ($i = 2; $i < count($customerData); $i++) {
                                    if (isset($customerData[$i])) {
                                        $customer = is_object($customerData[$i]) ? $customerData[$i] : (object)$customerData[$i];
                                        if (isset($customer->user_id) && !empty($customer->user_id)) {
                                            $searchUserId = $customer->user_id;
                                            log_message('info', "Admin::orderListAjax - 첫 번째 user_id 사용: {$searchUserId}");
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            if (empty($searchUserId)) {
                                log_message('warning', "Admin::orderListAjax - user_id를 찾을 수 없음: compCode={$compCode}, 고객 데이터는 있지만 user_id가 없음");
                                log_message('debug', "Admin::orderListAjax - 전체 고객 데이터 샘플 (처음 3개): " . json_encode(array_slice($customerData, 0, 3), JSON_UNESCAPED_UNICODE));
                            }
                        } else {
                            log_message('warning', "Admin::orderListAjax - 고객 데이터 배열이 아니거나 크기가 2 이하: " . (is_array($customerData) ? '크기=' . count($customerData) : gettype($customerData)));
                        }
                    }

                    // user_id가 없어도 주문 목록 조회 수행 (comp_no만으로 조회 가능)
                    log_message('info', "Admin::orderListAjax - 주문 목록 조회 시작: compCode={$compCode}, userId=" . ($searchUserId ?: '없음'));
                    try {
                        // 주문 목록 조회 (comp_no 파라미터 전달 - 실제 거래처 코드 사용, user_id는 전달하지 않음)
                        // 모든 주문을 가져온 후 백엔드에서 페이지네이션 처리하므로 limit을 크게 설정
                        $compNo = $compCode; // 실제 거래처 코드 사용
                        log_message('info', "Admin::orderListAjax - getOrderList 호출 직전: compCode={$compCode}, compNo={$compNo}, fromDate={$fromDate}, toDate={$toDate}, page=1 (모든 주문 수집 후 백엔드에서 페이지네이션)");
                        $orderListResult = $insungApiService->getOrderList($mCode, $ccCode, $token, '', $fromDate, $toDate, $state, null, null, $compNo, 1000, 1, $apiIdx);
                            log_message('info', "Admin::orderListAjax - getOrderList 호출 완료: " . (isset($orderListResult['success']) ? ('success=' . ($orderListResult['success'] ? 'true' : 'false')) : 'success 키 없음'));
                            
                            if (!$orderListResult) {
                                log_message('error', "Admin::orderListAjax - getOrderList 반환값이 null 또는 false");
                                continue;
                            }
                            
                            if ($orderListResult && isset($orderListResult['success']) && $orderListResult['success']) {
                                $orderData = $orderListResult['data'] ?? null;
                                log_message('info', "Admin::orderListAjax - 주문 데이터 수신: " . (is_array($orderData) ? '배열, 크기=' . count($orderData) : '배열 아님'));
                                
                                // orderData 배열 크기 확인 및 NO-DATA 처리
                                if ($orderData && is_array($orderData)) {
                                    // 응답 코드 확인
                                    $orderCode = '';
                                    $orderMsg = '';
                                    if (isset($orderData[0])) {
                                        if (is_object($orderData[0])) {
                                            $orderCode = $orderData[0]->code ?? '';
                                            $orderMsg = $orderData[0]->msg ?? '';
                                        } elseif (is_array($orderData[0])) {
                                            $orderCode = $orderData[0]['code'] ?? '';
                                            $orderMsg = $orderData[0]['msg'] ?? '';
                                        }
                                    }
                                    
                                    log_message('debug', "Admin::orderListAjax - 주문 목록 응답 코드: {$orderCode}, 메시지: {$orderMsg}, 배열 크기: " . count($orderData));
                                    
                                    // NO-DATA 응답인 경우 스킵
                                    if (strpos($orderMsg, 'NO-DATA') !== false) {
                                        log_message('info', "Admin::orderListAjax - 주문 데이터 없음 (NO-DATA): compCode={$compCode}, userId={$searchUserId}");
                                        continue;
                                    }
                                    
                                    // 배열 크기가 1 이하이면 데이터 없음
                                    if (count($orderData) <= 1) {
                                        log_message('info', "Admin::orderListAjax - 주문 데이터 없음 (배열 크기 1 이하): compCode={$compCode}, userId={$searchUserId}");
                                        continue;
                                    }
                                    
                                    // 주문 데이터가 있는 경우 파싱
                                    if (count($orderData) > 1) {
                                // total_page 추출
                                $pageInfo = $orderData[1] ?? null;
                                log_message('debug', "Admin::orderListAjax - pageInfo 구조: " . json_encode($pageInfo, JSON_UNESCAPED_UNICODE));
                                
                                if ($pageInfo) {
                                    if (is_object($pageInfo) && isset($pageInfo->total_page)) {
                                        $totalPage = max($totalPage, (int)$pageInfo->total_page);
                                    } elseif (is_array($pageInfo) && isset($pageInfo['total_page'])) {
                                        $totalPage = max($totalPage, (int)$pageInfo['total_page']);
                                    }
                                    
                                    // total_record 확인
                                    $totalRecord = 0;
                                    if (is_object($pageInfo) && isset($pageInfo->total_record)) {
                                        $totalRecord = (int)$pageInfo->total_record;
                                    } elseif (is_array($pageInfo) && isset($pageInfo['total_record'])) {
                                        $totalRecord = (int)$pageInfo['total_record'];
                                    }
                                    log_message('info', "Admin::orderListAjax - total_record={$totalRecord}, total_page={$totalPage}, orderData 배열 크기=" . count($orderData));
                                }
                                
                                // 거래처명 조회
                                $compName = '';
                                $companyInfoResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, $compCode, '', 1, 1, $apiIdx);
                                if ($companyInfoResult) {
                                    $companyInfoData = $companyInfoResult;
                                    if (is_object($companyInfoData) && isset($companyInfoData->Result)) {
                                        $resultArray = is_array($companyInfoData->Result) ? $companyInfoData->Result : [$companyInfoData->Result];
                                        if (isset($resultArray[2]->items[0]->item[0]->corp_name)) {
                                            $compName = $resultArray[2]->items[0]->item[0]->corp_name;
                                        }
                                    } elseif (is_array($companyInfoData) && isset($companyInfoData[2])) {
                                        if (isset($companyInfoData[2]->items[0]->item[0]->corp_name)) {
                                            $compName = $companyInfoData[2]->items[0]->item[0]->corp_name;
                                        }
                                    }
                                }

                                // 주문 데이터 파싱
                                // order_list API 응답 구조: [0]=응답코드, [1]=페이지정보, [2]부터=주문데이터
                                $orderCount = 0;
                                log_message('debug', "Admin::orderListAjax - orderData 배열 구조 확인: count=" . count($orderData));
                                
                                // $orderData[2]부터 주문 데이터가 있는지 확인
                                for ($i = 2; $i < count($orderData); $i++) {
                                    if (isset($orderData[$i])) {
                                        $order = $orderData[$i];
                                        // 주문 객체인지 확인 (serial_number 등이 있는지)
                                        if (is_object($order) || is_array($order)) {
                                            $orderObj = is_object($order) ? $order : (object)$order;
                                            if (isset($orderObj->serial_number) || isset($orderObj->user_id) || isset($orderObj->order_date)) {
                                                $orderCount++;
                                                $orders[] = [
                                                    'serial_number' => $order->serial_number ?? '',
                                                    'comp_name' => $compName,
                                                    'user_id' => $order->user_id ?? '',
                                                    'order_state' => $order->order_state ?? '',
                                                    'order_date' => $order->order_date ?? '',
                                                    'pickup_time' => isset($order->pickup_time) ? date('m-d H:i', strtotime($order->pickup_time)) : '',
                                                    'complete_time' => $order->complete_time ?? '',
                                                    'customer_name' => $order->customer_name ?? '',
                                                    'departure_staff' => $order->departure_staff ?? '',
                                                    'departure_customer' => $order->departure_customer ?? '',
                                                    'departure_dong_name' => $order->departure_dong_name ?? '',
                                                    'departure_department' => $order->departure_department ?? '',
                                                    'departure_tel' => $order->departure_tel ?? '',
                                                    'departure_address' => $order->departure_address ?? '',
                                                    'destination_customer' => $order->destination_customer ?? '',
                                                    'destination_dong_name' => $order->destination_dong_name ?? '',
                                                    'destination_staff' => $order->destination_staff ?? '',
                                                    'destination_tel' => $order->destination_tel ?? '',
                                                    'destination_address' => $order->destination_address ?? '',
                                                    'delivery_type' => $order->delivery_type ?? '',
                                                    'delivery_item_text' => $order->delivery_item_text ?? '',
                                                    'car_type' => $order->car_type ?? '',
                                                    'pay_gbn' => $order->payment_type ?? '',
                                                    'basic_cost' => $order->basic_cost ?? '',
                                                    'addition_cost' => $order->addition_cost ?? '',
                                                    'delivery_cost' => $order->delivery_cost ?? '',
                                                    'summary' => $order->summary ?? '',
                                                    'rider_info' => $order->rider_info ?? '',
                                                    'rider_name' => $order->rider_name ?? ''
                                                ];
                                            }
                                        }
                                    }
                                    }
                                    log_message('info', "Admin::orderListAjax - 파싱된 주문 수: {$orderCount}, 총 orders 배열 크기: " . count($orders));
                                }
                            } else {
                                log_message('warning', "Admin::orderListAjax - orderData가 배열이 아님: " . gettype($orderData));
                            }
                        } else {
                            log_message('warning', "Admin::orderListAjax - orderListResult가 실패 또는 success=false: " . json_encode($orderListResult, JSON_UNESCAPED_UNICODE));
                        }
                        } catch (\Exception $e) {
                            log_message('error', "Admin::orderListAjax - getOrderList 호출 중 예외 발생: compCode={$compCode}, " . $e->getMessage());
                            log_message('error', "Admin::orderListAjax - 예외 스택: " . $e->getTraceAsString());
                            continue;
                        }
                    $processedCount++;
                } catch (\Exception $e) {
                    // 개별 거래처 처리 중 오류 발생 시 로그만 남기고 계속 진행
                    log_message('error', "Admin::orderListAjax - Error processing company {$compCode}: " . $e->getMessage());
                    continue;
                }
                }

                // 전체 주문 수 계산 (기존 로직 - 주석 처리)
                /*
                $totalRecord = count($orders);
                
                // 페이지당 15개로 슬라이싱
                $itemsPerPage = 15;
                $offset = ($page - 1) * $itemsPerPage;
                $pagedOrders = array_slice($orders, $offset, $itemsPerPage);
                
                // 전체 페이지 수 재계산
                $totalPage = ceil($totalRecord / $itemsPerPage);
                if ($totalPage < 1) {
                    $totalPage = 1;
                }
                
                log_message('info', "Admin::orderListAjax - 완료: 전체 주문 수={$totalRecord}, 현재 페이지={$page}, 표시할 주문 수=" . count($pagedOrders) . ", total_page={$totalPage}");
                
                return $this->response->setJSON([
                    'success' => true,
                    'data' => [
                        'orders' => $pagedOrders,
                        'total_record' => $totalRecord,
                        'total_page' => $totalPage,
                        'current_page' => $page,
                        'items_per_page' => $itemsPerPage
                    ]
                ]);
                */
            }
        } catch (\Exception $e) {
            log_message('error', "Admin::orderListAjax - Error: " . $e->getMessage());
            log_message('error', "Admin::orderListAjax - Stack trace: " . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 목록 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 주문 상세 정보 조회 (AJAX) - 인성 API 사용
     */
    /**
     * 콜센터별 거래처 목록 조회 (AJAX - 오더유형 설정용)
     */
    public function getCompaniesByCcForOrderType()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // daumdata 로그인 및 user_type = 1 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        
        if ($loginType !== 'daumdata' || $userType != '1') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        $ccCode = $this->request->getGet('cc_code');
        if (!$ccCode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '콜센터 코드가 필요합니다.'
            ])->setStatusCode(400);
        }

        // 해당 콜센터의 거래처 목록 조회
        $result = $this->insungCompanyListModel->getAllCompanyListWithCc($ccCode);
        $companyList = $result['companies'] ?? [];
        
        return $this->response->setJSON([
            'success' => true,
            'companies' => $companyList
        ]);
    }
    
    public function getOrderDetail()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 권한 체크: STN 로그인 super_admin 또는 daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $hasPermission = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            $hasPermission = true;
        } elseif (!$loginType || $loginType === 'stn') {
            if ($userRole === 'super_admin') {
                $hasPermission = true;
            }
        }
        
        if (!$hasPermission) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        $serialNumber = $this->request->getGet('idx');
        
        if (empty($serialNumber)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문번호가 필요합니다.'
            ])->setStatusCode(400);
        }

        // API 정보 조회
        $mCode = session()->get('m_code');
        $ccCode = session()->get('cc_code');
        $token = session()->get('token');
        $apiIdx = session()->get('api_idx');
        
        // 세션에 없으면 DB에서 조회
        if (!$mCode || !$ccCode || !$token || !$apiIdx) {
            $insungApiListModel = new \App\Models\InsungApiListModel();
            $apiInfo = $insungApiListModel->getApiInfoBySubdomain();
            
            if ($apiInfo) {
                $mCode = $apiInfo['mcode'] ?? $mCode;
                $ccCode = $apiInfo['cccode'] ?? $ccCode;
                $token = $apiInfo['token'] ?? $token;
                $apiIdx = $apiInfo['idx'] ?? $apiIdx;
                
                // 세션에 저장
                session()->set('m_code', $mCode);
                session()->set('cc_code', $ccCode);
                session()->set('token', $token);
                session()->set('api_idx', $apiIdx);
            }
        }

        if (!$mCode || !$ccCode || !$token || !$apiIdx) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보가 설정되지 않았습니다.'
            ])->setStatusCode(500);
        }

        try {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // user_id는 빈 값으로 전달 (comp_no만으로 조회)
            $orderDetailResult = $insungApiService->getOrderDetail($mCode, $ccCode, $token, '', $serialNumber, $apiIdx);
            
            if (!$orderDetailResult || !isset($orderDetailResult['success']) || !$orderDetailResult['success']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $orderDetailResult['message'] ?? '주문 상세 정보를 가져올 수 없습니다.'
                ])->setStatusCode(404);
            }

            $orderData = $orderDetailResult['data'] ?? null;
            
            if (!$orderData || !is_array($orderData)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주문 상세 데이터가 없습니다.'
                ])->setStatusCode(404);
            }

            // 인성 API 응답 구조 파싱
            // $orderData[0]: 응답 코드
            // $orderData[1]: 고객 정보
            // $orderData[2]: 기사 정보
            // $orderData[3]: 주문 시간 정보
            // $orderData[4]: 주소 정보
            // $orderData[5]: 금액 정보
            // $orderData[6]: 기타 정보
            
            $getValue = function($data, $key, $default = '') {
                if (is_object($data)) {
                    return $data->$key ?? $default;
                } elseif (is_array($data)) {
                    return $data[$key] ?? $default;
                }
                return $default;
            };

            // 응답 데이터 파싱
            $parsedData = [
                'serial_number' => $serialNumber,
                'order_number' => $serialNumber,
            ];

            // 고객 정보 (인덱스 1)
            if (isset($orderData[1])) {
                $customerInfo = $orderData[1];
                $parsedData['customer_name'] = $getValue($customerInfo, 'customer_name');
                $parsedData['customer_tel'] = $getValue($customerInfo, 'customer_tel_number');
                $parsedData['customer_department'] = $getValue($customerInfo, 'customer_department');
                $parsedData['customer_duty'] = $getValue($customerInfo, 'customer_duty');
            }

            // 기사 정보 (인덱스 2)
            if (isset($orderData[2])) {
                $riderInfo = $orderData[2];
                $parsedData['rider_code'] = $getValue($riderInfo, 'rider_code_no');
                $parsedData['rider_name'] = $getValue($riderInfo, 'rider_name');
                $parsedData['rider_tel'] = $getValue($riderInfo, 'rider_tel_number');
                $parsedData['rider_lon'] = $getValue($riderInfo, 'rider_lon');
                $parsedData['rider_lat'] = $getValue($riderInfo, 'rider_lat');
            }

            // 주문 시간 정보 (인덱스 3)
            if (isset($orderData[3])) {
                $timeInfo = $orderData[3];
                $parsedData['order_time'] = $getValue($timeInfo, 'order_time');
                $parsedData['allocation_time'] = $getValue($timeInfo, 'allocation_time');
                $parsedData['pickup_time'] = $getValue($timeInfo, 'pickup_time');
                $parsedData['resolve_time'] = $getValue($timeInfo, 'resolve_time');
                $parsedData['complete_time'] = $getValue($timeInfo, 'complete_time');
            }

            // 주소 정보 (인덱스 4)
            if (isset($orderData[4])) {
                $addressInfo = $orderData[4];
                $parsedData['departure_dong_name'] = $getValue($addressInfo, 'departure_dong_name');
                $parsedData['departure_address'] = $getValue($addressInfo, 'departure_address');
                $parsedData['departure_company_name'] = $getValue($addressInfo, 'departure_company_name');
                $parsedData['departure_tel'] = $getValue($addressInfo, 'departure_tel_number');
                $parsedData['departure_department'] = $getValue($addressInfo, 'start_department');
                $parsedData['departure_staff'] = $getValue($addressInfo, 'start_duty');
                $parsedData['departure_lon'] = $getValue($addressInfo, 'start_lon');
                $parsedData['departure_lat'] = $getValue($addressInfo, 'start_lat');
                
                $parsedData['destination_dong_name'] = $getValue($addressInfo, 'destination_dong_name');
                $parsedData['destination_address'] = $getValue($addressInfo, 'destination_address');
                $parsedData['destination_company_name'] = $getValue($addressInfo, 'destination_company_name');
                $parsedData['destination_tel'] = $getValue($addressInfo, 'destination_tel_number');
                $parsedData['destination_department'] = $getValue($addressInfo, 'dest_department');
                $parsedData['destination_staff'] = $getValue($addressInfo, 'dest_duty');
                $parsedData['destination_lon'] = $getValue($addressInfo, 'dest_lon');
                $parsedData['destination_lat'] = $getValue($addressInfo, 'dest_lat');
                
                $parsedData['distance'] = $getValue($addressInfo, 'distince'); // API 오타 그대로 사용
            }

            // 금액 정보 (인덱스 5)
            if (isset($orderData[5])) {
                $costInfo = $orderData[5];
                $parsedData['basic_cost'] = $getValue($costInfo, 'basic_cost');
                $parsedData['addition_cost'] = $getValue($costInfo, 'addition_cost');
                $parsedData['delivery_cost'] = $getValue($costInfo, 'delivery_cost');
                $parsedData['total_cost'] = $getValue($costInfo, 'total_cost');
            }

            // 기타 정보 (인덱스 6 이상)
            if (isset($orderData[6])) {
                $extraInfo = $orderData[6];
                $parsedData['order_state'] = $getValue($extraInfo, 'order_state');
                $parsedData['delivery_type'] = $getValue($extraInfo, 'delivery_type');
                $parsedData['car_type'] = $getValue($extraInfo, 'car_type');
                $parsedData['payment_type'] = $getValue($extraInfo, 'payment_type');
                $parsedData['summary'] = $getValue($extraInfo, 'summary');
                $parsedData['user_id'] = $getValue($extraInfo, 'user_id');
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $parsedData
            ]);
            
        } catch (\Exception $e) {
            log_message('error', "Admin::getOrderDetail - Error: " . $e->getMessage());
            log_message('error', "Admin::getOrderDetail - Stack trace: " . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => '주문 상세 정보 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
