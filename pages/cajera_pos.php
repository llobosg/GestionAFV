<?php
require_once __DIR__ . '/../includes/config.php';
session_start();

// Verificar rol: cajera o admin
if (!isset($_SESSION['id_usuario']) || !in_array($_SESSION['rol'], ['cajera', 'admin'])) {
    header('Location: ../index.php');
    exit;
}
$id_negocio = $_SESSION['id_negocio'];
$es_admin = ($_SESSION['rol'] === 'admin');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>POS - <?= $es_admin ? 'Admin' : 'Cajera' ?> | StockApp</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    body {
      background: linear-gradient(to bottom, #f5f7fa, #e4e7f0);
      font-family: 'Segoe UI', sans-serif;
      padding: 1rem;
      margin: 0;
    }
    .container { max-width: 1200px; margin: 0 auto; }
    .search-box {
      display: flex; gap: 0.5rem; margin-bottom: 1.5rem;
    }
    .search-box input, .search-box select {
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
    }
    .product-grid {
      width: 100%;
      border-collapse: collapse;
      margin: 1rem 0;
    }
    .product-grid th,
    .product-grid td {
      padding: 0.6rem;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    .product-grid th {
      background: #071289;
      color: white;
    }
    .total-section {
      text-align: right;
      font-size: 1.3rem;
      font-weight: bold;
      margin: 1.5rem 0;
    }
    .btn-action {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      margin-right: 0.5rem;
    }
    .btn-grabar { background: #2ECC71; color: white; }
    .btn-anular { background: #E74C3C; color: white; }
    .btn-editar { background: #3498DB; color: white; padding: 0.3rem 0.6rem; font-size: 0.85rem; }
    .btn-eliminar { background: #E74C3C; color: white; padding: 0.3rem 0.6rem; font-size: 0.85rem; }
    .hidden { display: none; }
  </style>
</head>
<body>
  <div class="container">
    <h2>🛒 Punto de Venta - <?= $es_admin ? 'Administración' : 'Cajera' ?></h2>

    <!-- Búsqueda inteligente -->
    <div class="search-box">
      <input type="text" id="busquedaProducto" placeholder="Buscar producto (ej: tom)" autocomplete="off">
      <select id="listaProductos" style="display:none;"></select>
    </div>

    <!-- Tabla de productos seleccionados -->
    <table class="product-grid" id="tablaProductos">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Precio/UM</th>
          <th>Peso (g)</th>
          <th>UM</th>
          <th>Tipo Venta</th>
          <th>Total</th>
          <?php if ($es_admin): ?>
            <th>Acción</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody id="cuerpoTabla">
        <!-- Productos se agregan aquí dinámicamente -->
      </tbody>
    </table>

    <!-- Total -->
    <div class="total-section">
      Total: $<span id="totalVenta">0</span>
    </div>

    <!-- Botones -->
    <div>
      <button class="btn-action btn-grabar" onclick="grabarVenta()">Grabar Venta</button>
      <button class="btn-action btn-anular" onclick="anularVenta()">Anular Venta</button>
    </div>
  </div>

  <script>
    let productosSeleccionados = [];
    const unidadesMedida = ['unidad','docena','paquete','malla','cajón','caja','saco','display'];

    // === BÚSQUEDA INTELIGENTE ===
    document.getElementById('busquedaProducto').addEventListener('input', async function() {
      const query = this.value.trim();
      if (query.length < 2) {
        document.getElementById('listaProductos').style.display = 'none';
        return;
      }

      const response = await fetch('../api/buscar_productos.php?q=' + encodeURIComponent(query));
      const productos = await response.json();

      const select = document.getElementById('listaProductos');
      select.innerHTML = '';
      productos.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id_producto;
        opt.textContent = `${p.nombre} (${p.um}) - $${p.precio_venta}`;
        opt.dataset.producto = JSON.stringify(p);
        select.appendChild(opt);
      });

      if (productos.length > 0) {
        select.style.display = 'block';
        select.size = Math.min(productos.length, 5);
      } else {
        select.style.display = 'none';
      }
    });

    // === SELECCIONAR PRODUCTO ===
    document.getElementById('listaProductos').addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      if (!selectedOption) return;

      const producto = JSON.parse(selectedOption.dataset.producto);
      agregarProducto(producto);

      // Limpiar
      document.getElementById('busquedaProducto').value = '';
      this.style.display = 'none';
      this.size = 0;
    });

    // === AGREGAR PRODUCTO A LA TABLA ===
    function agregarProducto(p) {
      const id = Date.now() + Math.random(); // ID temporal único
      productosSeleccionados.push({
        id_temp: id,
        id_producto: p.id_producto,
        nombre: p.nombre,
        um: p.um,
        precio_venta: parseFloat(p.precio_venta),
        peso: '',
        um_cantidad: p.um === 'kg' ? '' : '1',
        tipo_venta: 'efectivo'
      });
      renderizarTabla();
    }

    // === RENDERIZAR TABLA ===
    function renderizarTabla() {
      const tbody = document.getElementById('cuerpoTabla');
      tbody.innerHTML = '';

      let totalGeneral = 0;

      productosSeleccionados.forEach(item => {
        let cantidad = 0;
        let totalLinea = 0;

        if (item.um === 'gramos' && item.peso) {
          cantidad = parseFloat(item.peso) / 1000; // Convertir g → kg
          totalLinea = item.precio_venta * cantidad;
        } else if (item.um !== 'gramos' && item.um_cantidad) {
          cantidad = parseFloat(item.um_cantidad) || 0;
          totalLinea = item.precio_venta * cantidad;
        }

        totalGeneral += totalLinea;

        const tr = document.createElement('tr');

        tr.innerHTML = `
          <td>${item.nombre}</td>
          <td>$${item.precio_venta.toFixed(0)}</td>
          <td><input type="number" min="0" step="1" value="${item.peso}" onchange="actualizarCampo('${item.id_temp}', 'peso', this.value)" ${item.um === 'gramos' ? '' : 'disabled'}></td>
          <td>
            ${item.um === 'gramos' ? '' : `
              <select onchange="actualizarCampo('${item.id_temp}', 'um_cantidad', this.value)">
                <option value="">-</option>
                ${unidadesMedida.map(u => `<option value="${u}" ${item.um_cantidad === u ? 'selected' : ''}>${u}</option>`).join('')}
              </select>
            `}
          </td>
          <td>
            <select onchange="actualizarCampo('${item.id_temp}', 'tipo_venta', this.value)">
              <option value="efectivo" ${item.tipo_venta === 'efectivo' ? 'selected' : ''}>Efectivo</option>
              <option value="tarjeta" ${item.tipo_venta === 'tarjeta' ? 'selected' : ''}>Tarjeta</option>
            </select>
          </td>
          <td>$${totalLinea.toFixed(0)}</td>
          ${<?php echo json_encode($es_admin); ?> ? `
            <td>
              <button class="btn-editar" onclick="editarProducto('${item.id_temp}')">✏️</button>
              <button class="btn-eliminar" onclick="eliminarProducto('${item.id_temp}')">🗑️</button>
            </td>
          ` : '<td></td>'}
        `;
        tbody.appendChild(tr);
      });

      document.getElementById('totalVenta').textContent = totalGeneral.toFixed(0);
    }

    // === ACTUALIZAR CAMPO ===
    function actualizarCampo(idTemp, campo, valor) {
      const item = productosSeleccionados.find(p => p.id_temp == idTemp);
      if (item) {
        item[campo] = valor;
        renderizarTabla();
      }
    }

    // === ELIMINAR PRODUCTO ===
    function eliminarProducto(idTemp) {
      productosSeleccionados = productosSeleccionados.filter(p => p.id_temp != idTemp);
      renderizarTabla();
    }

    // === GRABAR VENTA ===
    async function grabarVenta() {
      if (productosSeleccionados.length === 0) {
        alert('⚠️ No hay productos para vender');
        return;
      }

      // Validar que cada línea tenga peso o UM
      for (const item of productosSeleccionados) {
        if (item.um === 'gramos' && !item.peso) {
          alert(`❌ El producto "${item.nombre}" requiere peso en gramos`);
          return;
        }
        if (item.um !== 'gramos' && !item.um_cantidad) {
          alert(`❌ El producto "${item.nombre}" requiere unidad de medida`);
          return;
        }
      }

      const response = await fetch('../api/grabar_venta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ productos: productosSeleccionados })
      });

      const data = await response.json();
      if (data.success) {
        alert('✅ Venta registrada exitosamente');
        productosSeleccionados = [];
        renderizarTabla();
      } else {
        alert('❌ ' + data.message);
      }
    }

    // === ANULAR VENTA ===
    async function anularVenta() {
      if (productosSeleccionados.length === 0) {
        alert('No hay venta para anular');
        return;
      }

      if (!confirm('¿Estás segura de anular esta venta?')) return;

      // Opcional: si ya se grabó, llamar a API para restaurar stock
      // Por ahora, solo limpiamos
      productosSeleccionados = [];
      renderizarTabla();
      alert('✅ Venta anulada');
    }

    // Cerrar lista al hacer clic fuera
    document.addEventListener('click', (e) => {
      const searchBox = document.querySelector('.search-box');
      if (!searchBox.contains(e.target)) {
        document.getElementById('listaProductos').style.display = 'none';
        document.getElementById('listaProductos').size = 0;
      }
    });
  </script>
</body>
</html>