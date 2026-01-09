<?php
/**
 * config/paths.php - Configuración centralizada de rutas
 */

// Definir la raíz del proyecto
define('PROJECT_ROOT', dirname(__DIR__));

// Definir rutas importantes
define('APP_DIR', PROJECT_ROOT . '/app');
define('PUBLIC_DIR', PROJECT_ROOT . '/public');
define('ROUTES_DIR', PROJECT_ROOT . '/routes');
define('DATABASE_DIR', PROJECT_ROOT . '/database');

// URLs base para el frontend
define('BASE_URL', 'http://localhost/draftosaurus');
define('PUBLIC_URL', BASE_URL . '/public');
define('API_URL', BASE_URL . '/routes');

// Rutas de archivos importantes
define('DATABASE_CONFIG', APP_DIR . '/config/Database.php');
define('DATABASE_SQL', DATABASE_DIR . '/draftosaurus.sql');

// Configuración de sesiones
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_lifetime', 86400); // 24 horas
    session_start();
}

/**
 * Función helper para construir URLs
 */
function buildUrl($path, $base = PUBLIC_URL) {
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Función helper para incluir archivos
 */
function includeFile($relativePath, $required = true) {
    $fullPath = PROJECT_ROOT . '/' . ltrim($relativePath, '/');
    
    if ($required) {
        require_once $fullPath;
    } else {
        include_once $fullPath;
    }
}
?>