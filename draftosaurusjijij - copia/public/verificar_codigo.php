<?php
$email = isset($_GET['email']) ? $_GET['email'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación - Draftosaurus</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>
    <div class="box">
        <h2>Verifica tu identidad</h2>
        <p>Hemos enviado un código de verificación al correo: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        <form onsubmit="verifyCode(event)">
            <input type="text" id="code" maxlength="6" placeholder="Ingresa el código" required>
            <button type="submit">Verificar</button>
        </form>
        <div id="verificacion-msg"></div>
        <a href="../public/recuperar_contrasena.php" class="back-link">← Usar otro correo</a>
    </div>

    <script>
        function verifyCode(event) {
            event.preventDefault();
            const code = document.getElementById("code").value;
            const msg = document.getElementById("verificacion-msg");
            // Simulación: código correcto es 123456
            if (code === "123456") {
                msg.textContent = "✅ Código verificado. Redirigiendo...";
                msg.style.color = "#16a34a";
                setTimeout(function() {
                    window.location.href = "nueva_contrasena.php?email=<?php echo urlencode($email); ?>";
                }, 1200);
            } else {
                msg.textContent = "❌ Código incorrecto. Intenta nuevamente.";
                msg.style.color = "#e74c3c";
            }
        }
    </script>
</body>
</html>
