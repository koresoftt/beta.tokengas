<?php
// identificadores.php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

session_start();
$apiBase = 'https://api-beta.ationet.com';

// 1) Autocomplete de compañías
if (isset($_GET['ajax']) && $_GET['ajax'] === 'companies') {
    header('Content-Type: application/json; charset=UTF-8');

    if (!isset($_SESSION['access_token'])) {
        echo json_encode(['error' => 'Token no encontrado en sesión']);
        exit;
    }
    $token = $_SESSION['access_token'];
    $term  = trim($_GET['term'] ?? '');
    if ($term === '') {
        echo json_encode([]);  // vació para no mostrar nada
        exit;
    }

    // Llamada directa al endpoint correcto
    $url = "{$apiBase}/Companies?search=" . urlencode($term);
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Accept: application/json"
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo json_encode(['error' => "HTTP {$code} al buscar compañías"]);
        exit;
    }

    $json = json_decode($resp, true);
    $list = $json['Content'] ?? [];

    // Mapeo a {id,name}
    $out = array_map(fn($c) => [
        'id'   => $c['CompanyCode'],
        'name' => $c['CompanyName'],
    ], $list);

    echo json_encode($out);
    exit;
}

// 2) Contratos por compañía
if (isset($_GET['ajax']) && $_GET['ajax'] === 'companyContracts') {
    header('Content-Type: application/json; charset=UTF-8');

    if (!isset($_SESSION['access_token'])) {
        echo json_encode(['error' => 'Token no encontrado en sesión']);
        exit;
    }
    $companyId = trim($_GET['companyId'] ?? '');
    if ($companyId === '') {
        echo json_encode(['error' => 'Falta companyId']);
        exit;
    }
    $token = $_SESSION['access_token'];

    $url = "{$apiBase}/CompanyContracts?companyCode=" . urlencode($companyId);
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Accept: application/json"
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo json_encode(['error' => "HTTP {$code} al obtener contratos"]);
        exit;
    }

    $data = json_decode($resp, true);
    echo json_encode($data['Content'] ?? []);
    exit;
}

// 3) Validación de etiqueta o TRACK
if (isset($_GET['ajax']) && $_GET['ajax'] === 'checkIdentificador') {
    header('Content-Type: application/json; charset=UTF-8');

    if (!isset($_SESSION['access_token'])) {
        echo json_encode(['error' => 'Token no encontrado en sesión']);
        exit;
    }
    $token = $_SESSION['access_token'];
    $label = trim($_GET['label'] ?? '');
    $track = trim($_GET['track'] ?? '');
    if ($label === '' && $track === '') {
        echo json_encode(['error' => 'Parámetro requerido']);
        exit;
    }

    $page = 1; $found = false;
    do {
        $q = ['page' => $page, 'pageSize' => 100];
        if ($label) $q['label']       = $label;
        if ($track) $q['trackNumber'] = $track;

        $url = "{$apiBase}/Identifications?" . http_build_query($q);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                "Accept: application/json"
            ],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !$resp) break;

        $data = json_decode($resp, true);
        if (empty($data['Content'])) break;
        foreach ($data['Content'] as $item) {
            if (($label && $item['Label'] === $label) ||
                ($track && $item['TrackNumber'] === $track)) {
                $found = true;
                break 2;
            }
        }
        $page++;
    } while (true);

    echo json_encode(['exists' => $found]);
    exit;
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Identificadores NFC</title>
  <link rel="icon" href="../assets/ICOTG.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/adminlte.css">
  <style>
    .form-check-input { transform: scale(1.3); }
    .table th, .table td { vertical-align: middle; }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <div class="content-wrapper p-4">
    <section class="content">
      <div class="container-fluid">
        <h4 class="mb-4">Gestión de Identificadores NFC</h4>

        <table class="table table-bordered table-striped" id="tablaIdentificadores">
          <thead class="table-dark text-center">
            <tr>
              <th>TIPO</th>
              <th>TIPO DE USO</th>
              <th>MODELO</th>
              <th>PROGRAMA</th>
              <th>COMPAÑÍA</th>
              <th>CONTRATO</th>
              <th>ETIQUETA</th>
              <th>TRACK (UID)</th>
              <th>NIP</th>
              <th>REQ. CAMBIO NIP</th>
            </tr>
          </thead>
          <tbody>
            <tr id="row-1">
              <td>
                <select class="form-control tipo-select" onchange="actualizarModelo(this)">
                  <option value="">-- Seleccione --</option>
                  <option value="TARJETA">Tarjeta</option>
                  <option value="TAG">TAG</option>
                </select>
              </td>
              <td><input type="text" class="form-control" value="FLOTILLA" readonly></td>
              <td><input type="text" class="form-control modelo-input" readonly></td>
              <td>
                <select class="form-control">
                  <option value="CLASSIC">Classic</option>
                  <option value="TOKENGAS">Tokengas</option>
                </select>
              </td>
              <td><input type="text" id="compania" class="form-control" placeholder="Buscar compañía..."></td>
              <td>
                <select id="contrato" class="form-control">
                  <option value="">-- Selecciona contrato --</option>
                </select>
              </td>
              <td><input type="text" class="form-control etiqueta-input" maxlength="19" value="1508-0000-0000-" ></td>
              <td><input type="text" class="form-control uid-field" id="uid-1" readonly></td>
              <td><input type="text" class="form-control" maxlength="4" value="1234"></td>
              <td class="text-center"><input type="checkbox" class="form-check-input" checked></td>
            </tr>
          </tbody>
        </table>

        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-success" onclick="agregarRenglon()">Agregar Renglón</button>
          <button class="btn btn-danger" onclick="borrarRenglones()">Borrar Renglón</button>
          <button class="btn btn-primary" onclick="crearIdentificadores()">Crear Identificadores</button>
        </div>

      </div>
    </section>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="../js/identificadores.js"></script>

</body>
</html>
