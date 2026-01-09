<?php
/**
 * routes/historial.php
 *
 * Maneja todas las solicitudes AJAX relacionadas con el historial de partidas.
 * Usa HistorialController.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/controllers/HistorialController.php';

$controller = new HistorialController();

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'listar':
            // GET: ?id_partida=1
            if (!isset($_GET['id_partida'])) {
                echo json_encode(['success' => false, 'message' => 'Falta id_partida']);
                exit;
            }
            $controller->listar((int)$_GET['id_partida']);
            break;

        case 'agregar':
            // POST: { id_partida, id_usuario, accion, dado, meta }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['id_partida']) || !isset($input['id_usuario']) || !isset($input['accion'])) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }
            $controller->agregar($input);
            break;

        case 'eliminar':
            // POST: { id_historial }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['id_historial'])) {
                echo json_encode(['success' => false, 'message' => 'Falta id_historial']);
                exit;
            }
            $controller->eliminar((int)$input['id_historial']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en servidor: ' . $e->getMessage()
    ]);
}
