// Barra de navegación compartida entre los módulos

function renderNav(active) {
  const links = [
    { id: 'reservas', href: '../reservas/reservas.html', label: 'Reservas' },
    { id: 'productos', href: '../productos/productos.html', label: 'Productos' },
    { id: 'pedidos', href: '../pedidos/pedidos.html', label: 'Pedidos' },
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

    if (link.id === active) {
      a.className = 'active';
    }

    li.appendChild(a);
    ul.appendChild(li);
  });

  const usuario = TokenStore.getUser();

  if (usuario && usuario.nombre) {
    const li = document.createElement('li');
    li.className = 'nav-user';
    li.textContent = usuario.nombre;
    ul.appendChild(li);
  }

  const logoutItem = document.createElement('li');
  logoutItem.className = 'nav-logout';
  const logoutLink = document.createElement('a');
  logoutLink.href = '#';
  logoutLink.textContent = 'Salir';
  logoutLink.title = 'Cerrar sesión';
  logoutLink.addEventListener('click', (event) => {
    event.preventDefault();
    logout();
  });
  logoutItem.appendChild(logoutLink);
  ul.appendChild(logoutItem);

  nav.appendChild(ul);
  document.body.prepend(nav);
}
