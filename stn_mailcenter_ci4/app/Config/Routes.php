<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// 메인 페이지
$routes->get('/', 'Home::index');

// 인증 관련 라우트
$routes->group('auth', function($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('processLogin', 'Auth::processLogin');
    $routes->get('logout', 'Auth::logout');
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

// 배송조회 관련 라우트
$routes->group('delivery', function($routes) {
    $routes->get('list', 'Delivery::list');
    $routes->get('getOrderDetail', 'Delivery::getOrderDetail');
    $routes->post('updateStatus', 'Delivery::updateStatus');
    $routes->get('printWaybill', 'Delivery::printWaybill');
    $routes->post('syncInsungOrders', 'Delivery::syncInsungOrders');
});

// 회원정보 관련 라우트
$routes->group('member', function($routes) {
    $routes->get('list', 'Member::list');
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
    $routes->post('updateServicePermission', 'Admin::updateServicePermission');
    $routes->post('createServicePermission', 'Admin::createServicePermission');
    // 서비스 타입 관리
    $routes->post('createServiceType', 'Admin::createServiceType');
    $routes->post('updateServiceType', 'Admin::updateServiceType');
    $routes->post('batchUpdateServiceStatus', 'Admin::batchUpdateServiceStatus');
    $routes->post('deactivateAllServices', 'Admin::deactivateAllServices');
    $routes->post('updateServiceSortOrder', 'Admin::updateServiceSortOrder');
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
    $routes->get('getCompaniesByCc', 'Insung::getCompaniesByCc');
    $routes->get('getCompaniesByCcForSelect', 'Insung::getCompaniesByCcForSelect');
    $routes->post('uploadCcLogo', 'Insung::uploadCcLogo');
    $routes->delete('deleteCcLogo/(:segment)', 'Insung::deleteCcLogo/$1');
    $routes->post('uploadCompanyLogos', 'Insung::uploadCompanyLogos');
    $routes->delete('deleteCompanyLogo/(:segment)', 'Insung::deleteCompanyLogo/$1');
});

// 테스트 라우트
$routes->get('test/db', 'Test::db');
$routes->get('test/tables', 'Test::tables');
