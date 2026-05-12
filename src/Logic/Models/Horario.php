<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

// RF 21: Modelo de Horarios de Atención
// Define días y horas disponibles por especialidad, y asigna horarios a médicos
// Usado para verificar disponibilidad antes de permitir solicitar turno
class Horario {

    public static function listarTodos() {
        $conn = Database::getConnection();
        $query = "SELECT * FROM horario ORDER BY 
                    FIELD(dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'),
                    hora_inicio";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function crear($dia_semana, $hora_inicio, $hora_fin) {
        $conn = Database::getConnection();
        $query = "INSERT INTO horario (dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        return $stmt->execute([$dia_semana, $hora_inicio, $hora_fin]);
    }

    // Eliminar horario (también limpia asignaciones en personal_horario)
    public static function eliminar($id) {
        $conn = Database::getConnection();
        $conn->prepare("DELETE FROM personal_horario WHERE id_horario = ?")->execute([$id]);
        $stmt = $conn->prepare("DELETE FROM horario WHERE id_horario = ?");
        return $stmt->execute([$id]);
    }

    public static function obtenerPorId($id) {
        $conn = Database::getConnection();
        $query = "SELECT * FROM horario WHERE id_horario = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Listar horarios asignados a un médico específico
    public static function listarPorPersonal($id_personal) {
        $conn = Database::getConnection();
        $query = "SELECT h.* FROM horario h
                  INNER JOIN personal_horario ph ON h.id_horario = ph.id_horario
                  WHERE ph.id_personal = ?
                  ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'), h.hora_inicio";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_personal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Asignar horario a un médico (evita duplicados)
    public static function asignarAPersonal($id_personal, $id_horario) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM personal_horario WHERE id_personal = ? AND id_horario = ?");
        $stmt->execute([$id_personal, $id_horario]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
            return false;
        }
        $stmt = $conn->prepare("INSERT INTO personal_horario (id_personal, id_horario) VALUES (?, ?)");
        return $stmt->execute([$id_personal, $id_horario]);
    }

    // Desasignar horario de un médico
    public static function desasignarDePersonal($id_personal, $id_horario) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM personal_horario WHERE id_personal = ? AND id_horario = ?");
        return $stmt->execute([$id_personal, $id_horario]);
    }

    // RF 21: Verificar si hay atención disponible hoy para una especialidad
    // Compara día actual y hora actual contra horarios del médico de esa especialidad
    public static function verificarDisponibilidad($id_especialidad) {
        $conn = Database::getConnection();
        $dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes', 
                 'Wednesday' => 'Miercoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado'];
        $diaHoy = $dias[date('l')];
        $horaActual = date('H:i:s');

        $query = "SELECT COUNT(*) as total FROM horario h
                  INNER JOIN personal_horario ph ON h.id_horario = ph.id_horario
                  INNER JOIN personal p ON ph.id_personal = p.id_personal
                  WHERE p.id_especialidad = ? AND h.dia_semana = ? 
                  AND h.hora_inicio <= ? AND h.hora_fin >= ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_especialidad, $diaHoy, $horaActual, $horaActual]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    }

    // Obtener horarios de una especialidad (para mostrar mensajes de error)
    public static function horariosParaEspecialidad($id_especialidad) {
        $conn = Database::getConnection();
        $query = "SELECT DISTINCT h.dia_semana, h.hora_inicio, h.hora_fin
                  FROM horario h
                  INNER JOIN personal_horario ph ON h.id_horario = ph.id_horario
                  INNER JOIN personal p ON ph.id_personal = p.id_personal
                  WHERE p.id_especialidad = ?
                  ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'), h.hora_inicio";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_especialidad]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Listar todo el personal médico (para asignación de horarios)
    public static function personalConHorarios() {
        $conn = Database::getConnection();
        $query = "SELECT p.id_personal, per.nombre, per.apellido, e.nombre_especialidad
                  FROM personal p
                  INNER JOIN persona per ON p.id_persona = per.id_persona
                  INNER JOIN especialidad e ON p.id_especialidad = e.id_especialidad
                  ORDER BY per.apellido, per.nombre";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
