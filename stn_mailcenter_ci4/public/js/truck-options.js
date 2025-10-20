/**
 * 트럭 관련 옵션 상수
 * 
 * 운송수단으로 트럭을 선택했을 때 사용되는
 * 용량, 바디타입 등의 정적 옵션 데이터를 관리합니다.
 */
const TRUCK_OPTIONS = {
    /**
     * 트럭 용량 옵션
     */
    capacities: {
        '1t': '1t',
        '1t_cargo': '1t화물',
        '1.2t_cargo': '1.2t화물',
        '1.4t': '1.4t',
        '1.4t_cargo': '1.4t화물',
        '2.5t': '2.5t',
        '3.5t': '3.5t',
        '4.5t': '4.5t',
        '4.5t_axle': '4.5t축',
        '4.5t_plus': '4.5t플러스',
        '5t': '5t',
        '8t': '8t',
        '11t': '11t',
        '14t': '14t',
        '15t': '15t',
        '18t': '18t',
        '25t': '25t'
    },

    /**
     * 트럭 바디/특수 타입 옵션 (통합)
     */
    bodyTypes: {
        // 일반 바디 타입
        'cargo': '카고',
        'plus_cargo': '플러스카고',
        'axle_cargo': '축카고',
        'plus_axle_cargo': '플축카고',
        'lift_cargo': '리프트카고',
        'wing_body': '윙바디',
        'plus_wing': '플러스윙',
        'axle_wing': '축윙',
        'plus_axle_wing': '플축윙',
        'lift_wing': '리프트윙',
        'top': '탑',
        'lift_top': '리프트탑',
        'tarpaulin': '호루',
        'lift_tarpaulin': '리프트호루',
        
        // 특수 바디 타입
        'jabara': '자바라',
        'lift_jabara': '리프트자바라',
        'refrigerated_top': '냉동탑',
        'cold_storage_top': '냉장탑',
        'refrigerated_wing': '냉동윙',
        'cold_storage_wing': '냉장윙',
        'refrigerated_top_lift': '냉동탑리',
        'cold_storage_top_lift': '냉장탑리',
        'refrigerated_plus_axle_wing': '냉동플축윙',
        'cold_storage_plus_axle_wing': '냉장플축윙',
        'refrigerated_plus_axle_lift': '냉동플축리',
        'cold_storage_plus_axle_lift': '냉장플축리',
        'flat_car': '평카',
        'lowboy': '로브이',
        'trailer': '츄레라',
        'lowbed': '로베드',
        'ladder': '사다리',
        'extra_long_axle': '초장축'
    }
};

/**
 * 트럭 옵션 유틸리티 함수들
 */
const TruckOptionsUtils = {
    /**
     * 특정 용량의 표시명을 반환
     * 
     * @param {string} capacity - 용량 코드
     * @returns {string|null} 표시명 또는 null
     */
    getCapacityName: function(capacity) {
        return TRUCK_OPTIONS.capacities[capacity] || null;
    },

    /**
     * 특정 바디 타입의 표시명을 반환
     * 
     * @param {string} bodyType - 바디 타입 코드
     * @returns {string|null} 표시명 또는 null
     */
    getBodyTypeName: function(bodyType) {
        return TRUCK_OPTIONS.bodyTypes[bodyType] || null;
    },

    /**
     * 용량이 유효한지 확인
     * 
     * @param {string} capacity - 용량 코드
     * @returns {boolean} 유효 여부
     */
    isValidCapacity: function(capacity) {
        return capacity in TRUCK_OPTIONS.capacities;
    },

    /**
     * 바디 타입이 유효한지 확인
     * 
     * @param {string} bodyType - 바디 타입 코드
     * @returns {boolean} 유효 여부
     */
    isValidBodyType: function(bodyType) {
        return bodyType in TRUCK_OPTIONS.bodyTypes;
    },

    /**
     * 용량 옵션을 select 옵션으로 변환
     * 
     * @returns {string} HTML option 태그들
     */
    getCapacityOptions: function() {
        let options = '<option value="">용량을 선택하세요</option>';
        for (const [key, value] of Object.entries(TRUCK_OPTIONS.capacities)) {
            options += `<option value="${key}">${value}</option>`;
        }
        return options;
    },

    /**
     * 바디 타입 옵션을 select 옵션으로 변환
     * 
     * @returns {string} HTML option 태그들
     */
    getBodyTypeOptions: function() {
        let options = '<option value="">바디 타입을 선택하세요</option>';
        for (const [key, value] of Object.entries(TRUCK_OPTIONS.bodyTypes)) {
            options += `<option value="${key}">${value}</option>`;
        }
        return options;
    }
};
