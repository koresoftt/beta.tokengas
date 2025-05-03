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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TokenGas</title>
  <link rel="icon" href="/tokengas/assets/ICOTG.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/tokengas/css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  
  <style>
    /* Distribución de info-box: la imagen se mantiene a la izquierda y el contenido centrado */
    .info-box {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .info-box-icon {
      flex: 0 0 auto;
    }
    .info-box-content {
      flex: 1;
      text-align: center;
    }
    
    /* Spinner estilo loader (usa fondo blanco con animación de barras) */
    .loader {
      width: 96px;
      height: 16px;
      display: inline-block;
      background-color: #FFF;
      border: 1px solid #FFF;
      border-radius: 4px;
      background-image: linear-gradient(45deg, rgba(0, 0, 0, 0.25) 25%, transparent 25%, transparent 50%, rgba(0, 0, 0, 0.25) 50%, rgba(0, 0, 0, 0.25) 75%, transparent 75%, transparent);
      background-size: 1em 1em;
      box-sizing: border-box;
      animation: barStripe 1s linear infinite;
    }
    @keyframes barStripe {
      0% {
        background-position: 1em 0;
      }
      100% {
        background-position: 0 0;
      }
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
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">
  <nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">

    <!-- Botón para mostrar el sidebar -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list"></i>
        </a>
      </li>
    </ul>

    <!-- Nombre del usuario y opción de cerrar sesión -->
    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <?php echo htmlspecialchars($_SESSION['name'] ?? 'Invitado'); ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item text-danger" href="/tokengas/logout.php">Cerrar sesión</a></li>
        </ul>
      </li>
    </ul>

  </div>
</nav>

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
      <div class="sidebar-wrapper">
        <nav class="mt-2">
          <?php include __DIR__ . '/layout/sidebar.php'; ?>
        </nav>
      </div>
    </aside>

    <div class="app-main">
      <main class="content p-4">
        <div class="container-fluid">
          <div class="row">
            <div class="col-12">
              <div class="card card-dark">
                <div class="card-header">
                  <h3 class="card-title">Litros Vendidos al Día</h3>
                </div>
                <div class="card-body">
                  <div class="row">
                    <!-- Producto Regular -->
                    <div class="col-md-4">
                      <div class="info-box">
                        <span class="info-box-icon">
                          <img src="../assets/Gasverde.png" alt="Gasolina Regular" style="width: 96px; height: 96px;">
                        </span>
                        <div class="info-box-content">
                          <span class="info-box-text">Regular</span>
                          <span class="info-box-number" id="litros-REGULAR">
                            <span class="loader"></span>
                          </span>
                          <button class="btn btn-sm btn-secondary mt-2" onclick="toggleHistorial('REGULAR')">
                            Ver Historial <i class="fas fa-info-circle"></i>
                          </button>
                        </div>
                      </div>
                      <!-- Contenedor de historial -->
                      <div id="historial-REGULAR" class="historial-table"></div>
                    </div>
                    <!-- Producto Premium -->
                    <div class="col-md-4">
                      <div class="info-box">
                        <span class="info-box-icon">
                          <img src="../assets/Gasroja.png" alt="Gasolina Premium" style="width: 96px; height: 96px;">
                        </span>
                        <div class="info-box-content">
                          <span class="info-box-text">Premium</span>
                          <span class="info-box-number" id="litros-PREMIUM">
                            <span class="loader"></span>
                          </span>
                          <button class="btn btn-sm btn-secondary mt-2" onclick="toggleHistorial('PREMIUM')">
                            Ver Historial <i class="fas fa-info-circle"></i>
                          </button>
                        </div>
                      </div>
                      <div id="historial-PREMIUM" class="historial-table"></div>
                    </div>
                    <!-- Producto Diesel -->
                    <div class="col-md-4">
                      <div class="info-box">
                        <span class="info-box-icon">
                          <img src="../assets/Gasnegra.png" alt="Gasolina Diesel" style="width: 96px; height: 96px;">
                        </span>
                        <div class="info-box-content">
                          <span class="info-box-text">Diesel</span>
                          <span class="info-box-number" id="litros-DIESEL">
                            <span class="loader"></span>
                          </span>
                          <button class="btn btn-sm btn-secondary mt-2" onclick="toggleHistorial('DIESEL')">
                            Ver Historial <i class="fas fa-info-circle"></i>
                          </button>
                        </div>
                      </div>
                      <div id="historial-DIESEL" class="historial-table"></div>
                    </div>
                  </div> <!-- row -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <footer class="app-footer"></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="../js/adminlte.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
          scrollbarTheme: 'os-theme-light',
          scrollbarAutoHide: 'leave',
          scrollbarClickScroll: true,
      };
    </script>

    <script>
      // Variables globales para almacenar el estado actual
      let currentKPIs = { REGULAR: 0, PREMIUM: 0, DIESEL: 0 };
      let isFirstLoad = true;

      // Alterna la visualización del contenedor de historial
      function toggleHistorial(prod) {
          const elem = document.getElementById('historial-' + prod);
          elem.style.display = (elem.style.display === 'none' || elem.style.display === '') ? 'block' : 'none';
      }

      // Formatea los números con dos decimales
      function formatLitrosMX(num) {
          return new Intl.NumberFormat('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
          }).format(num);
      }

      // Consulta el endpoint y actualiza la vista
      function actualizarKPIs() {
  fetch('../PHP/procesar_kpis.php')
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        ['REGULAR','PREMIUM','DIESEL'].forEach(prod => {
          document.getElementById('litros-' + prod).textContent = 'Error: ' + data.error;
        });
        return;
      }
      for (const prod in data.variaciones) {
        const newVal = data.variaciones[prod].hoy;
        currentKPIs[prod] = newVal;
        const info = data.variaciones[prod];
        const hoyForm = formatLitrosMX(newVal);
        const varAbs = Math.abs(info.variacion).toFixed(2);
        document.getElementById('litros-' + prod).innerHTML = `
          <strong style="font-size:1.6rem;">${hoyForm} L</strong><br>
          <span style="font-size:1.3rem;" class="${info.variacion >= 0 ? 'text-success' : 'text-danger'}">
              ${info.direccion} ${varAbs}%
          </span>
        `;
        // Integración del historial con estilo AdminLTE:
        const histDiv = document.getElementById('historial-' + prod);
        if (data.historial && data.historial[prod]) {
          let cardHTML = `
            <div class="card card-dark card-outline">
              <div class="card-header p-1">
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" onclick="toggleHistorial('${prod}')">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body p-0">
                <table class="table table-sm table-bordered text-center mb-0">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Litros</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${Object.keys(data.historial[prod]).map(fecha => `
                      <tr>
                        <td>${fecha}</td>
                        <td>${formatLitrosMX(data.historial[prod][fecha])} L</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
          `;
          histDiv.innerHTML = cardHTML;
        }
      }
    })
    .catch(err => {
      ['REGULAR','PREMIUM','DIESEL'].forEach(prod => {
        document.getElementById('litros-' + prod).textContent = 'Error: ' + err;
      });
      console.error(err);
    });
}

      document.addEventListener('DOMContentLoaded', () => {
          actualizarKPIs();
          setInterval(actualizarKPIs, 60000);
      });
    </script>
  </div>
</body>
</html>
