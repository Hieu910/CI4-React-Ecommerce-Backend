<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->options('(:any)', function () {
    $response = response();
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    return $response
        ->setHeader('Access-Control-Allow-Origin', $origin)
        ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
        ->setHeader('Access-Control-Allow-Credentials', 'true')
        ->setStatusCode(200);
});

$routes->group('api', function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->post('register', 'AuthController::register');
    $routes->post('logout', 'AuthController::logout');
    $routes->post('refresh', 'AuthController::refresh');
    $routes->get('products', 'ProductController::index');
    $routes->get('products/(:num)', 'ProductController::detail/$1');
    $routes->get('products/tag', 'ProductController::getByTag');
    $routes->get('products/(:num)/related', 'ProductController::related/$1');
    $routes->get('categories', 'CategoryController::index');
});

$routes->group('api', ['filter' => 'auth_filter'], function ($routes) {

    $routes->group('user', [], function ($routes) {
        $routes->get('profile', 'UserController::getProfile');
        $routes->post('profile/update', 'UserController::updateProfile');
        $routes->post('change-password', 'UserController::changePassword');
        $routes->post('checkout', 'OrderController::checkout');
        $routes->get('orders', 'OrderController::index');
         $routes->get('orders/(:num)', 'OrderController::show/$1');
    });

    $routes->group('cart', function ($routes) {
        $routes->get('/', 'CartController::index');
        $routes->post('save', 'CartController::saveToCart');
        $routes->delete('delete/(:num)', 'CartController::delete/$1');
    });

    $routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'admin_filter'], function ($routes) {
        $routes->get('dashboard', '\App\Controllers\Admin\DashboardController::index');

        $routes->group('users', function ($routes) {
            $routes->get('/', 'UserController::index');
            $routes->post('update/(:num)', 'UserController::update/$1');
        });

        $routes->group('products', function ($routes) {
            $routes->post('create', 'ProductController::create');
            $routes->post('update/(:num)', 'ProductController::update/$1');
            $routes->post('delete/(:num)', 'ProductController::delete/$1');
            $routes->get('variants/(:num)', 'ProductController::getVariants/$1');
        });


        $routes->group('orders', function ($routes) {
            $routes->get('/', 'OrderController::index');
            $routes->get('(:num)', 'OrderController::show/$1');
            $routes->post('update-status/(:num)', 'OrderController::updateStatus/$1');
        });
    });
});
