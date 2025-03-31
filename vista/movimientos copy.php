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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/tokengas/css/movimientos.css">
  <link rel="stylesheet" href="/tokengas/css/sidebar.css">
  <!-- Estilos para Spinner, Filtros y Resumen -->
  <style>
    /* Spinner dentro de la tabla */
    #spinner-row {
      text-align: center;
      font-size: 1.2em;
      color: #fff;
    }
    #spinner-icon {
      display: inline-block;
      width: 30px;
      height: 30px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin-right: 10px;
      vertical-align: middle;
    }
    @keyframes spin {
      100% { transform: rotate(360deg); }
    }
    /* Input de filtro, inicialmente oculto y deshabilitado */
    #tabla-filtro {
      display: none;
      max-width: 200px;
    }
    /* Resumen de resultados en línea */
    #resumenResultados {
      margin-bottom: 1rem;
    }
    /* Icono refrescar en el resumen */
    #refreshSummary {
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <?php include __DIR__ . '/layout/sidebar.php'; ?>
    <!-- Contenido principal -->
    <main class="content p-4">
      <div id="movimientos">
        <h1 class="text-light">Movimientos</h1>
        <!-- Selección de año -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="text-light">Selecciona el año:</h5>
            <select id="select-anio" class="form-select w-auto">
              <option value="2025" selected>2025</option>
              <option value="2024">2024</option>
            </select>
          </div>
        </div>
        <!-- Botones de mes y controles de filtro -->
        <div class="mb-3 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="text-light">Selecciona un mes:</h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <?php
              $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
              foreach ($meses as $index => $mes) {
                  $mesNum = str_pad($index + 1, 2, "0", STR_PAD_LEFT);
                  echo "<button class='btn btn-month' data-mes='$mesNum'>$mes</button>";
              }
              ?>
              <!-- Botón de filtro (inicialmente deshabilitado) -->
              <button id="btn-filter" class="btn btn-outline-secondary" title="Filtrar" disabled>
                <ion-icon name="search-outline"></ion-icon>
              </button>
              <!-- Input de filtro (inicialmente oculto y deshabilitado) -->
              <input type="text" id="tabla-filtro" class="form-control" placeholder="Filtrar..." disabled>
              <!-- Select para filtrar por resultado (inicialmente deshabilitado) -->
              <div class="d-flex align-items-center">
                <label for="resultadoFiltro" class="text-light me-2 mb-0">Filtrar por resultado:</label>
                <select id="resultadoFiltro" class="form-select w-auto" style="max-width: 100px;" disabled>
                  <option value="todos" selected>Todos</option>
                  <option value="✅">✅</option>
                  <option value="⚠️">⚠️</option>
                  <option value="❌">❌</option>
                </select>
              </div>
            </div>
          </div>
          <button id="ver-logs-btn" class="btn btn-warning" disabled>Log</button>
        </div>
        <!-- Resumen de resultados con icono de refresco a la derecha -->
        <div id="resumenResultados" class="mb-3 d-flex justify-content-between align-items-center text-light">
          <div>
            <span id="ingresoTotalVerificado">Ingreso total verificado: $0.00</span>
            <span id="comisionesCobradas" class="ms-3">Comisiones Cobradas: $0.00</span>
          </div>
          <!-- Icono de refresco para actualizar el resumen y buscar nuevos movimientos -->
          <span id="refreshSummary" title="Buscar nuevos movimientos">
            <ion-icon name="refresh-outline"></ion-icon>
          </span>
        </div>
        <!-- Tabla de movimientos -->
        <div class="table-container mt-4">
          <div class="d-flex justify-content-end mb-3">
            <button id="cargar-csv-btn" class="btn btn-secondary me-2">Cargar CSV</button>
            <button id="exportar-excel-btn" class="btn btn-success me-2">
              <img src="/tokengas/assets/icono_excel.png" alt="Exportar a Excel" class="icono-excel"> Exportar
            </button>
            <button id="generar-poliza-btn" class="btn btn-primary">Generar Póliza</button>
          </div>
          <table class="table table-bordered" id="movements-table">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Código de Contrato</th>
                <th>Compañía</th>
                <th>Tipo de Movimiento</th>
                <th>Descripción del Movimiento</th>
                <th>Monto</th>
                <th>Resultado</th>
              </tr>
            </thead>
            <tbody id="movements-list">
              <!-- Fila del Spinner (inicialmente oculta) -->
              <tr id="spinner-row" style="display: none;">
                <td colspan="7">
                  <span id="spinner-icon"></span> Cargando movimientos...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  <!-- Input oculto para cargar CSV -->
  <input type="file" id="csvInput" accept=".csv" style="display: none;">
  <!-- Modal personalizado para Log -->
  <div id="logModal" style="display: none; position: fixed; top: 30%; left: 50%; transform: translate(-50%, -50%); background-color: #333; padding: 20px; border-radius: 8px; z-index: 2000; text-align: center;">
    <h3 style="color: #fff;">Selecciona una opción</h3>
    <p style="color: #fff;">¿Deseas ver el Excel en pantalla o descargarlo?</p>
    <button id="viewExcelBtn" class="btn btn-primary me-2">Ver en pantalla</button>
    <button id="downloadExcelBtn" class="btn btn-success">Descargar Excel</button>
    <button id="closeModalBtn" class="btn btn-secondary ms-2">Cerrar</button>
  </div>
  <!-- Scripts externos -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <script defer src="/tokengas/js/sidebar.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script src="/tokengas/js/mostrarmovimientos.js"></script>
  <script src="/tokengas/js/compararmovimentos.js"></script>
  <script src="/tokengas/js/recompararmovimientos.js"></script>
  <script src="/tokengas/js/cargarCSV.js"></script>
  <script src="/tokengas/js/generarpoliza.js"></script>
  <script>
    // Función para fusionar movimientos nuevos con los guardados en localStorage.
    function fusionarMovimientos(nuevosMovimientos) {
      let movimientosGuardados = JSON.parse(localStorage.getItem("movimientosData")) || [];
      nuevosMovimientos.forEach(nuevoMov => {
        const index = movimientosGuardados.findIndex(mov => mov.id === nuevoMov.id);
        if (index === -1) {
          movimientosGuardados.push(nuevoMov);
        } else {
          movimientosGuardados[index] = { ...movimientosGuardados[index], ...nuevoMov };
        }
      });
      localStorage.setItem("movimientosData", JSON.stringify(movimientosGuardados));
      return movimientosGuardados;
    }

    // Función para cargar movimientos desde localStorage
    function cargarPersistencia() {
      const movimientosGuardados = JSON.parse(localStorage.getItem("movimientosData")) || [];
      if (movimientosGuardados.length > 0) {
        mostrarMovimientos(movimientosGuardados);
      }
    }

    // Actualizar movimientos: obtiene nuevos desde la API y los fusiona
    function actualizarMovimientos() {
      const anio = document.getElementById("select-anio").value;
      const mes = mesSeleccionado || "01"; // Asegúrate de que 'mesSeleccionado' se actualice al hacer clic en los botones de mes
      const url = /PHP/APImovimientos.php?accion=obtener_movimientos&mes=${mes}&year=${anio};
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.status === "success" && Array.isArray(data.movements)) {
            const movimientosFusionados = fusionarMovimientos(data.movements);
            mostrarMovimientos(movimientosFusionados);
            alert("Se han agregado nuevos movimientos");
          } else {
            alert("No se obtuvieron movimientos nuevos.");
          }
        })
        .catch(err => {
          console.error("Error al actualizar movimientos:", err);
          alert("Error al actualizar movimientos.");
        });
    }

    // Al hacer clic en el icono de refresco en el resumen, se busca actualizar (agregar nuevos movimientos)
    document.getElementById("refreshSummary").addEventListener("click", () => {
      actualizarMovimientos();
    });

    // Función para aplicar ambos filtros: texto y resultado.
    function aplicarFiltros() {
      const textoFiltro = document.getElementById("tabla-filtro").value.toLowerCase();
      const resultadoFiltro = document.getElementById("resultadoFiltro").value;
      const filas = document.querySelectorAll("#movements-list tr");
      filas.forEach(fila => {
        if (fila.id === "spinner-row") return;
        const textoFila = fila.textContent.toLowerCase();
        const resultadoCelda = fila.cells[6] && fila.cells[6].childNodes[0] ? fila.cells[6].childNodes[0].textContent.trim() : "";
        const coincideTexto = textoFila.includes(textoFiltro);
        const coincideResultado = (resultadoFiltro === "todos" || resultadoCelda === resultadoFiltro);
        fila.style.display = (coincideTexto && coincideResultado) ? "" : "none";
      });
      calcularResumen();
    }

    // Función para convertir cadena a número
    function parseCurrency(str) {
      return parseFloat(str.replace(/[$,]/g, '')) || 0;
    }

    // Función para calcular y actualizar el resumen, considerando solo las filas visibles
    function calcularResumen() {
      let ingresoTotal = 0;
      let comisiones = 0;
      const filas = Array.from(document.querySelectorAll("#movements-list tr")).filter(
        fila => fila.id !== "spinner-row" && window.getComputedStyle(fila).display !== "none"
      );
      filas.forEach(fila => {
        const resultado = fila.cells[6] && fila.cells[6].childNodes[0] ? fila.cells[6].childNodes[0].textContent.trim() : "";
        if (resultado === "✅") {
          const montoText = fila.cells[5] ? fila.cells[5].textContent.trim() : "0";
          const monto = parseCurrency(montoText);
          ingresoTotal += monto;
          const tipo = fila.cells[3] ? fila.cells[3].textContent.trim() : "";
          if (tipo === "COMISIÓN") {
            comisiones += Math.abs(monto);
          }
        }
      });
      const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
      document.getElementById("ingresoTotalVerificado").textContent = "Ingreso total verificado: " + formatter.format(ingresoTotal);
      document.getElementById("comisionesCobradas").textContent = "Comisiones Cobradas: " + formatter.format(comisiones);
    }

    // Eventos para cargar CSV
    document.getElementById('cargar-csv-btn').addEventListener('click', () => {
      const filas = document.querySelector("#movements-table tbody").querySelectorAll("tr");
      if (filas.length === 0) {
        alert("No hay movimientos para cargar CSV.");
        return;
      }
      document.getElementById('csvInput').click();
    });
    document.getElementById('csvInput').addEventListener('change', (event) => {
      const archivoCSV = event.target.files[0];
      if (archivoCSV) {
        console.log(Archivo seleccionado: ${archivoCSV.name});
        if (typeof uploadCSV === "function") {
          uploadCSV(event);
        } else {
          console.error("Error: La función 'uploadCSV' no está definida.");
        }
      } else {
        console.error("No se seleccionó ningún archivo.");
      }
    });
    // Evento para generar póliza
    document.getElementById('generar-poliza-btn').addEventListener('click', () => {
      console.log("Generando póliza desde movimientos...");
      if (typeof generarPolizaDesdeTabla === "function") {
        generarPolizaDesdeTabla();
      } else {
        console.error("Error: La función 'generarPolizaDesdeTabla' no está definida.");
      }
    });
    // Botón Log: preparar Excel y mostrar modal personalizado
    document.getElementById("ver-logs-btn").addEventListener("click", () => {
      if (movimientosEstructurados.length === 0) {
        alert("No hay movimientos detallados para exportar.");
        return;
      }
      const data = [];
      data.push([
        "Movimiento", "Fecha", "Contrato", "Compañía", "Saldo Inicial Banco",
        "Total Depósitos ATIO", "Total Retiros ATIO", "Total Devoluciones ATIO",
        "Total Comisiones ATIO", "Saldo Total Banco", "Saldo Total ATIO",
        "Saldo Final Ajustado", "Resultado"
      ]);
      movimientosEstructurados.forEach(item => {
        data.push([
          item.movimiento,
          item.fecha,
          item.contrato || "",
          item.compania && item.compania.trim() !== "" ? item.compania : "No encontrado",
          Number(item.saldoInicialBanco),
          Number(item.totalDepositosATIO),
          Number(item.totalRetirosATIO),
          Number(item.totalDevolucionesATIO),
          Number(item.totalComisionesATIO),
          Number(item.saldoTotalBanco),
          Number(item.saldoTotalATIO),
          Number(item.saldoFinalAjustado),
          item.resultado
        ]);
      });
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.aoa_to_sheet(data);
      const range = XLSX.utils.decode_range(ws['!ref']);
      for (let R = 1; R <= range.e.r; ++R) {
        for (let C = 4; C <= 11; ++C) {
          const cellAddress = { c: C, r: R };
          const cellRef = XLSX.utils.encode_cell(cellAddress);
          if (ws[cellRef] && ws[cellRef].t === 'n') {
            ws[cellRef].z = '$ #,##0.00';
          }
        }
      }
      XLSX.utils.book_append_sheet(wb, ws, "Logs");
      const filename = Logs_Movimientos_${new Date().toISOString().slice(0, 10)}.xlsx;
      document.getElementById("downloadExcelBtn").onclick = () => {
        XLSX.writeFile(wb, filename);
        document.getElementById("logModal").style.display = "none";
      };
      document.getElementById("viewExcelBtn").onclick = () => {
        const htmlStr = XLSX.utils.sheet_to_html(ws);
        const win = window.open("", "_blank");
        win.document.write(htmlStr);
        win.document.close();
        document.getElementById("logModal").style.display = "none";
      };
      document.getElementById("closeModalBtn").onclick = () => {
        document.getElementById("logModal").style.display = "none";
      };
      document.getElementById("logModal").style.display = "block";
    });
    // Exportar tabla a Excel
    function exportarMovimientosAExcel() {
      console.log("Exportando movimientos a Excel...");
      const tabla = document.querySelector("#movements-table");
      const filas = tabla.querySelectorAll("tbody tr");
      if (filas.length === 0) {
        alert("No hay movimientos para exportar.");
        return;
      }
      const workbook = XLSX.utils.book_new();
      const worksheet = XLSX.utils.table_to_sheet(tabla, { raw: true });
      XLSX.utils.book_append_sheet(workbook, worksheet, "Movimientos");
      const nombreArchivo = Movimientos_${new Date().toISOString().slice(0, 10)}.xlsx;
      XLSX.writeFile(workbook, nombreArchivo);
      alert(Movimientos exportados correctamente como '${nombreArchivo}'.);
    }
    document.getElementById("exportar-excel-btn").addEventListener("click", exportarMovimientosAExcel);
    // Eventos para los controles de filtro: input y select
    document.getElementById("tabla-filtro").addEventListener("keyup", aplicarFiltros);
    document.getElementById("resultadoFiltro").addEventListener("change", aplicarFiltros);
    // Botón de filtro: muestra/oculta el input y habilita controles
    document.getElementById("btn-filter").addEventListener("click", function() {
      const filtroInput = document.getElementById("tabla-filtro");
      if (filtroInput.style.display === "none" || filtroInput.style.display === "") {
        filtroInput.style.display = "inline-block";
        filtroInput.disabled = false;
        document.getElementById("resultadoFiltro").disabled = false;
        filtroInput.focus();
      } else {
        filtroInput.style.display = "none";
        filtroInput.value = "";
        aplicarFiltros();
      }
    });
    // Observador para habilitar/deshabilitar controles de filtro y recalcular resumen según el contenido del tbody
    const movementsList = document.getElementById("movements-list");
    const observer = new MutationObserver(() => {
      const filas = Array.from(movementsList.querySelectorAll("tr")).filter(fila => fila.id !== "spinner-row");
      const filterDisabled = filas.length === 0;
      document.getElementById("btn-filter").disabled = filterDisabled;
      document.getElementById("tabla-filtro").disabled = filterDisabled;
      document.getElementById("resultadoFiltro").disabled = filterDisabled;
      calcularResumen();
    });
    observer.observe(movementsList, { childList: true });
    // Al cargar la página, si hay datos persistidos, se muestran
    window.addEventListener("load", () => {
      if (localStorage.getItem("movimientosData")) {
        const movimientosGuardados = JSON.parse(localStorage.getItem("movimientosData"));
        if (movimientosGuardados.length > 0) {
          mostrarMovimientos(movimientosGuardados);
        }
      }
    });
  </script>
</body>
</html>