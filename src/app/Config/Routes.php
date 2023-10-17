<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

 $routes->group("api", function ($routes) {
    // Rotas de Clientes
    $routes->group("clientes", function ($routes) {
        $routes->get('', 'ClientController::get_clients', ['filter' => 'authFilter']);
        $routes->post('', 'ClientController::create_client', ['filter' => 'authFilter']);
        $routes->delete('(:num)', 'ClientController::delete_client/$1', ['filter' => 'authFilter']);
        $routes->put('(:num)', 'ClientController::update_client/$1', ['filter' => 'authFilter']);
    });

    // Rotas de Produtos
    $routes->group("produtos", function ($routes) {
        $routes->get('', 'ProductController::get_products', ['filter' => 'authFilter']);
        $routes->post('', 'ProductController::create_product', ['filter' => 'authFilter']);
        $routes->delete('(:num)', 'ProductController::delete_product/$1', ['filter' => 'authFilter']);
        $routes->put('(:num)', 'ProductController::update_product/$1', ['filter' => 'authFilter']);
    });

    // Rotas de Usuário
    $routes->post("register", "UserController::register");
    $routes->post("login", "UserController::login");
});

