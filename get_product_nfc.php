<?php
// Silenciar warnings/errores HTML que puedan romper la respuesta JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

// 1. Validar autenticación de sesión
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'success' => false, 
        'message' => 'Usuario no autenticado'
    ]);
    exit;
}

// 2. Conexión a la base de datos
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
    echo json_encode([
        'status'  => 'error',
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
    exit;
}

// 3. Captura y validación de parámetros
$sucursal = strtolower(trim($_GET['sucursal'] ?? 'centro'));$nfc_code = trim($_GET['nfc'] ?? $_GET['sku'] ?? '');

if (empty($nfc_code)) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'success' => false, 
        'message' => 'Código NFC/SKU no proporcionado'
    ]);
    exit;
}

$tablas_permitidas = ['centro', 'kennedy', 'norte'];
if (!in_array($sucursal,$tablas_permitidas, true)) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'success' => false,
        'message' => 'Sucursal no válida'
    ]);
    exit;
}

// 4. Búsqueda del producto en la tabla de la sucursal
try {
    $stmt =$pdo->prepare("
        SELECT id, sku, nombre, stock, ubicacion 
        FROM `$sucursal` 
        WHERE nfc_id = :cod OR sku = :cod OR codigo_qr = :cod OR id = :cod 
        LIMIT 1
    ");
    $stmt->execute([':cod' =>$nfc_code]);
    $producto =$stmt->fetch();

    ob_end_clean();
    if ($producto) {
        echo json_encode([
            'status'   => 'success',
            'success'  => true,
            'data'     => $producto,
            'producto' => $producto // Mantenido por compatibilidad
        ]);
    } else {
        echo json_encode([
            'status'  => 'error',
            'success' => false,
            'message' => 'No se encontró el producto en la sucursal ' . ucfirst($sucursal)
        ]);
    }
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'success' => false, 
        'message' => 'Error SQL: ' . $e->getMessage()
    ]);
}
?>