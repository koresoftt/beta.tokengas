<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fecha programada para hoy
    $dateFrom = date("Y/m/d") . " 00:00:00"; // Fecha con hora inicial del día
    $dateTo = date("Y/m/d") . " 23:59:59";   // Fecha con hora final del día

    // Ruta completa al script Python
    $pythonScript = "C:\laragon\www\tokengas\Python\api.py";

    // Ruta completa al ejecutable de Python
    $pythonInterpreter = "C:\Python39\python.exe"; // Cambia esta ruta según la instalación de Python en tu sistema

    // Construir el comando para ejecutar el script Python con las fechas
    $command = escapeshellcmd("$pythonInterpreter $pythonScript '$dateFrom' '$dateTo' 2>&1");

    // Ejecutar el comando y capturar la salida
    $output = shell_exec($command);

    // Manejar la salida del script Python
    if ($output === null || empty($output)) {
        $result = "Error: No se pudo ejecutar el script Python. Verifica las rutas, permisos y configuración.";
    } else {
        $result = $output;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejecutar Script Python</title>
</head>
<body>
    <h1>Ejecutar Script Python para la Fecha de Hoy</h1>
    <form method="POST" action="">
        <button type="submit">Ejecutar</button>
    </form>

    <?php if (isset($result)): ?>
        <h2>Resultados:</h2>
        <pre><?php echo htmlspecialchars($result); ?></pre>
    <?php endif; ?>
</body>
</html>
