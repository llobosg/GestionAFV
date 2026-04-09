<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

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
  <title>📉 Registrar Merma — Gestión AFV</title>
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
      max-width: 800px;
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
      background: #F44336;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      font-size: 1rem;
    }
    .buscador-producto {
      position: relative;
    }
    .resultados {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1px solid #ccc;
      border-top: none;
      max-height: 200px;
      overflow-y: auto;
      z-index: 10;
      border-radius: 0 0 8px 8px;
    }
    .item { padding: 0.6rem; cursor: pointer; }
    .item:hover { background: #f0f8f0; }

    /* Toast */
    .toast-container {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1000;
    }
    .toast {
      background: #F44336;
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
      <h2>📉 Registrar Merma</h2>
      
      <div class="form-group">
        <label>Producto</label>
        <div class="buscador-producto">
          <input type="text" id="buscador" class="form-control" placeholder="Buscar producto...">
          <div class="resultados" id="resultados" style="display:none;"></div>
        </div>
      </div>

      <div class="form-group">
        <label>Cantidad a desechar</label>
        <input type="number" step="0.01" id="cantidad" class="form-control" min="0.01" placeholder="Ej: 2.5">
      </div>

      <div class="form-group">
        <label>Comentario (opcional)</label>
        <textarea id="comentario" class="form-control" rows="3" placeholder="Ej: Tomates muy maduros"></textarea>
      </div>

      <button class="btn" onclick="registrarMerma()">🗑️ Registrar Merma</button>
    </div>
  </div>

  <div class="toast-container" id="toast-container"></div>

  <script>
    let productos = [];
    let productoSeleccionado = null;

    // Cargar productos
    async function cargarProductos() {
      const res = await fetch('/api/cajera/listar_productos.php'); // reutilizamos API existente
      productos = await res.json();
    }

    document.getElementById('buscador').addEventListener('input', function(e) {
      const q = e.target.value.toLowerCase();
      const cont = document.getElementById('resultados');
      if (!q) {
        cont.style.display = 'none';
        return;
      }
      const r = productos.filter(p => p.producto.toLowerCase().includes(q)).slice(0, 10);
      if (r.length > 0) {
        cont.innerHTML = r.map(p => 
          `<div class="item" onclick="seleccionar(${p.id_producto})">${p.producto} • Stock: ${p.stock_actual}</div>`
        ).join('');
        cont.style.display = 'block';
      } else {
        cont.style.display = 'none';
      }
    });

    function seleccionar(id) {
      productoSeleccionado = productos.find(p => p.id_producto == id);
      document.getElementById('buscador').value = productoSeleccionado.producto;
      document.getElementById('resultados').style.display = 'none';
    }

    async function registrarMerma() {
      if (!productoSeleccionado) {
        showToast('Selecciona un producto', 'error');
        return;
      }
      const cantidad = parseFloat(document.getElementById('cantidad').value);
      if (!cantidad || cantidad <= 0) {
        showToast('Cantidad inválida', 'error');
        return;
      }
      if (cantidad > parseFloat(productoSeleccionado.stock_actual)) {
        showToast('❌ Stock insuficiente', 'error');
        return;
      }

      const data = {
        id_producto: productoSeleccionado.id_producto,
        cantidad: cantidad,
        comentario: document.getElementById('comentario').value.trim()
      };

      const res = await fetch('/api/admin/registrar_merma.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      });
      const result = await res.json();

      if (result.success) {
        showToast('✅ Merma registrada y stock actualizado');
        document.getElementById('buscador').value = '';
        document.getElementById('cantidad').value = '';
        document.getElementById('comentario').value = '';
        productoSeleccionado = null;
      } else {
        showToast('❌ Error al registrar merma', 'error');
      }
    }

    function showToast(msg, type = 'success') {
      const t = document.createElement('div');
      t.className = 'toast';
      t.textContent = msg;
      document.getElementById('toast-container').appendChild(t);
      setTimeout(() => t.classList.add('show'), 10);
      setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => t.remove(), 300);
      }, 3000);
    }

    document.addEventListener('DOMContentLoaded', cargarProductos);
  </script>
</body>
</html>