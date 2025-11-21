<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InsungCcListModel;
use App\Models\InsungCompanyListModel;
use App\Models\InsungUsersListModel;

class Insung extends BaseController
{
    protected $ccListModel;
    protected $companyListModel;
    protected $usersListModel;

    public function __construct()
    {
        $this->ccListModel = new InsungCcListModel();
        $this->companyListModel = new InsungCompanyListModel();
        $this->usersListModel = new InsungUsersListModel();
        helper('form');
    }

    /**
     * 콜센터 관리 목록 (user_type = 1 접근 가능)
     */
    public function ccList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // daumdata 로그인 및 user_type = 1 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        
        if ($loginType !== 'daumdata' || $userType != '1') {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        // 콜센터 목록 조회 (소속 고객사 수 포함)
        $ccList = $this->ccListModel->getAllCcListWithCompanyCount();

        $data = [
            'title' => '콜센터 관리',
            'content_header' => [
                'title' => '콜센터 관리',
                'description' => '콜센터 정보를 관리합니다.'
            ],
            'cc_list' => $ccList
        ];

        return view('insung/cc_list', $data);
    }

    /**
     * 고객사 관리 목록 (user_type = 1 접근 가능)
     */
    public function companyList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // daumdata 로그인 및 user_type = 1 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        
        if ($loginType !== 'daumdata' || $userType != '1') {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        // 콜센터 필터 파라미터
        $ccCodeFilter = $this->request->getGet('cc_code') ?? 'all';
        $searchName = $this->request->getGet('search_name') ?? '';
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = 20;

        // 콜센터 목록 조회 (select option용)
        $ccList = $this->ccListModel->getAllCcList();

        // 고객사 목록 조회 (콜센터와 조인, 필터 적용, 페이징)
        $result = $this->companyListModel->getAllCompanyListWithCc(
            $ccCodeFilter !== 'all' ? $ccCodeFilter : null,
            $searchName,
            $page,
            $perPage
        );
        
        $companyList = $result['companies'];
        $totalCount = $result['total_count'];
        
        // 페이징 정보 계산
        $totalPages = ceil($totalCount / $perPage);
        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_count' => $totalCount,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages
        ];

        $data = [
            'title' => '고객사 관리',
            'content_header' => [
                'title' => '고객사 관리',
                'description' => '고객사 정보를 관리합니다.'
            ],
            'company_list' => $companyList,
            'cc_list' => $ccList,
            'cc_code_filter' => $ccCodeFilter,
            'search_name' => $searchName,
            'pagination' => $pagination
        ];

        return view('insung/company_list', $data);
    }

    /**
     * 고객사회원정보 목록 (user_type = 1, 3 접근 가능)
     */
    public function userList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        // daumdata 로그인 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        $sessionCcCode = session()->get('cc_code');
        
        if ($loginType !== 'daumdata' || !in_array($userType, ['1', '3'])) {
            return redirect()->to('/')->with('error', '접근 권한이 없습니다.');
        }

        // 필터 파라미터
        $ccCodeFilter = $this->request->getGet('cc_code') ?? 'all';
        $compCodeFilter = $this->request->getGet('comp_code') ?? 'all';
        $searchName = $this->request->getGet('search_name') ?? '';
        $searchId = $this->request->getGet('search_id') ?? '';
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = 20;

        // 콜센터 목록 조회 (select option용)
        $ccList = $this->ccListModel->getAllCcList();
        
        // 고객사 목록 조회 (select option용, 콜센터 필터 적용)
        $companyListForSelect = [];
        if ($ccCodeFilter !== 'all') {
            $companyResult = $this->companyListModel->getAllCompanyListWithCc($ccCodeFilter);
            $companyListForSelect = $companyResult['companies'] ?? [];
        } else {
            // 전체 고객사 조회 (페이징 없이)
            $db = \Config\Database::connect();
            $builder = $db->table('tbl_company_list c');
            $builder->select('c.comp_code, c.comp_name');
            $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'left');
            $query = $builder->get();
            if ($query !== false) {
                $companyListForSelect = $query->getResultArray();
            }
        }

        // 회원 목록 조회 (필터 및 페이징 적용)
        $ccCodeForQuery = ($ccCodeFilter !== 'all') ? $ccCodeFilter : null;
        $compCodeForQuery = ($compCodeFilter !== 'all') ? $compCodeFilter : null;
        
        // user_type = 3인 경우 소속 콜센터로 필터링
        if ($userType == '3' && $sessionCcCode) {
            $ccCodeForQuery = $sessionCcCode;
        }
        
        $result = $this->usersListModel->getAllUserListWithFilters(
            $ccCodeForQuery,
            $compCodeForQuery,
            $searchName,
            $searchId,
            $page,
            $perPage
        );
        
        $userList = $result['users'];
        $pagination = $result['pagination'];
        
        $data = [
            'title' => '고객사 회원정보',
            'content_header' => [
                'title' => '고객사 회원정보',
                'description' => '고객사 회원 정보를 조회합니다.'
            ],
            'user_list' => $userList,
            'cc_list' => $ccList,
            'company_list' => $companyListForSelect,
            'cc_code_filter' => $ccCodeFilter,
            'comp_code_filter' => $compCodeFilter,
            'search_name' => $searchName,
            'search_id' => $searchId,
            'pagination' => $pagination
        ];

        return view('insung/user_list', $data);
    }

    /**
     * 콜센터별 고객사 목록 조회 (AJAX - 모달용)
     */
    public function getCompaniesByCc()
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

        $companyList = $this->companyListModel->getAllCompanyListWithCc($ccCode);
        
        return $this->response->setJSON([
            'success' => true,
            'companies' => $companyList['companies'] ?? []
        ]);
    }

    /**
     * 콜센터별 고객사 목록 조회 (AJAX - select box용)
     */
    public function getCompaniesByCcForSelect()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $ccCode = $this->request->getGet('cc_code');
        
        if ($ccCode && $ccCode !== 'all') {
            $companyList = $this->companyListModel->getAllCompanyListWithCc($ccCode);
            $companies = $companyList['companies'] ?? [];
        } else {
            // 전체 고객사 조회
            $db = \Config\Database::connect();
            $builder = $db->table('tbl_company_list c');
            $builder->select('c.comp_code, c.comp_name');
            $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'left');
            $query = $builder->get();
            $companies = $query !== false ? $query->getResultArray() : [];
        }
        
        return $this->response->setJSON([
            'success' => true,
            'companies' => $companies
        ]);
    }

    /**
     * 고객사 로고 일괄 업로드 (AJAX)
     */
    public function uploadCompanyLogos()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // daumdata 로그인 및 user_type = 1 또는 3 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        
        if ($loginType !== 'daumdata' || !in_array($userType, ['1', '3'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        try {
            // comp_idxs 배열 받기 (comp_idx를 기준으로 업데이트)
            $compIdxs = null;
            
            // 1. FormData에서 전달된 경우 (comp_codes[] 또는 comp_codes - 하위 호환성을 위해 유지)
            $postData = $this->request->getPost();
            if (isset($postData['comp_codes']) && is_array($postData['comp_codes'])) {
                $compIdxs = $postData['comp_codes']; // 실제로는 comp_idx 배열
            } elseif (isset($postData['comp_codes'])) {
                // 단일 값인 경우 배열로 변환
                $compIdxs = [$postData['comp_codes']];
            }
            
            // 2. JSON에서 전달된 경우
            if (empty($compIdxs)) {
                $inputData = $this->request->getJSON(true);
                if (!empty($inputData['comp_codes']) && is_array($inputData['comp_codes'])) {
                    $compIdxs = $inputData['comp_codes']; // 실제로는 comp_idx 배열
                }
            }
            
            if (empty($compIdxs) || !is_array($compIdxs)) {
                log_message('error', 'Insung::uploadCompanyLogos - comp_idxs is empty or not array. POST: ' . json_encode($this->request->getPost()) . ', JSON: ' . json_encode($this->request->getJSON(true)));
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '고객사를 선택해주세요.'
                ])->setStatusCode(400);
            }
            
            log_message('debug', 'Insung::uploadCompanyLogos - comp_idxs: ' . json_encode($compIdxs));

            // 파일 업로드 처리
            $file = $this->request->getFile('logo_file');
            
            if ($file && $file->isValid() && !$file->hasMoved()) {
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

                // 파일 확장자
                $extension = $file->getExtension();
                
                // 파일 내용을 먼저 읽어서 메모리에 저장 (여러 고객사에 복사하기 위해)
                $fileContent = file_get_contents($file->getTempName());
                if ($fileContent === false) {
                    throw new \Exception('파일을 읽을 수 없습니다.');
                }
                
                // 각 고객사에 대해 로고 업로드
                $successCount = 0;
                $failCount = 0;
                
                foreach ($compIdxs as $compIdx) {
                    try {
                        // comp_idx 정리 (정수로 변환)
                        $compIdx = (int)$compIdx;
                        if (empty($compIdx)) {
                            log_message('error', 'Insung::uploadCompanyLogos - Empty or invalid comp_idx in array: ' . $compIdx);
                            $failCount++;
                            continue;
                        }
                        
                        log_message('debug', 'Insung::uploadCompanyLogos - Processing comp_idx: ' . $compIdx);
                        
                        // comp_idx로 고객사 조회
                        $existingCompany = $this->companyListModel->find($compIdx);
                        if (!$existingCompany) {
                            log_message('error', 'Insung::uploadCompanyLogos - Company not found for comp_idx: ' . $compIdx);
                            $failCount++;
                            continue;
                        }
                        
                        // 디버깅: existingCompany의 키 확인
                        log_message('debug', 'Insung::uploadCompanyLogos - existingCompany keys: ' . json_encode(array_keys($existingCompany)));
                        
                        // 기존 로고 파일 삭제
                        if (!empty($existingCompany['logo_path'])) {
                            $oldLogoPath = FCPATH . $existingCompany['logo_path'];
                            if (file_exists($oldLogoPath)) {
                                @unlink($oldLogoPath);
                            }
                        }

                        // 파일명 생성 (고객사별로 고유한 파일명)
                        $compCode = $existingCompany['comp_code'] ?? $compIdx;
                        $fileName = 'comp_logo_' . $compCode . '_' . time() . '_' . uniqid() . '.' . $extension;
                        $filePath = $uploadPath . $fileName;

                        // 파일 내용을 각 고객사별로 저장
                        if (file_put_contents($filePath, $fileContent) === false) {
                            throw new \Exception('이미지 파일 저장에 실패했습니다.');
                        }

                        $logoPath = 'uploads/logos/' . $fileName;

                        // DB에 로고 경로 저장 (comp_idx를 직접 사용)
                        $updateResult = $this->companyListModel->update($compIdx, [
                            'logo_path' => $logoPath
                        ]);

                        if ($updateResult) {
                            log_message('debug', 'Insung::uploadCompanyLogos - Successfully updated logo for comp_idx: ' . $compIdx);
                            $successCount++;
                        } else {
                            $errors = $this->companyListModel->errors();
                            log_message('error', 'Insung::uploadCompanyLogos - Update failed for comp_idx ' . $compIdx . '. Errors: ' . json_encode($errors));
                            $failCount++;
                        }
                    } catch (\Exception $e) {
                        log_message('error', 'Failed to upload logo for comp_idx ' . $compIdx . ': ' . $e->getMessage());
                        $failCount++;
                    }
                }

                $message = "{$successCount}개 고객사에 로고가 업로드되었습니다.";
                if ($failCount > 0) {
                    $message .= " ({$failCount}개 실패)";
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => $message
                ]);

            } else {
                // 클립보드 이미지 처리
                $inputData = $this->request->getJSON(true);
                
                if (empty($inputData['image_data'])) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '이미지 파일이 제공되지 않았습니다.'
                    ])->setStatusCode(400);
                }

                // base64 이미지 데이터인지 확인
                if (!preg_match('/^data:image\/(\w+);base64,/', $inputData['image_data'], $matches)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '유효하지 않은 이미지 형식입니다.'
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

                $imageType = $matches[1]; // png, jpeg, gif 등
                
                // 각 고객사에 대해 로고 업로드
                $successCount = 0;
                $failCount = 0;
                
                foreach ($compIdxs as $compIdx) {
                    try {
                        // comp_idx 정리 (정수로 변환)
                        $compIdx = (int)$compIdx;
                        if (empty($compIdx)) {
                            log_message('error', 'Insung::uploadCompanyLogos - Empty or invalid comp_idx in array (clipboard): ' . $compIdx);
                            $failCount++;
                            continue;
                        }
                        
                        log_message('debug', 'Insung::uploadCompanyLogos - Processing comp_idx (clipboard): ' . $compIdx);
                        
                        // comp_idx로 고객사 조회
                        $existingCompany = $this->companyListModel->find($compIdx);
                        if (!$existingCompany) {
                            log_message('error', 'Insung::uploadCompanyLogos - Company not found for comp_idx (clipboard): ' . $compIdx);
                            $failCount++;
                            continue;
                        }
                        
                        // 기존 로고 파일 삭제
                        if (!empty($existingCompany['logo_path'])) {
                            $oldLogoPath = FCPATH . $existingCompany['logo_path'];
                            if (file_exists($oldLogoPath)) {
                                @unlink($oldLogoPath);
                            }
                        }

                        // 파일명 생성
                        $compCode = $existingCompany['comp_code'] ?? $compIdx;
                        $fileName = 'comp_logo_' . $compCode . '_' . time() . '_' . uniqid() . '.' . $imageType;
                        $filePath = $uploadPath . $fileName;

                        // 파일 저장
                        if (file_put_contents($filePath, $imageData) === false) {
                            throw new \Exception('이미지 파일 저장에 실패했습니다.');
                        }

                        $logoPath = 'uploads/logos/' . $fileName;

                        // DB에 로고 경로 저장 (comp_idx를 직접 사용)
                        $updateResult = $this->companyListModel->update($compIdx, [
                            'logo_path' => $logoPath
                        ]);

                        if ($updateResult) {
                            log_message('debug', 'Insung::uploadCompanyLogos - Successfully updated logo for comp_idx (clipboard): ' . $compIdx);
                            $successCount++;
                        } else {
                            $errors = $this->companyListModel->errors();
                            log_message('error', 'Insung::uploadCompanyLogos - Update failed for comp_idx (clipboard) ' . $compIdx . '. Errors: ' . json_encode($errors));
                            $failCount++;
                        }
                    } catch (\Exception $e) {
                        log_message('error', 'Failed to upload logo for comp_idx (clipboard) ' . $compIdx . ': ' . $e->getMessage());
                        $failCount++;
                    }
                }

                $message = "{$successCount}개 고객사에 로고가 업로드되었습니다.";
                if ($failCount > 0) {
                    $message .= " ({$failCount}개 실패)";
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => $message
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Insung::uploadCompanyLogos - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 고객사 로고 삭제 (AJAX)
     */
    public function deleteCompanyLogo($compCode)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // daumdata 로그인 및 user_type = 1 또는 3 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        
        if ($loginType !== 'daumdata' || !in_array($userType, ['1', '3'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        try {
            // 고객사 정보 조회
            $company = $this->companyListModel->getCompanyByCode($compCode);
            
            if (!$company) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '고객사 정보를 찾을 수 없습니다.'
                ])->setStatusCode(404);
            }

            // 기존 로고 파일 삭제
            if (!empty($company['logo_path'])) {
                $oldLogoPath = FCPATH . $company['logo_path'];
                if (file_exists($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }

            // DB에서 로고 경로 삭제
            $updateResult = $this->companyListModel->update($company['idx'], [
                'logo_path' => null
            ]);

            if (!$updateResult) {
                throw new \Exception('로고 삭제에 실패했습니다.');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '로고가 성공적으로 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Insung::deleteCompanyLogo - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
