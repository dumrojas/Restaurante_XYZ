// Cliente para consumir la API REST de los microservicios

const API_BASE = {
  auth: 'http://127.0.0.1:8000',
  reservas: 'http://127.0.0.1:8001',
  productos: 'http://127.0.0.1:8002',
  pedidos: 'http://127.0.0.1:8003',
};

// Manejo del token de sesión
const TokenStore = {
  KEY: 'restaurante_token',
  get: () => localStorage.getItem(TokenStore.KEY),
  set: (token) => localStorage.setItem(TokenStore.KEY, token),
  clear: () => localStorage.removeItem(TokenStore.KEY),
};

async function request(service, path, options = {}) {
  const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
  const token = TokenStore.get();

  if (token) {
    headers['Authorization'] = 'Bearer ' + token;
  }

  const response = await fetch(API_BASE[service] + path, { ...options, headers });
  const text = await response.text();
  const data = text ? JSON.parse(text) : null;

  if (!response.ok) {
    const message = (data && (data.error || data.mensaje)) || 'Error ' + response.status;
    throw new Error(message);
  }

  return data;
}

const api = {
  auth: {
    login: (credenciales) =>
      request('auth', '/login', { method: 'POST', body: JSON.stringify(credenciales) }),
    logout: () => request('auth', '/logout', { method: 'POST' }),
  },
  reservas: {
    mesas: () => request('reservas', '/mesas'),
    crearMesa: (mesa) => request('reservas', '/mesas', { method: 'POST', body: JSON.stringify(mesa) }),
    reservas: () => request('reservas', '/reservas'),
    crearReserva: (reserva) =>
      request('reservas', '/reservas', { method: 'POST', body: JSON.stringify(reserva) }),
  },
  productos: {
    listar: () => request('productos', '/productos'),
    crear: (producto) =>
      request('productos', '/productos', { method: 'POST', body: JSON.stringify(producto) }),
  },
  pedidos: {
    listar: () => request('pedidos', '/pedidos'),
    crear: (pedido) => request('pedidos', '/pedidos', { method: 'POST', body: JSON.stringify(pedido) }),
  },
};
