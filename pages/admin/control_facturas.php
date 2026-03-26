<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';
$nombre = $_SESSION['nombre_usuario'] ?? 'Admin';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<title>Dashboard Facturas</title>

<style>

/* ===== BASE ===== */
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f4f6f9;
}

/* ===== TOP BAR ===== */
.top-bar {
    background: linear-gradient(135deg,#4CAF50,#2E7D32);
    color: white;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
}

/* ===== CONTAINER ===== */
.container {
    max-width: 1300px;
    margin: 2rem auto;
}

/* ===== KPI ===== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.kpi {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
}

.kpi h4 {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.kpi span {
    font-size: 1.4rem;
    font-weight: bold;
}

/* ===== GRID ===== */
.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

/* ===== CARD ===== */
.card {
    background: white;
    border-radius: 12px;
    padding: 1.2rem;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}

.chart-container {
    height: 250px;
}

/* ===== TABLA ===== */
.table-card {
    margin-top: 1.5rem;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #4CAF50;
    color: white;
    padding: 0.6rem;
}

td {
    padding: 0.6rem;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #f5f5f5;
}

/* ===== BADGES ===== */
.badge {
    padding: 4px 8px;
    border-radius: 6px;
    color: white;
    font-size: 0.75rem;
}

.pendiente { background:#FF9800; }
.pagada { background:#4CAF50; }
.anulada { background:#F44336; }

/* ===== FILTROS ===== */
.filtros {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

select {
    padding: 0.4rem;
}

</style>
</head>

<body>

<div class="top-bar">
    <div><?= $nombre_negocio ?></div>
    <div><?= $nombre ?></div>
</div>

<div class="container">

    <!-- KPIs -->
    <div class="kpis">
        <div class="kpi"><h4>Total</h4><span id="kpiTotal">$0</span></div>
        <div class="kpi"><h4>IVA</h4><span id="kpiIVA">$0</span></div>
        <div class="kpi"><h4>Cantidad</h4><span id="kpiQty">0</span></div>
        <div class="kpi"><h4>Pendiente</h4><span id="kpiPendiente">$0</span></div>
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
    </div>

    <!-- GRID -->
    <div class="grid">

        <div class="card">
            <h3>Estado</h3>
            <div class="chart-container">
                <canvas id="chartEstado"></canvas>
            </div>
        </div>

        <div class="card">
            <h3>Mensual</h3>
            <div class="chart-container">
                <canvas id="chartMensual"></canvas>
            </div>
        </div>

    </div>

    <!-- TABLA -->
    <div class="card table-card">
        <h3>Facturas</h3>

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

<script>

let chartEstado, chartMensual;

function money(v){
    return '$'+Number(v).toLocaleString('es-CL');
}

async function cargar(){

    const estado = document.getElementById('filtro-estado').value;
    const periodo = document.getElementById('filtro-periodo').value;

    const res = await fetch(`/api/admin/facturas_estadisticas.php?estado=${estado}&periodo=${periodo}`);
    const data = await res.json();

    /* KPIs */
    document.getElementById('kpiTotal').innerText = money(data.total_monto);
    document.getElementById('kpiIVA').innerText = money(data.total_iva);
    document.getElementById('kpiQty').innerText = data.total_qty;
    document.getElementById('kpiPendiente').innerText = money(data.pendiente.monto);

    /* CHART ESTADO */
    if(chartEstado) chartEstado.destroy();

    chartEstado = new Chart(chartEstado = document.getElementById('chartEstado'), {
        type:'bar',
        data:{
            labels:['Pendiente','Pagada','Anulada'],
            datasets:[{
                data:[
                    data.pendiente.monto,
                    data.pagada.monto,
                    data.anulada.monto
                ],
                backgroundColor:['#FF9800','#4CAF50','#F44336']
            }]
        }
    });

    /* CHART MENSUAL */
    if(chartMensual) chartMensual.destroy();

    chartMensual = new Chart(document.getElementById('chartMensual'),{
        type:'bar',
        data:{
            labels:['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
            datasets:[
                {
                    label:'Monto',
                    data:data.mensual.map(m=>m.valor),
                    backgroundColor:'#4CAF50'
                },
                {
                    label:'IVA',
                    data:data.mensual.map(m=>m.iva),
                    backgroundColor:'#FF5722'
                }
            ]
        }
    });

    /* TABLA */
    document.getElementById('tabla').innerHTML =
        data.facturas.map(f=>`
            <tr>
                <td>${f.fecha}</td>
                <td>${f.nro_factura||'-'}</td>
                <td>${f.proveedor}</td>
                <td>${money(f.monto)}</td>
                <td>${money(f.monto*0.19)}</td>
                <td><span class="badge ${f.estado}">${f.estado}</span></td>
            </tr>
        `).join('');
}

/* EVENTS */
document.getElementById('filtro-estado').onchange=cargar;
document.getElementById('filtro-periodo').onchange=cargar;

document.addEventListener('DOMContentLoaded', cargar);

</script>

</body>
</html>