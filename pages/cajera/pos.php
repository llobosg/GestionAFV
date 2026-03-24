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

    .left-column { 
      width: 50%; /* ← reducido de 80% a 50% */
      border-right: 1px solid #eee; 
      display: flex;
      flex-direction: column;
    }

    .right-column { 
      width: 50%; /* ← ahora ocupa la mitad */
      display: flex;
      flex-direction: column;
      padding: 1.5rem;
    }

    /* Asegurar que la lista de productos tenga altura y scroll */
    .lista-productos {
      flex: 1;
      overflow-y: auto;
      margin-top: 1rem;
      padding-right: 0.5rem;
    }

    .producto-item {
      padding: 0.8rem;
      border: 1px solid #eee;
      border-radius: 8px;
      margin-bottom: 0.6rem;
      cursor: pointer;
      font-size: 0.9rem;
      background: #fafafa;
    }

    .producto-item:hover {
      background: #f0f8f0;
    }
  </style>
</head>
<body>

  <div class="pos-container">
    
    <!-- IZQUIERDA: Carrito -->
    <div class="left-column">
      <div class="header-pos">
        <h2>🛒 Carrito de Ventas</h2>
        <select id="metodo-pago" style="padding:0.5rem; border-radius:6px;">
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
      <h2 style="margin-top:0;">🔍 Seleccionar Producto</h2>
      <input type="text" class="buscador" id="buscador-productos" placeholder="Buscar producto...">

      <!-- Contenedor con scroll garantizado -->
      <div class="lista-productos" id="lista-productos">
        <p style="color:#666; font-style:italic;">Escribe para buscar productos...</p>
      </div>
    </div>

  </div>

  <script>
    let carrito = [];
    let productosCache = [];

    async function cargarProductos() {
      const res = await fetch('/api/admin/listar_productos.php');
      productosCache = await res.json();
      filtrarProductos('');
    }

    function filtrarProductos(query) {
      const resultados = productosCache.filter(p => 
        p.producto.toLowerCase().includes(query.toLowerCase())
      ).slice(0, 30);

      const contenedor = document.getElementById('lista-productos');
      if (resultados.length === 0) {
        contenedor.innerHTML = '<p style="color:#999;">No hay productos</p>';
        return;
      }

      contenedor.innerHTML = resultados.map(p => `
        <div class="producto-item" onclick="agregarAlCarrito(${p.id_producto})">
          <div class="producto-nombre">${p.producto}</div>
          <div><span class="producto-precio">$${parseFloat(p.precio_venta).toFixed(2)}</span>
          <span class="producto-stock">Stock: ${parseFloat(p.stock_actual).toFixed(2)}</span></div>
          <div style="font-size:0.8rem; color:#666;">${p.familia} • ${p.subfamilia}</div>
        </div>
      `).join('');
    }

    document.getElementById('buscador-productos').addEventListener('input', (e) => {
      filtrarProductos(e.target.value);
    });

    function agregarAlCarrito(idProducto) {
      const producto = productosCache.find(p => p.id_producto == idProducto);
      if (!producto) return;

      const cantidad = parseFloat(prompt(`Cantidad a vender de "${producto.producto}"\nStock: ${producto.stock_actual}`, "1"));
      if (!cantidad || isNaN(cantidad) || cantidad <= 0) return;

      if (cantidad > parseFloat(producto.stock_actual)) {
        alert(`❌ Stock insuficiente.\nDisponible: ${producto.stock_actual}`);
        return;
      }

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
          alert('✅ Venta registrada');
          carrito = [];
          renderizarCarrito();
        } else {
          alert('❌ Error: ' + (result.message || 'No se pudo registrar'));
        }
      } catch (err) {
        alert('Error de conexión');
      }
    }

    document.addEventListener('DOMContentLoaded', cargarProductos);
  </script>
</body>
</html>