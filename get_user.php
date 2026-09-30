<?php
session_start();
header('Content-Type: application/json');

$db_host = 'localhost';
$db_port = '8889';
$db_name = 'mediterranean';
$db_user = 'root';
$db_pass = 'root';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
} catch (PDOException $e) {
    echo json_encode(['nombre' => 'Administrador', 'iniciales' => 'AD']);
    exit;
}

$nombre_usuario = "Administrador";

if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) $nombre_usuario = $user['nombre_completo'];
} else {
    $stmt = $pdo->query("SELECT nombre_completo FROM usuarios ORDER BY id ASC LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) $nombre_usuario = $user['nombre_completo'];
}

// Sacar iniciales
$partes = explode(' ', trim($nombre_usuario));
$iniciales = (count($partes) >= 2) 
    ? mb_strtoupper(mb_substr($partes[0],0,1).mb_substr($partes[1],0,1))
    : mb_strtoupper(mb_substr($nombre_usuario,0,2));

echo json_encode(['nombre' => $nombre_usuario, 'iniciales' => $iniciales]);