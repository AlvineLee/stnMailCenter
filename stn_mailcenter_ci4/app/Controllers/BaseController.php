<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = ['url'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = service('session');
    }
    
    /**
     * 서브도메인 접근 권한 체크
     * 서브도메인에 속하지 않는 계정은 접근 불가 (슈퍼 권한 제외)
     * 
     * @return bool|RedirectResponse true면 접근 허용, RedirectResponse면 리다이렉트
     */
    protected function checkSubdomainAccess()
    {
        // 로그인하지 않은 경우는 체크하지 않음 (로그인 페이지로 리다이렉트는 각 컨트롤러에서 처리)
        if (!session()->get('is_logged_in')) {
            return true;
        }
        
        $subdomainConfig = config('Subdomain');
        $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
        
        // default 서브도메인(메인 사이트)은 체크하지 않음
        if ($currentSubdomain === 'default') {
            return true;
        }
        
        // 슈퍼 권한 체크
        $loginType = session()->get('login_type');
        $userRole = session()->get('user_role');
        $userType = session()->get('user_type');
        
        $isSuperAdmin = false;
        if ($loginType === 'daumdata' && $userType == '1') {
            // daumdata 로그인 user_type = 1 (메인 사이트 관리자)
            $isSuperAdmin = true;
        } elseif ($loginType === 'stn' && $userRole === 'super_admin') {
            // STN 로그인 super_admin
            $isSuperAdmin = true;
        }
        
        // 슈퍼 권한이면 모든 서브도메인 접근 가능
        if ($isSuperAdmin) {
            return true;
        }
        
        // 서브도메인의 comp_code 조회
        $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
        if (!$subdomainCompCode) {
            log_message('warning', "Subdomain access check failed: Could not find comp_code for subdomain: {$currentSubdomain}");
            // 세션 삭제 후 로그인 페이지로 리다이렉트 (리다이렉트 루프 방지)
            session()->destroy();
            $subdomainName = $subdomainConfig->getCurrentConfig()['name'] ?? '해당 서브도메인';
            return redirect()->to('/auth/login')
                ->with('error', '서브도메인 정보를 찾을 수 없습니다.')
                ->with('error_detail', "{$subdomainName}의 서브도메인 설정 정보를 찾을 수 없습니다. 시스템 관리자에게 문의해주세요.");
        }
        
        // 사용자의 user_company와 서브도메인의 comp_code 비교
        $userCompany = session()->get('user_company');
        
        // daumdata 로그인인 경우 user_company로 체크
        if ($loginType === 'daumdata') {
            if (empty($userCompany)) {
                log_message('warning', "Subdomain access check failed: user_company is empty for user: " . session()->get('user_id'));
                // 세션 삭제 후 로그인 페이지로 리다이렉트 (리다이렉트 루프 방지)
                session()->destroy();
                $subdomainName = $subdomainConfig->getCurrentConfig()['name'] ?? '해당 서브도메인';
                return redirect()->to('/auth/login')
                    ->with('error', '고객사 정보가 없습니다.')
                    ->with('error_detail', "계정에 고객사 정보가 등록되어 있지 않습니다. {$subdomainName}에 접근하려면 계정에 고객사 정보가 필요합니다. 시스템 관리자에게 문의하여 계정 정보를 확인해주세요.");
            }
            
            if ($userCompany !== $subdomainCompCode) {
                log_message('info', "Subdomain access denied: user_company({$userCompany}) != subdomain_comp_code({$subdomainCompCode}) for user: " . session()->get('user_id'));
                
                // 사용자의 고객사명 조회
                $userCompanyName = '';
                try {
                    $db = \Config\Database::connect();
                    $compBuilder = $db->table('tbl_company_list');
                    $compBuilder->select('comp_name');
                    $compBuilder->where('comp_code', $userCompany);
                    $compQuery = $compBuilder->get();
                    if ($compQuery !== false) {
                        $compResult = $compQuery->getRowArray();
                        if ($compResult && !empty($compResult['comp_name'])) {
                            $userCompanyName = $compResult['comp_name'];
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', "Error retrieving company name: " . $e->getMessage());
                }
                
                // 세션 삭제 후 로그인 페이지로 리다이렉트 (리다이렉트 루프 방지)
                session()->destroy();
                $subdomainName = $subdomainConfig->getCurrentConfig()['name'] ?? '해당 서브도메인';
                $errorMessage = "{$subdomainName}에 접근할 권한이 없습니다.";
                if ($userCompanyName) {
                    $errorMessage .= " 현재 계정은 '{$userCompanyName}' 소속입니다.";
                }
                $errorMessage .= " 올바른 서브도메인으로 접속하시거나, 시스템 관리자에게 접근 권한을 요청해주세요.";
                
                return redirect()->to('/auth/login')
                    ->with('error', '서브도메인 접근 권한이 없습니다.')
                    ->with('error_detail', $errorMessage);
            }
        } else {
            // STN 로그인인 경우 (슈퍼 권한이 아닌 경우)
            // STN 로그인은 일반적으로 서브도메인 접근 불가 (필요시 추가 로직 구현)
            log_message('info', "Subdomain access denied: STN login without super_admin for user: " . session()->get('user_id'));
            // 세션 삭제 후 로그인 페이지로 리다이렉트 (리다이렉트 루프 방지)
            session()->destroy();
            $subdomainName = $subdomainConfig->getCurrentConfig()['name'] ?? '해당 서브도메인';
            return redirect()->to('/auth/login')
                ->with('error', '서브도메인 접근 권한이 없습니다.')
                ->with('error_detail', "STN 로그인 계정은 {$subdomainName}에 접근할 수 없습니다. 서브도메인 접근은 다음데이터(daumdata) 로그인 계정만 가능합니다.");
        }
        
        return true;
    }
    
    /**
     * user_class별 필터 설정 (공통 메서드)
     * Delivery, History, Dashboard 컨트롤러에서 공통으로 사용
     * 
     * @param string $loginType 로그인 타입 ('daumdata' 또는 'stn')
     * @param string|null $userClass user_class 값
     * @param string|null $userType user_type 값
     * @param string|null $userCompany user_company 값
     * @param string|null $userDept user_dept 값
     * @param int|null $userIdx user_idx 값 (user_class=4일 때 정산관리부서 조회용)
     * @param string|null $subdomainCompCode 서브도메인 comp_code
     * @param string|null $userRole user_role 값 (STN 로그인용)
     * @param string|null $customerId customer_id 값 (STN 로그인용)
     * @return array 필터 배열
     */
    protected function buildUserClassFilters(
        $loginType,
        $userClass = null,
        $userType = null,
        $userCompany = null,
        $userDept = null,
        $userIdx = null,
        $subdomainCompCode = null,
        $userRole = null,
        $customerId = null
    ) {
        $filters = [];
        
        // user_class별 필터링 (주문조회 권한)
        // user_type과 user_class는 별개로 판단
        // user_class=1,2일 때는 user_type과 관계없이 전체 조회 권한
        if ($loginType === 'daumdata') {
            if ($userClass == '1' || $userClass == '2') {
                // user_class = 1,2: 전체 주문 리스트 (dept_name 필터 없음, user_type과 관계없이)
                // 서브도메인이 있으면 서브도메인 내 전체, 없으면 전체
                $filters['user_type'] = '1';
                $filters['customer_id'] = null; // 전체 조회
            } elseif ($userClass == '4') {
                // user_class = 4(정산담당자): 정산관리부서로 필터링
                $filters['user_type'] = '1';
                $filters['customer_id'] = null;
                if ($userIdx) {
                    $userSettlementDeptModel = new \App\Models\UserSettlementDeptModel();
                    $settlementDepts = $userSettlementDeptModel->getSettlementDeptNamesForQuery($userIdx);
                    if ($settlementDepts !== null && !empty($settlementDepts)) {
                        $filters['settlement_depts'] = $settlementDepts; // 정산관리부서 목록 필터 추가
                    } else {
                        // 정산관리부서가 설정되지 않았으면 빈 결과
                        $filters['settlement_depts'] = [];
                    }
                } else {
                    $filters['settlement_depts'] = [];
                }
            } elseif ($userClass == '5') {
                // user_class = 5(일반): env1 확인 후 필터링 결정
                $compCodeForEnv = $subdomainCompCode ?? $userCompany;
                $env1 = null;
                if ($compCodeForEnv) {
                    $db = \Config\Database::connect();
                    $envBuilder = $db->table('tbl_company_env');
                    $envBuilder->select('env1');
                    $envBuilder->where('comp_code', $compCodeForEnv);
                    $envQuery = $envBuilder->get();
                    if ($envQuery !== false) {
                        $envResult = $envQuery->getRowArray();
                        if ($envResult && isset($envResult['env1'])) {
                            $env1 = $envResult['env1'];
                        }
                    }
                }
                
                // env1=1(전체 조회)일 때는 user_company 필터만 추가하지 않음 (comp_code 필터는 유지)
                if ($env1 != '1') {
                    $filters['user_type'] = '5';
                    $filters['user_company'] = $userCompany; // 같은 회사의 모든 주문 조회
                } else {
                    // env1=1(전체 조회): user_company 필터만 제거, comp_code 필터는 유지
                    // user_type=5로 유지하되, user_company 필터는 추가하지 않도록 플래그 설정
                    $filters['user_type'] = '5';
                    $filters['skip_user_company_filter'] = true;
                }
            } elseif (isset($userClass) && (int)$userClass >= 3 && !empty($userDept)) {
                // user_class = 3 이상: 부서명으로 필터링 (user_class=5는 위에서 처리됨)
                $filters['user_type'] = '1';
                $filters['customer_id'] = null;
                $filters['user_dept'] = $userDept; // 부서명 필터 추가
            } elseif ($userClass == '3') {
                // user_class = 3(부서장): 전체 주문 리스트 (dept_name이 없을 경우)
                $filters['user_type'] = '1';
                $filters['customer_id'] = null; // 전체 조회
            } else {
                // user_class가 없거나 다른 값인 경우 user_type으로 폴백
                if ($userType == '1') {
                    $filters['user_type'] = '1';
                    $filters['customer_id'] = null;
                } elseif ($userType == '3') {
                    $filters['user_type'] = '3';
                    $filters['user_company'] = $userCompany;
                } elseif ($userType == '5') {
                    $filters['user_type'] = '5';
                    $filters['user_company'] = $userCompany;
                }
            }
        } else {
            // STN 로그인인 경우 기존 로직
            $filters['customer_id'] = $userRole !== 'super_admin' ? $customerId : null;
        }
        
        return $filters;
    }
}
