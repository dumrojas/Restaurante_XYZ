<?php

namespace App\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductoController
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Producto::query();

        if (!empty($params['categoria_id'])) {
            $query->where('categoria_id', (int) $params['categoria_id']);
        } elseif (!empty($params['categoria'])) {
            $categoria = Categoria::where('nombre', $params['categoria'])->first();

            if ($categoria) {
                $query->where('categoria_id', $categoria->id);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if (isset($params['disponible']) && $params['disponible'] !== '') {
            $query->where('disponible', filter_var($params['disponible'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($params['buscar'])) {
            $query->where('nombre', 'like', '%' . $params['buscar'] . '%');
        }

        $productos = $query->orderBy('nombre')->get();

        return $this->json($response, $productos->toArray());
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $nombre = trim((string) ($data['nombre'] ?? ''));
        $precio = (float) ($data['precio'] ?? 0);

        if ($nombre === '' || $precio <= 0) {
            return $this->json($response, ['error' => 'Nombre y precio mayor a cero son obligatorios'], 422);
        }

        if (Producto::where('nombre', $nombre)->exists()) {
            return $this->json($response, ['error' => 'Ya existe un producto con ese nombre'], 409);
        }

        $categoria = $this->resolverCategoria($data);

        if (!$categoria) {
            return $this->json($response, ['error' => 'La categoría no existe'], 422);
        }

        $producto = Producto::create([
            'nombre' => $nombre,
            'descripcion' => $data['descripcion'] ?? null,
            'precio' => $precio,
            'disponible' => isset($data['disponible']) ? (bool) $data['disponible'] : true,
            'categoria_id' => $categoria->id,
        ]);

        return $this->json($response, $producto->toArray(), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $producto = Producto::find((int) $args['id']);

        if (!$producto) {
            return $this->json($response, ['error' => 'Producto no encontrado'], 404);
        }

        $data = (array) $request->getParsedBody();

        if (isset($data['nombre'])) {
            $nombre = trim((string) $data['nombre']);

            if ($nombre === '') {
                return $this->json($response, ['error' => 'El nombre no puede estar vacío'], 422);
            }

            $duplicado = Producto::where('nombre', $nombre)
                ->where('id', '!=', $producto->id)
                ->exists();

            if ($duplicado) {
                return $this->json($response, ['error' => 'Ya existe un producto con ese nombre'], 409);
            }

            $producto->nombre = $nombre;
        }

        if (isset($data['precio'])) {
            $precio = (float) $data['precio'];

            if ($precio <= 0) {
                return $this->json($response, ['error' => 'El precio debe ser mayor a cero'], 422);
            }

            $producto->precio = $precio;
        }

        if (isset($data['categoria_id']) || isset($data['categoria'])) {
            $categoria = $this->resolverCategoria($data);

            if (!$categoria) {
                return $this->json($response, ['error' => 'La categoría no existe'], 422);
            }

            $producto->categoria_id = $categoria->id;
        }

        if (array_key_exists('descripcion', $data)) {
            $producto->descripcion = $data['descripcion'];
        }

        if (array_key_exists('disponible', $data)) {
            $producto->disponible = (bool) $data['disponible'];
        }

        $producto->save();

        return $this->json($response, $producto->toArray());
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $producto = Producto::find((int) $args['id']);

        if (!$producto) {
            return $this->json($response, ['error' => 'Producto no encontrado'], 404);
        }

        $producto->delete();

        return $this->json($response, ['mensaje' => 'Producto eliminado']);
    }

    private function resolverCategoria(array $data): ?Categoria
    {
        if (!empty($data['categoria_id'])) {
            return Categoria::find((int) $data['categoria_id']);
        }

        if (!empty($data['categoria'])) {
            return Categoria::where('nombre', trim((string) $data['categoria']))->first();
        }

        return null;
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
