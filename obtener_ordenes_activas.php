<?php
header('Content-Type: application/json');

// Conexión a MAMP (puerto 8889)
$host = 'localhost';
$port = '8889';
$db   = 'mediterranean';
$user = 'root'; 
$pass = 'root'; // Contraseña por defecto de MAMP en macOS

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para agrupar o traer las órdenes en estado 'Pendiente'
    $sql = "SELECT id, cliente, sucursal, producto_nombre, cantidad, estado, fecha_creacion 
            FROM picking 
            WHERE estado = 'Pendiente' 
            ORDER BY fecha_creacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $ordenes]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>