<?php
header('Content-Type: application/json');
$conexion = new mysqli("localhost", "tu_usuario", "tu_password", "tu_base_datos");

if ($conexion->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión"]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['accion']) && $input['accion'] === 'actualizar_rapido') {
    $id = intval($input['id']);
    $stock = intval($input['stock']);
    
    $stmt = $conexion->prepare("UPDATE inventario SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $stock, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al ejecutar query"]);
    }
} elseif (isset($input['accion']) && $input['accion'] === 'editar_completo') {
    $id = intval($input['id']);
    $barcode = $input['barcode'];
    $name = $input['name'];
    $location = $input['location'];
    $stock = intval($input['stock']);

    $stmt = $conexion->prepare("UPDATE inventario SET barcode = ?, name = ?, location = ?, stock = ? WHERE id = ?");
    $stmt->bind_param("sssii", $barcode, $name, $location, $stock, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
}
?>