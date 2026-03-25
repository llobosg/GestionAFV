<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_producto = (int)($data['id_producto'] ?? 0);
$cantidad = (float)($data['cantidad'] ?? 0);
$comentario = trim($data['comentario'] ?? '');

if ($id_producto <= 0 || $cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar stock
    $stmt = $pdo->prepare("SELECT stock_actual FROM productos WHERE id_producto = ? AND id_negocio = ?");
    $stmt->execute([$id_producto, $_SESSION['id_negocio']]);
    $stock = $stmt->fetchColumn();

    if (!$stock || $stock < $cantidad) {
        throw new Exception('Stock insuficiente');
    }

    // Registrar venta tipo "merma"
    $stmt = $pdo->prepare("
        INSERT INTO ventas (id_negocio, id_cajera, fecha, hora, total, metodo_pago)
        VALUES (?, ?, CURDATE(), CURTIME(), 0.00, 'merma')
    ");
    $stmt->execute([$_SESSION['id_negocio'], $_SESSION['id_usuario']]);
    $id_venta = $pdo->lastInsertId();

    // Registrar detalle (con valor negativo si se desea, o 0)
    $precio_unitario = 0.00;
    $subtotal = 0.00;

    $pdo->prepare("
        INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_unitario, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$id_venta, $id_producto, $cantidad, $precio_unitario, $subtotal]);

    // Actualizar stock
    $pdo->prepare("
        UPDATE productos SET stock_actual = stock_actual - ?
        WHERE id_producto = ?
    ")->execute([$cantidad, $id_producto]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error merma: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'No se pudo registrar la merma']);
}
?>