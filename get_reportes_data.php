<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$port = '8889'; // Puerto de MAMP
$db   = 'mediterranean';
$user = 'root';
$pass = 'root'; // Ajusta la contraseña según tu configuración de MAMP

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Obtener el total de usuarios en el sistema
    $sqlUsuarios = "SELECT COUNT(*) AS total_usuarios FROM usuarios WHERE estado = 'activo'";
    $stmtUsuarios = $pdo->query($sqlUsuarios);
    $totalUsuarios = $stmtUsuarios->fetch()['total_usuarios'];

    // 2. Obtener los últimos 10 movimientos
    $sqlMovimientos = "
        SELECT 
            m.id,
            m.tipo AS tipo_movimiento,
            m.producto_nombre,
            m.cantidad,
            m.sucursal,
            m.sucursal_destino,
            m.fecha,
            'Completado' AS estado
        FROM movimientos m
        ORDER BY m.fecha DESC
        LIMIT 10
    ";
    $stmtMov = $pdo->query($sqlMovimientos);
    $movimientos = $stmtMov->fetchAll();

    echo json_encode([
        'status' => 'success',
        'metrics' => [
            'total_usuarios' => (int)$totalUsuarios
        ],
        'reportes' => $movimientos
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>