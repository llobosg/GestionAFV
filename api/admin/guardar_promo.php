<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
session_start();

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_promo = (int)$input['id_promo'];
$nombre = trim($input['nombre']);
$precio_promo = (float)$input['precio_promo'];
$activo = (bool)$input['activo'];

$stmt = $pdo->prepare("
    UPDATE productos_promo 
    SET nombre = ?, precio_promo = ?, activo = ?
    WHERE id_promo = ? AND EXISTS (
        SELECT 1 FROM productos p 
        JOIN productos_promo pp ON p.id_producto = pp.id_producto_base
        WHERE pp.id_promo = ? AND p.id_negocio = ?
    )
");
$stmt->execute([$nombre, $precio_promo, $activo, $id_promo, $id_promo, $_SESSION['id_negocio']]);

echo json_encode(['success' => true]);
?>