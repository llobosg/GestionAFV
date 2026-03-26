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

<title>🧾 Control de Facturas — NegocioUP</title>

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
    align-items: center;
}

/* CONTENEDOR GENERAL */
.container {
    max-width: 1300px;
    margin: 2rem auto;
}

/* SECCIONES */
.dashboard-top {
    display: flex;
    gap: 2rem;
    height: 45vh;
    margin-bottom: 2rem;
}

.dashboard-bottom {
    background: white;
    padding: 1.5rem;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
}

/* TARJETAS */
.card {
    flex: 1;
    background: white;
    padding: 1.5rem;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
}

/* FILTROS */
.filtros {
    margin-bottom: 1rem;
}

.filtro-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

select, button {
    padding: 0.5rem;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.btn-limpiar {
    background: #ff9800;
    color: white;
    border: none;
}

/* CANVAS */
.chart-container {
    flex: 1;
    position: relative;
}

/* TABLA */
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

</style>
</head>

<body>

<div class="top-bar">
    <a href="/public/home.php" style="color:white;">← Home</a>
    <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
    <span><?= htmlspecialchars($nombre) ?></span>
</div>

<div class="container">

    <h2>🧾 Dashboard de Facturas</h2>

    <!-- 🔹 SECCIÓN SUPERIOR -->
    <div class="dashboard-top">

        <!-- IZQUIERDA -->
        <div class="card">

            <div class="filtros">
                <div class="filtro-row">
                    <select id="filtro-estado">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="pagada">Pagada</option>
                        <option value="anulada">Anulada</option>
                    </select>

                    <button class="btn-limpiar" onclick="limpiarFiltros()">Limpiar</button>
                </div>

                <div class="filtro-row">
                    <select id="filtro-periodo">
                        <option value="hoy">Hoy</option>
                        <option value="semana">7 días</option>
                        <option value="mes">Mes</option>
                        <option value="ytd">Año</option>
                    </select>
                </div>
            </div>
            <h3>📊 Resumen</h3>
            <div class="chart-container">
                <canvas id="chartEstado"></canvas>
            </div>
        </div>

        <!-- DERECHA -->
        <div class="card">
            <h3>📅 Facturación mensual</h3>
            <div class="chart-container">
                <canvas id="chartMensual"></canvas>
            </div>
        </div>
    </div>

    <!-- 🔹 SECCIÓN INFERIOR -->
    <div class="dashboard-bottom">
        <h3>📋 Listado de Facturas</h3>
        <table id="tabla-facturas">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>N°</th>
                    <th>Proveedor</th>
                    <th>Monto</th>
                    <th>IVA</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>

let chartEstado = null;
let chartMensual = null;

function formatearMoneda(v) {
      return '$' + parseFloat(v).toLocaleString('es-CL', { minimumFractionDigits: 0 });
    }

    document.getElementById('filtro-periodo').addEventListener('change', function() {
      const cont = document.getElementById('contenedor-meses');
      cont.style.display = this.value === 'meses' ? 'block' : 'none';
      cargarDatos();
    });

function limpiarFiltros() {
    document.getElementById('filtro-estado').value = '';
    document.getElementById('filtro-periodo').value = 'hoy';
    cargarDatos();
}

async function cargarDatos() {

    const estado = document.getElementById('filtro-estado').value;
    const periodo = document.getElementById('filtro-periodo').value;

    const params = new URLSearchParams();
    if (estado) params.append('estado', estado);
    params.append('periodo', periodo);

    const res = await fetch(`/api/admin/facturas_estadisticas.php?${params}`);
    const data = await res.json();

    /* ===== GRAFICO ESTADO ===== */
    if (chartEstado) chartEstado.destroy();

    chartEstado = new Chart(document.getElementById('chartEstado'), {
        type: 'bar',
        data: {
            labels: ['Pendiente', 'Pagada', 'Anulada'],
            datasets: [{
                data: [
                    data.pendiente.monto,
                    data.pagada.monto,
                    data.anulada.monto
                ],
                backgroundColor: ['#FF9800','#4CAF50','#F44336']
            }]
        }
    });

    /* ===== GRAFICO MENSUAL ===== */
    if (chartMensual) chartMensual.destroy();

    chartMensual = new Chart(document.getElementById('chartMensual'), {
        type: 'bar',
        data: {
            labels: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
            datasets: [
                {
                    label: 'Monto',
                    data: data.mensual.map(m => m.valor),
                    backgroundColor: '#4CAF50'
                },
                {
                    label: 'IVA',
                    data: data.mensual.map(m => m.iva),
                    backgroundColor: '#FF5722'
                }
            ]
        }
    });

    /* ===== TABLA ===== */
    const tbody = document.querySelector('#tabla-facturas tbody');

    tbody.innerHTML = data.facturas.map(f => `
        <tr>
            <td>${f.fecha}</td>
            <td>${f.nro_factura || '-'}</td>
            <td>${f.proveedor}</td>
            <td>$${Number(f.monto).toLocaleString('es-CL')}</td>
            <td>$${Number(f.monto * 0.19).toLocaleString('es-CL')}</td>
            <td><button class="acciones-btn" onclick="editarFactura(${f.id_factura})">✏️</button></td>
            <td>${f.estado}</td>
        </tr>
    `).join('');
}

function editarFactura(id) {
  alert('Edición de factura #' + id + ' (próximamente)');
}

// Iniciar
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('filtro-periodo').value = 'hoy';
  cargarDatos();
});

</script>

</body>
</html>