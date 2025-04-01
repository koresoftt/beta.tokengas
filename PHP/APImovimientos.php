<?php
header('Content-Type: application/json');
session_start();

/**
 * Maneja la respuesta de error de la API.
 *
 * @param string $mensaje Mensaje de error.
 * @param int $codigo Código de estado HTTP (opcional, por defecto 500).
 */
function respondWithError($mensaje, $codigo = 500) {
    http_response_code($codigo);
    echo json_encode(["status" => "error", "message" => $mensaje]);
    exit;
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
    $dateFrom = "$year/$mes/01 00:00:00";
    $dateTo = "$year/$mes/$ultimoDia 23:59:59";

    error_log("apimovimientos.php - obtenerFechaMes - dateFrom: " . $dateFrom);
    error_log("apimovimientos.php - obtenerFechaMes - dateTo: " . $dateTo);

    return [$dateFrom, $dateTo];
}

/**
 * Obtiene los movimientos para un contrato dado dentro de un rango de fechas.
 *
 * @param string $idContrato ID del contrato.
 * @param string $dateFrom Fecha inicio (formato "YYYY/MM/DD HH:MM:SS").
 * @param string $dateTo Fecha fin (formato "YYYY/MM/DD HH:MM:SS").
 * @param string $token Token de acceso.
 * @return array|false Lista de movimientos o false en caso de error.
 */
function obtenerMovimientosContrato($idContrato, $dateFrom, $dateTo, $token) {
    $urlBase = "https://api.ationet.com/ContractsMovements";
    $page = 1;
    $pageSize = 100;
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
            "order" => "desc",
            "page" => $page,
            "pageSize" => $pageSize,
        ]);

        error_log("apimovimientos.php - obtenerMovimientosContrato - URL: " . $url);
        error_log("apimovimientos.php - obtenerMovimientosContrato - idContrato: " . $idContrato);
        error_log("apimovimientos.php - obtenerMovimientosContrato - dateFrom: " . $dateFrom);
        error_log("apimovimientos.php - obtenerMovimientosContrato - dateTo: " . $dateTo);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,  // **CUIDADO:  Cambiar a true en producción**
            CURLOPT_SSL_VERIFYHOST => false,  // **CUIDADO:  Cambiar a true en producción**
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $curlError = curl_error($curl);
            error_log("apimovimientos.php - obtenerMovimientosContrato - Error cURL: " . $curlError);
            curl_close($curl);
            return false;
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        error_log("apimovimientos.php - obtenerMovimientosContrato - HTTP Code: " . $httpCode);

        if ($httpCode !== 200) {
            error_log("apimovimientos.php - obtenerMovimientosContrato - Error HTTP $httpCode: " . $response);
            curl_close($curl);
            return false;
        }

        $data = json_decode($response, true);
        if (!isset($data['Content']) || !is_array($data['Content'])) {
            error_log("apimovimientos.php - obtenerMovimientosContrato - Error en la respuesta de la API: " . $response);
            curl_close($curl);
            return false;
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

// --------------------------------------------------------------------------
//  MAIN LOGIC
// --------------------------------------------------------------------------

// 1. Validar parámetros GET: se espera 'mes' y 'year'
if (!isset($_GET['mes'], $_GET['year']) || !checkdate((int)$_GET['mes'], 1, (int)$_GET['year'])) {
    respondWithError("Parámetros inválidos");
}

$mes = str_pad((int)$_GET['mes'], 2, '0', STR_PAD_LEFT);
$year = (int)$_GET['year'];

// 2. Obtener rango de fechas para el mes solicitado
list($dateFrom, $dateTo) = obtenerFechaMes($year, $mes);

// 3. Verificar si el access_token existe en la sesión
if (!isset($_SESSION['access_token'])) {
    respondWithError("No se pudo obtener el token", 401); // 401 Unauthorized
}

$token = $_SESSION['access_token'];

// 4. Obtener IDs de contratos (Debes implementar tu lógica real)
//    En este ejemplo, se simula la obtención de IDs.
function obtenerIdsContratos() {
    // **IMPORTANTE:** Reemplaza esta simulación con tu código real.
    return [
        "6c203773-7ffd-4fe9-ba75-e51366b8c9b7",
        "3efab8-088c-4285-b9a8-27a695e1e75e",
        // ... otros IDs ...
    ];
}
$idsContratos = obtenerIdsContratos();
if (empty($idsContratos)) {
    respondWithError("No se encontraron contratos");
}

// 5. Obtener movimientos de cada contrato dentro del rango de fechas
$movimientosTotales = [];
foreach ($idsContratos as $idContrato) {
    $movimientos = obtenerMovimientosContrato($idContrato, $dateFrom, $dateTo, $token);
    if ($movimientos === false) {
        respondWithError("Error al obtener movimientos para el contrato $idContrato", 500);
    } else {
        $movimientosTotales = array_merge($movimientosTotales, $movimientos);
    }
}

// 6. Respuesta final en JSON
if (empty($movimientosTotales)) {
    echo json_encode(["status" => "error", "message" => "No se encontraron movimientos"]);
} else {
    echo json_encode(["status" => "success", "movements" => $movimientosTotales], JSON_PRETTY_PRINT);
}
?>