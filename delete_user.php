<?php
<<<<<<< HEAD
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

=======
include 'conexion.php'; 
header('Content-Type: application/json; charset=utf-8');

// Leer los datos JSON recibidos por la petición
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
<<<<<<< HEAD
    echo json_encode(["success" => false, "message" => "ID no válido"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["success" => true, "message" => "Expediente eliminado correctamente"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
=======
    echo json_encode(["success" => false, "message" => "ID no recibido"]);
    exit;
}

// 1. Corregido: Usar 'correo' y 'nombre_completo' en lugar de 'email' y 'nombre'
$stmt_info = $conn->prepare("SELECT correo, nombre_completo FROM usuarios WHERE id = ?");
$stmt_info->bind_param("i", $id);
$stmt_info->execute();
$resultado = $stmt_info->get_result();
$usuario = $resultado->fetch_assoc();
$stmt_info->close();

if (!$usuario) {
    echo json_encode(["success" => false, "message" => "El usuario no existe"]);
    exit;
}

// 2. Eliminar realmente el registro de la base de datos
$stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    
    // 3. Enviar notificación usando los nombres correctos de las llaves del array
    enviarNotificacionSilenciosa($usuario['correo'], $usuario['nombre_completo']);

    echo json_encode(["success" => true, "message" => "Usuario eliminado correctamente de la base de datos."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar en la base de datos"]);
}

$conn->close();

/**
 * Función para enviar el correo de manera interna sin interrumpir la respuesta JSON
 */
function enviarNotificacionSilenciosa($emailDestino, $nombreUsuario) {
    if (empty($emailDestino)) return;
    
    $asunto = "Notificación de eliminación de cuenta";
    $mensaje = "Hola $nombreUsuario, te informamos que tu expediente ha sido eliminado del sistema corporativo.";
    
    $headers = "From: tavolkaoficial@gmail.com\r\n";
    $headers .= "Reply-To: tavolkaoficial@gmail.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    @mail($emailDestino, $asunto, $mensaje, $headers);
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
}
?>