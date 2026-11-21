# Microservicios

Backend del sistema de reservas y pedidos. Cada servicio usa PHP 8, Slim 4, Eloquent ORM y su propia base de datos MySQL.

| Servicio | Base de datos | Puerto local |
| --- | --- | --- |
| ms-auth | restaurante_auth | 8000 |
| ms-reservas | restaurante_reservas | 8001 |
| ms-productos | restaurante_productos | 8002 |
| ms-pedidos | restaurante_pedidos | 8003 |

## Instalación

```bash
cd backend/ms-auth
composer install
cp .env.example .env
php -S 127.0.0.1:8000 -t public
```

Cada servicio se ejecuta igual, cambiando la carpeta y el puerto.

## Bases de datos

Los scripts están en `backend/<servicio>/database/schema.sql`:

```bash
mysql -u root < backend/ms-auth/database/schema.sql
```

## Endpoints

La documentación completa de todos los endpoints está en `../docs/endpoints.md`.

### Endpoints base

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/` | Mensaje de bienvenida del servicio |
| GET | `/health` | Estado del servicio |
| GET | `/admin` | Ruta protegida de ejemplo |

La ruta `/admin` requiere el encabezado `Authorization: Bearer 12345`.

## Estructura

```
app/
├── Config/       # conexión a la base de datos y variables de entorno
├── Controllers/  # controladores
├── Middleware/   # CORS y validación de token
├── Models/       # modelos Eloquent
└── Routes/       # definición de rutas
public/
└── index.php
```
