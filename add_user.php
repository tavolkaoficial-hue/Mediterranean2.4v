<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre_completo'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $rol = $_POST['rol'] ?? 'Empleado';
    $estado = $_POST['estado'] ?? 'pendiente';
    
    // Generar un token único de registro biométrico
    $token = bin2hex(random_bytes(16));

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, correo, telefono, rol, estado, token_registro) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $correo, $telefono, $rol, $estado, $token]);

        echo json_encode([
            "success" => true,
            "message" => "Usuario registrado con éxito",
            "token" => $token
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error al registrar (posible correo duplicado): " . $e->getMessage()
        ]);
    }
}
?>