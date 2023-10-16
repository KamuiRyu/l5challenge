<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('api/clientes', 'ClientController::get_clients');
$routes->post('api/clientes', 'ClientController::create_client');
$routes->delete('api/clientes/(:num)', 'ClientController::delete_client/$1');
$routes->put('api/clientes/(:num)', 'ClientController::update_client/$1');

$routes->get('api/produtos', 'ProductController::get_products');
$routes->post('api/produtos', 'ProductController::create_product');
$routes->delete('api/produtos/(:num)', 'ProductController::delete_product/$1');
$routes->put('api/produtos/(:num)', 'ProductController::update_product/$1');