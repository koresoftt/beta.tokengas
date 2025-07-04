<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    http_response_code(401);
    echo "<div class='alert alert-danger'>Token de sesión no encontrado. Por favor inicia sesión.</div>";
    exit();
}

$token = $_SESSION['access_token'];

$etiqueta = trim($_GET['etiqueta'] ?? '');
$vehiculo = trim($_GET['vehiculo'] ?? '');
$compania = trim($_GET['compania'] ?? '');
$searchText = trim("$etiqueta $vehiculo $compania");

$page = 1;
$pageSize = 100;
$totalPages = 1;
$items = [];

do {
    $params = [
        "searchText" => $searchText,
        "page" => $page,
        "pageSize" => $pageSize,
        "orderField" => "label",
        "orderType" => "asc",
        "closeToExpiration" => "false"
    ];

    $apiUrl = "https://api-beta.ationet.com/Identifications?" . http_build_query($params);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]
    ]);

    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($curl_error) {
        echo "<div class='alert alert-danger'>Error de conexión cURL: $curl_error</div>";
        exit();
    }

    if ($http_status !== 200) {
        echo "<div class='alert alert-danger'>Error al consultar la API. Código $http_status</div>";
        exit();
    }

    $data = json_decode($response, true);
    $items = array_merge($items, $data['Content'] ?? []);
    $totalPages = $data['TotalPages'] ?? 1;
    $page++;
} while ($page <= $totalPages);

if (empty($items)) {
    echo "<div class='alert alert-warning'>No se encontraron resultados con el filtro aplicado.</div>";
    exit();
}

echo "<div class='table-responsive'>";
echo "<table class='table table-bordered table-striped'>";
echo "<thead><tr>
        <th>Etiqueta</th>
        <th>Compañía</th>
        <th>Subcuenta</th>
        <th>PIN</th>
        <th>Saldo</th>
        <th>Reglas</th>
        <th>Estado</th>
    </tr></thead><tbody>";

foreach ($items as $index => $item) {
    $label = htmlspecialchars($item['Label'] ?? '-');
    $company = htmlspecialchars($item['CompanyName'] ?? '-');
    $subaccount = htmlspecialchars($item['SubAccountDescription'] ?? '-');
    $pin = htmlspecialchars($item['PIN'] ?? '----');
    $pinId = "pin_$index";
    $rules = htmlspecialchars($item['ContractDescription'] ?? '-');
    $rulesShort = strlen($rules) > 20 ? substr($rules, 0, 20) . '...' : $rules;
    $estado = htmlspecialchars($item['State'] ?? '-');

    // Buscar saldo real por vehículo
    $saldoReal = '0.00';
    if (isset($item['IdentificationId'])) {
        $vehiculoId = null;

        // Buscar el vehículo que contiene este identificador
        $vehiculoApiUrl = "https://api.ationet.com/Vehicles?";
        $vehiculoCurl = curl_init();
        curl_setopt_array($vehiculoCurl, [
            CURLOPT_URL => $vehiculoApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token",
                "Content-Type: application/json"
            ]
        ]);
        $vehiculosResponse = curl_exec($vehiculoCurl);
        curl_close($vehiculoCurl);

        $vehiculosData = json_decode($vehiculosResponse, true);
        $vehiculos = $vehiculosData['Content'] ?? [];

        foreach ($vehiculos as $vehiculo) {
            foreach ($vehiculo['Identifications'] ?? [] as $id) {
                if ($id['IdentificationLabel'] === $item['Label']) {
                    foreach ($vehiculo['Balances'] ?? [] as $bal) {
                        foreach ($bal['Identifications'] ?? [] as $balId) {
                            if ($balId['Label'] === $item['Label']) {
                                $saldoReal = number_format($bal['Value'] ?? 0, 2);
                                break 3;
                            }
                        }
                    }
                }
            }
        }
    }

    echo "<tr>";
    echo "<td>$label</td>";
    echo "<td>$company</td>";
    echo "<td>$subaccount</td>";
    echo "<td>
            <span id='$pinId' class='d-none'>$pin</span>
            <span id='masked_$pinId'>••••</span>
            <button class='btn btn-sm btn-outline-secondary' onclick=\"togglePIN('$pinId')\">👁</button>
          </td>";
    echo "<td>\$$saldoReal</td>";
    echo "<td title=\"$rules\">$rulesShort</td>";
    echo "<td>$estado</td>";
    echo "</tr>";
}


echo "</tbody></table>";
echo "</div>";

echo <<<JS
<script>
    function togglePIN(id) {
        const pin = document.getElementById(id);
        const masked = document.getElementById('masked_' + id);
        if (pin.classList.contains('d-none')) {
            pin.classList.remove('d-none');
            masked.classList.add('d-none');
        } else {
            pin.classList.add('d-none');
            masked.classList.remove('d-none');
        }
    }
</script>
JS;
