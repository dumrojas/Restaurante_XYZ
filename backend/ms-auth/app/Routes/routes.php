<?php

use App\Controllers\AuthController;
use App\Middleware\TokenMiddleware;
use Slim\App;

return function (App $app): void {
    $app->post('/login', [AuthController::class, 'login']);
    $app->post('/logout', [AuthController::class, 'logout']);
    $app->get('/sesion', [AuthController::class, 'sesion'])->add(new TokenMiddleware());
};
