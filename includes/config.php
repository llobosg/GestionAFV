<?php
// includes/config.php — Gestión AFV — Versión Railway-friendly

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Manejo de errores silencioso en producción
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Detectar entorno Railway
$isRailway = getenv('RAILWAY_ENVIRONMENT') !== false;

// Cargar variables según entorno
if ($isRailway) {
    $host = getenv('MYSQLHOST');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
    $dbname = getenv('MYSQLDATABASE');
    $port = getenv('MYSQLPORT') ?: '3306';
} else {
    // Entorno local (XAMPP)
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'gestionafv_local';
    $port = '3306';
}

// Validación crítica
if (empty($host) || empty($user) || empty($dbname)) {
    error_log("❌ Variables de base de datos faltantes en entorno: " . ($isRailway ? 'Railway' : 'Local'));
    http_response_code(500);
    die("Error: Base de datos no configurada");
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("❌ Error de conexión a DB: " . $e->getMessage());
    http_response_code(500);
    die("No se pudo conectar a la base de datos");
}
?>