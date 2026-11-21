<?php

use App\Controllers\HomeController;
use App\Controllers\PedidoController;
use App\Middleware\TokenMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/health', [HomeController::class, 'health']);

    $app->group('/pedidos', function (RouteCollectorProxy $group) {
        $group->get('', [PedidoController::class, 'index']);
        $group->get('/{id}', [PedidoController::class, 'show']);
        $group->post('', [PedidoController::class, 'store']);
        $group->patch('/{id}/estado', [PedidoController::class, 'updateEstado']);
    })->add(new TokenMiddleware());
};
