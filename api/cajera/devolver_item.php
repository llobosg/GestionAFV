<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

$data = json_decode(file_get_contents('php://input'), true);
$id_detalle = $data['id_detalle'];
$id_venta = $data['id_venta'];

try {
    $pdo->beginTransaction();

    // Obtener detalle
    $stmt = $pdo->prepare("SELECT id_producto, cantidad FROM ventas_detalle WHERE id_detalle = ?");
    $stmt->execute([$id_detalle]);
    $detalle = $stmt->fetch();
    if (!$detalle) throw new Exception('Detalle no encontrado');

    // Eliminar el ítem
    $pdo->prepare("DELETE FROM ventas_detalle WHERE id_detalle = ?")->execute([$id_detalle]);

    // Restaurar stock
    $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id_producto = ?")
        ->execute([$detalle['cantidad'], $detalle['id_producto']]);

    // Recalcular total de la venta
    $total = $pdo->prepare("SELECT COALESCE(SUM(subtotal), 0) FROM ventas_detalle WHERE id_venta = ?");
    $total->execute([$id_venta]);
    $nuevo_total = $total->fetchColumn();

    // Actualizar venta
    $pdo->prepare("UPDATE ventas SET total = ? WHERE id_venta = ?")->execute([$nuevo_total, $id_venta]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Devolución error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al devolver']);
}
?>