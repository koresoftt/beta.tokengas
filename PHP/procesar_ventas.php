<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sumar Litros por Fecha</title>
</head>
<body>
    <h1>Seleccionar Rango de Fechas</h1>
    <form method="POST" action="procesar_fechas.php">
        <label for="dateFrom">Fecha Desde:</label>
        <input type="date" id="dateFrom" name="dateFrom" required>
        <br><br>
        <label for="dateTo">Fecha Hasta:</label>
        <input type="date" id="dateTo" name="dateTo" required>
        <br><br>
        <button type="submit">Calcular Litros</button>
    </form>
</body>
</html>
