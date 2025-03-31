<?php
header('Content-Type: application/json');
session_start();

// 🌎 **1. Configurar Zona Horaria de Mazatlán**
date_default_timezone_set('America/Mazatlan');

// 🛠️ **2. Obtener el Token**
$tokenUrl = 'https://beta.tokengas.com.mx/auth/token_handler.php';
$tokenJson = file_get_contents($tokenUrl);

if (!$tokenJson) {
    echo json_encode(["error" => "No se pudo conectar con token_handler.php"]);
    exit();
}

$tokenData = json_decode($tokenJson, true);
if (!isset($tokenData['access_token'])) {
    echo json_encode(["error" => "Token inválido"]);
    exit();
}
$accessToken = $tokenData['access_token'];

// 🕒 **3. Obtener Fecha y Hora de Mazatlán**
$dtInicio = new DateTime("now", new DateTimeZone("America/Mazatlan"));
$horaActual = $dtInicio->format('H:i:s');
$fechaHoy = $dtInicio->format('Y/m/d');

/**
 * 🔗 **Función para construir URL de consulta**
 */
function buildUrl($fecha, $horaActual) {
    return "https://api.ationet.com/Transactions"
         . "?dateTimeFrom=" . urlencode("$fecha 00:00:00")
         . "&dateTimeTo="   . urlencode("$fecha $horaActual");
}

// **4. Inicializar Array de Litros Vendidos**
$litrosPorFecha = [];
$fechas = [];

// 📅 **5. Generar las últimas 3 semanas + Hoy**
for ($i = 3; $i >= 0; $i--) {
    $dt = new DateTime("today -".($i * 7)." days", new DateTimeZone("America/Mazatlan"));
    $fecha = $dt->format('Y/m/d');
    $litrosPorFecha[$fecha] = ['REGULAR' => 0, 'PREMIUM' => 0, 'DIESEL' => 0];
    $fechas[] = $fecha;
}

// 🔄 **6. Inicializar cURL Multi-Requests**
$mh = curl_multi_init();
$handlesPrimera = [];

foreach ($fechas as $fecha) {
    $url = buildUrl($fecha, $horaActual) . "&page=1";
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
    curl_multi_add_handle($mh, $ch);
    $handlesPrimera[$fecha] = $ch;
}

$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running);

$paginasPendientes = [];

foreach ($handlesPrimera as $fecha => $ch) {
    $resp = curl_multi_getcontent($ch);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);

    $data = json_decode($resp, true);
    if (!isset($data['Content']) || empty($data['Content'])) {
        continue;
    }

    $contenido  = $data['Content'];
    $totalPages = $data['TotalPages'] ?? 1;

    foreach ($contenido as $mov) {
        $fc = $mov['FuelCode'] ?? null;
        $vol = (float)($mov['ProductVolumeDispensed'] ?? 0);
        if ($fc === '10100') $litrosPorFecha[$fecha]['REGULAR'] += $vol;
        if ($fc === '10300') $litrosPorFecha[$fecha]['PREMIUM'] += $vol;
        if ($fc === '10400') $litrosPorFecha[$fecha]['DIESEL']  += $vol;
    }

    if ($totalPages > 1) {
        $paginasPendientes[$fecha] = (int)$totalPages;
    }
}
curl_multi_close($mh);

// 🔄 **7. Procesar páginas 2 en adelante**
$mh2 = curl_multi_init();
$handlesRestantes = [];

foreach ($paginasPendientes as $fecha => $totalPages) {
    for ($p = 2; $p <= $totalPages; $p++) {
        $url = buildUrl($fecha, $horaActual) . "&page=" . $p;
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
        curl_multi_add_handle($mh2, $ch);
        $handlesRestantes[] = ['fecha' => $fecha, 'handle' => $ch];
    }
}

$running = null;
do {
    curl_multi_exec($mh2, $running);
    curl_multi_select($mh2);
} while ($running);

foreach ($handlesRestantes as $item) {
    $fecha = $item['fecha'];
    $ch = $item['handle'];

    $resp = curl_multi_getcontent($ch);
    curl_multi_remove_handle($mh2, $ch);
    curl_close($ch);

    $data = json_decode($resp, true);
    if (!isset($data['Content']) || empty($data['Content'])) {
        continue;
    }

    foreach ($data['Content'] as $mov) {
        $fc = $mov['FuelCode'] ?? null;
        $vol = (float)($mov['ProductVolumeDispensed'] ?? 0);
        if ($fc === '10100') $litrosPorFecha[$fecha]['REGULAR'] += $vol;
        if ($fc === '10300') $litrosPorFecha[$fecha]['PREMIUM'] += $vol;
        if ($fc === '10400') $litrosPorFecha[$fecha]['DIESEL']  += $vol;
    }
}
curl_multi_close($mh2);

// 📊 **8. Calcular Historial y Variaciones**
$historial = [];
$variaciones = [];
$hoy = array_pop($fechas);
$productos = ['REGULAR', 'PREMIUM', 'DIESEL'];
$litrosHoy = $litrosPorFecha[$hoy];

foreach ($productos as $p) {
    $historial[$p] = [];
    foreach ($fechas as $f) {
        $historial[$p][$f] = $litrosPorFecha[$f][$p];
    }

    $valoresHistoricos = array_values($historial[$p]);
    $promedio = count($valoresHistoricos) > 0 ? array_sum($valoresHistoricos) / count($valoresHistoricos) : 0;
    $hoyValor = $litrosHoy[$p];
    $variacion = $promedio > 0 ? (($hoyValor - $promedio) / $promedio) * 100 : ($hoyValor > 0 ? 100 : 0);
    $direccion = $variacion > 0 ? '📈' : ($variacion < 0 ? '📉' : '➡️');

    $variaciones[$p] = [
        'hoy' => round($hoyValor, 2),
        'variacion' => round($variacion, 2),
        'direccion' => $direccion
    ];
}

// ✅ **9. Respuesta Final**
echo json_encode([
    "error" => null,
    "fecha_hoy" => $hoy,
    "hora_actual" => $horaActual,
    "litros" => $litrosHoy,
    "historial" => $historial,
    "variaciones" => $variaciones
], JSON_PRETTY_PRINT);
