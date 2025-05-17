<?php
// identificadores.php

// mostrar errores solo en dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();
session_start();

// 1) Autorización básica
if (empty($_SESSION['access_token'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'No autorizado']));
}

$apiBase   = $_ENV['API_BASE']    ?? 'https://api-beta.ationet.com';
$networkId = $_ENV['ENTITIESB']   ?? '';  // tu GUID de red desde .env

// 2) Si viene AJAX, manejamos todas las rutas aquí
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $token = $_SESSION['access_token'];

    switch ($_GET['ajax']) {

      // ── 1) Autocomplete compañías ───────────────────────
      case 'companies':
        $all = []; $page = 1;
        do {
          $url = "{$apiBase}/companies?page={$page}&limit=50";
          $ch  = curl_init($url);
          curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"]
          ]);
          $resp = curl_exec($ch);
          $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);
          if ($code !== 200) break;
          $json  = json_decode($resp, true);
          $chunk = $json['Content'] ?? [];
          if (empty($chunk)) break;
          $all = array_merge($all, $chunk);
          $page++;
        } while (true);

        $term = mb_strtolower(trim($_GET['term'] ?? ''));
        $out  = [];
        foreach ($all as $c) {
          $name = $c['Name'] ?? '';
          $id   = $c['Id']   ?? null;
          if ($term === '' || mb_stripos($name, $term) !== false) {
            $out[] = ['id'=>$id,'name'=>$name];
          }
        }
        echo json_encode($out ?: ['error'=>'No se encontraron coincidencias']);
        break;


      // ── 2) Contratos por compañía ───────────────────────
      case 'companyContracts':
        $companyId = trim($_GET['companyId'] ?? '');
        if ($companyId === '') {
          echo json_encode(['error'=>'Falta companyId']);
          break;
        }
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
          echo json_encode(['error'=>"HTTP {$code} al obtener contratos"]);
          break;
        }
        $data = json_decode($resp, true)['Content'] ?? [];
        echo json_encode($data);
        break;


      // ── 3) Validación label/track ───────────────────────
      case 'checkIdentificador':
        $label = trim($_GET['label'] ?? '');
        $track = trim($_GET['track'] ?? '');
        if ($label === '' && $track === '') {
          echo json_encode(['error'=>'Parámetro requerido']);
          break;
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
          $json  = json_decode($resp, true);
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
        } while (true);
        echo json_encode(['exists'=>$found]);
        break;


      // ── 4) Crear Identificadores ────────────────────────
      case 'createIdentificadores':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          exit(json_encode(['error'=>'Método no permitido']));
        }
        $raw   = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (json_last_error()!==JSON_ERROR_NONE || empty($input['items']) || !is_array($input['items'])) {
          http_response_code(400);
          exit(json_encode(['error'=>'Payload inválido']));
        }

        foreach ($input['items'] as $idx => $it) {
          $payload = [
            'NetworkId'             => $networkId,
            'UseType'               => 0,
            'Type'                  => $it['Type']                  ?? null,
            'State'                 => $it['State']                 ?? 7,
            'TypeModelId'           => $it['TypeModelId']           ?? null,
            'TypeModelDescription'  => $it['TypeModelDescription']  ?? null,
            'ProgramId'             => $it['ProgramId']             ?? null,
            'ProgramDescription'    => $it['ProgramDescription']    ?? null,
            'IdCompany'             => $it['IdCompany']             ?? null,
            'ContractId'            => $it['ContractId']            ?? null,
            'ContractCode'          => $it['ContractCode']          ?? null,
            'Label'                 => $it['Label']                 ?? null,
            'TrackNumber'           => $it['TrackNumber']           ?? null,
            'PAN'                   => $it['PAN']                   ?? null,
            'PIN'                   => $it['PIN']                   ?? null,
            'RequiresPINChange'     => !empty($it['RequiresPINChange']),
            'Active'                => true
          ];
          $ch = curl_init("{$apiBase}/Identifications");
          curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
              "Authorization: Bearer {$token}",
              "Content-Type: application/json",
              "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
          ]);
          $resp    = curl_exec($ch);
          $httpCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
          $err     = curl_error($ch);
          curl_close($ch);

          if ($err || $httpCode < 200 || $httpCode >= 300) {
            http_response_code(502);
            exit(json_encode([
              'error'    => "Fila {$idx} falló: " . ($err ?: "HTTP {$httpCode}"),
              'response' => json_decode($resp, true)
            ]));
          }
        }

        // si llegamos aquí, todo bien
        echo json_encode(['success'=>true]);
        break;


      default:
        http_response_code(400);
        echo json_encode(['error'=>'Petición inválida']);
        break;
    }
    exit;
}

// 5) Si no es AJAX, dibujamos la página:
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Gestión de Identificadores NFC</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
  <link href="../css/adminlte.css" rel="stylesheet">
  <style>
    .form-check-input { transform: scale(1.3); }
    .table th, .table td { vertical-align: middle; }
  </style>

  <script>
    // esto lo usa tu JS
    window.API_URL    = 'identificadores.php';
    window.NETWORK_ID = '<?php echo $networkId;?>';
  </script>
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
            <td><input type="text" class="form-control tipo-uso-input" value="FLOTILLA" readonly></td>
            <td><input type="text" class="form-control modelo-input" readonly></td>
            <td>
              <select class="form-control programa-select">
                <option value="CLASSIC">Classic</option>
                <option value="TOKENGAS">Tokengas</option>
              </select>
            </td>
            <td><input type="text" class="form-control compania-autocomplete" placeholder="Buscar compañía..."></td>
            <td>
              <select class="form-control contrato-select">
                <option value="">-- Selecciona contrato --</option>
              </select>
            </td>
            <td><input type="text" class="form-control etiqueta-input" maxlength="19" value="1508-0000-0000-0001"></td>
            <td><input type="text" class="form-control uid-field" readonly></td>
            <td><input type="text" class="form-control nip-field" maxlength="4" value="1234"></td>
            <td class="text-center"><input type="checkbox" class="form-check-input req-nip-checkbox" checked></td>
          </tr>
        </tbody>
      </table>
      <div class="mt-3 d-flex gap-2">
        <button id="btnAgregar" class="btn btn-success">Agregar Renglón</button>
        <button id="btnBorrar"  class="btn btn-danger">Borrar Renglón</button>
        <button id="btnCrear"   class="btn btn-primary">Crear Identificadores</button>
        <button id="btnExportarExcel" class="btn btn-secondary">Exportar a CSV</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <script src="../js/identificadores.js"></script>
</body>
</html>
