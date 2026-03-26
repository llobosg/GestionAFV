<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard Facturas</title>

<style>

body {
    background:#f4f6f9;
    font-family:'Segoe UI', sans-serif;
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

/* CONTENEDOR */
.container {
    max-width:1300px;
    margin:2rem auto;
}

/* KPIs */
.kpi-row {
    display:flex;
    gap:1rem;
    margin-bottom:1rem;
}
.kpi {
    flex:1;
    background:white;
    padding:1rem;
    border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,0.08);
}
.kpi h4 { margin:0; color:#777; }
.kpi strong { font-size:1.4rem; }

/* FILTROS */
.filtros {
    display:flex;
    gap:1rem;
    margin-bottom:1rem;
}

input, select {
    padding:6px;
    border-radius:6px;
    border:1px solid #ccc;
}

/* BUSCADOR */
.search-box {
    position:relative;
}
.clear-btn {
    position:absolute;
    right:8px;
    top:5px;
    cursor:pointer;
    font-size:14px;
}

/* TABLA */
.card {
    background:white;
    padding:1.5rem;
    border-radius:14px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

table {
    width:100%;
    border-collapse:collapse;
}

th,td {
    padding:0.7rem;
    border-bottom:1px solid #eee;
}

th {
    background:#4CAF50;
    color:white;
}

tr:hover {
    background:#f5f5f5;
    cursor:pointer;
}

/* BADGES */
.badge {
    padding:4px 8px;
    border-radius:6px;
    color:white;
    font-size:0.75rem;
}
.pendiente{background:#FF9800;}
.pagada{background:#4CAF50;}
.anulada{background:#F44336;}

/* DRAWER */
.drawer {
    position:fixed;
    top:0;
    right:-420px;
    width:400px;
    height:100%;
    background:white;
    box-shadow:-3px 0 10px rgba(0,0,0,0.2);
    padding:1.5rem;
    transition:0.3s;
    overflow:auto;
}
.drawer.open { right:0; }

.drawer-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.icon-btn {
    cursor:pointer;
    margin-right:10px;
}

/* INPUT EDIT */
.editable input {
    width:100%;
}

/* MINI TABLA */
.mini-table {
    width:100%;
    margin-top:1rem;
    font-size:0.85rem;
}

.mini-table td {
    padding:4px;
}

</style>
</head>
<body>
  <div class="header">
    <div class="app-name">NegociosUP</div>
      <h1>Panel de control 🥦🍎🥕</h1>
      <a href="/public/home.php" 
        style="background:#2E7D32; color:white; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.9rem;">
        ← Volver a Home
      </a>
    </div>
  </div>

<div class="container">

<h2>📊 Dashboard Facturas</h2>

<!-- KPIs -->
<div class="kpi-row">
    <div class="kpi"><h4>Total</h4><strong id="kpi-total">$0</strong></div>
    <div class="kpi"><h4>IVA</h4><strong id="kpi-iva">$0</strong></div>
    <div class="kpi"><h4>Cantidad</h4><strong id="kpi-cantidad">0</strong></div>
    <div class="kpi"><h4>Pendiente</h4><strong id="kpi-pendiente">$0</strong></div>
</div>

<!-- FILTROS -->
<div class="filtros">
    <select id="filtro-estado">
        <option value="">Todos</option>
        <option value="pendiente">Pendiente</option>
        <option value="pagada">Pagada</option>
        <option value="anulada">Anulada</option>
    </select>

    <select id="filtro-periodo">
        <option value="hoy">Hoy</option>
        <option value="semana">7 días</option>
        <option value="mes">Mes</option>
        <option value="ytd">Año</option>
    </select>

    <div class="search-box">
        <input type="text" id="buscador" placeholder="🔍 Buscar...">
        <span class="clear-btn" onclick="limpiarBusqueda()">✖</span>
    </div>
</div>

<!-- TABLA -->
<div class="card">
<table>
<thead>
<tr>
<th>Fecha</th>
<th>N°</th>
<th>Proveedor</th>
<th>Monto</th>
<th>IVA</th>
<th>Estado</th>
</tr>
</thead>
<tbody id="tabla"></tbody>
</table>
</div>

</div>

<!-- DRAWER -->
<div class="drawer" id="drawer">
<h3>Detalle Factura</h3>
<div class="drawer-header">
    <div>
        <span class="icon-btn" onclick="activarEdicion()">✏️</span>
    </div>
    <span onclick="cerrarDrawer()">✖</span>
</div>

<div id="detalle"></div>

  <h4>Productos</h4>
  <table id="tabla-productos">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Cant</th>
        <th>Precio</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <table class="mini-table" id="tabla-productos"></table>
  <button onclick="guardarFactura()" style="margin-top:10px; background:#4CAF50; color:white; border:none; padding:8px 12px; border-radius:6px;">
    💾 Guardar cambios
  </button>
</div>

<script>

let dataGlobal = [];
let facturaActual = null;
let editMode = false;

function money(v){
    return '$' + Number(v).toLocaleString('es-CL');
}

/* CARGA */
async function cargarDatos(){

    const estado = document.getElementById('filtro-estado').value;
    const periodo = document.getElementById('filtro-periodo').value;

    const params = new URLSearchParams();
    if(estado) params.append('estado', estado);
    params.append('periodo', periodo);

    const res = await fetch('/api/admin/facturas_estadisticas.php?'+params);
    const data = await res.json();

    dataGlobal = data.facturas;

    document.getElementById('kpi-total').innerText = money(data.total_monto);
    document.getElementById('kpi-iva').innerText = money(data.total_iva);
    document.getElementById('kpi-cantidad').innerText = data.total_qty;
    document.getElementById('kpi-pendiente').innerText = money(data.pendiente.monto);

    renderTabla();
}

/* BUSCADOR */
document.getElementById('buscador').addEventListener('input', renderTabla);

function limpiarBusqueda(){
    document.getElementById('buscador').value='';
    renderTabla();
}

/* TABLA */
function renderTabla(){

    const filtro = document.getElementById('buscador').value.toLowerCase();

    const filtrados = dataGlobal.filter(f =>
        (f.proveedor||'').toLowerCase().includes(filtro) ||
        (f.nro_factura||'').toString().includes(filtro)
    );

    document.getElementById('tabla').innerHTML =
        filtrados.map(f=>`
            <tr onclick='abrirDrawer(${JSON.stringify(f)})'>
                <td>${f.fecha}</td>
                <td>${f.nro_factura||'-'}</td>
                <td>${f.proveedor}</td>
                <td>${money(f.monto)}</td>
                <td>${money(f.monto*0.19)}</td>
                <td><span class="badge ${f.estado}">${f.estado}</span></td>
            </tr>
        `).join('');
}

/* DRAWER */
async function abrirDrawer(f){
  try {
      facturaActual = f;
      editMode = false;
      renderDetalle();
      document.getElementById('drawer').classList.add('open');

      const resProd = await fetch(`/api/admin/factura_productos.php?id_factura=${factura.id_factura}`);
      const productos = await resProd.json();

      const tbody = document.querySelector('#tabla-productos tbody');

      tbody.innerHTML = productos.map(p => `
        <tr>
          <td><input class="prod-nombre" value="${p.nombre}"></td>
          <td><input class="prod-cantidad" type="number" value="${p.cantidad}"></td>
          <td><input class="prod-precio" type="number" value="${p.precio}"></td>
        </tr>
      `).join('');
  } catch (err) {
    console.error("Error cargando productos:", err);
    alert("Error cargando productos");
  }
}

function renderDetalle(){

    const f = facturaActual;

    document.getElementById('detalle').innerHTML = `
        <p><strong>Proveedor:</strong> ${campo('proveedor', f.proveedor)}</p>
        <p><strong>Factura:</strong> ${campo('nro_factura', f.nro_factura)}</p>
        <p><strong>Monto:</strong> ${campo('monto', f.monto)}</p>
        <p><strong>Glosa:</strong> ${campo('glosa', f.glosa || '')}</p>
        <p><strong>Estado:</strong> ${campo('estado', f.estado)}</p>
    `;

    // Productos mock (puedes reemplazar por API)
    const productos = f.productos || [
        {nombre:'Producto A', cantidad:1, precio:1000}
    ];

    document.getElementById('tabla-productos').innerHTML =
        productos.map(p=>`
            <tr>
                <td>${editMode?`<input value="${p.nombre}">`:p.nombre}</td>
                <td>${editMode?`<input value="${p.cantidad}">`:p.cantidad}</td>
                <td>${editMode?`<input value="${p.precio}">`:money(p.precio)}</td>
            </tr>
        `).join('');
}

function campo(key, value){
    return editMode
        ? `<input value="${value||''}" data-key="${key}">`
        : value;
}

function activarEdicion(){
    editMode = !editMode;
    renderDetalle();
}

function cerrarDrawer(){
    document.getElementById('drawer').classList.remove('open');
}

/* EVENTOS */
document.getElementById('filtro-estado').onchange = cargarDatos;
document.getElementById('filtro-periodo').onchange = cargarDatos;

/* INIT */
cargarDatos();

async function guardarFactura() {

  const id = document.getElementById('panel-id').textContent;

  const proveedor = document.getElementById('panel-proveedor').value.trim();
  const monto = parseFloat(document.getElementById('panel-monto').value);
  const estado = document.getElementById('panel-estado').value;
  const glosa = document.getElementById('panel-glosa').value.trim();

  /* VALIDACIONES */
  if (!proveedor) {
    alert("Proveedor requerido");
    return;
  }

  if (isNaN(monto) || monto <= 0) {
    alert("Monto inválido");
    return;
  }

  if (!estado) {
    alert("Estado requerido");
    return;
  }

  const productos = [];

  document.querySelectorAll('#tabla-productos tbody tr').forEach(tr => {
    const nombre = tr.querySelector('.prod-nombre').value;
    const cantidad = parseFloat(tr.querySelector('.prod-cantidad').value);
    const precio = parseFloat(tr.querySelector('.prod-precio').value);

    if (nombre && cantidad > 0 && precio > 0) {
      productos.push({ nombre, cantidad, precio });
    }
  });

  try {
    const res = await fetch('/api/admin/factura_guardar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_factura: id,
        proveedor,
        monto,
        estado,
        glosa,
        productos
      })
    });

    const data = await res.json();

    if (data.status === 'ok') {
      alert("✅ Guardado correctamente");
      cargarDatos();
    } else {
      alert("❌ Error: " + data.message);
    }

  } catch (e) {
    console.error(e);
    alert("Error de conexión");
  }
}

</script>

</body>
</html>