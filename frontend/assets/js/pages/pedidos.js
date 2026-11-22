// Gestión de pedidos

if (requireSession()) {
  renderNav('pedidos');

  // Botones de cambio de estado según el estado actual del pedido
  const TRANSICIONES = {
    pendiente: [
      { estado: 'en_preparacion', texto: 'Preparar', clase: '' },
      { estado: 'cancelado', texto: 'Cancelar', clase: ' btn-danger' },
    ],
    en_preparacion: [
      { estado: 'entregado', texto: 'Entregar', clase: '' },
      { estado: 'cancelado', texto: 'Cancelar', clase: ' btn-danger' },
    ],
    entregado: [{ estado: 'pagado', texto: 'Pagar', clase: '' }],
  };

  // Pedidos que ya no admiten cambios en sus productos
  const PEDIDOS_CERRADOS = ['pagado', 'cancelado'];

  const pedidosBody = document.getElementById('pedidos-body');
  const itemForm = document.getElementById('item-form');
  const mesaSelect = document.getElementById('mesa-select');
  const productoSelect = document.getElementById('producto-select');
  const carritoBody = document.getElementById('carrito-body');
  const carritoTotal = document.getElementById('carrito-total');
  const carritoCantidad = document.getElementById('carrito-cantidad');
  const registrarPedido = document.getElementById('registrar-pedido');
  const filtroForm = document.getElementById('filtro-form');
  const filtroEstado = document.getElementById('filtro-estado');
  const gestionCard = document.getElementById('gestion-card');
  const gestionId = document.getElementById('gestion-id');
  const gestionProducto = document.getElementById('gestion-producto');
  const agregarItemForm = document.getElementById('agregar-item-form');
  const detalleBody = document.getElementById('detalle-body');
  const gestionTotal = document.getElementById('gestion-total');

  let mesasPorId = {};
  let productosPorId = {};
  let carrito = [];
  let filtrosActuales = {};
  let gestionPedidoId = null;

  function nombreMesa(id) {
    return mesasPorId[id] ? mesasPorId[id].numero : 'Mesa ' + id;
  }

  function renderCarrito() {
    carritoBody.innerHTML = '';

    if (!carrito.length) {
      carritoBody.innerHTML =
        '<tr><td colspan="5" class="empty">Agrega productos para armar el pedido</td></tr>';
      carritoTotal.textContent = 'Total: $0';
      carritoCantidad.textContent = '0 productos';
      return;
    }

    let total = 0;
    let unidades = 0;

    carrito.forEach((item, indice) => {
      const subtotal = item.precio_unitario * item.cantidad;
      total += subtotal;
      unidades += item.cantidad;

      const fila = document.createElement('tr');
      fila.innerHTML =
        '<td>' + escapeHtml(item.nombre_producto) + '</td>' +
        '<td>' + escapeHtml(formatMoney(item.precio_unitario)) + '</td>' +
        '<td><input type="number" min="1" value="' + item.cantidad + '" ' +
          'data-cantidad="' + indice + '" aria-label="Cantidad de ' +
          escapeHtml(item.nombre_producto) + '"></td>' +
        '<td>' + escapeHtml(formatMoney(subtotal)) + '</td>' +
        '<td><button type="button" class="btn btn-small btn-danger" ' +
          'data-quitar="' + indice + '">Quitar</button></td>';
      carritoBody.appendChild(fila);
    });

    carritoTotal.textContent = 'Total: ' + formatMoney(total);
    carritoCantidad.textContent = unidades + (unidades === 1 ? ' producto' : ' productos');
  }

  function productosDisponibles() {
    return api.productos.listar({ disponible: true });
  }

  function llenarSelectProductos(select, productos) {
    select.innerHTML = '<option value="">Selecciona un producto</option>';

    productos.forEach((producto) => {
      productosPorId[producto.id] = producto;

      const opcion = document.createElement('option');
      opcion.value = producto.id;
      opcion.textContent = producto.nombre + ' - ' + formatMoney(producto.precio);
      select.appendChild(opcion);
    });
  }

  async function cargarOpciones() {
    try {
      const [mesas, productos] = await Promise.all([
        api.reservas.mesas(),
        productosDisponibles(),
      ]);

      mesasPorId = {};
      mesaSelect.innerHTML = '<option value="">Selecciona una mesa</option>';

      mesas.forEach((mesa) => {
        mesasPorId[mesa.id] = mesa;

        const opcion = document.createElement('option');
        opcion.value = mesa.id;
        opcion.textContent = mesa.numero + ' (' + formatEstado(mesa.estado) + ')';

        if (mesa.estado === 'disponible' || mesa.estado === 'fuera_servicio') {
          opcion.disabled = true;
        }

        mesaSelect.appendChild(opcion);
      });

      llenarSelectProductos(productoSelect, productos);
      llenarSelectProductos(gestionProducto, productos);
    } catch (error) {
      showMessage(error.message || 'No se pudieron cargar las opciones', 'error');
    }
  }

  async function cargarPedidos() {
    try {
      const pedidos = await api.pedidos.listar(filtrosActuales);

      pedidosBody.innerHTML = '';

      if (!pedidos.length) {
        pedidosBody.innerHTML = '<tr><td colspan="6" class="empty">No hay pedidos registrados</td></tr>';
        return;
      }

      pedidos.forEach((pedido) => {
        const detalle = (pedido.detalles || [])
          .map((item) => item.cantidad + '× ' + item.nombre_producto)
          .join(', ');
        const pedidoAbierto = !PEDIDOS_CERRADOS.includes(pedido.estado);
        const botonGestionar = pedidoAbierto
          ? '<button type="button" class="btn btn-small btn-secondary" data-accion="gestionar" data-id="' +
            pedido.id + '">Gestionar</button>'
          : '';
        const botonesEstado = (TRANSICIONES[pedido.estado] || [])
          .map(
            (accion) =>
              '<button type="button" class="btn btn-small' + accion.clase + '" ' +
              'data-id="' + pedido.id + '" data-estado="' + accion.estado + '">' +
              accion.texto + '</button>'
          )
          .join('');

        const fila = document.createElement('tr');
        fila.innerHTML =
          '<td>' + escapeHtml(nombreMesa(pedido.mesa_id)) + '</td>' +
          '<td>' + escapeHtml(detalle || 'Sin detalle') + '</td>' +
          '<td>' + escapeHtml(formatMoney(pedido.total)) + '</td>' +
          '<td>' + pedido.cantidad_total + '</td>' +
          '<td><span class="badge badge-' + escapeHtml(pedido.estado) + '">' +
            escapeHtml(formatEstado(pedido.estado)) + '</span></td>' +
          '<td><div class="actions">' + botonGestionar + botonesEstado + '</div></td>';
        pedidosBody.appendChild(fila);
      });
    } catch (error) {
      showMessage(error.message || 'No se pudieron cargar los pedidos', 'error');
    }
  }

  async function cargarDetalle(id) {
    try {
      const pedido = await api.pedidos.obtener(id);

      // Un pedido pagado o cancelado ya no se puede gestionar
      if (PEDIDOS_CERRADOS.includes(pedido.estado)) {
        gestionPedidoId = null;
        gestionCard.hidden = true;
        return;
      }

      gestionPedidoId = pedido.id;
      gestionId.textContent = '#' + pedido.id + ' — ' + nombreMesa(pedido.mesa_id);
      gestionCard.hidden = false;
      detalleBody.innerHTML = '';

      (pedido.detalles || []).forEach((detalle) => {
        const fila = document.createElement('tr');
        fila.innerHTML =
          '<td>' + escapeHtml(detalle.nombre_producto) + '</td>' +
          '<td>' + escapeHtml(formatMoney(detalle.precio_unitario)) + '</td>' +
          '<td><input type="number" min="1" value="' + detalle.cantidad + '" ' +
            'data-detalle-cantidad="' + detalle.id + '" aria-label="Cantidad"></td>' +
          '<td>' + escapeHtml(formatMoney(detalle.subtotal)) + '</td>' +
          '<td><div class="actions">' +
            '<button type="button" class="btn btn-small" data-detalle-accion="actualizar" ' +
              'data-id="' + detalle.id + '">Actualizar</button>' +
            '<button type="button" class="btn btn-small btn-danger" data-detalle-accion="quitar" ' +
              'data-id="' + detalle.id + '">Quitar</button>' +
          '</div></td>';
        detalleBody.appendChild(fila);
      });

      if (!(pedido.detalles || []).length) {
        detalleBody.innerHTML = '<tr><td colspan="5" class="empty">El pedido no tiene productos</td></tr>';
      }

      gestionTotal.textContent = 'Total: ' + formatMoney(pedido.total) +
        ' · ' + pedido.cantidad_total + ' productos';
    } catch (error) {
      showMessage(error.message || 'No se pudo consultar el detalle', 'error');
    }
  }

  async function recargarGestion() {
    if (gestionPedidoId) {
      await cargarDetalle(gestionPedidoId);
    }
  }

  itemForm.addEventListener('submit', (event) => {
    event.preventDefault();

    const producto = productosPorId[productoSelect.value];
    const cantidad = Number(itemForm.cantidad.value);

    if (!producto) {
      showMessage('Selecciona un producto', 'error');
      return;
    }

    if (!Number.isInteger(cantidad) || cantidad < 1) {
      showMessage('La cantidad debe ser mayor a cero', 'error');
      return;
    }

    const existente = carrito.find((item) => item.producto_id === producto.id);

    if (existente) {
      existente.cantidad += cantidad;
    } else {
      carrito.push({
        producto_id: producto.id,
        nombre_producto: producto.nombre,
        precio_unitario: Number(producto.precio),
        cantidad,
      });
    }

    itemForm.cantidad.value = 1;
    renderCarrito();
  });

  carritoBody.addEventListener('click', (event) => {
    const boton = event.target.closest('button[data-quitar]');

    if (!boton) {
      return;
    }

    carrito.splice(Number(boton.dataset.quitar), 1);
    renderCarrito();
  });

  carritoBody.addEventListener('change', (event) => {
    const input = event.target.closest('input[data-cantidad]');

    if (!input) {
      return;
    }

    const cantidad = Number(input.value);

    if (!Number.isInteger(cantidad) || cantidad < 1) {
      input.value = carrito[Number(input.dataset.cantidad)].cantidad;
      showMessage('La cantidad debe ser mayor a cero', 'error');
      return;
    }

    carrito[Number(input.dataset.cantidad)].cantidad = cantidad;
    renderCarrito();
  });

  registrarPedido.addEventListener('click', async () => {
    const mesaId = Number(mesaSelect.value);
    const mesa = mesasPorId[mesaId];

    if (!mesaId) {
      showMessage('Selecciona una mesa', 'error');
      return;
    }

    if (!mesa || mesa.estado === 'disponible' || mesa.estado === 'fuera_servicio') {
      showMessage('No se pueden registrar pedidos para mesas disponibles', 'error');
      return;
    }

    if (!carrito.length) {
      showMessage('Agrega al menos un producto', 'error');
      return;
    }

    registrarPedido.disabled = true;

    try {
      await api.pedidos.crear({ mesa_id: mesaId, productos: carrito });
      showMessage('Pedido registrado', 'success');
      carrito = [];
      renderCarrito();
      await cargarPedidos();
    } catch (error) {
      showMessage(error.message || 'No se pudo registrar el pedido', 'error');
    } finally {
      registrarPedido.disabled = false;
    }
  });

  agregarItemForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!gestionPedidoId) {
      return;
    }

    const producto = productosPorId[gestionProducto.value];
    const cantidad = Number(agregarItemForm.cantidad.value);

    if (!producto) {
      showMessage('Selecciona un producto', 'error');
      return;
    }

    if (!Number.isInteger(cantidad) || cantidad < 1) {
      showMessage('La cantidad debe ser mayor a cero', 'error');
      return;
    }

    try {
      await api.pedidos.agregarProducto(gestionPedidoId, {
        producto_id: producto.id,
        nombre_producto: producto.nombre,
        precio_unitario: Number(producto.precio),
        cantidad,
      });
      showMessage('Producto agregado al pedido', 'success');
      await recargarGestion();
      await cargarPedidos();
    } catch (error) {
      showMessage(error.message || 'No se pudo agregar el producto', 'error');
    }
  });

  detalleBody.addEventListener('click', async (event) => {
    const boton = event.target.closest('button[data-detalle-accion]');

    if (!boton || !gestionPedidoId) {
      return;
    }

    const detalleId = boton.dataset.id;
    boton.disabled = true;

    try {
      if (boton.dataset.detalleAccion === 'actualizar') {
        const input = detalleBody.querySelector('input[data-detalle-cantidad="' + detalleId + '"]');
        const cantidad = Number(input.value);

        if (!Number.isInteger(cantidad) || cantidad < 1) {
          showMessage('La cantidad debe ser mayor a cero', 'error');
          boton.disabled = false;
          return;
        }

        await api.pedidos.cambiarCantidad(gestionPedidoId, detalleId, cantidad);
        showMessage('Cantidad actualizada', 'success');
      } else {
        await api.pedidos.eliminarProducto(gestionPedidoId, detalleId);
        showMessage('Producto quitado del pedido', 'success');
      }

      await recargarGestion();
      await cargarPedidos();
    } catch (error) {
      showMessage(error.message || 'No se pudo actualizar el pedido', 'error');
      boton.disabled = false;
    }
  });

  pedidosBody.addEventListener('click', async (event) => {
    const gestionar = event.target.closest('button[data-accion="gestionar"]');

    if (gestionar) {
      await cargarDetalle(gestionar.dataset.id);
      gestionCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }

    const boton = event.target.closest('button[data-estado]');

    if (!boton) {
      return;
    }

    boton.disabled = true;

    try {
      await api.pedidos.cambiarEstado(boton.dataset.id, boton.dataset.estado);
      showMessage('Pedido actualizado', 'success');
      await cargarPedidos();
      await recargarGestion();
    } catch (error) {
      showMessage(error.message || 'No se pudo actualizar el pedido', 'error');
      boton.disabled = false;
    }
  });

  filtroForm.addEventListener('submit', (event) => {
    event.preventDefault();
    filtrosActuales = { estado: filtroEstado.value };
    cargarPedidos();
  });

  document.getElementById('limpiar-filtro').addEventListener('click', () => {
    filtroForm.reset();
    filtrosActuales = {};
    cargarPedidos();
  });

  (async () => {
    renderCarrito();
    await cargarOpciones();
    await cargarPedidos();
  })();
}
