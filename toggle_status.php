<?php
<<<<<<< HEAD
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
=======
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos enviados en el cuerpo JSON
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        echo json_encode([
            "success" => false, 
            "error"   => "ID de usuario no recibido."
        ]);
        exit;
    }

    try {
        // Alternar estado usando CASE de SQL:
        // Si está 'activo' -> pasa a 'inactivo'
        // Si está 'inactivo' o 'pendiente' -> pasa a 'activo'
        $sql = "UPDATE usuarios 
                SET estado = CASE 
                    WHEN estado = 'activo' THEN 'inactivo'
                    ELSE 'activo'
                END 
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "success" => true, 
                "message" => "Estado actualizado correctamente."
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "error"   => "No se encontró el usuario o no hubo cambios."
            ]);
        }

    } catch (PDOException $e) {
        echo json_encode([
            "success" => false, 
            "error"   => "Error en la base de datos: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "success" => false, 
        "error"   => "Método no permitido."
    ]);
}
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
?>