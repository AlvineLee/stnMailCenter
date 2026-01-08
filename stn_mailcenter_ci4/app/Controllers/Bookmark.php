<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookmarkModel;
use App\Models\RecentListModel;
use App\Models\OrderModel;
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
                            'addr_road' => $item->location ?? '', // 도로명 주소 (location 필드 사용)
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
     * 최근 사용 기록 팝업 화면 (tbl_orders에서 조회)
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

        $loginType = session()->get('login_type');
        
        // 로그인 타입에 따라 user_id 결정
        if ($loginType === 'daumdata') {
            // daumdata 로그인: tbl_users_list의 idx를 user_id로 사용
            $userId = session()->get('user_idx');
            $insungUserId = session()->get('user_id'); // insung_user_id 비교용 (문자열)
        } else {
            // STN 로그인: user_id 사용
            $userId = session()->get('user_id');
            $insungUserId = null; // STN 로그인은 insung_user_id 비교 불필요
        }
        
        if (!$userId) {
            $data = [
                'type' => $type,
                'keyword' => $keyword,
                'orders' => []
            ];
            return view('recent/popup', $data);
        }
        
        // tbl_orders에서 본인이 등록한 주문만 조회 (접수자 이름 포함)
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_orders o');
        
        // 접수자 이름을 위해 tbl_users_list와 조인 (collation 충돌 해결)
        // insung_user_id가 실제 접수자를 나타내므로 이것으로만 조인
        $builder->select('o.*, u.user_name as receiver_name');
        $builder->join('tbl_users_list u', "CONVERT(o.insung_user_id USING utf8mb4) COLLATE utf8mb4_general_ci = CONVERT(u.user_id USING utf8mb4) COLLATE utf8mb4_general_ci", 'left', false);
        
        // 본인 주문만 필터링: user_id로 필터링 (daumdata는 user_idx, STN은 user_id)
        $builder->where('o.user_id', $userId);
        
        // daumdata 로그인 시 insung_user_id로도 필터링하여 본인 주문만 확실히 가져오기
        // AND 조건으로 둘 다 만족하는 경우만 가져오기
        if ($loginType === 'daumdata' && $insungUserId) {
            // insung_user_id 직접 비교 (collation 문제가 있으면 PHP에서 필터링)
            $builder->where('o.insung_user_id', $insungUserId);
        }
        
        // 최근 1개월 데이터만 조회
        $oneMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
        $builder->where('o.save_date >=', $oneMonthAgo);
        
        // 검색 키워드가 있으면 출발지/도착지 정보로 검색
        if (!empty($keyword)) {
            $builder->groupStart();
            $builder->like('o.departure_company_name', $keyword);
            $builder->orLike('o.departure_contact', $keyword);
            $builder->orLike('o.departure_address', $keyword);
            $builder->orLike('o.destination_company_name', $keyword);
            $builder->orLike('o.destination_contact', $keyword);
            $builder->orLike('o.destination_address', $keyword);
            $builder->groupEnd();
        }
        
        // 정렬: 접수날짜 최신순
        $builder->orderBy('o.save_date', 'DESC');
        $builder->limit(50); // 최근 50개만 (성능 개선)
        
        $query = $builder->get();
        
        if ($query === false) {
            log_message('error', 'Bookmark::recentPopup - Query failed');
            $orders = [];
        } else {
            $orders = $query->getResultArray();
            
            // 전화번호 필드 및 receiver_name 복호화 처리
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno'];
            foreach ($orders as &$order) {
                // 전화번호 필드 복호화
                $order = $encryptionHelper->decryptFields($order, $phoneFields);
                
                // receiver_name 복호화 (tbl_users_list.user_name이 암호화되어 있을 수 있음)
                if (isset($order['receiver_name']) && !empty($order['receiver_name'])) {
                    $decrypted = $encryptionHelper->decrypt($order['receiver_name']);
                    // 복호화 결과가 원본과 다르면 복호화 성공
                    if ($decrypted !== $order['receiver_name']) {
                        $order['receiver_name'] = $decrypted;
                    }
                }
            }
            unset($order); // 참조 해제
            
            // insung_user_id가 본인과 같은 항목을 우선 정렬 (PHP에서 정렬)
            if ($loginType === 'daumdata' && $insungUserId && !empty($orders)) {
                usort($orders, function($a, $b) use ($insungUserId) {
                    $aIsMine = (!empty($a['insung_user_id']) && $a['insung_user_id'] === $insungUserId) ? 0 : 1;
                    $bIsMine = (!empty($b['insung_user_id']) && $b['insung_user_id'] === $insungUserId) ? 0 : 1;
                    
                    // 본인 주문 우선
                    if ($aIsMine !== $bIsMine) {
                        return $aIsMine - $bIsMine;
                    }
                    
                    // 같은 우선순위면 접수날짜 최신순
                    $aDate = strtotime($a['save_date'] ?? '1970-01-01');
                    $bDate = strtotime($b['save_date'] ?? '1970-01-01');
                    return $bDate - $aDate;
                });
            }
        }

        $data = [
            'type' => $type,
            'keyword' => $keyword,
            'orders' => $orders
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
        
        // 즐겨찾기 데이터 준비 (테이블 구조에 맞게 필드명 매핑)
        // 좌표는 INT 타입이므로 숫자로 변환 (0이면 빈 값으로 처리)
        $lon = !empty($postData['lon']) ? (int)$postData['lon'] : 0;
        $lat = !empty($postData['lat']) ? (int)$postData['lat'] : 0;
        if ($lon == 0) $lon = '';
        if ($lat == 0) $lat = '';
        
        $bookmarkData = [
            'user_id' => $userId,
            'company_name' => $postData['c_name'] ?? '',
            'c_telno' => $postData['c_tel'] ?? '',
            'dept_name' => $postData['c_dept'] ?? '',
            'charge_name' => $postData['c_charge'] ?? '',
            'lon' => $lon,
            'lat' => $lat,
            'addr_jibun' => $postData['c_addr'] ?? '', // 지번 주소
            'addr_road' => $postData['c_addr2'] ?? ''  // 도로명 주소
        ];

        // 필수 필드 검증
        if (empty($bookmarkData['company_name']) || empty($bookmarkData['c_telno'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '상호명과 연락처는 필수 항목입니다.'
            ]);
        }
        
        // 좌표가 없고 주소가 있으면 인성 API로 좌표 조회
        if ((empty($bookmarkData['lon']) || empty($bookmarkData['lat'])) && (!empty($bookmarkData['addr_jibun']) || !empty($bookmarkData['addr_road']))) {
            log_message('info', "Bookmark::add - 좌표 조회 시작. 현재 좌표: lon={$bookmarkData['lon']}, lat={$bookmarkData['lat']}");
            log_message('info', "Bookmark::add - 주소 정보: addr_jibun={$bookmarkData['addr_jibun']}, addr_road={$bookmarkData['addr_road']}");
            
            // daumdata 로그인 시에만 인성 API 사용
            if ($loginType === 'daumdata') {
                $mCode = session()->get('m_code');
                $ccCode = session()->get('cc_code');
                log_message('info', "Bookmark::add - daumdata 로그인 확인. m_code={$mCode}, cc_code={$ccCode}");
                
                // m_code와 cc_code로 API 정보 조회
                if (!empty($mCode) && !empty($ccCode)) {
                    $apiInfo = $this->apiListModel->getApiInfoByMcodeCccode($mCode, $ccCode);
                } else {
                    // m_code가 없으면 cc_code만으로 조회 시도 (하위 호환성)
                    log_message('warning', "Bookmark::add - m_code가 없어 cc_code만으로 조회 시도. cc_code={$ccCode}");
                    $apiInfo = $this->apiListModel->getApiInfoByCcCode($ccCode);
                }
                
                if ($apiInfo) {
                    $mcode = $apiInfo['mcode'] ?? '';
                    $cccode = $apiInfo['cccode'] ?? '';
                    $token = $this->insungApiService->getTokenKey($apiInfo['idx']);
                    
                    log_message('info', "Bookmark::add - API 정보 조회 성공. mcode={$mcode}, cccode={$cccode}, api_idx={$apiInfo['idx']}");
                    
                    // 주소 조합 (지번 주소 우선, 없으면 도로명 주소)
                    $addressForCoord = !empty($bookmarkData['addr_jibun']) ? $bookmarkData['addr_jibun'] : $bookmarkData['addr_road'];
                    
                    // 주소 정리 (강원특별자치도 → 강원도, 지하 제거)
                    if (!empty($addressForCoord)) {
                        $originalAddress = $addressForCoord;
                        $addressForCoord = str_replace("강원특별자치도", "강원도", $addressForCoord);
                        $addressForCoord = preg_replace('/\s*지하\s*/i', ' ', $addressForCoord);
                        $addressForCoord = preg_replace('/\s+/', ' ', $addressForCoord);
                        $addressForCoord = trim($addressForCoord);
                        
                        log_message('info', "Bookmark::add - 주소 정리 완료. 원본: {$originalAddress}, 정리 후: {$addressForCoord}");
                        log_message('info', "Bookmark::add - 인성 API 좌표 조회 호출 시작. 주소: {$addressForCoord}");
                        
                        $coordResult = $this->insungApiService->getAddressCoordinates($mcode, $cccode, $token, $addressForCoord, $apiInfo['idx']);
                        
                        log_message('info', "Bookmark::add - 인성 API 좌표 조회 응답: " . json_encode($coordResult, JSON_UNESCAPED_UNICODE));
                        
                        if ($coordResult && isset($coordResult['lon']) && isset($coordResult['lat'])) {
                            $lon = $coordResult['lon'];
                            $lat = $coordResult['lat'];
                            
                            log_message('info', "Bookmark::add - 좌표 추출 성공. 원본 값: lon={$lon}, lat={$lat}");
                            
                            // 좌표가 0이 아니고 유효한 값인지 확인
                            $lonNum = floatval($lon);
                            $latNum = floatval($lat);
                            
                            log_message('info', "Bookmark::add - 좌표 숫자 변환: lonNum={$lonNum}, latNum={$latNum}");
                            
                            if (!empty($lon) && !empty($lat) && $lonNum > 0 && $latNum > 0) {
                                // INT 타입이므로 문자열을 정수로 변환
                                $bookmarkData['lon'] = (int)$lonNum;
                                $bookmarkData['lat'] = (int)$latNum;
                                log_message('info', "Bookmark::add - 유효한 좌표 저장 완료. lon={$bookmarkData['lon']}, lat={$bookmarkData['lat']}");
                            } else {
                                log_message('warning', "Bookmark::add - 유효하지 않은 좌표 (0 또는 음수). 주소: {$addressForCoord}, lon={$lon}, lat={$lat}, lonNum={$lonNum}, latNum={$latNum}");
                                // 좌표가 유효하지 않으면 빈 값으로 설정 (0 대신)
                                $bookmarkData['lon'] = '';
                                $bookmarkData['lat'] = '';
                            }
                        } else {
                            log_message('warning', "Bookmark::add - 좌표 조회 실패 또는 응답 형식 오류. 주소: {$addressForCoord}, 응답: " . json_encode($coordResult, JSON_UNESCAPED_UNICODE));
                            // 좌표 조회 실패 시 빈 값으로 설정
                            $bookmarkData['lon'] = '';
                            $bookmarkData['lat'] = '';
                        }
                    } else {
                        log_message('warning', "Bookmark::add - 주소가 비어있어 좌표 조회 불가");
                    }
                } else {
                    log_message('warning', "Bookmark::add - API 정보 조회 실패. m_code={$mCode}, cc_code={$ccCode}");
                    log_message('warning', "Bookmark::add - 세션 정보 확인 필요. m_code 존재: " . (!empty($mCode) ? 'Y' : 'N') . ", cc_code 존재: " . (!empty($ccCode) ? 'Y' : 'N'));
                }
            } else {
                log_message('info', "Bookmark::add - daumdata 로그인이 아니어서 좌표 조회 건너뜀. login_type={$loginType}");
            }
        } else {
            log_message('info', "Bookmark::add - 좌표 조회 조건 불만족. lon={$bookmarkData['lon']}, lat={$bookmarkData['lat']}, addr_jibun={$bookmarkData['addr_jibun']}, addr_road={$bookmarkData['addr_road']}");
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
                    'sido' => $postData['c_sido'] ?? '',
                    'gugun' => $postData['c_gungu'] ?? '',
                    'dong' => $postData['c_dong'] ?? '',
                    'address_detail' => trim(($bookmarkData['addr_jibun'] ?? '') . ' ' . ($bookmarkData['addr_road'] ?? '')),
                    'location' => $bookmarkData['addr_jibun'] ?? $bookmarkData['addr_road'] ?? '',
                    'c_code' => $postData['c_code'] ?? ''
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
