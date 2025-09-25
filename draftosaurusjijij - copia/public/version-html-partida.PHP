<?php
session_start();
// --- DEBUG TEMPORAL ---
if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php?error=sesion');
    exit();
}
$id_usuario_actual = $_SESSION['id_usuario'];
$nombre_usuario_actual = $_SESSION['nombre_usuario'] ?? 'Tú';
$id_partida = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_partida === 0) {
    header('Location: menu.php?error=nopartida');
    exit();
}
$project_folder = basename(dirname(__DIR__));
$api_base_url = '/' . $project_folder . '/api';

require_once __DIR__ . '/../app/config/database.php';
$db = Database::getInstance()->getConnection();

// Obtener datos de la partida y jugadores
$partida = null;
$jugadores = [];
$nombre_partida = '';
$host_id = 0;
$host_nombre = '';

if ($id_partida > 0) {
    // Consulta partida (host = posicion_turno = 1)
    $stmt = $db->prepare("SELECT p.*, u.nombre_usuario as host_nombre, u.id_usuario as host_id
                          FROM partida p
                          JOIN participacion_p pa ON pa.id_partida = p.id_partida AND pa.posicion_turno = 1
                          JOIN usuario u ON pa.id_usuario = u.id_usuario
                          WHERE p.id_partida = ?");
    $stmt->execute([$id_partida]);
    $partida = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($partida) {
        $nombre_partida = $partida['nombre'] ?? $partida['nombre_partida'] ?? '';
        $host_id = $partida['host_id'];
        $host_nombre = $partida['host_nombre'];
    }

    // Consulta jugadores
    $stmt = $db->prepare("SELECT u.id_usuario, u.nombre_usuario
                          FROM participacion_p pa
                          JOIN usuario u ON pa.id_usuario = u.id_usuario
                          WHERE pa.id_partida = ?
                          ORDER BY pa.posicion_turno ASC");
    $stmt->execute([$id_partida]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draftosaurus - Partida Online</title>

    <!-- Dependencias Externas -->
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
        .theme-verano header { background-color: #ffc107 !important; }
        .theme-invierno header { background-color: #1b94cb !important; }
        .theme-invierno #info-ronda-card { background-color: #014945 !important; }
        
        /* --- POSICIONAMIENTO TABLERO VERANO --- */
        #verano-zones { display: block; }
        #invierno-zones { display: none; }

        #cont-bosque_semejanza { top: 7%; left: 5.5%; width: 27%; height: 15%; }
        #cont-bosque_semejanza .drop-zone { width: 15%; height: 100%; }
        #cont-bosque_semejanza .drop-zone[data-posicion="0"] { left: 0; }
        #cont-bosque_semejanza .drop-zone[data-posicion="1"] { left: 20%; }
        #cont-bosque_semejanza .drop-zone[data-posicion="2"] { left: 40%; }
        #cont-bosque_semejanza .drop-zone[data-posicion="3"] { left: 60%; }
        #cont-bosque_semejanza .drop-zone[data-posicion="4"] { left: 80%; }
        #cont-bosque_semejanza .drop-zone[data-posicion="5"] { left: 100%; }

        #cont-prado_diferencia { top: 43%; left: 60.5%; width: 27%; height: 15%; }
        #cont-prado_diferencia .drop-zone { width: 15%; height: 100%; }
        #cont-prado_diferencia .drop-zone[data-posicion="0"] { left: 0; }
        #cont-prado_diferencia .drop-zone[data-posicion="1"] { left: 20%; }
        #cont-prado_diferencia .drop-zone[data-posicion="2"] { left: 40%; }
        #cont-prado_diferencia .drop-zone[data-posicion="3"] { left: 60%; }
        #cont-prado_diferencia .drop-zone[data-posicion="4"] { left: 80%; }
        #cont-prado_diferencia .drop-zone[data-posicion="5"] { left: 100%; }

        #cont-pradera_amor { top: 69%; left: 11.5%; width: 20%; height: 20%; }
        #cont-pradera_amor .drop-zone { width: 45%; height: 30%; }
        #cont-pradera_amor .drop-zone[data-posicion="0"] { top: 0; left: 0; }
        #cont-pradera_amor .drop-zone[data-posicion="1"] { top: 0; left: 55%; }
        #cont-pradera_amor .drop-zone[data-posicion="2"] { top: 35%; left: 0%; }
        #cont-pradera_amor .drop-zone[data-posicion="3"] { top: 35%; left: 55%; }
        #cont-pradera_amor .drop-zone[data-posicion="4"] { top: 70%; left: 0%; }
        #cont-pradera_amor .drop-zone[data-posicion="5"] { top: 70%; left: 55%; }

        #cont-trio_frondoso { top: 40%; left: 7%; width: 20%; height: 15%; }
        #cont-trio_frondoso .drop-zone { width: 30%; height: 100%; }
        #cont-trio_frondoso .drop-zone[data-posicion="0"] { left: 35%; }
        #cont-trio_frondoso .drop-zone[data-posicion="1"] { left: 0; }
        #cont-trio_frondoso .drop-zone[data-posicion="2"] { left: 70%; }
        
        #rey_selva { top: 6%; left: 67%; width: 14%; height: 10%; }
        #isla_solitaria { top: 67%; left: 79%; width: 15%; height: 13%; }
        #rio { top: 75%; left: 50%; width: 15%; height: 15%; border-radius: 10px; }

        /* --- POSICIONAMIENTO TABLERO INVIERNO --- */
        #cont-bosque_ordenado { top: 11%; left: 5.5%; width: 27%; height: 14%; }
        #cont-bosque_ordenado .drop-zone { width: 15%; height: 100%; }
        #cont-bosque_ordenado .drop-zone[data-posicion="0"] { left: 0; }
        #cont-bosque_ordenado .drop-zone[data-posicion="1"] { left: 20%; }
        #cont-bosque_ordenado .drop-zone[data-posicion="2"] { left: 40%; }
        #cont-bosque_ordenado .drop-zone[data-posicion="3"] { left: 60%; }
        #cont-bosque_ordenado .drop-zone[data-posicion="4"] { left: 80%; }
        #cont-bosque_ordenado .drop-zone[data-posicion="5"] { left: 100%; }

        #cont-puente_enamorados_izq { top: 36%; left: 27%; width: 55%; height: 13%; }
        #cont-puente_enamorados_izq .drop-zone { width: 34%; height: 40%; }
        #cont-puente_enamorados_izq .drop-zone[data-posicion="0"] { top: 0; left: 0; }
        #cont-puente_enamorados_izq .drop-zone[data-posicion="1"] { top: 50%; left: 0; }
        #cont-puente_enamorados_izq .drop-zone[data-posicion="2"] { top: 100%; left: 0; }
        
        #cont-puente_enamorados_der { top: 36%; left: 60%; width: 47%; height: 13%; }
        #cont-puente_enamorados_der .drop-zone { width: 30%; height: 40%; }
        #cont-puente_enamorados_der .drop-zone[data-posicion="0"] { top: 0; left: 0; }
        #cont-puente_enamorados_der .drop-zone[data-posicion="1"] { top: 50%; left: 0; }
        #cont-puente_enamorados_der .drop-zone[data-posicion="2"] { top: 100%; left: 0; }
        
        #cont-piramide { top: 66%; left: 39%; width: 47%; height: 23%;}
        #cont-piramide .drop-zone { width: 33%; height: 21%; }
        #cont-piramide .drop-zone[data-posicion="0"] { top: 68%; left: 0; }
        #cont-piramide .drop-zone[data-posicion="1"] { top: 68%; left: 36%; }
        #cont-piramide .drop-zone[data-posicion="2"] { top: 68%; left: 72%; }
        #cont-piramide .drop-zone[data-posicion="3"] { top: 34%; left: 18%; }
        #cont-piramide .drop-zone[data-posicion="4"] { top: 34%; left: 54%; }
        #cont-piramide .drop-zone[data-posicion="5"] { top: 0; left: 36%; }
        
        #puesto_observacion { top: 10%; left: 65%; width: 14%; height: 11.5%;}
        #zona_cuarentena { top: 72%; left: 5%; width: 15%; height: 16%; }
        #rio_invierno { top: 67%; left: 27%; width: 7%; height: 28%; border-radius: 10px; }

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

<body class="text-dark theme-verano">
    <!-- Pantalla de espera antes de iniciar la partida -->
    <div id="waiting-room" class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-white" style="z-index:3000;">
        <h2 class="mb-3">Sala de Espera</h2>
        <p>Esperando a que todos los jugadores se unan y presionen <strong>Listo</strong>...</p>
        <div id="players-list" class="mb-4"></div>
        <button id="ready-btn" class="btn btn-success btn-lg mb-2">Listo</button>
        <button id="start-btn" class="btn btn-primary btn-lg mb-2" style="display:none;">Empezar Partida</button>
        <div id="waiting-msg" class="mt-2 text-muted"></div>
    </div>

    <div id="pre-game-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background-color: rgba(43, 23, 0, 0.9); z-index: 2000; color: white;">
        <h1 class="display-1 fw-bold" id="countdown-number">3</h1>
        <p class="lead">La partida comenzará pronto...</p>
    </div>

    <!-- Encabezado Fijo -->
    <header class="shadow-sm sticky-top">
        <nav class="container-fluid d-flex justify-content-between align-items-center p-2">
            <a class="navbar-brand text-white fw-bold border border-2 border-white p-1" href="#">DRAFTOSAURUS</a>
            <div class="text-white small d-none d-sm-flex flex-column align-items-center flex-md-row gap-md-4">
                <p class="mb-0"><span class="fw-semibold">Partida:</span>
                    <span id="gameNameDisplay"><?php echo htmlspecialchars($nombre_partida); ?></span>
                </p>
                <p class="mb-0"><span class="fw-semibold">Host:</span>
                    <span id="hostNameDisplay" class="user-color-4"><?php echo htmlspecialchars($host_nombre); ?></span>
                </p>
            </div>
            <div class="d-flex align-items-center fs-4">
                <button id="change-board-btn" class="btn btn-link text-white" title="Cambiar Tablero"><i class="bi bi-map-fill"></i></button>
                <button class="btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#pauseModal" title="Pausar Partida"><i class="bi bi-pause-circle-fill"></i></button>
                <button class="btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#settingsModal" title="Ajustes"><i class="bi bi-gear-fill"></i></button>
                <button class="btn btn-link text-white" data-bs-toggle="modal" data-bs-target="#exitModal" title="Salir de la Partida"><i class="bi bi-box-arrow-right"></i></button>
            </div>
        </nav>
    </header>

    <!-- Barra de Enlace y Contraseña -->
    <div id="navbar-extra" class="bg-body-secondary py-2 shadow-sm">
        <div class="container-fluid d-flex flex-wrap justify-content-center align-items-center gap-3 gap-md-5 small">
            <div id="copy-container" class="d-flex align-items-center gap-2" role="button" title="Hacer clic para copiar">
                <span id="link-text" class="fw-semibold" data-link="jueguito.com/enlacealapartida">******************************</span>
                <span id="copy-feedback" class="text-success fw-bold d-none">¡Copiado!</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span id="password-text" class="font-monospace bg-white px-2 py-1 rounded text-dark" data-password="ContraseñaPartida">******************</span>
                <button id="toggle-password" class="btn btn-sm btn-outline-secondary p-1" title="Mostrar/Ocultar contraseña">
                    <i id="eye-open" class="bi bi-eye-fill d-none"></i>
                    <i id="eye-closed" class="bi bi-eye-slash-fill"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal del Juego -->
    <div class="container-fluid mt-3">
        <div class="row g-4">
            <!-- Columna Izquierda -->
            <div class="col-lg-5">
                <div class="d-flex flex-column gap-4">

                    <!-- Fila para el panel de Turno -->
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
                    <!-- Fin de la Fila -->

                    <!-- Panel de Jugadores -->
                    <div class="row g-3 flex-grow-1">
                        <?php
                        // Mostrar los jugadores en las tarjetas
                        foreach ($jugadores as $i => $jugador):
                            $colorClass = 'user-color-' . (($i % 4) + 1);
                        ?>
                        <div class="col-6">
                            <div class="card h-100 player-card-clickable"
                                 id="player-card-<?php echo $i; ?>"
                                 data-bs-toggle="modal"
                                 data-bs-target="#profileModal"
                                 data-player-id="<?php echo $jugador['id_usuario']; ?>">
                                <div class="card-body text-center d-flex flex-column">
                                    <p class="fw-semibold <?php echo $colorClass; ?> mb-2"><?php echo htmlspecialchars($jugador['nombre_usuario']); ?></p>
                                    <p class="score-icon fw-bold small mb-2"><i class="bi bi-star-fill text-warning"></i> <span class="player-score">0</span></p>
                                    <div class="d-flex flex-wrap justify-content-center mt-auto" style="gap: 4px 8px;">
                                        <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="T-Rex">0</p><img src="../public/img/t-rex.png" class="dino-score-icon"></div>
                                        <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Triceratops">0</p><img src="../public/img/triceratops.png" class="dino-score-icon"></div>
                                        <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Stegosaurus">0</p><img src="../public/img/stegosaurus.png" class="dino-score-icon"></div>
                                        <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Pterodactilo">0</p><img src="../public/img/pterodactilo.png" class="dino-score-icon"></div>
                                        <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Plesiosaurio">0</p><img src="../public/img/plesiosaurio.png" class="dino-score-icon"></div>
                                        <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Brachiosaurus">0</p><img src="../public/img/brachiosaurus.png" class="dino-score-icon"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>


            <!-- Columna Derecha (Tablero Principal) -->
            <div class="col-lg-7">
                <div class="row align-items-start g-3">
                    <div class="col-md-8">
                        <div class="d-flex flex-column gap-4 h-100">
                            <!-- Mano de Dinos (Móvil) -->
                            <div id="dino-hand-mobile" class="d-lg-none gap-2 p-2 card shadow-sm mb-3"></div>
                            

                            <!-- TABLERO PRINCIPAL -->
                            <div id="mi-tablero" class="board-container shadow-lg">
                                <img src="../public/img/tablero verano.jpg" alt="Tablero Principal" class="board-image">
    
                                <!-- ZONAS VERANO -->
                                <div id="verano-zones">
                                    <div id="cont-bosque_semejanza" class="recinto" data-recinto-nombre="Bosque de la Semejanza" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div><div class="drop-zone" data-posicion="3"></div><div class="drop-zone" data-posicion="4"></div><div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-prado_diferencia" class="recinto" data-recinto-nombre="Prado de la Diferencia" data-tipo="llanura" data-lado="banos">
                                        <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div><div class="drop-zone" data-posicion="3"></div><div class="drop-zone" data-posicion="4"></div><div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-pradera_amor" class="recinto" data-recinto-nombre="Pradera del Amor" data-tipo="llanura" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div><div class="drop-zone" data-posicion="3"></div><div class="drop-zone" data-posicion="4"></div><div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-trio_frondoso" class="recinto" data-recinto-nombre="Trío Frondoso" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div>
                                    </div>
                                    <div id="rey_selva" class="recinto drop-zone" data-recinto-nombre="Rey de la Selva" data-tipo="bosque" data-lado="banos" data-posicion="0"></div>
                                    <div id="isla_solitaria" class="recinto drop-zone" data-recinto-nombre="Isla Solitaria" data-tipo="llanura" data-lado="banos" data-posicion="0"></div>
                                    <div id="rio" class="recinto drop-zone" data-recinto-nombre="Rio" data-tipo="rio" data-lado="ninguno" data-posicion="0"></div>
                                </div>
                                
                                <!-- ZONAS INVIERNO -->
                                <div id="invierno-zones">
                                     <div id="cont-bosque_ordenado" class="recinto" data-recinto-nombre="Bosque Ordenado" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div><div class="drop-zone" data-posicion="3"></div><div class="drop-zone" data-posicion="4"></div><div class="drop-zone" data-posicion="5"></div>
                                    </div>
                                    <div id="cont-puente_enamorados_izq" class="recinto" data-recinto-nombre="Puente de los Enamorados Izquierda" data-tipo="bosque" data-lado="cafeteria">
                                        <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div>
                                    </div>
                                    <div id="cont-puente_enamorados_der" class="recinto" data-recinto-nombre="Puente de los Enamorados Derecha" data-tipo="llanura" data-lado="banos">
                                         <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div>
                                    </div>
                                    <div id="cont-piramide" class="recinto" data-recinto-nombre="La Pirámide" data-tipo="llanura" data-lado="banos">
                                        <div class="drop-zone" data-posicion="0"></div><div class="drop-zone" data-posicion="1"></div><div class="drop-zone" data-posicion="2"></div><div class="drop-zone" data-posicion="3"></div><div class="drop-zone" data-posicion="4"></div><div class="drop-zone" data-posicion="5"></div>
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
                            <!-- Info de Ronda -->
                             <div id="info-ronda-card" class="card bg-success text-white p-3 shadow-sm d-none d-lg-block">
                                <p class="mb-1 small">Dinos en Parque: <strong id="dinos-colocados-contador">0 / 12</strong></p>
                                <p class="mb-1 small">Ronda: <strong id="ronda-actual-contador">1 / 2</strong></p>
                                <p class="mb-0 small">Turno: <strong id="turno-actual-contador">1 / 6</strong></p>
                            </div>
                            <!-- Mano de Dinos (Desktop) -->
                            <div id="dino-hand-desktop" class="d-none d-lg-flex flex-column align-items-center gap-2 p-2 card shadow-sm">
                                <h6 class="fw-bold small text-center my-1">Dinos en Mano</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila de Tableros de Oponentes -->
        <div class="row mt-3">
            <div class="col-12">
                <h5 class="fw-bold mb-3">Tableros de los Oponentes</h5>
                <div class="row g-4">
                    <?php
                    // Mostrar tableros de oponentes (excluyendo el usuario actual)
                    foreach ($jugadores as $i => $jugador):
                        if ($jugador['id_usuario'] == $id_usuario_actual) continue;
                        $colorClass = 'user-color-' . ((($i % 4) + 1));
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card shadow-sm opponent-board-wrapper">
                            <div class="card-header small fw-semibold <?php echo $colorClass; ?> text-start border-0">
                                <?php echo htmlspecialchars($jugador['nombre_usuario']); ?>
                            </div>
                            <div class="opponent-board" data-oponente-id="<?php echo $jugador['id_usuario']; ?>">
                                <img src="../public/img/tablero verano.jpg" class="card-img-top">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie de Página -->
    <footer class="text-center text-muted small py-4 mt-5 border-top bg-body-tertiary">
        <div class="container">
            <img src="../public/img/LOGO_NUEVO-removebg-preview.png" alt="Logo Pétalos Studio" class="mb-2" style="height: 40px;">
            <p class="mb-0">TÉRMINOS DE SERVICIO | PRIVACIDAD | CONTACTO</p>
        </div>
    </footer>

    <!-- Modales -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Perfil del Jugador</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" id="profile-pic" class="profile-pic mb-3 mx-auto">
                    <h3 id="profile-name" class="fw-bold"></h3>
                    <div class="d-flex justify-content-center gap-4 my-3 text-muted">
                        <div>
                            <div class="fw-bold" id="profile-games"></div>
                            <div class="small">Partidas</div>
                        </div>
                        <div>
                            <div class="fw-bold" id="profile-wins"></div>
                            <div class="small">Victorias</div>
                        </div>
                    </div>
                    <p id="profile-desc" class="text-muted small"></p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="pauseModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Partida Pausada</h5>
                </div>
                <div class="modal-body">
                    <p>El juego está en pausa. Presiona "Reanudar" para continuar.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Reanudar</button></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="exitModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Salir de la Partida</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres salir?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger" id="abandon-btn">Abandonar Partida</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Seguir más tarde</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Continuar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajustes</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label for="language-select" class="form-label">Idioma</label><select class="form-select" id="language-select">
                            <option selected>Español</option>
                            <option value="1">English</option>
                        </select></div>
                    <div class="mb-3"><label for="volume-slider" class="form-label">Volumen de Sonido</label><input type="range" class="form-range" id="volume-slider" min="0" max="100" step="1"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="endGameModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">¡Partida Finalizada!</h5>
                </div>
                <div class="modal-body">
                    <h4 class="text-center mb-4">Resultados Finales</h4>
                    <ul class="list-group" id="final-scores-list">
                        <!-- Los resultados se llenarán aquí -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">Jugar Otra Vez</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="quarantineModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">¡Decisión de Cuarentena!</h5>
                </div>
                <div class="modal-body">
                    <p>Tienes un dinosaurio en la <strong>Zona de Cuarentena</strong>. Elige un recinto vacío para moverlo.</p>
                    <div id="quarantine-options-list" class="list-group" style="max-height: 200px; overflow-y: auto;">
                        <!-- Opciones de movimiento se llenarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tween.js/18.6.4/tween.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const apiBaseUrl = <?php echo json_encode($api_base_url); ?>;
    const partidaId = <?php echo json_encode($id_partida); ?>;
    const userId = <?php echo json_encode($id_usuario_actual); ?>;
    const hostId = <?php echo json_encode($host_id); ?>;
    const playersListDiv = document.getElementById('players-list');
    const readyBtn = document.getElementById('ready-btn');
    const startBtn = document.getElementById('start-btn');
    const waitingMsg = document.getElementById('waiting-msg');
    const waitingRoom = document.getElementById('waiting-room');

    let estadoJugadores = [];
    let yoListo = false;
    let partidaIniciada = false;

    async function fetchEstadoJugadores() {
        // Cambia la ruta para usar el router api.php
        const res = await fetch(`${apiBaseUrl}/api.php?action=estado_espera&id_partida=${partidaId}`, { credentials: 'include' });
        const data = await res.json();
        estadoJugadores = data.jugadores || [];
        partidaIniciada = data.iniciada || false;
        renderPlayers();
        if (partidaIniciada) {
            waitingRoom.style.display = 'none';
        }
        // Si eres host y todos están listos, muestra el botón de empezar
        if (userId == hostId && estadoJugadores.every(j => j.listo)) {
            startBtn.style.display = '';
        } else {
            startBtn.style.display = 'none';
        }
    }

    function renderPlayers() {
        playersListDiv.innerHTML = estadoJugadores.map(j =>
            `<div>
                <span>${j.nombre}</span>
                <span class="badge ${j.listo ? 'bg-success' : 'bg-secondary'}">${j.listo ? 'Listo' : 'Esperando'}</span>
                ${j.id == hostId ? '<span class="badge bg-warning text-dark ms-2">Anfitrión</span>' : ''}
            </div>`
        ).join('');
        waitingMsg.textContent = estadoJugadores.filter(j => !j.listo).length > 0
            ? 'Esperando a que todos presionen Listo...'
            : '¡Todos están listos!';
    }

    readyBtn.onclick = async function() {
        // Cambia la ruta para usar el router api.php
        await fetch(`${apiBaseUrl}/api.php?action=marcar_listo&id_partida=${partidaId}&id_usuario=${userId}`, { method: 'POST', credentials: 'include' });
        yoListo = true;
        readyBtn.disabled = true;
        readyBtn.textContent = 'Listo ✔';
    };

    startBtn.onclick = async function() {
        // Cambia la ruta para usar el router api.php
        await fetch(`${apiBaseUrl}/api.php?action=iniciar&id_partida=${partidaId}`, { method: 'POST', credentials: 'include' });
        startBtn.disabled = true;
        startBtn.textContent = 'Iniciando...';
    };

    // Polling para actualizar el estado cada 2 segundos
    setInterval(fetchEstadoJugadores, 2000);
    fetchEstadoJugadores();
});
</script>
    <script>
        // --- INYECCIÓN DE VARIABLES PHP AL JS ---
        const PHP_VARS = {
            idUsuario: <?php echo json_encode($id_usuario_actual); ?>,
            nombreUsuario: <?php echo json_encode($nombre_usuario_actual); ?>,
            idPartida: <?php echo json_encode($id_partida); ?>,
            apiBaseUrl: <?php echo json_encode($api_base_url); ?>,
            nombrePartida: <?php echo json_encode($nombre_partida); ?>,
            hostId: <?php echo json_encode($host_id); ?>,
            hostNombre: <?php echo json_encode($host_nombre); ?>,
            jugadores: <?php echo json_encode($jugadores); ?>
        };
        // --- MODIFICACIÓN DEL ESTADO INICIAL ---
        document.addEventListener('DOMContentLoaded', () => {
            // --- ESTADO Y CONFIGURACIÓN DEL JUEGO ---
            const gameState = {
                partidaId: 'partida_demo_123',
                miId: 0,
                jugadorActivo: 0, // Quien lanza el dado
                turnoActual: 1, // 1 a 6
                rondaActual: 1, // 1 a 2
                estado: 'esperando_dado', // 'esperando_dado', 'colocando_dino'
                restriccionDado: null,
                dinoSeleccionado: null,
                isPaused: false,
                dinoBag: [],
                playerHands: { 0: [], 1: [], 2: [], 3: [] },
                playerBoards: { 0: {}, 1: {}, 2: {}, 3: {} }, 
                jugadoresQueHanColocado: new Set(),
                boardType: 'verano' // 'verano' o 'invierno'
            };

            const players = PHP_VARS.jugadores.map((j, i) => ({
                id: j.id_usuario,
                name: j.nombre_usuario,
                colorClass: 'user-color-' + ((i % 4) + 1)
            }));
            players.sort((a, b) => a.age - b.age);
            gameState.jugadorActivo = players[0].id;

            const ALL_DINOS = {
                'T-Rex': { src: '../public/img/t-rex.png', count: 10 },
                'Triceratops': { src: '../public/img/triceratops.png', count: 10 },
                'Stegosaurus': { src: '../public/img/stegosaurus.png', count: 10 },
                'Pterodactilo': { src: '../public/img/pterodactilo.png', count: 10 },
                'Plesiosaurio': { src: '../public/img/plesiosaurio.png', count: 10 },
                'Brachiosaurus': { src: '../public/img/brachiosaurus.png', count: 10 }
            };

            const playerProfiles = [
                { id: 0, name: 'Tú', pfp: 'https://placehold.co/100x100/0d6efd/ffffff?text=Tú', games: 128, wins: 45, desc: 'Estratega y amante de los T-Rex.' },
                { id: 1, name: 'Xavier', pfp: 'https://placehold.co/100x100/198754/ffffff?text=X', games: 99, wins: 30, desc: 'Siempre apostando por la diversidad de especies.' },
                { id: 2, name: 'Julieta', pfp: 'https://placehold.co/100x100/ffc107/ffffff?text=J', games: 210, wins: 80, desc: 'Coleccionista de parejas de dinosaurios.' },
                { id: 3, name: 'Joaquín', pfp: 'https://placehold.co/100x100/dc3545/ffffff?text=J', games: 50, wins: 15, desc: 'Nuevo en el parque, ¡pero con ganas de aprender!' }
            ];

            const diceRestrictionsMap = {
                1: { id: 'bosque', texto: 'en un recinto del Bosque' },
                2: { id: 'banos', texto: 'a la derecha del río (Baños)' },
                3: { id: 'vacio', texto: 'en un recinto vacío' },
                4: { id: 'sin_trex', texto: 'en un recinto que no contenga un T-Rex' },
                5: { id: 'llanura', texto: 'en un recinto de la Llanura' },
                6: { id: 'cafeteria', texto: 'a la izquierda del río (Cafetería)' }
            };

            // --- SELECTORES DE ELEMENTOS DEL DOM ---
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
            const infoRondaCard = document.getElementById('info-ronda-card');

            // --- LÓGICA DE JUEGO PRINCIPAL ---

            function initializeGameData() {
                const numPlayers = players.length;
                let dinosToBag = [];
                let dinosToRemovePerSpecies = 0;

                if (numPlayers === 3) {
                    dinosToRemovePerSpecies = 4;
                } else if (numPlayers === 2 || numPlayers === 4) {
                    dinosToRemovePerSpecies = 2;
                }

                for (const especie in ALL_DINOS) {
                    const count = ALL_DINOS[especie].count - dinosToRemovePerSpecies;
                    for (let i = 0; i < count; i++) {
                        dinosToBag.push({ especie, src: ALL_DINOS[especie].src });
                    }
                }
                
                gameState.dinoBag = dinosToBag.sort(() => Math.random() - 0.5);
            }

            function validarColocacion(dinoEspecie, recintoNombre, dropZone, playerId) {
                const boardState = gameState.playerBoards[playerId];
                const dinosEnRecinto = boardState[recintoNombre] || [];
                const pos = parseInt(dropZone.dataset.posicion);

                if (gameState.jugadorActivo !== playerId && gameState.restriccionDado) {
                    const recintoEl = dropZone.closest('.recinto');
                    let cumpleDado = false;
                    switch (gameState.restriccionDado.id) {
                        case 'bosque': cumpleDado = recintoEl.dataset.tipo === 'bosque'; break;
                        case 'llanura': cumpleDado = recintoEl.dataset.tipo === 'llanura'; break;
                        case 'cafeteria': cumpleDado = recintoEl.dataset.lado === 'cafeteria'; break;
                        case 'banos': cumpleDado = recintoEl.dataset.lado === 'banos'; break;
                        case 'vacio': cumpleDado = dinosEnRecinto.length === 0; break;
                        case 'sin_trex': cumpleDado = !dinosEnRecinto.some(d => d.especie === 'T-Rex'); break;
                    }
                    if (recintoEl.dataset.tipo === 'rio') cumpleDado = true;
                    if (!cumpleDado) {
                        if(playerId === gameState.miId) showToast(`Regla del dado: Debes colocar ${gameState.restriccionDado.texto}. El Río siempre es una opción.`);
                        return false;
                    }
                }

                switch (recintoNombre) {
                    case 'Bosque de la Semejanza':
                    case 'Prado de la Diferencia':
                    case 'Bosque Ordenado':
                        if (pos > 0 && !dinosEnRecinto.some(d => d.posicion === (pos - 1))) {
                           if(playerId === gameState.miId) showToast('Debes llenar este recinto de izquierda a derecha sin dejar huecos.');
                            return false;
                        }
                        if (recintoNombre === 'Bosque de la Semejanza' && dinosEnRecinto.length > 0 && dinosEnRecinto[0].especie !== dinoEspecie) {
                           if(playerId === gameState.miId) showToast('Este bosque solo puede tener dinosaurios de la misma especie.');
                            return false;
                        }
                        if (recintoNombre === 'Prado de la Diferencia' && dinosEnRecinto.some(d => d.especie === dinoEspecie)) {
                           if(playerId === gameState.miId) showToast('Este prado solo puede tener dinosaurios de especies diferentes.');
                            return false;
                        }
                        if (recintoNombre === 'Bosque Ordenado' && dinosEnRecinto.length > 0) {
                            const primeraEspecie = dinosEnRecinto[0].especie;
                            const segundaEspecie = dinosEnRecinto.find(d => d.especie !== primeraEspecie)?.especie;
                            if (segundaEspecie) {
                                const esPosicionPar = dinosEnRecinto.length % 2 === 0;
                                if (esPosicionPar && dinoEspecie !== primeraEspecie) return false;
                                if (!esPosicionPar && dinoEspecie !== segundaEspecie) return false;
                            } else {
                                if (dinoEspecie === primeraEspecie) return false;
                            }
                        }
                        break;
                    case 'Trío Frondoso':
                        if (dinosEnRecinto.length >= 3) {
                           if(playerId === gameState.miId) showToast('Este recinto no puede tener más de 3 dinosaurios.');
                            return false;
                        }
                        break;
                    case 'Rey de la Selva':
                    case 'Isla Solitaria':
                    case 'Puesto de Observación':
                    case 'Zona de Cuarentena':
                        if (dinosEnRecinto.length >= 1) {
                           if(playerId === gameState.miId) showToast('Este recinto solo puede albergar 1 dinosaurio.');
                            return false;
                        }
                        break;
                    case 'La Pirámide':
                        const count = dinosEnRecinto.length;
                        if ((count < 3 && pos > 2) || (count >= 3 && count < 5 && (pos < 3 || pos > 4)) || (count === 5 && pos !== 5) || count >= 6) {
                            if(playerId === gameState.miId) showToast('Debes llenar la pirámide por niveles: primero la base, luego el medio y al final la cima.');
                            return false;
                        }
                        const adyacentes = { 0: [1, 3], 1: [0, 2, 3, 4], 2: [1, 4], 3: [0, 1, 5], 4: [1, 2, 5], 5: [3, 4] };
                        const dinosVecinos = (adyacentes[pos] || []).map(p => dinosEnRecinto.find(d => d.posicion === p)).filter(Boolean);
                        if (dinosVecinos.some(vecino => vecino.especie === dinoEspecie)) {
                           if(playerId === gameState.miId) showToast('No puedes colocar dinosaurios de la misma especie en casillas adyacentes.');
                            return false;
                        }
                        break;
                }
                return true;
            }

            function handleDinoClick(e) {
                const dinoElement = e.target.closest('.dinosaurio-mano');
                if (!dinoElement) return;
                if (gameState.estado !== 'colocando_dino' || gameState.jugadoresQueHanColocado.has(gameState.miId)) {
                    return;
                }
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
                if (!dropZone || !dropZone.classList.contains('habilitado') || !gameState.dinoSeleccionado || gameState.jugadoresQueHanColocado.has(gameState.miId)) return;
                
                placeDinoForPlayer(gameState.miId, gameState.dinoSeleccionado, dropZone);
            }

            function placeDinoForPlayer(playerId, dinoElement, dropZone) {
                const dinoEspecie = dinoElement.dataset.dinoEspecie;
                const recintoNombre = dropZone.closest('.recinto').dataset.recintoNombre;
                const posicion = parseInt(dropZone.dataset.posicion);

                if (!validarColocacion(dinoEspecie, recintoNombre, dropZone, playerId)) {
                    return;
                }

                if (playerId === gameState.miId) {
                    const dinoEnTablero = document.createElement('img');
                    dinoEnTablero.src = dinoElement.src;
                    dinoEnTablero.alt = dinoEspecie;
                    dinoEnTablero.classList.add('dino-en-tablero');
                    dropZone.innerHTML = '';
                    dropZone.appendChild(dinoEnTablero);
                }
                 dinoElement.classList.add('colocado');

                if (!gameState.playerBoards[playerId][recintoNombre]) {
                    gameState.playerBoards[playerId][recintoNombre] = [];
                }
                gameState.playerBoards[playerId][recintoNombre].push({ especie: dinoEspecie, posicion: posicion });
                
                const dinoIdInHand = dinoElement.dataset.dinoId;
                gameState.playerHands[playerId] = gameState.playerHands[playerId].filter(d => d.id != dinoIdInHand);

                const playerName = players.find(p => p.id === playerId).name;
                const playerColor = players.find(p => p.id === playerId).colorClass;
                addHistory(`<strong class="${playerColor}">${playerName}</strong> ha colocado un <strong>${dinoEspecie}</strong>.`);
                
                updatePlayerCardCounts(playerId);
                updateLiveScores();

                if (playerId === gameState.miId) {
                    gameState.dinoSeleccionado = null;
                    deshabilitarTodasLasZonas();
                }
                
                gameState.jugadoresQueHanColocado.add(playerId);
                updateUI();

                if (gameState.jugadoresQueHanColocado.size === players.length) {
                    setTimeout(finalizarTurno, 1000);
                }
            }


            function finalizarTurno() {
                const manosPasadas = {};
                for (let i = 0; i < players.length; i++) {
                    const jugadorActual = players[i];
                    const jugadorSiguiente = players[(i + 1) % players.length];
                    manosPasadas[jugadorSiguiente.id] = gameState.playerHands[jugadorActual.id];
                }
                gameState.playerHands = manosPasadas;


                gameState.turnoActual++;
                if (gameState.turnoActual > 6) {
                    gameState.rondaActual++;
                    if (gameState.rondaActual > 2) {
                        endGame();
                        return;
                    }
                    startRound();
                } else {
                    const currentIndex = players.findIndex(p => p.id === gameState.jugadorActivo);
                    gameState.jugadorActivo = players[(currentIndex + 1) % players.length].id;
                    gameState.estado = 'esperando_dado';
                    gameState.jugadoresQueHanColocado.clear();
                    actualizarManoUI(gameState.playerHands[gameState.miId]);
                    updateUI();
                }
            }

            function startRound() {
                addHistory(`<strong>--- INICIO DE LA RONDA ${gameState.rondaActual} ---</strong>`);
                gameState.turnoActual = 1;
                
                players.forEach(player => {
                    gameState.playerHands[player.id] = [];
                    for (let j = 0; j < 6; j++) {
                        if (gameState.dinoBag.length > 0) {
                            const dino = gameState.dinoBag.pop();
                            gameState.playerHands[player.id].push({ id: `dino-${player.id}-${j}-${gameState.rondaActual}`, ...dino });
                        }
                    }
                });

                actualizarManoUI(gameState.playerHands[gameState.miId]);
                updateUI();
            }

            function actualizarManoUI(mano) {
                dinoHandDesktop.innerHTML = '';
                dinoHandMobile.innerHTML = '';
                const title = document.createElement('h6');
                title.className = 'fw-bold small text-center my-1';
                title.textContent = 'Dinos en Mano';
                dinoHandDesktop.appendChild(title);

                mano.forEach(dino => {
                    const img = document.createElement('img');
                    img.src = dino.src;
                    img.alt = dino.especie;
                    img.className = 'dinosaurio-mano';
                    img.dataset.dinoId = dino.id;
                    img.dataset.dinoEspecie = dino.especie;
                    dinoHandDesktop.appendChild(img);
                    dinoHandMobile.appendChild(img.cloneNode(true));
                });
            }

            function habilitarZonasParaJugador() {
                 if(gameState.jugadoresQueHanColocado.has(gameState.miId)) return;

                const todasLasZonas = mainBoard.querySelectorAll(`#${gameState.boardType}-zones .drop-zone`);
                todasLasZonas.forEach(zone => {
                    if (zone.children.length > 0 && zone.closest('.recinto').dataset.recintoNombre !== 'Rio') {
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
                    turnMessage.innerHTML = esMiTurnoDeLanzar ? `<strong>¡Te toca lanzar!</strong>` : `Turno de <strong class="${activePlayer.colorClass}">${activePlayer.name}</strong> para lanzar.`;
                } else if (gameState.estado === 'colocando_dino') {
                    if (gameState.jugadoresQueHanColocado.has(gameState.miId)) {
                        turnMessage.innerHTML = `Esperando a los demás jugadores...`;
                    } else {
                        turnMessage.innerHTML = `<strong>Elige un dino y colócalo en tu parque.</strong>`;
                    }
                }
                
                let totalDinosColocados = 0;
                for(const recinto in gameState.playerBoards[gameState.miId]){
                    totalDinosColocados += gameState.playerBoards[gameState.miId][recinto].length;
                }

                dinosColocadosContador.textContent = `${totalDinosColocados} / 12`;
                rondaActualContador.textContent = `${gameState.rondaActual} / 2`;
                turnoActualContador.textContent = `${gameState.turnoActual} / 6`;
                
                document.querySelectorAll('.dice-turn-icon').forEach(icon => icon.remove());
                const playerCard = document.getElementById(`player-card-${activePlayer.id}`);
                if (playerCard) {
                    playerCard.querySelector('.card-body').insertAdjacentHTML('beforeend', `<i class="bi bi-dice-6-fill dice-turn-icon" title="Lanza el dado"></i>`);
                }
            }

            function addHistory(message) {
                historyLog.innerHTML += `<p class="mb-1">${message}</p>`;
                historyLog.scrollTop = historyLog.scrollHeight;
            }

            function showToast(message) {
                const toastContainer = document.getElementById('toast-container') || document.createElement('div');
                if(!toastContainer.id) {
                    toastContainer.id = 'toast-container';
                    toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
                    toastContainer.style.zIndex = 1055;
                    document.body.appendChild(toastContainer);
                }
                const toastEl = document.createElement('div');
                toastEl.className = 'toast show align-items-center text-white bg-danger border-0';
                toastEl.setAttribute('role', 'alert');
                toastEl.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
                toastContainer.appendChild(toastEl);
                const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                toast.show();
            }
            
            function setupBoard(type) {
                gameState.boardType = type;
                const body = document.body;
                const boardImg = mainBoard.querySelector('.board-image');
                const veranoZones = document.getElementById('verano-zones');
                const inviernoZones = document.getElementById('invierno-zones');
                
                body.classList.remove('theme-verano', 'theme-invierno');

                if (type === 'invierno') {
                    body.classList.add('theme-invierno');
                    boardImg.src = '../public/img/tablero invierno.jpg';
                    veranoZones.style.display = 'none';
                    inviernoZones.style.display = 'block';
                    document.getElementById("gameNameDisplay").textContent = "Invierno Glacial";
                    infoRondaCard.classList.remove('bg-success');
                    infoRondaCard.classList.add('bg-primary');
                } else {
                    body.classList.add('theme-verano');
                    boardImg.src = '../public/img/tablero verano.jpg';
                    veranoZones.style.display = 'block';
                    inviernoZones.style.display = 'none';
                    document.getElementById("gameNameDisplay").textContent = "Verano Jurásico";
                    infoRondaCard.classList.add('bg-success');
                    infoRondaCard.classList.remove('bg-primary');
                }

                document.querySelectorAll('.opponent-board img').forEach(img => img.src = boardImg.src);
            }

            function init() {
                [dinoHandDesktop, dinoHandMobile, mainBoard].forEach(el => el.addEventListener('click', e => {
                    if (e.target.closest('.dinosaurio-mano')) handleDinoClick(e);
                    else if (e.target.closest('.drop-zone')) handleZoneClick(e);
                }));
                rollButton.addEventListener('click', () => rollDiceAnimation());
                
                changeBoardBtn.addEventListener('click', () => {
                    if(gameState.rondaActual > 1 || gameState.turnoActual > 1) {
                        showToast("No se puede cambiar el tablero una vez iniciada la partida.");
                        return;
                    }
                    const newType = gameState.boardType === 'verano' ? 'invierno' : 'verano';
                    setupBoard(newType);
                });

                setupLinkBar();
                setupModals();
                initializeGameData();
                setupPlayerCards();
                players.forEach(p => updatePlayerCardCounts(p.id));
                startRound();
                animate();
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

                copyContainer.addEventListener('click', () => {
                    navigator.clipboard.writeText(linkText.dataset.link).then(() => {
                        copyFeedback.classList.remove('d-none');
                        setTimeout(() => { copyFeedback.classList.add('d-none'); }, 2000);
                    });
                });

                togglePasswordButton.addEventListener('click', () => {
                    isInfoVisible = !isInfoVisible;
                    passwordText.textContent = isInfoVisible ? passwordText.dataset.password : '*'.repeat(passwordText.dataset.password.length);
                    linkText.textContent = isInfoVisible ? linkText.dataset.link : '*'.repeat(linkText.dataset.link.length);
                    eyeOpen.classList.toggle('d-none', !isInfoVisible);
                    eyeClosed.classList.toggle('d-none', isInfoVisible);
                });
            }

            function setupModals() {
                const profileModal = document.getElementById('profileModal');
                profileModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const playerId = parseInt(button.getAttribute('data-player-id'));
                    const profileData = playerProfiles.find(p => p.id === playerId) || players.find(p => p.id === playerId);
                    if (profileData) {
                        profileModal.querySelector('.modal-title').textContent = `Perfil de ${profileData.name}`;
                        profileModal.querySelector('#profile-pic').src = profileData.pfp || `https://placehold.co/100x100?text=${profileData.name.charAt(0)}`;
                        profileModal.querySelector('#profile-name').textContent = profileData.name;
                        profileModal.querySelector('#profile-games').textContent = profileData.games || 'N/A';
                        profileModal.querySelector('#profile-wins').textContent = profileData.wins || 'N/A';
                        profileModal.querySelector('#profile-desc').textContent = profileData.desc || 'Un nuevo jugador en el parque.';
                    }
                });

                const pauseModal = document.getElementById('pauseModal');
                pauseModal.addEventListener('show.bs.modal', () => {
                    gameState.isPaused = true;
                    addHistory(`<strong>Partida Pausada.</strong>`);
                });
                pauseModal.addEventListener('hide.bs.modal', () => {
                    gameState.isPaused = false;
                    addHistory(`<strong>Partida Reanudada.</strong>`);
                });

                document.getElementById('abandon-btn').addEventListener('click', () => {
                    addHistory(`<strong class="text-danger">${players.find(p => p.id === gameState.miId).name} ha abandonado la partida.</strong>`);
                });
            }

            const container = document.getElementById('dice-container');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);
            scene.add(new THREE.AmbientLight(0xffffff, 0.8));
            const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
            directionalLight.position.set(5, 10, 7.5);
            scene.add(directionalLight);

            const loader = new THREE.TextureLoader();
            const materials = [
                new THREE.MeshLambertMaterial({ map: loader.load('../public/img/bosque.png') }), 
                new THREE.MeshLambertMaterial({ map: loader.load('../public/img/banos.png') }),
                new THREE.MeshLambertMaterial({ map: loader.load('../public/img/vacio.png') }),
                new THREE.MeshLambertMaterial({ map: loader.load('../public/img/t-rexs.png') }),
                new THREE.MeshLambertMaterial({ map: loader.load('../public/img/rocas.png') }),
                new THREE.MeshLambertMaterial({ map: loader.load('../public/img/cafeteria.png') })
            ];

            const dice = new THREE.Mesh(new THREE.BoxGeometry(), materials);
            scene.add(dice);
            camera.position.z = 1.8;

            function animate(time) {
                requestAnimationFrame(animate);
                if (typeof TWEEN !== 'undefined') TWEEN.update(time);
                renderer.render(scene, camera);
            }

            function rollDiceAnimation() {
                 if (gameState.estado !== 'esperando_dado') return;
                
                rollButton.disabled = true;
                const result = Math.floor(Math.random() * 6) + 1;

                gameState.restriccionDado = { ...diceRestrictionsMap[result], resultado: result };

                 const targetRotations = {
                    1: { x: 0, y: Math.PI / 2, z: 0 }, 2: { x: 0, y: -Math.PI / 2, z: 0 }, 3: { x: Math.PI / 2, y: 0, z: 0 },
                    4: { x: -Math.PI / 2, y: 0, z: 0 }, 5: { x: 0, y: 0, z: 0 }, 6: { x: 0, y: Math.PI, z: 0 }
                };
                
                const finalRotation = {
                    x: targetRotations[result].x + (Math.PI * 4), y: targetRotations[result].y + (Math.PI * 4), z: targetRotations[result].z + (Math.PI * 4)
                };

                new TWEEN.Tween(dice.rotation)
                    .to(finalRotation, 1500).easing(TWEEN.Easing.Cubic.Out)
                    .onComplete(() => {
                        dice.rotation.set(targetRotations[result].x, targetRotations[result].y, targetRotations[result].z);
                        gameState.estado = 'colocando_dino';
                        const currentPlayer = players.find(p => p.id === gameState.jugadorActivo);
                        addHistory(`<strong class="${currentPlayer.colorClass}">${currentPlayer.name}</strong> ha sacado: <strong>${diceRestrictionsMap[result].texto}</strong>.`);
                        habilitarZonasParaJugador();
                        updateUI();
                    }).start();
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
                                    <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Pterodactilo">0</p><img src="../public/img/pterodactilo.png" class="dino-score-icon"></div>
                                    <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Plesiosaurio">0</p><img src="../public/img/plesiosaurio.png" class="dino-score-icon"></div>
                                    <div class="text-center"><p class="fw-normal mb-0 small dino-count" data-dino="Brachiosaurus">0</p><img src="../public/img/brachiosaurus.png" class="dino-score-icon"></div>
                                </div></div>`;
                    }
                });
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
                        init();
                    }
                }, 1000);
            }
            
            function endGame() {
                gameState.estado = 'fin';
                updateUI();

                const myBoard = gameState.playerBoards[gameState.miId];
                if (myBoard['Zona de Cuarentena'] && myBoard['Zona de Cuarentena'].length > 0) {
                    handleQuarantineChoice(); 
                } else {
                    finalizeScoreCalculation();
                }
            }
            
            function finalizeScoreCalculation() {
                addHistory(`<strong>¡FIN DE LA PARTIDA!</strong>`);
                turnMessage.innerHTML = '<strong>¡Partida Finalizada!</strong>';
                rollButton.disabled = true;
                const finalScores = calculateFinalScores();

                finalScores.forEach(playerScore => {
                    const board = gameState.playerBoards[playerScore.id];
                    playerScore.trexCount = Object.values(board).flat().filter(d => d.especie === 'T-Rex').length;
                });

                const scoresList = document.getElementById('final-scores-list');
                scoresList.innerHTML = '';
                
                finalScores.sort((a, b) => {
                    if (b.score !== a.score) {
                        return b.score - a.score;
                    }
                    return a.trexCount - b.trexCount; 
                });

                let lastScore = -1, lastTrexCount = -1, rank = 0;
                finalScores.forEach((playerScore, index) => {
                    if (playerScore.score !== lastScore || playerScore.trexCount !== lastTrexCount) {
                        rank = index + 1;
                        lastScore = playerScore.score;
                        lastTrexCount = playerScore.trexCount;
                    }
                    const medal = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : '';
                    scoresList.innerHTML += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold fs-5 ${playerScore.colorClass}">${medal} ${playerScore.name}</span>
                                <small class="text-muted d-block">T-Rex: ${playerScore.trexCount}</small>
                            </div>
                            <span class="badge bg-primary rounded-pill fs-6">${playerScore.score} Puntos</span>
                        </li>`;
                });
                
                const endGameModal = new bootstrap.Modal(document.getElementById('endGameModal'));
                endGameModal.show();
            }

            function handleQuarantineChoice() {
                const quarantineModalEl = document.getElementById('quarantineModal');
                const quarantineModal = new bootstrap.Modal(quarantineModalEl);
                const optionsList = document.getElementById('quarantine-options-list');
                optionsList.innerHTML = '';

                const myBoard = gameState.playerBoards[gameState.miId];
                const dino = myBoard['Zona de Cuarentena'][0];

                const allZones = document.querySelectorAll(`#${gameState.boardType}-zones .drop-zone`);
                allZones.forEach(zone => {
                    const recinto = zone.closest('.recinto');
                    if(zone.children.length === 0) { // Can only move to empty spots
                        const option = document.createElement('a');
                        option.href = '#';
                        option.className = 'list-group-item list-group-item-action';
                        option.textContent = recinto.dataset.recintoNombre;
                        option.dataset.recintoNombre = recinto.dataset.recintoNombre;
                        option.dataset.posicion = zone.dataset.posicion;
                        optionsList.appendChild(option);
                    }
                });

                const quarantineClickHandler = (e) => {
                    e.preventDefault();
                    const target = e.target;
                    const targetRecinto = target.dataset.recintoNombre;
                    const targetPos = parseInt(target.dataset.posicion);
                    
                    delete myBoard['Zona de Cuarentena'];
                    if (!myBoard[targetRecinto]) myBoard[targetRecinto] = [];
                    myBoard[targetRecinto].push({ especie: dino.especie, posicion: targetPos });

                    document.querySelector('#zona_cuarentena').innerHTML = '';
                    const targetZone = document.querySelector(`.recinto[data-recinto-nombre="${targetRecinto}"] .drop-zone[data-posicion="${targetPos}"]`);
                     if(targetZone) {
                        const dinoImg = document.createElement('img');
                        dinoImg.src = ALL_DINOS[dino.especie].src;
                        dinoImg.className = 'dino-en-tablero';
                        targetZone.appendChild(dinoImg);
                    }
                    
                    quarantineModal.hide();
                    quarantineModalEl.removeEventListener('click', quarantineClickHandler);
                    finalizeScoreCalculation();
                };
                
                quarantineModalEl.addEventListener('click', quarantineClickHandler);
                quarantineModal.show();
            }

            function calculateLiveScore(playerId) {
                const board = gameState.playerBoards[playerId];
                let totalScore = 0;
                for (const recinto in board) {
                    const dinos = board[recinto];
                    if (dinos.length === 0) continue;
                    switch (recinto) {
                        case 'Bosque de la Semejanza': totalScore += ({ 1: 1, 2: 4, 3: 8, 4: 12, 5: 18, 6: 24 }[dinos.length] || 0); break;
                        case 'Prado de la Diferencia': totalScore += ({ 1: 1, 2: 3, 3: 6, 4: 10, 5: 15, 6: 21 }[dinos.length] || 0); break;
                        case 'Pradera del Amor':
                            const counts = dinos.reduce((acc, dino) => { acc[dino.especie] = (acc[dino.especie] || 0) + 1; return acc; }, {});
                            for (const especie in counts) { totalScore += Math.floor(counts[especie] / 2) * 5; }
                            break;
                        case 'Trío Frondoso': if (dinos.length === 3) totalScore += 7; break;
                        case 'Isla Solitaria':
                            if(dinos.length > 0) {
                                const especieUnica = dinos[0].especie;
                                if (Object.values(board).flat().filter(d => d.especie === especieUnica).length === 1) totalScore += 7;
                            }
                            break;
                        case 'Bosque Ordenado': totalScore += ({ 1: 2, 2: 4, 3: 8, 4: 12, 5: 18, 6: 24 }[dinos.length] || 0); break;
                        case 'Puente de los Enamorados Izquierda': break;
                        case 'Puente de los Enamorados Derecha':
                            const izq = board['Puente de los Enamorados Izquierda'] || [], der = board['Puente de los Enamorados Derecha'] || [];
                            const countsIzq = izq.reduce((acc, dino) => { acc[dino.especie] = (acc[dino.especie] || 0) + 1; return acc; }, {});
                            const countsDer = der.reduce((acc, dino) => { acc[dino.especie] = (acc[dino.especie] || 0) + 1; return acc; }, {});
                            for(const especie in countsIzq) { if(countsDer[especie]) { totalScore += Math.min(countsIzq[especie], countsDer[especie]) * 6; } }
                            break;
                        case 'La Pirámide':
                            dinos.forEach(dino => {
                                if (dino.posicion <= 2) totalScore += 1;
                                else if (dino.posicion <= 4) totalScore += 2;
                                else if (dino.posicion === 5) totalScore += 3;
                            });
                            break;
                        case 'Rio': totalScore += dinos.length; break;
                    }
                }
                const recintosConTRex = new Set();
                for (const recinto in board) {
                    if (board[recinto].some(d => d.especie === 'T-Rex') && recinto !== 'Rio') {
                        recintosConTRex.add(recinto);
                    }
                }
                totalScore += recintosConTRex.size;
                return totalScore;
            }

            function calculateFinalScores() {
                const allPlayerScores = [];
                players.forEach(player => {
                    let totalScore = calculateLiveScore(player.id);
                    const board = gameState.playerBoards[player.id];
                    // Add scores from competitive enclosures
                    if(board['Rey de la Selva'] && board['Rey de la Selva'].length > 0) {
                        const miEspecie = board['Rey de la Selva'][0].especie;
                        let tengoMas = true;
                        const miConteo = Object.values(board).flat().filter(d => d.especie === miEspecie).length;
                        for (const otherPlayer of players) {
                            if (otherPlayer.id === player.id) continue;
                            const otroConteo = Object.values(gameState.playerBoards[otherPlayer.id]).flat().filter(d => d.especie === miEspecie).length;
                            if (otroConteo > miConteo) { tengoMas = false; break; }
                        }
                        if (tengoMas) totalScore += 7;
                    }
                    if(board['Puesto de Observación'] && board['Puesto de Observación'].length > 0) {
                        const especieObservada = board['Puesto de Observación'][0].especie;
                        const playerIndex = players.findIndex(p => p.id === player.id);
                        const rightPlayerId = players[(playerIndex + 1) % players.length].id;
                        const conteoDerecha = Object.values(gameState.playerBoards[rightPlayerId]).flat().filter(d => d.especie === especieObservada).length;
                        totalScore += conteoDerecha * 2;
                    }

                    allPlayerScores.push({ id: player.id, name: player.name, colorClass: player.colorClass, score: totalScore });
                });
                return allPlayerScores;
            }

            function updateLiveScores() {
                players.forEach(player => {
                    const score = calculateLiveScore(player.id);
                    const card = document.getElementById(`player-card-${player.id}`);
                    if (card) card.querySelector('.player-score').textContent = score;
                });
            }
            
            function updatePlayerCardCounts(playerId) {
                const card = document.getElementById(`player-card-${playerId}`);
                if (!card) return;
                const allDinosOnBoard = Object.values(gameState.playerBoards[playerId] || {}).flat();
                card.querySelectorAll('.dino-count').forEach(countEl => {
                    const dinoType = countEl.dataset.dino;
                    const count = allDinosOnBoard.filter(d => d.especie === dinoType).length;
                    countEl.textContent = count;
                });
            }

            startGameWithCountdown();
        });
    </script>
</body>

</html>

