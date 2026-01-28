<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// 메인 페이지
$routes->get('/', 'Home::index');

// 메일룸 기사 앱 (PWA)
$routes->group('mailroom-driver', function($routes) {
    $routes->get('/', 'MailroomDriver::index');
    $routes->get('detail/(:num)', 'MailroomDriver::detail/$1');
});

// 인증 관련 라우트
$routes->group('auth', function($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('processLogin', 'Auth::processLogin');
    $routes->get('logout', 'Auth::logout');
    $routes->post('checkRecaptcha', 'Auth::checkRecaptcha');
});

// 고객검색 관련 라우트
$routes->group('search-company', function($routes) {
    $routes->get('/', 'SearchCompany::index');
    $routes->get('register', 'SearchCompany::register');
    $routes->post('getCompanyInfo', 'SearchCompany::getCompanyInfo');
    $routes->post('search', 'SearchCompany::search');
    $routes->post('doRegister', 'SearchCompany::doRegister');
    // 직원검색 관련 라우트
    $routes->get('employee-search', 'SearchCompany::employeeSearch');
    $routes->get('searchEmployee', 'SearchCompany::searchEmployee');
});

// 입점신청 관련 라우트
$routes->group('store-registration', function($routes) {
    $routes->get('/', 'StoreRegistration::index');
    $routes->get('form', 'StoreRegistration::showForm');
    $routes->post('submit', 'StoreRegistration::submit');
    $routes->get('view/(:num)', 'StoreRegistration::view/$1');
    $routes->post('update-status', 'StoreRegistration::updateStatus');
});

// 대시보드 관련 라우트
$routes->group('dashboard', function($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->post('submitOrder', 'Dashboard::submitOrder');
    $routes->get('orders', 'Dashboard::orders');
    $routes->post('test-insung-api', 'Dashboard::testInsungApi');
    $routes->post('test-token-refresh', 'Dashboard::testTokenRefresh');
});

// 서비스 관련 라우트
$routes->group('service', function($routes) {
    $routes->get('mailroom', 'Service::mailroom');
    $routes->get('getRecentOrdersForDeparture', 'Service::getRecentOrdersForDeparture');
    $routes->get('quick-motorcycle', 'Service::quickMotorcycle');
    $routes->get('quick-vehicle', 'Service::quickVehicle');
    $routes->get('quick-flex', 'Service::quickFlex');
    $routes->get('quick-moving', 'Service::quickMoving');
    $routes->get('international', 'Service::international');
    $routes->get('linked-bus', 'Service::linkedBus');
    $routes->get('linked-ktx', 'Service::linkedKtx');
    $routes->get('linked-airport', 'Service::linkedAirport');
    $routes->get('linked-shipping', 'Service::linkedShipping');
    $routes->get('parcel-visit', 'Service::parcelVisit');
    $routes->get('parcel-same-day', 'Service::parcelSameDay');
    $routes->get('parcel-convenience', 'Service::parcelConvenience');
    $routes->get('parcel-night', 'Service::parcelNight');
    $routes->get('parcel-bag', 'Service::parcelBag');
    $routes->get('postal', 'Service::postal');
    $routes->get('general-document', 'Service::generalDocument');
    $routes->get('general-errand', 'Service::generalErrand');
    $routes->get('general-tax', 'Service::generalTax');
    $routes->get('life-buy', 'Service::lifeBuy');
    $routes->get('life-taxi', 'Service::lifeTaxi');
    $routes->get('life-driver', 'Service::lifeDriver');
    $routes->get('life-wreath', 'Service::lifeWreath');
    $routes->get('life-accommodation', 'Service::lifeAccommodation');
    $routes->get('life-stationery', 'Service::lifeStationery');
    
    // 서비스별 주문 접수
    $routes->post('submitServiceOrder', 'Service::submitServiceOrder');
    $routes->post('parseMultiOrderExcel', 'Service::parseMultiOrderExcel');
});

// API 테스트 라우트
$routes->group('api-test', function($routes) {
    $routes->get('/', 'ApiTest::index');
    $routes->get('ilyang', 'ApiTest::testIlyangApi');
    $routes->get('ip', 'ApiTest::testIpInfo');
    $routes->get('spec', 'ApiTest::getApiSpec');
    $routes->get('sample', 'ApiTest::generateSampleData');
});

// 외부 API 제공 (인성 주문 Redis 데이터)
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->get('insung-order/list', 'InsungOrderApi::list');
    $routes->get('insung-order/detail/(:any)', 'InsungOrderApi::detail/$1');
    $routes->get('insung-order/stats', 'InsungOrderApi::stats');
    $routes->get('insung-order/refresh', 'InsungOrderApi::refresh');

    // 메일룸 기사 앱 API
    $routes->post('mailroom/login', 'MailroomApi::login');
    $routes->get('mailroom/orders', 'MailroomApi::orders');
    $routes->get('mailroom/orders/(:num)', 'MailroomApi::orderDetail/$1');
    $routes->post('mailroom/orders/(:num)/pickup', 'MailroomApi::pickup/$1');
    $routes->post('mailroom/orders/(:num)/complete', 'MailroomApi::complete/$1');
    $routes->post('mailroom/orders/(:num)/message', 'MailroomApi::sendMessage/$1');
    $routes->get('mailroom/preset-messages', 'MailroomApi::presetMessages');
    $routes->get('mailroom/stats', 'MailroomApi::stats');
});

// 메일룸 관리 (웹)
$routes->group('mailroom', function($routes) {
    $routes->get('/', 'Mailroom::index');
    $routes->get('create', 'Mailroom::create');
    $routes->post('store', 'Mailroom::store');
    $routes->get('detail/(:num)', 'Mailroom::detail/$1');
    $routes->post('assign/(:num)', 'Mailroom::assignDriver/$1');
    $routes->post('handle-directly/(:num)', 'Mailroom::handleDirectly/$1');  // 직접 처리
    $routes->get('print/(:num)', 'Mailroom::printLabel/$1');
    $routes->get('cancel/(:num)', 'Mailroom::cancelOrder/$1');
    $routes->get('floors/(:num)', 'Mailroom::getFloors/$1');

    // 건물 관리
    $routes->get('buildings', 'Mailroom::buildings');
    $routes->post('buildings/store', 'Mailroom::storeBuilding');
    $routes->post('buildings/update/(:num)', 'Mailroom::updateBuilding/$1');
    $routes->get('buildings/delete/(:num)', 'Mailroom::deleteBuilding/$1');

    // 층 관리 (AJAX)
    $routes->post('floors/store', 'Mailroom::storeFloor');
    $routes->delete('floors/delete/(:num)', 'Mailroom::deleteFloor/$1');

    // 기사 관리
    $routes->get('drivers', 'Mailroom::drivers');
    $routes->post('drivers/store', 'Mailroom::storeDriver');
    $routes->post('drivers/update/(:num)', 'Mailroom::updateDriver/$1');
    $routes->get('drivers/delete/(:num)', 'Mailroom::deleteDriver/$1');
    $routes->post('drivers/assign-buildings/(:num)', 'Mailroom::assignBuildings/$1');
    $routes->get('drivers/approve/(:num)', 'Mailroom::approveDriver/$1');
    $routes->get('drivers/reject/(:num)', 'Mailroom::rejectDriver/$1');
    $routes->get('drivers/status/(:num)/(:alpha)', 'Mailroom::changeDriverStatus/$1/$2');

    // QR 코드 생성
    $routes->get('qr/driver-register/(:num)', 'Mailroom::driverRegisterQr/$1');

    // 기사 셀프 등록 (QR 스캔)
    $routes->get('driver-register/(:num)', 'Mailroom::driverRegisterForm/$1');
    $routes->post('driver-register/(:num)', 'Mailroom::driverRegisterSubmit/$1');
});

// 배송조회 관련 라우트
$routes->group('delivery', function($routes) {
    $routes->get('list', 'Delivery::list');
    $routes->get('getOrderDetail', 'Delivery::getOrderDetail');
    $routes->get('getIlyangOrderDetail', 'Delivery::getIlyangOrderDetail');
    $routes->post('updateStatus', 'Delivery::updateStatus');
    $routes->get('printWaybill', 'Delivery::printWaybill');
    $routes->post('syncInsungOrders', 'Delivery::syncInsungOrders');
    $routes->post('saveColumnOrder', 'Delivery::saveColumnOrder');
    $routes->get('map-view', 'Delivery::mapView');
});

// 이용내역상세조회 관련 라우트
$routes->group('history', function($routes) {
    $routes->get('list', 'History::list');
    $routes->post('saveColumnOrder', 'History::saveColumnOrder');
    $routes->get('getOrderDetail', 'History::getOrderDetail');
    $routes->get('getIlyangOrderDetail', 'History::getIlyangOrderDetail');
    $routes->get('getOrderSign', 'History::getOrderSign');
    $routes->get('getCancelledOrderDetail', 'History::getCancelledOrderDetail');
    $routes->post('resubmitOrder', 'History::resubmitOrder');
    $routes->post('softDeleteOrder', 'History::softDeleteOrder');
});

// 즐겨찾기 관련 라우트
$routes->group('bookmark', function($routes) {
    $routes->get('popup', 'Bookmark::popup');
    $routes->get('recent-popup', 'Bookmark::recentPopup');
    $routes->get('organization-popup', 'Bookmark::organizationPopup');
    $routes->post('add', 'Bookmark::add');
});

// 회원정보 관련 라우트
$routes->group('member', function($routes) {
    $routes->get('list', 'Member::list');
    $routes->get('getDepartmentList', 'Member::getDepartmentList');
    $routes->get('getSettlementDepts', 'Member::getSettlementDepts');
    $routes->post('changePassword', 'Member::changePassword');
    $routes->post('updateAddress', 'Member::updateAddress');
    $routes->post('updateUserInfo', 'Member::updateUserInfo');
});

// 고객관리 관련 라우트
$routes->group('customer', function($routes) {
    $routes->get('head', 'Customer::head');
    $routes->get('branch', 'Customer::branch');
    $routes->get('agency', 'Customer::agency');
    $routes->get('budget', 'Customer::budget');
    $routes->get('items', 'Customer::items');
    
    // AJAX 라우트 - 고객사 및 사용자 계정 관리
    $routes->get('getUsersByCustomer/(:num)', 'Customer::getUsersByCustomer/$1');
    $routes->get('getUserAccountInfo/(:num)', 'Customer::getUserAccountInfo/$1');
    $routes->get('getCustomerInfo/(:num)', 'Customer::getCustomerInfo/$1');
    $routes->post('createHeadOffice', 'Customer::createHeadOffice');
    $routes->post('updateHeadOffice/(:num)', 'Customer::updateHeadOffice/$1');
    $routes->post('createBranch', 'Customer::createBranch');
    $routes->post('updateBranch/(:num)', 'Customer::updateBranch/$1');
    $routes->post('createAgency', 'Customer::createAgency');
    $routes->post('createUserAccount', 'Customer::createUserAccount');
    $routes->post('updateUserAccount/(:num)', 'Customer::updateUserAccount/$1');
});

// 부서관리 관련 라우트
$routes->group('department', function($routes) {
    $routes->get('/', 'Department::index');
    $routes->get('create', 'Department::create');
    $routes->post('store', 'Department::store');
    $routes->get('show/(:num)', 'Department::show/$1');
    $routes->get('edit/(:num)', 'Department::edit/$1');
    $routes->post('update/(:num)', 'Department::update/$1');
    $routes->delete('delete/(:num)', 'Department::delete/$1');
    $routes->post('toggle-status/(:num)', 'Department::toggleStatus/$1');
    $routes->get('search', 'Department::search');
    $routes->get('get-by-customer', 'Department::getByCustomer');
    $routes->get('hierarchy', 'Department::getHierarchy');
    $routes->get('hierarchy-ajax', 'Department::getHierarchyAjax');
});

// 청구관리 관련 라우트
$routes->group('billing', function($routes) {
    $routes->get('/', 'Billing::index');
    $routes->get('department', 'Billing::department');
    $routes->get('department-group', 'Billing::departmentGroup');
    $routes->get('customer-group', 'Billing::customerGroup');
    $routes->get('create', 'Billing::create');
    $routes->post('store', 'Billing::store');
    $routes->get('show/(:num)', 'Billing::show/$1');
    $routes->get('edit/(:num)', 'Billing::edit/$1');
    $routes->post('update/(:num)', 'Billing::update/$1');
    $routes->delete('delete/(:num)', 'Billing::delete/$1');
    $routes->post('update-status/(:num)', 'Billing::updateStatus/$1');
    $routes->post('send/(:num)', 'Billing::send/$1');
    $routes->get('get-unbilled-orders', 'Billing::getUnbilledOrders');
    $routes->get('get-statistics', 'Billing::getStatistics');
    $routes->get('search', 'Billing::search');
    $routes->get('history', 'Billing::index'); // 청구 내역은 index와 동일
});

// 관리자설정 관련 라우트
$routes->group('admin', function($routes) {
    $routes->get('order-type', 'Admin::orderType');
    $routes->get('notification', 'Admin::notification');
    $routes->get('pricing', 'Admin::pricing');
    $routes->post('savePricing', 'Admin::savePricing');
    $routes->post('updateServicePermission', 'Admin::updateServicePermission');
    $routes->post('createServicePermission', 'Admin::createServicePermission');
    // 서비스 타입 관리
    $routes->post('createServiceType', 'Admin::createServiceType');
    $routes->post('updateServiceType', 'Admin::updateServiceType');
    $routes->post('batchUpdateServiceStatus', 'Admin::batchUpdateServiceStatus');
    $routes->post('deactivateAllServices', 'Admin::deactivateAllServices');
    $routes->post('updateServiceSortOrder', 'Admin::updateServiceSortOrder');
    // 시스템 설정 관련
    $routes->get('settings', 'Admin::settings');
    $routes->post('save-settings', 'Admin::saveSettings');
    $routes->get('login-attempts', 'Admin::loginAttempts');
    // 배송사유 관리
    $routes->post('addDeliveryReason', 'Admin::addDeliveryReason');
    $routes->post('updateDeliveryReason', 'Admin::updateDeliveryReason');
    $routes->post('deleteDeliveryReason', 'Admin::deleteDeliveryReason');
    $routes->get('getDeliveryReasons', 'Admin::getDeliveryReasons');
    // 거래처별 배송사유 설정
    $routes->get('getCompanyDeliveryReasonSetting', 'Admin::getCompanyDeliveryReasonSetting');
    $routes->post('updateCompanyDeliveryReasonSetting', 'Admin::updateCompanyDeliveryReasonSetting');
});

// 운송사 관리 관련 라우트
$routes->group('shipping-company', function($routes) {
    $routes->get('/', 'ShippingCompany::index');
    $routes->get('get-active', 'ShippingCompany::getActive');
    $routes->get('get/(:num)', 'ShippingCompany::get/$1');
    $routes->post('create', 'ShippingCompany::create');
    $routes->post('update/(:num)', 'ShippingCompany::update/$1');
    $routes->post('toggle-status/(:num)', 'ShippingCompany::toggleStatus/$1');
});

// 콜센터 관리 관련 라우트 (슈퍼관리자 전용)
$routes->group('call-center', function($routes) {
    $routes->get('building', 'CallCenter::building');
    $routes->get('getUserServicePermissions', 'CallCenter::getUserServicePermissions');
    $routes->post('updateUserServicePermissions', 'CallCenter::updateUserServicePermissions');
    $routes->post('activateAllUserServices', 'CallCenter::activateAllUserServices');
    $routes->post('deactivateAllUserServices', 'CallCenter::deactivateAllUserServices');
    // 향후 추가될 다른 메뉴들
    $routes->get('driver', 'CallCenter::driver');
    $routes->get('settlement', 'CallCenter::settlement');
    $routes->get('billing', 'CallCenter::billing');
    $routes->get('receivables', 'CallCenter::receivables');
    $routes->get('fee', 'CallCenter::fee');
    $routes->get('permission', 'CallCenter::permission');
    $routes->get('tax', 'CallCenter::tax');
    $routes->get('management-info', 'CallCenter::managementInfo');
    $routes->get('auto-dispatch', 'CallCenter::autoDispatch');
});

// 환경 테스트 라우트
$routes->get('env-test', function() {
    return 'Environment: ' . ENVIRONMENT;
});

// 디버깅 관련 라우트 (개발 환경에서만)
if (ENVIRONMENT === 'development') {
    $routes->group('debug', function($routes) {
        $routes->get('login', 'Debug::login');
        $routes->get('createUser', 'Debug::createUser');
    });
}

// 그룹사 관리 라우트 (슈퍼관리자 전용)
$routes->group('group-company', function($routes) {
    $routes->get('/', 'GroupCompany::index');
    $routes->get('getAccountInfo/(:num)', 'GroupCompany::getAccountInfo/$1');
    $routes->post('createHeadOfficeAccount', 'GroupCompany::createHeadOfficeAccount');
    $routes->post('changePassword', 'GroupCompany::changePassword');
    $routes->post('uploadLogo', 'GroupCompany::uploadLogo');
    $routes->post('deleteLogo/(:num)', 'GroupCompany::deleteLogo/$1');
    $routes->delete('deleteLogo/(:num)', 'GroupCompany::deleteLogo/$1');
    // 오더유형 설정 관련 라우트
    $routes->get('getUserServicePermissions', 'GroupCompany::getUserServicePermissions');
    $routes->post('updateUserServicePermissions', 'GroupCompany::updateUserServicePermissions');
});

// 기본 홈 라우트 (리다이렉트)
$routes->get('home', 'Dashboard::index');

// 인성 시스템 관련 라우트
$routes->group('insung', function($routes) {
    $routes->get('cc-list', 'Insung::ccList');
    $routes->get('company-list', 'Insung::companyList');
    $routes->get('user-list', 'Insung::userList');
    // 퀵사별 통계
    $routes->get('stats', 'Insung::stats');
    $routes->get('getStatsData', 'Insung::getStatsData');
    $routes->get('getCompaniesByCc', 'Insung::getCompaniesByCc');
    $routes->get('getCompaniesByCcForSelect', 'Insung::getCompaniesByCcForSelect');
    $routes->post('uploadCcLogo', 'Insung::uploadCcLogo');
    $routes->delete('deleteCcLogo/(:segment)', 'Insung::deleteCcLogo/$1');
    $routes->post('uploadCompanyLogos', 'Insung::uploadCompanyLogos');
    $routes->delete('deleteCompanyLogo/(:segment)', 'Insung::deleteCompanyLogo/$1');
    $routes->post('getInsungMemberList', 'Insung::getInsungMemberList');
    $routes->post('useInsungMember', 'Insung::useInsungMember');
    $routes->post('batchUseInsungMembers', 'Insung::batchUseInsungMembers');
    // 콜센터 관리 API
    $routes->post('addCcList', 'Insung::addCcList');
    $routes->post('updateCcList', 'Insung::updateCcList');
    $routes->get('getCcDetail', 'Insung::getCcDetail');
    $routes->get('getApiListForSelect', 'Insung::getApiListForSelect');
});

// 관리자 관련 라우트
$routes->group('admin', function($routes) {
    $routes->get('order-type', 'Admin::orderType');
    $routes->get('getCompaniesByCcForOrderType', 'Admin::getCompaniesByCcForOrderType');
    $routes->get('syncCompaniesForOrderType', 'Admin::syncCompaniesForOrderType');
    $routes->get('pricing', 'Admin::pricing');
    $routes->get('notification', 'Admin::notification');
    $routes->get('order-list', 'Admin::orderList');
    $routes->post('order-list-ajax', 'Admin::orderListAjax');
    $routes->get('order-detail', 'Admin::getOrderDetail');
    $routes->get('company-list', 'Admin::companyList');
    $routes->get('company-list-cc', 'Admin::companyListForCc'); // 콜센터 관리자용 거래처관리
    $routes->get('company-edit', 'Admin::companyEdit'); // 거래처 수정 폼
    $routes->post('company-save', 'Admin::companySave'); // 거래처 저장
    $routes->get('company-customer-list', 'Admin::companyCustomerList'); // 거래처별 고객 리스트
    $routes->get('company-customer-form', 'Admin::companyCustomerForm'); // 고객 등록/수정 폼
    $routes->post('company-customer-save', 'Admin::companyCustomerSave'); // 고객 등록/수정 저장
    $routes->get('getDepartmentList', 'Admin::getDepartmentList'); // 부서 목록 조회
    $routes->get('getSettlementDeptsByUserId', 'Admin::getSettlementDeptsByUserId'); // 정산관리부서 조회
    $routes->post('createServiceType', 'Admin::createServiceType');
    $routes->post('updateServiceType', 'Admin::updateServiceType');
    $routes->post('batchUpdateServiceStatus', 'Admin::batchUpdateServiceStatus');
    $routes->post('deactivateAllServices', 'Admin::deactivateAllServices');
    $routes->post('updateServiceSortOrder', 'Admin::updateServiceSortOrder');
    $routes->post('savePricing', 'Admin::savePricing');
    $routes->post('updateServicePermission', 'Admin::updateServicePermission');
    $routes->post('createServicePermission', 'Admin::createServicePermission');
    // 인성API연계센터 관리
    $routes->get('api-list', 'Admin::apiList');
    $routes->get('getApiDetail', 'Admin::getApiDetail');
    $routes->post('refreshApiToken', 'Admin::refreshApiToken');
    $routes->post('refreshAllApiTokens', 'Admin::refreshAllApiTokens');
});

// 인성주문 관련 라우트 (거래처 코드 2338395 전용)
$routes->group('insung-order', function($routes) {
    $routes->get('list', 'InsungOrder::list');
    $routes->post('fetchOrders', 'InsungOrder::fetchOrders');
    $routes->get('getRedisStats', 'InsungOrder::getRedisStats');
    $routes->get('getCachedOrders', 'InsungOrder::getCachedOrders');
});

// 테스트 라우트
$routes->get('test/db', 'Test::db');
$routes->get('test/tables', 'Test::tables');
