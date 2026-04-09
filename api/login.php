<?php
header('Content-Type: application/json');

// Detectar raíz del proyecto de forma robusta
if (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['DOCUMENT_ROOT'], '/public') !== false) {
    // Ej: DOCUMENT_ROOT = /app/public → raíz = /app
    $root = dirname($_SERVER['DOCUMENT_ROOT']);
} else {
    // Fallback para desarrollo local
    $root = dirname(__DIR__, 2);
}

// Asegurar que la ruta no esté vacía
if (empty($root) || !is_dir($root)) {
    $root = '/app'; // Ruta explícita para Railway
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