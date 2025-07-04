<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: application/json');

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Validar variables del entorno
$requiredEnv = ['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_PORT'];
foreach ($requiredEnv as $var) {
    if (empty($_ENV[$var])) {
        http_response_code(500);
        echo json_encode(['error' => "Falta la variable de entorno: {$var}"]);
        exit;
    }
}

// Leer JSON recibido
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

// Datos básicos
$compania = $data['compania'] ?? '';
$contrato = $data['contrato'] ?? '';
$monto    = number_format((float)($data['monto'] ?? 0), 2);
$comision = isset($data['comision']) ? '$ ' . number_format((float)$data['comision'], 2) . ' MXN' : 'No aplica';
$fecha    = $data['fecha'] ?? date('Y-m-d');
$contracts = $data['CompanyContracts'] ?? [];

if (!$compania || !$contrato || !$monto || !$contracts) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos requeridos']);
    exit;
}

// Buscar el contrato específico con correo
$selected = null;
foreach ($contracts as $c) {
    if ($c['Code'] === $contrato && !empty($c['Billing']['RecepientEmails'])) {
        $selected = $c;
        break;
    }
}

if (!$selected) {
    http_response_code(404);
    echo json_encode(['error' => 'Contrato no encontrado o sin correo']);
    exit;
}

$correoDestino = $selected['Billing']['RecepientEmails'];
$nombreDestino = $selected['Billing']['Name'] ?? $selected['Description'] ?? 'Usuario';
$contractDesc  = "{$selected['Code']} - {$selected['Description']}";

// HTML del correo con logo (puedes cambiar la ruta del logo si es necesario)
$logoURL = 'https://console.tokengas.com.mx/assets/logo_tg1.png';
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; background: #f9f9f9; }
    .container { background: #fff; padding: 20px; max-width: 600px; margin: auto; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    .logo { text-align: center; margin-bottom: 20px; }
    .title { color: #0d6efd; }
    .highlight { font-weight: bold; }
    .footer { margin-top: 30px; font-size: 0.9em; color: #888; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <img src="{$logoURL}" alt="Logo" height="50">
    </div>
    <h2 class="title">Depósito registrado exitosamente</h2>
    <p>Estimado(a) <strong>{$nombreDestino}</strong>,</p>
    <p>Se ha registrado un nuevo depósito en tu contrato <strong>{$contractDesc}</strong> de la compañía <strong>{$compania}</strong>.</p>
    <ul>
      <li><span class="highlight">Monto:</span> \$ {$monto} MXN</li>
      <li><span class="highlight">Comisión:</span> {$comision}</li>
      <li><span class="highlight">Fecha:</span> {$fecha}</li>
    </ul>
    <p>Este correo es informativo. Si tienes dudas, contáctanos en <a href="mailto:{$_ENV['SMTP_USER']}">{$_ENV['SMTP_USER']}</a>.</p>
    <div class="footer">© TokenGas - Todos los derechos reservados</div>
  </div>
</body>
</html>
HTML;

// Envío del correo
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];
    $mail->Password = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)$_ENV['SMTP_PORT'];

    $mail->setFrom($_ENV['SMTP_USER'], 'TokenGas');

    foreach (explode(';', $correoDestino) as $email) {
        $mail->addAddress(trim($email));
    }

    $mail->isHTML(true);
    $mail->Subject = "Depósito registrado - {$contractDesc}";
    $mail->Body    = $html;

    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("❌ Error PHPMailer: " . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode(['error' => "Error al enviar correo: " . $mail->ErrorInfo]);
}
