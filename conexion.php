<?php
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
}
?>