<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

try {
    // Leer datos (soporta tanto POST normal como JSON)
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $nombre = trim($input['usuario'] ?? '');
        $password = $input['password'] ?? '';
    } else {
        $nombre = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
    }

    if (empty($nombre) || empty($password)) {
        throw new Exception('Nombre y contraseña son obligatorios');
    }

    // Buscar solo por nombre (asume unicidad global o por diseño)
    $stmt = $pdo->prepare("
        SELECT 
            u.id_usuario, 
            u.nombre, 
            u.apellido, 
            u.rol, 
            u.password,
            u.id_negocio,
            n.nombre AS nombre_negocio
        FROM usuarios u
        JOIN negocios n ON u.id_negocio = n.id_negocio
        WHERE u.nombre = ?
    ");
    $stmt->execute([$nombre]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        throw new Exception('Nombre o contraseña incorrectos');
    }

    // Guardar en sesión
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nombre_usuario'] = $usuario['nombre'];
    $_SESSION['apellido_usuario'] = $usuario['apellido'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['id_negocio'] = $usuario['id_negocio'];
    $_SESSION['nombre_negocio'] = $usuario['nombre_negocio'];
    $_SESSION['email'] = $usuario['email'];

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>