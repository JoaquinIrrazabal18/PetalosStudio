<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Draftosaurus</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>
    <div class="box">
        <h2>Recuperar tu contraseña</h2>
        <p>Introduce tu correo electrónico para recibir un enlace de recuperación.</p>
        <form onsubmit="sendRecovery(event)">
            <input type="email" id="email" placeholder="Tu correo electrónico" required>
            <button type="submit">Enviar enlace</button>
        </form>
        <a href="Configuraciones.php" class="back-link">← Volver a Configuración</a>
    </div>

    <script>
        function sendRecovery(event) {
            event.preventDefault();
            const email = document.getElementById("email").value;
            // Corrige la redirección para enviar el email como parámetro GET
            window.location.href = "verificar_codigo.php?email=" + encodeURIComponent(email);
        }
    </script>
</body>
</html>
</html>
