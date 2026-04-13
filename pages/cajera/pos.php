<?php
  require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

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

  // Variables seguras
  $id_negocio = $_SESSION['id_negocio'] ?? 1;
  $nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';
  $nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
  $apellido = $_SESSION['apellido_usuario'] ?? '';
  $nombre_completo = trim("{$nombre} {$apellido}") ?: $nombre;
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

    /* === TOAST NOTIFICATIONS === */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
    }
    .toast {
      background: #4CAF50;
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      margin-bottom: 0.75rem;
      min-width: 280px;
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.3s ease;
    }
    .toast.show {
      opacity: 1;
      transform: translateX(0);
    }
    .toast.warning { background: #FF9800; }
    .toast.error { background: #F44336; }

    /* === BARRA SUPERIOR === */
    .top-bar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      color: white;
      padding: 0.8rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 900;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .top-left .btn-home {
      color: white;
      text-decoration: none;
      font-weight: bold;
      font-size: 1.1rem;
    }
    .top-center {
      font-size: 1.2rem;
      font-weight: bold;
    }
    .top-right {
      font-size: 0.95rem;
      opacity: 0.9;
    }
    /* Ajustar contenedor principal para no solapar la barra */
    .pos-container {
      margin-top: 60px; /* ← espacio para la barra */
    }
  </style>
</head>
<!-- 
<script src="https://cdnjs.cloudflare.com/ajax/libs/qz-tray/2.2.0/qz-tray.js"></script>
<script>
// Conectar a QZ Tray
async function conectarImpresora() {
  try {
    if (!qz.websocket.isActive()) {
      await qz.websocket.connect();
    }
    return true;
  } catch (err) {
    alert('❌ Instala QZ Tray en esta PC para imprimir');
    console.error(err);
    return false;
  }
}

// Imprimir ticket
async function imprimirTicket(venta) {
  const conectado = await conectarImpresora();
  if (!conectado) return;

  // Formato ESC/POS básico
  let commands = [
    { type: 'raw', data: '\x1B\x40' }, // Inicializar
    { type: 'text', data: venta.nombre_negocio + '\n', options: { align: 'center', bold: true } },
    { type: 'text', data: new Date().toLocaleString('es-CL') + '\n', options: { align: 'center' } },
    { type: 'text', data: 'Cajero: ' + venta.cajero + '\n\n' },
    { type: 'raw', data: '--------------------------------\n' },

    // Productos
    ...venta.detalles.map(d => 
      `${d.producto.substring(0,20).padEnd(20)} ${d.cantidad.toString().padStart(4)} ${('$' + d.precio_unitario).padStart(8)} ${('$' + d.subtotal).padStart(8)}\n`
    ),

    { type: 'raw', data: '--------------------------------\n\n' },

    // Totales
    { type: 'text', data: 'NETO:'.padEnd(30) + ('$' + venta.neto).padStart(10) + '\n' },
    { type: 'text', data: 'IVA (19%):'.padEnd(30) + ('$' + venta.iva).padStart(10) + '\n' },
    { type: 'text', data: 'TOTAL:'.padEnd(30) + ('$' + venta.total).padStart(10) + '\n\n' },

    { type: 'text', data: 'Medio de pago: ' + venta.metodo_pago + '\n\n' },

    // Código de barras (Code 128)
    { type: 'barcode', data: venta.id_venta.toString().padStart(8, '0'), options: { type: '128', height: 50, width: 2 } },

    { type: 'text', data: '\n\npowered by NegocioUP\n', options: { align: 'center', small: true } },
    { type: 'raw', data: '\x1D\x56\x41\x03' } // Cortar papel
  ];

  try {
    await qz.printers.find().then(async (printer) => {
      await qz.print(printer, commands);
    });
  } catch (err) {
    alert('Error al imprimir: ' + err.message);
  }
}
</script>
-->
<body>
    <!-- BARRA SUPERIOR -->
    <div class="top-bar">
      <div class="top-left">
        <a href="/public/home.php" class="btn-home">← Home</a>
      </div>
      <div class="top-center">
        <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
      </div>
      <div class="top-right">
        <span><?= htmlspecialchars($nombre_completo) ?></span> • 
        <span id="fecha-hora"></span>
      </div>
    </div>
    <!-- TOAST Y SCRIPTS -->
    <div class="toast-container" id="toast-container"></div>
    <div class="pos-container">

    <!-- IZQUIERDA: Carrito -->
    <div class="left-column">
      <h2>  🛒 Carrito de compra</h2>
      <table id="tabla-carrito">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cant.</th>
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

      <!-- BOTONES MOVIDOS AQUÍ -->
      <div style="margin-top: 1.5rem; display:flex; gap:0.8rem;">
        <button class="btn btn-finalize" onclick="finalizarVenta()">✅ Finalizar Venta</button>
        <button class="btn btn-cancel" onclick="cancelarVentaCompleta()">❌ Cancelar Venta</button>
      </div>
    </div>

    <!-- DERECHA: Formulario + Calculadora -->
    <div class="right-column">
      <h2>➕ Agregar Producto 🥦🍎🥕</h2>

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
        <!-- Botones de pago con iconos -->
        <div class="pago-buttons">
          <button class="pago-btn" onclick="setMetodoPago('efectivo')">💵 Efectivo</button>
          <button class="pago-btn" onclick="setMetodoPago('tarjeta')">💳 Tarjetas</button>
        </div>
        <input type="hidden" id="metodo-pago" value="efectivo">
      </div>

      <button class="btn btn-add" onclick="agregarAlCarrito()">Agregar al Carrito</button>

      <!-- CALCULADORA -->
      <div class="calc-buttons">
        <button class="calc-btn operator" onclick="calcClear()">C</button> <!-- ← Nuevo botón -->
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

  <script>
    let carrito = [];
    let productosCache = [];

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

    // === DEPURACIÓN Y LÓGICA DEL BUSCADOR POS ===

    // Variable global para el producto seleccionado (asegúrate de declararla arriba si no existe)
    let productoSeleccionado = null; 

    // 1. Escuchar el input del buscador
    const inputBuscador = document.getElementById('buscador-producto');
    const contenedorResultados = document.getElementById('resultados-producto');

    if (!inputBuscador || !contenedorResultados) {
        console.error('❌ ERROR CRÍTICO: Faltan elementos del DOM (#buscador-producto o #resultados-producto)');
    } else {
        console.log('✅ Elementos del buscador encontrados correctamente.');

        inputBuscador.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            
            // Limpiar resultados si está vacío
            if (!query || query.length < 2) {
                contenedorResultados.style.display = 'none';
                contenedorResultados.innerHTML = '';
                return;
            }

            console.log(`🔍 Buscando: "${query}"...`);
            
            // Verificar cache
            if (typeof productosCache === 'undefined' || !Array.isArray(productosCache)) {
                console.error('❌ productosCache no está definido o no es un array.');
                contenedorResultados.innerHTML = '<div style="padding:10px; color:red;">Error de carga de datos</div>';
                contenedorResultados.style.display = 'block';
                return;
            }

            // Filtrar productos (normales y promos)
            const resultados = productosCache.filter(p => 
                p.producto && p.producto.toLowerCase().includes(query)
            );

            console.log(`📊 Resultados encontrados: ${resultados.length}`);

            if (resultados.length === 0) {
                contenedorResultados.innerHTML = '<div style="padding:10px;">No se encontraron productos</div>';
                contenedorResultados.style.display = 'block';
                return;
            }

            // Renderizar lista
            let html = '';
            // Dentro del bucle forEach donde generas el html...
            resultados.forEach(p => {
                // ⚠️ AGREGA ESTE LOG PARA VERIFICAR EL ID REAL
                console.log(`🆔 Renderizando: ${p.producto} | ID Real DB: ${p.id_producto}`);

                const tipoLabel = p.tipo === 'promo' ? '🏷️ Promo' : ' Normal';
                const precioDisplay = `$${parseFloat(p.precio_venta).toLocaleString('es-CL')}`;
                
                html += `
                    <div class="resultado-item" style="padding:10px; border-bottom:1px solid #eee; cursor:pointer; display:flex; justify-content:space-between;" 
                        onclick="seleccionarProducto(${p.id_producto})"> <!-- Aquí se pasa el ID -->
                        <span><strong>${p.producto}</strong> <small>(${tipoLabel})</small></span>
                        <span>${precioDisplay}</span>
                    </div>
                `;
            });

            contenedorResultados.innerHTML = html;
            contenedorResultados.style.display = 'block';
        });
    }

    // 2. Función para seleccionar producto (con protección de errores)
    function seleccionarProducto(id) {
        console.log(`👆 Intentando seleccionar producto ID: ${id}`);

        try {
            if (!Array.isArray(productosCache)) throw new Error('Cache no cargado');

            const p = productosCache.find(x => x.id_producto == id);
            if (!p) {
                console.error('❌ Producto no encontrado');
                return;
            }

            console.log('✅ Producto seleccionado:', p.producto, '| Tipo:', p.tipo_registro);
            productoSeleccionado = p;

            // Referencias al DOM
            const inputNombre = document.getElementById('buscador-producto');
            const inputPrecio = document.getElementById('precio');
            const inputCantidad = document.getElementById('cantidad');
            
            if (!inputNombre || !inputPrecio || !inputCantidad) throw new Error('Faltan inputs');

            // 1. Llenar datos básicos
            inputNombre.value = p.producto;
            
            // 2. Lógica específica para PROMOS vs NORMAL
            if (p.tipo_registro === 'promo') {
                // --- CASO A: Es una PROMO ---
                const cantidadPromo = parseInt(p.cantidad_unidades) || 1;
                const precioPromo = parseFloat(p.precio_venta);

                inputPrecio.value = precioPromo.toFixed(2);
                inputCantidad.value = cantidadPromo; // Inicia con la cantidad de la promo (ej: 2)
                
                // Guardamos metadata en el input para validar después
                inputCantidad.dataset.esPromo = "true";
                inputCantidad.dataset.minPromo = cantidadPromo;
                inputCantidad.dataset.precioUnitarioPromo = (precioPromo / cantidadPromo).toFixed(2); // Precio unitario real

                console.log(`️ Modo PROMO activado: Min ${cantidadPromo}, Precio Total $${precioPromo}`);

            } else {
                // --- CASO B: Es PRODUCTO NORMAL ---
                inputPrecio.value = parseFloat(p.precio_venta).toFixed(2);
                inputCantidad.value = 1;
                
                inputCantidad.dataset.esPromo = "false";
                
                // Verificar si existe una PROMO asociada a este mismo producto base
                // (Asumimos que el nombre es igual o tienes un id_producto_base)
                const promoAsociada = productosCache.find(prod => 
                    prod.tipo_registro === 'promo' && 
                    prod.producto === p.producto // O comparar IDs base si los tienes
                );

                if (promoAsociada) {
                    console.log(`ℹ️ Existe promo para este producto: ${promoAsociada.cantidad_unidades} x $${promoAsociada.precio_venta}`);
                    inputCantidad.dataset.tienePromoDisponible = "true";
                    inputCantidad.dataset.cantidadMinimaPromo = promoAsociada.cantidad_unidades;
                    inputCantidad.dataset.idPromoAsociada = promoAsociada.id_producto;
                    inputCantidad.dataset.precioPromoTotal = promoAsociada.precio_venta;
                } else {
                    inputCantidad.dataset.tienePromoDisponible = "false";
                }
            }

            // 3. Calcular subtotal inicial
            calcularSubtotal();

            // Ocultar resultados
            document.getElementById('resultados-producto').style.display = 'none';
            document.getElementById('resultados-producto').innerHTML = '';

        } catch (error) {
            console.error('💥 Error en selección:', error);
            alert('Error al procesar el producto.');
        }
    }

    // === NUEVA FUNCIÓN: Validar cambios en Cantidad ===
    // Debes agregar un listener al input de cantidad
    document.addEventListener('DOMContentLoaded', () => {
        const inputCantidad = document.getElementById('cantidad');
        
        if (inputCantidad) {
            inputCantidad.addEventListener('change', function() {
                let val = parseInt(this.value);
                if (isNaN(val) || val < 1) val = 1;

                // ESCENARIO 1: Es un producto en PROMO
                if (this.dataset.esPromo === "true") {
                    const min = parseInt(this.dataset.minPromo);
                    
                    // Forzar múltiplos
                    if (val % min !== 0) {
                        // Redondear al múltiplo más cercano (hacia arriba o abajo, tú decides)
                        // Aquí redondeamos hacia abajo para no cobrar de más accidentalmente, o arriba para forzar venta
                        const nuevoVal = Math.floor(val / min) * min;
                        
                        if (nuevoVal === 0) {
                            alert(`⚠️ Este producto solo se vende en packs de ${min} unidades.`);
                            this.value = min;
                            val = min;
                        } else {
                            const confirmacion = confirm(`⚠️ La promoción es de a ${min} unidades.\nLa cantidad ${val} no es válida.\n¿Deseas ajustar a ${nuevoVal} (${nuevoVal/min} packs)?`);
                            if (confirmacion) {
                                this.value = nuevoVal;
                                val = nuevoVal;
                            } else {
                                this.value = min; // Volver al mínimo
                                val = min;
                            }
                        }
                    }
                    
                    // Recalcular precio si es necesario (aunque el precio total ya viene fijo por pack, a veces se quiere mostrar unitario)
                    // En tu caso, el precio en el input 'precio' parece ser el TOTAL del pack.
                    // Si cambias cantidad de 2 a 4, el precio debería duplicarse? 
                    // SI ES PROMO FIJA (2x1200), usualmente el precio unitario baja, pero el total sube.
                    // Ajuste lógico: Si es promo, el precio unitario es (PrecioPromo / CantidadPromo).
                    const precioUnitarioReal = parseFloat(this.dataset.precioUnitarioPromo);
                    document.getElementById('precio').value = (precioUnitarioReal * val).toFixed(2);

                } 
                // ESCENARIO 2: Es producto NORMAL pero tiene PROMO disponible
                else if (this.dataset.tienePromoDisponible === "true") {
                    const minPromo = parseInt(this.dataset.cantidadMinimaPromo);
                    
                    if (val >= minPromo) {
                        const idPromo = this.dataset.idPromoAsociada;
                        const precioPromoTotal = parseFloat(this.dataset.precioPromoTotal);
                        const precioActualNormal = parseFloat(document.getElementById('precio').value);
                        const costoNormalActual = precioActualNormal * val;
                        
                        // Comparar costos
                        if (precioPromoTotal < costoNormalActual) {
                            const msg = `🔥 ¡Oferta Detectada!\n\nLlevas ${val} unidades de "${productoSeleccionado.producto}".\n\n💰 Comprando normal: $${costoNormalActual}\n✨ Comprando en PROMO (${minPromo}x): $${precioPromoTotal}\n\n¿Quieres cambiar automáticamente a la promoción?`;
                            
                            if (confirm(msg)) {
                                // CAMBIAR AL PRODUCTO PROMO
                                seleccionarProducto(idPromo); 
                                // Ajustar cantidad al múltiplo exacto si excede
                                const inputCant = document.getElementById('cantidad');
                                if (val > minPromo && val % minPromo !== 0) {
                                    inputCant.value = Math.floor(val / minPromo) * minPromo;
                                }
                                return; // Salir para no ejecutar el resto
                            }
                        }
                    }
                }

                // Actualizar subtotal final
                calcularSubtotal();
            });
        }
    });

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
          <td class="acciones">
            <button onclick="editarProducto(${i})" title="Editar">✏️</button>
            <button onclick="eliminarDelCarrito(${i})" title="Eliminar">🗑️</button>
          </td>
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
        showToast('El carrito está vacío', 'warning');
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
          showToast('✅ Venta registrada con éxito');
          
          // En lugar de descargar PDF
          if (result.success) {
            showToast('✅ Venta registrada');

            /*
            // Preparar datos para impresión
            const ventaData = {
              id_venta: result.id_venta,
              nombre_negocio: "<?= htmlspecialchars($nombre_negocio) ?>",
              cajero: "<?= htmlspecialchars($nombre_completo) ?>",
              total: carrito.reduce((sum, item) => sum + item.subtotal, 0),
              neto: $venta['total'] / 1.19,
              iva: $venta['total'] - $neto,
              metodo_pago: document.getElementById('metodo-pago').value === 'efectivo' ? 'Efectivo' : 'Tarjeta',
              detalles: carrito
            };

            // Imprimir en impresora local
            imprimirTicket(ventaData);
            */

            // Limpiar
            carrito = [];
            renderizarCarrito();
            limpiarFormulario();
          }
        } else {
          showToast('❌ Error: ' + (result.message || 'No se pudo registrar'), 'error');
        }
      } catch (err) {
        showToast('❌ Error de conexión', 'error');
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

    // === EDITAR PRODUCTO EN CARRITO ===
    function editarProducto(index) {
      const item = carrito[index];
      const nuevaCantidad = parseFloat(prompt(`Editar cantidad para "${item.producto}"\nActual: ${item.cantidad}`, item.cantidad));
      
      if (nuevaCantidad && !isNaN(nuevaCantidad) && nuevaCantidad > 0) {
        // Verificar stock
        const prod = productosCache.find(p => p.id_producto === item.id_producto);
        if (prod && nuevaCantidad <= parseFloat(prod.stock_actual)) {
          item.cantidad = nuevaCantidad;
          item.subtotal = item.cantidad * item.precio_unitario;
          renderizarCarrito();
        } else {
          showToast('❌ Stock insuficiente para la nueva cantidad', 'warning');
        }
      }
    }

    // === CANCELAR VENTA COMPLETA ===
    function cancelarVentaCompleta() {
      if (confirm('¿Cancelar toda la venta?')) {
        carrito = [];
        renderizarCarrito();
        limpiarFormulario();
      }
    }

    // === CALCULADORA: LIMPIAR ===
    function calcClear() {
      document.getElementById('calc-display').value = '0';
    }

    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = `toast ${type === 'warning' ? 'warning' : type === 'error' ? 'error' : ''}`;
      toast.textContent = message;

      document.getElementById('toast-container').appendChild(toast);

      // Mostrar
      setTimeout(() => toast.classList.add('show'), 10);

      // Ocultar y eliminar
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
    // Actualizar fecha y hora en tiempo real
    function actualizarFechaHora() {
      const ahora = new Date();
      const opciones = { 
        weekday: 'short', 
        day: '2-digit', 
        month: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit' 
      };
      document.getElementById('fecha-hora').textContent = ahora.toLocaleString('es-ES', opciones);
    }
    setInterval(actualizarFechaHora, 1000);
    actualizarFechaHora(); // Inicial
  </script>
  <div class="toast-container" id="toast-container"></div>
</body>
</html>