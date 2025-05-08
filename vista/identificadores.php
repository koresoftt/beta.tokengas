<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Identificadores</title>
  <link rel="stylesheet" href="../css/adminlte.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
    th { background-color: #f2f2f2; }
    input, select { width: 100%; }
  </style>
</head>
<body>
<div class="container mt-4">
  <h2>IDENTIFICADORES</h2>
  <table>
    <thead>
      <tr>
        <th>COMPAÑÍA</th>
        <th>CONTRATO</th>
        <th>TIPO</th>
        <th>ETIQUETA</th>
        <th>TRACK (UID)</th>
        <th>NIP</th>
        <th>REQUIERE CAMBIO DE NIP</th>
        <th>ACCIÓN</th>
      </tr>
    </thead>
    <tbody id="tbody-form">
      <!-- JS insertará aquí -->
    </tbody>
  </table>
  <div class="mt-3">
    <button class="btn btn-success" onclick="agregarFila()">➕ Agregar tarjeta</button>
    <button class="btn btn-primary">Guardar</button>
    <button class="btn btn-secondary">Cancelar</button>
  </div>
</div>
<script src="../js/depositos.js"></script>
</body>
</html>
