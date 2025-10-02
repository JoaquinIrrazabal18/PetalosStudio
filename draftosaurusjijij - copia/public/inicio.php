<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draftosaurus - Juego Online</title>

    <!-- Enlaces externos necesarios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One:wght@400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/inicio.css">
</head>

<body>
    <div class="contenedor-principal">
        <div class="tarjeta-juego aparecer">
            
            <!-- ENCABEZADO DEL JUEGO -->
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <!-- PANTALLA 1: VERIFICACIÓN DE EDAD -->
            <div id="pantalla-edad" class="contenido-juego">
                <div class="text-center mb-4">
                    <i class="fas fa-birthday-cake icono-grande" style="color: var(--color-dorado);"></i>
                    <h3>¡Bienvenido aventurero!</h3>
                    <p class="text-muted">Para comenzar necesitamos conocer tu edad</p>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="edad-usuario">
                        <i class="fas fa-calendar-alt"></i> Tu edad:
                    </label>
                    <input type="number" class="form-control campo-formulario" id="edad-usuario"
                        placeholder="Ingresa tu edad" min="0" max="100">
                </div>

                <button class="boton-dino boton-dorado" onclick="verificarEdad()">
                    <i class="fas fa-check"></i> Continuar
                </button>
            </div>

            <!-- PANTALLA 2: OPCIONES DE ACCESO -->
            <div id="pantalla-bienvenida" class="contenido-juego oculto">
                <div class="text-center mb-4">
                    <i class="fas fa-door-open icono-grande" style="color: var(--color-verde);"></i>
                    <h3>¿Cómo quieres jugar?</h3>
                    <p class="text-muted">Elige tu modo de aventura preferido</p>
                </div>

                <button class="boton-dino" onclick="mostrarModoInvitado()">
                    <i class="fas fa-user-ninja icono-pequeno"></i>
                    Entrar como Invitado
                </button>

                <button class="boton-dino boton-dorado" onclick="mostrarRegistro()">
                    <i class="fas fa-user-plus icono-pequeno"></i>
                    Registrarse
                </button>

                <button class="boton-dino boton-marron" onclick="mostrarLogin()">
                    <i class="fas fa-sign-in-alt icono-pequeno"></i>
                    Iniciar Sesión
                </button>
            </div>

            <!-- PANTALLA 3: MODO INVITADO -->
            <div id="pantalla-invitado" class="contenido-juego oculto">
                <button class="boton-volver" onclick="volverBienvenida()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-user-ninja icono-grande" style="color: var(--color-verde);"></i>
                    <h3>Modo Invitado</h3>
                    <p class="text-muted">¡Perfecto! Ya estás listo para comenzar</p>
                </div>

                <button class="boton-dino" onclick="entrarAlJuego('Invitado')">
                    <i class="fas fa-play icono-pequeno"></i>
                    ¡Comenzar Aventura!
                </button>
            </div>

            <!-- PANTALLA 4: REGISTRO -->
            <div id="pantalla-registro" class="contenido-juego oculto">
                <button class="boton-volver" onclick="volverBienvenida()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-user-plus icono-grande" style="color: var(--color-dorado);"></i>
                    <h3>Crear Cuenta</h3>
                    <p class="text-muted">Únete a la comunidad de exploradores</p>
                </div>

                <form id="formulario-registro">
                    <div class="mb-3">
                        <label class="form-label" for="usuario-registro">
                            <i class="fas fa-user"></i> Nombre de Usuario:
                        </label>
                        <input type="text" class="form-control campo-formulario" id="usuario-registro"
                            placeholder="Tu nombre de explorador" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password-registro">
                            <i class="fas fa-lock"></i> Contraseña:
                        </label>
                        <input type="password" class="form-control campo-formulario" id="password-registro"
                            placeholder="Crea una contraseña segura" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="confirmar-password">
                            <i class="fas fa-lock"></i> Confirmar Contraseña:
                        </label>
                        <input type="password" class="form-control campo-formulario" id="confirmar-password"
                            placeholder="Repite tu contraseña" required>
                    </div>

                    <button type="submit" class="boton-dino boton-dorado">
                        <i class="fas fa-user-check icono-pequeno"></i>
                        Crear Cuenta
                    </button>
                </form>
            </div>

            <!-- PANTALLA 5: LOGIN -->
            <div id="pantalla-login" class="contenido-juego oculto">
                <button class="boton-volver" onclick="volverBienvenida()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-sign-in-alt icono-grande" style="color: var(--color-marron);"></i>
                    <h3>Iniciar Sesión</h3>
                    <p class="text-muted">¡Bienvenido de vuelta, explorador!</p>
                </div>

                <form id="formulario-login">
                    <div class="mb-3">
                        <label class="form-label" for="usuario-login">
                            <i class="fas fa-user"></i> Nombre de Usuario:
                        </label>
                        <input type="text" class="form-control campo-formulario" id="usuario-login"
                            placeholder="Tu nombre de explorador" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password-login">
                            <i class="fas fa-lock"></i> Contraseña:
                        </label>
                        <input type="password" class="form-control campo-formulario" id="password-login"
                            placeholder="Tu contraseña" required>
                    </div>

                    <button type="submit" class="boton-dino boton-marron">
                        <i class="fas fa-sign-in-alt icono-pequeno"></i>
                        Entrar al Juego
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/inicio.js"></script>
</body>

</html>