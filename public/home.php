<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
$rol = $_SESSION['rol'] ?? 'cajera';
$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Mi Negocio';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🏡 Home — <?= htmlspecialchars($nombre_negocio) ?></title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
      margin: 0; padding: 0;
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
    }
    .header {
      background: #2E7D32;
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .main {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    .welcome {
      font-size: 1.4rem;
      margin-bottom: 2rem;
      color: #1B5E20;
    }
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }
    .card {
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      text-align: center;
    }
    .card h3 {
      color: #2E7D32;
      margin-top: 0;
    }
    .logout {
      color: #FF5252;
      text-decoration: none;
      font-weight: bold;
    }
    .logout:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="header">
    <h2>🥦 NegocioUP</h2>
    <a href="../api/logout.php" class="logout">Salir</a>
  </div>

  <div class="main">
    <div class="welcome">
      ¡Hola, <?= htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario') ?>!<br>
      Bienvenido a <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
    </div>

    <div class="cards">
      <?php if ($rol === 'admin'): ?>
        <div class="card">
          <h3>📊 Reportes</h3>
          <p>Ventas, stock, proveedores</p>
        </div>
        <div class="card">
          <h3>📦 Productos</h3>
          <p>Administra tu catálogo</p>
        </div>
        <div class="card">
          <h3>👥 Usuarios</h3>
          <p>Gestiona cajeras</p>
        </div>
        <div style="margin: 2rem; padding: 1.5rem; background: #e8f5e9; border-radius: 12px; text-align: center;">
          <h2>⚙️ Panel de Administración</h2>
          <p>Gestiona tu catálogo de productos</p>
          <a href="../pages/admin/productos.php" 
            style="display: inline-block; padding: 0.75rem 1.5rem; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
            🥦 Mantenedor de Productos
          </a>
        </div>
      <?php endif; ?>

      <div class="card">
        <h3>🛒 Punto de Venta</h3>
        <p>Registra tus ventas diarias</p>
      </div>

      <div class="card">
        <h3>📈 Stock</h3>
        <p>Control en tiempo real</p>
      </div>
    </div>
  </div>
</body>
</html>