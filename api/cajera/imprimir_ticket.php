<?php
require_once __DIR__ . '/../../vendor/autoload.php'; // TCPDF
require_once __DIR__ . '/../../includes/config.php';

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
    SELECT v.*, u.nombre as cajero 
    FROM ventas v
    JOIN usuarios u ON v.id_cajera = u.id_usuario
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
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(true, 5);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Negocio
$pdf->SetFont('', 'B', 12);
$pdf->Cell(0, 5, htmlspecialchars($venta['nombre_negocio']), 0, 1, 'C');
$pdf->Ln(2);

// Fecha y cajero
$pdf->SetFont('', '', 9);
$pdf->Cell(0, 4, date('d/m/Y H:i', strtotime($venta['fecha'] . ' ' . $venta['hora'])), 0, 1, 'C');
$pdf->Cell(0, 4, "Cajero: " . htmlspecialchars($venta['cajero']), 0, 1, 'C');
$pdf->Ln(2);

// Separador
$pdf->Cell(0, 2, str_repeat('-', 30), 0, 1, 'C');
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

$pdf->Ln(2);
$pdf->Cell(0, 2, str_repeat('-', 30), 0, 1, 'C');
$pdf->Ln(2);

// Totales
$neto = $venta['total'] / 1.19;
$iva = $venta['total'] - $neto;

$pdf->SetFont('', 'B', 9);
$pdf->Cell(40, 4, 'NETO:', 0, 0, 'R');
$pdf->Cell(20, 4, '$' . number_format($neto, 0), 0, 1, 'R');

$pdf->Cell(40, 4, 'IVA (19%):', 0, 0, 'R');
$pdf->Cell(20, 4, '$' . number_format($iva, 0), 0, 1, 'R');

$pdf->Cell(40, 4, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(20, 4, '$' . number_format($venta['total'], 0), 0, 1, 'R');

$pdf->Ln(2);

// Medio de pago
$metodo = $venta['metodo_pago'] === 'efectivo' ? 'Efectivo' : 
          ($venta['metodo_pago'] === 'tarjeta' ? 'Tarjeta' : 'Merma');
$pdf->Cell(0, 4, "Medio de pago: $metodo", 0, 1, 'L');

$pdf->Ln(3);

// Código QR (opcional)
$qrData = "Venta #{$venta['id_venta']} - {$venta['nombre_negocio']} - Total: {$venta['total']}";
$pdf->write2DBarcode($qrData, 'QRCODE,M', 15, $pdf->GetY(), 30, 30, [], 'N');

$pdf->Ln(35);

// Footer
$pdf->SetFont('', 'I', 7);
$pdf->Cell(0, 4, 'powered by NegocioUP', 0, 1, 'C');

// Salida
$pdf->Output("ticket_venta_{$venta['id_venta']}.pdf", 'D');
exit;
?>