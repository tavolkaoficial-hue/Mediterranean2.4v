<?php
// conexion.php
$host = "localhost";
$port = 8889; // Puerto según tu phpMyAdmin
$user = "root";
$pass = "root"; // Ajusta según tu configuración de MAMP
$db   = "mediterranean";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Error de conexión: " . $conn->connect_error]));
}

// Recibir datos en formato JSON desde JS (fetch/axios)
$input = json_decode(file_get_contents('php_input'), true);

if (!$input) {
    // Si envías por $_POST tradicional:
    $input = $_POST;
}

$cliente_id  = isset($input['cliente_id']) ? intval($input['cliente_id']) : 0;
$sucursal_id = isset($input['sucursal_id']) ? intval($input['sucursal_id']) : 0;
$productos   = isset($input['productos']) ? $input['productos'] : []; // Array con items

if ($cliente_id <= 0 || empty($productos)) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos (cliente o productos faltantes)."]);
    exit;
}

// Iniciar transacción para asegurar integridad
$conn->begin_transaction();

try {
    // 1. Insertar el encabezado del pedido
    $stmtPedido = $conn->prepare("INSERT INTO pedidos (cliente_id, fecha) VALUES (?, NOW())");
    $stmtPedido->bind_param("i", $cliente_id);
    $stmtPedido->execute();
    
    $pedido_id = $conn->insert_id; // ID autogenerado del pedido
    $stmtPedido->close();

    // 2. Insertar el detalle de los productos (pedidos_detalle)
    $stmtDetalle = $conn->prepare("INSERT INTO pedidos_detalle (pedido_id, producto_id, sucursal_id, cantidad, ubicacion) VALUES (?, ?, ?, ?, ?)");

    foreach ($productos as $item) {
        $producto_id = intval($item['producto_id']);
        $cantidad    = intval($item['cantidad']);
        $ubicacion   = isset($item['ubicacion']) ? $item['ubicacion'] : 'Bodega Principal';

        $stmtDetalle->bind_param("iiiis", $pedido_id, $producto_id, $sucursal_id, $cantidad, $ubicacion);
        $stmtDetalle->execute();
    }
    
    $stmtDetalle->close();

    // Confirmar cambios
    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Pedido guardado con éxito", "pedido_id" => $pedido_id]);

} catch (Exception $e) {
    // Revertir si ocurre un error
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Error al guardar: " . $e->getMessage()]);
}

$conn->close();
?>