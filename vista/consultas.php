<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Consultas</title>
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
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>

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

        <div class="container mt-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    Consultas de Identificadores
                </div>
                <div class="card-body">
                    <form id="form-filtros" class="row g-3">
                        <div class="col-md-4">
                            <label for="etiqueta" class="form-label">Etiqueta</label>
                            <input type="text" id="etiqueta" name="etiqueta" class="form-control" placeholder="Número de etiqueta">
                        </div>
                        <div class="col-md-4">
                            <label for="vehiculo" class="form-label">Vehículo (PAN o Modelo)</label>
                            <input type="text" id="vehiculo" name="vehiculo" class="form-control" placeholder="PAN o modelo">
                        </div>
                        <div class="col-md-4">
                            <label for="compania" class="form-label">Compañía</label>
                            <input type="text" id="compania" name="compania" class="form-control" placeholder="Código o nombre">
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="button" id="btn-buscar" class="btn btn-primary me-2">Buscar</button>
                            <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="cargando" class="text-center mt-3" style="display:none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>

            <div class="mt-4" id="resultados"></div>
        </div>

        <footer class="app-footer text-center py-3 text-muted">
            <small>&copy; <?php echo date('Y'); ?> TokenGas. Todos los derechos reservados.</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="../js/adminlte.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>

    <script>
        $('#btn-buscar').on('click', function () {
            const datos = $('#form-filtros').serialize();
            $('#cargando').show();
            $('#resultados').empty();
            $.get('../PHP/buscar_consultas.php', datos, function (html) {
                $('#cargando').hide();
                $('#resultados').html(html);
            });
        });

        $('#form-filtros').on('submit', function (e) {
            e.preventDefault();
            $('#btn-buscar').click();
        });
    </script>
</body>

</html>
