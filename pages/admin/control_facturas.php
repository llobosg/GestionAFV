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

.active-row {
  background: #e8f5e9;
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
        <div>ID: <span id="panel-id"></span></div>
        <div>
        Proveedor:
        <input id="panel-proveedor">
        </div>
        <div>
            Monto:
            <input id="panel-monto" type="number">
        </div>
        <div>
            Estado:
            <select id="panel-estado">
            <option value="pendiente">Pendiente</option>
            <option value="pagada">Pagada</option>
            <option value="anulada">Anulada</option>
            </select>
        </div>
        <div>
            Glosa:
            <textarea id="panel-glosa"></textarea>
        </div>
        <button onclick="guardarFactura()" style="margin-top:10px; background:#4CAF50; color:white; border:none; padding:8px 12px; border-radius:6px;">
            💾 Guardar cambios
        </button>
    </div>
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

    document.getElementById('drawer').classList.add('open');

    renderDetalle();

  } catch (err) {

    console.error("Error abriendo drawer:", err);
    alert("Error cargando detalle");

  }
}

function renderDetalle() {

  if (!facturaActual) {
    console.error("No hay factura seleccionada");
    return;
  }

  const elId = document.getElementById('panel-id');
  const elProveedor = document.getElementById('panel-proveedor');
  const elMonto = document.getElementById('panel-monto');
  const elEstado = document.getElementById('panel-estado');
  const elGlosa = document.getElementById('panel-glosa');

  if (!elId || !elProveedor || !elMonto || !elEstado || !elGlosa) {
    console.error("Faltan elementos del panel", {
      elId, elProveedor, elMonto, elEstado, elGlosa
    });
    return;
  }

  elId.textContent = facturaActual.id_factura;
  elProveedor.value = facturaActual.proveedor || '';
  elMonto.value = facturaActual.monto || 0;
  elEstado.value = facturaActual.estado || 'pendiente';
  elGlosa.value = facturaActual.glosa || '';
  tr.classList.add('active-row')
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
  if (!proveedor) return alert("Proveedor requerido");
  if (isNaN(monto) || monto <= 0) return alert("Monto inválido");
  if (!estado) return alert("Estado requerido");

  try {

    const res = await fetch('/api/admin/factura_guardar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_factura: id,
        proveedor,
        monto,
        estado,
        glosa
      })
    });

    const data = await res.json();

    if (data.status === 'ok') {
      alert("✅ Guardado");
      cargarDatos();
    } else {
      alert("❌ " + data.message);
    }

    document.getElementById('drawer').classList.remove('open');

  } catch (e) {
    console.error(e);
    alert("Error conexión");
  }
}

</script>

</body>
</html>