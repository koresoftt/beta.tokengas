<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'] ?? '', FILTER_SANITIZE_STRING);

    $url = "https://api-beta.ationet.com/token";
    $postData = http_build_query([
        "grant_type" => "password",
        "username"   => $correo,
        "password"   => $password,
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    header('Content-Type: application/json');
    echo json_encode([
        'http_status' => $http_status,
        'raw_response' => $response,
        'decoded_response' => json_decode($response, true),
    ], JSON_PRETTY_PRINT);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Test Login API</title>
</head>
<body>
  <h1>Test Login API</h1>
  <form method="POST">
    <label>Usuario (correo): <input type="text" name="username" required></label><br>
    <label>Contraseña: <input type="password" name="password" required></label><br>
    <button type="submit">Probar login</button>
  </form>
</body>
</html>
