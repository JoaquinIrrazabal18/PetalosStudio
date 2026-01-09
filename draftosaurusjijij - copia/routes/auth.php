<?php
/**
 * routes/auth.php
 *
 * Endpoint para autenticación de usuarios.
 * Usa UsuarioController.
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../app/controllers/UsuarioController.php';

$controller = new UsuarioController();

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'login':
            // POST: { email, password }
            $controller->login();
            break;

        case 'logout':
            $controller->logout();
            break;

        case 'status':
            // Devuelve si hay un usuario logueado
            if (isset($_SESSION['id_usuario'])) {
                echo json_encode([
                    'success' => true,
                    'usuario' => [
                        'id_usuario' => $_SESSION['id_usuario'],
                        'nombre'     => $_SESSION['nombre'],
                        'email'      => $_SESSION['email']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
            }
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
