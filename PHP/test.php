<?php
// test_token.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../auth/token_handler.php';

$accessToken = trim(token());
echo json_encode(["access_token" => $accessToken]);
