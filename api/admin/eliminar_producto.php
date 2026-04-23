<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

// Validación de sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;

// Obtener datos del body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id_producto'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id_producto = intval($data['id_producto']);

try {
    // Paso 1: Verificar si es una promoción
    // Buscamos en productos_promo usando id_promo (que enviamos como id_producto en el frontend)
    $stmt_check = $pdo->prepare("SELECT id_promo FROM productos_promo WHERE id_promo = ? AND id_negocio = ?");
    $stmt_check->execute([$id_producto, $id_negocio]);
    $es_promo = $stmt_check->fetch();

    if ($es_promo) {
        // Paso 2a: Eliminar Promoción
        $stmt_del = $pdo->prepare("DELETE FROM productos_promo WHERE id_promo = ? AND id_negocio = ?");
        $stmt_del->execute([$id_producto, $id_negocio]);
        
        echo json_encode(['success' => true, 'message' => 'Promoción eliminada correctamente']);
    } else {
        // Paso 2b: Eliminar Producto Normal
        // Verificamos primero que exista y pertenezca al negocio
        $stmt_check_prod = $pdo->prepare("SELECT id_producto FROM productos WHERE id_producto = ? AND id_negocio = ?");
        $stmt_check_prod->execute([$id_producto, $id_negocio]);
        
        if (!$stmt_check_prod->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado o no pertenece a este negocio']);
            exit;
        }

        $stmt_del = $pdo->prepare("DELETE FROM productos WHERE id_producto = ? AND id_negocio = ?");
        $stmt_del->execute([$id_producto, $id_negocio]);
        
        echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente']);
    }

} catch (Exception $e) {
    error_log("Error al eliminar producto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>