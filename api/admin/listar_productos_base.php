<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit;
}

$stmt = $pdo->prepare("SELECT id_producto, producto FROM productos WHERE id_negocio = ? AND activo = 1");
$stmt->execute([$_SESSION['id_negocio']]);
echo json_encode($stmt->fetchAll());
?>