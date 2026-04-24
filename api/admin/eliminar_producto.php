<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_negocio'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id_producto'])) {
        throw new Exception("ID no proporcionado");
    }

    $id_producto = (int)$data['id_producto'];
    $id_negocio = $_SESSION['id_negocio'];

    // Verificar si el producto existe y pertenece al negocio
    $stmt_check = $pdo->prepare("SELECT id_producto FROM productos WHERE id_producto = ? AND id_negocio = ?");
    $stmt_check->execute([$id_producto, $id_negocio]);
    
    if (!$stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado o no autorizado']);
        exit;
    }

    // === SOFT DELETE: Marcar como inactivo ===
    $stmt = $pdo->prepare("UPDATE productos SET activo = 0, updated_at = NOW() WHERE id_producto = ? AND id_negocio = ?");
    $stmt->execute([$id_producto, $id_negocio]);

    echo json_encode(['success' => true, 'message' => 'Producto desactivado correctamente']);

} catch (Exception $e) {
    error_log("Error en eliminar_producto.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>