<?php
// postidentificador.php

// DEV: mostrar errores
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// ——————— Configura tu token aquí ———————
$accessToken = 'UUosXw…tu_token_aquí…T6blqW9tLgCFirAVn6qbvNWG-GxZ5Mr9R5okTGBsZtUfrimbz9MQqcvl99A_0lCjex4L0hrH7yWxzp7PcMncb5q9bofO2Bhanoxcfdh9jlkhpQqJRgIAIlL9b5jEOIkQ38v_9kxeidySyUm4GI61qaHsK2-zXzuJs79iJdMbDjSdkiuarDhSClL-zxJBqdeenVL2eAKwPB5kt-mS2qBrvr3F9ItsI-edYu5MhLlN6dFgtzCzIPoOYiwEfEWC5AqUxFmO62gtGU7fR5m2zRcDzKAteni00nu8dQ7Yg22duppl6xeEUED7plv6v6isK_A0E35uG3ubTj3_p4AdqNtrmMrW7QbLv4vPgHcR1IIJ-eRDI4_fQAkDdg8pte8tsivbQvK1qr-3I6FvK80Q2qpvUB15HF-r70s_3azOyJQ9up2GgFi20SUmSg9DINW3k2XW6uZq0sm3FT33_noRcq6gut1H4wwHDZwXCuMaFhbQcYbqe16JFFOLvmph_6Xcd-fHwxCF6jZ3vxxHY4ayZg23l6b1HmkdX25Sb6gVZdGgMOvwOmvd36bv0W_oLXaXIteLRvvQ5PSqi_J9AB0f3oSv0rhECywArwA-dw5VBBMMNBkigip2-0OPfgR1wxLMGvfTp2w_F6b6B8CJFV8sAPln5IgU34HomyMjVl0yS9USu6C_5VkHJrmWGugzw1jPDLEjdUp_THQWS9TF5YmCzmoBX-RcltTnuMW-ikYnpsY0W7pHwM0uLA_NP0Og1v_KNyjJM2t6NO32E_2ug9kNlzW1ONsXfNIJ0Ilcj7dnHDJzKXRAFsrQ5J-FWjJmnRPRlwqIJwHySbzX3vgmyjzlQctW1KmwRkCd_IcCFGxUUZrmp5fNvz3ineMq91raKCS0hm-yZYTEk80JljfuucrW3i9e66XAkUoCAhamcqd3gMfW3XJC7AOVTqk_Fj2c40hM-R0CsQGfuWAdQrtGhac2mbJRljaomJAF6BUz7RYxqvCqOm1a226Liyk6uXv3DlxPpqvFbV2XONqhlq1McVThWjcj7DT-uxY4-_NF8jQSOS63V29yCu8678CIYgd6IuQwfFLXKdSufhCLEXLWNrazwHQRRXEn4_Mg_kb1tjfubbBDjK2iXCvdRPdZgslfiTbcllzE6s5Vsf25B24jwAHCT7bBCGueixtwGn6A9oLFDCX5ba-IAP_xcN1UxQJ0wxEc2GeMcnO6OOfhKoyUuv7Utl-_mZaTEEvd1tFAAhTHffBEnbaoQna_4gbT1_fgIgPnJLdnbr0nszaCNNp6AblonAMlc1lwgn0p';

// Si es GET, mostramos un mini-formulario para pruebas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=UTF-8');

    // Ejemplo de payload con TODOS los campos obligatorios
    $example = [
      
        "NetworkId": "e3731a47-2f36-4c3f-9a19-5f95ac921268",
        "UseType": 0,
        "Type": 1,
        "TypeModelId": "1ab9115d-0c84-4b87-8b65-bc974ce2432e",
        "TypeModelDescription": "TAG RFID",
        "ProgramId": "4c56bc46-0553-43be-95d9-314a4dc70e0c",
        "ProgramDescription": "Classic",
        "IdCompany": "8d3196fc-fa7b-4759-9bc1-992b54823027",
        "ContractId": "9f23a881-1ab0-4b11-b6e0-19df5ba1ba4f",
        "ContractCode": "1122-2001-0000099",
        "Label": "1508-0000-0000-4897",
        "TrackNumber": "08117549 ✅",
        "PAN": "1508000000004897",
        "PIN": "1234",
        "RequiresPINChange": true,
        "Active": true
      
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Prueba Crear Identificación</title>
  <style>
    body { font-family:sans-serif; margin:20px; }
    textarea { width:100%; height:200px; font-family:monospace; }
    pre { background:#f4f4f4; padding:10px; }
    button { padding:6px 12px; margin-top:8px; }
  </style>
</head>
<body>
  <h1>TEST POST /identifications</h1>
  <p>Edita el JSON si quieres y pulsa <strong>Enviar</strong>:</p>
  <textarea id="payload"><?php 
    echo htmlspecialchars(json_encode($example, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); 
  ?></textarea>
  <br>
  <button id="send">Enviar</button>
  <h2>Respuesta:</h2>
  <pre id="response"></pre>
  <script>
    document.getElementById('send').onclick = () => {
      const payload = document.getElementById('payload').value;
      const respEl  = document.getElementById('response');
      respEl.textContent = 'Enviando…';
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer <?php echo $accessToken ?>'
        },
        body: payload
      })
      .then(async r => {
        const txt = await r.text();
        let out;
        try { out = JSON.stringify(JSON.parse(txt), null, 2); }
        catch { out = txt; }
        respEl.textContent = `HTTP ${r.status} ${r.statusText}\n\n${out}`;
      })
      .catch(err => {
        respEl.textContent = 'Error: ' + err;
      });
    };
  </script>
</body>
</html>
    <?php
    exit;
}

// Si es POST, enviamos la petición real a la API
header('Content-Type: application/json; charset=UTF-8');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error'=>'JSON inválido','details'=>json_last_error_msg()]);
    exit;
}

// Validar campos obligatorios
$required = [
  'NetworkId','UseType','Type','TypeModelId','TypeModelDescription',
  'ProgramId','ProgramDescription','IdCompany','ContractId','ContractCode',
  'Label','TrackNumber','PAN','PIN','RequiresPINChange','Active'
];
$missing = [];
foreach ($required as $f) {
  if (!isset($data[$f])) {
    $missing[] = $f;
  }
}
if ($missing) {
  http_response_code(422);
  echo json_encode(['error'=>'Faltan campos','missing'=>$missing]);
  exit;
}

// Llamada real a la API
$ch = curl_init('https://api-beta.ationet.com/identifications');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => [
    "Authorization: Bearer {$accessToken}",
    "Content-Type: application/json",
    "Accept: application/json"
  ],
  CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
]);
$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

http_response_code($err ? 500 : $code);
if ($err) {
  echo json_encode(['error'=>'cURL','detail'=>$err]);
} else {
  echo $response;
}
