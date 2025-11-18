<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StoreRegistrationModel;
use App\Models\ServiceTypeModel;

class StoreRegistration extends BaseController
{
    protected $storeRegistrationModel;
    protected $serviceTypeModel;

    public function __construct()
    {
        $this->storeRegistrationModel = new StoreRegistrationModel();
        $this->serviceTypeModel = new ServiceTypeModel();
    }

    /**
     * 입점신청 리스트 페이지
     */
    public function index()
    {
        // 페이징 및 필터 파라미터
        $page = $this->request->getGet('page') ?? 1;
        $keyword = $this->request->getGet('keyword') ?? '';
        $status = $this->request->getGet('status') ?? '';
        $itemsPerPage = 10;
        
        $filters = [
            'keyword' => $keyword,
            'status' => $status
        ];
        
        // 총 레코드 수 조회
        $totalItems = $this->storeRegistrationModel->getTotalCount($filters);
        
        // 페이징 계산
        $offset = ($page - 1) * $itemsPerPage;
        
        // 데이터 조회
        $registrations = $this->storeRegistrationModel->getAllRegistrations($itemsPerPage, $offset, $filters);
        
        // 페이징 헬퍼 생성
        $paginationHelper = new \App\Libraries\PaginationHelper(
            $totalItems,
            $itemsPerPage,
            $page,
            base_url('store-registration'),
            $filters
        );
        
        $data = [
            'title' => '입점관리',
            'content_header' => [
                'title' => '입점관리',
                'description' => '입점신청 현황 및 승인 관리'
            ],
            'registrations' => $registrations,
            'status_counts' => $this->storeRegistrationModel->getStatusCounts(),
            'pagination' => $paginationHelper,
            'filters' => $filters
        ];

        return view('store_registration/list', $data);
    }

    /**
     * 입점신청 상세보기 (AJAX)
     */
    public function view($id)
    {
        try {
            $registration = $this->storeRegistrationModel->getRegistrationById($id);
            
            if (!$registration) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '입점신청 정보를 찾을 수 없습니다.'
                ]);
            }

            $data = [
                'registration' => $registration,
                'services' => $this->storeRegistrationModel->getRegistrationServices($id)
            ];

            return $this->response->setJSON([
                'success' => true,
                'html' => view('store_registration/detail_popup', $data)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'StoreRegistration::view Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '상세보기 조회 중 오류가 발생했습니다.'
            ]);
        }
    }

    /**
     * 입점신청 승인/거부 처리
     */
    public function updateStatus()
    {
        try {
            $id = $this->request->getPost('id');
            $status = $this->request->getPost('status');
            $notes = $this->request->getPost('notes');
            $userId = session('user_id') ?? 1; // 임시로 1 사용

            if (!$id || !in_array($status, ['approved', 'rejected'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '잘못된 요청입니다.'
                ]);
            }

            $result = $this->storeRegistrationModel->updateStatus($id, $status, $notes, $userId);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $status === 'approved' ? '입점신청이 승인되었습니다.' : '입점신청이 거부되었습니다.'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '처리 중 오류가 발생했습니다.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'StoreRegistration::updateStatus Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 입점신청 폼 제출 처리
     */
    public function submit()
    {
        $validation = \Config\Services::validation();
        
        $validation->setRules([
            'applicant_type' => 'required',
            'hierarchy_level' => 'required',
            'company_name' => 'required|max_length[100]',
            'business_number' => 'required|max_length[20]',
            'representative_name' => 'required|max_length[50]',
            'representative_phone' => 'required|max_length[20]',
            'representative_email' => 'required|valid_email|max_length[100]',
            'company_address' => 'required',
            'primary_service_category' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력 정보를 확인해주세요.',
                'errors' => $validation->getErrors()
            ]);
        }

        $data = [
            'applicant_type' => $this->request->getPost('applicant_type'),
            'hierarchy_level' => $this->request->getPost('hierarchy_level'),
            'company_name' => $this->request->getPost('company_name'),
            'business_number' => $this->request->getPost('business_number'),
            'business_type' => $this->request->getPost('business_type'),
            'employee_count' => $this->request->getPost('employee_count'),
            'company_address' => $this->request->getPost('company_address'),
            'representative_name' => $this->request->getPost('representative_name'),
            'representative_phone' => $this->request->getPost('representative_phone'),
            'representative_email' => $this->request->getPost('representative_email'),
            'annual_revenue' => $this->request->getPost('annual_revenue'),
            'primary_service_category' => $this->request->getPost('primary_service_category'),
            'expected_monthly_orders' => $this->request->getPost('expected_monthly_orders'),
            'contract_period' => $this->request->getPost('contract_period'),
            'special_requirements' => $this->request->getPost('special_requirements'),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 파일 업로드 처리
        $files = $this->request->getFiles();
        if (!empty($files['business_license_file']) && $files['business_license_file']->isValid()) {
            $data['business_license_file'] = $this->uploadFile($files['business_license_file'], 'business_license');
        }
        if (!empty($files['company_profile_file']) && $files['company_profile_file']->isValid()) {
            $data['company_profile_file'] = $this->uploadFile($files['company_profile_file'], 'company_profile');
        }

        $registrationId = $this->storeRegistrationModel->insert($data);

        if ($registrationId) {
            return $this->response->setJSON([
                'success' => true,
                'message' => '입점신청이 완료되었습니다. 검토 후 연락드리겠습니다.'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => '신청 처리 중 오류가 발생했습니다. 다시 시도해주세요.'
            ]);
        }
    }

    /**
     * 파일 업로드 처리
     */
    private function uploadFile($file, $type)
    {
        $uploadPath = WRITEPATH . 'uploads/store_registration/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fileName = $type . '_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file->getExtension();
        $file->move($uploadPath, $fileName);
        
        return $fileName;
    }
}