<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Envía una respuesta JSON de error y finaliza la ejecución.
 *
 * @param string $message Mensaje de error.
 * @param int $httpCode Código HTTP (por defecto 400).
 */
function respondWithError($message, $httpCode = 400) {
    http_response_code($httpCode);
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

/**
 * Obtiene el token de acceso llamando al endpoint remoto.
 * Se espera que el endpoint devuelva JSON con el campo 'access_token'.
 *
 * @return string|null El token de acceso o null en caso de error.
 */
function obtenerToken() {
    $url = "https://beta.tokengas.com.mx/auth/token_handler.php";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        // En producción es recomendable habilitar la verificación SSL
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        error_log("Error cURL al obtener token: " . curl_error($curl));
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

/**
 * Obtiene los IDs de los contratos mediante paginación.
 *
 * @param string $token Token de acceso.
 * @return array Lista de IDs de contrato.
 */
function obtenerIdsContratos($token) {
    $ids = [];
    $page = 1;
    $headers = ["Authorization: Bearer $token"];

    while (true) {
        $url = "https://api.ationet.com/CompanyContracts?page=$page&limit=50";
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            error_log("Error cURL al obtener contratos: " . curl_error($curl));
            curl_close($curl);
            break;
        }
        curl_close($curl);

        $data = json_decode($response, true);
        if (empty($data['Content'])) {
            break;
        }
        foreach ($data['Content'] as $item) {
            if (isset($item['Id'])) {
                $ids[] = $item['Id'];
            }
        }
        $page++;
    }
    return $ids;
}

/**
 * Obtiene los movimientos para un contrato dado dentro de un rango de fechas.
 *
 * @param string $idContrato ID del contrato.
 * @param string $dateFrom Fecha inicio (formato "YYYY/MM/DD HH:MM:SS").
 * @param string $dateTo Fecha fin (formato "YYYY/MM/DD HH:MM:SS").
 * @param string $token Token de acceso.
 * @return array Lista de movimientos.
 */
function obtenerMovimientos($idContrato, $dateFrom, $dateTo, $token) {
    $urlBase = "https://api.ationet.com/Movements";
    $page = 1;
    $pageSize = 100; // Tamaño de página configurable
    $movimientosTotales = [];
    $headers = [
        "Authorization: Bearer $token",
        "Content-Type: application/json",
    ];

    $curl = curl_init();
    while (true) {
        $url = $urlBase . "?" . http_build_query([
            "idContract" => $idContrato,
            "dateFrom" => $dateFrom,
            "dateTo" => $dateTo,
            "amountFrom" => 0,
            "amountTo" => 100000000,
            "operationType" => "Money deposit to contract",
            "orderType" => "desc",
            "page" => $page,
            "pageSize" => $pageSize,
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            error_log("Error cURL: " . curl_error($curl));
            break;
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            error_log("Error HTTP $httpCode: " . $response);
            break;
        }

        $data = json_decode($response, true);
        if (!isset($data['Content']) || !is_array($data['Content'])) {
            error_log("Error en la respuesta de la API: " . $response);
            break;
        }

        $movimientosTotales = array_merge($movimientosTotales, $data['Content']);

        if (count($data['Content']) < $pageSize) {
            break;
        }
        $page++;
    }
    curl_close($curl);
    return $movimientosTotales;
}

/**
 * Calcula el rango de fechas para un mes específico.
 *
 * @param int $year Año.
 * @param int $mes Mes.
 * @return array [fechaInicio, fechaFin] en formato "YYYY/MM/DD HH:MM:SS".
 */
function obtenerFechaMes($year, $mes) {
    $ultimoDia = date("t", strtotime("$year-$mes-01"));
    return ["$year/$mes/01 00:00:00", "$year/$mes/$ultimoDia 23:59:59"];
}

// Validar parámetros GET: se espera 'mes' y 'year'
if (!isset($_GET['mes'], $_GET['year']) || !checkdate((int)$_GET['mes'], 1, (int)$_GET['year'])) {
    respondWithError("Parámetros inválidos");
}

$mes = str_pad((int)$_GET['mes'], 2, '0', STR_PAD_LEFT);
$year = (int)$_GET['year'];

// Obtener rango de fechas para el mes solicitado
list($dateFrom, $dateTo) = obtenerFechaMes($year, $mes);

// Obtener el token desde la sesión si existe y no está expirado
$token = $_SESSION['access_token'] ?? null;
if (!$token || time() > ($_SESSION['token_expiry'] ?? 0)) {
    $token = obtenerToken();
    if (!$token) {
        respondWithError("No se pudo obtener el token", 500);
    }
    $_SESSION['access_token'] = $token;
    // Establece la expiración del token en 1 hora (3600 segundos). Ajusta si es necesario.
    $_SESSION['token_expiry'] = time() + 3600;
}

// Obtener IDs de contratos utilizando el token
$idsContratos = obtenerIdsContratos($token);
if (empty($idsContratos)) {
    respondWithError("No se encontraron contratos");
}

// Obtener movimientos de cada contrato dentro del rango de fechas
$movimientosTotales = [];
foreach ($idsContratos as $idContrato) {
    $movimientos = obtenerMovimientos($idContrato, $dateFrom, $dateTo, $token);
    $movimientosTotales = array_merge($movimientosTotales, $movimientos);
}

// Respuesta final en JSON
if (empty($movimientosTotales)) {
    echo json_encode(["status" => "error", "message" => "No se encontraron movimientos"]);
} else {
    echo json_encode(["status" => "success", "movements" => $movimientosTotales], JSON_PRETTY_PRINT);
}
?>
