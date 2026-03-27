<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';
$nombre_usuario = $_SESSION['nombre_usuario'] ?? 'Admin';
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
    margin:0;
}

/* HEADER */
.header {
    background: linear-gradient(135deg, #4CAF50, #2E7D32);
    color:white;
    padding:1rem 2rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
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

.active-row {
    background:#e8f5e9 !important;
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
.drawer-overlay {
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.3);
    opacity:0;
    pointer-events:none;
    transition:0.3s;
}

.drawer-overlay.open {
    opacity:1;
    pointer-events:auto;
}

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
}

.drawer.open {
    right:0;
}

.drawer h3 {
    margin-top:0;
}

.form-group {
    display:flex;
    align-items:center;
    margin-bottom:1rem;
    gap:1rem;
}

.form-group label {
    width:120px;
    font-size:0.85rem;
    color:#555;
}

.form-group input,
.form-group textarea,
.form-group select {
    flex:1;
}

/* SAVE INDICATOR */
.save-status {
    font-size:0.8rem;
    color:#4CAF50;
    margin-top:10px;
}

</style>
</head>

<body>

<div class="header">
    <div><strong><?= $nombre_negocio ?></strong></div>
    <div><?= $nombre_usuario ?></div>
    <a href="/public/home.php" style="color:white;">← Home</a>
</div>

<div class="container">

<h2>📊 Dashboard Facturas</h2>

<div class="kpi-row">
    <div class="kpi">
        <h4>IVA x Pagar</h4>
        <strong id="kpi-iva">$0</strong>
    </div>
    <div class="kpi">
        <h4>IVA Pagado</h4>
        <strong id="kpi-iva-pagado">$0</strong>
    </div>
    <div class="kpi">
        <h4>IVA Pendiente</h4>
        <strong id="kpi-iva-pendiente">$0</strong>
    </div>
    <div class="kpi">
        <h4>Total</h4>
        <strong id="kpi-total">$0</strong>
    </div>
    <div class="kpi">
        <h4>Pagadas</h4>
        <strong id="kpi-pagadas">$0</strong>
    </div>
    <div class="kpi">
        <h4>Cantidad</h4>
        <strong id="kpi-cantidad">0</strong>
    </div>
</div>

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

<!-- OVERLAY -->
<div class="drawer-overlay" id="overlay" onclick="cerrarDrawer()"></div>

<!-- DRAWER -->
<div class="drawer" id="drawer">
    <h3>Factura #<span id="panel-id"></span></h3>

    <div class="form-group">
        <label>Proveedor</label>
        <input id="panel-proveedor">
    </div>

    <div class="form-group">
        <label>Monto</label>
        <input id="panel-monto" type="number">
    </div>

    <div class="form-group">
        <label>Estado</label>
        <select id="panel-estado">
            <option value="pendiente">Pendiente</option>
            <option value="pagada">Pagada</option>
            <option value="anulada">Anulada</option>
        </select>
    </div>

    <div class="form-group">
        <label>Glosa</label>
        <textarea id="panel-glosa"></textarea>
    </div>

    <div class="save-status" id="saveStatus"></div>
</div>

<script>

let dataGlobal = [];
let facturaActual = null;
let debounceTimer = null;

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

    const ivaTotal = data.total_monto * 0.19;
const ivaPagado = data.pagada.monto * 0.19;
const ivaPendiente = ivaTotal - ivaPagado;

document.getElementById('kpi-iva').innerText = money(ivaTotal);
document.getElementById('kpi-iva-pagado').innerText = money(ivaPagado);
document.getElementById('kpi-iva-pendiente').innerText = money(ivaPendiente);

document.getElementById('kpi-total').innerText = money(data.total_monto);
document.getElementById('kpi-pagadas').innerText = money(data.pagada.monto);
document.getElementById('kpi-cantidad').innerText = data.total_qty;

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

    document.getElementById('tabla').innerHTML =
        dataGlobal.filter(f =>
            (f.proveedor||'').toLowerCase().includes(filtro) ||
            (f.nro_factura||'').toString().includes(filtro)
        ).map(f=>`
            <tr onclick="abrirDrawer(${f.id_factura})">
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
function abrirDrawer(id){

    facturaActual = dataGlobal.find(f => f.id_factura == id);

    document.getElementById('drawer').classList.add('open');
    document.getElementById('overlay').classList.add('open');

    renderDetalle();
}

function renderDetalle(){

    document.getElementById('panel-id').textContent = facturaActual.id_factura;
    document.getElementById('panel-proveedor').value = facturaActual.proveedor || '';
    document.getElementById('panel-monto').value = facturaActual.monto || 0;
    document.getElementById('panel-estado').value = facturaActual.estado;
    document.getElementById('panel-glosa').value = facturaActual.glosa || '';
}

function cerrarDrawer(){
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
}

/* AUTO SAVE */
document.addEventListener('DOMContentLoaded', () => {

    const $ = id => document.getElementById(id);

    ['panel-proveedor','panel-monto','panel-estado','panel-glosa']
    .forEach(id => {
        $(id).addEventListener('input', autoGuardar);
    });

    $('filtro-estado').onchange = cargarDatos;
    $('filtro-periodo').onchange = cargarDatos;

    cargarDatos();
});

function autoGuardar(){

    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(async () => {
        const $ = id => document.getElementById(id);

        const payload = {
            id_factura: facturaActual.id_factura,
            proveedor: $('panel-proveedor').value,
            monto: $('panel-monto').value,
            estado: $('panel-estado').value,
            glosa: $('panel-glosa').value
        };

        document.getElementById('saveStatus').innerText = 'Guardando...';

        await fetch('/api/admin/factura_guardar.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });

        document.getElementById('saveStatus').innerText = '✔ Guardado';
        cargarDatos();

    }, 600);
}

/* EVENTOS */
const $ = id => document.getElementById(id);

$('filtro-estado').onchange = cargarDatos;
$('filtro-periodo').onchange = cargarDatos;

cargarDatos();

</script>

</body>
</html>