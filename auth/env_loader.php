<?php
require_once __DIR__ . 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); // Asume que .env está en la raíz del proyecto
try {
    $dotenv->load();
    error_log(".env cargado correctamente");
    error_log("Path del .env cargado: " . __DIR__ . '/../.env');
    error_log("API_USERNAME del .env: " . getenv('API_USERNAME')); //log del username
    error_log("API_PASSWORD del .env: " . getenv('API_PASSWORD')); //log del password
    error_log("API_URL del .env: " . getenv('API_URL')); //log del api_url
} catch (Dotenv\Exception\InvalidPathException $e){
    error_log("Error al cargar .env: ".$e->getMessage());
}
?>