<?php
// postidentificadores.php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../../')->load();
session_start();

// … validaciones de ajax, método POST y token …

// Cargamos el NetworkId desde .env
$networkId = $_ENV['ENTITIESB'] ?? null;

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
// … validaciones de JSON …

$apiBase     = $_ENV['API_BASE'] ?? 'https://api-beta.ationet.com';
$accessToken = $_SESSION['access_token'];

foreach ($input['items'] as $idx => $it) {
    // Inyectamos NetworkId aquí
    $payload = [
        'NetworkId'               => $networkId,
        'UseType'                 => $it['UseType'] ?? 0,
        'Type'                    => $it['Type'] ?? null,
        'TypeModelId'             => $it['TypeModelId'] ?? null,
        'TypeModelDescription'    => $it['TypeModelDescription'] ?? null,
        'ProgramId'               => $it['ProgramId'] ?? null,
        'ProgramDescription'      => $it['ProgramDescription'] ?? null,
        'IdCompany'               => $it['IdCompany'] ?? null,
        'ContractId'              => $it['ContractId'] ?? null,
        'ContractCode'            => $it['ContractCode'] ?? null,
        'Label'                   => $it['Label'] ?? null,
        'TrackNumber'             => $it['TrackNumber'] ?? null,
        'PAN'                     => $it['PAN'] ?? null,
        'PIN'                     => $it['PIN'] ?? null,
        'RequiresPINChange'       => !empty($it['RequiresPINChange']),
        'Active'                  => true
    ];

    $ch = curl_init("{$apiBase}/identifications");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
    ]);
    $resp     = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $code < 200 || $code >= 300) {
        http_response_code(502);
        echo json_encode([
            'error'    => "Fila {$idx} fallo: " . ($curlErr ?: "HTTP {$code}"),
            'response' => json_decode($resp, true)
        ]);
        exit;
    }
}

// Todo bien
echo json_encode(['success' => true]);
