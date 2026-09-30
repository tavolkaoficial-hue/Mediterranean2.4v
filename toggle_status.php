<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if ($id) {
    // Consultar estado actual
    $stmt = $pdo->prepare("SELECT estado FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
        // Ciclo de estados: pendiente -> activo -> inactivo -> pendiente
        $nuevoEstado = 'pendiente';
        if ($user['estado'] === 'pendiente') $nuevoEstado = 'activo';
        elseif ($user['estado'] === 'activo') $nuevoEstado = 'inactivo';

        $update = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
        $update->execute([$nuevoEstado, $id]);

        echo json_encode(["success" => true, "nuevo_estado" => $nuevoEstado]);
        exit;
    }
}
echo json_encode(["success" => false]);
?>