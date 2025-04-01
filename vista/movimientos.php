<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: /index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos para la tabla de respuesta del API */
        #api-response-table {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        #api-response-table th,
        #api-response-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        #api-response-table th {
            background-color: #f2f2f2;
        }

        .btn-month {
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1>Seleccionar Mes y Año</h1>

        <div class="mb-3">
            <h5>Mes:</h5>
            <?php
            $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            foreach ($meses as $index => $mes) {
                $mesNum = str_pad($index + 1, 2, "0", STR_PAD_LEFT);
                echo "<button class='btn btn-outline-primary btn-month' data-mes='$mesNum'>$mes</button>";
            }
            ?>
        </div>

        <div class="mb-3">
            <h5>Año:</h5>
            <select id="select-anio" class="form-select" style="width: auto;">
                <option value="2025" selected>2025</option>
                <option value="2024">2024</option>
                <option value="2023">2023</option>
            </select>
        </div>

        <button id="btn-enviar" class="btn btn-primary">Enviar</button>

        <table id="api-response-table" class="table table-bordered" style="display: none;">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Contrato</th>
                    <th>Compañía</th>
                    <th>Tipo</th>
                    <th>Descripcion</th>
                    <th>Monto</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody id="movements-tbody">
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let mesSeleccionado = null;
            let anioSeleccionado = document.getElementById('select-anio').value; // Inicializar con el año seleccionado

            document.querySelectorAll('.btn-month').forEach(button => {
                button.addEventListener('click', function() {
                    mesSeleccionado = this.getAttribute('data-mes');
                    document.querySelectorAll('.btn-month').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            document.getElementById('select-anio').addEventListener('change', function() {
                anioSeleccionado = this.value; // Actualizar anioSeleccionado al cambiar el año
            });

            document.getElementById('btn-enviar').addEventListener('click', function() {
                if (!mesSeleccionado) {
                    alert('Por favor, selecciona un mes.');
                    return;
                }

                const url = `../PHP/APImovimientos.php?mes=${mesSeleccionado}&year=${anioSeleccionado}`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.movements) {
                            mostrarMovimientosEnTabla(data.movements);
                        } else {
                            alert('Error al obtener movimientos: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error de red:', error);
                        alert('Error de red. Por favor, inténtalo de nuevo.');
                    });
            });


            function mostrarMovimientosEnTabla(movements) {
              const table = document.getElementById('api-response-table');
              const tbody = document.getElementById('movements-tbody');

              tbody.innerHTML = ''; // Limpiar la tabla
              if (movements.length === 0) {
                 tbody.innerHTML = '<tr><td colspan="7">No se encontraron movimientos.</td></tr>';
                 table.style.display = 'table';
                 return;
              }

              movements.forEach(movement => {
                  const row = document.createElement('tr');
                  row.innerHTML = `
                      <td>${movement.Fecha || 'N/A'}</td>
                      <td>${movement.Código_de_Contrato || 'N/A'}</td>
                      <td>${movement.Compañía || 'N/A'}</td>
                      <td>${movement.Tipo_de_Movimiento || 'N/A'}</td>
                      <td>${movement.Descripción_del_Movimiento || 'N/A'}</td>
                      <td>${movement.Monto || 'N/A'}</td>
                      <td>${movement.Resultado || 'N/A'}</td>
                  `;
                  tbody.appendChild(row);
              });
              table.style.display = 'table';
          }
        });
    </script>
</body>

</html>