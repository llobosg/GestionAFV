<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

use TCPDF;

if (!in_array($_SESSION['rol'], ['cajera', 'admin'])) {
    http_response_code(403);
    exit;
}

$id_venta = $_GET['id_venta'] ?? 0;
if (!$id_venta) {
    http_response_code(400);
    exit;
}

// Obtener venta
$stmt = $pdo->prepare("
    SELECT v.*, u.nombre as cajero, n.nombre as nombre_negocio
    FROM ventas v
    JOIN usuarios u ON v.id_cajera = u.id_usuario
    JOIN negocios n ON v.id_negocio = n.id_negocio
    WHERE v.id_venta = ? AND v.id_negocio = ?
");
$stmt->execute([$id_venta, $_SESSION['id_negocio']]);
$venta = $stmt->fetch();

if (!$venta) {
    http_response_code(404);
    exit;
}

// Obtener detalles
$stmt = $pdo->prepare("
    SELECT p.producto, vd.cantidad, vd.precio_unitario, vd.subtotal
    FROM ventas_detalle vd
    JOIN productos p ON vd.id_producto = p.id_producto
    WHERE vd.id_venta = ?
    ORDER BY p.producto
");
$stmt->execute([$id_venta]);
$detalles = $stmt->fetchAll();

// === Generar PDF ===
$pdf = new TCPDF('P', 'mm', [80, 150], true, 'UTF-8', false);
$pdf->SetMargins(2, 2, 2); // márgenes mínimos
$pdf->SetAutoPageBreak(true, 2);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 9);

// --- HEADER: Nombre del negocio ---
$pdf->SetFont('', 'B', 12);
$pdf->Cell(0, 6, htmlspecialchars($venta['nombre_negocio']), 0, 1, 'C');
$pdf->Ln(1);

// Fecha y cajero
$pdf->SetFont('', '', 8);
$pdf->Cell(0, 4, date('d/m/Y H:i', strtotime($venta['fecha'] . ' ' . $venta['hora'])), 0, 1, 'C');
$pdf->Cell(0, 4, "Cajero: " . htmlspecialchars($venta['cajero']), 0, 1, 'C');
$pdf->Ln(2);

// --- Separador completo ---
$pdf->Cell(0, 2, str_repeat('=', 50), 0, 1, 'C');
$pdf->Ln(1);

// Cabecera tabla
$pdf->SetFont('', 'B', 8);
$pdf->Cell(30, 4, 'Producto', 0, 0);
$pdf->Cell(10, 4, 'Cant', 0, 0, 'R');
$pdf->Cell(15, 4, 'P.Unit', 0, 0, 'R');
$pdf->Cell(15, 4, 'TOTAL', 0, 1, 'R');

$pdf->SetFont('', '', 8);
foreach ($detalles as $d) {
    $pdf->Cell(30, 4, substr(htmlspecialchars($d['producto']), 0, 20), 0, 0);
    $pdf->Cell(10, 4, number_format($d['cantidad'], 2), 0, 0, 'R');
    $pdf->Cell(15, 4, '$' . number_format($d['precio_unitario'], 0), 0, 0, 'R');
    $pdf->Cell(15, 4, '$' . number_format($d['subtotal'], 0), 0, 1, 'R');
}

$pdf->Ln(1);
$pdf->Cell(0, 0, str_repeat('=', 50), 0, 1, 'C');
$pdf->Ln(2);

// --- TOTALES: izquierda/derecha ---
$neto = $venta['total'] / 1.19;
$iva = $venta['total'] - $neto;

$pdf->SetFont('', 'B', 9);
// NETO
$pdf->Cell(35, 4, 'NETO:', 0, 0, 'L');
$pdf->Cell(28, 4, '$' . number_format($neto, 0), 0, 1, 'R');

// IVA
$pdf->Cell(35, 4, 'IVA (19%):', 0, 0, 'L');
$pdf->Cell(28, 4, '$' . number_format($iva, 0), 0, 1, 'R');

// TOTAL
$pdf->Cell(35, 4, 'TOTAL:', 0, 0, 'L');
$pdf->Cell(28, 4, '$' . number_format($venta['total'], 0), 0, 1, 'R');

$pdf->Ln(2);

// Medio de pago
$metodo = match($venta['metodo_pago']) {
    'efectivo' => 'Efectivo',
    'tarjeta' => 'Tarjeta',
    'merma' => 'Merma',
    default => ucfirst($venta['metodo_pago'])
};
$pdf->SetFont('', '', 8);
$pdf->Cell(0, 4, "Medio de pago: $metodo", 0, 1, 'L');

$pdf->Ln(3);

// --- Código de barras lineal (Code 128) ---
$codigo = sprintf('%08d', $venta['id_venta']); // Ej: 00012345
$pdf->write1DBarcode($codigo, 'C128A', '', '', 60, 12, 0.4, ['position'=>'C']);

$pdf->Ln(15);

// Footer
$pdf->SetFont('', 'I', 7);
$pdf->Cell(0, 4, 'powered by NegocioUP', 0, 1, 'C');

$pdf->Output("ticket_{$venta['id_venta']}.pdf", 'D');
exit;
?>