<?php
header('Content-Type: application/json');

// === DETECTAR RAÍZ DEL PROYECTO DE FORMA CONFIABLE ===
$possibleRoots = [
    '/app', // Railway
    dirname(__DIR__, 2), // Desarrollo local: /proyecto/api → /proyecto
    $_SERVER['DOCUMENT_ROOT'] ? dirname($_SERVER['DOCUMENT_ROOT']) : null,
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
    echo json_encode(['success' => false, 'message' => 'Error interno: configuración no encontrada']);
    exit;
}

require_once $root . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        throw new Exception('Email y contraseña son obligatorios');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }

    $stmt = $pdo->prepare("
        SELECT id_usuario, nombre, apellido, rol, id_negocio, password_hash 
        FROM usuarios 
        WHERE email = ? AND activo = 1
    ");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        throw new Exception('Credenciales incorrectas');
    }

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nombre_usuario'] = $usuario['nombre'];
    $_SESSION['apellido_usuario'] = $usuario['apellido'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['id_negocio'] = $usuario['id_negocio'];

    $stmt_negocio = $pdo->prepare("SELECT nombre FROM negocios WHERE id_negocio = ?");
    $stmt_negocio->execute([$usuario['id_negocio']]);
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