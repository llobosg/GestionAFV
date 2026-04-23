<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

// Validar sesión y rol
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /public/login.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio']; // <--- OBTENER ID DEL NEGOCIO

// Obtener lista de productos
$stmt = $pdo->prepare("SELECT id_producto, producto FROM productos WHERE activo = 1 AND id_negocio = ? ORDER BY producto");
$stmt->execute([$id_negocio]);
$productos = $stmt->fetchAll();

$message = '';

if ($_POST) {
    try {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $id_producto_base = (int)($_POST['id_producto_base'] ?? 0);
        $cantidad_unidades = (int)($_POST['cantidad_unidades'] ?? 0);
        $precio_promo = (float)($_POST['precio_promo'] ?? 0);

        if (!$nombre || !$id_producto_base || $cantidad_unidades < 2 || $precio_promo <= 0) {
            throw new Exception('Todos los campos son obligatorios y válidos.');
        }

        // Verificar que el producto base exista Y pertenezca a este negocio
        $stmt_check = $pdo->prepare("SELECT 1 FROM productos WHERE id_producto = ? AND id_negocio = ?");
        $stmt_check->execute([$id_producto_base, $id_negocio]);
        if (!$stmt_check->fetch()) {
            throw new Exception('Producto base no válido o no pertenece a tu negocio.');
        }

        // INSERT con id_negocio
        $stmt_ins = $pdo->prepare("
            INSERT INTO productos_promo (id_negocio, nombre, descripcion, id_producto_base, cantidad_unidades, precio_promo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt_ins->execute([$id_negocio, $nombre, $descripcion, $id_producto_base, $cantidad_unidades, $precio_promo]);

        $message = '✅ Promoción creada con éxito.';
    } catch (Exception $e) {
        $message = '❌ ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>➕ Crear Producto Promocional — NegocioUP</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; background: #f9f9f9; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #2E7D32; margin-top: 0; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; margin-bottom: 0.4rem; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 0.6rem; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; }
        button { background: #4CAF50; color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #388E3C; }
        .alert { padding: 0.8rem; margin-bottom: 1.2rem; border-radius: 6px; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
        a.back { display: inline-block; margin-top: 1rem; color: #2196F3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Crear Producto Promocional</h2>

        <?php if ($message): ?>
            <div class="alert <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nombre de la promoción *</label>
                <input type="text" name="nombre" placeholder="Ej: Pepino 2x$1000" required>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" rows="2" placeholder="Opcional: detalles de la promo"></textarea>
            </div>

            <div class="form-group">
                <label>Producto base *</label>
                <select name="id_producto_base" required>
                    <option value="">Seleccionar producto...</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id_producto'] ?>"><?= htmlspecialchars($p['producto']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cantidad en promoción *</label>
                <input type="number" name="cantidad_unidades" min="2" value="2" required>
                <small>Ej: 2 para "2x", 3 para "3x", etc.</small>
            </div>

            <div class="form-group">
                <label>Precio promocional ($)*</label>
                <input type="number" step="0.01" name="precio_promo" min="0.01" required>
            </div>

            <button type="submit">Crear Promoción</button>
        </form>

        <a href="/pages/admin/productos.php" class="back">← Volver a Productos</a>
    </div>
</body>
</html>