<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Ruta principal - Chatbot TUPA
$routes->get('/', 'Bot::index');

// Rutas del Bot
$routes->post('/bot/consultar', 'Bot::consultar');
$routes->get('/bot/sugerencias', 'Bot::sugerencias');
$routes->get('/bot/categorias', 'Bot::categorias');

// Rutas Administrativas
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function($routes) {
    $routes->get('tupa', 'Tupa::index');
    $routes->get('tupa/create', 'Tupa::create');
    $routes->post('tupa/store', 'Tupa::store');
    $routes->get('tupa/edit/(:num)', 'Tupa::edit/$1');
    $routes->post('tupa/update/(:num)', 'Tupa::update/$1');
    $routes->post('tupa/delete/(:num)', 'Tupa::delete/$1');
});
