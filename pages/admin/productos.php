<?php
require_once __DIR__ . '/../../includes/config.php';

// Solo admin puede acceder
if ($_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio'];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>🥦 Mantenedor de Productos — Gestión AFV</title>
  <link rel="stylesheet" href="/public/styles.css">
  <style>
    body { background: #f9fbe7; font-family: sans-serif; }
    .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #4CAF50; color: white; }
    .btn { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; cursor: pointer; }
    .btn-edit { background: #2196F3; color: white; }
    .btn-delete { background: #f44336; color: white; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.3rem; font-weight: bold; }
    .form-group input, .form-group select { width: 100%; padding: 0.5rem; }
  </style>
</head>
<body>
  <div class="container">
    <h1>🥦 Mantenedor de Productos</h1>
    
    <!-- Formulario -->
    <div id="form-producto" style="background:white;padding:1.5rem;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:2rem;">
      <h2 id="form-title">Agregar Producto</h2>
      <form id="producto-form">
        <input type="hidden" id="id_producto">
        <div class="form-group">
          <label>Código (ej: FRU-MANZ-001)</label>
          <input type="text" id="codigo" required>
        </div>
        <div class="form-group">
          <label>Tipo</label>
          <select id="tipo" required>
            <option value="">-- Seleccionar --</option>
            <option value="Abarrotes">Abarrotes</option>
            <option value="Frutas">Frutas</option>
            <option value="Verduras">Verduras</option>
            <option value="Lácteos">Lácteos</option>
            <option value="Botillería">Botillería</option>
            <option value="Bebidas">Bebidas</option>
            <option value="Propios">Propios</option>
            <option value="Otros">Otros</option>
          </select>
        </div>
        <div class="form-group">
          <label>Familia (ej: Manzana)</label>
          <input type="text" id="familia" required>
        </div>
        <div class="form-group">
          <label>Subfamilia (ej: Fuji)</label>
          <input type="text" id="subfamilia" required>
        </div>
        <div class="form-group">
          <label>Unidad de Medida</label>
          <select id="unidad_medida" required>
            <option value="unidad">Unidad</option>
            <option value="kg">Kilogramo</option>
            <option value="litro">Litro</option>
            <option value="paquete">Paquete</option>
            <option value="caja">Caja</option>
            <option value="bandeja">Bandeja</option>
            <option value="docena">Docena</option>
            <option value="1/2 docena">1/2 docena</option>
            <option value="pack">Pack</option>
          </select>
        </div>
        <div class="form-group">
          <label>Precio Compra ($)</label>
          <input type="number" step="0.01" id="precio_compra" required min="0">
        </div>
        <div class="form-group">
          <label>% Utilidad (ej: 30 para 30%)</label>
          <input type="number" step="0.1" id="porc_utilidad" required min="0" value="30">
        </div>
        <button type="submit" class="btn btn-edit">Guardar</button>
        <button type="button" onclick="limpiarForm()" class="btn" style="background:#ccc;">Cancelar</button>
      </form>
    </div>

    <!-- Tabla -->
    <table id="tabla-productos">
      <thead>
        <tr>
          <th>Código</th>
          <th>Producto</th>
          <th>Tipo</th>
          <th>UM</th>
          <th>Compra</th>
          <th>Utilidad</th>
          <th>Venta</th>
          <th>Stock</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', cargarProductos);

    async function cargarProductos() {
      const res = await fetch('/api/admin/listar_productos.php');
      const productos = await res.json();
      const tbody = document.querySelector('#tabla-productos tbody');
      tbody.innerHTML = productos.map(p => `
        <tr>
          <td>${p.codigo || '-'}</td>
          <td>${p.producto}</td>
          <td>${p.tipo}</td>
          <td>${p.unidad_medida}</td>
          <td>$${parseFloat(p.precio_compra).toFixed(2)}</td>
          <td>${parseFloat(p.porc_utilidad).toFixed(1)}%</td>
          <td>$${parseFloat(p.precio_venta).toFixed(2)}</td>
          <td>${parseFloat(p.stock_actual).toFixed(2)}</td>
          <td>
            <button class="btn btn-edit" onclick="editarProducto(${p.id_producto})">Editar</button>
            <button class="btn btn-delete" onclick="eliminarProducto(${p.id_producto})">Eliminar</button>
          </td>
        </tr>
      `).join('');
    }

    document.getElementById('producto-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = {
        id_producto: document.getElementById('id_producto').value || null,
        id_negocio: <?= $id_negocio ?>,
        codigo: document.getElementById('codigo').value,
        tipo: document.getElementById('tipo').value,
        familia: document.getElementById('familia').value,
        subfamilia: document.getElementById('subfamilia').value,
        unidad_medida: document.getElementById('unidad_medida').value,
        precio_compra: document.getElementById('precio_compra').value,
        porc_utilidad: document.getElementById('porc_utilidad').value
      };
      await fetch('/api/admin/guardar_producto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      });
      limpiarForm();
      cargarProductos();
    });

    function editarProducto(id) {
      alert('Edición completa pendiente. Implementaremos en el próximo paso.');
    }

    async function eliminarProducto(id) {
      if (confirm('¿Eliminar este producto?')) {
        await fetch('/api/admin/eliminar_producto.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ id_producto: id })
        });
        cargarProductos();
      }
    }

    function limpiarForm() {
      document.getElementById('producto-form').reset();
      document.getElementById('id_producto').value = '';
      document.getElementById('form-title').textContent = 'Agregar Producto';
    }
  </script>
</body>
</html>