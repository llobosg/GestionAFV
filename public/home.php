<?php
require_once __DIR__ . '/../includes/config.php';

// Proteger acceso sin login
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

// Asignar valores seguros (con fallback)
$nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$apellido = $_SESSION['apellido_usuario'] ?? '';
$rol = $_SESSION['rol'] ?? 'cajera';
$id_negocio = $_SESSION['id_negocio'] ?? 1;
$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';

// Nombre completo seguro
$nombre_completo = trim("{$nombre} {$apellido}") ?: $nombre;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Home — Gestión AFV</title>
  <style>
    body { 
      font-family: sans-serif; 
      background: #f9fbe7; 
      padding: 2rem; 
    }
    .header { 
      background: #4CAF50; 
      color: white; 
      padding: 1rem; 
      border-radius: 8px; 
      margin-bottom: 2rem; 
    }
    .module { 
      margin: 2rem; 
      padding: 1.5rem; 
      border-radius: 12px; 
      text-align: center; 
    }
    .admin { background: #e8f5e9; }
    .cajera { background: #e3f2fd; }
    .btn { 
      display: inline-block; 
      padding: 0.75rem 1.5rem; 
      border-radius: 8px; 
      font-weight: bold; 
      text-decoration: none; 
    }
    .btn-admin { background: #4CAF50; color: white; }
    .btn-pos { background: #2196F3; color: white; }
  </style>
</head>
<body>
  <div class="header">
    <h1>Home 🏠</h1>
    <p>Bienvenido/a, <strong><?= htmlspecialchars($nombre_completo) ?></strong></p>
    <p>Negocio: <strong><?= htmlspecialchars($nombre_negocio) ?></strong> | Rol: <?= htmlspecialchars(ucfirst($rol)) ?></p>
  </div>

  <?php if ($rol === 'admin'): ?>
    <div class="module admin">
      <h2>⚙️ Panel de Administración</h2>
      <a href="../pages/admin/cierre_caja.php" class="btn btn-admin" style="background:#9C27B0; margin-top:0.5rem;">💰 Cierre de Caja</a>
      <a href="../pages/admin/control_stock.php" class="btn btn-admin" style="background:#FF9800; margin-top:0.5rem;">📦 Control de Stock</a>
      <a href="../pages/admin/productos.php" class="btn btn-admin">🥦 Mantenedor de Productos</a>
      <a href="../pages/admin/registrar_merma.php" class="btn btn-admin" style="background:#F44336; margin-top:0.5rem;">📉 Registrar Merma</a>
      <a href="../pages/admin/registrar_factura.php" class="btn btn-admin" style="background:#2196F3; margin-top:0.5rem;">🧾 Registrar Factura</a>
    </div>
  <?php endif; ?>

  <?php if ($rol === 'cajera' || $rol === 'admin'): ?>
    <div class="module cajera">
      <h2>🛒 Punto de Venta</h2>
      <p>Registra ventas diarias</p>
      <a href="../pages/cajera/pos.php" class="btn btn-pos">💳 Abrir POS</a>
      
      <!-- NUEVO BOTÓN -->
      <a href="../pages/cajera/buscar_venta.php" class="btn btn-pos" style="background:#FF9800; margin-top:0.8rem;">
        🔍 Buscar Venta (Devoluciones)
      </a>
    </div>
  <?php endif; ?>

</body>
</html>