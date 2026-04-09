<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

$error = '';
$mensaje = '';
$token = $_GET['token'] ?? '';

if ($_POST['password'] ?? false) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // Validar token
        $stmt = $pdo->prepare("
            SELECT u.id_usuario 
            FROM recuperacion_password r
            JOIN usuarios u ON r.id_usuario = u.id_usuario
            WHERE r.token = ? AND r.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Actualizar contraseña
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?")
                ->execute([$hash, $usuario['id_usuario']]);

            // Eliminar token usado
            $pdo->prepare("DELETE FROM recuperacion_password WHERE token = ?")->execute([$token]);

            $mensaje = '✅ Tu contraseña ha sido actualizada. Ya puedes iniciar sesión.';
        } else {
            $error = 'El enlace ha expirado o es inválido.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🥦 Nueva Contraseña — Gestión AFV</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #e4edc3 100%);
      margin: 0; padding: 0;
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .container {
      background: white;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 90%;
      text-align: center;
    }
    .logo { font-size: 2.5rem; margin-bottom: 1rem; color: #4CAF50; }
    h1 { color: #2E7D32; margin-bottom: 1.5rem; }
    .form-group { margin-bottom: 1.2rem; text-align: left; }
    .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; }
    .form-group input {
      width: 100%; padding: 0.7rem;
      border: 1px solid #ccc; border-radius: 8px;
      font-size: 1rem;
    }
    .btn-submit {
      width: 100%; padding: 0.8rem;
      background: #4CAF50; color: white;
      border: none; border-radius: 8px;
      font-size: 1.1rem; font-weight: bold;
      cursor: pointer;
    }
    .message {
      padding: 0.8rem; border-radius: 6px; margin-bottom: 1.5rem;
    }
    .error { background: #ffebee; color: #c62828; }
    .success { background: #e8f5e9; color: #2E7D32; }
    .back-link {
      display: block; margin-top: 1.5rem;
      color: #2E7D32; text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">🥦</div>
    <h1>Nueva Contraseña</h1>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($mensaje): ?>
      <div class="message success"><?= htmlspecialchars($mensaje) ?></div>
      <a href="index.php" class="back-link">Iniciar sesión</a>
    <?php else: ?>
      <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-group">
          <label for="password">Nueva contraseña</label>
          <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
          <label for="confirm">Confirmar contraseña</label>
          <input type="password" id="confirm" name="confirm" required>
        </div>
        <button type="submit" class="btn-submit">Actualizar Contraseña</button>
      </form>
      <a href="index.php" class="back-link">← Volver al login</a>
    <?php endif; ?>
  </div>
</body>
</html>