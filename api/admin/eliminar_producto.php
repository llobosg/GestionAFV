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

    // 1. VERIFICAR SI ES UNA PROMOCIÓN
    // Asumiendo que la PK de productos_promo es id_promo, pero en el frontend enviamos id_producto
    // Si tu tabla productos_promo usa id_promo como PK, ajusta la consulta.
    // Si usas id_producto como referencia cruzada, ajusta según tu estructura.
    // Basado en tu estructura anterior, asumimos que buscamos por id_promo si es promo.
    
    // Intentamos buscar en promos primero
    $stmt_check_promo = $pdo->prepare("SELECT id_promo FROM productos_promo WHERE id_promo = ? AND id_negocio = ?");
    $stmt_check_promo->execute([$id_producto, $id_negocio]);
    $es_promo = $stmt_check_promo->fetch();

    if ($es_promo) {
        // ELIMINAR PROMOCIÓN
        $stmt_del = $pdo->prepare("DELETE FROM productos_promo WHERE id_promo = ? AND id_negocio = ?");
        $stmt_del->execute([$id_producto, $id_negocio]);
        echo json_encode(['success' => true, 'message' => 'Promoción eliminada']);
        
    } else {
        // ELIMINAR PRODUCTO NORMAL
        $stmt_del = $pdo->prepare("DELETE FROM productos WHERE id_producto = ? AND id_negocio = ?");
        $stmt_del->execute([$id_producto, $id_negocio]);
        
        if ($stmt_del->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        }
    }

} catch (Exception $e) {
    error_log("Error en eliminar_producto.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>