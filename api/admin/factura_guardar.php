<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_factura'] ?? null;
$proveedor = $data['proveedor'] ?? '';
$monto = $data['monto'] ?? 0;
$estado = $data['estado'] ?? '';
$glosa = $data['glosa'] ?? '';

if (!$id || !$proveedor || !$estado) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE facturas
        SET proveedor = ?, monto = ?, estado = ?, glosa = ?
        WHERE id_factura = ?
    ");

    $stmt->execute([$proveedor, $monto, $estado, $glosa, $id]);

    echo json_encode(["status" => "ok"]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}