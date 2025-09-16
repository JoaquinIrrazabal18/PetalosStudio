<?php
/**
 * app/controllers/PartidaController.php - Ubicación: /tu_proyecto/app/controllers/PartidaController.php
 * Controlador corregido para integración con frontend
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

            // Validar campos requeridos
            $requiredFields = ['nombre', 'lado'];
            $errors = ValidationHelper::validateRequired($input, $requiredFields);
            
            if (!empty($errors)) {
                JsonResponse::validation($errors);
            }

            // Preparar datos de la partida
            $data = [
                'nombre' => ValidationHelper::sanitize($input['nombre']),
                'lado' => $input['lado'] ?? 'Verano',
                'jugadores' => [$_SESSION['id_usuario']] // Solo el creador por ahora
            ];

            // Validar lado del tablero
            if (!in_array($data['lado'], ['Verano', 'Invierno'])) {
                $data['lado'] = 'Verano';
            }

            $result = $this->partidaModel->crear($data);

            if ($result['success']) {
                JsonResponse::success($result, 'Partida creada exitosamente');
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error creando partida: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
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
                JsonResponse::success($result['partida'], 'Estado obtenido exitosamente');
            } else {
                JsonResponse::error($result['message'], 400);
            }

        } catch (Exception $e) {
            error_log("Error obteniendo estado: " . $e->getMessage());
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

            // Validar campos requeridos
            $requiredFields = ['id_partida', 'id_dino_ficha', 'recinto', 'posicion'];
            $errors = ValidationHelper::validateRequired($input, $requiredFields);
            
            if (!empty($errors)) {
                JsonResponse::validation($errors);
            }

            // Agregar ID del usuario de la sesión
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
     * Obtener mano de dinosaurios del jugador actual
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

            $db = Database::getInstance();
            $sql = "SELECT mano_json 
                    FROM mano_dinosaurios 
                    WHERE id_partida = ? AND id_usuario = ?
                    ORDER BY id DESC LIMIT 1";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$id_partida, $id_usuario]);
            $mano = $stmt->fetch();

            if ($mano) {
                $dinosaurios = json_decode($mano['mano_json'], true);
                
                // Obtener información completa de cada dinosaurio
                $manoCompleta = [];
                foreach ($dinosaurios as $dino) {
                    // Obtener datos del dinosaurio desde la tabla dino_ficha
                    $sqlDino = "SELECT id_dino_ficha, especie FROM dino_ficha WHERE id_dino_ficha = ?";
                    $stmtDino = $db->getConnection()->prepare($sqlDino);
                    $stmtDino->execute([$dino['id_dino_ficha']]);
                    $dinoData = $stmtDino->fetch();
                    
                    if ($dinoData) {
                        $manoCompleta[] = [
                            'id_dino_ficha' => $dinoData['id_dino_ficha'],
                            'especie' => $dinoData['especie']
                        ];
                    }
                }
                
                JsonResponse::success([
                    'mano' => $manoCompleta,
                    'cantidad' => count($manoCompleta)
                ], 'Mano obtenida exitosamente');
            } else {
                JsonResponse::success(['mano' => [], 'cantidad' => 0], 'Mano vacía');
            }

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

            $db = Database::getInstance();
            $sql = "SELECT recinto, especie, posicion, creado_en
                    FROM tablero_posiciones 
                    WHERE id_partida = ? AND id_usuario = ?
                    ORDER BY creado_en ASC";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$id_partida, $id_usuario]);
            $posiciones = $stmt->fetchAll();

            // Agrupar por recinto
            $tablero = [];
            foreach ($posiciones as $pos) {
                if (!isset($tablero[$pos['recinto']])) {
                    $tablero[$pos['recinto']] = [];
                }
                $tablero[$pos['recinto']][] = [
                    'especie' => $pos['especie'],
                    'posicion' => (int)$pos['posicion'],
                    'fecha' => $pos['creado_en']
                ];
            }

            JsonResponse::success([
                'tablero' => $tablero,
                'total_dinosaurios' => count($posiciones)
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

    /**
     * Pausar partida
     */
    public function pausar(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión', 401);
            }

            $input = $this->getJsonInput();
            if (!$input || !isset($input['id_partida'])) {
                JsonResponse::error('ID de partida requerido', 400);
            }

            $id_partida = (int)$input['id_partida'];
            
            $db = Database::getInstance();
            $sql = "UPDATE partida SET estado_partida = 'pausada' WHERE id_partida = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $success = $stmt->execute([$id_partida]);

            if ($success) {
                JsonResponse::success(['id_partida' => $id_partida], 'Partida pausada');
            } else {
                JsonResponse::error('No se pudo pausar la partida', 400);
            }

        } catch (Exception $e) {
            error_log("Error pausando partida: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Reanudar partida pausada
     */
    public function reanudar(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión', 401);
            }

            $input = $this->getJsonInput();
            if (!$input || !isset($input['id_partida'])) {
                JsonResponse::error('ID de partida requerido', 400);
            }

            $id_partida = (int)$input['id_partida'];
            
            $db = Database::getInstance();
            $sql = "UPDATE partida SET estado_partida = 'en_curso' WHERE id_partida = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $success = $stmt->execute([$id_partida]);

            if ($success) {
                JsonResponse::success(['id_partida' => $id_partida], 'Partida reanudada');
            } else {
                JsonResponse::error('No se pudo reanudar la partida', 400);
            }

        } catch (Exception $e) {
            error_log("Error reanudando partida: " . $e->getMessage());
            JsonResponse::error('Error interno del servidor', 500);
        }
    }

    /**
     * Eliminar/cancelar partida
     */
    public function eliminar(): void {
        try {
            if (!$this->estaAutenticado()) {
                JsonResponse::error('Debe iniciar sesión', 401);
            }

            $input = $this->getJsonInput();
            if (!$input || !isset($input['id_partida'])) {
                JsonResponse::error('ID de partida requerido', 400);
            }

            $id_partida = (int)$input['id_partida'];

            // Verificar permisos (solo creador o admin puede eliminar)
            // Por ahora permitimos a cualquier usuario autenticado
            
            // Cambiar estado a cancelada en lugar de eliminar físicamente
            $db = Database::getInstance();
            $sql = "UPDATE partida SET estado_partida = 'cancelada' WHERE id_partida = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $success = $stmt->execute([$id_partida]);

            if ($success && $stmt->rowCount() > 0) {
                JsonResponse::success(['id_partida' => $id_partida], 'Partida cancelada');
            } else {
                JsonResponse::error('No se pudo cancelar la partida', 400);
            }

        } catch (Exception $e) {
            error_log("Error eliminando partida: " . $e->getMessage());
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
     * Verificar si el usuario está autenticado
     */
    private function estaAutenticado(): bool {
        return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
    }

    /**
     * Verificar si el usuario es administrador
     */
    private function esAdministrador(): bool {
        return $this->estaAutenticado() && 
               isset($_SESSION['rol']) && 
               $_SESSION['rol'] === 'Administrador';
    }

    /**
     * Verificar si el usuario participa en una partida
     */
    private function participaEnPartida(int $id_partida, int $id_usuario): bool {
        try {
            $db = Database::getInstance();
            $sql = "SELECT COUNT(*) FROM participacion_p WHERE id_partida = ? AND id_usuario = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$id_partida, $id_usuario]);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validar que una partida existe y está en un estado válido
     */
    private function validarPartida(int $id_partida): array {
        try {
            $estado = $this->partidaModel->obtenerEstado($id_partida);
            return $estado;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error validando partida'];
        }
    }

    /**
     * Registrar acción en el historial de la partida
     */
    private function registrarAccion(int $id_partida, int $id_usuario, string $accion, array $meta = []): void {
        try {
            $db = Database::getInstance();
            $sql = "INSERT INTO historial (id_partida, id_usuario, accion, meta_json, fecha) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([
                $id_partida,
                $id_usuario,
                $accion,
                json_encode($meta, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (Exception $e) {
            error_log("Error registrando acción: " . $e->getMessage());
        }
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

    /**
     * Middleware para verificar participación en partida
     */
    private function requireParticipation(int $id_partida): void {
        $this->requireAuth();
        if (!$this->participaEnPartida($id_partida, $_SESSION['id_usuario'])) {
            JsonResponse::error('No participas en esta partida', 403);
        }
    }
}