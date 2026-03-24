<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    die();
}

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id_negocio = ? ORDER BY producto");
$stmt->execute([$_SESSION['id_negocio']]);
echo json_encode($stmt->fetchAll());
?>