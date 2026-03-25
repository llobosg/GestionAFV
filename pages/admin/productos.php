<?php
require_once __DIR__ . '/../../includes/config.php';

// Validación segura de sesión
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /public/index.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>🥦 Mantenedor de Productos — NegocioUP</title>
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
    .form-row {
      display: flex;
      gap: 0.75rem;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      flex: 1;
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

    /* Gráficos */
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
    /* Barras verticales */
    .barras-container {
      display: flex;
      align-items: flex-end;
      height: 120px;
      gap: 0.5rem;
      padding: 0.5rem;
    }
    .barra-vertical {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .barra-fill-v {
      width: 100%;
      background: #4CAF50;
      border-radius: 4px 4px 0 0;
      transition: height 0.3s;
    }
    .barra-label {
      font-size: 0.7rem;
      text-align: center;
      margin-top: 0.3rem;
    }
    /* Semáforo */
    .semaphore {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 1rem;
    }
    .light {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      opacity: 0.3;
    }
    .light.active {
      opacity: 1;
    }
    .light.green { background: #4CAF50; }
    .light.yellow { background: #FFC107; }
    .light.red { background: #F44336; }
    .promedio-value {
      font-size: 1.8rem;
      font-weight: bold;
      text-align: center;
      margin-top: 0.5rem;
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="app-name">NegociosUP</div>
      <h1>Mantenedor de Productos 🥦🍎🥕</h1>
      <a href="/public/home.php" 
        style="background:#2E7D32; color:white; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.9rem;">
        ← Volver a Home
      </a>
    </div>
  </div>

  <div class="container">
    
    <!-- LADO IZQUIERDO -->
    <div class="tabla-container">
      <div class="buscador-inteligente">
        <input type="text" id="buscador-global" placeholder="Buscar producto (ej: tomate, manzana...)">
        <button onclick="document.getElementById('buscador-global').value=''; aplicarFiltros()">×</button>
      </div>
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
        <button class="btn-limpiar-filtros" onclick="limpiarFiltros()">🧹 Limpiar</button>
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

    <!-- LADO DERECHO -->
    <div class="right-column">
      <div class="form-container">
        <h2 id="form-title">Agregar Producto</h2>
        <form id="producto-form">
          <input type="hidden" id="id_producto">
          <input type="hidden" id="codigo">
          <!-- Campo producto oculto -->
          <input type="hidden" id="producto-generado">

          <!-- Fila 1: Tipo -->
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

          <!-- Fila 2: Familia + Subfamilia -->
          <div class="form-row">
            <div class="form-group">
              <label>Familia *</label>
              <input type="text" id="familia" required>
            </div>
            <div class="form-group">
              <label>Subfamilia *</label>
              <input type="text" id="subfamilia" required>
            </div>
          </div>

          <!-- Fila 3: Unidad Medida + % Utilidad -->
          <div class="form-row">
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
              <label>% Utilidad *</label>
              <input type="number" step="0.1" id="porc_utilidad" required min="0" value="30">
            </div>
          </div>

          <!-- Fila 4: Precio Compra + Precio Venta -->
          <div class="form-row">
            <div class="form-group">
              <label>Precio Compra ($)*</label>
              <input type="number" step="0.01" id="precio_compra" required min="0">
            </div>
            <div class="form-group readonly">
              <label>Precio Venta ($)</label>
              <input type="text" id="precio_venta-generado" readonly>
            </div>
          </div>

          <!-- Fila 5: Stock Actual + Crítico -->
          <div class="form-row">
            <div class="form-group">
              <label>Stock Actual</label>
              <input type="number" step="0.01" id="stock_actual" value="0">
            </div>
            <div class="form-group">
              <label>Stock Crítico</label>
              <input type="number" step="0.01" id="stock_critico" value="10">
            </div>
          </div>

          <div class="btn-group">
            <button type="submit" class="btn btn-save">Guardar</button>
            <button type="button" onclick="limpiarForm()" class="btn btn-cancel">Cancelar</button>
          </div>
        </form>
      </div>

      <!-- GRÁFICOS -->
      <div class="graficos-container">
        <div class="grafico">
          <h3>📊 Productos por Tipo</h3>
          <div class="barras-container" id="grafico-tipos"></div>
        </div>
        <div class="grafico">
          <h3>📈 Promedio de Stock</h3>
          <div class="promedio-value" id="promedio-stock">--</div>
          <div class="semaphore">
            <div class="light green" id="light-green"></div>
            <div class="light yellow" id="light-yellow"></div>
            <div class="light red" id="light-red"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <script>
    let productosCache = [];

    function actualizarGenerados() {
      const compra = parseFloat(document.getElementById('precio_compra').value) || 0;
      const utilidad = parseFloat(document.getElementById('porc_utilidad').value) || 0;
      document.getElementById('precio_venta-generado').value = (compra * (1 + utilidad / 100)).toFixed(2);
    }

    ['precio_compra', 'porc_utilidad'].forEach(id => {
      document.getElementById(id).addEventListener('input', actualizarGenerados);
    });

    // Filtros
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

    function renderizarGraficos(productos) {
      // === Productos por Tipo (barras verticales) ===
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
          const heightPct = (count / maxCount) * 100;
          html += `
            <div class="barra-vertical">
              <div class="barra-fill-v" style="height: ${Math.max(heightPct, 5)}%;"></div>
              <div class="barra-label">${tipo}<br>(${count})</div>
            </div>
          `;
        });
      document.getElementById('grafico-tipos').innerHTML = html || '<div style="color:#999;text-align:center;width:100%;">Sin datos</div>';

      // === Semáforo basado en stock_actual vs stock_critico ===
      if (productos.length === 0) {
        document.getElementById('promedio-stock').textContent = '0.00';
        document.getElementById('light-green').classList.remove('active');
        document.getElementById('light-yellow').classList.remove('active');
        document.getElementById('light-red').classList.add('active');
        return;
      }

      // Contar estados
      let rojos = 0, amarillos = 0, verdes = 0;
      productos.forEach(p => {
        const actual = parseFloat(p.stock_actual) || 0;
        const critico = parseFloat(p.stock_critico) || 10;
        if (actual >= critico) {
          verdes++;
        } else if (actual >= critico * 0.5) {
          amarillos++;
        } else {
          rojos++;
        }
      });

      // Determinar estado predominante
      const total = productos.length;
      const rojoPct = rojos / total;
      const amarilloPct = amarillos / total;

      // Reset luces
      document.getElementById('light-green').classList.remove('active');
      document.getElementById('light-yellow').classList.remove('active');
      document.getElementById('light-red').classList.remove('active');

      if (rojoPct > 0.3) {
        document.getElementById('light-red').classList.add('active');
        document.getElementById('promedio-stock').textContent = '⚠️ Crítico';
      } else if (amarilloPct > 0.5) {
        document.getElementById('light-yellow').classList.add('active');
        document.getElementById('promedio-stock').textContent = '🟡 Alerta';
      } else {
        document.getElementById('light-green').classList.add('active');
        document.getElementById('promedio-stock').textContent = '✅ Estable';
      }
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
        porc_utilidad: document.getElementById('porc_utilidad').value,
        stock_actual: document.getElementById('stock_actual').value || 0
      };

      await fetch('/api/admin/guardar_producto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      });

      limpiarForm();
      cargarProductos();
    });

    // Edición
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
      document.getElementById('stock_actual').value = producto.stock_actual;
      document.getElementById('form-title').textContent = 'Editar Producto';
      actualizarGenerados();
    }

    // Eliminación
    async function eliminarProducto(id) {
      if (!confirm('¿Eliminar este producto?')) return;
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

    document.addEventListener('DOMContentLoaded', cargarProductos);
  </script>
</body>
</html>