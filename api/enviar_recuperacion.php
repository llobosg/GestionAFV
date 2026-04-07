<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

try {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$nombre || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Nombre y correo válidos son obligatorios');
    }

    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ? AND email = ?");
    $stmt->execute([$nombre, $email]);
    $usuario = $stmt->fetch();

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->prepare("
        INSERT INTO recuperacion_password (id_usuario, token, expires_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ")->execute([$usuario ? $usuario['id_usuario'] : null, $token, $expires]);

    if ($usuario) {
        require_once __DIR__ . '/../includes/BrevoMailer.php';
        $link = APP_URL . '/public/restablecer_password.php?token=' . urlencode($token);

        $mail = new BrevoMailer();
        $mail->setTo($email, $nombre);
        $mail->setSubject('Restablece tu contraseña en NegocioUP');
        $mail->setHtmlBody("
            <h2>¿Olvidaste tu contraseña?</h2>
            <p>Hola $nombre,</p>
            <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
            <p><a href='$link' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Restablecer Contraseña</a></p>
            <p>Si no solicitaste esto, ignora este correo.</p>
            <p>Equipo Gestión AFV 🥦</p>
        ");
        $mail->send();
    }

    // ✅ REDIRECCIÓN REAL (no JSON)
    header('Location: recuperar_password.php?mensaje=' . urlencode('¡Listo! Revisa tu correo para restablecer tu contraseña.'));
    exit;

} catch (Exception $e) {
    error_log("Error en enviar_recuperacion.php: " . $e->getMessage());
    header('Location: recuperar_password.php?mensaje=' . urlencode('Si los datos son correctos, recibirás un enlace en tu correo.'));
    exit;
}
?>