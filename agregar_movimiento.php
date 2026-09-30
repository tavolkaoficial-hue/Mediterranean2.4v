<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

$tipo = $_POST['tipo'] ?? 'Entrada';
$sucursalOrigen = strtolower($_POST['sucursal'] ?? '');
$sucursalDestino = strtolower($_POST['sucursal_destino'] ?? '');
$productoId = intval($_POST['productos'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

$tablasPermitidas = [
    'kennedy' => 'kennedy',
    'centro'  => 'centro',
    'norte'   => 'norte'
];

if (!isset($tablasPermitidas[$sucursalOrigen]) || $productoId <= 0 || $cantidad <= 0) {
    echo json_encode(["success" => false, "message" => "Datos de entrada no válidos."]);
    exit;
}

$tablaOrigen = $tablasPermitidas[$sucursalOrigen];

try {
    $pdo->beginTransaction();

    // 1. Obtener nombre y stock actual del producto en la sucursal de origen
    $stmtProd = $pdo->prepare("SELECT nombre, stock FROM `{$tablaOrigen}` WHERE id = :id");
    $stmtProd->execute([':id' => $productoId]);
    $prodOrigen =$stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$prodOrigen) {
        throw new Exception("El producto no existe en la sucursal de origen ({$sucursalOrigen}).");
    }

    $nombreProducto =$prodOrigen['nombre'];
    $stockOrigen = intval($prodOrigen['stock']);

    // 2. Procesar según tipo de movimiento
    if ($tipo === 'Entrada') {
        $stmtUpd =$pdo->prepare("UPDATE `{$tablaOrigen}` SET stock = stock + :cant WHERE id = :id");
        $stmtUpd->execute([':cant' => $cantidad, ':id' =>$productoId]);

    } elseif ($tipo === 'Salida') {
        if ($stockOrigen <$cantidad) {
            throw new Exception("Stock insuficiente en {$sucursalOrigen}. Stock disponible: {$stockOrigen}");
        }
        $stmtUpd =$pdo->prepare("UPDATE `{$tablaOrigen}` SET stock = stock - :cant WHERE id = :id");
        $stmtUpd->execute([':cant' => $cantidad, ':id' =>$productoId]);

    } elseif ($tipo === 'Transferencia') {
        if (!isset($tablasPermitidas[$sucursalDestino])) {
            throw new Exception("Sucursal de destino no válida.");
        }
        if ($sucursalOrigen ===$sucursalDestino) {
            throw new Exception("La sucursal de origen y destino deben ser distintas.");
        }
        if ($stockOrigen <$cantidad) {
            throw new Exception("Stock insuficiente para transferir desde {$sucursalOrigen}. Stock actual: {$stockOrigen}");
        }

        $tablaDestino = $tablasPermitidas[$sucursalDestino];

        // Descontar stock de Origen
        $stmtDesc =$pdo->prepare("UPDATE `{$tablaOrigen}` SET stock = stock - :cant WHERE id = :id");
        $stmtDesc->execute([':cant' => $cantidad, ':id' =>$productoId]);

        // Verificar si existe el producto en Destino (por nombre)
        $stmtDestCheck =$pdo->prepare("SELECT id FROM `{$tablaDestino}` WHERE nombre = :nombre");
        $stmtDestCheck->execute([':nombre' =>$nombreProducto]);
        $prodDestino =$stmtDestCheck->fetch(PDO::FETCH_ASSOC);

        if ($prodDestino) {
            $stmtAum =$pdo->prepare("UPDATE `{$tablaDestino}` SET stock = stock + :cant WHERE id = :id");
            $stmtAum->execute([':cant' => $cantidad, ':id' =>$prodDestino['id']]);
        } else {
            // Si el producto no existe en la sucursal destino, se inserta
            $stmtIns =$pdo->prepare("INSERT INTO `{$tablaDestino}` (nombre, stock) VALUES (:nombre, :cant)");
            $stmtIns->execute([':nombre' => $nombreProducto, ':cant' =>$cantidad]);
        }
    }

    // 3. Registrar auditoría en la tabla general 'movimientos'
    $sqlMov = "INSERT INTO movimientos (fecha, tipo, cantidad, comentario, sucursal, sucursal_destino, producto_id, producto_nombre) 
               VALUES (NOW(), :tipo, :cantidad, :comentario, :sucursal, :sucursal_destino, :producto_id, :producto_nombre)";
    
    $stmtMov =$pdo->prepare($sqlMov);$stmtMov->execute([
        ':tipo'             => $tipo,
        ':cantidad'         => $cantidad,
        ':comentario'       => $comentario,
        ':sucursal'         => ucfirst($sucursalOrigen),
        ':sucursal_destino' => ($tipo === 'Transferencia') ? ucfirst($sucursalDestino) : null,
        ':producto_id'      => $productoId,
        ':producto_nombre'  => $nombreProducto
    ]);

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Movimiento registrado y stock actualizado con éxito."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {$pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>