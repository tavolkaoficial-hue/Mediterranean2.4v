<?php
// Silenciar cualquier error o warning HTML para no romper la respuesta JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

$host     = 'localhost';
$port     = '8889';$dbname   = 'mediterranean';
$user     = 'root';$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user,$password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$sucursal = strtolower(trim($_GET['sucursal'] ?? 'centro'));
$codigo   = trim($_GET['codigo'] ?? '');

if (empty($codigo)) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Código no proporcionado']);
    exit;
}

$tablas_permitidas = ['centro', 'kennedy', 'norte'];
if (!in_array($sucursal,$tablas_permitidas, true)) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Sucursal no válida']);
    exit;
}

try {
    $stmt =$pdo->prepare("SELECT * FROM `$sucursal` WHERE sku = :cod OR codigo_qr = :cod OR nfc_id = :cod OR id = :cod LIMIT 1");
    $stmt->execute([':cod' =>$codigo]);
    $producto =$stmt->fetch();

    ob_end_clean();
    if ($producto) {
        echo json_encode([
            'status' => 'success',
            'data'   => $producto
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Producto no encontrado en la sucursal ' . ucfirst($sucursal)
        ]);
    }
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}
?>