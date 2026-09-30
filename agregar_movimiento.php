<?php
<<<<<<< HEAD
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
=======
// === CONFIGURACIÓN ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// === CONEXIÓN A DB ===
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mediterranean";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos: " . $conn->connect_error]);
    exit;
}

// Aseguramos compatibilidad con UTF8MB4
$conn->set_charset("utf8mb4");

// Habilitar reporte de errores MySQLi (depuración)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// === OBTENER DATOS DEL FORMULARIO ===
$producto_id = intval($_POST['productos'] ?? 0);
$tipo        = trim($_POST['tipo'] ?? '');
$cantidad    = intval($_POST['cantidad'] ?? 0);
$comentario  = trim($_POST['comentario'] ?? '');
$sucursal    = trim($_POST['sucursal'] ?? '');

// === VALIDACIONES ===
$errores = [];

if ($producto_id <= 0) $errores[] = "Producto inválido (productos: $producto_id)";
if (!in_array($tipo, ['Entrada', 'Salida'])) $errores[] = "Tipo de movimiento inválido (tipo: $tipo)";
if ($cantidad <= 0) $errores[] = "Cantidad inválida (cantidad: $cantidad)";
if (empty($sucursal)) $errores[] = "Sucursal no puede estar vacía";

if (count($errores) > 0) {
    echo json_encode(["status" => "error", "message" => implode(", ", $errores)]);
    exit;
}

// === VERIFICAR STOCK ACTUAL ===
try {
    $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Producto no encontrado (ID: $producto_id)"]);
        exit;
    }

    $row = $res->fetch_assoc();
    $stock_actual = intval($row['stock']);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Error al obtener stock: " . $e->getMessage()]);
    exit;
}

// === VERIFICAR STOCK EN SALIDAS ===
if ($tipo === 'Salida' && $stock_actual < $cantidad) {
    echo json_encode(["status" => "error", "message" => "Stock insuficiente. Actual: $stock_actual, requerido: $cantidad"]);
    exit;
}

// === INSERTAR MOVIMIENTO ===
try {
    $stmt = $conn->prepare("INSERT INTO movimientos (productos, tipo, cantidad, comentario, sucursal, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bind_param("isiss", $producto_id, $tipo, $cantidad, $comentario, $sucursal);

    if ($stmt->execute()) {
        // === ACTUALIZAR STOCK ===
        $nuevo_stock = ($tipo === 'Entrada') ? $stock_actual + $cantidad : $stock_actual - $cantidad;

        $upd = $conn->prepare("UPDATE productos SET stock = ?, fecha_actualizacion = NOW() WHERE id = ?");
        if (!$upd) {
            throw new Exception("Movimiento registrado pero no se pudo actualizar stock: " . $conn->error);
        }
        $upd->bind_param("ii", $nuevo_stock, $producto_id);
        $upd->execute();
        $upd->close();

        echo json_encode([
            "status" => "success",
            "message" => "Movimiento registrado correctamente",
            "nuevo_stock" => $nuevo_stock
        ]);
    } else {
        throw new Exception("No se pudo registrar el movimiento: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
