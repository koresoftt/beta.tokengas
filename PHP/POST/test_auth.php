<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$username = $_ENV['ATIONET_USER'];
$password = $_ENV['ATIONET_PASS'];
$entityId = $_ENV['ENTITIESB']; // o ENTITIESB si estás en beta

$entityJson = json_encode(['id' => $entityId, 'Type' => 'Network']);
$encodedEntity = urlencode($entityJson);

$postData = "grant_type=password&username={$username}&password={$password}&entity={$encodedEntity}";

$ch = curl_init('https://api-beta.ationet.com/Token'); // o https://api-beta.ationet.com/Token si estás en beta
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<pre>HTTP CODE: $httpCode\n";
echo "Response:\n$response</pre>";
