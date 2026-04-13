<?php
header('Content-Type: application/json');
// Ajusta la ruta si es necesario dependiendo de tu estructura de carpetas
require_once __DIR__ . '/../../includes/config.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validación de roles
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] !== 'cajera' && $_SESSION['rol'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    // 1. LEER DATOS DEL FRONTEND
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    if (!$data || !isset($data['detalles'])) {
        throw new Exception("Datos de venta inválidos");
    }

    $detalles = $data['detalles'];
    $id_negocio = (int)$data['id_negocio'];
    $id_cajera = (int)$_SESSION['id_usuario']; 
    $total_venta = (float)$data['total'];
    $metodo_pago = $data['metodo_pago'];

    $errors = [];
    $items_validados = [];

    // 2. VALIDAR STOCK Y MAPEAR IDS (PRE-TRANSACCIÓN)
    foreach ($detalles as $item) {
        $id_producto_recibido = (int)$item['id_producto'];
        $cantidad = (float)$item['cantidad'];
        
        //  LÓGICA DE MAPEO DE IDS (Promo -> Real)
        $id_producto_real = $id_producto_recibido;
        
        if ($id_producto_recibido > 10000) {
            $id_promo_real = $id_producto_recibido - 10000;
            
            $stmt_base = $pdo->prepare("SELECT id_producto_base FROM productos_promo WHERE id_promo = ?");
            $stmt_base->execute([$id_promo_real]);
            $base_data = $stmt_base->fetch(PDO::FETCH_ASSOC);
            
            if ($base_data) {
                $id_producto_real = (int)$base_data['id_producto_base'];
            } else {
                $errors[] = "Promoción inválida (ID Promo: $id_promo_real)";
                continue;
            }
        }

        // Verificar existencia y stock
        $stmt_stock = $pdo->prepare("SELECT stock_actual, producto FROM productos WHERE id_producto = ? AND id_negocio = ?");
        $stmt_stock->execute([$id_producto_real, $id_negocio]);
        $prod_data = $stmt_stock->fetch(PDO::FETCH_ASSOC);

        if (!$prod_data) {
            $errors[] = "Producto no encontrado (ID Real: $id_producto_real)";
            continue;
        }

        if ($cantidad > $prod_data['stock_actual']) {
            $errors[] = "Stock insuficiente para '{$prod_data['producto']}' (Solicitado: $cantidad, Disponible: {$prod_data['stock_actual']})";
            continue;
        }

        // Guardar item validado para procesar después
        $items_validados[] = [
            'id_producto_real' => $id_producto_real,
            'cantidad' => $cantidad,
            'precio_unitario' => (float)$item['precio_unitario'],
            'subtotal' => (float)$item['subtotal']
        ];
    }

    // Si hay errores, detenemos aquí y no tocamos la BD
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
        exit;
    }

    // 3. INICIAR TRANSACCIÓN Y PROCESAR VENTA
    $pdo->beginTransaction();

    // Insertar Cabecera de Venta
    $stmt_venta = $pdo->prepare("
        INSERT INTO ventas (id_negocio, id_cajera, fecha, hora, total, metodo_pago)
        VALUES (?, ?, CURDATE(), CURTIME(), ?, ?)
    ");
    $stmt_venta->execute([$id_negocio, $id_cajera, $total_venta, $metodo_pago]);
    $id_venta = $pdo->lastInsertId();

    // Insertar Detalles en tabla 'ventas_detalle' y Descontar Stock
    $stmt_detalle = $pdo->prepare("
        INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_unitario, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt_update_stock = $pdo->prepare("
        UPDATE productos SET stock_actual = stock_actual - ? WHERE id_producto = ?
    ");

    foreach ($items_validados as $item) {
        // Insertar detalle
        $stmt_detalle->execute([
            $id_venta,
            $item['id_producto_real'],
            $item['cantidad'],
            $item['precio_unitario'],
            $item['subtotal']
        ]);

        // Descontar stock
        $stmt_update_stock->execute([
            $item['cantidad'],
            $item['id_producto_real']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'id_venta' => $id_venta,
        'message' => 'Venta registrada correctamente'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en venta: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar la venta: ' . $e->getMessage()]);
}
?>