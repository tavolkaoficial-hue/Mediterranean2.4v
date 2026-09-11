<?php
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        echo json_encode(["success" => false, "error" => "ID no recibido."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Candidato eliminado con éxito."]);
        } else {
            echo json_encode(["success" => false, "error" => "No se encontró el candidato."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Error al eliminar: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Método no permitido."]);
}
?>