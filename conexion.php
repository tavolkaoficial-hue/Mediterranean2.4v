<?php
<<<<<<< HEAD
$host = 'localhost';
$port = '8889'; // <--- El puerto clave de MAMP
$dbname = 'mediterranean'; // <--- El nombre exacto de tu base de datos
$username = 'root'; // <--- Usuario por defecto en MAMP
$password = 'root'; // <--- En MAMP, la contraseña de root suele ser 'root' (si te da error de acceso, prueba a dejarla vacía '')

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $e->getMessage()]);
    exit;
=======
$host    = 'localhost';
$db      = 'mediterranean';
$user    = 'root';
$pass    = 'root'; // MAMP por defecto
$port    = 8889;   // Puerto por defecto de MySQL en MAMP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Captura de errores mediante excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve resultados como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Consultas preparadas reales por seguridad
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Retorna respuesta JSON estructurada si la conexión falla durante peticiones Fetch/AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
        exit;
    }
    
    die("Error de conexión a la base de datos: " . $e->getMessage());
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
}
?>