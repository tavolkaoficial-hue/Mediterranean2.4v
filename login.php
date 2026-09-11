<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

/*
|--------------------------------------------------------------------------
| CONEXIÓN MAMP
|--------------------------------------------------------------------------
*/

$host = "localhost";
$user = "root";
$pass = "root";
$db   = "mediterranean";
$port = 8889;

$conn = new mysqli(
    $host,
    $user,
    $pass,
    $db,
    $port
);

if ($conn->connect_error) {

    echo json_encode([
        "status"  => "error",
        "message" => "Error de conexión con la base de datos."
    ]);

    exit;
}

$conn->set_charset("utf8mb4");


/*
|--------------------------------------------------------------------------
| LISTA DE EMPLEADOS
|--------------------------------------------------------------------------
|
| login.html llama:
|
| login.php?get_users=1
|
*/

if (isset($_GET['get_users'])) {

    $sql = "
        SELECT
            id,
            nombre_completo,
            correo,
            foto_biometrica,
            estado,
            rol
        FROM usuarios
        WHERE estado = 'activo'
        AND foto_biometrica IS NOT NULL
        AND foto_biometrica <> ''
        ORDER BY nombre_completo ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {

        echo json_encode([
            "status"  => "error",
            "message" => "No se pudieron obtener los empleados."
        ]);

        exit;
    }

    $usuarios = [];

    while ($row = $result->fetch_assoc()) {

        $usuarios[] = [

            "id" => (int)$row["id"],

            "nombre_completo" =>
                $row["nombre_completo"],

            "correo" =>
                $row["correo"],

            "foto_biometrica" =>
                $row["foto_biometrica"],

            "estado" =>
                $row["estado"],

            "rol" =>
                $row["rol"]

        ];

    }

    echo json_encode(
        $usuarios,
        JSON_UNESCAPED_UNICODE
    );

    $conn->close();

    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN BIOMÉTRICO
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["biometric_login"])
) {

    $user_id = isset($_POST["user_id"])
        ? (int)$_POST["user_id"]
        : 0;

    $correo = trim(
        $_POST["correo"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDAR DATOS RECIBIDOS
    |--------------------------------------------------------------------------
    */

    if (
        $user_id <= 0 ||
        $correo === ""
    ) {

        echo json_encode([
            "status"  => "error",
            "message" => "No se recibió correctamente el empleado."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | BUSCAR EMPLEADO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            nombre_completo,
            correo,
            rol,
            estado,
            foto_biometrica
        FROM usuarios
        WHERE id = ?
        AND correo = ?
        LIMIT 1
    ");


    if (!$stmt) {

        echo json_encode([
            "status"  => "error",
            "message" => "Error preparando la consulta."
        ]);

        exit;
    }


    $stmt->bind_param(
        "is",
        $user_id,
        $correo
    );


    $stmt->execute();


    $stmt->store_result();


    if ($stmt->num_rows === 0) {

        $stmt->close();

        echo json_encode([
            "status"  => "error",
            "message" => "El empleado no existe o el correo no coincide."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | EXTRAER DATOS
    |--------------------------------------------------------------------------
    */

    $stmt->bind_result(
        $id,
        $nombre_completo,
        $correo_db,
        $rol,
        $estado,
        $foto_biometrica
    );


    $stmt->fetch();


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | VALIDAR ESTADO
    |--------------------------------------------------------------------------
    */

    if ($estado !== "activo") {

        echo json_encode([
            "status"  => "error",
            "message" => "El empleado no tiene acceso activo."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR BIOMETRÍA REGISTRADA
    |--------------------------------------------------------------------------
    */

    if (
        empty($foto_biometrica) ||
        strlen($foto_biometrica) < 50
    ) {

        echo json_encode([
            "status"  => "error",
            "message" => "Este empleado no tiene biometría registrada."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR NUEVA SESIÓN
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | SESIONES COMPATIBLES
    |--------------------------------------------------------------------------
    |
    | Usamos las nuevas variables y también
    | $_SESSION["usuarios"], porque mediterranean.php
    | utiliza ese nombre.
    |
    */

    $_SESSION["authenticated"] =
        true;

    $_SESSION["user_id"] =
        (int)$id;

    $_SESSION["usuarios"] =
        $nombre_completo;

    $_SESSION["nombre_completo"] =
        $nombre_completo;

    $_SESSION["correo"] =
        $correo_db;

    $_SESSION["rol"] =
        $rol;


    /*
    |--------------------------------------------------------------------------
    | FORZAR ESCRITURA DE SESIÓN
    |--------------------------------------------------------------------------
    |
    | Cerramos la sesión antes de enviar la respuesta.
    | Esto garantiza que los datos queden escritos.
    |
    */

    session_write_close();


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA AL LOGIN.HTML
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "status" =>
            "success",

        "message" =>
            "Autenticación correcta.",

        "user_id" =>
            (int)$id,

        "nombre" =>
            $nombre_completo,

        "correo" =>
            $correo_db,

        "rol" =>
            $rol,

        "redirect" =>
            "mediterranean.php"

    ], JSON_UNESCAPED_UNICODE);


    $conn->close();

    exit;
}


/*
|--------------------------------------------------------------------------
| PETICIÓN NO VÁLIDA
|--------------------------------------------------------------------------
*/

echo json_encode([

    "status" =>
        "error",

    "message" =>
        "Petición no válida."

], JSON_UNESCAPED_UNICODE);


$conn->close();

exit;