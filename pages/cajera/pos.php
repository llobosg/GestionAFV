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
      max-width: 1200px;
      display: flex;
      height: 85vh;
    }
    .column {
      padding: 1.5rem;
      overflow-y: auto;
    }
    .left-column { width: 60%; border-right: 1px solid #eee; }
    .right-column { width: 40%; }
    .header-pos {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    .header-pos h2 {
      color: #2E7D32;
      margin: 0;
    }
    .buscador {
      padding: 0.8rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      width: 100%;
      margin-bottom: 1.5rem;
      font-size: 1rem;
    }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 0.6rem; text-align: left; border-bottom: 1px solid #eee; }
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
    .form-producto {
      margin-top: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .form-group {
      display: flex;
      flex-direction: column;
    }
    .form-group label {
      font-weight: 600; margin-bottom: 0.3rem;
    }
    .form-group input, .form-group select {
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    .btn-group {
      display: flex;
      gap: 0.5rem;
      margin-top: 1rem;
    }
    .btn {
      flex: 1;
      padding: 0.6rem;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }
    .btn-add { background: #4CAF50; color: white; }
    .btn-finalizar { background: #2196F3; color: white; }
  </style>
</head>
<body>

  <div class="pos-container">
    
    <!-- IZQUIERDA: Carrito -->
    <div class="left-column">
      <div class="header-pos">
        <h2>🛒 Carrito de Ventas</h2>
        <select id="metodo-pago">
          <option value="efectivo">Efectivo</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>
      
      <table id="tabla-carrito">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Subtotal</th>
            <th class="acciones">Acciones</th>
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

      <div class="btn-group">
        <button class="btn btn-finalizar" onclick="finalizarVenta()">Finalizar Venta</button>
      </div>
    </div>

    <!-- DERECHA: Selector de productos -->
    <div class="right-column">
      <h2>🔍 Seleccionar Producto</h2>
      <input type="text" class="buscador" id="buscador-productos" placeholder="Buscar producto...">
      
      <div id="lista-productos" style="margin-top:1rem;">
        <!-- Se llenará dinámicamente -->
        <p style="color:#666;">Empieza a escribir para buscar...</p>
      </div>
    </div>

  </div>

  <script>
    let carrito = [];
    let productosCache = [];

    // Cargar productos del negocio
    async function cargarProductos() {
      const res = await fetch('/api/admin/listar_productos.php');
      productosCache = await res.json();
      filtrarProductos('');
    }

    function filtrarProductos(query) {
      const resultados = productosCache.filter(p => 
        p.producto.toLowerCase().includes(query.toLowerCase())
      ).slice(0, 20);

      const contenedor = document.getElementById('lista-productos');
      if (resultados.length === 0) {
        contenedor.innerHTML = '<p style="color:#999;">No se encontraron productos</p>';
        return;
      }

      contenedor.innerHTML = resultados.map(p => `
        <div style="padding:0.8rem; border:1px solid #eee; border-radius:8px; margin-bottom:0.6rem; cursor:pointer;"
             onclick="agregarAlCarrito(${p.id_producto})">
          <strong>${p.producto}</strong><br>
          <small>${p.familia} • ${p.subfamilia}</small><br>
          <span style="color:#4CAF50;">$${parseFloat(p.precio_venta).toFixed(2)}</span>
          <span style="float:right; color:#666;">Stock: ${parseFloat(p.stock_actual).toFixed(2)}</span>
        </div>
      `).join('');
    }

    document.getElementById('buscador-productos').addEventListener('input', (e) => {
      filtrarProductos(e.target.value);
    });

    function agregarAlCarrito(idProducto) {
      const producto = productosCache.find(p => p.id_producto == idProducto);
      if (!producto) return;

      const cantidad = parseFloat(prompt(`Cantidad a vender de "${producto.producto}"\nStock disponible: ${producto.stock_actual}`, "1"));
      if (!cantidad || isNaN(cantidad) || cantidad <= 0) return;

      if (cantidad > parseFloat(producto.stock_actual)) {
        alert(`❌ Stock insuficiente.\nDisponible: ${producto.stock_actual}`);
        return;
      }

      // Verificar si ya está en el carrito
      const itemExistente = carrito.find(item => item.id_producto === idProducto);
      if (itemExistente) {
        itemExistente.cantidad += cantidad;
      } else {
        carrito.push({
          id_producto: producto.id_producto,
          producto: producto.producto,
          cantidad: cantidad,
          precio_unitario: parseFloat(producto.precio_venta),
          subtotal: cantidad * parseFloat(producto.precio_venta)
        });
      }

      renderizarCarrito();
      document.getElementById('buscador-productos').value = '';
      filtrarProductos('');
    }

    function eliminarDelCarrito(index) {
      carrito.splice(index, 1);
      renderizarCarrito();
    }

    function renderizarCarrito() {
      const tbody = document.querySelector('#tabla-carrito tbody');
      tbody.innerHTML = carrito.map((item, index) => `
        <tr>
          <td>${item.producto}</td>
          <td>${item.cantidad.toFixed(2)}</td>
          <td>$${item.precio_unitario.toFixed(2)}</td>
          <td>$${item.subtotal.toFixed(2)}</td>
          <td class="acciones">
            <button onclick="eliminarDelCarrito(${index})">🗑️</button>
          </td>
        </tr>
      `).join('');

      const total = carrito.reduce((sum, item) => sum + item.subtotal, 0);
      document.getElementById('total-carrito').textContent = `$${total.toFixed(2)}`;
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
        } else {
          alert('❌ Error: ' + (result.message || 'No se pudo registrar la venta'));
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