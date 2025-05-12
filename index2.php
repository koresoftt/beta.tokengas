<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 🔐 Token válido y completo
$_SESSION['access_token'] = '3MiNmZ1PoCA7zqR9G1REfLzxR5GIUQVq7PT0OToBnyod1fueZCqT3f-KK4tarL5i5_t9Y-S5pcAdTbCfWce4wMFn613TF82nRb73XIY5Emm6j6gACFftDKhPKWUkLCkATDEC3IsHK-CMCWRStVOXfBUyrONr7HV7l4mnbFXA_dkJYNnTx5A82dn-T5G6PT_0-lUzofMicvqd8_ym6_AjVgwE4_z78QpbEcC1_33BYhOf9Iwi5ySm-FHLh02r2WOjNqY77pbhDHsK6frspP0ylCMrOkfXIjYyefG3akNflvn47nr67zD4faRNFz41FCGLUa9FCMvAUiDqaEaS3NIRvRecD3GssRVGO3z__LAV-qJW40j3h36A3vlyFYd2XjbB7FP6JkRL5Tj6FYz42B7KSt5ozbvcG1fbdsm7bAwp948C6wlyDykfoXq0lZBfj2-_sJkWhYzrjYZ3e_yeP8gw0AU2SdzH0zCmwQqj4L6JNf3f57F_31aTcZ10VE1BAhSw42d51GcVEQHfOKIRY9-zYIkkI7On8f-7yL6scByoap8Wt5sS6UzOx0gv1Pmvqney1pKOk6X_fhHHEE66SQoeC1Jaf4bHuIo5edemyTaTjyX1pyZ1oW5tjalYRaOyThzRaTlM5T3dysoAscsmBRivpftjL_78sf3hMSzBLd8HX-q_gSxEEA8LMp89moh1TjF2QXw41BM_5UJlPeEuDPo3m7cFy3lvPoP01VinD9f233AUy7ptdKWKn6W0RPbBKv-w2qJcWJY7tXYLG5qsww9gGaomhVCCizFasFtcelkgzRZdfFxJlsmrCbmnQCWmFHnCl_EX7zkpCPwW9Ow7sMaAtWJLK12Tr3GfIgkFOZlI_4O767fwMGEVeR53-wXJ1fPPePNFic0L4nd05pfMBzhE4-E7Wehv2puyRYFN72mxVDgd2e9WPXPyyjMMA_GFM_buYqUHc52D_fqu_RbwHrEyIEb6Ml6U5GH54wt5C7aV879sh_tRHy62ovng-0p5xIuAK0Y9uS2zbeAWXCllA1sLVHjD8NqTipk5DgofgiX92oVyF2uHQH7pedLnxLAHBV6afBqejVB7B50yTYRLk_PSx6PAqxmDOsaBKJ6pE7jHVpCZoJNr6mNs2_bPxx1pYvQAZsJ7zQH0FYu8-Mek8ml6VOs0QDDLu8CCFfzJtIWy0H06LkaZAeiqEZisp4nh1QUUB4AVHDrqHWkz7aam6bVqtLXduXn1Y3v3ae33tCL5rQGnNL-uhMiDGzL2gSiYBehnYgcdS8Nd_B9gPzW-tt8pdsbEIRiKTktcQomY0oENCUBy0NL3h5v-VNef8XxxO5kzXO2mChKMcC5tr97vHmjXqUs3u09H1MMJY807rnZG9MYCsCIDSaGaJpCdedRUuPv7rooy1tkbGh4OPb-1Blgkf74AcjcahsErsKar5PKAb0xIwc--7wIJwGypmVhTeUijk7GpQSIpywaoK5dN-4hnXzWV6VVE8iEXQe9Ix7L760tQKbglcwi8hKSq8BiVyfmc91aJHigay-6fd3czkYLOvXKVE0Lt_NlQelEt2hywBs4Io4n80ffv9V2hbxFPdkuZx6Bh3uBXbvYXVr1jjtdAFn1iDs9DYUS6u4OTd__G54acMoOdKQxWQW5XQh2I_pIx2kDPV-0XY6E-N0Hcdhd7fGiX3V6M5Uuvn8-5Lxyto_Oqj5XcWdpR00yqjbAikel0KEoFk-GJKLZfiMPiOxvqoaKCpJw3Cm9v6MSBKNZRL9UgkgI9hHHMJJqWgMskzXZQQ1IN0heubMyAZ72RI-Figr003x6EAWgcL5vRYRiXX0-n_DPVpvLGfhxlTKhQIRTvJqQWC5BKkV_gP-wVN1h4DZeoUQkVWIypvjh7jyZCe710aSVvcy5Gi2mE9UKj-jeL967fi3m_KgA_xvPGZPg-qiV3pNvtwGjuU6DL3yPErIDwcHwsMxQrZH2isl2IRri2m54TLGB2A59GAgJZpVBITFZm9x90Zw763yDoYLz8moXJR43fI-M6WN86pAeirCdahZA9k3sdE7IZ5NlUmhrtLnN7R2V-mKzcGRwfI_g8OTFuZFu7FEoVAzttDkQbGD5R';

$apiBase = 'https://api-beta.ationet.com';

if (isset($_GET['ajax']) && $_GET['ajax'] === 'identifications') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['access_token'])) {
        echo json_encode(['error' => 'Token no encontrado en sesión']);
        exit;
    }

    $label = $_GET['label'] ?? '';
    $track = $_GET['track'] ?? '';
    if (!$label && !$track) {
        echo json_encode(['error' => 'Se requiere label o track']);
        exit;
    }

    $accessToken = $_SESSION['access_token'];
    $existe = false;
    $page = 1;

    do {
        $queryParams = [
            'contract[0]' => null,
            'subAccount[0]' => null,
            'loyaltyPrograms[0]' => null,
            'loyaltyAccounts[0]' => null,
            'types[0]' => null,
            'state[0]' => null,
            'page' => $page,
            'pageSize' => 100
        ];

        if ($label) $queryParams['label'] = $label;
        if ($track) $queryParams['trackNumber'] = $track;

        $query = http_build_query($queryParams);
        $ch = curl_init("{$apiBase}/Identifications?$query");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $accessToken",
                "Accept: application/json"
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            echo json_encode([
                'error' => 'Error al consultar identificaciones',
                'httpCode' => $httpCode,
                'response' => $response
            ]);
            exit;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['Content'])) {
            echo json_encode(['error' => 'Respuesta no válida o vacía']);
            exit;
        }

        foreach ($data['Content'] as $item) {
            if (($label && ($item['Label'] ?? '') === $label) || ($track && ($item['TrackNumber'] ?? '') === $track)) {
                $existe = true;
                break 2;
            }
        }

        $page++;
    } while (!empty($data['Content']));

    echo json_encode(['exists' => $existe]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Validar Etiqueta y Track</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .valid-feedback, .invalid-feedback {
      display: block;
    }
  </style>
</head>
<body class="p-4">

  <h4 class="mb-4">Validación de Identificadores</h4>

  <div class="mb-3">
    <label for="etiqueta" class="form-label">Etiqueta</label>
    <input type="text" class="form-control etiqueta-input" id="etiqueta" placeholder="Ej: 1508-0000-0000-4411">
  </div>

  <div class="mb-3">
    <label for="track" class="form-label">TRACK (UID)</label>
    <input type="text" class="form-control track-input" id="track" placeholder="Ej: 45121">
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    $(function () {
      function validar(ajaxParam, valor, tipoCampo, inputSelector) {
        const $input = $(inputSelector);
        $.ajax({
          url: 'index2.php',
          method: 'GET',
          dataType: 'json',
          data: {
            ajax: 'identifications',
            [ajaxParam]: valor
          },
          success: function (data) {
            $input.removeClass('is-valid is-invalid');
            $input.next('.valid-feedback, .invalid-feedback').remove();

            if (data.exists) {
              $input.addClass('is-invalid');
              $input.after(`<div class="invalid-feedback">${tipoCampo} ya existe ❌</div>`);
            } else {
              $input.addClass('is-valid');
              $input.after(`<div class="valid-feedback">${tipoCampo} disponible ✅</div>`);
            }
          },
          error: function (xhr, status, error) {
            console.error(`Error validando ${tipoCampo}:`, error);
          }
        });
      }

      $(document).on('blur', '.etiqueta-input', function () {
        const valor = $(this).val().trim();
        if (valor) validar('label', valor, 'Etiqueta', this);
      });

      $(document).on('blur', '.track-input', function () {
        const valor = $(this).val().trim();
        if (valor) validar('track', valor, 'TRACK', this);
      });
    });
  </script>

</body>
</html>
