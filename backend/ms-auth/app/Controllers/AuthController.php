<?php

namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $usuario = trim((string) ($data['usuario'] ?? ''));
        $contrasena = (string) ($data['contrasena'] ?? '');

        if ($usuario === '' || $contrasena === '') {
            return $this->json($response, ['error' => 'Usuario y contraseña son obligatorios'], 422);
        }

        $user = Usuario::where('usuario', $usuario)
            ->orWhere('correo', $usuario)
            ->first();

        if (!$user || $user->contrasena !== $contrasena) {
            return $this->json($response, ['error' => 'Credenciales incorrectas'], 401);
        }

        if ($user->estado !== 'activo') {
            return $this->json($response, ['error' => 'El usuario está inactivo'], 403);
        }

        $user->token = bin2hex(random_bytes(24));
        $user->sesion_activa = true;
        $user->save();

        return $this->json($response, [
            'token' => $user->token,
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'usuario' => $user->usuario,
                'rol' => $user->rol,
            ],
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $user = $this->usuarioPorToken($request);

        if (!$user) {
            return $this->json($response, ['error' => 'No autorizado'], 401);
        }

        $user->token = null;
        $user->sesion_activa = false;
        $user->save();

        return $this->json($response, ['mensaje' => 'Sesión cerrada']);
    }

    public function sesion(Request $request, Response $response): Response
    {
        $user = $this->usuarioPorToken($request);

        if (!$user) {
            return $this->json($response, ['error' => 'Sesión no válida'], 401);
        }

        return $this->json($response, [
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'usuario' => $user->usuario,
                'rol' => $user->rol,
            ],
        ]);
    }

    private function usuarioPorToken(Request $request): ?Usuario
    {
        $token = trim(str_replace('Bearer', '', $request->getHeaderLine('Authorization')));

        if ($token === '') {
            return null;
        }

        return Usuario::where('token', $token)
            ->where('sesion_activa', true)
            ->first();
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
