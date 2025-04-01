<?php
// Forzamos salida JSON solo si este archivo es un endpoint
header('Content-Type: application/json');

// Iniciar sesión si no está activa (evita "Ignoring session_start()...")
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Configurar zona horaria
date_default_timezone_set('America/Mazatlan');

// Función para registrar mensajes en el log
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[{$timestamp}] [{$level}] {$message}", 0);
}

// 2. Obtener el Token de la Sesión
if (!isset($_SESSION['access_token'])) {
    logMessage("Token no encontrado en sesión", 'ERROR');
    echo json_encode(["error" => "Token no encontrado en sesión"]);
    exit();
}
$accessToken = $_SESSION['access_token'];

// 3. Obtener fecha y hora de Mazatlán
$dtInicio = new DateTime("now", new DateTimeZone("America/Mazatlan"));
$horaActual = $dtInicio->format('H:i:s');
$fechaHoy = $dtInicio->format('Y/m/d');

// Función para construir la URL de consulta
function buildUrl($fecha, $horaActual) {
    return "https://api-beta.ationet.com/Transactions"
        . "?dateTimeFrom=" . urlencode("$fecha 00:00:00")
        . "&dateTimeTo=" . urlencode("$fecha $horaActual");
}

// 4. Inicializar array de litros vendidos
$litrosPorFecha = [];
$fechas = [];

// 5. Generar las últimas 3 semanas + hoy
for ($i = 3; $i >= 0; $i--) {
    $dt = new DateTime("today -" . ($i * 7) . " days", new DateTimeZone("America/Mazatlan"));
    $fecha = $dt->format('Y/m/d');
    $litrosPorFecha[$fecha] = ['REGULAR' => 0, 'PREMIUM' => 0, 'DIESEL' => 0];
    $fechas[] = $fecha;
}

// 6. Multi-request cURL para la página 1 de cada fecha
$mh = curl_multi_init();
$handlesPrimera = [];

foreach ($fechas as $fecha) {
    $url = buildUrl($fecha, $horaActual) . "&page=1";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Elimina el echo que imprimía el token en cada iteración
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);

    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    curl_multi_add_handle($mh, $ch);
    $handlesPrimera[$fecha] = $ch;
}

// Ejecutar las solicitudes en paralelo
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running);

$paginasPendientes = [];

// Procesar las respuestas de la página 1
foreach ($handlesPrimera as $fecha => $ch) {
    $resp = curl_multi_getcontent($ch);
    $info = curl_getinfo($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);

    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);

    if ($errno) {
        logMessage("cURL error for $fecha: $error", 'ERROR');
        continue;
    }

    if ($info['http_code'] != 200) {
        logMessage("HTTP error for $fecha: " . $info['http_code'], 'ERROR');
        continue;
    }

    $data = json_decode($resp, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage("JSON error for $fecha: " . json_last_error_msg(), 'ERROR');
        continue;
    }

    if (!isset($data['Content']) || empty($data['Content'])) {
        logMessage("No Content for $fecha", 'WARNING');
        continue;
    }

    $contenido = $data['Content'];
    $totalPages = $data['TotalPages'] ?? 1;

    // Sumar los volúmenes por FuelCode
    foreach ($contenido as $mov) {
        $fc = $mov['FuelCode'] ?? null;
        $vol = (float)($mov['ProductVolumeDispensed'] ?? 0);
        if ($fc === '10100') $litrosPorFecha[$fecha]['REGULAR'] += $vol;
        if ($fc === '10300') $litrosPorFecha[$fecha]['PREMIUM'] += $vol;
        if ($fc === '10400') $litrosPorFecha[$fecha]['DIESEL'] += $vol;
    }

    // Si hay más páginas, las procesamos después
    if ($totalPages > 1) {
        $paginasPendientes[$fecha] = (int)$totalPages;
    }
}

curl_multi_close($mh);

// 7. Procesar páginas 2 en adelante
$mh2 = curl_multi_init();
$handlesRestantes = [];

foreach ($paginasPendientes as $fecha => $totalPages) {
    for ($p = 2; $p <= $totalPages; $p++) {
        $url = buildUrl($fecha, $horaActual) . "&page=" . $p;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_multi_add_handle($mh2, $ch);
        $handlesRestantes[] = ['fecha' => $fecha, 'handle' => $ch];
    }
}

$running = null;
do {
    curl_multi_exec($mh2, $running);
    curl_multi_select($mh2);
} while ($running);

// Procesar las respuestas de las páginas 2..N
foreach ($handlesRestantes as $item) {
    $fecha = $item['fecha'];
    $ch = $item['handle'];

    $resp = curl_multi_getcontent($ch);
    $info = curl_getinfo($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);

    curl_multi_remove_handle($mh2, $ch);
    curl_close($ch);

    if ($errno) {
        logMessage("cURL error for $fecha (page 2+): $error", 'ERROR');
        continue;
    }

    if ($info['http_code'] != 200) {
        logMessage("HTTP error for $fecha (page 2+): " . $info['http_code'], 'ERROR');
        continue;
    }

    $data = json_decode($resp, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage("JSON error for $fecha (page 2+): " . json_last_error_msg(), 'ERROR');
        continue;
    }

    if (!isset($data['Content']) || empty($data['Content'])) {
        logMessage("No Content for $fecha (page 2+)", 'WARNING');
        continue;
    }

    // Sumar volúmenes para las páginas restantes
    foreach ($data['Content'] as $mov) {
        $fc = $mov['FuelCode'] ?? null;
        $vol = (float)($mov['ProductVolumeDispensed'] ?? 0);
        if ($fc === '10100') $litrosPorFecha[$fecha]['REGULAR'] += $vol;
        if ($fc === '10300') $litrosPorFecha[$fecha]['PREMIUM'] += $vol;
        if ($fc === '10400') $litrosPorFecha[$fecha]['DIESEL'] += $vol;
    }
}
curl_multi_close($mh2);

// 8. Calcular historial y variaciones
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
    $promedio = count($valoresHistoricos) > 0 
                ? array_sum($valoresHistoricos) / count($valoresHistoricos) 
                : 0;
    $hoyValor = $litrosHoy[$p];
    $variacion = $promedio > 0 
        ? (($hoyValor - $promedio) / $promedio) * 100 
        : ($hoyValor > 0 ? 100 : 0);
    $direccion = $variacion > 0 ? '📈' : ($variacion < 0 ? '📉' : '➡️');

    $variaciones[$p] = [
        'hoy' => round($hoyValor, 2),
        'variacion' => round($variacion, 2),
        'direccion' => $direccion
    ];
}

// 9. Respuesta final en JSON
echo json_encode([
    "error" => null,
    "fecha_hoy" => $hoy,
    "hora_actual" => $horaActual,
    "litros" => $litrosHoy,
    "historial" => $historial,
    "variaciones" => $variaciones
], JSON_PRETTY_PRINT);
