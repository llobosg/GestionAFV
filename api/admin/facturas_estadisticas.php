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

// === BASE WHERE (SIN ESTADO) ===
$whereBase = "id_negocio = ? AND fecha BETWEEN ? AND ?";
$paramsBase = [$id_negocio, $inicio, $hoy];

// === STATS POR ESTADO (SIN DUPLICAR FILTRO) ===
$estados = ['pendiente', 'pagada', 'anulada'];
$stats = [];

foreach ($estados as $e) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(monto), 0)
        FROM facturas
        WHERE $whereBase AND estado = ?
    ");
    $stmt->execute(array_merge($paramsBase, [$e]));
    $stats[$e] = ['monto' => (float)$stmt->fetchColumn()];
}

// === TOTALES ===
$whereFinal = $whereBase;
$paramsFinal = $paramsBase;

if ($estado) {
    $whereFinal .= " AND estado = ?";
    $paramsFinal[] = $estado;
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) as qty, COALESCE(SUM(monto),0) as total
    FROM facturas
    WHERE $whereFinal
");
$stmt->execute($paramsFinal);
$row = $stmt->fetch();

$total_qty = (int)$row['qty'];
$total_monto = (float)$row['total'];
$total_iva = $total_monto * 0.19;

// === MENSUAL ===
$mensual = [];
for ($i = 11; $i >= 0; $i--) {
    $fecha = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(monto),0)
        FROM facturas
        WHERE id_negocio = ? AND DATE_FORMAT(fecha,'%Y-%m') = ?
    ");
    $stmt->execute([$id_negocio, $fecha]);
    $valor = (float)$stmt->fetchColumn();
    $mensual[] = [
        'valor' => $valor,
        'iva' => $valor * 0.19
    ];
}

// === FACTURAS ===
$stmt = $pdo->prepare("
    SELECT *
    FROM facturas
    WHERE $whereFinal
    ORDER BY fecha DESC
");
$stmt->execute($paramsFinal);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === RESPUESTA SEGURA ===
echo json_encode([
    'pendiente' => $stats['pendiente'],
    'pagada' => $stats['pagada'],
    'anulada' => $stats['anulada'],
    'total_qty' => $total_qty,
    'total_monto' => $total_monto,
    'total_iva' => $total_iva,
    'mensual' => $mensual,
    'facturas' => $facturas
]);
?>