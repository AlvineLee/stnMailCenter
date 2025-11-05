<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ShippingCompanyModel;

class ShippingCompany extends BaseController
{
    protected $shippingCompanyModel;

    public function __construct()
    {
        $this->shippingCompanyModel = new ShippingCompanyModel();
        helper('form');
    }

    /**
     * 운송사 관리 메인 페이지
     */
    public function index()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return redirect()->to('/dashboard')->with('error', '접근 권한이 없습니다.');
        }

        // 운송사 목록 조회 (모든 운송사)
        $companies = $this->shippingCompanyModel->getAllCompanies();

        $data = [
            'title' => '운송사 관리',
            'content_header' => [
                'title' => '운송사 관리',
                'description' => '해외특송 및 택배서비스를 제공하는 운송사를 관리합니다.'
            ],
            'companies' => $companies
        ];

        return view('admin/shipping_company', $data);
    }


    /**
     * 운송사 추가
     */
    public function create()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼관리자 권한 체크
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
            'company_code' => 'required|max_length[50]|is_unique[tbl_shipping_companies.company_code]',
            'company_name' => 'required|max_length[100]',
            'platform_code' => 'required|max_length[50]|is_unique[tbl_shipping_companies.platform_code]',
            'is_active' => 'permit_empty|in_list[0,1]',
            'contract_start_date' => 'permit_empty|valid_date',
            'contract_end_date' => 'permit_empty|valid_date'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        // API 설정을 JSON으로 변환 (필요시)
        $apiConfig = [];
        if (!empty($inputData['access_key'])) {
            $apiConfig['access_key'] = $inputData['access_key'];
        }
        if (!empty($inputData['account_no'])) {
            $apiConfig['account_no'] = $inputData['account_no'];
        }
        if (!empty($inputData['edi_code'])) {
            $apiConfig['edi_code'] = $inputData['edi_code'];
        }

        $insertData = [
            'company_code' => $inputData['company_code'],
            'company_name' => $inputData['company_name'],
            'platform_code' => $inputData['platform_code'],
            'api_config' => !empty($apiConfig) ? json_encode($apiConfig) : null,
            'is_active' => isset($inputData['is_active']) ? (int)$inputData['is_active'] : 1,
            'contract_start_date' => !empty($inputData['contract_start_date']) ? $inputData['contract_start_date'] : null,
            'contract_end_date' => !empty($inputData['contract_end_date']) ? $inputData['contract_end_date'] : null
        ];

        $result = $this->shippingCompanyModel->insert($insertData);

        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => '운송사가 추가되었습니다.',
                'data' => ['id' => $result]
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => '운송사 추가에 실패했습니다.'
            ])->setStatusCode(500);
        }
    }

    /**
     * 운송사 수정
     */
    public function update($id)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼관리자 권한 체크
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

        // 운송사 존재 확인
        $company = $this->shippingCompanyModel->find($id);
        if (!$company) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '운송사를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'company_code' => "required|max_length[50]|is_unique[tbl_shipping_companies.company_code,id,{$id}]",
            'company_name' => 'required|max_length[100]',
            'platform_code' => "required|max_length[50]|is_unique[tbl_shipping_companies.platform_code,id,{$id}]",
            'is_active' => 'permit_empty|in_list[0,1]',
            'contract_start_date' => 'permit_empty|valid_date',
            'contract_end_date' => 'permit_empty|valid_date'
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        // API 설정을 JSON으로 변환 (필요시)
        $apiConfig = [];
        if (!empty($inputData['access_key'])) {
            $apiConfig['access_key'] = $inputData['access_key'];
        }
        if (!empty($inputData['account_no'])) {
            $apiConfig['account_no'] = $inputData['account_no'];
        }
        if (!empty($inputData['edi_code'])) {
            $apiConfig['edi_code'] = $inputData['edi_code'];
        }

        $updateData = [
            'company_code' => $inputData['company_code'],
            'company_name' => $inputData['company_name'],
            'platform_code' => $inputData['platform_code'],
            'is_active' => isset($inputData['is_active']) ? (int)$inputData['is_active'] : 1,
            'contract_start_date' => !empty($inputData['contract_start_date']) ? $inputData['contract_start_date'] : null,
            'contract_end_date' => !empty($inputData['contract_end_date']) ? $inputData['contract_end_date'] : null
        ];

        if (!empty($apiConfig)) {
            $updateData['api_config'] = json_encode($apiConfig);
        }

        $result = $this->shippingCompanyModel->update($id, $updateData);

        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => '운송사가 수정되었습니다.'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => '운송사 수정에 실패했습니다.'
            ])->setStatusCode(500);
        }
    }

    /**
     * 활성화된 운송사 목록 조회 (AJAX)
     */
    public function getActive()
    {
        $companies = $this->shippingCompanyModel->getActiveCompanies();
        
        return $this->response->setJSON([
            'success' => true,
            'companies' => $companies
        ]);
    }

    /**
     * 운송사 정보 조회 (AJAX)
     */
    public function get($id)
    {
        $company = $this->shippingCompanyModel->find($id);
        
        if (!$company) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '운송사를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $company
        ]);
    }

    /**
     * 운송사 활성화/비활성화
     */
    public function toggleStatus($id)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // 슈퍼관리자 권한 체크
        if (session()->get('user_role') !== 'super_admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        // 운송사 존재 확인
        $company = $this->shippingCompanyModel->find($id);
        if (!$company) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '운송사를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        // 상태 토글
        $newStatus = $company['is_active'] == 1 ? 0 : 1;
        $result = $this->shippingCompanyModel->update($id, ['is_active' => $newStatus]);

        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => $newStatus == 1 ? '운송사가 활성화되었습니다.' : '운송사가 비활성화되었습니다.',
                'data' => ['is_active' => $newStatus]
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => '운송사 상태 변경에 실패했습니다.'
            ])->setStatusCode(500);
        }
    }
}

