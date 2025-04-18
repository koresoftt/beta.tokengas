<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[{$timestamp}] [{$level}] {$message}", 0);
}


function obtenerAccessToken() {
    // Obtener credenciales desde .env
    $username = $_ENV['ATIONET_USER'] ?? null;
    $password = $_ENV['ATIONET_PASS'] ?? null;
    $entityId = $_ENV['ENTITIESB'] ?? null; // Entidad para producción
    $apiUrl = 'https://api-beta.ationet.com/Token'; // URL de producción
    
    if (!$username || !$password || !$entityId) {
        logMessage("Credenciales o entidad no configuradas en .env", 'ERROR');
        exit("Error: Credenciales o entidad no configuradas.");
    }
    
    // Tipo de entidad fijo (por ejemplo, "Network")
    $entityType = "Network";
    $entityJson = json_encode(['id' => $entityId, 'Type' => $entityType]);
    $encodedEntity = urlencode($entityJson);
    
    // Construir los parámetros para la solicitud POST
    $postData = "grant_type=password&username={$username}&password={$password}&entity={$encodedEntity}";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_HEADER => false,
    ]);
    
    $response = curl_exec($curl);
    $curl_errno = curl_errno($curl);
    $curl_error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($curl_errno) {
        logMessage("cURL error: " . $curl_error, 'ERROR');
        exit("Error interno del servidor cURL");
    }
    
    if ($http_code !== 200) {
        logMessage("API error. HTTP code: " . $http_code, 'ERROR');
        exit("Error en la API. Código HTTP: " . $http_code);
    }
    
    curl_close($curl);
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage("JSON error: " . json_last_error_msg(), 'ERROR');
        exit("Error JSON: " . json_last_error_msg());
    }
    
    if (!isset($data['access_token'])) {
        logMessage("API error: Token inválido. Respuesta: " . $response, 'ERROR');
        exit("Error API: Token inválido");
    }
    
    logMessage("Token obtenido: " . $data['access_token'], 'INFO');
    return $data['access_token'];
    
    
    // Inicia la sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guardar el token en sesión
$_SESSION['access_token'] = obtenerAccessToken();
}
