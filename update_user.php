<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre_completo'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $rol = $_POST['rol'] ?? 'Empleado';
    $estado = $_POST['estado'] ?? 'pendiente';
    $direccion = $_POST['direccion'] ?? '';
    $seguro_social = $_POST['seguro_social'] ?? '';

    if (!$id) {
        echo json_encode(["success" => false, "message" => "ID de usuario no proporcionado"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, correo = ?, telefono = ?, rol = ?, estado = ?, direccion = ?, seguro_social = ? WHERE id = ?");
        $stmt->execute([$nombre, $correo, $telefono, $rol, $estado, $direccion, $seguro_social, $id]);

        echo json_encode(["success" => true, "message" => "Usuario actualizado correctamente"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>