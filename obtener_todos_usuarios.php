<?php
<<<<<<< HEAD
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
    $usuarios = $stmt->fetchAll();
    echo json_encode($usuarios);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
=======
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT 
                id, 
                nombre_completo, 
                correo, 
                telefono, 
                rol, 
                estado, 
                token_registro, 
                foto_biometrica, 
                direccion, 
                seguro_social 
            FROM usuarios 
            ORDER BY id DESC";

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formato de respuesta estandarizado
    echo json_encode([
        'success' => true,
        'users'   => $usuarios
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500); // Código de error del servidor
    echo json_encode([
        'success' => false,
        'error'   => 'Error al consultar usuarios: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
}
?>