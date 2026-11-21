<?php

namespace App\Controllers;

use App\Models\Categoria;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoriaController
{
    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write(
            json_encode(Categoria::orderBy('nombre')->get()->toArray(), JSON_UNESCAPED_UNICODE)
        );

        return $response->withHeader('Content-Type', 'application/json');
    }
}
