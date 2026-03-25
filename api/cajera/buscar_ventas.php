<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if (!in_array($_SESSION['rol'], ['cajera', 'admin'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$id_venta = $_GET['id_venta'] ?? null;
$total = $_GET['total'] ?? null;

$sql = "SELECT * FROM ventas WHERE id_negocio = ? AND fecha BETWEEN ? AND ?";
$params = [$_SESSION['id_negocio'], $desde, $hasta];

if ($id_venta) {
    $sql .= " AND id_venta = ?";
    $params[] = $id_venta;
}
if ($total) {
    $sql .= " AND total = ?";
    $params[] = $total;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll());
?>