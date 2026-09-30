<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$port = '8889'; // Puerto MAMP
$db   = 'mediterranean';
$user = 'root';
$pass = 'root'; 

$sucursal = isset($_GET['sucursal']) ? strtolower(trim($_GET['sucursal'])) : '';

$tablasPermitidas = ['centro', 'kennedy', 'norte'];

if (!in_array($sucursal, $tablasPermitidas)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Sucursal no válida'
    ]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Consulta de los productos en la tabla seleccionada
    $stmt = $pdo->prepare("SELECT sku, nombre, categoria, precio_compra, precio_venta, stock, reorder, estado FROM {$sucursal} ORDER BY nombre ASC");
    $stmt->execute();
    $productos = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'sucursal' => $sucursal,
        'productos' => $productos
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al consultar productos: ' . $e->getMessage()
    ]);
}
?>