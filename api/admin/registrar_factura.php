<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("
        INSERT INTO facturas (id_negocio, nro_factura, proveedor, fecha, monto, estado, glosa)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['id_negocio'],
        $data['nro_factura'],
        $data['proveedor'],
        $data['fecha'],
        $data['monto'],
        $data['estado'],
        $data['glosa']
    ]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error factura: " . $e->getMessage());
    echo json_encode(['success' => false]);
}
?>