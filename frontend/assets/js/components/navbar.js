// Barra de navegación compartida entre los módulos

function renderNav(active) {
  const links = [
    { id: 'reservas', href: '../reservas/reservas.html', label: 'Reservas' },
    { id: 'productos', href: '../productos/productos.html', label: 'Productos' },
    { id: 'pedidos', href: '../pedidos/pedidos.html', label: 'Pedidos' },
    { id: 'logout', href: '#', label: 'Cerrar sesión' },
  ];

  const nav = document.createElement('nav');
  nav.className = 'navbar';

  const brand = document.createElement('a');
  brand.className = 'brand';
  brand.href = '../reservas/reservas.html';
  brand.textContent = 'Restaurante XYZ';
  nav.appendChild(brand);

  const ul = document.createElement('ul');

  links.forEach((link) => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.href = link.href;
    a.textContent = link.label;

    if (link.id === 'logout') {
      a.addEventListener('click', (event) => {
        event.preventDefault();
        logout();
      });
    }

    if (link.id === active) {
      a.className = 'active';
    }

    li.appendChild(a);
    ul.appendChild(li);
  });

  nav.appendChild(ul);
  document.body.prepend(nav);
}
