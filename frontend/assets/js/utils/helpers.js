// Utilidades comunes del frontend

function showMessage(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => toast.remove(), 3500);
}

function formatMoney(value) {
  return '$' + Number(value).toLocaleString('es-CO');
}

function logout() {
  TokenStore.clear();
  window.location.href = '../auth/login.html';
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
