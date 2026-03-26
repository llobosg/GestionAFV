<?php
require_once __DIR__ . '/../../includes/config.php';

if ($_SESSION['rol'] !== 'admin') {
    header('Location: /public/home.php');
    exit;
}

$id_negocio = $_SESSION['id_negocio'] ?? 1;
$nombre_negocio = $_SESSION['nombre_negocio'] ?? 'Negocio';
$nombre = $_SESSION['nombre_usuario'] ?? 'Admin';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<title>Dashboard Facturas — NegocioUP</title>

<style>

body {
    background: #f4f6f9;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
}

/* TOP BAR */
.top-bar {
    background: linear-gradient(135deg, #4CAF50, #2E7D32);
    color: white;
    padding: 0.8rem 2rem;
    display: flex;
    justify-content: space-between;
}

/* CONTENEDOR */
.container {
    max-width: 1300px;
    margin: 2rem auto;
}

/* KPI */
.kpi-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.kpi {
    flex: 1;
    background: white;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.07);
}

.kpi h4 {
    margin: 0;
    font-size: 0.9rem;
    color: #777;
}

.kpi strong {
    font-size: 1.5rem;
}

/* DASHBOARD */
.dashboard-top {
    display: flex;
    gap: 1.5rem;
    height: 45vh;
}

.card {
    flex: 1;
    background: white;
    padding: 1.2rem;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
}

.chart-container {
    flex: 1;
}

/* FILTROS */
.filtros {
    margin-bottom: 1rem;
}

select, button {
    padding: 0.4rem;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.btn-limpiar {
    background: #ff9800;
    color: white;
    border: none;
}

/* TABLA */
.dashboard-bottom {
    margin-top: 2rem;
    background: white;
    padding: 1.5rem;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 0.7rem;
    border-bottom: 1px solid #eee;
}

th {
    background: #4CAF50;
    color: white;
}

tr:hover {
    background: #f1f1f1;
}

/* BADGES */
.badge {
    padding: 4px 8px;
    border-radius: 6px;
    color: white;
    font-size: 0.75rem;
}

.pendiente { background: #FF9800; }
.pagada { background: #4CAF50; }
.anulada { background: #F44336; }

</style>
</head>

<body>

<div class="top-bar">
    <span>← Home</span>
    <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
    <span><?= htmlspecialchars($nombre) ?></span>
</div>

<div class="container">

<h2>📊 Dashboard de Facturas</h2>

<!-- KPIs -->
<div class="kpi-row">
    <div class="kpi"><h4>Total</h4><strong id="kpi-total">$0</strong></div>
    <div class="kpi"><h4>IVA</h4><strong id="kpi-iva">$0</strong></div>
    <div class="kpi"><h4>Cantidad</h4><strong id="kpi-cantidad">0</strong></div>
    <div class="kpi"><h4>Pendiente</h4><strong id="kpi-pendiente">$0</strong></div>
</div>

<!-- GRÁFICOS -->
<div class="dashboard-top">

    <div class="card">
        <h3>Resumen por Estado</h3>
        <div class="chart-container">
            <canvas id="chartEstado"></canvas>
        </div>
    </div>

    <div class="card">
        <h3>Facturación Mensual</h3>
        <div class="chart-container">
            <canvas id="chartMensual"></canvas>
        </div>
    </div>

</div>

<!-- TABLA -->
<div class="dashboard-bottom">
    <h3>Listado de Facturas</h3>
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
    return '$' + Number(v).toLocaleString('es-CL');
}

async function cargarDatos(){

    const res = await fetch('/api/admin/facturas_estadisticas.php');
    const data = await res.json();

    /* KPI */
    document.getElementById('kpi-total').innerText = money(data.total_monto);
    document.getElementById('kpi-iva').innerText = money(data.total_iva);
    document.getElementById('kpi-cantidad').innerText = data.total_qty;
    document.getElementById('kpi-pendiente').innerText = money(data.pendiente.monto);

    /* GRAFICO ESTADO */
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
        },
        options:{
            plugins:{
                tooltip:{
                    callbacks:{
                        label:(ctx)=> ' $' + ctx.raw.toLocaleString('es-CL')
                    }
                },
                legend:{display:false}
            }
        }
    });

    /* GRAFICO MENSUAL */
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
        },
        options:{
            interaction:{mode:'index'},
            plugins:{
                tooltip:{
                    callbacks:{
                        label:(ctx)=> ctx.dataset.label+': '+money(ctx.raw)
                    }
                }
            }
        }
    });

    /* TABLA */
    document.getElementById('tabla').innerHTML =
        data.facturas.map(f=>`
            <tr>
                <td>${f.fecha}</td>
                <td>${f.nro_factura || '-'}</td>
                <td>${f.proveedor}</td>
                <td>${money(f.monto)}</td>
                <td>${money(f.monto*0.19)}</td>
                <td><span class="badge ${f.estado}">${f.estado}</span></td>
            </tr>
        `).join('');
}

cargarDatos();

</script>

</body>
</html>