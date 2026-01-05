<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\InsungApiService;
use App\Models\InsungApiListModel;

class SearchCompany extends BaseController
{
    protected $insungApiService;
    protected $insungApiListModel;

    public function __construct()
    {
        $this->insungApiService = new InsungApiService();
        $this->insungApiListModel = new InsungApiListModel();
    }

    /**
     * 고객검색 팝업 페이지
     */
    public function index()
    {
        $apiIdx = $this->request->getGet('api_idx');
        $apiCode = $this->request->getGet('api_code'); // api_code 파라미터 추가
        
        if (empty($apiIdx)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보가 없습니다.'
            ])->setStatusCode(400);
        }

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->find($apiIdx);
        
        if (!$apiInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        // api_code가 전달되지 않았으면 apiInfo에서 가져오기
        if (empty($apiCode) && !empty($apiInfo['api_code'])) {
            $apiCode = $apiInfo['api_code'];
        }

        // 서브도메인에서 comp_code 가져오기
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        $compCode = $subdomainConfig->getCurrentCompCode();
        
        // 서브도메인일 경우: 해당 서브도메인의 comp_code만 사용 (하드코딩)
        $companyList = [];
        $defaultCompCode = '';
        $isSubdomainAccess = ($currentSubdomain && $currentSubdomain !== 'default');
        
        if ($isSubdomainAccess) {
            // 서브도메인 설정에서 comp_code 가져오기
            $subdomainInfo = $subdomainConfig->getCurrentConfig();
            if (!empty($subdomainInfo['comp_code'])) {
                $defaultCompCode = $subdomainInfo['comp_code'];
                
                // 해당 comp_code의 정보만 조회
                $db = \Config\Database::connect();
                $compBuilder = $db->table('tbl_company_list');
                $compBuilder->select('comp_code, comp_name');
                $compBuilder->where('comp_code', $defaultCompCode);
                $compQuery = $compBuilder->get();
                
                if ($compQuery !== false) {
                    $compResult = $compQuery->getRowArray();
                    if ($compResult) {
                        $companyList = [$compResult]; // 단일 항목만
                    }
                }
            }
        } else {
            // 메인 도메인일 경우: api_code를 사용하여 올바른 cc_list 찾기
            $defaultCompCode = $compCode ?? '';
            
            if (!empty($apiCode) && !empty($apiIdx)) {
                $db = \Config\Database::connect();
                
                // 1. tbl_cc_list에서 cc_apicode=api_idx AND cc_code=api_code인 레코드 찾기
                $ccBuilder = $db->table('tbl_cc_list');
                $ccBuilder->select('idx');
                $ccBuilder->where('cc_apicode', $apiIdx);
                $ccBuilder->where('cc_code', $apiCode);
                $ccQuery = $ccBuilder->get();
                
                if ($ccQuery !== false) {
                    $ccResult = $ccQuery->getRowArray();
                    if ($ccResult && !empty($ccResult['idx'])) {
                        $ccIdx = $ccResult['idx'];
                        
                        // 2. tbl_company_list에서 cc_idx=ccIdx인 모든 레코드 조회
                        $compBuilder = $db->table('tbl_company_list');
                        $compBuilder->select('comp_code, comp_name');
                        $compBuilder->where('cc_idx', $ccIdx);
                        $compBuilder->orderBy('comp_name', 'ASC');
                        $compQuery = $compBuilder->get();
                        
                        if ($compQuery !== false) {
                            $companyList = $compQuery->getResultArray();
                            
                            // 서브도메인에서 가져온 comp_code가 목록에 있으면 기본값으로 설정
                            if (!empty($defaultCompCode)) {
                                $found = false;
                                foreach ($companyList as $company) {
                                    if ($company['comp_code'] === $defaultCompCode) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $defaultCompCode = ''; // 목록에 없으면 빈 값
                                }
                            } else if (!empty($companyList)) {
                                // 기본값이 없으면 첫 번째 항목을 기본값으로
                                $defaultCompCode = $companyList[0]['comp_code'] ?? '';
                            }
                        }
                    }
                }
            }
        }

        $data = [
            'title' => '고객 검색',
            'api_idx' => $apiIdx,
            'api_code' => $apiCode, // api_code 추가
            'api_info' => $apiInfo,
            'comp_code' => $defaultCompCode,
            'company_list' => $companyList // 회사 목록 추가
        ];

        return view('search_company/index', $data);
    }

    /**
     * 거래처 정보 조회 (AJAX)
     */
    public function getCompanyInfo()
    {
        $apiIdx = $this->request->getPost('api_idx');
        $compCode = $this->request->getPost('comp_code');

        if (empty($apiIdx) || empty($compCode)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '필수 파라미터가 없습니다.'
            ])->setStatusCode(400);
        }

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->find($apiIdx);
        
        if (!$apiInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        $mcode = $apiInfo['mcode'] ?? '';
        $cccode = $apiInfo['cccode'] ?? '';
        $token = $this->insungApiService->getTokenKey($apiIdx);

        // 거래처 정보 조회
        $result = $this->insungApiService->getCompanyList($mcode, $cccode, $token, $compCode, '', 1, 1, $apiIdx);

        // API 응답 구조 확인 (객체 또는 배열)
        $code = null;
        $msg = null;
        
        if (is_object($result) && isset($result->Result[0]->result_info[0]->code)) {
            // 객체 형태 응답
            $code = $result->Result[0]->result_info[0]->code;
            $msg = $result->Result[0]->result_info[0]->msg ?? '';
        } elseif (is_array($result) && isset($result[0]->code)) {
            // 배열 형태 응답
            $code = $result[0]->code;
            $msg = $result[0]->msg ?? '';
        }

        if (!$result || $code != '1000') {
            return $this->response->setJSON([
                'success' => false,
                'message' => $msg ?: '거래처 정보 조회 실패',
                'code' => $code ?: 'UNKNOWN'
            ])->setStatusCode(400);
        }

        // 결과 파싱
        $totalRecord = 0;
        $compName = '';
        
        if (is_object($result)) {
            // 객체 형태 응답 파싱
            if (isset($result->Result[1]->page_info[0]->total_record)) {
                $totalRecord = (int)$result->Result[1]->page_info[0]->total_record;
            }
            if ($totalRecord > 0 && isset($result->Result[2]->items[0]->item[0]->corp_name)) {
                $compName = $result->Result[2]->items[0]->item[0]->corp_name;
            }
        } elseif (is_array($result)) {
            // 배열 형태 응답 파싱
            if (isset($result[1]->page_info[0]->total_record)) {
                $totalRecord = (int)$result[1]->page_info[0]->total_record;
            }
            if ($totalRecord > 0 && isset($result[2]->items[0]->item[0]->corp_name)) {
                $compName = $result[2]->items[0]->item[0]->corp_name;
            }
        }

        if ($totalRecord > 0 && !empty($compName)) {
            return $this->response->setJSON([
                'success' => true,
                'comp_name' => $compName
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => '조회하신 거래처코드는 존재하지 않습니다.'
        ])->setStatusCode(404);
    }

    /**
     * 회원 리스트 조회 (AJAX)
     */
    public function search()
    {
        $apiIdx = $this->request->getPost('api_idx');
        $compCode = $this->request->getPost('comp_code');
        $chargeName = $this->request->getPost('charge_name') ?? '';
        $telNo = $this->request->getPost('tel_no') ?? '';
        $page = (int)($this->request->getPost('page') ?? 1);
        $limit = (int)($this->request->getPost('limit') ?? 15);

        if (empty($apiIdx) || empty($compCode)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '거래처코드를 입력해주세요.'
            ])->setStatusCode(400);
        }

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->find($apiIdx);
        
        if (!$apiInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        $mcode = $apiInfo['mcode'] ?? '';
        $cccode = $apiInfo['cccode'] ?? '';
        $token = $this->insungApiService->getTokenKey($apiIdx);

        // 회원 리스트 조회
        $result = $this->insungApiService->getCustomerAttachedList(
            $mcode, 
            $cccode, 
            $token, 
            $compCode,  // comp_no
            '',         // comp_name
            '',         // user_id
            '',         // user_name
            $telNo,     // tel_no
            '',         // cust_name
            '',         // dept_name
            $chargeName, // staff_name (담당자명)
            $page,      // page
            $limit,     // limit
            $apiIdx
        );

        // API 응답 구조 확인 (객체 또는 배열)
        $code = null;
        $msg = null;
        
        if (is_object($result) && isset($result->Result[0]->result_info[0]->code)) {
            // 객체 형태 응답
            $code = $result->Result[0]->result_info[0]->code;
            $msg = $result->Result[0]->result_info[0]->msg ?? '';
        } elseif (is_array($result) && isset($result[0]->code)) {
            // 배열 형태 응답
            $code = $result[0]->code;
            $msg = $result[0]->msg ?? '';
        }

        if (!$result || $code != '1000') {
            return $this->response->setJSON([
                'success' => false,
                'message' => $msg ?: '회원 리스트 조회 실패',
                'code' => $code ?: 'UNKNOWN'
            ])->setStatusCode(400);
        }

        // 결과 파싱
        $members = [];
        $totalPage = 1;
        $totalRecord = 0;
        $currentDisplayArticle = 0;
        $rawItemCount = 0; // API에서 받은 원본 아이템 개수
        
        if (is_object($result)) {
            // 객체 형태 응답 파싱
            if (isset($result->Result[1])) {
                $pageInfo = $result->Result[1];
                $totalPage = isset($pageInfo->total_page) ? (int)$pageInfo->total_page : 1;
                $totalRecord = isset($pageInfo->total_record) ? (int)$pageInfo->total_record : 0;
                // display_article 또는 current_display_article 필드 확인
                $currentDisplayArticle = isset($pageInfo->current_display_article) ? (int)$pageInfo->current_display_article : (isset($pageInfo->display_article) ? (int)$pageInfo->display_article : 0);
            }
            $loopNum = $currentDisplayArticle > 0 ? $currentDisplayArticle + 2 : 2;

            for ($i = 2; $i < $loopNum; $i++) {
                if (!isset($result->Result[$i])) continue;
                
                $rawItemCount++; // 원본 아이템 개수 카운트

                $item = $result->Result[$i];
                $telNo1 = $item->tel_no1 ?? '';
                $telNo2 = $item->tel_no2 ?? '';
                
                // 전화번호 필터링 (tel_no 파라미터가 있으면)
                if (!empty($telNo)) {
                    $telMatch = false;
                    if (!empty($telNo1) && strpos($telNo1, $telNo) !== false) {
                        $telMatch = true;
                    }
                    if (!empty($telNo2) && strpos($telNo2, $telNo) !== false) {
                        $telMatch = true;
                    }
                    if (!$telMatch) {
                        continue;
                    }
                }

                $members[] = [
                    'dept_name' => $item->dept_name ?? '',
                    'charge_name' => $item->charge_name ?? '',
                    'tel_no1' => $telNo1,
                    'tel_no2' => $telNo2,
                    'user_id' => $item->user_id ?? '',
                    'c_code' => $item->c_code ?? '',
                    'use_state' => $item->use_state ?? ''
                ];
            }
        } elseif (is_array($result)) {
            // 배열 형태 응답 파싱
            if (isset($result[1])) {
                $pageInfo = is_object($result[1]) ? $result[1] : (object)$result[1];
                $totalPage = isset($pageInfo->total_page) ? (int)$pageInfo->total_page : 1;
                $totalRecord = isset($pageInfo->total_record) ? (int)$pageInfo->total_record : 0;
                // display_article 또는 current_display_article 필드 확인
                $currentDisplayArticle = isset($pageInfo->current_display_article) ? (int)$pageInfo->current_display_article : (isset($pageInfo->display_article) ? (int)$pageInfo->display_article : 0);
            }
            $loopNum = $currentDisplayArticle > 0 ? $currentDisplayArticle + 2 : 2;

            for ($i = 2; $i < $loopNum; $i++) {
                if (!isset($result[$i])) continue;
                
                $rawItemCount++; // 원본 아이템 개수 카운트

                $telNo1 = $result[$i]->tel_no1 ?? '';
                $telNo2 = $result[$i]->tel_no2 ?? '';
                
                // 전화번호 필터링 (tel_no 파라미터가 있으면)
                if (!empty($telNo)) {
                    $telMatch = false;
                    if (!empty($telNo1) && strpos($telNo1, $telNo) !== false) {
                        $telMatch = true;
                    }
                    if (!empty($telNo2) && strpos($telNo2, $telNo) !== false) {
                        $telMatch = true;
                    }
                    if (!$telMatch) {
                        continue;
                    }
                }

                $members[] = [
                    'dept_name' => $result[$i]->dept_name ?? '',
                    'charge_name' => $result[$i]->charge_name ?? '',
                    'tel_no1' => $telNo1,
                    'tel_no2' => $telNo2,
                    'user_id' => $result[$i]->user_id ?? '',
                    'c_code' => $result[$i]->c_code ?? '',
                    'use_state' => $result[$i]->use_state ?? ''
                ];
            }
        }
        
        // 이미 등록된 사용자 체크 (tbl_users_list에서 user_id로 확인)
        if (!empty($members)) {
            $db = \Config\Database::connect();
            $userIds = array_filter(array_column($members, 'user_id'));
            
            if (!empty($userIds)) {
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->select('user_id');
                $userBuilder->whereIn('user_id', $userIds);
                $userQuery = $userBuilder->get();
                
                $registeredUserIds = [];
                if ($userQuery !== false) {
                    $registeredUsers = $userQuery->getResultArray();
                    foreach ($registeredUsers as $user) {
                        if (!empty($user['user_id'])) {
                            $registeredUserIds[] = $user['user_id'];
                        }
                    }
                }
                
                // 각 회원에 is_registered 플래그 추가
                foreach ($members as &$member) {
                    $member['is_registered'] = !empty($member['user_id']) && in_array($member['user_id'], $registeredUserIds);
                }
                unset($member);
            } else {
                // user_id가 없는 경우 모두 미등록으로 처리
                foreach ($members as &$member) {
                    $member['is_registered'] = false;
                }
                unset($member);
            }
        }
        
        // 필터링 후 실제 반환된 개수 로그
        // log_message('debug', 'SearchCompany::search - API 응답: page=' . $page . ', limit=' . $limit . ', rawItemCount=' . $rawItemCount . ', filteredCount=' . count($members) . ', totalRecord=' . $totalRecord . ', totalPage=' . $totalPage);
        // log_message('debug', 'SearchCompany::search - 검색 조건: compCode=' . $compCode . ', chargeName=' . $chargeName . ', telNo=' . $telNo);
        
        // API 응답의 페이징 정보가 검색 조건에 맞는지 확인
        // 첫 페이지이고 실제 반환된 개수가 limit보다 적으면, 그게 전체 결과라는 의미
        // 검색 조건이 있는 경우 API가 전체 레코드 수를 반환할 수 있으므로 재계산 필요
        if ($page == 1 && count($members) < $limit) {
            // 검색 조건이 있으면 API 응답의 total_record가 검색 결과에 맞지 않을 수 있음
            if (!empty($chargeName) || !empty($telNo)) {
                // 실제 반환된 개수를 기반으로 재계산
                $totalRecord = count($members);
                $totalPage = 1;
                // log_message('debug', 'SearchCompany::search - 검색 조건 적용, 페이징 정보 재계산: totalRecord=' . $totalRecord . ', totalPage=' . $totalPage);
            }
        }

        // 페이징 정보 계산
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPage,
            'total_count' => $totalRecord > 0 ? $totalRecord : count($members),
            'per_page' => $limit,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPage,
            'prev_page' => $page > 1 ? $page - 1 : 1,
            'next_page' => $page < $totalPage ? $page + 1 : $totalPage
        ];

        return $this->response->setJSON([
            'success' => true,
            'members' => $members,
            'total' => count($members),
            'pagination' => $pagination
        ]);
    }

    /**
     * 회원등록 페이지
     */
    public function register()
    {
        $apiIdx = $this->request->getGet('api_idx');
        $cCode = $this->request->getGet('ccode');

        if (empty($apiIdx) || empty($cCode)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '필수 파라미터가 없습니다.'
            ])->setStatusCode(400);
        }

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->find($apiIdx);
        
        if (!$apiInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        $mcode = $apiInfo['mcode'] ?? '';
        $cccode = $apiInfo['cccode'] ?? '';
        $token = $this->insungApiService->getTokenKey($apiIdx);

        // 회원 상세 정보 조회
        $result = $this->insungApiService->getMemberDetailByCode($mcode, $cccode, $token, $cCode, $apiIdx);

        if (!$result || (is_object($result) && isset($result->Result[0]->result_info[0]->code) && $result->Result[0]->result_info[0]->code != '1000')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => isset($result->Result[0]->result_info[0]->msg) ? $result->Result[0]->result_info[0]->msg : '회원 정보 조회 실패'
            ])->setStatusCode(400);
        }

        // 결과 파싱
        $memberInfo = null;
        if (is_object($result) && isset($result->Result[1]->item[0])) {
            $item = $result->Result[1]->item[0];
            $memberInfo = [
                'cust_name' => $item->name ?? '',
                'dong_name' => $item->basic_dong ?? '',
                'tel_no1' => $item->tel_no1 ?? '',
                'tel_no2' => $item->tel_no2 ?? '',
                'dept_name' => $item->dept_name ?? '',
                'charge_name' => $item->charge_name ?? '',
                'user_id' => $item->user_id ?? '',
                'user_code' => $item->user_code ?? $item->c_code ?? ''  // user_code 또는 c_code
            ];
        }

        $data = [
            'title' => '회원 등록',
            'api_idx' => $apiIdx,
            'c_code' => $cCode,
            'member_info' => $memberInfo
        ];

        return view('search_company/register', $data);
    }

    /**
     * 회원 등록 처리 (AJAX)
     */
    public function doRegister()
    {
        $apiIdx = $this->request->getPost('api_idx');
        $cCode = $this->request->getPost('ccode');
        $userId = $this->request->getPost('user_id');
        $password = $this->request->getPost('password');
        $passwordConfirm = $this->request->getPost('password_confirm');

        // 유효성 검사
        if (empty($apiIdx) || empty($cCode) || empty($userId) || empty($password) || empty($passwordConfirm)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '모든 필드를 입력해주세요.'
            ])->setStatusCode(400);
        }

        if ($password !== $passwordConfirm) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '비밀번호와 비밀번호 확인이 일치하지 않습니다.'
            ])->setStatusCode(400);
        }

        if (strlen($password) < 4 || strlen($password) > 20) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '비밀번호는 4자리 이상 20자리 이하로 입력하세요.'
            ])->setStatusCode(400);
        }

        // API 정보 조회
        $apiInfo = $this->insungApiListModel->find($apiIdx);
        
        if (!$apiInfo) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'API 정보를 찾을 수 없습니다.'
            ])->setStatusCode(404);
        }

        $mcode = $apiInfo['mcode'] ?? '';
        $cccode = $apiInfo['cccode'] ?? '';
        $token = $this->insungApiService->getTokenKey($apiIdx);

        // 현재 회원 정보 조회 (user_code 가져오기)
        $memberResult = $this->insungApiService->getMemberDetailByCode($mcode, $cccode, $token, $cCode, $apiIdx);
        
        $userCode = '';
        $currentUserId = '';
        if ($memberResult && isset($memberResult->Result[1]->item[0])) {
            $item = $memberResult->Result[1]->item[0];
            $userCode = $item->user_code ?? $item->c_code ?? '';
            $currentUserId = $item->user_id ?? '';
        }

        // 자기 자신의 아이디가 아닌 경우 중복 확인
        if ($currentUserId != $userId) {
            // 아이디 중복 확인
            $existResult = $this->insungApiService->checkMemberExist($mcode, $cccode, $token, $userId, $apiIdx);
            
            if (!$existResult || (is_array($existResult) && isset($existResult[0]->code) && $existResult[0]->code != '1000')) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => isset($existResult[0]->msg) ? $existResult[0]->msg : '이미 등록된 아이디이거나 조회 오류입니다.'
                ])->setStatusCode(400);
            }
        } else {
            // 자기 자신의 아이디인 경우: 임시 아이디로 변경 후 다시 변경
            $tempId = $userId . time();
            
            // 1차 변경: 임시 아이디로
            $setupResult1 = $this->insungApiService->setupCustomer($mcode, $cccode, $token, $cCode, $tempId, $password, $apiIdx);
            
            if (!$setupResult1 || (is_array($setupResult1) && isset($setupResult1[0]->code) && $setupResult1[0]->code != '1000')) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => isset($setupResult1[0]->msg) ? $setupResult1[0]->msg : '아이디 등록 실패 (1차 변경)'
                ])->setStatusCode(400);
            }
        }

        // 회원 등록/수정
        $setupResult = $this->insungApiService->setupCustomer($mcode, $cccode, $token, $cCode, $userId, $password, $apiIdx);

        if (!$setupResult || (is_array($setupResult) && isset($setupResult[0]->code) && $setupResult[0]->code != '1000')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => isset($setupResult[0]->msg) ? $setupResult[0]->msg : '아이디 등록 실패'
            ])->setStatusCode(400);
        }

        // 인성API 성공 후 tbl_users_list INSERT/UPDATE (user_code를 키값으로 사용)
        try {
            $db = \Config\Database::connect();
            $insungUsersListModel = new \App\Models\InsungUsersListModel();
            
            // user_code로 사용자 찾기 (키값)
            $existingUser = null;
            if (!empty($userCode)) {
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->where('user_ccode', $userCode);
                $userQuery = $userBuilder->get();
                $existingUser = $userQuery ? $userQuery->getRowArray() : null;
            }
            
            // user_code가 없으면 user_id로 찾기 (하위 호환성)
            if (!$existingUser) {
                $userBuilder = $db->table('tbl_users_list');
                $userBuilder->where('user_id', $userId);
                $userQuery = $userBuilder->get();
                $existingUser = $userQuery ? $userQuery->getRowArray() : null;
            }
            
            // 회원 상세 정보에서 추가 데이터 가져오기
            $memberData = [];
            if ($memberResult && isset($memberResult->Result[1]->item[0])) {
                $item = $memberResult->Result[1]->item[0];
                $memberData = [
                    'user_name' => $item->charge_name ?? '',  // 담당자명
                    'user_dept' => $item->dept_name ?? '',    // 부서명
                    'user_tel1' => $item->tel_no1 ?? '',      // 전화번호1
                    'user_tel2' => $item->tel_no2 ?? ''       // 전화번호2
                ];
            }
            
            // 서브도메인에서 comp_code 가져오기
            $subdomainConfig = config('Subdomain');
            $compCode = $subdomainConfig->getCurrentCompCode();
            
            // api_code를 사용하여 올바른 cc_list 찾기 (cc_apicode와 cc_code로 필터링)
            $ccIdx = null;
            if (!empty($apiInfo['api_code'])) {
                $ccBuilder = $db->table('tbl_cc_list');
                $ccBuilder->select('idx');
                $ccBuilder->where('cc_apicode', $apiIdx);
                $ccBuilder->where('cc_code', $apiInfo['api_code']); // api_code로 필터링
                $ccQuery = $ccBuilder->get();
                if ($ccQuery !== false) {
                    $ccResult = $ccQuery->getRowArray();
                    if ($ccResult && !empty($ccResult['idx'])) {
                        $ccIdx = $ccResult['idx'];
                    }
                }
            }
            
            if ($existingUser) {
                // 기존 사용자: UPDATE (user_code가 동일하면 아이디 변경 가능)
                $updateData = [
                    'user_id' => $userId,  // 아이디 변경 가능
                    'user_pass' => $password  // 평문 비밀번호 (tbl_users_list는 평문 저장)
                ];
                
                // user_code가 있으면 업데이트
                if (!empty($userCode)) {
                    $updateData['user_ccode'] = $userCode;
                }
                
                // 회원 상세 정보가 있으면 추가 필드도 업데이트
                if (!empty($memberData['user_name'])) {
                    $updateData['user_name'] = $memberData['user_name'];
                }
                if (!empty($memberData['user_dept'])) {
                    $updateData['user_dept'] = $memberData['user_dept'];
                }
                if (!empty($memberData['user_tel1'])) {
                    $updateData['user_tel1'] = $memberData['user_tel1'];
                }
                if (!empty($memberData['user_tel2'])) {
                    $updateData['user_tel2'] = $memberData['user_tel2'];
                }
                if ($compCode) {
                    $updateData['user_company'] = $compCode;
                }
                
                $updateResult = $insungUsersListModel->update($existingUser['idx'], $updateData);
                
                if (!$updateResult) {
                    // log_message('error', 'SearchCompany::doRegister - Failed to update tbl_users_list for user_code: ' . $userCode . ', user_id: ' . $userId);
                } else {
                    // log_message('info', 'SearchCompany::doRegister - Updated tbl_users_list for user_code: ' . $userCode . ', user_id: ' . $userId);
                }
            } else {
                // 새 사용자: INSERT
                $insertData = [
                    'user_id' => $userId,
                    'user_pass' => $password,  // 평문 비밀번호
                    'user_name' => $memberData['user_name'] ?? '',
                    'user_dept' => $memberData['user_dept'] ?? '',
                    'user_tel1' => $memberData['user_tel1'] ?? '',
                    'user_tel2' => $memberData['user_tel2'] ?? '',
                    'user_company' => $compCode ?? '',
                    'user_type' => '5'  // 기본값: 개인 사용자
                ];
                
                // user_code가 있으면 추가
                if (!empty($userCode)) {
                    $insertData['user_ccode'] = $userCode;
                }
                
                $insertResult = $insungUsersListModel->insert($insertData);
                
                if (!$insertResult) {
                    // log_message('error', 'SearchCompany::doRegister - Failed to insert tbl_users_list for user_code: ' . $userCode . ', user_id: ' . $userId);
                } else {
                    // log_message('info', 'SearchCompany::doRegister - New user inserted to tbl_users_list, user_code: ' . $userCode . ', user_id: ' . $userId);
                }
            }
        } catch (\Exception $e) {
            // log_message('error', 'SearchCompany::doRegister - Error inserting/updating tbl_users_list: ' . $e->getMessage());
            // 인성API는 성공했지만 로컬 DB 업데이트 실패는 경고만 (성공 응답 반환)
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "정상적으로 아이디와 패스워드가 등록되었습니다.\n\n아이디: {$userId} 비밀번호: {$password} 정보로 로그인 하시기 바랍니다.",
            'user_id' => $userId,
            'password' => $password
        ]);
    }
}

