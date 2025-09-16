<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar de Cuenta - Draftosaurus</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>
    <div class="box">
        <h2>Selecciona una cuenta</h2>
        <div class="account-option" onclick="selectAccount('usuario1@example.com')">
            usuario1@example.com
        </div>
        <div class="account-option" onclick="selectAccount('usuario2@example.com')">
            usuario2@example.com
        </div>
        <a href="Configuraciones.php" class="add-account" onclick="addNewAccount()">➕ Usar otra cuenta</a>
        <a href="Configuraciones.php" class="back-link">← Volver a Configuración</a>
    </div>

    <script>
        function selectAccount(email) {
            alert("✅ Has iniciado sesión con: " + email);
            window.location.href = "Configuraciones.php";
        }

        function addNewAccount() {
            const newEmail = prompt("Introduce el correo de la nueva cuenta:");
            if (newEmail) {
                alert("🔑 Cuenta añadida: " + newEmail);
                window.location.href = "Configuraciones.php";
            }
        }
    </script>
</body>
</html>
