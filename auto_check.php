<?php
// auth_check.php - Sistema de verificación de autenticación
session_start();

// Función para verificar si el usuario está autenticado
function checkAdminAuth() {
    // Verificar si existe la sesión de administrador
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Redirigir al login si no está autenticado
        header('Location: login.php');
        exit;
    }
    
    // Verificar tiempo de sesión (opcional - sesión expira en 8 horas)
    $session_timeout = 8 * 60 * 60; // 8 horas en segundos
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_timeout)) {
        // Sesión expirada
        session_unset();
        session_destroy();
        header('Location: login.php?expired=true');
        exit;
    }
    
    // Actualizar tiempo de actividad
    $_SESSION['last_activity'] = time();
}

// Función para cerrar sesión
function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php?logout=success');
    exit;
}

// Verificar si se solicita logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    logout();
}

// Verificar autenticación automáticamente
checkAdminAuth();
?>