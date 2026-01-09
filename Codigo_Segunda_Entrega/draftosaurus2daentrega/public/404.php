<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - Draftosaurus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

            <!-- CONTENIDO DE ERROR -->
            <div class="contenido-juego">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle icono-grande" style="color: var(--color-dorado); font-size: 5rem;"></i>
                    <h3>¡Oops! Página no encontrada</h3>
                    <p class="text-muted">El dinosaurio que buscas se ha perdido en el tiempo.</p>
                </div>

                <div class="text-center">
                    <h4 style="color: var(--color-texto);">Error 404</h4>
                    <p>La página que intentas acceder no existe o ha sido movida.</p>
                    
                    <div class="mt-4">
                        <a href="inicio.php" class="boton-dino boton-dorado">
                            <i class="fas fa-home icono-pequeno"></i>
                            Volver al Inicio
                        </a>
                        
                        <a href="menu.php" class="boton-dino" style="margin-top: 10px;">
                            <i class="fas fa-gamepad icono-pequeno"></i>
                            Ir al Menú
                        </a>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="mt-4 text-center">
                    <p class="text-muted small">
                        Si crees que esto es un error, contacta al soporte técnico.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Redirigir automáticamente después de 5 segundos
        setTimeout(() => {
            window.location.href = 'inicio.php';
        }, 5000);

        // Mostrar contador regresivo
        let countdown = 5;
        const interval = setInterval(() => {
            countdown--;
            if (countdown > 0) {
                document.title = `Redirigiendo en ${countdown}s - Draftosaurus`;
            } else {
                clearInterval(interval);
            }
        }, 1000);
    </script>
</body>
</html>