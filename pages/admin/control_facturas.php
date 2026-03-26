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
    font-family:'Segoe UI';
    margin:0;
}

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
.kpi h4 { margin:0; color:#777; font-size:0.85rem; }
.kpi strong { font-size:1.4rem; }

/* FILTROS */
.filtros {
    display:flex;
    gap:1rem;
    margin-bottom:1rem;
}

select, input {
    padding:6px;
    border-radius:6px;
    border:1px solid #ccc;
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

/* PANEL LATERAL */
.drawer {
    position:fixed;
    top:0;
    right:-400px;
    width:350px;
    height:100%;
    background:white;
    box-shadow:-3px 0 10px rgba(0,0,0,0.2);
    padding:1.5rem;
    transition:0.3s;
    z-index:999;
}
.drawer.open {
    right:0;
}
.drawer h3 {
    margin-top:0;
}

.close-btn {
    float:right;
    cursor:pointer;
    font-size:1.2rem;
}

</style>
</head>

<body>

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

    <input type="text" id="buscador" placeholder="🔍 Buscar factura...">
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

<!-- PANEL LATERAL -->
<div class="drawer" id="drawer">
<span class="close-btn" onclick="cerrarDrawer()">✖</span>
<h3>Detalle Factura</h3>
<div id="detalle"></div>
</div>

<script>

let dataGlobal = [];

function money(v){
    return '$' + Number(v).toLocaleString('es-CL');
}

/* =========================
   CARGA DATOS
========================= */

async function cargarDatos(){

    const estado = document.getElementById('filtro-estado').value;
    const periodo = document.getElementById('filtro-periodo').value;

    const params = new URLSearchParams();
    if(estado) params.append('estado', estado);
    params.append('periodo', periodo);

    const res = await fetch('/api/admin/facturas_estadisticas.php?'+params);
    const data = await res.json();

    dataGlobal = data.facturas;

    actualizarKPIs(data);
    renderTabla();
}

/* =========================
   KPIs
========================= */

function actualizarKPIs(data){
    document.getElementById('kpi-total').innerText = money(data.total_monto);
    document.getElementById('kpi-iva').innerText = money(data.total_iva);
    document.getElementById('kpi-cantidad').innerText = data.total_qty;
    document.getElementById('kpi-pendiente').innerText = money(data.pendiente.monto);
}

/* =========================
   BUSCADOR
========================= */

document.getElementById('buscador').addEventListener('input', renderTabla);

/* =========================
   TABLA
========================= */

function renderTabla(){

    const filtro = document.getElementById('buscador').value.toLowerCase();

    const filtrados = dataGlobal.filter(f =>
        (f.proveedor || '').toLowerCase().includes(filtro) ||
        (f.nro_factura || '').toString().includes(filtro)
    );

    document.getElementById('tabla').innerHTML =
        filtrados.map(f=>`
            <tr onclick='abrirDrawer(${JSON.stringify(f)})'>
                <td>${f.fecha}</td>
                <td>${f.nro_factura || '-'}</td>
                <td>${f.proveedor}</td>
                <td>${money(f.monto)}</td>
                <td>${money(f.monto*0.19)}</td>
                <td><span class="badge ${f.estado}">${f.estado}</span></td>
            </tr>
        `).join('');
}

/* =========================
   DRAWER
========================= */

function abrirDrawer(f){
    const drawer = document.getElementById('drawer');

    document.getElementById('detalle').innerHTML = `
        <p><strong>Proveedor:</strong> ${f.proveedor}</p>
        <p><strong>Factura:</strong> ${f.nro_factura}</p>
        <p><strong>Monto:</strong> ${money(f.monto)}</p>
        <p><strong>IVA:</strong> ${money(f.monto*0.19)}</p>
        <p><strong>Estado:</strong> ${f.estado}</p>
        <p><strong>Fecha:</strong> ${f.fecha}</p>
    `;

    drawer.classList.add('open');
}

function cerrarDrawer(){
    document.getElementById('drawer').classList.remove('open');
}

/* =========================
   EVENTOS
========================= */

document.getElementById('filtro-estado').onchange = cargarDatos;
document.getElementById('filtro-periodo').onchange = cargarDatos;

/* INIT */
cargarDatos();

</script>

</body>
</html>