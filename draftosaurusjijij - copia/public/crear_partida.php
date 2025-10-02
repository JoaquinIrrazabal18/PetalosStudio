<?php
session_start();

// crear_partida.php - Ubicación: /tu_proyecto/crear_partida.php

if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php?error=sesion');
    exit();
}

$id_usuario_creador = $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['nombre_usuario'] ?? 'Usuario';
$project_folder = basename(dirname(__DIR__)); 
$api_base_url = '/' . $project_folder . '/api';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Partida - Draftosaurus</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f7f7f7;
    }
    .card {
      border-radius: 20px;
    }
    .loading {
      display: none;
    }
    .loading.show {
      display: inline-block;
    }
    .alert {
      border-radius: 15px;
    }
    .btn {
      border-radius: 15px;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

  <div class="container" style="max-width: 500px;">
    <h2 class="text-center mb-4">
      <i class="fas fa-dragon text-success"></i>
      Configurar Nueva Partida
    </h2>

    <form id="createGameForm" class="card p-4 shadow-lg bg-white">
      <div class="mb-3">
        <label for="gameName" class="form-label">
          <i class="fas fa-tag"></i> Nombre de la Partida
        </label>
        <input type="text" class="form-control" id="gameName" name="gameName"
               placeholder="Ej: Aventura Jurásica de los Martes" 
               required minlength="3" maxlength="50">
        <div class="form-text">Mínimo 3 caracteres, máximo 50</div>
      </div>
      <div class="mb-3">
        <label for="boardSide" class="form-label">
          <i class="fas fa-map"></i> Lado del Tablero
        </label>
        <select class="form-select" id="boardSide" name="boardSide" required>
          <option value="Verano" selected>🌞 Verano (Estándar)</option>
          <option value="Invierno">❄️ Invierno (Avanzado)</option>
        </select>
        <div class="form-text">El lado Invierno tiene reglas más complejas</div>
      </div>
      <div class="mb-3">
        <span class="form-label">Tipo de Partida</span>
        <div class="btn-group w-100" role="group">
          <input type="radio" class="btn-check" name="gameType" id="publicGame" value="publica" autocomplete="off" checked>
          <label class="btn btn-outline-success" for="publicGame">Pública</label>
          <input type="radio" class="btn-check" name="gameType" id="privateGame" value="privada" autocomplete="off">
          <label class="btn btn-outline-danger" for="privateGame">Privada</label>
        </div>
      </div>
      <div class="mb-3" id="password-container" style="display: none;">
        <label for="gamePassword" class="form-label">Contraseña de la Partida</label>
        <input type="password" class="form-control" id="gamePassword" name="gamePassword" placeholder="Contraseña para tus amigos">
      </div>
      <div class="mb-3">
        <label for="playerCount" class="form-label">
          <i class="fas fa-users"></i> Cantidad de Jugadores
        </label>
        <select class="form-select" id="playerCount" name="playerCount" required>
          <option value="2">2 jugadores</option>
          <option value="3">3 jugadores</option>
          <option value="4">4 jugadores</option>
          <option value="5">5 jugadores</option>
        </select>
        <div class="form-text">Elige entre 2 y 5 jugadores</div>
      </div>

      <div class="mb-4">
        <label class="form-label">
          <i class="fas fa-info-circle"></i> Información
        </label>
        <div class="alert alert-info mb-0">
          <small>
            <strong>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?>!</strong><br>
            Tu partida tendrá un código único para que otros jugadores puedan unirse.
          </small>
        </div>
      </div>
      
      <button type="submit" class="btn btn-success w-100 btn-lg">
        <i class="fas fa-play"></i>
        <span class="btn-text">¡Crear y Empezar a Jugar!</span>
        <span class="loading">
          <i class="fas fa-spinner fa-spin"></i> Creando...
        </span>
      </button>
      
      <a href="menu.php" class="btn btn-secondary w-100 mt-2">
        <i class="fas fa-arrow-left"></i> Volver al Menú
      </a>
      
      <!-- Contenedor para mensajes dinámicos -->
      <div id="messages-container"></div>
    </form>

    <!-- Modal para mostrar código de partida -->
    <div class="modal fade" id="partidaCreadaModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">¡Partida Creada Exitosamente!</h5>
          </div>
          <div class="modal-body text-center">
            <div class="mb-4">
              <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
              <h4 class="mt-3" id="nombrePartidaCreada"></h4>
            </div>
            
            <div class="alert alert-info">
              <h5><i class="fas fa-key"></i> Código de la Partida:</h5>
              <div class="input-group">
                <input type="text" class="form-control text-center fw-bold fs-4" id="codigoPartida" readonly>
                <button class="btn btn-outline-secondary" type="button" id="copiarCodigo">
                  <i class="fas fa-copy"></i> Copiar
                </button>
              </div>
              <small class="text-muted mt-2 d-block">
                Comparte este código con otros jugadores para que se unan
              </small>
            </div>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-success btn-lg" id="irAPartida">
              <i class="fas fa-play"></i> Ir a la Partida
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    const API_CONFIG = {
        baseUrl: '<?php echo $api_base_url; ?>',
        userId: <?php echo $id_usuario_creador; ?>,
        userName: '<?php echo addslashes($nombre_usuario); ?>'
    };

    const form = document.getElementById('createGameForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const loading = submitBtn.querySelector('.loading');
    const messagesContainer = document.getElementById('messages-container');
    const passwordContainer = document.getElementById('password-container');
    const passwordInput = document.getElementById('gamePassword');

    // Lógica para mostrar/ocultar el campo de contraseña
    document.querySelectorAll('input[name="gameType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'privada') {
                passwordContainer.style.display = 'block';
                passwordInput.required = true;
            } else {
                passwordContainer.style.display = 'none';
                passwordInput.required = false;
                passwordInput.value = '';
            }
        });
    });

    // Verificar sesión al cargar la página
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const response = await fetch(`${API_CONFIG.baseUrl}/usuarios/verificarSesion.php`, {
                method: 'GET',
                credentials: 'include'
            });
            const result = await response.json();
            if (!result.success) {
                showError('Tu sesión ha expirado. Redirigiendo al login...');
                setTimeout(() => window.location.href = 'inicio.php', 2000);
                return;
            }
        } catch (error) {
            showError('Error verificando sesión');
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        setLoadingState(true);
        clearMessages();

        try {
            const tipo = document.querySelector('input[name="gameType"]:checked').value;
            const cantidadJugadores = parseInt(document.getElementById('playerCount').value, 10);

            // Solo el usuario actual al crear la partida
            const jugadores = [API_CONFIG.userId];

            const formData = {
                nombre: document.getElementById('gameName').value.trim(),
                lado: document.getElementById('boardSide').value,
                tipo: tipo,
                jugadores: jugadores,
                cantidad_jugadores: cantidadJugadores // puedes enviar este campo si el backend lo usa
            };

            if (tipo === 'privada') {
                formData.contrasena = passwordInput.value;
                if (!formData.contrasena) {
                    throw new Error('Por favor, introduce una contraseña para la partida privada.');
                }
            }

            // Cambia la URL del fetch para usar el router correcto
            const response = await fetch(`${API_CONFIG.baseUrl}/api.php?action=crearPartida`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify(formData)
            });

            let result;
            try {
                result = await response.json();
            } catch (err) {
                throw new Error('Respuesta del servidor no es JSON válido. Verifica el backend.');
            }

            if (result.success && result.data && result.data.id_partida) {
                // Redirige a la pantalla de espera (no inicia la partida)
                window.location.href = `version-html-partida.PHP?id=${result.data.id_partida}`;
            } else {
                throw new Error(result.message || 'Error desconocido al crear la partida');
            }

        } catch (error) {
            showError(error.message || 'Error de conexión al servidor');
            setLoadingState(false);
        }
    });

    function setLoadingState(isLoading) {
        submitBtn.disabled = isLoading;
        if (isLoading) {
            btnText.style.display = 'none';
            loading.classList.add('show');
        } else {
            btnText.style.display = 'inline';
            loading.classList.remove('show');
        }
    }

    function showError(message) {
        showMessage(message, 'danger', 'exclamation-triangle');
    }

    function showMessage(message, type, icon) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
        alertDiv.innerHTML = `
            <i class="fas fa-${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        messagesContainer.appendChild(alertDiv);
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, type === 'success' ? 3000 : 5000);
    }

    function clearMessages() {
        messagesContainer.innerHTML = '';
    }

    document.getElementById('gameName').addEventListener('input', function() {
        const value = this.value.trim();
        const isValid = value.length >= 3 && value.length <= 50;
        this.classList.toggle('is-valid', isValid && value.length > 0);
        this.classList.toggle('is-invalid', !isValid && value.length > 0);
    });
  </script>
</body>
</html>