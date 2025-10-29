<?php

namespace App\Libraries;

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
            'international' => 'ilyang',  // 해외특송 -> 일양 API
            // 'parcel-visit' => 'parcel',    // 방문택배 -> 택배 API (향후)
            // 'parcel-same-day' => 'parcel', // 당일택배 -> 택배 API (향후)
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
}
