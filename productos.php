<?php
<<<<<<< HEAD
// Desactivar impresión de errores HTML para no corromper la respuesta JSON
=======
// Desactivar impresión de errores HTML para evitar corromper la respuesta HTTP JSON
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $host = "127.0.0.1";
<<<<<<< HEAD
    $port = 8889; // Puerto MAMP
=======
    $port = 8889; // Cambia este puerto solo si MySQL en tu MAMP usa otro puerto
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
    $user = "root";
    $pass = "root"; 
    $dbname = "mediterranean";

    $conn = @new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión BD: " . $conn->connect_error);
    }

<<<<<<< HEAD
    $conn->set_charset("utf8mb4");
    $accion = $_GET['accion'] ?? '';
    $rawInput = file_get_json_input();

    // Mapea la sucursal ingresada al nombre exacto de la tabla SQL
    function obtenerNombreTabla($sucursal) {
        $s = strtolower(trim((string)$sucursal));
        if (strpos($s, 'centro') !== false || $s === '4' || $s === 'centro') {
            return 'centro';
        }
        if (strpos($s, 'norte') !== false || $s === '2' || $s === 'norte') {
            return 'norte';
        }
        return 'kennedy'; // Default
    }

    // 1. OBTENER CATÁLOGOS DINÁMICOS DE LAS TABLAS INDEPENDIENTES
    if ($accion === 'obtener_catalogos') {
        $sucursales = [
            ["id" => "kennedy", "nombre" => "Kennedy"],
            ["id" => "norte", "nombre" => "Norte"],
            ["id" => "centro", "nombre" => "Centro"]
        ];

        $categorias = [];
        $proveedores = [];
        $unidades = [];

        // Consultar Categorías
        $catRes = $conn->query("SELECT nombre FROM categorias ORDER BY nombre ASC");
        if ($catRes) {
            while ($r = $catRes->fetch_assoc()) {
                $categorias[] = $r['nombre'];
            }
        }

        // Consultar Proveedores
        $provRes = $conn->query("SELECT nombre FROM proveedores ORDER BY nombre ASC");
        if ($provRes) {
            while ($r = $provRes->fetch_assoc()) {
                $proveedores[] = $r['nombre'];
            }
        }

        // Consultar Unidades de Medida
        $uniRes = $conn->query("SELECT nombre FROM unidades ORDER BY nombre ASC");
        if ($uniRes) {
            while ($r = $uniRes->fetch_assoc()) {
                $unidades[] = $r['nombre'];
            }
        }

        // Valores por defecto si la base de datos está vacía
        if (empty($categorias)) {
            $categorias = ["General", "Mobiliario", "Cristalería", "Equipos", "Construcción", "Herramientas", "Pinturas", "Eléctricos", "Plomería", "Hogar"];
        }
        if (empty($proveedores)) {
            $proveedores = ["Proveedor General", "Homecenter", "Nestlé", "Valleta Glass", "Argos"];
        }
        if (empty($unidades)) {
            $unidades = ["Unidad", "Cajas", "Paquete", "Kilogramos", "Litros", "Gramos", "Metros", "Pallet", "Docena", "Saco"];
        }

        echo json_encode([
            "success" => true,
            "sucursales" => $sucursales,
            "categorias" => array_values(array_unique($categorias)),
            "proveedores" => array_values(array_unique($proveedores)),
            "unidades" => array_values(array_unique($unidades))
        ]);
        exit;
    }

    // NUEVO: GUARDAR NUEVA OPCIÓN MANUAL EN TABLA INDEPENDIENTE (CATEGORÍA, PROVEEDOR O UNIDAD)
    if ($accion === 'guardar_catalogo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo = $_POST['tipo'] ?? $rawInput['tipo'] ?? '';
        $valor = trim($_POST['valor'] ?? $rawInput['valor'] ?? '');

        if (empty($valor)) {
            echo json_encode(["success" => false, "message" => "El valor no puede estar vacío."]);
            exit;
        }

        $tablaCatalogo = '';
        if ($tipo === 'categoria') $tablaCatalogo = 'categorias';
        if ($tipo === 'proveedor') $tablaCatalogo = 'proveedores';
        if ($tipo === 'unit') $tablaCatalogo = 'unidades';

        if (!empty($tablaCatalogo)) {
            $stmt = $conn->prepare("INSERT INTO {$tablaCatalogo} (nombre) VALUES (?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
            $stmt->bind_param("s", $valor);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Registro guardado correctamente en la tabla {$tablaCatalogo}."]);
            } else {
                echo json_encode(["success" => false, "message" => "Error al guardar en base de datos: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "Tipo de catálogo no válido."]);
        }
        exit;
    }

    // 2. LISTAR TODOS LOS PRODUCTOS DE TODAS LAS SUCURSALES (O FILTRADO)
    if ($accion === 'listar' || $accion === 'listar_por_sucursal') {
        $sucursalParam = $_GET['sucursal'] ?? '';
        $tablasAConsultar = !empty($sucursalParam) ? [obtenerNombreTabla($sucursalParam)] : ['kennedy', 'centro', 'norte'];
        
        $productos = [];
        foreach ($tablasAConsultar as $tabla) {
            $query = "SELECT id, sucursal, sku, nombre, abc, prioridad, precio_compra, precio_venta, stock, reorder, 
                             tipo_empaque AS unit, batch, expiry, codigo_qr AS barcode, categoria, proveedor, estado, img, descripcion 
                      FROM {$tabla} ORDER BY id DESC";
            $res = $conn->query($query);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $row['tabla_origen'] = $tabla;
                    $row['nombre_sucursal'] = ucfirst($tabla);
                    $productos[] = $row;
                }
            }
        }

=======
    $conn->set_charset("utf8");
    $accion = $_GET['accion'] ?? '';

    // 1. LISTAR PRODUCTOS
    if ($accion === 'listar') {
        $result = $conn->query("SELECT * FROM productos ORDER BY id DESC");
        $productos = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
        }
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
        echo json_encode($productos);
        exit;
    }

<<<<<<< HEAD
    // 3. AGREGAR PRODUCTO A LA TABLA CORRESPONDIENTE
    if ($accion === 'agregar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawSucursal = $_POST['sucursal'] ?? $rawInput['sucursal'] ?? 'kennedy';
        $tabla = obtenerNombreTabla($rawSucursal);
        $sucursalNombre = ucfirst($tabla);

        $sku = !empty($_POST['sku']) ? $_POST['sku'] : ($rawInput['sku'] ?? null);
        $nombre = $_POST['nombre'] ?? $rawInput['nombre'] ?? '';
        $abc = $_POST['abc'] ?? $rawInput['abc'] ?? 'A';
        $precio_compra = floatval($_POST['precio_compra'] ?? $rawInput['precio_compra'] ?? 0);
        $precio_venta = floatval($_POST['precio_venta'] ?? $rawInput['precio_venta'] ?? 0);
        $stock = intval($_POST['stock'] ?? $rawInput['stock'] ?? 0);
        $reorder = intval($_POST['reorder'] ?? $rawInput['reorder'] ?? 10);
        $tipo_empaque = $_POST['unit'] ?? $rawInput['unit'] ?? 'Unidad';
        $batch = $_POST['batch'] ?? $rawInput['batch'] ?? '';
        $expiry = !empty($_POST['expiry']) ? $_POST['expiry'] : null;
        $barcode = !empty($_POST['barcode']) ? $_POST['barcode'] : ($rawInput['barcode'] ?? null);
        $categoria = $_POST['categoria'] ?? $rawInput['categoria'] ?? 'General';
        $proveedor = $_POST['proveedor'] ?? $rawInput['proveedor'] ?? 'Proveedor General';
        $estado = $_POST['estado'] ?? $rawInput['estado'] ?? 'Activo';
        $descripcion = $_POST['descripcion'] ?? $rawInput['descripcion'] ?? '';

        // Guardar automáticamente la categoría, proveedor y unidad en sus tablas si no existen
        if (!empty($categoria)) {
            $stC = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
            $stC->bind_param("s", $categoria);
            $stC->execute();
            $stC->close();
        }
        if (!empty($proveedor)) {
            $stP = $conn->prepare("INSERT INTO proveedores (nombre) VALUES (?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
            $stP->bind_param("s", $proveedor);
            $stP->execute();
            $stP->close();
        }
        if (!empty($tipo_empaque)) {
            $stU = $conn->prepare("INSERT INTO unidades (nombre) VALUES (?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
            $stU->bind_param("s", $tipo_empaque);
            $stU->execute();
            $stU->close();
        }

        // Manejo de imagen
        $img_path = "";
        if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
            $dir = "uploads/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
=======
    // 2. AGREGAR PRODUCTO
    if ($accion === 'agregar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre = $_POST['nombre'] ?? '';
        $categorias = $_POST['categoria'] ?? $_POST['categorias'] ?? '';
        $proveedores = $_POST['proveedor'] ?? $_POST['proveedores'] ?? '';
        $precio_compra = floatval($_POST['precio_compra'] ?? 0);
        $precio_venta = floatval($_POST['precio_venta'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $sucursal = $_POST['sucursal'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $estado = $_POST['estado'] ?? 'Activo';

        // Procesamiento de la Imagen
        $img_path = "";
        if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
            $dir = "uploads/";
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
            $filename = time() . "_" . basename($_FILES['img']['name']);
            $target_file = $dir . $filename;
            if (move_uploaded_file($_FILES['img']['tmp_name'], $target_file)) {
                $img_path = $target_file;
            }
        }

<<<<<<< HEAD
        $sql = "INSERT INTO {$tabla} 
                (sucursal, sku, nombre, abc, precio_compra, precio_venta, stock, reorder, tipo_empaque, batch, expiry, codigo_qr, categoria, proveedor, estado, img, descripcion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }

        $stmt->bind_param("ssssddiisssssssss", 
            $sucursalNombre, $sku, $nombre, $abc, $precio_compra, $precio_venta, 
            $stock, $reorder, $tipo_empaque, $batch, $expiry, $barcode, 
            $categoria, $proveedor, $estado, $img_path, $descripcion
        );

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "message" => "Producto guardado con éxito en la sucursal {$sucursalNombre}.", 
                "id" => $conn->insert_id
            ]);
=======
        $stmt = $conn->prepare("INSERT INTO productos (nombre, categorias, proveedores, precio_compra, precio_venta, stock, sucursal, img, descripcion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddissss", $nombre, $categorias, $proveedores, $precio_compra, $precio_venta, $stock, $sucursal, $img_path, $descripcion, $estado);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Guardado con éxito"]);
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
        } else {
            echo json_encode(["success" => false, "message" => "Error SQL: " . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

<<<<<<< HEAD
    // 4. ACTUALIZAR STOCK EN LA TABLA DE ORIGEN
    if ($accion === 'actualizar_stock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = intval($_POST['id'] ?? $rawInput['id'] ?? 0);
        $stock = intval($_POST['stock'] ?? $rawInput['stock'] ?? 0);
        $tabla = obtenerNombreTabla($_POST['tabla_origen'] ?? $rawInput['tabla_origen'] ?? 'kennedy');

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE {$tabla} SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $stock, $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Stock actualizado en la tabla '{$tabla}'."]);
            } else {
                echo json_encode(["success" => false, "message" => "Error SQL: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "ID no válido."]);
=======
    // 3. ELIMINAR PRODUCTO (Con control de Llave Foránea / Soft Delete)
    if ($accion === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Intentar borrado físico directo
            $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Producto eliminado correctamente"]);
            } else {
                // Si choca con la llave foránea de movimientos, aplicamos Soft Delete (cambiar estado a Inactivo)
                $stmt->close();
                $stmtUpdate = $conn->prepare("UPDATE productos SET estado = 'Inactivo' WHERE id = ?");
                $stmtUpdate->bind_param("i", $id);
                
                if ($stmtUpdate->execute()) {
                    echo json_encode([
                        "success" => true, 
                        "message" => "El producto tiene movimientos históricos registrados, por lo que fue marcado como 'Inactivo' de manera segura."
                    ]);
                } else {
                    echo json_encode(["success" => false, "message" => "No se pudo actualizar el estado del producto."]);
                }
                $stmtUpdate->close();
                exit;
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "ID de producto inválido."]);
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
        }
        exit;
    }

<<<<<<< HEAD
    // 5. ELIMINAR PRODUCTO DE LA TABLA DE ORIGEN
    if ($accion === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = intval($_POST['id'] ?? $rawInput['id'] ?? 0);
        $tabla = obtenerNombreTabla($_POST['tabla_origen'] ?? $rawInput['tabla_origen'] ?? 'kennedy');

        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM {$tabla} WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Producto eliminado de la tabla '{$tabla}'."]);
            } else {
                echo json_encode(["success" => false, "message" => "Error SQL: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "ID no válido."]);
=======
    // 3.1 ACTUALIZAR STOCK INDIVIDUAL (Nuevo endpoint requerido por la interfaz)
    if ($accion === 'actualizar_stock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = intval($_POST['id'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $stock, $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Stock actualizado correctamente"]);
            } else {
                echo json_encode(["success" => false, "message" => "Error al actualizar stock: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "message" => "ID inválido"]);
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
        }
        exit;
    }

<<<<<<< HEAD
=======
    // 4. OBTENER OPCIONES DE CATÁLOGOS
    if ($accion === 'obtener_catalogos') {
        $sucursalesRes = $conn->query("SELECT DISTINCT sucursal FROM productos WHERE sucursal IS NOT NULL AND sucursal != ''");
        $categoriasRes = $conn->query("SELECT DISTINCT categorias FROM productos WHERE categorias IS NOT NULL AND categorias != ''");
        $proveedoresRes = $conn->query("SELECT DISTINCT proveedores FROM productos WHERE proveedores IS NOT NULL AND proveedores != ''");

        $sucursales = ["Centro", "Kennedy", "Fontibón", "Chapinero", "Suba"];
        if ($sucursalesRes) { while ($r = $sucursalesRes->fetch_assoc()) { if (!in_array($r['sucursal'], $sucursales)) $sucursales[] = $r['sucursal']; } }

        $categorias = ["Construcción", "Herramientas", "Pinturas", "Eléctricos", "Plomería", "Hogar", "Mobiliario"];
        if ($categoriasRes) { while ($r = $categoriasRes->fetch_assoc()) { if (!in_array($r['categorias'], $categorias)) $categorias[] = $r['categorias']; } }

        $proveedores = ["Homecenter", "Nestlé", "Valleta Glass", "Avianca", "Argos"];
        if ($proveedoresRes) { while ($r = $proveedoresRes->fetch_assoc()) { if (!in_array($r['proveedores'], $proveedores)) $proveedores[] = $r['proveedores']; } }

        echo json_encode([
            "success" => true,
            "sucursales" => array_values($sucursales),
            "categorias" => array_values($categorias),
            "proveedores" => array_values($proveedores)
        ]);
        exit;
    }

>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}
<<<<<<< HEAD

function file_get_json_input() {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }
    return [];
}
=======
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
?>