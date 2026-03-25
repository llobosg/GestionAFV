<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'cajera' && $_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;
$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';
$nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$apellido = $_SESSION['apellido_usuario'] ?? '';
$nombre_completo = trim("{$nombre} {$apellido}") ?: $nombre;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>🔍 Buscar Venta — Gestión AFV</title>
  <style>
    body {
      background: #f9fbe7;
      font-family: 'Segoe UI', sans-serif;
      margin: 0; padding: 0;
    }
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
    .search-box {
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    .form-row {
      display: flex;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .form-group {
      flex: 1;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.3rem;
      font-weight: bold;
    }
    .form-control {
      width: 100%;
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    .btn {
      padding: 0.6rem 1.2rem;
      background: #4CAF50;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
    th, td { padding: 0.6rem; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #4CAF50; color: white; }
    .acciones button {
      background: #f44336;
      color: white;
      border: none;
      padding: 0.3rem 0.6rem;
      border-radius: 4px;
      cursor: pointer;
    }
    .toast-container {
      position: fixed; top: 20px; right: 20px; z-index: 1000;
    }
    .toast {
      background: #4CAF50; color: white; padding: 1rem; border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-bottom: 0.75rem;
      min-width: 280px; opacity: 0; transform: translateX(100%);
      transition: all 0.3s ease;
    }
    .toast.show { opacity: 1; transform: translateX(0); }
    .toast.error { background: #F44336; }
  </style>
</head>
<body>

  <div class="top-bar">
    <a href="/public/home.php" style="color:white; text-decoration:none;">← Home</a>
    <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
    <span><?= htmlspecialchars($nombre_completo) ?></span>
  </div>

  <div class="container">
    <h2>🔍 Buscar Venta para Devolución</h2>

    <div class="search-box">
      <div class="form-row">
        <div class="form-group">
          <label>Fecha (desde)</label>
          <input type="date" id="fecha-desde" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Fecha (hasta)</label>
          <input type="date" id="fecha-hasta" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>ID Venta (opcional)</label>
          <input type="number" id="id-venta" class="form-control" placeholder="Ej: 123">
        </div>
        <div class="form-group">
          <label>Total (opcional)</label>
          <input type="number" step="0.01" id="total-venta" class="form-control" placeholder="Ej: 5000.50">
        </div>
      </div>
      <button class="btn" onclick="buscarVentas()">Buscar Ventas</button>
    </div>

    <div id="resultados"></div>
  </div>

  <div class="toast-container" id="toast-container"></div>

  <script>
    async function buscarVentas() {
      const desde = document.getElementById('fecha-desde').value;
      const hasta = document.getElementById('fecha-hasta').value;
      const idVenta = document.getElementById('id-venta').value || null;
      const total = document.getElementById('total-venta').value || null;

      const params = new URLSearchParams();
      params.append('desde', desde);
      params.append('hasta', hasta);
      if (idVenta) params.append('id_venta', idVenta);
      if (total) params.append('total', total);

      const res = await fetch(`/api/cajera/buscar_ventas.php?${params}`);
      const ventas = await res.json();

      let html = '<h3>Resultados</h3>';
      if (ventas.length === 0) {
        html += '<p>No se encontraron ventas.</p>';
      } else {
        html += `
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Método</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              ${ventas.map(v => `
                <tr>
                  <td>${v.id_venta}</td>
                  <td>${v.fecha} ${v.hora}</td>
                  <td>$${parseFloat(v.total).toFixed(2)}</td>
                  <td>${v.metodo_pago}</td>
                  <td class="acciones">
                    <button onclick="verDetalle(${v.id_venta})">Ver Productos</button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
      }
      document.getElementById('resultados').innerHTML = html;
    }

    async function verDetalle(idVenta) {
      const res = await fetch(`/api/cajera/detalle_venta.php?id=${idVenta}`);
      const data = await res.json();

      if (!data.venta) return;

      let html = `
        <h3>Detalles de Venta #${idVenta}</h3>
        <p><strong>Fecha:</strong> ${data.venta.fecha} ${data.venta.hora}</p>
        <p><strong>Total:</strong> $${parseFloat(data.venta.total).toFixed(2)}</p>
        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cant.</th>
              <th>Precio</th>
              <th>Subtotal</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            ${data.detalles.map(d => `
              <tr>
                <td>${d.producto}</td>
                <td>${d.cantidad}</td>
                <td>$${parseFloat(d.precio_unitario).toFixed(2)}</td>
                <td>$${parseFloat(d.subtotal).toFixed(2)}</td>
                <td>
                  <button onclick="devolverItem(${d.id_detalle}, ${idVenta})">↩️ Devolver</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        <button onclick="anularVentaCompleta(${idVenta})" style="background:#f44336;margin-top:1rem;">🗑️ Anular Venta Completa</button>
        <hr>
      `;
      document.getElementById('resultados').innerHTML = html;
    }

    async function devolverItem(idDetalle, idVenta) {
      if (!confirm('¿Devolver este producto y restaurar su stock?')) return;

      const res = await fetch('/api/cajera/devolver_item.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id_detalle: idDetalle, id_venta: idVenta })
      });
      const result = await res.json();
      showToast(result.success ? '✅ Producto devuelto' : '❌ Error en devolución', result.success ? 'success' : 'error');
      if (result.success) verDetalle(idVenta);
    }

    async function anularVentaCompleta(idVenta) {
      if (!confirm('¿Anular toda la venta y devolver todo el stock?')) return;

      const res = await fetch('/api/cajera/anular_venta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id_venta: idVenta })
      });
      const result = await res.json();
      showToast(result.success ? '✅ Venta anulada' : '❌ Error al anular', result.success ? 'success' : 'error');
      if (result.success) buscarVentas();
    }

    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `toast ${type === 'error' ? 'error' : ''}`;
      toast.textContent = message;
      document.getElementById('toast-container').appendChild(toast);
      setTimeout(() => toast.classList.add('show'), 10);
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
  </script>
</body>
</html>