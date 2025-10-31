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
});

// 회원정보 관련 라우트
$routes->group('member', function($routes) {
    $routes->get('list', 'Member::list');
});

// 고객관리 관련 라우트
$routes->group('customer', function($routes) {
    $routes->get('head', 'Customer::head');
    $routes->get('branch', 'Customer::branch');
    $routes->get('agency', 'Customer::agency');
    $routes->get('budget', 'Customer::budget');
    $routes->get('items', 'Customer::items');
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

// 기본 홈 라우트 (리다이렉트)
$routes->get('home', 'Dashboard::index');

// 테스트 라우트
$routes->get('test/db', 'Test::db');
$routes->get('test/tables', 'Test::tables');
