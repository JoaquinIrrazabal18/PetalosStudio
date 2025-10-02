<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
$db = Database::getInstance()->getConnection();

$id_partida = isset($_GET['id_partida']) ? (int)$_GET['id_partida'] : 0;
if ($id_partida === 0) {
    echo json_encode(['success' => false, 'message' => 'ID de partida inválido']);
    exit;
}

// Verifica que todos estén listos
$stmt = $db->prepare("SELECT COUNT(*) FROM participacion_p WHERE id_partida = ? AND listo = 0");
$stmt->execute([$id_partida]);
if ($stmt->fetchColumn() == 0) {
    $stmt2 = $db->prepare("UPDATE partida SET iniciada = 1 WHERE id_partida = ?");
    $stmt2->execute([$id_partida]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No todos están listos']);
}
