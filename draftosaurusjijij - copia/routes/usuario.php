<?php
/**
 * routes/usuario.php - Ruta corregida para usuarios
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Iniciar sesión si no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Log de debug para rastrear problemas
error_log("Usuario.php - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Usuario.php - REQUEST_URI: " . $_SERVER['REQUEST_URI']);

try {
    // Incluir las dependencias necesarias
    require_once __DIR__ . '/../app/controllers/UsuarioController.php';
    
    $controller = new UsuarioController();
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    error_log("Usuario.php - Action: " . ($action ?? 'null'));
    
    switch ($action) {
        case 'registrar':
            $controller->registrar();
            break;

        case 'login':
            $controller->login();
            break;

        case 'logout':
            $controller->logout();
            break;

        case 'verificar_sesion':
            $controller->verificarSesion();
            break;

        case 'recuperar':
            $controller->recuperarCuenta();
            break;

        case 'verificar_codigo':
            $controller->verificarCodigo();
            break;

        case 'nueva_contrasena':
            $controller->nuevaContrasena();
            break;

        case 'obtener_perfil':
            $controller->obtenerPerfil();
            break;

        case 'actualizar_perfil':
            $controller->actualizarPerfil();
            break;

        case 'listar':
            $controller->listar();
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Acción no reconocida: ' . $action,
                'available_actions' => ['registrar', 'login', 'logout', 'verificar_sesion']
            ]);
            break;
    }
} catch (Throwable $e) {
    error_log("Error en ruta usuario: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>