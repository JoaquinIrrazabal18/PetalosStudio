<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../app/controllers/PartidaController.php';

$controller = new PartidaController();
$controller->crear();
