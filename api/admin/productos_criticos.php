<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit;
}
    
$stmt = $pdo->prepare("
    SELECT producto, tipo, stock_actual, stock_critico 
    FROM productos 
    WHERE id_negocio = ? AND stock_actual <= stock_critico 
    ORDER BY stock_actual ASC
");
$stmt->execute([$_SESSION['id_negocio']]);
echo json_encode($stmt->fetchAll());
?>