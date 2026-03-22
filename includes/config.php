<?php
// includes/config.php — Gestión AFV
header("Access-Control-Allow-Origin: https://gestionafv-produccion.up.railway.app"); // Ajusta si usas dominio personalizado

// Configuración de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Manejo de errores silencioso en producción
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

/**
 * Carga variables de entorno desde múltiples fuentes
 */
function loadEnvVars() {
    // Cargar .env en CLI o desarrollo local
    if (php_sapi_name() === 'cli' || !getenv('RAILWAY_ENVIRONMENT')) {
        if (file_exists(__DIR__ . '/.env')) {
            $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
                    [$key, $value] = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }

    // Prioridad: $_ENV > getenv() > $_SERVER
    return [
        'MYSQLHOST'      => $_ENV['MYSQLHOST']      ?? getenv('RAILWAY_DB_HOST')      ?? $_SERVER['MYSQLHOST']      ?? '127.0.0.1',
        'MYSQLUSER'      => $_ENV['MYSQLUSER']      ?? getenv('RAILWAY_DB_USER')      ?? $_SERVER['MYSQLUSER']      ?? 'root',
        'MYSQLPASSWORD'  => $_ENV['MYSQLPASSWORD']  ?? getenv('RAILWAY_DB_PASSWORD')  ?? $_SERVER['MYSQLPASSWORD']  ?? '',
        'MYSQLDATABASE'  => $_ENV['MYSQLDATABASE']  ?? getenv('RAILWAY_DB_NAME')      ?? $_SERVER['MYSQLDATABASE']  ?? 'gestionafv_local',
        'MYSQLPORT'      => $_ENV['MYSQLPORT']      ?? getenv('RAILWAY_DB_PORT')      ?? $_SERVER['MYSQLPORT']      ?? '3306',
        'BREVO_API_KEY'  => $_ENV['BREVO_API_KEY']  ?? getenv('BREVO_API_KEY')        ?? $_SERVER['BREVO_API_KEY']  ?? ''
    ];
}

$env = loadEnvVars();

// Validación crítica de conexión
if (empty($env['MYSQLHOST']) || empty($env['MYSQLUSER']) || empty($env['MYSQLDATABASE'])) {
    error_log("❌ Faltan variables esenciales para la base de datos");
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
    }
    die("Error de configuración: base de datos no disponible\n");
}

// Constantes útiles
define('BREVO_API_KEY', $env['BREVO_API_KEY']);

try {
    $pdo = new PDO(
        "mysql:host={$env['MYSQLHOST']};port={$env['MYSQLPORT']};dbname={$env['MYSQLDATABASE']};charset=utf8mb4",
        $env['MYSQLUSER'],
        $env['MYSQLPASSWORD'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("❌ Error de conexión a DB: " . $e->getMessage());
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
    }
    die("No se pudo conectar a la base de datos\n");
}
?>