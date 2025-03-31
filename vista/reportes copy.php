<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: /index.php");
    exit();
}

// Mostrar un spinner mientras se construye el reporte (ver HTML más abajo)
ob_start();

$tokenJson = file_get_contents('http://localhost/tokengas/auth/token_handler.php');
if ($tokenJson === false) die("Error: No se pudo obtener el token.");

$tokenData = json_decode($tokenJson, true);
if (!isset($tokenData['access_token'])) die("Error: Token inválido.");

$accessToken = $tokenData['access_token'];

/**
 * Función auxiliar para obtener litros totales de un día (00:00:00 a 23:59:59),
 * manejando TODAS LAS PÁGINAS para no perder transacciones.
 */
function obtenerLitrosDelDia($fecha, $token) {
    $litros = [
        'REGULAR' => 0.0,
        'PREMIUM' => 0.0,
        'DIESEL'  => 0.0
    ];

    $currentPage = 1;
    $totalPages  = 1;

    do {
        $url = "https://api.ationet.com/Transactions"
             . "?dateTimeFrom=" . urlencode($fecha . " 00:00:00")
             . "&dateTimeTo="   . urlencode($fecha . " 23:59:59")
             . "&page="         . $currentPage;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            break; // Error en la conexión
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            break; // Respuesta no válida
        }

        $totalPages = isset($data['TotalPages']) ? (int)$data['TotalPages'] : 1;

        if (isset($data['Content']) && is_array($data['Content'])) {
            foreach ($data['Content'] as $transaccion) {
                $volumen  = (float)($transaccion['ProductVolumeDispensed'] ?? 0);
                $fuelCode = $transaccion['FuelCode'] ?? null;

                switch ($fuelCode) {
                    case '10100': $litros['REGULAR'] += $volumen; break;
                    case '10300': $litros['PREMIUM'] += $volumen; break;
                    case '10400': $litros['DIESEL']  += $volumen; break;
                }
            }
        }

        $currentPage++;
    } while ($currentPage <= $totalPages);

    return $litros;
}

// 1) Vamos a obtener los litros para los últimos 3 martes y HOY
$litrosHistoricos = [];
for ($i = 3; $i >= 0; $i--) {
    // today - (N*7) days => 3 martes pasados y hoy
    $fecha = new DateTime("today -". ($i * 7) ." days");
    $fechaFormato = $fecha->format('Y/m/d');
    $litrosHistoricos[$fechaFormato] = obtenerLitrosDelDia($fechaFormato, $accessToken);
}

// 2) Calcular variaciones con respecto al promedio histórico
$productos  = ['REGULAR','PREMIUM','DIESEL'];
$hoy        = array_pop($litrosHistoricos); // Extraemos el día de hoy
$variaciones= [];

// Guardamos también info histórica para cada producto, para mostrar al hacer clic
// (los 3 fechas) => array con [fecha => litros].
$historialPorProducto = [];

foreach ($productos as $producto) {
    // Creamos un array con [fecha => litros] para mostrar al hacer clic
    $historialPorProducto[$producto] = [];
    foreach ($litrosHistoricos as $fecha => $litrosDia) {
        $historialPorProducto[$producto][$fecha] = $litrosDia[$producto];
    }

    // Calculamos promedio
    $historicoProducto = array_column($litrosHistoricos, $producto); // [val1, val2, val3]
    $promedioHistorico = 0;
    if (count($historicoProducto) > 0) {
        $promedioHistorico = array_sum($historicoProducto) / count($historicoProducto);
    }

    $litrosHoy = $hoy[$producto];
    if ($promedioHistorico == 0) {
        $variacion = ($litrosHoy > 0) ? 100.0 : 0.0;
    } else {
        $variacion = (($litrosHoy - $promedioHistorico) / $promedioHistorico) * 100;
    }

    $direccion = '➡️';
    if ($variacion > 0) $direccion = '📈';
    if ($variacion < 0) $direccion = '📉';

    $variaciones[$producto] = [
        'hoy'       => round($litrosHoy, 2),
        'variacion' => round($variacion, 2),
        'direccion' => $direccion
    ];
}

// Terminamos la parte de lógica. Ahora guardamos en buffer el HTML que generaremos.
$htmlContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KPIs: Litros (con Toggle Fechas + Spinner)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/tokengas/css/movimientos.css">
    <link rel="stylesheet" href="/tokengas/css/sidebar.css">
    <style>
    /* Spinner CSS */
    #spinner {
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1.5rem;
        color: #fff;
        background: rgba(0,0,0,0.7);
        padding: 1rem 2rem;
        border-radius: 8px;
        z-index: 9999;
    }
    </style>
</head>
<body>
<div id="spinner">Cargando reporte...</div>

<div class="d-flex" id="contenido" style="display:none;">
    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <main class="content p-4">
        <h1 class="text-light">KPIs: Litros Vendidos Hoy vs Promedio Histórico</h1>

        <div class="row">
        <?php foreach ($variaciones as $producto => $data): ?>
            <?php
            // Prepara la tabla HTML de los 3 días históricos
            // Ej: array('2025/02/28'=> 300, '2025/02/21'=> 250, ...)
            $detallesHTML = '';
            foreach ($historialPorProducto[$producto] as $fecha => $litrosVal) {
                $detallesHTML .= '<tr>'
                               . ' <td>' . htmlspecialchars($fecha) . '</td>'
                               . ' <td>' . number_format($litrosVal, 2) . ' L</td>'
                               . '</tr>';
            }
            ?>
            <div class="col-md-4 mb-3">
                <div class="card bg-dark text-light text-center">
                    <div class="card-body">
                        <h4>
                            <?php echo htmlspecialchars($producto); ?>
                            <!-- Ícono para desplegar info -->
                            <span style="cursor:pointer; font-size:1.2rem; margin-left:0.5rem;"
                                  onclick="toggleHistorial('<?php echo $producto; ?>');"
                                  title="Ver histórico de 3 días">
                                ℹ️
                            </span>
                        </h4>
                        <p style="font-size: 1.8rem;">
                            <strong><?php echo number_format($data['hoy'], 2); ?> litros</strong>
                        </p>
                        <p class="<?php echo ($data['variacion'] >= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo $data['direccion'] . ' ' . abs($data['variacion']); ?>%
                        </p>

                        <!-- Contenedor oculto con la info de las 3 fechas -->
                        <div id="historial-<?php echo $producto; ?>"
                             style="display:none; margin-top:1rem; text-align:left;">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Litros</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php echo $detallesHTML; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

    </main>
</div>

<script>
/**
 * Función para quitar el spinner y mostrar el contenido
 * la llamamos al final del body cuando todo esté listo
 */
function mostrarContenido() {
    document.getElementById('spinner').style.display = 'none';
    document.getElementById('contenido').style.display = 'flex';
}

/**
 * Alternar la tabla de históricos al hacer clic en ℹ️
 */
function toggleHistorial(producto) {
    const elem = document.getElementById('historial-' + producto);
    if (elem.style.display === 'none') {
        elem.style.display = 'block';
    } else {
        elem.style.display = 'none';
    }
}

// Esperamos a que cargue todo
window.addEventListener('load', function() {
    // Ocultamos spinner, mostramos contenido
    mostrarContenido();
});
</script>
</body>
</html>

