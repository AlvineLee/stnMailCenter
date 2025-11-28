<?php

namespace App\Libraries;

use App\Models\InsungApiListModel;

/**
 * 인성 API 서비스 클래스
 * 토큰 관리 및 API 호출 기능 제공
 */
class InsungApiService
{
    //protected $baseUrl = 'http://quick.api.insungdata.com';
    protected $baseUrl = 'https://requick-api.283.co.kr/'; //new API 서버
    
    protected $apiListModel;
    protected $keyStr = ''; // 임의의 8글자 prefix (설정에서 가져올 수 있음)

    public function __construct()
    {
        $this->apiListModel = new InsungApiListModel();
        
        // key_str은 설정 파일이나 환경 변수에서 가져올 수 있음
        // 임시로 빈 문자열로 설정 (실제 사용 시 설정 필요)
        $this->keyStr = getenv('INSUNG_KEY_STR') ?: '';
    }

    /**
     * cURL을 사용한 POST 요청
     * 
     * @param string $url 요청 URL
     * @param string $param POST 데이터 (쿼리스트링 형식)
     * @return string JSON 문자열
     */
    protected function curlPost($url, $param)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            log_message('error', 'Insung API cURL Error: ' . $error);
            return false;
        }
        
        curl_close($ch);
        
        // JSON 문자열 전처리 (기존 로직과 동일)
        $response = str_replace("\\u", "[slashu]", $response);
        $response = str_replace("\\", "", $response);
        $response = str_replace("[slashu]", "\\u", $response);
        $response = str_replace("\r", "", $response);
        $response = str_replace("\n", "", $response);
        
        return $response;
    }

    /**
     * 인성 API 호출
     * 
     * @param string $url API 엔드포인트 URL
     * @param string $param 쿼리스트링 파라미터
     * @return object|false JSON 디코드된 응답 객체 또는 false
     */
    public function callApi($url, $param)
    {
        // API 호출 로그 (민감한 정보는 마스킹)
        $maskedParam = preg_replace('/(token|ukey|akey|password)=[^&]*/', '$1=***', $param);
        log_message('info', "Insung API Call: {$url}");
        log_message('debug', "Insung API Params (masked): {$maskedParam}");
        
        $response = $this->curlPost($url, $param);
        
        if ($response === false) {
            log_message('error', 'Insung API: cURL request failed');
            return false;
        }
        
        // 응답 로그 (처음 200자만)
        $responsePreview = strlen($response) > 200 ? substr($response, 0, 200) . '...' : $response;
        log_message('debug', "Insung API Response: {$responsePreview}");
        
        $decoded = json_decode($response);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Insung API JSON Decode Error: ' . json_last_error_msg());
            log_message('error', 'Response: ' . $response);
            return false;
        }
        
        // 응답 코드 로그 (배열 또는 객체 형태 모두 처리)
        if (is_array($decoded) && isset($decoded[0])) {
            // 배열 형태인 경우
            if (is_object($decoded[0]) && isset($decoded[0]->code)) {
                $code = $decoded[0]->code;
                $msg = isset($decoded[0]->msg) ? $decoded[0]->msg : 'No message';
                log_message('info', "Insung API Response Code: {$code}, Message: {$msg}");
            } elseif (is_array($decoded[0]) && isset($decoded[0]['code'])) {
                $code = $decoded[0]['code'];
                $msg = isset($decoded[0]['msg']) ? $decoded[0]['msg'] : 'No message';
                log_message('info', "Insung API Response Code: {$code}, Message: {$msg}");
            }
        } elseif (is_object($decoded) && isset($decoded->code)) {
            // 객체 형태인 경우
            $code = $decoded->code;
            $msg = isset($decoded->msg) ? $decoded->msg : 'No message';
            log_message('info', "Insung API Response Code: {$code}, Message: {$msg}");
        }
        
        return $decoded;
    }

    /**
     * 토큰 생성
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $ukey 임의의 8글자 + ckey
     * @param string $akey ukey를 MD5로 변환한 값
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 업데이트용, 있으면 직접 사용)
     * @return string|false 새 토큰 또는 false
     */
    protected function createToken($mcode, $cccode, $ukey, $akey, $apiIdx = null)
    {
        $url = $this->baseUrl . "/api/oauth/";
        // 레거시 코드와 동일하게 urlencode 사용하지 않음
        $poststring = "m_code=$mcode&cc_code=$cccode&ukey=$ukey&akey=$akey&type=JSON";
        
        log_message('debug', "Token creation - mcode: {$mcode}, cccode: {$cccode}, ukey length: " . strlen($ukey) . ", akey: " . substr($akey, 0, 10) . "...");
        
        $jresult = $this->callApi($url, $poststring);
        
        // 응답 코드 확인 (배열 또는 객체 형태 모두 처리)
        $code = '';
        $msg = '';
        if (is_array($jresult) && isset($jresult[0])) {
            if (is_object($jresult[0])) {
                $code = $jresult[0]->code ?? '';
                $msg = $jresult[0]->msg ?? 'No error message';
            } elseif (is_array($jresult[0])) {
                $code = $jresult[0]['code'] ?? '';
                $msg = $jresult[0]['msg'] ?? 'No error message';
            }
        } elseif (is_object($jresult)) {
            $code = $jresult->code ?? '';
            $msg = $jresult->msg ?? 'No error message';
        }
        
        if (!$jresult || $code != "1000") {
            $errorCode = $code ?: 'unknown';
            $errorMsg = $msg ?: 'No error message';
            log_message('error', "Insung API Token Creation Failed [Error Code]: {$errorCode}, [Message]: {$errorMsg}");
            log_message('debug', "Token Creation Request: mcode={$mcode}, cccode={$cccode}");
            return false;
        }
        
        // 토큰 추출
        $token = '';
        if (is_array($jresult) && isset($jresult[0])) {
            if (is_object($jresult[0])) {
                $token = $jresult[0]->token ?? '';
            } elseif (is_array($jresult[0])) {
                $token = $jresult[0]['token'] ?? '';
            }
        } elseif (is_object($jresult)) {
            $token = $jresult->token ?? '';
        }
        
        if (empty($token)) {
            log_message('error', 'Insung API Token Creation: Token is empty in response');
            return false;
        }
        
        // api_list 테이블의 token 필드 업데이트
        if ($apiIdx !== null) {
            // apiIdx가 직접 제공된 경우 사용
            $updateResult = $this->apiListModel->updateToken($apiIdx, $token);
            if (!$updateResult) {
                log_message('error', "Failed to update token in database for api_idx: {$apiIdx}");
            } else {
                log_message('info', "Token updated successfully in database for api_idx: {$apiIdx}");
            }
        } else {
            // apiIdx가 없으면 mcode, cccode로 조회
            $apiInfo = $this->apiListModel->getApiInfoByMcodeCccode($mcode, $cccode);
            if ($apiInfo && isset($apiInfo['idx'])) {
                $this->apiListModel->updateToken($apiInfo['idx'], $token);
                log_message('info', "Token updated successfully in database for api_idx: {$apiInfo['idx']}");
            } else {
                log_message('warning', "API info not found for mcode={$mcode}, cccode={$cccode}, token not saved to DB");
            }
        }
        
        return $token;
    }

    /**
     * 토큰 갱신
     * 
     * @param int $apiIdx api_list 테이블의 idx 값
     * @return string|false 새 토큰 또는 false
     */
    public function updateTokenKey($apiIdx)
    {
        $apiRow = $this->apiListModel->getApiInfoByIdx($apiIdx);
        
        if (!$apiRow) {
            log_message('error', "Insung API: API info not found for idx: {$apiIdx}");
            return false;
        }
        
        $mcode = $apiRow['mcode'] ?? '';
        $cccode = $apiRow['cccode'] ?? '';
        $ckey = $apiRow['ckey'] ?? '';
        
        if (empty($mcode) || empty($cccode) || empty($ckey)) {
            log_message('error', "Insung API: Missing required fields (mcode, cccode, ckey) for idx: {$apiIdx}");
            log_message('debug', "API Row data: " . json_encode($apiRow));
            return false;
        }
        
        // ukey 생성: key_str + ckey (레거시 코드와 동일)
        // 레거시 코드에서는 $key_str = "myapikey"를 사용
        $keyStr = getenv('INSUNG_KEY_STR') ?: 'myapikey';
        
        // ckey 값 검증 및 정리 (앞뒤 공백 제거)
        $ckey = trim($ckey);
        
        // ckey 값 확인 (디버깅용 - 처음 10자만)
        $ckeyPreview = strlen($ckey) > 10 ? substr($ckey, 0, 10) . '...' : $ckey;
        log_message('debug', "ckey preview (first 10 chars): {$ckeyPreview}, full length: " . strlen($ckey));
        
        $ukey = $keyStr . $ckey;
        
        // akey 생성: ukey를 MD5로 변환
        $akey = md5($ukey);
        
        log_message('info', "Updating token for api_idx: {$apiIdx}, mcode: {$mcode}, cccode: {$cccode}");
        log_message('debug', "ukey prefix: {$keyStr}, ukey length: " . strlen($ukey) . ", akey: " . substr($akey, 0, 16) . "...");
        
        // apiIdx를 직접 전달하여 토큰 생성 및 업데이트
        return $this->createToken($mcode, $cccode, $ukey, $akey, $apiIdx);
    }

    /**
     * 토큰 조회 및 초기화
     * 
     * @param int $apiIdx api_list 테이블의 idx 값
     * @return string|false 토큰 또는 false
     */
    public function getTokenKey($apiIdx)
    {
        $apiRow = $this->apiListModel->getApiInfoByIdx($apiIdx);
        
        if (!$apiRow) {
            log_message('error', "Insung API: API info not found for idx: {$apiIdx}");
            return false;
        }
        
        // 토큰이 있으면 반환
        if (!empty($apiRow['token'])) {
            return $apiRow['token'];
        }
        
        // 토큰이 없으면 새로 생성
        return $this->updateTokenKey($apiIdx);
    }

    /**
     * API 호출 (토큰 만료 시 자동 갱신 및 재시도)
     * 
     * @param string $url API 엔드포인트 URL
     * @param array $params 파라미터 배열 (m_code, cc_code, token 등)
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return object|false JSON 디코드된 응답 객체 또는 false
     */
    public function callApiWithAutoTokenRefresh($url, $params, $apiIdx = null)
    {
        // 파라미터를 쿼리스트링으로 변환
        $paramString = http_build_query($params);
        
        log_message('info', "Insung API: Initial call to {$url}");
        
        // 첫 번째 API 호출
        $jresult = $this->callApi($url, $paramString);
        
        if (!$jresult) {
            log_message('error', "Insung API: Initial call failed (no response)");
            return false;
        }
        
        // 응답 코드 확인 (배열 또는 객체 형태 모두 처리)
        $code = '';
        if (is_array($jresult) && isset($jresult[0])) {
            if (is_object($jresult[0]) && isset($jresult[0]->code)) {
                $code = $jresult[0]->code;
            } elseif (is_array($jresult[0]) && isset($jresult[0]['code'])) {
                $code = $jresult[0]['code'];
            }
        } elseif (is_object($jresult) && isset($jresult->code)) {
            $code = $jresult->code;
        }
        
        // 토큰 만료 확인 (에러 코드 1001) - 토큰이 만료되었거나 잘못된 경우
        if ($code == "1001" && $apiIdx !== null) {
            log_message('info', "Insung API: Token expired (code 1001), refreshing token for api_idx: {$apiIdx}");
            
            // 토큰 갱신
            $newToken = $this->updateTokenKey($apiIdx);
            
            if ($newToken) {
                log_message('info', "Insung API: Token refreshed successfully, retrying API call");
                
                // 파라미터의 token 업데이트
                $params['token'] = $newToken;
                $paramString = http_build_query($params);
                
                // 재시도
                log_message('info', "Insung API: Retrying API call with new token");
                $jresult = $this->callApi($url, $paramString);
                
                // 재시도 후에도 1001이면 로그 남기기
                $retryCode = '';
                if (is_array($jresult) && isset($jresult[0])) {
                    if (is_object($jresult[0]) && isset($jresult[0]->code)) {
                        $retryCode = $jresult[0]->code;
                    } elseif (is_array($jresult[0]) && isset($jresult[0]['code'])) {
                        $retryCode = $jresult[0]['code'];
                    }
                } elseif (is_object($jresult) && isset($jresult->code)) {
                    $retryCode = $jresult->code;
                }
                
                if ($retryCode == "1001") {
                    log_message('warning', "Insung API: Token refresh retry still returned code 1001");
                } else if ($retryCode == "1000") {
                    log_message('info', "Insung API: Retry successful after token refresh");
                }
            } else {
                log_message('error', "Insung API: Token refresh failed for api_idx: {$apiIdx}, returning original 1001 response");
            }
        }
        
        return $jresult;
    }

    /**
     * 로그인 API 호출
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $userId 사용자 ID
     * @param string $password 비밀번호
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return object|false JSON 응답 또는 false
     */
    public function login($mcode, $cccode, $token, $userId, $password, $apiIdx = null)
    {
        $url = rtrim($this->baseUrl, '/') . "/api/login/";
        $params = [
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'token' => $token,
            'user_id' => $userId,
            'password' => $password,
            'type' => 'json'
        ];
        
        return $this->callApiWithAutoTokenRefresh($url, $params, $apiIdx);
    }

    /**
     * 회원 상세 조회 API 호출
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $userId 사용자 ID
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return object|false JSON 응답 또는 false
     */
    public function getMemberDetail($mcode, $cccode, $token, $userId, $apiIdx = null)
    {
        $url = $this->baseUrl . "/api/member_detail/";
        $params = [
            'type' => 'json',
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'token' => $token,
            'user_id' => $userId
        ];
        
        return $this->callApiWithAutoTokenRefresh($url, $params, $apiIdx);
    }

    /**
     * 회사 목록 조회 API 호출
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $compNo 회사 번호
     * @param string $compName 회사명
     * @param int $page 페이지 번호
     * @param int $limit 페이지당 항목 수
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return object|false JSON 응답 또는 false
     */
    public function getCompanyList($mcode, $cccode, $token, $compNo = '', $compName = '', $page = 1, $limit = 1000, $apiIdx = null)
    {
        $url = $this->baseUrl . "/api/company_list/";
        $params = [
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'token' => $token,
            'comp_no' => $compNo,
            'comp_name' => $compName,
            'page' => $page,
            'limit' => $limit,
            'type' => 'json'
        ];
        
        return $this->callApiWithAutoTokenRefresh($url, $params, $apiIdx);
    }

    /**
     * 거래처별 직원목록 조회 API 호출 (customer_attached_list)
     * https://guide.283.co.kr/#/apiCustomerAttachedListScreen 참조
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $compNo 회사 번호 (선택)
     * @param string $compName 회사명 (선택)
     * @param string $userId 사용자 ID (선택)
     * @param string $userName 사용자명 (선택)
     * @param int $page 페이지 번호
     * @param int $limit 페이지당 항목 수
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return object|false JSON 응답 또는 false
     */
    public function getCustomerAttachedList($mcode, $cccode, $token, $compNo = '', $compName = '', $userId = '', $userName = '', $page = 1, $limit = 1000, $apiIdx = null)
    {
        $url = $this->baseUrl . "/api/customer/list/";
        $params = [
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'token' => $token,
            'comp_no' => $compNo,
            'comp_name' => $compName,
            'user_id' => $userId,
            'user_name' => $userName,
            'page' => $page,
            'limit' => $limit,
            'type' => 'json'
        ];
        
        return $this->callApiWithAutoTokenRefresh($url, $params, $apiIdx);
    }

    /**
     * 주문 접수 API 호출
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $userId 사용자 ID
     * @param array $orderData 주문 데이터
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return array ['success' => bool, 'serial_number' => string|null, 'message' => string]
     */
    public function registerOrder($mcode, $cccode, $token, $userId, $orderData, $apiIdx = null)
    {
        $url = $this->baseUrl . "/api/order_regist/";
        
        // 주소에서 시도, 구군, 동 추출
        $startSido = $this->extractSido($orderData['departure_address'] ?? '');
        $startGugun = $this->extractGugun($orderData['departure_address'] ?? '');
        $startDong = $orderData['departure_dong'] ?? '';
        // 동이 없으면 주소에서 추출 시도
        if (empty($startDong)) {
            $startDong = $this->extractDong($orderData['departure_address'] ?? '');
        }
        
        $destSido = $this->extractSido($orderData['destination_address'] ?? '');
        $destGugun = $this->extractGugun($orderData['destination_address'] ?? '');
        $destDong = $orderData['destination_dong'] ?? '';
        // 동이 없으면 주소에서 추출 시도
        if (empty($destDong)) {
            $destDong = $this->extractDong($orderData['destination_address'] ?? '');
        }
        
        // 좌표 처리 (루비 버전 참조: 좌표가 없으면 /api/axis/address/ API 호출)
        // 루비 버전: $s_fulladdr && !$s_lon && !$s_lat일 때 좌표 조회
        $startLon = $orderData['departure_lon'] ?? '';
        $startLat = $orderData['departure_lat'] ?? '';
        $destLon = $orderData['destination_lon'] ?? '';
        $destLat = $orderData['destination_lat'] ?? '';
        
        // 출발지 좌표가 없으면 인성 API로 조회
        // 루비 버전 참조: s_fulladdr (지번 주소)를 우선 사용
        $departureFullAddr = $orderData['departure_fulladdr'] ?? '';
        $departureAddress = $orderData['departure_address'] ?? '';
        
        // 좌표가 없으면 좌표 조회 시도
        if (empty($startLon) || empty($startLat)) {
            // 지번 주소 우선 사용, 없으면 일반 주소 사용
            $addressForCoord = !empty($departureFullAddr) ? $departureFullAddr : $departureAddress;
            if (!empty($addressForCoord)) {
                $addressForCoord = str_replace("강원특별자치도", "강원도", $addressForCoord);
                log_message('debug', "Insung::registerOrder - Fetching coordinates for departure address: {$addressForCoord}");
                $coordResult = $this->getAddressCoordinates($mcode, $cccode, $token, $addressForCoord, $apiIdx);
                if ($coordResult && isset($coordResult['lon']) && isset($coordResult['lat'])) {
                    $startLon = $coordResult['lon'];
                    $startLat = $coordResult['lat'];
                    log_message('info', "Insung::registerOrder - Departure coordinates fetched: lon={$startLon}, lat={$startLat}");
                } else {
                    log_message('warning', "Insung::registerOrder - Failed to fetch departure coordinates for address: {$addressForCoord}");
                }
            } else {
                log_message('warning', "Insung::registerOrder - Departure address is empty, cannot fetch coordinates");
            }
        }
        
        // 도착지 좌표가 없으면 인성 API로 조회
        // 루비 버전 참조: d_fulladdr (지번 주소)를 우선 사용
        $destinationFullAddr = $orderData['destination_fulladdr'] ?? '';
        $destinationAddress = $orderData['destination_address'] ?? '';
        
        // 좌표가 없으면 좌표 조회 시도
        if (empty($destLon) || empty($destLat)) {
            // 지번 주소 우선 사용, 없으면 일반 주소 사용
            $addressForCoord = !empty($destinationFullAddr) ? $destinationFullAddr : $destinationAddress;
            if (!empty($addressForCoord)) {
                $addressForCoord = str_replace("강원특별자치도", "강원도", $addressForCoord);
                log_message('debug', "Insung::registerOrder - Fetching coordinates for destination address: {$addressForCoord}");
                $coordResult = $this->getAddressCoordinates($mcode, $cccode, $token, $addressForCoord, $apiIdx);
                if ($coordResult && isset($coordResult['lon']) && isset($coordResult['lat'])) {
                    $destLon = $coordResult['lon'];
                    $destLat = $coordResult['lat'];
                    log_message('info', "Insung::registerOrder - Destination coordinates fetched: lon={$destLon}, lat={$destLat}");
                } else {
                    log_message('warning', "Insung::registerOrder - Failed to fetch destination coordinates for address: {$addressForCoord}");
                }
            } else {
                log_message('warning', "Insung::registerOrder - Destination address is empty, cannot fetch coordinates");
            }
        }
        
        // kind 변환 (서비스 타입에 따라 숫자로 변환)
        $kind = $this->convertKind($orderData['service_type'] ?? '', $orderData['delivery_method'] ?? '');
        
        // kind_etc 변환 (플렉스일 때 배송수단 선택값)
        $kindEtc = '';
        if ($kind === '7') { // 플렉스인 경우
            $kindEtc = $this->convertKindEtc($orderData['delivery_method'] ?? '');
        }
        
        // item_type 변환 (물품 종류에 따라 숫자로 변환)
        $itemType = $this->convertItemType($orderData['item_type'] ?? '');
        
        // doc 변환 (배송방법: 1:편도, 3:왕복, 5:경유)
        $doc = $this->convertDoc($orderData['deliveryMethod'] ?? $orderData['delivery_method'] ?? '');
        
        // sfast 변환 (배송선택: 1:일반, 3:급송 등)
        $sfast = $this->convertSfast($orderData['deliveryType'] ?? $orderData['delivery_type'] ?? '', $orderData['urgency_level'] ?? '');
        
        // state 변환 (처리상태: 10:접수, 20:대기, 50:문의)
        // PHP 버전에서는 state=& (빈 값)으로 보내지만, 주문 접수 시에는 10(접수)로 설정하는 것이 명확함
        $state = $orderData['state'] ?? '10'; // 주문 접수 시 기본값 10(접수), 필요시 orderData에서 받아옴
        
        // 금액 계산 (인성 API는 항상 거리 기반으로 계산)
        // 인성 API는 거리 기반 요금제이므로 좌표가 필수입니다
        // 좌표가 없으면 주문 접수 불가
        if (empty($startLon) || empty($startLat) || empty($destLon) || empty($destLat)) {
            log_message('error', "Insung::registerOrder - Coordinates are required for distance-based pricing. startLon={$startLon}, startLat={$startLat}, destLon={$destLon}, destLat={$destLat}");
            return [
                'success' => false,
                'serial_number' => null,
                'message' => '출발지 또는 도착지 좌표가 없어 거리 기반 요금 계산이 불가능합니다. 주소를 정확히 입력해주세요.'
            ];
        }
        
        // orderData에 doc 추가 (getPriceFromPayInfo에서 사용)
        $orderDataForPrice = $orderData;
        $orderDataForPrice['doc'] = $doc;
        
        // 거리 기반 가격 계산
        // 인성 API 문서 참조: /api/cost_distance/ API는 distance 파라미터만 전달
        // Response에서 kind와 car_kind에 따라 적절한 필드 사용
        // 인성 API 실패 시 tbl_pay_info 테이블에서 조회
        log_message('debug', "Insung::registerOrder - Calculating price by distance. startLon={$startLon}, startLat={$startLat}, destLon={$destLon}, destLat={$destLat}, kind={$kind}, car_kind=" . ($orderData['car_kind'] ?? ''));
        $calculatedPrice = $this->calculatePriceByDistance($mcode, $cccode, $token, $userId, $startLon, $startLat, $destLon, $destLat, $kind, $apiIdx, $orderDataForPrice);
        
        if ($calculatedPrice <= 0) {
            log_message('error', "Insung::registerOrder - Price calculation returned 0 or failed. Both Insung API and tbl_pay_info table failed to provide pricing.");
            return [
                'success' => false,
                'serial_number' => null,
                'message' => '거리 기반 요금 계산에 실패했습니다. 거리 또는 차종 정보를 확인해주세요.'
            ];
        }
        
        $price = (string)$calculatedPrice;
        log_message('info', "Insung::registerOrder - Price calculated: {$price}");
        
        $params = [
            'type' => 'json',
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'user_id' => $userId,
            'token' => $token,
            // 주문자 정보
            'c_name' => $orderData['company_name'] ?? '',
            'c_mobile' => $orderData['contact'] ?? '',
            'c_dept_name' => $orderData['departure_department'] ?? '',
            'c_charge_name' => $orderData['departure_manager'] ?? '',
            'reason_desc' => '',
            // 출발지 정보
            's_start' => $orderData['departure_company_name'] ?? '',
            'start_telno' => $orderData['departure_contact'] ?? '',
            'dept_name' => $orderData['departure_department'] ?? '',
            'charge_name' => $orderData['departure_manager'] ?? '',
            'start_sido' => $startSido,
            'start_gugun' => $startGugun,
            'start_dong' => $startDong,
            'start_lon' => $startLon,
            'start_lat' => $startLat,
            // 루비 버전 참조: $s_location = $s_address." ".$s_address2;
            'start_location' => trim(($orderData['departure_address'] ?? '') . ' ' . ($orderData['departure_detail'] ?? '')),
            // 도착지 정보
            's_dest' => $orderData['destination_company_name'] ?? '',
            'dest_telno' => $orderData['destination_contact'] ?? '',
            'dest_dept' => $orderData['destination_department'] ?? '',
            'dest_charge' => $orderData['destination_manager'] ?? '',
            'dest_sido' => $destSido,
            'dest_gugun' => $destGugun,
            'dest_dong' => $destDong,
            // 루비 버전 참조: $d_location = $d_address." ".$d_address2;
            'dest_location' => trim(($orderData['destination_address'] ?? '') . ' ' . ($orderData['detail_address'] ?? '')),
            'dest_lon' => $destLon,
            'dest_lat' => $destLat,
            // 물품 정보
            'kind' => $kind,
            'kind_etc' => $kindEtc,
            'pay_gbn' => $this->convertPaymentType($orderData['payment_type'] ?? ''),
            'doc' => $doc,
            'sfast' => $sfast,
            'item_type' => $itemType,
            // 루비 버전 참조: & 문자 오류로인한 특수문자로 치환
            'memo' => preg_replace("/&/", "＆", $orderData['delivery_content'] ?? ($orderData['notes'] ?? '')),
            'sms_telno' => $orderData['sms_telno'] ?? ($orderData['contact'] ?? ''),
            // 예약 정보 (PHP 버전: use_check=$sel_reserve, pickup_date=$reserve_date, pick_hour=$reserve_hour, pick_min=$reserve_min, pick_sec=0)
            'use_check' => $orderData['reserve_check'] ?? ($orderData['use_check'] ?? '0'),
            'pickup_date' => $orderData['reserve_date'] ?? ($orderData['pickup_date'] ?? ''),
            'pick_hour' => $orderData['reserve_hour'] ?? ($orderData['pick_hour'] ?? ''),
            'pick_min' => $orderData['reserve_min'] ?? ($orderData['pick_min'] ?? ''),
            'pick_sec' => $orderData['reserve_sec'] ?? ($orderData['pick_sec'] ?? '0'),
            // 금액 정보 (루비 버전 참조: price=$price, add_cost=, discount_cost=, delivery_cost=)
            // 루비 버전에서는 add_cost, discount_cost, delivery_cost를 빈 값으로 전송
            'price' => $price,
            's_c_code' => $orderData['s_c_code'] ?? '',
            'd_c_code' => $orderData['d_c_code'] ?? '',
            'add_cost' => '', // 루비 버전 참조: 빈 값으로 전송
            'discount_cost' => '', // 루비 버전 참조: 빈 값으로 전송
            'delivery_cost' => '', // 루비 버전 참조: 빈 값으로 전송
            'car_kind' => $orderData['car_kind'] ?? '', // 차종구분 코드 (PHP 버전: car_kind=$car_kind)
            'state' => $state, // 처리상태 (PHP 버전: state= 빈 값, 우리는 10으로 설정)
            'distince' => $orderData['distance'] ?? '', // PHP 버전에는 없지만 API 문서에 있을 수 있음
            'o_c_code' => $orderData['o_c_code'] ?? '' // 주문자 고객사 코드 (PHP 버전: o_c_code=$o_ccode)
        ];
        
        $result = $this->callApiWithAutoTokenRefresh($url, $params, $apiIdx);
        
        if (!$result) {
            return [
                'success' => false,
                'serial_number' => null,
                'message' => 'API 호출 실패'
            ];
        }
        
        // 응답이 배열인 경우 첫 번째 요소 확인
        if (is_array($result) && isset($result[0])) {
            $code = $result[0]->code ?? $result[0]['code'] ?? '';
            $msg = $result[0]->msg ?? $result[0]['msg'] ?? '';
            
            if ($code === '1000') {
                // 성공 시 serial_number 추출
                $serialNumber = null;
                if (isset($result[1])) {
                    $serialNumber = $result[1]->serial_number ?? $result[1]['serial_number'] ?? null;
                }
                
                return [
                    'success' => true,
                    'serial_number' => $serialNumber,
                    'message' => '주문 접수 성공'
                ];
            } else {
                return [
                    'success' => false,
                    'serial_number' => null,
                    'message' => "주문 접수 실패: [{$code}] {$msg}"
                ];
            }
        }
        
        return [
            'success' => false,
            'serial_number' => null,
            'message' => '응답 형식 오류'
        ];
    }

    /**
     * 주소에서 시도 추출
     */
    private function extractSido($address)
    {
        if (empty($address)) {
            return '';
        }
        
        // 시도 목록
        $sidos = ['서울', '부산', '대구', '인천', '광주', '대전', '울산', '세종', '경기', '강원', '충북', '충남', '전북', '전남', '경북', '경남', '제주'];
        
        foreach ($sidos as $sido) {
            if (strpos($address, $sido) === 0 || strpos($address, $sido . '특별시') !== false || strpos($address, $sido . '광역시') !== false || strpos($address, $sido . '도') !== false) {
                return $sido;
            }
        }
        
        // 특별자치도 처리
        if (strpos($address, '강원특별자치도') !== false) {
            return '강원도';
        }
        if (strpos($address, '전북특별자치도') !== false) {
            return '전북';
        }
        
        return '';
    }

    /**
     * 주소에서 구군 추출
     */
    private function extractGugun($address)
    {
        if (empty($address)) {
            return '';
        }
        
        // 시도 제거 후 첫 번째 단어 추출
        $sido = $this->extractSido($address);
        if ($sido) {
            $address = str_replace([$sido . '특별시', $sido . '광역시', $sido . '도', $sido], '', $address);
        }
        
        $parts = explode(' ', trim($address));
        if (!empty($parts[0])) {
            return $parts[0];
        }
        
        return '';
    }

    /**
     * 주소에서 동 추출
     */
    private function extractDong($address)
    {
        if (empty($address)) {
            return '';
        }
        
        // 시도, 구군 제거
        $sido = $this->extractSido($address);
        $gugun = $this->extractGugun($address);
        
        if ($sido) {
            $address = str_replace([$sido . '특별시', $sido . '광역시', $sido . '도', $sido], '', $address);
        }
        if ($gugun) {
            $address = str_replace($gugun, '', $address);
        }
        
        // "동"으로 끝나는 단어 찾기
        $parts = explode(' ', trim($address));
        foreach ($parts as $part) {
            if (preg_match('/(.+동)/u', $part, $matches)) {
                return $matches[1];
            }
        }
        
        return '';
    }

    /**
     * kind 변환 (배송수단)
     * 인성 API: 1(오토), 2(다마스), 3(트럭), 4(밴), 5(라보), 6(지하철), 7(플렉스)
     */
    private function convertKind($serviceType, $deliveryMethod)
    {
        // delivery_method 우선 확인
        if ($deliveryMethod === 'motorcycle' || $serviceType === 'quick-motorcycle') {
            return '1'; // 오토
        } elseif ($serviceType === 'quick-flex') {
            return '7'; // 플렉스
        } elseif ($deliveryMethod === 'vehicle' || $serviceType === 'quick-vehicle') {
            return '3'; // 트럭
        } elseif ($serviceType === 'quick-moving') {
            return '3'; // 트럭
        }
        
        // 기본값: 오토
        return '1';
    }

    /**
     * kind_etc 변환 (플렉스 서비스의 배송수단 선택값)
     * 인성 API 문서: 플렉스(7)일 경우 승용, SUV, 도보, PM만 입력 가능
     */
    private function convertKindEtc($deliveryMethod)
    {
        $map = [
            'sedan' => '승용',        // 승용차 → 승용
            'scooter' => 'PM',        // 스쿠터 → PM (Personal Mobility)
            'bicycle' => 'PM',        // 자전거 → PM (Personal Mobility)
            'walking' => '도보',      // 도보 → 도보
            'subway' => 'PM',         // 지하철 → PM (또는 빈 값, API 문서에 없음)
            'motorcycle' => 'PM',     // 오토바이 → PM
            'vehicle' => '승용',       // 차량 → 승용
            'suv' => 'SUV',           // SUV → SUV (혹시 폼에 추가될 경우 대비)
        ];
        
        return $map[$deliveryMethod] ?? '';
    }

    /**
     * item_type 변환 (물품종류)
     * 인성 API: 1(서류봉투), 2(소박스), 3(중박스), 4(대박스)
     */
    private function convertItemType($itemType)
    {
        if (empty($itemType)) {
            return '1'; // 기본값: 서류봉투
        }
        
        // 한글 물품명을 숫자로 변환
        $itemTypeLower = mb_strtolower($itemType, 'UTF-8');
        
        // 서류 관련
        if (strpos($itemTypeLower, '서류') !== false || strpos($itemTypeLower, '봉투') !== false) {
            return '1'; // 서류봉투
        }
        
        // 소/중/대 구분
        if (strpos($itemTypeLower, '소') !== false) {
            return '2'; // 소박스
        } elseif (strpos($itemTypeLower, '중') !== false) {
            return '3'; // 중박스
        } elseif (strpos($itemTypeLower, '대') !== false) {
            return '4'; // 대박스
        }
        
        // 기본값: 서류봉투
        return '1';
    }

    /**
     * 결제 타입 변환 (인성 API pay_gbn 매핑)
     * 인성 API: 1(선불), 2(착불), 3(신용), 4(송금), 5(수금)
     */
    private function convertPaymentType($paymentType)
    {
        $map = [
            'cash_in_advance' => '1',   // 선불 → 1
            'cash_on_delivery' => '2',  // 착불 → 2
            'credit_transaction' => '3', // 신용거래 → 3
            'bank_transfer' => '4',     // 송금 → 4
            // '5' => '5' // 수금은 현재 폼에 없음
        ];
        
        // 기본값: 신용거래(3)
        return $map[$paymentType] ?? '3';
    }

    /**
     * doc 변환 (배송방법)
     * 인성 API: 1(편도), 3(왕복), 5(경유)
     */
    private function convertDoc($deliveryMethod)
    {
        $map = [
            'one_way' => '1',      // 편도 → 1
            'round_trip' => '3',   // 왕복 → 3
            'via' => '5',          // 경유 → 5
        ];
        
        // 기본값: 편도(1)
        return $map[$deliveryMethod] ?? '1';
    }

    /**
     * sfast 변환 (배송선택)
     * 인성 API: 1(일반), 3(급송), 5(조조), 7(야간), 8(할증), 9(과적), 0(택배), A(심야), B(휴일), C(납품), D(대기), F(눈비), 4(독차), 6(혼적), G(할인), M(마일), H(우편), I(행랑), J(해외), K(신문), Q(퀵), N(보관), O(혹한), P(상하차), R(명절)
     */
    private function convertSfast($deliveryType, $urgencyLevel)
    {
        // deliveryType 우선 확인
        if ($deliveryType === 'express') {
            return '3'; // 급송
        } elseif ($deliveryType === 'normal') {
            return '1'; // 일반
        }
        
        // urgency_level 확인
        if ($urgencyLevel === 'urgent') {
            return '3'; // 급송
        } elseif ($urgencyLevel === 'super_urgent') {
            return '3'; // 급송
        } elseif ($urgencyLevel === 'normal') {
            return '1'; // 일반
        }
        
        // 기본값: 일반(1)
        return '1';
    }

    /**
     * 주문 상세 조회 API 호출
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $userId 사용자 ID
     * @param string $serialNumber 인성 주문번호 (serial_number)
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function getOrderDetail($mcode, $cccode, $token, $userId, $serialNumber, $apiIdx = null)
    {
        $url = $this->baseUrl . "/api/order_detail/";
        
        $params = [
            'type' => 'json',
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'user_id' => $userId,
            'token' => $token,
            'serial' => $serialNumber
        ];
        
        $result = $this->callApiWithAutoTokenRefresh($url, $params, $apiIdx);
        
        if (!$result) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'API 호출 실패'
            ];
        }
        
        // 응답이 배열인 경우 첫 번째 요소 확인
        if (is_array($result) && isset($result[0])) {
            $code = $result[0]->code ?? $result[0]['code'] ?? '';
            $msg = $result[0]->msg ?? $result[0]['msg'] ?? '';
            
            if ($code === '1000') {
                // 성공 시 주문 상세 데이터 반환
                return [
                    'success' => true,
                    'data' => $result,
                    'message' => '주문 상세 조회 성공'
                ];
            } else {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => "주문 상세 조회 실패: [{$code}] {$msg}"
                ];
            }
        }
        
        return [
            'success' => false,
            'data' => null,
            'message' => '응답 형식 오류'
        ];
    }

    /**
     * 주소로부터 좌표 조회 (인성 API /api/axis/address/)
     * 루비 버전 참조: $s_fulladdr && !$s_lon && !$s_lat일 때 호출
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $address 주소
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @return array|false ['lon' => string, 'lat' => string] 또는 false
     */
    private function getAddressCoordinates($mcode, $cccode, $token, $address, $apiIdx = null)
    {
        if (empty($address)) {
            log_message('warning', "Insung::getAddressCoordinates - Address is empty");
            return false;
        }

        $url = $this->baseUrl . "/api/axis/address/";
        $params = [
            'type' => 'json',
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'token' => $token,
            'query' => $address
        ];

        log_message('debug', "Insung::getAddressCoordinates - Calling API with address: {$address}");
        $result = $this->callApiWithAutoTokenRefresh($url, $params, $apiIdx);

        if (!$result) {
            log_message('error', "Insung::getAddressCoordinates - API call failed or invalid response");
            return false;
        }

        // 루비 버전 참조: $jresult->Result[0]->result_info[0]->code == "1000"
        // $jresult->Result[1]->query_result[0]->coordinate[0]->insung_lon
        // $jresult->Result[1]->query_result[0]->coordinate[0]->insung_lat
        
        // 응답 구조 확인: Result 키로 래핑된 경우와 배열 형태 모두 처리
        $code = '';
        $msg = '';
        $queryResult = null;
        
        // 1. Result 키로 래핑된 경우 (루비 버전과 동일)
        if (is_object($result) && isset($result->Result) && is_array($result->Result)) {
            if (isset($result->Result[0]) && is_object($result->Result[0])) {
                if (isset($result->Result[0]->result_info) && is_array($result->Result[0]->result_info) && isset($result->Result[0]->result_info[0])) {
                    $code = $result->Result[0]->result_info[0]->code ?? '';
                    $msg = $result->Result[0]->result_info[0]->msg ?? '';
                }
            }
            if (isset($result->Result[1])) {
                $queryResult = $result->Result[1];
            }
        }
        // 2. 배열 형태인 경우
        elseif (is_array($result) && isset($result[0])) {
            if (is_object($result[0])) {
                $code = $result[0]->code ?? '';
                $msg = $result[0]->msg ?? '';
            } elseif (is_array($result[0])) {
                $code = $result[0]['code'] ?? '';
                $msg = $result[0]['msg'] ?? '';
            }
            if (isset($result[1])) {
                $queryResult = $result[1];
            }
        }
        // 3. 객체 형태인 경우 (직접 code 속성)
        elseif (is_object($result) && isset($result->code)) {
            $code = $result->code ?? '';
            $msg = $result->msg ?? '';
            $queryResult = $result;
        }
        
        if ($code !== '1000') {
            log_message('warning', "Insung::getAddressCoordinates - API returned error. Code: {$code}, Message: {$msg}");
            return false;
        }
        
        if (!$queryResult) {
            log_message('warning', "Insung::getAddressCoordinates - Query result not found in response");
            return false;
        }
        
        // 좌표 추출
        $lon = '';
        $lat = '';
        
        // 루비 버전 참조: $jresult->Result[1]->query_result[0]->coordinate[0]->insung_lon
        if (is_object($queryResult) && isset($queryResult->query_result) && is_array($queryResult->query_result) && isset($queryResult->query_result[0])) {
            if (isset($queryResult->query_result[0]->coordinate) && is_array($queryResult->query_result[0]->coordinate) && isset($queryResult->query_result[0]->coordinate[0])) {
                $coord = $queryResult->query_result[0]->coordinate[0];
                $lon = $coord->insung_lon ?? '';
                $lat = $coord->insung_lat ?? '';
            }
        } elseif (is_array($queryResult) && isset($queryResult['query_result']) && is_array($queryResult['query_result']) && isset($queryResult['query_result'][0])) {
            if (isset($queryResult['query_result'][0]['coordinate']) && is_array($queryResult['query_result'][0]['coordinate']) && isset($queryResult['query_result'][0]['coordinate'][0])) {
                $coord = $queryResult['query_result'][0]['coordinate'][0];
                $lon = $coord['insung_lon'] ?? '';
                $lat = $coord['insung_lat'] ?? '';
            }
        }
        
        if (!empty($lon) && !empty($lat)) {
            log_message('info', "Insung::getAddressCoordinates - Coordinates found: lon={$lon}, lat={$lat}");
            return [
                'lon' => $lon,
                'lat' => $lat
            ];
        } else {
            log_message('warning', "Insung::getAddressCoordinates - Coordinate data not found in response structure. Response: " . json_encode($result));
            return false;
        }
    }

    /**
     * 거리 기반 가격 계산 (인성 API /api/axis/navigation/ 및 /api/cost_distance/)
     * 인성 API 문서 참조: /api/cost_distance/ API는 distance 파라미터만 전달
     * Response에서 kind와 car_kind에 따라 적절한 필드 사용
     * 
     * @param string $mcode 마스터 코드
     * @param string $cccode 콜센터 코드
     * @param string $token 토큰
     * @param string $userId 사용자 ID
     * @param string $startLon 출발지 경도
     * @param string $startLat 출발지 위도
     * @param string $destLon 도착지 경도
     * @param string $destLat 도착지 위도
     * @param string $kind 배송수단 (1:오토, 2:다마스, 3:트럭, 5:라보)
     * @param int|null $apiIdx api_list 테이블의 idx (토큰 갱신용)
     * @param array $orderData 주문 데이터 (car_kind 포함)
     * @return int 계산된 가격 (실패 시 0)
     */
    private function calculatePriceByDistance($mcode, $cccode, $token, $userId, $startLon, $startLat, $destLon, $destLat, $kind, $apiIdx = null, $orderData = [])
    {
        // 1. 거리 조회 (/api/axis/navigation/)
        $navUrl = $this->baseUrl . "/api/axis/navigation/";
        $navParams = [
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'token' => $token,
            'from_lat' => $startLat,
            'from_lon' => $startLon,
            'to_lat' => $destLat,
            'to_lon' => $destLon,
            'type' => 'json'
        ];

        log_message('debug', "Insung::calculatePriceByDistance - Calling navigation API. from_lat={$startLat}, from_lon={$startLon}, to_lat={$destLat}, to_lon={$destLon}");
        $navResult = $this->callApiWithAutoTokenRefresh($navUrl, $navParams, $apiIdx);

        if (!$navResult || !is_array($navResult) || !isset($navResult[0])) {
            log_message('error', "Insung::calculatePriceByDistance - Navigation API call failed or invalid response");
            return 0;
        }

        $navCode = $navResult[0]->code ?? $navResult[0]['code'] ?? '';
        $navMsg = $navResult[0]->msg ?? $navResult[0]['msg'] ?? '';
        if ($navCode !== '1000') {
            log_message('warning', "Insung::calculatePriceByDistance - Navigation API returned error. Code: {$navCode}, Message: {$navMsg}");
            return 0;
        }

        // 루비 버전 참조: $jresult[0]->distance_info[0]->navigation_distance
        $distance = 0;
        if (isset($navResult[0]->distance_info[0]->navigation_distance)) {
            $distance = $navResult[0]->distance_info[0]->navigation_distance;
        } elseif (isset($navResult[0]['distance_info'][0]['navigation_distance'])) {
            $distance = $navResult[0]['distance_info'][0]['navigation_distance'];
        }

        if (empty($distance)) {
            log_message('warning', "Insung::calculatePriceByDistance - Distance not found in response. Response: " . json_encode($navResult));
            return 0;
        }

        // 거리 단위 변환 (미터 -> 킬로미터, 올림)
        $distance = str_replace(',', '', $distance);
        $distance = (float)$distance / 1000;
        $distance = ceil($distance);
        log_message('info', "Insung::calculatePriceByDistance - Distance calculated: {$distance} km");

        // 2. 거리 기반 가격 조회 (/api/cost_distance/)
        // 인성 API 문서 참조: Request 파라미터는 m_code, cc_code, user_id, token, distance, type만 전달
        // car_kind는 전달하지 않음 (Response에서 car_kind에 따라 적절한 필드 사용)
        $costUrl = $this->baseUrl . "/api/cost_distance/";
        $costParams = [
            'm_code' => $mcode,
            'cc_code' => $cccode,
            'user_id' => $userId,
            'token' => $token,
            'distance' => $distance,
            'type' => 'json'
        ];

        $carKind = $orderData['car_kind'] ?? '';
        log_message('debug', "Insung::calculatePriceByDistance - Calling cost_distance API. distance={$distance}km, kind={$kind}, car_kind={$carKind} (API 파라미터에는 distance만 전달, kind와 car_kind는 응답 파싱에 사용)");
        $costResult = $this->callApiWithAutoTokenRefresh($costUrl, $costParams, $apiIdx);

        if (!$costResult) {
            log_message('error', "Insung::calculatePriceByDistance - Cost distance API call failed or invalid response");
            return 0;
        }

        // 응답 구조 확인: Result 키로 래핑된 경우와 배열 형태 모두 처리
        $costCode = '';
        $costMsg = '';
        $costData = null;

        // 1. Result 키로 래핑된 경우
        if (is_object($costResult) && isset($costResult->Result) && is_array($costResult->Result)) {
            if (isset($costResult->Result[0]) && is_object($costResult->Result[0])) {
                $costCode = $costResult->Result[0]->code ?? '';
                $costMsg = $costResult->Result[0]->msg ?? '';
            }
            if (isset($costResult->Result[1])) {
                $costData = $costResult->Result[1];
            }
        }
        // 2. 배열 형태인 경우 (기존 처리)
        elseif (is_array($costResult) && isset($costResult[0])) {
            if (is_object($costResult[0])) {
                $costCode = $costResult[0]->code ?? '';
                $costMsg = $costResult[0]->msg ?? '';
            } elseif (is_array($costResult[0])) {
                $costCode = $costResult[0]['code'] ?? '';
                $costMsg = $costResult[0]['msg'] ?? '';
            }
            if (isset($costResult[1])) {
                $costData = $costResult[1];
            }
        }
        // 3. 객체 형태인 경우 (직접 code 속성)
        elseif (is_object($costResult) && isset($costResult->code)) {
            $costCode = $costResult->code ?? '';
            $costMsg = $costResult->msg ?? '';
            $costData = $costResult;
        }

        if ($costCode !== '1000' || !$costData) {
            log_message('warning', "Insung::calculatePriceByDistance - Cost distance API returned error or no cost data. Code: {$costCode}, Message: {$costMsg}");
            log_message('debug', "Insung::calculatePriceByDistance - Full costResult: " . json_encode($costResult));
            return 0;
        }

        // 인성 API 문서 참조: Response 필드
        // basic_cost, damas_cost, ven_cost, truck_cost, labo_cost
        // cargo_1_4_cost, cargo_2_5_cost, cargo_3_5_cost, cargo_5_cost, cargo_8_cost
        // cargo_11_cost, cargo_14_cost, cargo_15_cost, cargo_18_cost, cargo_25_cost
        $price = 0;
        
        // 응답 데이터 구조 확인 (객체 또는 배열)
        if (is_object($costData)) {
            $costDataArray = (array)$costData;
        } elseif (is_array($costData)) {
            $costDataArray = $costData;
        } else {
            log_message('error', "Insung::calculatePriceByDistance - Invalid costData structure. Type: " . gettype($costData));
            log_message('debug', "Insung::calculatePriceByDistance - costData: " . json_encode($costData));
            return 0;
        }
        
        log_message('debug', "Insung::calculatePriceByDistance - costData structure: " . json_encode($costDataArray, JSON_UNESCAPED_UNICODE));
        
        // car_kind에 따른 cargo_X_cost 필드 매핑
        // 인성 API 문서 참조: car_kind는 차종구분 코드 (01: 플축카고, 15: 카고, 33: 초장축 등)
        $carKind = $orderData['car_kind'] ?? '';
        $cargoCostField = null;
        
        // car_kind가 있고 kind가 3(트럭)인 경우 cargo_X_cost 필드 사용
        if ($kind === '3' && !empty($carKind)) {
            // car_kind에 따른 cargo_X_cost 필드 매핑
            // 실제 매핑은 인성 API 문서나 응답 데이터를 확인하여 정확히 매핑해야 함
            // 여기서는 일반적인 매핑만 제공 (필요시 수정)
            $carKindToCargoField = [
                '15' => 'cargo_1_4_cost',  // 카고 (1.4톤)
                '09' => 'cargo_2_5_cost',  // 플러스카고 (2.5톤)
                '20' => 'cargo_3_5_cost',  // 축카고 (3.5톤)
                '01' => 'cargo_5_cost',    // 플축카고 (5톤)
                '11' => 'cargo_8_cost',    // 리프트카고 (8톤)
                '33' => 'cargo_25_cost',   // 초장축 (25톤)
                // 추가 매핑 필요시 여기에 추가
            ];
            
            $cargoCostField = $carKindToCargoField[$carKind] ?? null;
            
            if ($cargoCostField) {
                $price = $costDataArray[$cargoCostField] ?? (is_object($costData) ? ($costData->$cargoCostField ?? 0) : 0);
                log_message('debug', "Insung::calculatePriceByDistance - Using {$cargoCostField} for car_kind={$carKind}, price={$price}");
            }
        }
        
        // car_kind 기반 필드가 없거나 kind가 3이 아닌 경우 kind에 따라 기본 필드 사용
        if ($price == 0) {
            switch ($kind) {
                case '1': // 오토
                    $price = $costDataArray['basic_cost'] ?? (is_object($costData) ? ($costData->basic_cost ?? 0) : 0);
                    break;
                case '2': // 다마스
                    $price = $costDataArray['damas_cost'] ?? (is_object($costData) ? ($costData->damas_cost ?? 0) : 0);
                    break;
                case '3': // 트럭 (car_kind가 없거나 매핑되지 않은 경우)
                    $price = $costDataArray['truck_cost'] ?? (is_object($costData) ? ($costData->truck_cost ?? 0) : 0);
                    log_message('debug', "Insung::calculatePriceByDistance - Using truck_cost (car_kind not mapped or empty), price={$price}");
                    break;
                case '5': // 라보
                    $price = $costDataArray['labo_cost'] ?? (is_object($costData) ? ($costData->labo_cost ?? 0) : 0);
                    break;
                default:
                    $price = $costDataArray['basic_cost'] ?? (is_object($costData) ? ($costData->basic_cost ?? 0) : 0);
            }
        }

        // 가격 문자열 정리 (쉼표, "원" 제거, 유니코드 "원" 문자도 처리)
        if (is_string($price)) {
            $price = str_replace(',', '', $price);
            $price = str_replace('원', '', $price);
            $price = str_replace("\xEC\x9B\x90", '', $price); // UTF-8 "원" 바이트 제거
            $price = preg_replace('/\p{Hangul}/u', '', $price); // 한글 문자 제거 (안전장치)
            $price = trim($price);
        }
        $price = (int)$price;

        // 인성 API에서 가격을 가져오지 못한 경우 (0 또는 실패) tbl_pay_info 테이블 조회
        // 루비 버전 참조: ret_distance_price_kt 함수
        if ($price <= 0) {
            log_message('info', "Insung::calculatePriceByDistance - Insung API returned 0 or failed, falling back to tbl_pay_info table. distance={$distance}km, kind={$kind}");
            $price = $this->getPriceFromPayInfo($distance, $kind, $orderData);
            
            if ($price > 0) {
                log_message('info', "Insung::calculatePriceByDistance - Price retrieved from tbl_pay_info: {$price} for kind={$kind}, distance={$distance}km");
            } else {
                log_message('warning', "Insung::calculatePriceByDistance - Price not found in tbl_pay_info for kind={$kind}, distance={$distance}km");
            }
        } else {
            log_message('info', "Insung::calculatePriceByDistance - Price calculated from Insung API: {$price} for kind={$kind}, distance={$distance}km");
        }

        return $price;
    }

    /**
     * tbl_pay_info 테이블에서 거리 기반 가격 조회
     * 루비 버전 참조: ret_distance_price_kt 함수
     * p_comp_gbn = 'K'인 것만 조회
     * 
     * @param int $distance 거리 (km)
     * @param string $kind 배송수단 (1:오토, 2:다마스, 3:트럭, 5:라보)
     * @param array $orderData 주문 데이터 (doc, car_kind 포함)
     * @return int 계산된 가격 (실패 시 0)
     */
    private function getPriceFromPayInfo($distance, $kind, $orderData = [])
    {
        try {
            $db = \Config\Database::connect();
            
            // 루비 버전 참조: pay_info 테이블에서 p_comp_gbn = 'K'이고 p_start_km <= $distance and p_dest_km >= $distance 조건으로 조회
            // p_comp_gbn = 'K'인 것만 조회
            $builder = $db->table('tbl_pay_info');
            $builder->where('p_comp_gbn', 'K');
            $builder->where('p_start_km <=', $distance);
            $builder->where('p_dest_km >=', $distance);
            $builder->limit(1);
            
            $payRow = $builder->get()->getRowArray();
            
            if (!$payRow) {
                log_message('warning', "Insung::getPriceFromPayInfo - No price data found in tbl_pay_info for p_comp_gbn=K, distance={$distance}km");
                return 0;
            }
            
            // 트럭 기본 요금 가져오기 (기준 요금)
            $truckBasePrice = (int)($payRow['p_truck_base_price'] ?? 0);
            
            if ($truckBasePrice <= 0) {
                log_message('warning', "Insung::getPriceFromPayInfo - Truck base price is 0 or not found for distance={$distance}km");
                return 0;
            }
            
            // kind에 따라 요금 계산
            $price = 0;
            switch ($kind) {
                case '1': // 오토 (자전거)
                    $calcType = $payRow['p_bike_calc_type'] ?? 'fixed';
                    $value = (int)($payRow['p_bike_value'] ?? 0);
                    
                    if ($calcType === 'percent') {
                        // 비율 계산: 트럭 기본 요금 × (비율 / 100)
                        $price = (int)($truckBasePrice * ($value / 100));
                    } else {
                        // 고정금액: 트럭 기본 요금 + 고정금액 (또는 고정금액만 사용)
                        // 기존 데이터 호환성을 위해 고정금액이 있으면 그대로 사용, 없으면 트럭 기본 요금 사용
                        $price = $value > 0 ? $value : $truckBasePrice;
                    }
                    break;
                    
                case '2': // 다마스
                    $calcType = $payRow['p_damas_calc_type'] ?? 'fixed';
                    $value = (int)($payRow['p_damas_value'] ?? 0);
                    
                    if ($calcType === 'percent') {
                        // 비율 계산: 트럭 기본 요금 × (비율 / 100)
                        $price = (int)($truckBasePrice * ($value / 100));
                    } else {
                        // 고정금액: 트럭 기본 요금 + 고정금액 (또는 고정금액만 사용)
                        $price = $value > 0 ? $value : $truckBasePrice;
                    }
                    break;
                    
                case '3': // 트럭
                    // car_kind에 따라 적절한 톤수별 요금 사용
                    $carKind = $orderData['car_kind'] ?? '';
                    $truckPrice = 0;
                    
                    // car_kind에 따른 톤수 매핑 (새 구조: JSON 필드 사용)
                    $carKindToTonnage = [
                        '15' => '1.4',   // 카고 (1.4톤)
                        '09' => '2.5',   // 플러스카고 (2.5톤)
                        '20' => '3.5',   // 축카고 (3.5톤)
                        '01' => '5',     // 플축카고 (5톤)
                        '11' => '8',     // 리프트카고 (8톤)
                        '12' => '8',     // 플러스리 (8톤)
                        '42' => '8',     // 플축리 (8톤)
                        '14' => '11',    // 리프트윙 (11톤)
                        '16' => '11',    // 플러스윙리 (11톤)
                        '17' => '11',    // 플축윙리 (11톤)
                        '33' => '25',    // 초장축 (25톤)
                        // 추가 매핑 필요시 여기에 추가
                    ];
                    
                    // p_truck_tonnages JSON 필드에서 톤수별 요금 조회
                    if (!empty($carKind) && isset($carKindToTonnage[$carKind])) {
                        $tonnage = $carKindToTonnage[$carKind];
                        $truckTonnagesJson = $payRow['p_truck_tonnages'] ?? null;
                        
                        if ($truckTonnagesJson) {
                            $truckTonnages = json_decode($truckTonnagesJson, true);
                            if (is_array($truckTonnages) && isset($truckTonnages[$tonnage])) {
                                $truckPrice = (int)$truckTonnages[$tonnage];
                            }
                        }
                    }
                    
                    // 톤수별 요금이 없으면 기본 요금 사용
                    $price = $truckPrice > 0 ? $truckPrice : $truckBasePrice;
                    break;
                    
                case '5': // 라보
                    $calcType = $payRow['p_labo_calc_type'] ?? 'fixed';
                    $value = (int)($payRow['p_labo_value'] ?? 0);
                    
                    if ($calcType === 'percent') {
                        // 비율 계산: 트럭 기본 요금 × (비율 / 100)
                        $price = (int)($truckBasePrice * ($value / 100));
                    } else {
                        // 고정금액: 트럭 기본 요금 + 고정금액 (또는 고정금액만 사용)
                        $price = $value > 0 ? $value : $truckBasePrice;
                    }
                    break;
                    
                default:
                    // 기본값: 트럭 기본 요금 사용
                    $price = $truckBasePrice;
            }
            
            // 루비 버전 참조: doc == 3 (왕복)이면 가격 × 1.7
            $doc = $orderData['doc'] ?? '';
            if ($doc === '3') {
                $price = (int)($price * 1.7);
                log_message('debug', "Insung::getPriceFromPayInfo - Round trip (doc=3), price multiplied by 1.7: {$price}");
            }
            
            return $price;
            
        } catch (\Exception $e) {
            log_message('error', "Insung::getPriceFromPayInfo - Database query failed: " . $e->getMessage());
            return 0;
        }
    }
}

