<?php
require_once __DIR__ . '/../../includes/config.php';

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
    body { 
      background: #f9fbe7; 
      font-family: 'Segoe UI', sans-serif; 
      margin: 0; 
      padding: 0;
    }
    .container {
      display: flex;
      height: calc(100vh - 2rem);
      margin: 1rem;
      gap: 1.5rem;
    }
    /* Lado izquierdo: tabla */
    .tabla-container {
      width: 70%;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .filtros {
      padding: 1rem;
      background: #f5f5f5;
      border-bottom: 1px solid #eee;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.75rem;
    }
    .filtros input, .filtros select {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 0.9rem;
    }
    .tabla-scroll {
      flex: 1;
      overflow-y: auto;
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
    }
    th, td { 
      padding: 0.6rem 0.8rem; 
      text-align: left; 
      border-bottom: 1px solid #eee; 
      font-size: 0.9rem;
    }
    th { 
      background: #4CAF50; 
      color: white; 
      position: sticky;
      top: 0;
    }
    .acciones { text-align: center; }
    .acciones button {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1.1rem;
      margin: 0 0.3rem;
      opacity: 0.7;
      transition: opacity 0.2s;
    }
    .acciones button:hover { opacity: 1; }

    /* Lado derecho: formulario */
    .form-container {
      width: 30%;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .form-container h2 {
      margin-top: 0;
      color: #2E7D32;
      font-size: 1.3rem;
    }
    .form-group {
      display: flex;
      flex-direction: column;
    }
    .form-group label {
      font-weight: 600;
      margin-bottom: 0.3rem;
      font-size: 0.9rem;
    }
    .form-group input, .form-group select {
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 0.95rem;
    }
    .form-group.readonly input {
      background: #f9f9f9;
      color: #666;
    }
    .btn-group {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.5rem;
    }
    .btn {
      flex: 1;
      padding: 0.6rem;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 0.95rem;
    }
    .btn-save { background: #4CAF50; color: white; }
    .btn-cancel { background: #ccc; color: #333; }
  </style>
</head>
<body>
  <div class="container">
    
    <!-- LADO IZQUIERDO: TABLA -->
    <div class="tabla-container">
      <div class="filtros">
        <select id="filtro-tipo">
          <option value="">Todos los tipos</option>
          <option value="Abarrotes">Abarrotes</option>
          <option value="Frutas">Frutas</option>
          <option value="Verduras">Verduras</option>
          <option value="Lácteos">Lácteos</option>
          <option value="Botillería">Botillería</option>
          <option value="Bebidas">Bebidas</option>
          <option value="Propios">Propios</option>
          <option value="Otros">Otros</option>
        </select>
        <input type="text" id="filtro-familia" placeholder="Familia">
        <input type="text" id="filtro-producto" placeholder="Producto">
        <input type="number" id="filtro-stock" placeholder="Stock mínimo">
      </div>
      <div class="tabla-scroll">
        <table id="tabla-productos">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Familia</th>
              <th>Subfamilia</th>
              <th>UM</th>
              <th>Compra</th>
              <th>% Util.</th>
              <th>Venta</th>
              <th>Stock</th>
              <th class="acciones">Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- LADO DERECHO: FORMULARIO -->
    <div class="form-container">
      <h2 id="form-title">Agregar Producto</h2>
      
      <form id="producto-form">
        <input type="hidden" id="id_producto">
        <!-- Código autogenerado (oculto) -->
        <input type="hidden" id="codigo">

        <div class="form-group">
          <label>Tipo *</label>
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
          <label>Familia * (ej: Manzana)</label>
          <input type="text" id="familia" required>
        </div>

        <div class="form-group">
          <label>Subfamilia * (ej: Fuji)</label>
          <input type="text" id="subfamilia" required>
        </div>

        <div class="form-group">
          <label>Unidad de Medida *</label>
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
          <label>Precio Compra ($)*</label>
          <input type="number" step="0.01" id="precio_compra" required min="0">
        </div>

        <div class="form-group">
          <label>% Utilidad *</label>
          <input type="number" step="0.1" id="porc_utilidad" required min="0" value="30">
        </div>

        <!-- Campos generados (solo lectura) -->
        <div class="form-group readonly">
          <label>Producto generado</label>
          <input type="text" id="producto-generado" readonly>
        </div>

        <div class="form-group readonly">
          <label>Precio Venta ($)</label>
          <input type="text" id="precio_venta-generado" readonly>
        </div>

        <div class="btn-group">
          <button type="submit" class="btn btn-save">Guardar</button>
          <button type="button" onclick="limpiarForm()" class="btn btn-cancel">Cancelar</button>
        </div>
      </form>
    </div>

  </div>

  <script>
    // Actualizar campos generados en tiempo real
    function actualizarGenerados() {
      const familia = document.getElementById('familia').value || '';
      const subfamilia = document.getElementById('subfamilia').value || '';
      const compra = parseFloat(document.getElementById('precio_compra').value) || 0;
      const utilidad = parseFloat(document.getElementById('porc_utilidad').value) || 0;

      document.getElementById('producto-generado').value = `${familia} ${subfamilia}`.trim();
      document.getElementById('precio_venta-generado').value = (compra * (1 + utilidad / 100)).toFixed(2);
    }

    // Event listeners para actualizar en tiempo real
    ['familia', 'subfamilia', 'precio_compra', 'porc_utilidad'].forEach(id => {
      document.getElementById(id).addEventListener('input', actualizarGenerados);
    });

    // Cargar productos al iniciar
    document.addEventListener('DOMContentLoaded', () => {
      cargarProductos();
      actualizarGenerados();
    });

    async function cargarProductos() {
      const res = await fetch('/api/admin/listar_productos.php');
      const productos = await res.json();
      const tbody = document.querySelector('#tabla-productos tbody');
      tbody.innerHTML = productos.map(p => `
        <tr>
          <td>${p.producto}</td>
          <td>${p.tipo}</td>
          <td>${p.familia}</td>
          <td>${p.subfamilia}</td>
          <td>${p.unidad_medida}</td>
          <td>$${parseFloat(p.precio_compra).toFixed(2)}</td>
          <td>${parseFloat(p.porc_utilidad).toFixed(1)}%</td>
          <td>$${parseFloat(p.precio_venta).toFixed(2)}</td>
          <td>${parseFloat(p.stock_actual).toFixed(2)}</td>
          <td class="acciones">
            <button onclick="editarProducto(${p.id_producto})">✏️</button>
            <button onclick="eliminarProducto(${p.id_producto})">🗑️</button>
          </td>
        </tr>
      `).join('');
    }

    document.getElementById('producto-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      // Generar código único: TIPO-FAMILIA-SUBFAMILIA-ID (pero lo generamos en backend)
      const data = {
        id_producto: document.getElementById('id_producto').value || null,
        id_negocio: <?= $id_negocio ?>,
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
      alert('Edición completa: implementaremos en el próximo paso.');
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
      actualizarGenerados();
    }
  </script>
</body>
</html>