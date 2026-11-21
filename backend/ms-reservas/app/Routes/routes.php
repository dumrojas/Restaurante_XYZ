<?php

use App\Controllers\HomeController;
use App\Controllers\MesaController;
use App\Controllers\ReservaController;
use App\Middleware\TokenMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/health', [HomeController::class, 'health']);

    $app->group('/mesas', function (RouteCollectorProxy $group) {
        $group->get('', [MesaController::class, 'index']);
        $group->post('', [MesaController::class, 'store']);
        $group->put('/{id}', [MesaController::class, 'update']);
        $group->patch('/{id}/estado', [MesaController::class, 'updateEstado']);
    })->add(new TokenMiddleware());

    $app->group('/reservas', function (RouteCollectorProxy $group) {
        $group->get('', [ReservaController::class, 'index']);
        $group->post('', [ReservaController::class, 'store']);
        $group->put('/{id}', [ReservaController::class, 'update']);
        $group->patch('/{id}/cancelar', [ReservaController::class, 'cancelar']);
        $group->patch('/{id}/estado', [ReservaController::class, 'updateEstado']);
    })->add(new TokenMiddleware());
};
