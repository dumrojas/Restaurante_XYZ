<?php

use App\Config\Database;
use App\Config\Env;
use App\Middleware\CorsMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../.env');

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

Database::boot();

$app->options('/{routes:.+}', fn($request, $response) => $response);

(require __DIR__ . '/../app/Routes/routes.php')($app);

$app->addErrorMiddleware(true, true, true);
$app->add(new CorsMiddleware());

$app->run();
