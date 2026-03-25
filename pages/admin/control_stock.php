<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;
$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';
$nombre = $_SESSION['nombre_usuario'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';

// En control_stock.php
const $email = "<?= $_SESSION['email'] ?? '' ?>";
if (!$email) {
    alert("❌ No se encontró el correo del administrador. Contacte al soporte.");
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>📦 Control de Stock — NegocioUP</title>
  <style>
    body { background: #f9fbe7; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; }
    .top-bar {
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      color: white;
      padding: 0.8rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    .alerta {
      background: #ffebee;
      color: #C62828;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
    th, td { padding: 0.7rem; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #4CAF50; color: white; }
    .btn-enviar {
      padding: 0.7rem 1.5rem;
      background: #FF9800;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      font-size: 1rem;
      margin-top: 1rem;
    }
  </style>
</head>
<body>

  <div class="top-bar">
    <a href="/public/home.php" style="color:white; text-decoration:none;">← Home</a>
    <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
    <span><?= htmlspecialchars($nombre) ?></span>
  </div>

  <div class="container">
    <h2>📦 Productos por Debajo del Stock Crítico</h2>
    
    <div class="alerta">
      ⚠️ Estos productos necesitan reposición inmediata
    </div>

    <table id="tabla-reposicion">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Tipo</th>
          <th>Stock Actual</th>
          <th>Stock Crítico</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <button class="btn-enviar" onclick="enviarPedido()">📤 Enviar Pedido por Correo</button>
  </div>

  <script>
    async function cargarProductosCriticos() {
      const res = await fetch('/api/admin/productos_criticos.php');
      const productos = await res.json();
      const tbody = document.querySelector('#tabla-reposicion tbody');
      tbody.innerHTML = productos.map(p => `
        <tr>
          <td>${p.producto}</td>
          <td>${p.tipo}</td>
          <td>${parseFloat(p.stock_actual).toFixed(2)}</td>
          <td>${parseFloat(p.stock_critico).toFixed(2)}</td>
        </tr>
      `).join('');
    }

    async function enviarPedido() {
      if (!confirm('¿Enviar listado de reposición por correo?')) return;
      
      const res = await fetch('/api/admin/enviar_pedido_reposicion.php');
      const result = await res.json();
      alert(result.success ? '✅ Correo enviado' : '❌ Error al enviar');
    }

    document.addEventListener('DOMContentLoaded', cargarProductosCriticos);
  </script>
</body>
</html>