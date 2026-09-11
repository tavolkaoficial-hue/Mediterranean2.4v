<?php
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = $_POST['id'] ?? null;
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $correo          = trim($_POST['correo'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');

    // Normalización del Rol:
    // Mapea 'Empleado' a 'Invitado' para mantener compatibilidad con la base de datos
    $rol_recibido    = trim($_POST['rol'] ?? 'Empleado');
    $rol             = ($rol_recibido === 'Empleado') ? 'Invitado' : $rol_recibido;

    $estado          = trim($_POST['estado'] ?? 'activo');
    $direccion       = trim($_POST['direccion'] ?? '');
    $seguro_social   = trim($_POST['seguro_social'] ?? '');

    // Validación básica
    if (!$id) {
        echo json_encode(["success" => false, "error" => "ID de usuario no especificado."]);
        exit;
    }

    if (empty($nombre_completo) || empty($correo)) {
        echo json_encode(["success" => false, "error" => "El nombre y el correo electrónico son obligatorios."]);
        exit;
    }

    try {
        $sql = "UPDATE usuarios 
                SET nombre_completo = :nombre, 
                    correo          = :correo, 
                    telefono        = :telefono, 
                    rol             = :rol, 
                    estado          = :estado, 
                    direccion       = :direccion, 
                    seguro_social   = :seguro_social
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'        => $nombre_completo,
            ':correo'        => $correo,
            ':telefono'      => $telefono,
            ':rol'           => $rol,
            ':estado'        => $estado,
            ':direccion'     => $direccion,
            ':seguro_social' => $seguro_social,
            ':id'            => $id
        ]);

        echo json_encode([
            "success" => true, 
            "message" => "Usuario actualizado correctamente."
        ]);

    } catch (PDOException $e) {
        // Manejo específico para correos duplicados
        if ($e->getCode() == 23000) {
            echo json_encode([
                "success" => false, 
                "error"   => "El correo electrónico ingresado ya está registrado por otro candidato."
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "error"   => "Error en la base de datos: " . $e->getMessage()
            ]);
        }
    }
} else {
    echo json_encode([
        "success" => false, 
        "error"   => "Método no permitido."
    ]);
}
?>