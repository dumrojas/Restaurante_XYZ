<?php

namespace App\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductoController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Producto::with('categoria');

        if (!empty($params['categoria_id'])) {
            $query->where('categoria_id', (int) $params['categoria_id']);
        } elseif (!empty($params['categoria'])) {
            $query->whereHas('categoria', fn ($categoria) => $categoria->where('nombre', $params['categoria']));
        }

        if (isset($params['disponible']) && $params['disponible'] !== '') {
            $query->where('disponible', $this->booleano($params['disponible']));
        }

        if (!empty($params['buscar'])) {
            $query->where('nombre', 'like', '%' . $params['buscar'] . '%');
        }

        return $this->json($response, $query->orderBy('nombre')->get()->toArray());
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $nombre = trim((string) ($data['nombre'] ?? ''));
        $precio = (float) ($data['precio'] ?? 0);

        if ($nombre === '') {
            return $this->json($response, ['error' => 'El nombre no puede estar vacío'], 422);
        }

        if ($precio <= 0) {
            return $this->json($response, ['error' => 'El precio debe ser mayor a cero'], 422);
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
            'disponible' => array_key_exists('disponible', $data) ? $this->booleano($data['disponible']) : true,
            'categoria_id' => $categoria->id,
        ]);

        return $this->json($response, $producto->load('categoria')->toArray(), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $producto = Producto::find((int) $args['id']);

        if (!$producto) {
            return $this->json($response, ['error' => 'Producto no encontrado'], 404);
        }

        $data = (array) $request->getParsedBody();

        if (array_key_exists('nombre', $data)) {
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

        if (array_key_exists('precio', $data)) {
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
            $producto->disponible = $this->booleano($data['disponible']);
        }

        $producto->save();

        return $this->json($response, $producto->load('categoria')->toArray());
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

    // Acepta true/false, 1/0 y "true"/"false" (un (bool) "false" daría true)
    private function booleano($valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
