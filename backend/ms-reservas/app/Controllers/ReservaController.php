<?php

namespace App\Controllers;

use App\Models\Mesa;
use App\Models\Reserva;
use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReservaController extends Controller
{
    private const ESTADOS = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];

    // Estados que ocupan la mesa en su fecha y hora
    private const ACTIVAS = ['pendiente', 'confirmada'];

    private const CAMPOS = [
        'nombre_cliente', 'telefono_cliente', 'cantidad_personas', 'fecha', 'hora', 'observaciones', 'mesa_id', 'estado',
    ];

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

        return $this->json($response, $query->orderBy('fecha')->orderBy('hora')->get()->toArray());
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $error = $this->validarReserva($data);

        if ($error !== null) {
            return $this->json($response, ['error' => $error], 422);
        }

        $reserva = Reserva::create($this->datosReserva($data) + ['estado' => 'pendiente']);

        return $this->json($response, $reserva->toArray(), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::find((int) $args['id']);

        if (!$reserva) {
            return $this->json($response, ['error' => 'Reserva no encontrada'], 404);
        }

        $cambios = array_intersect_key((array) $request->getParsedBody(), array_flip(self::CAMPOS));

        if (isset($cambios['estado']) && !in_array($cambios['estado'], self::ESTADOS, true)) {
            return $this->json($response, ['error' => 'Estado de reserva no válido'], 422);
        }

        $data = array_merge($reserva->only(self::CAMPOS), $cambios);

        // Un cambio de estado simple no se valida de nuevo, salvo que reactive
        // una reserva cancelada o finalizada: ahí la mesa podría estar ocupada
        $soloEstado = array_keys($cambios) === ['estado'];
        $reactiva = !in_array($reserva->estado, self::ACTIVAS, true)
            && in_array($data['estado'], self::ACTIVAS, true);

        if (!$soloEstado || $reactiva) {
            $error = $this->validarReserva($data, (int) $reserva->id);

            if ($error !== null) {
                return $this->json($response, ['error' => $error], 422);
            }
        }

        $reserva->fill($this->datosReserva($data) + ['estado' => $data['estado']]);
        $reserva->save();

        return $this->json($response, $reserva->toArray());
    }

    public function cancelar(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::find((int) $args['id']);

        if (!$reserva) {
            return $this->json($response, ['error' => 'Reserva no encontrada'], 404);
        }

        if (!in_array($reserva->estado, self::ACTIVAS, true)) {
            return $this->json($response, ['error' => 'Solo se pueden cancelar reservas pendientes o confirmadas'], 422);
        }

        $reserva->estado = 'cancelada';
        $reserva->save();

        return $this->json($response, $reserva->toArray());
    }

    private function datosReserva(array $data): array
    {
        $observaciones = trim((string) ($data['observaciones'] ?? ''));

        return [
            'nombre_cliente' => trim((string) $data['nombre_cliente']),
            'telefono_cliente' => trim((string) $data['telefono_cliente']),
            'cantidad_personas' => (int) $data['cantidad_personas'],
            'fecha' => $data['fecha'],
            'hora' => $this->normalizarHora((string) $data['hora']),
            'observaciones' => $observaciones === '' ? null : $observaciones,
            'mesa_id' => (int) $data['mesa_id'],
        ];
    }

    private function normalizarHora(string $hora): string
    {
        return substr($hora, 0, 5) . ':00';
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

        $fecha = (string) $data['fecha'];
        $hora = substr((string) $data['hora'], 0, 5);
        $fechaHora = DateTime::createFromFormat('!Y-m-d H:i', $fecha . ' ' . $hora);

        if (!$fechaHora || $fechaHora->format('Y-m-d H:i') !== $fecha . ' ' . $hora) {
            return 'La fecha o la hora no tienen un formato válido';
        }

        if ($fechaHora < new DateTime()) {
            return 'No se permiten reservas en fechas u horas pasadas';
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
            ->where('fecha', $fecha)
            ->where('hora', $this->normalizarHora($hora))
            ->whereIn('estado', self::ACTIVAS)
            ->when($id !== null, fn ($query) => $query->where('id', '!=', $id))
            ->exists();

        if ($ocupada) {
            return 'La mesa ya tiene una reserva en esa fecha y hora';
        }

        return null;
    }
}
