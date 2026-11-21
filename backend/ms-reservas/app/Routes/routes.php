<?php

use App\Controllers\MesaController;
use App\Controllers\ReservaController;
use App\Middleware\TokenMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/mesas', function (RouteCollectorProxy $group) {
        $group->get('', [MesaController::class, 'index']);
        $group->post('', [MesaController::class, 'store']);
        $group->put('/{id}', [MesaController::class, 'update']);
    })->add(new TokenMiddleware());

    $app->group('/reservas', function (RouteCollectorProxy $group) {
        $group->get('', [ReservaController::class, 'index']);
        $group->post('', [ReservaController::class, 'store']);
        $group->put('/{id}', [ReservaController::class, 'update']);
        $group->patch('/{id}/cancelar', [ReservaController::class, 'cancelar']);
    })->add(new TokenMiddleware());
};
