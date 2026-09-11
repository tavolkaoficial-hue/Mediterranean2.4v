<?php
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token           = trim($_POST["token"] ?? '');
    $direccion       = trim($_POST["direccion"] ?? '');
    $seguro_social   = trim($_POST["seguro_social"] ?? '');
    $latitud         = trim($_POST["latitud"] ?? '');
    $longitud        = trim($_POST["longitud"] ?? '');
    $foto_biometrica = $_POST["foto_biometrica"] ?? '';

    if (empty($token) || empty($direccion) || empty($seguro_social) || empty($foto_biometrica)) {
        echo "<script>alert('⚠ Por favor complete todos los campos y la captura de foto'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE usuarios SET direccion = ?, seguro_social = ?, foto_biometrica = ?, latitud = ?, longitud = ?, estado = 'activo' WHERE token_registro = ? AND estado = 'pendiente'");
    $stmt->bind_param("ssssss", $direccion, $seguro_social, $foto_biometrica, $latitud, $longitud, $token);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "<script>alert('✅ Registro completado exitosamente.'); window.location='login.html';</script>";
    } else {
        echo "<script>alert('❌ No se pudo actualizar la información o el token ha expirado.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>