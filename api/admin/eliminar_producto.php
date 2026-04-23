<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación básica de seguridad
if (!isset($_SESSION['id_negocio'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

try {
    // Obtener datos del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['id_producto'])) {
        throw new Exception("ID de producto no proporcionado");
    }

    $id_producto = (int)$data['id_producto'];
    $id_negocio = $_SESSION['id_negocio'];

    // 1. VERIFICAR SI ES UNA PROMOCIÓN
    // Buscamos en la tabla productos_promo. 
    // Asumimos que la PK de productos_promo también se llama id_producto o id_promo. 
    // Si tu tabla usa 'id_promo', cambia la columna abajo.
    $stmt_check_promo = $pdo->prepare("SELECT id_producto FROM productos_promo WHERE id_promo = ? AND id_negocio = ?");
    $stmt_check_promo->execute([$id_promo, $id_negocio]);
    $es_promo = $stmt_check_promo->fetch();

    if ($es_promo) {
        // === ELIMINAR PROMOCIÓN ===
        $stmt_del = $pdo->prepare("DELETE FROM productos_promo WHERE id_promo = ? AND id_negocio = ?");
        $stmt_del->execute([$id_promo, $id_negocio]);
        
        echo json_encode(['success' => true, 'message' => 'Promoción eliminada correctamente']);
        
    } else {
        // === ELIMINAR PRODUCTO NORMAL ===
        // Opcional: Verificar si tiene stock o movimientos antes de borrar (buena práctica)
        
        $stmt_del = $pdo->prepare("DELETE FROM productos WHERE id_producto = ? AND id_negocio = ?");
        $stmt_del->execute([$id_producto, $id_negocio]);
        
        // Verificar si realmente se borró algo
        if ($stmt_del->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente']);
        } else {
            // Si no encontró el producto, podría ser que ya estaba borrado o no pertenece a este negocio
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado o no autorizado']);
        }
    }

} catch (Exception $e) {
    error_log("Error en eliminar_producto.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>