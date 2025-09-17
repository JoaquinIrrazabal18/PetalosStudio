<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Draftosaurus</title>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <div class="settings-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🦕</div>
            <div class="game-title">DRAFTOSAURUS</div>
            <div class="subtitle">Crea tu zoológico de dinosaurios</div>
            <div class="studio-name">Pétalos Studio</div>
        </div>

        <!-- Content -->
        <div class="content">
            <button class="back-button" onclick="window.location.href='menu.php'">
                ← Volver al Menu
                <a href="/public/menu.php"></a>
            </button>

            <!-- Audio Settings -->
            <div class="settings-section">
                <h3 class="section-title">
                    <span class="section-icon">🔊</span>
                    Sonido y Música
                </h3>

                <div class="setting-item">
                    <span class="setting-label">Volumen General</span>
                    <div class="slider-container">
                        <input type="range" class="slider" min="0" max="100" value="75" id="masterVolume">
                        <span class="volume-value" id="masterVolumeValue">75%</span>
                    </div>
                </div>

                <div class="setting-item">
                    <span class="setting-label">Efectos de Sonido</span>
                    <div class="slider-container">
                        <input type="range" class="slider" min="0" max="100" value="80" id="sfxVolume">
                        <span class="volume-value" id="sfxVolumeValue">80%</span>
                    </div>
                </div>
            </div>

            <!-- User Profile Settings -->
            <div class="settings-section">
                <h3 class="section-title">
                    <span class="section-icon">👤</span>
                    Perfil de Usuario
                </h3>

                <div class="setting-item">
                    <span class="setting-label">Idioma</span>
                    <div class="dropdown">
                        <select id="language">
                            <option value="es" selected>Español</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                </div>

                <div class="setting-item">
                    <span class="setting-label">Cambiar Contraseña</span>
                    <button class="btn btn-secondary" onclick="changePassword()">
                        Cambiar
                    </button>
                </div>

                <div class="setting-item">
                    <span class="setting-label">Cambiar de Cuenta</span>
                    <button class="btn btn-secondary" onclick="switchAccount()">
                        Cambiar Cuenta
                    </button>
                </div>

                <div class="setting-item">
                    <span class="setting-label">Cerrar Sesión</span>
                    <button class="btn btn-danger" onclick="logout()">
                        Cerrar Sesión
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="saveSettings()">
                    Aceptar
                </button>
                <button class="btn btn-secondary" onclick="resetSettings()">
                    Restablecer
                </button>
            </div>
        </div>
    </div>

    <script>
        // Update volume displays
        function updateVolumeDisplay(sliderId, displayId) {
            const slider = document.getElementById(sliderId);
            const display = document.getElementById(displayId);
            slider.addEventListener('input', function() {
                display.textContent = this.value + '%';
            });
        }

        // Initialize volume displays
        updateVolumeDisplay('masterVolume', 'masterVolumeValue');
        updateVolumeDisplay('sfxVolume', 'sfxVolumeValue');

        // Settings object to store all settings
        const gameSettings = {
            audio: {
                masterVolume: 75,
                sfxVolume: 80
            },
            profile: {
                language: 'es'
            }
        };

        function goBack() {
            window.location.href = 'inicio.php';

        }

        function saveSettings() {
            // Collect all settings
            gameSettings.audio.masterVolume = document.getElementById('masterVolume').value;
            gameSettings.audio.sfxVolume = document.getElementById('sfxVolume').value;
            gameSettings.profile.language = document.getElementById('language').value;

            // Save animation
            const button = event.target;
            button.style.transform = 'scale(0.95)';
            button.textContent = 'Guardando...';

            setTimeout(() => {
                button.style.transform = 'scale(1)';
                button.textContent = 'Aceptar';
                alert('¡Configuración guardada exitosamente! 🦕');
            }, 1000);
        }

        function resetSettings() {
            if (confirm('¿Estás seguro de que quieres restablecer toda la configuración a los valores predeterminados?')) {
                // Reset all sliders
                document.getElementById('masterVolume').value = 75;
                document.getElementById('sfxVolume').value = 80;

                // Update displays
                document.getElementById('masterVolumeValue').textContent = '75%';
                document.getElementById('sfxVolumeValue').textContent = '80%';

                // Reset dropdown
                document.getElementById('language').value = 'es';

                alert('¡Configuración restablecida! 🦖');
            }
        }

        function changePassword() {
            // Redirige a la página de recuperación de contraseña
            window.location.href = "recuperar_contrasena.php";
        }

        function switchAccount() {
            if (confirm('¿Estás seguro de que quieres cambiar de cuenta? Se cerrará tu sesión actual.')) {
                alert('Redirigiendo a la pantalla de inicio de sesión...');
                window.location.href = "recuperar_cuenta.php";
            }
        }

        function logout() {
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                alert('Cerrando sesión... ¡Hasta pronto! 🦕');
                window.location.href  = "inicio.php";
            }
        }
    </script>
</body>

</html>