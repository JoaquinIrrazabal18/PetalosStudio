
// Update volume displays
function updateVolumeDisplay(sliderId, displayId) {
    const slider = document.getElementById(sliderId);
    const display = document.getElementById(displayId);
    slider.addEventListener('input', function () {
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
    // Animation for going back
    document.querySelector('.settings-container').style.transform = 'translateX(-100%)';
    setTimeout(() => {
        window.location.href = 'inicio.php' ;
    }, 300);

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
        // Aquí iría la lógica para cerrar sesión
    }
}
