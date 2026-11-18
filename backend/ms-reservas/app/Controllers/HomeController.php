<?php

namespace App\Controllers;

use App\Config\Env;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    public function index(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'servicio' => Env::get('APP_NAME', 'ms'),
            'mensaje' => 'Hello world!',
        ]);
    }

    public function health(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'servicio' => Env::get('APP_NAME', 'ms'),
            'estado' => 'ok',
        ]);
    }

    public function admin(Request $request, Response $response): Response
    {
        return $this->json($response, ['mensaje' => 'Zona segura']);
    }

    private function json(Response $response, array $data): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
