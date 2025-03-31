<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    http_response_code(403);
    echo json_encode(["error" => "No autorizado"]);
    exit();
}

$api_url = "https://api-beta.ationet.com/companies"; // URL de la API
$token = $_SESSION['access_token'];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
?>
