<?php
// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Crear instancia de Dotenv y cargar las variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
