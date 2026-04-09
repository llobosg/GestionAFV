<?php
header('Content-Type: application/json');

// === DETECTAR RAÍZ DEL PROYECTO ===
$possibleRoots = [
    '/app',
    dirname(__DIR__, 2),
    ($_SERVER['DOCUMENT_ROOT'] ?? '') ? dirname($_SERVER['DOCUMENT_ROOT']) : null,
];

$root = null;
foreach ($possibleRoots as $path) {
    if ($path && is_dir($path . '/includes') && file_exists($path . '/includes/config.php')) {
        $root = $path;
        break;
    }
}

if (!$root) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuración no encontrada']);
    exit;
}

require_once $root . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $rawInput = file_get_contents('php://input');
    $input = [];

    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
    }

    if (!$input || !isset($input['usuario'])) {
        $input = [
            'usuario' => $_POST['usuario'] ?? '',
            'password' => $_POST['password'] ?? ''
        ];
    }

    $usuario = trim($input['usuario'] ?? '');
    $password = $input['password'] ?? '';

    if (!$usuario || !$password) {
        throw new Exception('Nombre de usuario y contraseña son obligatorios');
    }

    // Buscar por NOMBRE (no por email)
    $stmt = $pdo->prepare("
    SELECT id_usuario, nombre, apellido, rol, id_negocio, password 
        FROM usuarios 
        WHERE nombre = ? AND activo = 1
    ");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('Credenciales incorrectas');
    }

    $_SESSION['id_usuario'] = $user['id_usuario'];
    $_SESSION['nombre_usuario'] = $user['nombre'];
    $_SESSION['apellido_usuario'] = $user['apellido'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['id_negocio'] = $user['id_negocio'];

    $stmt_negocio = $pdo->prepare("SELECT nombre FROM negocios WHERE id_negocio = ?");
    $stmt_negocio->execute([$user['id_negocio']]);
    $negocio = $stmt_negocio->fetch();
    $_SESSION['nombre_negocio'] = $negocio['nombre'] ?? 'Negocio';

    echo json_encode([
        'success' => true,
        'redirect' => '/public/home.php'
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>