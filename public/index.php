<!-- public/index.php -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🥦 Iniciar Sesión — NegocioUP</title>
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
    .btn-login {
      width: 100%; padding: 0.8rem;
      background: #4CAF50; color: white;
      border: none; border-radius: 8px;
      font-size: 1.1rem; font-weight: bold;
      cursor: pointer;
    }
    .error {
      color: #C62828;
      background: #ffebee;
      padding: 0.7rem;
      border-radius: 6px;
      margin-bottom: 1rem;
      display: none;
    }
    .forgot-link {
      display: block;
      margin-top: 1rem;
      color: #2E7D32;
      text-decoration: none;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">🥦</div>
    <h1>Iniciar Sesión</h1>

    <div id="error-msg" class="error"></div>

    <form id="login-form">
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
    <a href="recuperar_password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
  </div>

  <script>
    document.getElementById('login-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData);

      try {
        const res = await fetch('../api/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const result = await res.json();

        if (result.success) {
          // ✅ Redirección automática a home.php
          window.location.href = 'home.php';
        } else {
          document.getElementById('error-msg').textContent = result.message || 'Error desconocido';
          document.getElementById('error-msg').style.display = 'block';
        }
      } catch (err) {
        document.getElementById('error-msg').textContent = 'No se pudo conectar al servidor';
        document.getElementById('error-msg').style.display = 'block';
      }
    });
  </script>
</body>
</html>