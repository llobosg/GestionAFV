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
<title>Facturas — NegocioUP</title>

<style>

body {
    background:#f4f6f9;
    font-family:'Segoe UI';
}

/* CONTENEDOR */
.container{
    max-width:1200px;
    margin:2rem auto;
}

/* CARD */
.card{
    background:white;
    padding:1.5rem;
    border-radius:14px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

/* CHIPS */
.chips{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:1rem;
}

.chip{
    padding:6px 12px;
    border-radius:20px;
    background:#e0e0e0;
    cursor:pointer;
    font-size:0.85rem;
}

.chip.active{
    background:#4CAF50;
    color:white;
}

/* BOTONES */
.actions{
    display:flex;
    justify-content:space-between;
    margin-bottom:1rem;
}

.btn{
    padding:6px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

.btn-excel{
    background:#2E7D32;
    color:white;
}

/* TABLA */
table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:0.7rem;
    border-bottom:1px solid #eee;
}

th{
    background:#4CAF50;
    color:white;
}

tr:hover{
    background:#f5f5f5;
}

/* BADGES */
.badge{
    padding:4px 8px;
    border-radius:6px;
    color:white;
    font-size:0.75rem;
}

.pendiente{background:#FF9800;}
.pagada{background:#4CAF50;}
.anulada{background:#F44336;}

/* PAGINACION */
.pagination{
    margin-top:1rem;
    display:flex;
    gap:6px;
}

.page-btn{
    padding:5px 10px;
    border:1px solid #ccc;
    cursor:pointer;
}

.page-btn.active{
    background:#4CAF50;
    color:white;
}

</style>
</head>

<body>

<div class="container">

<div class="card">

<h2>📋 Listado de Facturas</h2>

<!-- FILTROS -->
<div class="chips" id="chips-estado">
    <div class="chip active" data-value="">Todos</div>
    <div class="chip" data-value="pendiente">Pendiente</div>
    <div class="chip" data-value="pagada">Pagada</div>
    <div class="chip" data-value="anulada">Anulada</div>
</div>

<div class="chips" id="chips-periodo">
    <div class="chip active" data-value="hoy">Hoy</div>
    <div class="chip" data-value="semana">7 días</div>
    <div class="chip" data-value="mes">Mes</div>
    <div class="chip" data-value="ytd">Año</div>
</div>

<!-- ACCIONES -->
<div class="actions">
    <button class="btn btn-excel" onclick="exportarExcel()">⬇ Exportar Excel</button>
</div>

<!-- TABLA -->
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

<div class="pagination" id="pagination"></div>

</div>

</div>

<script>

let estado = '';
let periodo = 'hoy';
let pagina = 1;
const porPagina = 10;
let dataGlobal = [];

/* =========================
   CHIPS
========================= */

document.querySelectorAll('#chips-estado .chip').forEach(c=>{
    c.onclick=()=>{
        document.querySelectorAll('#chips-estado .chip').forEach(x=>x.classList.remove('active'));
        c.classList.add('active');
        estado = c.dataset.value;
        pagina = 1;
        cargarDatos();
    }
});

document.querySelectorAll('#chips-periodo .chip').forEach(c=>{
    c.onclick=()=>{
        document.querySelectorAll('#chips-periodo .chip').forEach(x=>x.classList.remove('active'));
        c.classList.add('active');
        periodo = c.dataset.value;
        pagina = 1;
        cargarDatos();
    }
});

/* =========================
   DATA
========================= */

async function cargarDatos(){

    const params = new URLSearchParams();
    if(estado) params.append('estado', estado);
    params.append('periodo', periodo);

    const res = await fetch('/api/admin/facturas_estadisticas.php?'+params);
    const data = await res.json();

    dataGlobal = data.facturas;
    renderTabla();
}

/* =========================
   TABLA + PAGINACION
========================= */

function renderTabla(){

    const inicio = (pagina-1)*porPagina;
    const datos = dataGlobal.slice(inicio, inicio+porPagina);

    document.getElementById('tabla').innerHTML = datos.map(f=>`
        <tr onclick="verDetalle(${f.id_factura})">
            <td>${f.fecha}</td>
            <td>${f.nro_factura || '-'}</td>
            <td>${f.proveedor}</td>
            <td>$${Number(f.monto).toLocaleString('es-CL')}</td>
            <td>$${Number(f.monto*0.19).toLocaleString('es-CL')}</td>
            <td><span class="badge ${f.estado}">${f.estado}</span></td>
        </tr>
    `).join('');

    renderPagination();
}

function renderPagination(){

    const totalPaginas = Math.ceil(dataGlobal.length / porPagina);
    let html = '';

    for(let i=1;i<=totalPaginas;i++){
        html += `<div class="page-btn ${i===pagina?'active':''}" onclick="irPagina(${i})">${i}</div>`;
    }

    document.getElementById('pagination').innerHTML = html;
}

function irPagina(p){
    pagina = p;
    renderTabla();
}

/* =========================
   EXPORTAR EXCEL
========================= */

function exportarExcel(){
    const params = new URLSearchParams();
    if(estado) params.append('estado', estado);
    params.append('periodo', periodo);

    window.open('/api/admin/exportar_facturas.php?'+params);
}

/* =========================
   DETALLE (HOOK)
========================= */

function verDetalle(id){
    alert('Abrir detalle factura #' + id);
}

/* INIT */
cargarDatos();

</script>

</body>
</html>