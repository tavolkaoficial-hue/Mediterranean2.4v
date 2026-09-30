<?php
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