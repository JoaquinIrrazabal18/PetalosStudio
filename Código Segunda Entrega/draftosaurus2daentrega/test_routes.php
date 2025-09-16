<?php
/**
 * test_routes.php - Verificar que las rutas funcionen
 * Coloca este archivo en la RAÍZ de tu proyecto
 */

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Las rutas están funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'post_data' => file_get_contents('php://input'),
    'get_params' => $_GET,
    'files_exist' => [
        'routes/usuario.php' => file_exists(__DIR__ . '/routes/usuario.php'),
        'app/controllers/UsuarioController.php' => file_exists(__DIR__ . '/app/controllers/UsuarioController.php'),
        'app/config/Database.php' => file_exists(__DIR__ . '/app/config/Database.php')
    ]
]);
?>