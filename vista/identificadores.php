<?php
// identificadores.php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();
session_start();

$apiBase = 'https://api-beta.ationet.com';

// — AJAX 1: buscar compañías paginadas y filtrar por término —
if (isset($_GET['ajax']) && $_GET['ajax'] === 'companies') {
    header('Content-Type: application/json; charset=UTF-8');
    if (empty($_SESSION['access_token'])) {
        echo json_encode(['error'=>'Token no encontrado']); exit;
    }
    $token = $_SESSION['access_token'];

    // Recolectar todas las compañías (paginado)
    $all = []; $page = 1; $limit = 50;
    do {
        $url = "{$apiBase}/companies?page={$page}&limit={$limit}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"]
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) break;
        $json = json_decode($resp, true);
        $chunk = $json['Content'] ?? [];
        if (empty($chunk)) break;
        $all = array_merge($all, $chunk);
        $page++;
    } while(true);

    // Filtrar localmente según term
    $term = mb_strtolower(trim($_GET['term'] ?? ''));
    $out = [];
    foreach ($all as $c) {
        $name = $c['Name'] ?? '';
        $id   = $c['Id']   ?? null;
        if ($term === '' || mb_stripos($name, $term) !== false) {
            $out[] = ['id'=>$id,'name'=>$name];
        }
    }
    echo json_encode($out ?: ['error'=>'No se encontraron coincidencias']); 
    exit;
}

// — AJAX 2: obtener contratos de una compañía —
if (isset($_GET['ajax']) && $_GET['ajax'] === 'companyContracts') {
    header('Content-Type: application/json; charset=UTF-8');
    if (empty($_SESSION['access_token'])) {
        echo json_encode(['error'=>'Token no encontrado']); exit;
    }
    $companyId = trim($_GET['companyId'] ?? '');
    if ($companyId === '') {
        echo json_encode(['error'=>'Falta companyId']); exit;
    }
    $token = $_SESSION['access_token'];

    $url = "{$apiBase}/CompanyContracts?companyId=" . urlencode($companyId);
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"]
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        echo json_encode(['error'=>"HTTP {$code} al obtener contratos"]); exit;
    }
    $json = json_decode($resp, true);
    echo json_encode($json['Content'] ?? []); 
    exit;
}

// — AJAX 3: validar etiqueta (label) o trackNumber —
if (isset($_GET['ajax']) && $_GET['ajax'] === 'checkIdentificador') {
    header('Content-Type: application/json; charset=UTF-8');
    if (empty($_SESSION['access_token'])) {
        echo json_encode(['error'=>'Token no encontrado']); exit;
    }
    $token = $_SESSION['access_token'];
    $label = trim($_GET['label'] ?? '');
    $track = trim($_GET['track'] ?? '');
    if ($label === '' && $track === '') {
        echo json_encode(['error'=>'Parámetro requerido']); exit;
    }

    $found = false; $page = 1;
    do {
        $q = ['page'=>$page,'pageSize'=>100];
        if ($label) $q['label']       = $label;
        if ($track) $q['trackNumber'] = $track;
        $url = "{$apiBase}/Identifications?" . http_build_query($q);
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"]
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) break;
        $json = json_decode($resp, true);
        $items = $json['Content'] ?? [];
        if (empty($items)) break;
        foreach ($items as $it) {
            if (($label && $it['Label'] === $label) ||
                ($track && $it['TrackNumber'] === $track)) {
                $found = true;
                break 2;
            }
        }
        $page++;
    } while(true);

    echo json_encode(['exists'=>$found]);
    exit;
}

// Si no es AJAX, renderizamos la página:
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Identificadores NFC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
  <link href="../css/adminlte.css" rel="stylesheet">
  <style>.form-check-input{transform:scale(1.3);} .table th,.table td{vertical-align:middle;}</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">
    <div class="content-wrapper p-4">
      <h4 class="mb-4">Gestión de Identificadores NFC</h4>
      <table class="table table-bordered table-striped" id="tablaIdentificadores">
        <thead class="table-dark text-center">
          <tr>
            <th>TIPO</th><th>TIPO DE USO</th><th>MODELO</th><th>PROGRAMA</th>
            <th>COMPAÑÍA</th><th>CONTRATO</th><th>ETIQUETA</th><th>TRACK (UID)</th>
            <th>NIP</th><th>REQ. CAMBIO NIP</th>
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
    <td>
      <input type="text" class="form-control tipo-uso-input" value="FLOTILLA" readonly>
    </td>
    <td>
      <input type="text" class="form-control modelo-input" readonly>
    </td>
    <td>
      <select class="form-control programa-select">
        <option value="CLASSIC">Classic</option>
        <option value="TOKENGAS">Tokengas</option>
      </select>
    </td>
    <td>
      <!-- antes era id="compania" -->
      <input type="text" class="form-control compania-autocomplete" placeholder="Buscar compañía...">
    </td>
    <td>
      <!-- antes era id="contrato" -->
      <select class="form-control contrato-select">
        <option value="">-- Selecciona contrato --</option>
      </select>
    </td>
    <td>
      <input type="text" class="form-control etiqueta-input" maxlength="19" value="1508-0000-0000-0001">
    </td>
    <td>
      <input type="text" class="form-control uid-field" readonly>
    </td>
    <td>
      <input type="text" class="form-control nip-field" maxlength="4" value="1234">
    </td>
    <td class="text-center">
      <input type="checkbox" class="form-check-input req-nip-checkbox" checked>
    </td>
  </tr>
</tbody>

      </table>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-success" onclick="agregarRenglon()">Agregar Renglón</button>
        <button class="btn btn-danger"  onclick="borrarRenglones()">Borrar Renglón</button>
        <button class="btn btn-primary" onclick="crearIdentificadores()">Crear Identificadores</button>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <script src="../js/identificadores.js"></script>
</body>
</html>
