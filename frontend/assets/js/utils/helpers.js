// Utilidades comunes del frontend

function showMessage(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => toast.remove(), 3500);
}

function formatMoney(value) {
  const numero = Number(value) || 0;
  return '$' + numero.toLocaleString('es-CO', { maximumFractionDigits: 0 });
}

function formatDate(value) {
  if (!value) {
    return '';
  }

  const [anio, mes, dia] = String(value).slice(0, 10).split('-');
  return dia && mes && anio ? dia + '/' + mes + '/' + anio : value;
}

function formatTime(value) {
  return value ? String(value).slice(0, 5) : '';
}

const ESTADOS_TEXTO = {
  pendiente: 'Pendiente',
  confirmada: 'Confirmada',
  cancelada: 'Cancelada',
  finalizada: 'Finalizada',
  disponible: 'Disponible',
  reservada: 'Reservada',
  ocupada: 'Ocupada',
  fuera_servicio: 'Fuera de servicio',
  en_preparacion: 'En preparación',
  entregado: 'Entregado',
  pagado: 'Pagado',
  cancelado: 'Cancelado',
};

function formatEstado(value) {
  return ESTADOS_TEXTO[value] || String(value || '').replace(/_/g, ' ');
}

function escapeHtml(value) {
  const reemplazos = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  return String(value ?? '').replace(/[&<>"']/g, (caracter) => reemplazos[caracter]);
}

// Cierra la sesión en el servidor y limpia los datos locales
async function logout() {
  try {
    if (TokenStore.get()) {
      await api.auth.logout();
    }
  } catch (error) {
    // La sesión local se cierra aunque el servidor no responda
  }

  TokenStore.clear();
  window.location.href = '../auth/login.html';
}

// Redirige al login cuando no hay una sesión guardada
function requireSession() {
  if (!TokenStore.get()) {
    window.location.href = '../auth/login.html';
    return false;
  }

  return true;
}

// Valida que todos los campos required de un formulario tengan valor
function validateRequired(form) {
  const inputs = form.querySelectorAll('[required]');

  for (const input of inputs) {
    if (!input.value.trim()) {
      showMessage('Completa el campo: ' + (input.name || input.id), 'error');
      input.focus();
      return false;
    }
  }

  return true;
}
