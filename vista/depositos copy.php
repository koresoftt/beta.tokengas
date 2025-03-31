<?php
/**
 * depositos.php
 * -------------
 * 1. Maneja peticiones AJAX (companies / companyContracts).
 * 2. Verifica la sesión (opcional).
 * 3. Muestra la página con diseño Bootstrap y sidebar.
 */

// Mostrar errores en desarrollo (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---------------------------------------------------------------------
// (1) MODO AJAX: ?ajax=companies
// ---------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === 'companies') {
    header('Content-Type: application/json');
    
    // Obtener el token
    $tokenJson = file_get_contents('http://localhost/tokengas/auth/token_handler_beta.php');
    if ($tokenJson === false) {
        echo json_encode(['error' => 'No se pudo obtener el token']);
        exit;
    }
    $tokenData = json_decode($tokenJson, true);
    if (!isset($tokenData['access_token'])) {
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
    $accessToken = $tokenData['access_token'];

    // Obtener TODAS las compañías (paginación)
    $allCompanies = [];
    $page = 1;
    $limit = 50;
    while (true) {
        $url = "https://api-beta.ationet.com/companies?page={$page}&limit={$limit}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $accessToken"
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200 || !$response) {
            echo json_encode([
                'error'    => 'Error al obtener compañías',
                'httpCode' => $httpCode,
                'response' => $response
            ]);
            exit;
        }
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Error al decodificar JSON de compañías']);
            exit;
        }
        if (!isset($data['Content']) || !is_array($data['Content'])) {
            break;
        }
        $content = $data['Content'];
        if (empty($content)) {
            break;
        }
        $allCompanies = array_merge($allCompanies, $content);
        $page++;
    }
    // Filtrar por término
    $searchTerm = isset($_GET['term']) ? mb_strtolower($_GET['term']) : '';
    $results = [];
    foreach ($allCompanies as $company) {
        $id   = $company['Id']   ?? null;
        $name = $company['Name'] ?? '';
        if ($searchTerm === '' || strpos(mb_strtolower($name), $searchTerm) !== false) {
            $results[] = [
                'id'   => $id,
                'name' => $name
            ];
        }
    }
    echo json_encode(empty($results) ? ['error' => 'No se encontraron coincidencias.'] : $results);
    exit;
}

// ---------------------------------------------------------------------
// (2) MODO AJAX: ?ajax=companyContracts
// ---------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === 'companyContracts') {
    header('Content-Type: application/json');
    
    $companyId = $_GET['companyId'] ?? '';
    if (!$companyId) {
        echo json_encode(['error' => 'No se especificó companyId']);
        exit;
    }
    
    $tokenJson = file_get_contents('http://localhost/tokengas/auth/token_handler_beta.php');
    if ($tokenJson === false) {
        echo json_encode(['error' => 'No se pudo obtener el token']);
        exit;
    }
    $tokenData = json_decode($tokenJson, true);
    if (!isset($tokenData['access_token'])) {
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
    $accessToken = $tokenData['access_token'];
    
    $url = "https://api-beta.ationet.com/CompanyContracts/?companyId={$companyId}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken"
        ]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        echo json_encode([
            'error'    => 'Error al obtener contratos',
            'httpCode' => $httpCode,
            'response' => $response
        ]);
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

// ---------------------------------------------------------------------
// (3) MODO HTML: Si no es AJAX, mostramos la página completa
// ---------------------------------------------------------------------
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: /index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos - Depósitos</title>
    <link rel="icon" href="/tokengas/assets/ICOTG.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="/tokengas/css/movimientos.css">
    <link rel="stylesheet" href="/tokengas/css/sidebar.css">
    <link rel="stylesheet" href="/tokengas/css/depositos.css">
    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/layout/sidebar.php'; ?>
        <!-- Contenido principal -->
        <main class="content p-4">
            <div id="movimientos" class="container-fluid">
                <h1 class="text-light mb-4">Depósitos</h1>
                <div class="card mb-4" style="background-color: #343a40; border: none;">
                    <div class="card-header text-white" style="background-color: #444;">
                        <h2 class="h5 mb-0">Búsqueda de Compañías</h2>
                    </div>
                    <div class="card-body">
                        <!-- Búsqueda: Compañía y Total -->
                        <div class="mb-3">
                            <label for="compania" class="form-label text-light">Compañía:</label>
                            <input type="text" id="compania" class="form-control d-inline-block w-auto" placeholder="Escribe el nombre de la compañía...">
                            <label for="total" class="form-label text-light ms-3">Total:</label>
                            <input type="text" id="total" class="form-control d-inline-block w-auto" >
                        </div>
                        <!-- Fecha y campo extra -->
                        <div class="mb-3">
                            <span class="text-light me-2">TB -</span>
                            <!-- Se genera la fecha actual en formato "ddmmyyyy" -->
                            <input type="text" id="tbFecha" class="form-control d-inline-block w-auto" value="<?php date_default_timezone_set('America/Mazatlan');echo date('dmY'); ?>">
                            <input type="text" id="tbExtra" placeholder="10 caracteres" maxlength="10" class="form-control d-inline-block w-auto ms-3">
                        </div>
                        <!-- Saldo -->
                        <h3 id="saldo" class="text-light">Saldo: 0.00</h3>
                        <!-- Conteo de compañías encontradas -->
                        <div id="companyCount" class="mt-3 fw-bold text-light"></div>
                        <!-- Tabla de contratos -->
                        <div id="listaContratos" class="table-responsive mt-3">
                          <table class="table table-dark table-hover align-middle mb-0">
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
                                <!-- Se cargarán los contratos vía AJAX -->
                            </tbody>
                          </table>
                        </div>
                        <!-- Advertencia de saldo negativo -->
                        <div id="mensajeAdvertencia" class="text-danger fw-bold"></div>
                        <!-- Botón Enviar -->
                        <button id="btnEnviar" class="btn btn-primary mt-3">Enviar</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="/tokengas/js/depositos.js"></script>
    <script defer src="/tokengas/js/sidebar.js"></script>
</body>
</html>
