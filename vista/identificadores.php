<?php

session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: /..   /index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">


<head>
    <meta charset="UTF-8">
    <title>Reportes</title>
    <link rel="icon" href="../assets/ICOTG.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">

    <!-- Botón para mostrar el sidebar -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list"></i>
        </a>
      </li>
    </ul>

    <!-- Nombre del usuario y opción de cerrar sesión -->
    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <?php echo htmlspecialchars($_SESSION['name'] ?? 'Invitado'); ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item text-danger" href="../logout.php">Cerrar sesión</a></li>
        </ul>
      </li>
    </ul>

  </div>
</nav>


    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <?php include __DIR__ . '/layout/sidebar.php'; ?>
            </nav>
        </div>
    </aside>

    <div class="app-main">
        <!-- texto aqui-->

    <h1>koresoft</h1>

    </div>

    <footer class="app-footer">
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
<script src="/../js/adminlte.js"></script>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        
    
</script>

</body>

</html>