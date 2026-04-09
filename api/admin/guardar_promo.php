<?php
// 1. Desactivar toda salida de errores
error_reporting(0);
ini_set('display_errors', '0');

// 2. Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// 3. Evitar múltiples session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id_promo = (int)($input['id_promo'] ?? 0);
$nombre = trim($input['nombre'] ?? '');
$precio_promo = (float)($input['precio_promo'] ?? 0);
$activo = !empty($input['activo']) && $input['activo'] !== '0';

if (!$id_promo || !$nombre || $precio_promo <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $stmt_check = $pdo->prepare("
        SELECT pp.id_promo 
        FROM productos_promo pp
        JOIN productos p ON pp.id_producto_base = p.id_producto
        WHERE pp.id_promo = ? AND p.id_negocio = ?
    ");
    $stmt_check->execute([$id_promo, $_SESSION['id_negocio']]);
    
    if (!$stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Promoción no encontrada']);
        exit;
    }

    $stmt_update = $pdo->prepare("
        UPDATE productos_promo 
        SET nombre = ?, precio_promo = ?, activo = ?
        WHERE id_promo = ?
    ");
    $stmt_update->execute([$nombre, $precio_promo, $activo, $id_promo]);

    // 4. Limpiar buffer y salir inmediatamente
    ob_end_clean();
    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    error_log("Error promo: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error interno']);
    exit;
}
?>