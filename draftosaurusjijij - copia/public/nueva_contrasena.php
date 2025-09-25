<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Draftosaurus</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>
    <div class="box">
        <h2>Crea tu nueva contraseña</h2>
        <form onsubmit="setNewPassword(event)">
            <input type="password" id="newPass" placeholder="Nueva contraseña" required>
            <input type="password" id="confirmPass" placeholder="Confirmar contraseña" required>
            <button type="submit">Guardar Contraseña</button>
        </form>
    </div>

    <script>
        function setNewPassword(event) {
            event.preventDefault();
            const newPass = document.getElementById("newPass").value;
            const confirmPass = document.getElementById("confirmPass").value;

            if (newPass === confirmPass) {
                alert("🔒 Tu contraseña ha sido cambiada exitosamente.");
                window.location.href = "Configuraciones.php";
            } else {
                alert("❌ Las contraseñas no coinciden.");
            }
        }
    </script>
</body>
</html>
