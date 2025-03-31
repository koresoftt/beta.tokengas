<?php
// test_session.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Muestra todo el contenido de $_SESSION
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
