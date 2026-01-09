<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
$db = Database::getInstance()->getConnection();

$id_partida = isset($_GET['id_partida']) ? (int)$_GET['id_partida'] : 0;
$id_usuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;

if ($id_partida === 0 || $id_usuario === 0) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $db->prepare("UPDATE participacion_p SET listo = 1 WHERE id_partida = ? AND id_usuario = ?");
$stmt->execute([$id_partida, $id_usuario]);
echo json_encode(['success' => true]);
