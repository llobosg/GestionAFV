<?php
// includes/config.php — Gestión AFV — Versión Railway compatible

session_start();

// Detectar entorno Railway
$isRailway = getenv('RAILWAY_ENVIRONMENT') !== false;

if ($isRailway) {
    $host = getenv('MYSQLHOST');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
    $dbname = getenv('MYSQLDATABASE');
    $port = getenv('MYSQLPORT') ?: '3306';
} else {
    // Local (XAMPP)
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'gestionafv_local';
    $port = '3306';
}
// Definir constante para Brevo
if (!defined('BREVO_API_KEY')) {
    define('BREVO_API_KEY', $env['BREVO_API_KEY'] ?? '');
}

// Validación crítica
if (empty($host) || empty($user) || empty($dbname)) {
    http_response_code(500);
    die("Error: Variables de base de datos faltantes");
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("❌ Error DB: " . $e->getMessage());
    http_response_code(500);
    die("No se pudo conectar a la base de datos");
}
?>