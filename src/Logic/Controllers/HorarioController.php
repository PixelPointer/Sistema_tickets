<?php
require_once dirname(__DIR__) . '/Models/Horario.php';
require_once dirname(__DIR__) . '/Models/Personal.php';

class HorarioController {

    public function listarHorarios() {
        return Horario::listarTodos();
    }

    public function listarPersonal() {
        return Horario::personalConHorarios();
    }

    public function horariosPorPersonal($id_personal) {
        return Horario::listarPorPersonal($id_personal);
    }

    public function crearHorario($dia_semana, $hora_inicio, $hora_fin) {
        if (empty($dia_semana) || empty($hora_inicio) || empty($hora_fin)) {
            return ['success' => false, 'message' => 'Todos los campos son requeridos'];
        }
        if ($hora_inicio >= $hora_fin) {
            return ['success' => false, 'message' => 'La hora de inicio debe ser menor a la hora de fin'];
        }
        if (Horario::crear($dia_semana, $hora_inicio, $hora_fin)) {
            return ['success' => true, 'message' => 'Horario creado correctamente'];
        }
        return ['success' => false, 'message' => 'Error al crear horario'];
    }

    public function eliminarHorario($id) {
        if (Horario::eliminar($id)) {
            return ['success' => true, 'message' => 'Horario eliminado'];
        }
        return ['success' => false, 'message' => 'Error al eliminar'];
    }

    public function asignarHorario($id_personal, $id_horario) {
        if (empty($id_personal) || empty($id_horario)) {
            return ['success' => false, 'message' => 'Seleccione personal y horario'];
        }
        if (Horario::asignarAPersonal($id_personal, $id_horario)) {
            return ['success' => true, 'message' => 'Horario asignado correctamente'];
        }
        return ['success' => false, 'message' => 'El horario ya está asignado a este médico'];
    }

    public function desasignarHorario($id_personal, $id_horario) {
        if (Horario::desasignarDePersonal($id_personal, $id_horario)) {
            return ['success' => true, 'message' => 'Horario desasignado'];
        }
        return ['success' => false, 'message' => 'Error al desasignar'];
    }
}
