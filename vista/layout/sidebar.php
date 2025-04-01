<?php
// layout/sidebar.php
?>

<div class="sidebar-brand">
    <a href="../vista/dashboard.php" class="brand-link">
        <img src="../assets/logo_tg2.png" alt="TokenGas Logo" class="brand-image opacity-75 shadow d-block mx-auto">
    </a>
</div>

<nav class="mt-2">
    <ul class="nav sidebar-menu flex-column" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="../vista/dashboard.php" class="nav-link" data-lte-toggle="true">
                <i class="nav-icon bi bi-house-door"></i>
                <p>Inicio</p>
            </a>
        </li>

        <li class="nav-item has-treeview">
            <a href="#" class="nav-link" data-lte-toggle="treeview">
                <i class="nav-icon bi bi-building"></i>
                <p>
                    Compañías
                    <i class="right bi bi-chevron-right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="../vista/depositos.php" class="nav-link" data-lte-toggle="true">
                        <i class="nav-icon bi bi-cash-stack"></i>
                        <p>Depósitos</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../vista/retiros.php" class="nav-link" data-lte-toggle="true">
                        <i class="nav-icon bi bi-cash"></i>
                        <p>Retiros</p>
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item">
            <a href="../vista/movimientos.php" class="nav-link" data-lte-toggle="true">
                <i class="nav-icon bi bi-bar-chart-line"></i>
                <p>Movimientos</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="../vista/reportes.php" class="nav-link" data-lte-toggle="true">
                <i class="nav-icon bi bi-file-earmark-text"></i>
                <p>Reportes</p>
            </a>
        </li>
    </ul>
</nav>