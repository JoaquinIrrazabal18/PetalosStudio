<?php
/**
 * app/controllers/HistorialController.php
 *
 * Controlador para gestionar historial de partidas.
 * Conecta con Historial.php (modelo) y devuelve JSON.
 */

require_once __DIR__ . '/../models/Historial.php';

class HistorialController
{
    private $historialModel;

    public function __construct()
    {
        $this->historialModel = new Historial();
    }

    /**
     * Listar historial de una partida
     * GET: ?id_partida=1
     */
    public function listar(int $idPartida): void
    {
        try {
            $data = $this->historialModel->listarPorPartida($idPartida);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al listar historial: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Agregar registro al historial
     * POST: { id_partida, id_usuario, accion, dado, meta }
     */
    public function agregar(array $input): void
    {
        try {
            $data = [
                'id_partida' => (int)$input['id_partida'],
                'id_usuario' => (int)$input['id_usuario'],
                'accion'     => trim($input['accion']),
                'dado'       => $input['dado'] ?? null,
                'meta'       => $input['meta'] ?? []
            ];

            $result = $this->historialModel->agregar($data);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al agregar historial: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar registro del historial
     * POST: { id_historial }
     */
    public function eliminar(int $idHistorial): void
    {
        try {
            $result = $this->historialModel->eliminar($idHistorial);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar historial: ' . $e->getMessage()
            ]);
        }
    }
}
