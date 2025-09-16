<?php
// Iniciamos la sesión para verificar que el usuario está logueado
session_start();

// Si no hay una sesión activa, no se puede jugar, redirigimos al inicio.
if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php');
    exit();
}

// Obtenemos el ID de la partida desde la URL. Si no existe, es un error.
$id_partida = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_partida === 0) {
    header('Location: menu.php?error=nopartida');
    exit();
}

// --- SOLUCIÓN: CÁLCULO DINÁMICO DE LA RUTA BASE ---
// Esto obtiene la ruta de la carpeta del proyecto, sin importar el nombre.
$project_folder = basename(dirname(__DIR__)); 
$api_base_url = '/' . $project_folder . '/api';
$id_usuario_actual = $_SESSION['id_usuario'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draftosaurus - Partida Online</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ESTILOS GENERALES Y DE DISEÑO */
        :root {
            --user1-color: #0d6efd;
            --user2-color: #198754;
            --user3-color: #ffc107;
            --user4-color: #dc3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eaddc7;
            background-image:
                linear-gradient(45deg, rgba(0, 0, 0, 0.03) 25%, transparent 25%, transparent 75%, rgba(0, 0, 0, 0.03) 75%, rgba(0, 0, 0, 0.03)),
                linear-gradient(-45deg, rgba(0, 0, 0, 0.03) 25%, transparent 25%, transparent 75%, rgba(0, 0, 0, 0.03) 75%, rgba(0, 0, 0, 0.03));
            background-size: 20px 20px;
        }

        #pre-game-overlay {
            transition: opacity 0.5s ease-in-out;
        }

        .fade-out {
            opacity: 0;
            pointer-events: none;
        }

        .board-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            aspect-ratio: 1 / 1;
            margin: auto;
        }

        .board-image {
            width: 100%;
            height: 100%;
            border-radius: 15px;
        }

        .opponent-board-wrapper {
            transition: transform 0.3s ease-in-out;
        }

        .opponent-board-wrapper:hover {
            transform: scale(1.05);
            z-index: 10;
        }

        .opponent-board {
            position: relative;
        }

        .oponente-dino {
            position: absolute;
            width: 10%;
            height: 12%;
            object-fit: contain;
            pointer-events: none;
            transition: transform 0.3s ease;
        }

        /* --- ESTILOS PARA CLICK-TO-PLACE --- */
        .dinosaurio-mano {
            width: 50px;
            height: 50px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.3s, visibility 0.3s;
            border-radius: 10px;
        }

        .dinosaurio-mano:hover {
            transform: scale(1.1);
        }

        .dinosaurio-mano.seleccionado {
            transform: scale(1.2);
            box-shadow: 0 0 15px 5px var(--user1-color);
            background-color: rgba(13, 110, 253, 0.2);
        }

        .dinosaurio-mano.colocado {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .dino-en-tablero {
            width: 50px;
            height: 50px;
            object-fit: contain;
            pointer-events: none;
        }

        .drop-zone {
            position: absolute;
            border-radius: 8px;
            transition: background-color 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .drop-zone.habilitado {
            border: 3px dashed rgba(25, 135, 84, 0.8);
            cursor: pointer;
        }

        .drop-zone.habilitado:hover {
            background-color: rgba(25, 135, 84, 0.3);
            box-shadow: 0 0 10px rgba(25, 135, 84, 0.7);
        }

        .drop-zone.bloqueado {
            border: 3px dashed rgba(150, 150, 150, 0.6);
            cursor: not-allowed;
        }

        .turn-panel .card-body {
            padding: 0.8rem;
        }

        #history-log {
            height: 70px;
            overflow-y: auto;
        }

        #dino-hand-mobile {
            display: flex;
            flex-direction: row;
            justify-content: center;
            flex-wrap: wrap;
        }

        .user-color-1 {
            color: var(--user1-color) !important;
        }

        .user-color-2 {
            color: var(--user2-color) !important;
        }

        .user-color-3 {
            color: var(--user3-color) !important;
        }

        .user-color-4 {
            color: var(--user4-color) !important;
        }

        #dice-container {
            width: 70px;
            height: 70px;
        }

        .dino-score-icon {
            width: 30px;
        }

        .player-card-clickable {
            cursor: pointer;
        }

        .player-card-clickable .card-body {
            position: relative;
        }

        .dice-turn-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 1.2rem;
        }

        .score-icon {
            position: absolute;
            top: 5px;
            left: 5px;
            font-size: 1rem;
        }

        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        #dino-hand-desktop.card,
        .opponent-board-wrapper .card-header {
            background-color: rgba(255, 255, 255, 0.6) !important;
        }

        .recinto {
            position: absolute;
        }

        #quarantine-options-list .list-group-item-action {
            cursor: pointer;
        }

        /* --- THEME COLORS --- */
        .theme-verano header {
            background-color: #ffc107 !important;
        }

        .theme-invierno header {
            background-color: #1b94cb !important;
        }

        .theme-invierno #info-ronda-card {
            background-color: #014945 !important;
        }

        /* --- POSICIONAMIENTO TABLERO VERANO --- */
        #verano-zones {
            display: block;
        }

        #invierno-zones {
            display: none;
        }

        #cont-bosque_semejanza {
            top: 7%;
            left: 5.5%;
            width: 27%;
            height: 15%;
        }

        #cont-bosque_semejanza .drop-zone {
            width: 15%;
            height: 100%;
        }

        #cont-bosque_semejanza .drop-zone[data-posicion="0"] {
            left: 0;
        }

        #cont-bosque_semejanza .drop-zone[data-posicion="1"] {
            left: 20%;
        }

        #cont-bosque_semejanza .drop-zone[data-posicion="2"] {
            left: 40%;
        }

        #cont-bosque_semejanza .drop-zone[data-posicion="3"] {
            left: 60%;
        }

        #cont-bosque_semejanza .drop-zone[data-posicion="4"] {
            left: 80%;
        }

        #cont-bosque_semejanza .drop-zone[data-posicion="5"] {
            left: 100%;
        }

        #cont-prado_diferencia {
            top: 43%;
            left: 60.5%;
            width: 27%;
            height: 15%;
        }

        #cont-prado_diferencia .drop-zone {
            width: 15%;
            height: 100%;
        }

        #cont-prado_diferencia .drop-zone[data-posicion="0"] {
            left: 0;
        }

        #cont-prado_diferencia .drop-zone[data-posicion="1"] {
            left: 20%;
        }

        #cont-prado_diferencia .drop-zone[data-posicion="2"] {
            left: 40%;
        }

        #cont-prado_diferencia .drop-zone[data-posicion="3"] {
            left: 60%;
        }

        #cont-prado_diferencia .drop-zone[data-posicion="4"] {
            left: 80%;
        }

        #cont-prado_diferencia .drop-zone[data-posicion="5"] {
            left: 100%;
        }

        #cont-pradera_amor {
            top: 69%;
            left: 11.5%;
            width: 20%;
            height: 20%;
        }

        #cont-pradera_amor .drop-zone {
            width: 45%;
            height: 30%;
        }

        #cont-pradera_amor .drop-zone[data-posicion="0"] {
            top: 0;
            left: 0;
        }

        #cont-pradera_amor .drop-zone[data-posicion="1"] {
            top: 0;
            left: 55%;
        }

        #cont-pradera_amor .drop-zone[data-posicion="2"] {
            top: 35%;
            left: 0%;
        }

        #cont-pradera_amor .drop-zone[data-posicion="3"] {
            top: 35%;
            left: 55%;
        }

        #cont-pradera_amor .drop-zone[data-posicion="4"] {
            top: 70%;
            left: 0%;
        }

        #cont-pradera_amor .drop-zone[data-posicion="5"] {
            top: 70%;
            left: 55%;
        }

        #cont-trio_frondoso {
            top: 40%;
            left: 7%;
            width: 20%;
            height: 15%;
        }

        #cont-trio_frondoso .drop-zone {
            width: 30%;
            height: 100%;
        }

        #cont-trio_frondoso .drop-zone[data-posicion="0"] {
            left: 35%;
        }

        #cont-trio_frondoso .drop-zone[data-posicion="1"] {
            left: 0;
        }

        #cont-trio_frondoso .drop-zone[data-posicion="2"] {
            left: 70%;
        }

        #rey_selva {
            top: 6%;
            left: 67%;
            width: 14%;
            height: 10%;
        }

        #isla_solitaria {
            top: 67%;
            left: 79%;
            width: 15%;
            height: 13%;
        }

        #rio {
            top: 75%;
            left: 50%;
            width: 15%;
            height: 15%;
            border-radius: 10px;
        }

        /* --- POSICIONAMIENTO TABLERO INVIERNO --- */
        #cont-bosque_ordenado {
            top: 11%;
            left: 5.5%;
            width: 27%;
            height: 14%;
        }

        #cont-bosque_ordenado .drop-zone {
            width: 15%;
            height: 100%;
        }

        #cont-bosque_ordenado .drop-zone[data-posicion="0"] {
            left: 0;
        }

        #cont-bosque_ordenado .drop-zone[data-posicion="1"] {
            left: 20%;
        }

        #cont-bosque_ordenado .drop-zone[data-posicion="2"] {
            left: 40%;
        }

        #cont-bosque_ordenado .drop-zone[data-posicion="3"] {
            left: 60%;
        }

        #cont-bosque_ordenado .drop-zone[data-posicion="4"] {
            left: 80%;
        }

        #cont-bosque_ordenado .drop-zone[data-posicion="5"] {
            left: 100%;
        }

        #cont-puente_enamorados_izq {
            top: 36%;
            left: 27%;
            width: 55%;
            height: 13%;
        }

        #cont-puente_enamorados_izq .drop-zone {
            width: 34%;
            height: 40%;
        }

        #cont-puente_enamorados_izq .drop-zone[data-posicion="0"] {
            top: 0;
            left: 0;
        }

        #cont-puente_enamorados_izq .drop-zone[data-posicion="1"] {
            top: 50%;
            left: 0;
        }

        #cont-puente_enamorados_izq .drop-zone[data-posicion="2"] {
            top: 100%;
            left: 0;
        }

        #cont-puente_enamorados_der {
            top: 36%;
            left: 60%;
            width: 47%;
            height: 13%;
        }

        #cont-puente_enamorados_der .drop-zone {
            width: 30%;
            height: 40%;
        }

        #cont-puente_enamorados_der .drop-zone[data-posicion="0"] {
            top: 0;
            left: 0;
        }

        #cont-puente_enamorados_der .drop-zone[data-posicion="1"] {
            top: 50%;
            left: 0;
        }

        #cont-puente_enamorados_der .drop-zone[data-posicion="2"] {
            top: 100%;
            left: 0;
        }

        #cont-piramide {
            top: 66%;
            left: 39%;
            width: 47%;
            height: 23%;
        }

        #cont-piramide .drop-zone {
            width: 33%;
            height: 21%;
        }

        #cont-piramide .drop-zone[data-posicion="0"] {
            top: 68%;
            left: 0;
        }

        #cont-piramide .drop-zone[data-posicion="1"] {
            top: 68%;
            left: 36%;
        }

        #cont-piramide .drop-zone[data-posicion="2"] {
            top: 68%;
            left: 72%;
        }

        #cont-piramide .drop-zone[data-posicion="3"] {
            top: 34%;
            left: 18%;
        }

        #cont-piramide .drop-zone[data-posicion="4"] {
            top: 34%;
            left: 54%;
        }

        #cont-piramide .drop-zone[data-posicion="5"] {
            top: 0;
            left: 36%;
        }

        #puesto_observacion {
            top: 10%;
            left: 65%;
            width: 14%;
            height: 11.5%;
        }

        #zona_cuarentena {
            top: 72%;
            left: 5%;
            width: 15%;
            height: 16%;
        }

        #rio_invierno {
            top: 67%;
            left: 27%;
            width: 7%;
            height: 28%;
            border-radius: 10px;
        }

        /* --- Estilo para la barra de scroll --- */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #ffffff;
        }

        .theme-verano ::-webkit-scrollbar-thumb {
            background-color: #ffc107;
            border-radius: 20px;
            border: 3px solid #ffffff;
        }

        .theme-verano ::-webkit-scrollbar-thumb:hover {
            background-color: #e0a800;
        }

        .theme-invierno ::-webkit-scrollbar-thumb {
            background-color: #1b94cb;
            border-radius: 20px;
            border: 3px solid #ffffff;
        }

        .theme-invierno ::-webkit-scrollbar-thumb:hover {
            background-color: #1679a1;
        }
    </style>
</head>

<body class="text-dark"> <div id="pre-game-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background-color: rgba(43, 23, 0, 0.9); z-index: 2000; color: white;">
        <h1 class="display-1 fw-bold" id="countdown-number">3</h1>
        <p class="lead">La partida comenzará pronto...</p>
    </div>

    <header class="shadow-sm sticky-top">
        <nav class="container-fluid d-flex justify-content-between align-items-center p-2">
            <a class="navbar-brand text-white fw-bold border border-2 border-white p-1" href="#">DRAFTOSAURUS</a>
            <div class="text-white small d-none d-sm-flex flex-column align-items-center flex-md-row gap-md-4">
                <p class="mb-0"><span class="fw-semibold">Partida:</span> <span id="gameNameDisplay"></span></p>
                <p class="mb-0"><span class="fw-semibold">Host:</span> <span id="hostNameDisplay" class="user-color-4"></span></p>
            </div>
            <div class="d-flex align-items-center fs-4">
                <button id="change-board-btn" class="btn btn-link text-white" title="Cambiar Tablero"><i class="bi bi-map-fill"></i></button>
                <button class="btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#pauseModal" title="Pausar Partida"><i class="bi bi-pause-circle-fill"></i></button>
                <button class="btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#settingsModal" title="Ajustes"><i class="bi bi-gear-fill"></i></button>
                <button class="btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#exitModal" title="Salir de la Partida"><i class="bi bi-box-arrow-right"></i></button>
            </div>
        </nav>
    </header>

    <div id="navbar-extra" class="bg-body-secondary py-2 shadow-sm">
        <div class="container-fluid d-flex flex-wrap justify-content-center align-items-center gap-3 gap-md-5 small">
            <div id="copy-container" class="d-flex align-items-center gap-2" role="button" title="Hacer clic para copiar">
                <span id="link-text" class="fw-semibold" data-link="jueguito.com/enlacealapartida">******************************</span>
                <span id="copy-feedback" class="text-success fw-bold d-none">¡Copiado!</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span id="password-text" class="font-monospace bg-white px-2 py-1 rounded text-dark" data-password="ContraseñaPartida">******************</span>
                <button id="toggle-password" class="btn btn-sm btn-outline-secondary p-1 lh-1" title="Mostrar/Ocultar contraseña">
                    <i id="eye-open" class="bi bi-eye-fill d-none"></i>
                    <i id="eye-closed" class="bi bi-eye-slash-fill"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-3">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="d-flex flex-column gap-4">

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card shadow-sm turn-panel h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="flex-grow-1">
                                            <p id="turn-message" class="fw-semibold mb-2">Cargando partida...</p>
                                            <button id="roll-dice-btn" class="btn btn-primary btn-sm" disabled>Lanzar Dado</button>
                                        </div>
                                        <div id="dice-container"></div>
                                    </div>
                                    <div id="history-log" class="p-2 rounded bg-body-tertiary small"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 flex-grow-1">
                        <div class="col-6">
                            <div class="card h-100 player-card-clickable" id="player-card-0" data-bs-toggle="modal" data-bs-target="#profileModal" data-player-id="0"></div>
                        </div>
                        <div class="col-6">
                            <div class="card h-100 player-card-clickable" id="player-card-1" data-bs-toggle="modal" data-bs-target="#profileModal" data-player-id="1"></div>
                        </div>
                        <div class="col-6">
                            <div class="card h-100 player-card-clickable" id="player-card-2" data-bs-toggle="modal" data-bs-target="#profileModal" data-player-id="2"></div>
                        </div>
                        <div class="col-6">
                            <div class="card h-100 player-card-clickable" id="player-card-3" data-bs-toggle="modal" data-bs-target="#profileModal" data-player-id="3"></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-lg-7">
                <div class="row align-items-start g-3">
                    <div class="col-md-8">
                        <div class="d-flex flex-column gap-4 h-100">
                            <div id="dino-hand-mobile" class="d-lg-none gap-2 p-2 card shadow-sm mb-3"></div>


                            <div id="mi-tablero" class="board-container shadow-lg">
                                <img src="../public/img/tablero verano.jpg" alt="Tablero Principal" class="board-image">

                                <div id="verano-zones">
                                    <div id="cont-bosque_semejanza" class="recinto" data-recinto-nombre="Bosque de la Semejanza" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                        <div class="drop-zone" data-posicion="3"></div>
                                        <div class="drop-zone" data-posicion="4"></div>
                                        <div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-prado_diferencia" class="recinto" data-recinto-nombre="Prado de la Diferencia" data-tipo="llanura" data-lado="banos">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                        <div class="drop-zone" data-posicion="3"></div>
                                        <div class="drop-zone" data-posicion="4"></div>
                                        <div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-pradera_amor" class="recinto" data-recinto-nombre="Pradera del Amor" data-tipo="llanura" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                        <div class="drop-zone" data-posicion="3"></div>
                                        <div class="drop-zone" data-posicion="4"></div>
                                        <div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-trio_frondoso" class="recinto" data-recinto-nombre="Trío Frondoso" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                    </div>
                                    <div id="rey_selva" class="recinto drop-zone" data-recinto-nombre="Rey de la Selva" data-tipo="bosque" data-lado="banos" data-posicion="0"></div>
                                    <div id="isla_solitaria" class="recinto drop-zone" data-recinto-nombre="Isla Solitaria" data-tipo="llanura" data-lado="banos" data-posicion="0"></div>
                                    <div id="rio" class="recinto drop-zone" data-recinto-nombre="Rio" data-tipo="rio" data-lado="ninguno" data-posicion="0"></div>
                                </div>

                                <div id="invierno-zones">
                                    <div id="cont-bosque_ordenado" class="recinto" data-recinto-nombre="Bosque Ordenado" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                        <div class="drop-zone" data-posicion="3"></div>
                                        <div class="drop-zone" data-posicion="4"></div>
                                        <div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-puente_enamorados_izq" class="recinto" data-recinto-nombre="Puente de los Enamorados Izquierda" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                    </div>
                                    <div id="cont-puente_enamorados_der" class="recinto" data-recinto-nombre="Puente de los Enamorados Derecha" data-tipo="llanura" data-lado="banos">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                    </div>
                                    <div id="cont-piramide" class="recinto" data-recinto-nombre="La Pirámide" data-tipo="llanura" data-lado="banos">
                                        <div class="drop-zone" data-posicion="0"></div>
                                        <div class="drop-zone" data-posicion="1"></div>
                                        <div class="drop-zone" data-posicion="2"></div>
                                        <div class="drop-zone" data-posicion="3"></div>
                                        <div class="drop-zone" data-posicion="4"></div>
                                        <div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="puesto_observacion" class="recinto drop-zone" data-recinto-nombre="Puesto de Observación" data-tipo="bosque" data-lado="banos" data-posicion="0"></div>
                                    <div id="zona_cuarentena" class="recinto drop-zone" data-recinto-nombre="Zona de Cuarentena" data-tipo="llanura" data-lado="cafeteria" data-posicion="0"></div>
                                    <div id="rio_invierno" class="recinto drop-zone" data-recinto-nombre="Rio" data-tipo="rio" data-lado="ninguno" data-posicion="0"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex flex-column gap-3">
                            <div id="info-ronda-card" class="card bg-success text-white p-3 shadow-sm d-none d-lg-block">
                                <p class="mb-1 small">Dinos en Parque: <strong id="dinos-colocados-contador">0 / 12</strong></p>
                                <p class="mb-1 small">Ronda: <strong id="ronda-actual-contador">1 / 2</strong></p>
                                <p class="mb-0 small">Turno: <strong id="turno-actual-contador">1 / 6</strong></p>
                            </div>
                            <div id="dino-hand-desktop" class="d-none d-lg-flex flex-column align-items-center gap-2 p-2 card shadow-sm">
                                <h6 class="fw-bold small text-center my-1">Dinos en Mano</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <h5 class="fw-bold mb-3">Tableros de los Oponentes</h5>
                <div class="row g-4" id="opponent-boards-container">
                    </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted small py-4 mt-5 border-top bg-body-tertiary">
        <div class="container">
            <img src="../public/img/LOGO_NUEVO-removebg-preview.png" alt="Logo Pétalos Studio" class="mb-2" style="height: 40px;">
            <p class="mb-0">TÉRMINOS DE SERVICIO | PRIVACIDAD | CONTACTO</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tween.js/18.6.4/tween.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
   // SOLO reemplaza el script JavaScript al final de partida.php con este código
// Mantén todo el HTML, CSS y estructura exactamente igual

document.addEventListener('DOMContentLoaded', () => {
    // Configuración de la API
    const API_CONFIG = {
        baseUrl: '/draftosaurustesteo15/api', // Ajusta según tu carpeta de proyecto
        endpoints: {
            obtenerEstado: '/partidas/estado',
            hacerMovimiento: '/partidas/movimiento',
            verificarSesion: '/usuarios/verificarSesion',
            obtenerMano: '/partidas/obtenerMano',
            obtenerTablero: '/partidas/obtenerTablero'
        }
    };

    // Estado del juego híbrido (backend + simulado)
    const gameState = {
        partidaId: null,
        miId: null,
        miNombre: null,
        jugadores: [],
        jugadorActivo: 0,
        turnoActual: 1,
        rondaActual: 1,
        estado: 'esperando_dado',
        restriccionDado: null,
        dinoSeleccionado: null,
        isPaused: false,
        miMano: [],
        miTablero: {},
        jugadoresQueHanColocado: new Set(),
        boardType: 'verano',
        backendConectado: false
    };

    // Datos de jugadores (mantener los originales)
    const players = [{
            id: 0,
            name: 'Tú',
            colorClass: 'user-color-1',
            age: 25
        },
        {
            id: 1,
            name: 'Xavier',
            colorClass: 'user-color-2',
            age: 30
        },
        {
            id: 2,
            name: 'Julieta',
            colorClass: 'user-color-3',
            age: 22
        },
        {
            id: 3,
            name: 'Joaquín',
            colorClass: 'user-color-4',
            age: 28
        }
    ];

    // Imágenes de dinosaurios
    const ALL_DINOS = {
        'Tiranosaurio Rex': {
            src: '../public/img/t-rex.png',
            count: 10
        },
        'Triceratops': {
            src: '../public/img/triceratops.png',
            count: 10
        },
        'Stegosaurus': {
            src: '../public/img/stegosaurus.png',
            count: 10
        },
        'Pterodáctilo': {
            src: '../public/img/pterodactilo.png',
            count: 10
        },
        'Plesiosaurio': {
            src: '../public/img/plesiosaurio.png',
            count: 10
        },
        'Brachiosaurus': {
            src: '../public/img/brachiosaurus.png',
            count: 10
        }
    };

    // Elementos del DOM
    const rollButton = document.getElementById('roll-dice-btn');
    const turnMessage = document.getElementById('turn-message');
    const historyLog = document.getElementById('history-log');
    const dinoHandDesktop = document.getElementById('dino-hand-desktop');
    const dinoHandMobile = document.getElementById('dino-hand-mobile');
    const mainBoard = document.getElementById('mi-tablero');
    const dinosColocadosContador = document.getElementById('dinos-colocados-contador');
    const rondaActualContador = document.getElementById('ronda-actual-contador');
    const turnoActualContador = document.getElementById('turno-actual-contador');
    const changeBoardBtn = document.getElementById('change-board-btn');

    // Inicialización híbrida
    async function initializeGame() {
        try {
            // Intentar conectar con backend
            await obtenerIdPartida();
            await verificarSesion();
            await actualizarEstadoPartida();
            gameState.backendConectado = true;
            addHistory('¡Partida cargada desde el backend!');
        } catch (error) {
            console.log('Backend no disponible, usando modo simulado:', error);
            gameState.backendConectado = false;
            inicializarModoSimulado();
            addHistory('Modo de prueba activado - datos simulados');
        }

        setupEventListeners();
        setupBoard(gameState.boardType);
        updateUI();
        startGameLoop();
    }

    async function obtenerIdPartida() {
        const urlParams = new URLSearchParams(window.location.search);
        gameState.partidaId = urlParams.get('id');
        
        if (!gameState.partidaId) {
            throw new Error('ID de partida no encontrado en la URL');
        }
    }

    async function verificarSesion() {
        const response = await fetch(`${API_CONFIG.baseUrl}${API_CONFIG.endpoints.verificarSesion}`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error('Sesión no válida');
        }
        
        gameState.miId = result.data.id_usuario;
        gameState.miNombre = result.data.nombre_usuario;
    }

    async function actualizarEstadoPartida() {
        const response = await fetch(`${API_CONFIG.baseUrl}${API_CONFIG.endpoints.obtenerEstado}?id_partida=${gameState.partidaId}`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Error obteniendo estado de la partida');
        }
        
        const partida = result.data;
        
        // Actualizar estado del juego
        gameState.rondaActual = partida.ronda_actual || 1;
        gameState.turnoActual = partida.turno_actual || 1;
        gameState.estado = partida.estado_partida || 'en_curso';
        gameState.boardType = (partida.lado_tablero || 'Verano').toLowerCase();
        
        // Obtener mi mano y tablero
        await obtenerMiMano();
        await obtenerMiTablero();
    }

    async function obtenerMiMano() {
        try {
            const response = await fetch(`${API_CONFIG.baseUrl}${API_CONFIG.endpoints.obtenerMano}?id_partida=${gameState.partidaId}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success && result.data.mano) {
                gameState.miMano = result.data.mano;
            }
        } catch (error) {
            console.log('Error obteniendo mano del backend:', error);
        }
        
        if (gameState.miMano.length === 0) {
            crearManoSimulada();
        }
        
        actualizarManoUI();
    }

    async function obtenerMiTablero() {
        try {
            const response = await fetch(`${API_CONFIG.baseUrl}${API_CONFIG.endpoints.obtenerTablero}?id_partida=${gameState.partidaId}&id_usuario=${gameState.miId}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                gameState.miTablero = result.data.tablero || {};
            }
        } catch (error) {
            console.log('Error obteniendo tablero del backend:', error);
        }
        
        actualizarTableroUI();
    }

    function inicializarModoSimulado() {
        gameState.miId = 0;
        gameState.miNombre = 'Tú';
        gameState.jugadorActivo = 0;
        gameState.estado = 'esperando_dado';
        crearManoSimulada();
    }

    function crearManoSimulada() {
        const especies = Object.keys(ALL_DINOS);
        gameState.miMano = [];
        
        for (let i = 0; i < 6; i++) {
            const especieRandom = especies[Math.floor(Math.random() * especies.length)];
            gameState.miMano.push({
                id_dino_ficha: `sim_${i}_${Date.now()}`,
                especie: especieRandom
            });
        }
    }

    function setupEventListeners() {
        // Event listeners originales
        if (rollButton) {
            rollButton.addEventListener('click', handleDiceRoll);
        }

        if (dinoHandDesktop) {
            dinoHandDesktop.addEventListener('click', handleDinoClick);
        }
        if (dinoHandMobile) {
            dinoHandMobile.addEventListener('click', handleDinoClick);
        }
        if (mainBoard) {
            mainBoard.addEventListener('click', handleZoneClick);
        }

        if (changeBoardBtn) {
            changeBoardBtn.addEventListener('click', () => {
                const newType = gameState.boardType === 'verano' ? 'invierno' : 'verano';
                setupBoard(newType);
                gameState.boardType = newType;
            });
        }

        // Mantener setup original de modales y otros elementos
        setupLinkBar();
        setupModals();
        setupPlayerCards();
    }

    function actualizarManoUI() {
        // Limpiar manos
        if (dinoHandDesktop) {
            dinoHandDesktop.innerHTML = '<h6 class="fw-bold small text-center my-1">Dinos en Mano</h6>';
        }
        if (dinoHandMobile) {
            dinoHandMobile.innerHTML = '';
        }
        
        // Agregar dinosaurios de la mano
        gameState.miMano.forEach((dino, index) => {
            const img = document.createElement('img');
            img.src = ALL_DINOS[dino.especie]?.src || ALL_DINOS['Tiranosaurio Rex'].src;
            img.alt = dino.especie;
            img.className = 'dinosaurio-mano';
            img.dataset.dinoId = dino.id_dino_ficha;
            img.dataset.dinoEspecie = dino.especie;
            
            if (dinoHandDesktop) {
                dinoHandDesktop.appendChild(img.cloneNode(true));
            }
            if (dinoHandMobile) {
                dinoHandMobile.appendChild(img.cloneNode(true));
            }
        });
    }

    function actualizarTableroUI() {
        // Limpiar tablero actual
        const todasLasZonas = mainBoard.querySelectorAll('.drop-zone');
        todasLasZonas.forEach(zona => {
            zona.innerHTML = '';
        });
        
        // Colocar dinosaurios en el tablero
        for (const recinto in gameState.miTablero) {
            const dinosaurios = gameState.miTablero[recinto];
            dinosaurios.forEach(dino => {
                colocarDinosaurioEnTablero(recinto, dino.posicion, dino.especie);
            });
        }
    }

    function colocarDinosaurioEnTablero(recintoNombre, posicion, especie) {
        // Encontrar la zona correcta
        const recinto = mainBoard.querySelector(`[data-recinto-nombre="${recintoNombre}"]`);
        if (!recinto) return;
        
        const zona = recinto.querySelector(`[data-posicion="${posicion}"]`);
        if (!zona) return;
        
        // Crear imagen del dinosaurio
        const img = document.createElement('img');
        img.src = ALL_DINOS[especie]?.src || ALL_DINOS['Tiranosaurio Rex'].src;
        img.alt = especie;
        img.className = 'dino-en-tablero';
        
        zona.appendChild(img);
    }

    function handleDinoClick(e) {
        const dinoElement = e.target.closest('.dinosaurio-mano');
        if (!dinoElement) return;
        
        if (gameState.estado !== 'colocando_dino' || gameState.jugadoresQueHanColocado.has(gameState.miId)) {
            return;
        }
        
        // Deseleccionar todos los dinosaurios
        document.querySelectorAll('.dinosaurio-mano').forEach(d => d.classList.remove('seleccionado'));
        
        if (gameState.dinoSeleccionado === dinoElement) {
            gameState.dinoSeleccionado = null;
            deshabilitarTodasLasZonas();
        } else {
            dinoElement.classList.add('seleccionado');
            gameState.dinoSeleccionado = dinoElement;
            habilitarZonasParaJugador();
        }
    }

    function handleZoneClick(e) {
        const dropZone = e.target.closest('.drop-zone');
        if (!dropZone || !dropZone.classList.contains('habilitado') || !gameState.dinoSeleccionado) return;

        hacerMovimiento(dropZone);
    }

    async function hacerMovimiento(dropZone) {
        const recintoElement = dropZone.closest('.recinto');
        const recintoNombre = recintoElement.dataset.recintoNombre;
        const posicion = parseInt(dropZone.dataset.posicion);
        const dinoId = gameState.dinoSeleccionado.dataset.dinoId;
        const especie = gameState.dinoSeleccionado.dataset.dinoEspecie;

        // Intentar hacer movimiento en el backend si está conectado
        if (gameState.backendConectado && !dinoId.startsWith('sim_')) {
            try {
                const movimiento = {
                    id_partida: gameState.partidaId,
                    id_usuario: gameState.miId,
                    id_dino_ficha: dinoId,
                    recinto: recintoNombre,
                    posicion: posicion,
                    restriccion_dado: gameState.restriccionDado
                };
                
                const response = await fetch(`${API_CONFIG.baseUrl}${API_CONFIG.endpoints.hacerMovimiento}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(movimiento)
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Error realizando movimiento');
                }
            } catch (error) {
                console.log('Error con backend, usando modo local:', error);
            }
        }

        // Aplicar movimiento localmente (funciona tanto para backend como simulado)
        hacerMovimientoLocal(dropZone, recintoNombre, posicion, especie, dinoId);
    }

    function hacerMovimientoLocal(dropZone, recintoNombre, posicion, especie, dinoId) {
        // Colocar dinosaurio en el tablero visualmente
        const img = document.createElement('img');
        img.src = gameState.dinoSeleccionado.src;
        img.alt = especie;
        img.className = 'dino-en-tablero';
        dropZone.innerHTML = '';
        dropZone.appendChild(img);
        
        // Remover dinosaurio de la mano
        gameState.dinoSeleccionado.classList.add('colocado');
        gameState.dinoSeleccionado = null;
        deshabilitarTodasLasZonas();
        
        // Actualizar estado local
        if (!gameState.miTablero[recintoNombre]) {
            gameState.miTablero[recintoNombre] = [];
        }
        gameState.miTablero[recintoNombre].push({ especie, posicion });
        
        gameState.miMano = gameState.miMano.filter(d => d.id_dino_ficha != dinoId);
        
        const playerName = players.find(p => p.id === gameState.miId).name;
        const playerColor = players.find(p => p.id === gameState.miId).colorClass;
        addHistory(`<strong class="${playerColor}">${playerName}</strong> ha colocado un <strong>${especie}</strong>.`);

        gameState.jugadoresQueHanColocado.add(gameState.miId);
        updateUI();

        // Simular que otros jugadores también juegan (solo en modo simulado)
        if (!gameState.backendConectado && gameState.jugadoresQueHanColocado.size < players.length) {
            setTimeout(() => simularMovimientoOponente(), 2000);
        } else if (gameState.jugadoresQueHanColocado.size === players.length) {
            setTimeout(finalizarTurno, 1000);
        }
    }

    function simularMovimientoOponente() {
        // Simular que un oponente hace un movimiento
        const oponentesRestantes = players.filter(p => !gameState.jugadoresQueHanColocado.has(p.id));
        if (oponentesRestantes.length > 0) {
            const oponente = oponentesRestantes[0];
            gameState.jugadoresQueHanColocado.add(oponente.id);
            addHistory(`<strong class="${oponente.colorClass}">${oponente.name}</strong> ha colocado un dinosaurio.`);
            
            if (gameState.jugadoresQueHanColocado.size < players.length) {
                setTimeout(simularMovimientoOponente, 1500);
            } else {
                setTimeout(finalizarTurno, 1000);
            }
        }
    }

    function finalizarTurno() {
        // Lógica original de finalizar turno
        gameState.turnoActual++;
        if (gameState.turnoActual > 6) {
            gameState.rondaActual++;
            if (gameState.rondaActual > 2) {
                // Fin del juego
                addHistory('<strong>¡FIN DE LA PARTIDA!</strong>');
                turnMessage.innerHTML = '<strong>¡Partida Finalizada!</strong>';
                rollButton.disabled = true;
                return;
            }
            // Nueva ronda
            gameState.turnoActual = 1;
            crearManoSimulada();
            actualizarManoUI();
        }
        
        // Cambiar jugador activo
        const currentIndex = players.findIndex(p => p.id === gameState.jugadorActivo);
        gameState.jugadorActivo = players[(currentIndex + 1) % players.length].id;
        gameState.estado = 'esperando_dado';
        gameState.jugadoresQueHanColocado.clear();
        
        updateUI();
    }

    async function handleDiceRoll() {
        try {
            rollButton.disabled = true;
            
            const resultado = Math.floor(Math.random() * 6) + 1;
            
            const diceRestrictionsMap = {
                1: { id: 'bosque', texto: 'en un recinto del Bosque' },
                2: { id: 'banos', texto: 'a la derecha del río (Baños)' },
                3: { id: 'vacio', texto: 'en un recinto vacío' },
                4: { id: 'sin_trex', texto: 'en un recinto que no contenga un T-Rex' },
                5: { id: 'llanura', texto: 'en un recinto de la Llanura' },
                6: { id: 'cafeteria', texto: 'a la izquierda del río (Cafetería)' }
            };
            
            gameState.restriccionDado = {
                ...diceRestrictionsMap[resultado],
                resultado: resultado
            };

            gameState.estado = 'colocando_dino';
            const currentPlayer = players.find(p => p.id === gameState.jugadorActivo);
            addHistory(`<strong class="${currentPlayer.colorClass}">${currentPlayer.name}</strong> ha sacado: <strong>${gameState.restriccionDado.texto}</strong>.`);
            
            if (gameState.jugadorActivo === gameState.miId) {
                habilitarZonasParaJugador();
            }
            
            updateUI();
            
        } catch (error) {
            console.error('Error lanzando dado:', error);
            rollButton.disabled = false;
        }
    }

    function habilitarZonasParaJugador() {
        if (gameState.jugadoresQueHanColocado.has(gameState.miId)) return;

        const todasLasZonas = mainBoard.querySelectorAll(`.drop-zone`);
        todasLasZonas.forEach(zone => {
            if (zone.children.length > 0) {
                zone.classList.remove('habilitado');
                zone.classList.add('bloqueado');
                return;
            }
            zone.classList.add('habilitado');
            zone.classList.remove('bloqueado');
        });
    }

    function deshabilitarTodasLasZonas() {
        mainBoard.querySelectorAll('.drop-zone').forEach(zone => zone.classList.remove('habilitado', 'bloqueado'));
    }

    function updateUI() {
        const activePlayer = players.find(p => p.id === gameState.jugadorActivo);
        const esMiTurnoDeLanzar = activePlayer.id === gameState.miId;

        rollButton.disabled = !esMiTurnoDeLanzar || gameState.estado !== 'esperando_dado';

        if (gameState.estado === 'esperando_dado') {
            turnMessage.innerHTML = esMiTurnoDeLanzar ? 
                `<strong>¡Te toca lanzar!</strong>` : 
                `Turno de <strong class="${activePlayer.colorClass}">${activePlayer.name}</strong> para lanzar.`;
        } else if (gameState.estado === 'colocando_dino') {
            if (gameState.jugadoresQueHanColocado.has(gameState.miId)) {
                turnMessage.innerHTML = `Esperando a los demás jugadores...`;
            } else {
                turnMessage.innerHTML = `<strong>Elige un dino y colócalo en tu parque.</strong>`;
            }
        }

        let totalDinosColocados = 0;
        for (const recinto in gameState.miTablero) {
            totalDinosColocados += gameState.miTablero[recinto].length;
        }

        if (dinosColocadosContador) {
            dinosColocadosContador.textContent = `${totalDinosColocados} / 12`;
        }
        if (rondaActualContador) {
            rondaActualContador.textContent = `${gameState.rondaActual} / 2`;
        }
        if (turnoActualContador) {
            turnoActualContador.textContent = `${gameState.turnoActual} / 6`;
        }

        // Actualizar indicador visual del turno (mantener lógica original)
        document.querySelectorAll('.dice-turn-icon').forEach(icon => icon.remove());
        const playerCard = document.getElementById(`player-card-${activePlayer.id}`);
        if (playerCard) {
            playerCard.querySelector('.card-body').insertAdjacentHTML('beforeend', 
                `<i class="bi bi-dice-6-fill dice-turn-icon" title="Lanza el dado"></i>`);
        }
    }

    function setupBoard(tipo) {
        const body = document.body;
        const boardImg = mainBoard.querySelector('.board-image');
        const veranoZones = document.getElementById('verano-zones');
        const inviernoZones = document.getElementById('invierno-zones');
        const infoRondaCard = document.getElementById('info-ronda-card');
        
        body.classList.remove('theme-verano', 'theme-invierno');
        
        if (tipo === 'invierno') {
            body.classList.add('theme-invierno');
            if (boardImg) boardImg.src = '../public/img/tablero invierno.jpg';
            if (veranoZones) veranoZones.style.display = 'none';
            if (inviernoZones) inviernoZones.style.display = 'block';
            document.getElementById("gameNameDisplay").textContent = "Invierno Glacial";
            if (infoRondaCard) {
                infoRondaCard.classList.remove('bg-success');
                infoRondaCard.classList.add('bg-primary');
            }
        } else {
            body.classList.add('theme-verano');
            if (boardImg) boardImg.src = '../public/img/tablero verano.jpg';
            if (veranoZones) veranoZones.style.display = 'block';
            if (inviernoZones) inviernoZones.style.display = 'none';
            document.getElementById("gameNameDisplay").textContent = "Verano Jurásico";
            if (infoRondaCard) {
                infoRondaCard.classList.add('bg-success');
                infoRondaCard.classList.remove('bg-primary');
            }
        }
        
        // Actualizar tableros de oponentes
        document.querySelectorAll('.opponent-board img').forEach(img => {
            if (boardImg) img.src = boardImg.src;
        });
    }

    function addHistory(message) {
        if (historyLog) {
            historyLog.innerHTML += `<p class="mb-1">${message}</p>`;
            historyLog.scrollTop = historyLog.scrollHeight;
        }
    }

    function setupLinkBar() {
        const copyContainer = document.getElementById('copy-container');
        const copyFeedback = document.getElementById('copy-feedback');
        const linkText = document.getElementById('link-text');
        const togglePasswordButton = document.getElementById('toggle-password');
        const passwordText = document.getElementById('password-text');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');
        let isInfoVisible = false;

        if (copyContainer) {
            copyContainer.addEventListener('click', () => {
                navigator.clipboard.writeText(linkText.dataset.link).then(() => {
                    if (copyFeedback) {
                        copyFeedback.classList.remove('d-none');
                        setTimeout(() => {
                            copyFeedback.classList.add('d-none');
                        }, 2000);
                    }
                });
            });
        }

        if (togglePasswordButton) {
            togglePasswordButton.addEventListener('click', () => {
                isInfoVisible = !isInfoVisible;
                if (passwordText) {
                    passwordText.textContent = isInfoVisible ? passwordText.dataset.password : '*'.repeat(passwordText.dataset.password.length);
                }
                if (linkText) {
                    linkText.textContent = isInfoVisible ? linkText.dataset.link : '*'.repeat(linkText.dataset.link.length);
                }
                if (eyeOpen && eyeClosed) {
                    eyeOpen.classList.toggle('d-none', !isInfoVisible);
                    eyeClosed.classList.toggle('d-none', isInfoVisible);
                }
            });
        }
    }

    function setupModals() {
        const profileModal = document.getElementById('profileModal');
        if (profileModal) {
            profileModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const playerId = parseInt(button.getAttribute('data-player-id'));
                const player = players.find(p => p.id === playerId);
                if (player) {
                    profileModal.querySelector('.modal-title').textContent = `Perfil de ${player.name}`;
                    profileModal.querySelector('#profile-pic').src = `https://placehold.co/100x100?text=${player.name.charAt(0)}`;
                    profileModal.querySelector('#profile-name').textContent = player.name;
                    profileModal.querySelector('#profile-games').textContent = Math.floor(Math.random() * 100) + 20;
                    profileModal.querySelector('#profile-wins').textContent = Math.floor(Math.random() * 30) + 5;
                    profileModal.querySelector('#profile-desc').textContent = `Un jugador ${player.name === 'Tú' ? 'experimentado' : 'entusiasta'} de Draftosaurus.`;
                }
            });
        }

        const pauseModal = document.getElementById('pauseModal');
        if (pauseModal) {
            pauseModal.addEventListener('show.bs.modal', () => {
                gameState.isPaused = true;
                addHistory(`<strong>Partida Pausada.</strong>`);
            });
            pauseModal.addEventListener('hide.bs.modal', () => {
                gameState.isPaused = false;
                addHistory(`<strong>Partida Reanudada.</strong>`);
            });
        }

        const abandonBtn = document.getElementById('abandon-btn');
        if (abandonBtn) {
            abandonBtn.addEventListener('click', () => {
                addHistory(`<strong class="text-danger">${players.find(p => p.id === gameState.miId).name} ha abandonado la partida.</strong>`);
            });
        }
    }

    function setupPlayerCards() {
        players.forEach((player) => {
            const card = document.getElementById(`player-card-${player.id}`);
            if (card) {
                card.innerHTML = `<div class="card-body text-center d-flex flex-column">
                        <p class="fw-semibold ${player.colorClass} mb-2">${player.name}</p>
                        <p class="score-icon fw-bold small mb-2"><i class="bi bi-star-fill text-warning"></i> <span class="player-score">0</span></p>
                        <div class="d-flex flex-wrap justify-content-center mt-auto" style="gap: 4px 8px;">
                            <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="T-Rex">0</p><img src="../public/img/t-rex.png" class="dino-score-icon"></div>
                            <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Triceratops">0</p><img src="../public/img/triceratops.png" class="dino-score-icon"></div>
                            <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Stegosaurus">0</p><img src="../public/img/stegosaurus.png" class="dino-score-icon"></div>
                            <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Pterodáctilo">0</p><img src="../public/img/pterodactilo.png" class="dino-score-icon"></div>
                            <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Plesiosaurio">0</p><img src="../public/img/plesiosaurio.png" class="dino-score-icon"></div>
                            <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Brachiosaurus">0</p><img src="../public/img/brachiosaurus.png" class="dino-score-icon"></div>
                        </div></div>`;
            }
        });
    }

    function startGameLoop() {
        // Actualizar periódicamente solo si el backend está conectado
        if (gameState.backendConectado) {
            setInterval(async () => {
                if (gameState.estado === 'en_curso' && !document.hidden) {
                    try {
                        await actualizarEstadoPartida();
                    } catch (error) {
                        console.error('Error en loop de juego:', error);
                        // Si falla el backend, cambiar a modo simulado
                        gameState.backendConectado = false;
                        addHistory('Conexión perdida - continuando en modo local');
                    }
                }
            }, 10000); // Cada 10 segundos
        }
    }

    function startGameWithCountdown() {
        const countdownNumber = document.getElementById('countdown-number');
        const overlay = document.getElementById('pre-game-overlay');
        let count = 3;
        
        const interval = setInterval(() => {
            count--;
            countdownNumber.textContent = count > 0 ? count : '¡JUEGA!';
            if (count < 0) {
                clearInterval(interval);
                overlay.classList.add('fade-out');
                setTimeout(initializeGame, 500);
            }
        }, 1000);
    }

    // Estilos CSS adicionales para las interacciones
    const additionalStyles = `
        .dinosaurio-mano {
            width: 50px;
            height: 50px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.3s, visibility 0.3s;
            border-radius: 10px;
        }

        .dinosaurio-mano:hover {
            transform: scale(1.1);
        }

        .dinosaurio-mano.seleccionado {
            transform: scale(1.2);
            box-shadow: 0 0 15px 5px var(--user1-color);
            background-color: rgba(13, 110, 253, 0.2);
        }

        .dinosaurio-mano.colocado {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .dino-en-tablero {
            width: 50px;
            height: 50px;
            object-fit: contain;
            pointer-events: none;
        }

        .drop-zone.habilitado {
            border: 3px dashed rgba(25, 135, 84, 0.8);
            cursor: pointer;
        }

        .drop-zone.habilitado:hover {
            background-color: rgba(25, 135, 84, 0.3);
            box-shadow: 0 0 10px rgba(25, 135, 84, 0.7);
        }

        .drop-zone.bloqueado {
            border: 3px dashed rgba(150, 150, 150, 0.6);
            cursor: not-allowed;
        }

        .dino-score-icon {
            width: 30px;
        }
    `;

    // Agregar estilos al documento
    const styleSheet = document.createElement('style');
    styleSheet.textContent = additionalStyles;
    document.head.appendChild(styleSheet);

    // Iniciar el juego con countdown
    startGameWithCountdown();
});
    </script>
</body>
</html>