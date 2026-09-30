<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

error_reporting(0);
ini_set('display_errors', 0);

include("conexion.php");

$sucursales = [];

if (isset($conn) && !$conn->connect_error) {
    $sql = "SELECT nombre FROM sucursales ORDER BY nombre ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sucursales[] = $row["nombre"];
        }
    }
}

// Respaldo automático si la tabla en MySQL está vacía o no existe
if (empty($sucursales)) {
    $sucursales = ["Kennedy", "Centro", "Norte"];
}

echo json_encode([
    "success" => true,
    "sucursales" => $sucursales
], JSON_UNESCAPED_UNICODE);
?>