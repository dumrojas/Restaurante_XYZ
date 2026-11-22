<?php

namespace App\Controllers;

use App\Models\DetallePedido;
use App\Models\Pedido;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PedidoController extends Controller
{
    private const ESTADOS = ['pendiente', 'en_preparacion', 'entregado', 'pagado', 'cancelado'];

    // Un pedido pagado o cancelado ya no admite cambios
    private const CERRADOS = ['pagado', 'cancelado'];

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Pedido::with('detalles');

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $pedidos = $query->orderBy('fecha', 'desc')->orderBy('hora', 'desc')->get();

        return $this->json($response, $pedidos->map(fn (Pedido $pedido) => $this->pedidoArray($pedido))->all());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::with('detalles')->find((int) $args['id']);

        if (!$pedido) {
            return $this->json($response, ['error' => 'Pedido no encontrado'], 404);
        }

        return $this->json($response, $this->pedidoArray($pedido));
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $mesaId = (int) ($data['mesa_id'] ?? 0);
        $productos = $data['productos'] ?? [];

        if ($mesaId <= 0) {
            return $this->json($response, ['error' => 'La mesa es obligatoria'], 422);
        }

        $errorMesa = $this->validarMesa($mesaId);

        if ($errorMesa !== null) {
            return $this->json($response, ['error' => $errorMesa], 422);
        }

        if (!is_array($productos) || count($productos) === 0) {
            return $this->json($response, ['error' => 'El pedido no puede estar vacío'], 422);
        }

        // Si un producto llega repetido se suman sus cantidades en un solo detalle
        $detalles = [];

        foreach ($productos as $item) {
            $detalle = $this->construirDetalle((array) $item);

            if (is_string($detalle)) {
                return $this->json($response, ['error' => $detalle], 422);
            }

            $id = $detalle['producto_id'];

            if (isset($detalles[$id])) {
                $detalles[$id]['cantidad'] += $detalle['cantidad'];
                $detalles[$id]['subtotal'] += $detalle['subtotal'];
            } else {
                $detalles[$id] = $detalle;
            }
        }

        $total = array_sum(array_column($detalles, 'subtotal'));

        // El pedido y su detalle se guardan juntos o no se guarda ninguno
        $pedido = Capsule::connection()->transaction(function () use ($mesaId, $total, $detalles) {
            $pedido = Pedido::create([
                'mesa_id' => $mesaId,
                'fecha' => date('Y-m-d'),
                'hora' => date('H:i:s'),
                'subtotal' => $total,
                'total' => $total,
                'estado' => 'pendiente',
            ]);

            $pedido->detalles()->createMany(array_values($detalles));

            return $pedido;
        });

        return $this->json($response, $this->pedidoArray($pedido), 201);
    }

    public function agregarProducto(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::find((int) $args['id']);

        if (!$pedido) {
            return $this->json($response, ['error' => 'Pedido no encontrado'], 404);
        }

        if (in_array($pedido->estado, self::CERRADOS, true)) {
            return $this->json($response, ['error' => 'El pedido está cerrado y no se puede modificar'], 422);
        }

        $data = (array) $request->getParsedBody();
        $data['cantidad'] = $data['cantidad'] ?? 1;
        $detalle = $this->construirDetalle($data);

        if (is_string($detalle)) {
            return $this->json($response, ['error' => $detalle], 422);
        }

        $existente = $pedido->detalles()->where('producto_id', $detalle['producto_id'])->first();

        if ($existente) {
            $existente->cantidad += $detalle['cantidad'];
            $existente->subtotal = $existente->cantidad * (float) $existente->precio_unitario;
            $existente->save();
        } else {
            $pedido->detalles()->create($detalle);
        }

        return $this->json($response, $this->pedidoArray($this->recalcular($pedido)), 201);
    }

    public function cambiarCantidad(Request $request, Response $response, array $args): Response
    {
        $detalle = $this->detalle((int) $args['id'], (int) $args['detalleId']);

        if (!$detalle) {
            return $this->json($response, ['error' => 'Producto del pedido no encontrado'], 404);
        }

        if (in_array($detalle->pedido->estado, self::CERRADOS, true)) {
            return $this->json($response, ['error' => 'El pedido está cerrado y no se puede modificar'], 422);
        }

        $cantidad = (int) (((array) $request->getParsedBody())['cantidad'] ?? 0);

        if ($cantidad < 1) {
            return $this->json($response, ['error' => 'Las cantidades deben ser mayores a cero'], 422);
        }

        $detalle->cantidad = $cantidad;
        $detalle->subtotal = $cantidad * (float) $detalle->precio_unitario;
        $detalle->save();

        return $this->json($response, $this->pedidoArray($this->recalcular($detalle->pedido)));
    }

    public function eliminarProducto(Request $request, Response $response, array $args): Response
    {
        $detalle = $this->detalle((int) $args['id'], (int) $args['detalleId']);

        if (!$detalle) {
            return $this->json($response, ['error' => 'Producto del pedido no encontrado'], 404);
        }

        $pedido = $detalle->pedido;

        if (in_array($pedido->estado, self::CERRADOS, true)) {
            return $this->json($response, ['error' => 'El pedido está cerrado y no se puede modificar'], 422);
        }

        if ($pedido->detalles()->count() <= 1) {
            return $this->json($response, ['error' => 'El pedido no puede quedar vacío'], 422);
        }

        $detalle->delete();

        return $this->json($response, $this->pedidoArray($this->recalcular($pedido)));
    }

    public function updateEstado(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::find((int) $args['id']);

        if (!$pedido) {
            return $this->json($response, ['error' => 'Pedido no encontrado'], 404);
        }

        $estado = (string) (((array) $request->getParsedBody())['estado'] ?? '');

        if (!in_array($estado, self::ESTADOS, true)) {
            return $this->json($response, ['error' => 'Estado de pedido no válido'], 422);
        }

        if (in_array($pedido->estado, self::CERRADOS, true)) {
            return $this->json($response, ['error' => 'El pedido está cerrado y no cambia de estado'], 422);
        }

        $pedido->estado = $estado;
        $pedido->save();

        return $this->json($response, $this->pedidoArray($pedido));
    }

    /**
     * Valida un producto del pedido y arma su detalle, o devuelve el mensaje de error.
     *
     * @return array|string
     */
    private function construirDetalle(array $item)
    {
        $productoId = (int) ($item['producto_id'] ?? 0);
        $nombre = trim((string) ($item['nombre_producto'] ?? ''));
        $cantidad = (int) ($item['cantidad'] ?? 0);
        $precio = (float) ($item['precio_unitario'] ?? 0);

        if ($productoId <= 0) {
            return 'Cada producto debe tener un identificador';
        }

        if ($nombre === '') {
            return 'Cada producto debe tener un nombre';
        }

        if ($cantidad < 1) {
            return 'Las cantidades deben ser mayores a cero';
        }

        if ($precio <= 0) {
            return 'El precio de cada producto debe ser mayor a cero';
        }

        return [
            'producto_id' => $productoId,
            'nombre_producto' => $nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $cantidad * $precio,
        ];
    }

    private function detalle(int $pedidoId, int $detalleId): ?DetallePedido
    {
        return DetallePedido::where('pedido_id', $pedidoId)->where('id', $detalleId)->first();
    }

    // Subtotal y total se recalculan en la base de datos tras cada cambio en el detalle
    private function recalcular(Pedido $pedido): Pedido
    {
        $total = (float) $pedido->detalles()->sum('subtotal');

        $pedido->subtotal = $total;
        $pedido->total = $total;
        $pedido->save();

        return $pedido->load('detalles');
    }

    private function pedidoArray(Pedido $pedido): array
    {
        $pedido->loadMissing('detalles');

        $respuesta = $pedido->toArray();
        $respuesta['cantidad_total'] = (int) $pedido->detalles->sum('cantidad');

        return $respuesta;
    }

    private function validarMesa(int $mesaId): ?string
    {
        $mesa = Capsule::connection('reservas')
            ->table('mesas')
            ->where('id', $mesaId)
            ->first();

        if (!$mesa) {
            return 'La mesa no existe';
        }

        if (!in_array($mesa->estado, ['reservada', 'ocupada'], true)) {
            return 'Solo se registran pedidos para mesas reservadas u ocupadas';
        }

        return null;
    }
}
