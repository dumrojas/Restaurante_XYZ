<?php

use App\Controllers\ProductoController;
use App\Middleware\TokenMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/productos', function (RouteCollectorProxy $group) {
        $group->get('', [ProductoController::class, 'index']);
        $group->post('', [ProductoController::class, 'store']);
        $group->put('/{id}', [ProductoController::class, 'update']);
        $group->delete('/{id}', [ProductoController::class, 'destroy']);
    })->add(new TokenMiddleware());
};
