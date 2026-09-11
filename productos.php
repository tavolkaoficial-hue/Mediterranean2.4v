<?php
// Desactivar impresión de errores HTML para evitar corromper la respuesta HTTP JSON
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
    $port = 8889; // Cambia este puerto solo si MySQL en tu MAMP usa otro puerto
    $user = "root";
    $pass = "root"; 
    $dbname = "mediterranean";

    $conn = @new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        throw new Exception("Error de conexión BD: " . $conn->connect_error);
    }

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
        echo json_encode($productos);
        exit;
    }

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
            $filename = time() . "_" . basename($_FILES['img']['name']);
            $target_file = $dir . $filename;
            if (move_uploaded_file($_FILES['img']['tmp_name'], $target_file)) {
                $img_path = $target_file;
            }
        }

        $stmt = $conn->prepare("INSERT INTO productos (nombre, categorias, proveedores, precio_compra, precio_venta, stock, sucursal, img, descripcion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddissss", $nombre, $categorias, $proveedores, $precio_compra, $precio_venta, $stock, $sucursal, $img_path, $descripcion, $estado);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Guardado con éxito"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error SQL: " . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

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
        }
        exit;
    }

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
        }
        exit;
    }

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

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}
?>