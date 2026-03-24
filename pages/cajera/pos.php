<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'cajera' && $_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>🛒 Punto de Venta — Gestión AFV</title>
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #e4edc3 100%);
      margin: 0; padding: 0;
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .pos-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      width: 95%;
      max-width: 1400px;
      display: flex;
      height: 85vh;
    }
    .column {
      padding: 1.5rem;
      overflow-y: auto;
    }
    .left-column { 
      width: 50%; 
      border-right: 1px solid #eee; 
    }
    .right-column { 
      width: 50%; 
      display: flex;
      flex-direction: column;
    }
    h2 {
      color: #2E7D32;
      margin-top: 0;
    }
    table { width: 100%; border-collapse: collapse; }
    th, td { 
      padding: 0.6rem; 
      text-align: left; 
      border-bottom: 1px solid #eee; 
      font-size: 0.95rem; /* ← mismo tamaño que botones */
    }
    th { background: #4CAF50; color: white; }
    .acciones { text-align: center; }
    .acciones button {
      background: none; border: none; cursor: pointer; opacity: 0.7;
    }
    .acciones button:hover { opacity: 1; }
    .total-row {
      font-weight: bold;
      background: #f0f8f0;
    }

    /* Formulario derecho */
    .form-group {
      margin-bottom: 1rem;
    }
    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.3rem;
      font-size: 0.95rem;
    }
    .form-control {
      width: 100%;
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 0.95rem;
    }
    .btn {
      padding: 0.6rem;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 0.95rem; /* ← tamaño consistente */
    }
    .btn-add { background: #4CAF50; color: white; width: 100%; margin: 0.5rem 0; }
    .btn-finalize { background: #2196F3; color: white; width: 100%; margin: 0.5rem 0; }
    .btn-cancel { background: #f44336; color: white; width: 100%; margin: 0.5rem 0; }

    /* Buscador de productos */
    .producto-buscador {
      position: relative;
    }
    .producto-results {
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
      border-radius: 0 0 6px 6px;
    }
    .producto-item {
      padding: 0.5rem;
      cursor: pointer;
    }
    .producto-item:hover {
      background: #f0f8f0;
    }
  </style>
</head>
<body>

  <div class="pos-container">
    
    <!-- IZQUIERDA: Carrito (solo lectura) -->
    <div class="left-column">
      <h2>🛒 Productos Vendidos</h2>
      
      <table id="tabla-carrito">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cant.</th>
            <th>Precio</th>
            <th>Subtotal</th>
            <th class="acciones">✕</th>
          </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
          <tr class="total-row">
            <td colspan="3" style="text-align:right;">Total:</td>
            <td id="total-carrito">$0.00</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- DERECHA: Formulario de entrada -->
    <div class="right-column">
      <h2>➕ Agregar Producto</h2>

      <div class="form-group">
        <label>Producto</label>
        <div class="producto-buscador">
          <input type="text" id="buscador-producto" class="form-control" placeholder="Escribe para buscar...">
          <div class="producto-results" id="resultados-producto" style="display:none;"></div>
        </div>
      </div>

      <div class="form-group">
        <label>Cantidad</label>
        <input type="number" step="0.01" id="cantidad" class="form-control" value="1" min="0.01">
      </div>

      <div class="form-group">
        <label>Precio Unitario ($)</label>
        <input type="number" step="0.01" id="precio" class="form-control" readonly>
      </div>

      <div class="form-group">
        <label>Subtotal ($)</label>
        <input type="text" id="subtotal" class="form-control" readonly>
      </div>

      <div class="form-group">
        <label>Medio de Pago</label>
        <select id="metodo-pago" class="form-control">
          <option value="efectivo">Efectivo</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>

      <button class="btn btn-add" onclick="agregarAlCarrito()">Agregar al Carrito</button>
      <button class="btn btn-finalize" onclick="finalizarVenta()">Finalizar Venta</button>
      <button class="btn btn-cancel" onclick="limpiarFormulario()">Cancelar Venta</button>
    </div>

  </div>

  <script>
    let carrito = [];
    let productosCache = [];
    let productoSeleccionado = null;

    // Cargar productos
    async function cargarProductos() {
      const res = await fetch('/api/admin/listar_productos.php');
      productosCache = await res.json();
    }

    // Buscar productos mientras escribes
    document.getElementById('buscador-producto').addEventListener('input', function(e) {
      const query = e.target.value.toLowerCase();
      const contenedor = document.getElementById('resultados-producto');
      if (!query) {
        contenedor.style.display = 'none';
        return;
      }

      const resultados = productosCache.filter(p => 
        p.producto.toLowerCase().includes(query)
      ).slice(0, 10);

      if (resultados.length > 0) {
        contenedor.innerHTML = resultados.map(p => `
          <div class="producto-item" onclick="seleccionarProducto(${p.id_producto})">
            ${p.producto} • $${parseFloat(p.precio_venta).toFixed(2)} • Stock: ${p.stock_actual}
          </div>
        `).join('');
        contenedor.style.display = 'block';
      } else {
        contenedor.style.display = 'none';
      }
    });

    function seleccionarProducto(id) {
      const p = productosCache.find(x => x.id_producto == id);
      if (!p) return;

      productoSeleccionado = p;
      document.getElementById('buscador-producto').value = p.producto;
      document.getElementById('precio').value = parseFloat(p.precio_venta).toFixed(2);
      document.getElementById('cantidad').value = 1;
      calcularSubtotal();
      document.getElementById('resultados-producto').style.display = 'none';
    }

    function calcularSubtotal() {
      const cantidad = parseFloat(document.getElementById('cantidad').value) || 0;
      const precio = parseFloat(document.getElementById('precio').value) || 0;
      document.getElementById('subtotal').value = (cantidad * precio).toFixed(2);
    }

    ['cantidad', 'precio'].forEach(id => {
      document.getElementById(id).addEventListener('input', calcularSubtotal);
    });

    function agregarAlCarrito() {
      if (!productoSeleccionado) {
        alert('Selecciona un producto primero');
        return;
      }

      const cantidad = parseFloat(document.getElementById('cantidad').value);
      const precio = parseFloat(document.getElementById('precio').value);
      const stock = parseFloat(productoSeleccionado.stock_actual);

      if (cantidad > stock) {
        alert(`❌ Stock insuficiente. Disponible: ${stock}`);
        return;
      }

      // Verificar si ya está en el carrito
      const existente = carrito.find(item => item.id_producto === productoSeleccionado.id_producto);
      if (existente) {
        existente.cantidad += cantidad;
        existente.subtotal = existente.cantidad * existente.precio_unitario;
      } else {
        carrito.push({
          id_producto: productoSeleccionado.id_producto,
          producto: productoSeleccionado.producto,
          cantidad: cantidad,
          precio_unitario: precio,
          subtotal: cantidad * precio
        });
      }

      renderizarCarrito();
      limpiarFormulario();
    }

    function eliminarDelCarrito(index) {
      carrito.splice(index, 1);
      renderizarCarrito();
    }

    function renderizarCarrito() {
      const tbody = document.querySelector('#tabla-carrito tbody');
      tbody.innerHTML = carrito.map((item, i) => `
        <tr>
          <td>${item.producto}</td>
          <td>${item.cantidad.toFixed(2)}</td>
          <td>$${item.precio_unitario.toFixed(2)}</td>
          <td>$${item.subtotal.toFixed(2)}</td>
          <td class="acciones"><button onclick="eliminarDelCarrito(${i})">×</button></td>
        </tr>
      `).join('');

      const total = carrito.reduce((sum, item) => sum + item.subtotal, 0);
      document.getElementById('total-carrito').textContent = `$${total.toFixed(2)}`;
    }

    function limpiarFormulario() {
      document.getElementById('buscador-producto').value = '';
      document.getElementById('cantidad').value = '1';
      document.getElementById('precio').value = '';
      document.getElementById('subtotal').value = '';
      productoSeleccionado = null;
      document.getElementById('resultados-producto').style.display = 'none';
    }

    async function finalizarVenta() {
      if (carrito.length === 0) {
        alert('El carrito está vacío');
        return;
      }

      const metodoPago = document.getElementById('metodo-pago').value;
      const total = carrito.reduce((sum, item) => sum + item.subtotal, 0);

      const ventaData = {
        id_negocio: <?= $id_negocio ?>,
        id_cajera: <?= $_SESSION['id_usuario'] ?>,
        metodo_pago: metodoPago,
        total: total,
        detalles: carrito
      };

      try {
        const res = await fetch('/api/cajera/registrar_venta.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(ventaData)
        });

        const result = await res.json();
        if (result.success) {
          alert('✅ Venta registrada con éxito');
          carrito = [];
          renderizarCarrito();
          limpiarFormulario();
        } else {
          alert('❌ Error: ' + (result.message || 'No se pudo registrar'));
        }
      } catch (err) {
        alert('Error de conexión');
      }
    }

    // Iniciar
    document.addEventListener('DOMContentLoaded', cargarProductos);
  </script>
</body>
</html>