<?php
require_once 'conexion.php';

// Configurar encabezado para respuestas JSON si la petición es AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $seguro_social = trim($_POST['seguro_social'] ?? '');
    $fotoBase64 = $_POST['foto_base64'] ?? null; // Soporte para cámara web (Canvas/DataURL)

    if (empty($token)) {
        responderError("Token de registro no especificado o inválido.", $isAjax);
    }

    $directorioDestino = 'uploads/biometria/';
    if (!is_dir($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }

    $fotoRuta = null;

    // OPCIÓN A: Procesar archivo enviado mediante $_FILES
    if (isset($_FILES['foto_biometrica']) && $_FILES['foto_biometrica']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto_biometrica']['tmp_name'];
        $fileName = $_FILES['foto_biometrica']['name'];
        $fileSize = $_FILES['foto_biometrica']['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        
        // Limitar tamaño máximo a 5MB y validar extensión
        if (in_array($ext, $extensionesPermitidas) && $fileSize <= 5 * 1024 * 1024) {
            $nombreFoto = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destinoFinal = $directorioDestino . $nombreFoto;

            if (move_uploaded_file($fileTmpPath, $destinoFinal)) {
                $fotoRuta = $destinoFinal;
            }
        }
    } 
    // OPCIÓN B: Procesar foto enviada desde Canvas / Webcam en Base64
    elseif (!empty($fotoBase64) && preg_match('/^data:image\/(\w+);base64,/', $fotoBase64, $type)) {
        $data = substr($fotoBase64, strpos($fotoBase64, ',') + 1);
        $data = base64_decode($data);

        if ($data !== false) {
            $ext = strtolower($type[1]);
            $nombreFoto = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destinoFinal = $directorioDestino . $nombreFoto;

            if (file_put_contents($destinoFinal, $data)) {
                $fotoRuta = $destinoFinal;
            }
        }
    }

    // Armado de consulta SQL dinámica
    $sql = "UPDATE usuarios SET 
                telefono = :telefono,
                direccion = :direccion, 
                seguro_social = :seguro_social, 
                estado = 'activo',
                token_registro = NULL"; // Se invalida el token consumido

    if ($fotoRuta) {
        $sql .= ", foto_biometrica = :foto";
    }

    $sql .= " WHERE token_registro = :token";

    try {
        $stmt = $pdo->prepare($sql);
        
        $parametros = [
            ':telefono'      => $telefono,
            ':direccion'     => $direccion,
            ':seguro_social' => $seguro_social,
            ':token'         => $token
        ];

        if ($fotoRuta) {
            $parametros[':foto'] = $fotoRuta;
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Biometría registrada correctamente.']);
                exit;
            } else {
                echo "<script>
                        alert('¡Registro biométrico completado con éxito!');
                        window.location.href = 'usuarios.html';
                      </script>";
                exit;
            }
        } else {
            responderError("El token es inválido o el registro ya fue completado previamente.", $isAjax);
        }

    } catch (PDOException $e) {
        responderError("Error en la base de datos: " . $e->getMessage(), $isAjax);
    }
} else {
    header("Location: usuarios.html");
    exit();
}

function responderError($mensaje, $isAjax) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $mensaje]);
        exit;
    } else {
        die($mensaje);
    }
}
?>