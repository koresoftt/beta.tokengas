<?php
require_once __DIR__ . '/../../auth/env_loader.php';

// Leer payload JSON del cuerpo de la petición
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validar JSON válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

// Campos obligatorios
$required = ['SubscriberCode','ActionCode','CompanyCode','ContractCode','Amount','CurrencyCode','Description'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(422);
        echo json_encode(['error' => "Falta el campo obligatorio: {$field}"]);
        exit;
    }
}

// Obtener credenciales desde .env
$ationetUser = $_ENV['ATIONET_USER'] ?? '';
$ationetPass = $_ENV['ATIONET_PASS'] ?? '';
$authorization = "Basic {$ationetUser}:{$ationetPass}";

// Endpoint
$url = "https://native-beta.ationet.com/v1/Interface";

// Preparar JSON a enviar
$jsonData = json_encode($data);

// Configurar cURL
$ch = curl_init($url);
$headers = [
    "Content-Type: application/json",
    "Authorization: {$authorization}",
    "User-Agent: Fiddler",
    "Accept-Encoding: gzip"
];
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $jsonData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => ''
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
}
curl_close($ch);

// Responder siempre JSON
header('Content-Type: application/json');

if (isset($error_msg)) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL Error', 'message' => $error_msg]);
} else {
    http_response_code($httpCode);
    echo $response;
}
?>