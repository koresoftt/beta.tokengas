<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); // Subir un nivel para llegar a la raíz
$dotenv->load();

$ATUS = $_ENV['ATIONET_USER'];
$ATPW = $_ENV['ATIONET_PASS'];

echo $ATUS;
echo $ATPW;
?>