<?php
// logout.php

// 1. Iniciar sesión si no está activa (CRÍTICO para poder destruirla)
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de cookies igual que en el resto de la app
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '', 
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 2. Limpiar array de sesión
$_SESSION = [];

// 3. Eliminar cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destruir la sesión (solo si está activa)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 5. Redirigir al login (usando ruta relativa o absoluta según tu config)
// Asegúrate de que esta ruta sea correcta desde la raíz del dominio
header('Location: /public/index.php');
exit;
?>