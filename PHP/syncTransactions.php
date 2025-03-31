<?php
// syncTransactions.php

// 1. Obtener el token de autenticación
$tokenJson = file_get_contents('http://localhost/tokengas/auth/token_handler.php');
if ($tokenJson === false) {
    die("Error: No se pudo obtener el token.");
}
$tokenData = json_decode($tokenJson, true);
if (!isset($tokenData['access_token'])) {
    die("Error: Token inválido.");
}
$accessToken = $tokenData['access_token'];

// 2. Obtener transacciones desde la API
$apiUrl = 'https://api.ationet.com/transactions';
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $accessToken",
    "Content-Type: application/json"
]);
$apiResponse = curl_exec($ch);
if (curl_errno($ch)) {
    die("Error en la llamada a la API: " . curl_error($ch));
}
curl_close($ch);
$apiData = json_decode($apiResponse, true);
if (!isset($apiData['data'])) {
    die("Error: No se encontraron transacciones en la API.");
}
$apiTransactions = $apiData['data'];

// 3. Conectar a la base de datos PostgreSQL
$host     = '127.0.0.1';
$port     = '5432';
$dbname   = 'DBTG';
$user     = 'postgres';
$password = 'koresolano19';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Error de conexión a la BD: " . $e->getMessage());
}

// 4. Sincronizar transacciones
foreach ($apiTransactions as $tx) {
    $txId = $tx['Id'] ?? null;
    if (!$txId) continue;
    $dateTime   = $tx['DateTime'] ?? null;
    $fuelCode   = $tx['FuelCode'] ?? null;
    $merchantId = $tx['MerchantId'] ?? null;
    $volume     = isset($tx['ProductVolumeDispensed']) ? floatval($tx['ProductVolumeDispensed']) : 0;
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM public.transactions WHERE "Id" = :id');
    $stmt->execute(['id' => $txId]);
    $exists = $stmt->fetchColumn();
    
    if ($exists > 0) {
        $updateStmt = $pdo->prepare('UPDATE public.transactions 
            SET "DateTime" = :dt, "FuelCode" = :fc, "MerchantId" = :mid, "ProductVolumeDispensed" = :vol
            WHERE "Id" = :id');
        $updateStmt->execute([
            'dt'  => $dateTime,
            'fc'  => $fuelCode,
            'mid' => $merchantId,
            'vol' => $volume,
            'id'  => $txId
        ]);
        echo "Transacción $txId actualizada.<br>";
    } else {
        $insertStmt = $pdo->prepare('INSERT INTO public.transactions 
            ("Id", "DateTime", "FuelCode", "MerchantId", "ProductVolumeDispensed")
            VALUES (:id, :dt, :fc, :mid, :vol)');
        $insertStmt->execute([
            'id'  => $txId,
            'dt'  => $dateTime,
            'fc'  => $fuelCode,
            'mid' => $merchantId,
            'vol' => $volume
        ]);
        echo "Transacción $txId insertada.<br>";
    }
}

echo "Sincronización completada.";
?>
