# Frontend

Aplicación del restaurante desarrollada con HTML5, CSS3 y JavaScript Vanilla.
Consume las APIs REST de los microservicios con `fetch`.

## Estructura

```
frontend/
├── assets/
│   ├── css/          # hojas de estilo
│   ├── img/          # imágenes
│   └── js/
│       ├── api/      # llamadas a los microservicios
│       ├── components/  # componentes reutilizables
│       └── utils/    # funciones auxiliares
├── pages/
│   ├── auth/         # inicio de sesión
│   ├── reservas/     # mesas y reservas
│   ├── productos/    # menú
│   └── pedidos/      # pedidos
└── docs/             # documentación de endpoints
```

Por ahora solo están creadas las carpetas base. El código se va agregando en los siguientes commits.

## Conexión con el backend

Las pantallas consumen los microservicios en los puertos 8000 a 8003:

- `ms-auth`: login, logout y validación de sesión.
- `ms-reservas`: mesas y reservas.
- `ms-productos`: categorías y productos.
- `ms-pedidos`: pedidos con detalle y total.

Para probar el frontend hay que iniciar los cuatro servicios y servir esta carpeta:

```bash
php -S 127.0.0.1:5500 -t frontend
```

Luego abrir `http://127.0.0.1:5500` e ingresar con el usuario administrador que esté cargado en `restaurante_auth.usuarios`.

La sesión se guarda en `localStorage`; si el token deja de ser válido, la aplicación vuelve al login.
