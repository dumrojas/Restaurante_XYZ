// Cliente para consumir la API REST de los microservicios

const API_BASE = {
  auth: 'http://127.0.0.1:8000',
  reservas: 'http://127.0.0.1:8001',
  productos: 'http://127.0.0.1:8002',
  pedidos: 'http://127.0.0.1:8003',
};

// Manejo del token de sesión
const TokenStore = {
  TOKEN_KEY: 'restaurante_token',
  USER_KEY: 'restaurante_usuario',

  get() {
    return localStorage.getItem(this.TOKEN_KEY);
  },

  set(token) {
    localStorage.setItem(this.TOKEN_KEY, token);
  },

  getUser() {
    try {
      return JSON.parse(localStorage.getItem(this.USER_KEY)) || null;
    } catch (error) {
      return null;
    }
  },

  setUser(usuario) {
    localStorage.setItem(this.USER_KEY, JSON.stringify(usuario));
  },

  clear() {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
  },
};

function esPaginaLogin() {
  return window.location.pathname.endsWith('login.html');
}

function rutaLogin() {
  return window.location.pathname.includes('/pages/') ? '../auth/login.html' : 'pages/auth/login.html';
}

function queryString(filtros = {}) {
  const parametros = new URLSearchParams();

  Object.entries(filtros).forEach(([clave, valor]) => {
    if (valor !== '' && valor !== null && valor !== undefined) {
      parametros.append(clave, valor);
    }
  });

  const query = parametros.toString();
  return query ? '?' + query : '';
}

async function request(service, path, options = {}) {
  const headers = { Accept: 'application/json', ...(options.headers || {}) };

  if (options.body) {
    headers['Content-Type'] = 'application/json';
  }

  const token = TokenStore.get();

  if (token) {
    headers.Authorization = 'Bearer ' + token;
  }

  let response;

  try {
    response = await fetch(API_BASE[service] + path, { ...options, headers });
  } catch (error) {
    throw new Error('No se pudo conectar con el servidor. Verifica que los microservicios estén activos.');
  }

  const text = await response.text();
  let data = null;

  if (text) {
    try {
      data = JSON.parse(text);
    } catch (error) {
      data = null;
    }
  }

  // Solo se cierra la sesión si el token enviado dejó de ser válido
  if (response.status === 401 && token && !esPaginaLogin()) {
    TokenStore.clear();
    window.location.href = rutaLogin();
    throw new Error('La sesión expiró. Vuelve a iniciar sesión.');
  }

  if (!response.ok) {
    const message = (data && (data.error || data.mensaje)) || 'Error ' + response.status;
    throw new Error(message);
  }

  return data;
}

function conCuerpo(metodo, datos) {
  return { method: metodo, body: JSON.stringify(datos) };
}

const api = {
  auth: {
    login: (credenciales) => request('auth', '/login', conCuerpo('POST', credenciales)),
    logout: () => request('auth', '/logout', { method: 'POST' }),
    sesion: () => request('auth', '/sesion'),
  },

  reservas: {
    mesas: () => request('reservas', '/mesas'),
    crearMesa: (mesa) => request('reservas', '/mesas', conCuerpo('POST', mesa)),
    actualizarMesa: (id, mesa) => request('reservas', '/mesas/' + id, conCuerpo('PUT', mesa)),
    cambiarEstadoMesa: (id, estado) =>
      request('reservas', '/mesas/' + id + '/estado', conCuerpo('PATCH', { estado })),

    reservas: (filtros) => request('reservas', '/reservas' + queryString(filtros)),
    crearReserva: (reserva) => request('reservas', '/reservas', conCuerpo('POST', reserva)),
    actualizarReserva: (id, reserva) =>
      request('reservas', '/reservas/' + id, conCuerpo('PUT', reserva)),
    cancelarReserva: (id) => request('reservas', '/reservas/' + id + '/cancelar', { method: 'PATCH' }),
    cambiarEstadoReserva: (id, estado) =>
      request('reservas', '/reservas/' + id + '/estado', conCuerpo('PATCH', { estado })),
  },

  productos: {
    categorias: () => request('productos', '/categorias'),
    listar: (filtros) => request('productos', '/productos' + queryString(filtros)),
    crear: (producto) => request('productos', '/productos', conCuerpo('POST', producto)),
    actualizar: (id, producto) => request('productos', '/productos/' + id, conCuerpo('PUT', producto)),
    eliminar: (id) => request('productos', '/productos/' + id, { method: 'DELETE' }),
  },

  pedidos: {
    listar: (filtros) => request('pedidos', '/pedidos' + queryString(filtros)),
    obtener: (id) => request('pedidos', '/pedidos/' + id),
    crear: (pedido) => request('pedidos', '/pedidos', conCuerpo('POST', pedido)),
    cambiarEstado: (id, estado) =>
      request('pedidos', '/pedidos/' + id + '/estado', conCuerpo('PATCH', { estado })),
  },
};
