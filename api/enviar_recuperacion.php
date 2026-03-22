<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

try {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$nombre || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Nombre y correo válidos son obligatorios');
    }

    // Verificar que el usuario exista
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ? AND email = ?");
    $stmt->execute([$nombre, $email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        // No revelar si el usuario existe o no (seguridad)
        echo json_encode(['redirect' => 'recuperar_password.php?mensaje=Si los datos son correctos, recibirás un enlace en tu correo.']);
        exit;
    }

    // Generar token de recuperación (válido 1 hora)
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->prepare("
        INSERT INTO recuperacion_password (id_usuario, token, expires_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ")->execute([$usuario['id_usuario'], $token, $expires]);

    // Enviar correo (usa Brevo u otro servicio)
    $link = "https://gestionafv.up.railway.app/restablecer_password.php?token=$token";

    // Aquí integrarías BrevoMailer (similar a CanchaSport)
    // Por ahora, simulamos el envío
    error_log("📧 Enviar correo a $email con enlace: $link");

    echo json_encode(['redirect' => 'recuperar_password.php?mensaje=Si los datos son correctos, recibirás un enlace en tu correo.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['redirect' => 'recuperar_password.php?error=' . urlencode($e->getMessage())]);
}
?>S