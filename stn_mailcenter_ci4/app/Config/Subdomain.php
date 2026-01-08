<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * 서브도메인별 설정 관리
 * 각 서브도메인에 대한 브랜딩 정보 (로고, 연락처 등)를 관리
 */
class Subdomain extends BaseConfig
{
    /**
     * 서브도메인별 설정
     * 
     * @var array<string, array{name: string, logo_path: string, logo_text: string, contact: string, theme_color: string}>
     */
    public array $configs = [
        'lgchem' => [
            'name' => 'LG화학',
            'logo_path' => '/assets/images/logos/lgchem.png', // 로고 이미지 경로
            'logo_text' => 'LG화학', // 텍스트 로고 (이미지 없을 때)
            'contact' => '02-1234-5678',
            'email' => 'support@lgchem.daumdata.com',
            'theme_color' => '#0066CC', // LG화학 브랜드 컬러
            'description' => 'LG화학 전용 온라인 접수 시스템',
            'comp_code' => '2327787'
        ],
        'gucci' => [
            'name' => '구찌',
            'logo_path' => '/assets/images/logos/gucci.png',
            'logo_text' => 'GUCCI',
            'contact' => '02-9876-5432',
            'email' => 'support@gucci.daumdata.com',
            'theme_color' => '#1C1C1C', // 구찌 브랜드 컬러
            'description' => '구찌 전용 온라인 접수 시스템',
            'm_code' => null, // DB에서 조회 (서브도메인 기반)
            'cc_code' => null, // 로드로지스 (ROD)
            'cccode' => null, // 로드로지스 (ROD) - API 조회용
            'comp_code' => '1136344' // 구찌 거래처 코드
        ],
        'aloyoga' => [
            'name' => '알로요가코리아',
            'logo_path' => '/assets/images/logos/aloyoga.png',
            'logo_text' => '알로요가코리아',
            'contact' => '02-0000-0000',
            'email' => 'support@aloyoga.daumdata.com',
            'theme_color' => '#667eea',
            'description' => '알로요가코리아 전용 온라인 접수 시스템',
            'm_code' => null, // DB에서 조회 (서브도메인 기반)
            'cc_code' => null, // 로드로지스 (ROD)
            'cccode' => null, // 로드로지스 (ROD) - API 조회용
            'comp_code' => '2335527'
        ],
        'dev' => [
            'name' => 'DaumData (개발)',
            'logo_path' => 'assets/images/logo/daumdata_logo_2.png',
            'logo_text' => 'DaumData',
            'contact' => '02-0000-0000',
            'email' => 'support@dev.daumdata.com',
            'theme_color' => '#667eea',
            'description' => '개발 서버',
            'm_code' => null, // DB에서 조회 (서브도메인 기반)
            'cc_code' => null // DB에서 조회 (서브도메인 기반)
        ],
        // 기본 설정 (서브도메인 없거나 매칭되지 않는 경우)
        'default' => [
            'name' => 'DaumData',
            'logo_path' => 'assets/images/logo/daumdata_logo_2.png',
            'logo_text' => 'DaumData',
            'contact' => '02-0000-0000',
            'email' => 'support@daumdata.com',
            'theme_color' => '#667eea',
            'description' => 'ONE\'CALL'
        ]
    ];
    
    /**
     * 현재 서브도메인 감지 및 설정 반환
     * 
     * @return array 선언된 서브도메인이면 해당 설정, 없으면 default 설정 반환
     */
    public function getCurrentConfig(): array
    {
        $subdomain = $this->detectSubdomain();
        
        // 선언된 서브도메인이 있으면 해당 설정 반환
        if ($subdomain && isset($this->configs[$subdomain])) {
            return $this->configs[$subdomain];
        }
        
        // 선언되지 않은 서브도메인 또는 기본 도메인은 default 설정 반환
        return $this->configs['default'];
    }
    
    /**
     * 현재 서브도메인 감지
     * 
     * @return string|null 설정에 선언된 서브도메인만 반환, 없으면 null (default로 처리)
     */
    public function detectSubdomain(): ?string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // lgchem.daumdata.com, lgchem.local.daumdata.com, lgchem.dev.daumdata.com 등에서 서브도메인 추출
        // 패턴 1: lgchem.daumdata.com (운영)
        // 패턴 2: lgchem.local.daumdata.com (로컬)
        // 패턴 3: lgchem.dev.daumdata.com (개발서버)
        if (preg_match('/^([^.]+)\.(local\.|dev\.)?daumdata\.com/', $host, $matches)) {
            $subdomain = $matches[1];
            
            // 'local', 'api', 'localapi', 'www', 'dev' 등은 기본 도메인으로 처리
            if (in_array($subdomain, ['local', 'api', 'localapi', 'www', 'dev'])) {
                return null;
            }
            
            // 설정에 선언된 서브도메인만 반환
            // 선언되지 않은 서브도메인은 null 반환하여 default로 처리
            if (isset($this->configs[$subdomain])) {
                return $subdomain;
            }
            
            // 설정에 없는 서브도메인은 null 반환 (default로 처리됨)
            return null;
        }
        
        return null;
    }
    
    /**
     * 현재 서브도메인 이름 반환
     * 
     * @return string
     */
    public function getCurrentSubdomain(): string
    {
        return $this->detectSubdomain() ?? 'default';
    }
    
    /**
     * 등록되지 않은 서브도메인인지 확인
     * 
     * @return bool 등록되지 않은 서브도메인이면 true, 아니면 false
     */
    public function isUnregisteredSubdomain(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // 서브도메인 패턴 확인
        if (preg_match('/^([^.]+)\.(local\.|dev\.)?daumdata\.com/', $host, $matches)) {
            $subdomain = $matches[1];
            
            // 'local', 'api', 'localapi', 'www', 'dev' 등은 기본 도메인으로 처리 (등록되지 않은 서브도메인이 아님)
            if (in_array($subdomain, ['local', 'api', 'localapi', 'www', 'dev'])) {
                return false;
            }
            
            // 설정에 없는 서브도메인은 등록되지 않은 서브도메인
            if (!isset($this->configs[$subdomain])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 감지된 서브도메인 이름 반환 (등록 여부와 관계없이)
     * 
     * @return string|null 서브도메인이 감지되면 서브도메인 이름, 아니면 null
     */
    public function getDetectedSubdomain(): ?string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        if (preg_match('/^([^.]+)\.(local\.|dev\.)?daumdata\.com/', $host, $matches)) {
            $subdomain = $matches[1];
            
            // 'local', 'api', 'localapi', 'www', 'dev' 등은 기본 도메인으로 처리
            if (in_array($subdomain, ['local', 'api', 'localapi', 'www', 'dev'])) {
                return null;
            }
            
            return $subdomain;
        }
        
        return null;
    }
    
    /**
     * 서브도메인별 m_code, cc_code 조회 (DB에서)
     * 
     * @param string $subdomain 서브도메인 이름
     * @return array|null ['m_code' => string, 'cc_code' => string] 또는 null
     */
    public function getApiCodesBySubdomain($subdomain): ?array
    {
        if (!$subdomain || !isset($this->configs[$subdomain])) {
            return null;
        }
        
        try {
            $db = \Config\Database::connect();
            
            // 서브도메인 이름으로 고객사명 매핑
            $companyNameMap = [
                'gucci' => '구찌',
                'lgchem' => 'LG화학',
                'aloyoga' => '알로요가코리아'
            ];
            
            $companyName = $companyNameMap[$subdomain] ?? $this->configs[$subdomain]['name'] ?? null;
            
            if (!$companyName) {
                return null;
            }
            
            // 고객사명으로 comp_code 조회
            // comp_owner 필드에서 검색 (comp_name은 'ROD_구찌'처럼 접두사가 있어서 매칭 안됨)
            $compBuilder = $db->table('tbl_company_list c');
            $compBuilder->select('c.comp_code');
            $compBuilder->like('c.comp_owner', $companyName);
            $compQuery = $compBuilder->get();
            
            if ($compQuery === false) {
                return null;
            }
            
            $compResult = $compQuery->getRowArray();
            if (!$compResult || empty($compResult['comp_code'])) {
                return null;
            }
            
            $compCode = $compResult['comp_code'];
            
            // comp_code로 API 정보 조회 (tbl_company_list -> tbl_cc_list -> tbl_api_list)
            $apiBuilder = $db->table('tbl_company_list c');
            $apiBuilder->select('
                d.mcode as m_code,
                d.cccode as cc_code
            ');
            $apiBuilder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
            $apiBuilder->join('tbl_api_list d', 'cc.cc_apicode = d.idx', 'inner');
            $apiBuilder->where('c.comp_code', $compCode);
            $apiQuery = $apiBuilder->get();
            
            if ($apiQuery === false) {
                return null;
            }
            
            $apiResult = $apiQuery->getRowArray();
            if (!$apiResult || empty($apiResult['m_code']) || empty($apiResult['cc_code'])) {
                return null;
            }
            
            return [
                'm_code' => $apiResult['m_code'],
                'cc_code' => $apiResult['cc_code']
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'Subdomain::getApiCodesBySubdomain - Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 현재 서브도메인의 m_code, cc_code 조회
     * 
     * @return array|null ['m_code' => string, 'cc_code' => string] 또는 null
     */
    public function getCurrentApiCodes(): ?array
    {
        $subdomain = $this->detectSubdomain();
        if (!$subdomain) {
            return null;
        }
        
        return $this->getApiCodesBySubdomain($subdomain);
    }
    
    /**
     * 서브도메인별 comp_code 캐시
     * 
     * @var array<string, string|null>
     */
    private static $compCodeCache = [];
    
    /**
     * 서브도메인별 comp_code 조회 (설정 우선, 없으면 DB에서)
     * 
     * @param string $subdomain 서브도메인 이름
     * @return string|null comp_code 또는 null
     */
    public function getCompCodeBySubdomain($subdomain): ?string
    {
        if (!$subdomain || !isset($this->configs[$subdomain])) {
            return null;
        }
        
        // 캐시 확인
        if (isset(self::$compCodeCache[$subdomain])) {
            return self::$compCodeCache[$subdomain];
        }
        
        // 1. 설정에 comp_code가 하드코딩되어 있으면 우선 사용
        if (isset($this->configs[$subdomain]['comp_code']) && !empty($this->configs[$subdomain]['comp_code'])) {
            $compCode = $this->configs[$subdomain]['comp_code'];
            log_message('debug', "Subdomain::getCompCodeBySubdomain - 설정에서 조회: subdomain={$subdomain}, comp_code={$compCode}");
            // 캐시에 저장
            self::$compCodeCache[$subdomain] = $compCode;
            return $compCode;
        }
        
        // 2. 설정에 없으면 DB에서 조회
        try {
            $db = \Config\Database::connect();
            
            // 서브도메인 이름으로 고객사명 매핑
            $companyNameMap = [
                'gucci' => '구찌',
                'lgchem' => 'LG화학',
                'aloyoga' => '알로요가코리아'
            ];
            
            $companyName = $companyNameMap[$subdomain] ?? $this->configs[$subdomain]['name'] ?? null;
            
            if (!$companyName) {
                self::$compCodeCache[$subdomain] = null;
                return null;
            }
            
            // 고객사명으로 comp_code 조회
            // comp_owner 필드에서 검색 (comp_name은 'ROD_구찌'처럼 접두사가 있어서 매칭 안됨)
            $compBuilder = $db->table('tbl_company_list c');
            $compBuilder->select('c.comp_code, c.comp_name');
            $compBuilder->like('c.comp_owner', $companyName);
            $compBuilder->limit(1);
            $compQuery = $compBuilder->get();
            
            if ($compQuery === false) {
                log_message('debug', "Subdomain::getCompCodeBySubdomain - 쿼리 실패: subdomain={$subdomain}, companyName={$companyName}");
                self::$compCodeCache[$subdomain] = null;
                return null;
            }
            
            $compResult = $compQuery->getRowArray();
            if (!$compResult || empty($compResult['comp_code'])) {
                log_message('debug', "Subdomain::getCompCodeBySubdomain - 결과 없음: subdomain={$subdomain}, companyName={$companyName}");
                self::$compCodeCache[$subdomain] = null;
                return null;
            }
            
            $compCode = $compResult['comp_code'];
            $compName = $compResult['comp_name'] ?? '';
            log_message('debug', "Subdomain::getCompCodeBySubdomain - DB 조회 성공: subdomain={$subdomain}, companyName={$companyName}, comp_code={$compCode}, comp_name={$compName}");
            
            // 캐시에 저장
            self::$compCodeCache[$subdomain] = $compCode;
            
            return $compCode;
            
        } catch (\Exception $e) {
            log_message('error', 'Subdomain::getCompCodeBySubdomain - Error: ' . $e->getMessage());
            self::$compCodeCache[$subdomain] = null;
            return null;
        }
    }
    
    /**
     * 현재 서브도메인의 comp_code 조회
     * 
     * @return string|null comp_code 또는 null
     */
    public function getCurrentCompCode(): ?string
    {
        $subdomain = $this->detectSubdomain();
        if (!$subdomain) {
            return null;
        }
        
        // 캐시 확인
        if (isset(self::$compCodeCache[$subdomain])) {
            return self::$compCodeCache[$subdomain];
        }
        
        // 설정에 comp_code가 하드코딩되어 있으면 우선 사용
        if (isset($this->configs[$subdomain]['comp_code']) && !empty($this->configs[$subdomain]['comp_code'])) {
            $compCode = $this->configs[$subdomain]['comp_code'];
            // 캐시에 저장 (로그는 첫 호출 시에만 출력)
            self::$compCodeCache[$subdomain] = $compCode;
            // log_message('debug', "Subdomain::getCurrentCompCode - 설정에서 조회: subdomain={$subdomain}, comp_code={$compCode}");
            return $compCode;
        }
        
        // 설정에 없으면 DB에서 조회 (이미 캐싱 포함)
        return $this->getCompCodeBySubdomain($subdomain);
    }
    
    /**
     * 서브도메인별 API 정보 조회 (m_code, cc_code, token, api_idx)
     * 
     * @param string $subdomain 서브도메인 이름
     * @return array|null ['m_code' => string, 'cc_code' => string, 'token' => string, 'api_idx' => int] 또는 null
     */
    public function getApiInfoForSubdomain($subdomain): ?array
    {
        if (!$subdomain || !isset($this->configs[$subdomain])) {
            return null;
        }
        
        try {
            $db = \Config\Database::connect();
            
            // comp_code 조회
            $compCode = $this->getCompCodeBySubdomain($subdomain);
            if (!$compCode) {
                return null;
            }
            
            // comp_code로 API 정보 조회 (tbl_company_list -> tbl_cc_list -> tbl_api_list)
            $apiBuilder = $db->table('tbl_company_list c');
            $apiBuilder->select('
                d.idx as api_idx,
                d.mcode as m_code,
                d.cccode as cc_code,
                d.token
            ');
            $apiBuilder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'inner');
            $apiBuilder->join('tbl_api_list d', 'cc.cc_apicode = d.idx', 'inner');
            $apiBuilder->where('c.comp_code', $compCode);
            $apiQuery = $apiBuilder->get();
            
            if ($apiQuery === false) {
                return null;
            }
            
            $apiResult = $apiQuery->getRowArray();
            if (!$apiResult || empty($apiResult['m_code']) || empty($apiResult['cc_code'])) {
                return null;
            }
            
            return [
                'api_idx' => (int)$apiResult['api_idx'],
                'm_code' => $apiResult['m_code'],
                'cc_code' => $apiResult['cc_code'],
                'token' => $apiResult['token'] ?? ''
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'Subdomain::getApiInfoForSubdomain - Error: ' . $e->getMessage());
            return null;
        }
    }
}

