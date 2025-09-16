<?php
/**
 * app/config/Database.php
 * Clase Singleton para conexión a la base de datos - CORREGIDA
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuración de la base de datos
    private $host = '127.0.0.1';
    private $database = 'draftosaurus';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    /**
     * Constructor privado para evitar instanciación directa
     */
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            // Error más detallado para debug
            error_log("Database connection failed: " . $e->getMessage());
            
            // En desarrollo, mostrar el error real
            if (defined('DEBUG_MODE') || $_SERVER['SERVER_NAME'] === 'localhost') {
                throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
            } else {
                throw new Exception("Error de conexión a la base de datos. Contacta al administrador.");
            }
        }
    }

    /**
     * Obtener la instancia única de la clase (Singleton)
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtener la conexión PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    // ... resto de métodos igual que antes
    
    public function executeQuery(string $sql, array $params = []): bool {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Error al ejecutar la consulta");
        }
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Fetch one failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Error al obtener el registro");
        }
    }

    public function fetchAll(string $sql, array $params = []): array {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Fetch all failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Error al obtener los registros");
        }
    }

    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool {
        return $this->connection->commit();
    }

    public function rollBack(): bool {
        return $this->connection->rollBack();
    }

    public function __clone() {
        throw new Exception("No se puede clonar una instancia Singleton");
    }

    public function __wakeup() {
        throw new Exception("No se puede deserializar una instancia Singleton");
    }

    public function __destruct() {
        $this->connection = null;
    }
}

/**
 * Helper class para respuestas JSON
 */
class JsonResponse {
    public static function success($data = null, string $message = 'Operación exitosa'): void {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message = 'Error en la operación', int $code = 400): void {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function validation(array $errors): void {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Helper class para validaciones
 */
class ValidationHelper {
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validatePassword(string $password): bool {
        return is_string($password) && strlen($password) >= 6;
    }

    public static function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function validateAge(int $age): bool {
        return $age >= 8 && $age <= 120;
    }

    public static function validateEnum(string $value, array $allowedValues): bool {
        return in_array($value, $allowedValues, true);
    }

    public static function validateRequired(array $data, array $requiredFields): array {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "El campo {$field} es obligatorio";
            }
        }
        return $errors;
    }
}

// Definir constante de debug
define('DEBUG_MODE', true);
?>