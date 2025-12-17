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
}
