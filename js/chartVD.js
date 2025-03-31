<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: /index.php");
    exit();
}

// 1. Obtener el token desde token_handler.php
$tokenJson = file_get_contents('http://localhost/tokengas/auth/token_handler.php');
if ($tokenJson === false) {
    die("❌ No se pudo conectar con token_handler.php");
}

$tokenData = json_decode($tokenJson, true);
if (!isset($tokenData['access_token'])) {
    die("❌ No se encontró 'access_token' en la respuesta de token_handler.php");
}
$accessToken = $tokenData['access_token'];

// 2. Obtener fecha/hora de inicio del día
//    Ejemplo: "2025/03/06 00:00:00" si hoy es 6 de marzo de 2025
$hoy = date('Y/m/d'); // "YYYY/MM/DD"
$fechaDesde = $hoy . ' 00:00:00';

// 3. Construir la URL con el parámetro dateTimeFrom
$url = 'https://api.ationet.com/Transactions'
     . '?dateTimeFrom=' . urlencode($fechaDesde)
     // Puedes agregar más parámetros si son necesarios
     . '&order=desc'
     . '&page=1'
     . '&pageSize=50'
     . '&paginate=true';

// 4. Iniciar cURL y configurar la petición
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);

// 5. Ejecutar la petición
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error en cURL: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. Procesar la respuesta
if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    if ($data === null) {
        echo "❌ Error al decodificar JSON: " . json_last_error_msg();
        exit;
    }

    // Aquí ya tienes los datos desde dateTimeFrom
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "❌ Error en la solicitud. Código HTTP: $httpCode<br>";
    echo "Respuesta de la API: $response";
}
