<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

try {
    if (!isset($_SESSION['id_negocio']) || !isset($_SESSION['id_usuario'])) {
        throw new Exception('Sesión inválida');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $productos = $data['productos'] ?? [];

    if (empty($productos)) {
        throw new Exception('No hay productos');
    }

    $pdo->beginTransaction();

    // Insertar venta
    $pdo->prepare("
        INSERT INTO ventas (id_negocio, id_cajera, fecha, hora, total, metodo_pago)
        VALUES (?, ?, CURDATE(), CURTIME(), 0, 'efectivo')
    ")->execute([$_SESSION['id_negocio'], $_SESSION['id_usuario']]);
    $id_venta = $pdo->lastInsertId();

    $total_venta = 0;

    foreach ($productos as $p) {
        $id_producto = (int)$p['id_producto'];
        $precio_venta = (float)$p['precio_venta'];
        $tipo_venta = $p['tipo_venta'] ?? 'efectivo';

        // Determinar cantidad real
        if ($p['um'] === 'gramos') {
            $cantidad = (float)($p['peso'] ?? 0) / 1000; // g → kg
        } else {
            $cantidad = (float)($p['um_cantidad'] ?? 1);
        }

        if ($cantidad <= 0) continue;

        $subtotal = $precio_venta * $cantidad;
        $total_venta += $subtotal;

        // Registrar en detalle
        $pdo->prepare("
            INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, um, tipo_venta)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$id_venta, $id_producto, $cantidad, $precio_venta, $p['um'], $tipo_venta]);

        // Actualizar stock
        $pdo->prepare("
            UPDATE productos SET stock_actual = stock_actual - ? WHERE id_producto = ?
        ")->execute([$cantidad, $id_producto]);
    }

    // Actualizar total en venta
    $pdo->prepare("UPDATE ventas SET total = ? WHERE id_venta = ?")->execute([$total_venta, $id_venta]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Venta registrada']);

} catch (Exception $e) {
    $pdo->rollback();
    http_response_code(400);
    error_log("Error en grabar_venta.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>