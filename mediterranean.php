<<<<<<< HEAD
<?php
// Mediterranean ERP - Conexión y Consultas dinámicas
session_start();

$host = 'localhost';
$port = '8889';
$db   = 'mediterranean';
$user = 'root'; // Ajusta según la configuración de tu MAMP
$pass = 'root'; // Ajusta según la configuración de tu MAMP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// OBTENER INFORMACIÓN DEL USUARIO LOGUEADO
$nombreUsuarioLogueado = 'Usuario';
$inicialesUsuario = 'US';

if (isset($_SESSION['usuario_id']) || isset($_SESSION['id'])) {
    $userId = $_SESSION['usuario_id'] ?? $_SESSION['id'];
    $stmtUser = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = :id LIMIT 1");
    $stmtUser->execute(['id' => $userId]);
    $usuario = $stmtUser->fetch();
    if ($usuario && !empty($usuario['nombre_completo'])) {
        $nombreUsuarioLogueado = $usuario['nombre_completo'];
    }
} elseif (isset($_SESSION['correo'])) {
    $stmtUser = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmtUser->execute(['correo' => $_SESSION['correo']]);
    $usuario = $stmtUser->fetch();
    if ($usuario && !empty($usuario['nombre_completo'])) {
        $nombreUsuarioLogueado = $usuario['nombre_completo'];
    }
}

// Generar iniciales para el avatar
$partesNombre = explode(' ', trim($nombreUsuarioLogueado));
if (count($partesNombre) >= 2) {
    $inicialesUsuario = strtoupper(mb_substr($partesNombre[0], 0, 1) . mb_substr($partesNombre[1], 0, 1));
} else {
    $inicialesUsuario = strtoupper(mb_substr($nombreUsuarioLogueado, 0, 2));
}

// 1. Tablas de sucursales activas en el sistema
$sucursales = ['kennedy', 'centro', 'norte'];

// 2. Consulta unificada mediante UNION ALL
$unionQueries = [];
foreach ($sucursales as $s) {
    $unionQueries[] = "SELECT id, sucursal, sku, nombre, precio_compra, precio_venta, stock, reorder, categoria, proveedor, estado, created_at, updated_at FROM `$s` WHERE estado = 'Activo'";
}
$queryUnificada = implode(" UNION ALL ", $unionQueries);

// 3. Cálculos de Indicadores de Inventario (KPIs)
$sqlKPIs = "SELECT 
                SUM(precio_venta * stock) AS valor_inventario,
                COUNT(id) AS total_skus,
                SUM(CASE WHEN stock <= reorder THEN 1 ELSE 0 END) AS stock_critico
            FROM ($queryUnificada) AS inventario_total";

$stmtKPIs = $pdo->query($sqlKPIs);
$kpis = $stmtKPIs->fetch();

$valorInventario = $kpis['valor_inventario'] ?? 0;
$totalSkus = $kpis['total_skus'] ?? 0;
$stockCritico = $kpis['stock_critico'] ?? 0;

// 4. Conteo de productos por Sucursal (para ocupación)
$sucursalesData = [];
foreach ($sucursales as $s) {
    $stmtCount = $pdo->query("SELECT SUM(stock) as total_stock FROM `$s` WHERE estado = 'Activo'");
    $res = $stmtCount->fetch();
    $sucursalesData[$s] = $res['total_stock'] ?? 0;
}

// 5. Categorías registradas
$categoriasStmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
$categoriasList = $categoriasStmt->fetchAll();

// 6. Obtener últimos movimientos unificados ordenados por actualización
$sqlMovimientos = "SELECT sku, nombre, stock, sucursal, updated_at FROM ($queryUnificada) AS inventario_total ORDER BY updated_at DESC LIMIT 5";
$movimientosStmt = $pdo->query($sqlMovimientos);
$ultimosMovimientos = $movimientosStmt->fetchAll();
?>
=======
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
<title>Mediterranean | Enterprise Inventory</title>
=======
<title>Mediterranean | Enterprise Inventory ERP</title>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
<!-- Fuentes Tipográficas -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<!-- Chart.js para Gráficos Interactivos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  :root {
    /* PALETA CLAYMORPHIC AZUL GRISÁCEO (SLATE) */
    --bg-main: #ffffff;
    --clay-bg: #cbd5e1;
    --clay-surface: #f1f5f9;
    
    /* TONO AZUL GRISÁCEO PARA TARJETAS Y PANELES */
    --clay-card: #e0e7ff; 
    
    --text-primary: #0f172a;
    --text-secondary: #334155;
    --text-muted: #64748b;

    --accent-cyan: #0284c7;
    --accent-blue: #2563eb;
    --accent-indigo: #4f46e5;
    --green: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;

    /* SOMBRAS Y RELIEVES CLAYMORPHISM EN TONOS AZUL GRISÁCEO */
    --clay-shadow-outer: 12px 12px 24px #cbd5e1, -8px -8px 20px #ffffff;
    --clay-shadow-card: 14px 14px 28px #cbd5e1, -10px -10px 22px #ffffff;
    --clay-inset-light: inset 2px 2px 4px rgba(255, 255, 255, 0.8), inset -3px -3px 6px rgba(15, 23, 42, 0.08);
    --clay-inset-glow: inset 1px 1px 2px rgba(255, 255, 255, 0.6), inset -2px -2px 4px rgba(37, 99, 235, 0.2);
    --clay-btn-shadow: 6px 6px 14px #cbd5e1, -4px -4px 10px #ffffff;

    --transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    --sidebar-width: 280px;
  }

  body.sidebar-collapsed {
    --sidebar-width: 90px;
  }

  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; }

  /* FONDO BASE CON DEGRADÉ DE AZUL GRISÁCEO */
  body {
    margin: 0;
    min-height: 100vh;
    color: var(--text-primary);
    font-family: "Plus Jakarta Sans", sans-serif;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 50%, #cbd5e1 100%);
    background-attachment: fixed;
    overflow-x: hidden;
  }

 #tsparticles {
  position: fixed;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
<<<<<<< HEAD
  z-index: -1;
  background-color: #70a2ec;
=======
  z-index: -1; /* Para que quede detrás del contenido */
  background-color: #70a2ec; /* Cambia al color de fondo que desees */
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
}

  .app-container {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 1;
  }

  /* SIDEBAR CLAYMORPHIC */
  .sidebar {
    position: fixed;
    top: 16px;
    left: 16px;
    bottom: 16px;
    width: var(--sidebar-width);
    z-index: 5000;
    padding: 24px 16px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
    background: linear-gradient(145deg, #ffffff, #f1f5f9);
    border-radius: 32px;
    box-shadow: var(--clay-shadow-outer);
    transition: var(--transition);
  }

  .brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 0 20px;
    margin-bottom: 15px;
  }

  .brand-logo-img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    filter: drop-shadow(0 10px 15px rgba(0,0,0,0.15));
    transition: var(--transition);
  }

  .brand-logo-img:hover {
    transform: scale(1.05) translateY(-3px);
  }

  .menu-label {
    padding: 16px 12px 8px;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .side-link {
    width: 100%;
    height: 52px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 18px;
    border: none;
    border-radius: 20px;
    color: var(--text-secondary);
    background: transparent;
    font: 600 14px "Plus Jakarta Sans", sans-serif;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    margin-bottom: 8px;
    white-space: nowrap;
  }

  .side-link:hover {
    background: #e2e8f0;
    color: var(--accent-cyan);
    box-shadow: var(--clay-btn-shadow), var(--clay-inset-light);
    transform: translateY(-2px);
  }

  .side-link.active {
    color: #fff;
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
    box-shadow: 0 10px 20px rgba(2, 132, 199, 0.25), var(--clay-inset-glow);
  }

  .side-icon {
    font-size: 20px;
    min-width: 24px;
    display: inline-block;
    text-align: center;
    transition: var(--transition);
  }

  .side-link.active .side-icon {
    color: #fff;
  }

  .sidebar-footer {
    margin-top: auto;
    padding-top: 15px;
  }

  .system-status-box {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding: 12px 16px;
    border-radius: 18px;
    color: var(--green);
    background: var(--clay-bg);
    box-shadow: var(--clay-inset-light);
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
  }

  .online-dot {
    width: 10px;
    height: 10px;
    min-width: 10px;
    border-radius: 50%;
    background: var(--green);
    box-shadow: 0 0 12px var(--green);
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.8; }
  }

  .languages {
    display: flex;
    margin-bottom: 12px;
    padding: 6px;
    border-radius: 18px;
    background: var(--clay-bg);
    box-shadow: var(--clay-inset-light);
  }

  .languages button {
    flex: 1;
    padding: 8px;
    border: 0;
    border-radius: 12px;
    color: var(--text-muted);
    background: transparent;
    font: 800 12px "Plus Jakarta Sans", sans-serif;
    cursor: pointer;
    transition: var(--transition);
  }

  .languages button.active-language {
    color: var(--text-primary);
    background: var(--clay-surface);
    box-shadow: var(--clay-btn-shadow);
  }

  .logout-btn {
    width: 100%;
    height: 48px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 16px;
    border-radius: 18px;
    color: var(--danger);
    background: var(--clay-surface);
    box-shadow: var(--clay-btn-shadow);
    font: 700 13px "Plus Jakarta Sans", sans-serif;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
  }

  .logout-btn:hover {
    background: var(--danger);
    color: #fff;
    box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
    transform: translateY(-2px);
  }

  /* MODO COLAPSADO */
  body.sidebar-collapsed .brand-logo-img { width: 45px; height: 45px; }
  body.sidebar-collapsed .menu-label,
  body.sidebar-collapsed .side-link span:not(.side-icon),
  body.sidebar-collapsed .system-status-box span,
  body.sidebar-collapsed .languages,
  body.sidebar-collapsed .logout-btn span:not(.side-icon) {
    display: none;
  }

  body.sidebar-collapsed .side-link,
  body.sidebar-collapsed .logout-btn {
    justify-content: center;
    padding: 0;
  }

  /* TOPBAR CLAYMORPHIC */
  .top-bar {
    position: fixed;
    top: 16px;
    left: calc(var(--sidebar-width) + 32px);
    right: 16px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: linear-gradient(145deg, #ffffff, #f1f5f9);
    border-radius: 28px;
    box-shadow: var(--clay-shadow-outer);
    z-index: 4000;
    transition: var(--transition);
  }

  .top-left-group {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .toggle-sidebar-btn {
    background: var(--clay-surface);
    border: none;
    color: var(--accent-cyan);
    width: 44px;
    height: 44px;
    border-radius: 16px;
    cursor: pointer;
    display: grid;
    place-items: center;
    font-size: 18px;
    box-shadow: var(--clay-btn-shadow);
    transition: var(--transition);
  }

  .toggle-sidebar-btn:hover {
    transform: translateY(-2px) scale(1.05);
    color: #fff;
    background: var(--accent-cyan);
  }

  .welcome {
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 500;
  }

  .welcome strong { color: var(--text-primary); }

  .top-actions {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .clock-container {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--clay-bg);
    padding: 8px 18px;
    border-radius: 16px;
    box-shadow: var(--clay-inset-light);
  }

  .clock-icon {
    color: var(--accent-cyan);
    font-size: 16px;
  }

  .today {
    color: var(--text-primary);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 15px;
    font-weight: 700;
  }

  .profile-badge {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 16px;
    color: #fff;
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-indigo));
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--clay-btn-shadow), var(--clay-inset-glow);
  }

  .profile-badge:hover {
    transform: scale(1.08) translateY(-2px);
  }

  /* MAIN CONTENT LAYOUT */
  .main-content {
    margin-left: calc(var(--sidebar-width) + 32px);
    padding-top: 102px;
    padding-right: 16px;
    width: 100%;
    min-height: 100vh;
    transition: var(--transition);
  }

  .section {
    display: none !important;
    padding-bottom: 90px;
    animation: fadeIn 0.4s ease-out;
  }

  .section.active { display: block !important; }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .heading h1 {
    margin: 0;
    color: var(--text-primary);
    font-family: "Space Grotesk", sans-serif;
    font-weight: 700;
    font-size: 34px;
    letter-spacing: -0.5px;
  }

  .heading p { 
    margin: 6px 0 0; 
    color: var(--text-secondary); 
    font-size: 14px; 
  }

  /* TARJETAS METRICAS CLAYMORPHIC AZUL GRISÁCEO */
  .metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 25px;
  }

  .card {
    position: relative;
    border-radius: 28px;
    background: linear-gradient(145deg, #e0e8f5, #cbd8ed);
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: var(--clay-shadow-card);
    padding: 24px;
    transition: var(--transition);
  }

  .card:hover {
    transform: translateY(-6px);
    box-shadow: 18px 18px 36px #b8c4d8, -12px -12px 28px #ffffff;
  }

  .metric-label { 
    color: var(--text-muted); 
    font-size: 11px; 
    text-transform: uppercase; 
    letter-spacing: 1.5px; 
    font-weight: 800; 
  }

  .metric-value { 
    display: block; 
    margin-top: 10px; 
    color: var(--text-primary); 
    font: 700 32px "Space Grotesk", sans-serif; 
  }

  .metric-change { 
    display: block; 
    margin-top: 8px; 
    color: var(--green); 
    font-size: 13px; 
    font-weight: 700; 
  }

  .metric-icon {
    position: absolute;
    top: 22px;
    right: 22px;
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    border-radius: 18px;
    color: var(--accent-cyan);
    font-size: 22px;
    background: #f1f5f9;
    box-shadow: var(--clay-btn-shadow), var(--clay-inset-light);
    transition: var(--transition);
  }

  .card:hover .metric-icon {
    transform: scale(1.1) rotate(6deg);
    color: #fff;
    background: var(--accent-cyan);
  }

  .dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(285px, .75fr);
    gap: 20px;
    margin-top: 24px;
  }

  .panel {
    border-radius: 28px;
    background: linear-gradient(145deg, #e0e8f5, #cbd8ed);
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: var(--clay-shadow-card);
    padding: 28px;
    position: relative;
    transition: var(--transition);
  }

  .panel-header { 
    display: flex; 
    align-items: flex-start; 
    justify-content: space-between; 
    margin-bottom: 20px; 
  }

  .panel h3 { 
    font-family: "Space Grotesk", sans-serif;
    font-size: 20px; 
    margin: 0;
    color: var(--text-primary);
  }

  .panel-subtitle { 
    color: var(--text-secondary); 
    font-size: 13px; 
    margin-top: 4px; 
  }

<<<<<<< HEAD
=======
  /* BOTONES RÁPIDOS */
  .quick-actions { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 14px; 
    margin-top: 24px; 
  }

  .quick-action {
    background: linear-gradient(145deg, #e0e8f5, #cbd8ed);
    color: var(--accent-cyan);
    padding: 12px 22px;
    border-radius: 20px;
    cursor: pointer;
    font: 700 13px "Plus Jakarta Sans", sans-serif;
    text-decoration: none;
    box-shadow: var(--clay-btn-shadow);
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .quick-action:hover {
    background: var(--accent-cyan);
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(2, 132, 199, 0.3);
  }

>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
  /* ESTILOS DE TABLAS Y MOVIMIENTOS DE INVENTARIO */
  .table-responsive {
    width: 100%;
    overflow-x: auto;
    margin-top: 10px;
  }

  .data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }

  .data-table th {
    color: var(--text-muted);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    padding: 0 16px 10px;
    text-align: left;
  }

  .data-table td {
    padding: 14px 16px;
    background: rgba(255, 255, 255, 0.5);
    font-size: 13px;
    color: var(--text-primary);
    font-weight: 600;
  }

  .data-table tr td:first-child {
    border-top-left-radius: 16px;
    border-bottom-left-radius: 16px;
  }

  .data-table tr td:last-child {
    border-top-right-radius: 16px;
    border-bottom-right-radius: 16px;
  }

  .badge {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 800;
    display: inline-block;
<<<<<<< HEAD
    text-transform: capitalize;
=======
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
  }

  .badge-success { background: rgba(16, 185, 129, 0.15); color: var(--green); }
  .badge-warning { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
  .badge-danger { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
  .badge-info { background: rgba(2, 132, 199, 0.15); color: var(--accent-cyan); }

  /* WEATHER PANEL */
  .weather-panel {
    background: linear-gradient(135deg, #e0e8f5 0%, #cbd8ed 100%);
  }

<<<<<<< HEAD
=======
  .weather-display {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
  }

  .weather-main {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .weather-temp {
    font-family: "Space Grotesk", sans-serif;
    font-size: 48px;
    font-weight: 700;
    color: var(--accent-cyan);
    line-height: 1;
    text-shadow: 0 4px 12px rgba(2, 132, 199, 0.2);
  }

  .weather-icon {
    font-size: 44px;
    filter: drop-shadow(0 8px 12px rgba(0,0,0,0.1));
  }

  .weather-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
  }

  .weather-item {
    text-align: center;
    background: var(--clay-bg);
    padding: 10px;
    border-radius: 16px;
    box-shadow: var(--clay-inset-light);
  }

  .weather-item span {
    display: block;
    font-size: 10px;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 800;
    letter-spacing: 1px;
  }

  .weather-item strong {
    font-size: 14px;
    color: var(--text-primary);
    margin-top: 2px;
    display: block;
  }

>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
  .secondary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 24px;
  }

  .branch-row {
    display: grid;
    grid-template-columns: 130px 1fr 50px;
    gap: 14px;
    align-items: center;
    margin-bottom: 18px;
    padding: 8px 12px;
    border-radius: 16px;
    transition: var(--transition);
  }

<<<<<<< HEAD
  .branch-name { font-size: 13px; color: var(--text-primary); font-weight: 700; text-transform: capitalize; }
=======
  .branch-name { font-size: 13px; color: var(--text-primary); font-weight: 700; }
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
  
  .health-bar { 
    height: 12px; 
    border-radius: 20px; 
    background: var(--clay-bg); 
    box-shadow: var(--clay-inset-light);
    overflow: hidden; 
  }

  .health-progress { 
    height: 100%; 
    border-radius: inherit; 
    background: linear-gradient(90deg, var(--accent-blue), var(--accent-cyan)); 
    transition: width 1s ease; 
  }

  /* FOOTER */
  .copyright-fixed {
    position: fixed;
    left: calc(var(--sidebar-width) + 32px);
    right: 16px;
    bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    background: linear-gradient(145deg, #ffffff, #f1f5f9);
    color: var(--text-muted);
    font-size: 12px;
    z-index: 3000;
    border-radius: 20px;
    box-shadow: var(--clay-shadow-outer);
    transition: var(--transition);
  }

  .copyright-fixed a {
    color: var(--accent-cyan);
    text-decoration: none;
    font-weight: 700;
  }

  /* CHATBOT */
  .chatbot-launcher {
    position: fixed;
    z-index: 6000;
    right: 32px;
    bottom: 60px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px 10px 12px;
    border-radius: 40px;
    color: var(--text-primary);
    background: linear-gradient(145deg, #ffffff, #f1f5f9);
    box-shadow: var(--clay-shadow-card);
    cursor: pointer;
    transition: var(--transition);
  }

  .chatbot-launcher:hover {
    transform: translateY(-4px) scale(1.03);
  }

  .robot-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
    color: #fff;
    display: grid;
    place-items: center;
    font-size: 20px;
    box-shadow: var(--clay-inset-glow);
  }

  .chat-window {
    position: fixed;
    right: 32px;
    bottom: 125px;
    width: min(360px, calc(100vw - 40px));
    height: 480px;
    display: none;
    flex-direction: column;
    background: linear-gradient(145deg, #ffffff, #f1f5f9);
    border-radius: 32px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15), var(--clay-shadow-outer);
    overflow: hidden;
    z-index: 7000;
  }

  .chat-header {
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f1f5f9;
    box-shadow: var(--clay-inset-light);
  }

  .chat-header strong { 
    color: var(--text-primary); 
    font-family: "Space Grotesk", sans-serif; 
    font-size: 18px; 
  }

  .chat-close { 
    border: none; 
    background: transparent; 
    color: var(--text-muted); 
    font-size: 24px; 
    cursor: pointer; 
  }

  .chat-messages { 
    flex: 1; 
    overflow-y: auto; 
    padding: 18px; 
    display: flex; 
    flex-direction: column; 
    gap: 12px; 
  }

  .message {
    max-width: 85%;
    padding: 12px 16px;
    border-radius: 18px;
    font-size: 13px;
    line-height: 1.5;
  }

  .message.user { 
    align-self: flex-end; 
    color: #fff; 
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue)); 
    font-weight: 600; 
    border-bottom-right-radius: 4px; 
    box-shadow: var(--clay-inset-glow);
  }

  .message.bot { 
    align-self: flex-start; 
    color: var(--text-primary); 
    background: var(--clay-bg); 
    box-shadow: var(--clay-inset-light);
    border-bottom-left-radius: 4px; 
  }

  .chat-input { 
    display: flex; 
    gap: 10px; 
    padding: 14px; 
    background: #f1f5f9; 
  }

  .chat-input input {
    flex: 1;
    padding: 12px 16px;
    border-radius: 16px;
    border: none;
    outline: none;
    color: var(--text-primary);
    background: var(--clay-bg);
    box-shadow: var(--clay-inset-light);
    font-size: 13px;
  }

  .chat-input button {
    border: none;
    padding: 0 18px;
    border-radius: 16px;
    background: var(--accent-cyan);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
  }

  .chat-input button:hover {
    background: var(--accent-blue);
  }

  /* ADAPTACIÓN RESPONSIVA */
  @media (max-width: 1100px) {
    .metrics { grid-template-columns: repeat(2, 1fr); }
    .dashboard-grid, .secondary-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 1000px) {
    :root { --sidebar-width: 90px; }
    .brand-logo-img { width: 45px; height: 45px; }
    .menu-label, .side-link span:not(.side-icon), .system-status-box span, .languages, .logout-btn span:not(.side-icon) { display: none; }
    .side-link, .logout-btn { justify-content: center; padding: 0; }
  }

  @media (max-width: 620px) {
    :root { --sidebar-width: 0px; }
    .sidebar { transform: translateX(-110%); width: 270px; left: 10px; top: 10px; bottom: 10px; }
    body.mobile-open .sidebar { transform: translateX(0); }
    .top-bar, .main-content, .copyright-fixed { left: 10px; right: 10px; width: auto; }
    .top-bar { padding: 0 16px; }
    .section { padding: 0 0 80px; }
    .metrics { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<div id="tsparticles"></div>
<script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>

<div class="app-container">
  <!-- SIDEBAR CLAYMORPHIC -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <img src="images/LogoMediterranean1992.png" alt="Mediterranean Logo" class="brand-logo-img" onerror="this.onerror=null; this.src='https://via.placeholder.com/130/e2e8f0/0284c7?text=M';">
    </div>

    <div class="menu-label" data-es="PRINCIPAL" data-en="MAIN">PRINCIPAL</div>
    <button class="side-link active" data-section="inicio" onclick="mostrarSeccion('inicio', this)">
<<<<<<< HEAD
      <span class="side-icon">⌂</span><span data-es="Panel ERP" data-en="ERP Dashboard">Menu Principal</span>
=======
      <span class="side-icon">⌂</span><span data-es="Panel ERP" data-en="ERP Dashboard">Menu principal</span>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
    </button>
    
    <a href="stock.html" class="side-link">
      <span class="side-icon">📦</span><span data-es="Inventario & Stock" data-en="Stock & Inventory">Inventario & Stock</span>
    </a>
    <a href="sucursales.html" class="side-link">
      <span class="side-icon">🏢</span><span data-es="Sucursales (Almacenes)" data-en="Branches (Warehouses)">Sucursales / Almacenes</span>
    </a>
    <a href="movimientos.html" class="side-link">
      <span class="side-icon">🔄</span><span data-es="Historial de Movimientos" data-en="Movement Log">Kardex / Movimientos</span>
    </a>

<<<<<<< HEAD
   <div class="menu-label" data-es="ADMINISTRACIÓN" data-en="ADMINISTRATION">ADMINISTRACIÓN</div>
      
      <a href="configuracion.html" class="side-link">
        <span class="side-icon">⚙</span><span data-es="Configuración ERP" data-en="ERP Settings">Configuración</span>
      </a>

=======
    <div class="menu-label" data-es="ADMINISTRACIÓN" data-en="ADMINISTRATION">ADMINISTRACIÓN</div>
    <a href="usuarios.html" class="side-link">
      <span class="side-icon">👤</span><span data-es="Usuarios & Roles" data-en="Users & Roles">Usuarios & Roles</span>
    </a>
    <a href="reportes.html" class="side-link">
      <span class="side-icon">📊</span><span data-es="Reportes BI" data-en="BI Reports">Reportes & Analítica</span>
    </a>
    <a href="utilidades.html" class="side-link">
      <span class="side-icon">🛠</span><span data-es="Auditoría / Ajustes" data-en="Audit / Settings">Auditoría & Ajustes</span>
    </a>
    <a href="configuracion.html" class="side-link">
      <span class="side-icon">⚙</span><span data-es="Configuración ERP" data-en="ERP Settings">Configuración ERP</span>
    </a>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f

    <div class="sidebar-footer">
      <div class="system-status-box">
        <span class="online-dot"></span>
        <span data-es="ERP En Línea" data-en="ERP Online">En Línea</span>
      </div>

      <div class="languages">
        <button id="btn-es" class="active-language" onclick="cambiarIdioma('es')">ES</button>
        <button id="btn-en" onclick="cambiarIdioma('en')">EN</button>
      </div>

<<<<<<< HEAD
      <a href="logout.php" class="logout-btn">
=======
      <a href="login.html" class="logout-btn">
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
        <span class="side-icon">⍈</span>
        <span data-es="Cerrar Sesión" data-en="Logout">Cerrar Sesión</span>
      </a>
    </div>
  </aside>

  <!-- TOPBAR -->
  <header class="top-bar">
    <div class="top-left-group">
      <button class="toggle-sidebar-btn" onclick="toggleSidebar()" title="Plegar/Desplegar Menú">☰</button>
      <div class="welcome">
<<<<<<< HEAD
        <span>Bienvenido,</span> <strong><?= htmlspecialchars($nombreUsuarioLogueado) ?></strong>
=======
        <span data-es="Consola Global ERP" data-en="Global ERP Console">Mediterranean v2.4</span> • <strong>Modulo Principal</strong>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
      </div>
    </div>
    
    <div class="top-actions">
      <div class="clock-container">
        <span class="clock-icon">🕒</span>
<<<<<<< HEAD
        <div class="today" id="currentTime"><?php echo date('H:i:s'); ?></div>
      </div>
      <div class="profile-badge" title="<?= htmlspecialchars($nombreUsuarioLogueado) ?>"><?= htmlspecialchars($inicialesUsuario) ?></div>
=======
        <div class="today" id="currentTime">00:00:00</div>
      </div>
      <div class="profile-badge" title="Administrador Principal">AD</div>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
    </div>
  </header>

  <!-- CONTENIDO PRINCIPAL DE INVENTARIO -->
  <main class="main-content">
    <section id="inicio" class="section active">
      <div class="heading">
        <h1 data-es="Control General de Inventarios" data-en="General Inventory Control">Control General de Inventarios</h1>
<<<<<<< HEAD
        <p data-es="Métricas unificadas de stock en tiempo real desde Kennedy, Centro y Norte." data-en="Unified real-time stock metrics from Kennedy, Centro, and Norte.">Métricas unificadas de stock en tiempo real desde Kennedy, Centro y Norte.</p>
      </div>

      <!-- METRICAS CONECTADAS A MYSQL -->
=======
        <p data-es="Indicadores clave de rendimiento (KPIs), métricas de stock y control multialmacén en tiempo real." data-en="Key performance indicators (KPIs), stock metrics and real-time multi-warehouse control.">Indicadores clave de rendimiento (KPIs), métricas de stock y control multialmacén en tiempo real.</p>
      </div>

      <div class="quick-actions">
        <a href="productos.html" class="quick-action" data-es="＋ Registrar Entrada" data-en="＋ Stock In">＋ Entrada Stock</a>
        <a href="productos.html" class="quick-action" data-es="－ Registrar Salida" data-en="－ Stock Out">－ Salida Stock</a>
        <a href="movimientos.html" class="quick-action" data-es="🔁 Transferencia Almacenes" data-en="🔁 Transfer Warehouse">🔁 Transferencia Inter-Sucursal</a>
        <a href="reportes.html" class="quick-action" data-es="📄 Generar Kardex PDF" data-en="📄 Export Kardex PDF">📄 Generar Kardex</a>
      </div>

      <!-- METRICAS DE INVENTARIO -->
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
      <div class="metrics">
        <div class="card">
          <span class="metric-icon">💰</span>
          <span class="metric-label" data-es="Valor del Inventario" data-en="Inventory Valuation">Valor del Inventario</span>
<<<<<<< HEAD
          <span class="metric-value">$<?= number_format($valorInventario, 2) ?></span>
          <span class="metric-change">↑ Cálculo en tiempo real</span>
        </div>
        <div class="card">
          <span class="metric-icon">📦</span>
          <span class="metric-label" data-es="SKUs Registrados" data-en="Active SKUs">SKUs Registrados</span>
          <span class="metric-value"><?= number_format($totalSkus) ?></span>
          <span class="metric-change" data-es="Total entre sucursales" data-en="Total across branches">Total entre sucursales</span>
        </div>
        <div class="card">
          <span class="metric-icon">⚠️</span>
          <span class="metric-label" data-es="Stock Crítico" data-en="Low Stock Alerts">Stock Crítico</span>
          <span class="metric-value" style="color: var(--danger);"><?= $stockCritico ?></span>
          <span class="metric-change" style="color: var(--danger)" data-es="Reabastecimiento requerido" data-en="Urgent restock needed">Reabastecimiento requerido</span>
        </div>
        <div class="card">
          <span class="metric-icon">🏷️</span>
          <span class="metric-label" data-es="Categorías" data-en="Categories">Categorías</span>
          <span class="metric-value"><?= count($categoriasList) ?></span>
          <span class="metric-change" data-es="Familias de producto" data-en="Product families">Familias de producto</span>
=======
          <span class="metric-value">$842,500</span>
          <span class="metric-change">↑ 4.2% <span data-es="vs mes anterior" data-en="vs last month">vs mes anterior</span></span>
        </div>
        <div class="card">
          <span class="metric-icon">📦</span>
          <span class="metric-label" data-es="Total de SKUs Activos" data-en="Active SKUs">SKUs Registrados</span>
          <span class="metric-value">3,420</span>
          <span class="metric-change" data-es="↑ 28 nuevos este mes" data-en="↑ 28 new this month">↑ 28 nuevos este mes</span>
        </div>
        <div class="card">
          <span class="metric-icon">⚠️</span>
          <span class="metric-label" data-es="Alertas de Stock Bajo" data-en="Low Stock Alerts">Stock Crítico</span>
          <span class="metric-value" style="color: var(--danger);">14</span>
          <span class="metric-change" style="color: var(--danger)" data-es="Reabastecimiento urgente" data-en="Urgent restock needed">Reabastecimiento urgente</span>
        </div>
        <div class="card">
          <span class="metric-icon">🔁</span>
          <span class="metric-label" data-es="Índice de Rotación" data-en="Turnover Ratio">Rotación Anual</span>
          <span class="metric-value">6.8x</span>
          <span class="metric-change" data-es="Eficiencia de stock óptima" data-en="Optimal stock efficiency">Eficiencia óptima</span>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
        </div>
      </div>

      <!-- PANELES DE GRÁFICOS INTERACTIVOS (CHART.JS) -->
      <div class="dashboard-grid">
        
        <!-- PANEL GRÁFICO 1: ENTRADAS VS SALIDAS -->
        <div class="panel">
          <div class="panel-header">
            <div>
<<<<<<< HEAD
              <h3 data-es="Flujo de Mercancía" data-en="Stock Flow">Flujo de Mercancía</h3>
              <div class="panel-subtitle" data-es="Movimiento global en almacenes" data-en="Global warehouse movement">Movimiento global en almacenes</div>
=======
              <h3 data-es="Flujo de Mercancía (Últimos 6 Meses)" data-en="Stock Flow (Last 6 Months)">Flujo de Mercancía (Mes a Mes)</h3>
              <div class="panel-subtitle" data-es="Comparativa entre Entradas y Salidas operativas" data-en="Inbound vs Outbound stock balance">Comparativa entre Entradas y Salidas de almacén</div>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
            </div>
          </div>
          <div style="position: relative; height:280px; width:100%;">
            <canvas id="flowChart"></canvas>
          </div>
        </div>

<<<<<<< HEAD
        <!-- PANEL GRÁFICO 2: DISTRIBUCIÓN POR CATEGORÍAS (DINÁMICO CON MYSQL) -->
        <div class="panel">
          <div class="panel-header">
            <div>
              <h3 data-es="Categorías Registradas" data-en="Registered Categories">Categorías Registradas</h3>
              <div class="panel-subtitle" data-es="BD: Categorías activas" data-en="DB: Active categories">BD: Categorías activas</div>
=======
        <!-- PANEL GRÁFICO 2: DISTRIBUCIÓN POR CATEGORÍAS -->
        <div class="panel">
          <div class="panel-header">
            <div>
              <h3 data-es="Categorías de Stock" data-en="Stock Categories">Distribución de Productos</h3>
              <div class="panel-subtitle" data-es="Valoración total según familia de producto" data-en="Valuation split by product family">Porcentaje por familias de productos</div>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
            </div>
          </div>
          <div style="position: relative; height:280px; width:100%; display:grid; place-items:center;">
            <canvas id="categoryChart"></canvas>
          </div>
        </div>
      </div>

<<<<<<< HEAD
      <!-- SECCIÓN SECUNDARIA: TABLA Y ESTADO DE SUCURSALES -->
      <div class="secondary-grid">
        
        <!-- TABLA DINÁMICA DE ÚLTIMOS PRODUCTOS ACTUALIZADOS -->
        <div class="panel">
          <div class="panel-header">
            <div>
              <h3 data-es="Últimos Productos Actualizados" data-en="Recent Updated Products">Últimos Productos Actualizados</h3>
              <div class="panel-subtitle" data-es="Lectura desde tablas de sucursales" data-en="Live read from branch tables">Lectura desde tablas de sucursales</div>
            </div>
            <a href="movimientos.php" style="color:var(--accent-cyan); font-size:12px; font-weight:700; text-decoration:none;">Ver Todo →</a>
=======
      <!-- SECCIÓN SECUNDARIA: TABLA KARDEX Y ESTADO DE SUCURSALES -->
      <div class="secondary-grid">
        
        <!-- HISTORIAL DE ÚLTIMOS MOVIMIENTOS -->
        <div class="panel">
          <div class="panel-header">
            <div>
              <h3 data-es="Últimos Movimientos (Kardex)" data-en="Recent Stock Movements">Últimos Movimientos (Kardex)</h3>
              <div class="panel-subtitle" data-es="Registro continuo de auditoría de inventario" data-en="Real-time inventory audit log">Monitoreo dinámico en tiempo real</div>
            </div>
            <a href="movimientos.html" style="color:var(--accent-cyan); font-size:12px; font-weight:700; text-decoration:none;">Ver Todo →</a>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
          </div>

          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
<<<<<<< HEAD
                  <th data-es="SKU / Producto" data-en="SKU / Product">SKU / Producto</th>
                  <th data-es="Sucursal" data-en="Branch">Sucursal</th>
                  <th data-es="Stock" data-en="Stock">Stock</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($ultimosMovimientos as $mov): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($mov['sku'] ?? 'S/N') ?></strong><br>
                    <small style="color:var(--text-muted);"><?= htmlspecialchars($mov['nombre']) ?></small>
                  </td>
                  <td><span class="badge badge-info"><?= htmlspecialchars($mov['sucursal']) ?></span></td>
                  <td><?= $mov['stock'] ?> u</td>
                </tr>
                <?php endforeach; ?>
=======
                  <th data-es="SKU / Producto" data-en="SKU / Product">Producto</th>
                  <th data-es="Tipo" data-en="Type">Tipo</th>
                  <th data-es="Cant." data-en="Qty">Cant.</th>
                  <th data-es="Origen/Destino" data-en="Origin/Dest">Almacén</th>
                  <th data-es="Usuario" data-en="User">Operador</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <strong>INV-9021</strong><br>
                    <small style="color:var(--text-muted);">Aceite de Oliva 1L</small>
                  </td>
                  <td><span class="badge badge-success" data-es="Entrada" data-en="Inbound">Entrada</span></td>
                  <td>+150 u</td>
                  <td>Sucursal Centro</td>
                  <td>J. Pérez</td>
                </tr>
                <tr>
                  <td>
                    <strong>INV-4820</strong><br>
                    <small style="color:var(--text-muted);">Conservas de Atún 500g</small>
                  </td>
                  <td><span class="badge badge-danger" data-es="Salida" data-en="Outbound">Salida</span></td>
                  <td>-45 u</td>
                  <td>Sucursal Norte</td>
                  <td>M. Gómez</td>
                </tr>
                <tr>
                  <td>
                    <strong>INV-1102</strong><br>
                    <small style="color:var(--text-muted);">Vino Tinto Reserva</small>
                  </td>
                  <td><span class="badge badge-info" data-es="Traspaso" data-en="Transfer">Traspaso</span></td>
                  <td>80 u</td>
                  <td>Norte ➔ Sur</td>
                  <td>A. Silva</td>
                </tr>
                <tr>
                  <td>
                    <strong>INV-3391</strong><br>
                    <small style="color:var(--text-muted);">Queso Manchego 2kg</small>
                  </td>
                  <td><span class="badge badge-warning" data-es="Ajuste" data-en="Adjustment">Ajuste</span></td>
                  <td>-3 u</td>
                  <td>Sucursal Sur</td>
                  <td>C. López</td>
                </tr>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
              </tbody>
            </table>
          </div>
        </div>

<<<<<<< HEAD
        <!-- OCUPACIÓN REAL POR TABLA DE SUCURSAL -->
        <div class="panel">
          <div class="panel-header">
            <div>
              <h3 data-es="Stock por Sucursal" data-en="Stock by Branch">Stock por Sucursal</h3>
              <div class="panel-subtitle" data-es="Volumen total almacenado" data-en="Total stored volume">Volumen total almacenado</div>
            </div>
          </div>

          <?php foreach($sucursalesData as $sucursalNombre => $totalStock): ?>
          <div class="branch-row">
            <div class="branch-name"><?= $sucursalNombre ?></div>
            <div class="health-bar"><div class="health-progress" style="width: <?= min(($totalStock / 500) * 100, 100) ?>%;"></div></div>
            <div style="font-size: 12px; text-align: right; font-weight:bold; color:var(--accent-cyan);"><?= $totalStock ?> u</div>
          </div>
          <?php endforeach; ?>
=======
        <!-- CAPACIDAD Y ESTADO DE ALMACENES / SUCURSALES -->
        <div class="panel">
          <div class="panel-header">
            <div>
              <h3 data-es="Ocupación de Almacenes" data-en="Warehouse Occupancy">Capacidad de Sucursales</h3>
              <div class="panel-subtitle" data-es="Límite físico de stock almacenado" data-en="Physical storage threshold">Volumen utilizado por ubicación</div>
            </div>
          </div>

          <div class="branch-row">
            <div class="branch-name" data-es="Sucursal Centro" data-en="Central Hub">Sucursal Centro</div>
            <div class="health-bar"><div class="health-progress" style="width: 82%;"></div></div>
            <div style="font-size: 12px; text-align: right; font-weight:bold; color:var(--accent-cyan);">82%</div>
          </div>
          
          <div class="branch-row">
            <div class="branch-name" data-es="Sucursal Norte" data-en="North Depot">Sucursal Norte</div>
            <div class="health-bar"><div class="health-progress" style="width: 64%;"></div></div>
            <div style="font-size: 12px; text-align: right; font-weight:bold; color:var(--accent-cyan);">64%</div>
          </div>

          <div class="branch-row">
            <div class="branch-name" data-es="Sucursal Sur" data-en="South Depot">Sucursal Sur</div>
            <div class="health-bar"><div class="health-progress" style="width: 94%; background: var(--danger);"></div></div>
            <div style="font-size: 12px; text-align: right; font-weight:bold; color:var(--danger);">94%</div>
          </div>

          <div class="branch-row">
            <div class="branch-name" data-es="Almacén Puerto" data-en="Port Warehouse">Almacén Puerto</div>
            <div class="health-bar"><div class="health-progress" style="width: 38%; background: var(--green);"></div></div>
            <div style="font-size: 12px; text-align: right; font-weight:bold; color:var(--green);">38%</div>
          </div>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f

          <!-- PANORAMA CLIMÁTICO REAL DENTRO DEL ERP -->
          <div class="panel weather-panel" style="margin-top:20px; padding:18px; border-radius:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <small style="color:var(--text-muted); font-size:10px; font-weight:800; text-transform:uppercase;" data-es="Clima Operativo Logístico" data-en="Logistics Operational Weather">Clima Logístico</small>
                <div style="font-weight:700; font-size:13px; color:var(--text-primary);" id="locationName">Detectando ubicación...</div>
              </div>
              <span id="weatherIcon" style="font-size:26px;">🌤️</span>
            </div>
            <div style="display:flex; align-items:center; gap:12px; margin-top:8px;">
              <span style="font-size:24px; font-weight:700; color:var(--accent-cyan);" id="weatherTemp">--°C</span>
              <span style="font-size:12px; color:var(--text-secondary);" id="weatherDesc">Cargando clima...</span>
            </div>
          </div>

        </div>

      </div>
    </section>
  </main>
</div>

<!-- CHATBOT -->
<div class="chatbot-launcher" onclick="toggleChat()">
  <div class="robot-avatar">🤖</div>
  <div>
    <small style="display:block; font-size:9px; color:var(--text-muted);">Asistente IA</small>
    <b style="font-size:12px; color:var(--text-primary);">Mediterranean ERP</b>
  </div>
</div>

<div class="chat-window" id="chatWindow">
  <div class="chat-header">
    <strong>Mediterranean AI</strong>
    <button class="chat-close" onclick="toggleChat()">×</button>
  </div>
  <div class="chat-messages" id="chatMessages">
<<<<<<< HEAD
    <div class="message bot">Hola, soy tu asistente Luxor . ¿Necesitas consultar stock, buscar un SKU o generar una orden de traspaso?</div>
=======
    <div class="message bot">Hola, soy tu asistente del ERP. ¿Necesitas consultar stock, buscar un SKU o generar una orden de traspaso?</div>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
  </div>
  <div class="chat-input">
    <input type="text" id="userInput" placeholder="Ej: ¿Qué productos están sin stock?">
    <button onclick="sendMessage()">Enviar</button>
  </div>
</div>

<!-- FOOTER -->
<footer class="copyright-fixed">
<<<<<<< HEAD
  <a href="politica-completa.html">© <?php echo date('Y'); ?> Mediterranean Technologies</a>
=======
  <a href="politica-completa.html">© 2026 Mediterranean Technologies | Enterprise Inventory System</a>
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
</footer>

<script>
  function toggleSidebar() {
    if (window.innerWidth <= 620) {
      document.body.classList.toggle('mobile-open');
    } else {
      document.body.classList.toggle('sidebar-collapsed');
    }
  }

  function mostrarSeccion(id, el = null) {
    document.querySelectorAll(".section").forEach(sec => sec.classList.remove("active"));
    const target = document.getElementById(id);
    if(target) target.classList.add("active");

    if(el) {
      document.querySelectorAll(".side-link").forEach(link => link.classList.remove("active"));
      el.classList.add("active");
    }
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function actualizarHora() {
    const ahora = new Date();
    const h = String(ahora.getHours()).padStart(2, '0');
    const m = String(ahora.getMinutes()).padStart(2, '0');
    const s = String(ahora.getSeconds()).padStart(2, '0');
    const reloj = document.getElementById("currentTime");
    if(reloj) reloj.textContent = `${h}:${m}:${s}`;
  }
  setInterval(actualizarHora, 1000);
<<<<<<< HEAD
=======
  actualizarHora();
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f

  function cambiarIdioma(lang) {
    document.getElementById('btn-es').classList.toggle('active-language', lang === 'es');
    document.getElementById('btn-en').classList.toggle('active-language', lang === 'en');

    document.querySelectorAll('[data-es]').forEach(el => {
      el.textContent = el.getAttribute(`data-${lang}`);
    });
  }

  const chatWindow = document.getElementById("chatWindow");
  function toggleChat() {
    chatWindow.style.display = (chatWindow.style.display === "flex") ? "none" : "flex";
  }

  function sendMessage() {
    const input = document.getElementById("userInput");
    const msg = input.value.trim();
    if(!msg) return;

    addMsg(msg, "user");
    input.value = "";

    setTimeout(() => {
      let resp = "Entendido. Puedes gestionar los registros desde el módulo de Kardex o Inventario.";
      const lower = msg.toLowerCase();
      if(lower.includes("hola")) resp = "¡Hola! ¿En qué puedo asistirte en la gestión de almacenes?";
<<<<<<< HEAD
      if(lower.includes("stock") || lower.includes("crítico")) resp = "Actualmente hay <?= $stockCritico ?> SKUs en nivel crítico que requieren reabastecimiento.";
=======
      if(lower.includes("stock") || lower.includes("crítico")) resp = "Actualmente hay 14 SKUs en nivel crítico. La Sucursal Sur requiere reabastecimiento inmediato.";
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
      addMsg(resp, "bot");
    }, 600);
  }

  function addMsg(text, type) {
    const container = document.getElementById("chatMessages");
    const div = document.createElement("div");
    div.className = `message ${type}`;
    div.textContent = text;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
  }

<<<<<<< HEAD
  /* INICIALIZACIÓN DE GRÁFICOS Y SERVICIOS */
  document.addEventListener("DOMContentLoaded", () => {
    initCharts();
    initRealWeather();
  });

  /* INICIALIZACIÓN DE GRÁFICOS (CHART.JS) */
  function initCharts() {
=======
  /* INICIALIZACIÓN DE GRÁFICOS (CHART.JS) */
  function initCharts() {
    // 1. Gráfico de Flujo de Mercancías
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
    const ctxFlow = document.getElementById('flowChart').getContext('2d');
    new Chart(ctxFlow, {
      type: 'line',
      data: {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
        datasets: [
          {
            label: 'Entradas (Stock In)',
            data: [1200, 1900, 1500, 2200, 1800, 2400],
            borderColor: '#0284c7',
            backgroundColor: 'rgba(2, 132, 199, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 3
          },
          {
            label: 'Salidas (Stock Out)',
            data: [1000, 1400, 1700, 1600, 1500, 2100],
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', labels: { font: { family: 'Plus Jakarta Sans', weight: '600' } } }
        },
        scales: {
          y: { grid: { color: 'rgba(0,0,0,0.05)' } },
          x: { grid: { display: false } }
        }
      }
    });

<<<<<<< HEAD
=======
    // 2. Gráfico de Categorías (Dona)
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
      type: 'doughnut',
      data: {
<<<<<<< HEAD
        labels: [<?php foreach($categoriasList as $cat) echo "'".htmlspecialchars($cat['nombre'])."',"; ?>],
        datasets: [{
          data: [<?php foreach($categoriasList as$cat) echo "1,"; ?>],
=======
        labels: ['Alimentos', 'Bebidas', 'Empaques', 'Insumos', 'Otros'],
        datasets: [{
          data: [40, 25, 15, 12, 8],
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
          backgroundColor: ['#0284c7', '#2563eb', '#4f46e5', '#10b981', '#f59e0b'],
          borderWidth: 4,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', weight: '600' } } }
        },
        cutout: '70%'
      }
    });
  }

  /* SERVICIO DE CLIMA REAL */
  function initRealWeather() {
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        position => {
          const lat = position.coords.latitude;
          const lon = position.coords.longitude;
          getCityName(lat, lon);
          getWeatherData(lat, lon);
        },
        error => {
<<<<<<< HEAD
          document.getElementById("locationName").textContent = "Bogotá, CO (Defecto)";
          getWeatherData(4.6097, -74.0817);
        }
      );
    } else {
      document.getElementById("locationName").textContent = "Bogotá, CO (Defecto)";
      getWeatherData(4.6097, -74.0817);
=======
          document.getElementById("locationName").textContent = "Madrid, ES (Defecto)";
          getWeatherData(40.4168, -3.7038);
        }
      );
    } else {
      document.getElementById("locationName").textContent = "Madrid, ES (Defecto)";
      getWeatherData(40.4168, -3.7038);
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
    }
  }

  function getCityName(lat, lon) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
      .then(res => res.json())
      .then(data => {
        const city = data.address.city || data.address.town || data.address.village || "Ubicación Local";
        document.getElementById("locationName").textContent = `${city}`;
      })
      .catch(() => {
        document.getElementById("locationName").textContent = `Lat: ${lat.toFixed(2)}, Lon: ${lon.toFixed(2)}`;
      });
  }

  function getWeatherData(lat, lon) {
    fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true`)
      .then(res => res.json())
      .then(data => {
        const weather = data.current_weather;
        document.getElementById("weatherTemp").textContent = `${Math.round(weather.temperature)}°C`;
        const codeInfo = getWeatherCodeInfo(weather.weathercode);
        document.getElementById("weatherDesc").textContent = codeInfo.desc;
        document.getElementById("weatherIcon").textContent = codeInfo.icon;
      })
      .catch(() => {
        document.getElementById("weatherDesc").textContent = "Clima disponible";
      });
  }

  function getWeatherCodeInfo(code) {
    if (code === 0) return { desc: "Cielo Despejado", icon: "☀️" };
    if (code >= 1 && code <= 3) return { desc: "Nublado", icon: "⛅" };
    if (code >= 51 && code <= 67) return { desc: "Lluvia", icon: "🌧️" };
    return { desc: "Normal", icon: "🌤️" };
  }

<<<<<<< HEAD
  tsParticles.load("tsparticles", {
    particles: {
      number: {
        value: 80,
        density: {
          enable: true,
          value_area: 800
        }
      },
      color: {
        value: "#ffffff"
      },
      shape: {
        type: "circle"
      },
      opacity: {
        value: 0.5
      },
      size: {
        value: 3,
        random: true
      },
      line_linked: {
        enable: true,
        distance: 150,
        color: "#ffffff",
        opacity: 0.4,
        width: 1
      },
      move: {
        enable: true,
        speed: 2,
        direction: "none",
        straight: false
      }
    },
    interactivity: {
      events: {
        onhover: {
          enable: true,
          mode: "repulse"
        }
      }
    }
  });
=======
 tsParticles.load("tsparticles", {
  particles: {
    number: {
      value: 80,
      density: {
        enable: true,
        value_area: 800
      }
    },
    color: {
      value: "#ffffff"
    },
    shape: {
      type: "circle"
    },
    opacity: {
      value: 0.5
    },
    size: {
      value: 3,
      random: true
    },
    line_linked: {
      enable: true,
      distance: 150,
      color: "#ffffff",
      opacity: 0.4,
      width: 1
    },
    move: {
      enable: true,
      speed: 2,
      direction: "none",
      straight: false
    }
  },
  interactivity: {
    events: {
      onhover: {
        enable: true,
        mode: "repulse"
      }
    }
  }
});
>>>>>>> cd4f4f931e399817bdd82fefcf81c8d48407574f
</script>
</body>
</html>