<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: /tokengas/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reportes</title>
  <link rel="icon" href="/assets/ICOTG.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/tokengas/css/movimientos.css">
  <link rel="stylesheet" href="/tokengas/css/sidebar.css">
  <link rel="stylesheet" href="/tokengas/css/navbar.css">

</head>


<body>

    <div class="d-flex">
        <!-- 📌 Sidebar -->

        <?php include __DIR__ . '/layout/sidebar.php'; ?>


        <!-- 📌 Contenido principal -->
        <main class="content p-4">
            <div id="movimientos">
                <h1 class="text-light">REPORTES</h1>

          
                </div>
  <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script defer src="/tokengas/js/sidebar.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>

</body>
</html>
