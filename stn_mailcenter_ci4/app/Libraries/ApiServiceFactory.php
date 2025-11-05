<?php

namespace App\Libraries;

use App\Models\ShippingCompanyModel;

/**
 * API 서비스 팩토리 클래스
 * 다양한 API 서비스를 통합 관리
 */
class ApiServiceFactory
{
    /**
     * API 서비스 인스턴스 생성
     * 
     * @param string $apiType API 타입 (ilyang, inseong, parcel 등)
     * @param bool $isTestMode 테스트 모드 여부
     * @return object API 서비스 인스턴스
     */
    public static function create($apiType, $isTestMode = true)
    {
        switch ($apiType) {
            case 'ilyang':
                return new IlyangApiService($isTestMode);
            
            case 'inseong':
                // TODO: 인성Data API 서비스 구현 예정
                throw new \Exception('인성Data API 서비스는 아직 구현되지 않았습니다.');
            
            case 'parcel':
                // TODO: 택배 API 서비스 구현 예정
                throw new \Exception('택배 API 서비스는 아직 구현되지 않았습니다.');
            
            default:
                throw new \Exception("지원하지 않는 API 타입입니다: {$apiType}");
        }
    }

    /**
     * 서비스 타입에 따른 API 타입 매핑
     * 
     * @param string $serviceType 서비스 타입
     * @return string|null API 타입
     */
    public static function getApiTypeByService($serviceType)
    {
        $mapping = [
            'international' => 'ilyang',      // 해외특송 -> 일양 API
            'parcel-visit' => 'ilyang',       // 방문택배 -> 일양 API
            'parcel-same-day' => 'ilyang',    // 당일택배 -> 일양 API
            'parcel-convenience' => 'ilyang', // 편의점택배 -> 일양 API
            'parcel-night' => 'ilyang',       // 야간배송 -> 일양 API
            'parcel-bag' => 'ilyang',         // 택배백 -> 일양 API
            // 'life-buy' => 'inseong',       // 라이프 구매 -> 인성Data API (향후)
        ];

        return $mapping[$serviceType] ?? null;
    }

    /**
     * API 연동이 필요한 서비스인지 확인
     * 
     * @param string $serviceType 서비스 타입
     * @return bool
     */
    public static function needsApiIntegration($serviceType)
    {
        return self::getApiTypeByService($serviceType) !== null;
    }

    /**
     * 활성화된 운송사 조회 (서비스 타입에 맞는 API를 제공하는 운송사)
     * 
     * @param string $serviceType 서비스 타입
     * @return array|null 활성화된 운송사 정보
     */
    public static function getActiveShippingCompany($serviceType)
    {
        $apiType = self::getApiTypeByService($serviceType);
        
        if (!$apiType) {
            return null;
        }

        $shippingCompanyModel = new \App\Models\ShippingCompanyModel();
        
        // 활성화된 운송사 중에서 해당 API 타입을 제공하는 운송사 조회
        $activeCompanies = $shippingCompanyModel->where('is_active', 1)->findAll();
        
        foreach ($activeCompanies as $company) {
            // api_provider가 없으므로 company_code로 매핑
            // company_code가 apiType과 일치하는 운송사 선택
            if ($company['company_code'] === $apiType) {
                return $company;
            }
        }
        
        return null;
    }

    /**
     * API 서비스 인스턴스 생성 (활성화된 운송사 설정 사용)
     * 
     * @param string $serviceType 서비스 타입
     * @param bool $isTestMode 테스트 모드 여부
     * @return object|null API 서비스 인스턴스
     */
    public static function createForService($serviceType, $isTestMode = true)
    {
        $shippingCompany = self::getActiveShippingCompany($serviceType);
        
        if (!$shippingCompany) {
            log_message('warning', "No active shipping company found for service: {$serviceType}");
            return null;
        }

        $apiType = self::getApiTypeByService($serviceType);
        
        if (!$apiType) {
            return null;
        }

        // API 설정 로드 (api_config JSON에서)
        $apiConfig = [];
        if (!empty($shippingCompany['api_config'])) {
            $apiConfig = is_string($shippingCompany['api_config']) 
                ? json_decode($shippingCompany['api_config'], true) 
                : $shippingCompany['api_config'];
        }

        switch ($apiType) {
            case 'ilyang':
                // API 설정을 IlyangApiService에 전달
                $service = new IlyangApiService($isTestMode, $apiConfig);
                return $service;
            
            case 'inseong':
                // TODO: 인성Data API 서비스 구현 예정
                throw new \Exception('인성Data API 서비스는 아직 구현되지 않았습니다.');
            
            case 'parcel':
                // TODO: 택배 API 서비스 구현 예정
                throw new \Exception('택배 API 서비스는 아직 구현되지 않았습니다.');
            
            default:
                return null;
        }
    }
}
