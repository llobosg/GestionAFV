<?php
// 1. Desactivar TODO lo que pueda imprimir
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '0');

// 2. Si ya hay sesión, no iniciar otra
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Forzar contenido JSON
header('Content-Type: application/json');

// 4. Función de salida segura
function responder($data) {
    // Limpiar cualquier buffer residual
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Asegurar que no haya espacios ni saltos antes del JSON
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // Salir inmediatamente → evita que FrankenPHP añada logs
    exit;
}

// 5. Cargar configuración
require_once __DIR__ . '/../../includes/config.php';

// 6. Validar sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    responder(['success' => false, 'message' => 'Acceso denegado']);
}

// 7. Leer datos
$input = json_decode(file_get_contents('php://input'), true);

$id_promo = (int)($input['id_promo'] ?? 0);
$nombre = trim($input['nombre'] ?? '');
$precio_promo = (float)($input['precio_promo'] ?? 0);
$activo = !empty($input['activo']) && $input['activo'] !== '0';

if (!$id_promo || !$nombre || $precio_promo <= 0) {
    responder(['success' => false, 'message' => 'Datos incompletos']);
}

try {
    // Verificar pertenencia al negocio
    $stmt = $pdo->prepare("
        SELECT 1 FROM productos_promo pp
        JOIN productos p ON pp.id_producto_base = p.id_producto
        WHERE pp.id_promo = ? AND p.id_negocio = ?
    ");
    $stmt->execute([$id_promo, $_SESSION['id_negocio']]);
    
    if (!$stmt->fetch()) {
        responder(['success' => false, 'message' => 'Promoción no autorizada']);
    }

    // Actualizar
    $update = $pdo->prepare("
        UPDATE productos_promo 
        SET nombre = ?, precio_promo = ?, activo = ?
        WHERE id_promo = ?
    ");
    $update->execute([$nombre, $precio_promo, $activo, $id_promo]);

    responder(['success' => true]);

} catch (Exception $e) {
    error_log("PROMO_ERROR: " . $e->getMessage());
    responder(['success' => false, 'message' => 'Error interno']);
}
?>