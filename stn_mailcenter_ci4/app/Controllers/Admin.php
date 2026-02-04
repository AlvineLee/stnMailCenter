<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\ServiceTypeModel;
use App\Models\UserServicePermissionModel;
use App\Models\InsungCcListModel;
use App\Models\CcServicePermissionModel;
use App\Models\CompanyServicePermissionModel;
use App\Models\CompanyMailroomPermissionModel;
use App\Models\InsungCompanyListModel;
use App\Models\InsungApiListModel;

class Admin extends BaseController
{
    protected $adminModel;
    protected $serviceTypeModel;
    protected $userServicePermissionModel;
    protected $insungCcListModel;
    protected $ccServicePermissionModel;
    protected $companyServicePermissionModel;
    protected $companyMailroomPermissionModel;
    protected $insungCompanyListModel;
    protected $insungApiListModel;
    
    public function __construct()
    {
        $this->adminModel = new AdminModel();
        $this->serviceTypeModel = new ServiceTypeModel();
        $this->userServicePermissionModel = new UserServicePermissionModel();
        $this->insungCcListModel = new InsungCcListModel();
        $this->ccServicePermissionModel = new CcServicePermissionModel();
        $this->companyServicePermissionModel = new CompanyServicePermissionModel();
        $this->companyMailroomPermissionModel = new CompanyMailroomPermissionModel();
        $this->insungCompanyListModel = new InsungCompanyListModel();
        $this->insungApiListModel = new InsungApiListModel();
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
        
        log_message('debug', "orderType - 선택된 값: ccCode=" . ($selectedCcCode ?? 'null') . ", compCode=" . ($selectedCompCode ?? 'null'));
        
        // 슈퍼관리자인 경우 항상 tbl_api_list에서만 조회
        if ($loginType === 'daumdata' && $userType == '1') {
            // tbl_api_list에서 API 목록 조회 (tbl_cc_list가 아닌 tbl_api_list만 사용)
            // api_gbn='M' (메인 API)만 조회
            $apiList = $this->insungApiListModel->getMainApiList();
            
            // 뷰에서 사용하는 형식으로 변환 (cc_code, cc_name)
            // cc_code는 cccode 값, cc_name은 "cccode - api_name" 형식
            foreach ($apiList as $api) {
                $cccode = $api['cccode'] ?? '';
                $apiName = $api['api_name'] ?? '';
                
                // cccode와 api_name이 모두 있는 경우만 추가
                if (!empty($cccode) && !empty($apiName)) {
                    $ccList[] = [
                        'cc_code' => $cccode,
                        'cc_name' => $cccode . ' - ' . $apiName
                    ];
                }
            }
            
            // 선택된 콜센터가 있으면 해당 콜센터의 거래처 목록 조회 (DB에서만)
            if ($selectedCcCode) {
                $db = \Config\Database::connect();

                // tbl_api_list에서 cccode로 idx 찾기
                $apiInfo = $this->insungApiListModel->where('cccode', $selectedCcCode)->first();

                if ($apiInfo && !empty($apiInfo['idx'])) {
                    $apiIdx = (int)$apiInfo['idx'];

                    // tbl_cc_list -> tbl_company_list 직접 조인 (use_yn = 'Y'만)
                    $builder = $db->table('tbl_cc_list cc');
                    $builder->select('
                        c.comp_code,
                        c.comp_name,
                        c.cc_idx,
                        cc.cc_code,
                        cc.cc_name
                    ');
                    $builder->join('tbl_company_list c', 'c.cc_idx = cc.idx', 'inner');
                    $builder->where('cc.cc_apicode', $apiIdx);
                    $builder->where('c.use_yn', 'Y');
                    $builder->orderBy('c.comp_name', 'ASC');

                    $query = $builder->get();
                    if ($query !== false) {
                        $companyList = $query->getResultArray();
                    } else {
                        $companyList = [];
                    }
                } else {
                    $companyList = [];
                }
            }
            
            // 권한 조회 우선순위: 거래처 > 콜센터 > 마스터
            $permissionMap = [];
            $contractMap = [];  // 계약여부 맵 (거래처 레벨에서만)
            $permissionSource = 'master'; // 'master', 'cc', 'company'
            $hasMailroomPermission = false; // 메일룸 권한 (거래처 레벨에서만)

            // 거래처가 선택된 경우: 거래처 권한 우선 조회
            if ($selectedCompCode && $selectedCcCode) {
                // 메일룸 권한 조회 (service_types와 독립적)
                $hasMailroomPermission = $this->companyMailroomPermissionModel->hasPermission($selectedCompCode);

                log_message('debug', "orderType - 거래처 권한 조회 시작: compCode={$selectedCompCode}, ccCode={$selectedCcCode}");
                $companyPermissions = $this->companyServicePermissionModel->getCompanyServicePermissions($selectedCompCode);
                log_message('debug', "orderType - 거래처 권한 조회 결과 개수: " . count($companyPermissions));

                if (!empty($companyPermissions)) {
                    // 거래처 권한이 있으면 거래처 권한 사용
                    foreach ($companyPermissions as $permission) {
                        $serviceTypeId = $permission['service_type_id'] ?? null;
                        $isEnabled = (bool)($permission['is_enabled'] ?? false);
                        $isUncontracted = $permission['is_uncontracted'] ?? null;
                        // is_uncontracted: 1=미계약/권한없음, 0/NULL=계약(기본)
                        if ($serviceTypeId) {
                            $permissionMap[$serviceTypeId] = $isEnabled;
                            $contractMap[$serviceTypeId] = $isUncontracted;
                        }
                    }
                    $permissionSource = 'company';
                    log_message('debug', "orderType - 거래처 권한 사용: permissionSource=company, permissionMap 개수: " . count($permissionMap));
                } else {
                    // 거래처 권한이 없으면 콜센터 권한 상속
                    log_message('debug', "orderType - 거래처 권한 없음, 콜센터 권한 상속 시도: ccCode={$selectedCcCode}");
                    $ccPermissions = $this->ccServicePermissionModel->getCcServicePermissions($selectedCcCode);
                    log_message('debug', "orderType - 콜센터 권한 조회 결과 개수: " . count($ccPermissions));
                    if (!empty($ccPermissions)) {
                        foreach ($ccPermissions as $permission) {
                            $serviceTypeId = $permission['service_type_id'] ?? null;
                            $isEnabled = (bool)($permission['is_enabled'] ?? false);
                            if ($serviceTypeId) {
                                $permissionMap[$serviceTypeId] = $isEnabled;
                            }
                        }
                        $permissionSource = 'cc';
                        log_message('debug', "orderType - 콜센터 권한 사용: permissionSource=cc, permissionMap 개수: " . count($permissionMap));
                    } else {
                        log_message('debug', "orderType - 콜센터 권한도 없음, 마스터 설정 사용");
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

                    // 계약여부 (거래처 선택 시에만)
                    if ($permissionSource === 'company') {
                        $service['is_uncontracted'] = isset($contractMap[$service['id']]) ? $contractMap[$service['id']] : null;
                    } else {
                        $service['is_uncontracted'] = null;
                    }
                }
            }

            log_message('debug', "orderType - 권한 적용 완료: permissionSource={$permissionSource}, permissionMap 개수: " . count($permissionMap));
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
            'user_type' => $userType,
            'show_contract_settings' => !empty($selectedCompCode),
            'has_mailroom_permission' => $hasMailroomPermission
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
            return $this->response->setJSON(['success' => false, 'message' => '로���인이 필요합니다.'])->setStatusCode(401);
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
                log_message('debug', "batchUpdateServiceStatus - 거래처 권한 저장 시작: compCode={$compCode}, ccCode={$ccCode}");
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
                
                log_message('debug', "batchUpdateServiceStatus - 저장할 거래처 권한 개수: " . count($permissions));
                log_message('debug', "batchUpdateServiceStatus - 거래처 권한 데이터: " . json_encode($permissions, JSON_UNESCAPED_UNICODE));
                
                $result = $this->companyServicePermissionModel->batchUpdateCompanyServicePermissions($compCode, $permissions);
                
                log_message('debug', "batchUpdateServiceStatus - 거래처 권한 저장 결과: " . ($result ? 'success' : 'failed'));
                
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
     * 계약여부 일괄 업데이트 (거래처 레벨)
     */
    public function batchUpdateContractStatus()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        // 권한 체크: daumdata 로그인 user_type=1만 가능
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        // JSON 요청 처리
        $json = $this->request->getJSON(true);
        $compCode = $json['comp_code'] ?? null;
        $ccCode = $json['cc_code'] ?? null;
        $contracts = $json['contracts'] ?? [];
        $mailroomPermission = $json['mailroom_permission'] ?? null; // 메일룸 권한 (별도 파라미터)

        if (empty($compCode)) {
            return $this->response->setJSON(['success' => false, 'message' => '거래처 코드가 필요합니다.']);
        }


        // 일반 서비스 계약여부 데이터 수집
        $regularContractData = [];
        foreach ($contracts as $contract) {
            $serviceTypeId = $contract['service_type_id'] ?? null;
            $isUncontracted = $contract['is_uncontracted'] ?? 0;

            if (!$serviceTypeId) {
                continue;
            }

            $regularContractData[] = [
                'service_type_id' => $serviceTypeId,
                'is_uncontracted' => $isUncontracted ? 1 : 0
            ];
        }

        // 결과 저장
        $regularResult = true;
        $mailroomResult = true;

        // 일반 서비스 계약여부 저장
        if (!empty($regularContractData)) {
            $regularResult = $this->companyServicePermissionModel->batchUpdateCompanyServiceContracts($compCode, $regularContractData);
        }

        // 메일룸 권한 저장
        if ($mailroomPermission !== null) {
            $mailroomResult = $this->companyMailroomPermissionModel->setPermission($compCode, $mailroomPermission);
        }

        // 전체 결과 확인
        if ($regularResult && $mailroomResult) {
            return $this->response->setJSON([
                'success' => true,
                'message' => '계약여부 설정이 저장되었습니다.'
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '계약여부 저장에 실패했습니다.']);
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

        // 거래처 목록 조회 (API - 모든 페이지 순회)
        $companyList = [];
        if ($mCode && $ccCode && $token) {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // 첫 번째 페이지 호출하여 total_page 확인
            $firstPageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', '', 1, 100, $apiIdx);
            
            if ($firstPageResult) {
                // 응답 구조 파싱
                $companyData = $firstPageResult;
                $code = '';
                $totalPage = 1;
                
                if (is_object($companyData) && isset($companyData->Result)) {
                    $resultArray = is_array($companyData->Result) ? $companyData->Result : [$companyData->Result];
                    if (isset($resultArray[0]->result_info[0]->code)) {
                        $code = $resultArray[0]->result_info[0]->code;
                    } elseif (isset($resultArray[0]->code)) {
                        $code = $resultArray[0]->code;
                    }
                    
                    if (isset($resultArray[1]->page_info[0]->total_page)) {
                        $totalPage = (int)$resultArray[1]->page_info[0]->total_page;
                    } elseif (isset($resultArray[1]->total_page)) {
                        $totalPage = (int)$resultArray[1]->total_page;
                    }
                } elseif (is_array($companyData)) {
                    if (isset($companyData[0]->code)) {
                        $code = $companyData[0]->code;
                    } elseif (isset($companyData[0]['code'])) {
                        $code = $companyData[0]['code'];
                    }
                    
                    if (isset($companyData[1]->page_info[0]->total_page)) {
                        $totalPage = (int)$companyData[1]->page_info[0]->total_page;
                    } elseif (isset($companyData[1]->total_page)) {
                        $totalPage = (int)$companyData[1]->total_page;
                    }
                }
                
                // 성공 코드 확인 (1000)
                if ($code === '1000' && $totalPage > 0) {
                    // 모든 페이지 순회
                    for ($page = 1; $page <= $totalPage; $page++) {
                        // 첫 번째 페이지는 이미 호출했으므로 재사용
                        if ($page === 1) {
                            $pageResult = $firstPageResult;
                        } else {
                            $pageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', '', $page, 100, $apiIdx);
                        }
                        
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
                                            'cc_code' => $ccCode
                                        ];
                                    }
                                }
                            } elseif (is_array($pageData) && isset($pageData[2])) {
                                if (isset($pageData[2]->items[0]->item)) {
                                    $items = is_array($pageData[2]->items[0]->item) ? $pageData[2]->items[0]->item : [$pageData[2]->items[0]->item];
                                    foreach ($items as $item) {
                                        $companyList[] = [
                                            'comp_no' => $item->comp_no ?? '',
                                            'corp_name' => $item->corp_name ?? '',
                                            'owner' => $item->owner ?? '',
                                            'tel_no' => $item->tel_no ?? '',
                                            'cc_code' => $ccCode
                                        ];
                                    }
                                }
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

        // 거래처 목록 조회 (API - 모든 페이지 순회)
        $companyList = [];
        if ($mCode && $ccCode && $token) {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // 첫 번째 페이지 호출하여 total_page 확인
            $firstPageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', '', 1, 100, $apiIdx);
            
            if ($firstPageResult) {
                // 응답 구조 파싱
                $companyData = $firstPageResult;
                $code = '';
                $totalPage = 1;
                
                if (is_object($companyData) && isset($companyData->Result)) {
                    $resultArray = is_array($companyData->Result) ? $companyData->Result : [$companyData->Result];
                    if (isset($resultArray[0]->result_info[0]->code)) {
                        $code = $resultArray[0]->result_info[0]->code;
                    } elseif (isset($resultArray[0]->code)) {
                        $code = $resultArray[0]->code;
                    }
                    
                    if (isset($resultArray[1]->page_info[0]->total_page)) {
                        $totalPage = (int)$resultArray[1]->page_info[0]->total_page;
                    } elseif (isset($resultArray[1]->total_page)) {
                        $totalPage = (int)$resultArray[1]->total_page;
                    }
                } elseif (is_array($companyData)) {
                    if (isset($companyData[0]->code)) {
                        $code = $companyData[0]->code;
                    } elseif (isset($companyData[0]['code'])) {
                        $code = $companyData[0]['code'];
                    }
                    
                    if (isset($companyData[1]->page_info[0]->total_page)) {
                        $totalPage = (int)$companyData[1]->page_info[0]->total_page;
                    } elseif (isset($companyData[1]->total_page)) {
                        $totalPage = (int)$companyData[1]->total_page;
                    }
                }
                
                // 성공 코드 확인 (1000)
                if ($code === '1000' && $totalPage > 0) {
                    // 모든 페이지 순회
                    for ($page = 1; $page <= $totalPage; $page++) {
                        // 첫 번째 페이지는 이미 호출했으므로 재사용
                        if ($page === 1) {
                            $pageResult = $firstPageResult;
                        } else {
                            $pageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', '', $page, 100, $apiIdx);
                        }
                        
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
                                            'cc_code' => $ccCode
                                        ];
                                    }
                                }
                            } elseif (is_array($pageData) && isset($pageData[2])) {
                                if (isset($pageData[2]->items[0]->item)) {
                                    $items = is_array($pageData[2]->items[0]->item) ? $pageData[2]->items[0]->item : [$pageData[2]->items[0]->item];
                                    foreach ($items as $item) {
                                        $companyList[] = [
                                            'comp_no' => $item->comp_no ?? '',
                                            'corp_name' => $item->corp_name ?? '',
                                            'owner' => $item->owner ?? '',
                                            'tel_no' => $item->tel_no ?? '',
                                            'cc_code' => $ccCode
                                        ];
                                    }
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
     * 콜센터 관리자용 거래처관리 (user_type = 3 또는 user_class = 1)
     * user_type = 3: user_cc_idx를 기반으로 해당 콜센터의 거래처 목록 조회
     * user_class = 1: 본인 거래처만 조회
     */
    public function companyListForCc()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: daumdata 로그인 user_type=3 (콜센터 관리자) 또는 user_class=1 (거래처 관리자)
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        $userClass = session()->get('user_class');
        $userId = session()->get('user_id');
        
        // user_class가 세션에 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata' && $userId) {
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_class, user_company');
            $userBuilder->where('user_id', $userId);
            $userQuery = $userBuilder->get();
            if ($userQuery !== false) {
                $userResult = $userQuery->getRowArray();
                if ($userResult) {
                    if (isset($userResult['user_class'])) {
                        $userClass = $userResult['user_class'];
                    }
                }
            }
        }
        
        // 권한 체크: user_type=3 또는 user_class=1만 허용
        if ($loginType !== 'daumdata' || ($userType != '3' && $userClass != '1')) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }
        
        // user_cc_idx 조회 (세션 또는 DB에서)
        $userCcIdx = session()->get('user_cc_idx');

        // 세션에 없으면 DB에서 조회
        if (empty($userCcIdx)) {
            $userId = session()->get('user_id');
            if ($userId) {
                $db = \Config\Database::connect();
                $userBuilder = $db->table('tbl_users_list u');
                $userBuilder->select('c.cc_idx');
                $userBuilder->join('tbl_company_list c', 'u.user_company = c.comp_code', 'left');
                $userBuilder->where('u.user_id', (string)$userId); // 문자열로 명시적 변환
                $userQuery = $userBuilder->get();

                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult && !empty($userResult['cc_idx'])) {
                        $userCcIdx = (int)$userResult['cc_idx']; // 정수로 명시적 변환
                        session()->set('user_cc_idx', $userCcIdx);
                    }
                }
            }
        }

        // 우선순위: user_type=3이 있으면 콜센터 관리자로 처리, 없으면 user_class=1로 거래처 관리자 처리
        if ($userType == '3') {
            // user_type = 3 (콜센터 관리자): 콜센터의 모든 거래처 조회
            // userCcIdx가 정수인지 확인하고 변환
            if (empty($userCcIdx) || !is_numeric($userCcIdx)) {
                return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
            }

            $userCcIdx = (int)$userCcIdx; // 최종적으로 정수로 변환

            // user_cc_idx로 콜센터 정보 조회 (cc_code 가져오기)
            $db = \Config\Database::connect();
            $ccBuilder = $db->table('tbl_cc_list');
            $ccBuilder->select('cc_code, cc_name');
            $ccBuilder->where('idx', $userCcIdx);
            $ccQuery = $ccBuilder->get();

            $ccInfo = null;
            if ($ccQuery !== false) {
                $ccInfo = $ccQuery->getRowArray();
            }
        } else {
            // user_class = 1 (거래처 관리자): 본인 거래처만 조회
            $userCompany = session()->get('user_company');
            if (empty($userCompany) && $userId) {
                $db = \Config\Database::connect();
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->select('user_company');
                $userBuilder->where('user_id', $userId);
                $userQuery = $userBuilder->get();
                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult && isset($userResult['user_company'])) {
                        $userCompany = $userResult['user_company'];
                    }
                }
            }

            if (empty($userCompany)) {
                return redirect()->to('/')->with('error', '거래처 정보를 찾을 수 없습니다.');
            }

            // user_company로 거래처 정보 조회
            $db = \Config\Database::connect();
            $compBuilder = $db->table('tbl_company_list');
            $compBuilder->select('comp_code, comp_name, cc_idx');
            $compBuilder->where('comp_code', $userCompany);
            $compQuery = $compBuilder->get();

            $compInfo = null;
            if ($compQuery !== false) {
                $compInfo = $compQuery->getRowArray();
            }

            if (empty($compInfo)) {
                return redirect()->to('/')->with('error', '거래처 정보를 찾을 수 없습니다.');
            }

            // cc_idx로 cc_code 조회
            if (!empty($compInfo['cc_idx'])) {
                $ccBuilder = $db->table('tbl_cc_list');
                $ccBuilder->select('cc_code, cc_name');
                $ccBuilder->where('idx', $compInfo['cc_idx']);
                $ccQuery = $ccBuilder->get();

                $ccInfo = null;
                if ($ccQuery !== false) {
                    $ccInfo = $ccQuery->getRowArray();
                }
            }
        }
        
        if (empty($ccInfo) || empty($ccInfo['cc_code'])) {
            return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
        }
        
        $ccCode = $ccInfo['cc_code'];
        
        // 검색 조건
        $searchCompName = $this->request->getGet('search_compname') ?? '';
        
        // API 정보 조회 (cc_code로)
        $insungApiListModel = new \App\Models\InsungApiListModel();
        $apiInfo = $insungApiListModel->getApiInfoByCode($ccCode);
        
        if (empty($apiInfo)) {
            return redirect()->to('/')->with('error', 'API 정보를 찾을 수 없습니다.');
        }
        
        $mCode = $apiInfo['mcode'] ?? '';
        $ccCodeApi = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';
        $apiIdx = $apiInfo['idx'] ?? null;
        
        // 검색어 처리 (c_comp_list.html 참조)
        // 원본 검색어 저장 (뷰에서 사용)
        $originalSearchCompName = $searchCompName;
        
        // API 호출용 검색어 처리 (cc_code prefix 추가)
        $apiSearchCompName = $searchCompName;
        if (!empty($apiSearchCompName)) {
            // 이미 prefix가 있으면 그대로 사용, 없으면 추가
            if (strpos($apiSearchCompName, $ccCode . '_') !== 0) {
                $apiSearchCompName = $ccCode . '_' . $apiSearchCompName;
            }
        } else {
            $apiSearchCompName = $ccCode . '_';
        }
        
        // 거래처 목록 조회 (API - 모든 페이지 순회)
        $companyList = [];
        if ($mCode && $ccCodeApi && $token) {
            $insungApiService = new \App\Libraries\InsungApiService();
            
            // 첫 번째 페이지 호출하여 total_page 확인
            $firstPageResult = $insungApiService->getCompanyList($mCode, $ccCodeApi, $token, '', $apiSearchCompName, 1, 100, $apiIdx);
            
            if ($firstPageResult) {
                // 응답 구조 파싱
                $companyData = $firstPageResult;
                $code = '';
                $totalPage = 1;
                
                if (is_object($companyData) && isset($companyData->Result)) {
                    $resultArray = is_array($companyData->Result) ? $companyData->Result : [$companyData->Result];
                    if (isset($resultArray[0]->result_info[0]->code)) {
                        $code = $resultArray[0]->result_info[0]->code;
                    } elseif (isset($resultArray[0]->code)) {
                        $code = $resultArray[0]->code;
                    }
                    
                    if (isset($resultArray[1]->page_info[0]->total_page)) {
                        $totalPage = (int)$resultArray[1]->page_info[0]->total_page;
                    } elseif (isset($resultArray[1]->total_page)) {
                        $totalPage = (int)$resultArray[1]->total_page;
                    }
                } elseif (is_array($companyData)) {
                    if (isset($companyData[0]->code)) {
                        $code = $companyData[0]->code;
                    } elseif (isset($companyData[0]['code'])) {
                        $code = $companyData[0]['code'];
                    }
                    
                    if (isset($companyData[1]->page_info[0]->total_page)) {
                        $totalPage = (int)$companyData[1]->page_info[0]->total_page;
                    } elseif (isset($companyData[1]->total_page)) {
                        $totalPage = (int)$companyData[1]->total_page;
                    }
                }
                
                // 성공 코드 확인 (1000)
                if ($code === '1000' && $totalPage > 0) {
                    // 모든 페이지 순회
                    for ($page = 1; $page <= $totalPage; $page++) {
                        // 첫 번째 페이지는 이미 호출했으므로 재사용
                        if ($page === 1) {
                            $pageResult = $firstPageResult;
                        } else {
                            $pageResult = $insungApiService->getCompanyList($mCode, $ccCodeApi, $token, '', $apiSearchCompName, $page, 100, $apiIdx);
                        }
                        
                        if ($pageResult) {
                            $pageData = $pageResult;
                            
                            // 응답 구조 파싱
                            if (is_object($pageData) && isset($pageData->Result)) {
                                $resultArray = is_array($pageData->Result) ? $pageData->Result : [$pageData->Result];
                                if (isset($resultArray[2]->items[0]->item)) {
                                    $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
                                    foreach ($items as $item) {
                                        $companyList[] = [
                                            'comp_code' => $item->comp_no ?? '',
                                            'comp_name' => $item->corp_name ?? '',
                                            'comp_owner' => $item->owner ?? '',
                                            'comp_tel' => $item->tel_no ?? '',
                                            'comp_addr' => $item->adddress ?? '',
                                            'comp_memo' => '' // API에서 제공하지 않음
                                        ];
                                    }
                                }
                            } elseif (is_array($pageData) && isset($pageData[2])) {
                                if (isset($pageData[2]->items[0]->item)) {
                                    $items = is_array($pageData[2]->items[0]->item) ? $pageData[2]->items[0]->item : [$pageData[2]->items[0]->item];
                                    foreach ($items as $item) {
                                        $companyList[] = [
                                            'comp_code' => $item->comp_no ?? '',
                                            'comp_name' => $item->corp_name ?? '',
                                            'comp_owner' => $item->owner ?? '',
                                            'comp_tel' => $item->tel_no ?? '',
                                            'comp_addr' => $item->adddress ?? '',
                                            'comp_memo' => '' // API에서 제공하지 않음
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // user_class = 1일 때는 본인 거래처만 필터링
        $filteredCompanies = $companyList;
        if ($userClass == '1' && !empty($userCompany)) {
            $filteredCompanies = array_filter($companyList, function($company) use ($userCompany) {
                return isset($company['comp_code']) && $company['comp_code'] === $userCompany;
            });
            // 배열 인덱스 재정렬
            $filteredCompanies = array_values($filteredCompanies);
        }
        
        $data = [
            'title' => '거래처관리',
            'content_header' => [
                'title' => '거래처관리',
                'description' => ($userClass == '1') ? '본인 거래처를 조회하고 관리할 수 있습니다.' : '소속 콜센터의 거래처 목록을 조회하고 관리할 수 있습니다.'
            ],
            'company_list' => $filteredCompanies,
            'search_compname' => $originalSearchCompName, // 원본 검색어
            'user_cc_idx' => $userCcIdx ?? null,
            'cc_code' => $ccCode,
            'user_class' => $userClass
        ];

        return view('admin/company-list-cc', $data);
    }

    /**
     * 거래처 수정 폼 (user_type = 3 또는 user_class = 1 접근 가능)
     */
    public function companyEdit()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: user_type = 3 (콜센터 관리자) 또는 user_class = 1 (거래처 관리자)
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        $userClass = session()->get('user_class');
        $userId = session()->get('user_id');
        $userCompany = session()->get('user_company');
        
        // user_class가 세션에 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata' && $userId) {
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_class, user_company');
            $userBuilder->where('user_id', $userId);
            $userQuery = $userBuilder->get();
            if ($userQuery !== false) {
                $userResult = $userQuery->getRowArray();
                if ($userResult) {
                    if (isset($userResult['user_class'])) {
                        $userClass = $userResult['user_class'];
                    }
                    if (isset($userResult['user_company'])) {
                        $userCompany = $userResult['user_company'];
                    }
                }
            }
        }
        
        if ($loginType !== 'daumdata' || ($userType != '3' && $userClass != '1')) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }
        
        // comp_code 파라미터 필수
        $compCode = $this->request->getGet('comp_code');

        // user_type = 3이 아니고 user_class = 1일 때는 본인 거래처만 조회 가능
        if ($userType != '3' && $userClass == '1') {
            if (empty($userCompany)) {
                return redirect()->to('/')->with('error', '거래처 정보를 찾을 수 없습니다.');
            }
            // comp_code가 없거나 본인 거래처가 아니면 본인 거래처로 설정
            if (empty($compCode) || $compCode !== $userCompany) {
                $compCode = $userCompany;
            }
        } elseif (empty($compCode)) {
            return redirect()->to('/admin/company-list-cc')->with('error', '거래처 코드가 필요합니다.');
        }
        
        $db = \Config\Database::connect();
        $userCcIdx = session()->get('user_cc_idx');
        
        // user_type = 3일 때만 user_cc_idx 조회
        if ($userType == '3' && empty($userCcIdx)) {
            $currentUserId = session()->get('user_id');
            if ($currentUserId) {
                $userBuilder = $db->table('tbl_users_list u');
                $userBuilder->select('c.cc_idx');
                $userBuilder->join('tbl_company_list c', 'u.user_company = c.comp_code', 'left');
                $userBuilder->where('u.user_id', (string)$currentUserId);
                $userQuery = $userBuilder->get();
                
                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult && !empty($userResult['cc_idx'])) {
                        $userCcIdx = (int)$userResult['cc_idx'];
                        session()->set('user_cc_idx', $userCcIdx);
                    }
                }
            }
        }
        
        // 콜센터 정보 조회
        $ccInfo = null;
        if ($userType == '3') {
            // user_type = 3일 때는 user_cc_idx 사용
            if (empty($userCcIdx)) {
                return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
            }

            $ccBuilder = $db->table('tbl_cc_list');
            $ccBuilder->select('cc_code, cc_name');
            $ccBuilder->where('idx', $userCcIdx);
            $ccQuery = $ccBuilder->get();
            $ccInfo = $ccQuery !== false ? $ccQuery->getRowArray() : null;
        } elseif ($userClass == '1') {
            // user_class = 1일 때는 comp_code로 cc_idx 조회 후 cc_code 조회
            $compBuilder = $db->table('tbl_company_list');
            $compBuilder->select('cc_idx');
            $compBuilder->where('comp_code', $compCode);
            $compQuery = $compBuilder->get();

            if ($compQuery !== false) {
                $compResult = $compQuery->getRowArray();
                if ($compResult && !empty($compResult['cc_idx'])) {
                    $ccBuilder = $db->table('tbl_cc_list');
                    $ccBuilder->select('cc_code, cc_name');
                    $ccBuilder->where('idx', $compResult['cc_idx']);
                    $ccQuery = $ccBuilder->get();

                    if ($ccQuery !== false) {
                        $ccInfo = $ccQuery->getRowArray();
                    }
                }
            }
        }
        
        if (empty($ccInfo) || empty($ccInfo['cc_code'])) {
            return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
        }
        
        // API 정보 조회
        $insungApiListModel = new \App\Models\InsungApiListModel();
        $apiInfo = $insungApiListModel->getApiInfoByCode($ccInfo['cc_code']);
        
        if (empty($apiInfo)) {
            return redirect()->to('/')->with('error', 'API 정보를 찾을 수 없습니다.');
        }
        
        $mCode = $apiInfo['mcode'] ?? '';
        $ccCodeApi = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';
        $apiIdx = $apiInfo['idx'] ?? null;
        
        // 거래처 정보 조회 (인성 API)
        $insungApiService = new \App\Libraries\InsungApiService();
        $apiResult = $insungApiService->getCompanyList($mCode, $ccCodeApi, $token, $compCode, '', 1, 1, $apiIdx);
        
        $companyInfo = null;
        $item = null;
        
        // API 응답 구조 안전하게 파싱
        if ($apiResult !== false) {
            // 응답 코드 확인
            $code = '';
            if (is_object($apiResult) && isset($apiResult->Result)) {
                $resultArray = is_array($apiResult->Result) ? $apiResult->Result : [$apiResult->Result];
                if (isset($resultArray[0]->result_info[0]->code)) {
                    $code = $resultArray[0]->result_info[0]->code;
                } elseif (isset($resultArray[0]->code)) {
                    $code = $resultArray[0]->code;
                }
                
                // 성공 코드이고 데이터가 있는 경우
                if ($code === '1000' && isset($resultArray[2])) {
                    if (isset($resultArray[2]->items[0]->item)) {
                        $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
                        if (!empty($items) && isset($items[0])) {
                            $item = $items[0];
                        }
                    }
                }
            } elseif (is_array($apiResult)) {
                if (isset($apiResult[0]->code)) {
                    $code = $apiResult[0]->code;
                } elseif (isset($apiResult[0]['code'])) {
                    $code = $apiResult[0]['code'];
                }
                
                // 성공 코드이고 데이터가 있는 경우
                if ($code === '1000' && isset($apiResult[2])) {
                    if (isset($apiResult[2]->items[0]->item)) {
                        $items = is_array($apiResult[2]->items[0]->item) ? $apiResult[2]->items[0]->item : [$apiResult[2]->items[0]->item];
                        if (!empty($items) && isset($items[0])) {
                            $item = $items[0];
                        }
                    }
                }
            }
            
            log_message('debug', "Admin::companyEdit - API response code: {$code}, item found: " . ($item ? 'yes' : 'no') . ", comp_code: {$compCode}");
        } else {
            log_message('error', "Admin::companyEdit - API call failed for comp_code: {$compCode}");
        }
        
        if ($item) {
            // API 응답의 모든 필드를 로깅 (디버깅용) - 객체를 배열로 변환하여 로깅
            $itemArray = is_object($item) ? (array)$item : $item;
            log_message('debug', 'Admin::companyEdit - API response item fields: ' . json_encode($itemArray, JSON_UNESCAPED_UNICODE));
            
            // credit 필드 확인 (stnlogis 참조: c_comp_list.html 74-86 라인)
            // API 응답에서 credit 필드가 있는지 확인, 없으면 기본값 "3" 사용
            $credit = '3'; // 기본값 3 (stnlogis와 동일)
            if (isset($item->credit) && !empty($item->credit)) {
                $credit = $item->credit;
            } elseif (isset($itemArray['credit']) && !empty($itemArray['credit'])) {
                $credit = $itemArray['credit'];
            }
            
            log_message('debug', 'Admin::companyEdit - credit value from API: ' . $credit);
            
            $companyInfo = [
                'comp_code' => $compCode,
                'comp_name' => $item->corp_name ?? '',
                // stnlogis 참조: owner = 대표자명, staff_name = 담당자명
                'representative_name' => $item->owner ?? '', // 대표자명 (stnlogis: comp_owner)
                'comp_owner' => $item->staff_name ?? '', // 담당자명 (stnlogis: comp_staff)
                'comp_tel' => $item->tel_no ?? '',
                'comp_addr' => $item->adddress ?? $item->address ?? '',
                'business_number' => $item->business_number ?? '',
                // API에서 거래구분 필드 (credit) - stnlogis와 동일하게 처리 (기본값 3)
                'comp_type' => $credit,
                'comp_gbn' => $credit
            ];
        }
        
        // 로컬 DB에서 추가 정보 조회 (comp_memo 등)
        if (!empty($companyInfo)) {
            // tbl_company_list에서 기본 정보 조회 (거래구분, 대표자명은 API에서 가져왔으므로 제외)
            // sido, gungu는 tbl_company_list에 없으므로 제외
            $localBuilder = $db->table('tbl_company_list');
            $localBuilder->select('comp_memo, comp_dong, comp_addr_detail');
            $localBuilder->where('comp_code', $compCode);
            $localQuery = $localBuilder->get();
            
            if ($localQuery !== false) {
                $localInfo = $localQuery->getRowArray();
                if ($localInfo) {
                    // 로컬 DB 정보를 병합 (API 정보 우선, 로컬 DB는 보조)
                    $companyInfo['comp_memo'] = $localInfo['comp_memo'] ?? '';
                    $companyInfo['comp_dong'] = $localInfo['comp_dong'] ?? '';
                    $companyInfo['comp_addr_detail'] = $localInfo['comp_addr_detail'] ?? '';
                }
            }
            
            // sido, gungu는 API 좌표 정보에서 가져오거나 폼에서만 사용 (로컬 DB에 저장하지 않음)
            // 기본값으로 빈 문자열 설정
            if (!isset($companyInfo['sido'])) {
                $companyInfo['sido'] = '';
            }
            if (!isset($companyInfo['gungu'])) {
                $companyInfo['gungu'] = '';
            }
            
            // representative_name은 API에서만 관리하므로 로컬 DB 조회하지 않음
            // (tbl_company_list에는 comp_owner만 있고 representative_name 필드는 없음)
            
            // 거래구분은 API의 credit 값만 사용 (로컬 DB 조회하지 않음)
            // API에서 가져오지 못한 경우 기본값 설정
            if (empty($companyInfo['comp_type']) && empty($companyInfo['comp_gbn'])) {
                $companyInfo['comp_type'] = '';
                $companyInfo['comp_gbn'] = '';
            }
            
            // tbl_company_env에서 배송조회 제한 정보 조회
            $envBuilder = $db->table('tbl_company_env');
            $envBuilder->select('env1, env2, env3, env5');
            $envBuilder->where('comp_code', $compCode);
            $envQuery = $envBuilder->get();
            
            if ($envQuery !== false) {
                $envInfo = $envQuery->getRowArray();
                if ($envInfo) {
                    // env 필드를 일반 필드명으로 매핑
                    // env1: 조회권한 (delivery_inquiry_permission) - 값: 1(전체조회), 3(본인오더조회)
                    // env2: 요금조회 (fee_inquiry) - 값: 1(전체조회), 3(기본요금조회)
                    // env3: 요금계산방식 (fee_calc_method) - 값: 1(동대동방식), 3(거리요금방식)
                    // env5: 오더승인여부 (order_approval_type) - 값: 1(일반방식), 3(관리자승인방식)
                    $companyInfo['delivery_inquiry_permission'] = $envInfo['env1'] ?? '1';
                    $companyInfo['fee_inquiry'] = $envInfo['env2'] ?? '1';
                    $companyInfo['fee_calc_method'] = $envInfo['env3'] ?? '1';
                    $companyInfo['order_approval_type'] = $envInfo['env5'] ?? '1';
                } else {
                    // 기본값 설정 (stnlogis와 동일하게 숫자 값 사용)
                    $companyInfo['delivery_inquiry_permission'] = '1';
                    $companyInfo['fee_inquiry'] = '1';
                    $companyInfo['fee_calc_method'] = '1';
                    $companyInfo['order_approval_type'] = '1';
                }
            } else {
                // 기본값 설정 (stnlogis와 동일하게 숫자 값 사용)
                $companyInfo['delivery_inquiry_permission'] = '1';
                $companyInfo['fee_inquiry'] = '1';
                $companyInfo['fee_calc_method'] = '1';
                $companyInfo['order_approval_type'] = '1';
            }
        }
        
        if (empty($companyInfo)) {
            return redirect()->to('/admin/company-list-cc')->with('error', '거래처를 찾을 수 없습니다.');
        }
        
        $data = [
            'title' => '거래처 수정',
            'content_header' => [
                'title' => '거래처 수정',
                'description' => '거래처 정보를 수정할 수 있습니다.',
                'back_button' => [
                    'label' => '거래처 목록',
                    'url' => 'admin/company-list-cc'
                ]
            ],
            'company_info' => $companyInfo,
            'comp_code' => $compCode
        ];
        
        return view('admin/company-edit', $data);
    }

    /**
     * 거래처 저장 (user_type = 3 또는 user_class = 1 접근 가능)
     */
    public function companySave()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: user_type = 3 (콜센터 관리자) 또는 user_class = 1 (거래처 관리자)
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        $userClass = session()->get('user_class');
        $userId = session()->get('user_id');
        $userCompany = session()->get('user_company');
        
        // user_class가 세션에 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata' && $userId) {
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_class, user_company');
            $userBuilder->where('user_id', $userId);
            $userQuery = $userBuilder->get();
            if ($userQuery !== false) {
                $userResult = $userQuery->getRowArray();
                if ($userResult) {
                    if (isset($userResult['user_class'])) {
                        $userClass = $userResult['user_class'];
                    }
                    if (isset($userResult['user_company'])) {
                        $userCompany = $userResult['user_company'];
                    }
                }
            }
        }
        
        if ($loginType !== 'daumdata' || ($userType != '3' && $userClass != '1')) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }
        
        // POST 데이터
        $compCode = $this->request->getPost('comp_code');
        
        // user_class = 1일 때는 본인 거래처만 수정 가능
        if ($userClass == '1') {
            if (empty($userCompany)) {
                return redirect()->to('/')->with('error', '거래처 정보를 찾을 수 없습니다.');
            }
            // comp_code가 본인 거래처가 아니면 거부
            if (empty($compCode) || $compCode !== $userCompany) {
                return redirect()->to('/admin/company-list-cc')->with('error', '접근 권한이 없습니다.');
            }
        }
        $compName = $this->request->getPost('comp_name');
        $compOwner = $this->request->getPost('comp_owner');
        $compTel = $this->request->getPost('comp_tel');
        $compAddr = $this->request->getPost('comp_addr');
        $compAddrDetail = $this->request->getPost('comp_addr_detail');
        $compMemo = $this->request->getPost('comp_memo');
        $compType = $this->request->getPost('comp_type');
        $deliveryInquiryPermission = $this->request->getPost('delivery_inquiry_permission');
        $feeCalcMethod = $this->request->getPost('fee_calc_method');
        $orderApprovalType = $this->request->getPost('order_approval_type');
        $representativeName = $this->request->getPost('representative_name');
        $feeInquiry = $this->request->getPost('fee_inquiry');
        $compDong = $this->request->getPost('comp_dong');
        $sido = $this->request->getPost('sido');
        $gungu = $this->request->getPost('gungu');
        $businessNumber = $this->request->getPost('business_number');
        
        // 유효성 검사
        if (empty($compCode)) {
            return redirect()->back()->with('error', '거래처 코드가 필요합니다.');
        }
        
        if (empty($compName)) {
            return redirect()->back()->with('error', '업체명이 필요합니다.');
        }
        
        if (empty($representativeName)) {
            return redirect()->back()->with('error', '대표자명이 필요합니다.');
        }
        
        if (empty($compTel)) {
            return redirect()->back()->with('error', '전화번호가 필요합니다.');
        }
        
        if (empty($compAddr)) {
            return redirect()->back()->with('error', '주소가 필요합니다.');
        }
        
        // 로컬 DB에 설정 정보만 저장/업데이트 (기본 정보는 API에서 관리)
        $db = \Config\Database::connect();
        
        // tbl_company_list에 기본 정보 저장/업데이트
        $checkBuilder = $db->table('tbl_company_list');
        $checkBuilder->select('comp_code');
        $checkBuilder->where('comp_code', $compCode);
        $checkQuery = $checkBuilder->get();
        $companyExists = ($checkQuery !== false && $checkQuery->getNumRows() > 0);
        
        // 기본 정보는 모두 API에서만 관리하므로 로컬 DB에 저장하지 않음
        // (거래처명, 대표자명, 담당자명, 전화번호, 주소, 거래구분, 사��자번호 등)
        // 로컬 DB에는 설정 정보만 저장 (comp_memo, comp_dong, comp_addr_detail)
        $companyUpdateData = [
            'comp_code' => $compCode, // INSERT 시 필요 (키값)
            'comp_memo' => $compMemo ?? '', // 메모 (로컬 전용)
            'comp_dong' => $compDong ?? '', // 동 정보 (로컬 전용)
            'comp_addr_detail' => $compAddrDetail ?? '' // 상�� 주소 (로컬 전용)
            // comp_type, representative_name, comp_name, comp_tel, comp_addr, business_number 등은 API에서만 관리
            // sido, gungu는 tbl_company_list에 없으므로 저장하지 않음
        ];
        
        $companyBuilder = $db->table('tbl_company_list');
        
        if ($companyExists) {
            // UPDATE: 설정 정보만 업데이트
            $companyBuilder->where('comp_code', $compCode);
            $companyResult = $companyBuilder->update($companyUpdateData);
        } else {
            // INSERT: 새 레코드 생성 (comp_code만 필수, 나머지는 설정 정보)
            $companyResult = $companyBuilder->insert($companyUpdateData);
        }
        
        // tbl_company_env에 배송조회 제한 정보 저장/���데이트
        $envCheckBuilder = $db->table('tbl_company_env');
        $envCheckBuilder->select('comp_env_idx, comp_code');
        $envCheckBuilder->where('comp_code', $compCode);
        $envCheckQuery = $envCheckBuilder->get();
        $envExists = ($envCheckQuery !== false && $envCheckQuery->getNumRows() > 0);
        
        $envUpdateData = [
            'comp_code' => $compCode,
            'env1' => $deliveryInquiryPermission ?? '1',  // 조회권한: 1(전체조회), 3(본인오더조회)
            'env2' => $feeInquiry ?? '1',                  // 요금조회: 1(전체조회), 3(기본요금조회)
            'env3' => $feeCalcMethod ?? '1',              // 요금계산방식: 1(동대동방식), 3(거리요금방식)
            'env5' => $orderApprovalType ?? '1'            // 오더승인여부: 1(일반방식), 3(관리자승인방식)
        ];
        
        $envBuilder = $db->table('tbl_company_env');
        
        if ($envExists) {
            // UPDATE: 배송조회 제한 정보 업데이트
            $envBuilder->where('comp_code', $compCode);
            $envResult = $envBuilder->update($envUpdateData);
        } else {
            // INSERT: 새 레코드 생성
            // comp_idx는 0으로 설정 (필요시 나중에 업데이트)
            $envUpdateData['comp_idx'] = 0;
            $envResult = $envBuilder->insert($envUpdateData);
        }
        
        if ($companyResult && $envResult) {
            return redirect()->to('/admin/company-list-cc')->with('success', '거래처 정보가 수정되었습니다.');
        } else {
            return redirect()->back()->with('error', '거래처 정보 수정에 실패했습니다.');
        }
    }

    /**
     * 거래처별 고객 리스트 (user_type = 3 접근 가능)
     */
    public function companyCustomerList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: user_type = 3 (콜센터 관리자) 또는 user_class = 1 (거래처 관리자)
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        $userClass = session()->get('user_class');
        $userId = session()->get('user_id');
        $userCompany = session()->get('user_company');
        
        // user_class가 세션에 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata' && $userId) {
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_class, user_company');
            $userBuilder->where('user_id', $userId);
            $userQuery = $userBuilder->get();
            if ($userQuery !== false) {
                $userResult = $userQuery->getRowArray();
                if ($userResult) {
                    if (isset($userResult['user_class'])) {
                        $userClass = $userResult['user_class'];
                    }
                    if (isset($userResult['user_company'])) {
                        $userCompany = $userResult['user_company'];
                    }
                }
            }
        }
        
        if ($loginType !== 'daumdata' || ($userType != '3' && $userClass != '1')) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }
        
        // comp_code 파라미터 (권한에 따라 다르게 처리)
        $compCode = $this->request->getGet('comp_code');

        // 우선순위: user_type=3이 있으면 콜센터 관리자로 처리, 없으면 user_class=1로 거래처 관리자 처리
        if ($userType == '3') {
            // user_type = 3 (콜센터 관리자): 모든 거래처 조회 가능
            if (empty($compCode)) {
                return redirect()->to('/admin/company-list-cc')->with('error', '거래처 코드가 필요합니다.');
            }
        } else {
            // user_class = 1 (거래처 관리자): 본인 거래처만 조회 가능
            if (empty($userCompany)) {
                return redirect()->to('/')->with('error', '거래처 정보를 찾을 수 없습니다.');
            }
            // comp_code가 없거나 본인 거래처가 아니면 본인 거래처로 설정
            if (empty($compCode) || $compCode !== $userCompany) {
                $compCode = $userCompany;
            }
        }
        
        // user_cc_idx 조회 (user_type = 3일 때 필요)
        $db = \Config\Database::connect();
        $userCcIdx = session()->get('user_cc_idx');

        if ($userType == '3') {
            // 세션에 없으면 DB에서 조회
            if (empty($userCcIdx)) {
                $currentUserId = session()->get('user_id');
                if ($currentUserId) {
                    $userBuilder = $db->table('tbl_users_list u');
                    $userBuilder->select('c.cc_idx');
                    $userBuilder->join('tbl_company_list c', 'u.user_company = c.comp_code', 'left');
                    $userBuilder->where('u.user_id', (string)$currentUserId);
                    $userQuery = $userBuilder->get();

                    if ($userQuery !== false) {
                        $userResult = $userQuery->getRowArray();
                        if ($userResult && !empty($userResult['cc_idx'])) {
                            $userCcIdx = (int)$userResult['cc_idx'];
                            session()->set('user_cc_idx', $userCcIdx);
                        }
                    }
                }
            }

            // user_type=3일 때는 반드시 cc_idx가 필요
            if (empty($userCcIdx)) {
                return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
            }
        }
        
        // 콜센터 정보 조회 (권한에 따라 다르게 처리)
        $ccInfo = null;
        if ($userType == '3') {
            // user_type = 3 (콜센터 관리자): user_cc_idx로 cc_code 조회
            $ccBuilder = $db->table('tbl_cc_list');
            $ccBuilder->select('cc_code, cc_name');
            $ccBuilder->where('idx', $userCcIdx);
            $ccQuery = $ccBuilder->get();
            $ccInfo = $ccQuery !== false ? $ccQuery->getRowArray() : null;
        } else {
            // user_class = 1 (거래처 관리자): comp_code로 cc_idx 조회 후 cc_code 조회
            $compBuilder = $db->table('tbl_company_list');
            $compBuilder->select('cc_idx');
            $compBuilder->where('comp_code', $compCode);
            $compQuery = $compBuilder->get();

            if ($compQuery !== false) {
                $compResult = $compQuery->getRowArray();
                if ($compResult && !empty($compResult['cc_idx'])) {
                    $ccBuilder = $db->table('tbl_cc_list');
                    $ccBuilder->select('cc_code, cc_name');
                    $ccBuilder->where('idx', $compResult['cc_idx']);
                    $ccQuery = $ccBuilder->get();

                    if ($ccQuery !== false) {
                        $ccInfo = $ccQuery->getRowArray();
                    }
                }
            }
        }
        
        if (empty($ccInfo) || empty($ccInfo['cc_code'])) {
            return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
        }
        
        // API 정보 조회
        $insungApiListModel = new \App\Models\InsungApiListModel();
        $apiInfo = $insungApiListModel->getApiInfoByCode($ccInfo['cc_code']);
        
        if (empty($apiInfo)) {
            return redirect()->to('/')->with('error', 'API 정보를 찾을 수 없습니다.');
        }
        
        $mCode = $apiInfo['mcode'] ?? '';
        $ccCodeApi = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';
        $apiIdx = $apiInfo['idx'] ?? null;
        
        // 거래처 정보 조회 (인성 API)
        $insungApiService = new \App\Libraries\InsungApiService();
        $apiResult = $insungApiService->getCompanyList($mCode, $ccCodeApi, $token, $compCode, '', 1, 1, $apiIdx);
        
        $companyInfo = null;
        $item = null;
        
        // API 응답 구조 안전하게 파싱
        if ($apiResult !== false) {
            // 응답 코드 확인
            $code = '';
            if (is_object($apiResult) && isset($apiResult->Result)) {
                $resultArray = is_array($apiResult->Result) ? $apiResult->Result : [$apiResult->Result];
                if (isset($resultArray[0]->result_info[0]->code)) {
                    $code = $resultArray[0]->result_info[0]->code;
                } elseif (isset($resultArray[0]->code)) {
                    $code = $resultArray[0]->code;
                }
                
                // 성공 코드이고 데이터가 있는 경우
                if ($code === '1000' && isset($resultArray[2])) {
                    if (isset($resultArray[2]->items[0]->item)) {
                        $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
                        if (!empty($items) && isset($items[0])) {
                            $item = $items[0];
                        }
                    }
                }
            } elseif (is_array($apiResult)) {
                if (isset($apiResult[0]->code)) {
                    $code = $apiResult[0]->code;
                } elseif (isset($apiResult[0]['code'])) {
                    $code = $apiResult[0]['code'];
                }
                
                // 성공 코드이고 데이터가 있는 경우
                if ($code === '1000' && isset($apiResult[2])) {
                    if (isset($apiResult[2]->items[0]->item)) {
                        $items = is_array($apiResult[2]->items[0]->item) ? $apiResult[2]->items[0]->item : [$apiResult[2]->items[0]->item];
                        if (!empty($items) && isset($items[0])) {
                            $item = $items[0];
                        }
                    }
                }
            }
            
            log_message('debug', "Admin::companyCustomerList - API response code: {$code}, item found: " . ($item ? 'yes' : 'no') . ", comp_code: {$compCode}");
        } else {
            log_message('error', "Admin::companyCustomerList - API call failed for comp_code: {$compCode}");
        }
        
        if ($item) {
            $companyInfo = [
                'comp_code' => $compCode,
                'comp_name' => $item->corp_name ?? '',
                'comp_owner' => $item->owner ?? '',
                'comp_tel' => $item->tel_no ?? '',
                'comp_addr' => $item->adddress ?? $item->address ?? '',
                'comp_addr_detail' => '' // API에서 제공하지 않음
            ];
            
            // 로컬 DB에서 추가 정보 조회 (comp_addr_detail 등)
            $localBuilder = $db->table('tbl_company_list');
            $localBuilder->select('comp_addr_detail');
            $localBuilder->where('comp_code', $compCode);
            $localQuery = $localBuilder->get();
            
            if ($localQuery !== false) {
                $localInfo = $localQuery->getRowArray();
                if ($localInfo && !empty($localInfo['comp_addr_detail'])) {
                    $companyInfo['comp_addr_detail'] = $localInfo['comp_addr_detail'];
                }
            }
        }
        
        if (empty($companyInfo)) {
            log_message('error', "Admin::companyCustomerList - Company not found in API for comp_code: {$compCode}");
            return redirect()->to('/admin/company-list-cc')->with('error', '거래처를 찾을 수 없습니다.');
        }
        
        // 검색 조건 (단일 검색 필드: 아이디, 이름, 부서, 전화번호)
        $searchKeyword = $this->request->getGet('search_keyword') ?? '';
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = 20;
        
        // tbl_users_list에서 user_company = comp_code인 사용자 조회
        $userBuilder = $db->table('tbl_users_list');
        $userBuilder->select('user_id, user_pass, user_name, user_dept, user_tel1, user_tel2, user_addr, user_addr_detail, user_class, user_memo');
        $userBuilder->where('user_company', $compCode);
        
        // 검색 조건 적용 (아이디, 이름, 부서, 전화번호로 검색)
        if (!empty($searchKeyword)) {
            $userBuilder->groupStart()
                ->like('user_id', $searchKeyword)
                ->orLike('user_name', $searchKeyword)
                ->orLike('user_dept', $searchKeyword)
                ->orLike('user_tel1', $searchKeyword)
                ->orLike('user_tel2', $searchKeyword)
                ->groupEnd();
        }
        
        // 전체 개수 조회
        $totalCount = $userBuilder->countAllResults(false);
        
        // 페이징 적용
        $userBuilder->orderBy('user_id', 'ASC');
        $userBuilder->limit($perPage, ($page - 1) * $perPage);
        $userQuery = $userBuilder->get();
        
        $userList = [];
        if ($userQuery !== false) {
            // 암호화된 필드 복호화
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            $encryptedFields = ['user_pass', 'user_name', 'user_tel1', 'user_tel2'];
            $userList = $userQuery->getResultArray();
            
            // 각 사용자 정보 복호화
            foreach ($userList as &$user) {
                $user = $encryptionHelper->decryptFields($user, $encryptedFields);
            }
            unset($user);
        }
        
        // 페이징 계산
        helper('pagination');
        $pagination = calculatePagination($totalCount, $page, $perPage);
        
        // user_class 라벨 매핑 (고객 목록용)
        $userClassLabels = [
            '1' => '관리자',
            '3' => '부서장',
            '4' => '정산담당자',
            '5' => '일반'
        ];
        
        // 사용자 데이터 포맷팅
        $formattedUserList = [];
        foreach ($userList as $user) {
            $formattedUser = $user;
            $userClass = $user['user_class'] ?? '5';
            $formattedUser['user_class_label'] = $userClassLabels[$userClass] ?? '일반 고객';
            $formattedUserList[] = $formattedUser;
        }
        
        $data = [
            'title' => '고객관리 - ' . ($companyInfo['comp_name'] ?? ''),
            'content_header' => [
                'title' => '고객관리',
                'description' => '거래처: ' . ($companyInfo['comp_name'] ?? '') . ' (' . $compCode . ')',
                'action_button' => [
                    'label' => '+ 신규고객 등록',
                    'url' => 'admin/company-customer-form?comp_code=' . urlencode($compCode) . '&mode=add'
                ]
            ],
            'company_info' => $companyInfo,
            'user_list' => $formattedUserList,
            'search_keyword' => $searchKeyword,
            'pagination' => $pagination,
            'total_count' => $totalCount,
            'comp_code' => $compCode
        ];
        
        return view('admin/company-customer-list', $data);
    }

    /**
     * 고객 등록/수정 폼 (user_type = 3 접근 가능)
     */
    public function companyCustomerForm()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: user_type = 3 (콜센터 관리자) 또는 user_class = 1 (거래처 관리자)
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        $userClass = session()->get('user_class');
        $userId = session()->get('user_id');
        $userCompany = session()->get('user_company');
        
        // user_class가 세션에 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata' && $userId) {
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_class, user_company');
            $userBuilder->where('user_id', $userId);
            $userQuery = $userBuilder->get();
            if ($userQuery !== false) {
                $userResult = $userQuery->getRowArray();
                if ($userResult) {
                    if (isset($userResult['user_class'])) {
                        $userClass = $userResult['user_class'];
                    }
                    if (isset($userResult['user_company'])) {
                        $userCompany = $userResult['user_company'];
                    }
                }
            }
        }
        
        if ($loginType !== 'daumdata' || ($userType != '3' && $userClass != '1')) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }
        
        // 파라미터
        $compCode = $this->request->getGet('comp_code');
        $mode = $this->request->getGet('mode') ?? 'add'; // add 또는 edit
        $userIdParam = $this->request->getGet('user_id') ?? '';
        $searchKeyword = $this->request->getGet('search_keyword') ?? ''; // 검색 파라미터 유지용
        
        // user_class = 1일 때는 본인 거래처만 조회 가능
        if ($userClass == '1') {
            if (empty($userCompany)) {
                return redirect()->to('/')->with('error', '거래처 정보를 찾을 수 없습니다.');
            }
            // comp_code가 없거나 본인 거래처가 아니면 본인 거래처로 설정
            if (empty($compCode) || $compCode !== $userCompany) {
                $compCode = $userCompany;
            }
        } elseif (empty($compCode)) {
            return redirect()->to('/admin/company-list-cc')->with('error', '거래처 코드가 필요합니다.');
        }
        
        if ($mode === 'edit' && empty($userIdParam)) {
            return redirect()->to('/admin/company-customer-list?comp_code=' . urlencode($compCode))->with('error', '사용자 ID가 필요합니다.');
        }
        
        // 거래처 정보 조회 (인성 API)
        $db = \Config\Database::connect();
        $userCcIdx = session()->get('user_cc_idx');
        
        // user_type = 3일 때만 user_cc_idx 조회
        if ($userType == '3' && empty($userCcIdx)) {
            $currentUserId = session()->get('user_id');
            if ($currentUserId) {
                $userBuilder = $db->table('tbl_users_list u');
                $userBuilder->select('c.cc_idx');
                $userBuilder->join('tbl_company_list c', 'u.user_company = c.comp_code', 'left');
                $userBuilder->where('u.user_id', (string)$currentUserId);
                $userQuery = $userBuilder->get();
                
                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult && !empty($userResult['cc_idx'])) {
                        $userCcIdx = (int)$userResult['cc_idx'];
                        session()->set('user_cc_idx', $userCcIdx);
                    }
                }
            }
        }
        
        if (empty($userCcIdx)) {
            return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
        }
        
        // 콜센터 정보 조회
        $ccBuilder = $db->table('tbl_cc_list');
        $ccBuilder->select('cc_code, cc_name');
        $ccBuilder->where('idx', $userCcIdx);
        $ccQuery = $ccBuilder->get();
        $ccInfo = $ccQuery !== false ? $ccQuery->getRowArray() : null;
        
        if (empty($ccInfo) || empty($ccInfo['cc_code'])) {
            return redirect()->to('/')->with('error', '콜센터 정보를 찾을 수 없습니다.');
        }
        
        // API 정보 조회
        $insungApiListModel = new \App\Models\InsungApiListModel();
        $apiInfo = $insungApiListModel->getApiInfoByCode($ccInfo['cc_code']);
        
        if (empty($apiInfo)) {
            return redirect()->to('/')->with('error', 'API 정보를 찾을 수 없습니다.');
        }
        
        $mCode = $apiInfo['mcode'] ?? '';
        $ccCodeApi = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';
        $apiIdx = $apiInfo['idx'] ?? null;
        
        // 거래처 정보 조회 (인성 API)
        $insungApiService = new \App\Libraries\InsungApiService();
        $apiResult = $insungApiService->getCompanyList($mCode, $ccCodeApi, $token, $compCode, '', 1, 1, $apiIdx);
        
        $companyInfo = null;
        if ($apiResult !== false && isset($apiResult->Result[2]->items[0]->item[0])) {
            $item = $apiResult->Result[2]->items[0]->item[0];
            $companyInfo = [
                'comp_code' => $compCode,
                'comp_name' => $item->corp_name ?? ''
            ];
        }
        
        if (empty($companyInfo)) {
            return redirect()->to('/admin/company-list-cc')->with('error', '거래처를 찾을 수 없습니다.');
        }
        
        // 고객 정보 (수정 모드일 때)
        $customerInfo = null;
        if ($mode === 'edit' && !empty($userIdParam)) {
            // DB에서 기본 정보 조회 (URL 파라미터의 user_id 사용)
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_id, user_pass, user_name, user_dept, user_tel1, user_tel2, user_addr, user_addr_detail, user_class, user_memo, user_ccode, user_sido, user_gungu, user_dong');
            $userBuilder->where('user_id', $userIdParam);
            $userBuilder->where('user_company', $compCode);
            $userQuery = $userBuilder->get();
            
            if ($userQuery !== false) {
                $customerInfo = $userQuery->getRowArray();
                
                // 암호화된 필드 복호화
                if (!empty($customerInfo)) {
                    $encryptionHelper = new \App\Libraries\EncryptionHelper();
                    $encryptedFields = ['user_pass', 'user_name', 'user_tel1', 'user_tel2'];
                    $customerInfo = $encryptionHelper->decryptFields($customerInfo, $encryptedFields);
                }
            }
            
            // 인성 API로 상세 정보 조회
            if (!empty($customerInfo)) {
                // DB에서 조회한 주소 정보를 기본값으로 설정
                $customerInfo['user_addr1'] = $customerInfo['user_addr'] ?? '';
                $customerInfo['user_addr2'] = $customerInfo['user_addr_detail'] ?? '';
                
                // DB에서 조회한 시도, 군구, 동 정보도 포함
                $customerInfo['sido'] = $customerInfo['user_sido'] ?? '';
                $customerInfo['gungu'] = $customerInfo['user_gungu'] ?? '';
                $customerInfo['user_dong'] = $customerInfo['user_dong'] ?? '';
                
                $memberResult = $insungApiService->getMemberDetail($mCode, $ccCodeApi, $token, $userIdParam, $apiIdx);
                
                if ($memberResult !== false && isset($memberResult->Result[1])) {
                    $member = $memberResult->Result[1];
                    $customerInfo['user_dept'] = $member->dept_name ?? $customerInfo['user_dept'];
                    $customerInfo['user_name'] = $member->charge_name ?? $customerInfo['user_name'];
                    $customerInfo['user_tel1'] = $member->tel_number ?? $customerInfo['user_tel1'];
                    
                    // 인성 API에서 주소 정보가 있으면 우선 사용
                    $apiAddr1 = trim(($member->sido ?? '') . ' ' . ($member->gugun ?? '') . ' ' . ($member->dong_name ?? '') . ' ' . ($member->ri ?? ''));
                    if (!empty($apiAddr1)) {
                        $customerInfo['user_addr1'] = $apiAddr1;
                    }
                    if (!empty($member->location)) {
                        $customerInfo['user_addr2'] = $member->location;
                    }
                    if (!empty($member->dong_name)) {
                        $customerInfo['user_dong'] = $member->dong_name;
                    }
                    if (!empty($member->sido)) {
                        $customerInfo['sido'] = $member->sido;
                    }
                    if (!empty($member->gugun)) {
                        $customerInfo['gungu'] = $member->gugun;
                    }
                }
            }
        }
        
        // 권한 목록 조회 (활성화된 것만)
        $classInfoModel = new \App\Models\ClassInfoModel();
        $activeClasses = $classInfoModel->getActiveClasses();

        $data = [
            'title' => ($mode === 'edit' ? '고객수정' : '고객등록'),
            'content_header' => [
                'title' => ($mode === 'edit' ? '고객수정' : '고객등록'),
                'description' => '거래처: ' . ($companyInfo['comp_name'] ?? '')
            ],
            'company_info' => $companyInfo,
            'customer_info' => $customerInfo,
            'mode' => $mode,
            'comp_code' => $compCode,
            'user_id' => $userIdParam,
            'search_keyword' => $searchKeyword, // 검색 파라미터 전달
            'active_classes' => $activeClasses // DB에서 불러온 활성 권한 목록
        ];

        return view('admin/company-customer-form', $data);
    }

    /**
     * 고객 등록/수정 저장 (user_type = 3 접근 가능)
     */
    public function companyCustomerSave()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        // 권한 체크: user_type = 3 (콜센터 관리자) 또는 user_class = 1 (거래처 관리자)
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        $userClass = session()->get('user_class');
        $userCompany = session()->get('user_company');
        
        // user_class가 세션에 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata') {
            $currentUserId = session()->get('user_id');
            if ($currentUserId) {
                $db = \Config\Database::connect();
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->select('user_class, user_company');
                $userBuilder->where('user_id', $currentUserId);
                $userQuery = $userBuilder->get();
                if ($userQuery !== false) {
                    $userResult = $userQuery->getRowArray();
                    if ($userResult) {
                        if (isset($userResult['user_class'])) {
                            $userClass = $userResult['user_class'];
                        }
                        if (isset($userResult['user_company'])) {
                            $userCompany = $userResult['user_company'];
                        }
                    }
                }
            }
        }
        
        if ($loginType !== 'daumdata' || ($userType != '3' && $userClass != '1')) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }
        
        // POST 데이터
        $mode = $this->request->getPost('mode') ?? 'add';
        $compCode = $this->request->getPost('comp_code');
        $userId = $this->request->getPost('user_id');
        $userPass = $this->request->getPost('user_pass');
        $userPass2 = $this->request->getPost('user_pass2');
        $userName = $this->request->getPost('user_name');
        $userDept = $this->request->getPost('user_dept');
        $userTel1 = $this->request->getPost('user_tel1');
        $userTel2 = $this->request->getPost('user_tel2');
        $userAddr1 = $this->request->getPost('user_addr1');
        $userAddr2 = $this->request->getPost('user_addr2');
        $userDong = $this->request->getPost('user_dong');
        $userMemo = $this->request->getPost('user_memo');
        $userClass = $this->request->getPost('user_class') ?? '5';
        $sido = $this->request->getPost('sido');
        $gungu = $this->request->getPost('gungu');
        
        // mode가 'editok'인 경우 'edit'로 변환 (폼에서 editok로 전송됨)
        $isEditMode = ($mode === 'edit' || $mode === 'editok');
        
        // 유효성 검사
        if (empty($compCode)) {
            return redirect()->back()->with('error', '거래처 코드가 필요합니다.');
        }
        
        if (empty($userId)) {
            return redirect()->back()->with('error', '아이디가 필요합니다.');
        }
        
        if (empty($userName)) {
            return redirect()->back()->with('error', '담당자명이 필요합니다.');
        }
        
        if (empty($userTel1)) {
            return redirect()->back()->with('error', '전화번호1이 필요합니다.');
        }
        
        if (empty($userAddr1)) {
            return redirect()->back()->with('error', '주소가 필요합니다.');
        }
        
        // 비밀번호 검사
        if (!$isEditMode) {
            if (empty($userPass)) {
                return redirect()->back()->with('error', '비밀번호가 필요합니다.');
            }
            if (strlen($userPass) < 5 || strlen($userPass) > 30) {
                return redirect()->back()->with('error', '비밀번호는 5자리 이상 30자리 이하로 입력하세요.');
            }
        } else {
            if (!empty($userPass) && (strlen($userPass) < 5 || strlen($userPass) > 30)) {
                return redirect()->back()->with('error', '비밀번호는 5자리 이상 30자리 이하로 입력하세요.');
            }
        }
        
        if ($userPass !== $userPass2) {
            return redirect()->back()->with('error', '입력된 두 비밀번호가 일치하지 않습니다.');
        }
        
        if ($userName === $userPass) {
            return redirect()->back()->with('error', '고객명과 비밀번호를 다르게 입력하세요.');
        }
        
        if ($userId === $userPass) {
            return redirect()->back()->with('error', '아이디와 비밀번호를 다르게 입력하세요.');
        }
        
        // user_class = 1일 때는 본인 거래처만 수정 가능
        if ($userClass == '1') {
            if (empty($userCompany)) {
                return redirect()->back()->with('error', '거래처 정보를 찾을 수 없습니다.');
            }
            if ($compCode !== $userCompany) {
                return redirect()->back()->with('error', '본인 거래처만 수정할 수 있습니다.');
            }
        }
        
        // API 정보 조회
        $db = \Config\Database::connect();
        $userCcIdx = session()->get('user_cc_idx');
        
        if ($userClass == '1') {
            // user_class = 1일 때는 comp_code로 cc_idx ��회 후 cc_code 조회
            $compBuilder = $db->table('tbl_company_list');
            $compBuilder->select('cc_idx');
            $compBuilder->where('comp_code', $compCode);
            $compQuery = $compBuilder->get();
            
            if ($compQuery !== false) {
                $compResult = $compQuery->getRowArray();
                if ($compResult && !empty($compResult['cc_idx'])) {
                    $ccBuilder = $db->table('tbl_cc_list');
                    $ccBuilder->select('cc_code');
                    $ccBuilder->where('idx', $compResult['cc_idx']);
                    $ccQuery = $ccBuilder->get();
                    $ccInfo = $ccQuery !== false ? $ccQuery->getRowArray() : null;
                } else {
                    $ccInfo = null;
                }
            } else {
                $ccInfo = null;
            }
        } else {
            // user_type = 3일 때는 기존 로직 사용
            if (empty($userCcIdx)) {
                $currentUserId = session()->get('user_id');
                if ($currentUserId) {
                    $userBuilder = $db->table('tbl_users_list u');
                    $userBuilder->select('c.cc_idx');
                    $userBuilder->join('tbl_company_list c', 'u.user_company = c.comp_code', 'left');
                    $userBuilder->where('u.user_id', (string)$currentUserId);
                    $userQuery = $userBuilder->get();
                    
                    if ($userQuery !== false) {
                        $userResult = $userQuery->getRowArray();
                        if ($userResult && !empty($userResult['cc_idx'])) {
                            $userCcIdx = (int)$userResult['cc_idx'];
                            session()->set('user_cc_idx', $userCcIdx);
                        }
                    }
                }
            }
            
            $ccBuilder = $db->table('tbl_cc_list');
            $ccBuilder->select('cc_code');
            $ccBuilder->where('idx', $userCcIdx);
            $ccQuery = $ccBuilder->get();
            $ccInfo = $ccQuery !== false ? $ccQuery->getRowArray() : null;
        }
        
        if (empty($ccInfo) || empty($ccInfo['cc_code'])) {
            return redirect()->back()->with('error', '콜센터 정보를 찾을 수 없습니다.');
        }
        
        $insungApiListModel = new \App\Models\InsungApiListModel();
        $apiInfo = $insungApiListModel->getApiInfoByCode($ccInfo['cc_code']);
        
        if (empty($apiInfo)) {
            return redirect()->back()->with('error', 'API 정보를 찾을 수 없습니다.');
        }
        
        $mCode = $apiInfo['mcode'] ?? '';
        $ccCodeApi = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';
        $apiIdx = $apiInfo['idx'] ?? null;
        
        $insungApiService = new \App\Libraries\InsungApiService();
        
        // 거래처 정보 조회 (거래처명에서 cc_code prefix 제거)
        $companyBuilder = $db->table('tbl_company_list');
        $companyBuilder->select('comp_name');
        $companyBuilder->where('comp_code', $compCode);
        $companyQuery = $companyBuilder->get();
        $companyRow = $companyQuery !== false ? $companyQuery->getRowArray() : null;
        
        $compName = $companyRow['comp_name'] ?? '';
        // cc_code prefix 제거 (예: "ROD_구찌" -> "구찌")
        if (!empty($compName) && strpos($compName, $ccInfo['cc_code'] . '_') === 0) {
            $compName = substr($compName, strlen($ccInfo['cc_code'] . '_'));
        }
        
        // 동명이 없으면 "미지정"으로 설정
        if (empty($userDong)) {
            $userDong = '미지정';
        }
        
        // 좌표 추출 (주소가 있을 경우)
        $extLon = '';
        $extLat = '';
        $userAddrCk = 0;
        
        if (!empty($userAddr1)) {
            $coordResult = $insungApiService->getAddressCoordinates($mCode, $ccCodeApi, $token, $userAddr1, $apiIdx);
            if ($coordResult && isset($coordResult['lon']) && isset($coordResult['lat'])) {
                $extLon = $coordResult['lon'];
                $extLat = $coordResult['lat'];
                
                // 좌표로 주소 분리
                if (!empty($coordResult['normal_lon']) && !empty($coordResult['normal_lat'])) {
                    $addrFromCoord = $insungApiService->getAddressFromCoordinates($mCode, $ccCodeApi, $token, $coordResult['normal_lat'], $coordResult['normal_lon'], $apiIdx);
                    if ($addrFromCoord) {
                        $sido = $addrFromCoord['sido'] ?? $sido;
                        $gungu = $addrFromCoord['gugun'] ?? $gungu;
                        $userDong = $addrFromCoord['dong'] ?? $userDong;
                        $userAddrDetail = $addrFromCoord['detail'] ?? $userAddr2;
                        $userAddrCk = 1;
                    }
                }
            }
        }
        
        // credit 값 (기본값 3, 추후 거래처 정보에서 가져올 수 있음)
        $credit = '3';
        
        if ($isEditMode) {
            // 수정 모드: 기존 회원 정보 조회하여 credit 값 가져오기
            $existingUserBuilder = $db->table('tbl_users_list');
            $existingUserBuilder->select('user_ccode');
            $existingUserBuilder->where('user_id', $userId);
            $existingUserBuilder->where('user_company', $compCode);
            $existingUserQuery = $existingUserBuilder->get();
            $existingUser = $existingUserQuery !== false ? $existingUserQuery->getRowArray() : null;
            
            $userCcode = $existingUser['user_ccode'] ?? '';
            
            // 기존 회원 정보 조회
            $memberDetailResult = $insungApiService->getMemberDetail($mCode, $ccCodeApi, $token, $userId, $apiIdx);
            $custName = $compName;
            $email = '';
            $location = $userAddr2 ?? '';
            
            $member = null;
            if ($memberDetailResult !== false) {
                // 응답 구조 확인: Result[1] 또는 직접 [1] 형태
                if (isset($memberDetailResult->Result) && isset($memberDetailResult->Result[1])) {
                    $member = $memberDetailResult->Result[1];
                } elseif (isset($memberDetailResult[1])) {
                    $member = $memberDetailResult[1];
                } elseif (is_array($memberDetailResult) && isset($memberDetailResult[1])) {
                    $member = (object)$memberDetailResult[1];
                }
                
                if ($member) {
                    $custName = $member->cust_name ?? $compName;
                    $credit = $member->credit_customer_code ?? $credit;
                    $email = $member->email ?? '';
                    $location = $member->location ?? $userAddr2 ?? '';
                }
            }
            
            // 비밀번호 설정
            $setPass = !empty($userPass) ? $userPass : ($existingUser['user_pass'] ?? '');
            
            // 인성 API 회원 수정
            $modifyResult = $insungApiService->modifyMember(
                $mCode, $ccCodeApi, $token, $userId, $setPass, $custName, $userDong, $userTel1, 
                $credit, $userDept ?? '', $userName, $email, $location, $extLon, $extLat, 
                $userAddr1, $compCode, $apiIdx
            );
            
            if ($modifyResult === false) {
                return redirect()->back()->with('error', '인성 API 회원 수정에 실패했습니다.');
            }
            
            // 응답 코드 확인
            $code = '';
            if (is_object($modifyResult) && isset($modifyResult->code)) {
                $code = $modifyResult->code;
            } elseif (is_array($modifyResult) && isset($modifyResult[0]->code)) {
                $code = $modifyResult[0]->code;
            }
            
            if ($code !== '1000') {
                $msg = '';
                if (is_object($modifyResult) && isset($modifyResult->msg)) {
                    $msg = $modifyResult->msg;
                } elseif (is_array($modifyResult) && isset($modifyResult[0]->msg)) {
                    $msg = $modifyResult[0]->msg;
                }
                return redirect()->back()->with('error', '인성 API 회원 수정 실패: [' . $code . '] ' . $msg);
            }
            
            // 수정 후 c_code 조회하여 업데이트
            $memberDetailResult = $insungApiService->getMemberDetail($mCode, $ccCodeApi, $token, $userId, $apiIdx);
            if ($memberDetailResult !== false) {
                // 응답 구조 확인: Result[1] 또는 직접 [1] 형태
                if (isset($memberDetailResult->Result) && isset($memberDetailResult->Result[1]) && isset($memberDetailResult->Result[1]->c_code)) {
                    $userCcode = $memberDetailResult->Result[1]->c_code;
                } elseif (isset($memberDetailResult[1]) && isset($memberDetailResult[1]->c_code)) {
                    $userCcode = $memberDetailResult[1]->c_code;
                } elseif (is_array($memberDetailResult) && isset($memberDetailResult[1]) && isset($memberDetailResult[1]['c_code'])) {
                    $userCcode = $memberDetailResult[1]['c_code'];
                }
            }
            
            // 암호화 헬퍼 인스턴스 생성
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            $encryptedFields = ['user_pass', 'user_name', 'user_tel1', 'user_tel2'];
            
            // DB 업데이트
            $userData = [
                'user_id' => $userId,
                'user_name' => $userName,
                'user_dept' => $userDept ?? '',
                'user_tel1' => $userTel1,
                'user_tel2' => $userTel2 ?? '',
                'user_addr' => $userAddr1,
                'user_addr_detail' => $userAddr2 ?? '',
                'user_company' => $compCode,
                'user_class' => $userClass,
                'user_memo' => $userMemo ?? '',
                'user_sido' => $sido ?? '',
                'user_gungu' => $gungu ?? '',
                'user_dong' => $userDong ?? '',
                'user_ccode' => $userCcode
            ];
            
            if (!empty($userPass)) {
                $userData['user_pass'] = $userPass;
            }
            
            // 암호화 처리
            $userData = $encryptionHelper->encryptFields($userData, $encryptedFields);
            
            // DB 업데이트 (user_id만으로 조회, user_company는 업데이트 데이터에 포함)
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->where('user_id', $userId);
            $userBuilder->where('user_company', $compCode);
            $updateResult = $userBuilder->update($userData);
            
            // 업데이트 결과 확인
            if ($updateResult === false) {
                log_message('error', "Admin::companyCustomerSave - DB 업데이트 실패: user_id={$userId}, comp_code={$compCode}");
                return redirect()->back()->with('error', 'DB 업데이트에 실패했습니다.');
            }
            
            // 업데이트된 행 수 확인
            $affectedRows = $db->affectedRows();
            if ($affectedRows === 0) {
                log_message('warning', "Admin::companyCustomerSave - 업데이트된 행이 없음: user_id={$userId}, comp_code={$compCode}. user_id만으로 재시도");
                // user_company 조건 없이 user_id만으로 재시도
                $userBuilder2 = $db->table('tbl_users_list');
                $userBuilder2->where('user_id', $userId);
                $updateResult2 = $userBuilder2->update($userData);
                $affectedRows2 = $db->affectedRows();
                
                if ($affectedRows2 === 0) {
                    log_message('warning', "Admin::companyCustomerSave - user_id만으로도 업데이트 실패: user_id={$userId}. INSERT 시도");
                    // 행이 없으면 INSERT 시도 (혹시 모를 경우를 대비)
                    $userData['created_at'] = date('Y-m-d H:i:s');
                    $userData['updated_at'] = date('Y-m-d H:i:s');
                    $insertResult = $db->table('tbl_users_list')->insert($userData);
                    if ($insertResult === false) {
                        log_message('error', "Admin::companyCustomerSave - DB INSERT 실패: user_id={$userId}, comp_code={$compCode}");
                    } else {
                        log_message('info', "Admin::companyCustomerSave - DB INSERT 성공: user_id={$userId}, comp_code={$compCode}");
                    }
                } else {
                    log_message('info', "Admin::companyCustomerSave - DB 업데이트 성공 (user_id만으로): user_id={$userId}, affected_rows={$affectedRows2}");
                }
            } else {
                log_message('info', "Admin::companyCustomerSave - DB 업데이트 성공: user_id={$userId}, comp_code={$compCode}, affected_rows={$affectedRows}");
            }
            
            // user_id로 user_idx 조회 (정산관리부서 저장/삭제용)
            $userIdxBuilder = $db->table('tbl_users_list');
            $userIdxBuilder->select('idx');
            $userIdxBuilder->where('user_id', $userId);
            if (!empty($compCode)) {
                $userIdxBuilder->where('user_company', $compCode);
            }
            $userIdxQuery = $userIdxBuilder->get();
            
            $userIdx = null;
            if ($userIdxQuery !== false) {
                $userIdxResult = $userIdxQuery->getRowArray();
                if ($userIdxResult && isset($userIdxResult['idx'])) {
                    $userIdx = $userIdxResult['idx'];
                } else {
                    // user_company 조건 없이 재시도
                    $userIdxBuilder2 = $db->table('tbl_users_list');
                    $userIdxBuilder2->select('idx');
                    $userIdxBuilder2->where('user_id', $userId);
                    $userIdxQuery2 = $userIdxBuilder2->get();
                    if ($userIdxQuery2 !== false) {
                        $userIdxResult2 = $userIdxQuery2->getRowArray();
                        if ($userIdxResult2 && isset($userIdxResult2['idx'])) {
                            $userIdx = $userIdxResult2['idx'];
                        }
                    }
                }
            }
            
            // 정산관리부서 처리
            $userSettlementDeptModel = new \App\Models\UserSettlementDeptModel();
            if ($userIdx) {
                if ($userClass == '4') {
                    // user_class=4일 때: 정산관리부서 저장
                    $settlementDeptsJson = $this->request->getPost('settlement_depts');
                    $settlementDepts = [];
                    
                    if (!empty($settlementDeptsJson)) {
                        // JSON 문자열인 경우 파싱
                        if (is_string($settlementDeptsJson)) {
                            $settlementDepts = json_decode($settlementDeptsJson, true);
                            if (!is_array($settlementDepts)) {
                                $settlementDepts = [];
                            }
                        } elseif (is_array($settlementDeptsJson)) {
                            $settlementDepts = $settlementDeptsJson;
                        }
                    }
                    
                    // 정산관리부서 저장
                    $saveResult = $userSettlementDeptModel->saveSettlementDepts($userIdx, $settlementDepts);
                    
                    if ($saveResult) {
                        log_message('info', "Admin::companyCustomerSave - 정산관리부서 저장 성공: user_id={$userId}, user_idx={$userIdx}, depts=" . implode(',', $settlementDepts));
                    } else {
                        log_message('error', "Admin::companyCustomerSave - 정산관리부서 저장 실패: user_id={$userId}, user_idx={$userIdx}");
                    }
                } else {
                    // user_class가 4가 아닐 때: 기존 정산관리부서 삭제
                    $deleteResult = $userSettlementDeptModel->where('user_idx', $userIdx)->delete();
                    if ($deleteResult !== false) {
                        log_message('info', "Admin::companyCustomerSave - 정산관리부서 삭제 완료: user_id={$userId}, user_idx={$userIdx} (user_class={$userClass})");
                    }
                }
            } else {
                log_message('warning', "Admin::companyCustomerSave - user_idx 조회 실패: user_id={$userId}, comp_code={$compCode}");
            }
            
        } else {
            // 등록 모드: 아이디 중복 확인
            $checkExistResult = $insungApiService->checkMemberExist($mCode, $ccCodeApi, $token, $userId, $apiIdx);
            if ($checkExistResult !== false) {
                $code = '';
                if (is_object($checkExistResult) && isset($checkExistResult->code)) {
                    $code = $checkExistResult->code;
                } elseif (is_array($checkExistResult) && isset($checkExistResult[0]->code)) {
                    $code = $checkExistResult[0]->code;
                }
                
                if ($code !== '1000') {
                    return redirect()->back()->with('error', '이미 등록된 아이디이거나 조회 오류입니다.');
                }
            }
            
            // 인성 API 회원 등록
            $registerResult = $insungApiService->registerMember(
                $mCode, $ccCodeApi, $token, $compCode, $userId, $userPass, $compName, $userDong, 
                $userTel1, $credit, $userDept ?? '', $userName, '', $userAddr2 ?? '', 
                $extLon, $extLat, $userAddr1, $apiIdx
            );
            
            if ($registerResult === false) {
                return redirect()->back()->with('error', '인성 API 회원 등록에 실패했습니다.');
            }
            
            // 응답 코드 확인
            $code = '';
            if (is_object($registerResult) && isset($registerResult->code)) {
                $code = $registerResult->code;
            } elseif (is_array($registerResult) && isset($registerResult[0]->code)) {
                $code = $registerResult[0]->code;
            }
            
            if ($code !== '1000') {
                $msg = '';
                if (is_object($registerResult) && isset($registerResult->msg)) {
                    $msg = $registerResult->msg;
                } elseif (is_array($registerResult) && isset($registerResult[0]->msg)) {
                    $msg = $registerResult[0]->msg;
                }
                return redirect()->back()->with('error', '인성 API 회원 등록 실패: [' . $code . '] ' . $msg);
            }
            
            // 등록 후 c_code 조회
            $userCcode = '';
            $memberDetailResult = $insungApiService->getMemberDetail($mCode, $ccCodeApi, $token, $userId, $apiIdx);
            if ($memberDetailResult !== false) {
                // 응답 구조 확인: Result[1] 또는 직접 [1] 형태
                if (isset($memberDetailResult->Result) && isset($memberDetailResult->Result[1]) && isset($memberDetailResult->Result[1]->c_code)) {
                    $userCcode = $memberDetailResult->Result[1]->c_code;
                } elseif (isset($memberDetailResult[1]) && isset($memberDetailResult[1]->c_code)) {
                    $userCcode = $memberDetailResult[1]->c_code;
                } elseif (is_array($memberDetailResult) && isset($memberDetailResult[1]) && isset($memberDetailResult[1]['c_code'])) {
                    $userCcode = $memberDetailResult[1]['c_code'];
                }
            }
            
            // 암호화 헬퍼 인스턴스 생성
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            $encryptedFields = ['user_pass', 'user_name', 'user_tel1', 'user_tel2'];
            
            // DB 저장
            $userData = [
                'user_id' => $userId,
                'user_pass' => $userPass,
                'user_name' => $userName,
                'user_dept' => $userDept ?? '',
                'user_tel1' => $userTel1,
                'user_tel2' => $userTel2 ?? '',
                'user_addr' => $userAddr1,
                'user_addr_detail' => $userAddr2 ?? '',
                'user_company' => $compCode,
                'user_class' => $userClass,
                'user_memo' => $userMemo ?? '',
                'user_sido' => $sido ?? '',
                'user_gungu' => $gungu ?? '',
                'user_dong' => $userDong ?? '',
                'user_ccode' => $userCcode,
                'user_cc_idx' => $userCcIdx
            ];
            
            // 암호화 처리
            $userData = $encryptionHelper->encryptFields($userData, $encryptedFields);
            
            $userBuilder = $db->table('tbl_users_list');
            $insertResult = $userBuilder->insert($userData);
            
            // 정산관리부서 저장 (user_class=4일 때만)
            if ($userClass == '4' && $insertResult !== false) {
                $insertId = $db->insertID();
                
                if ($insertId) {
                    $settlementDeptsJson = $this->request->getPost('settlement_depts');
                    $settlementDepts = [];
                    
                    if (!empty($settlementDeptsJson)) {
                        // JSON 문자열인 경우 파싱
                        if (is_string($settlementDeptsJson)) {
                            $settlementDepts = json_decode($settlementDeptsJson, true);
                            if (!is_array($settlementDepts)) {
                                $settlementDepts = [];
                            }
                        } elseif (is_array($settlementDeptsJson)) {
                            $settlementDepts = $settlementDeptsJson;
                        }
                    }
                    
                    // 정산관리부서 저장
                    $userSettlementDeptModel = new \App\Models\UserSettlementDeptModel();
                    $saveResult = $userSettlementDeptModel->saveSettlementDepts($insertId, $settlementDepts);
                    
                    if ($saveResult) {
                        log_message('info', "Admin::companyCustomerSave - 정산관리부서 저장 성공 (등록): user_id={$userId}, user_idx={$insertId}, depts=" . implode(',', $settlementDepts));
                    } else {
                        log_message('error', "Admin::companyCustomerSave - 정산관리부서 저장 실패 (등록): user_id={$userId}, user_idx={$insertId}");
                    }
                }
            }
        }
        
        // 리다이렉트 URL 구성 (검색 파라미터 포함)
        $redirectUrl = '/admin/company-customer-list?comp_code=' . urlencode($compCode);
        if (!empty($searchKeyword)) {
            $redirectUrl .= '&search_keyword=' . urlencode($searchKeyword);
        }
        
        return redirect()->to($redirectUrl)->with('success', ($isEditMode ? '고객 정보가 수정되었습니다.' : '고객이 등록되었습니다.'));
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
        
        // user_class 조회 (dept_name 파라미터 조건 확인용)
        $loginType = session()->get('login_type');
        $userClass = session()->get('user_class');
        $userId = session()->get('user_id');
        $userDept = session()->get('user_dept');
        
        // user_class가 세션에 없으면 DB에서 조회
        if (empty($userClass) && $loginType === 'daumdata' && $userId) {
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('user_class, user_dept');
            $userBuilder->where('user_id', $userId);
            $userQuery = $userBuilder->get();
            if ($userQuery !== false) {
                $userResult = $userQuery->getRowArray();
                if ($userResult) {
                    if (isset($userResult['user_class'])) {
                        $userClass = $userResult['user_class'];
                    }
                    if (isset($userResult['user_dept'])) {
                        $userDept = $userResult['user_dept'];
                    }
                }
            }
        }
        
        // user_class가 3 이상이고 user_dept가 있을 때만 deptName 설정
        $deptName = null;
        if (isset($userClass) && (int)$userClass >= 3 && !empty($userDept)) {
            $deptName = $userDept;
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
                    $orderListResult = $insungApiService->getOrderList($mCode, $ccCode, $token, '', $fromDate, $toDate, $state, null, $deptName, $compNo, 15, $page, $apiIdx);
                    
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
                    $orderListResult = $insungApiService->getOrderList($mCode, $ccCode, $token, '', $fromDate, $toDate, $state, null, $deptName, null, 15, $page, $apiIdx);
                    
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
                            log_message('info', "Admin::orderListAjax - 주문 데이터 없음 (배��� 크기 1 이하)");
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
                        $orderListResult = $insungApiService->getOrderList($mCode, $ccCode, $token, '', $fromDate, $toDate, $state, null, $deptName, $compNo, 1000, 1, $apiIdx);
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
                                            
                                            // 필터링 조건 확인을 위한 로그
                                            $hasSerialNumber = isset($orderObj->serial_number);
                                            $hasUserId = isset($orderObj->user_id);
                                            $hasOrderDate = isset($orderObj->order_date);
                                            
                                            if (!($hasSerialNumber || $hasUserId || $hasOrderDate)) {
                                                log_message('debug', "Admin::orderListAjax - 인덱스 {$i} 데이터 필터링됨: serial_number=" . ($hasSerialNumber ? '있음' : '없음') . ", user_id=" . ($hasUserId ? '있음' : '없음') . ", order_date=" . ($hasOrderDate ? '있음' : '없음') . ", 데이터: " . json_encode($orderObj, JSON_UNESCAPED_UNICODE));
                                            }
                                            
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
     * 콜센터별 거래처 목록 조회 (AJAX - 오더유형 설정용)
     * API 건수와 DB 건수를 비교하여 반환 (동기화 필요 여부 판단용)
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

        $db = \Config\Database::connect();

        // tbl_api_list에서 API 정보 전체 조회
        $apiInfo = $this->insungApiListModel->where('cccode', $ccCode)->first();

        if (!$apiInfo || empty($apiInfo['idx'])) {
            return $this->response->setJSON([
                'success' => true,
                'companies' => [],
                'db_count' => 0,
                'api_count' => 0,
                'need_sync' => false
            ]);
        }

        $apiIdx = (int)$apiInfo['idx'];
        $mCode = $apiInfo['mcode'] ?? '';
        $apiCcCode = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';

        // cc_idx 조회 (tbl_cc_list.cc_apicode = tbl_api_list.idx)
        $ccBuilder = $db->table('tbl_cc_list');
        $ccBuilder->select('idx');
        $ccBuilder->where('cc_apicode', $apiIdx);
        $ccQuery = $ccBuilder->get();
        $ccResult = $ccQuery ? $ccQuery->getRowArray() : null;
        $ccIdx = $ccResult['idx'] ?? null;

        // DB에서 거래처 목록 조회 (use_yn = 'Y'만)
        $companyList = [];
        $dbCount = 0;
        if (!empty($ccIdx)) {
            $builder = $db->table('tbl_company_list c');
            $builder->select('c.comp_code, c.comp_name, c.cc_idx');
            $builder->where('c.cc_idx', $ccIdx);
            $builder->where('c.use_yn', 'Y');
            $builder->orderBy('c.comp_name', 'ASC');
            $query = $builder->get();
            $companyList = $query ? $query->getResultArray() : [];
            $dbCount = count($companyList);
        }

        // API에서 건수만 조회 (limit=1로 호출하여 total_record 확인)
        $apiCount = 0;
        $needSync = false;

        if (!empty($mCode) && !empty($apiCcCode) && !empty($token)) {
            try {
                $insungApiService = new \App\Libraries\InsungApiService();
                $apiResult = $insungApiService->getCompanyList($mCode, $apiCcCode, $token, '', '', 1, 1, $apiIdx);

                if ($apiResult) {
                    $apiCount = $this->extractTotalRecordFromApiResponse($apiResult);
                }
            } catch (\Exception $e) {
                // API 오류 시 건수 비교 불가 - DB 데이터만 사용
            }
        }

        // 건수가 다르면 동기화 필요
        $needSync = ($apiCount > 0 && $apiCount !== $dbCount);

        return $this->response->setJSON([
            'success' => true,
            'companies' => $companyList,
            'db_count' => $dbCount,
            'api_count' => $apiCount,
            'need_sync' => $needSync
        ]);
    }

    /**
     * API 응답에서 total_record 추출
     */
    private function extractTotalRecordFromApiResponse($apiResult)
    {
        $totalRecord = 0;

        if (is_object($apiResult) && isset($apiResult->Result)) {
            if (isset($apiResult->Result[1])) {
                $pageInfoData = $apiResult->Result[1];
                if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                    $totalRecord = (int)($pageInfoData->page_info[0]->total_record ?? 0);
                } elseif (isset($pageInfoData->total_record)) {
                    $totalRecord = (int)$pageInfoData->total_record;
                }
            }
        } elseif (is_array($apiResult) && isset($apiResult[1])) {
            $pageInfoData = is_object($apiResult[1]) ? $apiResult[1] : (object)$apiResult[1];
            if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                $totalRecord = (int)($pageInfoData->page_info[0]->total_record ?? 0);
            } elseif (isset($pageInfoData->total_record)) {
                $totalRecord = (int)$pageInfoData->total_record;
            }
        }

        return $totalRecord;
    }

    /**
     * 거래처 수동 동기화 (AJAX)
     */
    public function syncCompaniesForOrderType()
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

        $ccCode = $this->request->getGet('cc_code') ?? $this->request->getPost('cc_code');
        if (!$ccCode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '콜센터 코드가 필요합니다.'
            ])->setStatusCode(400);
        }

        $db = \Config\Database::connect();

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->where('cccode', $ccCode)->first();

        if (!$apiInfo || empty($apiInfo['idx'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ]);
        }

        $apiIdx = (int)$apiInfo['idx'];
        $mCode = $apiInfo['mcode'] ?? '';
        $apiCcCode = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';

        // cc_idx 조회
        $ccBuilder = $db->table('tbl_cc_list');
        $ccBuilder->select('idx');
        $ccBuilder->where('cc_apicode', $apiIdx);
        $ccQuery = $ccBuilder->get();
        $ccResult = $ccQuery ? $ccQuery->getRowArray() : null;
        $ccIdx = $ccResult['idx'] ?? null;

        if (empty($ccIdx)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '콜센터 정보를 찾을 수 없습니다.'
            ]);
        }

        // 동기화 실행
        $syncResult = $this->syncCompaniesFromInsungApi($mCode, $apiCcCode, $token, $apiIdx, $ccIdx, $db);

        // 동기화 후 거래처 목록 다시 조회 (use_yn = 'Y'만)
        $builder = $db->table('tbl_company_list c');
        $builder->select('c.comp_code, c.comp_name, c.cc_idx');
        $builder->where('c.cc_idx', $ccIdx);
        $builder->where('c.use_yn', 'Y');
        $builder->orderBy('c.comp_name', 'ASC');
        $query = $builder->get();
        $companyList = $query ? $query->getResultArray() : [];

        return $this->response->setJSON([
            'success' => $syncResult['status'] === 'success',
            'message' => $syncResult['message'] ?? '',
            'companies' => $companyList,
            'stats' => $syncResult['stats'] ?? null
        ]);
    }

    /**
     * 거래처 동기화 (SSE - 실시간 진행률 스트리밍)
     */
    public function syncCompaniesWithProgress()
    {
        // SSE 헤더 설정
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // nginx 버퍼링 비활성화

        // 출력 버퍼링 비활성화
        if (ob_get_level()) {
            ob_end_clean();
        }

        // SSE 이벤트 전송 헬퍼
        $sendEvent = function($event, $data) {
            echo "event: {$event}\n";
            echo "data: " . json_encode($data) . "\n\n";
            flush();
        };

        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            $sendEvent('error', ['message' => '로그인이 필요합니다.']);
            exit;
        }

        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            $sendEvent('error', ['message' => '접근 권한이 없습니다.']);
            exit;
        }

        $ccCode = $this->request->getGet('cc_code');
        if (!$ccCode) {
            $sendEvent('error', ['message' => '콜센터 코드가 필요합니다.']);
            exit;
        }

        $db = \Config\Database::connect();

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->where('cccode', $ccCode)->first();

        if (!$apiInfo || empty($apiInfo['idx'])) {
            $sendEvent('error', ['message' => 'API 정보를 찾을 수 없습니다.']);
            exit;
        }

        $apiIdx = (int)$apiInfo['idx'];
        $mCode = $apiInfo['mcode'] ?? '';
        $apiCcCode = $apiInfo['cccode'] ?? '';
        $token = $apiInfo['token'] ?? '';

        // cc_idx 조회
        $ccBuilder = $db->table('tbl_cc_list');
        $ccBuilder->select('idx');
        $ccBuilder->where('cc_apicode', $apiIdx);
        $ccQuery = $ccBuilder->get();
        $ccResult = $ccQuery ? $ccQuery->getRowArray() : null;
        $ccIdx = $ccResult['idx'] ?? null;

        if (empty($ccIdx)) {
            $sendEvent('error', ['message' => '콜센터 정보를 찾을 수 없습니다.']);
            exit;
        }

        // 동기화 시작
        $sendEvent('start', ['message' => '동기화를 시작합니다...']);

        try {
            $insungApiService = new \App\Libraries\InsungApiService();

            // 첫 번째 페이지 호출
            $sendEvent('progress', ['step' => 'api', 'message' => 'API에서 거래처 목록 조회 중...', 'percent' => 5]);

            $firstPageResult = $insungApiService->getCompanyList($mCode, $apiCcCode, $token, '', '', 1, 1000, $apiIdx);

            if (!$firstPageResult) {
                $sendEvent('error', ['message' => 'API 호출에 실패했습니다.']);
                exit;
            }

            // 응답 파싱
            $code = '';
            $totalPage = 1;
            $totalRecord = 0;

            if (is_object($firstPageResult) && isset($firstPageResult->Result)) {
                if (isset($firstPageResult->Result[0]->result_info[0]->code)) {
                    $code = $firstPageResult->Result[0]->result_info[0]->code;
                } elseif (isset($firstPageResult->Result[0]->code)) {
                    $code = $firstPageResult->Result[0]->code;
                }

                if (isset($firstPageResult->Result[1])) {
                    $pageInfoData = $firstPageResult->Result[1];
                    if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                        $pageInfo = $pageInfoData->page_info[0];
                        $totalPage = (int)($pageInfo->total_page ?? 1);
                        $totalRecord = (int)($pageInfo->total_record ?? 0);
                    } elseif (isset($pageInfoData->total_page)) {
                        $totalPage = (int)$pageInfoData->total_page;
                        $totalRecord = (int)($pageInfoData->total_record ?? 0);
                    }
                }
            } elseif (is_array($firstPageResult)) {
                if (isset($firstPageResult[0])) {
                    if (is_object($firstPageResult[0])) {
                        if (isset($firstPageResult[0]->result_info[0]->code)) {
                            $code = $firstPageResult[0]->result_info[0]->code;
                        } elseif (isset($firstPageResult[0]->code)) {
                            $code = $firstPageResult[0]->code;
                        }
                    } elseif (is_array($firstPageResult[0])) {
                        $code = $firstPageResult[0]['code'] ?? '';
                    }
                }

                if (isset($firstPageResult[1])) {
                    $pageInfoData = is_object($firstPageResult[1]) ? $firstPageResult[1] : (object)$firstPageResult[1];
                    if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                        $pageInfo = $pageInfoData->page_info[0];
                        $totalPage = (int)($pageInfo->total_page ?? 1);
                        $totalRecord = (int)($pageInfo->total_record ?? 0);
                    } elseif (isset($pageInfoData->total_page)) {
                        $totalPage = (int)$pageInfoData->total_page;
                        $totalRecord = (int)($pageInfoData->total_record ?? 0);
                    }
                }
            }

            if ($code !== '1000') {
                $sendEvent('error', ['message' => "API 응답 오류 (code: {$code})"]);
                exit;
            }

            $sendEvent('progress', [
                'step' => 'info',
                'message' => "총 {$totalRecord}건, {$totalPage}페이지 처리 예정",
                'percent' => 10,
                'total' => $totalRecord,
                'totalPage' => $totalPage
            ]);

            $syncedCount = 0;
            $updatedCount = 0;
            $insertedCount = 0;
            $deactivatedCount = 0;
            $apiCompCodes = [];

            // 모든 페이지 순회
            for ($page = 1; $page <= $totalPage; $page++) {
                if ($page === 1) {
                    $pageResult = $firstPageResult;
                } else {
                    $pageResult = $insungApiService->getCompanyList($mCode, $apiCcCode, $token, '', '', $page, 1000, $apiIdx);
                }

                if (!$pageResult) {
                    continue;
                }

                // 아이템 추출
                $items = $this->extractCompanyItems($pageResult);

                foreach ($items as $item) {
                    $compNo = $item->comp_no ?? '';
                    $corpName = $item->corp_name ?? '';
                    $owner = $item->owner ?? '';
                    $telNo = $item->tel_no ?? '';
                    $address = $item->adddress ?? '';

                    if (empty($compNo)) {
                        continue;
                    }

                    $apiCompCodes[] = $compNo;

                    // 기존 거래처 확인
                    $compBuilder = $db->table('tbl_company_list');
                    $compBuilder->where('comp_code', $compNo);
                    $compBuilder->where('cc_idx', $ccIdx);
                    $compQuery = $compBuilder->get();
                    $existingCompany = $compQuery ? $compQuery->getRowArray() : null;

                    $now = date('Y-m-d H:i:s');

                    if ($existingCompany) {
                        $companyData = [
                            'comp_name' => $corpName,
                            'comp_owner' => $owner,
                            'comp_tel' => $telNo,
                            'comp_addr' => $address,
                            'use_yn' => 'Y',
                            'updated_at' => $now
                        ];
                        $db->table('tbl_company_list')
                           ->where('comp_code', $compNo)
                           ->where('cc_idx', $ccIdx)
                           ->update($companyData);
                        $updatedCount++;
                    } else {
                        $companyData = [
                            'cc_idx' => $ccIdx,
                            'comp_code' => $compNo,
                            'comp_name' => $corpName,
                            'comp_owner' => $owner,
                            'comp_tel' => $telNo,
                            'comp_addr' => $address,
                            'use_yn' => 'Y',
                            'created_at' => $now
                        ];
                        $db->table('tbl_company_list')->insert($companyData);
                        $insertedCount++;
                    }
                    $syncedCount++;
                }

                // 페이지 진행률 (10% ~ 90% 구간)
                $pagePercent = 10 + (int)(($page / $totalPage) * 80);
                $sendEvent('progress', [
                    'step' => 'sync',
                    'message' => "{$page}/{$totalPage} 페이지 처리 완료 ({$syncedCount}건)",
                    'percent' => $pagePercent,
                    'current' => $syncedCount,
                    'total' => $totalRecord,
                    'page' => $page,
                    'totalPage' => $totalPage
                ]);
            }

            // 거래 종료 처리
            $sendEvent('progress', ['step' => 'deactivate', 'message' => '거래 종료 처리 중...', 'percent' => 92]);

            if (!empty($apiCompCodes)) {
                $deactivateBuilder = $db->table('tbl_company_list');
                $deactivateBuilder->where('cc_idx', $ccIdx);
                $deactivateBuilder->where('use_yn', 'Y');
                $deactivateBuilder->whereNotIn('comp_code', $apiCompCodes);
                $deactivatedCount = $deactivateBuilder->countAllResults(false);

                $db->table('tbl_company_list')
                   ->where('cc_idx', $ccIdx)
                   ->where('use_yn', 'Y')
                   ->whereNotIn('comp_code', $apiCompCodes)
                   ->update([
                       'use_yn' => 'N',
                       'updated_at' => date('Y-m-d H:i:s')
                   ]);
            }

            // 거래처 목록 조회
            $sendEvent('progress', ['step' => 'fetch', 'message' => '거래처 목록 조회 중...', 'percent' => 95]);

            $builder = $db->table('tbl_company_list c');
            $builder->select('c.comp_code, c.comp_name, c.cc_idx');
            $builder->where('c.cc_idx', $ccIdx);
            $builder->where('c.use_yn', 'Y');
            $builder->orderBy('c.comp_name', 'ASC');
            $query = $builder->get();
            $companyList = $query ? $query->getResultArray() : [];

            // 완료
            $sendEvent('complete', [
                'success' => true,
                'message' => "동기화 완료: 총 {$syncedCount}건",
                'stats' => [
                    'total' => $syncedCount,
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'deactivated' => $deactivatedCount
                ],
                'companies' => $companyList
            ]);

        } catch (\Exception $e) {
            $sendEvent('error', ['message' => '동기화 중 오류가 발생했습니다: ' . $e->getMessage()]);
        }

        exit;
    }

    /**
     * 인성 API에서 거래처 목록을 조회하여 DB에 동기화
     */
    private function syncCompaniesFromInsungApi($mCode, $ccCode, $token, $apiIdx, $ccIdx, $db)
    {
        // 토큰이 없으면 동기화 스킵
        if (empty($mCode) || empty($ccCode) || empty($token)) {
            // log_message('warning', "syncCompaniesFromInsungApi - API 정보 불완전: mcode={$mCode}, cccode={$ccCode}, token=" . (empty($token) ? 'empty' : 'exists'));
            return [
                'status' => 'skipped',
                'message' => 'API 인증 정보가 불완전합니다.'
            ];
        }

        try {
            $insungApiService = new \App\Libraries\InsungApiService();

            // 첫 번째 페이지 호출 (limit=1000)
            $firstPageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', '', 1, 1000, $apiIdx);

            if (!$firstPageResult) {
                // log_message('error', "syncCompaniesFromInsungApi - API 호출 실패");
                return [
                    'status' => 'api_error',
                    'message' => 'API 호출에 실패했습니다.'
                ];
            }

            // 응답 파싱
            $code = '';
            $totalPage = 1;
            $totalRecord = 0;

            if (is_object($firstPageResult) && isset($firstPageResult->Result)) {
                if (isset($firstPageResult->Result[0]->result_info[0]->code)) {
                    $code = $firstPageResult->Result[0]->result_info[0]->code;
                } elseif (isset($firstPageResult->Result[0]->code)) {
                    $code = $firstPageResult->Result[0]->code;
                }

                if (isset($firstPageResult->Result[1])) {
                    $pageInfoData = $firstPageResult->Result[1];
                    if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                        $pageInfo = $pageInfoData->page_info[0];
                        $totalPage = (int)($pageInfo->total_page ?? 1);
                        $totalRecord = (int)($pageInfo->total_record ?? 0);
                    } elseif (isset($pageInfoData->total_page)) {
                        $totalPage = (int)$pageInfoData->total_page;
                        $totalRecord = (int)($pageInfoData->total_record ?? 0);
                    }
                }
            } elseif (is_array($firstPageResult)) {
                if (isset($firstPageResult[0])) {
                    if (is_object($firstPageResult[0])) {
                        if (isset($firstPageResult[0]->result_info[0]->code)) {
                            $code = $firstPageResult[0]->result_info[0]->code;
                        } elseif (isset($firstPageResult[0]->code)) {
                            $code = $firstPageResult[0]->code;
                        }
                    } elseif (is_array($firstPageResult[0])) {
                        $code = $firstPageResult[0]['code'] ?? '';
                    }
                }

                if (isset($firstPageResult[1])) {
                    $pageInfoData = is_object($firstPageResult[1]) ? $firstPageResult[1] : (object)$firstPageResult[1];
                    if (isset($pageInfoData->page_info) && is_array($pageInfoData->page_info) && isset($pageInfoData->page_info[0])) {
                        $pageInfo = $pageInfoData->page_info[0];
                        $totalPage = (int)($pageInfo->total_page ?? 1);
                        $totalRecord = (int)($pageInfo->total_record ?? 0);
                    } elseif (isset($pageInfoData->total_page)) {
                        $totalPage = (int)$pageInfoData->total_page;
                        $totalRecord = (int)($pageInfoData->total_record ?? 0);
                    }
                }
            }

            if ($code !== '1000') {
                // log_message('error', "syncCompaniesFromInsungApi - API 응답 오류: code={$code}");
                return [
                    'status' => 'api_response_error',
                    'message' => "API 응답 오류 (code: {$code})"
                ];
            }

            $syncedCount = 0;
            $updatedCount = 0;
            $insertedCount = 0;
            $deactivatedCount = 0;
            $apiCompCodes = []; // API에서 받은 모든 comp_code 수집

            // 모든 페이지 순회
            for ($page = 1; $page <= $totalPage; $page++) {
                if ($page === 1) {
                    $pageResult = $firstPageResult;
                } else {
                    $pageResult = $insungApiService->getCompanyList($mCode, $ccCode, $token, '', '', $page, 1000, $apiIdx);
                }

                if (!$pageResult) {
                    continue;
                }

                // 아이템 추출
                $items = $this->extractCompanyItems($pageResult);

                foreach ($items as $item) {
                    $compNo = $item->comp_no ?? '';
                    $corpName = $item->corp_name ?? '';
                    $owner = $item->owner ?? '';
                    $telNo = $item->tel_no ?? '';
                    $address = $item->adddress ?? ''; // API 오타 그대로 사용

                    if (empty($compNo)) {
                        continue;
                    }

                    // API에서 받은 comp_code 수집
                    $apiCompCodes[] = $compNo;

                    // 기존 거래처 확인
                    $compBuilder = $db->table('tbl_company_list');
                    $compBuilder->where('comp_code', $compNo);
                    $compBuilder->where('cc_idx', $ccIdx);
                    $compQuery = $compBuilder->get();
                    $existingCompany = $compQuery ? $compQuery->getRowArray() : null;

                    $now = date('Y-m-d H:i:s');

                    if ($existingCompany) {
                        // 업데이트 - use_yn을 Y로 (다시 활성화), updated_at 갱신
                        $companyData = [
                            'comp_name' => $corpName,
                            'comp_owner' => $owner,
                            'comp_tel' => $telNo,
                            'comp_addr' => $address,
                            'use_yn' => 'Y',
                            'updated_at' => $now
                        ];
                        $db->table('tbl_company_list')
                           ->where('comp_code', $compNo)
                           ->where('cc_idx', $ccIdx)
                           ->update($companyData);
                        $updatedCount++;
                    } else {
                        // 신규 삽입 - use_yn Y, created_at 설정
                        $companyData = [
                            'cc_idx' => $ccIdx,
                            'comp_code' => $compNo,
                            'comp_name' => $corpName,
                            'comp_owner' => $owner,
                            'comp_tel' => $telNo,
                            'comp_addr' => $address,
                            'use_yn' => 'Y',
                            'created_at' => $now
                        ];
                        $db->table('tbl_company_list')->insert($companyData);
                        $insertedCount++;
                    }
                    $syncedCount++;
                }
            }

            // API에 없고 DB에만 있는 거래처는 use_yn = 'N' 처리 (거래 종료)
            if (!empty($apiCompCodes)) {
                $deactivateBuilder = $db->table('tbl_company_list');
                $deactivateBuilder->where('cc_idx', $ccIdx);
                $deactivateBuilder->where('use_yn', 'Y');
                $deactivateBuilder->whereNotIn('comp_code', $apiCompCodes);
                $deactivatedCount = $deactivateBuilder->countAllResults(false);

                // 비활성화 처리
                $db->table('tbl_company_list')
                   ->where('cc_idx', $ccIdx)
                   ->where('use_yn', 'Y')
                   ->whereNotIn('comp_code', $apiCompCodes)
                   ->update([
                       'use_yn' => 'N',
                       'updated_at' => date('Y-m-d H:i:s')
                   ]);
            }

            return [
                'status' => 'success',
                'message' => "동기화 완료: 총 {$syncedCount}건",
                'stats' => [
                    'total' => $syncedCount,
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'deactivated' => $deactivatedCount
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'exception',
                'message' => '동기화 중 오류가 발생했습니다: ' . $e->getMessage()
            ];
        }
    }

    /**
     * API 응답에서 거래처 아이템 추출
     */
    private function extractCompanyItems($pageData)
    {
        $items = [];

        if (is_object($pageData) && isset($pageData->Result)) {
            $resultArray = is_array($pageData->Result) ? $pageData->Result : [$pageData->Result];
            if (isset($resultArray[2]->items[0]->item)) {
                $items = is_array($resultArray[2]->items[0]->item) ? $resultArray[2]->items[0]->item : [$resultArray[2]->items[0]->item];
            }
        } elseif (is_array($pageData) && isset($pageData[2])) {
            if (isset($pageData[2]->items[0]->item)) {
                $items = is_array($pageData[2]->items[0]->item) ? $pageData[2]->items[0]->item : [$pageData[2]->items[0]->item];
            }
        }

        return $items;
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

    /**
     * 부서 목록 조회 API 호출 (거래처관리용)
     */
    public function getDepartmentList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $loginType = session()->get('login_type');
        $userId = session()->get('user_id');

        try {
            // daumdata 로그인만 지원 (부서 목록은 Insung API 사용)
            if ($loginType !== 'daumdata') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '부서 목록 조회는 daumdata 로그인만 지원됩니다.'
                ])->setStatusCode(400);
            }

            // 사용자 정보 조회
            $insungUsersListModel = new \App\Models\InsungUsersListModel();
            $user = $insungUsersListModel->getUserWithCompanyInfo($userId);
            
            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '사용자 정보를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }

            // API 정보 조회
            $mCode = $user['m_code'] ?? '';
            $ccCode = $user['cc_code'] ?? '';
            $token = $user['token'] ?? '';

            if (empty($mCode) || empty($ccCode) || empty($token)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 정보가 없습니다.'
                ])->setStatusCode(400);
            }

            // Insung API 서비스 호출
            $insungApiService = new \App\Libraries\InsungApiService();
            $apiIdx = $user['api_idx'] ?? null;
            
            $result = $insungApiService->getDepartmentList($mCode, $ccCode, $token, $userId, $apiIdx);

            if ($result && $result['success']) {
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $result['message'] ?? '부서 목록 조회에 실패했습니다.'
                ])->setStatusCode(500);
            }

        } catch (\Exception $e) {
            log_message('error', 'Admin::getDepartmentList - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => '부서 목록 조회 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }

    /**
     * user_id로 정산관리부서 목록 조회 (거래처관리용)
     */
    public function getSettlementDeptsByUserId()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $loginType = session()->get('login_type');
        $requestUserId = $this->request->getGet('user_id');

        try {
            // daumdata 로그인만 지원
            if ($loginType !== 'daumdata') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '정산관���부서 조회는 daumdata 로그인만 지원됩니다.'
                ])->setStatusCode(400);
            }

            if (empty($requestUserId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'user_id가 필요합니다.'
                ])->setStatusCode(400);
            }

            // user_id로 user_idx 조회
            $db = \Config\Database::connect();
            $userBuilder = $db->table('tbl_users_list');
            $userBuilder->select('idx');
            $userBuilder->where('user_id', $requestUserId);
            $userQuery = $userBuilder->get();
            
            if ($userQuery === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '사용자 조회에 실패했습니다.'
                ])->setStatusCode(500);
            }
            
            $userResult = $userQuery->getRowArray();
            if (!$userResult || !isset($userResult['idx'])) {
                return $this->response->setJSON([
                    'success' => true,
                    'data' => []
                ]);
            }

            $userIdx = $userResult['idx'];

            // 정산관리부서 목록 조회
            $userSettlementDeptModel = new \App\Models\UserSettlementDeptModel();
            $settlementDepts = $userSettlementDeptModel->getSettlementDeptsByUserIdx($userIdx);

            return $this->response->setJSON([
                'success' => true,
                'data' => $settlementDepts
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Admin::getSettlementDeptsByUserId - ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => '정산관리부서 조회 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }

    /**
     * 시스템 설정 페이지 (로그인 제한 설정 등)
     */
    public function settings()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // user_type=1 체크 (최고 관리자만 접근 가능)
        $userType = session()->get('user_type');
        if ($userType !== '1' && $userType !== 1) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        $systemSettingModel = new \App\Models\SystemSettingModel();
        $loginSettings = $systemSettingModel->getLoginSettings();

        // 배송사유 목록 조회
        $deliveryReasonModel = new \App\Models\DeliveryReasonModel();
        $deliveryReasons = $deliveryReasonModel->getAllReasons();

        // 권한 목록 조회
        $classInfoModel = new \App\Models\ClassInfoModel();
        $classes = $classInfoModel->getAllClasses();

        $data = [
            'title' => 'DaumData - 시스템 설정',
            'content_header' => [
                'title' => '시스템 설정',
                'description' => '로그인 보안 및 시스템 설정을 관리합니다.'
            ],
            'settings' => $loginSettings,
            'delivery_reasons' => $deliveryReasons,
            'classes' => $classes
        ];

        return view('admin/settings', $data);
    }

    /**
     * 시스템 설정 저장
     */
    public function saveSettings()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // user_type=1 체크
        $userType = session()->get('user_type');
        if ($userType !== '1' && $userType !== 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        $systemSettingModel = new \App\Models\SystemSettingModel();
        $userIdx = session()->get('user_idx');

        try {
            $maxAttempts = (int)$this->request->getPost('login_max_attempts');
            $lockoutMinutes = (int)$this->request->getPost('login_lockout_minutes');
            $attemptWindow = (int)$this->request->getPost('login_attempt_window');

            // 유효성 검사
            if ($maxAttempts < 1 || $maxAttempts > 20) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '최대 시도 횟수는 1~20 사이의 값이어야 합니다.'
                ]);
            }
            if ($lockoutMinutes < 1 || $lockoutMinutes > 60) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '잠금 시간은 1~60분 사이의 값이어야 합니다.'
                ]);
            }
            if ($attemptWindow < 5 || $attemptWindow > 120) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '시도 횟수 체크 시간은 5~120분 사이의 값이어야 합니다.'
                ]);
            }

            // 설정 저장
            $systemSettingModel->setSetting('login_max_attempts', $maxAttempts, 'int', '로그인 최대 시도 횟수', $userIdx);
            $systemSettingModel->setSetting('login_lockout_minutes', $lockoutMinutes, 'int', '로그인 잠금 시간 (분)', $userIdx);
            $systemSettingModel->setSetting('login_attempt_window', $attemptWindow, 'int', '로그인 시도 횟수 체크 시간 범위 (분)', $userIdx);

            return $this->response->setJSON([
                'success' => true,
                'message' => '설정이 저장되었습니다.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Admin::saveSettings - ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => '설정 저장 중 오류가 발생했습니다.'
            ])->setStatusCode(500);
        }
    }

    /**
     * 로그인 시��� 내역 조회 페이지
     */
    public function loginAttempts()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // user_type=1 체크
        $userType = session()->get('user_type');
        if ($userType !== '1' && $userType !== 1) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        $loginAttemptModel = new \App\Models\LoginAttemptModel();

        // 모바일 여부 확인
        $userAgent = $this->request->getUserAgent();
        $isMobile = $userAgent->isMobile();

        // 필터
        $filters = [
            'user_id' => $this->request->getGet('user_id'),
            'ip_address' => $this->request->getGet('ip_address'),
            'is_success' => $this->request->getGet('is_success'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to')
        ];

        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = $isMobile ? 15 : 20;

        $result = $loginAttemptModel->getAttemptHistory($filters, $page, $perPage);
        $statistics = $loginAttemptModel->getStatistics(7);

        $data = [
            'title' => 'DaumData - 로그인 시도 내역',
            'content_header' => [
                'title' => '로그인 시도 내역',
                'description' => '로그인 성공/실패 내역을 조회합니다.'
            ],
            'attempts' => $result['attempts'],
            'total_count' => $result['total_count'],
            'pagination' => $result['pagination'],
            'statistics' => $statistics,
            'filters' => $filters,
            'current_page' => $page,
            'per_page' => $perPage
        ];

        return view('admin/login-attempts', $data);
    }

    /**
     * 배송사유 추가
     */
    public function addDeliveryReason()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        $userType = session()->get('user_type');
        if ($userType !== '1' && $userType !== 1) {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        $data = [
            'reason_code' => $this->request->getPost('reason_code'),
            'reason_name' => $this->request->getPost('reason_name'),
            'sort_order' => (int)$this->request->getPost('sort_order') ?: 0,
            'is_active' => $this->request->getPost('is_active') ?? 'Y'
        ];

        if (empty($data['reason_code']) || empty($data['reason_name'])) {
            return $this->response->setJSON(['success' => false, 'message' => '배송사유 코드와 이름은 필수입니다.']);
        }

        $deliveryReasonModel = new \App\Models\DeliveryReasonModel();
        $result = $deliveryReasonModel->addReason($data);

        return $this->response->setJSON($result);
    }

    /**
     * 배송사유 수정
     */
    public function updateDeliveryReason()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        $userType = session()->get('user_type');
        if ($userType !== '1' && $userType !== 1) {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        $id = $this->request->getPost('id');
        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID가 필요합니다.']);
        }

        $data = [
            'reason_code' => $this->request->getPost('reason_code'),
            'reason_name' => $this->request->getPost('reason_name'),
            'sort_order' => (int)$this->request->getPost('sort_order'),
            'is_active' => $this->request->getPost('is_active')
        ];

        $deliveryReasonModel = new \App\Models\DeliveryReasonModel();
        $result = $deliveryReasonModel->updateReason($id, $data);

        return $this->response->setJSON($result);
    }

    /**
     * 배송사유 삭제
     */
    public function deleteDeliveryReason()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        $userType = session()->get('user_type');
        if ($userType !== '1' && $userType !== 1) {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        $id = $this->request->getPost('id');
        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID가 필요합니다.']);
        }

        $deliveryReasonModel = new \App\Models\DeliveryReasonModel();
        $result = $deliveryReasonModel->deleteReason($id);

        return $this->response->setJSON($result);
    }

    /**
     * 배송사유 목록 조회 (AJAX)
     */
    public function getDeliveryReasons()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        $deliveryReasonModel = new \App\Models\DeliveryReasonModel();
        $reasons = $deliveryReasonModel->getAllReasons();

        return $this->response->setJSON(['success' => true, 'data' => $reasons]);
    }

    /**
     * 거래처별 배송사유 사용 설정 조회
     */
    public function getCompanyDeliveryReasonSetting()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        $compCode = $this->request->getGet('comp_code');
        if (empty($compCode)) {
            return $this->response->setJSON(['success' => false, 'message' => '거래처 코드가 필요합니다.']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('tbl_company_list');
        $company = $builder->where('comp_code', $compCode)->get()->getRowArray();

        if (!$company) {
            return $this->response->setJSON(['success' => false, 'message' => '거래처를 찾을 수 없습니다.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'use_delivery_reason' => $company['use_delivery_reason'] ?? 'N'
        ]);
    }

    /**
     * 거래처별 배송사유 사용 설정 업데이트
     */
    public function updateCompanyDeliveryReasonSetting()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.'])->setStatusCode(401);
        }

        $userType = session()->get('user_type');
        if ($userType !== '1' && $userType !== 1) {
            return $this->response->setJSON(['success' => false, 'message' => '접근 권한이 없습니다.'])->setStatusCode(403);
        }

        $compCode = $this->request->getPost('comp_code');
        $useDeliveryReason = $this->request->getPost('use_delivery_reason');

        if (empty($compCode)) {
            return $this->response->setJSON(['success' => false, 'message' => '거래처 코드가 필요합니다.']);
        }

        if (!in_array($useDeliveryReason, ['Y', 'N'])) {
            return $this->response->setJSON(['success' => false, 'message' => '잘못된 설정 값입니다.']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('tbl_company_list');
        $result = $builder->where('comp_code', $compCode)->update([
            'use_delivery_reason' => $useDeliveryReason,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => '배송사유 사용 설정이 변경되었습니다.']);
        }

        return $this->response->setJSON(['success' => false, 'message' => '설정 변경에 실패했습니다.']);
    }

    /**
     * 인성API연계센터 관리 페이지
     */
    public function apiList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // 권한 체크: daumdata 로그인 user_type=1
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        // 모바일 여부 확인
        $userAgent = $this->request->getUserAgent();
        $isMobile = $userAgent->isMobile();

        // 검색 필터
        $filters = [
            'api_gbn' => $this->request->getGet('api_gbn') ?? '',
            'cccode' => $this->request->getGet('cccode') ?? '',
            'api_name' => $this->request->getGet('api_name') ?? ''
        ];
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = $isMobile ? 15 : 20;

        // API 목록 조회
        $result = $this->insungApiListModel->getApiListWithPagination($filters, $page, $perPage);

        // 토큰 암호화 처리
        $encryptionHelper = new \App\Libraries\EncryptionHelper();
        $apiList = $result['api_list'];
        foreach ($apiList as &$api) {
            if (!empty($api['token'])) {
                $api['token_encrypted'] = $encryptionHelper->encrypt($api['token']);
            } else {
                $api['token_encrypted'] = '';
            }
        }
        unset($api);

        $data = [
            'title' => '인성API연계센터 관리',
            'content_header' => [
                'title' => '인성API연계센터 관리',
                'description' => 'API 연계센터 정보를 관리하고 토큰을 갱신합니다.'
            ],
            'api_list' => $apiList,
            'filters' => $filters,
            'pagination' => $result['pagination'],
            'total_count' => $result['total_count']
        ];

        return view('admin/api_list', $data);
    }

    /**
     * API 토큰 갱신 (AJAX)
     */
    public function refreshApiToken()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 권한 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        $idx = $this->request->getPost('idx');
        if (empty($idx)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보가 없습니다.'
            ]);
        }

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->find($idx);
        if (!$apiInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ]);
        }

        try {
            // 인성 API로 토큰 갱신 요청 (updateTokenKey가 DB 저장까지 처리)
            $insungApiService = new \App\Libraries\InsungApiService();
            $newToken = $insungApiService->updateTokenKey($idx);

            if ($newToken) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => '토큰이 갱신되었습니다.',
                    'token' => $newToken
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '토큰 갱신에 실패했습니다.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Admin::refreshApiToken - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '토큰 갱신 중 오류가 발생했습니다.'
            ]);
        }
    }

    /**
     * 전체 API 토큰 일괄 갱신 (AJAX)
     */
    public function refreshAllApiTokens()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 권한 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        try {
            // 메인 API 목록 조회
            $apiList = $this->insungApiListModel->getMainApiList();

            $successCount = 0;
            $failCount = 0;
            $insungApiService = new \App\Libraries\InsungApiService();

            foreach ($apiList as $api) {
                // updateTokenKey가 DB 저장까지 처리
                $newToken = $insungApiService->updateTokenKey($api['idx']);

                if ($newToken) {
                    $successCount++;
                } else {
                    $failCount++;
                    log_message('error', "Token refresh failed for idx={$api['idx']}");
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => "토큰 갱신 완료: 성공 {$successCount}건, 실패 {$failCount}건"
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Admin::refreshAllApiTokens - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '토큰 일괄 갱신 중 오류가 발생했습니다.'
            ]);
        }
    }

    /**
     * API 정보 상세 조회 (AJAX)
     */
    public function getApiDetail()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $idx = $this->request->getGet('idx');
        if (empty($idx)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보가 없습니다.'
            ]);
        }

        $apiInfo = $this->insungApiListModel->find($idx);
        if (!$apiInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ]);
        }

        // 토큰 암호화 처리
        $encryptionHelper = new \App\Libraries\EncryptionHelper();
        if (!empty($apiInfo['token'])) {
            $apiInfo['token_encrypted'] = $encryptionHelper->encrypt($apiInfo['token']);
        } else {
            $apiInfo['token_encrypted'] = '';
        }

        return $this->response->setJSON([
            'success' => true,
            'api' => $apiInfo
        ]);
    }

    /**
     * 권한 관리 목록
     */
    public function classList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // 권한 체크: daumdata 로그인 user_type=1 (슈퍼관리자)만 허용
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        $classInfoModel = new \App\Models\ClassInfoModel();
        $classes = $classInfoModel->getAllClasses();

        $data = [
            'title' => '권한 관리',
            'classes' => $classes
        ];

        return view('admin/class_list', $data);
    }

    /**
     * 권한 등록/수정 폼
     */
    public function classForm($classId = null)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // 권한 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        $classInfoModel = new \App\Models\ClassInfoModel();
        $classInfo = null;

        if ($classId !== null) {
            // 수정 모드
            $classInfo = $classInfoModel->find($classId);
            if (!$classInfo) {
                return redirect()->to('/admin/class-list')->with('error', '권한 정보를 찾을 수 없습니다.');
            }
        }

        $data = [
            'title' => $classId ? '권한 수정' : '권한 등록',
            'classInfo' => $classInfo,
            'isEdit' => $classId !== null
        ];

        return view('admin/class_form', $data);
    }

    /**
     * 권한 저장 (등록/수정)
     */
    public function classSave()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // 권한 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        $classInfoModel = new \App\Models\ClassInfoModel();
        $isEdit = $this->request->getPost('is_edit') === '1';
        $originalClassId = $this->request->getPost('original_class_id');

        // 유효성 검사
        $rules = [
            'class_id' => 'required|integer',
            'class_name' => 'required|max_length[50]',
            'permission_level' => 'required|integer',
            'is_active' => 'required|in_list[0,1]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $classId = $this->request->getPost('class_id');
        $className = $this->request->getPost('class_name');
        $classDesc = $this->request->getPost('class_desc');
        $permissionLevel = $this->request->getPost('permission_level');
        $isActive = $this->request->getPost('is_active');

        $data = [
            'class_id' => $classId,
            'class_name' => $className,
            'class_desc' => $classDesc,
            'permission_level' => $permissionLevel,
            'is_active' => $isActive
        ];

        try {
            if ($isEdit && $originalClassId) {
                // 수정 모드
                if ($classId != $originalClassId) {
                    // class_id가 변경되었는지 확인
                    if ($classInfoModel->classExists($classId)) {
                        return redirect()->back()->withInput()->with('error', "권한 ID {$classId}는 이미 사용 중입니다.");
                    }

                    // PK가 변경되므로 기존 데이터 삭제 후 새로 생성
                    $classInfoModel->delete($originalClassId);
                    $classInfoModel->insert($data);
                } else {
                    // class_id가 동일하면 일반 업데이트
                    $classInfoModel->update($classId, $data);
                }
                $message = '권한 정보가 수정되었습니다.';
            } else {
                // 등록 모드
                // class_id 중복 체크
                if ($classInfoModel->classExists($classId)) {
                    return redirect()->back()->withInput()->with('error', "권한 ID {$classId}는 이미 사용 중입니다.");
                }

                $classInfoModel->insert($data);
                $message = '권한이 등록되었습니다.';
            }

            return redirect()->to('/admin/class-list')->with('success', $message);
        } catch (\Exception $e) {
            log_message('error', 'Admin::classSave - ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', '권한 저장 중 오류가 발생했습니다.');
        }
    }

    /**
     * 권한 삭제
     */
    public function classDelete()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 권한 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');

        if ($loginType !== 'daumdata' || $userType != '1') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        $classId = $this->request->getPost('class_id');
        if (empty($classId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '권한 ID가 없습니다.'
            ]);
        }

        $classInfoModel = new \App\Models\ClassInfoModel();

        // 해당 권한을 사용하는 사용자가 있는지 확인
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_users_list');
        $userCount = $builder->where('user_class', $classId)->countAllResults();

        if ($userCount > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "이 권한을 사용하는 사용자가 {$userCount}명 있어 삭제할 수 없습니다."
            ]);
        }

        try {
            $classInfoModel->delete($classId);
            return $this->response->setJSON([
                'success' => true,
                'message' => '권한이 삭제되었습니다.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Admin::classDelete - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '권한 삭제 중 오류가 발생했습니다.'
            ]);
        }
    }

}
