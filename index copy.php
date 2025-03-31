<?php
// Configuraciones de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']); // Limpiar el error una vez mostrado

// Validación del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar el token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Solicitud inválida (CSRF detectado).");
    }

    // Obtener y sanitizar datos del formulario
    $correo = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($correo) || empty($password)) {
        $_SESSION['error_message'] = "Por favor, completa todos los campos.";
        header("Location: index.php");
        exit;
    }

    // Llamada a la API
    $url = "https://api-beta.ationet.com/Token";
    $postData = http_build_query([
        "grant_type" => "password",
        "username"   => $correo,
        "password"   => $password,
      //  "client_id"  => "Ationet",
    //    "client_secret" => "your_client_secret"
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

    $data = json_decode($response, true);

    if ($http_status === 200 && isset($data['access_token'])) {
        unset($_SESSION['csrf_token']); // Renovar el token CSRF
        $_SESSION['access_token'] = $data['access_token'];
        header("Location: /tokengas/vista/dashboard.php");
        exit;
    } else {
        $_SESSION['error_message'] = "No se pudo iniciar sesión. Intenta nuevamente.";
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="/tokengas/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            
            width: 350px;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
            position: fixed;
            top: 30px;
        }
        .logo {
            margin-bottom: 20px;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        .login-btn {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            width: 100%;
        }
        .login-btn:hover {
            background-color: #0056b3;
        }
        input {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            box-sizing: border-box;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const errorMessage = document.getElementById("error-message");
            const inputs = document.querySelectorAll("#username, #password");

            inputs.forEach(input => {
                input.addEventListener("input", function () {
                    if (errorMessage) {
                        errorMessage.style.display = "none";
                    }
                });
            });
        });
    </script>
</head>
<body>
<div class="login-container">
    <img src="/tokengas/assets/logo_tg1.png" alt="Logo de Tokengas" class="logo" width="150">
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
