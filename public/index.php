<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🥦 Gestión AFV — Iniciar Sesión</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="manifest" href="manifest.json">
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
    .login-container {
      background: white;
      padding: 2.5rem;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      text-align: center;
      max-width: 400px;
      width: 90%;
    }
    .logo {
      font-size: 3rem;
      margin-bottom: 1.5rem;
      color: #4CAF50;
    }
    h1 {
      color: #2E7D32;
      margin-bottom: 1.5rem;
      font-weight: 700;
    }
    .form-group {
      margin-bottom: 1.2rem;
      text-align: left;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.4rem;
      font-weight: 600;
      color: #333;
    }
    .form-group input {
      width: 100%;
      padding: 0.7rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
    }
    .btn-login {
      width: 100%;
      padding: 0.8rem;
      background: #4CAF50;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      margin-top: 1rem;
    }
    .btn-login:hover {
      background: #388E3C;
    }
    .forgot-link {
      display: block;
      margin-top: 1rem;
      color: #2E7D32;
      text-decoration: none;
      font-size: 0.9rem;
    }
    .forgot-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">🥦🍎🥕</div>
    <h1>Gestión AFV</h1>
    <p>Inicia sesión para acceder a tu negocio</p>

    <?php if (!empty($_GET['error'])): ?>
      <div style="background:#ffebee;color:#c62828;padding:0.8rem;border-radius:6px;margin-bottom:1.5rem;font-size:0.9rem;">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="../api/login.php">
      <div class="form-group">
        <label for="usuario">Nombre</label>
        <input type="text" id="usuario" name="usuario" placeholder="Ej: Ana" required>
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn-login">Iniciar Sesión</button>
    </form>
    <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
  </div>
</body>
</html>