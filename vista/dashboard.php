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
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .loader-bar {
      background-image: linear-gradient(45deg, rgba(0,0,0,0.2) 25%, transparent 25%, transparent 50%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.2) 75%, transparent 75%, transparent);
      background-size: 1em 1em;
      animation: barStripe 1s linear infinite;
    }
    @keyframes barStripe {
      0% { background-position: 1em 0; }
      100% { background-position: 0 0; }
    }
  </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
            <i class="bi bi-list"></i>
          </a>
        </li>
      </ul>
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
            <div class="bg-white rounded shadow p-4">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Litros Vendidos al Día</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php
                $productos = [
                  'REGULAR' => 'Gasverde.png',
                  'PREMIUM' => 'Gasroja.png',
                  'DIESEL'  => 'Gasnegra.png'
                ];
                foreach ($productos as $tipo => $imagen): ?>
                <div class="flex items-center bg-gray-100 rounded p-3 shadow-sm">
                  <div class="flex-shrink-0">
                    <img src="../assets/<?= $imagen ?>" alt="<?= $tipo ?>" class="w-16 h-16">
                  </div>
                  <div class="ml-3 flex flex-col justify-center flex-1">
                    <span class="text-sm text-gray-600 font-medium"><?= $tipo ?></span>
                    <div id="litros-<?= $tipo ?>" class="text-xl font-bold text-blue-700 leading-tight">
                      <div class="w-20 h-4 bg-white border border-gray-300 rounded loader-bar"></div>
                    </div>
                    <button onclick="toggleHistorial('<?= $tipo ?>')" class="mt-2 text-xs bg-gray-300 hover:bg-gray-400 text-gray-800 px-2 py-1 rounded w-max">
                      Ver Historial <i class="fas fa-info-circle ml-1"></i>
                    </button>
                    <div id="historial-<?= $tipo ?>" class="hidden mt-2"></div>
                  </div>
                </div>
                <?php endforeach; ?>
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
    let currentKPIs = { REGULAR: 0, PREMIUM: 0, DIESEL: 0 };
    let kpiController = null;

    function toggleHistorial(prod) {
      const elem = document.getElementById('historial-' + prod);
      elem.classList.toggle('hidden');
    }

    function formatLitrosMX(num) {
      return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(num);
    }

    function actualizarKPIs() {
      if (kpiController) kpiController.abort();
      kpiController = new AbortController();

      fetch('../PHP/procesar_kpis.php', { signal: kpiController.signal })
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            ['REGULAR','PREMIUM','DIESEL'].forEach(prod => {
              document.getElementById('litros-' + prod).textContent = 'Error: ' + data.error;
            });
            return;
          }
          for (const prod in data.variaciones) {
            const info = data.variaciones[prod];
            const hoyForm = formatLitrosMX(info.hoy);
            const varAbs = Math.abs(info.variacion).toFixed(2);
            document.getElementById('litros-' + prod).innerHTML = `
              <strong class="text-2xl">${hoyForm} L</strong><br>
              <span class="text-sm ${info.variacion >= 0 ? 'text-green-600' : 'text-red-600'}">
                ${info.direccion} ${varAbs}%
              </span>`;

            const histDiv = document.getElementById('historial-' + prod);
            if (data.historial && data.historial[prod]) {
              let cardHTML = `
                <div class="border rounded bg-white shadow-sm">
                  <div class="flex justify-end px-2 py-1">
                    <button type="button" class="text-gray-500 hover:text-red-500" onclick="toggleHistorial('${prod}')">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-center border-t border-gray-200">
                      <thead class="bg-gray-100 text-gray-700">
                        <tr>
                          <th class="px-3 py-2 border-b border-gray-200">Fecha</th>
                          <th class="px-3 py-2 border-b border-gray-200">Litros</th>
                        </tr>
                      </thead>
                      <tbody>
                        ${Object.entries(data.historial[prod]).map(([fecha, litros]) => `
                          <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 border-b">${fecha}</td>
                            <td class="px-3 py-2 border-b">${formatLitrosMX(litros)} L</td>
                          </tr>
                        `).join('')}
                      </tbody>
                    </table>
                  </div>
                </div>`;
              histDiv.innerHTML = cardHTML;
            }
          }
        })
        .catch(err => {
          if (err.name !== 'AbortError') {
            ['REGULAR','PREMIUM','DIESEL'].forEach(prod => {
              document.getElementById('litros-' + prod).textContent = 'Error: ' + err;
            });
          }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
      if (document.getElementById('litros-REGULAR')) {
        setTimeout(() => {
          actualizarKPIs();
          setInterval(actualizarKPIs, 60000);
        }, 200);
      }
    });
  </script>
</div>
</body>
</html>