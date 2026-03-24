<?php
require_once __DIR__ . '/../../includes/config.php';

// Validación segura de sesión
if (!isset($_SESSION['id_usuario']) || empty($_SESSION['rol'])) {
    header('Location: /public/index.php');
    exit;
}

$rol = $_SESSION['rol'];
if ($rol !== 'cajera' && $rol !== 'admin') {
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
    .left-column { 
      width: 70%; 
      border-right: 1px solid #eee; 
      padding: 1.5rem;
      overflow-y: auto;
    }
    .right-column { 
      width: 30%; 
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    h2 {
      color: #2E7D32;
      margin-top: 0;
      font-size: 1.2rem;
    }
    table { width: 100%; border-collapse: collapse; }
    th, td { 
      padding: 0.6rem; 
      text-align: left; 
      border-bottom: 1px solid #eee; 
      font-size: 0.95rem;
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

    /* Formulario */
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
      width: 100%; /* ← ajustado al 100% del 30% */
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 0.95rem;
    }

    /* Botones de pago */
    .pago-buttons {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.3rem;
    }
    .pago-btn {
      flex: 1;
      padding: 0.5rem;
      border: 1px solid #ccc;
      background: white;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }
    .pago-btn.active {
      background: #2196F3;
      color: white;
    }

    /* Botones de acción */
    .btn {
      width: 100%;
      padding: 0.6rem;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 0.95rem;
      margin: 0.3rem 0;
    }
    .btn-add { background: #4CAF50; color: white; }
    .btn-finalize { background: #2196F3; color: white; }
    .btn-cancel { background: #f44336; color: white; }

    /* Buscador */
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

    /* Calculadora */
    .calculadora {
      background: #f9f9f9;
      padding: 1rem;
      border-radius: 8px;
      margin-top: 0.5rem;
    }
    .calc-display {
      width: 100%;
      padding: 0.5rem;
      margin-bottom: 0.5rem;
      text-align: right;
      font-size: 1.2rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .calc-buttons {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.3rem;
    }
    .calc-btn {
      padding: 0.4rem;
      background: #e0e0e0;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
    }
    .calc-btn.operator {
      background: #2196F3;
      color: white;
    }
    .calc-btn.equals {
      background: #4CAF50;
      color: white;
    }
  </style>
</head>
<body>

  <div class="pos-container">
    
    <!-- IZQUIERDA: Carrito -->
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

    <!-- DERECHA: Formulario + Calculadora -->
    <div class="right-column">
      <h2>➕ Agregar Producto</h2>

      <div class="form-group">
        <label>Producto</label>
        <div class="producto-buscador">
          <input type="text" id="buscador-producto" class="form-control" placeholder="Buscar...">
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
        <div class="pago-buttons">
          <button class="pago-btn" onclick="setMetodoPago('efectivo')">Efectivo</button>
          <button class="pago-btn" onclick="setMetodoPago('tarjeta')">Tarjetas</button>
        </div>
        <input type="hidden" id="metodo-pago" value="efectivo">
      </div>

      <button class="btn btn-add" onclick="agregarAlCarrito()">Agregar al Carrito</button>
      <button class="btn btn-finalize" onclick="finalizarVenta()">Finalizar Venta</button>
      <button class="btn btn-cancel" onclick="limpiarFormulario()">Cancelar Venta</button>

      <!-- CALCULADORA -->
      <div class="calculadora">
        <input type="text" class="calc-display" id="calc-display" value="0" readonly>
        <div class="calc-buttons">
          <button class="calc-btn" onclick="calcAppend('7')">7</button>
          <button class="calc-btn" onclick="calcAppend('8')">8</button>
          <button class="calc-btn" onclick="calcAppend('9')">9</button>
          <button class="calc-btn operator" onclick="calcAppend('/')">/</button>
          
          <button class="calc-btn" onclick="calcAppend('4')">4</button>
          <button class="calc-btn" onclick="calcAppend('5')">5</button>
          <button class="calc-btn" onclick="calcAppend('6')">6</button>
          <button class="calc-btn operator" onclick="calcAppend('*')">*</button>
          
          <button class="calc-btn" onclick="calcAppend('1')">1</button>
          <button class="calc-btn" onclick="calcAppend('2')">2</button>
          <button class="calc-btn" onclick="calcAppend('3')">3</button>
          <button class="calc-btn operator" onclick="calcAppend('-')">-</button>
          
          <button class="calc-btn" onclick="calcAppend('0')">0</button>
          <button class="calc-btn" onclick="calcAppend('.')">.</button>
          <button class="calc-btn equals" onclick="calcEval()">=</button>
          <button class="calc-btn operator" onclick="calcAppend('+')">+</button>
        </div>
      </div>
    </div>

  </div>

  <script>
    let carrito = [];
    let productosCache = [];
    let productoSeleccionado = null;

    // Inicializar
    document.addEventListener('DOMContentLoaded', () => {
      cargarProductos();
      document.getElementById('cantidad').addEventListener('input', calcularSubtotal);
      document.getElementById('precio').addEventListener('input', calcularSubtotal);
    });

    async function cargarProductos() {
      const res = await fetch('/api/cajera/listar_productos.php');
      productosCache = await res.json();
    }

    // Buscador
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

    function setMetodoPago(metodo) {
      document.querySelectorAll('.pago-btn').forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      document.getElementById('metodo-pago').value = metodo;
    }

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
      
      // Reset pago
      document.querySelectorAll('.pago-btn').forEach(btn => btn.classList.remove('active'));
      document.querySelector('.pago-btn:first-child').classList.add('active');
      document.getElementById('metodo-pago').value = 'efectivo';
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
          alert('✅ Venta registrada');
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

    // === CALCULADORA ===
    function calcAppend(value) {
      const display = document.getElementById('calc-display');
      if (display.value === '0' && value !== '.') {
        display.value = value;
      } else {
        display.value += value;
      }
    }

    function calcEval() {
      const display = document.getElementById('calc-display');
      try {
        // Prevenir inyección, solo permitir operaciones básicas
        const expr = display.value.replace(/[^0-9+\-*/().]/g, '');
        const result = Function('"use strict";return (' + expr + ')')();
        display.value = parseFloat(result.toFixed(2)).toString();
      } catch (e) {
        display.value = 'Error';
        setTimeout(() => display.value = '0', 1000);
      }
    }
  </script>
</body>
</html>