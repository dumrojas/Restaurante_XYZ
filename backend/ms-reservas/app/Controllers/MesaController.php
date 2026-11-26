<?php

namespace App\Controllers;

use App\Models\Mesa;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MesaController extends Controller
{
    private const ESTADOS = ['disponible', 'reservada', 'ocupada', 'fuera_servicio'];

    public function index(Request $request, Response $response): Response
    {
        return $this->json($response, Mesa::orderBy('numero')->get()->toArray());
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $numero = trim((string) ($data['numero'] ?? ''));
        $capacidad = (int) ($data['capacidad'] ?? 0);
        $estado = (string) ($data['estado'] ?? 'disponible');

        if ($numero === '') {
            return $this->json($response, ['error' => 'El número o nombre de la mesa es obligatorio'], 422);
        }

        if ($capacidad <= 0) {
            return $this->json($response, ['error' => 'La capacidad debe ser mayor a cero'], 422);
        }

        if (!in_array($estado, self::ESTADOS, true)) {
            return $this->json($response, ['error' => 'Estado de mesa no válido'], 422);
        }

        if (Mesa::where('numero', $numero)->exists()) {
            return $this->json($response, ['error' => 'Ya existe una mesa con ese número'], 409);
        }

        $mesa = Mesa::create([
            'numero' => $numero,
            'capacidad' => $capacidad,
            'estado' => $estado,
        ]);

        return $this->json($response, $mesa->toArray(), 201);
    }

    // Según el enunciado, de una mesa solo se editan la capacidad y el estado
    public function update(Request $request, Response $response, array $args): Response
    {
        $mesa = Mesa::find((int) $args['id']);

        if (!$mesa) {
            return $this->json($response, ['error' => 'Mesa no encontrada'], 404);
        }

        $data = (array) $request->getParsedBody();

        if (isset($data['capacidad'])) {
            $capacidad = (int) $data['capacidad'];

            if ($capacidad <= 0) {
                return $this->json($response, ['error' => 'La capacidad debe ser mayor a cero'], 422);
            }

            $mesa->capacidad = $capacidad;
        }

        if (isset($data['estado'])) {
            if (!in_array($data['estado'], self::ESTADOS, true)) {
                return $this->json($response, ['error' => 'Estado de mesa no válido'], 422);
            }

            $mesa->estado = $data['estado'];
        }

        $mesa->save();

        return $this->json($response, $mesa->toArray());
    }
}
