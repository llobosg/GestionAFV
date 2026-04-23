<?php
header('Content-Type: application/json');
// Asegúrate que la ruta a config.php sea correcta según tu estructura
require_once __DIR__ . '/../../includes/config.php'; 

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación básica de sesión (Ajusta según tu variable de sesión real, ej: $_SESSION['rol'] o $_SESSION['id_negocio'])
if (!isset($_SESSION['id_negocio'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

try {
    // Obtener datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception("Datos inválidos o vacíos");
    }

    $id_negocio = $_SESSION['id_negocio'];
    $isUpdate = !empty($data['id_producto']);

    if ($isUpdate) {
        // === ACTUALIZAR PRODUCTO EXISTENTE ===
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
                stock_critico = ?
            WHERE id_producto = ? AND id_negocio = ?
        ");
        
        $stmt->execute([
            $data['tipo'],
            $data['familia'],
            $data['subfamilia'],
            $data['unidad_medida'],
            $data['precio_compra'],
            $data['porc_utilidad'],
            $data['stock_actual'] ?? 0,
            $data['stock_critico'] ?? 10,
            $data['id_producto'],
            $id_negocio
        ]);

    } else {
        // === CREAR NUEVO PRODUCTO ===
        
        // Generar código único
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
            $data['stock_actual'] ?? 0,
            $data['stock_critico'] ?? 10
        ]);

        // OBTENER EL ID DEL NUEVO PRODUCTO
        $id_producto = $pdo->lastInsertId();

        // === REGISTRO DE INGRESO DE STOCK (Opcional) ===
        // Solo ejecutamos esto si el frontend envía 'cantidad'. 
        // Como tu formulario actual NO tiene campo cantidad/factura, esto probablemente se saltará
        // a menos que agregues esos campos al form HTML.
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

            // Actualizar stock del producto con la cantidad ingresada
            $stmtUpdateStock = $pdo->prepare("
                UPDATE productos 
                SET stock_actual = stock_actual + ?
                WHERE id_producto = ?
            ");
            $stmtUpdateStock->execute([$data['cantidad'], $id_producto]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Guardado correctamente']);

} catch (Exception $e) {
    // Log del error para Railway/Backend
    error_log("Error en guardar_producto.php: " . $e->getMessage());
    // Respuesta JSON de error para el Frontend
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>