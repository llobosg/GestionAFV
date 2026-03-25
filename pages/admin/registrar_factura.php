<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;
$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';
$nombre = $_SESSION['nombre_usuario'] ?? 'Admin';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>🧾 Registrar Factura — NegocioUP</title>
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
      max-width: 700px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    .form-box {
      background: white;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    h2 { color: #2E7D32; margin-top: 0; }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label {
      display: block;
      margin-bottom: 0.4rem;
      font-weight: bold;
    }
    .form-control {
      width: 100%;
      padding: 0.7rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
    }
    .btn {
      padding: 0.7rem 1.5rem;
      background: #2196F3;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      font-size: 1rem;
    }
    .toast-container {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1000;
    }
    .toast {
      background: #2196F3;
      color: white;
      padding: 1rem 1.8rem;
      border-radius: 10px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.2);
      min-width: 280px;
      text-align: center;
      font-weight: bold;
      opacity: 0;
      transform: translateY(100px);
      transition: all 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28);
    }
    .toast.show {
      opacity: 1;
      transform: translateY(0);
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
    <div class="form-box">
      <h2>🧾 Registrar Factura</h2>
      
      <div class="form-group">
        <label>N° Factura (opcional)</label>
        <input type="text" id="nro-factura" class="form-control" placeholder="Ej: F001-12345">
      </div>

      <div class="form-group">
        <label>Proveedor *</label>
        <input type="text" id="proveedor" class="form-control" placeholder="Ej: Frutas del Sur S.A." required>
      </div>

      <div class="form-group">
        <label>Fecha *</label>
        <input type="date" id="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="form-group">
        <label>Monto ($)</label>
        <input type="number" step="0.01" id="monto" class="form-control" placeholder="Ej: 50000.50">
      </div>

      <div class="form-group">
        <label>Estado</label>
        <select id="estado" class="form-control">
          <option value="pendiente">Pendiente</option>
          <option value="pagada">Pagada</option>
          <option value="anulada">Anulada</option>
        </select>
      </div>

      <div class="form-group">
        <label>Glosa (productos incluidos)</label>
        <textarea id="glosa" class="form-control" rows="3" placeholder="Ej: Tomates, zapallos, cebollas"></textarea>
      </div>

      <button class="btn" onclick="registrarFactura()">✅ Registrar Factura</button>
    </div>
  </div>

  <div class="toast-container" id="toast-container"></div>

  <script>
    async function registrarFactura() {
      const data = {
        nro_factura: document.getElementById('nro-factura').value || null,
        proveedor: document.getElementById('proveedor').value.trim(),
        fecha: document.getElementById('fecha').value,
        monto: document.getElementById('monto').value || null,
        estado: document.getElementById('estado').value,
        glosa: document.getElementById('glosa').value.trim()
      };

      if (!data.proveedor || !data.fecha) {
        showToast('Proveedor y fecha son obligatorios', 'error');
        return;
      }

      const res = await fetch('/api/admin/registrar_factura.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      });
      const result = await res.json();

      if (result.success) {
        showToast('✅ Factura registrada');
        document.getElementById('nro-factura').value = '';
        document.getElementById('proveedor').value = '';
        document.getElementById('monto').value = '';
        document.getElementById('glosa').value = '';
      } else {
        showToast('❌ Error al registrar', 'error');
      }
    }

    function showToast(msg, type = 'success') {
      const t = document.createElement('div');
      t.className = 'toast';
      if (type === 'error') t.style.background = '#F44336';
      t.textContent = msg;
      document.getElementById('toast-container').appendChild(t);
      setTimeout(() => t.classList.add('show'), 10);
      setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => t.remove(), 300);
      }, 3000);
    }
  </script>
</body>
</html>