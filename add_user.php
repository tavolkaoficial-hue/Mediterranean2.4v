<?php
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    
    // Normalización del Rol:
    // Si la interfaz envía 'Empleado', lo transformamos al valor 'Invitado' compatible con la BD
    $rol_recibido = trim($_POST['rol'] ?? 'Empleado');
    $rol = ($rol_recibido === 'Empleado') ? 'Invitado' : $rol_recibido;

    $estado = trim($_POST['estado'] ?? 'pendiente');
    $direccion = trim($_POST['direccion'] ?? '');
    $seguro_social = trim($_POST['seguro_social'] ?? '');

    // Validación básica de campos obligatorios
    if (empty($nombre) || empty($correo)) {
        echo json_encode([
            'success' => false, 
            'error' => 'El nombre completo y el correo son obligatorios.'
        ]);
        exit;
    }

    // Generar un token único criptográficamente seguro
    $token = bin2hex(random_bytes(16));

    // Construir el enlace completo de registro
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $generatedLink = "{$scheme}://{$host}/completar_registro.php?token={$token}";

    try {
        $sql = "INSERT INTO usuarios (nombre_completo, correo, telefono, rol, estado, direccion, seguro_social, token_registro) 
                VALUES (:nombre, :correo, :telefono, :rol, :estado, :direccion, :seguro_social, :token)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'        => $nombre,
            ':correo'        => $correo,
            ':telefono'      => $telefono,
            ':rol'           => $rol,
            ':estado'        => $estado,
            ':direccion'     => $direccion,
            ':seguro_social' => $seguro_social,
            ':token'         => $token
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'id'      => $newId,
            'token'   => $token,
            'link'    => $generatedLink,
            'message' => 'Candidato creado con éxito.'
        ]);

    } catch (PDOException $e) {
        // Manejo específico para correos o campos duplicados (Código de error SQL 23000)
        if ($e->getCode() == 23000) {
            echo json_encode([
                'success' => false, 
                'error'   => 'El correo electrónico ya se encuentra registrado.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error'   => 'Error en la base de datos: ' . $e->getMessage()
            ]);
        }
    }
} else {
    echo json_encode([
        'success' => false, 
        'error'   => 'Método de solicitud no permitido.'
    ]);
}
?>