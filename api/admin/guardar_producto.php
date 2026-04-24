<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_negocio'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) throw new Exception("Datos inválidos");

    $id_negocio = $_SESSION['id_negocio'];
    $isUpdate = !empty($data['id_producto']);

    // === REDONDEOS CORRECTOS ===
    $precio_compra = round(floatval($data['precio_compra'] ?? 0), 0);
    $porc_utilidad = round(floatval($data['porc_utilidad'] ?? 0), 0); // 1 decimal para %
    $stock_actual = round(floatval($data['stock_actual'] ?? 0), 0);
    $stock_critico = round(floatval($data['stock_critico'] ?? 10), 0); // default 10 si no se envía
    
    // precio_venta es GENERATED, pero si lo enviamos, lo redondeamos
    $precio_venta = isset($data['precio_venta']) ? round(floatval($data['precio_venta']), 2) : null;

    if ($isUpdate) {
        // UPDATE: precio_venta se ignora si es GENERATED, se recalcula automáticamente
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET tipo = ?, familia = ?, subfamilia = ?, unidad_medida = ?, 
                precio_compra = ?, porc_utilidad = ?, 
                stock_actual = ?, stock_critico = ?
            WHERE id_producto = ? AND id_negocio = ?
        ");
        $stmt->execute([
            $data['tipo'], $data['familia'], $data['subfamilia'], $data['unidad_medida'],
            $precio_compra, $porc_utilidad,
            $stock_actual, $stock_critico,
            $data['id_producto'], $id_negocio
        ]);
    } else {
        // INSERT: precio_venta se genera automáticamente por la BD
        $codigo = strtoupper(substr($data['tipo'] ?? 'GEN', 0, 3)) . '-' . 
                  strtoupper(str_replace(' ', '', $data['familia'] ?? 'OTROS')) . '-' . 
                  strtoupper(str_replace(' ', '', $data['subfamilia'] ?? 'GEN')) . '-' . 
                  uniqid();

        $stmt = $pdo->prepare("
            INSERT INTO productos (
                codigo, tipo, id_negocio, familia, subfamilia, 
                unidad_medida, precio_compra, porc_utilidad, 
                stock_actual, stock_critico
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $codigo, $data['tipo'], $id_negocio,
            $data['familia'], $data['subfamilia'], $data['unidad_medida'],
            $precio_compra, $porc_utilidad,
            $stock_actual, $stock_critico
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Guardado correctamente']);

} catch (Exception $e) {
    error_log("Error en guardar_producto.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>