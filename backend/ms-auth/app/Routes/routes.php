<?php

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Middleware\TokenMiddleware;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/health', [HomeController::class, 'health']);

    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/logout', [AuthController::class, 'logout']);
    $app->get('/sesion', [AuthController::class, 'sesion'])->add(new TokenMiddleware());

    $app->get('/admin', [HomeController::class, 'admin'])->add(new TokenMiddleware());
};
