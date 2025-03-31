<?php
// 1. Mostrar errores para depurar
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Obtener el token desde token_handler.php
$tokenJson = file_get_contents('http://localhost/tokengas/auth/token_handler.php');
if ($tokenJson === false) {
    die("❌ No se pudo conectar con token_handler.php");
}

$tokenData = json_decode($tokenJson, true);
if (!isset($tokenData['access_token'])) {
    die("❌ No se encontró 'access_token' en la respuesta de token_handler.php");
}
$accessToken = $tokenData['access_token'];

// 3. Iterar sobre las páginas de 'companies'
$allCompanies = [];
$page = 1;
$limit = 50; // Ajusta si la API permite un valor mayor

while (true) {
    // Construimos la URL con paginación
    $url = "https://api.ationet.com/companies?page={$page}&limit={$limit}";
    // Opcional: podrías agregar &orderField=name&orderType=asc si la API lo soporta

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

    // 4. Validar la respuesta HTTP
    if ($httpCode !== 200 || !$response) {
        die("❌ Error en la solicitud (página {$page}). Código HTTP: $httpCode");
    }

    // 5. Decodificar el JSON
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("❌ Error al decodificar el JSON en la página {$page}");
    }

    // 6. Verificar que exista 'Content'
    if (!isset($data['Content']) || !is_array($data['Content'])) {
        die("❌ No se encontró 'Content' o no es un array en la página {$page}");
    }

    $content = $data['Content'];

    // Si la página no trae datos, terminamos
    if (empty($content)) {
        break;
    }

    // 7. Agregamos las compañías de esta página a $allCompanies
    $allCompanies = array_merge($allCompanies, $content);

    $page++;
}

// 8. Listar los nombres de todas las compañías
// Cabecera HTML básica
echo "<!DOCTYPE html>";
echo "<html lang='es'><head><meta charset='UTF-8'><title>Lista de Compañías</title></head><body>";

echo "<h1>Lista de Compañías</h1>";
echo "<p>Total de compañías encontradas: <strong>" . count($allCompanies) . "</strong></p>";

echo "<ul>";
foreach ($allCompanies as $company) {
    // 'Name' puede ser null o no existir, así que validamos
    $name = isset($company['Name']) ? $company['Name'] : 'Sin nombre';
    echo "<li>" . htmlspecialchars($name) . "</li>";
}
echo "</ul>";

// Cierre HTML
echo "</body></html>";
