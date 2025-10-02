<?php
// NO debe haber nada antes de este <?php

// Limpia cualquier buffer de salida accidental
while (ob_get_level()) { ob_end_clean(); }
ob_start();

header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once __DIR__ . '/../app/controllers/UsuarioController.php';
require_once __DIR__ . '/../app/controllers/PartidaController.php';

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'registrar':
            $controller = new UsuarioController();
            $controller->registrar();
            break;
        case 'login':
            $controller = new UsuarioController();
            $controller->login();
            break;
        case 'logout':
            $controller = new UsuarioController();
            $controller->logout();
            break;
        case 'verificarSesion':
            $controller = new UsuarioController();
            $controller->verificarSesion();
            break;
        case 'crearPartida':
            $controller = new PartidaController();
            $controller->crear();
            break;
        case 'obtenerEstadoPartida':
            $controller = new PartidaController();
            $controller->estado();
            break;
        case 'hacerMovimiento':
            $controller = new PartidaController();
            $controller->movimiento();
            break;
        case 'estado_espera':
            require __DIR__ . '/partidas/estado_espera.php';
            exit;
        case 'marcar_listo':
            require __DIR__ . '/partidas/marcar_listo.php';
            exit;
        case 'iniciar':
            require __DIR__ . '/partidas/iniciar.php';
            exit;
        default:
            if (ob_get_length()) ob_clean();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
            exit;
    }
} catch (Throwable $e) {
    error_log($e); // <-- Agrega esta línea para ver el error exacto en error_log
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor', 'error' => $e->getMessage()]);
    exit;
}

