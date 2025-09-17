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
        <p>Hemos enviado un código de verificación al correo que ingresaste.</p>
        <form onsubmit="verifyCode(event)">
            <input type="text" id="code" maxlength="6" placeholder="Ingresa el código" required>
            <button type="submit">Verificar</button>
        </form>
        <a href="../public/recuperar_contrasena.php" class="back-link">← Usar otro correo</a>
    </div>

    <script>
        function verifyCode(event) {
            event.preventDefault();
            const code = document.getElementById("code").value;

            if (code === "123456") {
                alert("✅ Código verificado. Ahora puedes cambiar tu contraseña.");
                window.location.href = "nueva_contrasena.php";
            } else {
                alert("❌ Código incorrecto. Intenta nuevamente.");
            }
        }
    </script>
</body>
</html>
