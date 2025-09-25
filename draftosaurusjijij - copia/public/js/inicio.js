// public/js/inicio.js - CORREGIDO para conectar con APIs

// CONFIGURACIÓN GLOBAL - CORREGIDA
const CONFIG = {
    API_BASE_URL: '../routes',
    ENDPOINTS: {
        USUARIO: '../routes/usuario.php',
        PARTIDA: '../routes/partida.php'
    }
};

// Función para obtener la URL base correcta
function getBaseUrl() {
    const currentPath = window.location.pathname;
    const basePath = currentPath.includes('/public/') ? '../' : './';
    return basePath;
}

// Actualizar endpoints con la URL base correcta
CONFIG.ENDPOINTS.USUARIO = getBaseUrl() + 'routes/usuario.php';
CONFIG.ENDPOINTS.PARTIDA = getBaseUrl() + 'routes/partida.php';

// VARIABLES GLOBALES
let usuarioActual = null;
let edadUsuario = null;

// CLASE PARA MANEJAR LA API - MEJORADA
class ApiClient {
    static async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        const config = { ...defaultOptions, ...options };
        
        try {
            console.log('🔄 Realizando petición a:', url);
            console.log('📤 Opciones:', config);
            
            const response = await fetch(url, config);
            
            // Obtener el texto de la respuesta primero
            const responseText = await response.text();
            console.log('📥 Respuesta raw:', responseText);
            
            // Intentar parsear como JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ Error parseando JSON:', parseError);
                console.error('📝 Respuesta que causó el error:', responseText);
                throw new Error('La respuesta del servidor no es JSON válido. Verifica la configuración del servidor.');
            }
            
            console.log('✅ Datos parseados:', data);
            
            if (!response.ok && !data.success) {
                throw new Error(data.message || `HTTP Error: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('🚨 API Error:', error);
            throw error;
        }
    }

    static async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    static async get(url, params = {}) {
        const urlParams = new URLSearchParams(params);
        const fullUrl = urlParams.toString() ? `${url}?${urlParams}` : url;
        return this.request(fullUrl, { method: 'GET' });
    }
}

// FUNCIÓN PARA MOSTRAR MENSAJES VISUALES
function mostrarMensaje(mensaje, tipo = 'info', duracion = 3000) {
    // Remover mensajes anteriores
    const mensajesAnteriores = document.querySelectorAll('.alert-message');
    mensajesAnteriores.forEach(msg => msg.remove());

    // Crear elemento de mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `alert alert-${tipo} alert-dismissible fade show alert-message`;
    mensajeDiv.style.position = 'fixed';
    mensajeDiv.style.top = '20px';
    mensajeDiv.style.right = '20px';
    mensajeDiv.style.zIndex = '9999';
    mensajeDiv.style.maxWidth = '400px';
    
    mensajeDiv.innerHTML = `
        <div>${mensaje}</div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(mensajeDiv);
    
    // Auto-remover después de la duración especificada
    setTimeout(() => {
        if (mensajeDiv.parentNode) {
            mensajeDiv.remove();
        }
    }, duracion);
    
    return mensajeDiv;
}

// FUNCIONES DE NAVEGACIÓN ENTRE PANTALLAS
function mostrarPantalla(idPantalla) {
    const pantallas = [
        'pantalla-edad',
        'pantalla-bienvenida', 
        'pantalla-invitado',
        'pantalla-registro',
        'pantalla-login'
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
    const pantallaElemento = document.getElementById(idPantalla);
    if (pantallaElemento) {
        pantallaElemento.classList.remove('oculto');
        pantallaElemento.classList.add('aparecer');
    }
}

// VERIFICACIÓN DE EDAD MEJORADA
async function verificarEdad() {
    const campoEdad = document.getElementById('edad-usuario');
    const edad = parseInt(campoEdad.value);

    // Validaciones
    if (!campoEdad.value || campoEdad.value.trim() === '') {
        mostrarMensaje('Por favor, ingresa tu edad', 'warning');
        campoEdad.focus();
        return;
    }

    if (isNaN(edad) || edad < 8 || edad > 120) {
        mostrarMensaje('Por favor, ingresa una edad válida (entre 8 y 120 años)', 'danger');
        campoEdad.focus();
        return;
    }

    edadUsuario = edad;

    // Mensaje especial para menores
    if (edad < 12) {
        mostrarMensaje('Recuerda siempre pedir ayuda a un adulto cuando juegues online', 'info', 4000);
    }

    mostrarMensaje('¡Edad verificada correctamente!', 'success', 2000);
    
    setTimeout(() => {
        mostrarPantalla('pantalla-bienvenida');
    }, 1000);
}

// FUNCIONES DE NAVEGACIÓN
function volverBienvenida() {
    mostrarPantalla('pantalla-bienvenida');
}

function mostrarModoInvitado() {
    mostrarPantalla('pantalla-invitado');
}

function mostrarRegistro() {
    mostrarPantalla('pantalla-registro');
}

function mostrarLogin() {
    mostrarPantalla('pantalla-login');
}

// ENTRADA AL JUEGO
async function entrarAlJuego(nombreUsuario) {
    try {
        usuarioActual = nombreUsuario;

        // Guardar en localStorage
        const datosUsuario = {
            usuario: nombreUsuario,
            edad: edadUsuario,
            fecha: new Date().toISOString(),
            tipo: nombreUsuario === 'Invitado' ? 'invitado' : 'registrado'
        };
        
        localStorage.setItem('usuario_draftosaurus', JSON.stringify(datosUsuario));

        mostrarMensaje(`¡Bienvenido ${nombreUsuario}!`, 'success', 2000);

        // Redirigir al menú
        setTimeout(() => {
            window.location.href = `menu.php?usuario=${encodeURIComponent(nombreUsuario)}`;
        }, 1500);

    } catch (error) {
        console.error('Error entrando al juego:', error);
        mostrarMensaje('Error al entrar al juego', 'danger');
    }
}

// MANEJADORES DE FORMULARIOS MEJORADOS
function inicializarFormularios() {
    const formRegistro = document.getElementById('formulario-registro');
    const formLogin = document.getElementById('formulario-login');

    if (formRegistro) {
        formRegistro.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            const usuario = document.getElementById('usuario-registro').value.trim();
            const password = document.getElementById('password-registro').value;
            const confirmarPassword = document.getElementById('confirmar-password').value;

            // Validaciones
            if (!usuario || usuario.length < 3) {
                mostrarMensaje('El nombre de usuario debe tener al menos 3 caracteres', 'warning');
                return;
            }

            if (!password || password.length < 6) {
                mostrarMensaje('La contraseña debe tener al menos 6 caracteres', 'warning');
                return;
            }

            if (password !== confirmarPassword) {
                mostrarMensaje('Las contraseñas no coinciden', 'danger');
                return;
            }

            try {
                mostrarMensaje('Registrando usuario...', 'info', 1000);
                
                // Llamar a la API de registro
                const response = await ApiClient.post(CONFIG.ENDPOINTS.USUARIO + '?action=registrar', {
                    nombre_usuario: usuario,
                    email: usuario + '@draftosaurus.local', // Email temporal para demo
                    password: password,
                    edad: edadUsuario
                });

                console.log('🎉 Respuesta del registro:', response);

                if (response.success) {
                    mostrarMensaje(`¡Cuenta creada exitosamente! Bienvenido ${usuario}!`, 'success', 3000);
                    usuarioActual = response.data;
                    setTimeout(() => {
                        entrarAlJuego(usuario);
                    }, 1500);
                } else {
                    mostrarMensaje(response.message || 'Error al registrar usuario', 'danger');
                }

            } catch (error) {
                console.error('💥 Error en registro:', error);
                mostrarMensaje('Error al registrar usuario: ' + error.message, 'danger');
            }
        });
    }

    if (formLogin) {
        formLogin.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            const usuario = document.getElementById('usuario-login').value.trim();
            const password = document.getElementById('password-login').value;

            if (!usuario || !password) {
                mostrarMensaje('Por favor, completa todos los campos', 'warning');
                return;
            }

            try {
                mostrarMensaje('Iniciando sesión...', 'info', 1000);
                
                // Llamar a la API de login
                const response = await ApiClient.post(CONFIG.ENDPOINTS.USUARIO + '?action=login', {
                    email: usuario + '@draftosaurus.local', // Email temporal para demo
                    password: password
                });

                console.log('🔐 Respuesta del login:', response);

                if (response.success) {
                    mostrarMensaje(`¡Bienvenido de vuelta, ${response.data.nombre_usuario}!`, 'success', 2000);
                    usuarioActual = response.data;
                    setTimeout(() => {
                        entrarAlJuego(response.data.nombre_usuario);
                    }, 1000);
                } else {
                    mostrarMensaje(response.message || 'Error al iniciar sesión', 'danger');
                }

            } catch (error) {
                console.error('💥 Error en login:', error);
                mostrarMensaje('Error al iniciar sesión: ' + error.message, 'danger');
            }
        });
    }

    // Validación en tiempo real para la edad
    const campoEdad = document.getElementById('edad-usuario');
    if (campoEdad) {
        campoEdad.addEventListener('input', function() {
            const edad = parseInt(this.value);
            if (this.value && (isNaN(edad) || edad < 8 || edad > 120)) {
                this.style.borderColor = '#dc3545';
                this.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
            } else if (this.value) {
                this.style.borderColor = '#28a745';
                this.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
            } else {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });

        // Permitir envío con Enter
        campoEdad.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verificarEdad();
            }
        });
    }
}

// VERIFICAR SESIÓN EXISTENTE
async function verificarSesionExistente() {
    try {
        const response = await ApiClient.get(CONFIG.ENDPOINTS.USUARIO + '?action=verificar_sesion');
        
        if (response.success && response.data) {
            usuarioActual = response.data;
            
            // Si hay sesión activa, redirigir al menú
            const confirmacion = confirm(`Ya tienes una sesión activa como ${response.data.nombre_usuario}. ¿Deseas continuar?`);
            if (confirmacion) {
                window.location.href = `menu.php?usuario=${encodeURIComponent(response.data.nombre_usuario)}`;
            }
        }
    } catch (error) {
        // No hay sesión activa o error - continuar normalmente
        console.log('No hay sesión activa');
    }
}

// INICIALIZACIÓN DE LA APLICACIÓN
document.addEventListener('DOMContentLoaded', async function () {
    console.log('🚀 Inicializando aplicación Draftosaurus...');
    console.log('🔧 Configuración:', CONFIG);
    
    try {
        // Verificar si hay una sesión existente
        await verificarSesionExistente();
        
        // Verificar que estamos en la página correcta
        const pantallaEdad = document.getElementById('pantalla-edad');
        if (pantallaEdad) {
            mostrarPantalla('pantalla-edad');
            inicializarFormularios();
        } else {
            console.error('No se encontró la pantalla de edad. Verifica el HTML.');
        }

        // Configurar manejo de errores global
        window.addEventListener('error', function(e) {
            console.error('💥 Error global:', e.error);
            mostrarMensaje('Ha ocurrido un error inesperado', 'danger');
        });

        // Configurar manejo de promesas rechazadas
        window.addEventListener('unhandledrejection', function(e) {
            console.error('💥 Promesa rechazada:', e.reason);
            mostrarMensaje('Error de conexión', 'danger');
        });

    } catch (error) {
        console.error('💥 Error inicializando aplicación:', error);
        mostrarMensaje('Error al inicializar la aplicación', 'danger');
    }
});

// HACER FUNCIONES GLOBALES PARA COMPATIBILIDAD CON HTML
window.verificarEdad = verificarEdad;
window.volverBienvenida = volverBienvenida;
window.mostrarModoInvitado = mostrarModoInvitado;
window.mostrarRegistro = mostrarRegistro;
window.mostrarLogin = mostrarLogin;
window.entrarAlJuego = entrarAlJuego;
window.mostrarMensaje = mostrarMensaje;
window.ApiClient = ApiClient;

// EXPORTAR PARA USO EN OTROS MÓDULOS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ApiClient,
        mostrarMensaje,
        CONFIG
    };
}