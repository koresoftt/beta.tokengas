<!-- layout/sidebar.php -->


<aside class="app-sidebar">

    <!-- Botón para colapsar el sidebar -->
    <button class="btn btn-outline-light me-3" id="sidebarToggle" type="button">
      <ion-icon name="menu-outline"></ion-icon>
    </button>

   

    <!-- Botón hamburguesa para colapsar el navbar en móviles -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTokengas" aria-controls="navbarTokengas" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

  <!-- Logo del sidebar -->
  <div class="sidebar-brand">
    <a href="/tokengas/vista/dashboard.php">
      <img src="/tokengas/assets/logo_tg2.png" alt="TokenGas Logo" class="sidebar-logo">
    </a>
  </div>

  
  <!-- Menú de Navegación -->
  <nav>
    <ul class="sidebar-menu">
      <li>
        <a href="/tokengas/vista/dashboard.php">
          <ion-icon name="home-outline"></ion-icon>
          <span>Inicio</span>
        </a>
      </li>

      <li class="submenu">
        <a href="#" class="submenu-toggle">
          <ion-icon name="business-outline"></ion-icon>
          <span>Compañías</span>
          <ion-icon name="chevron-down-outline" class="submenu-icon"></ion-icon>
        </a>
        <ul class="submenu-items">
          <li><a href="/tokengas/vista/depositos.php">Depósitos</a></li>
          <li><a href="/tokengas/vista/retiros.php">Retiros</a></li>
        </ul>
      </li>

      <li>
        <a href="/tokengas/vista/movimientos.php">
          <ion-icon name="bar-chart-outline"></ion-icon>
          <span>Movimientos</span>
        </a>
      </li>

      <li>
        <a href="/tokengas/vista/reportes.php">
          <ion-icon name="document-text-outline"></ion-icon>
          <span>Reportes</span>
        </a>
      </li>
    </ul>
  </nav>
</aside>
