<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php'; // Usa PDO ($pdo)

$producto_id = isset($_GET['productos']) ? intval($_GET['productos']) : 0;
$desde       = isset($_GET['desde']) ? $_GET['desde'] : null;
$hasta       = isset($_GET['hasta']) ? $_GET['hasta'] : null;
$sucursal    = isset($_GET['sucursal']) ? $_GET['sucursal'] : '';
$tipo        = isset($_GET['tipo']) ? $_GET['tipo'] : '';

try {
    $sql = "SELECT 
                m.id, 
                m.fecha, 
                m.tipo, 
                m.cantidad, 
                m.comentario, 
                m.sucursal,
                COALESCE(p.nombre, CONCAT('Producto #', m.productos)) AS nombre_producto
            FROM movimientos m
            LEFT JOIN productos p ON m.productos = p.id
            WHERE 1=1";
            
    $params = [];

    if ($producto_id > 0) {
        $sql .= " AND m.productos = :producto_id";
        $params[':producto_id'] = $producto_id;
    }

    if (!empty($desde) && !empty($hasta)) {
        $sql .= " AND DATE(m.fecha) BETWEEN :desde AND :hasta";
        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;
    }

    if (!empty($sucursal)) {
        $sql .= " AND m.sucursal = :sucursal";
        $params[':sucursal'] = $sucursal;
    }

    if (!empty($tipo)) {
        $sql .= " AND m.tipo = :tipo";
        $params[':tipo'] = $tipo;
    }

    $sql .= " ORDER BY m.fecha DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["movimientos" => $movimientos]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener datos: " . $e->getMessage()]);
}
?>