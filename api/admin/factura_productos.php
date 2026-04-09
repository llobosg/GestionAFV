<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';

$id = $_GET['id_factura'] ?? null;

if (!$id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_producto, nombre, cantidad, precio
    FROM factura_productos
    WHERE id_factura = ?
");

$stmt->execute([$id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));