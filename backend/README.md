# Microservicios

Backend del sistema de reservas y pedidos. Cada servicio usa PHP 8.0, Slim 4, Eloquent ORM 9
y su propia base de datos MySQL.

| Servicio | Base de datos | Puerto local |
| --- | --- | --- |
| ms-auth | restaurante_auth | 8000 |
| ms-reservas | restaurante_reservas | 8001 |
| ms-productos | restaurante_productos | 8002 |
| ms-pedidos | restaurante_pedidos | 8003 |

## Requisitos

- PHP 8.0.2 o superior, con la extensión `pdo_mysql`.
- Composer 2.
- MySQL o MariaDB.

Cada `composer.json` fija la plataforma en PHP 8.0.2 (`config.platform.php`). Así Composer
instala versiones compatibles con PHP 8.0 aunque el equipo tenga una versión más nueva.

## Instalación

```bash
cd backend/ms-auth
composer install
php -S 127.0.0.1:8000 -t public
```

Cada servicio se ejecuta igual, cambiando la carpeta y el puerto. Antes de arrancarlo hay
que crear un archivo `.env` en su carpeta con las variables de la sección siguiente.

## Variables de entorno

Cada servicio lee un `.env` en su propia carpeta. Estas son las variables disponibles:

| Variable | Uso |
| --- | --- |
| `APP_DEBUG` | `true` muestra el detalle de los errores |
| `APP_TIMEZONE` | Zona horaria para validar fechas (por defecto `America/Bogota`) |
| `CORS_ORIGIN` | Origen permitido para el frontend (por defecto `*`) |
| `DB_*` | Conexión a la base de datos propia del servicio |
| `DB_AUTH_DATABASE` | Base de `ms-auth`, para validar el token (no aplica en `ms-auth`) |
| `DB_RESERVAS_DATABASE` | Solo `ms-pedidos`: valida el estado de la mesa |

Como el enunciado no permite comunicación entre microservicios, la sesión se valida leyendo
la tabla `usuarios` de `restaurante_auth`.

## Bases de datos

Los scripts están en `backend/<servicio>/database/schema.sql`:

```bash
mysql -u root < backend/ms-auth/database/schema.sql
```

## Endpoints

La documentación de los endpoints está en `../docs/endpoints.md`.

## Estructura

```
app/
├── Config/       # conexión a la base de datos y variables de entorno
├── Controllers/  # controladores (Controller.php tiene la respuesta JSON común)
├── Middleware/   # CORS y validación de token
├── Models/       # modelos Eloquent
└── Routes/       # definición de rutas
public/
└── index.php
```

## Limitaciones conocidas

- Las contraseñas se comparan en texto plano, como vienen en la base de datos entregada.
