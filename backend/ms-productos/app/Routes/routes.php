<?php

use App\Controllers\CategoriaController;
use App\Controllers\HomeController;
use App\Controllers\ProductoController;
use App\Middleware\TokenMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/health', [HomeController::class, 'health']);

    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/categorias', [CategoriaController::class, 'index']);

        $group->get('/productos', [ProductoController::class, 'index']);
        $group->post('/productos', [ProductoController::class, 'store']);
        $group->put('/productos/{id}', [ProductoController::class, 'update']);
        $group->delete('/productos/{id}', [ProductoController::class, 'destroy']);
    })->add(new TokenMiddleware());
};
