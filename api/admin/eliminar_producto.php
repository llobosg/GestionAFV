<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    die();
}

$data = json_decode(file_get_contents('php://input'), true);
$pdo->prepare("DELETE FROM productos WHERE id_producto = ? AND id_negocio = ?")
    ->execute([$data['id_producto'], $_SESSION['id_negocio']]);

echo json_encode(['success' => true]);
?>