<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

// Permitir acceso a cajeras y admins
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['cajera', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id_negocio = ? ORDER BY producto");
$stmt->execute([$id_negocio]);
echo json_encode($stmt->fetchAll());
?>