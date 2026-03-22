<?php
// Detectar entorno Railway
if (getenv('RAILWAY_ENVIRONMENT')) {
    $host = getenv('RAILWAY_DB_HOST');
    $dbname = getenv('RAILWAY_DB_NAME');
    $username = getenv('RAILWAY_DB_USER');
    $password = getenv('RAILWAY_DB_PASSWORD');
    $port = getenv('RAILWAY_DB_PORT') ?: '3306';
} else {
    // Local (XAMPP)
    $host = 'localhost';
    $dbname = 'gestionafv_local';
    $username = 'root';
    $password = '';
    $port = '3306';
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>