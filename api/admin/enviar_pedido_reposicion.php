<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

// Obtener productos críticos
$stmt = $pdo->prepare("SELECT producto, stock_actual, stock_critico FROM productos WHERE id_negocio = ? AND stock_actual <= stock_critico");
$stmt->execute([$_SESSION['id_negocio']]);
$productos = $stmt->fetchAll();

if (empty($productos)) {
    echo json_encode(['success' => false, 'message' => 'No hay productos críticos']);
    exit;
}

// Generar HTML del correo
$html = "<h2>Pedido de Reposición – " . htmlspecialchars($_SESSION['nombre_negocio']) . "</h2>";
$html .= "<p>Los siguientes productos requieren reposición inmediata:</p>";
$html .= "<table border='1' style='border-collapse:collapse;'>";
$html .= "<tr><th>Producto</th><th>Stock Actual</th><th>Stock Crítico</th></tr>";
foreach ($productos as $p) {
    $html .= "<tr><td>{$p['producto']}</td><td>{$p['stock_actual']}</td><td>{$p['stock_critico']}</td></tr>";
}
$html .= "</table>";

// Enviar correo (reutiliza BrevoMailer)
require_once __DIR__ . '/../../includes/BrevoMailer.php';
$mailer = new BrevoMailer();
$mailer->setTo($_SESSION['email'] ?? 'admin@negocioup.com', $_SESSION['nombre_usuario'])
       ->setSubject('📦 Pedido de Reposición – ' . $_SESSION['nombre_negocio'])
       ->setHtmlBody($html)
       ->send();

echo json_encode(['success' => true]);
?>