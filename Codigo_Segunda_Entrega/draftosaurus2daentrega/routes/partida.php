<?php
/**
 * routes/partida.php - Ruta corregida para partidas
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/controllers/PartidaController.php';

$controller = new PartidaController();
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'crear':
            $controller->crear();
            break;

        case 'unirse':
            $controller->unirse();
            break;

        case 'estado':
            $controller->estado();
            break;

        case 'movimiento':
            $controller->movimiento();
            break;

        case 'finalizar':
            $controller->finalizar();
            break;

        case 'eliminar':
            $controller->eliminar();
            break;

        case 'listar':
            $controller->listar();
            break;

        case 'historial':
            $controller->historial();
            break;

        case 'obtener_mano':
            $controller->obtenerMano();
            break;

        case 'obtener_tablero':
            $controller->obtenerTablero();
            break;

        case 'pausar':
            $controller->pausar();
            break;

        case 'reanudar':
            $controller->reanudar();
            break;

        default:
            JsonResponse::error('Acción no reconocida', 400);
            break;
    }
} catch (Throwable $e) {
    error_log("Error en ruta partida: " . $e->getMessage());
    JsonResponse::error('Error interno del servidor', 500);
}
?>