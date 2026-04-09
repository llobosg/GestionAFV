<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    die();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['id_producto'])) {
    $stmt = $pdo->prepare("
    UPDATE productos 
        SET 
            tipo = ?, familia = ?, subfamilia = ?, unidad_medida = ?, 
            precio_compra = ?, porc_utilidad = ?, 
            stock_actual = ?, stock_critico = ?
        WHERE id_producto = ? AND id_negocio = ?
    ");
    $stmt->execute([
        $data['tipo'],
        $data['familia'],
        $data['subfamilia'],
        $data['unidad_medida'],
        $data['precio_compra'],
        $data['porc_utilidad'],
        $data['stock_actual'] ?? 0,
        $data['stock_critico'] ?? 10,
        $data['id_producto'],
        $_SESSION['id_negocio']
    ]);
} else {
    // Dentro del bloque de inserción:
    $codigo = strtoupper(substr($data['tipo'], 0, 3)) . '-' . 
            strtoupper(str_replace(' ', '', $data['familia'])) . '-' . 
            strtoupper(str_replace(' ', '', $data['subfamilia'])) . '-' . 
            uniqid();

    $stmt = $pdo->prepare("
        INSERT INTO productos (
            codigo, tipo, id_negocio, familia, subfamilia, 
            unidad_medida, precio_compra, porc_utilidad, 
            stock_actual, stock_critico
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $codigo,
        $data['tipo'],
        $data['id_negocio'],
        $data['familia'],
        $data['subfamilia'],
        $data['unidad_medida'],
        $data['precio_compra'],
        $data['porc_utilidad'],
        $data['stock_actual'] ?? 0,
        $data['stock_critico'] ?? 10
    ]);

    // En el INSERT de ingresos_stock
    $pdo->prepare("
        INSERT INTO ingresos_stock (
            id_producto, id_negocio, cantidad, precio_compra_unitario,
            nro_factura, proveedor, fecha_factura, monto_factura, 
            estado_factura, fechapago_factura
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $id_producto,
        $data['id_negocio'],
        $data['cantidad'],
        $data['precio_compra'],
        $data['nro_factura'] ?: null,
        $data['proveedor'],
        $data['fecha_factura'],
        $data['monto_factura'] ?: null,
        $data['estado_factura'],
        $data['fecha_pago'] ?: null
    ]);

    // Actualizar stock_actual del producto
    $pdo->prepare("
        UPDATE productos 
        SET stock_actual = stock_actual + ?
        WHERE id_producto = ?
    ")->execute([$data['cantidad'], $id_producto]);
}

echo json_encode(['success' => true]);
?>