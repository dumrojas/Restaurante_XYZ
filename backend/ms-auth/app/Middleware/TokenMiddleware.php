<?php

namespace App\Middleware;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

class TokenMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authorization = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(\S+)$/', $authorization, $matches)) {
            return $this->noAutorizado();
        }

        $token = $matches[1];

        $valido = Capsule::connection('auth')
            ->table('usuarios')
            ->where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->exists();

        if (!$valido) {
            return $this->noAutorizado();
        }

        return $handler->handle($request);
    }

    private function noAutorizado(): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => 'No autorizado']));

        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
