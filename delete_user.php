<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID no válido"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["success" => true, "message" => "Expediente eliminado correctamente"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>