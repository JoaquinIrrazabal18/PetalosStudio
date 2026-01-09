<?php
/**
 * app/controllers/PartidaController.php - MEJORADO para multijugador real
 */

require_once __DIR__ . '/../Models/Partida.php';
require_once __DIR__ . '/../config/Database.php';

class PartidaController {
    private $partidaModel;

    public function __construct() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->partidaModel = new Partida();
    }

    /**
     * Crear nueva partida
     */
    public function crear(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión para crear una partida', 401);
            }

            $input = $this->getJsonInput();
            if (!$input) {
                JsonResponse::error('Datos JSON inválidos', 400);
            }

            // Si no hay jugadores, agrega el usuario actual
            if (!isset($input['jugadores']) || !is_array($input['jugadores']) || count($input['jugadores']) === 0) {
                $input['jugadores'] = [$_SESSION['id_usuario']];
            }

            // Validar cantidad_jugadores
            $cantidad_jugadores = isset($input['cantidad_jugadores']) ? (int)$input['cantidad_jugadores'] : 2;
            if ($cantidad_jugadores < 2 || $cantidad_jugadores > 5) {
                JsonResponse::error('La partida debe tener entre 2 y 5 jugadores', 400);
            }

            $data = [
                'nombre' => ValidationHelper::sanitize($input['nombre'] ?? 'Nueva Partida'),
                'lado' => $input['lado'] ?? 'Verano',
                'tipo' => $input['tipo'] ?? 'publica',
                'jugadores' => $input['jugadores'],
                'contrasena' => $input['contrasena'] ?? null,
                'cantidad_jugadores' => $cantidad_jugadores
            ];

            if (!in_array($data['lado'], ['Verano', 'Invierno'])) {
                $data['lado'] = 'Verano';
            }

            $result = $this->partidaModel->crear($data);

            if ($result['success']) {
                JsonResponse::success($result['data'], 'Partida creada exitosamente');
            } else {
                JsonResponse::error($result['message'], 400);
            }
        } catch (Exception $e) {
            error_log("Error creando partida: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener estado actual de la partida
     */
    public function estado(): void {
        try {
            if (!isset($_GET['id_partida'])) {
                JsonResponse::error('ID de partida requerido', 400);
            }

            $id_partida = (int)$_GET['id_partida'];
            $result = $this->partidaModel->obtenerEstado($id_partida);

            if ($result['success']) {
                // Agregar información adicional para el frontend
                $estado = $result['partida'];
                
                // Obtener mano del jugador actual si está autenticado
                if ($this->estaAutenticado()) {
                    $mano = $this->partidaModel->obtenerManoJugador($id_partida, $_SESSION['id_usuario']);
                    $estado['mi_mano'] = $mano;
                    
                    // Obtener mi tablero
                    $tablero = $this->partidaModel->obtenerTableroJugador($id_partida, $_SESSION['id_usuario']);
                    $estado['mi_tablero'] = $tablero;
                }
                
                JsonResponse::success($estado, 'Estado obtenido exitosamente');
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error obteniendo estado: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Lanzar dado - NUEVO MÉTODO
     */
    public function lanzarDado(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión', 401);
            }

            $input = $this->getJsonInput();
            if (!$input || !isset($input['id_partida'])) {
                JsonResponse::error('ID de partida requerido', 400);
            }

            $id_partida = (int)$input['id_partida'];
            $id_usuario = $_SESSION['id_usuario'];

            $result = $this->partidaModel->lanzarDado($id_partida, $id_usuario);

            if ($result['success']) {
                JsonResponse::success($result['data'], 'Dado lanzado exitosamente');
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error lanzando dado: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Realizar movimiento
     */
    public function movimiento(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión', 401);
            }

            $input = $this->getJsonInput();
            if (!$input) {
                JsonResponse::error('Datos JSON inválidos', 400);
            }

            $requiredFields = ['id_partida', 'id_dino_ficha', 'recinto', 'posicion'];
            $errors = ValidationHelper::validateRequired($input, $requiredFields);
            
            if (!empty($errors)) {
                JsonResponse::validation($errors);
            }

            $input['id_usuario'] = $_SESSION['id_usuario'];
            $result = $this->partidaModel->realizarMovimiento($input);

            if ($result['success']) {
                JsonResponse::success($result, 'Movimiento realizado exitosamente');
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error realizando movimiento: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Obtener mano del jugador
     */
    public function obtenerMano(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión', 401);
            }

            if (!isset($_GET['id_partida'])) {
                JsonResponse::error('ID de partida requerido', 400);
            }

            $id_partida = (int)$_GET['id_partida'];
            $id_usuario = $_SESSION['id_usuario'];

            $mano = $this->partidaModel->obtenerManoJugador($id_partida, $id_usuario);
            
            JsonResponse::success([
                'mano' => $mano,
                'cantidad' => count($mano)
            ], 'Mano obtenida exitosamente');

        } catch (Exception $e) {
            error_log("Error obteniendo mano: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Obtener tablero del jugador
     */
    public function obtenerTablero(): void {
        try {
            if (!isset($_GET['id_partida']) || !isset($_GET['id_usuario'])) {
                JsonResponse::error('ID de partida e ID de usuario requeridos', 400);
            }

            $id_partida = (int)$_GET['id_partida'];
            $id_usuario = (int)$_GET['id_usuario'];

            $tablero = $this->partidaModel->obtenerTableroJugador($id_partida, $id_usuario);
            $totalDinos = 0;
            
            foreach ($tablero as $recinto => $dinos) {
                $totalDinos += count($dinos);
            }

            JsonResponse::success([
                'tablero' => $tablero,
                'total_dinosaurios' => $totalDinos
            ], 'Tablero obtenido exitosamente');

        } catch (Exception $e) {
            error_log("Error obteniendo tablero: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Obtener historial de partidas del usuario
     */
    public function historial(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión', 401);
            }

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $historial = $this->partidaModel->obtenerHistorialJugador($_SESSION['id_usuario'], $limit);

            JsonResponse::success($historial, 'Historial obtenido exitosamente');

        } catch (Exception $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    // === MÉTODOS HELPER PRIVADOS ===

    private function getJsonInput(): ?array {
        $json = file_get_contents('php://input');
        if (!$json) {
            return null;
        }
        return json_decode($json, true);
    }

    private function estaAutenticado(): bool {
        return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
    }

    private function esAdministrador(): bool {
        return $this->estaAutenticado() && 
               isset($_SESSION['rol']) && 
               $_SESSION['rol'] === 'Administrador';
    }
}