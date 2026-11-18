<?php

use App\Controllers\HomeController;
use App\Middleware\TokenMiddleware;
use Slim\App;

return function (App $app): void {
    $app->get('/', [HomeController::class, 'index']);
    $app->get('/health', [HomeController::class, 'health']);
    $app->get('/admin', [HomeController::class, 'admin'])->add(new TokenMiddleware());
};
