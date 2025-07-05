<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

foreach (['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_PORT'] as $var) {
    if (empty($_ENV[$var])) {
        http_response_code(500);
        echo json_encode(['error' => "Falta la variable de entorno: {$var}"]);
        exit;
    }
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$compania = $data['compania'] ?? '';
$fecha = $data['fecha'] ?? date('Y-m-d');
$comision = isset($data['comision']) ? '$ ' . number_format((float)$data['comision'], 2) . ' MXN' : 'No aplica';
$montoTotal = number_format((float)($data['monto'] ?? 0), 2);
$contracts = $data['CompanyContracts'] ?? [];
$detalles = $data['Detalles'] ?? [];

if (empty($contracts) || empty($detalles)) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan detalles o contratos']);
    exit;
}

// Agrupar por correo
$grupos = [];
foreach ($detalles as $detalle) {
    $codigoContrato = $detalle['contrato'];
    $info = array_filter($contracts, fn($c) => $c['Code'] === $codigoContrato);
    $info = array_values($info)[0] ?? null;

    if (!$info || empty($info['Billing']['RecepientEmails'])) {
        continue;
    }

    $claveCorreo = $info['Billing']['RecepientEmails'];
    if (!isset($grupos[$claveCorreo])) $grupos[$claveCorreo] = [];
    $grupos[$claveCorreo][] = $detalle;
}

if (empty($grupos)) {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontraron correos válidos']);
    exit;
}

$logoURL = 'https://console.tokengas.com.mx/assets/logo_tg1.png';
$errors = [];

foreach ($grupos as $correoDestino => $detallesGrupo) {
    $tablaHtml = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; font-size: 14px; width: 100%;">';
    $tablaHtml .= '<thead style="background: #f1f1f1;"><tr><th>Contrato</th><th>Descripción</th><th>Monto</th></tr></thead><tbody>';
    foreach ($detallesGrupo as $item) {
        $c = htmlspecialchars($item['contrato']);
        $d = htmlspecialchars($item['descripcion']);
        $m = number_format((float)$item['monto'], 2);
        $tablaHtml .= "<tr><td>{$c}</td><td>{$d}</td><td style='text-align:right;'>$ {$m} MXN</td></tr>";
    }
    $tablaHtml .= '</tbody></table>';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; background: #f9f9f9; }
    .container { background: #fff; padding: 20px; max-width: 700px; margin: auto; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    .logo { text-align: center; margin-bottom: 20px; }
    .footer { margin-top: 30px; font-size: 0.9em; color: #888; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <img src="{$logoURL}" alt="TokenGas" height="50">
    </div>
    <h2>Depósito registrado exitosamente</h2>
    <p>Compañía: <strong>{$compania}</strong></p>
    <p>Fecha: <strong>{$fecha}</strong></p>
    {$tablaHtml}
    <br>
    <p><strong>Total depositado:</strong> $ {$montoTotal} MXN</p>
    <p><strong>Comisión total aplicada:</strong> {$comision}</p>
    <p>Este correo es informativo. Si tienes dudas, contáctanos en <a href="mailto:{$_ENV['SMTP_USER']}">{$_ENV['SMTP_USER']}</a>.</p>
    <div class="footer">© TokenGas - Todos los derechos reservados</div>
  </div>
</body>
</html>
HTML;

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

        foreach (explode(';', $correoDestino) as $to) {
            $mail->addAddress(trim($to));
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Confirmación de Depósito - {$compania}";
        $mail->Body    = $html;
        $mail->send();
    } catch (Exception $e) {
        $errors[] = "Error con {$correoDestino}: " . $mail->ErrorInfo;
    }
}

if ($errors) {
    http_response_code(500);
    echo json_encode(['error' => $errors]);
} else {
    echo json_encode(['success' => true]);
}
