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

    // === ENVIAR CORREO REAL CON BREVO ===
    require_once __DIR__ . '/../includes/BrevoMailer.php';

    $link = APP_URL . '/public/restablecer_password.php?token=' . urlencode($token);

    error_log("🔑 BREVO_API_KEY cargada: " . (defined('BREVO_API_KEY') ? (strlen(BREVO_API_KEY) > 10 ? substr(BREVO_API_KEY, 0, 10) . '...' : 'vacía') : 'NO DEFINIDA'));

    $mailer = new BrevoMailer();
    $mailer->setTo($email, $nombre)
           ->setSubject('Restablece tu contraseña en Gestión AFV')
           ->setHtmlBody("
               <h2>¿Olvidaste tu contraseña?</h2>
               <p>Hola $nombre,</p>
               <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
               <p><a href='$link' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Restablecer Contraseña</a></p>
               <p>Si no solicitaste esto, ignora este correo.</p>
               <p>Equipo Gestión AFV 🥦</p>
           ")
           ->send();

           error_log("📧 Intentando enviar correo a: " . $email);
    error_log("📧 DEBUG: Enviando correo a $email | Token: $token | Link: $link");
    echo json_encode(['redirect' => 'recuperar_password.php?mensaje=¡Listo! Revisa tu correo para restablecer tu contraseña.']);

} catch (Exception $e) {
    error_log("Error en enviar_recuperacion.php: " . $e->getMessage());
    error_log("❌ Error al enviar correo: " . $e->getMessage());
    // No revelar detalles internos
    echo json_encode(['redirect' => 'recuperar_password.php?mensaje=Si los datos son correctos, recibirás un enlace en tu correo.']);
}
?>