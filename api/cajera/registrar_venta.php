<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

if ($_SESSION['rol'] !== 'cajera' && $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();

    // Insertar cabecera de venta
    $stmt = $pdo->prepare("
        INSERT INTO ventas (id_negocio, id_cajera, fecha, hora, total, metodo_pago)
        VALUES (?, ?, CURDATE(), CURTIME(), ?, ?)
    ");
    $stmt->execute([
        $data['id_negocio'],
        $data['id_cajera'],
        $data['total'],
        $data['metodo_pago']
    ]);
    $id_venta = $pdo->lastInsertId();

    // Insertar detalles y actualizar stock
    foreach ($data['detalles'] as $detalle) {
        // Obtener stock actual
        $prod = $pdo->prepare("SELECT stock_actual FROM productos WHERE id_producto = ?");
        $prod->execute([$detalle['id_producto']]);
        $stock = $prod->fetchColumn();

        if ($stock < $detalle['cantidad']) {
            throw new Exception("Stock insuficiente para producto ID {$detalle['id_producto']}");
        }

        // Insertar detalle
        $pdo->prepare("
            INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $id_venta,
            $detalle['id_producto'],
            $detalle['cantidad'],
            $detalle['precio_unitario'],
            $detalle['subtotal']
        ]);

        // Reducir stock
        $pdo->prepare("
            UPDATE productos SET stock_actual = stock_actual - ?
            WHERE id_producto = ?
        ")->execute([$detalle['cantidad'], $detalle['id_producto']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_venta' => $id_venta]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en venta: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar la venta']);
}
?>