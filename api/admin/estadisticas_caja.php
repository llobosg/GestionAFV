<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$id_negocio = $_SESSION['id_negocio'];
$periodo = $_GET['periodo'] ?? 'dia'; // dia, semana, mes, ytd

// Rango de fechas
$hoy = date('Y-m-d');
switch ($periodo) {
    case 'semana':
        $inicio = date('Y-m-d', strtotime('-6 days'));
        break;
    case 'mes':
        $inicio = date('Y-m-01');
        break;
    case 'ytd':
        $inicio = date('Y-01-01');
        break;
    default:
        $inicio = $hoy;
}

// 1. Ventas totales (excluyendo mermas)
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(total), 0) as ventas,
        COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) as efectivo,
        COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END), 0) as tarjeta
    FROM ventas 
    WHERE id_negocio = ? AND fecha BETWEEN ? AND ? 
      AND metodo_pago IN ('efectivo', 'transferencia')
");
$stmt->execute([$id_negocio, $inicio, $hoy]);
$ventasData = $stmt->fetch();

// 2. Costo total (suma de precio_compra * cantidad en ventas_detalle)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(vd.cantidad * p.precio_compra), 0) as costo
    FROM ventas v
    JOIN ventas_detalle vd ON v.id_venta = vd.id_venta
    JOIN productos p ON vd.id_producto = p.id_producto
    WHERE v.id_negocio = ? AND v.fecha BETWEEN ? AND ? 
      AND v.metodo_pago IN ('efectivo', 'transferencia')
");
$stmt->execute([$id_negocio, $inicio, $hoy]);
$costo = (float)$stmt->fetchColumn();

// 3. Mermas (usando precio_compra como costo de pérdida)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(vd.cantidad * p.precio_compra), 0) as mermas
    FROM ventas v
    JOIN ventas_detalle vd ON v.id_venta = vd.id_venta
    JOIN productos p ON vd.id_producto = p.id_producto
    WHERE v.id_negocio = ? AND v.fecha BETWEEN ? AND ? 
      AND v.metodo_pago = 'merma'
");
$stmt->execute([$id_negocio, $inicio, $hoy]);
$mermas = (float)$stmt->fetchColumn();

// 4. Ventas por día (para tabla)
$filtroTabla = ($periodo === 'dia') ? "AND fecha = '$hoy'" : "AND fecha BETWEEN '$inicio' AND '$hoy'";
$stmt = $pdo->prepare("
    SELECT fecha, 
           SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END) as efectivo,
           SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END) as tarjeta,
           SUM(total) as total
    FROM ventas 
    WHERE id_negocio = ? AND metodo_pago IN ('efectivo', 'transferencia') $filtroTabla
    GROUP BY fecha
    ORDER BY fecha DESC
");
$stmt->execute([$id_negocio]);
$ventasPorDia = $stmt->fetchAll();

echo json_encode([
    'periodo' => $periodo,
    'ventas' => (float)$ventasData['ventas'],
    'costo' => $costo,
    'mermas' => $mermas,
    'saldo' => (float)$ventasData['ventas'] - $costo - $mermas,
    'efectivo' => (float)$ventasData['efectivo'],
    'tarjeta' => (float)$ventasData['tarjeta'],
    'ventas_por_dia' => $ventasPorDia
]);
?>