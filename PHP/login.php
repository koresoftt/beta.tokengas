<?php
// login.php
session_start();

// Obtener los datos del formulario
$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';

// Access Token (¡IMPORTANTE! Reemplaza con tu token real)
$accessToken = ['hAg-L88pfQjnOs7oXDwHXphY2rVqMb4ek-TQyKFHW2-wlu-YB-fddrzv6oEqWq9dIJXYwslq6aRP3a7O2SAn4LiA_x7jh-7JG2lyd0PXkIcnPajO_31NNFDrzQEslKKIE0xXtrWmQ9qtRkEarQYkDA1OfI25GfQxfev4XRH04h9WWGq5FlkUmoLQ0zmWdP6KVxQXw_10laIdYaLkTuNj_5W2U6AbKmR4N5vwasNwMmVDGVVi0bKY-PDpQZ7cUjmJ_WaaWSlvdizGQJAj8V0JZl3JOGcY5CG7kmEb_U5Ox5O5Y4ocQnDR7_KtGQcX0M_sZFHvsDeiDwEOL0drSBipGgE6GO4zVkeLVrMSmIfSWKlz425TnbKgJD9n6w0n3XCNF_0cJu8mpzsowbmMTfQc80qPg6meUHCgumEA5j4p-CMf8AoH5mbDRZb-0fnQSNClSWTkLAg683odyeQrMLoijmTqw93BvKfhaOHULDxsjl8p-n25D4d84WsNR7Lz8qhV3GWd10TglA19_Tx415lUx5avYWbcSUZ3p8KiEe32mCeyOdLAc1_U-9IKD9bLV6XLSvX2sMatkk9Dx4H2NC2eHJpbQ2yXPrrs9NAcHCGGwmB5mEGqkn_bYf-SQNB4mlr0v9m8Zb7hHWom2ZVK9TOUSdvPwMnd43VAQq7MzeC7n52QPFJctrgeC-fleiiE0Hq--TkqUXiVRk37VLryxewCj-xbWNO2AzjobfgcQL0AbGgyURbLvBu6on159t3a1NLvB4cYEvVwJa7rQOiJerokQJkvM4clIdP_9jW0z_EzOzYjAtTAVNdf89Z7WkDPMpJYoV3q7STDcd6Y-4YCEtNbeAKGZivWb6JK1DI3LOlFjJQM1tPVpDYrBd7NAb2x8mf4x94fX9jJGs-AmeedsIxipjlOp7uxxGinHQXWe0shiuK8a50DqM9lIJngT9OYlsaNFgV0bfzF5CLIME1aWJmR3WUIO8Aww-_p5y-W2Xk9ur_OQg113CoBFMKIOtOTExeJxn0Ht9oaGc5Tr9ezOkQ0KDUj_4DNHeSD0mCS_9G0ezuBcmRWUVO1nyXbs9xjmb6oYpm5_r4XWAd_jfP50rsOUNUtSJ2wT6jTXH5WSstYl2baupTYjsiRxOKL8zgZtCkEHSB2mKrKqlG9gLvHxKCyVDAO7T2MDHP3PIvOKA_IeliBJcHDT3_koN_nsJvMBEqWqk7yCIE6mB8be2K2cEEz5VEEZdxW4s8125-AheDzDagHy5SYiKLRzxpGdiBMlqvppIUEG1zL36rvgBOJf5lw3U7sdjdsvqjqYDchjderc0n3xPhNGKJ-7GpgJlEf96t-lyV6R5t_XPaO-RlEri4_b3IVBMlfHvJzvv0r5JYw0jxBp8mX22px4y4g0iBuGRnlQby-V8loNWlO-8PwYCrpUHfREQMzrwcRdmFulmqpby8gnqp71Ev0Vb8OhAK6CX1RQK_beviPduxEGICH41USH2AyqDSRPxZE11e0mzYICHJ3jIXomxG6M06qP42rWjxXeXWrX_2gsLXwmHt8oHW_08N4xGau_Oi3gLukqALHNAYHTCbMCx2uLt-bHMcwr3bLWohb56VRPqXa2A6HuBFnJkLj7ql8D7mdeKdpt67zXypL6EIhCsAGfVKqex2xTRCwnUcAYC5wEKY0lav4xNlTXLjOQALaHQVNVVv_Yvossef1AkL2A-KmkRY3LwIEDQ211n8G5RpT_QFHFrv41-e8qevsSF8Fhm2ZL6CG1MHn6Kva23-BGtFq51b3KEAOiLf-21JrIR_qamiLWFfms7suo6jGWplOGB-s57utF6EmklZ6RDipwerNY8s09ycmnyEcgoNwvMlYMnPtOsmMQoG3mURb1J6ozHzIh72WrJiqfyWynAW7xQ3GzJQ7QsHrkxoTazOiVX2OtFjr_Cbc0QXta5qSr2VmHV00Zhe2OsDah2nOmav6OH-_NR6J_xx2l3ME'];

// Verifica que el token no esté vacío
if (empty($accessToken)) {
    echo json_encode(['error' => 'Access token no proporcionado.']);
    exit;
}

// URL de la API
$url = 'https://api.ationet.com/users'; // Reemplaza con tu API URL

// Inicializar cURL
$ch = curl_init();

// Datos de la petición (JSON)
$data = [
    'email' => $email,
    'password' => $password
    // Agrega aquí otros datos que la API pueda requerir
];
$postData = json_encode($data);

// Configurar cURL para la petición POST
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Encabezados
$headers = [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Ejecutar cURL
$response = curl_exec($ch);

// Manejar errores de cURL
if (curl_errno($ch)) {
    $error = ['error' => 'Error cURL: ' . curl_error($ch)];
    if ($curl_info = curl_getinfo($ch)) {
        $error['curl_info'] = $curl_info;
    }
    curl_close($ch);
    echo json_encode($error);
    exit;
}

// Obtener el código de estado HTTP
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_info = curl_getinfo($ch);

// Cerrar cURL
curl_close($ch);

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Manejar errores de decodificación JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => "Error JSON: " . json_last_error_msg(), 'response' => $response];
    exit;
}

// Manejar errores HTTP y mostrar la respuesta de la API (JSON)
if ($http_status != 200) {
    echo json_encode(['error' => 'Error HTTP: ' . $http_status, 'response' => $data, 'curl_info' => $curl_info];
    exit;
}

// Devolver la respuesta de la API (JSON)
echo json_encode($data);
?>