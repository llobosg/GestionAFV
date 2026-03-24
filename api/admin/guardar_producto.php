<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    die();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['id_producto'])) {
    $stmt = $pdo->prepare("
        UPDATE productos 
        SET codigo = ?, tipo = ?, familia = ?, subfamilia = ?, unidad_medida = ?, precio_compra = ?, porc_utilidad = ?
        WHERE id_producto = ? AND id_negocio = ?
    ");
    $stmt->execute([
        $data['codigo'],
        $data['tipo'],
        $data['familia'],
        $data['subfamilia'],
        $data['unidad_medida'],
        $data['precio_compra'],
        $data['porc_utilidad'],
        $data['id_producto'],
        $_SESSION['id_negocio']
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO productos (codigo, tipo, id_negocio, familia, subfamilia, unidad_medida, precio_compra, porc_utilidad)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['codigo'],
        $data['tipo'],
        $_SESSION['id_negocio'],
        $data['familia'],
        $data['subfamilia'],
        $data['unidad_medida'],
        $data['precio_compra'],
        $data['porc_utilidad']
    ]);
}

echo json_encode(['success' => true]);
?>