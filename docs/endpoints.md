# Documentación de endpoints

Todos los servicios responden JSON. Las rutas de negocio requieren el encabezado
`Authorization: Bearer <token>`; el token se valida contra la tabla
`restaurante_auth.usuarios` (`token`, `sesion_activa`, `estado`).

| Servicio | Base URL | Base de datos |
| --- | --- | --- |
| ms-auth | http://127.0.0.1:8000 | restaurante_auth |
| ms-reservas | http://127.0.0.1:8001 | restaurante_reservas |
| ms-productos | http://127.0.0.1:8002 | restaurante_productos |
| ms-pedidos | http://127.0.0.1:8003 | restaurante_pedidos |

## ms-auth

| Método | Ruta | Descripción | Protegida |
| --- | --- | --- | --- |
| POST | `/login` | Inicia sesión con `usuario` o `correo` y `contrasena`; devuelve `token` y `usuario` | No |
| POST | `/logout` | Invalida el token y cierra la sesión | Sí |
| GET | `/sesion` | Valida la sesión y devuelve el usuario | Sí |

`POST /login`

```json
{ "usuario": "admin", "contrasena": "admin123" }
```

```json
{
  "token": "…",
  "usuario": { "id": 1, "nombre": "Administrador General", "usuario": "admin", "rol": "administrador" }
}
```

Errores: `401` credenciales incorrectas, `403` usuario inactivo, `422` campos obligatorios.

## ms-reservas

| Método | Ruta | Descripción | Protegida |
| --- | --- | --- | --- |
| GET | `/mesas` | Lista de mesas | Sí |
| POST | `/mesas` | Crea una mesa | Sí |
| PUT | `/mesas/{id}` | Edita capacidad y estado | Sí |
| GET | `/reservas` | Lista/busca reservas (`?fecha=`, `?cliente=`, `?estado=`) | Sí |
| POST | `/reservas` | Crea una reserva | Sí |
| PUT | `/reservas/{id}` | Edita la reserva, incluido su estado | Sí |
| PATCH | `/reservas/{id}/cancelar` | Cancela una reserva | Sí |

`POST /mesas`

```json
{ "numero": "MESA-5", "capacidad": 4, "estado": "disponible" }
```

Estados de mesa: `disponible`, `reservada`, `ocupada`, `fuera_servicio`.

`POST /reservas`

```json
{
  "nombre_cliente": "Carlos Ramirez",
  "telefono_cliente": "3001234567",
  "cantidad_personas": 2,
  "fecha": "2026-12-01",
  "hora": "19:00",
  "mesa_id": 1,
  "observaciones": "Sin observaciones"
}
```

Estados de reserva: `pendiente`, `confirmada`, `cancelada`, `finalizada`.

Validaciones: no se permiten fechas pasadas, mesas fuera de servicio, exceder la
capacidad ni duplicar la reserva de una mesa en la misma fecha y hora. También se
validan mesas duplicadas y capacidad mayor a cero.

Errores: `404` no encontrado, `409` duplicado, `422` validación, `401` sin token válido.

## ms-productos

| Método | Ruta | Descripción | Protegida |
| --- | --- | --- | --- |
| GET | `/productos` | Lista/busca productos (`?categoria=`, `?disponible=`, `?buscar=`) | Sí |
| POST | `/productos` | Crea un producto | Sí |
| PUT | `/productos/{id}` | Edita nombre, categoría, precio y disponibilidad | Sí |
| DELETE | `/productos/{id}` | Elimina un producto | Sí |

`POST /productos`

```json
{
  "nombre": "Hamburguesa Especial",
  "categoria": "Platos fuertes",
  "precio": 28000,
  "disponible": true
}
```

Categorías sugeridas: Entradas, Bebidas, Platos fuertes y Postres. Deben existir en
la tabla `categorias` del esquema entregado; el servicio no las crea automáticamente.

Validaciones: nombre y categoría obligatorios, precio mayor a cero y nombre de
producto no duplicado.

Errores: `404` no encontrado, `409` duplicado, `422` validación, `401` sin token válido.

## ms-pedidos

| Método | Ruta | Descripción | Protegida |
| --- | --- | --- | --- |
| GET | `/pedidos` | Lista pedidos (`?estado=`) | Sí |
| GET | `/pedidos/{id}` | Consulta el detalle de un pedido | Sí |
| POST | `/pedidos` | Crea un pedido con sus productos | Sí |
| POST | `/pedidos/{id}/productos` | Agrega un producto a un pedido existente | Sí |
| PUT | `/pedidos/{id}/productos/{detalleId}` | Modifica la cantidad de un producto | Sí |
| DELETE | `/pedidos/{id}/productos/{detalleId}` | Quita un producto del pedido | Sí |
| PATCH | `/pedidos/{id}/estado` | Cambia el estado del pedido | Sí |

`POST /pedidos`

```json
{
  "mesa_id": 2,
  "productos": [
    { "producto_id": 1, "nombre_producto": "Hamburguesa Especial", "precio_unitario": 28000, "cantidad": 1 },
    { "producto_id": 2, "nombre_producto": "Limonada Natural", "precio_unitario": 8000, "cantidad": 2 }
  ]
}
```

La respuesta incluye `detalles`, `subtotal` y `total`. La cantidad total de productos se
calcula sumando el campo `cantidad` de los detalles y se devuelve como `cantidad_total`
calculado, sin almacenarlo en la base de datos. Al agregar, cambiar o quitar productos se
recalculan `subtotal` y `total`. El esquema SQL entregado no se modifica.

Estados: `pendiente`, `en_preparacion`, `entregado`, `pagado`, `cancelado`.

Validaciones: pedido no vacío, cantidades mayores a cero, identificadores de producto
y mesa obligatorios, estado válido, y solo se registran pedidos para mesas `reservada`
u `ocupada` (no para mesas disponibles o fuera de servicio).

Errores: `404` no encontrado, `422` validación, `401` sin token válido.
