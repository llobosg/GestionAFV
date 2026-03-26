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
          <div class="barras-container" id="grafico-estado">
            <!-- Se llenará -->
          </div>
        </div>
      </div>

      <!-- SUPERIOR DERECHA -->
      <div class="superior-der">
        <h3>📅 Facturas por Mes (Valor e IVA)</h3>
        <div class="barras-mensuales" id="grafico-mensual">
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

    // === VALIDACIÓN ===
    if (!data || !data.mensual) {
      console.error("Respuesta inválida");
      return;
    }

    // === GRAFICO ESTADO ===
    const valores = [
      data.pendiente?.monto || 0,
      data.pagada?.monto || 0,
      data.anulada?.monto || 0,
      data.total_qty || 0,
      data.total_monto || 0,
      data.total_iva || 0
    ];

    const maxEstado = Math.max(...valores, 1);

    document.getElementById('grafico-estado').innerHTML = valores.map((v, i) => {
      const clases = ['pendiente','pagada','anulada','qty','monto','iva'];
      const labels = ['Pendiente','Pagada','Anulada','Cant.','Total','IVA'];

      return `
        <div class="barra-item">
          <div class="barra-fill ${clases[i]}" style="height:${calcularAltura(v, maxEstado)};"></div>
          <div class="barra-label">
            ${i === 3 ? v : formatearMoneda(v)}<br>${labels[i]}
          </div>
        </div>
      `;
    }).join('');

    // === GRAFICO MENSUAL ===
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    const maxMensual = Math.max(
      ...data.mensual.flatMap(m => [m.valor || 0, m.iva || 0]),
      1
    );

    let htmlMensual = '';

    data.mensual.forEach((m, i) => {
      htmlMensual += `
        <div class="mes-item">
          <div class="mes-barra" style="height:${calcularAltura(m.valor || 0, maxMensual)}; background:#4CAF50;"></div>
          <div class="mes-barra" style="height:${calcularAltura(m.iva || 0, maxMensual)}; background:#FF5722; margin-top:2px;"></div>
          <div class="mes-label">${meses[i]}</div>
        </div>
      `;
    });

    document.getElementById('grafico-mensual').innerHTML = htmlMensual;

    // === TABLA ===
    const tbody = document.querySelector('#tabla-facturas tbody');

    tbody.innerHTML = (data.facturas || []).map(f => `
      <tr>
        <td>${f.fecha}</td>
        <td>${f.nro_factura || '-'}</td>
        <td>${f.proveedor}</td>
        <td>${formatearMoneda(f.monto)}</td>
        <td>${formatearMoneda(f.monto * 0.19)}</td>
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