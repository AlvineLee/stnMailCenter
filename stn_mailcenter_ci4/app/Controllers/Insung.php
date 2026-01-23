<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InsungCcListModel;
use App\Models\InsungCompanyListModel;
use App\Models\InsungUsersListModel;
use App\Models\InsungStatsModel;

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

        // 모바일 여부 확인
        $userAgent = $this->request->getUserAgent();
        $isMobile = $userAgent->isMobile();

        // 검색 필터
        $filters = [
            'cc_code' => $this->request->getGet('cc_code') ?? '',
            'cc_name' => $this->request->getGet('cc_name') ?? '',
            'api_cccode' => $this->request->getGet('api_cccode') ?? ''
        ];
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = $isMobile ? 15 : 20;

        // 콜센터 목록 조회 (API 정보 조인, 페이징)
        $result = $this->ccListModel->getCcListWithApiInfo($filters, $page, $perPage);

        // API 연계센타 목록 조회 (select box용)
        $insungApiListModel = new \App\Models\InsungApiListModel();
        $apiList = $insungApiListModel->getMainApiList();

        $data = [
            'title' => '콜센터 관리',
            'content_header' => [
                'title' => '콜센터 관리',
                'description' => '콜센터 정보를 관리합니다.'
            ],
            'cc_list' => $result['cc_list'],
            'api_list' => $apiList,
            'filters' => $filters,
            'pagination' => $result['pagination'],
            'total_count' => $result['total_count']
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

        // 모바일 여부 확인
        $userAgent = $this->request->getUserAgent();
        $isMobile = $userAgent->isMobile();

        // 콜센터 필터 파라미터
        $ccCodeFilter = $this->request->getGet('cc_code') ?? 'all';
        $searchName = $this->request->getGet('search_name') ?? '';
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = $isMobile ? 15 : 20;

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
        
        // 비즈니스 로직: 회사 데이터 포맷팅
        $formattedCompanyList = [];
        foreach ($companyList as $company) {
            $formattedCompany = $company;
            
            // 상태 라벨 변환
            $isActive = ($company['is_active'] ?? 0) == 1;
            $formattedCompany['status_label'] = $isActive ? '활성' : '비활성';
            $formattedCompany['status_class'] = $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
            
            $formattedCompanyList[] = $formattedCompany;
        }
        
        // PaginationHelper 생성 (뷰에서 사용)
        $queryParams = array_filter([
            'cc_code' => ($ccCodeFilter !== 'all') ? $ccCodeFilter : null,
            'search_name' => !empty($searchName) ? $searchName : null
        ], function($value) {
            return $value !== null && $value !== '';
        });
        
        $paginationHelper = new \App\Libraries\PaginationHelper(
            $totalCount,
            $perPage,
            $page,
            base_url('insung/company-list'),
            $queryParams
        );
        
        // 페이징 정보 포맷팅 (뷰에서 사용)
        $paginationInfo = [
            'start_item' => ($page - 1) * $perPage + 1,
            'end_item' => min($page * $perPage, $totalCount),
            'total_count' => $totalCount
        ];

        $data = [
            'title' => '고객사 관리',
            'content_header' => [
                'title' => '고객사 관리',
                'description' => '고객사 정보를 관리합니다.'
            ],
            'company_list' => $formattedCompanyList,
            'cc_list' => $ccList,
            'cc_code_filter' => $ccCodeFilter,
            'search_name' => $searchName,
            'pagination' => $pagination,
            'pagination_info' => $paginationInfo,
            'pagination_helper' => $paginationHelper
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

        // 서브도메인 설정 확인
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
        $subdomainApiCodes = null;
        $isSubdomainAccess = ($currentSubdomain !== 'default');
        
        // 서브도메인으로 접근한 경우 API 정보 조회
        if ($isSubdomainAccess && $subdomainCompCode) {
            $subdomainApiCodes = $subdomainConfig->getCurrentApiCodes();
        }

        // 모바일 여부 확인
        $userAgent = $this->request->getUserAgent();
        $isMobile = $userAgent->isMobile();

        // 필터 파라미터 (검색 필드 변경: 고객사, 회사명, 아이디, 회원명)
        // 서브도메인으로 접근한 경우 comp_code를 서브도메인 값으로 고정
        $compCodeFilter = $isSubdomainAccess && $subdomainCompCode ? $subdomainCompCode : ($this->request->getGet('comp_code') ?? 'all');
        $compName = $this->request->getGet('comp_name') ?? '';
        $userId = $this->request->getGet('user_id') ?? '';
        $userName = $this->request->getGet('user_name') ?? '';
        $page = (int)($this->request->getGet('page') ?? 1);
        $perPage = $isMobile ? 15 : 20;

        // 전체 고객사 갯수 조회
        $db = \Config\Database::connect();
        
        // 서브도메인으로 접근한 경우 해당 고객사만 조회
        $companyListForSelect = [];
        $builder = $db->table('tbl_company_list c');
        $builder->select('c.comp_code, c.comp_name, c.cc_idx');
        $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
        
        if ($isSubdomainAccess && $subdomainCompCode) {
            // 서브도메인으로 접근한 경우 해당 고객사만 조회
            $builder->where('c.comp_code', $subdomainCompCode);
            $totalCompanyCount = 1;
        } else {
            // 메인도메인 접근 시 전체 고객사 조회
            $totalCompanyCount = $db->table('tbl_company_list')->countAllResults();
        }
        
        $builder->orderBy('c.comp_name', 'ASC');
        $query = $builder->get();
        if ($query !== false) {
            $companyListForSelect = $query->getResultArray();
        }
        
        // 각 고객사별 회원 수 및 API 정보 조회
        $insungApiListModel = new \App\Models\InsungApiListModel();
        foreach ($companyListForSelect as &$company) {
            // 회원 수 조회
            $userCountBuilder = $db->table('tbl_users_list');
            $userCountBuilder->where('user_company', $company['comp_code']);
            $company['user_count'] = $userCountBuilder->countAllResults();
            
            // API 정보 조회 (cc_code를 통해)
            // cc_code가 없으면 company_list에서 cc_idx로 조회
            if (empty($company['cc_code']) && !empty($company['cc_idx'])) {
                $ccBuilder = $db->table('tbl_cc_list');
                $ccBuilder->select('cc_code');
                $ccBuilder->where('idx', $company['cc_idx']);
                $ccResult = $ccBuilder->get()->getRowArray();
                if ($ccResult) {
                    $company['cc_code'] = $ccResult['cc_code'] ?? '';
                }
            }
            
            if (!empty($company['cc_code'])) {
                // api_code(cc_code)로 API 정보 조회
                $apiInfo = $insungApiListModel->getApiInfoByCode($company['cc_code']);
                if ($apiInfo) {
                    $company['m_code'] = $apiInfo['mcode'] ?? '';
                    $company['cc_code'] = $apiInfo['cccode'] ?? '';
                    $company['token'] = $apiInfo['token'] ?? '';
                    $company['api_idx'] = $apiInfo['idx'] ?? null;
                }
            }
        }
        unset($company); // 참조 해제

        // 회원 목록 조회 (기존 모델 메서드 사용, 성능 최적화)
        // 서브도메인으로 접근한 경우 comp_code를 서브도메인 값으로 고정
        if ($isSubdomainAccess && $subdomainCompCode) {
            $compCodeForQuery = $subdomainCompCode;
        } else {
            $compCodeForQuery = ($compCodeFilter !== 'all') ? $compCodeFilter : null;
        }
        
        // user_type = 3인 경우 소속 콜센터로 필터링
        // 서브도메인으로 접근한 경우 comp_code만 사용 (cc_code 필터링 제외)
        $ccCodeForQuery = null;
        if (!$isSubdomainAccess && $userType == '3' && $sessionCcCode) {
            // 메인도메인 접근 시 user_type=3인 경우만 cc_code 필터링
            $ccCodeForQuery = $sessionCcCode;
        }
        // 서브도메인으로 접근한 경우 comp_code만으로 필터링하므로 ccCodeForQuery는 null 유지
        
        // comp_name 검색이 있는 경우, 먼저 고객사 코드를 찾아서 필터링
        if ($compName && trim($compName) !== '') {
            $compNameTrimmed = trim($compName);
            // comp_name으로 고객사 코드 검색 (인덱스 활용)
            $compBuilder = $db->table('tbl_company_list');
            $compBuilder->select('comp_code');
            $compBuilder->like('comp_name', $compNameTrimmed);
            
            // 서브도메인으로 접근한 경우 해당 고객사만 검색
            if ($isSubdomainAccess && $subdomainCompCode) {
                $compBuilder->where('comp_code', $subdomainCompCode);
            }
            
            $compQuery = $compBuilder->get();
            $matchingCompCodes = [];
            if ($compQuery !== false) {
                $compResults = $compQuery->getResultArray();
                foreach ($compResults as $comp) {
                    $matchingCompCodes[] = $comp['comp_code'];
                }
            }
            
            // 매칭되는 고객사가 없으면 빈 결과 반환
            if (empty($matchingCompCodes)) {
                $userList = [];
                $totalCount = 0;
                helper('pagination');
                $pagination = calculatePagination(0, $page, $perPage);
            } else {
                // 선택한 고객사가 있고 매칭되지 않으면 빈 결과
                if ($compCodeForQuery && !in_array($compCodeForQuery, $matchingCompCodes)) {
                    $userList = [];
                    $totalCount = 0;
                    helper('pagination');
                    $pagination = calculatePagination(0, $page, $perPage);
                } else {
                    // 매칭되는 고객사 코드로 필터링 (WHERE IN 사용)
                    $searchName = $userName;
                    $searchId = $userId;
                    
                    // 모델 메서드 확장 필요: WHERE IN 지원
                    // 일단 첫 번째 고객사만 사용 (성능 최적화)
                    $effectiveCompCode = $compCodeForQuery ?: $matchingCompCodes[0];
                    $result = $this->usersListModel->getAllUserListWithFilters(
                        $ccCodeForQuery,
                        $effectiveCompCode,
                        $searchName,
                        $searchId,
                        $page,
                        $perPage
                    );
                    $userList = $result['users'];
                    $totalCount = $result['total_count'];
                    $pagination = $result['pagination'];
                }
            }
        } else {
            // comp_name 검색이 없는 경우 기존 로직 사용
            $searchName = $userName;
            $searchId = $userId;
            
            // 디버깅 로그
            log_message('debug', 'Insung::userList - getAllUserListWithFilters params: ccCode=' . ($ccCodeForQuery ?? 'null') . ', compCode=' . ($compCodeForQuery ?? 'null') . ', searchName=' . ($searchName ?? '') . ', searchId=' . ($searchId ?? ''));
            
            $result = $this->usersListModel->getAllUserListWithFilters(
                $ccCodeForQuery,
                $compCodeForQuery,
                $searchName,
                $searchId,
                $page,
                $perPage
            );
            
            $userList = $result['users'];
            $totalCount = $result['total_count'];
            $pagination = $result['pagination'];
            
            // 디버깅 로그
            log_message('debug', 'Insung::userList - result: totalCount=' . $totalCount . ', userList count=' . count($userList));
        }
        
        // 비즈니스 로직: 회원 데이터 포맷팅
        $formattedUserList = [];
        $totalCountForNumbering = $pagination['total_count'] ?? 0;
        $currentPageForNumbering = $pagination['current_page'] ?? 1;
        $perPageForNumbering = $pagination['per_page'] ?? 20;
        $startNumber = $totalCountForNumbering - (($currentPageForNumbering - 1) * $perPageForNumbering);
        $rowNumber = $startNumber;
        
        // user_type 라벨 매핑
        $userTypeLabels = [
            '1' => '메인 사이트 관리자',
            '3' => '콜센터 관리자',
            '5' => '일반 고객'
        ];
        
        foreach ($userList as $user) {
            $formattedUser = $user;
            
            // 역순 번호 할당
            $formattedUser['row_number'] = $rowNumber--;
            
            // user_type 라벨 변환
            $userType = $user['user_type'] ?? '5';
            $formattedUser['user_type_label'] = $userTypeLabels[$userType] ?? '일반 고객';
            $formattedUser['user_type_class'] = 'bg-blue-100 text-blue-800';
            
            $formattedUserList[] = $formattedUser;
        }
        
        // PaginationHelper 생성 (뷰에서 사용)
        $queryParams = array_filter([
            'comp_code' => ($compCodeFilter !== 'all') ? $compCodeFilter : null,
            'comp_name' => !empty($compName) ? $compName : null,
            'user_id' => !empty($userId) ? $userId : null,
            'user_name' => !empty($userName) ? $userName : null
        ], function($value) {
            return $value !== null && $value !== '';
        });
        
        $paginationHelper = new \App\Libraries\PaginationHelper(
            $pagination['total_count'],
            $pagination['per_page'],
            $pagination['current_page'],
            base_url('insung/user-list'),
            $queryParams
        );
        
        $data = [
            'title' => '고객사 회원정보',
            'content_header' => [
                'title' => '고객사 회원정보',
                'description' => '고객사 회원 정보를 조회합니다.'
            ],
            'user_list' => $formattedUserList,
            'company_list' => $companyListForSelect,
            'total_company_count' => $totalCompanyCount,
            'comp_code_filter' => $compCodeFilter,
            'comp_name' => $compName,
            'user_id' => $userId,
            'user_name' => $userName,
            'pagination' => $pagination,
            'pagination_helper' => $paginationHelper,
            'is_subdomain_access' => $isSubdomainAccess,
            'subdomain_comp_code' => $subdomainCompCode,
            'subdomain_api_codes' => $subdomainApiCodes
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
        $db = \Config\Database::connect();
        
        if ($ccCode && $ccCode !== 'all') {
            $companyList = $this->companyListModel->getAllCompanyListWithCc($ccCode);
            $companies = $companyList['companies'] ?? [];
        } else {
            // 전체 고객사 조회 (cc_code 포함, 고객사명 오름차순)
            $builder = $db->table('tbl_company_list c');
            $builder->select('c.comp_code, c.comp_name, c.cc_idx, cc.cc_code');
            $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
            $builder->orderBy('c.comp_name', 'ASC');
            $query = $builder->get();
            $companies = $query !== false ? $query->getResultArray() : [];
        }
        
        // 각 고객사별 회원 수 및 API 정보 조회
        $insungApiListModel = new \App\Models\InsungApiListModel();
        foreach ($companies as &$company) {
            // 회원 수 조회
            $userCountBuilder = $db->table('tbl_users_list');
            $userCountBuilder->where('user_company', $company['comp_code']);
            $company['user_count'] = $userCountBuilder->countAllResults();
            
            // API 정보 조회 (cc_code를 통해)
            // cc_code가 없으면 company_list에서 cc_idx로 조회
            if (empty($company['cc_code']) && !empty($company['cc_idx'])) {
                $ccBuilder = $db->table('tbl_cc_list');
                $ccBuilder->select('cc_code');
                $ccBuilder->where('idx', $company['cc_idx']);
                $ccResult = $ccBuilder->get()->getRowArray();
                if ($ccResult) {
                    $company['cc_code'] = $ccResult['cc_code'] ?? '';
                }
            }
            
            if (!empty($company['cc_code'])) {
                // api_code(cc_code)로 API 정보 조회
                $apiInfo = $insungApiListModel->getApiInfoByCode($company['cc_code']);
                if ($apiInfo) {
                    $company['m_code'] = $apiInfo['mcode'] ?? '';
                    $company['cc_code'] = $apiInfo['cccode'] ?? '';
                    $company['token'] = $apiInfo['token'] ?? '';
                    $company['api_idx'] = $apiInfo['idx'] ?? null;
                }
            }
        }
        unset($company); // 참조 해제
        
        return $this->response->setJSON([
            'success' => true,
            'companies' => $companies
        ]);
    }

    /**
     * 인성 API 거래처별 직원목록 조회 (AJAX)
     */
    public function getInsungMemberList()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        // daumdata 로그인 체크
        $loginType = session()->get('login_type');
        $userType = session()->get('user_type');
        
        if ($loginType !== 'daumdata' || !in_array($userType, ['1', '3'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '접근 권한이 없습니다.'
            ])->setStatusCode(403);
        }

        // 서브도메인 설정 확인
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
        $subdomainApiCodes = null;
        $isSubdomainAccess = ($currentSubdomain !== 'default');
        
        // 서브도메인으로 접근한 경우 API 정보 조회
        if ($isSubdomainAccess && $subdomainCompCode) {
            $subdomainApiCodes = $subdomainConfig->getCurrentApiCodes();
        }
        
        // 파라미터 받기 (JSON 요청이므로 getJSON으로 받기)
        // comp_no는 tbl_company_list.comp_code 값
        $jsonData = $this->request->getJSON(true);
        
        // JSON이 없으면 POST/GET으로 시도
        if (empty($jsonData)) {
            $compNo = $this->request->getPost('comp_no') ?? $this->request->getGet('comp_no') ?? '';
            $compName = $this->request->getPost('comp_name') ?? $this->request->getGet('comp_name') ?? '';
            $userId = $this->request->getPost('user_id') ?? $this->request->getGet('user_id') ?? '';
            $userName = $this->request->getPost('user_name') ?? $this->request->getGet('user_name') ?? '';
            $page = (int)($this->request->getPost('page') ?? $this->request->getGet('page') ?? 1);
            $limit = (int)($this->request->getPost('limit') ?? $this->request->getGet('limit') ?? 20);
        } else {
            // JSON에서 파라미터 추출
            $compNo = $jsonData['comp_no'] ?? '';
            $compName = $jsonData['comp_name'] ?? '';
            $userId = $jsonData['user_id'] ?? '';
            $userName = $jsonData['user_name'] ?? '';
            $page = (int)($jsonData['page'] ?? 1);
            $limit = (int)($jsonData['limit'] ?? 20);
        }
        
        // 서브도메인으로 접근한 경우 comp_no를 서브도메인 값으로 고정
        if ($isSubdomainAccess && $subdomainCompCode) {
            $compNo = $subdomainCompCode;
        }
        
        // 디버깅: 받은 파라미터 로깅
        log_message('debug', 'Insung::getInsungMemberList - Received params: comp_no=' . $compNo . ', comp_name=' . $compName . ', user_id=' . $userId . ', user_name=' . $userName . ', is_subdomain=' . ($isSubdomainAccess ? 'true' : 'false'));

        // comp_no를 기준으로 DB에서 API 정보 조회 (로그인 시 사용한 조인 쿼리와 동일)
        // comp_no는 tbl_company_list.comp_code 값
        // m_code, cc_code, token은 tbl_api_list에서 조회
        // 조인: tbl_company_list -> tbl_cc_list -> tbl_api_list
        
        if (empty($compNo)) {
            log_message('error', 'Insung::getInsungMemberList - comp_no is empty');
            return $this->response->setJSON([
                'success' => false,
                'message' => '고객사를 선택해주세요.'
            ])->setStatusCode(400);
        }
        
        // 서브도메인으로 접근한 경우 서브도메인의 API 정보 사용
        if ($isSubdomainAccess && $subdomainApiCodes && !empty($subdomainApiCodes['m_code']) && !empty($subdomainApiCodes['cc_code'])) {
            $mCode = $subdomainApiCodes['m_code'];
            $ccCode = $subdomainApiCodes['cc_code'];
            
            // token과 api_idx는 DB에서 조회
            $db = \Config\Database::connect();
            $apiBuilder = $db->table('tbl_api_list');
            $apiBuilder->select('token, idx as api_idx');
            $apiBuilder->where('mcode', $mCode);
            $apiBuilder->where('cccode', $ccCode);
            $apiQuery = $apiBuilder->get();
            
            if ($apiQuery !== false) {
                $apiInfo = $apiQuery->getRowArray();
                if ($apiInfo) {
                    $token = $apiInfo['token'] ?? '';
                    $apiIdx = $apiInfo['api_idx'] ?? null;
                } else {
                    log_message('error', 'Insung::getInsungMemberList - API info not found for subdomain: m_code=' . $mCode . ', cc_code=' . $ccCode);
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '서브도메인의 API 정보를 찾을 수 없습니다.'
                    ])->setStatusCode(400);
                }
            } else {
                log_message('error', 'Insung::getInsungMemberList - Failed to query API info for subdomain');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 정보 조회 중 오류가 발생했습니다.'
                ])->setStatusCode(500);
            }
        } else {
            // 메인도메인 접근 시 기존 로직 사용
            // tbl_company_list -> tbl_cc_list -> tbl_api_list
            // cc_apicode와 tbl_api_list.idx를 매칭
            $db = \Config\Database::connect();
            $builder = $db->table('tbl_company_list b');
            $builder->select('
                b.comp_code,
                d.mcode as m_code,
                d.cccode as cc_code,
                d.token,
                d.idx as api_idx
            ');
            $builder->join('tbl_cc_list c', 'b.cc_idx = c.idx', 'inner');
            // cc_apicode와 tbl_api_list.idx를 매칭
            $builder->join('tbl_api_list d', 'c.cc_apicode = d.idx', 'inner');
            $builder->where('b.comp_code', $compNo);
            
            $query = $builder->get();
            
            if ($query === false) {
                log_message('error', 'Insung::getInsungMemberList - Failed to query API info for comp_no: ' . $compNo);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 정보 조회 중 오류가 발생했습니다.'
                ])->setStatusCode(500);
            }
            
            $apiInfo = $query->getRowArray();
            
            if (!$apiInfo || empty($apiInfo['m_code']) || empty($apiInfo['cc_code']) || empty($apiInfo['token'])) {
                log_message('error', 'Insung::getInsungMemberList - API info not found or incomplete for comp_no: ' . $compNo);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '선택한 고객사의 API 정보를 찾을 수 없습니다.'
                ])->setStatusCode(400);
            }
            
            $mCode = $apiInfo['m_code'];
            $ccCode = $apiInfo['cc_code'];
            $token = $apiInfo['token'];
            $apiIdx = $apiInfo['api_idx'] ?? null;
        }
        
        log_message('debug', 'Insung::getInsungMemberList - Found API info: comp_no=' . $compNo . ', m_code=' . $mCode . ', cc_code=' . $ccCode . ', api_idx=' . $apiIdx);

        try {
            // 인성 API 호출
            $insungApiService = new \App\Libraries\InsungApiService();
            $result = $insungApiService->getCustomerAttachedList(
                $mCode,
                $ccCode,
                $token,
                $compNo,
                $compName,
                $userId,
                $userName,
                '', // telNo
                '', // custName
                '', // deptName
                '', // staffName
                $page,
                $limit,
                $apiIdx
            );

            if (!$result) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 호출 실패'
                ])->setStatusCode(500);
            }

            // 응답 처리
            if (is_array($result) && isset($result[0])) {
                $code = $result[0]->code ?? $result[0]['code'] ?? '';
                $msg = $result[0]->msg ?? $result[0]['msg'] ?? '';
                
                // 디버깅: API 응답 로깅
                log_message('debug', 'Insung::getInsungMemberList - API Response: code=' . $code . ', msg=' . $msg);
                log_message('debug', 'Insung::getInsungMemberList - Response structure: count=' . count($result) . ', result[1] type=' . (isset($result[1]) ? gettype($result[1]) : 'not set'));

                if ($code === '1000') {
                    // 성공 시 데이터 추출
                    // API 응답 구조: [0] = 처리결과, [1] = 페이지정보, [2]부터 = 회원 데이터 배열
                    $members = [];
                    $pageInfo = null;
                    
                    // 페이지 정보 추출 (result[1])
                    if (isset($result[1])) {
                        $pageInfo = is_object($result[1]) ? (array)$result[1] : $result[1];
                        log_message('debug', 'Insung::getInsungMemberList - Page info: ' . json_encode($pageInfo));
                    }
                    
                    // 회원 데이터 추출 (result[2]부터 또는 result[1]에 배열이 있는 경우)
                    if (isset($result[2]) && is_array($result[2])) {
                        // result[2]가 배열인 경우
                        $members = $result[2];
                    } elseif (isset($result[1]) && is_array($result[1]) && isset($result[1][0]) && is_array($result[1][0])) {
                        // result[1]이 배열의 배열인 경우
                        $members = $result[1];
                    } else {
                        // result[2]부터 모든 요소가 회원 데이터인 경우
                        for ($i = 2; $i < count($result); $i++) {
                            if (is_array($result[$i]) || is_object($result[$i])) {
                                $members[] = is_object($result[$i]) ? (array)$result[$i] : $result[$i];
                            }
                        }
                    }
                    
                    // total_record는 페이지 정보에서 가져오기
                    $totalCount = 0;
                    if ($pageInfo && isset($pageInfo['total_record'])) {
                        $totalCount = (int)$pageInfo['total_record'];
                    } else {
                        $totalCount = count($members);
                    }
                    
                    log_message('debug', 'Insung::getInsungMemberList - Extracted members count: ' . count($members) . ', total_record: ' . $totalCount);
                    
                    // 디버깅: 첫 번째 회원 데이터의 필드명 확인
                    if (!empty($members) && isset($members[0])) {
                        $firstMember = is_object($members[0]) ? (array)$members[0] : $members[0];
                        log_message('debug', 'Insung::getInsungMemberList - First member fields: ' . json_encode(array_keys($firstMember)));
                        log_message('debug', 'Insung::getInsungMemberList - First member data: ' . json_encode($firstMember));
                    }

                    // 등록된 user_ccode 목록 조회 (성능 최적화: IN 조건으로 한 번에 조회)
                    $registeredCCodes = [];
                    if (!empty($members)) {
                        // 모든 회원의 c_code 수집
                        $cCodes = [];
                        foreach ($members as $member) {
                            $memberArray = is_object($member) ? (array)$member : $member;
                            $cCode = $memberArray['c_code'] ?? '';
                            if (!empty($cCode) && $cCode !== '-') {
                                $cCodes[] = $cCode;
                            }
                        }
                        
                        // 중복 제거
                        $cCodes = array_unique($cCodes);
                        
                        // c_code가 있으면 DB에서 등록된 user_ccode 조회
                        if (!empty($cCodes)) {
                            $db = \Config\Database::connect();
                            $builder = $db->table('tbl_users_list');
                            $builder->select('user_ccode');
                            $builder->whereIn('user_ccode', $cCodes);
                            $builder->distinct();
                            $query = $builder->get();
                            
                            if ($query !== false) {
                                $results = $query->getResultArray();
                                foreach ($results as $row) {
                                    if (!empty($row['user_ccode'])) {
                                        $registeredCCodes[] = $row['user_ccode'];
                                    }
                                }
                            }
                            
                            log_message('debug', 'Insung::getInsungMemberList - Registered c_codes count: ' . count($registeredCCodes) . ' out of ' . count($cCodes));
                        }
                    }

                    return $this->response->setJSON([
                        'success' => true,
                        'code' => $code,
                        'message' => $msg,
                        'members' => $members,
                        'total_count' => $totalCount,
                        'page_info' => $pageInfo,
                        'registered_c_codes' => $registeredCCodes // 등록된 c_code 목록
                    ]);
                } else {
                    log_message('error', 'Insung::getInsungMemberList - API Error: code=' . $code . ', msg=' . $msg);
                    return $this->response->setJSON([
                        'success' => false,
                        'code' => $code,
                        'message' => $msg ?: '인성 API 호출 중 오류가 발생했습니다.'
                    ])->setStatusCode(400);
                }
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => '응답 형식 오류'
            ])->setStatusCode(500);

        } catch (\Exception $e) {
            log_message('error', 'Insung::getInsungMemberList - Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '서버 오류: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 인성회원 사용 (tbl_users_list에 insert duplicate update)
     */
    public function useInsungMember()
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
            // JSON 요청 본문에서 파라미터 받기
            $input = $this->request->getJSON(true);
            
            $userCcode = $input['user_ccode'] ?? '';
            $userId = $input['user_id'] ?? '';
            $userName = $input['user_name'] ?? '';
            $userDept = $input['user_dept'] ?? '';
            $userTel1 = $input['user_tel1'] ?? '';
            $userTel2 = $input['user_tel2'] ?? '';
            $userCompany = $input['user_company'] ?? '';
            $userType = $input['user_type'] ?? '5';
            
            // 필수 필드 검증
            if (empty($userCcode)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '사용자코드가 필요합니다.'
                ])->setStatusCode(400);
            }
            
            if (empty($userId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '아이디가 필요합니다.'
                ])->setStatusCode(400);
            }
            
            // tbl_users_list에 insert duplicate update 처리
            $db = \Config\Database::connect();
            
            // 암호화 헬퍼 인스턴스 생성
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            $encryptedFields = ['user_pass', 'user_name', 'user_tel1', 'user_tel2'];
            
            // user_id를 기준으로 중복 체크 (user_id가 unique key라고 가정)
            $userData = [
                'user_ccode' => $userCcode,
                'user_id' => $userId,
                'user_name' => $userName,
                'user_dept' => $userDept,
                'user_tel1' => $userTel1,
                'user_company' => $userCompany,
                'user_type' => $userType,
                'user_pass' => $userId // user_pass에 user_id 값 동일하게 설정
            ];
            
            // user_tel2가 있으면 추가
            if (!empty($userTel2)) {
                $userData['user_tel2'] = $userTel2;
            }
            
            // 암호화 처리
            $userData = $encryptionHelper->encryptFields($userData, $encryptedFields);
            
            // INSERT ... ON DUPLICATE KEY UPDATE 처리
            $builder = $db->table('tbl_users_list');
            
            // 먼저 user_id로 기존 데이터 확인
            $existingUser = $builder->where('user_id', $userId)->get()->getRowArray();
            
            if ($existingUser) {
                // 기존 데이터 업데이트
                $updateData = [
                    'user_ccode' => $userCcode,
                    'user_name' => $userName,
                    'user_dept' => $userDept,
                    'user_tel1' => $userTel1,
                    'user_company' => $userCompany,
                    'user_type' => $userType,
                    'user_pass' => $userId // user_pass에 user_id 값 동일하게 설정
                ];
                
                if (!empty($userTel2)) {
                    $updateData['user_tel2'] = $userTel2;
                }
                
                // 암호화 처리
                $updateData = $encryptionHelper->encryptFields($updateData, $encryptedFields);
                
                $result = $builder->where('user_id', $userId)->update($updateData);
                
                if ($result) {
                    log_message('info', "Insung::useInsungMember - Updated user: user_id={$userId}, user_ccode={$userCcode}");
                    
                    // user_id가 있으면 getMemberDetail API 호출하여 상세 정보 업데이트
                    if (!empty($userId) && !empty($userCompany)) {
                        $this->updateMemberDetailFromApi($db, $userCcode, $userId, $userCompany);
                    }
                    
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => '회원 정보가 업데이트되었습니다.',
                        'action' => 'updated'
                    ]);
                } else {
                    log_message('error', "Insung::useInsungMember - Failed to update user: user_id={$userId}");
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '회원 정보 업데이트에 실패했습니다.'
                    ])->setStatusCode(500);
                }
            } else {
                // 새 데이터 삽입
                $result = $builder->insert($userData);
                
                if ($result) {
                    log_message('info', "Insung::useInsungMember - Inserted user: user_id={$userId}, user_ccode={$userCcode}");
                    
                    // user_id가 있으면 getMemberDetail API 호출하여 상세 정보 업데이트
                    if (!empty($userId) && !empty($userCompany)) {
                        $this->updateMemberDetailFromApi($db, $userCcode, $userId, $userCompany);
                    }
                    
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => '회원이 등록되었습니다.',
                        'action' => 'inserted'
                    ]);
                } else {
                    $error = $db->error();
                    log_message('error', "Insung::useInsungMember - Failed to insert user: user_id={$userId}, error=" . json_encode($error));
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '회원 등록에 실패했습니다: ' . ($error['message'] ?? '알 수 없는 오류')
                    ])->setStatusCode(500);
                }
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Insung::useInsungMember - Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '서버 오류: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 인성회원 일괄 처리 (현재 페이지의 아이디 있는 회원만)
     */
    public function batchUseInsungMembers()
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
            // JSON 요청 본문에서 파라미터 받기
            $input = $this->request->getJSON(true);
            
            if (empty($input['members']) || !is_array($input['members'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '회원 데이터가 필요합니다.'
                ])->setStatusCode(400);
            }
            
            $members = $input['members'];
            $db = \Config\Database::connect();
            $builder = $db->table('tbl_users_list');
            
            $successCount = 0;
            $failCount = 0;
            $errors = [];
            
            foreach ($members as $member) {
                try {
                    $userCcode = $member['c_code'] ?? '';
                    $userId = $member['user_id'] ?? '';
                    $userName = $member['user_name'] ?? $member['cust_name'] ?? '';
                    $userDept = $member['dept_name'] ?? '';
                    $userTel1 = $member['tel_no1'] ?? '';
                    $userTel2 = $member['tel_no2'] ?? '';
                    $userCompany = $member['company_code'] ?? '';
                    $userType = '5'; // 기본값
                    
                    // 필수 필드 검증
                    if (empty($userCcode) || empty($userId)) {
                        $failCount++;
                        $errors[] = "user_id: {$userId} - 필수 필드 누락";
                        continue;
                    }
                    
                    // user_id를 기준으로 중복 체크
                    $existingUser = $builder->where('user_id', $userId)->get()->getRowArray();
                    
                    $userData = [
                        'user_ccode' => $userCcode,
                        'user_id' => $userId,
                        'user_name' => $userName,
                        'user_dept' => $userDept,
                        'user_tel1' => $userTel1,
                        'user_company' => $userCompany,
                        'user_type' => $userType,
                        'user_pass' => $userId // user_pass에 user_id 값 동일하게 설정
                    ];
                    
                    // user_tel2가 있으면 추가
                    if (!empty($userTel2)) {
                        $userData['user_tel2'] = $userTel2;
                    }
                    
                    if ($existingUser) {
                        // 기존 데이터 업데이트
                        $updateData = [
                            'user_ccode' => $userCcode,
                            'user_name' => $userName,
                            'user_dept' => $userDept,
                            'user_tel1' => $userTel1,
                            'user_company' => $userCompany,
                            'user_type' => $userType,
                            'user_pass' => $userId
                        ];
                        
                        if (!empty($userTel2)) {
                            $updateData['user_tel2'] = $userTel2;
                        }
                        
                        $result = $builder->where('user_id', $userId)->update($updateData);
                        
                        if ($result) {
                            $successCount++;
                            
                            // user_id가 있으면 getMemberDetail API 호출하여 상세 정보 업데이트
                            if (!empty($userId) && !empty($userCompany)) {
                                $this->updateMemberDetailFromApi($db, $userCcode, $userId, $userCompany);
                            }
                        } else {
                            $failCount++;
                            $errors[] = "user_id: {$userId} - 업데이트 실패";
                        }
                    } else {
                        // 새 데이터 삽입
                        $result = $builder->insert($userData);
                        
                        if ($result) {
                            $successCount++;
                            
                            // user_id가 있으면 getMemberDetail API 호출하여 상세 정보 업데이트
                            if (!empty($userId) && !empty($userCompany)) {
                                $this->updateMemberDetailFromApi($db, $userCcode, $userId, $userCompany);
                            }
                        } else {
                            $failCount++;
                            $dbError = $db->error();
                            $errors[] = "user_id: {$userId} - 삽입 실패: " . ($dbError['message'] ?? '알 수 없는 오류');
                        }
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = "user_id: " . ($member['user_id'] ?? 'unknown') . " - " . $e->getMessage();
                    log_message('error', 'Insung::batchUseInsungMembers - Member processing error: ' . $e->getMessage());
                }
            }
            
            $message = "일괄 처리가 완료되었습니다. 성공: {$successCount}건, 실패: {$failCount}건";
            
            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Insung::batchUseInsungMembers - Exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '서버 오류: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
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
                    throw new \Exception('파일을 읽��� 수 없습니다.');
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

    /**
     * 인성 API를 통해 회원 상세 정보를 조회하고 업데이트
     * 
     * @param object $db 데이터베이스 연결 객체
     * @param string $userCcode 사용자 코드 (c_code)
     * @param string $userId 사용자 ID
     * @param string $userCompany 회사 코드 (comp_no)
     * @return bool 성공 여부
     */
    private function updateMemberDetailFromApi($db, $userCcode, $userId, $userCompany)
    {
        try {
            // user_company를 통해 API 정보 조회 (tbl_company_list -> tbl_cc_list -> tbl_api_list)
            $builder = $db->table('tbl_company_list b');
            $builder->select('
                b.comp_code,
                d.mcode as m_code,
                d.cccode as cc_code,
                d.token,
                d.idx as api_idx
            ');
            $builder->join('tbl_cc_list c', 'b.cc_idx = c.idx', 'inner');
            $builder->join('tbl_api_list d', 'c.cc_apicode = d.idx', 'inner');
            $builder->where('b.comp_code', $userCompany);
            
            $query = $builder->get();
            
            if ($query === false) {
                log_message('error', "Insung::updateMemberDetailFromApi - Failed to query API info for comp_code: {$userCompany}");
                return false;
            }
            
            $apiInfo = $query->getRowArray();
            
            if (!$apiInfo || empty($apiInfo['m_code']) || empty($apiInfo['cc_code']) || empty($apiInfo['token'])) {
                log_message('error', "Insung::updateMemberDetailFromApi - API info not found or incomplete for comp_code: {$userCompany}");
                return false;
            }
            
            $mCode = $apiInfo['m_code'];
            $ccCode = $apiInfo['cc_code'];
            $token = $apiInfo['token'];
            $apiIdx = $apiInfo['api_idx'] ?? null;
            
            // getMemberDetail API 호출
            $insungApiService = new \App\Libraries\InsungApiService();
            $result = $insungApiService->getMemberDetail($mCode, $ccCode, $token, $userId, $apiIdx);
            
            if (!$result) {
                log_message('error', "Insung::updateMemberDetailFromApi - API call failed for user_id: {$userId}");
                return false;
            }
            
            // API 응답 처리
            if (is_array($result) && isset($result[0])) {
                $code = $result[0]->code ?? $result[0]['code'] ?? '';
                
                if ($code === '1000' && isset($result[1])) {
                    // 성공 시 상세 정보 추출
                    $memberDetail = is_object($result[1]) ? (array)$result[1] : $result[1];
                    
                    // 필드 매핑 (사용자 제공 SQL 기준)
                    $compNo = $memberDetail['comp_no'] ?? '';
                    $deptName = $memberDetail['dept_name'] ?? '';
                    $chargeName = $memberDetail['charge_name'] ?? '';
                    $telNumber = $memberDetail['tel_number'] ?? '';
                    $sido = $memberDetail['sido'] ?? '';
                    $gugun = $memberDetail['gugun'] ?? '';
                    $dongName = $memberDetail['dong_name'] ?? '';
                    $ri = $memberDetail['ri'] ?? '';
                    $lon = $memberDetail['lon'] ?? '';
                    $lat = $memberDetail['lat'] ?? '';
                    
                    // 주소 조합 (sido + gugun + dong_name + ri)
                    $userAddr = trim(implode(' ', array_filter([$sido, $gugun, $dongName, $ri])));
                    
                    // user_ccode를 기준으로 업데이트
                    $updateBuilder = $db->table('tbl_users_list');
                    $updateData = [
                        'user_company' => $compNo,
                        'user_dept' => $deptName,
                        'user_name' => $chargeName,
                        'user_tel1' => $telNumber,
                        'user_addr' => $userAddr,
                        'user_sido' => $sido,
                        'user_gungu' => $gugun,
                        'user_dong' => $dongName,
                        'user_addr_detail' => $ri,
                        'user_lon' => $lon,
                        'user_lat' => $lat,
                        'user_id' => $userId
                    ];
                    
                    $updateResult = $updateBuilder->where('user_ccode', $userCcode)->update($updateData);
                    
                    if ($updateResult) {
                        log_message('info', "Insung::updateMemberDetailFromApi - Updated member detail for user_ccode: {$userCcode}, user_id: {$userId}");
                        return true;
                    } else {
                        log_message('error', "Insung::updateMemberDetailFromApi - Failed to update member detail for user_ccode: {$userCcode}");
                        return false;
                    }
                } else {
                    $msg = $result[0]->msg ?? $result[0]['msg'] ?? '';
                    log_message('error', "Insung::updateMemberDetailFromApi - API Error: code={$code}, msg={$msg}");
                    return false;
                }
            }
            
            log_message('error', "Insung::updateMemberDetailFromApi - Invalid API response format for user_id: {$userId}");
            return false;

        } catch (\Exception $e) {
            log_message('error', "Insung::updateMemberDetailFromApi - Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 콜센터 추가 (AJAX)
     */
    public function addCcList()
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

        try {
            $ccCode = trim($this->request->getPost('cc_code') ?? '');
            $ccName = trim($this->request->getPost('cc_name') ?? '');
            $ccApicode = $this->request->getPost('cc_apicode') ?? '';
            $ccQuickid = trim($this->request->getPost('cc_quickid') ?? '');
            $ccTelno = trim($this->request->getPost('cc_telno') ?? '');
            $ccMemo = trim($this->request->getPost('cc_memo') ?? '');
            $ccDongname = trim($this->request->getPost('cc_dongname') ?? '');
            $ccAddr = trim($this->request->getPost('cc_addr') ?? '');
            $ccAddrDetail = trim($this->request->getPost('cc_addr_detail') ?? '');

            // 필수 필드 검증
            if (empty($ccCode)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '회사코드를 입력해주세요.'
                ]);
            }
            if (empty($ccName)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '회사명을 입력해주세요.'
                ]);
            }
            if (empty($ccApicode)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API연계센타를 선택해주세요.'
                ]);
            }
            if (empty($ccQuickid)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '퀵아이디를 입력해주세요.'
                ]);
            }
            if (empty($ccDongname)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '주소검색 후 입력해주세요.'
                ]);
            }

            // 회사코드 중복 체크
            if ($this->ccListModel->isCodeExists($ccCode)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "입력하신 {$ccCode} 회사코드는 이미 사용중입니다."
                ]);
            }

            // 퀵아이디 중복 체크
            if ($this->ccListModel->isQuickIdExists($ccQuickid)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "입력하신 {$ccQuickid} 아이디는 이미 사용중입니다."
                ]);
            }

            // API 정보 조회
            $insungApiListModel = new \App\Models\InsungApiListModel();
            $apiInfo = $insungApiListModel->find($ccApicode);

            if (!$apiInfo) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'API 정보를 찾을 수 없습니다.'
                ]);
            }

            // DB에 콜센터 추가
            $insertData = [
                'cc_code' => $ccCode,
                'cc_name' => $ccName,
                'cc_apicode' => $ccApicode,
                'cc_quickid' => $ccQuickid,
                'cc_telno' => $ccTelno,
                'cc_memo' => $ccMemo,
                'cc_dongname' => $ccDongname,
                'cc_addr' => $ccAddr,
                'cc_addr_detail' => $ccAddrDetail
            ];

            $result = $this->ccListModel->insert($insertData);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => '콜센터가 등록되었습니다.',
                    'idx' => $result
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '콜센터 등록에 실패했습니다.'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Insung::addCcList - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '서버 오류: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 콜센터 수정 (AJAX)
     */
    public function updateCcList()
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

        try {
            $idx = $this->request->getPost('idx');
            $ccCode = trim($this->request->getPost('cc_code') ?? '');
            $ccName = trim($this->request->getPost('cc_name') ?? '');
            $ccApicode = $this->request->getPost('cc_apicode') ?? '';
            $ccQuickid = trim($this->request->getPost('cc_quickid') ?? '');
            $ccTelno = trim($this->request->getPost('cc_telno') ?? '');
            $ccMemo = trim($this->request->getPost('cc_memo') ?? '');
            $ccDongname = trim($this->request->getPost('cc_dongname') ?? '');
            $ccAddr = trim($this->request->getPost('cc_addr') ?? '');
            $ccAddrDetail = trim($this->request->getPost('cc_addr_detail') ?? '');

            if (empty($idx)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '수정할 콜센터 정보가 없습니다.'
                ]);
            }

            // 기존 데이터 확인
            $existing = $this->ccListModel->find($idx);
            if (!$existing) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '콜센터 정보를 찾을 수 없습니다.'
                ]);
            }

            // 회사코드 중복 체크 (자신 제외)
            if ($this->ccListModel->isCodeExists($ccCode, $idx)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "입력하신 {$ccCode} 회사코드는 이미 사용중입니다."
                ]);
            }

            // 퀵아이디 중복 체크 (자신 제외)
            if ($this->ccListModel->isQuickIdExists($ccQuickid, $idx)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "입력하신 {$ccQuickid} 아이디는 이미 사용중입니다."
                ]);
            }

            // DB 업데이트
            $updateData = [
                'cc_code' => $ccCode,
                'cc_name' => $ccName,
                'cc_apicode' => $ccApicode,
                'cc_quickid' => $ccQuickid,
                'cc_telno' => $ccTelno,
                'cc_memo' => $ccMemo,
                'cc_dongname' => $ccDongname,
                'cc_addr' => $ccAddr,
                'cc_addr_detail' => $ccAddrDetail
            ];

            $result = $this->ccListModel->update($idx, $updateData);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => '콜센터 정보가 수정되었습니다.'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => '콜센터 수정에 실패했습니다.'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Insung::updateCcList - ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => '서버 오류: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 콜센터 상세 조회 (AJAX)
     */
    public function getCcDetail()
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
                'message' => '콜센터 정보가 없습니다.'
            ]);
        }

        $ccInfo = $this->ccListModel->find($idx);
        if (!$ccInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '콜센터 정보를 찾을 수 없습니다.'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'cc' => $ccInfo
        ]);
    }

    /**
     * API 연계센타 목록 조회 (select box용)
     */
    public function getApiListForSelect()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $insungApiListModel = new \App\Models\InsungApiListModel();
        // api_gbn = 'M'인 것만 조회 (메인 API 센타)
        $apiList = $insungApiListModel->getMainApiList();

        return $this->response->setJSON([
            'success' => true,
            'api_list' => $apiList
        ]);
    }

    /**
     * 퀵사별 통계 대시보드 (user_type = 1 접근 가능)
     */
    public function stats()
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

        $statsModel = new InsungStatsModel();
        $db = \Config\Database::connect();

        // 기간 유형 (기본: 일별)
        $periodType = $this->request->getGet('period_type') ?? 'daily';
        $ccCode = $this->request->getGet('cc_code') ?? null;
        $periodStart = $this->request->getGet('period_start') ?? null;

        // 유효한 기간 유형 체크
        $validPeriodTypes = ['daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'yearly'];
        if (!in_array($periodType, $validPeriodTypes)) {
            $periodType = 'daily';
        }

        if ($ccCode === 'all' || $ccCode === '') {
            $ccCode = null;
        }

        // 사용 가능한 기간 목록 조회 (최근 30개)
        $availablePeriods = $db->table('tbl_insung_stats')
            ->select('period_start, period_label')
            ->where('period_type', $periodType)
            ->where('cc_code IS NULL')
            ->orderBy('period_start', 'DESC')
            ->limit(30)
            ->get()
            ->getResultArray();

        // 선택된 기간이 없으면 최신 기간 사용
        if (empty($periodStart) && !empty($availablePeriods)) {
            $periodStart = $availablePeriods[0]['period_start'];
        }

        // 기간별 통계 조회 (최근 30개) - 테이블용
        $stats = $statsModel->getStatsByPeriodType($periodType, $ccCode, 30);

        // 전체 통계 조회 (cc_code = null) - 테이블용
        $totalStats = $statsModel->getStatsByPeriodType($periodType, null, 30);

        // 선택된 기간의 콜센터별 통계 조회 (전체)
        $topCallCenters = [];
        if ($periodStart) {
            $topCallCenters = $statsModel->getTopCallCenters($periodType, $periodStart, 'total_orders', 50);
        }

        // 선택된 기간의 전체 통계 (요약 카드용)
        $selectedPeriodStats = null;
        if ($periodStart) {
            $selectedPeriodStats = $statsModel->getTotalStats($periodType, $periodStart);
        }

        // 콜센터 목록 조회 (필터용) - MAX로 비어있지 않은 api_name 선택
        $ccList = $db->table('tbl_insung_stats')
            ->select('cc_code, MAX(api_name) as api_name')
            ->where('cc_code IS NOT NULL')
            ->groupBy('cc_code')
            ->orderBy('api_name', 'ASC')
            ->get()
            ->getResultArray();

        // 기간 유형 라벨
        $periodTypeLabels = [
            'daily' => '일별',
            'weekly' => '주별',
            'monthly' => '월별',
            'quarterly' => '분기별',
            'semi_annual' => '반기별',
            'yearly' => '연별'
        ];

        $data = [
            'title' => '퀵사별 통계',
            'content_header' => [
                'title' => '퀵사별 통계',
                'description' => '퀵사별 주문 통계를 조회합니다.'
            ],
            'stats' => $stats,
            'total_stats' => $totalStats,
            'top_call_centers' => $topCallCenters,
            'selected_period_stats' => $selectedPeriodStats,
            'cc_list' => $ccList,
            'available_periods' => $availablePeriods,
            'period_type' => $periodType,
            'period_type_label' => $periodTypeLabels[$periodType] ?? $periodType,
            'period_type_labels' => $periodTypeLabels,
            'period_start' => $periodStart,
            'cc_code_filter' => $ccCode
        ];

        return view('insung/stats', $data);
    }

    /**
     * 통계 데이터 API (AJAX - 차트용)
     */
    public function getStatsData()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $statsModel = new InsungStatsModel();

        $periodType = $this->request->getGet('period_type') ?? 'daily';
        $ccCode = $this->request->getGet('cc_code') ?? null;
        $limit = (int)($this->request->getGet('limit') ?? 30);

        if ($ccCode === 'all' || $ccCode === '') {
            $ccCode = null;
        }

        // 통계 조회
        $stats = $statsModel->getStatsByPeriodType($periodType, $ccCode, $limit);

        // 차트 데이터 포맷
        $chartData = [
            'labels' => [],
            'total_orders' => [],
            'completed_orders' => [],
            'cancelled_orders' => [],
            'completion_rate' => []
        ];

        // 역순으로 (오래된 것부터)
        $stats = array_reverse($stats);

        foreach ($stats as $stat) {
            $chartData['labels'][] = $stat['period_label'];
            $chartData['total_orders'][] = (int)$stat['total_orders'];
            $chartData['completed_orders'][] = (int)$stat['state_30_count'];
            $chartData['cancelled_orders'][] = (int)$stat['state_40_count'];
            $chartData['completion_rate'][] = (float)$stat['completion_rate'];
        }

        return $this->response->setJSON([
            'success' => true,
            'chart_data' => $chartData,
            'stats' => $stats
        ]);
    }
}
