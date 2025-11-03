<?php

if (!function_exists('getUserServicePermissions')) {
    /**
     * 현재 로그인한 사용자의 활성화된 서비스 권한 조회 (service_code를 키로 하는 배열)
     */
    function getUserServicePermissions()
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return [];
        }
        
        // 슈퍼관리자는 모든 서비스 접근 가능
        if (session()->get('user_role') === 'super_admin') {
            return getActiveServiceTypes();
        }
        
        $userServicePermissionModel = new \App\Models\UserServicePermissionModel();
        $permissions = $userServicePermissionModel->getUserServicePermissions($userId);
        
        // 활성화된 서비스만 반환 (service_code를 키로)
        // 마스터 데이터의 활성화 여부를 우선 확인 (service_is_active = 1)
        // 그 다음 계정별 권한 확인 (is_enabled = 1)
        $activePermissions = [];
        foreach ($permissions as $permission) {
            // 마스터가 활성화되어 있고, 계정 권한도 활성화되어 있어야 함
            $isMasterActive = isset($permission['service_is_active']) && $permission['service_is_active'] == 1;
            $isAccountEnabled = isset($permission['is_enabled']) && $permission['is_enabled'] == 1;
            
            if (!empty($permission['service_code']) && $isMasterActive && $isAccountEnabled) {
                $activePermissions[$permission['service_code']] = [
                    'id' => $permission['service_type_id'],
                    'service_code' => $permission['service_code'],
                    'service_name' => $permission['service_name'],
                    'service_category' => $permission['service_category']
                ];
            }
        }
        
        return $activePermissions;
    }
}

if (!function_exists('getActiveServiceTypes')) {
    /**
     * 모든 활성 서비스 타입 조회
     */
    function getActiveServiceTypes()
    {
        $serviceTypeModel = new \App\Models\ServiceTypeModel();
        $serviceTypes = $serviceTypeModel->where('is_active', 1)->findAll();
        
        $result = [];
        foreach ($serviceTypes as $service) {
            $result[$service['service_code']] = [
                'id' => $service['id'],
                'service_code' => $service['service_code'],
                'service_name' => $service['service_name'],
                'service_category' => $service['service_category']
            ];
        }
        
        return $result;
    }
}

if (!function_exists('hasServicePermission')) {
    /**
     * 사용자가 특정 서비스 권한을 가지고 있는지 확인
     */
    function hasServicePermission($serviceCode)
    {
        $permissions = getUserServicePermissions();
        return isset($permissions[$serviceCode]);
    }
}

if (!function_exists('getServiceMenuIcon')) {
    /**
     * 서비스 코드에 따른 아이콘 경로 반환
     */
    function getServiceMenuIcon($serviceCode)
    {
        // 서비스 코드별 아이콘 매핑 (기존 하드코딩된 구조 유지)
        $iconMap = [
            'mailroom' => 'assets/icons/18.png',
            'quick-motorcycle' => 'assets/icons/49.png',
            'quick-vehicle' => 'assets/icons/25.png',
            'quick-flex' => 'assets/icons/169.png',
            'quick-moving' => 'assets/icons/200.png',
            'international' => 'assets/icons/12.png',
            'linked-bus' => 'assets/icons/bus.svg',
            'linked-ktx' => 'assets/icons/train.svg',
            'linked-airport' => 'assets/icons/airport.svg',
            'linked-shipping' => 'assets/icons/ship.svg',
            'parcel-visit' => 'assets/icons/walking.svg',
            'parcel-same-day' => 'assets/icons/clock.svg',
            'parcel-convenience' => 'assets/icons/store.svg',
            'parcel-bag' => 'assets/icons/bag.svg',
            'postal' => 'assets/icons/envelope.svg',
            'general-document' => 'assets/icons/document.svg',
            'general-errand' => 'assets/icons/people.svg',
            'general-tax' => 'assets/icons/tax.svg',
            'life-buy' => 'assets/icons/shopping.svg',
            'life-taxi' => 'assets/icons/taxi.svg',
            'life-driver' => 'assets/icons/car.svg',
            'life-wreath' => 'assets/icons/flower.svg',
            'life-accommodation' => 'assets/icons/hotel.svg',
            'life-stationery' => 'assets/icons/pencil.svg'
        ];
        
        // 매핑에 있으면 해당 아이콘 사용, 없으면 기본 아이콘
        return $iconMap[$serviceCode] ?? 'assets/icons/general.svg';
    }
}

if (!function_exists('getCategoryMenuIcon')) {
    /**
     * 카테고리에 따른 아이콘 경로 반환
     */
    function getCategoryMenuIcon($category)
    {
        $categoryIconMap = [
            '퀵서비스' => 'assets/icons/71.png',
            'quick' => 'assets/icons/71.png',
            '연계배송서비스' => 'assets/icons/linked.svg',
            'linked' => 'assets/icons/linked.svg',
            '택배서비스' => 'assets/icons/parcel.svg',
            'parcel' => 'assets/icons/parcel.svg',
            '우편서비스' => 'assets/icons/envelope.svg',
            'postal' => 'assets/icons/envelope.svg',
            '일반서비스' => 'assets/icons/general.svg',
            'general' => 'assets/icons/general.svg',
            '생활서비스' => 'assets/icons/life.svg',
            'life' => 'assets/icons/life.svg',
            '메일룸서비스' => 'assets/icons/18.png',
            'mailroom' => 'assets/icons/18.png',
            '해외특송서비스' => 'assets/icons/12.png',
            'international' => 'assets/icons/12.png'
        ];
        
        return $categoryIconMap[$category] ?? 'assets/icons/general.svg';
    }
}

if (!function_exists('getCategoryDisplayName')) {
    /**
     * 카테고리 표시명 반환
     */
    function getCategoryDisplayName($category)
    {
        $categoryNameMap = [
            'quick' => '퀵서비스',
            'linked' => '연계배송서비스',
            'parcel' => '택배서비스',
            'postal' => '우편서비스',
            'general' => '일반서비스',
            'life' => '생활서비스',
            'mailroom' => '메일룸서비스',
            'international' => '해외특송서비스'
        ];
        
        // 이미 한글이면 그대로 반환
        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $category)) {
            return $category;
        }
        
        return $categoryNameMap[$category] ?? $category;
    }
}

if (!function_exists('buildDynamicServiceMenu')) {
    /**
     * DB에서 가져온 서비스 타입으로 동적 메뉴 구조 생성
     */
    function buildDynamicServiceMenu($userPermissions)
    {
        $serviceTypeModel = new \App\Models\ServiceTypeModel();
        
        // 활성 서비스 타입 조회 (카테고리별 그룹화)
        $serviceTypesGrouped = $serviceTypeModel->getServiceTypesGroupedByCategory();
        
        if (empty($serviceTypesGrouped)) {
            return [];
        }
        
        // 카테고리 순서 정의 (사용자 지정 순서)
        $categoryOrder = [
            '메일룸서비스',
            '퀵서비스',
            '해외특송서비스',
            '연계배송서비스',
            '택배서비스',
            '우편서비스',
            '일반서비스',
            '생활서비스',
            'mailroom',
            'quick',
            'international',
            'linked',
            'parcel',
            'postal',
            'general',
            'life'
        ];
        
        // 카테고리를 지정한 순서대로 정렬
        $orderedCategories = [];
        foreach ($categoryOrder as $orderedCategory) {
            // 한글 카테고리명이나 영문 카테고리명 매칭
            foreach ($serviceTypesGrouped as $category => $services) {
                $categoryDisplayName = getCategoryDisplayName($category);
                if ($orderedCategory === $category || $orderedCategory === $categoryDisplayName) {
                    $orderedCategories[$category] = $services;
                    break;
                }
            }
        }
        
        // 순서에 없는 카테고리는 뒤에 추가
        foreach ($serviceTypesGrouped as $category => $services) {
            if (!isset($orderedCategories[$category])) {
                $orderedCategories[$category] = $services;
            }
        }
        
        $menuItems = [];
        
        // 지정한 순서대로 카테고리 처리
        foreach ($orderedCategories as $category => $services) {
            // 권한이 있는 서비스만 필터링
            $allowedServices = [];
            foreach ($services as $service) {
                if (hasServicePermission($service['service_code'])) {
                    $allowedServices[] = $service;
                }
            }
            
            // 권한이 있는 서비스가 없으면 스킵
            if (empty($allowedServices)) {
                continue;
            }
            
            // sort_order로 정렬 (NULL 값은 마지막으로)
            usort($allowedServices, function($a, $b) {
                $sortA = isset($a['sort_order']) ? (int)$a['sort_order'] : 9999;
                $sortB = isset($b['sort_order']) ? (int)$b['sort_order'] : 9999;
                
                if ($sortA === $sortB) {
                    // sort_order가 같으면 서비스명으로 정렬
                    return strcmp($a['service_name'] ?? '', $b['service_name'] ?? '');
                }
                return $sortA <=> $sortB;
            });
            
            // 카테고리 표시명
            $categoryDisplayName = getCategoryDisplayName($category);
            $categoryIcon = getCategoryMenuIcon($category);
            
            // 서비스가 1개만 있으면 단일 메뉴로 표시
            if (count($allowedServices) === 1) {
                $service = $allowedServices[0];
                $isExternalLink = !empty($service['is_external_link']) && $service['is_external_link'] == 1;
                $menuItems[] = [
                    'type' => 'single',
                    'menu' => [
                        'url' => $isExternalLink ? ($service['external_url'] ?? '#') : ('service/' . $service['service_code']),
                        'icon' => getServiceMenuIcon($service['service_code']),
                        'name' => $service['service_name'],
                        'is_external_link' => $isExternalLink
                    ]
                ];
            } else {
                // 여러 개면 하위 메뉴로 그룹화
                // sort_order 순서대로 정렬된 배열을 순서대로 처리
                $children = [];
                foreach ($allowedServices as $service) {
                    $isExternalLink = !empty($service['is_external_link']) && $service['is_external_link'] == 1;
                    $children[] = [
                        'service_code' => $service['service_code'],
                        'url' => $isExternalLink ? ($service['external_url'] ?? '#') : ('service/' . $service['service_code']),
                        'icon' => getServiceMenuIcon($service['service_code']),
                        'name' => $service['service_name'],
                        'is_external_link' => $isExternalLink
                    ];
                }
                
                $menuItems[] = [
                    'type' => 'submenu',
                    'menu' => [
                        'name' => $categoryDisplayName,
                        'icon' => $categoryIcon
                    ],
                    'children' => $children
                ];
            }
        }
        
        return $menuItems;
    }
}

