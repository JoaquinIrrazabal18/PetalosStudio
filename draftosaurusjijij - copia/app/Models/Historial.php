<?php
/**
 * app/models/Historial.php
 *
 * Modelo para interactuar con la tabla historial.
 */

require_once __DIR__ . '/../config/Database.php';

class Historial
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Listar historial por partida
     */
    public function listarPorPartida(int $idPartida): array
    {
        $sql = "SELECT h.id_historial, h.id_partida, h.id_usuario, u.nombre AS usuario,
                       h.accion, h.dado, h.meta_json, h.fecha
                FROM historial h
                JOIN usuario u ON h.id_usuario = u.id_usuario
                WHERE h.id_partida = ?
                ORDER BY h.fecha ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idPartida]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agregar acción al historial
     * $data = [
     *   'id_partida' => 1,
     *   'id_usuario' => 2,
     *   'accion'     => 'Colocó dinosaurio en Bosque',
     *   'dado'       => 'Bosque',
     *   'meta'       => ['recinto' => 'bosque', 'especie' => 't_rex']
     * ]
     */
    public function agregar(array $data): array
    {
        $sql = "INSERT INTO historial (id_partida, id_usuario, accion, dado, meta_json)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $ok = $stmt->execute([
            $data['id_partida'],
            $data['id_usuario'],
            $data['accion'],
            $data['dado'],
            json_encode($data['meta'], JSON_UNESCAPED_UNICODE)
        ]);

        if ($ok) {
            return ['success' => true, 'id_historial' => $this->conn->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Error al guardar historial'];
        }
    }

    /**
     * Eliminar acción del historial
     */
    public function eliminar(int $idHistorial): array
    {
        $sql = "DELETE FROM historial WHERE id_historial = ?";
        $stmt = $this->conn->prepare($sql);
        $ok = $stmt->execute([$idHistorial]);

        return $ok
            ? ['success' => true]
            : ['success' => false, 'message' => 'Error al eliminar historial'];
    }
}
