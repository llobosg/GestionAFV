<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

// Validar rol
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['cajera', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Leer datos
$data = json_decode(file_get_contents('php://input'), true);
$id_venta = (int)($data['id_venta'] ?? 0);

if ($id_venta <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de venta inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar que la venta pertenece al negocio actual
    $stmt = $pdo->prepare("SELECT id_negocio FROM ventas WHERE id_venta = ?");
    $stmt->execute([$id_venta]);
    $venta = $stmt->fetch();

    if (!$venta || $venta['id_negocio'] != $_SESSION['id_negocio']) {
        throw new Exception('Venta no encontrada o acceso denegado');
    }

    // Obtener todos los ítems para restaurar stock
    $detalles = $pdo->prepare("
        SELECT id_producto, cantidad 
        FROM ventas_detalle 
        WHERE id_venta = ?
    ");
    $detalles->execute([$id_venta]);
    $items = $detalles->fetchAll();

    // Restaurar stock por cada producto
    foreach ($items as $item) {
        $pdo->prepare("
            UPDATE productos 
            SET stock_actual = stock_actual + ? 
            WHERE id_producto = ?
        ")->execute([
            $item['cantidad'],
            $item['id_producto']
        ]);
    }

    // Eliminar detalles
    $pdo->prepare("DELETE FROM ventas_detalle WHERE id_venta = ?")
        ->execute([$id_venta]);

    // Eliminar cabecera
    $pdo->prepare("DELETE FROM ventas WHERE id_venta = ?")
        ->execute([$id_venta]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Venta anulada y stock restaurado']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error al anular venta {$id_venta}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'No se pudo anular la venta']);
}
?>