<?php
// Este archivo es una versión simplificada sin dependencias externas para diagnóstico

// Función para registrar eventos en el log
function log_debug($message) {
    error_log("[TEST][" . date('Y-m-d H:i:s') . "] " . $message);
}

// Iniciamos log para seguimiento
log_debug("INICIO - Ejecución de test_endpoint.php - Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'DESCONOCIDO'));

// Información del servidor
$server_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconocido',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Desconocido',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Desconocido',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Desconocido',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Desconocido',
    'error_log_path' => ini_get('error_log')
];

// Verificar extensiones PHP instaladas
$extensions = [
    'curl' => extension_loaded('curl'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl')
];

log_debug("Información del servidor: " . json_encode($server_info));
log_debug("Extensiones PHP: " . json_encode($extensions));

// Responder siempre JSON
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Endpoint de prueba funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => $server_info,
    'extensions' => $extensions
]);

log_debug("FIN - Ejecución de test_endpoint.php completada");
?> 