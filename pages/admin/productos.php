<<?php
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
    .header {
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .header h1 { margin: 0; font-size: 1.6rem; }
    .header .app-name { font-weight: bold; }

    .container {
      display: flex;
      height: calc(100vh - 60px - 2rem);
      margin: 1rem;
      gap: 1.5rem;
    }

    /* Lado izquierdo */
    .tabla-container {
      width: 70%;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .buscador-inteligente {
      padding: 1rem;
      background: #f0f8f0;
      display: flex;
      gap: 0.5rem;
    }
    .buscador-inteligente input {
      flex: 1;
      padding: 0.6rem 1rem;
      border: 1px solid #ccc;
      border-radius: 20px;
      font-size: 1rem;
    }
    .buscador-inteligente button {
      background: #e0e0e0;
      border: none;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      cursor: pointer;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .filtros {
      padding: 1rem;
      background: #f5f5f5;
      border-bottom: 1px solid #eee;
      display: grid;
      grid-template-columns: repeat(4, 1fr) auto;
      gap: 0.75rem;
      align-items: end;
    }
    .filtros input, .filtros select {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 0.9rem;
    }
    .btn-limpiar-filtros {
      background: #ff9800;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      cursor: pointer;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }
    .tabla-scroll {
      flex: 1;
      overflow-y: auto;
    }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 0.6rem 0.8rem; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
    th { background: #4CAF50; color: white; position: sticky; top: 0; }
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

    /* Lado derecho */
    .right-column {
      width: 30%;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }
    .form-container {
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

    /* Gráficos fijos */
    .graficos-container {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 1.2rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
      overflow: hidden;
    }
    .grafico {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .grafico h3 {
      margin: 0 0 0.8rem 0;
      color: #2E7D32;
      font-size: 1.1rem;
    }
    .barra {
      height: 24px;
      background: #e0e0e0;
      border-radius: 4px;
      position: relative;
      margin-bottom: 0.4rem;
    }
    .barra-fill {
      height: 100%;
      background: #4CAF50;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding-right: 0.5rem;
      color: white;
      font-size: 0.8rem;
      font-weight: bold;
    }
    .promedio-stock {
      font-size: 1.8rem;
      font-weight: bold;
      text-align: center;
      margin-top: 0.5rem;
      padding: 0.5rem;
      border-radius: 8px;
    }
    .stock-verde { background: #e8f5e9; color: #2E7D32; }
    .stock-amarillo { background: #fff8e1; color: #FF8F00; }
    .stock-rojo { background: #ffebee; color: #C62828; }
  </style>
</head>
<body>

  <div class="header">
    <div class="app-name">Gestión AFV</div>
    <h1>Mantenedor de Productos 🥦🍎🥕</h1>
  </div>

  <div class="container">
    
    <!-- LADO IZQUIERDO -->
    <div class="tabla-container">
      <!-- Buscador inteligente -->
      <div class="buscador-inteligente">
        <input type="text" id="buscador-global" placeholder="Buscar producto (ej: tomate, manzana...)">
        <button onclick="document.getElementById('buscador-global').value=''; aplicarFiltros()">×</button>
      </div>

      <!-- Filtros -->
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
        <button class="btn-limpiar-filtros" onclick="limpiarFiltros()">
          🧹 Limpiar
        </button>
      </div>

      <!-- Tabla -->
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

    <!-- LADO DERECHO -->
    <div class="right-column">
      <div class="form-container">
        <h2 id="form-title">Agregar Producto</h2>
        <form id="producto-form">
          <input type="hidden" id="id_producto">
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

      <!-- Gráficos fijos -->
      <div class="graficos-container">
        <div class="grafico">
          <h3>📊 Productos por Tipo</h3>
          <div id="grafico-tipos">
            <div style="color:#666; font-style:italic;">Cargando...</div>
          </div>
        </div>
        <div class="grafico">
          <h3>📈 Promedio de Stock</h3>
          <div class="promedio-stock" id="promedio-stock">--</div>
        </div>
      </div>
    </div>

  </div>

  <script>
    let productosCache = [];

    function actualizarGenerados() {
      const familia = document.getElementById('familia').value || '';
      const subfamilia = document.getElementById('subfamilia').value || '';
      const compra = parseFloat(document.getElementById('precio_compra').value) || 0;
      const utilidad = parseFloat(document.getElementById('porc_utilidad').value) || 0;

      document.getElementById('producto-generado').value = `${familia} ${subfamilia}`.trim();
      document.getElementById('precio_venta-generado').value = (compra * (1 + utilidad / 100)).toFixed(2);
    }

    ['familia', 'subfamilia', 'precio_compra', 'porc_utilidad'].forEach(id => {
      document.getElementById(id).addEventListener('input', actualizarGenerados);
    });

    // Filtros y búsqueda
    function aplicarFiltros() {
      const busqueda = document.getElementById('buscador-global').value.toLowerCase();
      const tipo = document.getElementById('filtro-tipo').value;
      const familia = document.getElementById('filtro-familia').value.toLowerCase();
      const producto = document.getElementById('filtro-producto').value.toLowerCase();
      const stockMin = parseFloat(document.getElementById('filtro-stock').value) || -1;

      const tbody = document.querySelector('#tabla-productos tbody');
      tbody.innerHTML = '';

      const filtrados = productosCache.filter(p => {
        const matchBusqueda = !busqueda || p.producto.toLowerCase().includes(busqueda);
        const matchTipo = !tipo || p.tipo === tipo;
        const matchFamilia = !familia || p.familia.toLowerCase().includes(familia);
        const matchProducto = !producto || p.producto.toLowerCase().includes(producto);
        const matchStock = stockMin <= 0 || parseFloat(p.stock_actual) >= stockMin;
        return matchBusqueda && matchTipo && matchFamilia && matchProducto && matchStock;
      }).sort((a, b) => a.producto.localeCompare(b.producto));

      tbody.innerHTML = filtrados.map(p => `
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

    // Eventos de filtros
    ['buscador-global', 'filtro-tipo', 'filtro-familia', 'filtro-producto', 'filtro-stock'].forEach(id => {
      document.getElementById(id).addEventListener('input', aplicarFiltros);
    });

    function limpiarFiltros() {
      document.getElementById('buscador-global').value = '';
      document.getElementById('filtro-tipo').value = '';
      document.getElementById('filtro-familia').value = '';
      document.getElementById('filtro-producto').value = '';
      document.getElementById('filtro-stock').value = '';
      aplicarFiltros();
    }

    // Cargar productos
    async function cargarProductos() {
      const res = await fetch('/api/admin/listar_productos.php');
      productosCache = await res.json();
      aplicarFiltros();
      renderizarGraficos(productosCache);
    }

    // Renderizar gráficos
    function renderizarGraficos(productos) {
      // Promedio de stock con semáforo
      if (productos.length > 0) {
        const totalStock = productos.reduce((sum, p) => sum + parseFloat(p.stock_actual || 0), 0);
        const promedio = totalStock / productos.length;
        const promedioEl = document.getElementById('promedio-stock');
        promedioEl.textContent = promedio.toFixed(2);
        promedioEl.className = 'promedio-stock';
        if (promedio >= 50) promedioEl.classList.add('stock-verde');
        else if (promedio >= 10) promedioEl.classList.add('stock-amarillo');
        else promedioEl.classList.add('stock-rojo');
      } else {
        document.getElementById('promedio-stock').textContent = '0.00';
        document.getElementById('promedio-stock').className = 'promedio-stock stock-rojo';
      }

      // Productos por TIPO (máx 8)
      const tipos = {};
      productos.forEach(p => {
        tipos[p.tipo] = (tipos[p.tipo] || 0) + 1;
      });

      const maxCount = Math.max(...Object.values(tipos), 1);
      let html = '';
      Object.entries(tipos)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 8)
        .forEach(([tipo, count]) => {
          const pct = (count / maxCount) * 100;
          html += `
            <div style="margin-bottom:0.6rem;">
              <div style="font-size:0.85rem;margin-bottom:0.2rem;">${tipo} (${count})</div>
              <div class="barra">
                <div class="barra-fill" style="width:${Math.max(pct, 5)}%;">${count}</div>
              </div>
            </div>
          `;
        });
      document.getElementById('grafico-tipos').innerHTML = html || '<div style="color:#999;">No hay productos</div>';
    }

    // Formulario
    document.getElementById('producto-form').addEventListener('submit', async (e) => {
      e.preventDefault();
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

    // Edición REAL
    async function editarProducto(id) {
      const producto = productosCache.find(p => p.id_producto == id);
      if (!producto) return;

      document.getElementById('id_producto').value = producto.id_producto;
      document.getElementById('tipo').value = producto.tipo;
      document.getElementById('familia').value = producto.familia;
      document.getElementById('subfamilia').value = producto.subfamilia;
      document.getElementById('unidad_medida').value = producto.unidad_medida;
      document.getElementById('precio_compra').value = producto.precio_compra;
      document.getElementById('porc_utilidad').value = producto.porc_utilidad;
      document.getElementById('form-title').textContent = 'Editar Producto';
      actualizarGenerados();
    }

    // Eliminación REAL
    async function eliminarProducto(id) {
      if (!confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')) return;
      await fetch('/api/admin/eliminar_producto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id_producto: id })
      });
      cargarProductos();
    }

    function limpiarForm() {
      document.getElementById('producto-form').reset();
      document.getElementById('id_producto').value = '';
      document.getElementById('form-title').textContent = 'Agregar Producto';
      actualizarGenerados();
    }

    // Iniciar
    document.addEventListener('DOMContentLoaded', cargarProductos);
  </script>
</body>
</html>