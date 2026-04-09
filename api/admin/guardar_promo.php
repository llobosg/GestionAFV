<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session.php'; // ← usa el nuevo sistema
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
    $stmt = $pdo->prepare("
        SELECT 1 FROM productos_promo pp
        JOIN productos p ON pp.id_producto_base = p.id_producto
        WHERE pp.id_promo = ? AND p.id_negocio = ?
    ");
    $stmt->execute([$id_promo, $_SESSION['id_negocio']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Promoción no autorizada']);
        exit;
    }

    $update = $pdo->prepare("
        UPDATE productos_promo 
        SET nombre = ?, precio_promo = ?, activo = ?
        WHERE id_promo = ?
    ");
    $update->execute([$nombre, $precio_promo, $activo, $id_promo]);

    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    error_log("PROMO_ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno']);
    exit;
}
?>