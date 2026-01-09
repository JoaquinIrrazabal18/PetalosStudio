<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal - Draftosaurus</title>
    
    <!-- Enlaces externos necesarios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One:wght@400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/inicio.css">
</head>
<body>
    <div class="contenedor-principal">
        
        <!-- TARJETA PRINCIPAL: MENÚ -->
        <div id="pantalla-menu" class="tarjeta-juego aparecer">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeño"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <!-- MENÚ PRINCIPAL -->
            <div class="contenido-juego">
                <div class="text-center mb-4">
                    <i class="fas fa-home icono-grande" style="color: var(--color-verde);"></i>
                    <h3>¡Bienvenido <span id="nombre-jugador">Explorador</span>!</h3>
                    <p class="text-muted">¿Qué aventura jurásica te espera hoy?</p>
                </div>

                <div class="menu-opciones">
                    <div class="opcion-menu" onclick="mostrarPresentacion()">
                        <i class="fas fa-info-circle icono-grande" style="color: var(--color-verde);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">¿Qué es Draftosaurus?</div>
                        <div style="font-size: 0.9rem; color: #666;">Conoce el juego</div>
                    </div>

                    <div class="opcion-menu" onclick="crearPartida()">
                        <i class="fas fa-plus-circle icono-grande" style="color: var(--color-verde);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Crear Partida</div>
                        <div style="font-size: 0.9rem; color: #666;">Inicia una nueva aventura</div>
                    </div>

                    <div class="opcion-menu" onclick="unirsePartida()">
                        <i class="fas fa-users icono-grande" style="color: var(--color-verde);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Unirse a Partida</div>
                        <div style="font-size: 0.9rem; color: #666;">Únete a otros exploradores</div>
                    </div>

                    <div class="opcion-menu" onclick="verHistorial()">
                        <i class="fas fa-history icono-grande" style="color: var(--color-verde);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Historial</div>
                        <div style="font-size: 0.9rem; color: #666;">Revisa tus aventuras pasadas</div>
                    </div>

                    <div class="opcion-menu" onclick="mostrarAjustes()">
                        <i class="fas fa-cog icono-grande" style="color: var(--color-verde);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Ajustes</div>
                        <div style="font-size: 0.9rem; color: #666;">Personaliza tu experiencia</div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button class="boton-volver" onclick="cerrarSesion()">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>

        <!-- TARJETA SEPARADA: PRESENTACIÓN DEL JUEGO -->
        <div id="pantalla-presentacion" class="tarjeta-juego oculto">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeño"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAlMenu()">
                    <i class="fas fa-arrow-left"></i> Volver al Menu
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-dragon icono-grande" style="color: var(--color-dorado);"></i>
                    <h3>¿Qué es Draftosaurus?</h3>
                </div>

                <div class="text-center">
                    <img src="../public/img/Dinosaurios.png" alt="Dinosaurios de Draftosaurus" class="imagen-decorativa">
                </div>

                <div style="text-align: justify; line-height: 1.6; margin: 20px 0;">
                    <p><strong>Draftosaurus</strong> es un juego de mesa familiar y estratégico donde los jugadores compiten para construir el <strong>zoológico de dinosaurios más impresionante</strong>.</p>
                    
                    <p>Cada jugador selecciona dinosaurios por turnos, eligiendo cuidadosamente dónde ubicarlos en su parque para <strong>maximizar puntos</strong> según las reglas de las distintas zonas de exhibición.</p>
                    
                    <p>El objetivo es planificar bien el espacio, aprovechar combinaciones y restricciones para ganar la mayor cantidad de puntos posibles. Es un juego <strong>accesible, rápido y divertido</strong> que combina táctica con un tema atractivo y colorido para todas las edades.</p>
                </div>

                <button class="boton-dino boton-dorado" onclick="mostrarTablero()">
                    <i class="fas fa-play icono-pequeño"></i>
                    Ver el Tablero
                </button>
            </div>
        </div>

        <!-- TARJETA SEPARADA: TABLERO DE JUEGO -->
        <div id="pantalla-tablero" class="tarjeta-juego oculto">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeño"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAlMenu()">
                    <i class="fas fa-arrow-left"></i> Volver al Menu
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-map icono-grande" style="color: var(--color-verde);"></i>
                    <h3>Tableros de Draftosaurus</h3>
                    <p class="text-muted">Tu parque de dinosaurios</p>
                </div>

                <div class="tablero-container">
                    <img src="../public/img/tablero verano.jpg" alt="Tablero de Draftosaurus" class="imagen-decorativa">
                </div>
                <div class="tablero-Inv">  
                    <img src="../public/img/tablero invierno.jpg" alt="Tablero de Invierno" class="imagen-decorativa">
                </div>

                <div class="elementos-juego">
                    <div class="elemento-juego">
                        <img src="../public/img/Dinosaurios.png" alt="Dinosaurios disponibles" class="imagen-decorativa">
                        <h5 style="color: var(--color-texto); margin-top: 10px;">Dinosaurios</h5>
                        <p style="font-size: 0.9rem; color: #666;">6 especies diferentes, 10 dinos de cada una</p>
                    </div>
                    
                    <div class="elemento-juego">
                        <img src="../public/img/Dado2.png" alt="Dado de colocación" class="imagen-decorativa">
                        <h5 style="color: var(--color-texto); margin-top: 10px;">Dado</h5>
                        <p style="font-size: 0.9rem; color: #666;">Reglas de colocación</p>
                    </div>
                </div>

                <div class="text-center">
                    <button class="boton-dino" onclick="simularPartida()">
                        <i class="fas fa-dice icono-pequeño"></i>
                        Simular Partida
                    </button>
                </div>
            </div>
        </div>

        <!-- TARJETA SEPARADA: RESULTADOS -->
        <div id="pantalla-resultados" class="tarjeta-juego oculto">
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeño"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <div class="text-center mb-4">
                    <i class="fas fa-trophy icono-grande" style="color: var(--color-dorado);"></i>
                    <h3>¡Partida Finalizada!</h3>
                    <p class="text-muted">Resultados de <span id="jugador-resultado">Explorador</span></p>
                </div>

                <div class="estadisticas-resultado">
                    <div class="row">
                        <div class="col-4 text-center">
                            <i class="fas fa-dragon" style="color: var(--color-verde); font-size: 2rem;"></i>
                            <h4 style="color: var(--color-texto); margin: 10px 0;">12</h4>
                            <p style="color: #666;">Dinosaurios Colocados</p>
                        </div>
                        <div class="col-4 text-center">
                            <i class="fas fa-map-marker-alt" style="color: var(--color-marron); font-size: 2rem;"></i>
                            <h4 style="color: var(--color-texto); margin: 10px 0;">6</h4>
                            <p style="color: #666;">Recintos Usados</p>
                        </div>
                        <div class="col-4 text-center">
                            <i class="fas fa-dice" style="color: var(--color-dorado); font-size: 2rem;"></i>
                            <h4 style="color: var(--color-texto); margin: 10px 0;">12</h4>
                            <p style="color: #666;">Turnos Jugados</p>
                        </div>
                        <div class="col-4 text-center">
                            <i class="fas fa-sync-alt" style="color: var(--color-dorado); font-size: 2rem;"></i>
                            <h4 style="color: var(--color-texto); margin: 10px 0;">2</h4>
                            <p style="color: #666;">Rondas</p>
                        </div>
                    </div>
                </div>

                <div class="text-center" style="margin: 30px 0;">
                    <h2 style="color: var(--color-texto);">Puntuación Final</h2>
                    <div class="puntos-grandes">87 puntos</div>
                    <p class="felicitacion">
                         ¡Excelente trabajo <span id="nombre-final">Explorador</span>!
                    </p>
                </div>

                <div class="text-center">
                    <button class="boton-dino boton-dorado" onclick="volverAlMenu()">
                        <i class="fas fa-home icono-pequeño"></i>
                        Volver al Menu
                    </button>
                    
                    <button class="boton-dino" onclick="simularPartida()">
                        <i class="fas fa-redo icono-pequeño"></i>
                        Jugar de Nuevo
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/menu.js"></script>
</body>
</html>