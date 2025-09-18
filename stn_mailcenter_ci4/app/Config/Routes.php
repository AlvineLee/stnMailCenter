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
    $routes->get('parcel-visit', 'Service::parcelVisit');
    $routes->get('life-buy', 'Service::lifeBuy');
    
    // 서비스별 주문 접수
    $routes->post('submitServiceOrder', 'Service::submitServiceOrder');
});

// 기본 홈 라우트 (리다이렉트)
$routes->get('home', 'Dashboard::index');

// 테스트 라우트
$routes->get('test/db', 'Test::db');
$routes->get('test/tables', 'Test::tables');
