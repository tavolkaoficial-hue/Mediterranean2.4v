<?php
// Evitar que salidas previas o advertencias corrompan la estructura del JSON
ob_start();
ob_clean();

// Encabezados HTTP
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Activar excepciones en MySQLi para captura limpia de errores
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Credenciales de conexión MAMP
$host = "localhost";
$user = "root";
$pass = "root";
$db   = "mediterranean";
$port = 8889;

try {
    // 1. Validar y filtrar la sucursal/tabla enviada desde el frontend
    $sucursal = isset($_GET['sucursal']) ? strtolower(trim($_GET['sucursal'])) : 'kennedy';
    
    // Tablas permitidas para evitar inyecciones SQL
    $tablasPermitidas = ['kennedy', 'centro', 'norte'];
    
    if (!in_array($sucursal, $tablasPermitidas)) {$tablaTarget = 'kennedy';
    } else {
        $tablaTarget =$sucursal;
    }

    // 2. Conexión a la base de datos
    $conn = new mysqli($host,$user, $pass,$db, $port);$conn->set_charset("utf8mb4");

    // 3. Consulta SQL adaptada a las columnas exactas de las tablas kennedy, centro y norte
    $sql = "SELECT 
                id,
                COALESCE(sku, CONCAT('SKU-', id)) AS sku,
                COALESCE(nombre, 'Producto Sin Nombre') AS name,
                COALESCE(categoria, 'General') AS category,
                COALESCE(stock, 0) AS stock,
                COALESCE(sucursal, UPPER('$tablaTarget')) AS location,
                COALESCE(img, '') AS img,
                COALESCE(abc, 'C') AS abc,
                COALESCE(tipo_empaque, 'Unidad') AS unit,
                COALESCE(batch, 'LT-2026-00') AS batch,
                IFNULL(DATE_FORMAT(expiry, '%Y-%m-%d'), 'N/A') AS expiry,
                COALESCE(reorder, 10) AS reorder,
                COALESCE(codigo_qr, sku, id) AS barcode,
                CASE 
                    WHEN stock <= 0 THEN 'Crítico'
                    WHEN stock <= COALESCE(reorder, 10) THEN 'Reorden'
                    ELSE 'Óptimo'
                END AS status
            FROM `$tablaTarget`
            WHERE estado = 'Activo' OR estado IS NULL";

    $result =$conn->query($sql);$productos = [];

    while ($row =$result->fetch_assoc()) {
        $row['id']      = (int)$row['id'];
        $row['stock']   = (int)$row['stock'];
        $row['reorder'] = (int)$row['reorder'];

        $productos[] =$row;
    }

    // Retornar JSON
    echo json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error en la consulta de BD: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn) &&$conn instanceof mysqli && $conn->ping()) {$conn->close();
    }
}
?>