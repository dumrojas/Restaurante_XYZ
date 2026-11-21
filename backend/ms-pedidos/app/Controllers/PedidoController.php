<?php

namespace App\Controllers;

use App\Models\DetallePedido;
use App\Models\Pedido;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PedidoController
{
    private const ESTADOS = ['pendiente', 'en_preparacion', 'entregado', 'pagado', 'cancelado'];

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Pedido::with('detalles');

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $pedidos = $query->orderBy('fecha', 'desc')->orderBy('hora', 'desc')->get();
        $respuesta = [];

        foreach ($pedidos as $pedido) {
            $respuesta[] = $this->pedidoArray($pedido);
        }

        return $this->json($response, $respuesta);
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
        $items = $this->items($data);

        if ($mesaId <= 0) {
            return $this->json($response, ['error' => 'La mesa es obligatoria'], 422);
        }

        $errorMesa = $this->validarMesa($mesaId);

        if ($errorMesa !== null) {
            return $this->json($response, ['error' => $errorMesa], 422);
        }

        if (count($items) === 0) {
            return $this->json($response, ['error' => 'El pedido no puede estar vacío'], 422);
        }

        $total = 0;
        $detalles = [];

        foreach ($items as $item) {
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio_unitario'] ?? 0);
            $productoId = (int) ($item['producto_id'] ?? 0);

            if ($productoId <= 0) {
                return $this->json($response, ['error' => 'Cada producto debe tener un identificador'], 422);
            }

            if ($cantidad < 1) {
                return $this->json($response, ['error' => 'Las cantidades deben ser mayores a cero'], 422);
            }

            $subtotal = $cantidad * $precio;
            $total += $subtotal;

            $detalles[] = [
                'producto_id' => $productoId,
                'nombre_producto' => $item['nombre_producto'] ?? ('Producto ' . $productoId),
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
            ];
        }

        $pedido = Pedido::create([
            'mesa_id' => $mesaId,
            'fecha' => $data['fecha'] ?? date('Y-m-d'),
            'hora' => $data['hora'] ?? date('H:i:s'),
            'subtotal' => $total,
            'total' => $total,
            'estado' => 'pendiente',
        ]);

        $pedido->detalles()->createMany($detalles);

        return $this->json($response, $this->pedidoArray($pedido), 201);
    }

    public function agregarProducto(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::find((int) $args['id']);

        if (!$pedido) {
            return $this->json($response, ['error' => 'Pedido no encontrado'], 404);
        }

        $data = (array) $request->getParsedBody();
        $productoId = (int) ($data['producto_id'] ?? 0);
        $cantidad = (int) ($data['cantidad'] ?? 1);

        if ($productoId <= 0) {
            return $this->json($response, ['error' => 'El producto es obligatorio'], 422);
        }

        if ($cantidad < 1) {
            return $this->json($response, ['error' => 'Las cantidades deben ser mayores a cero'], 422);
        }

        $precio = (float) ($data['precio_unitario'] ?? 0);

        $pedido->detalles()->create([
            'producto_id' => $productoId,
            'nombre_producto' => $data['nombre_producto'] ?? ('Producto ' . $productoId),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $cantidad * $precio,
        ]);

        return $this->json($response, $this->pedidoArray($this->recalcular($pedido)), 201);
    }

    public function cambiarCantidad(Request $request, Response $response, array $args): Response
    {
        $detalle = $this->detalle((int) $args['id'], (int) $args['detalleId']);

        if (!$detalle) {
            return $this->json($response, ['error' => 'Producto del pedido no encontrado'], 404);
        }

        $data = (array) $request->getParsedBody();
        $cantidad = (int) ($data['cantidad'] ?? 0);

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
        $detalle->delete();

        return $this->json($response, $this->pedidoArray($this->recalcular($pedido)));
    }

    public function updateEstado(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::find((int) $args['id']);

        if (!$pedido) {
            return $this->json($response, ['error' => 'Pedido no encontrado'], 404);
        }

        $data = (array) $request->getParsedBody();
        $estado = (string) ($data['estado'] ?? '');

        if (!in_array($estado, self::ESTADOS, true)) {
            return $this->json($response, ['error' => 'Estado de pedido no válido'], 422);
        }

        $pedido->estado = $estado;
        $pedido->save();

        return $this->json($response, $this->pedidoArray($pedido));
    }

    private function detalle(int $pedidoId, int $detalleId): ?DetallePedido
    {
        return DetallePedido::where('pedido_id', $pedidoId)->where('id', $detalleId)->first();
    }

    private function recalcular(Pedido $pedido): Pedido
    {
        $total = 0;

        foreach ($pedido->detalles as $detalle) {
            $total += (float) $detalle->subtotal;
        }

        $pedido->subtotal = $total;
        $pedido->total = $total;
        $pedido->save();

        return $pedido->load('detalles');
    }

    private function pedidoArray(Pedido $pedido): array
    {
        $pedido->load('detalles');
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
            return 'No se pueden registrar pedidos para mesas disponibles';
        }

        return null;
    }

    private function items(array $data): array
    {
        if (!empty($data['productos']) && is_array($data['productos'])) {
            return $data['productos'];
        }

        if (!empty($data['producto_id'])) {
            return [[
                'producto_id' => $data['producto_id'],
                'nombre_producto' => $data['nombre_producto'] ?? null,
                'precio_unitario' => $data['precio_unitario'] ?? 0,
                'cantidad' => $data['cantidad'] ?? 1,
            ]];
        }

        return [];
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
