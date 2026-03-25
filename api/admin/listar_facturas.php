<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$id_negocio = $_SESSION['id_negocio'];

// Agrupar por factura única (nro + proveedor)
$stmt = $pdo->prepare("
    SELECT 
        MIN(id_ingreso) as id_ingreso,
        nro_factura,
        proveedor,
        fecha_factura,
        MAX(monto_factura) as monto_factura,
        estado_factura,
        fechapago_factura,
        SUM(precio_total) as total_real
    FROM ingresos_stock 
    WHERE id_negocio = ?
    GROUP BY nro_factura, proveedor, fecha_factura
    ORDER BY fecha_factura DESC
    LIMIT 20
");
$stmt->execute([$id_negocio]);
echo json_encode($stmt->fetchAll());
?>