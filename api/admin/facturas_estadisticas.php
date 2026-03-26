<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$id_negocio = $_SESSION['id_negocio'];
$estado = $_GET['estado'] ?? null;
$periodo = $_GET['periodo'] ?? 'hoy';
$mes = $_GET['mes'] ?? null;

// Rango de fechas
$hoy = date('Y-m-d');
switch ($periodo) {
    case 'semana': $inicio = date('Y-m-d', strtotime('-6 days')); break;
    case 'mes': $inicio = date('Y-m-01'); break;
    case 'ytd': $inicio = date('Y-01-01'); break;
    case 'meses': 
        $anio = date('Y');
        $inicio = "$anio-$mes-01";
        $hoy = date('Y-m-t', strtotime($inicio));
        break;
    default: $inicio = $hoy;
}

// Condición base
$where = "id_negocio = ?";
$params = [$id_negocio];
if ($estado) { $where .= " AND estado = ?"; $params[] = $estado; }
$where .= " AND fecha BETWEEN ? AND ?";
$params[] = $inicio;
$params[] = $hoy;

// Estadísticas por estado
$estados = ['pendiente', 'pagada', 'anulada'];
$stats = [];
foreach ($estados as $e) {
    $sql = "SELECT COALESCE(SUM(monto), 0) as monto FROM facturas WHERE $where AND estado = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$e]));
    $stats[$e] = ['monto' => (float)$stmt->fetchColumn()];
}

// Totales
$stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(monto), 0) FROM facturas WHERE $where");
$stmt->execute($params);
[$total_qty, $total_monto] = $stmt->fetch();
$total_iva = $total_monto * 0.19;

// Datos mensuales (últimos 12 meses)
$mensual = [];
for ($i = 11; $i >= 0; $i--) {
    $fecha = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(monto), 0) 
        FROM facturas 
        WHERE id_negocio = ? AND DATE_FORMAT(fecha, '%Y-%m') = ?
    ");
    $stmt->execute([$id_negocio, $fecha]);
    $valor = (float)$stmt->fetchColumn();
    $mensual[] = ['valor' => $valor, 'iva' => $valor * 0.19];
}

// Facturas filtradas
$stmt = $pdo->prepare("SELECT * FROM facturas WHERE $where ORDER BY fecha DESC");
$stmt->execute($params);
$facturas = $stmt->fetchAll();

echo json_encode([
    'pendiente' => $stats['pendiente'],
    'pagada' => $stats['pagada'],
    'anulada' => $stats['anulada'],
    'total_qty' => (int)$total_qty,
    'total_monto' => $total_monto,
    'total_iva' => $total_iva,
    'mensual' => $mensual,
    'facturas' => $facturas
]);
?>