<?php
if ($_POST['clave'] ?? false) {
    $hash = password_hash($_POST['clave'], PASSWORD_DEFAULT);
    echo "<h2>🔐 Hash generado:</h2>";
    echo "<code style='background:#f0f0f0;padding:0.5rem;display:block;word-break:break-all;'>$hash</code>";
    echo "<p>✅ Cópialo y pégalo en tu UPDATE SQL.</p>";
} else {
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Generador de Hash</title>
</head>
<body>
  <h2>Ingresa una contraseña para generar su hash</h2>
  <form method="POST">
    <input type="password" name="clave" placeholder="Ej: 12345" required style="padding:0.5rem;font-size:1.1rem;">
    <button type="submit" style="padding:0.5rem;background:#4CAF50;color:white;border:none;margin-left:0.5rem;">Generar Hash</button>
  </form>
</body>
</html>
<?php } ?>