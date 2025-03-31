<?php
header('Content-Type: text/html');
session_start();
require_once __DIR__ . '/../auth/token_handler.php';

$accessToken = trim(token());

// **Definir fechas desde el 1 de enero hasta hoy**
$yearActual = date("Y");
$fechaDesde = "$yearActual/01/01 00:00:00";
$fechaHasta = date("Y/m/d") . " 23:59:59";

// **Obtener la primera página para saber cuántas hay**
$urlBase = "https://api.ationet.com/Transactions?dateTimeFrom=" . urlencode($fechaDesde) . "&dateTimeTo=" . urlencode($fechaHasta) . "&page=1";
$ch = curl_init($urlBase);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
    CURLOPT_TIMEOUT => 10,
]);
$resp = curl_exec($ch);
curl_close($ch);

// **Decodificar la respuesta**
$datosIniciales = json_decode($resp, true);
$totalPages = $datosIniciales['TotalPages'] ?? 1;
$movimientos = $datosIniciales['Content'] ?? [];

// **Suma de litros por tipo de combustible**
$sumaLitros = ['REGULAR' => 0, 'PREMIUM' => 0, 'DIESEL' => 0];

// **Procesar la primera página**
foreach ($movimientos as $mov) {
    switch ($mov['FuelCode']) {
        case "10100": $sumaLitros['REGULAR'] += (float) $mov['ProductVolumeDispensed']; break;
        case "10300": $sumaLitros['PREMIUM'] += (float) $mov['ProductVolumeDispensed']; break;
        case "10400": $sumaLitros['DIESEL'] += (float) $mov['ProductVolumeDispensed']; break;
    }
}

// **Recorrer todas las páginas restantes y obtener los movimientos**
for ($page = 2; $page <= $totalPages; $page++) {
    $urlPagina = "https://api.ationet.com/Transactions?dateTimeFrom=" . urlencode($fechaDesde) . "&dateTimeTo=" . urlencode($fechaHasta) . "&page=$page";
    
    $ch = curl_init($urlPagina);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    $datosPagina = json_decode($resp, true);
    if (isset($datosPagina['Content'])) {
        foreach ($datosPagina['Content'] as $mov) {
            switch ($mov['FuelCode']) {
                case "10100": $sumaLitros['REGULAR'] += (float) $mov['ProductVolumeDispensed']; break;
                case "10300": $sumaLitros['PREMIUM'] += (float) $mov['ProductVolumeDispensed']; break;
                case "10400": $sumaLitros['DIESEL'] += (float) $mov['ProductVolumeDispensed']; break;
            }
        }
    }
}

// **Mostrar los resultados en la página**
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Consumo de Litros</title>
</head>
<body>
    <h2>Consumo de Litros (Desde el <?= $yearActual ?>/01/01 hasta Hoy)</h2>

    <p><strong>🟢 REGULAR:</strong> <?= number_format($sumaLitros['REGULAR'], 2) ?> L</p>
    <p><strong>🔴 PREMIUM:</strong> <?= number_format($sumaLitros['PREMIUM'], 2) ?> L</p>
    <p><strong>⚫ DIESEL:</strong> <?= number_format($sumaLitros['DIESEL'], 2) ?> L</p>

</body>
</html>
