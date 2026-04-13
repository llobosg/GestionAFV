<?php
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/session.php';

    // Permitir acceso a cajeras y admins
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['cajera', 'admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    $id_negocio = $_SESSION['id_negocio'] ?? 1;

    try {
        // Obtener productos normales
    $sql_normal = "
        SELECT 
            id_producto, 
            producto, 
            precio_venta, 
            stock_actual, 
            'normal' as tipo_registro,
            NULL as cantidad_unidades,
            id_producto as id_base_referencia
        FROM productos 
        WHERE id_negocio = ? AND activo = 1
    ";

    // Obtener productos en promoción
    $sql_promo = "
        SELECT 
            (10000 + pp.id_promo) as id_producto, -- ⚠️ CLAVE: Sumamos 10000 para hacer el ID único
            pp.nombre as producto, 
            pp.precio_promo as precio_venta, 
            p.stock_actual, 
            'promo' as tipo_registro,
            pp.cantidad_unidades,
            pp.id_producto_base as id_base_referencia
        FROM productos_promo pp
        JOIN productos p ON pp.id_producto_base = p.id_producto
        WHERE p.id_negocio = ? AND pp.activo = 1
    ";

    // Ejecutar consultas (asumiendo que usas PDO)
    $stmt_normal = $pdo->prepare($sql_normal);
    $stmt_normal->execute([$id_negocio]);
    $normales = $stmt_normal->fetchAll(PDO::FETCH_ASSOC);

    $stmt_promo = $pdo->prepare($sql_promo);
    $stmt_promo->execute([$id_negocio]);
    $promos = $stmt_promo->fetchAll(PDO::FETCH_ASSOC);

    // Unir en un solo array
    $productosCache = array_merge($normales, $promos);

    // Enviar al frontend como JSON
    header('Content-Type: application/json');
        echo json_encode($productosCache);
    exit;

    } catch (Exception $e) {
        error_log("Error al listar productos: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error interno']);
    }
?>