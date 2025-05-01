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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Movimientos</title>
  <link rel="icon" href="../assets/ICOTG.ico" type="image/x-icon">
  <!-- CSS: Bootstrap, AdminLTE, Iconos, Scrollbars, y estilos personalizados -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css" />
  <link rel="stylesheet" href="../css/movimientos.css" />
  <style>
    /* Spinner */
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
    /* Filtro */
    #tabla-filtro { display: none; max-width: 200px; }
    /* Resumen */
    #resumenResultados { margin-bottom: 1rem; }
    #refreshSummary { cursor: pointer; }
    /* Minimalist design for Sweetalert2 */
    .minimal-popup {
      background-color: #f5f5f5;
      border: none;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }
    .minimal-title {
      color: #333;
      font-weight: 500;
      font-size: 1.5rem;
    }
    .minimal-confirm {
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
    }
    .minimal-confirm:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">
    <!-- Navbar -->
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-fluid">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
              <i class="bi bi-list"></i>
            </a>
          </li>
        </ul>
      </div>
    </nav>
    <!-- Sidebar -->
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
      <div class="sidebar-wrapper">
        <nav class="mt-2">
          <?php include __DIR__ . '/layout/sidebar.php'; ?>
        </nav>
      </div>
    </aside>
    <!-- Contenido principal -->
    <div class="app-main">
      <main class="content p-4">
        <div id="movimientos">
          <h1 class="text-light">Movimientos</h1>
          <!-- Selección de año -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
              <h5 class="text-light mb-0">Año:</h5>
              <select id="select-anio" class="form-select w-auto">
                <option value="2025" selected>2025</option>
                <option value="2024">2024</option>
              </select>
            </div>
          </div>
          <!-- Botones de mes y controles de filtro -->
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
              <h5 class="text-light mb-0">Mes:</h5>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php
                  $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                  foreach ($meses as $i => $nombre) {
                    $m = str_pad($i + 1, 2, "0", STR_PAD_LEFT);
                    echo "<button class='btn btn-month' data-mes='$m'>$nombre</button>";
                  }
                ?>
                <button id="btn-filter" class="btn btn-outline-secondary" title="Filtrar" disabled>
                  <i class="bi bi-search"></i>
                </button>
                <input type="text" id="tabla-filtro" class="form-control" placeholder="Filtrar..." disabled />
                <div class="d-flex align-items-center">
                  <label for="resultadoFiltro" class="text-light me-2 mb-0">Resultado:</label>
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
          <!-- Resumen de resultados -->
          <div id="resumenResultados" class="mb-3 d-flex justify-content-between align-items-center text-light">
            <div>
              <span id="ingresoTotalVerificado">Ingreso total verificado: $0.00</span>
              <span id="comisionesCobradas" class="ms-3">Comisiones Cobradas: $0.00</span>
            </div>
            <div>
              <span id="refreshSummary" title="Buscar nuevos movimientos" class="me-3">
                <i class="bi bi-arrow-clockwise"></i>
              </span>
              <button id="guardarAvances" class="btn btn-success btn-sm" title="Guardar avances">
                <i class="bi bi-save"></i>
              </button>
              <button id="recuperarAvances" class="btn btn-info btn-sm ms-2" title="Recuperar avances">
                <i class="bi bi-cloud-download"></i>
              </button>
            </div>
          </div>
          <!-- Tabla de movimientos -->
          <div class="table-container mt-4">
            <div class="d-flex justify-content-end mb-3">
              <button id="cargar-csv-btn" class="btn btn-secondary me-2">Cargar CSV</button>
              <button id="exportar-excel-btn" class="btn btn-success me-2">
                <img src="../assets/icono_excel.png" alt="Exportar a Excel" class="icono-excel" /> Exportar
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
                <tr id="spinner-row" style="display: none;">
                  <td colspan="7">
                    <span id="spinner-icon"></span> Cargando movimientos...
                  </td>
                </tr>
                <!-- Las filas se agregarán dinámicamente -->
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>
  <!-- Input oculto para cargar CSV -->
  <input type="file" id="csvInput" accept=".csv" style="display: none;" />
  <!-- Modal para Log personalizado -->
  <div id="logModal" style="display: none; position: fixed; top: 30%; left: 50%; transform: translate(-50%, -50%);
       background-color: #333; padding: 20px; border-radius: 8px; z-index: 2000; text-align: center;">
    <h3 style="color: #fff;">Selecciona una opción</h3>
    <p style="color: #fff;">¿Deseas ver el Excel en pantalla o descargarlo?</p>
    <button id="viewExcelBtn" class="btn btn-primary me-2">Ver en pantalla</button>
    <button id="downloadExcelBtn" class="btn btn-success">Descargar Excel</button>
    <button id="closeModalBtn" class="btn btn-secondary ms-2">Cerrar</button>
  </div>
  
  <!-- Scripts externos -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/dist/js/OverlayScrollbars.min.js"></script>
  <script src="../js/adminlte.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!--<script defer src="../js/sidebar.js"></script> -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  
  <!-- Scripts propios: se asume que cargarCSV.js, generarpoliza.js, guardarTabla.js y cargartabla.js están correctamente definidos -->
  <script src="../js/mostrarmovimientos.js"></script>
  <script src="../js/compararmovimentos.js"></script>
  <script src="../js/recompararmovimientos.js"></script>
  <script src="../js/cargarCSV.js"></script>
  <script src="../js/generarpoliza.js"></script>
  <script src="../js/guardarTabla.js"></script>
  <script src="../js/cargartabla.js"></script>
  
  <!-- Código inline para asignar eventos y controles -->
  <script>
    (function() {
      // Variables globales (si no están definidas en los archivos externos)
      window.csvData = window.csvData || [];
      window.csvLoaded = (typeof window.csvLoaded !== "undefined") ? window.csvLoaded : false;
      
      // Función para aplicar filtros (texto y resultado)
      function aplicarFiltros() {
        const textoFiltro = document.getElementById("tabla-filtro").value.toLowerCase();
        const resultadoFiltro = document.getElementById("resultadoFiltro").value;
        const filas = document.querySelectorAll("#movements-list tr");
        filas.forEach(fila => {
          if (fila.id === "spinner-row") return;
          const textoFila = fila.textContent.toLowerCase();
          const resultadoCelda = fila.cells[6] && fila.cells[6].childNodes[0]
            ? fila.cells[6].childNodes[0].textContent.trim()
            : "";
          const coincideTexto = textoFila.includes(textoFiltro);
          const coincideResultado = (resultadoFiltro === "todos" || resultadoCelda === resultadoFiltro);
          fila.style.display = (coincideTexto && coincideResultado) ? "" : "none";
        });
        calcularResumen();
      }
      
      // Función para convertir cadena de moneda a número
      function parseCurrency(str) {
        return parseFloat(str.replace(/[$,]/g, '')) || 0;
      }
      
      // Función para calcular y actualizar el resumen basado en filas visibles
      function calcularResumen() {
        let ingresoTotal = 0, comisiones = 0;
        const filas = Array.from(document.querySelectorAll("#movements-list tr"))
                         .filter(fila => fila.id !== "spinner-row" && window.getComputedStyle(fila).display !== "none");
        filas.forEach(fila => {
          const resultado = fila.cells[6] && fila.cells[6].childNodes[0]
            ? fila.cells[6].childNodes[0].textContent.trim()
            : "";
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
      
      // Observador para habilitar/deshabilitar controles de filtro y recalcular resumen
      const movementsList = document.getElementById("movements-list");
      const observer = new MutationObserver(() => {
        const filas = Array.from(movementsList.querySelectorAll("tr")).filter(fila => fila.id !== "spinner-row");
        const filterDisabled = filas.length === 0;
        document.getElementById("btn-filter").disabled = filterDisabled;
        document.getElementById("tabla-filtro").disabled = filterDisabled;
        document.getElementById("resultadoFiltro").disabled = filterDisabled;
        // Deshabilitar "Cargar CSV" si no hay filas de datos reales
        document.getElementById("cargar-csv-btn").disabled = filterDisabled;
        calcularResumen();
      });
      observer.observe(movementsList, { childList: true });
      
      // Evento "Cargar CSV": verifica que existan filas reales y dispara el input file
      document.getElementById('cargar-csv-btn').addEventListener('click', () => {
        const dataRows = document.querySelector("#movements-table tbody").querySelectorAll("tr:not(#spinner-row)");
        if (dataRows.length === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'No hay datos disponibles',
            text: 'Selecciona un año y mes antes de cargar el CSV.',
            customClass: {
              popup: 'minimal-popup',
              title: 'minimal-title',
              confirmButton: 'minimal-confirm'
            },
            buttonsStyling: false
          });
          return;
        }
        document.getElementById('csvInput').click();
      });
      
      // Evento del input CSV: llama a uploadCSV (definida en cargarCSV.js)
      document.getElementById('csvInput').addEventListener('change', (event) => {
        const archivoCSV = event.target.files[0];
        if (archivoCSV) {
          console.log(`Archivo seleccionado: ${archivoCSV.name}`);
          if (typeof uploadCSV === "function") {
            uploadCSV(event);
          } else {
            console.error("La función 'uploadCSV' no está definida.");
          }
        } else {
          Swal.fire({
            icon: 'warning',
            title: 'Archivo no seleccionado',
            text: 'Por favor, seleccione un archivo CSV.',
            customClass: {
              popup: 'minimal-popup',
              title: 'minimal-title',
              confirmButton: 'minimal-confirm'
            },
            buttonsStyling: false
          });
        }
      });
      
      // Evento "Exportar Excel": verifica que existan filas reales en la tabla
      document.getElementById("exportar-excel-btn").addEventListener("click", () => {
        const dataRows = document.querySelector("#movements-table tbody").querySelectorAll("tr:not(#spinner-row)");
        if (dataRows.length === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'No hay datos que exportar',
            text: 'No se encontraron datos en la tabla.',
            customClass: {
              popup: 'minimal-popup',
              title: 'minimal-title',
              confirmButton: 'minimal-confirm'
            },
            buttonsStyling: false
          });
          return;
        }
        if (typeof exportarMovimientosAExcel === "function") {
          exportarMovimientosAExcel();
        } else {
          console.error("La función 'exportarMovimientosAExcel' no está definida.");
        }
      });
      
      // Evento "Generar Póliza": verifica que existan filas reales en la tabla
      document.getElementById("generar-poliza-btn").addEventListener("click", () => {
        const dataRows = document.querySelector("#movements-table tbody").querySelectorAll("tr:not(#spinner-row)");
        if (dataRows.length === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'No hay datos',
            text: 'No hay movimientos para generar la póliza.',
            customClass: {
              popup: 'minimal-popup',
              title: 'minimal-title',
              confirmButton: 'minimal-confirm'
            },
            buttonsStyling: false
          });
          return;
        }
        if (typeof generarPolizaDesdeTabla === "function") {
          generarPolizaDesdeTabla();
        } else {
          console.error("La función 'generarPolizaDesdeTabla' no está definida.");
        }
      });
      
      // Evento "Ver Log": prepara datos y muestra el modal personalizado para logs
      document.getElementById("ver-logs-btn").addEventListener("click", () => {
        if (typeof movimientosEstructurados === "undefined" || movimientosEstructurados.length === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'No hay logs',
            text: 'No hay movimientos detallados para exportar.'
          });
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
        const filename = `Logs_Movimientos_${new Date().toISOString().slice(0, 10)}.xlsx`;
      
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
      
      // Eventos para controles de filtro
      document.getElementById("tabla-filtro").addEventListener("keyup", aplicarFiltros);
      document.getElementById("resultadoFiltro").addEventListener("change", aplicarFiltros);
      
      // Botón de filtro: mostrar/ocultar input y habilitar select
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
      
      // Eventos para "Guardar avances" y "Recuperar avances"
      const guardarAvancesEl = document.getElementById("guardarAvances");
      if (guardarAvancesEl) {
        guardarAvancesEl.addEventListener("click", function() {
          console.log("Guardar avances pulsado");
          // Lógica para guardar el estado (por ejemplo, mediante guardarTabla() definida en guardarTabla.js)
        });
      }
      const recuperarAvancesEl = document.getElementById("recuperarAvances");
      if (recuperarAvancesEl) {
        recuperarAvancesEl.addEventListener("click", function() {
          console.log("Recuperar avances pulsado");
          // Llama a la función cargarTabla() definida en cargartabla.js para recuperar la tabla
          if (typeof cargarTabla === "function") {
            cargarTabla();
          } else {
            console.error("La función 'cargarTabla' no está definida.");
          }
        });
      }
      
      // (Opcional) Asignar un evento al botón "Guardar avances" para que llame a guardarTabla()
      document.addEventListener("DOMContentLoaded", function() {
        const btnGuardarTabla = document.getElementById("guardarAvances");
        if (btnGuardarTabla && typeof guardarTabla === "function") {
          btnGuardarTabla.addEventListener("click", guardarTabla);
        }
      });
    })();


    function exportarMovimientosAExcel() {
  const table = document.getElementById("movements-table");
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.table_to_sheet(table);
  XLSX.utils.book_append_sheet(wb, ws, "Movimientos");

  const filename = "Movimientos_" + new Date().toISOString().slice(0, 10) + ".xlsx";
  XLSX.writeFile(wb, filename);
}

  </script>
</body>
</html>
