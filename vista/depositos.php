<?php
/**
 * depositos.php
 * -------------
 * 1. Maneja peticiones AJAX (companies / companyContracts).
 * 2. Verifica la sesión (opcional).
 * 3. Muestra la página con diseño AdminLTE.
<0xC2><0xA0;*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

session_start();

// Modificación: Eliminar la lógica de beta y forzar producción.
$apiBase = 'https://api.ationet.com';

if (isset($_GET['ajax']) && $_GET['ajax'] === 'companies') {
  header('Content-Type: application/json');

  if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Token no encontrado en sesión']);
    exit;
  }
  $accessToken = $_SESSION['access_token'];

  $allCompanies = [];
  $page = 1;
  $limit = 50;
  while (true) {
    $ch = curl_init("{$apiBase}/companies?page={$page}&limit={$limit}");
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !$response) {
      echo json_encode(['error' => 'Error al obtener compañías', 'httpCode' => $httpCode, 'response' => $response]);
      exit;
    }
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['Content'])) break;
    $content = $data['Content'];
    if (empty($content)) break;
    $allCompanies = array_merge($allCompanies, $content);
    $page++;
  }

  $searchTerm = isset($_GET['term']) ? mb_strtolower($_GET['term']) : '';
  $results = [];
  foreach ($allCompanies as $company) {
    $id = $company['Id'] ?? null;
    $name = $company['Name'] ?? '';
    if ($searchTerm === '' || strpos(mb_strtolower($name), $searchTerm) !== false) {
      $results[] = ['id' => $id, 'name' => $name];
    }
  }
  echo json_encode(empty($results) ? ['error' => 'No se encontraron coincidencias.'] : $results);
  exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'companyContracts') {
  header('Content-Type: application/json');

  $companyId = $_GET['companyId'] ?? '';
  if (!$companyId) {
    echo json_encode(['error' => 'No se especificó companyId']);
    exit;
  }

  if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Token no encontrado en sesión']);
    exit;
  }
  $accessToken = $_SESSION['access_token'];

  $ch = curl_init("{$apiBase}/CompanyContracts/?companyId={$companyId}");
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"]
  ]);
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode !== 200 || !$response) {
    echo json_encode(['error' => 'Error al obtener contratos', 'httpCode' => $httpCode, 'response' => $response]);
    exit;
  }
  $data = json_decode($response, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Error al decodificar JSON de contratos']);
    exit;
  }
  $contracts = $data['Content'] ?? [];
  echo json_encode($contracts);
  exit;
}

if (!isset($_SESSION['access_token'])) {
  header("Location: ../index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Depósitos</title>
    <link rel="icon" href="../assets/ICOTG.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="../css/adminlte.css" />
    <link rel="stylesheet" href="../css/depositos.css" />
    <!-- Iconos y Scrollbars -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.4.5/styles/overlayscrollbars.min.css" />
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
        <main class="content p-4">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Búsqueda de Compañías</h3>
                            </div>
                            <div class="card-body">
                                <h1 class="mb-4">Depósitos</h1>
                                <div class="mb-3">
                                    <label for="compania" class="form-label">Compañía:</label>
                                    <input type="text" id="compania" class="form-control d-inline-block w-auto" placeholder="Escribe el nombre de la compañía...">
                                    <label for="total" class="form-label ms-3">Total:</label>
                                    <input type="text" id="total" class="form-control d-inline-block w-auto">
                                </div>
                                <div class="mb-3">
                                    <span class="me-2">TB -</span>
                                    <input type="text" id="tbFecha" class="form-control d-inline-block w-auto" value="<?php date_default_timezone_set('America/Mazatlan'); echo date('dmY'); ?>">
                                    <input type="text" id="tbExtra" placeholder="10 caracteres" maxlength="10" class="form-control d-inline-block w-auto ms-3">
                                </div>
                                <h3 id="saldo">Saldo: 0.00</h3>
                                <div id="companyCount" class="mt-3 fw-bold"></div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>ID Contrato</th>
                                                <th>Nombre Contrato</th>
                                                <th>Monto</th>
                                                <th>Comisión</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyContratos">
                                            <!-- Contratos vía AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                                <div id="mensajeAdvertencia" class="text-danger fw-bold mt-3"></div>
                                <button id="btnEnviar" class="btn btn-primary mt-3">Enviar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <footer class="app-footer"></footer>
</div>
<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.4.5/browser/overlayscrollbars.browser.es6.min.js"></script>
<script src="../js/adminlte.js"></script>
<script src="../js/depositos.js"></script>
</body>
</html>
