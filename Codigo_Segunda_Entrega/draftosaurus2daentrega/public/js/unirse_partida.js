// unirse_partida.js - Archivo JavaScript para la funcionalidad de unirse a partida
// VARIABLES GLOBALES
let usuarioActual = null;
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
    
    // Ocultar todas las pantallas
    pantallas.forEach(pantalla => {
        const elemento = document.getElementById(pantalla);
        if (elemento) {
            elemento.classList.add('oculto');
            elemento.classList.remove('aparecer');
        }
    });
    
    // Mostrar la pantalla solicitada
    const elemento = document.getElementById(idPantalla);
    if (elemento) {
        elemento.classList.remove('oculto');
        elemento.classList.add('aparecer');
    } else {
        console.error(`No se encontró la pantalla: ${idPantalla}`);
    }
}

// FUNCIONES DE NAVEGACIÓN PRINCIPAL
function volverAlMenu() {
    console.log('Regresando al menú principal...');
    window.location.href = "menu.php";
}

function volverAUnirse() {
    mostrarPantalla('pantalla-unirse');
}

function mostrarCodigo() {
    mostrarPantalla('pantalla-codigo');
    // Enfocar el campo de código
    setTimeout(() => {
        const campoCodigo = document.getElementById('codigo-partida');
        if (campoCodigo) {
            campoCodigo.focus();
        }
    }, 100);
}

function mostrarPartidasDisponibles() {
    mostrarPantalla('pantalla-disponibles');
    // Simular búsqueda automática de partidas
    setTimeout(() => {
        buscarPartidas();
    }, 500);
}

function partidaRapida() {
    mostrarPantalla('pantalla-rapida');
}

// FUNCIÓN PARA VALIDAR CÓDIGO DE PARTIDA
function validarCodigo(codigo) {
    // Formato esperado: DINO-XXXX-XXX (donde X puede ser número o letra)
    const regex = /^DINO-[A-Z0-9]{4}-[A-Z0-9]{3}$/i;
    return regex.test(codigo);
}

// FUNCIÓN PARA UNIRSE POR CÓDIGO
function unirseConCodigo(codigo) {
    if (!validarCodigo(codigo)) {
        mostrarMensaje('Código inválido. Debe tener el formato: DINO-XXXX-XXX', 'danger');
        return;
    }

    mostrarMensaje('Verificando código de partida...', 'info', 2000);
    
    // Simular verificación del código
    setTimeout(() => {
        // Códigos válidos para demo
        const codigosValidos = ['DINO-2024-001', 'DINO-2024-002', 'DINO-2024-003', 'DINO-TEST-ABC'];
        
        if (codigosValidos.includes(codigo.toUpperCase())) {
            mostrarMensaje('¡Código válido! Conectando a la partida...', 'success', 2000);
            setTimeout(() => {
                redirigirAPartida(codigo);
            }, 2000);
        } else {
            mostrarMensaje('Código no encontrado. Verifica que sea correcto o que la partida esté activa.', 'warning');
        }
    }, 1500);
}

// FUNCIÓN PARA BUSCAR PARTIDAS DISPONIBLES
function buscarPartidas() {
    const listaPartidas = document.getElementById('lista-partidas');
    if (!listaPartidas) return;

    // Mostrar indicador de carga
    listaPartidas.innerHTML = `
        <div class="text-center" style="padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--color-verde);"></i>
            <h5 style="margin-top: 15px;">Buscando partidas disponibles...</h5>
        </div>
    `;

    // Simular búsqueda de partidas
    setTimeout(() => {
        const partidasDisponibles = [
            {
                nombre: 'Expedición Jurásica',
                jugadores: '2/4',
                host: 'DinoExplorer',
                codigo: 'DINO-2024-001',
                estado: 'Esperando'
            },
            {
                nombre: 'Safari Prehistórico',
                jugadores: '1/3',
                host: 'TRexFan',
                codigo: 'DINO-2024-002',
                estado: 'Esperando'
            },
            {
                nombre: 'Reino de los Gigantes',
                jugadores: '3/4',
                host: 'VelociraptorKing',
                codigo: 'DINO-2024-003',
                estado: 'Esperando'
            }
        ];

        // Generar HTML de partidas
        let htmlPartidas = '';
        partidasDisponibles.forEach(partida => {
            htmlPartidas += `
                <div class="partida-item">
                    <div class="partida-info">
                        <h5>${partida.nombre}</h5>
                        <p><i class="fas fa-users"></i> ${partida.jugadores} jugadores | <i class="fas fa-clock"></i> ${partida.estado}</p>
                        <p><i class="fas fa-user"></i> Host: ${partida.host}</p>
                    </div>
                    <button class="boton-dino" onclick="unirsePartida('${partida.codigo}')" style="margin: 0; padding: 10px 15px;">
                        Unirse
                    </button>
                </div>
            `;
        });

        listaPartidas.innerHTML = htmlPartidas;
        mostrarMensaje('¡Se encontraron ' + partidasDisponibles.length + ' partidas disponibles!', 'success');
    }, 2000);
}

// FUNCIÓN PARA UNIRSE A PARTIDA ESPECÍFICA
function unirsePartida(codigo) {
    mostrarMensaje('Conectando a la partida...', 'info');
    
    setTimeout(() => {
        mostrarMensaje('¡Te has unido a la partida exitosamente!', 'success', 2000);
        setTimeout(() => {
            redirigirAPartida(codigo);
        }, 2000);
    }, 1500);
}

// FUNCIÓN PARA INICIAR BÚSQUEDA RÁPIDA
function iniciarBusquedaRapida() {
    if (busquedaActiva) return;
    
    busquedaActiva = true;
    mostrarMensaje('Iniciando búsqueda rápida...', 'info');
    
    // Simular progreso de búsqueda
    let progreso = 0;
    const progressBar = document.querySelector('.progress-bar');
    const estadoBusqueda = document.querySelector('.busqueda-estado h4');
    
    timerBusqueda = setInterval(() => {
        progreso += Math.random() * 15;
        
        if (progreso >= 100) {
            progreso = 100;
            clearInterval(timerBusqueda);
            busquedaActiva = false;
            
            // Simular encontrar partida
            mostrarMensaje('¡Partida encontrada! Conectando...', 'success');
            setTimeout(() => {
                redirigirAPartida('DINO-RAPID-' + Math.floor(Math.random() * 1000));
            }, 2000);
        }
        
        if (progressBar) {
            progressBar.style.width = progreso + '%';
            progressBar.setAttribute('aria-valuenow', progreso);
        }
        
        // Actualizar mensaje según progreso
        if (estadoBusqueda) {
            if (progreso < 30) {
                estadoBusqueda.textContent = 'Buscando jugadores...';
            } else if (progreso < 60) {
                estadoBusqueda.textContent = 'Evaluando partidas compatibles...';
            } else if (progreso < 90) {
                estadoBusqueda.textContent = 'Conectando con otros jugadores...';
            } else {
                estadoBusqueda.textContent = '¡Partida encontrada!';
            }
        }
        
    }, 500);
}

// FUNCIÓN PARA CANCELAR BÚSQUEDA RÁPIDA
function cancelarBusqueda() {
    if (timerBusqueda) {
        clearInterval(timerBusqueda);
        timerBusqueda = null;
    }
    
    busquedaActiva = false;
    mostrarMensaje('Búsqueda cancelada', 'warning');
    
    // Resetear barra de progreso
    const progressBar = document.querySelector('.progress-bar');
    const estadoBusqueda = document.querySelector('.busqueda-estado h4');
    
    if (progressBar) {
        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', 0);
    }
    
    if (estadoBusqueda) {
        estadoBusqueda.textContent = 'Búsqueda cancelada';
    }
    
    setTimeout(() => {
        volverAUnirse();
    }, 1000);
}

// FUNCIÓN PARA REDIRIGIR A LA PARTIDA
function redirigirAPartida(codigo) {
    console.log(`Redirigiendo a partida con código: ${codigo}`);
    
    // Obtener nombre del usuario
    const nombreUsuario = usuarioActual || 'Jugador';
    
    // Construir URL con parámetros
    const url = `partida.php?codigo=${encodeURIComponent(codigo)}&usuario=${encodeURIComponent(nombreUsuario)}`;
    
    // Redirigir a la partida
    window.location.href = url;
}

// FUNCIÓN PARA CARGAR DATOS DEL USUARIO
function cargarDatosUsuario() {
    try {
        // Intentar cargar desde URL params
        const urlParams = new URLSearchParams(window.location.search);
        const nombreParam = urlParams.get('usuario');
        
        if (nombreParam) {
            usuarioActual = nombreParam;
            return;
        }

        // Intentar cargar desde localStorage
        if (typeof(Storage) !== "undefined") {
            const usuarioGuardado = localStorage.getItem('usuario_draftosaurus');
            if (usuarioGuardado) {
                const datosUsuario = JSON.parse(usuarioGuardado);
                if (datosUsuario.usuario) {
                    usuarioActual = datosUsuario.usuario;
                    return;
                }
            }
        }

        // Valor por defecto
        usuarioActual = 'Jugador';
        
    } catch (error) {
        console.error('Error al cargar datos del usuario:', error);
        usuarioActual = 'Jugador';
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
            
            if (!codigo) {
                mostrarMensaje('Por favor, ingresa un código de partida', 'warning');
                campoCodigo.focus();
                return;
            }
            
            unirseConCodigo(codigo);
        });
    }
    
    // Formatear código automáticamente
    const campoCodigo = document.getElementById('codigo-partida');
    if (campoCodigo) {
        campoCodigo.addEventListener('input', function(evento) {
            // Convertir a mayúsculas
            this.value = this.value.toUpperCase();
            
            // Validar caracteres permitidos
            this.value = this.value.replace(/[^A-Z0-9-]/g, '');
            
            // Aplicar formato DINO-XXXX-XXX
            let valor = this.value.replace(/-/g, '');
            if (valor.length > 4) {
                if (valor.length > 8) {
                    valor = valor.substring(0, 4) + '-' + valor.substring(4, 8) + '-' + valor.substring(8, 11);
                } else {
                    valor = valor.substring(0, 4) + '-' + valor.substring(4);
                }
            }
            this.value = valor;
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

// FUNCIÓN DE INICIALIZACIÓN
function inicializarUnirsePartida() {
    console.log('Inicializando página de unirse a partida...');
    
    try {
        // Cargar datos del usuario
        cargarDatosUsuario();
        
        // Mostrar pantalla principal por defecto
        mostrarPantalla('pantalla-unirse');
        
        // Inicializar formularios
        inicializarFormularios();
        
        console.log(`Usuario actual: ${usuarioActual}`);
    } catch (error) {
        console.error('Error en inicialización:', error);
    }
}

// MANEJADOR DE ERRORES GLOBAL
window.addEventListener('error', function(e) {
    console.error('Error global en unirse_partida:', e.error);
});

// INICIALIZACIÓN CUANDO EL DOM ESTÁ LISTO
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando unirse a partida...');
    inicializarUnirsePartida();
});

// AGREGAR ESTILOS CSS ADICIONALES PARA LA LISTA DE PARTIDAS
const estilosAdicionales = `
<style>
.lista-partidas {
    margin-top: 20px;
}

.partida-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid var(--color-marron);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.partida-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    background: white;
}

.partida-info {
    flex: 1;
}

.partida-info h5 {
    margin-bottom: 10px;
    color: var(--color-texto);
    font-weight: 700;
}

.partida-info p {
    margin: 5px 0;
    color: #666;
    font-size: 0.9rem;
}

.busqueda-estado {
    padding: 40px 20px;
}

.progress {
    height: 10px;
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .partida-item {
        flex-direction: column;
        text-align: center;
    }
    
    .partida-info {
        margin-bottom: 15px;
    }
}
</style>
`;

// Insertar estilos adicionales
document.head.insertAdjacentHTML('beforeend', estilosAdicionales);

// Hacer funciones globales para que funcionen desde HTML
window.volverAlMenu = volverAlMenu;
window.volverAUnirse = volverAUnirse;
window.mostrarCodigo = mostrarCodigo;
window.mostrarPartidasDisponibles = mostrarPartidasDisponibles;
window.partidaRapida = partidaRapida;
window.buscarPartidas = buscarPartidas;
window.unirsePartida = unirsePartida;
window.iniciarBusquedaRapida = iniciarBusquedaRapida;
window.cancelarBusqueda = cancelarBusqueda;