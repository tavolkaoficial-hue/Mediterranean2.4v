<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
    $usuarios = $stmt->fetchAll();
    echo json_encode($usuarios);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>