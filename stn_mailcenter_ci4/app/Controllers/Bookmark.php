<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookmarkModel;
use App\Models\RecentListModel;
use App\Libraries\InsungApiService;
use App\Models\InsungApiListModel;

class Bookmark extends BaseController
{
    protected $bookmarkModel;
    protected $recentListModel;
    protected $insungApiService;
    protected $apiListModel;

    public function __construct()
    {
        $this->bookmarkModel = new BookmarkModel();
        $this->recentListModel = new RecentListModel();
        $this->insungApiService = new InsungApiService();
        $this->apiListModel = new InsungApiListModel();
    }

    /**
     * 즐겨찾기 팝업 화면 (인성 API 연동)
     * 
     * @return string
     */
    public function popup()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        $type = $this->request->getGet('type') ?? 'S'; // S: 출발지, D: 도착지, A: 경유지
        $keyword = $this->request->getGet('keyword') ?? '';

        $userId = session()->get('user_id');
        $loginType = session()->get('login_type');
        
        // 인성 API 환경 설정
        $ccCode = session()->get('cc_code');
        $apiListModel = new InsungApiListModel();
        $apiInfo = $apiListModel->getApiInfoByCcCode($ccCode);
        
        $bookmarks = [];
        $apiError = null;

        if ($apiInfo && $loginType === 'daumdata') {
            // 인성 API를 통한 즐겨찾기 리스트 조회
            $mcode = $apiInfo['mcode'] ?? '';
            $cccode = $apiInfo['cccode'] ?? '';
            $token = $this->insungApiService->getTokenKey($apiInfo['idx']);
            
            $result = $this->insungApiService->getCustomerList($mcode, $cccode, $token, $userId, $keyword, $apiInfo['idx']);
            
            if ($result && isset($result[0]->code) && $result[0]->code == '1000') {
                // 각 즐겨찾기에 대해 상세 정보 조회
                $loopNum = isset($result[1]->current_display_article) ? $result[1]->current_display_article + 2 : 2;
                
                for ($i = 2; $i < $loopNum; $i++) {
                    if (!isset($result[$i])) continue;
                    
                    $cCode = $result[$i]->company_serial ?? '';
                    if (empty($cCode)) continue;
                    
                    // 회원 상세 정보 조회
                    $detailResult = $this->insungApiService->getMemberDetailByCode($mcode, $cccode, $token, $cCode, $apiInfo['idx']);
                    
                    if ($detailResult && isset($detailResult->Result[0]->result_info[0]->code) && 
                        $detailResult->Result[0]->result_info[0]->code == '1000') {
                        
                        $item = $detailResult->Result[1]->item[0] ?? null;
                        if (!$item) continue;
                        
                        $cName = $item->name ?? '';
                        // 구찌 관련 필터링
                        if (preg_match('/구찌_테스트아이디|^\d|^구찌(?!_)|^구찌_OFFICE_\d+/', $cName)) {
                            continue;
                        }
                        $cName = preg_replace('/구찌_매장_|구찌_OFFICE_|구찌_ASEM_|구찌_/', '', $cName);
                        
                        $bookmarks[] = [
                            'c_code' => $cCode,
                            'c_name' => $cName,
                            'dept_name' => $item->dept_name ?? '',
                            'charge_name' => $item->charge_name ?? '',
                            'c_telno' => $item->tel_no1 ?? '',
                            'c_dong' => $item->basic_dong ?? '',
                            'c_addr' => $item->location ?? '',
                            'address2' => $result[$i]->address_detail ?? '',
                            'c_sido' => $item->sido ?? '',
                            'c_gungu' => $item->gugun ?? '',
                            'lon' => $item->lon ?? '',
                            'lat' => $item->lat ?? ''
                        ];
                    }
                }
            } else {
                $apiError = $result[0]->msg ?? '즐겨찾기 조회 실패';
            }
        } else {
            // 로컬 DB에서 즐겨찾기 조회
            $bookmarks = $this->bookmarkModel->getUserBookmarks($userId, $keyword);
        }

        $data = [
            'type' => $type,
            'keyword' => $keyword,
            'bookmarks' => $bookmarks,
            'apiError' => $apiError
        ];

        return view('bookmark/popup', $data);
    }

    /**
     * 최근 사용 기록 팝업 화면
     * 
     * @return string
     */
    public function recentPopup()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        $type = $this->request->getGet('type') ?? 'S';
        $keyword = $this->request->getGet('keyword') ?? '';

        $userId = session()->get('user_id');
        $recentList = $this->recentListModel->getUserRecentList($userId, $keyword);

        $data = [
            'type' => $type,
            'keyword' => $keyword,
            'recentList' => $recentList
        ];

        return view('recent/popup', $data);
    }

    /**
     * 조직도 팝업 화면 (인성 API 연동)
     * 
     * @return string
     */
    public function organizationPopup()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        $type = $this->request->getGet('type') ?? 'S';
        $searchStr = $this->request->getGet('search_str') ?? '';

        $userId = session()->get('user_id');
        $loginType = session()->get('login_type');
        
        $organizations = [];
        $apiError = null;
        $pageInfo = null; // 페이징 정보 저장용

        if ($loginType === 'daumdata') {
            $user_id = session()->get('user_company') ?? '';
            
            if (empty($user_id)) {
                $apiError = '고객사 정보가 없습니다.';
                log_message('error', 'Bookmark::organizationPopup - user_id 없음');
            } else {
                // Insung.php 방식 참고: tbl_company_list -> tbl_cc_list -> tbl_api_list
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
                $builder->join('tbl_api_list d', 'c.cc_apicode = d.idx', 'inner');
                $builder->where('b.comp_code', $user_id);
                
                $query = $builder->get();
                
                if ($query === false) {
                    $apiError = 'API 정보 조회 중 오류가 발생했습니다.';
                    log_message('error', 'Bookmark::organizationPopup - API 정보 조회 실패: user_id=' . $user_id);
                } else {
                    $apiInfo = $query->getRowArray();
                    
                    if (!$apiInfo || empty($apiInfo['m_code']) || empty($apiInfo['cc_code']) || empty($apiInfo['token'])) {
                        $apiError = 'API 정보를 찾을 수 없습니다.';
                        log_message('error', 'Bookmark::organizationPopup - API 정보 없음: user_id=' . $user_id);
                    } else {
                        $mcode = $apiInfo['m_code'];
                        $cccode = $apiInfo['cc_code'];
                        $token = $this->insungApiService->getTokenKey($apiInfo['api_idx']);
                        
                        log_message('debug', 'Bookmark::organizationPopup - API 호출 시작: mcode=' . $mcode . ', cccode=' . $cccode . ', user_id=' . $user_id);
                        
                        // 조직도 조회 (customer/list API 사용) - limit 200개씩 처리
                        $page = $this->request->getGet('page') ?? 1;
                        $limit = 200;
                        $result = $this->insungApiService->getCustomerAttachedList(
                            $mcode, $cccode, $token, $user_id, $searchStr, '', '', '', '', '', '', $page, $limit, $apiInfo['api_idx']
                        );
                        
                        // API 응답 전체 로그 출력
                        log_message('debug', 'Bookmark::organizationPopup - API 응답 타입: ' . gettype($result));
                        if (is_array($result)) {
                            log_message('debug', 'Bookmark::organizationPopup - API 응답 배열 개수: ' . count($result));
                            log_message('debug', 'Bookmark::organizationPopup - API 응답 전체: ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                        } elseif (is_object($result)) {
                            log_message('debug', 'Bookmark::organizationPopup - API 응답 객체: ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                        } else {
                            log_message('debug', 'Bookmark::organizationPopup - API 응답: ' . var_export($result, true));
                        }
                        
                        if (!$result) {
                            $apiError = 'API 호출 실패';
                            log_message('error', 'Bookmark::organizationPopup - API 호출 실패');
                        } elseif (is_array($result) && isset($result[0])) {
                            // 응답 코드 확인
                            $code = is_object($result[0]) ? ($result[0]->code ?? '') : ($result[0]['code'] ?? '');
                            $msg = is_object($result[0]) ? ($result[0]->msg ?? '') : ($result[0]['msg'] ?? '');
                            
                            log_message('debug', 'Bookmark::organizationPopup - 응답 코드: ' . $code . ', 메시지: ' . $msg);
                            
                            if ($code === '1000') {
                                // Insung.php의 getInsungMemberList 방식 참고
                                // API 응답 구조: [0] = 처리결과, [1] = 페이지정보, [2]부터 = 회원 데이터 배열
                                $members = [];
                                
                                // 페이지 정보 추출 (result[1])
                                if (isset($result[1])) {
                                    $pageInfo = is_object($result[1]) ? (array)$result[1] : $result[1];
                                    log_message('debug', 'Bookmark::organizationPopup - Page info: ' . json_encode($pageInfo));
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
                                
                                log_message('debug', 'Bookmark::organizationPopup - 추출된 회원 수: ' . count($members));
                                
                                // 각 회원 데이터 처리
                                foreach ($members as $member) {
                                    $memberArray = is_object($member) ? (array)$member : $member;
                                    $cCode = $memberArray['c_code'] ?? '';
                                    
                                    if (empty($cCode) || $cCode === '-') {
                                        continue;
                                    }
                                    
                                    log_message('debug', 'Bookmark::organizationPopup - 고객사 코드 처리: ' . $cCode);
                                    
                                    // 회원 상세 정보 조회 (주석처리)
                                    /*
                                    $detailResult = $this->insungApiService->getMemberDetailByCode($mcode, $cccode, $token, $cCode, $apiInfo['api_idx']);
                                    
                                    if ($detailResult && isset($detailResult->Result[0]->result_info[0]->code) && 
                                        $detailResult->Result[0]->result_info[0]->code == '1000') {
                                        
                                        $item = $detailResult->Result[1]->item[0] ?? null;
                                        if (!$item) {
                                            log_message('debug', 'Bookmark::organizationPopup - 상세 정보 item 없음: ' . $cCode);
                                            continue;
                                        }
                                        
                                        $cName = $item->name ?? '';
                                        // 구찌 관련 필터링
                                        if (preg_match('/구찌_테스트아이디|^\d|^구찌(?!_)|^구찌_OFFICE_\d+/', $cName)) {
                                            log_message('debug', 'Bookmark::organizationPopup - 필터링 제외: ' . $cName);
                                            continue;
                                        }
                                        $cName = preg_replace('/구찌_매장_|구찌_OFFICE_|구찌_ASEM_|구찌_/', '', $cName);
                                        
                                        $organizations[] = [
                                            'c_code' => $cCode,
                                            'c_name' => $cName,
                                            'dept_name' => $item->dept_name ?? '',
                                            'charge_name' => $item->charge_name ?? '',
                                            'c_telno' => $item->tel_no1 ?? '',
                                            'c_dong' => $item->basic_dong ?? '',
                                            'c_addr' => $item->location ?? '',
                                            'address2' => $memberArray['address_detail'] ?? '',
                                            'c_sido' => $item->sido ?? '',
                                            'c_gungu' => $item->gugun ?? '',
                                            'lon' => $item->lon ?? '',
                                            'lat' => $item->lat ?? ''
                                        ];
                                        
                                        log_message('debug', 'Bookmark::organizationPopup - 조직도 항목 추가: ' . $cName);
                                    } else {
                                        log_message('debug', 'Bookmark::organizationPopup - 상세 정보 조회 실패: ' . $cCode);
                                    }
                                    */
                                    
                                    // API 응답에서 직접 데이터 사용
                                    $cName = $memberArray['cust_name'] ?? $memberArray['company_name'] ?? $memberArray['name'] ?? '';
                                    // 구찌 관련 필터링
                                    if (preg_match('/구찌_테스트아이디|^\d|^구찌(?!_)|^구찌_OFFICE_\d+/', $cName)) {
                                        log_message('debug', 'Bookmark::organizationPopup - 필터링 제외: ' . $cName);
                                        continue;
                                    }
                                    $cName = preg_replace('/구찌_매장_|구찌_OFFICE_|구찌_ASEM_|구찌_/', '', $cName);
                                    
                                    $organizations[] = [
                                        'c_code' => $cCode,
                                        'c_name' => $cName,
                                        'dept_name' => $memberArray['dept_name'] ?? $memberArray['department_name'] ?? '',
                                        'charge_name' => $memberArray['charge_name'] ?? $memberArray['duty_name'] ?? '',
                                        'c_telno' => $memberArray['tel_number'] ?? $memberArray['tel_no1'] ?? '',
                                        'c_dong' => $memberArray['basic_dong'] ?? '',
                                        'c_addr' => $memberArray['location'] ?? $memberArray['address'] ?? '',
                                        'address2' => $memberArray['address_detail'] ?? '',
                                        'c_sido' => $memberArray['sido'] ?? '',
                                        'c_gungu' => $memberArray['gugun'] ?? '',
                                        'lon' => $memberArray['lon'] ?? '',
                                        'lat' => $memberArray['lat'] ?? ''
                                    ];
                                    
                                    log_message('debug', 'Bookmark::organizationPopup - 조직도 항목 추가: ' . $cName);
                                }
                                
                                log_message('debug', 'Bookmark::organizationPopup - 최종 조직도 개수: ' . count($organizations));
                            } else {
                                $apiError = $msg ?: '조직도 조회 실패 (코드: ' . $code . ')';
                                log_message('error', 'Bookmark::organizationPopup - API 응답 오류: ' . $apiError);
                            }
                        } else {
                            $apiError = '조직도 조회 실패: 응답 형식 오류';
                            log_message('error', 'Bookmark::organizationPopup - 응답 형식 오류: ' . gettype($result));
                        }
                    }
                }
            }
        } else {
            $apiError = 'daumdata 로그인만 조직도 기능을 사용할 수 있습니다.';
            log_message('debug', 'Bookmark::organizationPopup - 로그인 타입: ' . $loginType);
        }

        // 페이징 정보 계산
        $pagination = null;
        if (!empty($organizations) && $pageInfo !== null) {
            $totalCount = $pageInfo['total_record'] ?? count($organizations);
            $currentPage = (int)($this->request->getGet('page') ?? 1);
            $perPage = 200;
            $totalPages = $totalCount > 0 ? ceil($totalCount / $perPage) : 1;
            
            $pagination = [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_count' => $totalCount,
                'per_page' => $perPage,
                'has_prev' => $currentPage > 1,
                'has_next' => $currentPage < $totalPages,
                'prev_page' => $currentPage > 1 ? $currentPage - 1 : 1,
                'next_page' => $currentPage < $totalPages ? $currentPage + 1 : $totalPages
            ];
        }
        
        $data = [
            'type' => $type,
            'searchStr' => $searchStr,
            'organizations' => $organizations,
            'apiError' => $apiError,
            'pagination' => $pagination
        ];

        return view('organization/popup', $data);
    }

    /**
     * 즐겨찾기 추가 (AJAX)
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function add()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.']);
        }

        $userId = session()->get('user_id');
        $loginType = session()->get('login_type');
        
        $postData = $this->request->getPost();
        
        // 즐겨찾기 데이터 준비
        $bookmarkData = [
            'user_id' => $userId,
            'company_name' => $postData['c_name'] ?? '',
            'c_telno' => $postData['c_tel'] ?? '',
            'dept_name' => $postData['c_dept'] ?? '',
            'charge_name' => $postData['c_charge'] ?? '',
            'c_dong' => $postData['c_dong'] ?? '',
            'c_addr' => $postData['c_addr'] ?? '',
            'address2' => $postData['c_addr2'] ?? '',
            'c_sido' => $postData['c_sido'] ?? '',
            'gungu' => $postData['c_gungu'] ?? '',
            'lon' => $postData['lon'] ?? '',
            'lat' => $postData['lat'] ?? '',
            'c_code' => $postData['c_code'] ?? ''
        ];

        // 필수 필드 검증
        if (empty($bookmarkData['company_name']) || empty($bookmarkData['c_telno'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '상호명과 연락처는 필수 항목입니다.'
            ]);
        }
        
        // 인성 API에 등록 (daumdata 로그인 시)
        if ($loginType === 'daumdata') {
            $ccCode = session()->get('cc_code');
            $apiInfo = $this->apiListModel->getApiInfoByCcCode($ccCode);
            
            if ($apiInfo) {
                $mcode = $apiInfo['mcode'] ?? '';
                $cccode = $apiInfo['cccode'] ?? '';
                $token = $this->insungApiService->getTokenKey($apiInfo['idx']);
                
                $customerData = [
                    'company_name' => $bookmarkData['company_name'],
                    'dept_name' => $bookmarkData['dept_name'],
                    'staff_name' => $bookmarkData['charge_name'],
                    'tel_no' => $bookmarkData['c_telno'],
                    'sido' => $bookmarkData['c_sido'],
                    'gugun' => $bookmarkData['gungu'],
                    'dong' => $bookmarkData['c_dong'],
                    'address_detail' => ($bookmarkData['c_addr'] . ' ' . $bookmarkData['address2']),
                    'location' => $bookmarkData['c_addr'],
                    'c_code' => $bookmarkData['c_code']
                ];
                
                $apiResult = $this->insungApiService->registerCustomer(
                    $mcode, $cccode, $token, $userId, $customerData, $apiInfo['idx']
                );
                
                // 인성 API 실패해도 로컬 DB에는 저장 (옵션)
                // if (!$apiResult || (isset($apiResult[0]->code) && $apiResult[0]->code != '1000')) {
                //     return $this->response->setJSON([
                //         'success' => false, 
                //         'message' => $apiResult[0]->msg ?? '인성 API 등록 실패'
                //     ]);
                // }
            }
        }

        // tbl_bookmark_list에 insert/update 처리
        $result = $this->bookmarkModel->addBookmark($bookmarkData);
        
        if ($result) {
            // idx가 전달된 경우 (recent_list에서 추가한 경우) recent_list의 bn도 업데이트
            if (!empty($postData['idx'])) {
                $recentListModel = new RecentListModel();
                $recentListModel->update($postData['idx'], ['bn' => 1]);
            }
            
            return $this->response->setJSON(['success' => true, 'message' => '즐겨찾기가 추가되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '즐겨찾기 추가 실패']);
        }
    }
}
