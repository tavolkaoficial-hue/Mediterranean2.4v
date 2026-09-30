<?php
// Evita que warnings o notices arruinen la salida JSON
ob_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Configuración de conexión MAMP
$host = '127.0.0.1';
$port = '8889';
$db   = 'mediterranean';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'Error de conexión MySQL: ' . $e->getMessage()]);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$sucursalesValidas = ['kennedy', 'centro', 'norte'];

// -------------------------------------------------------------
// 1. CARGAR DATOS INICIALES (Clientes, Sucursales, Productos y Pedidos)
// -------------------------------------------------------------
if ($action === 'get_initial_data') {
    $clientes = [];
    $productosUnificados = [];
    $pedidos = [];

    // Cargar Clientes de la BD
    try {
        $stmtC = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");
        $clientes = $stmtC->fetchAll();
    } catch (Exception $e) {
        $clientes = [];
    }

    // Cargar Productos consultando las tablas de sucursales
    foreach ($sucursalesValidas as $suc) {
        try {
            $stmtP = $pdo->query("SELECT id, nombre, IFNULL(sku, 'SIN-SKU') AS sku, IFNULL(stock, 0) AS cantidad FROM `$suc` WHERE estado = 'Activo'");
            while ($p = $stmtP->fetch()) {
                $p['sucursal'] = $suc;
                $productosUnificados[] = $p;
            }
        } catch (Exception $e) {
            // Manejo silencioso si la sucursal falla
        }
    }

    // Cargar Órdenes de Picking
    try {
        $stmtPed = $pdo->query("SELECT * FROM picking ORDER BY id DESC");
        while ($p = $stmtPed->fetch()) {
            $pedidos[] = [
                'id' => $p['id'],
                'cliente_nombre' => $p['cliente'],
                'sucursal_id' => $p['sucursal'],
                'sucursal_nombre' => ucfirst($p['sucursal']),
                'producto_id' => $p['producto_id'],
                'producto_nombre' => $p['producto_nombre'],
                'ubicacion' => $p['ubicacion'],
                'cantidad' => $p['cantidad'],
                'estado' => $p['estado']
            ];
        }
    } catch (Exception $e) {
        $pedidos = [];
    }

    // Lista de sucursales para los selectores
    $listaSucursales = [
        ['id' => 'kennedy', 'nombre' => 'Kennedy'],
        ['id' => 'centro',  'nombre' => 'Centro'],
        ['id' => 'norte',   'nombre' => 'Norte']
    ];

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'clientes' => $clientes,
        'sucursales' => $listaSucursales,
        'productos' => $productosUnificados,
        'pedidos' => $pedidos
    ]);
    exit;
}

// -------------------------------------------------------------
// 2. OBTENER STOCK Y UBICACIÓN POR SUCURSAL Y PRODUCTO
// -------------------------------------------------------------
if ($action === 'get_product_branch_info') {
    $productoId = intval($_GET['producto_id'] ?? 0);
    $sucursal = $_GET['sucursal_id'] ?? '';

    if (in_array($sucursal, $sucursalesValidas) && $productoId > 0) {
        $stmt = $pdo->prepare("SELECT 'Bodega Principal' AS ubicacion, IFNULL(stock, 0) AS cantidad FROM `$sucursal` WHERE id = ?");
        $stmt->execute([$productoId]);
        $info = $stmt->fetch();

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $info ?: ['ubicacion' => 'General', 'cantidad' => 0]
        ]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Sucursal o Producto inválido']);
    }
    exit;
}

// -------------------------------------------------------------
// 3. REGISTRAR UN NUEVO CLIENTE
// -------------------------------------------------------------
if ($action === 'add_client') {
    $nombreCliente = trim($_POST['nombre'] ?? '');

    if (empty($nombreCliente)) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'El nombre del cliente no puede estar vacío']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre) VALUES (?)");
        $stmt->execute([$nombreCliente]);
        
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'nombre' => $nombreCliente
        ]);
    } catch (PDOException $e) {
        if (ob_get_length()) ob_clean();
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'error' => 'El cliente ya se encuentra registrado']);
        } else {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    exit;
}

// -------------------------------------------------------------
// 4. CREAR UNA NUEVA ORDEN DE PICKING
// -------------------------------------------------------------
if ($action === 'create_order') {
    $cliente = trim($_POST['cliente'] ?? '');
    $sucursal = $_POST['sucursal_id'] ?? '';
    $productoId = intval($_POST['producto_id'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $ubicacion = $_POST['ubicacion'] ?? 'General';

    if (!$cliente || !in_array($sucursal, $sucursalesValidas) || $productoId <= 0 || $cantidad <= 0) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Por favor complete todos los campos correctamente.']);
        exit;
    }

    $stmtProd = $pdo->prepare("SELECT nombre FROM `$sucursal` WHERE id = ?");
    $stmtProd->execute([$productoId]);
    $prod = $stmtProd->fetch();
    $productoNombre = $prod ? $prod['nombre'] : 'Producto General';

    $stmtInsert = $pdo->prepare("INSERT INTO picking (cliente, sucursal, producto_id, producto_nombre, ubicacion, cantidad, estado) VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')");
    $stmtInsert->execute([$cliente, $sucursal, $productoId, $productoNombre, $ubicacion, $cantidad]);

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// -------------------------------------------------------------
// 5. PROCESAR DESPACHO Y DESCONTAR DEL STOCK
// -------------------------------------------------------------
if ($action === 'process_picking') {
    $pedidoId = intval($_POST['pedido_id'] ?? 0);

    $stmtPick = $pdo->prepare("SELECT * FROM picking WHERE id = ? AND estado = 'Pendiente'");
    $stmtPick->execute([$pedidoId]);
    $pedido = $stmtPick->fetch();

    if (!$pedido) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'La orden no existe o ya fue despachada.']);
        exit;
    }

    $sucursal = $pedido['sucursal'];
    $productoId = $pedido['producto_id'];
    $cantidad = $pedido['cantidad'];

    $pdo->beginTransaction();

    try {
        $stmtStock = $pdo->prepare("UPDATE `$sucursal` SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmtStock->execute([$cantidad, $productoId, $cantidad]);

        if ($stmtStock->rowCount() === 0) {
            $pdo->rollBack();
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'error' => 'Stock insuficiente en la sucursal ' . ucfirst($sucursal)]);
            exit;
        }

        $stmtUpdatePick = $pdo->prepare("UPDATE picking SET estado = 'Entregado', fecha_despacho = NOW() WHERE id = ?");
        $stmtUpdatePick->execute([$pedidoId]);

        $pdo->commit();
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'message' => 'Despacho registrado y stock descontado de ' . ucfirst($sucursal)]);
    } catch (Exception $e) {
        $pdo->rollBack();
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Error en la transacción: ' . $e->getMessage()]);
    }
    exit;
}