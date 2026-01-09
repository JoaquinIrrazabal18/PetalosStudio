<?php
/**
 * app/controllers/UsuarioController.php
 * Controlador para gestionar usuarios según los requerimientos del EsRe
 */

require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../config/Database.php';

class UsuarioController {
    private $usuarioModel;

    public function __construct() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->usuarioModel = new Usuario();
    }

    /**
     * Registrar nuevo usuario
     * RFJ1: Creación de Perfil
     */
    public function registrar(): void {
        try {
            $input = $this->getJsonInput();

            if (!$input) {
                JsonResponse::error('Datos JSON inválidos', 400);
            }

            // Validar campos requeridos
            $requiredFields = ['nombre_usuario', 'email', 'password'];
            $errors = ValidationHelper::validateRequired($input, $requiredFields);

            if (!empty($errors)) {
                JsonResponse::validation($errors);
            }

            // Preparar datos del usuario
            $userData = [
                'nombre_usuario' => trim($input['nombre_usuario']),
                'email' => trim($input['email']),
                'password' => $input['password'],
                'edad' => isset($input['edad']) ? (int)$input['edad'] : null,
                'rol' => 'Jugador' // Por defecto siempre Jugador
            ];

            // Crear usuario
            $result = $this->usuarioModel->crear($userData);

            if ($result['success']) {
                // Iniciar sesión automáticamente después del registro
                $this->iniciarSesion($result['usuario']);
                JsonResponse::success($result['usuario'], $result['message']);
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error en registro: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Iniciar sesión de usuario
     * RFJ2: Inicio de Sesión de Usuario Registrado
     */
    public function login(): void {
        try {
            $input = $this->getJsonInput();

            if (!$input) {
                JsonResponse::error('Datos JSON inválidos', 400);
            }

            // Validar campos requeridos
            if (empty($input['email']) || empty($input['password'])) {
                JsonResponse::error('Email y contraseña son obligatorios', 400);
            }

            // Autenticar usuario
            $result = $this->usuarioModel->autenticar($input['email'], $input['password']);

            if ($result['success']) {
                $this->iniciarSesion($result['usuario']);
                JsonResponse::success($result['usuario'], $result['message']);
            } else {
                JsonResponse::error($result['message'], 401);
            }

        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void {
        try {
            $this->cerrarSesion();
            JsonResponse::success(null, 'Sesión cerrada exitosamente');
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            JsonResponse::error('Error cerrando sesión', 500);
        }
    }

    /**
     * Verificar estado de sesión
     */
    public function verificarSesion(): void {
        try {
            if ($this->estaAutenticado()) {
                $usuario = [
                    'id_usuario' => $_SESSION['id_usuario'],
                    'nombre_usuario' => $_SESSION['nombre_usuario'],
                    'email' => $_SESSION['email'],
                    'rol' => $_SESSION['rol']
                ];
                JsonResponse::success($usuario, 'Sesión activa');
            } else {
                JsonResponse::error('No hay sesión activa', 401);
            }
        } catch (Exception $e) {
            error_log("Error verificando sesión: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Solicitar recuperación de cuenta
     * RFJ8: Solicitud de Cambio de Contraseña
     */
    public function recuperarCuenta(): void {
        try {
            $input = $this->getJsonInput();

            if (!$input || empty($input['email'])) {
                JsonResponse::error('Email es obligatorio', 400);
            }

            $usuario = $this->usuarioModel->buscarPorEmail($input['email']);
            if (!$usuario) {
                JsonResponse::error('Email no encontrado', 404);
            }

            // Generar código de verificación (6 dígitos)
            $codigo = random_int(100000, 999999);
            
            // Guardar en sesión (en producción sería mejor usar cache/redis con TTL)
            $_SESSION['codigo_recuperacion'] = $codigo;
            $_SESSION['email_recuperacion'] = $usuario['email'];
            $_SESSION['tiempo_codigo'] = time();

            // En producción aquí enviarías un email
            // Por ahora devolvemos el código para testing
            JsonResponse::success([
                'codigo_demo' => $codigo, // Solo para desarrollo
                'email' => $usuario['email']
            ], 'Código de verificación generado');

        } catch (Exception $e) {
            error_log("Error en recuperación: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Verificar código de recuperación
     */
    public function verificarCodigo(): void {
        try {
            $input = $this->getJsonInput();

            if (!$input || empty($input['codigo'])) {
                JsonResponse::error('Código es obligatorio', 400);
            }

            // Verificar que existe un código en sesión
            if (!isset($_SESSION['codigo_recuperacion']) || !isset($_SESSION['tiempo_codigo'])) {
                JsonResponse::error('No hay código de recuperación activo', 400);
            }

            // Verificar que el código no haya expirado (15 minutos)
            if (time() - $_SESSION['tiempo_codigo'] > 900) {
                unset($_SESSION['codigo_recuperacion'], $_SESSION['tiempo_codigo'], $_SESSION['email_recuperacion']);
                JsonResponse::error('Código expirado', 400);
            }

            // Verificar código
            if ($_SESSION['codigo_recuperacion'] != $input['codigo']) {
                JsonResponse::error('Código inválido', 400);
            }

            // Marcar código como verificado
            $_SESSION['codigo_verificado'] = true;
            JsonResponse::success(null, 'Código verificado correctamente');

        } catch (Exception $e) {
            error_log("Error verificando código: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Establecer nueva contraseña
     */
    public function nuevaContrasena(): void {
        try {
            $input = $this->getJsonInput();

            if (!$input || empty($input['nueva_password'])) {
                JsonResponse::error('Nueva contraseña es obligatoria', 400);
            }

            // Verificar que el código fue verificado
            if (!isset($_SESSION['codigo_verificado']) || !$_SESSION['codigo_verificado']) {
                JsonResponse::error('Código no verificado', 400);
            }

            if (!isset($_SESSION['email_recuperacion'])) {
                JsonResponse::error('Sesión de recuperación inválida', 400);
            }

            // Actualizar contraseña
            $result = $this->usuarioModel->actualizarPassword(
                $_SESSION['email_recuperacion'], 
                $input['nueva_password']
            );

            if ($result['success']) {
                // Limpiar datos de recuperación
                unset($_SESSION['codigo_recuperacion'], $_SESSION['tiempo_codigo'], 
                      $_SESSION['email_recuperacion'], $_SESSION['codigo_verificado']);
                
                JsonResponse::success(null, $result['message']);
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Listar usuarios (solo para administradores)
     */
    public function listar(): void {
        try {
            // Verificar autenticación y permisos de administrador
            if (!$this->estaAutenticado()) {
                JsonResponse::error('No autenticado', 401);
            }

            if ($_SESSION['rol'] !== 'Administrador') {
                JsonResponse::error('Acceso denegado', 403);
            }

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $usuarios = $this->usuarioModel->listar($limit, $offset);
            $total = $this->usuarioModel->contarTotal();

            JsonResponse::success([
                'usuarios' => $usuarios,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ], 'Usuarios obtenidos exitosamente');

        } catch (Exception $e) {
            error_log("Error listando usuarios: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Obtener perfil del usuario actual
     */
    public function obtenerPerfil(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('No autenticado', 401);
            }

            $usuario = $this->usuarioModel->buscarPorId($_SESSION['id_usuario']);
            if (!$usuario) {
                JsonResponse::error('Usuario no encontrado', 404);
            }

            // Obtener estadísticas del usuario
            $estadisticas = $this->usuarioModel->obtenerEstadisticas($_SESSION['id_usuario']);
            
            $perfil = array_merge($usuario, ['estadisticas' => $estadisticas]);
            
            JsonResponse::success($perfil, 'Perfil obtenido exitosamente');

        } catch (Exception $e) {
            error_log("Error obteniendo perfil: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Actualizar perfil del usuario
     */
    public function actualizarPerfil(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('No autenticado', 401);
            }

            $input = $this->getJsonInput();
            if (!$input) {
                JsonResponse::error('Datos JSON inválidos', 400);
            }

            $result = $this->usuarioModel->actualizarPerfil($_SESSION['id_usuario'], $input);

            if ($result['success']) {
                // Actualizar datos de sesión si se cambió el nombre
                if (isset($input['nombre_usuario'])) {
                    $_SESSION['nombre_usuario'] = $input['nombre_usuario'];
                }
                JsonResponse::success(null, $result['message']);
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error actualizando perfil: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    // === MÉTODOS HELPER PRIVADOS ===

    /**
     * Obtener input JSON de la petición
     */
    private function getJsonInput(): ?array {
        $json = file_get_contents('php://input');
        if (!$json) {
            return null;
        }
        return json_decode($json, true);
    }

    /**
     * Iniciar sesión del usuario
     */
    private function iniciarSesion(array $usuario): void {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['login_time'] = time();
        
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
    }

    /**
     * Cerrar sesión del usuario
     */
    private function cerrarSesion(): void {
        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Eliminar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();
    }

    /**
     * Verificar si el usuario está autenticado
     */
    private function estaAutenticado(): bool {
        return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
    }

    /**
     * Verificar si el usuario es administrador
     */
    private function esAdministrador(): bool {
        return $this->estaAutenticado() && $_SESSION['rol'] === 'Administrador';
    }

    /**
     * Middleware para verificar autenticación
     */
    private function requireAuth(): void {
        if (!$this->estaAutenticado()) {
            JsonResponse::error('Acceso no autorizado', 401);
        }
    }

    /**
     * Middleware para verificar permisos de administrador
     */
    private function requireAdmin(): void {
        $this->requireAuth();
        if (!$this->esAdministrador()) {
            JsonResponse::error('Permisos insuficientes', 403);
        }
    }
}