<?php
// Habilita errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Verifica método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Leer datos JSON
$data = json_decode(file_get_contents('php://input'), true);
error_log("📥 JSON recibido: " . print_r($data, true));

// Validar campos requeridos
if (!isset($data['contrato'], $data['monto'], $data['CompanyContracts'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos necesarios']);
    exit;
}

// Buscar el contrato con email
$contractCode = $data['contrato'];
$contracts = $data['CompanyContracts'];
$selected = null;

foreach ($contracts as $c) {
    if ($c['Code'] === $contractCode && !empty($c['Billing']['RecepientEmails'])) {
        $selected = $c;
        break;
    }
}

if (!$selected) {
    error_log("❌ Contrato no encontrado o sin correo para código: $contractCode");
    http_response_code(404);
    echo json_encode(['error' => 'Contrato no encontrado o sin correo configurado']);
    exit;
}

// Preparar datos
$emailDestinatario = $selected['Billing']['RecepientEmails'];
$nombreDestinatario = $selected['Billing']['Name'] ?? $selected['Description'] ?? 'Usuario';
$contractDesc = "{$selected['Code']} - {$selected['Description']}";
$nombreCompania = $data['compania'] ?? 'Tu compañía';
$monto = number_format((float)$data['monto'], 2);
$fecha = date('Y-m-d');

// Preparar HTML del correo
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; }
    .container { padding: 20px; }
    .highlight { font-weight: bold; color: #0d6efd; }
    .amount { font-size: 1.2em; color: #198754; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Depósito registrado exitosamente</h2>
    <p>Estimado(a) {$nombreDestinatario},</p>
    <p>Se ha registrado un nuevo depósito en tu contrato <span class="highlight">{$contractDesc}</span> de la compañía <b>{$nombreCompania}</b>.</p>
    <p><b>Monto del depósito:</b> <span class="amount">\${$monto}</span></p>
    <p>Fecha del movimiento: <b>{$fecha}</b></p>
    <p>Este correo es solo informativo. Si tienes dudas, por favor contáctanos.</p>
    <br>
    <p>Atentamente,<br><b>TokenGas</b></p>
  </div>
</body>
</html>
HTML;

// Enviar con PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'atencionaclientes@tokengas.com.mx';
    $mail->Password = '@S1nM3x2023';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('atencionaclientes@tokengas.com.mx', 'TokenGas');

    foreach (explode(';', $emailDestinatario) as $to) {
        $mail->addAddress(trim($to));
    }

    $mail->isHTML(true);
    $mail->Subject = "Depósito registrado - {$contractDesc}";
    $mail->Body = $html;

    $mail->send();
    error_log("✅ Correo enviado correctamente");
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("❌ Error al enviar correo: " . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode(['error' => 'Error al enviar correo: ' . $mail->ErrorInfo]);
}
