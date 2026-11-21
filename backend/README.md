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
php -S 127.0.0.1:8000 -t public
```

Cada servicio se ejecuta igual, cambiando la carpeta y el puerto.
Cada servicio necesita un archivo `.env` con: `APP_NAME`, `APP_ENV`, `APP_DEBUG`,
`DB_DRIVER`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` y
`DB_AUTH_DATABASE`. En `ms-pedidos` también `DB_RESERVAS_DATABASE`.

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
├── Controllers/  # controladores
├── Middleware/   # CORS y validación de token
├── Models/       # modelos Eloquent
└── Routes/       # definición de rutas
public/
└── index.php
```
