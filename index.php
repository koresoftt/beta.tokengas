<?php
// Configuraciones de seguridad para cookies y sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir el token_handler (asegúrate de que la ruta sea correcta)
require_once __DIR__ . '../auth/token_handler.php';

// Generar token CSRF si aún no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Recuperar mensaje de error (si existe) y luego limpiarlo
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Procesamiento del formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF para prevenir ataques CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error: Solicitud inválida (CSRF detectado).");
    }

    // Obtener y sanitizar datos del formulario
    $correo = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'] ?? '', FILTER_SANITIZE_STRING);

    // Validar que se hayan completado los campos
    if (empty($correo) || empty($password)) {
        $_SESSION['error_message'] = "Por favor, completa todos los campos.";
        header("Location: index.php");
        exit;
    }

    // 1) Llamada inicial a la API para validar las credenciales del usuario
    $url = "https://api.ationet.com/Token";
    $postData = http_build_query([
        "grant_type" => "password",
        "username"    => $correo,
        "password"    => $password,
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

    // 2) Si la respuesta es exitosa y se recibe un access_token, el login fue válido
    if ($http_status === 200 && isset($data['access_token'])) {
        
        // Extraer datos de la respuesta que te interesen (por ejemplo, userName, entities, preferenceEntity)
        // y almacenarlos en la sesión
        $_SESSION['userName']         = $data['userName'] ?? '';
        $_SESSION['entities']         = $data['entities'] ?? '';
        $_SESSION['preferenceEntity'] = $data['preferenceEntity'] ?? '';

        // 3) Llamar a token_handler para obtener el token correcto (según entorno y entidad)
        $correctAccessToken = obtenerAccessToken();

        // Renovar el token CSRF (ya que se está validando un nuevo inicio de sesión)
        unset($_SESSION['csrf_token']);

        // Guardar el token definitivo en la sesión para su uso posterior (áreas protegidas)
        $_SESSION['access_token'] = $correctAccessToken;

        // Redireccionar al dashboard
        header("Location: /tokengas/vista/dashboard.php");
        exit;
    } else {
        // Si las credenciales no son válidas, se guarda un mensaje de error y se redirige al formulario
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