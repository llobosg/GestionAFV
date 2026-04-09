<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

if (!isset($_SESSION['id_negocio'])) {
    echo json_encode([]);
    exit;
}

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        id_producto,
        CONCAT(tipo, ' - ', familia, IFNULL(CONCAT(' ', subfamilia), '')) AS nombre,
        um,
        precio_venta
    FROM productos
    WHERE id_negocio = ? AND LOWER(familia) LIKE ?
    ORDER BY familia, subfamilia
    LIMIT 10
");
$stmt->execute([$_SESSION['id_negocio'], strtolower($q) . '%']);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>