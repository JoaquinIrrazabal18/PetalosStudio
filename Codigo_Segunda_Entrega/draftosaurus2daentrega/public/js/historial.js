// public/js/historial.js
// CAPA VISTA: Maneja toda la lógica del frontend para el historial

// CONFIGURACIÓN GLOBAL
const CONFIG = {
    API_BASE_URL: '/routes/historial.php',  // URL base de la API
    LIMITE_DEFAULT: 10,                     // Límite por defecto de partidas
    TIMEOUT_REQUEST: 10000                  // Timeout de 10 segundos
};

// CLASE PRINCIPAL para manejar el historial
class HistorialManager {
    constructor() {
        this.historial = [];          // Array para almacenar los datos
        this.estadisticas = null;     // Objeto para estadísticas
        this.cargando = false;        // Flag para controlar estado de carga
        this.init();                  // Inicializar al crear la instancia
    }

    /**
     * MÉTODO DE INICIALIZACIÓN: Se ejecuta cuando se carga la página
     */
    init() {
        console.log('🚀 Inicializando Historial Manager...');
        
        // VERIFICAR que estamos en la página correcta
        if (!document.getElementById('lista-partidas')) {
            console.warn('⚠️ Elemento lista-partidas no encontrado');
            return;
        }

        // CARGAR DATOS automáticamente al iniciar
        this.cargarHistorial();
        
        // CONFIGURAR EVENT LISTENERS (escuchadores de eventos)
        this.configurarEventListeners();
    }

    /**
     * MÉTODO PRINCIPAL: Cargar historial desde el servidor
     */
    async cargarHistorial(limite = CONFIG.LIMITE_DEFAULT) {
        try {
            // MOSTRAR INDICADOR DE CARGA
            this.mostrarCargando(true);
            this.cargando = true;

            console.log(`📡 Cargando historial (límite: ${limite})...`);

            // REALIZAR PETICIÓN HTTP a la API
            const url = `${CONFIG.API_BASE_URL}?action=obtener&limite=${limite}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                // TIMEOUT para evitar cuelgues
                signal: AbortSignal.timeout(CONFIG.TIMEOUT_REQUEST)
            });

            // VERIFICAR que la respuesta sea exitosa
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }

            // PARSEAR RESPUESTA JSON
            const data = await response.json();
            console.log('📦 Datos recibidos:', data);

            // VERIFICAR estructura de la respuesta
            if (!data.success) {
                throw new Error(data.mensaje || 'Error desconocido del servidor');
            }

            // ALMACENAR DATOS en la instancia
            this.historial = data.data.historial || [];
            this.estadisticas = data.data.estadisticas || null;

            // RENDERIZAR DATOS en la página
            this.renderizarHistorial();
            this.renderizarEstadisticas();

            console.log('✅ Historial cargado exitosamente');

        } catch (error) {
            // MANEJO DE ERRORES detallado
            console.error('❌ Error al cargar historial:', error);
            
            let mensajeError = 'Error desconocido';
            
            if (error.name === 'AbortError') {
                mensajeError = 'La petición tardó demasiado en responder';
            } else if (error.name === 'NetworkError') {
                mensajeError = 'Error de conexión. Verifica tu internet.';
            } else {
                mensajeError = error.message;
            }

            this.mostrarError(mensajeError);
            
        } finally {
            // SIEMPRE ocultar el indicador de carga
            this.mostrarCargando(false);
            this.cargando = false;
        }
    }

    /**
     * RENDERIZAR: Crear HTML para mostrar el historial
     */
    renderizarHistorial() {
        const contenedor = document.getElementById('lista-partidas');
        
        if (!contenedor) {
            console.error('❌ Contenedor lista-partidas no encontrado');
            return;
        }

        // SI NO HAY PARTIDAS
        if (!this.historial || this.historial.length === 0) {
            contenedor.innerHTML = this.crearHTMLSinPartidas();
            return;
        }

        // GENERAR HTML para cada partida
        let html = '<div class="historial-container">';
        
        this.historial.forEach((partida, index) => {
            html += this.crearHTMLPartida(partida, index);
        });
        
        html += '</div>';
        
        // INSERTAR HTML en el DOM
        contenedor.innerHTML = html;
        
        // CONFIGURAR eventos de las tarjetas
        this.configurarEventosPartidas();
    }

    /**
     * CREAR HTML: Template para una partida individual
     */
    crearHTMLPartida(partida, index) {
        // FORMATEAR FECHA de manera legible
        const fechaFormateada = this.formatearFecha(partida.fecha_fin);
        
        // CREAR lista de jugadores con colores
        let jugadoresHTML = '';
        partida.jugadores.forEach((jugador, i) => {
            const esGanador = jugador.nombre === partida.ganador;
            const claseGanador = esGanador ? 'jugador-ganador' : '';
            const iconoGanador = esGanador ? '🏆' : '';
            
            jugadoresHTML += `
                <div class="jugador ${claseGanador}">
                    <span class="nombre">${iconoGanador} ${jugador.nombre}</span>
                    <span class="puntaje">${jugador.puntaje} pts</span>
                </div>
            `;
        });

        // TEMPLATE COMPLETO de la tarjeta
        return `
            <div class="partida-card" data-partida-id="${partida.id}" data-index="${index}">
                <div class="partida-header">
                    <h3 class="partida-titulo">${partida.nombre}</h3>
                    <span class="partida-tablero tablero-${partida.lado_tablero.toLowerCase()}">
                        ${partida.lado_tablero}
                    </span>
                </div>
                
                <div class="partida-info">
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>${fechaFormateada}</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>Duración: ${partida.duracion}</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span>${partida.total_jugadores} jugadores</span>
                    </div>
                </div>

                <div class="partida-jugadores">
                    <h4>Resultados:</h4>
                    ${jugadoresHTML}
                </div>

                <div class="partida-acciones">
                    <button class="btn-detalle" onclick="historialManager.verDetalle(${partida.id})">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * CREAR HTML: Mensaje cuando no hay partidas
     */
    crearHTMLSinPartidas() {
        return `
            <div class="sin-partidas">
                <div class="sin-partidas-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>No hay partidas finalizadas</h3>
                <p>Cuando completes algunas partidas, aparecerán aquí.</p>
                <button class="boton-volver" onclick="window.location.href='inicio.php'">
                    <i class="fas fa-arrow-left"></i> Volver al Menú
                </button>
            </div>
        `;
    }

    /**
     * CONFIGURAR: Event listeners para interacciones
     */
    configurarEventListeners() {
        // BOTÓN de recarga (si existe)
        const btnRecargar = document.getElementById('btn-recargar');
        if (btnRecargar) {
            btnRecargar.addEventListener('click', () => {
                console.log('🔄 Recargando historial...');
                this.cargarHistorial();
            });
        }

        // SELECTOR de límite (si existe)
        const selectLimite = document.getElementById('limite-partidas');
        if (selectLimite) {
            selectLimite.addEventListener('change', (e) => {
                const nuevoLimite = parseInt(e.target.value);
                console.log(`🔢 Cambiando límite a: ${nuevoLimite}`);
                this.cargarHistorial(nuevoLimite);
            });
        }
    }

    /**
     * CONFIGURAR: Eventos específicos de las tarjetas de partida
     */
    configurarEventosPartidas() {
        // Agregar efecto hover y click a las tarjetas
        const tarjetas = document.querySelectorAll('.partida-card');
        
        tarjetas.forEach(tarjeta => {
            // EFECTO HOVER
            tarjeta.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            tarjeta.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    /**
     * VER DETALLE: Mostrar información completa de una partida
     */
    async verDetalle(partidaId) {
        try {
            console.log(`🔍 Viendo detalle de partida ${partidaId}...`);
            
            // BUSCAR partida en los datos locales primero
            const partida = this.historial.find(p => p.id === partidaId);
            
            if (!partida) {
                throw new Error('Partida no encontrada en los datos locales');
            }

            // MOSTRAR MODAL o página de detalle
            this.mostrarModalDetalle(partida);
            
        } catch (error) {
            console.error('❌ Error al ver detalle:', error);
            this.mostrarError('No se pudo cargar el detalle de la partida');
        }
    }

    /**
     * MODAL: Crear y mostrar modal con detalle de partida
     */
    mostrarModalDetalle(partida) {
        // CREAR modal dinámicamente
        const modalHTML = `
            <div class="modal-overlay" id="modal-detalle">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>${partida.nombre}</h2>
                        <button class="modal-close" onclick="historialManager.cerrarModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="detalle-info">
                            <h3>Información General</h3>
                            <p><strong>Tablero:</strong> ${partida.lado_tablero}</p>
                            <p><strong>Fecha inicio:</strong> ${this.formatearFecha(partida.fecha_inicio)}</p>
                            <p><strong>Fecha fin:</strong> ${this.formatearFecha(partida.fecha_fin)}</p>
                            <p><strong>Duración:</strong> ${partida.duracion}</p>
                        </div>
                        
                        <div class="detalle-jugadores">
                            <h3>Clasificación Final</h3>
                            ${this.crearTablaJugadores(partida.jugadores)}
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button class="boton-volver" onclick="historialManager.cerrarModal()">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;

        // AGREGAR al DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // MOSTRAR con animación
        requestAnimationFrame(() => {
            document.getElementById('modal-detalle').classList.add('mostrar');
        });
    }

    /**
     * CERRAR MODAL: Remover modal del DOM
     */
    cerrarModal() {
        const modal = document.getElementById('modal-detalle');
        if (modal) {
            modal.classList.remove('mostrar');
            setTimeout(() => {
                modal.remove();
            }, 300); // Esperar animación de salida
        }
    }

    /**
     * CREAR TABLA: HTML para mostrar jugadores ordenados
     */
    crearTablaJugadores(jugadores) {
        let html = '<div class="tabla-jugadores">';
        
        jugadores.forEach((jugador, index) => {
            const posicion = index + 1;
            const esGanador = posicion === 1;
            const medalla = posicion <= 3 ? this.obtenerMedalla(posicion) : '';
            
            html += `
                <div class="fila-jugador ${esGanador ? 'ganador' : ''}">
                    <span class="posicion">${medalla} ${posicion}°</span>
                    <span class="nombre-jugador">${jugador.nombre}</span>
                    <span class="puntaje-jugador">${jugador.puntaje} pts</span>
                    <span class="estado-jugador ${jugador.estado.toLowerCase()}">
                        ${jugador.estado}
                    </span>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    /**
     * OBTENER MEDALLA: Emoji según posición
     */
    obtenerMedalla(posicion) {
        const medallasEmoji = {
            1: '🏆',
            2: '🥈', 
            3: '🥉'
        };
        return medallasEmoji[posicion] || '';
    }

    /**
     * RENDERIZAR ESTADÍSTICAS: Mostrar estadísticas generales
     */
    renderizarEstadisticas() {
        const contenedorEstadisticas = document.getElementById('estadisticas-generales');
        
        if (!contenedorEstadisticas || !this.estadisticas) {
            return;
        }

        const stats = this.estadisticas;
        const duracionPromedio = Math.round(stats.duracion_promedio || 0);
        
        contenedorEstadisticas.innerHTML = `
            <div class="estadisticas-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div class="stat-info">
                        <h4>${stats.total_partidas || 0}</h4>
                        <p>Total Partidas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h4>${stats.partidas_completadas || 0}</h4>
                        <p>Completadas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h4>${duracionPromedio}min</h4>
                        <p>Promedio Duración</p>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * MOSTRAR CARGANDO: Indicador visual de carga
     */
    mostrarCargando(mostrar = true) {
        const contenedor = document.getElementById('lista-partidas');
        
        if (!contenedor) return;

        if (mostrar) {
            contenedor.innerHTML = `
                <div class="cargando">
                    <div class="spinner"></div>
                    <p>Cargando historial...</p>
                </div>
            `;
        }
    }

    /**
     * MOSTRAR ERROR: Mensaje de error al usuario
     */
    mostrarError(mensaje) {
        const contenedor = document.getElementById('lista-partidas');
        
        if (!contenedor) {
            alert(`Error: ${mensaje}`);
            return;
        }
    }

    /**
     * FORMATEAR FECHA: Convertir fecha SQL a formato legible
     */
    formatearFecha(fechaSQL) {
        if (!fechaSQL) return 'Fecha desconocida';
        
        try {
            const fecha = new Date(fechaSQL);
            const opciones = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            
            return fecha.toLocaleDateString('es-ES', opciones);
        } catch (error) {
            console.warn('Error al formatear fecha:', error);
            return 'Fecha inválida';
        }
    }

    /**
     * ACTUALIZAR: Método para actualizar datos sin recargar página
     */
    async actualizar() {
        if (this.cargando) {
            console.log('⏳ Ya hay una carga en progreso...');
            return;
        }
        
        await this.cargarHistorial();
    }
}

// INICIALIZACIÓN AUTOMÁTICA cuando se carga la página
let historialManager = null;

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 DOM cargado, inicializando Historial Manager...');
    historialManager = new HistorialManager();
});

// FUNCIONES GLOBALES para compatibilidad con HTML onclick
function recargarHistorial() {
    if (historialManager) {
        historialManager.actualizar();
    }
}

function verDetallePartida(partidaId) {
    if (historialManager) {
        historialManager.verDetalle(partidaId);
    }
}