<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/app/controllers/UsuarioController.php';
require_once __DIR__ . '/app/controllers/PartidaController.php';

$action = $_GET['action'] ?? '';

// Simple Router
switch ($action) {
    // --- User Actions ---
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
        
    // --- Game Actions ---
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
    
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
        break;
}
