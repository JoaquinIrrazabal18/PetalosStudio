<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
$db = Database::getInstance()->getConnection();

$id_partida = isset($_GET['id_partida']) ? (int)$_GET['id_partida'] : 0;
if ($id_partida === 0) {
    echo json_encode(['jugadores' => [], 'iniciada' => false]);
    exit;
}

// Obtener jugadores y su estado "listo"
$stmt = $db->prepare("SELECT u.id_usuario, u.nombre_usuario, pa.listo 
    FROM participacion_p pa 
    JOIN usuario u ON pa.id_usuario = u.id_usuario 
    WHERE pa.id_partida = ?");
$stmt->execute([$id_partida]);
$jugadores = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $jugadores[] = [
        'id' => $row['id_usuario'],
        'nombre' => $row['nombre_usuario'],
        'listo' => (bool)$row['listo']
    ];
}

// Verificar si la partida está iniciada
$stmt2 = $db->prepare("SELECT iniciada FROM partida WHERE id_partida = ?");
$stmt2->execute([$id_partida]);
$iniciada = (bool)($stmt2->fetchColumn());

echo json_encode(['jugadores' => $jugadores, 'iniciada' => $iniciada]);
