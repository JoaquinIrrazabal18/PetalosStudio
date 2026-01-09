<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a Partida - Draftosaurus</title>
    
    <!-- Enlaces externos necesarios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One:wght@400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/inicio.css">
</head>
<body>
    <div class="contenedor-principal">
        
        <!-- TARJETA PRINCIPAL: UNIRSE A PARTIDA -->
        <div id="pantalla-unirse" class="tarjeta-juego aparecer">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <!-- CONTENIDO PRINCIPAL -->
            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAlMenu()">
                    <i class="fas fa-arrow-left"></i> Volver al Menu
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-users icono-grande" style="color: var(--color-verde);"></i>
                    <h3>Unirse a Partida</h3>
                    <p class="text-muted">¡Únete a otros exploradores en su aventura jurásica!</p>
                </div>

                <!-- OPCIONES DE UNIRSE -->
                <div class="menu-opciones">
                    <div class="opcion-menu" onclick="mostrarCodigo()">
                        <i class="fas fa-key icono-grande" style="color: var(--color-dorado);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Código de Partida</div>
                        <div style="font-size: 0.9rem; color: #666;">Ingresa el código que te dieron</div>
                    </div>

                    <div class="opcion-menu" onclick="mostrarPartidasDisponibles()">
                        <i class="fas fa-list icono-grande" style="color: var(--color-verde);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Partidas Disponibles</div>
                        <div style="font-size: 0.9rem; color: #666;">Buscar partidas públicas</div>
                    </div>

                    <div class="opcion-menu" onclick="partidaRapida()">
                        <i class="fas fa-bolt icono-grande" style="color: var(--color-marron);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Partida Rápida</div>
                        <div style="font-size: 0.9rem; color: #666;">Búsqueda automática</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TARJETA SEPARADA: INGRESAR CÓDIGO -->
        <div id="pantalla-codigo" class="tarjeta-juego oculto">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAUnirse()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-key icono-grande" style="color: var(--color-dorado);"></i>
                    <h3>Código de Partida</h3>
                    <p class="text-muted">Ingresa el código que te proporcionaron</p>
                </div>

                <form id="formulario-codigo">
                    <div class="mb-3">
                        <label class="form-label" for="codigo-partida">
                            <i class="fas fa-hashtag"></i> Código de la Partida:
                        </label>
                        <input type="text" class="form-control campo-formulario" id="codigo-partida"
                            placeholder="Ej: DINO-2024-ABC" maxlength="15" required style="text-transform: uppercase;">
                        <div class="form-text">El código debe tener el formato: DINO-XXXX-XXX</div>
                    </div>

                    <button type="submit" class="boton-dino boton-dorado">
                        <i class="fas fa-sign-in-alt icono-pequeno"></i>
                        Unirse a Partida
                    </button>
                </form>
            </div>
        </div>

        <!-- TARJETA SEPARADA: PARTIDAS DISPONIBLES -->
        <div id="pantalla-disponibles" class="tarjeta-juego oculto">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAUnirse()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-list icono-grande" style="color: var(--color-verde);"></i>
                    <h3>Partidas Disponibles</h3>
                    <p class="text-muted">Selecciona una partida para unirte</p>
                </div>

                <div class="mb-3">
                    <button class="boton-dino" onclick="buscarPartidas()">
                        <i class="fas fa-search icono-pequeno"></i>
                        Buscar Partidas
                    </button>
                </div>

                <!-- Lista de partidas disponibles -->
                <div id="lista-partidas" class="lista-partidas">
                    <div class="partida-item">
                        <div class="partida-info">
                            <h5>Expedición Jurásica</h5>
                            <p><i class="fas fa-users"></i> 2/4 jugadores | <i class="fas fa-clock"></i> Esperando</p>
                            <p><i class="fas fa-user"></i> Host: DinoExplorer</p>
                        </div>
                        <button class="boton-dino" onclick="unirsePartida('DINO-2024-001')" style="margin: 0; padding: 10px 15px;">
                            Unirse
                        </button>
                    </div>

                    <div class="partida-item">
                        <div class="partida-info">
                            <h5>Safari Prehistórico</h5>
                            <p><i class="fas fa-users"></i> 1/3 jugadores | <i class="fas fa-clock"></i> Esperando</p>
                            <p><i class="fas fa-user"></i> Host: TRexFan</p>
                        </div>
                        <button class="boton-dino" onclick="unirsePartida('DINO-2024-002')" style="margin: 0; padding: 10px 15px;">
                            Unirse
                        </button>
                    </div>

                    <div class="partida-item">
                        <div class="partida-info">
                            <h5>Reino de los Gigantes</h5>
                            <p><i class="fas fa-users"></i> 3/4 jugadores | <i class="fas fa-clock"></i> Esperando</p>
                            <p><i class="fas fa-user"></i> Host: VelociraptorKing</p>
                        </div>
                        <button class="boton-dino" onclick="unirsePartida('DINO-2024-003')" style="margin: 0; padding: 10px 15px;">
                            Unirse
                        </button>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <p class="text-muted">¿No encuentras una partida? <a href="crear_partida.php" style="color: var(--color-verde);">Crea una nueva</a></p>
                </div>
            </div>
        </div>

        <!-- TARJETA SEPARADA: PARTIDA RÁPIDA -->
        <div id="pantalla-rapida" class="tarjeta-juego oculto">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAUnirse()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-bolt icono-grande" style="color: var(--color-marron);"></i>
                    <h3>Partida Rápida</h3>
                    <p class="text-muted">Te conectaremos con otros jugadores automáticamente</p>
                </div>

                <div class="text-center">
                    <div class="busqueda-estado mb-4">
                        <i class="fas fa-search fa-spin" style="font-size: 2rem; color: var(--color-verde); margin-bottom: 20px;"></i>
                        <h4>Buscando jugadores...</h4>
                        <p class="text-muted">Tiempo estimado: 30 segundos</p>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <button class="boton-dino boton-marron" onclick="iniciarBusquedaRapida()">
                        <i class="fas fa-play icono-pequeno"></i>
                        Iniciar Búsqueda
                    </button>

                    <button class="boton-volver mt-2" onclick="cancelarBusqueda()" style="margin-top: 10px;">
                        <i class="fas fa-times"></i> Cancelar Búsqueda
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/unirse_partida.js"></script>
</body>
</html>