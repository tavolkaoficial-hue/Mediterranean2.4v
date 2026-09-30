<?php
$host = 'localhost';
$port = '8889'; // <--- El puerto clave de MAMP
$dbname = 'mediterranean'; // <--- El nombre exacto de tu base de datos
$username = 'root'; // <--- Usuario por defecto en MAMP
$password = 'root'; // <--- En MAMP, la contraseña de root suele ser 'root' (si te da error de acceso, prueba a dejarla vacía '')

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $e->getMessage()]);
    exit;
}
?>