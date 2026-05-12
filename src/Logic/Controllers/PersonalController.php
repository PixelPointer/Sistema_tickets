<?php
require_once dirname(__DIR__) . '/Models/Personal.php';
require_once dirname(__DIR__) . '/Models/Usuario.php';

// Controlador de Personal Médico - CRUD de médicos en el sistema
class PersonalController {

    public function listPersonal($limit = 0, $offset = 0) {
        return Personal::listarTodos($limit, $offset);
    }

    public function contarPersonal() {
        return Personal::contarTodos();
    }

    // Buscar registro de personal por ID de persona
    public function getPersonalByPersonaId($id_persona) {
        $lista = Personal::listarTodos();
        foreach ($lista as $p) {
            if ($p['id_persona'] == $id_persona) {
                return $p;
            }
        }
        return null;
    }

    // Listar usuarios con rol médico que aún no están registrados en personal
    public function listMedicosSinPersonal() {
        $conn = Database::getConnection();
        $query = "SELECT u.id_usuario, u.id_persona, per.nombre, per.apellido, per.documento
                  FROM usuario u
                  INNER JOIN persona per ON u.id_persona = per.id_persona
                  WHERE u.id_rol = 2 AND u.id_persona NOT IN (SELECT id_persona FROM personal)
                  ORDER BY per.apellido, per.nombre";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Agregar médico a personal con especialidad y matrícula
    public function crearPersonal($id_persona, $id_especialidad, $matricula) {
        if (empty($id_persona) || empty($id_especialidad) || empty($matricula)) {
            return ['success' => false, 'message' => 'Todos los campos son requeridos'];
        }
        
        $existe = Personal::buscarPorPersona($id_persona);
        if ($existe) {
            return ['success' => false, 'message' => 'Esta persona ya está registrada como personal'];
        }
        
        if (Personal::crear($id_persona, $id_especialidad, $matricula)) {
            return ['success' => true, 'message' => 'Personal médico agregado correctamente'];
        }
        return ['success' => false, 'message' => 'Error al agregar el personal'];
    }

    public function eliminarPersonal($id) {
        if (Personal::eliminar($id)) {
            return ['success' => true, 'message' => 'Personal eliminado'];
        }
        return ['success' => false, 'message' => 'Error al eliminar'];
    }
}