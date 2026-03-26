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
    body { background: #f9fbe7; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; }
    .top-bar {
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      color: white;
      padding: 0.8rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    .superior {
      display: flex;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    .superior-izq, .superior-der {
      flex: 1;
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .filtros {
      margin-bottom: 1.5rem;
    }
    .filtro-row {
      display: flex;
      gap: 1rem;
      margin-bottom: 0.8rem;
    }
    select, button {
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    .btn-limpiar {
      background: #ff9800;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
    }
    .grafico {
      margin-top: 1.5rem;
    }
    .barras-container {
      display: flex;
      height: 160px;
      align-items: flex-end;
      gap: 0.8rem;
      padding-top: 1rem;
    }
    .barra-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .barra-fill {
      width: 100%;
      background: #4CAF50;
      border-radius: 4px 4px 0 0;
    }
    .barra-label {
      margin-top: 0.4rem;
      font-size: 0.8rem;
      text-align: center;
    }
    /* Colores */
    .pendiente { background: #FF9800; }
    .pagada { background: #4CAF50; }
    .anulada { background: #F44336; }
    .qty { background: #2196F3; }
    .monto { background: #9C27B0; }
    .iva { background: #FF5722; }

    /* Gráfico mensual */
    .barras-mensuales {
      display: flex;
      flex-wrap: wrap;
      height: 200px;
      align-items: flex-end;
      gap: 0.3rem;
      padding-top: 1rem;
    }
    .mes-item {
      width: 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .mes-barra {
      width: 100%;
      border-radius: 2px 2px 0 0;
    }
    .mes-label {
      font-size: 0.7rem;
      margin-top: 0.3rem;
    }

    /* Tabla inferior */
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 0.7rem; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #4CAF50; color: white; }
    .acciones-btn {
      background: none; border: none; cursor: pointer; font-size: 1.1rem;
    }
  </style>
</head>
<body>

  <div class="top-bar">
    <a href="/public/home.php" style="color:white; text-decoration:none;">← Home</a>
    <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
    <span><?= htmlspecialchars($nombre) ?></span>
  </div>

  <div class="container">
    <h2>🧾 Control de Facturas</h2>

    <!-- SUPERIOR -->
    <div class="superior">
      <!-- SUPERIOR IZQUIERDA -->
      <div class="superior-izq">
        <div class="filtros">
          <div class="filtro-row">
            <select id="filtro-estado">
              <option value="">Todos los estados</option>
              <option value="pendiente">Pendiente</option>
              <option value="pagada">Pagada</option>
              <option value="anulada">Anulada</option>
            </select>
            <button class="btn-limpiar" onclick="limpiarFiltros()">🧹 Limpiar</button>
          </div>
          <div class="filtro-row">
            <select id="filtro-periodo">
              <option value="hoy">Hoy</option>
              <option value="semana">Últimos 7 días</option>
              <option value="mes">Mes actual</option>
              <option value="ytd">YTD (Año)</option>
              <option value="meses">Meses anteriores</option>
            </select>
          </div>
          <div id="contenedor-meses" style="display:none;">
            <select id="filtro-mes-anterior">
              <?php
                $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                foreach ($meses as $i => $m) {
                  echo "<option value='" . ($i+1) . "'>$m</option>";
                }
              ?>
            </select>
          </div>
        </div>

        <div class="grafico">
          <h3>📊 Resumen por Estado</h3>
          <canvas id="chartEstado"></canvas>
            <!-- Se llenará -->
          </div>
        </div>
      </div>

      <!-- SUPERIOR DERECHA -->
      <div class="superior-der">
        <h3>📅 Facturas por Mes (Valor e IVA)</h3>
        <canvas id="chartMensual"></canvas>
          <!-- 12 meses x 2 barras -->
        </div>
      </div>
    </div>

    <!-- INFERIOR -->
    <div class="inferior">
      <h3>📋 Listado de Facturas</h3>
      <table id="tabla-facturas">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>N° Factura</th>
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
function calcularAltura(valor, max) {
  if (!max || max <= 0) return '5%'; // 👈 evita barras invisibles
  const pct = (valor / max) * 100;
  return Math.max(pct, 5) + '%';
}

function formatearMoneda(v) {
  return '$' + Number(v || 0).toLocaleString('es-CL');
}

let chartEstado = null;
let chartMensual = null;

async function cargarDatos() {
  try {

    const estado = document.getElementById('filtro-estado').value;
    const periodo = document.getElementById('filtro-periodo').value;
    let mes = null;

    if (periodo === 'meses') {
      mes = document.getElementById('filtro-mes-anterior').value;
    }

    const params = new URLSearchParams();
    if (estado) params.append('estado', estado);
    params.append('periodo', periodo);
    if (mes) params.append('mes', mes);

    const res = await fetch(`/api/admin/facturas_estadisticas.php?${params}`);
    const data = await res.json();

    console.log("DATA:", data);

    /* =========================
       🔹 GRAFICO ESTADO
    ========================= */

    const ctxEstado = document.getElementById('chartEstado').getContext('2d');

    if (chartEstado) chartEstado.destroy();

    chartEstado = new Chart(ctxEstado, {
      type: 'bar',
      data: {
        labels: ['Pendiente', 'Pagada', 'Anulada', 'Cantidad', 'Total', 'IVA'],
        datasets: [{
          label: 'Resumen',
          data: [
            data.pendiente.monto,
            data.pagada.monto,
            data.anulada.monto,
            data.total_qty,
            data.total_monto,
            data.total_iva
          ],
          backgroundColor: [
            '#FF9800',
            '#4CAF50',
            '#F44336',
            '#2196F3',
            '#9C27B0',
            '#FF5722'
          ],
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                let value = context.raw;
                if (context.dataIndex === 3) {
                  return 'Cantidad: ' + value;
                }
                return 'Monto: $' + value.toLocaleString('es-CL');
              }
            }
          },
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    /* =========================
       🔹 GRAFICO MENSUAL
    ========================= */

    const ctxMensual = document.getElementById('chartMensual').getContext('2d');

    if (chartMensual) chartMensual.destroy();

    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    chartMensual = new Chart(ctxMensual, {
      type: 'bar',
      data: {
        labels: meses,
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
      },
      options: {
        responsive: true,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': $' + context.raw.toLocaleString('es-CL');
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    /* =========================
       🔹 TABLA
    ========================= */

    const tbody = document.querySelector('#tabla-facturas tbody');

    tbody.innerHTML = data.facturas.map(f => `
      <tr>
        <td>${f.fecha}</td>
        <td>${f.nro_factura || '-'}</td>
        <td>${f.proveedor}</td>
        <td>$${Number(f.monto).toLocaleString('es-CL')}</td>
        <td>$${Number(f.monto * 0.19).toLocaleString('es-CL')}</td>
        <td>${f.estado}</td>
        <td><button class="acciones-btn">✏️</button></td>
      </tr>
    `).join('');

  } catch (err) {
    console.error("ERROR:", err);
  }
}
</script>
</body>
</html>