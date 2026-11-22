// Gestión de productos del menú

if (requireSession()) {
  renderNav('productos');

  const productosBody = document.getElementById('productos-body');
  const productoForm = document.getElementById('producto-form');
  const filtroForm = document.getElementById('filtro-form');
  const guardarProducto = document.getElementById('guardar-producto');
  const cancelarProducto = document.getElementById('cancelar-producto');
  const tituloProducto = document.getElementById('titulo-producto');

  let productosPorId = {};
  let filtrosActuales = {};
  let editandoProductoId = null;

  function reiniciarFormulario() {
    editandoProductoId = null;
    productoForm.reset();
    productoForm.disponible.checked = true;
    tituloProducto.textContent = 'Nuevo producto';
    guardarProducto.textContent = 'Guardar producto';
    cancelarProducto.hidden = true;
  }

  async function cargarProductos() {
    try {
      const productos = await api.productos.listar(filtrosActuales);

      productosBody.innerHTML = '';
      productosPorId = {};

      if (!productos.length) {
        productosBody.innerHTML = '<tr><td colspan="5" class="empty">No hay productos registrados</td></tr>';
        return;
      }

      productos.forEach((producto) => {
        productosPorId[producto.id] = producto;
        const disponible = Boolean(producto.disponible);

        const fila = document.createElement('tr');
        fila.innerHTML =
          '<td>' + escapeHtml(producto.nombre) + '</td>' +
          '<td>' + escapeHtml(producto.categoria ? producto.categoria.nombre : 'Sin categoría') + '</td>' +
          '<td>' + escapeHtml(formatMoney(producto.precio)) + '</td>' +
          '<td><span class="badge ' + (disponible ? 'badge-disponible' : 'badge-cancelado') + '">' +
            (disponible ? 'Sí' : 'No') + '</span></td>' +
          '<td><div class="actions">' +
            '<button type="button" class="btn btn-small btn-secondary" data-accion="editar" ' +
              'data-id="' + producto.id + '">Editar</button>' +
            '<button type="button" class="btn btn-small btn-secondary" data-accion="disponible" ' +
              'data-id="' + producto.id + '" data-valor="' + (disponible ? '0' : '1') + '">' +
              (disponible ? 'Marcar agotado' : 'Marcar disponible') + '</button>' +
            '<button type="button" class="btn btn-small btn-danger" data-accion="eliminar" ' +
              'data-id="' + producto.id + '">Eliminar</button>' +
          '</div></td>';
        productosBody.appendChild(fila);
      });
    } catch (error) {
      showMessage(error.message || 'No se pudieron cargar los productos', 'error');
    }
  }

  productosBody.addEventListener('click', async (event) => {
    const boton = event.target.closest('button[data-accion]');

    if (!boton) {
      return;
    }

    if (boton.dataset.accion === 'editar') {
      const producto = productosPorId[boton.dataset.id];

      if (!producto) {
        return;
      }

      editandoProductoId = producto.id;
      productoForm.nombre.value = producto.nombre;
      productoForm.categoria.value = producto.categoria ? producto.categoria.nombre : '';
      productoForm.precio.value = producto.precio;
      productoForm.disponible.checked = Boolean(producto.disponible);
      tituloProducto.textContent = 'Editar producto';
      guardarProducto.textContent = 'Actualizar producto';
      cancelarProducto.hidden = false;
      productoForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    boton.disabled = true;

    try {
      if (boton.dataset.accion === 'eliminar') {
        if (!confirm('¿Eliminar este producto?')) {
          boton.disabled = false;
          return;
        }

        await api.productos.eliminar(boton.dataset.id);
        showMessage('Producto eliminado', 'success');
      } else {
        await api.productos.actualizar(boton.dataset.id, {
          disponible: boton.dataset.valor === '1',
        });
        showMessage('Producto actualizado', 'success');
      }

      await cargarProductos();
    } catch (error) {
      showMessage(error.message || 'No se pudo actualizar el producto', 'error');
      boton.disabled = false;
    }
  });

  cancelarProducto.addEventListener('click', reiniciarFormulario);

  productoForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!validateRequired(productoForm)) {
      return;
    }

    const precio = Number(productoForm.precio.value);

    if (!(precio > 0)) {
      showMessage('El precio debe ser mayor a cero', 'error');
      return;
    }

    const producto = {
      nombre: productoForm.nombre.value.trim(),
      categoria: productoForm.categoria.value,
      precio,
      disponible: productoForm.disponible.checked,
    };

    guardarProducto.disabled = true;

    try {
      if (editandoProductoId) {
        await api.productos.actualizar(editandoProductoId, producto);
        showMessage('Producto actualizado', 'success');
      } else {
        await api.productos.crear(producto);
        showMessage('Producto guardado', 'success');
      }

      reiniciarFormulario();
      await cargarProductos();
    } catch (error) {
      showMessage(error.message || 'No se pudo guardar el producto', 'error');
    } finally {
      guardarProducto.disabled = false;
    }
  });

  filtroForm.addEventListener('submit', (event) => {
    event.preventDefault();
    filtrosActuales = {
      categoria: filtroForm.categoria.value,
      disponible: filtroForm.disponible.value,
      buscar: filtroForm.buscar.value.trim(),
    };
    cargarProductos();
  });

  document.getElementById('limpiar-filtro').addEventListener('click', () => {
    filtroForm.reset();
    filtrosActuales = {};
    cargarProductos();
  });

  cargarProductos();
}
