<?php
// config/auth.php

// Configuración de seguridad para cookies de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Establecer SameSite en Lax para que funcione bien con redirecciones estándar
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function requiere_login() {
    if (!is_logged_in()) {
        // Redirigir a login.php
        $prefix = '';
        if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
            $prefix = '../';
        }
        header("Location: " . $prefix . "login.php");
        exit;
    }
}

function requiere_login_api() {
    if (!is_logged_in()) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'mensaje' => 'Sesión no iniciada o expirada. Por favor, inicie sesión.',
            'code' => 401
        ]);
        exit;
    }
}

function requiere_rol($rol) {
    requiere_login();
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== $rol) {
        header("Location: index.php?error=no_autorizado");
        exit;
    }
}
?>
