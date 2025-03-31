<?php
require_once __DIR__ . '/../../auth/env_loader.php';

// 1) Obtenemos las credenciales del .env
$ationetUser = $_ENV['ATIONET_USER'] ?? null;
$ationetPass = $_ENV['ATIONET_PASS'] ?? null;

// 2) Construimos la cadena tal como tu API la requiere (texto plano)
$authorization = "Basic {$ationetUser}:{$ationetPass}";

// 3) Endpoint y datos
$url = "https://native-beta.ationet.com/v1/Interface";

$data = [
    "SubscriberCode" => "2F4",  
    "ActionCode"     => 907, 
    "CompanyCode"    => "C180923", 
    "ContractCode"   => "KORESOFT2",
    "Amount"         => 1500,
    "CurrencyCode"   => "MXN",
    "Description"    => "prueba 210325"
];

$jsonData = json_encode($data);

// 4) Configuramos cURL
$ch = curl_init($url);

// 5) Cabeceras (¡aquí usamos $authorization!)
$headers = [
    "Content-Type: application/json",
    "Authorization: $authorization",
    "User-Agent: Fiddler",
    "Accept-Encoding: gzip"
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

// Manejo de respuesta comprimida GZIP
curl_setopt($ch, CURLOPT_ENCODING, '');

// Para recibir respuesta en vez de imprimirla
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
}
curl_close($ch);

// 6) Mostramos resultado
if (isset($error_msg)) {
    echo "cURL Error: " . $error_msg;
} else {
    echo $response;
}
