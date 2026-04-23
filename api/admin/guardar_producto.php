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

    if (!$data) {
        throw new Exception("Datos inválidos");
    }

    $id_negocio = $_SESSION['id_negocio'];
    $isUpdate = !empty($data['id_producto']);

    // Asegurar valores por defecto
    $stock_actual = isset($data['stock_actual']) ? floatval($data['stock_actual']) : 0;
    $stock_critico = isset($data['stock_critico']) ? floatval($data['stock_critico']) : 10; // Default 10 si no viene

    if ($isUpdate) {
        // === ACTUALIZAR ===
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET 
                tipo = ?, 
                familia = ?, 
                subfamilia = ?, 
                unidad_medida = ?, 
                precio_compra = ?, 
                porc_utilidad = ?, 
                stock_actual = ?, 
                stock_critico = ?  -- <--- AGREGADO
            WHERE id_producto = ? AND id_negocio = ?
        ");
        
        $stmt->execute([
            $data['tipo'],
            $data['familia'],
            $data['subfamilia'],
            $data['unidad_medida'],
            $data['precio_compra'],
            $data['porc_utilidad'],
            $stock_actual,
            $stock_critico,
            $data['id_producto'],
            $id_negocio
        ]);

    } else {
        // === INSERTAR ===
        $tipoCode = strtoupper(substr($data['tipo'] ?? 'GEN', 0, 3));
        $famCode = strtoupper(str_replace(' ', '', $data['familia'] ?? 'OTROS'));
        $subCode = strtoupper(str_replace(' ', '', $data['subfamilia'] ?? 'GEN'));
        $codigo = $tipoCode . '-' . $famCode . '-' . $subCode . '-' . uniqid();

        $stmt = $pdo->prepare("
            INSERT INTO productos (
                codigo, tipo, id_negocio, familia, subfamilia, 
                unidad_medida, precio_compra, porc_utilidad, 
                stock_actual, stock_critico
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $codigo,
            $data['tipo'],
            $id_negocio,
            $data['familia'],
            $data['subfamilia'],
            $data['unidad_medida'],
            $data['precio_compra'],
            $data['porc_utilidad'],
            $stock_actual,
            $stock_critico // <--- AGREGADO
        ]);

        $id_producto = $pdo->lastInsertId();

        // Si hay cantidad inicial, registrar ingreso
        if (isset($data['cantidad']) && $data['cantidad'] > 0) {
            $stmtIngreso = $pdo->prepare("
                INSERT INTO ingresos_stock (
                    id_producto, id_negocio, cantidad, precio_compra_unitario,
                    nro_factura, proveedor, fecha_factura, monto_factura, 
                    estado_factura, fechapago_factura
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmtIngreso->execute([
                $id_producto,
                $id_negocio,
                $data['cantidad'],
                $data['precio_compra'],
                $data['nro_factura'] ?? null,
                $data['proveedor'] ?? null,
                $data['fecha_factura'] ?? null,
                $data['monto_factura'] ?? null,
                $data['estado_factura'] ?? 'pendiente',
                $data['fecha_pago'] ?? null
            ]);

            // Actualizar stock
            $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id_producto = ?")
                ->execute([$data['cantidad'], $id_producto]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Guardado correctamente']);

} catch (Exception $e) {
    error_log("Error en guardar_producto.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>