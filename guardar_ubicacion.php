<?php
// Silenciar cualquier error o warning HTML que contamine la respuesta JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Limpiar el búfer y enviar cabecera JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$port = '8889';$dbname = 'mediterranean';
$user = 'root';$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user,$password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD: ' . $e->getMessage()]);
    exit;
}

// Recibir datos JSON desde el formulario JS
$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);

if (!$data) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Los datos recibidos no están en formato JSON válido.']);
    exit;
}

$sku       = trim($data['sku'] ?? '');
$sucursal  = strtolower(trim($data['sucursal'] ?? ''));
$cantidad  = intval($data['cantidad'] ?? 0);
$ubicacion = trim($data['ubicacion'] ?? 'General');

// Validaciones corregidas (Línea 41 solucionada con '||')
if (empty($sku) || empty($sucursal) || $cantidad <= 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'El SKU, la sucursal y la cantidad deben ser válidos.']);
    exit;
}

$tablasPermitidas = ['kennedy', 'centro', 'norte'];
if (!in_array($sucursal,$tablasPermitidas)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => "La sucursal '$sucursal' no es válida."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Buscar el producto en la sucursal seleccionada
    $stmt =$pdo->prepare("SELECT id, nombre, stock FROM `$sucursal` WHERE sku = :sku OR id = :sku OR codigo_qr = :sku OR nfc_id = :sku LIMIT 1");
    $stmt->execute([':sku' =>$sku]);
    $producto =$stmt->fetch();

    if (!$producto) {$pdo->rollBack();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => "Producto '$sku' no encontrado en sucursal " . ucfirst($sucursal)]);
        exit;
    }

    $nuevoStock     = $producto['stock'] +$cantidad;
    $productoId     =$producto['id'];
    $nombreProducto =$producto['nombre'];

    // 2. Actualizar stock y campo 'ubicacion' en la tabla de la sucursal
    $updateStmt =$pdo->prepare("UPDATE `$sucursal` SET stock = :nuevoStock, ubicacion = :ubicacion, updated_at = NOW() WHERE id = :id");
    $updateStmt->execute([
        ':nuevoStock' => $nuevoStock,
        ':ubicacion'  => $ubicacion,
        ':id'         => $productoId
    ]);

    // 3. Registrar el historial en la tabla 'movimientos'
    $comentario = "Asignación de ubicación / Putaway en: " . $ubicacion;
    $sucursalNombre = ucfirst($sucursal);

    $movStmt =$pdo->prepare("
        INSERT INTO `movimientos` 
        (producto_id, producto_nombre, tipo, cantidad, comentario, sucursal, sucursal_destino, fecha) 
        VALUES 
        (:prod_id, :prod_nombre, 'Entrada', :cantidad, :comentario, :sucursal, :sucursal_destino, NOW())
    ");

    $movStmt->execute([
        ':prod_id'          => $productoId,
        ':prod_nombre'      => $nombreProducto,
        ':cantidad'         => $cantidad,
        ':comentario'       => $comentario,
        ':sucursal'         => $sucursalNombre,
        ':sucursal_destino' => $sucursalNombre
    ]);

    $pdo->commit();

    // Salida JSON limpia
    ob_end_clean();
    echo json_encode([
        'success'    => true,
        'message'    => "Producto guardado correctamente en la ubicación '$ubicacion'.",
        'nuevoStock' => $nuevoStock
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {$pdo->rollBack();
    }
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error SQL al procesar: ' . $e->getMessage()]);
}
?>