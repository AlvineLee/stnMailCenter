<?php

if (!function_exists('getUserServicePermissions')) {
    /**
     * 현재 로그인한 사용자의 활성화된 서비스 권한 조회 (service_code를 키로 하는 배열)
     */
    function getUserServicePermissions()
    {
        $userId = session()->get('user_id');
        $loginType = session()->get('login_type');
        
        if (!$userId) {
            return [];
        }
        
        // daumdata 로그인인 경우
        if ($loginType === 'daumdata') {
            $userType = session()->get('user_type');
            $userCompany = session()->get('user_company'); // 거래처 코드
            $ccCode = session()->get('cc_code'); // 콜센터 코드
            
            // 서브도메인 설정값 확인 (최우선순위)
            $subdomainConfig = config('Subdomain');
            $currentSubdomain = $subdomainConfig->getCurrentSubdomain();
            $subdomainCompCode = $subdomainConfig->getCurrentCompCode();
            $subdomainApiCodes = null;
            
            // 서브도메인이 있고 default가 아닌 경우, 서브도메인에서 m_code, cc_code 조회
            if ($currentSubdomain !== 'default') {
                $subdomainApiCodes = $subdomainConfig->getCurrentApiCodes();
                
                // 서브도메인에서 조회한 m_code, cc_code가 있으면 사용 (슈퍼권한 계정의 경우 세션에 없을 수 있음)
                if ($subdomainApiCodes && (!empty($subdomainApiCodes['m_code']) || !empty($subdomainApiCodes['cc_code']))) {
                    // 세션에 없으면 서브도메인에서 조회한 값 사용
                    if (empty($ccCode) && !empty($subdomainApiCodes['cc_code'])) {
                        $ccCode = $subdomainApiCodes['cc_code'];
                    }
                }
            }
            
            // user_type = 1 (메인 사이트 관리자)도 거래처/콜센터 권한을 따름
            // 거래처/콜센터 정보가 없고 서브도메인도 없으면 모든 서비스 접근 가능 (관리자 전용)
            if ($userType == '1' && !$userCompany && !$ccCode && $currentSubdomain === 'default') {
                return getActiveServiceTypes();
            }
            
            // 모든 활성 서비스 타입 조회
            $allServices = getActiveServiceTypes();
            if (empty($allServices)) {
                return [];
            }
            
            // 권한 맵 초기화 (service_code를 키로)
            $permissionMap = [];
            
            log_message('debug', "menu_helper::getUserServicePermissions - 서브도메인: {$currentSubdomain}, 조회된 comp_code: " . ($subdomainCompCode ?? 'NULL') . ", userCompany: " . ($userCompany ?? 'NULL') . ", ccCode: " . ($ccCode ?? 'NULL') . ", subdomainApiCodes: " . ($subdomainApiCodes ? json_encode($subdomainApiCodes) : 'NULL'));
            
            // 서브도메인이 있고 default가 아닌 경우, 서브도메인 권한만 사용 (최우선)
            if ($currentSubdomain !== 'default') {
                // 서브도메인 comp_code가 조회되지 않았으면 빈 배열 반환 (서브도메인 설정이 우선이므로)
                if (!$subdomainCompCode) {
                    log_message('warning', "menu_helper::getUserServicePermissions - 서브도메인({$currentSubdomain})에서 comp_code 조회 실패, 빈 배열 반환");
                    return [];
                }
                
                $companyServicePermissionModel = new \App\Models\CompanyServicePermissionModel();
                $subdomainPermissions = $companyServicePermissionModel->getCompanyServicePermissions($subdomainCompCode);
                
                log_message('debug', "menu_helper::getUserServicePermissions - 서브도메인 comp_code: {$subdomainCompCode}, 전체 권한 레코드 수: " . count($subdomainPermissions));
                
                // 서브도메인 거래처 권한 확인
                $enabledCount = 0;
                foreach ($subdomainPermissions as $permission) {
                    $serviceCode = $permission['service_code'] ?? null;
                    $serviceTypeId = $permission['service_type_id'] ?? null;
                    $isMasterActive = isset($permission['service_is_active']) && $permission['service_is_active'] == 1;
                    $isCompanyEnabled = isset($permission['is_enabled']) && $permission['is_enabled'] == 1;
                    
                    log_message('debug', "menu_helper::getUserServicePermissions - service_type_id: {$serviceTypeId}, service_code: {$serviceCode}, is_master_active: " . ($isMasterActive ? '1' : '0') . ", is_enabled: " . ($isCompanyEnabled ? '1' : '0'));
                    
                    if ($serviceCode && isset($allServices[$serviceCode])) {
                        // 마스터가 활성화되어 있고 거래처 권한도 활성화되어 있어야 함
                        if ($isMasterActive && $isCompanyEnabled) {
                            $permissionMap[$serviceCode] = $allServices[$serviceCode];
                            $enabledCount++;
                        }
                    }
                }
                
                // 거래처 권한이 없으면 상위 콜센터 권한 상속
                if (empty($permissionMap) && $subdomainApiCodes && !empty($subdomainApiCodes['cc_code'])) {
                    $ccCodeForInheritance = $subdomainApiCodes['cc_code'];
                    log_message('debug', "menu_helper::getUserServicePermissions - 거래처 권한 없음, 콜센터 권한 상속 시도: cc_code={$ccCodeForInheritance}");
                    
                    $ccServicePermissionModel = new \App\Models\CcServicePermissionModel();
                    $ccPermissions = $ccServicePermissionModel->getCcServicePermissions($ccCodeForInheritance);
                    
                    log_message('debug', "menu_helper::getUserServicePermissions - 콜센터 권한 조회 결과: cc_code={$ccCodeForInheritance}, 전체 권한 레코드 수: " . count($ccPermissions));
                    
                    if (!empty($ccPermissions)) {
                        foreach ($ccPermissions as $permission) {
                            $serviceCode = $permission['service_code'] ?? null;
                            $serviceTypeId = $permission['service_type_id'] ?? null;
                            $isMasterActive = isset($permission['service_is_active']) && $permission['service_is_active'] == 1;
                            $isCcEnabled = isset($permission['is_enabled']) && $permission['is_enabled'] == 1;
                            
                            log_message('debug', "menu_helper::getUserServicePermissions - 콜센터 권한: service_type_id={$serviceTypeId}, service_code={$serviceCode}, is_master_active=" . ($isMasterActive ? '1' : '0') . ", is_enabled=" . ($isCcEnabled ? '1' : '0'));
                            
                            if ($serviceCode && isset($allServices[$serviceCode])) {
                                // 마스터가 활성화되어 있고 콜센터 권한도 활성화되어 있어야 함
                                if ($isMasterActive && $isCcEnabled) {
                                    $permissionMap[$serviceCode] = $allServices[$serviceCode];
                                    $enabledCount++;
                                    log_message('debug', "menu_helper::getUserServicePermissions - 콜센터 권한 추가됨: service_code={$serviceCode}");
                                }
                            }
                        }
                        log_message('debug', "menu_helper::getUserServicePermissions - 콜센터 권한 상속 완료: 활성화된 권한 {$enabledCount}개");
                    } else {
                        log_message('debug', "menu_helper::getUserServicePermissions - 콜센터 권한이 없음: cc_code={$ccCodeForInheritance}");
                    }
                }
                
                // 서브도메인이 있으면 서브도메인 권한(또는 상속받은 콜센터 권한) 반환
                log_message('debug', "menu_helper::getUserServicePermissions - 서브도메인 활성화된 권한: {$enabledCount}개, 최종 반환 권한: " . count($permissionMap) . "개");
                return $permissionMap;
            }
            
            // 2. 사용자 세션의 거래처 권한 확인 (서브도메인이 없을 경우)
            if ($userCompany && empty($permissionMap)) {
                $companyServicePermissionModel = new \App\Models\CompanyServicePermissionModel();
                $companyPermissions = $companyServicePermissionModel->getCompanyServicePermissions($userCompany);
                
                if (!empty($companyPermissions)) {
                    // 거래처 권한이 있으면 거래처 권한 사용
                    foreach ($companyPermissions as $permission) {
                        $serviceCode = $permission['service_code'] ?? null;
                        if ($serviceCode && isset($allServices[$serviceCode])) {
                            // 마스터가 활성화되어 있고 거래처 권한도 활성화되어 있어야 함
                            $isMasterActive = isset($permission['service_is_active']) && $permission['service_is_active'] == 1;
                            $isCompanyEnabled = isset($permission['is_enabled']) && $permission['is_enabled'] == 1;
                            
                            if ($isMasterActive && $isCompanyEnabled) {
                                $permissionMap[$serviceCode] = $allServices[$serviceCode];
                            }
                        }
                    }
                    
                    // 거래처 권한이 하나라도 있으면 거래처 권한만 반환
                    if (!empty($permissionMap)) {
                        return $permissionMap;
                    }
                }
            }
            
            // 3. 거래처 권한이 없으면 콜센터 권한 확인
            if ($ccCode && empty($permissionMap)) {
                $ccServicePermissionModel = new \App\Models\CcServicePermissionModel();
                $ccPermissions = $ccServicePermissionModel->getCcServicePermissions($ccCode);
                
                if (!empty($ccPermissions)) {
                    foreach ($ccPermissions as $permission) {
                        $serviceCode = $permission['service_code'] ?? null;
                        if ($serviceCode && isset($allServices[$serviceCode])) {
                            // 마스터가 활성화되어 있고 콜센터 권한도 활성화되어 있어야 함
                            $isMasterActive = isset($permission['service_is_active']) && $permission['service_is_active'] == 1;
                            $isCcEnabled = isset($permission['is_enabled']) && $permission['is_enabled'] == 1;
                            
                            if ($isMasterActive && $isCcEnabled) {
                                $permissionMap[$serviceCode] = $allServices[$serviceCode];
                            }
                        }
                    }
                }
            }
            
            // 4. 거래처/콜센터 권한이 모두 없으면 빈 배열 반환 (마스터 설정만으로는 접근 불가)
            return $permissionMap;
        }
        
        // STN 로그인인 경우
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
            'parcel-night' => 'assets/icons/moon.svg',
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
            // $userPermissions 배열에 직접 체크 (전달받은 권한 정보 사용)
            $allowedServices = [];
            foreach ($services as $service) {
                // $userPermissions 배열에 service_code가 있으면 권한 있음
                if (isset($userPermissions[$service['service_code']])) {
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

