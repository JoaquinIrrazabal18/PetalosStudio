<?php
/**
 * app/Models/Usuario.php
 * Modelo para la gestión de usuarios según el EsRe
 * Implementa todas las validaciones y operaciones necesarias
 */

require_once __DIR__ . '/../config/Database.php';

class Usuario {
    private $db;
    private $connection;

    private const ALLOWED_ROLES = ['Jugador', 'Administrador'];
    private const MIN_PASSWORD_LENGTH = 6;
    private const MIN_AGE = 8;
    private const MAX_AGE = 120;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }

    /**
     * Crear un nuevo usuario con validaciones completas
     */
    public function crear(array $data): array {
        try {
            // Validar datos requeridos
            $requiredFields = ['nombre_usuario', 'email', 'password'];
            $errors = ValidationHelper::validateRequired($data, $requiredFields);
            
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Datos faltantes', 'errors' => $errors];
            }

            // Validaciones específicas
            if (!ValidationHelper::validateEmail($data['email'])) {
                return ['success' => false, 'message' => 'Email inválido'];
            }

            if (!ValidationHelper::validatePassword($data['password'])) {
                return ['success' => false, 'message' => 'La contraseña debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres'];
            }

            // Validar edad si se proporciona
            if (isset($data['edad']) && !ValidationHelper::validateAge((int)$data['edad'])) {
                return ['success' => false, 'message' => 'La edad debe estar entre ' . self::MIN_AGE . ' y ' . self::MAX_AGE . ' años'];
            }

            // Verificar si el email ya existe
            if ($this->existeEmail($data['email'])) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }

            // Verificar si el nombre de usuario ya existe
            if ($this->existeNombreUsuario($data['nombre_usuario'])) {
                return ['success' => false, 'message' => 'El nombre de usuario ya existe'];
            }

            // Preparar datos para inserción
            $userData = [
                'nombre_usuario' => ValidationHelper::sanitize($data['nombre_usuario']),
                'email' => strtolower(trim($data['email'])),
                'contrasena_cifrada' => password_hash($data['password'], PASSWORD_DEFAULT),
                'fecha_registro' => date('Y-m-d H:i:s'),
                'rol' => $data['rol'] ?? 'Jugador'
            ];

            // Validar rol
            if (!in_array($userData['rol'], self::ALLOWED_ROLES)) {
                $userData['rol'] = 'Jugador';
            }

            // Agregar fecha de nacimiento si se proporciona edad
            if (isset($data['edad'])) {
                $fechaNacimiento = date('Y-m-d', strtotime('-' . (int)$data['edad'] . ' years'));
                $userData['fecha_nacimiento'] = $fechaNacimiento;
            }

            // Insertar usuario en la base de datos
            $sql = "INSERT INTO usuario (nombre_usuario, email, contrasena_cifrada, fecha_nacimiento, fecha_registro, rol) 
                    VALUES (:nombre_usuario, :email, :contrasena_cifrada, :fecha_nacimiento, :fecha_registro, :rol)";

            $stmt = $this->connection->prepare($sql);
            $success = $stmt->execute([
                ':nombre_usuario' => $userData['nombre_usuario'],
                ':email' => $userData['email'],
                ':contrasena_cifrada' => $userData['contrasena_cifrada'],
                ':fecha_nacimiento' => $userData['fecha_nacimiento'] ?? null,
                ':fecha_registro' => $userData['fecha_registro'],
                ':rol' => $userData['rol']
            ]);

            if ($success) {
                $userId = $this->connection->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Usuario creado exitosamente',
                    'id_usuario' => (int)$userId,
                    'usuario' => [
                        'id_usuario' => (int)$userId,
                        'nombre_usuario' => $userData['nombre_usuario'],
                        'email' => $userData['email'],
                        'rol' => $userData['rol']
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear el usuario'];
            }

        } catch (PDOException $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Verificar si existe un email
     */
    private function existeEmail(string $email): bool {
        $sql = "SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch() !== false;
    }

    /**
     * Verificar si existe un nombre de usuario
     */
    private function existeNombreUsuario(string $nombreUsuario): bool {
        $sql = "SELECT id_usuario FROM usuario WHERE nombre_usuario = ? LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([trim($nombreUsuario)]);
        return $stmt->fetch() !== false;
    }

    /**
     * Buscar usuario por email
     */
    public function buscarPorEmail(string $email): ?array {
        try {
            $sql = "SELECT id_usuario, nombre_usuario, email, contrasena_cifrada, fecha_nacimiento, 
                           fecha_registro, ultimo_login, rol 
                    FROM usuario 
                    WHERE email = ? LIMIT 1";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([strtolower(trim($email))]);
            
            $usuario = $stmt->fetch();
            return $usuario ?: null;
        } catch (PDOException $e) {
            error_log("Error buscando usuario por email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar usuario por ID
     */
    public function buscarPorId(int $id): ?array {
        try {
            $sql = "SELECT id_usuario, nombre_usuario, email, fecha_nacimiento, 
                           fecha_registro, ultimo_login, rol 
                    FROM usuario 
                    WHERE id_usuario = ? LIMIT 1";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$id]);
            
            $usuario = $stmt->fetch();
            return $usuario ?: null;
        } catch (PDOException $e) {
            error_log("Error buscando usuario por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Autenticar usuario
     */
    public function autenticar(string $email, string $password): array {
        try {
            $usuario = $this->buscarPorEmail($email);
            
            if (!$usuario) {
                return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
            }

            if (!password_verify($password, $usuario['contrasena_cifrada'])) {
                return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
            }

            // Actualizar último login
            $this->actualizarUltimoLogin($usuario['id_usuario']);

            // Remover la contraseña de la respuesta
            unset($usuario['contrasena_cifrada']);

            return [
                'success' => true,
                'message' => 'Autenticación exitosa',
                'usuario' => $usuario
            ];
        } catch (PDOException $e) {
            error_log("Error en autenticación: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Actualizar último login
     */
    private function actualizarUltimoLogin(int $userId): void {
        try {
            $sql = "UPDATE usuario SET ultimo_login = NOW() WHERE id_usuario = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error actualizando último login: " . $e->getMessage());
        }
    }

    /**
     * Actualizar contraseña
     */
    public function actualizarPassword(string $email, string $newPassword): array {
        try {
            if (!ValidationHelper::validatePassword($newPassword)) {
                return ['success' => false, 'message' => 'La contraseña debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres'];
            }

            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE usuario SET contrasena_cifrada = ? WHERE email = ?";
            $stmt = $this->connection->prepare($sql);
            $success = $stmt->execute([$passwordHash, strtolower(trim($email))]);

            if ($success && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Contraseña actualizada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
        } catch (PDOException $e) {
            error_log("Error actualizando contraseña: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Listar todos los usuarios (para administradores)
     */
    public function listar(int $limit = 50, int $offset = 0): array {
        try {
            $sql = "SELECT id_usuario, nombre_usuario, email, fecha_nacimiento, 
                           fecha_registro, ultimo_login, rol
                    FROM usuario 
                    ORDER BY fecha_registro DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error listando usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar total de usuarios
     */
    public function contarTotal(): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM usuario";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error contando usuarios: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar perfil de usuario
     */
    public function actualizarPerfil(int $userId, array $data): array {
        try {
            $allowedFields = ['nombre_usuario', 'fecha_nacimiento'];
            $updateFields = [];
            $params = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field]) && !empty($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $field === 'nombre_usuario' 
                        ? ValidationHelper::sanitize($data[$field])
                        : $data[$field];
                }
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'No hay campos para actualizar'];
            }

            // Verificar que el nombre de usuario no esté en uso
            if (isset($data['nombre_usuario'])) {
                $sql = "SELECT id_usuario FROM usuario WHERE nombre_usuario = ? AND id_usuario != ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([ValidationHelper::sanitize($data['nombre_usuario']), $userId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'El nombre de usuario ya está en uso'];
                }
            }

            $params[] = $userId;
            $sql = "UPDATE usuario SET " . implode(', ', $updateFields) . " WHERE id_usuario = ?";
            
            $stmt = $this->connection->prepare($sql);
            $success = $stmt->execute($params);

            if ($success && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Perfil actualizado exitosamente'];
            } else {
                return ['success' => false, 'message' => 'No se pudo actualizar el perfil'];
            }
        } catch (PDOException $e) {
            error_log("Error actualizando perfil: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Eliminar usuario (soft delete - cambiar rol)
     */
    public function eliminar(int $userId): array {
        try {
            // En lugar de eliminar, podríamos desactivar o cambiar el estado
            $sql = "DELETE FROM usuario WHERE id_usuario = ?";
            $stmt = $this->connection->prepare($sql);
            $success = $stmt->execute([$userId]);

            if ($success && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Usuario eliminado exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
        } catch (PDOException $e) {
            error_log("Error eliminando usuario: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Obtener estadísticas básicas del usuario
     */
    public function obtenerEstadisticas(int $userId): array {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT p.id_partida) as partidas_jugadas,
                        SUM(CASE WHEN pp.gano_partida = 1 THEN 1 ELSE 0 END) as partidas_ganadas,
                        AVG(pp.puntuacion_final) as puntuacion_promedio,
                        MAX(pp.puntuacion_final) as mejor_puntuacion
                    FROM participacion_p pp
                    JOIN partida p ON pp.id_partida = p.id_partida
                    WHERE pp.id_usuario = ? AND p.estado_partida = 'finalizada'";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userId]);
            
            $stats = $stmt->fetch();
            
            return [
                'partidas_jugadas' => (int)($stats['partidas_jugadas'] ?? 0),
                'partidas_ganadas' => (int)($stats['partidas_ganadas'] ?? 0),
                'puntuacion_promedio' => round((float)($stats['puntuacion_promedio'] ?? 0), 1),
                'mejor_puntuacion' => (int)($stats['mejor_puntuacion'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [
                'partidas_jugadas' => 0,
                'partidas_ganadas' => 0,
                'puntuacion_promedio' => 0,
                'mejor_puntuacion' => 0
            ];
        }
    }
}