<?php
// Función para registrar eventos en el log
function log_debug($message) {
    error_log("[DEBUG][" . date('Y-m-d H:i:s') . "] " . $message);
}

// Iniciamos log para seguimiento
log_debug("INICIO - Ejecución de postdepositos.php - Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'DESCONOCIDO'));

// Intentar cargar las dependencias de forma segura
try {
    log_debug("Intentando cargar env_loader.php");
    @require_once __DIR__ . '/../../auth/env_loader.php';
    log_debug("env_loader.php cargado correctamente");
} catch (Exception $e) {
    // Si falla la carga, continuamos de todas formas
    log_debug("ERROR cargando env_loader.php: " . $e->getMessage());
}

// Inicializar variables de entorno por defecto si no se pudieron cargar
if (!isset($_ENV['ATIONET_USER']) || !isset($_ENV['ATIONET_PASS'])) {
    $_ENV['ATIONET_USER'] = '';
    $_ENV['ATIONET_PASS'] = '';
    log_debug("Variables de entorno ATIONET no encontradas. Usando valores por defecto.");
} else {
    log_debug("Variables de entorno ATIONET encontradas correctamente");
}

// Verificar si es una solicitud POST o GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_debug("Procesando solicitud POST");
    
    // Leer payload JSON del cuerpo de la petición
    $requestBody = file_get_contents('php://input');
    log_debug("Payload recibido: " . substr($requestBody, 0, 100) . (strlen($requestBody) > 100 ? '...' : ''));
    
    $data = json_decode($requestBody, true);

    // Validar JSON válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_debug("ERROR: JSON inválido - " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido', 'details' => json_last_error_msg()]);
        exit;
    }
    log_debug("JSON decodificado correctamente");

    // Campos obligatorios
    $required = ['SubscriberCode','ActionCode','CompanyCode','ContractCode','Amount','CurrencyCode','Description'];
    $missing = [];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        log_debug("ERROR: Faltan campos obligatorios: " . implode(', ', $missing));
        http_response_code(422);
        echo json_encode(['error' => "Faltan campos obligatorios", 'missing_fields' => $missing]);
        exit;
    }
    log_debug("Todos los campos obligatorios presentes");

    try {
        log_debug("Preparando llamada a API externa");
        // Obtener credenciales desde .env
        $ationetUser = $_ENV['ATIONET_USER'] ?? '';
        $ationetPass = $_ENV['ATIONET_PASS'] ?? '';
        $authorization = "Basic {$ationetUser}:{$ationetPass}";
        log_debug("Credenciales preparadas: " . (!empty($ationetUser) ? "Usuario configurado" : "Usuario NO configurado"));

        // Endpoint
        $url = "https://native-beta.ationet.com/v1/Interface";
        log_debug("URL destino: " . $url);

        // Preparar JSON a enviar
        $jsonData = json_encode($data);
        
        // Verificar si curl está disponible
        if (!function_exists('curl_init')) {
            log_debug("ERROR: cURL no está disponible en este servidor");
            throw new Exception("cURL no está disponible en este servidor");
        }
        
        // Configurar cURL
        log_debug("Iniciando configuración de cURL");
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
        log_debug("cURL configurado correctamente");

        log_debug("Ejecutando petición cURL");
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        log_debug("Respuesta recibida con código HTTP: " . $httpCode);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            log_debug("ERROR cURL: " . $error_msg);
        }
        curl_close($ch);
        log_debug("cURL cerrado");

        // Responder siempre JSON
        header('Content-Type: application/json');
        log_debug("Preparando respuesta al cliente");

        if (isset($error_msg)) {
            log_debug("Enviando respuesta de error al cliente");
            http_response_code(500);
            echo json_encode(['error' => 'cURL Error', 'message' => $error_msg]);
        } else {
            log_debug("Enviando respuesta exitosa al cliente");
            http_response_code($httpCode);
            echo $response;
        }
    } catch (Exception $e) {
        // Capturar cualquier excepción
        log_debug("EXCEPCIÓN: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Error interno', 'message' => $e->getMessage()]);
    }
} else {
    log_debug("Procesando solicitud GET - Enviando respuesta informativa");
    // Para solicitudes GET, mostrar un mensaje de API
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'active',
        'message' => 'Endpoint de depósitos activo. Esta API solo acepta solicitudes POST con JSON.',
        'version' => '1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Desconocido'
        ]
    ]);
}

log_debug("FIN - Ejecución de postdepositos.php completada");
?>