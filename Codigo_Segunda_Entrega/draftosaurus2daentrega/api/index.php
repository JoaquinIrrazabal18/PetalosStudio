<?php
/**
 * api/index.php - Ubicación: /tu_proyecto/api/index.php
 * Punto de entrada único para todas las peticiones de la API.
 * Versión corregida para integración con frontend
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Manejar petición pre-vuelo de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Cargar archivos necesarios
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';
require_once __DIR__ . '/../app/controllers/PartidaController.php';

// Analizar la URL para determinar el recurso y la acción
$request_uri = $_SERVER['REQUEST_URI'];

// IMPORTANTE: Ajustar la ruta base según tu estructura de carpetas
$base_path = '/draftosaurustesteo15/api'; // Cambia esto por tu carpeta real

$path = str_replace($base_path, '', $request_uri);
$path = trim($path, '/');

// Remover query parameters para el routing
$path = strtok($path, '?');

$segments = explode('/', $path);

$resource = $segments[0] ?? null;
$action = $segments[1] ?? null;

try {
    // Enrutamiento principal
    switch ($resource) {
        case 'usuarios':
            $controller = new UsuarioController();
            handleUsuarioRoutes($controller, $action);
            break;

        case 'partidas':
            $controller = new PartidaController();
            handlePartidaRoutes($controller, $action);
            break;

        default:
            JsonResponse::error('Recurso no encontrado: ' . $resource, 404);
            break;
    }
} catch (Exception $e) {
    error_log("Error en API: " . $e->getMessage());
    JsonResponse::error('Error interno del servidor', 500);
}

/**
 * Maneja las rutas del controlador de usuarios
 */
function handleUsuarioRoutes(UsuarioController $controller, ?string $action): void {
    switch ($action) {
        case 'registrar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->registrar();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->login();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'logout':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->logout();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'verificarSesion':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->verificarSesion();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'perfil':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->obtenerPerfil();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->actualizarPerfil();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'recuperarCuenta':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->recuperarCuenta();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'verificarCodigo':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->verificarCodigo();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'nuevaContrasena':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->nuevaContrasena();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'listar':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->listar();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        default:
            JsonResponse::error('Acción de usuario no válida: ' . $action, 404);
            break;
    }
}

/**
 * Maneja las rutas del controlador de partidas
 */
function handlePartidaRoutes(PartidaController $controller, ?string $action): void {
    switch ($action) {
        case 'crear':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->crear();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'estado':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->estado();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'movimiento':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->movimiento();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'obtenerMano':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->obtenerMano();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'obtenerTablero':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->obtenerTablero();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'historial':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->historial();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'pausar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->pausar();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'reanudar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->reanudar();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        case 'eliminar':
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->eliminar();
            } else {
                JsonResponse::error('Método no permitido', 405);
            }
            break;
            
        default:
            JsonResponse::error('Acción de partida no válida: ' . $action, 404);
            break;
    }
}