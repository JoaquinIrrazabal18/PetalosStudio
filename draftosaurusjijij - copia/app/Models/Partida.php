<?php
/**
 * app/Models/Partida.php - Ubicación: /tu_proyecto/app/Models/Partida.php
 * Modelo completo para manejar partidas según el EsRe
 * Implementa todas las reglas del juego Draftosaurus
 */

require_once __DIR__ . '/../config/Database.php';

class Partida {
    private $db;
    private $connection;

    // Especies de dinosaurios disponibles
    private const ESPECIES_DINOSAURIOS = [
        'Tiranosaurio Rex', 'Triceratops', 'Brachiosaurus', 
        'Stegosaurus', 'Pterodáctilo', 'Plesiosaurio'
    ];

    // Estados válidos de partida
    private const ESTADOS_PARTIDA = [
        'creada', 'en_curso', 'pausada', 'finalizada', 'cancelada'
    ];

    // Restricciones del dado
    private const RESTRICCIONES_DADO = [
        'El Bosque' => 1,
        'La Llanura' => 2, 
        'Los Baños' => 3,
        'La Cafetería' => 4,
        'Recinto Vacío' => 5,
        '¡Cuidado con el T-Rex!' => 6
    ];

    // Recintos por tablero
    private const RECINTOS_VERANO = [
        'El Bosque de la Semejanza', 'El Prado de la Diferencia', 
        'La Pradera del Amor', 'El Trío Frondoso', 
        'El Rey de la Selva', 'La Isla Solitaria', 'Rio'
    ];

    private const RECINTOS_INVIERNO = [
        'El Bosque Ordenado', 'El Puente de los Enamorados',
        'La Pirámide', 'El Puesto de Observación', 
        'Zona de Cuarentena', 'Rio'
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }

    /**
     * Crear nueva partida según RFD1-RFD4
     */
    public function crear(array $data): array {
        try {
            // Inicia la transacción solo aquí
            if (!$this->connection->inTransaction()) {
                $this->connection->beginTransaction();
            }

            $nombre = ValidationHelper::sanitize($data['nombre'] ?? 'Partida ' . date('Y-m-d H:i:s'));
            $lado_tablero = $data['lado'] ?? 'Verano';
            $jugadores = $data['jugadores'] ?? [];
            $cantidad_jugadores = isset($data['cantidad_jugadores']) ? (int)$data['cantidad_jugadores'] : 2;

            // Permite crear la partida con solo el usuario actual
            if (!is_array($jugadores) || count($jugadores) < 1) {
                return ['success' => false, 'message' => 'Se requiere al menos un jugador'];
            }
            if ($cantidad_jugadores < 2 || $cantidad_jugadores > 5) {
                return ['success' => false, 'message' => 'La partida debe tener entre 2 y 5 jugadores'];
            }

            // RFD1: Configurar dinosaurios según número de participantes
            $dinos_iniciales = $this->calcularDinosauriosBolsa($cantidad_jugadores);

            // Crear la partida con el número de jugadores esperados
            $sql = "INSERT INTO partida (fecha_creacion, estado_partida, num_participantes, 
                                       dinos_iniciales_bolsa, lado_tablero_usado, ronda_actual, 
                                       turno_actual, dinos_restantes_bolsa) 
                    VALUES (NOW(), 'creada', ?, 0, ?, 1, 1, 0)";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $cantidad_jugadores,
                $lado_tablero
            ]);
            $id_partida = $this->connection->lastInsertId();

            // Obtener el ID del tablero según el lado
            $tablero_id = $lado_tablero === 'Invierno' ? 2 : 1;

            // Agregar participantes según RFD3 y RFD4
            $participantes_ordenados = $this->ordenarJugadoresPorEdad($jugadores);
            
            foreach ($participantes_ordenados as $index => $jugador_data) {
                $sql = "INSERT INTO participacion_p (id_usuario, id_partida, id_tablero, 
                                                   fecha_hora_inicio, posicion_turno) 
                        VALUES (?, ?, ?, NOW(), ?)";
                
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([
                    $jugador_data['id_usuario'],
                    $id_partida,
                    $tablero_id,
                    $index + 1
                ]);
            }

            // Crear dinosaurios de la bolsa
            $this->crearDinosauriosBolsa($id_partida, $dinos_iniciales);

            // Iniciar primera ronda (NO iniciar/commit transacción dentro de iniciarRonda)
            $this->iniciarRonda($id_partida, 1);

            if ($this->connection->inTransaction()) {
                $this->connection->commit();
            }

            return [
                'success' => true,
                'message' => 'Partida creada exitosamente',
                'data' => [
                    'id_partida' => (int)$id_partida,
                    'nombre' => $nombre,
                    'lado_tablero' => $lado_tablero,
                    'num_participantes' => $cantidad_jugadores,
                    'participantes' => $participantes_ordenados
                ]
            ];
        } catch (PDOException $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            error_log("Error creando partida: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno al crear la partida: ' . $e->getMessage()];
        }
    }

    /**
     * Calcular número de dinosaurios en la bolsa según participantes
     * RFD1: Configuración específica por número de jugadores
     */
    private function calcularDinosauriosBolsa(int $num_participantes): int {
        switch ($num_participantes) {
            case 5:
                return 60; // 6 especies × 10 dinosaurios
            case 3:
                return 36; // 6 especies × 6 dinosaurios (removiendo 4 por especie)
            case 2:
            case 4:
            default:
                return 48; // 6 especies × 8 dinosaurios (removiendo 2 por especie)
        }
    }

    /**
     * Ordenar jugadores por edad (más joven primero)
     * RFD3: El jugador más joven comienza
     */
    private function ordenarJugadoresPorEdad(array $jugadores): array {
        $jugadores_con_edad = [];
        
        foreach ($jugadores as $id_usuario) {
            $sql = "SELECT id_usuario, nombre_usuario, 
                           YEAR(CURDATE()) - YEAR(fecha_nacimiento) as edad 
                    FROM usuario WHERE id_usuario = ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_usuario]);
            $jugador = $stmt->fetch();
            
            if ($jugador) {
                $jugadores_con_edad[] = [
                    'id_usuario' => (int)$jugador['id_usuario'],
                    'nombre_usuario' => $jugador['nombre_usuario'],
                    'edad' => (int)($jugador['edad'] ?? 25) // Default si no tiene fecha nacimiento
                ];
            }
        }

        // Ordenar por edad ascendente (más joven primero)
        usort($jugadores_con_edad, function($a, $b) {
            return $a['edad'] - $b['edad'];
        });

        return $jugadores_con_edad;
    }

    /**
     * Crear dinosaurios en la bolsa
     */
    private function crearDinosauriosBolsa(int $id_partida, int $total_dinos): void {
        $dinos_por_especie = intval($total_dinos / 6);
        
        foreach (self::ESPECIES_DINOSAURIOS as $especie) {
            for ($i = 0; $i < $dinos_por_especie; $i++) {
                $sql = "INSERT INTO dino_ficha (id_partida, especie, ubicacion_actual) 
                        VALUES (?, ?, 'bolsa')";
                
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$id_partida, $especie]);
            }
        }
    }

    /**
     * Iniciar una ronda
     * RFD4-RFD5: Estructura de rondas y reparto de dinosaurios
     */
    private function iniciarRonda(int $id_partida, int $numero_ronda): array {
        try {
            // NO iniciar/commit transacción aquí
            // Crear registro de ronda
            $sql = "INSERT INTO ronda (id_partida, numero_ronda, fecha_hora_inicio) 
                    VALUES (?, ?, NOW())";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida, $numero_ronda]);
            
            $id_ronda = $this->connection->lastInsertId();

            // RFD5: Cada jugador saca 6 dinosaurios al azar
            $this->repartirDinosaurios($id_partida, $numero_ronda);

            // ...NO commit aquí...

            return [
                'success' => true,
                'message' => "Ronda {$numero_ronda} iniciada",
                'id_ronda' => (int)$id_ronda
            ];

        } catch (PDOException $e) {
            // NO rollback aquí
            error_log("Error iniciando ronda: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al iniciar la ronda'];
        }
    }

    /**
     * Repartir dinosaurios a los jugadores
     */
    private function repartirDinosaurios(int $id_partida, int $ronda): void {
        // Obtener participantes ordenados por turno
        $sql = "SELECT pp.id_usuario, pp.posicion_turno 
                FROM participacion_p pp 
                WHERE pp.id_partida = ? 
                ORDER BY pp.posicion_turno";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida]);
        $participantes = $stmt->fetchAll();

        // Obtener dinosaurios disponibles en la bolsa
        $sql = "SELECT id_dino_ficha, especie 
                FROM dino_ficha 
                WHERE id_partida = ? AND ubicacion_actual = 'bolsa' 
                ORDER BY RAND()";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida]);
        $dinos_disponibles = $stmt->fetchAll();

        // Repartir 6 dinosaurios a cada jugador
        $dino_index = 0;
        foreach ($participantes as $participante) {
            $mano_dinos = [];
            
            for ($i = 0; $i < 6 && $dino_index < count($dinos_disponibles); $i++) {
                $dino = $dinos_disponibles[$dino_index];
                $mano_dinos[] = [
                    'id_dino_ficha' => $dino['id_dino_ficha'],
                    'especie' => $dino['especie']
                ];
                
                // Marcar dinosaurio como en mano
                $sql = "UPDATE dino_ficha 
                        SET ubicacion_actual = 'mano' 
                        WHERE id_dino_ficha = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$dino['id_dino_ficha']]);
                
                $dino_index++;
            }

            // Guardar mano del jugador (usando tabla auxiliar)
            $sql = "INSERT INTO mano_dinosaurios (id_partida, id_usuario, ronda, mano_json) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE mano_json = VALUES(mano_json)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                $id_partida,
                $participante['id_usuario'],
                $ronda,
                json_encode($mano_dinos)
            ]);
        }
    }

    /**
     * Obtener estado actual de la partida
     */
    public function obtenerEstado(int $id_partida): array {
        try {
            // Obtener datos básicos de la partida
            $sql = "SELECT * FROM partida WHERE id_partida = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida]);
            $partida = $stmt->fetch();

            if (!$partida) {
                return ['success' => false, 'message' => 'Partida no encontrada'];
            }

            // Obtener participantes
            $sql = "SELECT pp.*, u.nombre_usuario, t.tipo_tablero 
                    FROM participacion_p pp
                    JOIN usuario u ON pp.id_usuario = u.id_usuario  
                    JOIN tablero t ON pp.id_tablero = t.id_tablero
                    WHERE pp.id_partida = ? 
                    ORDER BY pp.posicion_turno";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida]);
            $participantes = $stmt->fetchAll();

            // Obtener jugador actual del turno
            $turno_actual = $this->obtenerJugadorTurnoActual($id_partida);

            return [
                'success' => true,
                'partida' => [
                    'id_partida' => (int)$partida['id_partida'],
                    'estado_partida' => $partida['estado_partida'],
                    'lado_tablero' => $partida['lado_tablero_usado'],
                    'ronda_actual' => (int)$partida['ronda_actual'],
                    'turno_actual' => (int)$partida['turno_actual'],
                    'num_participantes' => (int)$partida['num_participantes'],
                    'participantes' => $participantes,
                    'jugador_turno_actual' => $turno_actual
                ]
            ];

        } catch (PDOException $e) {
            error_log("Error obteniendo estado de partida: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener el estado de la partida'];
        }
    }

    /**
     * Obtener jugador del turno actual
     */
    private function obtenerJugadorTurnoActual(int $id_partida): ?array {
        try {
            $sql = "SELECT p.turno_actual, pp.id_usuario, u.nombre_usuario 
                    FROM partida p
                    JOIN participacion_p pp ON p.id_partida = pp.id_partida
                    JOIN usuario u ON pp.id_usuario = u.id_usuario
                    WHERE p.id_partida = ? AND pp.posicion_turno = p.turno_actual";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo jugador turno actual: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Realizar movimiento de dinosaurio
     * RFD6-RFD8: Colocación de dinosaurios con validaciones
     */
    public function realizarMovimiento(array $data): array {
        try {
            $this->connection->beginTransaction();

            $id_partida = (int)$data['id_partida'];
            $id_usuario = (int)$data['id_usuario'];
            $id_dino_ficha = (int)$data['id_dino_ficha'];
            $recinto_destino = $data['recinto'];
            $posicion = (int)($data['posicion'] ?? 0);
            $restriccion_dado = $data['restriccion_dado'] ?? null;

            // Verificar que es el turno del jugador o que puede jugar
            $puede_jugar = $this->puedeJugar($id_partida, $id_usuario);
            if (!$puede_jugar['success']) {
                return $puede_jugar;
            }

            // Validar el movimiento según las reglas del recinto
            $validacion = $this->validarMovimiento($id_partida, $id_usuario, $id_dino_ficha, 
                                                 $recinto_destino, $posicion, $restriccion_dado);
            if (!$validacion['success']) {
                return $validacion;
            }

            // Realizar el movimiento
            $resultado = $this->ejecutarMovimiento($id_partida, $id_usuario, $id_dino_ficha, 
                                                 $recinto_destino, $posicion);

            if ($resultado['success']) {
                // Verificar si se debe avanzar el turno
                $this->avanzarTurno($id_partida);
                $this->connection->commit();
            } else {
                $this->connection->rollBack();
            }

            return $resultado;

        } catch (PDOException $e) {
            $this->connection->rollBack();
            error_log("Error realizando movimiento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno al realizar movimiento'];
        }
    }

    /**
     * Verificar si un jugador puede jugar en el turno actual
     */
    private function puedeJugar(int $id_partida, int $id_usuario): array {
        // Verificar estado de la partida
        $sql = "SELECT estado_partida FROM partida WHERE id_partida = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida]);
        $estado = $stmt->fetchColumn();

        if ($estado !== 'en_curso' && $estado !== 'creada') {
            return ['success' => false, 'message' => 'La partida no está en curso'];
        }

        // Verificar que el usuario participa en la partida
        $sql = "SELECT posicion_turno FROM participacion_p 
                WHERE id_partida = ? AND id_usuario = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario]);
        $posicion = $stmt->fetchColumn();

        if (!$posicion) {
            return ['success' => false, 'message' => 'El usuario no participa en esta partida'];
        }

        return ['success' => true];
    }

    /**
     * Validar movimiento según reglas del juego
     */
    private function validarMovimiento(int $id_partida, int $id_usuario, int $id_dino_ficha, 
                                     string $recinto, int $posicion, ?string $restriccion_dado): array {
        
        // Verificar que el dinosaurio pertenece al usuario y está en su mano
        $sql = "SELECT df.especie 
                FROM dino_ficha df
                JOIN mano_dinosaurios md ON md.id_partida = df.id_partida
                WHERE df.id_dino_ficha = ? AND df.id_partida = ? AND md.id_usuario = ?
                AND JSON_CONTAINS(md.mano_json, JSON_OBJECT('id_dino_ficha', df.id_dino_ficha))";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_dino_ficha, $id_partida, $id_usuario]);
        $dino = $stmt->fetch();

        if (!$dino) {
            return ['success' => false, 'message' => 'Dinosaurio no válido o no está en tu mano'];
        }

        // Validar restricción del dado si aplica
        if ($restriccion_dado) {
            $validacion_dado = $this->validarRestriccionDado($id_partida, $id_usuario, 
                                                            $recinto, $restriccion_dado, $dino['especie']);
            if (!$validacion_dado['success']) {
                return $validacion_dado;
            }
        }

        // Validar reglas específicas del recinto
        return $this->validarReglasRecinto($id_partida, $id_usuario, $recinto, 
                                         $dino['especie'], $posicion);
    }

    /**
     * Validar restricción del dado
     * RFD54-RFD63: Restricciones específicas del dado
     */
    private function validarRestriccionDado(int $id_partida, int $id_usuario, string $recinto, 
                                          string $restriccion, string $especie_dino): array {
        
        // Obtener información del recinto
        $sql = "SELECT r.area, r.lado, r.capacidad_maxima
                FROM recinto r
                JOIN tablero t ON r.id_tablero = t.id_tablero
                JOIN participacion_p pp ON pp.id_tablero = t.id_tablero
                WHERE pp.id_partida = ? AND pp.id_usuario = ? AND r.nombre_recinto = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario, $recinto]);
        $info_recinto = $stmt->fetch();

        if (!$info_recinto) {
            return ['success' => false, 'message' => 'Recinto no válido'];
        }

        // Validar según la restricción
        switch ($restriccion) {
            case 'El Bosque':
                if ($info_recinto['area'] !== 'Bosque') {
                    return ['success' => false, 'message' => 'Debes colocar en un recinto del Bosque'];
                }
                break;

            case 'La Llanura':
                if ($info_recinto['area'] !== 'Llanura') {
                    return ['success' => false, 'message' => 'Debes colocar en un recinto de la Llanura'];
                }
                break;

            case 'Los Baños':
                if ($info_recinto['lado'] !== 'Baños') {
                    return ['success' => false, 'message' => 'Debes colocar en la zona de Baños'];
                }
                break;

            case 'La Cafetería':
                if ($info_recinto['lado'] !== 'Cafetería') {
                    return ['success' => false, 'message' => 'Debes colocar en la zona de Cafetería'];
                }
                break;

            case 'Recinto Vacío':
                // Verificar que el recinto esté vacío
                $sql = "SELECT COUNT(*) as ocupados 
                        FROM tablero_posiciones 
                        WHERE id_partida = ? AND id_usuario = ? AND recinto = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$id_partida, $id_usuario, $recinto]);
                $ocupados = $stmt->fetchColumn();
                
                if ($ocupados > 0) {
                    return ['success' => false, 'message' => 'El recinto debe estar vacío'];
                }
                break;

            case '¡Cuidado con el T-Rex!':
                // Verificar que el recinto no contenga un T-Rex
                $sql = "SELECT COUNT(*) as trex_count 
                        FROM tablero_posiciones 
                        WHERE id_partida = ? AND id_usuario = ? AND recinto = ? AND especie = 'Tiranosaurio Rex'";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$id_partida, $id_usuario, $recinto]);
                $trex_count = $stmt->fetchColumn();
                
                if ($trex_count > 0) {
                    return ['success' => false, 'message' => 'No puedes colocar en un recinto con T-Rex'];
                }
                break;
        }

        return ['success' => true];
    }

    /**
     * Validar reglas específicas de cada recinto
     * RFD15-RFD48: Reglas de colocación por recinto
     */
    private function validarReglasRecinto(int $id_partida, int $id_usuario, string $recinto, 
                                        string $especie, int $posicion): array {
        
        // Obtener dinosaurios ya colocados en el recinto
        $sql = "SELECT especie, posicion 
                FROM tablero_posiciones 
                WHERE id_partida = ? AND id_usuario = ? AND recinto = ?
                ORDER BY posicion";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario, $recinto]);
        $dinos_existentes = $stmt->fetchAll();

        // Aplicar reglas según el recinto
        switch ($recinto) {
            case 'El Bosque de la Semejanza':
                return $this->validarBosqueSemejanza($dinos_existentes, $especie, $posicion);
                
            case 'El Prado de la Diferencia':
                return $this->validarPradoDiferencia($dinos_existentes, $especie, $posicion);
                
            case 'La Pradera del Amor':
                return $this->validarPraderaAmor($dinos_existentes, $posicion);
                
            case 'El Trío Frondoso':
                return $this->validarTrioFrondoso($dinos_existentes);
                
            case 'El Rey de la Selva':
            case 'La Isla Solitaria':
            case 'El Puesto de Observación':
            case 'Zona de Cuarentena':
                return $this->validarRecintoUnico($dinos_existentes);
                
            case 'Rio':
                return ['success' => true]; // El río siempre acepta dinosaurios
                
            default:
                return ['success' => true];
        }
    }

    /**
     * RFD15-RFD17: Validar Bosque de la Semejanza
     */
    private function validarBosqueSemejanza(array $dinos_existentes, string $especie, int $posicion): array {
        if (count($dinos_existentes) >= 6) {
            return ['success' => false, 'message' => 'El bosque está completo'];
        }

        if (!empty($dinos_existentes)) {
            // Solo puede haber dinosaurios de la misma especie
            if ($dinos_existentes[0]['especie'] !== $especie) {
                return ['success' => false, 'message' => 'Solo pueden haber dinosaurios de la misma especie'];
            }
            
            // Debe llenarse de izquierda a derecha
            $siguiente_posicion = count($dinos_existentes);
            if ($posicion !== $siguiente_posicion) {
                return ['success' => false, 'message' => 'Debe llenarse de izquierda a derecha'];
            }
        }

        return ['success' => true];
    }

    /**
     * RFD18-RFD20: Validar Prado de la Diferencia
     */
    private function validarPradoDiferencia(array $dinos_existentes, string $especie, int $posicion): array {
        if (count($dinos_existentes) >= 6) {
            return ['success' => false, 'message' => 'El prado está completo'];
        }

        // Solo especies diferentes
        foreach ($dinos_existentes as $dino) {
            if ($dino['especie'] === $especie) {
                return ['success' => false, 'message' => 'No puede haber especies repetidas'];
            }
        }

        // Debe llenarse de izquierda a derecha
        $siguiente_posicion = count($dinos_existentes);
        if ($posicion !== $siguiente_posicion) {
            return ['success' => false, 'message' => 'Debe llenarse de izquierda a derecha'];
        }

        return ['success' => true];
    }

    /**
     * RFD21-RFD24: Validar Pradera del Amor
     */
    private function validarPraderaAmor(array $dinos_existentes, int $posicion): array {
        if (count($dinos_existentes) >= 6) {
            return ['success' => false, 'message' => 'La pradera está completa'];
        }

        return ['success' => true];
    }

    /**
     * RFD25-RFD27: Validar Trío Frondoso
     */
    private function validarTrioFrondoso(array $dinos_existentes): array {
        if (count($dinos_existentes) >= 3) {
            return ['success' => false, 'message' => 'El trío solo puede tener 3 dinosaurios'];
        }

        return ['success' => true];
    }

    /**
     * Validar recintos únicos (1 dinosaurio máximo)
     */
    private function validarRecintoUnico(array $dinos_existentes): array {
        if (count($dinos_existentes) >= 1) {
            return ['success' => false, 'message' => 'Este recinto solo puede tener 1 dinosaurio'];
        }

        return ['success' => true];
    }

    /**
     * Ejecutar el movimiento validado
     */
    private function ejecutarMovimiento(int $id_partida, int $id_usuario, int $id_dino_ficha, 
                                      string $recinto, int $posicion): array {
        try {
            // Obtener especie del dinosaurio
            $sql = "SELECT especie FROM dino_ficha WHERE id_dino_ficha = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_dino_ficha]);
            $especie = $stmt->fetchColumn();

            // Registrar en tablero_posiciones
            $sql = "INSERT INTO tablero_posiciones (id_partida, id_usuario, recinto, posicion, especie, creado_en) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida, $id_usuario, $recinto, $posicion, $especie]);

            // Actualizar ubicación del dinosaurio
            $sql = "UPDATE dino_ficha SET ubicacion_actual = ? WHERE id_dino_ficha = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$recinto, $id_dino_ficha]);

            // Remover dinosaurio de la mano del jugador
            $this->removerDinoMano($id_partida, $id_usuario, $id_dino_ficha);

            return ['success' => true, 'message' => 'Movimiento realizado exitosamente'];

        } catch (PDOException $e) {
            error_log("Error ejecutando movimiento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al ejecutar el movimiento'];
        }
    }

    /**
     * Remover dinosaurio de la mano del jugador
     */
    private function removerDinoMano(int $id_partida, int $id_usuario, int $id_dino_ficha): void {
        $sql = "SELECT mano_json FROM mano_dinosaurios 
                WHERE id_partida = ? AND id_usuario = ?
                ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario]);
        $mano_actual = $stmt->fetch();

        if ($mano_actual) {
            $mano = json_decode($mano_actual['mano_json'], true);
            
            // Remover el dinosaurio de la mano
            $mano = array_filter($mano, function($dino) use ($id_dino_ficha) {
                return $dino['id_dino_ficha'] != $id_dino_ficha;
            });

            // Actualizar la mano
            $sql = "UPDATE mano_dinosaurios 
                    SET mano_json = ?
                    WHERE id_partida = ? AND id_usuario = ?
                    ORDER BY id DESC LIMIT 1";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([json_encode(array_values($mano)), $id_partida, $id_usuario]);
        }
    }

    /**
     * Avanzar turno y verificar fin de ronda/partida
     * RFD10-RFD14: Gestión de turnos y rondas
     */
    private function avanzarTurno(int $id_partida): void {
        // Verificar si todos los jugadores completaron el turno
        $sql = "SELECT COUNT(DISTINCT pp.id_usuario) as total_jugadores,
                       COUNT(DISTINCT tp.id_usuario) as jugadores_jugaron
                FROM participacion_p pp
                LEFT JOIN tablero_posiciones tp ON pp.id_usuario = tp.id_usuario AND pp.id_partida = tp.id_partida
                WHERE pp.id_partida = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida]);
        $info = $stmt->fetch();

        // Si todos jugaron, avanzar al siguiente turno
        if ($info['total_jugadores'] == $info['jugadores_jugaron']) {
            $sql = "UPDATE partida 
                    SET turno_actual = turno_actual + 1 
                    WHERE id_partida = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida]);

            // Verificar si se completó la ronda (6 turnos)
            $this->verificarFinRonda($id_partida);
        }
    }

    /**
     * Verificar si se completó una ronda
     */
    private function verificarFinRonda(int $id_partida): void {
        $sql = "SELECT ronda_actual, turno_actual FROM partida WHERE id_partida = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida]);
        $partida = $stmt->fetch();

        if ($partida['turno_actual'] > 6) {
            // Finalizar ronda actual
            $sql = "UPDATE ronda 
                    SET fecha_hora_fin = NOW() 
                    WHERE id_partida = ? AND numero_ronda = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida, $partida['ronda_actual']]);

            if ($partida['ronda_actual'] >= 2) {
                // Finalizar partida
                $this->finalizarPartida($id_partida);
            } else {
                // Iniciar siguiente ronda
                $sql = "UPDATE partida 
                        SET ronda_actual = ronda_actual + 1, turno_actual = 1 
                        WHERE id_partida = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$id_partida]);

                $this->iniciarRonda($id_partida, $partida['ronda_actual'] + 1);
            }
        }
    }

    /**
     * Finalizar partida y calcular puntuaciones
     * RFD64-RFD68: Final de partida y desempate
     */
    public function finalizarPartida(int $id_partida): array {
        try {
            $this->connection->beginTransaction();

            // Actualizar estado de la partida
            $sql = "UPDATE partida 
                    SET estado_partida = 'finalizada', fecha_hora_fin = NOW() 
                    WHERE id_partida = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_partida]);

            // Calcular puntuaciones finales
            $puntuaciones = $this->calcularPuntuacionesFinal($id_partida);

            // Actualizar puntuaciones en la base de datos
            foreach ($puntuaciones as $resultado) {
                $sql = "UPDATE participacion_p 
                        SET puntuacion_final = ?, estado_participacion = 'Completada'
                        WHERE id_partida = ? AND id_usuario = ?";
                
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([
                    $resultado['puntuacion'],
                    $id_partida,
                    $resultado['id_usuario']
                ]);
            }

            // Determinar ganador (aplicar regla de desempate)
            $ganador = $this->determinarGanador($puntuaciones);
            if ($ganador) {
                $sql = "UPDATE participacion_p 
                        SET gano_partida = 1 
                        WHERE id_partida = ? AND id_usuario = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$id_partida, $ganador['id_usuario']]);
            }

            $this->connection->commit();

            return [
                'success' => true,
                'message' => 'Partida finalizada',
                'puntuaciones' => $puntuaciones,
                'ganador' => $ganador
            ];

        } catch (PDOException $e) {
            $this->connection->rollBack();
            error_log("Error finalizando partida: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al finalizar la partida'];
        }
    }

    /**
     * Calcular puntuaciones finales según las reglas
     */
    private function calcularPuntuacionesFinal(int $id_partida): array {
        $sql = "SELECT pp.id_usuario, u.nombre_usuario, pp.id_tablero
                FROM participacion_p pp
                JOIN usuario u ON pp.id_usuario = u.id_usuario
                WHERE pp.id_partida = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida]);
        $participantes = $stmt->fetchAll();

        $resultados = [];

        foreach ($participantes as $participante) {
            $puntuacion = $this->calcularPuntuacionJugador($id_partida, $participante['id_usuario'], 
                                                         $participante['id_tablero']);
            
            $resultados[] = [
                'id_usuario' => $participante['id_usuario'],
                'nombre_usuario' => $participante['nombre_usuario'],
                'puntuacion' => $puntuacion
            ];
        }

        // Ordenar por puntuación descendente
        usort($resultados, function($a, $b) {
            return $b['puntuacion'] - $a['puntuacion'];
        });

        return $resultados;
    }

    /**
     * Calcular puntuación de un jugador específico
     */
    private function calcularPuntuacionJugador(int $id_partida, int $id_usuario, int $id_tablero): int {
        $puntuacion_total = 0;

        // Obtener todas las posiciones del jugador
        $sql = "SELECT recinto, especie, COUNT(*) as cantidad
                FROM tablero_posiciones
                WHERE id_partida = ? AND id_usuario = ?
                GROUP BY recinto, especie";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario]);
        $posiciones = $stmt->fetchAll();

        // Agrupar por recinto
        $recintos = [];
        foreach ($posiciones as $pos) {
            if (!isset($recintos[$pos['recinto']])) {
                $recintos[$pos['recinto']] = [];
            }
            $recintos[$pos['recinto']][$pos['especie']] = $pos['cantidad'];
        }

        // Calcular puntuación por recinto según las reglas
        foreach ($recintos as $nombre_recinto => $dinos) {
            $puntos_recinto = $this->calcularPuntosRecinto($nombre_recinto, $dinos, $id_partida, $id_usuario);
            $puntuacion_total += $puntos_recinto;
        }

        // Bonus por T-Rex (RFD52-RFD53): +1 punto por recinto que contenga al menos un T-Rex
        $bonus_trex = $this->calcularBonusTRex($id_partida, $id_usuario);
        $puntuacion_total += $bonus_trex;

        return $puntuacion_total;
    }

    /**
     * Calcular puntos de un recinto específico
     */
    private function calcularPuntosRecinto(string $recinto, array $dinos, int $id_partida, int $id_usuario): int {
        switch ($recinto) {
            case 'El Bosque de la Semejanza':
                // RFD17: Tabla de puntuación específica
                $total_dinos = array_sum($dinos);
                $tabla_puntos = [0, 1, 4, 8, 12, 18, 24];
                return $tabla_puntos[$total_dinos] ?? 0;

            case 'El Prado de la Diferencia':
                // RFD20: Tabla de puntuación específica
                $especies_diferentes = count($dinos);
                $tabla_puntos = [0, 1, 3, 6, 10, 15, 21];
                return $tabla_puntos[$especies_diferentes] ?? 0;

            case 'La Pradera del Amor':
                // RFD22: 5 puntos por cada pareja
                $puntos = 0;
                foreach ($dinos as $cantidad) {
                    $puntos += intval($cantidad / 2) * 5;
                }
                return $puntos;

            case 'El Trío Frondoso':
                // RFD26: 7 puntos si hay exactamente 3 dinosaurios
                $total = array_sum($dinos);
                return ($total === 3) ? 7 : 0;

            case 'El Rey de la Selva':
                // RFD29: 7 puntos si ningún otro jugador tiene más de esa especie
                return $this->calcularPuntosReySelva($id_partida, $id_usuario, $dinos);

            case 'La Isla Solitaria':
                // RFD32: 7 puntos si es el único de su especie en el parque
                return $this->calcularPuntosIslaSolitaria($id_partida, $id_usuario, $dinos);

            case 'Rio':
                // RFD51: 1 punto por dinosaurio
                return array_sum($dinos);

            default:
                return 0;
        }
    }

    /**
     * Calcular puntos para Rey de la Selva
     */
    private function calcularPuntosReySelva(int $id_partida, int $id_usuario, array $dinos): int {
        if (empty($dinos)) return 0;

        $especie_rey = array_keys($dinos)[0]; // Solo puede haber 1 dinosaurio

        // Contar cuántos dinosaurios de esa especie tiene este jugador en total
        $sql = "SELECT COUNT(*) as total
                FROM tablero_posiciones
                WHERE id_partida = ? AND id_usuario = ? AND especie = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario, $especie_rey]);
        $mi_total = $stmt->fetchColumn();

        // Verificar si otros jugadores tienen más
        $sql = "SELECT COUNT(*) as total
                FROM tablero_posiciones tp
                JOIN participacion_p pp ON tp.id_usuario = pp.id_usuario AND tp.id_partida = pp.id_partida
                WHERE tp.id_partida = ? AND tp.id_usuario != ? AND tp.especie = ?
                GROUP BY tp.id_usuario
                HAVING total > ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario, $especie_rey, $mi_total]);
        $otros_con_mas = $stmt->fetchAll();

        return empty($otros_con_mas) ? 7 : 0;
    }

    /**
     * Calcular puntos para Isla Solitaria
     */
    private function calcularPuntosIslaSolitaria(int $id_partida, int $id_usuario, array $dinos): int {
        if (empty($dinos)) return 0;

        $especie_isla = array_keys($dinos)[0];

        // Verificar si es el único de esa especie en todo el parque del jugador
        $sql = "SELECT COUNT(*) as total
                FROM tablero_posiciones
                WHERE id_partida = ? AND id_usuario = ? AND especie = ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario, $especie_isla]);
        $total_especie = $stmt->fetchColumn();

        return ($total_especie === 1) ? 7 : 0;
    }

    /**
     * Calcular bonus por T-Rex
     */
    private function calcularBonusTRex(int $id_partida, int $id_usuario): int {
        $sql = "SELECT COUNT(DISTINCT recinto) as recintos_con_trex
                FROM tablero_posiciones
                WHERE id_partida = ? AND id_usuario = ? AND especie = 'Tiranosaurio Rex' AND recinto != 'Rio'";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id_partida, $id_usuario]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Determinar ganador aplicando reglas de desempate
     * RFD67-RFD68: Reglas de desempate
     */
    private function determinarGanador(array $puntuaciones): ?array {
        if (empty($puntuaciones)) return null;

        $max_puntuacion = $puntuaciones[0]['puntuacion'];
        $empatados = array_filter($puntuaciones, function($p) use ($max_puntuacion) {
            return $p['puntuacion'] === $max_puntuacion;
        });

        if (count($empatados) === 1) {
            return $empatados[0];
        }

        // Aplicar desempate: gana quien tiene menos T-Rex
        $ganador = null;
        $menor_trex = PHP_INT_MAX;

        foreach ($empatados as $participante) {
            $sql = "SELECT COUNT(*) as trex_count
                    FROM tablero_posiciones
                    WHERE id_partida = ? AND id_usuario = ? AND especie = 'Tiranosaurio Rex'";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$participante['id_partida'] ?? 0, $participante['id_usuario']]);
            $trex_count = (int)$stmt->fetchColumn();

            if ($trex_count < $menor_trex) {
                $menor_trex = $trex_count;
                $ganador = $participante;
            }
        }

        return $ganador;
    }

    /**
     * Listar partidas
     */
    public function listar(int $limit = 20, int $offset = 0): array {
        try {
            $sql = "SELECT p.id_partida, p.fecha_creacion, p.estado_partida, p.lado_tablero_usado,
                           p.num_participantes, p.fecha_hora_fin,
                           u_ganador.nombre_usuario as ganador
                    FROM partida p
                    LEFT JOIN participacion_p pp_ganador ON p.id_partida = pp_ganador.id_partida AND pp_ganador.gano_partida = 1
                    LEFT JOIN usuario u_ganador ON pp_ganador.id_usuario = u_ganador.id_usuario
                    ORDER BY p.fecha_creacion DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error listando partidas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener historial de un jugador
     */
    public function obtenerHistorialJugador(int $id_usuario, int $limit = 10): array {
        try {
            $sql = "SELECT p.id_partida, p.fecha_creacion, p.estado_partida, p.lado_tablero_usado,
                           p.num_participantes, p.fecha_hora_fin, pp.puntuacion_final, pp.gano_partida
                    FROM partida p
                    JOIN participacion_p pp ON p.id_partida = pp.id_partida
                    WHERE pp.id_usuario = ? AND p.estado_partida = 'finalizada'
                    ORDER BY p.fecha_creacion DESC
                    LIMIT ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id_usuario, $limit]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            return [];
        }
    }
}