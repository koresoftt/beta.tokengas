<?php
// Seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) session_start();

// Incluir token_handler
require_once __DIR__ . '/auth/token_handler.php';

// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Leer mensaje de error (si hay)
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Proceso de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: CSRF detectado.");
    }

    $correo = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($correo) || empty($password)) {
        $_SESSION['error_message'] = "Completa todos los campos.";
        header("Location: index.php");
        exit;
    }

    // Login a API
    $url = "https://api-beta.ationet.com/Token";
    $postData = http_build_query([
        "grant_type" => "password",
        "username"   => $correo,
        "password"   => $password
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($http_status === 200 && isset($data['access_token'])) {
        $_SESSION['userName']         = $data['userName'] ?? '';
        $_SESSION['entities']         = $data['entities'] ?? '';
        $_SESSION['preferenceEntity'] = $data['preferenceEntity'] ?? '';

        // Token correcto para operaciones protegidas
        $correctAccessToken = obtenerAccessToken();
        $_SESSION['access_token'] = $correctAccessToken;

        // Obtener nombre completo del usuario desde /Users
        $userInfoUrl = "https://api-beta.ationet.com/Users?userName=" . urlencode($correo);
        $headers = [
            "Authorization: Bearer {$correctAccessToken}",
            "Content-Type: application/json"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $responseUser = curl_exec($ch);
        curl_close($ch);

        $userData = json_decode($responseUser, true);
        $_SESSION['name'] = $userData['Content'][0]['Name'] ?? $data['userName'];

        // Redirigir al dashboard
        unset($_SESSION['csrf_token']);
        header("Location: vista/dashboard.php");
        exit;
    } else {
        $_SESSION['error_message'] = "No se pudo iniciar sesión.";
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-container">
    <img src="assets/logo_tg1.png" alt="Logo de Tokengas" class="logo" width="150">
    <form method="POST" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="text" name="username" id="username" placeholder="Usuario" required>
        <input type="password" name="password" id="password" placeholder="Contraseña" required>
        <button type="submit" class="login-btn">Ingreso</button>
    </form>
    <?php if (!empty($error_message)): ?>
        <div id="error-message" class="error-message">
            <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
