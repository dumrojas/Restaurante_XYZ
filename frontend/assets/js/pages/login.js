// Inicio de sesión

const form = document.getElementById('login-form');

// Si ya hay una sesión válida, entra directo al sistema
async function redirigirSiHaySesion() {
  if (!TokenStore.get()) {
    return;
  }

  try {
    const respuesta = await api.auth.sesion();
    TokenStore.setUser(respuesta.usuario);
    window.location.replace('../reservas/reservas.html');
  } catch {
    TokenStore.clear();
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (!validateRequired(form)) {
    return;
  }

  const credenciales = {
    usuario: form.usuario.value.trim(),
    contrasena: form.contrasena.value,
  };

  try {
    const respuesta = await api.auth.login(credenciales);

    if (!respuesta || !respuesta.token) {
      throw new Error('El servidor no devolvió un token');
    }

    TokenStore.set(respuesta.token);
    TokenStore.setUser(respuesta.usuario);
    showMessage('Bienvenido, ' + respuesta.usuario.nombre, 'success');
    window.location.href = '../reservas/reservas.html';
  } catch (error) {
    showMessage(error.message || 'No se pudo iniciar sesión', 'error');
  }
});

redirigirSiHaySesion();
