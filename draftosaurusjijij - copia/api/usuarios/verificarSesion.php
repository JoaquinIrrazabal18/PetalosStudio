<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'id_usuario' => $_SESSION['id_usuario'],
        'nombre_usuario' => $_SESSION['nombre_usuario'] ?? 'Usuario'
    ]
]);
exit;