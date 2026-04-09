<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

// Permitir acceso a cajeras y admins
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['cajera', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;

try {
    // === Productos normales ===
    $stmt_normales = $pdo->prepare("
        SELECT 
            id_producto,
            producto,
            precio_venta,
            stock_actual,
            'normal' AS tipo
        FROM productos 
        WHERE id_negocio = ? AND activo = 1
        ORDER BY producto
    ");
    $stmt_normales->execute([$id_negocio]);
    $productos_normales = $stmt_normales->fetchAll(PDO::FETCH_ASSOC);

    // === Productos promocionales ===
    $stmt_promo = $pdo->prepare("
        SELECT 
            pp.id_promo AS id_producto,
            CONCAT(p.producto, ' (', pp.cantidad_unidades, 'x$', FORMAT(pp.precio_promo, 0), ')') AS producto,
            pp.precio_promo AS precio_venta,
            p.stock_actual,
            'promo' AS tipo,
            pp.id_producto_base,
            pp.cantidad_unidades
        FROM productos_promo pp
        JOIN productos p ON pp.id_producto_base = p.id_producto
        WHERE p.id_negocio = ? AND pp.activo = 1 AND p.activo = 1
        ORDER BY p.producto, pp.cantidad_unidades
    ");
    $stmt_promo->execute([$id_negocio]);
    $productos_promo = $stmt_promo->fetchAll(PDO::FETCH_ASSOC);

    // === Combinar y devolver ===
    $todos_los_productos = array_merge($productos_normales, $productos_promo);
    echo json_encode($todos_los_productos);

} catch (Exception $e) {
    error_log("Error en listar_productos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar productos']);
}
?>