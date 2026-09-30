<?php
<<<<<<< HEAD
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
if ($limit <= 0) $limit = 15;

try {
    $sql = "SELECT 
                id, 
                fecha, 
                tipo, 
                cantidad, 
                comentario, 
                sucursal,
                sucursal_destino,
                producto_id,
                producto_nombre
            FROM movimientos
            ORDER BY fecha DESC 
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($movimientos as &$m) {
        if (!empty($m['fecha'])) {
            $m['fecha'] = date("Y-m-d H:i", strtotime($m['fecha']));
        }
    }

    echo json_encode($movimientos);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error al consultar movimientos: " . $e->getMessage()]);
}
?>
=======
include 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

$limit = intval($_GET['limit'] ?? 10);

$sql = "SELECT m.id, m.tipo AS tipo_movimiento, m.cantidad, m.comentario, m.fecha,
               p.nombre AS nombre_producto, s.nombre AS nombre_sucursal
        FROM movimientos m
        JOIN productos p ON m.productos = p.id
        LEFT JOIN sucursales s ON m.sucursal = s.nombre
        ORDER BY m.fecha DESC
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();

$result = $stmt->get_result();
$movimientos = [];
while ($row = $result->fetch_assoc()) {
    $row['fecha'] = date("Y-m-d H:i", strtotime($row['fecha']));
    $movimientos[] = $row;
}

echo json_encode($movimientos);

$stmt->close();
$conn->close();
?>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
