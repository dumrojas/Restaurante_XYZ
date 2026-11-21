<?php

namespace App\Controllers;

use App\Models\Mesa;
use App\Models\Reserva;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReservaController
{
    private const ESTADOS = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Reserva::query();

        if (!empty($params['fecha'])) {
            $query->where('fecha', $params['fecha']);
        }

        if (!empty($params['cliente'])) {
            $query->where('nombre_cliente', 'like', '%' . $params['cliente'] . '%');
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $reservas = $query->orderBy('fecha')->orderBy('hora')->get()->toArray();

        return $this->json($response, $reservas);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $error = $this->validarReserva($data);

        if ($error !== null) {
            return $this->json($response, ['error' => $error], 422);
        }

        $reserva = Reserva::create([
            'nombre_cliente' => trim((string) $data['nombre_cliente']),
            'telefono_cliente' => trim((string) $data['telefono_cliente']),
            'cantidad_personas' => (int) $data['cantidad_personas'],
            'fecha' => $data['fecha'],
            'hora' => $data['hora'],
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => 'pendiente',
            'mesa_id' => (int) $data['mesa_id'],
        ]);

        return $this->json($response, $reserva->toArray(), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::find((int) $args['id']);

        if (!$reserva) {
            return $this->json($response, ['error' => 'Reserva no encontrada'], 404);
        }

        $datos = (array) $request->getParsedBody();

        if (isset($datos['estado']) && !in_array($datos['estado'], self::ESTADOS, true)) {
            return $this->json($response, ['error' => 'Estado de reserva no válido'], 422);
        }

        $soloEstado = array_keys($datos) === ['estado'];
        $data = array_merge($reserva->toArray(), $datos);

        if (!$soloEstado) {
            $error = $this->validarReserva($data, (int) $reserva->id);

            if ($error !== null) {
                return $this->json($response, ['error' => $error], 422);
            }
        }

        $reserva->fill([
            'nombre_cliente' => trim((string) $data['nombre_cliente']),
            'telefono_cliente' => trim((string) $data['telefono_cliente']),
            'cantidad_personas' => (int) $data['cantidad_personas'],
            'fecha' => $data['fecha'],
            'hora' => $data['hora'],
            'observaciones' => $data['observaciones'] ?? null,
            'mesa_id' => (int) $data['mesa_id'],
            'estado' => $data['estado'],
        ]);

        $reserva->save();

        return $this->json($response, $reserva->toArray());
    }

    public function cancelar(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::find((int) $args['id']);

        if (!$reserva) {
            return $this->json($response, ['error' => 'Reserva no encontrada'], 404);
        }

        if ($reserva->estado === 'finalizada') {
            return $this->json($response, ['error' => 'La reserva ya fue finalizada'], 422);
        }

        $reserva->estado = 'cancelada';
        $reserva->save();

        return $this->json($response, $reserva->toArray());
    }

    private function validarReserva(array $data, ?int $id = null): ?string
    {
        $requeridos = ['nombre_cliente', 'telefono_cliente', 'cantidad_personas', 'fecha', 'hora', 'mesa_id'];

        foreach ($requeridos as $campo) {
            if (!isset($data[$campo]) || trim((string) $data[$campo]) === '') {
                return 'El campo ' . $campo . ' es obligatorio';
            }
        }

        if ((int) $data['cantidad_personas'] < 1) {
            return 'La cantidad de personas debe ser mayor a cero';
        }

        if ($data['fecha'] < date('Y-m-d')) {
            return 'No se permiten reservas en fechas pasadas';
        }

        $mesa = Mesa::find((int) $data['mesa_id']);

        if (!$mesa) {
            return 'La mesa no existe';
        }

        if ($mesa->estado === 'fuera_servicio') {
            return 'La mesa está fuera de servicio';
        }

        if ((int) $data['cantidad_personas'] > (int) $mesa->capacidad) {
            return 'La cantidad de personas supera la capacidad de la mesa';
        }

        $ocupada = Reserva::where('mesa_id', $mesa->id)
            ->where('fecha', $data['fecha'])
            ->where('hora', $data['hora'])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->when($id !== null, fn ($query) => $query->where('id', '!=', $id))
            ->exists();

        if ($ocupada) {
            return 'La mesa ya tiene una reserva en esa fecha y hora';
        }

        return null;
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
