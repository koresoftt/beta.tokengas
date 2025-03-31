<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: /tokengas/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TokenGas</title>
    <link rel="icon" href="/tokengas/assets/ICOTG.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/tokengas/css/movimientos.css">
    <link rel="stylesheet" href="/tokengas/css/sidebar.css">
    <style>
    /* Spinner estilo Mac */
    .spinner-mac {
      display: inline-block;
      width: 40px;
      height: 40px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin-mac 0.8s linear infinite;
    }
    @keyframes spin-mac {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    /* Historial oculto inicialmente */
    .historial-table {
      display: none; 
      margin-top: 1rem;
    }
    /* Ícono ℹ️ más grande */
    .info-icon {
      cursor: pointer;
      margin-left: 0.8rem;
      font-size: 1.5rem;
    }
  </style>
  
  
</head>

<body class="bg-dark text-light">
<div class="d-flex">
  <?php include __DIR__ . '/layout/sidebar.php'; ?>
  <main class="content p-4">
    <h1 style="font-size:2rem;">LITROS VENDIDOS AL DIA</h1>
    <div class="row">
      <!-- REGULAR -->
      <div class="col-md-4 mb-3">
        <div class="card bg-dark text-light text-center">
          <div class="card-body" id="card-REGULAR">
            <h4 style="font-size:1.5rem;">
              REGULAR
              <span class="info-icon" title="Ver histórico" onclick="toggleHistorial('REGULAR')">ℹ️</span>
            </h4>
            <!-- En el primer cargado se muestra el spinner -->
            <div id="litros-REGULAR"><div class="spinner-mac"></div></div>
            <!-- Historial -->
            <div id="historial-REGULAR" class="historial-table bg-secondary p-2"></div>
          </div>
        </div>
      </div>
      <!-- PREMIUM -->
      <div class="col-md-4 mb-3">
        <div class="card bg-dark text-light text-center">
          <div class="card-body" id="card-PREMIUM">
            <h4 style="font-size:1.5rem;">
              PREMIUM
              <span class="info-icon" title="Ver histórico" onclick="toggleHistorial('PREMIUM')">ℹ️</span>
            </h4>
            <div id="litros-PREMIUM"><div class="spinner-mac"></div></div>
            <div id="historial-PREMIUM" class="historial-table bg-secondary p-2"></div>
          </div>
        </div>
      </div>
      <!-- DIESEL -->
      <div class="col-md-4 mb-3">
        <div class="card bg-dark text-light text-center">
          <div class="card-body" id="card-DIESEL">
            <h4 style="font-size:1.5rem;">
              DIESEL
              <span class="info-icon" title="Ver histórico" onclick="toggleHistorial('DIESEL')">ℹ️</span>
            </h4>
            <div id="litros-DIESEL"><div class="spinner-mac"></div></div>
            <div id="historial-DIESEL" class="historial-table bg-secondary p-2"></div>
          </div>
        </div>
      </div>
    </div><!-- row -->
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script defer src="/tokengas/js/sidebar.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script>
  // Variables globales para almacenar el estado actual y controlar el primer cargado
  let currentKPIs = { REGULAR: 0, PREMIUM: 0, DIESEL: 0 };
  let isFirstLoad = true;

  // Función para alternar la visualización del historial
  function toggleHistorial(prod) {
    const elem = document.getElementById('historial-' + prod);
    elem.style.display = (elem.style.display === 'none' || elem.style.display === '') ? 'block' : 'none';
  }

  // Función para formatear números (estilo en-US: 1,234.56)
  function formatLitrosMX(num) {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(num);
  }

  // Función que consulta el endpoint y actualiza la vista solo si hay incremento en los litros
  function actualizarKPIs() {
    fetch('/tokengas/PHP/procesar_kpis.php')
    .then(res => res.json())
      .then(data => {
        // Se elimina la marca de primer cargado
        isFirstLoad = false;
        if (data.error) {
          ['REGULAR','PREMIUM','DIESEL'].forEach(prod => {
            document.getElementById('litros-' + prod).textContent = 'Error: ' + data.error;
          });
          return;
        }
        // Recorremos cada producto
        for (const prod in data.variaciones) {
          const newVal = data.variaciones[prod].hoy;
          const oldVal = currentKPIs[prod];
          // Actualizamos solo si hay incremento
          if (newVal > oldVal) {
            currentKPIs[prod] = newVal;
            const info = data.variaciones[prod];
            const hoyForm = formatLitrosMX(newVal);
            const varAbs  = Math.abs(info.variacion).toFixed(2);
            // Actualiza el contenido del KPI
            document.getElementById('litros-' + prod).innerHTML = `
              <strong style="font-size:1.6rem;">${hoyForm} L</strong><br>
              <span style="font-size:1.3rem;" class="${info.variacion >= 0 ? 'text-success' : 'text-danger'}">
                ${info.direccion} ${varAbs}%
              </span>
            `;
            // Actualiza el historial
            const histDiv = document.getElementById('historial-' + prod);
            if (data.historial && data.historial[prod]) {
              let tableHTML = `
                <table class="table table-dark table-sm">
                  <thead><tr><th>Fecha</th><th>Litros</th></tr></thead>
                  <tbody>
              `;
              for (const fecha in data.historial[prod]) {
                tableHTML += `<tr>
                  <td>${fecha}</td>
                  <td>${formatLitrosMX(data.historial[prod][fecha])} L</td>
                </tr>`;
              }
              tableHTML += '</tbody></table>';
              histDiv.innerHTML = tableHTML;
            }
          } else if (isFirstLoad) {
            // En el primer cargado, si no hay actualización, se quita el spinner y se muestran los datos iniciales
            const info = data.variaciones[prod];
            const hoyForm = formatLitrosMX(oldVal);
            const varAbs  = Math.abs(info.variacion).toFixed(2);
            document.getElementById('litros-' + prod).innerHTML = `
              <strong style="font-size:1.6rem;">${hoyForm} L</strong><br>
              <span style="font-size:1.3rem;" class="${info.variacion >= 0 ? 'text-success' : 'text-danger'}">
                ${info.direccion} ${varAbs}%
              </span>
            `;
          }
          // Si no hay cambios y ya no es el primer cargado, se mantiene el valor anterior sin modificarlo
        }
      })
      .catch(err => {
        ['REGULAR','PREMIUM','DIESEL'].forEach(prod => {
          document.getElementById('litros-' + prod).textContent = 'Error: ' + err;
        });
        console.error(err);
      });
  }

  // Al cargar la página se realiza la primera consulta y luego se actualiza cada 60 segundos
  document.addEventListener('DOMContentLoaded', () => {
    actualizarKPIs();
    setInterval(actualizarKPIs, 60000);
  });
</script>

 
</body>
</html>