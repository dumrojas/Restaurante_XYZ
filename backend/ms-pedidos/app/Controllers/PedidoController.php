<?php

namespace App\Controllers;

use App\Models\Pedido;
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

        return $this->json($response, $pedidos->toArray());
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::with('detalles')->find((int) $args['id']);

        if (!$pedido) {
            return $this->json($response, ['error' => 'Pedido no encontrado'], 404);
        }

        return $this->json($response, $pedido->toArray());
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $mesaId = (int) ($data['mesa_id'] ?? 0);
        $items = $this->items($data);

        if ($mesaId <= 0) {
            return $this->json($response, ['error' => 'La mesa es obligatoria'], 422);
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

        return $this->json($response, $pedido->load('detalles')->toArray(), 201);
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

        return $this->json($response, $pedido->toArray());
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
