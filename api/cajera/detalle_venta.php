<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT v.*, GROUP_CONCAT(d.id_detalle, '|', d.id_producto, '|', d.cantidad, '|', d.precio_unitario, '|', d.subtotal) AS detalles
    FROM ventas v
    LEFT JOIN ventas_detalle d ON v.id_venta = d.id_venta
    WHERE v.id_venta = ? AND v.id_negocio = ?
    GROUP BY v.id_venta
");
$stmt->execute([$id, $_SESSION['id_negocio']]);
$venta = $stmt->fetch();

if (!$venta) {
    echo json_encode(['venta' => null, 'detalles' => []]);
    exit;
}

$detalles = [];
if ($venta['detalles']) {
    foreach (explode(',', $venta['detalles']) as $item) {
        [$id_det, $id_prod, $cant, $precio, $subt] = explode('|', $item);
        // Obtener nombre del producto
        $p = $pdo->prepare("SELECT producto FROM productos WHERE id_producto = ?");
        $p->execute([$id_prod]);
        $prod = $p->fetchColumn() ?: 'Producto eliminado';
        $detalles[] = [
            'id_detalle' => $id_det,
            'producto' => $prod,
            'cantidad' => $cant,
            'precio_unitario' => $precio,
            'subtotal' => $subt
        ];
    }
}

echo json_encode(['venta' => $venta, 'detalles' => $detalles]);
?>