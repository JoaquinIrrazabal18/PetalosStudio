<?php
// unirse_partida.php - Ubicación: /tu_proyecto/unirse_partida.php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['nombre_usuario'] ?? 'Usuario';
$project_folder = basename(dirname(__DIR__)); 
$api_base_url = '/' . $project_folder . '/api';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a Partida - Draftosaurus</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One:wght@400&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/inicio.css">
</head>
<body>
    <div class="contenedor-principal">
        
        <!-- TARJETA PRINCIPAL: UNIRSE A PARTIDA -->
        <div id="pantalla-unirse" class="tarjeta-juego aparecer">
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAlMenu()">
                    <i class="fas fa-arrow-left"></i> Volver al Menu
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-users icono-grande" style="color: var(--color-verde);"></i>
                    <h3>¡Hola <?php echo htmlspecialchars($nombre_usuario); ?>!</h3>
                    <p class="text-muted">¿Cómo quieres unirte a una partida?</p>
                </div>

                <div class="menu-opciones">
                    <div class="opcion-menu" onclick="mostrarCodigo()">
                        <i class="fas fa-key icono-grande" style="color: var(--color-dorado);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Código de Partida</div>
                        <div style="font-size: 0.9rem; color: #666;">Ingresa el código que te dieron</div>
                    </div>

                    <div class="opcion-menu" onclick="mostrarPartidasDisponibles()">
                        <i class="fas fa-list icono-grande" style="color: var(--color-verde);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Partidas Disponibles</div>
                        <div style="font-size: 0.9rem; color: #666;">Buscar partidas públicas</div>
                    </div>

                    <div class="opcion-menu" onclick="partidaRapida()">
                        <i class="fas fa-bolt icono-grande" style="color: var(--color-marron);"></i>
                        <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 10px;">Partida Rápida</div>
                        <div style="font-size: 0.9rem; color: #666;">Búsqueda automática</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TARJETA SEPARADA: INGRESAR CÓDIGO -->
        <div id="pantalla-codigo" class="tarjeta-juego oculto">
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAUnirse()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-key icono-grande" style="color: var(--color-dorado);"></i>
                    <h3>Código de Partida</h3>
                    <p class="text-muted">Ingresa el código que te proporcionaron</p>
                </div>

                <form id="formulario-codigo">
                    <div class="mb-3">
                        <label class="form-label" for="codigo-partida">
                            <i class="fas fa-hashtag"></i> Código de la Partida:
                        </label>
                        <input type="text" class="form-control campo-formulario" id="codigo-partida"
                            placeholder="Ej: DINO-202412-001" maxlength="15" required style="text-transform: uppercase;">
                        <div class="form-text">El código debe tener el formato: DINO-XXXXXX-XXX</div>
                    </div>

                    <div class="mb-3" id="password-codigo-container" style="display: none;">
                        <label class="form-label" for="password-partida">
                            <i class="fas fa-lock"></i> Contraseña de la Partida:
                        </label>
                        <input type="password" class="form-control campo-formulario" id="password-partida"
                            placeholder="Contraseña de la partida privada">
                    </div>

                    <button type="submit" class="boton-dino">
                        <i class="fas fa-sign-in-alt icono-pequeno"></i>
                        <span class="btn-text">Unirse a Partida</span>
                        <span class="loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Verificando...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- TARJETA SEPARADA: PARTIDAS DISPONIBLES -->
        <div id="pantalla-disponibles" class="tarjeta-juego oculto">
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAUnirse()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-list icono-grande" style="color: var(--color-verde);"></i>
                    <h3>Partidas Disponibles</h3>
                    <p class="text-muted">Selecciona una partida para unirte</p>
                </div>

                <div class="mb-3">
                    <button class="boton-dino" onclick="buscarPartidas()">
                        <i class="fas fa-search icono-pequeno"></i>
                        Buscar Partidas
                    </button>
                </div>

                <div id="lista-partidas" class="lista-partidas">
                    <div class="text-center text-muted">
                        <i class="fas fa-search fa-2x mb-3"></i>
                        <p>Haz clic en "Buscar Partidas" para ver las partidas disponibles</p>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <p class="text-muted">¿No encuentras una partida? <a href="crear_partida.php" style="color: var(--color-verde);">Crea una nueva</a></p>
                </div>
            </div>
        </div>

        <!-- TARJETA SEPARADA: PARTIDA RÁPIDA -->
        <div id="pantalla-rapida" class="tarjeta-juego oculto">
            <div class="encabezado-juego">
                <h1 class="titulo-juego">
                    <i class="fas fa-dragon icono-pequeno"></i>
                    DRAFTOSAURUS
                </h1>
                <p>Crea tu zoológico de dinosaurios</p>
                <h1>Pétalos Studio</h1>
            </div>

            <div class="contenido-juego">
                <button class="boton-volver" onclick="volverAUnirse()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>

                <div class="text-center mb-4">
                    <i class="fas fa-bolt icono-grande" style="color: var(--color-marron);"></i>
                    <h3>Partida Rápida</h3>
                    <p class="text-muted">Te conectaremos con otros jugadores automáticamente</p>
                </div>

                <div class="text-center">
                    <div class="busqueda-estado mb-4" id="estado-busqueda" style="display: none;">
                        <i class="fas fa-search fa-spin" style="font-size: 2rem; color: var(--color-verde); margin-bottom: 20px;"></i>
                        <h4 id="mensaje-busqueda">Buscando jugadores...</h4>
                        <p class="text-muted">Tiempo estimado: 30 segundos</p>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%" id="progress-busqueda"></div>
                        </div>
                    </div>

                    <button class="boton-dino boton-marron" id="btn-iniciar-busqueda" onclick="iniciarBusquedaRapida()">
                        <i class="fas fa-play icono-pequeno"></i>
                        Iniciar Búsqueda
                    </button>

                    <button class="boton-volver mt-2" id="btn-cancelar-busqueda" onclick="cancelarBusqueda()" style="margin-top: 10px; display: none;">
                        <i class="fas fa-times"></i> Cancelar Búsqueda
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuración de la API
        const API_CONFIG = {
            baseUrl: '<?php echo $api_base_url; ?>',
            userId: <?php echo $id_usuario; ?>,
            userName: '<?php echo addslashes($nombre_usuario); ?>'
        };

        // Variables globales
        let busquedaActiva = false;
        let timerBusqueda = null;

        // FUNCIÓN PARA MOSTRAR MENSAJES VISUALES
        function mostrarMensaje(mensaje, tipo = 'info', duracion = 3000) {
            const mensajeDiv = document.createElement('div');
            mensajeDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
            mensajeDiv.style.position = 'fixed';
            mensajeDiv.style.top = '20px';
            mensajeDiv.style.right = '20px';
            mensajeDiv.style.zIndex = '9999';
            mensajeDiv.style.maxWidth = '400px';
            
            mensajeDiv.innerHTML = `
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            document.body.appendChild(mensajeDiv);
            
            setTimeout(() => {
                if (mensajeDiv.parentNode) {
                    mensajeDiv.remove();
                }
            }, duracion);
            
            return mensajeDiv;
        }

        // FUNCIONES DE NAVEGACIÓN ENTRE PANTALLAS
        function mostrarPantalla(idPantalla) {
            console.log(`Cambiando a pantalla: ${idPantalla}`);
            
            const pantallas = [
                'pantalla-unirse',
                'pantalla-codigo',
                'pantalla-disponibles',
                'pantalla-rapida'
            ];
            
            pantallas.forEach(pantalla => {
                const elemento = document.getElementById(pantalla);
                if (elemento) {
                    elemento.classList.add('oculto');
                    elemento.classList.remove('aparecer');
                }
            });
            
            const elemento = document.getElementById(idPantalla);
            if (elemento) {
                elemento.classList.remove('oculto');
                elemento.classList.add('aparecer');
            }
        }

        function volverAlMenu() {
            window.location.href = "menu.php";
        }

        function volverAUnirse() {
            mostrarPantalla('pantalla-unirse');
        }

        function mostrarCodigo() {
            mostrarPantalla('pantalla-codigo');
            setTimeout(() => {
                const campoCodigo = document.getElementById('codigo-partida');
                if (campoCodigo) {
                    campoCodigo.focus();
                }
            }, 100);
        }

        function mostrarPartidasDisponibles() {
            mostrarPantalla('pantalla-disponibles');
        }

        function partidaRapida() {
            mostrarPantalla('pantalla-rapida');
        }

        // FUNCIÓN PARA VALIDAR CÓDIGO DE PARTIDA
        function validarCodigo(codigo) {
            const regex = /^DINO-\d{6}-\d{3}$/i;
            return regex.test(codigo);
        }

        // FUNCIÓN PARA DECODIFICAR ID DE PARTIDA DESDE CÓDIGO
        function decodificarCodigoPartida(codigo) {
            try {
                const partes = codigo.split('-');
                if (partes.length === 3 && partes[0] === 'DINO') {
                    return parseInt(partes[2]);
                }
            } catch (error) {
                console.error('Error decodificando código:', error);
            }
            return null;
        }

        // FUNCIÓN PARA UNIRSE POR CÓDIGO
        async function unirseConCodigo(codigo, password = null) {
            if (!validarCodigo(codigo)) {
                mostrarMensaje('Código inválido. Debe tener el formato: DINO-XXXXXX-XXX', 'danger');
                return;
            }

            const submitBtn = document.querySelector('#formulario-codigo button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const loading = submitBtn.querySelector('.loading');
            
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            loading.style.display = 'inline-block';

            try {
                const idPartida = decodificarCodigoPartida(codigo);
                if (!idPartida) {
                    throw new Error('Código de partida inválido');
                }

                // Verificar si la partida existe y obtener su estado
                const response = await fetch(`${API_CONFIG.baseUrl}/partidas/estado?id_partida=${idPartida}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error('Partida no encontrada o no disponible');
                }

                const partida = result.data;
                
                // Verificar si la partida requiere contraseña
                if (partida.tipo === 'privada' && !password) {
                    document.getElementById('password-codigo-container').style.display = 'block';
                    document.getElementById('password-partida').required = true;
                    throw new Error('Esta partida requiere contraseña');
                }

                // Verificar contraseña si es necesario
                if (partida.tipo === 'privada' && password && partida.contrasena !== password) {
                    throw new Error('Contraseña incorrecta');
                }

                // Intentar unirse a la partida
                const joinResponse = await fetch(`${API_CONFIG.baseUrl}/partidas/unirse`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        id_partida: idPartida,
                        codigo: codigo,
                        contrasena: password
                    })
                });

                const joinResult = await joinResponse.json();

                if (joinResult.success) {
                    mostrarMensaje('¡Te has unido a la partida exitosamente!', 'success', 2000);
                    setTimeout(() => {
                        window.location.href = `partida.php?id=${idPartida}`;
                    }, 2000);
                } else {
                    throw new Error(joinResult.message || 'No se pudo unir a la partida');
                }

            } catch (error) {
                console.error('Error uniéndose a partida:', error);
                mostrarMensaje(error.message, 'danger');
            } finally {
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                loading.style.display = 'none';
            }
        }

        // FUNCIÓN PARA BUSCAR PARTIDAS DISPONIBLES
        async function buscarPartidas() {
            const listaPartidas = document.getElementById('lista-partidas');
            if (!listaPartidas) return;

            listaPartidas.innerHTML = `
                <div class="text-center" style="padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--color-verde);"></i>
                    <h5 style="margin-top: 15px;">Buscando partidas disponibles...</h5>
                </div>
            `;

            try {
                const response = await fetch(`${API_CONFIG.baseUrl}/partidas/disponibles`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    let htmlPartidas = '';
                    result.data.forEach(partida => {
                        const codigo = generarCodigoPartida(partida.id_partida);
                        const estado = partida.estado_partida === 'creada' ? 'Esperando' : 'En curso';
                        const jugadores = `${partida.participantes_actuales || 1}/${partida.num_participantes}`;
                        
                        htmlPartidas += `
                            <div class="partida-item">
                                <div class="partida-info">
                                    <h5>${partida.nombre || 'Partida sin nombre'}</h5>
                                    <p><i class="fas fa-users"></i> ${jugadores} jugadores | <i class="fas fa-clock"></i> ${estado}</p>
                                    <p><i class="fas fa-user"></i> Tablero: ${partida.lado_tablero}</p>
                                    <small class="text-muted">Código: ${codigo}</small>
                                </div>
                                <button class="boton-dino" onclick="unirsePartida(${partida.id_partida}, '${codigo}')" style="margin: 0; padding: 10px 15px;">
                                    Unirse
                                </button>
                            </div>
                        `;
                    });

                    listaPartidas.innerHTML = htmlPartidas;
                    mostrarMensaje(`¡Se encontraron ${result.data.length} partidas disponibles!`, 'success');
                } else {
                    listaPartidas.innerHTML = `
                        <div class="text-center text-muted" style="padding: 40px;">
                            <i class="fas fa-search fa-2x mb-3"></i>
                            <h5>No se encontraron partidas disponibles</h5>
                            <p>Puedes crear una nueva partida o intentar más tarde</p>
                            <a href="crear_partida.php" class="boton-dino" style="display: inline-block; width: auto; margin: 0; padding: 10px 20px;">
                                <i class="fas fa-plus"></i> Crear Partida
                            </a>
                        </div>
                    `;
                    mostrarMensaje('No se encontraron partidas disponibles', 'info');
                }

            } catch (error) {
                console.error('Error buscando partidas:', error);
                listaPartidas.innerHTML = `
                    <div class="text-center text-muted" style="padding: 40px;">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3 text-warning"></i>
                        <h5>Error al buscar partidas</h5>
                        <p>No se pudo conectar con el servidor</p>
                        <button class="boton-dino" onclick="buscarPartidas()" style="display: inline-block; width: auto; margin: 0; padding: 10px 20px;">
                            <i class="fas fa-redo"></i> Reintentar
                        </button>
                    </div>
                `;
                mostrarMensaje('Error al buscar partidas: ' + error.message, 'danger');
            }
        }

        // FUNCIÓN PARA GENERAR CÓDIGO DE PARTIDA
        function generarCodigoPartida(idPartida) {
            const año = new Date().getFullYear();
            const mes = String(new Date().getMonth() + 1).padStart(2, '0');
            const id = String(idPartida).padStart(3, '0');
            return `DINO-${año}${mes}-${id}`;
        }

        // FUNCIÓN PARA UNIRSE A PARTIDA ESPECÍFICA
        async function unirsePartida(idPartida, codigo) {
            try {
                mostrarMensaje('Uniéndose a la partida...', 'info');
                
                const response = await fetch(`${API_CONFIG.baseUrl}/partidas/unirse`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        id_partida: idPartida,
                        codigo: codigo
                    })
                });

                const result = await response.json();

                if (result.success) {
                    mostrarMensaje('¡Te has unido a la partida exitosamente!', 'success', 2000);
                    setTimeout(() => {
                        window.location.href = `partida.php?id=${idPartida}&codigo=${codigo}`;
                    }, 2000);
                } else {
                    throw new Error(result.message || 'No se pudo unir a la partida');
                }

            } catch (error) {
                console.error('Error uniéndose a partida:', error);
                mostrarMensaje('Error al unirse: ' + error.message, 'danger');
            }
        }

        // FUNCIÓN PARA INICIAR BÚSQUEDA RÁPIDA
        function iniciarBusquedaRapida() {
            if (busquedaActiva) return;
            
            busquedaActiva = true;
            
            const estadoBusqueda = document.getElementById('estado-busqueda');
            const btnIniciar = document.getElementById('btn-iniciar-busqueda');
            const btnCancelar = document.getElementById('btn-cancelar-busqueda');
            const progressBar = document.getElementById('progress-busqueda');
            const mensajeBusqueda = document.getElementById('mensaje-busqueda');
            
            estadoBusqueda.style.display = 'block';
            btnIniciar.style.display = 'none';
            btnCancelar.style.display = 'block';
            
            let progreso = 0;
            
            timerBusqueda = setInterval(() => {
                progreso += Math.random() * 10 + 5;
                
                if (progreso >= 100) {
                    progreso = 100;
                    clearInterval(timerBusqueda);
                    busquedaActiva = false;
                    
                    // Simular encontrar partida
                    mensajeBusqueda.textContent = '¡Partida encontrada!';
                    setTimeout(async () => {
                        try {
                            // Intentar encontrar una partida real disponible
                            const response = await fetch(`${API_CONFIG.baseUrl}/partidas/disponibles`, {
                                method: 'GET',
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            
                            if (result.success && result.data && result.data.length > 0) {
                                const partida = result.data[0]; // Tomar la primera disponible
                                const codigo = generarCodigoPartida(partida.id_partida);
                                await unirsePartida(partida.id_partida, codigo);
                            } else {
                                mostrarMensaje('No se encontraron partidas disponibles. Creando una nueva partida...', 'info');
                                setTimeout(() => {
                                    window.location.href = 'crear_partida.php';
                                }, 2000);
                            }
                        } catch (error) {
                            mostrarMensaje('Error en búsqueda rápida: ' + error.message, 'danger');
                            resetearBusquedaRapida();
                        }
                    }, 1000);
                    return;
                }
                
                if (progressBar) {
                    progressBar.style.width = progreso + '%';
                }
                
                if (mensajeBusqueda) {
                    if (progreso < 30) {
                        mensajeBusqueda.textContent = 'Buscando jugadores...';
                    } else if (progreso < 60) {
                        mensajeBusqueda.textContent = 'Evaluando partidas compatibles...';
                    } else if (progreso < 90) {
                        mensajeBusqueda.textContent = 'Conectando con otros jugadores...';
                    } else {
                        mensajeBusqueda.textContent = 'Finalizando conexión...';
                    }
                }
                
            }, 300);
        }

        // FUNCIÓN PARA CANCELAR BÚSQUEDA RÁPIDA
        function cancelarBusqueda() {
            if (timerBusqueda) {
                clearInterval(timerBusqueda);
                timerBusqueda = null;
            }
            
            busquedaActiva = false;
            mostrarMensaje('Búsqueda cancelada', 'warning');
            resetearBusquedaRapida();
        }

        function resetearBusquedaRapida() {
            const estadoBusqueda = document.getElementById('estado-busqueda');
            const btnIniciar = document.getElementById('btn-iniciar-busqueda');
            const btnCancelar = document.getElementById('btn-cancelar-busqueda');
            const progressBar = document.getElementById('progress-busqueda');
            const mensajeBusqueda = document.getElementById('mensaje-busqueda');
            
            estadoBusqueda.style.display = 'none';
            btnIniciar.style.display = 'block';
            btnCancelar.style.display = 'none';
            
            if (progressBar) {
                progressBar.style.width = '0%';
            }
            
            if (mensajeBusqueda) {
                mensajeBusqueda.textContent = 'Buscando jugadores...';
            }
        }

        // FUNCIÓN PARA INICIALIZAR FORMULARIOS
        function inicializarFormularios() {
            const formCodigo = document.getElementById('formulario-codigo');
            
            if (formCodigo) {
                formCodigo.addEventListener('submit', function(evento) {
                    evento.preventDefault();
                    
                    const campoCodigo = document.getElementById('codigo-partida');
                    const codigo = campoCodigo.value.trim().toUpperCase();
                    const password = document.getElementById('password-partida').value;
                    
                    if (!codigo) {
                        mostrarMensaje('Por favor, ingresa un código de partida', 'warning');
                        campoCodigo.focus();
                        return;
                    }
                    
                    unirseConCodigo(codigo, password || null);
                });
            }
            
            // Formatear código automáticamente
            const campoCodigo = document.getElementById('codigo-partida');
            if (campoCodigo) {
                campoCodigo.addEventListener('input', function(evento) {
                    this.value = this.value.toUpperCase();
                    this.value = this.value.replace(/[^A-Z0-9-]/g, '');
                });
                
                campoCodigo.addEventListener('keypress', function(evento) {
                    if (evento.key === 'Enter') {
                        const form = document.getElementById('formulario-codigo');
                        if (form) {
                            form.dispatchEvent(new Event('submit'));
                        }
                    }
                });
            }
        }

        // INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Inicializando unirse a partida...');
            mostrarPantalla('pantalla-unirse');
            inicializarFormularios();
            
            console.log(`Usuario: ${API_CONFIG.userName} (ID: ${API_CONFIG.userId})`);
        });

        // Hacer funciones globales
        window.volverAlMenu = volverAlMenu;
        window.volverAUnirse = volverAUnirse;
        window.mostrarCodigo = mostrarCodigo;
        window.mostrarPartidasDisponibles = mostrarPartidasDisponibles;
        window.partidaRapida = partidaRapida;
        window.buscarPartidas = buscarPartidas;
        window.unirsePartida = unirsePartida;
        window.iniciarBusquedaRapida = iniciarBusquedaRapida;
        window.cancelarBusqueda = cancelarBusqueda;
    </script>
</body>
</html>