<?php
// 1. Mostrar errores para depurar
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---------------------------------------------------------------------
// 1. Modo AJAX: si viene ?ajax=companies, respondemos con JSON
// ---------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === 'companies') {
    header('Content-Type: application/json');

    // 1. Obtener el token desde tu token_handler_beta.php
    $tokenJson = file_get_contents('http://localhost/tokengas/auth/token_handler.php');
    if ($tokenJson === false) {
        echo json_encode([]);
        exit;
    }

    $tokenData = json_decode($tokenJson, true);
    if (!isset($tokenData['access_token'])) {
        echo json_encode([]);
        exit;
    }
    $accessToken = $tokenData['access_token'];

    // 2. Iterar sobre las páginas para obtener TODAS las compañías
    $allCompanies = [];
    $page = 1;
    $limit = 50; // Ajusta si la API permite más (ej. 100, 200)

    while (true) {
        // Construimos la URL con paginación
        $url = "https://api.ationet.com/companies?page={$page}&limit={$limit}";

        // Hacemos cURL
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $accessToken"
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Si la respuesta no es 200 o está vacía, paramos
        if ($httpCode !== 200 || !$response) {
            echo json_encode([]);
            exit;
        }

        // Decodificamos el JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([]);
            exit;
        }

        // Verificamos que exista 'Content'
        if (!isset($data['Content']) || !is_array($data['Content'])) {
            echo json_encode([]);
            exit;
        }

        $content = $data['Content'];
        if (empty($content)) {
            // Si ya no hay más datos, salimos
            break;
        }

        // Agregamos las compañías de esta página
        $allCompanies = array_merge($allCompanies, $content);

        $page++;
    }

    // 3. Filtrar por término (si el usuario escribe algo)
    $searchTerm = isset($_GET['term']) ? mb_strtolower($_GET['term']) : '';
    $results = [];

    foreach ($allCompanies as $company) {
        $id   = $company['Id']   ?? null;
        $name = $company['Name'] ?? '';

        if ($searchTerm === '' || strpos(mb_strtolower($name), $searchTerm) !== false) {
            $results[] = [
                'id'   => $id,
                'name' => $name
            ];
        }
    }

    // 4. Devolvemos en JSON
    echo json_encode($results);
    exit;
}

// ---------------------------------------------------------------------
// 2. Modo HTML: si NO es ?ajax=companies, mostramos la página
// ---------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda de Compañías</title>
    <!-- jQuery y jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
</head>
<body>
<h1>Búsqueda de Compañías</h1>

<label for="compania">Compañía:</label>
<input type="text" id="compania" placeholder="Escribe el nombre de la compañía...">

<!-- Aquí el div donde se mostrará el total de compañías -->
<div id="companyCount" style="margin-top: 1rem; font-weight: bold;"></div>

<!-- Referenciamos el archivo depositos.js -->
<script src="/tokengas/js/depositos.js"></script>

</body>
</html>
