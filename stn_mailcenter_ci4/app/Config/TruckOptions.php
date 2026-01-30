<?php

namespace App\Config;

/**
 * 트럭 관련 옵션 상수 클래스
 * 
 * 운송수단으로 트럭을 선택했을 때 사용되는
 * 용량, 바디타입 등의 정적 옵션 데이터를 관리합니다.
 */
class TruckOptions 
{
    /**
     * 트럭 용량 옵션
     * 키: 코드값, 값: 표시명
     */
    const TRUCK_CAPACITIES = [
        '1t' => '1t',
        '1t_cargo' => '1t화물',
        '1.4t' => '1.4t',
        '1.4t_cargo' => '1.4t화물',
        '2.5t' => '2.5t',
        '3.5t' => '3.5t',
        '5t' => '5t',
        '8t' => '8t',
        '11t' => '11t',
        '14t' => '14t',
        '15t' => '15t',
        '18t' => '18t',
        '25t' => '25t'
    ];

    /**
     * 트럭 바디/특수 타입 옵션 (통합)
     * 키: 코드값, 값: 표시명
     */
    const TRUCK_BODY_TYPES = [
        // 1열
        'cargo_wing' => '카고/윙',
        'cargo' => '카고',
        'plus_cargo' => '플러스카고',
        'axle_cargo' => '축카고',
        'full_axle_cargo' => '풀축카고',
        'lift_cargo' => '리프트카고',
        'plus_lift' => '플러스리',

        // 2열
        'full_axle_lift' => '풀축리',
        'wing_body' => '윙바디',
        'plus_wing' => '플러스윙',
        'axle_wing' => '축윙',
        'full_axle_wing' => '풀축윙',
        'lift_wing' => '리프트윙',
        'plus_wing_lift' => '플러스윙리',
        'full_axle_wing_lift' => '풀축윙리',

        // 3열
        'top' => '탑',
        'lift_top' => '리프트탑',
        'tarpaulin' => '호루',
        'lift_tarpaulin' => '리프트호루',
        'jabara' => '자바라',
        'lift_jabara' => '리프트자바라',
        'refrigerated_top' => '냉동탑',
        'cold_storage_top' => '냉장탑',

        // 4열
        'refrigerated_wing' => '냉동윙',
        'cold_storage_wing' => '냉장윙',
        'refrigerated_top_lift' => '냉동탑리',
        'cold_storage_top_lift' => '냉장탑리',
        'refrigerated_full_axle_wing' => '냉동풀축윙',
        'cold_storage_full_axle_wing' => '냉장풀축윙',
        'refrigerated_full_axle_lift' => '냉동풀축리',
        'cold_storage_full_axle_lift' => '냉장풀축리',

        // 5열
        'flat_car' => '평카',
        'lowboy' => '로브이',
        'trailer' => '츄레라',
        'lowbed' => '로베드',
        'ladder' => '사다리',
        'extra_long_axle' => '초장축',
        'wide' => '광폭',
        'full_axle_cargo_wing' => '플축카고/윙'
    ];

    /**
     * 트럭 용량 옵션을 배열로 반환
     * 
     * @return array
     */
    public static function getCapacities(): array
    {
        return self::TRUCK_CAPACITIES;
    }

    /**
     * 트럭 바디 타입 옵션을 배열로 반환
     * 
     * @return array
     */
    public static function getBodyTypes(): array
    {
        return self::TRUCK_BODY_TYPES;
    }

    /**
     * 특정 용량의 표시명을 반환
     * 
     * @param string $capacity
     * @return string|null
     */
    public static function getCapacityName(string $capacity): ?string
    {
        return self::TRUCK_CAPACITIES[$capacity] ?? null;
    }

    /**
     * 특정 바디 타입의 표시명을 반환
     * 
     * @param string $bodyType
     * @return string|null
     */
    public static function getBodyTypeName(string $bodyType): ?string
    {
        return self::TRUCK_BODY_TYPES[$bodyType] ?? null;
    }

    /**
     * 용량이 유효한지 확인
     * 
     * @param string $capacity
     * @return bool
     */
    public static function isValidCapacity(string $capacity): bool
    {
        return array_key_exists($capacity, self::TRUCK_CAPACITIES);
    }

    /**
     * 바디 타입이 유효한지 확인
     * 
     * @param string $bodyType
     * @return bool
     */
    public static function isValidBodyType(string $bodyType): bool
    {
        return array_key_exists($bodyType, self::TRUCK_BODY_TYPES);
    }
}
