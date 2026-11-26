<?php

namespace App\Middleware;

use App\Config\Env;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Las peticiones preflight se responden sin pasar por las rutas
        $response = $request->getMethod() === 'OPTIONS'
            ? new Response()
            : $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', Env::get('CORS_ORIGIN', '*'))
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    }
}
