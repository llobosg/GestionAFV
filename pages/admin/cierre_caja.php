<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

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
  <title>💰 Cierre de Caja — NegocioUP</title>
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
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }
    .filtros {
      display: flex;
      gap: 0.8rem;
      margin-bottom: 2rem;
    }
    .btn-filtro {
      padding: 0.5rem 1rem;
      border: 1px solid #ccc;
      background: white;
      border-radius: 6px;
      cursor: pointer;
    }
    .btn-filtro.active {
      background: #4CAF50;
      color: white;
      border-color: #4CAF50;
    }
    .graficos {
      display: flex;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    .grafico {
      flex: 1;
      background: white;
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .titulo-grafico {
      margin-top: 0;
      color: #2E7D32;
      font-size: 1.2rem;
    }
    .barras-container {
      display: flex;
      height: 180px;
      align-items: flex-end;
      gap: 1rem;
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
      transition: height 0.4s ease;
    }
    .barra-label {
      margin-top: 0.5rem;
      font-size: 0.85rem;
      text-align: center;
    }
    /* Colores específicos */
    .ventas { background: #4CAF50; }
    .costo { background: #FF9800; }
    .mermas { background: #F44336; }
    .saldo { background: #2196F3; }
    .efectivo { background: #8BC34A; }
    .tarjeta { background: #9C27B0; }

    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 0.7rem; text-align: left; border-bottom: 1px solid #eee; }
    th { background: #4CAF50; color: white; }
  </style>
</head>
<body>

  <div class="top-bar">
    <a href="/public/home.php" style="color:white; text-decoration:none;">← Home</a>
    <strong><?= htmlspecialchars($nombre_negocio) ?></strong>
    <span><?= htmlspecialchars($nombre) ?></span>
  </div>

  <div class="container">
    <h2>💰 Cierre de Caja</h2>

    <!-- Filtros -->
    <div class="filtros">
      <button class="btn-filtro active" data-periodo="dia">Hoy</button>
      <button class="btn-filtro" data-periodo="semana">Últimos 7 días</button>
      <button class="btn-filtro" data-periodo="mes">Mes actual</button>
      <button class="btn-filtro" data-periodo="ytd">YTD (Año)</button>
    </div>

    <!-- Gráficos -->
    <div class="graficos">
      <!-- Gráfico 1: Ventas, Costo, Mermas, Saldo -->
      <div class="grafico">
        <h3 class="titulo-grafico">📊 Resumen Financiero</h3>
        <div class="barras-container" id="grafico-resumen">
          <!-- Se llenará dinámicamente -->
        </div>
      </div>

      <!-- Gráfico 2: Efectivo vs Tarjeta -->
      <div class="grafico">
        <h3 class="titulo-grafico">💳 Ventas por Medio de Pago</h3>
        <div class="barras-container" id="grafico-pago">
          <!-- Se llenará dinámicamente -->
        </div>
      </div>
    </div>

    <!-- Tabla de ventas por día -->
    <h3>Ventas por Día</h3>
    <table id="tabla-ventas-dia">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Efectivo</th>
          <th>Tarjeta</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <script>
    let periodoActual = 'dia';

    function formatearMoneda(valor) {
      return '$' + parseFloat(valor).toLocaleString('es-CL', { minimumFractionDigits: 0 });
    }

    function cargarEstadisticas() {
      fetch(`/api/admin/estadisticas_caja.php?periodo=${periodoActual}`)
        .then(res => res.json())
        .then(data => {
          const maxResumen = Math.max(data.ventas, data.costo, data.mermas, data.saldo, 1);
          const maxPago = Math.max(data.efectivo, data.tarjeta, 1);

          // Gráfico 1: Resumen
          const resumenHtml = `
            <div class="barra-item">
              <div class="barra-fill ventas" style="height:${calcularAltura(data.ventas, maxResumen)};"></div>
              <div class="barra-label">${formatearMoneda(data.ventas)}<br>Ventas</div>
            </div>
            <div class="barra-item">
              <div class="barra-fill costo" style="height:${calcularAltura(data.costo, maxResumen)};"></div>
              <div class="barra-label">${formatearMoneda(data.costo)}<br>Costo</div>
            </div>
            <div class="barra-item">
              <div class="barra-fill mermas" style="height:${calcularAltura(data.mermas, maxResumen)};"></div>
              <div class="barra-label">${formatearMoneda(data.mermas)}<br>Mermas</div>
            </div>
            <div class="barra-item">
              <div class="barra-fill saldo" style="height:${calcularAltura(data.saldo, maxResumen)};"></div>
              <div class="barra-label">${formatearMoneda(data.saldo)}<br>Saldo</div>
            </div>
          `;
          document.getElementById('grafico-resumen').innerHTML = resumenHtml;

          // Gráfico 2: Medio de pago
          const pagoHtml = `
            <div class="barra-item">
              <div class="barra-fill efectivo" style="height:${calcularAltura(data.efectivo, maxPago)};"></div>
              <div class="barra-label">${formatearMoneda(data.efectivo)}<br>Efectivo</div>
            </div>
            <div class="barra-item">
              <div class="barra-fill tarjeta" style="height:${calcularAltura(data.tarjeta, maxPago)};"></div>
              <div class="barra-label">${formatearMoneda(data.tarjeta)}<br>Tarjeta</div>
            </div>
          `;
          document.getElementById('grafico-pago').innerHTML = pagoHtml;

          // Tabla por día
          const tbody = document.querySelector('#tabla-ventas-dia tbody');
          tbody.innerHTML = data.ventas_por_dia.map(d => `
            <tr>
              <td>${d.fecha}</td>
              <td>${formatearMoneda(d.efectivo)}</td>
              <td>${formatearMoneda(d.tarjeta)}</td>
              <td>${formatearMoneda(d.total)}</td>
            </tr>
          `).join('');
        });
    }

    document.querySelectorAll('.btn-filtro').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        periodoActual = btn.dataset.periodo;
        cargarEstadisticas();
      });
    });

    document.addEventListener('DOMContentLoaded', cargarEstadisticas);

    function calcularAltura(valor, max) {
      if (max === 0) return '0%';
      const pct = (valor / max) * 100;
      return Math.max(pct, 5) + '%'; // mínimo 5% para visibilidad
    }
  </script>
</body>
</html>