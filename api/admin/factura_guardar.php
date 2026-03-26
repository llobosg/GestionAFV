<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_factura'] ?? null;
$proveedor = $data['proveedor'] ?? '';
$monto = $data['monto'] ?? 0;
$estado = $data['estado'] ?? '';
$glosa = $data['glosa'] ?? '';
$productos = $data['productos'] ?? [];

if (!$id || !$proveedor || !$estado) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

try {

    $pdo->beginTransaction();

    /* ACTUALIZAR FACTURA */
    $stmt = $pdo->prepare("
        UPDATE facturas
        SET proveedor = ?, monto = ?, estado = ?, glosa = ?
        WHERE id_factura = ?
    ");

    $stmt->execute([$proveedor, $monto, $estado, $glosa, $id]);

    /* ELIMINAR PRODUCTOS ANTERIORES */
    $pdo->prepare("DELETE FROM factura_productos WHERE id_factura = ?")
        ->execute([$id]);

    /* INSERTAR PRODUCTOS */
    $stmtProd = $pdo->prepare("
        INSERT INTO factura_productos (id_factura, nombre, cantidad, precio)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($productos as $p) {
        $stmtProd->execute([
            $id,
            $p['nombre'],
            $p['cantidad'],
            $p['precio']
        ]);
    }

    $pdo->commit();

    echo json_encode(["status" => "ok"]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}