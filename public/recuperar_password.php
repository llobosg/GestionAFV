<?php
$error = $_GET['error'] ?? '';
$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🥦 Recuperar Contraseña — NegocioUP</title>
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
    <h1>Recuperar Contraseña</h1>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($mensaje): ?>
      <div class="message success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" action="../api/enviar_recuperacion.php">
      <div class="form-group">
        <label for="nombre">Tu nombre</label>
        <input type="text" id="nombre" name="nombre" placeholder="Ej: Ana" required>
      </div>
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" placeholder="tu@correo.com" required>
      </div>
      <button type="submit" class="btn-submit">Enviar enlace de recuperación</button>
    </form>
    <a href="index.php" class="back-link">← Volver al login</a>
  </div>
</body>
</html>