<?php

use App\Controllers\PedidoController;
use App\Middleware\TokenMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/pedidos', function (RouteCollectorProxy $group) {
        $group->get('', [PedidoController::class, 'index']);
        $group->get('/{id}', [PedidoController::class, 'show']);
        $group->post('', [PedidoController::class, 'store']);
        $group->post('/{id}/productos', [PedidoController::class, 'agregarProducto']);
        $group->put('/{id}/productos/{detalleId}', [PedidoController::class, 'cambiarCantidad']);
        $group->delete('/{id}/productos/{detalleId}', [PedidoController::class, 'eliminarProducto']);
        $group->patch('/{id}/estado', [PedidoController::class, 'updateEstado']);
    })->add(new TokenMiddleware());
};
