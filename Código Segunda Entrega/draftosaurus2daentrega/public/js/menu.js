// menu.js - Archivo JavaScript específico para el menú principal
// VARIABLES GLOBALES
let usuarioActual = null;

// FUNCIONES DE NAVEGACIÓN ENTRE PANTALLAS
function mostrarPantalla(idPantalla) {
    console.log(`Cambiando a pantalla: ${idPantalla}`);
    
    const pantallas = [
        'pantalla-menu',
        'pantalla-presentacion',
        'pantalla-tablero',
        'pantalla-resultados'
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
        // Fallback: mostrar menú principal
        const menuPrincipal = document.getElementById('pantalla-menu');
        if (menuPrincipal) {
            menuPrincipal.classList.remove('oculto');
            menuPrincipal.classList.add('aparecer');
        }
    }
}

// FUNCIONES DEL MENÚ PRINCIPAL
function mostrarPresentacion() {
    mostrarPantalla('pantalla-presentacion');
}

function mostrarTablero() {
    mostrarPantalla('pantalla-tablero');
}

function volverAlMenu() {
    mostrarPantalla('pantalla-menu');
}

function simularPartida() {
    try {
        // Obtener nombre del usuario desde el DOM o usar valor por defecto
        const elementoNombre = document.getElementById('nombre-jugador');
        const nombreJugador = elementoNombre ? elementoNombre.textContent : 'Explorador';
        
        // Actualizar elementos de resultado
        const jugadorResultado = document.getElementById('jugador-resultado');
        const nombreFinal = document.getElementById('nombre-final');
        
        if (jugadorResultado) jugadorResultado.textContent = nombreJugador;
        if (nombreFinal) nombreFinal.textContent = nombreJugador;

        // Generar estadísticas aleatorias para la simulación
        const puntos = Math.floor(Math.random() * 100) + 1;
        const dinos = 12;
        const recintos = Math.floor(Math.random() * 7) + 1;
        const turnos = dinos;

        // Actualizar puntuación
        const puntosElemento = document.querySelector('.puntos-grandes');
        if (puntosElemento) {
            puntosElemento.textContent = `${puntos} puntos`;
        }
        
        // Actualizar estadísticas
        const estadisticas = document.querySelectorAll('.estadisticas-resultado h4');
        if (estadisticas.length >= 4) {
            estadisticas[0].textContent = dinos;
            estadisticas[1].textContent = recintos;
            estadisticas[2].textContent = turnos;
            estadisticas[3].textContent = '2'; // Rondas fijas
        }

        mostrarPantalla('pantalla-resultados');
    } catch (error) {
        console.error('Error en simulación de partida:', error);
        alert('Error al simular partida. Por favor, intenta de nuevo.');
    }
}

// FUNCIONES DE NAVEGACIÓN A OTRAS PÁGINAS
function crearPartida() {
    console.log('Navegando a crear partida...');
    try {
        window.location.href = "crear_partida.php";
    } catch (error) {
        console.error('Error al navegar a crear partida:', error);
        alert('Error al navegar. Verifica que el archivo crear_partida.php exista.');
    }
}

function unirsePartida() {
    alert('🦕 ¡Función "Unirse a Partida" próximamente!\n\nAquí podrás:\n• Ver partidas disponibles\n• Ingresar código de partida\n• Partidas rápidas');
}

function verHistorial() {
    console.log('Navegando a historial...');
    try {
        window.location.href = "historial.php";
    } catch (error) {
        console.error('Error al navegar a historial:', error);
        alert('Error al navegar. Verifica que el archivo historial.php exista.');
    }
}

function mostrarAjustes() {
    console.log('Navegando a configuraciones...');
    try {
        window.location.href = "Configuraciones.php";
    } catch (error) {
        console.error('Error al navegar a configuraciones:', error);
        alert('Error al navegar. Verifica que el archivo Configuraciones.php exista.');
    }
}

function cerrarSesion() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        try {
            // Limpiar datos del usuario
            usuarioActual = null;
            
            // Limpiar localStorage si existe
            if (typeof(Storage) !== "undefined") {
                localStorage.removeItem('usuario_draftosaurus');
            }
            
            // Redirigir al inicio
            window.location.href = "inicio.php";
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
            alert('Error al cerrar sesión. Intenta recargar la página.');
        }
    }
}

// FUNCIÓN PARA CARGAR DATOS DEL USUARIO
function cargarDatosUsuario() {
    try {
        // Intentar cargar desde URL params (si viene de inicio.php)
        const urlParams = new URLSearchParams(window.location.search);
        const nombreParam = urlParams.get('usuario');
        
        if (nombreParam) {
            const elementoNombre = document.getElementById('nombre-jugador');
            if (elementoNombre) {
                elementoNombre.textContent = nombreParam;
            }
            usuarioActual = nombreParam;
            return;
        }

        // Intentar cargar desde localStorage
        if (typeof(Storage) !== "undefined") {
            const usuarioGuardado = localStorage.getItem('usuario_draftosaurus');
            if (usuarioGuardado) {
                const datosUsuario = JSON.parse(usuarioGuardado);
                if (datosUsuario.usuario) {
                    const elementoNombre = document.getElementById('nombre-jugador');
                    if (elementoNombre) {
                        elementoNombre.textContent = datosUsuario.usuario;
                    }
                    usuarioActual = datosUsuario.usuario;
                    return;
                }
            }
        }

        // Valor por defecto
        const elementoNombre = document.getElementById('nombre-jugador');
        if (elementoNombre) {
            elementoNombre.textContent = 'Explorador';
        }
        usuarioActual = 'Explorador';
        
    } catch (error) {
        console.error('Error al cargar datos del usuario:', error);
        const elementoNombre = document.getElementById('nombre-jugador');
        if (elementoNombre) {
            elementoNombre.textContent = 'Explorador';
        }
        usuarioActual = 'Explorador';
    }
}

// FUNCIÓN DE INICIALIZACIÓN
function inicializarMenu() {
    console.log('Inicializando menu...');
    
    try {
        // Cargar datos del usuario
        cargarDatosUsuario();
        
        // Mostrar el menú principal por defecto
        mostrarPantalla('pantalla-menu');
        
        console.log(`Usuario actual: ${usuarioActual}`);
    } catch (error) {
        console.error('Error en inicialización del menu:', error);
    }
}

// MANEJADOR DE ERRORES GLOBAL PARA EL MENÚ
window.addEventListener('error', function(e) {
    console.error('Error global en menu:', e.error);
});

// INICIALIZACIÓN CUANDO EL DOM ESTÁ LISTO
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando menu...');
    inicializarMenu();
});

// MANEJO DE ESTADO DE NAVEGACIÓN (back/forward)
window.addEventListener('popstate', function(event) {
    console.log('Cambio de estado de navegación detectado');
    inicializarMenu();
});

// Hacer funciones globales para que funcionen desde HTML
window.mostrarPresentacion = mostrarPresentacion;
window.mostrarTablero = mostrarTablero;
window.volverAlMenu = volverAlMenu;
window.simularPartida = simularPartida;
window.crearPartida = crearPartida;
window.unirsePartida = unirsePartida;
window.verHistorial = verHistorial;
window.mostrarAjustes = mostrarAjustes;
window.cerrarSesion = cerrarSesion;