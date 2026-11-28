<?php

use App\Config\Database;
use App\Config\Env;
use App\Middleware\CorsMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Bogota'));

Database::boot();

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

(require __DIR__ . '/../app/Routes/routes.php')($app);

$app->addErrorMiddleware(Env::get('APP_DEBUG', 'false') === 'true', true, true);
$app->add(new CorsMiddleware());

$app->run();
